<?php

# Class to handle importing of MODES data to the database
class import
{
	# Constructor
	public function __construct ($settings, $databaseConnection, $baseUrl, $applicationRoot)
	{
		# Resource handles
		$this->settings = $settings;
		$this->databaseConnection = $databaseConnection;
		$this->baseUrl = $baseUrl;
		$this->applicationRoot = $applicationRoot;
		
		# Load required libraries
		require_once ('xml.php');
		require_once ('csv.php');
		
	}
	
	
	
	# Main entry point
	# Needs privileges: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
	public function run ($modesXmlExportFiles, $type_ignored, &$html)
	{
		# Start the HTML
		$html = '';
		
		# Determine the grouping; examples: 'museum', 'picturelibrary', etc.
		$grouping = array_key_first ($modesXmlExportFiles);
		
		# Determine the import file; e.g. /path/to/modes-catalogue-api/exports/museum20180801.xml
		$modesXmlExportFile = $modesXmlExportFiles[$grouping];
		
		# Get current tables
		$tables = $this->databaseConnection->getTables ($this->settings['database']);
		
		# Determine the table to use; if a grouping-specific table is present (e.g. biographies), use that, otherwise use the generic records table
		$isGroupingSpecific = (in_array ($grouping, $tables));	// Only 'biographies' and 'expeditions'
		$table = ($isGroupingSpecific ? $grouping : $this->settings['table']);
		
		# Archive off the previous data (if not already done on the current day)
		$this->databaseConnection->archiveTable ($table, $tables);
		
		# Obtain the XPath definitions
		$xPathsByGroup = $this->loadXPathDefinitions ();
		$xPaths = $xPathsByGroup[$grouping];
		
		# xPath configuration; those which are pulled out (as listed below) are either used in indexing lists or checked when searching
		# https://msdn.microsoft.com/en-us/library/ms256086.aspx is a useful resource
		# http://www.xpathtester.com/ is a useful resource for testing xpaths
		# Good XPath tutorial and tester at www.zvon.org/xxl/XPathTutorial/General/examples.html
		// Status,Administration/Progress/Keyword was the original but <Progress><Keyword><Keyword> is being found
		// Status,(Administration/Progress//Keyword)[last()]
		
		#!# DATA CLEANUP: Truncate long introductory text
		// Title should be as follows when collection level records fixed
		// Title,Identification/Title
		// Title,(Identification//Title)[last()]
		
		# Delete records of this grouping from the table first
		$this->databaseConnection->delete ($this->settings['database'], $table, array ('grouping' => $grouping));
		
		#!# Migrate callback to using PHP 5.3 callback system: https://stackoverflow.com/a/3409450
		
		# Parse the XML records
		#!# multiplesDelimiter is being compounded, e.g. ||||||||||||||KAM|||||||||||||| gets extra | either side during import each time
		$records = xml::recordParser (
			$modesXmlExportFile,
			$xpathRecordsRoot = '/Interchange/Object',
			$recordIdPath = 'ObjectIdentity/Number',
			$xPaths,
			$this->settings['multiplesDelimiter'],
			true,	/* Default */
			true,	/* documentToDataOrientatedXml - Default */
			300,	/* Default */
			$filter = (isSet ($this->settings['filter'][$grouping]) ? $this->settings['filter'][$grouping] : false)
		);
		
		# Insert the data, converting an INSERT to an UPDATE when the id exists already
		#!# Not clear why ON DUPLICATE KEY UPDATE should be necessary, given the delete() clearance above
		foreach ($records as $key => $record) {
			if (!$this->databaseConnection->insert ($this->settings['database'], $table, $record, true)) {
				$html .= "\n<p class=\"warning\">ERROR: There was a problem inserting the record into the database. MySQL said:</p>";
				$html .= application::dumpData ($this->databaseConnection->error (), false, $return = true);
				return false;
			}
		}
		
		# Count records inserted
		$recordsDone = count ($records);
		
		# Add the grouping
		#!# Ideally this would work using an XPath "string('{$grouping}')" but that seems not to work
		#!# Race condition problem if two imports for different groupings run concurrently
		$query = "UPDATE {$this->settings['database']}.{$table} SET `grouping` = '{$grouping}' WHERE `grouping` IS NULL;";
		$this->databaseConnection->query ($query);
		
		# Update the table comment to store the data date
		$tableComment = "Records (snapshot date: ?)";
		if (preg_match ('/20([0-9]{6})/', $modesXmlExportFile, $matches)) {		// Takes the export file in use; there may be a mix of data in the records
			$date = $matches[1];
			$tableComment = "Records (snapshot date: {$date})";
		}
		$result = $this->databaseConnection->setTableComment ($this->settings['database'], $table, $tableComment);
		
		# Perform fixups for the main record grouping
		if ($isGroupingSpecific) {
			$this->importBiographiesExpeditionsFixups ($grouping, $table);
		} else {
			$this->importCollectionLevelRecords ($grouping);
			$this->importMainRecordsFixups ($grouping);
		}
		
		# Confirm the result
		if ($recordsDone) {
			$html .= "\n<p>{$this->tick} <strong>Success: " . number_format ($recordsDone) . ' records refreshed / imported into the database.</strong></p>';
			$html .= "\n<p>Max memory used: " . round (memory_get_peak_usage (true) / 1048576, 2) . 'MB.</p>';
			$html .= "\n<p><a href=\"{$this->baseUrl}/import/\">Reset this page.</a></p>";
		}
		
		# Signal success
		return true;
	}
	
	
	# Function to load XPath definitions
	private function loadXPathDefinitions ()
	{
		# Load the XPaths
		$xPathsCsv = csv::getData ($this->applicationRoot . '/xpaths.csv', $stripKey = false, $hasNoKeys = true, false, $skipCommentLines = true);
		
		# Group by grouping
		$xPaths = array ();
		foreach ($xPathsCsv as $index => $line) {
			$groupings = explode (';', $line['groupings']);		// e.g. museum;art or museum
			foreach ($groupings as $grouping) {
				if ($line['xpath'] == 'NULL') {continue;}	// Skip fields marked as 'NULL', i.e. do not apply to this kind of record
				$xPaths[$grouping][ $line['field'] ] = $line['xpath'];
			}
		}
		
		# Return the definitions
		return $xPaths;
	}
	
	
	# Static version of the effect of the materialsSplitter; ideally this would not be required, but materialsSplitter() contains $this-> items
	# This is the CALLBACK version, i.e. used by xml::databaseChunking and is used for the SQL field materials
	public static function materialsSplitterStatic ($materialsObjectsArray)
	{
		# Turn each material group into either "a > b" or a
		$materials = array ();
		foreach ($materialsObjectsArray as $index => $materialsObject) {
			// This next line doesn't work: for some reason a load of extra suddenly appears
			//$result = $materialsObject->xpath ('//Keyword[not(Keyword)]');	// i.e. Lowest level 'Keyword' instances
			// if ($result = $materialsObject->xpath ('//Note')) {continue;}	// Refuse to process if there is a note field (i.e. unclean records)
			
			# Cast object as array
			$materialsArray = (array) $materialsObject;
			
			# Skip if no materials
			if (!$materialsArray || !isSet ($materialsArray['Keyword']) || empty ($materialsArray['Keyword'])) {continue;}
			
			# Add to the list
			#!# DATA CLEANUP: Once the data doesn't have any cases of Note within Keyword, replace this block with $materials[] = (is_array ($materialsArray['Keyword']) ? implode (' > ', $materialsArray['Keyword']) : $materialsArray['Keyword']);
			if (is_string ($materialsArray['Keyword'])) {
				$materials[] = $materialsArray['Keyword'];
			} else {
				$items = array ();
				foreach ($materialsArray['Keyword'] as $material) {
					if (is_string ($material)) {	// This should ensure that nested objects (due to Keyword/Note data problem) are ignored
						$items[] = $material;
					}
				}
				if ($items) {
					$materials[] = implode (' > ', $items);
				}
			}
		}
		
		# End if no items
		if (!$materials) {
			return '';
		}
		
		# Combine each material group with ||
		$string = '|' . implode ('||', $materials) . '|';
		
		# Return the assembled string
		return $string;
	}
	
	
	# Function to perform fixups for the biographies / expeditions records
	private function importBiographiesExpeditionsFixups ($grouping, $table)
	{
		# Set the ID to the bracketed part of the name, lower-cased; see: https://stackoverflow.com/questions/8072402/looking-to-extract-data-between-parentheses-in-a-string-via-mysql
		$query = "UPDATE {$this->settings['database']}.{$table} SET id = LOWER( SUBSTR(name,INSTR(name,'(') + 1, INSTR(name,')') - INSTR(name,'(') - 1) );";
		$this->databaseConnection->query ($query);
		
		/*
		# Set the collection value
		#!# This needs to come from the database
		$query = "UPDATE {$this->settings['database']}.{$table} SET collection = 'vsii';";
		$this->databaseConnection->query ($query);
		*/
	}
	
	
	# Function to handle the creation of the collections table from the Collection-level records
	private function importCollectionLevelRecords ($grouping)
	{
		# Clear out any present entries from a previous import
		$constraints = array ('source' => 'modes', 'grouping' => $grouping);
		$this->databaseConnection->delete ($this->settings['database'], 'collections', $constraints);
		
		# Add collections-level entries for this grouping into the collections table
		#!# Status=R: There are 6 different /Administration/Progress/Keyword currently A,B,H,P,R,Y - we currently use R and P; the others need to be documented; Collections are aware of this as of 180524
		$query = "INSERT INTO {$this->settings['database']}.collections
			(
				SELECT
					REPLACE (LOWER (id), ' ', '') AS id,
					id AS collection,
					'modes' AS source,
					`grouping`,
					NULL AS suppressed,
					Title AS title,
					id AS abbreviation,
					BriefDescription AS introductoryTextBrief,
					Description AS introductoryText,
					NULL AS aboutPageHtml,
					NULL AS aboutPageTabText,
					NULL AS contactsPageHtml,
					NULL AS contactsPageEmail,
					NULL AS sponsorNotice,
					NULL AS categoriesTable,
					1 AS disableCategories,
					1 AS disableMaterials,
					1 AS disableArtists,
					PhotographFilename AS imagesSubfolder
				FROM {$this->settings['database']}.{$this->settings['table']}
				WHERE
					    Type = 'collection'
					AND (`Status` != 'R' OR `Status` IS NULL)
					AND `grouping` = '{$grouping}'
				ORDER BY title
			)
		;";
		if (!$this->databaseConnection->query ($query)) {
			application::dumpData ($this->databaseConnection->error ());
		}
		
		# Delete from the main records table the collection-level entries (irrespective of their Status value)
		$constraints = array ('Type' => 'collection');
		$this->databaseConnection->delete ($this->settings['database'], $this->settings['table'], $constraints);
		
		# Add collections defined manually; these should be added upstream in the source data and removed from this file as they are fixed
		$this->addManualCollections ();
		
		# Convert imagesSubfolder paths from Windows to Unix: prepend the path, convert to forward-slashes, chop off the Windows equivalent of the path, and add the thumbnails directory
		$this->databaseConnection->query ("UPDATE {$this->settings['database']}.collections SET imagesSubfolder = REPLACE (REPLACE (CONCAT (imagesSubfolder, '/'), '\\\\', '/'), 'X:/spripictures/', '/thumbnails/');");
		
		# Apply other fixes to data; these should be fixed upstream in the source data and removed from this file as they are fixed
		$this->collectionsFixes ();
	}
	
	
	# Function to load collections defined manually
	private function addManualCollections ()
	{
		# Load the fixes data
		$fixes = csv::getData ($this->applicationRoot . '/legacy/collections-manual.csv', $stripKey = false, false, false, $skipCommentLines = true);
		
		# Clear out any present entries from a previous import
		$this->databaseConnection->delete ($this->settings['database'], 'collections', array ('source' => 'manual'));
		
		# Insert data
		foreach ($fixes as $fix) {
			$this->databaseConnection->insert ($this->settings['database'], 'collections', $fix);
		}
	}
	
	
	# Function to apply collections fixes
	private function collectionsFixes ()
	{
		# Load the fixes data
		$fixes = csv::getData ($this->applicationRoot . '/legacy/collections-fixes.csv', $stripKey = false, $hasNoKeys = true, false, $skipCommentLines = true);
		
		# Apply the fixes
		foreach ($fixes as $fix) {
			$preparedStatementValues = application::arrayFields ($fix, array ('collectionid', 'newvalue'));
			$this->databaseConnection->query ("UPDATE {$this->settings['database']}.collections SET {$fix['field']} = :newvalue WHERE id = :collectionid;", $preparedStatementValues);
		}
	}
	
	
	# Function to perform fixups for the main record grouping
	private function importMainRecordsFixups ($grouping)
	{
		# Normalise the Collection column such that all collections are surrounded by |...| even if only one
		$query = "UPDATE {$this->settings['database']}.{$this->settings['table']} SET Collection = CONCAT('{$this->settings['multiplesDelimiter']}', Collection, '{$this->settings['multiplesDelimiter']}') WHERE Collection IS NOT NULL;";
		$this->databaseConnection->query ($query);
		
		# Create a title for museum records which have no actual title (i.e. things that aren't artistic), using the ObjectType as the nearest equivalent
		$query = "UPDATE {$this->settings['database']}.{$this->settings['table']} SET Title = ObjectType WHERE Title IS NULL AND `grouping` = 'museum';";
		$this->databaseConnection->query ($query);
		
		# Create two indexes for the ID, splitting into prefix and suffix, for sortability reasons; see https://lists.mysql.com/mysql/213354
		#!# Casting as INT via silent discarding is not necessarily reliable
		$query = "UPDATE
			{$this->settings['database']}.{$this->settings['table']}
			SET
				/* VARCHAR field */							id_prefix = SUBSTRING_INDEX(id, ' ', 1),
				/* INT field, so a-b will be discarded */	id_suffix = REPLACE(SUBSTRING(SUBSTRING_INDEX(id, ' ', 2), LENGTH(SUBSTRING_INDEX(id, ' ', 2 -1)) + 1), ' ', '')	/* See: https://blog.fedecarg.com/2009/02/22/mysql-split-string-function/ */
			;";
		$this->databaseConnection->query ($query);
		
		# Delete empty images
		$query = "UPDATE {$this->settings['database']}.{$this->settings['table']} SET PhotographFilename = NULL WHERE PhotographFilename IN('.tif', '-master.tif') AND `grouping` = '{$grouping}';";
		$this->databaseConnection->query ($query);
	}
}

?>