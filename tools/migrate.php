<?php
////////////////////////////////////////////////////////////////////////////
// COURSE MIGRATOR
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/migrate.php
// @descrip	A stand alone tool for migrating flat-file course listings.
////////////////////////////////////////////////////////////////////////////

// CONFIGURATION DATA
$MIGRATEFOLDER = "/home/web/schedulemigrate/";
$IGNOREFILES   = array("..", ".");

// Bring in the database connection.
require "../inc/databaseConn.php";
global $dbConn;

// Now load up the list of courses to migrate
$files = scandir($MIGRATEFOLDER);
$files = array_diff($files, $IGNOREFILES);

// Iterate over the files in the migration folder
foreach($files as $file) {
	// If it's a directory, then we'll go D E E P E R
	if(is_dir($MIGRATEFOLDER . $file)) {
		// I N C E P T I O N
		
		echo "... Found directory for $file quarter\n";

		// Scan the directory and start parsing the files
		$subfolder = array_diff(scandir($MIGRATEFOLDER . $file), $IGNOREFILES);
		foreach($subfolder as $subfile) {
			$quarter = $subfolder;

			echo "    ... Found quarter $file courses for department $quarter\n";
			
			// Now open the file to expose it's goodness
			$fhandle = fopen($MIGRATEFOLDER . $file . '/' . $subfile, 'r');
			
			while($line = fgets($fhandle, 2048)) {
				// Split the line into useful data
				$lineSplit = explode("::", $line);

				foreach($lineSplit as $k => $word) {
					$lineSplit[$k] = mysql_real_escape_string(trim($word));
				}
				
				if(count($lineSplit) != 17) {
					echo "       *** Malformed line {$line}\n";
					continue;
				}

				////////////////////////////////////////
				// HOW IT'S SET UP.
				// 	Some people are stupid.
				// 	label::value
				
				// Iterate and try to resolve the labels
				$label = null;
				foreach($lineSplit as $word) {
					if(in_array($word, array("section", "title", "days", "status", "max", "instructor", "current", "id"))) {
						$label = $word;
						continue;
					} else {
						switch($label) {
							case null:							// The full department-course-section number
								$courseSplit = explode('-', $word);
								$department = $courseSplit[0];
								$course     = $courseSplit[1];
								$section    = $courseSplit[2];
								break;
							case "title":						// The course title
								$title = $word;
								break;
							case "days":						// The times serialized string
								$times = explode(',',$word);
								break;
							case "max":							// The maximum enrollment
								$maxenroll = $word;
								break;
							case "instructor":					// The instructor's name
								$instructor = $word;
								break;
							case "current":						// The current enrollment
								$curenroll = $word;
								break;
							case "status":						// Skipping
							case "section":						// Skipping
							case "id":							// Skipping
							default:
								break;
						}
					}
				}

				echo "        ... Found {$title}({$department}-{$course}-{$section})\n";
				
				// Build a query for the section and the course
				$query = "INSERT INTO courses (department, course, quarter, title) ";
				$query .= "VALUES('{$department}', '{$course}', '{$quarter}', '{$title}')";
				$result = mysql_query($query);
				if(!$result) {
					echo("        *** MySQL Error: " . mysql_error() . "\n");
				}
				$courseID = mysql_insert_id();

				$query = "INSERT INTO sections (course, section, instructor, maxenroll, curenroll) ";
				$query .= "VALUES('{$courseID}', '{$section}', '{$instructor}', '{$maxenroll}', '{$curenroll}')";
				$result = mysql_query($query);
				if(!$result) {
					echo("        *** MySQL Error: " . mysql_error() . "\n");
				}
				$sectionID = mysql_insert_id();		

				// Now insert the times
				foreach($times as $time) {
					$timeSplit = explode('-', $time);

					// Convert the days into numbers
					switch($timeSplit[0]) {
						case 'M':
							$day = 1;
							break;
						case 'T':
							$day = 2;
							break;
						case 'W':
							$day = 3;
							break;
						case 'R':
							$day = 4;
							break;
						case 'F':
							$day = 5;
							break;
						case 'S':
							$day = 6;
							break;
						default:
							$day = 0;
							break;
					}
					
					// Process the start and end times
					$startSplit = explode('.', $timeSplit[3]);
					$endSplit   = explode('.', $timeSplit[4]);
					$startTime = $startSplit[1] . $startSplit[2];
					$endTime   = $endSplit[1] . $endSplit[2];

					// Process the location
					$bldg = $timeSplit[1];
					$room = $timeSplit[2];

					// Build and execute a query
					$query = "INSERT INTO times (section, day, start, end, building, room) ";
					$query .= "VALUES('{$sectionID}', '{$day}', '{$startTime}', '{$endTime}', '{$bldg}', '{$room}')";
					$result = mysql_query($query);
					if(!$result) {
						echo("        *** MySQL Error: " . mysql_error() . "\n");
					}
				}
			} 
			fclose($fhandle);
		}
	} else {
		$quarter = explode('-', $file);
		$quarter = $quarter[0];
		echo "... Found department index for $quarter\n"; 

		// It's the department index for the quarter. We /should/ only need to process one of these
		// Open up the file for reading
		$fhandle = fopen($MIGRATEFOLDER . $file, "r");

		// Read line-by-line
		while($line = fgets($fhandle, 2048)) {
			// Split it into the information we need
			$lineSplit = explode("::", $line);
			
			// Sanitize. For safety's sake
			foreach($lineSplit as $k => $word) {
				$lineSplit[$k] = mysql_real_escape_string(trim($word));
			}

			if(count($lineSplit) != 2) {
				echo "    *** Malformed line! {$line}\n";
				continue;
			} else {
				echo "    ... Adding dept: {$lineSplit[1]}({$lineSplit[0]})\n";
			}

			// Turn it into a correct string for insertion into the db
			$values = "('" . implode("', '", $lineSplit) . "')";

			// Do the query
			$query = "INSERT INTO departments (id, title) VALUES ";
			$query .= $values;
			$query .= " ON DUPLICATE KEY UPDATE title='{$lineSplit[1]}'";
			$result = mysql_query($query);
			if(!$result) {
				echo("    *** MySQL Error: " . mysql_error() . "\n");
			}
		}

		// Close the file
		fclose($fhandle);
	}
}
