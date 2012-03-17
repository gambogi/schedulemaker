<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULEMAKER - CourseDB Model
//
// @file	models/BrowseModel.php
// @descrip	This model provides an abstraction to the database for retrieving
//			schools, departments, courses, and sections.
// @author	Benjamin Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

class CourseDBModel extends Model {
	// MEMBER VARIABLES ////////////////////////////////////////////////////

	// METHODS /////////////////////////////////////////////////////////////
	public function getSchools() {
		// Build a query for the all the schools
		$query = "SELECT * FROM schools ORDER BY id";
		$result = $this->dbConn->query($query);
		
		// Iterate over the list of schools and generate a school object
		$schools = array();
		foreach($result as $row) {
			$schools[] = new School($row['id'], $row['title']);
		}		

		return $schools;
	}

	public function currentQuarter() {
		// Different quarters based on what month it is
		switch(date('n')) {
			case 2:
			case 3:
				$quarter = date("Y")-1 . '3'; // Point them to the spring
				break;
			case 4:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
				$quarter = date("Y") . '1'; // Point them to the fall
				break;
			case 10:
			case 11:
			case 12:
			case 1:
				$quarter = date("Y") . '2'; // Point them to the summer
				break;
		}

		// Now lookup that quarter
		return $this->getQuarter($quarter);
	}

	public function getQuarter($quarter = 'all') {
		if($quarter == 'all') {
			// Build a query that will lookup the quarters
			$query = "SELECT quarter AS id, start, end, breakstart, breakend FROM quarters";
			$result = $this->dbConn->query($query);
			if(!$result) {
				//@TODO: Error
			}
	
			// For each row, create a quarter object
			$quarters = array();
			foreach($result as $row) {
				$quarters[] = new Quarter(
					$row['id'], 
					$row['start'],
					$row['end'],
					$row['breakstart'],
					$row['breakend']
					);
			}
			
			return $quarters;
		} else {
			// Build a query to select the only quarter
			$query = "SELECT quarter AS id, start, end, breakstart, breakend FROM quarters WHERE quarter={$quarter}";
			$result = $this->dbConn->query($query);
			if(!$result || count($result) != 1) {
				// @TODO: Error
			}
			
			// Build and return the quarter
			$result = $result[0];
			return new Quarter(
				$result['id'],
				$result['start'],
				$result['end'],
				$result['breakstart'],
				$result['breakend']
				);
		}
	}

	public function getDepartments($school) {
		// Build a query for all the schools
		$query = "SELECT title, id FROM departments WHERE school={$this->dbConn->escape($school)} ORDER BY id";
		$result = $this->dbConn->query($query);

		// Iterate over the results and create department objects
		$departments = array();
		foreach($result as $row) {
			$departments[] = new Department($row['id'], $row['title']);
		}

		return $departments;
	}
} 
