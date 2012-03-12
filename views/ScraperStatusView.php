<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULEMAKER - Scraper Status Log View
// 
// @file	views/ScraperStatusView.php
// @descrip	This view will take in a Status Model and generate a table that
//			contains the last 20 scraper logs.
// @author	Benjamin Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

class ScraperStatusView extends View {

	// MEMBER VARIABLES ////////////////////////////////////////////////////
	// StatusModel	an instance of the status model that we can get data from
	private $model;

	// METHODS /////////////////////////////////////////////////////////////

	protected function timeElapsed($time) {
		// Initialize the return string
		$return = "";
		
		// Divide off days
		$days = floor($time / (60 * 60 * 24));
		if($days) {
			$return .= "{$days} days ";
			$time -= $days * 60 * 60 * 24;
		}

		// Divide off hours
		$hours = floor($time / (60 * 60));
		if($hours) {
			$return .= "{$hours}:";
			$time -= $hours * 60 * 60;
		} else {
			$return .= "00:";
		}

		// Divide off minutes
		$mins = floor($time / 60);
		if($mins) {
			$return .= "{$mins}:";
			$time -= $mins * 60;
		} else {
			$return .= "00:";
		}

		// Divide off seconds
		$return .= str_pad($time, 2, "0", STR_PAD_LEFT);

		return $return;
	}

	/**
	 * Creates a new ScraperStatusView instance. It requires a StatusModel
	 * @param	StatusModel	$model	StatusModel instance
	 */
	public function __construct($model) {
		// Verify that we have a StatusModel
		if(!($model instanceof StatusModel)) {
			// @TODO: Error
		}

		$this->model = $model;
	}
	
	public function render() {
		// Load the last 20 log entries from the scraper
		$logEntries = $this->model->getLastScrapeLogs(20);
	
		// Load parameters for output
		$params = array(
			"lastScrape" => (!count($logEntries)) ? "Never" : $logEntries[0]['timeStarted'],
			"logEntries" => $logEntries
			);

		// Load the HTML for the output
		$this->load("ScraperStatus", $params);
	}
}