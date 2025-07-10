<?php

# Class to handle reports
class reportsController
{
	# Properties
	private $settings;
	private $databaseConnection;
	private $baseUrl;
	private $reports;
	private $reportsList;
	private $listingsList;
	private $html;
	
	
	# Contructor to list the reports
	public function __construct ($modesCatalogueApi)
	{
		# Obtain class properties
		$this->settings = $modesCatalogueApi->settings;
		$this->databaseConnection = $modesCatalogueApi->databaseConnection;
		$this->baseUrl = $modesCatalogueApi->baseUrl;
		
		# Create a handle to the reports module
		$this->reports = new reports ($modesCatalogueApi);
		$this->reportsList = $this->reports->getReportsList ();
		$this->listingsList = $this->reports->getListingsList ();
		
		# Determine which reports are informational reports
		$this->reportStatuses = $this->getReportStatuses ();
	}
	
	
	# Function to determine the status of each report
	private function getReportStatuses ()
	{
		# Start a list of informational reports
		$reportStatuses = array ();
		
		# Start a registry of listings-type reports that implement a count
		$this->countableListings = array ();
		
		# Loop through each report and each listing, detecting the status, and rewriting the name
		$this->reportsList  = $this->parseReportNames ($this->reportsList , $reportStatuses, $this->countableListings);
		$this->listingsList = $this->parseReportNames ($this->listingsList, $reportStatuses, $this->countableListings);
		
		# Return the status list
		return $reportStatuses;
	}
	
	
	# Helper function to strip any flag from report key names
	private function parseReportNames ($reportsRaw, &$reportStatuses, &$countableListings)
	{
		# Loop through each report, detecting whether each report is informational, and rewriting the name
		$reports = array ();	// Array of report key names without flag appended
		foreach ($reportsRaw as $key => $value) {
			if (preg_match ('/^(.+)_(info|problem|problemok)(|_countable)$/', $key, $matches)) {
				$key = $matches[1];
				$reportStatuses[$key] = $matches[2];
				$reports[$key] = $value;	// Register under new name
				if ($matches[3]) {
					$countableListings[] = $key;
				}
			} else {
				$reportStatuses[$key] = NULL;	// Unknown status
			}
			$reports[$key] = $value;	// Recreated list, with any _info stripped
		}
		
		# Return the rewritten list
		return $reports;
	}
	
	
	# Main page generator
	public function createPage ($id)
	{
		# Start the HTML
		$html  = '';
		
		# If no specified report, create a listing of reports
		if (!$id) {
			
			# Compile the HTML
			$html .= "\n<h2>Reports</h2>";
			$html .= $this->reportsJumplist ();
			$html .= "\n<p>This page lists the various reports that check for data errors or provide an informational overview of aspects of the data.</p>";
			$html .= $this->reportsTable ();
			
			# Return the HTML and end
			$this->html = $html;
			return true;
		}
		
		# Ensure the report ID is valid
		if (!isSet ($this->reportsList[$id])) {
			$html .= "\n<h2>Reports</h2>";
			$html .= $this->reportsJumplist ($id);
			$html .= "\n<p>There is no such report <em>" . htmlspecialchars ($id) . "</em>. Please check the URL and try again.</p>";
			$this->html;
			return false;
		}
		
		# Show the title
		$html .= "\n<h2>Report: " . htmlspecialchars (ucfirst ($this->reportsList[$id])) . '</h2>';
		$html .= $this->reportsJumplist ($id);
		
		# View the report
		$html .= $this->viewResults ($id);
		
		# Register the HTML
		$this->html = $html;
	}
	
	
	# Getter for HTML
	public function getHtml ()
	{
		return $this->html;
	}
	
	
	# Function to create a reports list
	private function reportsTable ($filterStatus = false)
	{
		# Get the list of reports
		$reports = $this->getReports ();
		
		# Filter if required
		if ($filterStatus) {
			foreach ($reports as $report => $description) {
				if ($this->reportStatuses[$report] != $filterStatus) {
					unset ($reports[$report]);
				}
			}
		}
		
		# Create a key to show report types
		$reportTypes = array (
			'ok' => 'OK',
			'problem' => 'Problems',
			'info' => 'Informational',
		);
		$types = array ();
		foreach ($reportTypes as $type => $label) {
			$types[] = "<strong class=\"{$type}\">{$label}</strong>";
		}
		$keyHtml = "\n<p id=\"reportskey\">Key: &nbsp;" . implode (' &nbsp;', $types) . '</p>';
		
		# Get the counts
		$counts = $this->getCounts ();
		
		# Get the total number of records
		$stats = $this->getStats ();
		$totalRecords = $stats['totalRecords'];
		
		# Mark problem reports with no errors as OK
		foreach ($this->reportStatuses as $key => $status) {
			if ($status == 'problem' || $status == 'problemok') {
				if (array_key_exists ($key, $counts)) {
					if ($counts[$key] == 0 || $status == 'problemok') {
						$this->reportStatuses[$key] = 'ok';
					}
				}
			}
		}
		
		# Convert to an HTML list
		$table = array ();
		foreach ($reports as $report => $description) {
			$key = $report . ($this->reportStatuses[$report] ? ' ' . $this->reportStatuses[$report] : '');	// Add CSS class if status known
			$link = $this->reportLink ($report);
			$table[$key]['Description'] = "<a href=\"{$link}\">" . ucfirst (htmlspecialchars ($description)) . '</a>';
			$table[$key]['Problems?'] = (($this->isListing ($report) && !in_array ($report, $this->countableListings)) ? '<span class="faded right">n/a</span>' : ($counts[$report] ? '<span class="warning right">' . number_format ($counts[$report]) : '<span class="success right">' . 'None') . '</span>');
			$percentage = ($counts[$report] ? round (100 * ($counts[$report] / $totalRecords), 2) . '%' : '-');
			$table[$key]['%'] = ($this->isListing ($report) ? '<span class="faded right">n/a</span>' : '<span class="comment right">' . ($percentage === '0%' ? '0.01%' : $percentage) . '</span>');
		}
		
		# Compile the HTML
		$html  = $keyHtml;
		$html .= application::htmlTable ($table, array (), 'reports lines', $keyAsFirstColumn = false, false, $allowHtml = true, false, false, $addRowKeyClasses = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to determine if the specified report is a listing type
	public function isListing ($report)
	{
		return (array_key_exists ($report, $this->listingsList));
	}
	
	
	# Function to get stats data
	private function getStats ()
	{
		# Get the data and return it
		$count = $this->databaseConnection->getTotal ($this->settings['database'], $this->settings['table']);
		$data = array ('totalRecords' => $count);
		return $data;
	}
	
	
	# Function to get the counts
	private function getCounts ()
	{
		# Get the list of reports
		$reports = $this->getReports ();
		
		# Get the counts
		$query = "SELECT report, COUNT(*) AS total FROM reportresults GROUP BY report;";
		$data = $this->databaseConnection->getPairs ($query);
		
		# Ensure that each report type has a count
		$counts = array ();
		foreach ($reports as $id => $description) {
			$counts[$id] = (isSet ($data[$id]) ? $data[$id] : 0);
		}
		
		# Return the counts
		return $counts;
	}
	
	
	# Function to create a reports jumplist
	private function reportsJumplist ($current = false)
	{
		# Determine the front reports page link
		$frontpage = $this->reportLink ();
		
		# Get the counts
		$counts = $this->getCounts ();
		
		# Create the list
		$droplist = array ();
		$droplist[$frontpage] = '';
		foreach ($this->reportsList as $report => $description) {
			$link = $this->reportLink ($report);
			$description = (mb_strlen ($description) > 50 ? mb_substr ($description, 0, 50) . '...' : $description);	// Truncate
			$droplist[$link] = ucfirst ($description) . ($this->isListing ($report) ? '' : ' (' . number_format ($counts[$report]) . ')');
		}
		
		# Create a link to the selected item
		$selected = $this->reportLink ($current);
		
		# Compile the HTML and register a processor
		$html = application::htmlJumplist ($droplist, $selected, $this->baseUrl . '/', $name = 'reportsjumplist', $parentTabLevel = 0, $class = 'reportsjumplist', 'Switch to: ');
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to link a report
	private function reportLink ($report = false)
	{
		return $this->baseUrl . ($report != 'tests' ? '/reports/' : '/') . ($report ? htmlspecialchars ($report) . '/' : '');
	}
	
	
	# Function to get the list of reports
	public function getReports ()
	{
		# Ensure each report exists
		foreach ($this->reportsList as $report => $description) {
			$methodName = 'report_' . $report;
			if (!method_exists ($this->reports, $methodName)) {
				unset ($this->reportsList[$report]);
			}
		}
		
		# Return the list
		return $this->reportsList;
	}
	
	
	# Function to view results of a report
	private function viewResults ($id)
	{
		# Determine the description
		$description = 'This report shows ' . $this->reportsList[$id] . '.';
		
		# Start the HTML With the description
		$html  = "\n<div class=\"graybox\">";
		if (!$this->isListing ($id)) {
			$html .= "\n<p id=\"exportlink\" class=\"right\"><a href=\"{$this->baseUrl}/reports/{$id}/{$id}.csv\">Export as CSV</a></p>";
		}
		$html .= "\n<p><strong>" . htmlspecialchars ($description) . '</strong></p>';
		$html .= "\n</div>";
		
		# Show the records for this query (having regard to any page number supplied via the URL)
		if ($this->isListing ($id)) {
			$viewMethod = "report_{$id}_view";
			$html .= $this->reports->{$viewMethod} ();
		} else {
			$baseLink = '/reports/' . $id . '/';
			$html .= $this->recordListing ($id, false, array (), $baseLink, true);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a record listing based on a query, with pagination
	public function recordListing ($id, $query, $preparedStatementValues = array (), $baseLink, $listingIsProblemType = false, $queryString = false, $view = 'listing' /* listing/record/table */, $tableViewTable = false, $knownTotalAvailable = false, $entityName = 'record', $orderingControlsHtml = false)
	{
		# Assemble the query, determining whether to use $id or $query
		if ($id) {
			$query = "SELECT * FROM reportresults WHERE report = :id ORDER BY recordId;";
			$preparedStatementValues = array ('id' => $id);
		}
		
		# Enable a listing type switcher, if in a supported view mode, which can override the view
		$listingTypeSwitcherHtml = false;
		$switcherSupportedViewTypes = array ('listing', 'record');
		if (in_array ($view, $switcherSupportedViewTypes)) {
			$listingTypes = array (
				'listing'	=> 'application_view_columns',
				'record'	=> 'application_tile_vertical',
			);
			$view = application::preferenceSwitcher ($listingTypeSwitcherHtml, $listingTypes);
			
			# In record mode, use a separate pagination memory
			$this->settings['paginationRecordsPerPageDefault'] = 250;
			if ($view == 'record') {
				$this->settings['paginationRecordsPerPageDefault'] = 25;
				$this->settings['paginationRecordsPerPagePresets'] = array (5, 10, 25, 50, 100);
				$this->settings['cookieName'] = 'fullrecordsperpage';
			}
		}
		
		# Load the pagination class
		$pagination = new pagination ($this->settings, $this->baseUrl);
		
		# Determine what page
		$page = $pagination->currentPage ();
		
		# Create a form to set the number of pagination records per page
		$paginationRecordsPerPage = $pagination->recordsPerPageForm ($recordsPerPageFormHtml);
		
		# Get the data, via pagination
		list ($dataRaw, $totalAvailable, $totalPages, $page, $actualMatchesReachedMaximum) = $this->databaseConnection->getDataViaPagination ($query, $tableViewTable, true, $preparedStatementValues, array (), $paginationRecordsPerPage, $page, false, $knownTotalAvailable);
		// application::dumpData ($this->databaseConnection->error ());
		
		# Start the HTML for the record listing
		$html  = '';
		
		# Show the listing of problematic records, or report a clean record set
		if ($listingIsProblemType) {
			if (!$dataRaw) {
				$html .= "\n<p class=\"success\">{$this->tick}" . " All {$entityName}s are correct - congratulations!</p>";
				return $html;
			} else {
				$html .= "\n<p class=\"warning\">" . '<img src="/images/icons/exclamation.png" /> The following ' . (($totalAvailable == 1) ? "{$entityName} matches" : number_format ($totalAvailable) . " {$entityName}s match") . ':</p>';
			}
		} else {
			if (!$dataRaw) {
				$html .= "\n<p>There are no {$entityName}s.</p>";
			} else {
				$html .= "\n<p>" . ($totalAvailable == 1 ? "There is one {$entityName}" : 'There are ' . number_format ($totalAvailable) . " {$entityName}s") . ':</p>';
			}
		}
		
		# Add pagination links and controls
		$paginationLinks = '';
		if ($dataRaw) {
			//$html .= $listingTypeSwitcherHtml;
			$html .= $recordsPerPageFormHtml;
			$paginationLinks = pagination::paginationLinks ($page, $totalPages, $this->baseUrl . $baseLink, $queryString);
			$html .= $paginationLinks;
		}
		
		# Compile the listing
		$data = array ();
		if ($dataRaw) {
			switch (true) {
				
				# List mode
				case ($view == 'listing'):
					
					# List mode needs just id=>id format
					foreach ($dataRaw as $index => $record) {
						$recordId = $record['recordId'];
						$data[$recordId] = $recordId;
					}
					$html .= $this->recordList ($data);
					$html  = "\n<div class=\"graybox\">" . $html . "\n</div>";	// Surround with a box
					break;
					
				# Record mode
				case ($view == 'record'):
					
					# Record mode shows each record
					foreach ($dataRaw as $index => $record) {
						$recordId = $record['recordId'];
						$data[$recordId] = $this->recordFieldValueTable ($recordId, 'rawdata');
						$data[$recordId] = "\n<h3>Record <a href=\"{$this->baseUrl}/records/{$recordId}/\">#{$recordId}</a>:</h3>" . "\n<div class=\"graybox\">" . $data[$recordId] . "\n</div>";	// Surround with a box
					}
					$html .= implode ($data);
					break;
					
				# Table view
				case ($view == 'searchresults'):
					$data = $dataRaw;
					
					# Show ordering controls if required
					$html .= $orderingControlsHtml;
					
					# Render as a table
					// $html .= $this->recordList ($data, true);
					
					# Render as boxes
					$html .= "\n<div class=\"clearright\">";
					foreach ($data as $record) {
						$html .= "\n<div class=\"graybox\">";
						$html .= "\n<p class=\"right comment\">#{$record['id']}</p>";
						$html .= "\n<h4><a href=\"{$this->baseUrl}/records/{$record['id']}/\">{$record['id']}</a></h4>";
						// Implementation pending
						$html .= "\n</div>";
					}
					$html .= "\n</div>";
					
					// # Surround with a box
					// $html  = "\n<div class=\"graybox\">" . $html . "\n</div>";
					
					break;
					
				# Self-defined
				case (preg_match ('/^callback\(([^)]+)\)/', $view, $matches)):	// e.g. callback(foo) will run $this->reports->foo ($data);
					
					# Pass the data to the callback to generate the HTML
					$callbackMethod = $matches[1];
					$html .= $this->reports->$callbackMethod ($dataRaw);
					break;
			}
		}
		
		# Surround the listing with a div for clearance purposes
		$html = "\n<div class=\"listing\">" . $html . "\n</div>";
		
		# Add the pagination controls again at the end, for long pages
		if (($view != 'listing') || count ($dataRaw) > 50) {
			$html .= $paginationLinks;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to format a list of records as a hyperlinked list
	private function recordList ($records, $fullInfo = false)
	{
		# Table mode
		if ($fullInfo) {
			$headings = array ();
			$headings['recordId'] = '#';
			$html = application::htmlTable ($records, $headings, 'lines', $keyAsFirstColumn = false, $uppercaseHeadings = true, $allowHtml = true);
			
		# List mode
		} else {
			$list = array ();
			foreach ($records as $record => $label) {
				$list[$record] = str_replace (' ', '&nbsp;', htmlspecialchars ($label));
				$list[$record] = '<li>' . $list[$record] . '</li>';
			}
			$html = application::splitListItems ($list, 4);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to export a listing as a CSV file
	public function reportCsv ($id)
	{
		# Get the data
		$query = "
			SELECT
				recordId
			FROM reportresults
			WHERE report = :id
			ORDER BY recordId
		;";
		$data = $this->databaseConnection->getData ($query, false, true, array ('id' => $id));
		
		// application::dumpData ($data);
		// die;
		
		# Convert to CSV
		csv::serve ($data, $id);
	}
	
	
	# Function to run the reports
	public function generateData ()
	{
		# Clean out the report results table
		$this->databaseConnection->truncate ($this->settings['database'], 'reportresults', true);
		
		# Run each report and insert the results
		$reports = $this->getReports ();
		foreach ($reports as $reportId => $description) {
			
			# Skip listing type reports, which implement data handling directly (and optional countability support), and which are handled separately in runListings ()
			if ($this->isListing ($reportId)) {continue;}
			
			# Run the report
			$result = $this->runReport ($reportId);
			
			# Handle errors
			if ($result === false) {
				echo "<p class=\"warning\">Error generating report <em>{$reportId}</em>:</p>";
				echo application::dumpData ($this->databaseConnection->error (), false, true);
			}
		}
	}
	
	
	# Function to run a report
	private function runReport ($reportId)
	{
		# Assemble the query and insert the data
		$reportFunction = 'report_' . $reportId;
		$query = $this->reports->$reportFunction ();
		$query = "INSERT INTO reportresults (report,recordId)\n" . $query . ';';
		$result = $this->databaseConnection->query ($query);
		
		# Return the result
		return $result;
	}
	
	
	# Function to run the listing reports
	private function runListings ()
	{
		# Run each listing report
		$reports = $this->getReports ();
		foreach ($reports as $reportId => $description) {
			if ($this->isListing ($reportId)) {
				$reportFunction = 'report_' . $reportId;
				$this->reports->$reportFunction ();
			}
		}
	}
}

?>
