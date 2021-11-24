<?php

#!# Images on left side of search results may not be triggering the thumbnailer - check

#!# Somewhere in galleryHtmlFromArticleData is where the gallery image subfolder will need to be prepended when it comes from a configuration


/* 
TODO:
	Uppercase in URL still at: /temporary/pictures/catalogue/article/p51.8.A179/
	Autolinking article numbers in text, e.g. /museum/catalogue/scrimshaw/about.html
	Crosslinking in related records
	profiles in W:\data\spripictures\Museum Catalogue\Profiles
	Advanced search
		Search on: Item, Name, Desc + brief desc, Material, Category
		Checkbox for complete/partial-delimed/undelimited
			Item number for exact search
		Show only those with images box
			Both types of no-image can be considered the same thing
		Sort by: Title, ID, Category, Material
			Two boxes if possible
*/


# Class to present the museum catalogue online
require_once ('frontControllerApplication.php');
class modesCatalogueApi extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'MODES catalogue API',
			'div' => 'modescatalogueapi',
			'administrators' => true,
			'tabUlClass' => 'tabsflat',
			
			# Importing
			'importFiles' => array ('records'),	// e.g. array ('museum');
			
			# Table
			'table' => 'records',
			
			'organisationName' => NULL,
			
			# Images
			'mainImageSize' => 450,
			'listingThumbnailSize' => 100,
			'listingThumbnailType' => 'gif',
			'articleImageClass' => false,
			'imageFilenameLiberalMatching' => true,	// Allow case-insensitive matches of image names
			'supportedImageSizes' => array (300,  100, 180, 400, 450, 600),		// First is default
			'supportedImageShapes' => array ('square'),
			
			'administratorEmail' => NULL,
			'multiplesDelimiter' => '|',
			
			# Pagination
			#!# This should be different for search vs listings - 250 is too many for a search
			'paginationRecordsPerPage' => 150,
			
			# Image data source (non-slash terminated)
			'imageStoreRoot' => NULL,
			
			# API
			'apiUsername' => 'guest',
			
			# Imports
			'importsSectionsMode' => true,
			
			# Search
			'availableGroupings' => array ('museum', 'art', 'picturelibrary', 'archives'),
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function to assign supported actions
	public function actions ()
	{
		# Define available tasks
		$actions = array (
			'home' => array (
				'description' => false,
				'url' => '',
				'tab' => 'Home',
			),
			'globalsearch' => array (
				'description' => false,
				'url' => '',
			),
			'contacts' => array (
				'description' => false,
				'url' => '',
			),
			'import' => array (
				'description' => false,
				'url' => 'import/',
				'tab' => 'Import',
				'icon' => 'database_refresh',
				'administrator' => true,
			),
			'reports' => array (
				'description' => false,
				'url' => 'reports/',
				'tab' => 'Data reports',
				'icon' => 'asterisk_orange',
				'administrator' => true,
			),
			'apidocumentation' => array (
				'description' => 'API (HTTP)',
				'url' => 'api/',
				'tab' => 'API',
				'icon' => 'feed',
				'administrator' => true,
			),
			'feedback' => array (
				'description' => 'Feedback/contact form',
				'url' => 'feedback.html',
				'tab' => 'Feedback',
			),
			'images' => array (
				'description' => 'Image thumbnailer',
				'url' => '/images/%id',
				'export' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			
			-- Administrators
			CREATE TABLE `administrators` (
			  `username` varchar(191) NOT NULL COMMENT 'Username',
			  `active` enum('','Yes','No') NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  `privilege` enum('Administrator','Restricted administrator') NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level',
			  PRIMARY KEY (`username`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System administrators';
			
			-- ARMC categories
			CREATE TABLE `armcCategories` (
			  `category` varchar(191) NOT NULL,
			  `classification` text,
			  PRIMARY KEY (`category`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
			
			-- Biographies
			CREATE TABLE `biographies` (
			  `id` varchar(191) NOT NULL COMMENT 'ID',
			  `name` varchar(255) DEFAULT NULL COMMENT 'Name',
			  `date` varchar(255) DEFAULT NULL COMMENT 'Date',
			  `alias` varchar(255) DEFAULT NULL COMMENT 'Alias',
			  `rank` varchar(255) DEFAULT NULL COMMENT 'Rank',
			  `nationality` varchar(255) DEFAULT NULL COMMENT 'Nationality',
			  `awards` text COMMENT 'Awards',
			  `about` text COMMENT 'About',
			  `image` varchar(255) DEFAULT NULL COMMENT 'Image',
			  `data` text NOT NULL COMMENT 'XML of record',
			  `collection` varchar(255) DEFAULT NULL COMMENT 'Collection',
			  `grouping` varchar(255) DEFAULT NULL COMMENT 'Grouping (internal field)',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Records (snapshot date: yymmdd)';
			
			-- Collections
			CREATE TABLE `collections` (
			  `id` varchar(191) NOT NULL COMMENT 'URL key',
			  `collection` varchar(255) NOT NULL COMMENT 'Indicator used in records',
			  `source` enum('manual','modes') NOT NULL COMMENT 'Source',
			  `grouping` enum('museum','picturelibrary','art','archives','Both') DEFAULT NULL,
			  `suppressed` int DEFAULT NULL COMMENT 'Whether to suppress from public view',
			  `title` varchar(255) NOT NULL,
			  `abbreviation` varchar(255) DEFAULT NULL,
			  `introductoryTextBrief` text NOT NULL,
			  `introductoryText` text COMMENT 'Introductory text',
			  `aboutPageHtml` text COMMENT 'Full ''about'' page',
			  `aboutPageTabText` varchar(255) DEFAULT NULL COMMENT 'Text in tab for about page (otherwise will default to ''About'')',
			  `contactsPageHtml` text COMMENT 'HTML of text on a contact page (or none to represent no page)',
			  `contactsPageEmail` varchar(255) DEFAULT NULL COMMENT 'E-mail address used in form',
			  `sponsorNotice` text COMMENT 'Sponsor notice',
			  `categoriesTable` varchar(255) DEFAULT NULL,
			  `disableCategories` int DEFAULT NULL COMMENT 'Disable listing of categories',
			  `disableMaterials` int DEFAULT NULL COMMENT 'Disable listing of materials',
			  `disableArtists` int DEFAULT NULL COMMENT 'Disable listing of artists',
			  `imagesSubfolder` varchar(255) DEFAULT NULL COMMENT 'Images subfolder',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table containing overall application configuration for each ';
			
			-- Expeditions
			CREATE TABLE `expeditions` (
			  `id` varchar(191) NOT NULL COMMENT 'ID',
			  `name` varchar(255) DEFAULT NULL COMMENT 'Name',
			  `date` varchar(255) DEFAULT NULL COMMENT 'Date',
			  `leader` varchar(255) DEFAULT NULL COMMENT 'Leader',
			  `about` text COMMENT 'About',
			  `data` text NOT NULL COMMENT 'XML of record',
			  `collection` varchar(255) DEFAULT NULL COMMENT 'Collection',
			  `grouping` varchar(255) DEFAULT NULL COMMENT 'Grouping (internal field)',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Records (snapshot date: yymmdd)';
			
			-- Lookups
			CREATE TABLE `lookups` (
			  `Term` varchar(191) NOT NULL COMMENT 'Term',
			  `BroaderTerm` varchar(255) DEFAULT NULL COMMENT 'Broader term',
			  `NarrowerTerm` varchar(255) DEFAULT NULL COMMENT 'Narrower term',
			  `SeeAlso` varchar(255) DEFAULT NULL COMMENT 'See also',
			  `PreferredTerm` varchar(255) DEFAULT NULL COMMENT 'Preferred term',
			  `UseFor` varchar(255) DEFAULT NULL COMMENT 'Use for',
			  PRIMARY KEY (`Term`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Terminology lookups';
			
			-- Records
			CREATE TABLE `records` (
			  `id` varchar(191) NOT NULL,
			  `id_prefix` varchar(10) DEFAULT NULL COMMENT 'Prefix part of ID for sorting purposes',
			  `id_suffix` int DEFAULT NULL COMMENT 'Numeric part of ID for sorting purposes',
			  `grouping` varchar(255) DEFAULT NULL COMMENT 'Grouping',
			  `Collection` varchar(191) DEFAULT NULL,
			  `Context` varchar(255) DEFAULT NULL,
			  `Type` varchar(255) DEFAULT NULL COMMENT 'Type of record (record or collection-level)',
			  `Status` varchar(1024) DEFAULT NULL,
			  `ObjectType` varchar(255) DEFAULT NULL,
			  `Title` varchar(1024) DEFAULT NULL,
			  `BriefDescription` text,
			  `Description` text,
			  `PhotographFilename` varchar(1024) DEFAULT NULL,
			  `ReproductionFilename` varchar(255) DEFAULT NULL COMMENT 'Reproduction/Filename',
			  `Category` varchar(255) DEFAULT NULL,
			  `Material` varchar(1024) DEFAULT NULL,
			  `Artist` varchar(255) DEFAULT NULL COMMENT 'Artist (if any)',
			  `data` text NOT NULL,
			  `CollectionName` varchar(255) DEFAULT NULL COMMENT 'Used for debugging; taken from Identification/CollectionName',
			  `searchKeyword` text COMMENT 'Keyword',
			  `searchDescription` text COMMENT 'Description search',
			  `searchPersonOrganisation` text COMMENT 'Person/organisation',
			  `searchExpedition` text COMMENT 'Expedition',
			  `searchPlace` text COMMENT 'Place',
			  `searchSubject` text COMMENT 'Subject',
			  PRIMARY KEY (`id`),
			  KEY `Gallery` (`Collection`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Records (snapshot date: yymmdd)' ROW_FORMAT=DYNAMIC;
		";
	}
	
	
	
	# Function to get collection counts
	private function getCollectionCounts ()
	{
		# Get the number of pre-compiled distinct values, which may include multiple collection lines, e.g. '|KAM||BPA|'
		$query = "SELECT
				Collection,
				COUNT(*) AS total
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE
				    Collection IS NOT NULL
				AND (Status IS NULL OR Status != 'R')
			GROUP BY Collection
			ORDER BY Collection
		;";
		$totals = $this->databaseConnection->getPairs ($query);
		
		# Tokenise the result, adding up the counts for each component
		$counts = array ();
		foreach ($totals as $string => $total) {
			$matches = preg_split ('/(\|+)/', $string, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($matches as $match) {
				$counts[$match] = (isSet ($counts[$match]) ? $counts[$match] : 0) + $total;
			}
		}
		
		# Return the counts
		return $counts;
	}
	
	
	# Function to get a list of collections
	public function getCollectionsData ($baseUrl, $grouping = false)
	{
		# Determine prepared statement values
		$preparedStatementValues = array ();
		
		# Deal with the grouping value, e.g. 'museum' or a list like 'museum,art'
		$groupings = array ();
		if ($grouping) {
			if (preg_match ('/^([,a-z]+)$/D', $grouping)) {
				$groupingsRaw = explode (',', $grouping);
				foreach ($groupingsRaw as $index => $grouping) {
					$groupings[":grouping{$index}"] = $grouping;	// e.g. :grouping0 => 'museum', :grouping1 => 'art'
				}
				$preparedStatementValues += $groupings;
			}
		}
		
		# Determine whether to include suppressed items
		$includeSuppressed = (isSet ($_GET['includesuppressed']) && $_GET['includesuppressed'] == '1');
		
		# Get the collections
		#!# Get rid of url/baseUrl distinction
		$query = "SELECT
			*,
			CONCAT ('{$baseUrl}/', id, '/') AS url,
			CONCAT ('{$baseUrl}/', id) AS baseUrl
		FROM {$this->settings['database']}.collections
		WHERE
			1=1
			" . ($includeSuppressed ? '' : ' AND (suppressed != 1 OR suppressed IS NULL)') . "
			" . ($groupings ? ' AND `grouping` IN(' . implode (', ', array_keys ($groupings)) . ')' : '') . "
		ORDER BY collection
		;";
		$collections = $this->databaseConnection->getData ($query, "{$this->settings['database']}.collections", true, $preparedStatementValues);
		
		# Get counts
		$counts = $this->getCollectionCounts ();
		
		# Remove empty collections
		foreach ($collections as $key => $collection) {
			if (!isSet ($counts[$collection['collection']])) {
				unset ($collections[$key]);
			}
		}
		
		# Add counts in to the data
		foreach ($collections as $key => $collection) {
			$collections[$key]['count'] = $counts[$collection['collection']];
		}
		
		# Truncate introduction text if required
		#!# DATA CLEANUP: Truncate long introductory text
		$truncateToCharacters = 550;
		foreach ($collections as $key => $collection) {
			if (strlen ($collection['introductoryTextBrief']) > $truncateToCharacters) {
				$truncationExtension = ' ...';
				$truncateToCharacters = $truncateToCharacters - strlen ($truncationExtension);
				$collections[$key]['introductoryTextBrief'] = substr ($collection['introductoryTextBrief'], 0, $truncateToCharacters) . $truncationExtension;
			}
		}
		
		# Create a collection cover image
		foreach ($collections as $key => $collection) {
			$this->collectionCoverImage ($collection['baseUrl'], $collection['title'], 100, $galleryImage /* returned by reference */, $width /* returned by reference */, $height /* returned by reference */);
			$collections[$key]['collectionCoverImage_src'] = $galleryImage;
			$collections[$key]['collectionCoverImage_width'] = $width;
			$collections[$key]['collectionCoverImage_height'] = $height;
		}
		
		# Default the about page tab text
		foreach ($collections as $key => $collection) {
			$collections[$key]['aboutPageTabText'] = ($collection['aboutPageTabText'] ? $collection['aboutPageTabText'] : 'About');
		}
		
		# Get the data update date
		$tableComment = $this->databaseConnection->getTableComment ($this->settings['database'], $this->settings['table']);
		preg_match ('|([0-9]{6})|', $tableComment, $matches);
		$timestamp = strtotime (date_format (date_create_from_format ('ymd', $matches[1]), 'Y-m-d') . ' 12:00:00');
		foreach ($collections as $key => $collection) {
			$collections[$key]['dataTimestamp'] = $timestamp;
			$collections[$key]['dataDateHumanReadable'] = date ('l, jS F Y', $timestamp);
		}
		
		# Return the data
		return $collections;
	}
	
	
	# Function to provide as the main image for a collection
	private function collectionCoverImage ($galleryBaseUrl, $title, $desiredBaseWidth = 100, &$galleryImage = false, &$width = false, &$height = false)
	{
		# Define the available sizes
		$sizes = array (
			100 => 'cover.jpg',
			225 => 'cover-large.jpg',
		);
		
		# Define the default file
		$defaultSize = key ($sizes);
		$galleryImage = $galleryBaseUrl . '/' . $sizes[$defaultSize];
		$baseWidth = $defaultSize;
		
		# Determine the filename for the desired size
		if (isSet ($sizes[$desiredBaseWidth])) {
			$desiredGalleryImage = $galleryBaseUrl . '/' . $sizes[$desiredBaseWidth];
			if (is_readable ($_SERVER['DOCUMENT_ROOT'] . $desiredGalleryImage)) {
				$galleryImage = $desiredGalleryImage;
				$baseWidth = $desiredBaseWidth;
			}
		}
		
		# Construct the HTML
		$html = '';
		if (is_readable ($_SERVER['DOCUMENT_ROOT'] . $galleryImage)) {
			list ($width, $height, $type, $attr) = getimagesize ($_SERVER['DOCUMENT_ROOT'] . $galleryImage);
			if ($width > $baseWidth || $height > $baseWidth) {
				$height = ceil (($width / $height) * $baseWidth);
				$width = $baseWidth;
			}
			
			# Compile the HTML
			#!# Should not be compiling an HTML tag in the API end
			$html = '<img src="' . $galleryImage . '" alt="Cover image" title="' . htmlspecialchars ($title) . '" width="' . $width . '" height="' . $height . '" class="shadow" />';
			
		} /* else {
			#!# Replace with link instead
			$galleryImage = "{$this->baseUrl}/images/spacer.gif";
			$width = $baseWidth;
			$height = $baseWidth;
		} */
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create the front page
	public function home ()
	{
		# Welcome
		$html  = "\n<p>This section provides the API powering the collections catalogue.</p>";
		if (!$this->user) {
			$html .= "\n<p>Please login if you have rights.</p>";
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to import the file, clearing any existing import
	public function import ()
	{
		# Define the import types
		$importTypes = array (
			'full' => 'FULL import (c. 2 mins)',
		);
		
		# Define the introduction HTML
		$fileCreationInstructionsHtml = '<p>Use the export facility in MODES, and save the file somewhere on your computer. Note that this can take a while to create.</p>';
		
		# Run the import UI
		$this->importUi ($this->settings['importFiles'], $importTypes, $fileCreationInstructionsHtml);
	}
	
	
	# Function to deal with record importing
	# Needs privileges: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
	public function doImport ($modesXmlExportFiles, $type_ignored, &$html)
	{
		require_once ('import.php');
		$import = new import ($this->settings, $this->databaseConnection, $this->baseUrl, $this->applicationRoot);
		return $import->run ($modesXmlExportFiles, $type_ignored, $html);
	}
	
	
	# Function to split records with a delimiter in a field
	private function delimiterSplitting ($data, $fieldname, $multiplesDelimiter, $removeTrailingBracketedComponent = false)
	{
		# Perform splitting
		$finalised = array ();
		foreach ($data as $index => $attributes) {
			
			# Remove the | and | from the start
			if ((substr ($attributes[$fieldname], 0, 1) == '|') && (substr ($attributes[$fieldname], -1, 1) == '|')) {
				$attributes[$fieldname] = substr ($attributes[$fieldname], 1, -1);
			}
			
			# Get the items
			$items = explode ($multiplesDelimiter, $attributes[$fieldname]);
			
			# Compile the master list of materials
			foreach ($items as $item) {
				
				# Skip first/last (and empty) items
				if (empty ($item)) {continue;}
				
				# Convert characters
				$item = str_replace (' & ', ($fieldname == 'category' ? ', ' : ' > '), $item);
				
				# Drop any trailing bracketed section if required
				if ($removeTrailingBracketedComponent) {
					$item = $this->materialNameMainPart ($item);
				}
				
				# Remove things ending with pattern (a) or (a, b)
				#!# TODO
				
				# Add (or create) the count for each item in the master list
				$finalised[$item][$fieldname] = $item;
				$finalised[$item]['count'] = $attributes['count'] + (isSet ($finalised[$item]['count']) ? $finalised[$item]['count'] : 0);
			}
		}
		
		# Sort
		ksort ($finalised);
		
		# Return the result
		return $finalised;
	}
	
	
	# Function to get the main part of a material name (i.e. exclude a bracketed suffix)
	# The model data structure is: array (Keyword => value1, Keyword2 => value2, Note => notevalue) becoming: "value1 > value2 (notevalue)"
	private function materialNameMainPart ($data, $asLink = false)
	{
		# Get the 'keyword' part out
		if (is_array ($data) && isSet ($data['Keyword'])) {
			$material = $data['Keyword'];
		} else {
			$material = $data;
		}
		
		# Convert hierarchical records
		$material = $this->convertHierarchical ($material);
		
		# Split the name
		$material = explode (' (', $material, 2);
		
		# If as a link, link the first part only
		if ($asLink) {
			$linkStart = "<a href=\"{$this->gallery['baseUrl']}/materials/" . str_replace ('?', '%3F', urlencode ($material[0])) . '">';
			$html  = $linkStart . ucfirst (htmlspecialchars ($material[0])) . '</a>';
			if (count ($material) > 1) {
				array_shift ($material);
				$html .= ' (' . implode (' (', $material);
			}
			
		} else {
			
			# If not as a link, return the text straight away
			$html  = trim ($material[0]);
		}
		
		# Get the 'parts' part out and add that on
		$parts = ((is_array ($data) && isSet ($data['Note'])) ? trim (htmlspecialchars ($data['Note'])) : false);
		if ($parts) {
			$html .= " ({$parts})";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to convert a value arranged as array(a,b,c) into (str) a > b > c
	private function convertHierarchical ($value, $ucFirst = false)
	{
		# Define the separator
		$separator = ' > ';
		
		# Convert to an array
		$items = array ();
		if (is_array ($value)) {
			foreach ($value as $item) {
				
				# Deal with items are are still an array, converting two or more items to "first (second)" or "first (second; third)";
				if (is_array ($item)) {
					$item = array_values ($item);
					$itemMain = $item[0];
					array_shift ($item);
					$itemAttributes = implode ('; ', $item);
					$item = "{$itemMain} ({$itemAttributes})";
				}
				
				# Add to the list
				$items[] = ($ucFirst ? ucfirst ($item) : $item);
			}
			
			# Implode with > separator
			$value = implode ($separator, $items);
		}
		
		# Return the value, possibly unchanged
		return $value;
	}
	
	
	# Function to get biography data
	private function getBiographyData ($baseUrl, $collection, $id = false, $fields = array (), $imageSize, $baseUrlExpeditions = false, $random = false, $forceId = false)
	{
		# Determine which database function to use
		$databaseFunction = ($id ? 'selectOne' : 'select');
		
		# Add limitations
		$conditions = array ();
		if ($id) {
			$conditions['id'] = $id;
		}
		if ($collection) {
			$conditions['collection'] = $collection;
		}
		
		# Randomise, if required
		$orderBy = 'name';
		$limit = false;
		if ($random) {
			$orderBy = 'RAND()';
			$limit = $random;
		}
		
		# Add support for forcing a specific ID to be at the start
		if ($forceId) {
			#!# Doesn't cope yet with values in quotes
			$orderBy = "FIELD(id, '{$forceId}') DESC, " . $orderBy;	// See: https://stackoverflow.com/questions/14104055/ordering-by-specific-field-value-first
		}
		
		# Get the data or end
		#!# Should be application-wide in main FCA settings
		$this->databaseConnection->setStrictWhere (true);
		if (!$data = $this->databaseConnection->{$databaseFunction} ($this->settings['database'], 'biographies', $conditions, $fields, $associative = true, $orderBy, $limit)) {
			return array ();
		}
		
		# Decorate each entry
		$expeditionsRaw = $this->getExpeditionData (false, false, false, array ('id', 'name'));
		if ($id) {
			$data = $this->decorateBiography ($data, $baseUrl, $imageSize, $expeditionsRaw, $baseUrlExpeditions);
		} else {
			foreach ($data as $key => $record) {
				$data[$key] = $this->decorateBiography ($record, $baseUrl, $imageSize, $expeditionsRaw, $baseUrlExpeditions);
			}
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to decorate biography data
	private function decorateBiography ($data, $baseUrl, $imageSize, $expeditionsRaw, $baseUrlExpeditions)
	{
		# Add image reference
		//$data['image'] = NULL;
		
		# Monikerise the ID
		$data['id'] = $this->monikerFromRecordId ($data['id']);
		
		# Explode multiple value types
		if (isSet ($data['nationality'])) {
			$data['nationality'] = $this->unpipeList ($data['nationality']);
		}
		if (isSet ($data['awards'])) {
			$data['awards'] = $this->unpipeList ($data['awards']);
		}
		
		if (isSet ($data['expeditions'])) {
			
			# Unpack the record for metadata extraction
			$json = json_encode (simplexml_load_string ($data['data']));
			$metadata = json_decode ($json, true);
			
			# Get expedition URLs
			$expeditions = array ();
			foreach ($expeditionsRaw as $id => $expedition) {
				$name = $expedition['name'];
				$expeditions[$name] = $baseUrlExpeditions . $expedition['url'];
			}
			
			# Extract expeditions
			$data['expeditions'] = array ();
			foreach ($metadata['Association'] as $event) {
				#!# Check for $event['Event'][EventType] == 'Antarctic expedition' ?
				if (!isSet ($event['Event'])) {continue;}
				$data['expeditions'][] = array (
					'title' => $event['Event']['EventName'],
					'date' => $event['Event']['Date']['DateBegin'] . '-' . $event['Event']['Date']['DateEnd'],
					'link' => $expeditions[$event['Event']['EventName']],
					'image' => NULL,
				);
			}
		}
		
		# Create a URL
		$data['link'] = $this->urlFromId ($data['id'], $baseUrl);
		
		# Add images
		if ($data['image']) {
			$data['image'] = $this->thumbnailLocation ('biographies', $data['moniker'], 1, $imageSize);
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to create a proper identifier from a record ID
	#!# Move into articleModel then make private
	public function monikerFromRecordId ($id)
	{
		$replacements = array (
			' ' => '_',
			'/' => '.',
		);
		return strtr ($id, $replacements);
	}
	
	
	# Function to determine the record ID from the moniker
	private function recordIdFromMoniker ($moniker, $disableDotToSlashConversion)
	{
		$replacements = array ();
		$replacements['_'] = ' ';
		if (!$disableDotToSlashConversion) {
			$replacements['.'] = '/';
		}
		return strtr ($moniker, $replacements);
	}
	
	
	# Function to create a URL from an ID
	private function urlFromId ($id, $baseUrl)
	{
		$moniker = $this->monikerFromRecordId ($id);
		return $baseUrl . '/' . $moniker . '/';
	}
	
	
	# Function to convert an article ID to a URL slug
	#!# Needs to be a pluggable callback
	private function articleIdToUrlSlug ($string, $type, $baseUrl, $asFullUrl = false)
	{
		# Lower-case
		$string = strtolower ($string);
		
		# Convert slash to dot
		$string = str_replace ('/', '.', $string);
		
		# Convert to a full URL if necessary
		if ($asFullUrl) {
			$string = $baseUrl . '/article/' . $string . '/';
		}
		
		# Return the result
		return $string;
	}
	
	
	# Function to convert a URL slug to an article ID
	#!# Needs to be a pluggable callback
	private function urlSlugToArticleId ($string, $type)
	{
		# Convert dot to slash
		$string = str_replace ('.', '/', $string);
		
		# Upper-case
		$string[0] = strtoupper ($string[0]);
		
		# Return the result
		return $string;
	}
	
	
	# Function to get expedition data
	private function getExpeditionData ($baseUrl, $collection, $id = false, $fields = array ())
	{
		# Determine which database function to use
		$databaseFunction = ($id ? 'selectOne' : 'select');
		
		# Add limitations
		$conditions = array ();
		if ($id) {
			$conditions['id'] = $id;
		}
		if ($collection) {
			$conditions['collection'] = $collection;
		}
		
		# Get the data or end
		#!# Should be application-wide in main FCA settings
		$this->databaseConnection->setStrictWhere (true);
		if (!$data = $this->databaseConnection->{$databaseFunction} ($this->settings['database'], 'expeditions', $conditions, $fields, $associative = true, $orderBy = 'name')) {
			return array ();
		}
		
		# Create a URL for each entry
		if ($id) {
			$data['url'] = $this->urlFromId ($data['id'], $baseUrl);
		} else {
			foreach ($data as $key => $record) {
				$data[$key]['url'] = $this->urlFromId ($record['id'], $baseUrl);
			}
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to get record categories
	private function getCategoriesData ($collection, $includeUnclassified = true)
	{
		# Assemble the query
		$query = "
			SELECT
				Category as category,
				COUNT(id) as count
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE (Status != 'R' OR Status IS NULL)
			AND Collection LIKE :collectionId
			GROUP BY `Category`;";
		$preparedStatementValues = array ('collectionId' => "%|{$collection['collection']}|%");
		
		#!# Migrate to getPairs, but delimiterSplitting will need to be reworked to the new data structure
		
		# Get the data or end
		if (!$data = $this->databaseConnection->getData ($query, false, true, $preparedStatementValues)) {return array ();}
		
		# Deal with multiple attribute delimiter splitting
		$data = $this->delimiterSplitting ($data, 'category', $this->settings['multiplesDelimiter']);
		
		# Get the AAT lookups and add the 'Unidentified object' type
		$categories = $this->databaseConnection->select ($this->settings['database'], $collection['categoriesTable']);
		if ($includeUnclassified) {$categories['Unidentified object']['classification'] = 'No classification available';}
		
		# Merge in the category data; note this can't be done using LEFT OUTER JOIN as the delimiter splitting makes that impossible
		foreach ($data as $index => $item) {
			$data[$index]['classification'] = (isSet ($categories[$item['category']]) ? $categories[$item['category']]['classification'] : false);
		}
		
		# Remove the unidentified object, or move it to the end if necessary
		if (isSet ($data['Unidentified object'])) {
			$unidentified = $data['Unidentified object'];
			unset ($data['Unidentified object']);
			if ($includeUnclassified) {
				$data['Unidentified object'] = $unidentified;
			}
		}
		
		# Return the categories
		return $data;
	}
	
	
	# Function to get record categories
	private function getGroup ($collectionId, $field)
	{
		# Assemble the query
		$query = "
			SELECT
				{$field},
				COUNT(id) as count
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE
				    Collection LIKE :collectionId
				AND (Status != 'R' OR Status IS NULL)
				AND {$field} != ''
			GROUP BY {$field}, id
			ORDER BY id;";
		$preparedStatementValues = array ('collectionId' => "%|{$collectionId}|%");
		
		#!# Migrate to getPairs, but delimiterSplitting will need to be reworked to the new data structure
		
		# Get the data
		if (!$data = $this->databaseConnection->getData ($query, false, true, $preparedStatementValues)) {return array ();}
		
		# Deal with multiple attribute delimiter splitting, removing any trailing bracketed component
		$data = $this->delimiterSplitting ($data, $field, $this->settings['multiplesDelimiter'], $removeTrailingBracketedComponent = true);
		
		# Sort by name
		ksort ($data);
		
		# Rearrange the items
		foreach ($data as $key => $attributes) {
			$data[$key] = $attributes['count'];
		}
		
		# Return the categories
		return $data;
	}
	
	
	
	/* API calls */
	
	
	# API documentation page
	public function apidocumentationIntroduction ()
	{
		# Create and return the HTML
		return $html = '
			<p>When prompted, the username is <strong>guest</strong> and there is no password. (The links below have the guest@ username embedded in them, which will work if the link is opened in an incognito window.)</p>
		';
	}
	
	
	# API call to get a list of collections
	public function apiCall_collections ()
	{
		# Start an array of data to be returned
		$data = array ();
		
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Optionally allow a grouping (or groupings, separated by comma) to be specified, e.g. 'museum' or a list like 'museum,art'
		$grouping = (isSet ($_GET['grouping']) ? $_GET['grouping'] : false);
		
		# Get the collections
		$collections = $this->getCollectionsData ($baseUrl, $grouping);
		
		# Compute total number of records
		$totalRecords = 0;
		foreach ($collections as $key => $collection) {
			$totalRecords += $collection['count'];
		}
		
		# Limit to fields if required
		$fields = (isSet ($_GET['fields']) && strlen ($_GET['fields']) ? explode (',', $_GET['fields']) : array ());
		if ($fields) {
			foreach ($collections as $id => $collection) {
				$collections[$id] = application::arrayFields ($collection, $fields);
			}
		}
		
		# Compile stats
		$data['summary'] = array (
			'totalCollections' => count ($collections),
			'totalRecords' => $totalRecords,
		);
		
		# Register the collections data
		$data['collections'] = $collections;
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for collections
	public function apiCallDocumentation_collections ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches the list of collections:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/collections?grouping=picturelibrary';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "summary": {
        "totalCollections": 22,
        "totalRecords": 19706
    },
    "collections": {
        "modern": {
            "id": "modern",
            "grouping": "picturelibrary",
            "suppressed": null,
            "collection": "Modern Photograph Collection",
            "title": "Modern Photograph Collection",
            "abbreviation": "",
            "introductoryTextBrief": "These are modern photographs taken during field research in the Arctic and Antarctic.",
            "introductoryText": "<p>These are modern photographs taken during field research in the Arctic and Antarctic.</p>",
            "aboutPageHtml": null,
            "aboutPageTabText": "About",
            "contactsPageHtml": null,
            "contactsPageEmail": null,
            "sponsorNotice": "",
            "categoriesTable": "",
            "disableArtists": null,
            "disableCategories": null,
            "disableMaterials": null,
            "imagesSubfolder": "/Picture Library Collections/",
            "source": "database",
            "url": "/picturelibrary/catalogue/modern/",
            "count": "121",
            "baseUrl": "/picturelibrary/catalogue/modern",
            "collectionCoverImage": "<img src=\"/picturelibrary/catalogue/modern/cover.jpg\" alt=\"Cover image\" title=\"Modern Photograph Collection\" width=\"100\" height=\"100\" class=\"diagram\" />"
        },
        "are1902-04": {
            "id": "are1902-04",
            "title": "Antarctic Relief Expeditions 1902-04",
            "introductoryTextBrief": "Photographs chronicling the second relief expedition, 1903-04, of the sailing ships \'Morning\' and \'Terra Nova\'.",
            "abbreviation": "ARE 1902-04",
            "source": "modes",
            "grouping": "picturelibrary",
            "collection": "ARE 1902-04",
            "sponsorNotice": "",
            "aboutPageHtml": "",
            "categoriesTable": "",
            "disableArtists": "",
            "disableCategories": "",
            "disableMaterials": "",
            "aboutPageTabText": "About",
            "introductoryText": "The Antarctic Relief Expeditions consisted of two voyages undertaken to aid the British National Antarctic Expedition.",
            "imagesSubfolder": "/thumbnails/Picture Library Collections/FreezeFrame/Antarctic_Relief_Expeditions_1902-04/",
            "url": "/picturelibrary/catalogue/are1902-04/",
            "count": "93",
            "baseUrl": "/picturelibrary/catalogue/are1902-04",
            "collectionCoverImage": "<img src=\"/picturelibrary/catalogue/are1902-04/cover.jpg\" alt=\"Cover image\" title=\"Antarctic Relief Expeditions 1902-04\" width=\"100\" height=\"75\" class=\"diagram\" />"
        },
        
        ...
    }
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<p>None.</p>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>grouping</strong> <em>string, comma-separated a-z values</em></dt>
				<dd>Filter to the specified grouping or groupings, e.g. \'museum\' or \'museum,art\'.<br />Currently-supported grouping values are: <tt>museum</tt>, <tt>art</tt>, <tt>picturelibrary</tt>.</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">Unspecified error.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API call to get details of a single collection
	public function apiCall_collection ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Get the collections
		$collections = $this->getCollectionsData ($baseUrl);
		
		# Require a collection ID to be specified
		$id = (isSet ($_GET['id']) ? $_GET['id'] : false);
		if (!$id || !isSet ($collections[$id])) {
			return array ('error' => 'Invalid collection ID.');
		}
		
		# Obtain the data
		$data = $collections[$id];
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for collection
	public function apiCallDocumentation_collection ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of a single collection:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/collection?id=modern';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "id": "modern",
    "grouping": "picturelibrary",
    "suppressed": null,
    "collection": "Modern Photograph Collection",
    "title": "Modern Photograph Collection",
    "abbreviation": "",
    "introductoryTextBrief": "These are modern photographs taken during field research in the Arctic and Antarctic.",
    "introductoryText": "<p>These are modern photographs taken during field research in the Arctic and Antarctic.</p>",
    "aboutPageHtml": null,
    "aboutPageTabText": "About",
    "contactsPageHtml": null,
    "contactsPageEmail": null,
    "sponsorNotice": "",
    "categoriesTable": "",
    "disableArtists": null,
    "disableCategories": null,
    "disableMaterials": null,
    "imagesSubfolder": "/Picture Library Collections/",
    "source": "database",
    "url": "/picturelibrary/catalogue/modern/",
    "count": "121",
    "baseUrl": "/picturelibrary/catalogue/modern",
    "collectionCoverImage": "<img src=\"/picturelibrary/catalogue/modern/cover.jpg\" alt=\"Cover image\" title=\"Modern Photograph Collection\" width=\"100\" height=\"100\" />"
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>id</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<p>None.</p>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">Invalid collection ID.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain a set of articles
	public function apiCall_articles ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Determine the articles prefix
		$baseUrlArticles = (isSet ($_GET['baseUrlArticles']) ? $_GET['baseUrlArticles'] : false);
		
		# Filter to a specified collection if required
		$collection = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Specify a search phrase if required
		$search = false;
		if (isSet ($_GET['search']) && strlen ($_GET['search'])) {
			if (strlen ($_GET['search']) < 3) {
				return array ('error' => 'The search phrase must be at least 3 characters.');
			}
			$search = $_GET['search'];
		};
		
		# Ensure either a collection or a search`	 has been specified
		if (!$collection && !$search) {
			return array ('error' => 'At least a collection or a search phrase must be specified.');
		}
		
		# Filter by category if specified
		$category = (isSet ($_GET['category']) && strlen ($_GET['category']) ? $_GET['category'] : false);
		
		# Filter by material if specified
		$material = (isSet ($_GET['material']) && strlen ($_GET['material']) ? $_GET['material'] : false);
		
		# Filter by artist if specified
		$artist = (isSet ($_GET['artist']) && strlen ($_GET['artist']) ? $_GET['artist'] : false);
		
		# Specify whether each article is required to have an image
		$requireImages = (isSet ($_GET['requireimages']) && ($_GET['requireimages'] == '1'));
		
		# Obtain a specified number of articles selected at random
		$random = (isSet ($_GET['random']) && ctype_digit ($_GET['random']) ? $_GET['random'] : false);
		
		# Get the current page
		$page = ((isSet ($_GET['page']) && ctype_digit ($_GET['page'])) ? $_GET['page'] : 1);
		
		# Image size
		$imageSize = (isSet ($_GET['imagesize']) ? $_GET['imagesize'] : (string) $this->settings['supportedImageSizes'][0]);
		if (!ctype_digit ($imageSize) || !in_array ($imageSize, $this->settings['supportedImageSizes'])) {		// Digit check to avoid situation where e.g. '300foo' will pass as valid, but resize will ignore that, resulting in full-size image
			return array ('error' => 'Unsupported image size.');
		}
		
		# Image shape
		$imageShape = (isSet ($_GET['imageshape']) ? $_GET['imageshape'] : false);
		if ($imageShape && !in_array ($imageShape, $this->settings['supportedImageShapes'])) {
			return array ('error' => 'Invalid shape supplied.');
		}
		
		# Get the data
		ini_set ('display_errors', false);	// #!# Ensure any errors do not disrupt API output
		require_once ('articleModel.php');
		$articleModel = new articleModel ($this, $this->settings, $this->databaseConnection);
		$data = $articleModel->getArticlesData ($baseUrl, $collection, $imageSize, $imageShape, $search, $category, $material, $artist, $requireImages, $random, $page);
		
		# Construct URLs
		foreach ($data['articles'] as $id => $article) {
			$data['articles'][$id]['link'] = $this->urlFromId ($id, $baseUrlArticles);
		}
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for article
	public function apiCallDocumentation_articles ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of multiple articles:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/articles?collection=armc&category=dolls';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "pagination": {
        "count": 150,
        "total": 571,
        "page": 1,
        "totalPages": 4
    },
    "relatedTerms": [],
    "articles": {
        "N: 1": {
            "id": "N: 1",
            "moniker": "n1",
            "status": null,
            "collections": [
                "armc"
            ],
            "title": "Harness",
            "briefDescription": "Harness, dog. Labrador Inuit. Labrador, Newfoundland, Canada, before April, 1938.",
            "images": [
                {
                    "path": "/collections/catalogue/images/records/size450/n1_1.jpg",
                    "width": 450,
                    "height": 300
                }
            ],
            "imageFiles": [
                "N1.TIF"
            ],
        },
        ...
    }
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<p>At least one of <strong>collection</strong> or <strong>search</strong> (documented below) must be specified.</p>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';

		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
			<dt><strong>imagesize</strong> <em>string: ' . implode ('|', $this->settings['supportedImageSizes']) . '</em></dt>
				<dd>Image size of the returned images</dd>
			<dt><strong>imageshape</strong> <em>string: ' . implode ('|', $this->settings['supportedImageShapes']) . '</em> default <em>[no value, i.e. original dimensions]</em></dt>
				<dd>Image shape of the returned images. If not supplied, the dimensions of the original will be used.</dd>
			<dt><strong>search</strong> <em>string</em></dt>
				<dd>A search string, which will be checked as a free text search against various fields</dd>
			<dt><strong>category</strong> <em>string</em></dt>
				<dd>The category to filter on</dd>
			<dt><strong>material</strong> <em>string</em></dt>
				<dd>The material to filter on</dd>
			<dt><strong>artist</strong> <em>string</em></dt>
				<dd>The artist to filter on</dd>
			<dt><strong>requireimages</strong> <em>integer 1|0</em> default <em>0</em></dt>
				<dd>Whether only articles with images should be included</dd>
			<dt><strong>random</strong> <em>integer</em></dt>
				<dd>Return only the specified number of images, ordered randomly</dd>
			<dt><strong>page</strong> <em>integer</em></dt>
				<dd>Pagination page; a maximum of ' . $this->settings['paginationRecordsPerPage'] . ' records are returned per page. If an invalid page is specified, an error is returned. The pagination field at the head of the record (on a valid page, e.g. page 1) shows the number of pages available.</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		$html .= "\n" . '<p>The format of each field in each article is the same as the <a href="#article">article API</a>, but each article is cut down to a smaller set of fields.</p>';
		$html .= "\n" . '<p>The related terms section will only be filled when using a search term and there are related terms for that search term.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such collection.</span>"
}</pre>
<pre class="code">
{
    "error": "<span class=\"warning\">The search phrase must be at least 3 characters.</span>"
}</pre>
<pre class="code">
{
    "error": "<span class=\"warning\">At least a collection or a search phrase must be specified.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain an article
	public function apiCall_article ()
	{
		# Require an article ID to be specified
		if (!isSet ($_GET['id']) || !strlen ($_GET['id'])) {
			return array ('error' => 'No article ID was supplied.');
		}
		
		# Determine whether to add an explicit collection context; otherwise the first is used
		$collectionId = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Determine whether to include the XML
		$includeXml = (isSet ($_GET['includeXml']) && ($_GET['includeXml'] == '1'));
		
		# Determine the expeditions baseUrl
		$baseUrlExpeditions = (isSet ($_GET['baseUrlExpeditions']) ? $_GET['baseUrlExpeditions'] : false);
		
		# Determine the biographies prefix
		$baseUrlPeople = (isSet ($_GET['baseUrlPeople']) ? $_GET['baseUrlPeople'] : false);
		
		# Image size
		$imageSize = (isSet ($_GET['imagesize']) ? $_GET['imagesize'] : (string) $this->settings['supportedImageSizes'][0]);
		if (!ctype_digit ($imageSize) || !in_array ($imageSize, $this->settings['supportedImageSizes'])) {		// Digit check to avoid situation where e.g. '300foo' will pass as valid, but resize will ignore that, resulting in full-size image
			return array ('error' => 'Unsupported image size.');
		}
		
		# Image shape
		$imageShape = (isSet ($_GET['imageshape']) ? $_GET['imageshape'] : false);
		if ($imageShape && !in_array ($imageShape, $this->settings['supportedImageShapes'])) {
			return array ('error' => 'Invalid shape supplied.');
		}
		
		# Parse the record data
		ini_set ('display_errors', false);	// #!# Ensure any errors do not disrupt API output
		require_once ('articleModel.php');
		$articleModel = new articleModel ($this, $this->settings, $this->databaseConnection);
		$data = $articleModel->getOne ($_GET['id'], $collectionId, $imageSize, $imageShape, $includeXml);
		
		# Get expedition URLs and images
		#!# Consider whether this block should logically be within articleModel
		if (isSet ($data['associatedExpedition'])) {
			if ($data['associatedExpedition']) {
				
				# Get the raw data
				$expeditionsRaw = $this->getExpeditionData (false, false, false, array ('id', 'name'));
				$expeditions = array ();
				foreach ($expeditionsRaw as $id => $expedition) {
					$name = $expedition['name'];
					$expeditions[$name] = $baseUrlExpeditions . $expedition['url'];
				}
				
				# Attach the URL if present
				foreach ($data['associatedExpedition'] as $index => $exhibition) {
					$name = $exhibition['name'];
					$data['associatedExpedition'][$index]['url'] = (isSet ($expeditions[$name]) ? $expeditions[$name] : NULL);
				}
				
				# Attach the image
				#!# Not yet implemented
				foreach ($data['associatedExpedition'] as $index => $exhibition) {
					$data['associatedExpedition'][$index]['image'] = NULL;
				}
			}
		}
		
		# Get people URLs and images
		#!# Consider whether this block should logically be within articleModel
		if (isSet ($data['associatedPerson'])) {
			if ($data['associatedPerson']) {
				
				# Attach the URL if present
				foreach ($data['associatedPerson'] as $index => $person) {
					$data['associatedPerson'][$index]['link'] = $this->urlFromId ($person['name'], $baseUrlPeople);
				}
				
				# Attach the image
				#!# Not yet implemented
				foreach ($data['associatedPerson'] as $index => $person) {
					$data['associatedPerson'][$index]['image'] = NULL;
				}
			}
		}
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for article
	public function apiCallDocumentation_article ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of an article:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/article?id=N:+76a-b&collection=?';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "id": "N: 76a-b",
    "moniker": "n76a-b",
    "type": "object",
    "status": null,
    "collections": [
        "armc"
    ],
    "context": "ARMC",
    "title": "Sledge, model; doll",
    "briefDescription": "Sledge, model, wood (a); doll (b). Tunumiit (East Greenlanders). Tasiilaq (Ammassalik), Kalaallit Nunaata Tunua (East Greenland).",
    "objectType": "Sledge, model; doll",
    "medium": null,
    "category": "|Sleds||Models||Dolls|",
    "artist": null,
    "classifiedNames": {
        "Current cultural affiliation": "Tunumiit",
        "Former cultural affiliation": "East Greenlanders",
        "Cultural subgroup": "ammassalimmiut",
        "Current place name": "Kalaallit Nunaata Tunu > Tasiilaq",
        "Former place name": "East Greenland > Ammassalik (Angmassalik)",
        "Keyword(s) (AAT)": "Dolls"
    },
    "fieldCollection": [],
    "materials": [],
    "numberOfItems": 2,
    "note": "Thalbitzer 1912: 367",
    "fullDescription": "a) Sledge (a) has wood runners which are vertical at the rear and curve upwards and taper towards straight front.",
    "relatedRecords": [],
    "dimensions": {
        "a": {
            "width": "97mm",
            "height": "107mm",
            "length": "242mm"
        },
        "b": {
            "width": "76mm",
            "height": "177mm"
        }
    },
    "placeName": null,
    "images": [
        {
            "path": "/collections/catalogue/images/records/size300/n76a-b_1.jpg",
            "width": 300,
            "height": 200
        }
    ],
    "imagesFiles": [
        "N_76_a_b.TIF"
    ],
    "imageBy": null,
    "imageColour": null,
    "navigationIds": {
        "current": "N: 76a-b",
        "previous": "N: 55",
        "next": "N: 77a",
        "start": "N: 1",
        "end": "Z: 313a-c"
    },
    "navigationIdsAdditional": {
        "categories": {
            "Dolls": {
                "current": "N: 76a-b",
                "previous": null,
                "next": "N: 244a",
                "start": "N: 76a-b",
                "end": "Z: 254b"
            },
            "Models": {
                "current": "N: 76a-b",
                "previous": "N: 55",
                "next": "N: 77a",
                "start": "N: 55",
                "end": "Y: 2014/8a-i"
            },
            "Sleds": {
                "current": "N: 76a-b",
                "previous": "N: 55",
                "next": "N: 77a",
                "start": "N: 55",
                "end": "Y: 2005/1/13"
            }
        }
    }
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>id</strong> <em>string</em></dt>
				<dd>The article identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier, which adds contextual information (e.g. IDs of next/previous items in same collection); the special value "?" (signifying auto) can also be used to generate context automatically from the first collection listed in the record if present</dd>
			<dt><strong>imagesize</strong> <em>string: ' . implode ('|', $this->settings['supportedImageSizes']) . '</em></dt>
				<dd>Image size of the returned images</dd>
			<dt><strong>imageshape</strong> <em>string: ' . implode ('|', $this->settings['supportedImageShapes']) . '</em> default <em>[no value, i.e. original dimensions]</em></dt>
				<dd>Image shape of the returned images. If not supplied, the dimensions of the original will be used.</dd>
			<dt><strong>baseUrlExpeditions</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value for each expedition</dd>
			<dt><strong>baseUrlPeople</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value for each person</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such record ID.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain a set of biographies
	public function apiCall_biographies ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Determine the expeditions baseUrl
		$baseUrlExpeditions = (isSet ($_GET['baseUrlExpeditions']) ? $_GET['baseUrlExpeditions'] : false);
		
		# Filter to a specified collection if required
		$collection = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Obtain a specified number of articles selected at random
		$random = (isSet ($_GET['random']) && ctype_digit ($_GET['random']) ? $_GET['random'] : false);
		
		# Obtain a specified number of articles selected at random
		$forceId = (isSet ($_GET['forceid']) ? $_GET['forceid'] : false);
		
		# Image size
		$imageSize = (isSet ($_GET['imagesize']) ? $_GET['imagesize'] : (string) $this->settings['supportedImageSizes'][0]);
		if (!ctype_digit ($imageSize) || !in_array ($imageSize, $this->settings['supportedImageSizes'])) {		// Digit check to avoid situation where e.g. '300foo' will pass as valid, but resize will ignore that, resulting in full-size image
			return array ('error' => 'Unsupported image size.');
		}
		
		# Get the data
		$fields = array ('id', 'name', 'rank', 'image');
		$data = $this->getBiographyData ($baseUrl, $collection, false, $fields, $imageSize, $baseUrlExpeditions, $random, $forceId);
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for biographies
	public function apiCallDocumentation_biographies ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of multiple biographies:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/biographies?collection=VSII&baseUrl=/museum/people';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "Abrahams, Frederick G.": {
        "id": "Abrahams, Frederick G.",
        "name": "Abrahams, Frederick G.",
        "date": "1885-unknown",
        "alias": null,
        "rank": null,
        "nationality": [
            "British"
        ],
        "awards": [],
        "about": "Frederick G. Abrahams was born [...]",
        "expeditions": [],
        "url": "/museum/people/Abrahams,_Frederick_G./"
    },
    "Adams, Jameson Boyd": {
        "id": "Adams, Jameson Boyd",
        "name": "Adams, Jameson Boyd",
        "date": "1880-1962",
        "alias": null,
        "rank": "Commander (Royal Naval Reserve)",
        "nationality": [
            "British"
        ],
        "awards": [
            "Distinguish Service Order",
            "Croix de Guerre",
            "Knight of the British Empire (1948)"
        ],
        "about": "Jameson Boyd Adams was born [...]",
        "expeditions": [],
        "url": "/museum/people/Adams,_Jameson_Boyd/"
    },
    ...
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<p>None.</p>';
		
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n<p>Parameter values which perform a filtering operation are case-sensitive.</p>";
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
			<dt><strong>baseUrl</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value</dd>
			<dt><strong>imagesize</strong> <em>string: ' . implode ('|', $this->settings['supportedImageSizes']) . '</em></dt>
				<dd>Image size of the returned images</dd>
			<dt><strong>random</strong> <em>integer</em></dt>
				<dd>Return only the specified number of images, ordered randomly</dd>
			<dt><strong>forceid</strong> <em>string</em></dt>
				<dd>Require this specific ID to be the first item</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		$html .= "\n" . '<p>The format of each field in each article is the same as the <a href="#biography">biography API</a>, but each biography is cut down to a smaller set of fields.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">Unidentified error.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain a biography
	public function apiCall_biography ()
	{
		# Require an article ID to be specified
		if (!isSet ($_GET['id']) || !strlen ($_GET['id'])) {
			return array ('error' => 'No article ID was supplied.');
		}
		
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Determine the expeditions baseUrl
		$baseUrlExpeditions = (isSet ($_GET['baseUrlExpeditions']) ? $_GET['baseUrlExpeditions'] : false);
		
		# Determine the 'Unknown person' image URL
		$nullPersonUrl = (isSet ($_GET['nullPersonUrl']) ? $_GET['nullPersonUrl'] : false);
		
		# Image size
		$imageSize = (isSet ($_GET['imagesize']) ? $_GET['imagesize'] : (string) $this->settings['supportedImageSizes'][0]);
		if (!ctype_digit ($imageSize) || !in_array ($imageSize, $this->settings['supportedImageSizes'])) {		// Digit check to avoid situation where e.g. '300foo' will pass as valid, but resize will ignore that, resulting in full-size image
			return array ('error' => 'Unsupported image size.');
		}
		
		# Get the record data
		$fields = array ('id', 'name', 'date', 'alias', 'rank', 'nationality', 'awards', 'about', 'data', 'collection', 'image');
		if (!$data = $this->getBiographyData ($baseUrl, $collection, $_GET['id'], $fields, $imageSize, $baseUrlExpeditions)) {
			return array ('error' => 'There is no such record ID.');
		}
		
		# Rank is not currently reliable, due to <note> leaf nodes
		require_once ('xml.php');
		$dataXml = xml::xml2array ($data['data'], false, $documentToDataOrientatedXml = true, $xmlIsFile = false);
		//application::dumpData ($dataXml);
		$data['rank'] = (isSet ($dataXml['Content']['Person']['Rank']['Rank']) ? $dataXml['Content']['Person']['Rank']['Rank'] : $dataXml['Content']['Person']['Rank']);
		
		# Add person title
		$data['title'] = $dataXml['Content']['Person']['PersonTitle'];
		
		# Handle alias
		$alias = $this->unpipeList ($data['alias']);
		$alias = array_unique ($alias);
		$data['alias'] = implode (', ', $alias);
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# Helper function to explode a pipe/double-pipe -separated list
	private function unpipeList ($string)
	{
		if (!$string) {return array ();}
		$list = explode ('|', $string);
		foreach ($list as $index => $item) {
			if (!strlen ($item)) {unset ($list[$index]);}
		}
		return array_values ($list);
	}
	
	
	# API documentation for biography
	public function apiCallDocumentation_biography ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of a biography:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/biography?id=' . urlencode ('Abrahams, Frederick G.') . '&baseUrl=/museum/people&baseUrlExpeditions=/museum/expeditions';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "id": "Shackleton, Ernest Henry",
    "name": "Shackleton, Ernest Henry",
    "date": "1874-1922",
    "alias": null,
    "rank": null,
    "nationality": [
        "British"
    ],
    "awards": [
        "Royal Geographical Society Special Gold Medal (1909)",
        "Knighthood",
        [...]
    ],
    "about": "Ernest Henry Shackleton was born on [...]",
    "data": "<Object><ObjectIdentity><Number>Shackleton, Ernest Henry</Number></ObjectIdentity> [...]",
    "collection": "VSII",
    "url": "/museum/people/Shackleton,_Ernest_Henry/",
    "expeditions": [
        {
            "title": "Shackleton-Rowett Antarctic Expedition 1921-22 (Quest)",
            "date": "1921-1922",
            "link": "/museum/expeditions/quest/",
            "image": null
        },
        {
            "title": "Imperial Trans-Antarctic Expedition 1914-16 (Endurance)",
            "date": "1914-1916",
            "link": "/museum/expeditions/endurance/",
            "image": null
        },
        [...]
    ]
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n<p>Parameter values which perform a filtering operation are case-sensitive.</p>";
		$html .= "\n" . '<dl class="code">
			<dt><strong>id</strong> <em>string</em></dt>
				<dd>The article identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>baseUrl</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value</dd>
			<dt><strong>imagesize</strong> <em>string: ' . implode ('|', $this->settings['supportedImageSizes']) . '</em></dt>
				<dd>Image size of the returned images</dd>
			<dt><strong>baseUrlExpeditions</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value for each expedition</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such record ID.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain a set of expeditions
	public function apiCall_expeditions ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Filter to a specified collection if required
		$collection = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Get the data
		$fields = array ('id', 'name', 'date', 'leader', 'about');
		$data = $this->getExpeditionData ($baseUrl, $collection, false, $fields);
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for expeditions
	public function apiCallDocumentation_expeditions ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of multiple expeditions:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/expeditions?collection=VSII&baseUrl=/museum';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "nimrod": {
        "id": "nimrod",
        "name": "British Antarctic Expedition 1907-09 (Nimrod)",
        "date": "1907",
        "leader": "",
        "about": "Shackleton\'s first expedition to the Antarctic as leader had [...]",
        "url": "/museum/nimrod/"
    },
    "endurance": {
        "id": "endurance",
        "name": "Imperial Trans-Antarctic Expedition 1914-16 (Endurance)",
        "date": "1914",
        "leader": "Shackleton, Ernest Henry",
        "about": "After Roald Amundsen successfully reached the South Pole [...]",
        "url": "/museum/endurance/"
    },
    
    ...
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<p>None.</p>';
		
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n<p>Parameter values which perform a filtering operation are case-sensitive.</p>";
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
			<dt><strong>baseUrl</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">Unidentified error.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain a expedition
	public function apiCall_expedition ()
	{
		# Require an article ID to be specified
		if (!isSet ($_GET['id']) || !strlen ($_GET['id'])) {
			return array ('error' => 'No article ID was supplied.');
		}
		
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Determine the biographies prefix
		$baseUrlPeople = (isSet ($_GET['baseUrlPeople']) ? $_GET['baseUrlPeople'] : false);
		
		# Get the record data
		$fields = array ('id', 'name', 'date', 'leader', 'about', 'data', 'collection');
		if (!$data = $this->getExpeditionData ($baseUrl, $collection, $_GET['id'], $fields)) {
			return array ('error' => 'There is no such record ID.');
		}
		
		# Unpack the record for metadata extraction
		$json = json_encode (simplexml_load_string ($data['data']));
		$metadata = json_decode ($json, true);
		
		# Date is not reliable
		#!# Remove when fixed by MySQL 8 upgrade: https://stackoverflow.com/questions/30090221/mysql-xpath-concatenation-operator-how-to-add-space
		$data['date'] = $metadata['Content']['Event']['Date']['DateBegin'] . '-' . $metadata['Content']['Event']['Date']['DateEnd'];
		
		# Get all the biographies
		#!# Need to add support in getBiographyData for getting a list of IDs, to avoid pointless lookup of people not present in the expedition
		#!# Fields needs to be filterable
		$allBiographies = $this->getBiographyData ($baseUrlPeople, $collection, false, $fields = array (/* 'link', 'image' */), $imageSize);
		
		# Extract people
		$data['people'] = array ();
		foreach ($metadata['Association']['Person'] as $person) {
			$data['people'][] = array (
				'name' => $person['PersonName'],
				'role' => $person['Role'],
				'link' => $allBiographies[$person['PersonName']]['link'],
				'image' => $allBiographies[$person['PersonName']]['image'],
			);
		}
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for expedition
	public function apiCallDocumentation_expedition ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches details of an expedition, including main details of any associated people:</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/expedition?id=nimrod&baseUrl=/museum/expeditions&baseUrlPeople=/museum/biographies';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "id": "nimrod",
    "name": "British Antarctic Expedition 1907-09 (Nimrod)",
    "date": "1907",
    "leader": "",
    "about": "Shackleton\'s first expedition to the Antarctic as leader had [...]",
    "data": "<Object><ObjectIdentity><Number>British Antarctic Expedition 1907-09 (Nimrod)</Number></ObjectIdentity>...</Object>",
    "collection": "VSII",
    "url": "/museum/expeditions/nimrod/",
    "people": [
        {
            "name": "Shackleton, Ernest Henry",
            "role": "Expedition leader",
            "link": "/museum/biographies/Shackleton,_Ernest_Henry/",
            "image": null
        },
        {
            "name": "Abrahams, Frederick G.",
            "role": "Ship party: able seaman",
            "link": "/museum/biographies/Abrahams,_Frederick_G./",
            "image": null
        },
        ...
    ]
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n<p>Parameter values which perform a filtering operation are case-sensitive.</p>";
		$html .= "\n" . '<dl class="code">
			<dt><strong>id</strong> <em>string</em></dt>
				<dd>The article identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>baseUrl</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value</dd>
			<dt><strong>baseUrlPeople</strong> <em>string</em></dt>
				<dd>A string which will be prefixed to the URL value for each person</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such record ID.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain the list of categories featured in a collection
	public function apiCall_categories ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Require an article ID to be specified
		if (!isSet ($_GET['collection']) || !strlen ($_GET['collection'])) {
			return array ('error' => 'No collection ID was supplied.');
		}
		$collectionId = $_GET['collection'];
		
		# Get the collections
		$collections = $this->getCollectionsData ($baseUrl);
		
		# Validate the collection ID
		if (!isSet ($collections[$collectionId])) {
			return array ('error' => 'There is no such collection.');
		}
		
		# End if disabled
		if ($collections[$collectionId]['disableCategories']) {return array ();}
		
		# Determine whether to include the unclassified value
		$includeUnclassified = true;
		if (isSet ($_GET['includeUnclassified']) && ($_GET['includeUnclassified'] == '0')) {
			$includeUnclassified = false;
		}
		
		# Get the data
		$data = $this->getCategoriesData ($collections[$collectionId], $includeUnclassified);
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for categories
	public function apiCallDocumentation_categories ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches the list of categories featured within a specified collection, and shows the number of record instances for each category.</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/categories?collection=armc';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "Animal equipment": {
        "category": "Animal equipment",
        "count": 1,
        "classification": "Objects facet & Furnishings and Equipment & Tools and Equipment & Equipment & Animal equipment "
    },
    "Arrows": {
        "category": "Arrows",
        "count": 20,
        "classification": "Objects facet & Furnishings and Equipment & Weapons and Ammunition & weapons & <projectile weapons> & <projectile weapons with nonexplosive propellant> & <projectiles with nonexplosive propellant> & arrows"
    },
    "Bags": {
        "category": "Bags",
        "count": 13,
        "classification": "Objects facet & Furnishings and Equipment & Containers & containers & <containers by form> & bags"
    },
    ...
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>includeUnclassified</strong> <em>integer 1|0</em> default <em>1</em></dt>
				<dd>Whether to include any entry enumerating unclassified items</dd>
		</dl>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		$html .= "\n" . '<p>If no categories, an empty array object will be returned.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such collection.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain the list of materials featured in a collection
	public function apiCall_materials ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Require an article ID to be specified
		if (!isSet ($_GET['collection']) || !strlen ($_GET['collection'])) {
			return array ('error' => 'No collection ID was supplied.');
		}
		$collectionId = $_GET['collection'];
		
		# Get the collections
		$collections = $this->getCollectionsData ($baseUrl);
		
		# Validate the collection ID
		if (!isSet ($collections[$collectionId])) {
			return array ('error' => 'There is no such collection.');
		}
		
		# End if disabled
		if ($collections[$collectionId]['disableMaterials']) {return array ();}
		
		# Get the data
		$data = $this->getGroup ($collections[$collectionId]['collection'], 'Material');
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for materials
	public function apiCallDocumentation_materials ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches the list of materials featured within a specified collection, and shows the number of record instances for each material.</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/materials?collection=armc';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "antler": 3,
    "antler > caribou": 1,
    "antler ?": 6,
    "baleen > whale": 5,
    ...
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<p>None.</p>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		$html .= "\n" . '<p>If no materials, an empty array object will be returned.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such collection.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# API function to obtain the list of artists featured in a collection
	public function apiCall_artists ()
	{
		# Determine the baseUrl
		$baseUrl = (isSet ($_GET['baseUrl']) ? $_GET['baseUrl'] : false);
		
		# Require an article ID to be specified
		if (!isSet ($_GET['collection']) || !strlen ($_GET['collection'])) {
			return array ('error' => 'No collection ID was supplied.');
		}
		$collectionId = $_GET['collection'];
		
		# Get the collections
		$collections = $this->getCollectionsData ($baseUrl);
		
		# Validate the collection ID
		if (!isSet ($collections[$collectionId])) {
			return array ('error' => 'There is no such collection.');
		}
		
		# End if disabled
		if ($collections[$collectionId]['disableArtists']) {return array ();}
		
		# Get the data
		$data = $this->getGroup ($collections[$collectionId]['collection'], 'Artist');
		
		# Return the data, which will be JSON-encoded
		return $data;
	}
	
	
	# API documentation for artists
	public function apiCallDocumentation_artists ()
	{
		# Start the HTML
		$html = '';
		
		# Introduction
		$html .= "\n<p>This API call fetches the list of artists featured within a specified collection, and shows the number of record instances for each artist.</p>";
		
		# Example
		$html .= "\n" . '<h3 id="example">Example</h3>';
		$exampleUrl = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/artists?collection=polarart';
		$html .= "\n" . '<pre class="code">' . '<a href="' . htmlspecialchars (str_replace ('://', '://guest@', $exampleUrl)) . '">' . htmlspecialchars ($exampleUrl) . '</a>' . '</pre>';
		$html .= "\n" . '<p>Result:</p>';
		$html .= "\n" . '<pre class="code">' . htmlspecialchars ('
{
    "Adams, Edward": 104,
    "Back, George": 54,
    "Baguley, Raymond M.": 1,
    "Baston, Thomas": 1,
    ...
}') . '</pre>';
		
		# Request parameters
		$html .= "\n" . '<h3 id="parametersrequired">Request parameters - required</h3>';
		$html .= "\n" . '<dl class="code">
			<dt><strong>collection</strong> <em>string</em></dt>
				<dd>The collection identifier</dd>
		</dl>';
		$html .= "\n" . '<h3 id="parametersoptional">Request parameters - optional</h3>';
		$html .= "\n" . '<p>None.</p>';
		
		# Response
		$html .= "\n" . '<h3 id="response">Response</h3>';
		$html .= "\n" . '<p>JSON object as above.</p>';
		$html .= "\n" . '<p>If no artists, an empty array object will be returned.</p>';
		
		# Error response
		$html .= "\n" . '<h3 id="error">Error response</h3>';
		$html .= "\n" . '<p>JSON object containing an error key and a text string.</p>';
		$html .= "\n" . '<p>Example error (text string will vary):</p>';
		$html .= "\n" . '<pre class="code">
{
    "error": "<span class=\"warning\">There is no such collection.</span>"
}</pre>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Image thumbnail serving, which takes a moniker (so that the URLs are clean and definitive) and convert to an ID (so that the underlying file can be retrieved)
	public function images ()
	{
		# Ensure there is a moniker
		if (!$moniker = (isSet ($_GET['id']) && strlen ($_GET['id']) ? $_GET['id'] : false)) {
			echo 'ERROR: No ID supplied.';
			application::sendHeader (404);
			return;
		}
		
		# Ensure there is a namespace, and that it is supported
#		$namespaces = array ('articles', 'biographies', 'expeditions');
		$namespaces = array ('biographies');
		if (!$namespace = (isSet ($_GET['namespace']) && in_array ($_GET['namespace'], $namespaces) ? $_GET['namespace'] : false)) {
			echo 'ERROR: No valid namespace supplied.';
			application::sendHeader (404);
			return;
		}
		
		# Get the index, from 1
		if (!$index = (isSet ($_GET['index']) && ctype_digit ($_GET['index']) ? $_GET['index'] : false)) {
			echo 'ERROR: No valid number supplied.';
			application::sendHeader (404);
			return;
		}
		
		# Get the size
		if (!$size = (isSet ($_GET['size']) && in_array ($_GET['size'], $this->settings['supportedImageSizes']) ? $_GET['size'] : false)) {
			echo 'ERROR: No valid size supplied.';
			application::sendHeader (404);
			return;
		}
		
		# Get the shape
		$shape = (isSet ($_GET['shape']) ? $_GET['shape'] : false);
		if ($shape && !in_array ($shape, $this->settings['supportedImageShapes'])) {
			echo 'ERROR: Invalid shape supplied.';
			application::sendHeader (404);
			return;
		}
		
		# Load the image library
		require_once ('image.php');
		
		# If the image is already present, serve as-is
		$thumbnailFile = $this->thumbnailFile ($namespace, $moniker, $index, $size, $shape);
		if (file_exists ($thumbnailFile)) {
			image::serve ($thumbnailFile);
			return;
		}
		
		#!# Hacky workaround
		$disableDotToSlashConversion = ($namespace == 'biographies');
		
		# Convert the moniker to an ID
		$recordId = $this->recordIdFromMoniker ($moniker, $disableDotToSlashConversion);
		
		# Get the ID from the database
		$imageString = $this->databaseConnection->selectOneField ($this->settings['database'], $namespace, 'image', array ('id' => $recordId), array ('image'));
		
		# End if none
		if (!$imageString) {
			echo 'ERROR: No such image.';
			application::sendHeader (404);
			return;
		}
		
		# Convert image string to array
		$images = $this->unpipeList ($imageString);
		
		# Select the image in the set to use
		$file = $images[ ($index - 1) ];
		
		# Write the thumbnail
		if (!$this->writeThumbnail ($file, $size, $shape, $thumbnailFile, /* Variables needed for workaround for legacy records without path: */ $namespace, $recordId, $errorText /* returned by reference */)) {
			echo $errorText;
			application::sendHeader (500);
			return;
		}
		
		# Serve the newly-generated thumbnail image
		image::serve ($thumbnailFile);
		return;
	}
	
	
	# Function to write a thumbnail
	public function writeThumbnail ($file, $size, $shape, $thumbnailFile, /* Variables needed for workaround for legacy records without path: */ $namespace, $recordId, &$errorText = false)
	{
		# Determine the file location from the database-stored value
		$file = $this->imageServerPath ($file, /* Variables needed for workaround for legacy records without path: */ $namespace, $recordId);
		
		# Ensure the size is supported, to prevent the API emitting full-size images; this check should not be necessary as the API interface does a similar check, but is added here for safety
		if (!ctype_digit ($size) || !in_array ($size, $this->settings['supportedImageSizes'])) {		// Digit check to avoid situation where e.g. '300foo' will pass as valid, but resize will ignore that, resulting in full-size image
			$errorText = 'Unsupported image size.';
			return false;
		}
		
		# End if the file still cannot be found
		if (!file_exists ($file)) {
			$errorText = "ERROR: Unable to create thumbnail as the referenced file ($file) could not be located.";
			return false;
		}
		
		# Set the width and height, so that the dominant dimension is the specified size, e.g. a landscape image allocates the size to the height, and then scales the width accordingly
		list ($width, $height, $type_ignored, $attributes_ignored) = getimagesize ($file);
		if ($width > $height) {
			$newWidth = $size;
			$newHeight = '';		// Auto
		} else {
			$newWidth = '';			// Auto
			$newHeight = $size;
		}
		
		# Crop if required to square
		$cropWidth = false;
		$cropHeight = false;
		if ($shape == 'square') {
			
			# Cropping needs to ensure both the height and width are at least the size, so the dominant dimension may have to be reversed to expand one side of the image beyond the square, after which the edges in the other dimension are then chopped
			if ($width > $height) {
				$newWidth = '';
				$newHeight = $size;	// Set the height to dominate
			}
			if ($height > $width) {
				$newWidth = $size;	// Set the width to dominate
				$newHeight = '';
			}
			
			# Set the crop width and height
			$cropWidth = $size;
			$cropHeight = $size;
		}
		
		# Enable watermarking, by defining a callback function, below
		$watermarkCallback = array ($this, 'watermarkImagick');
		
		# Resize the image
		ini_set ('max_execution_time', 30);
		require_once ('image.php');
		image::resize ($file, 'jpg', $newWidth, $newHeight, $thumbnailFile, $watermarkCallback, true, true, $cropWidth, $cropHeight);
		
		# Return success
		return true;
	}
	
	
	# Function to determine the original image location from the database-stored value
	private function imageServerPath ($file, /* Workaround for legacy records without path: */ $namespace, $recordId)
	{
		# Convert the location in Windows format to its Unix equivalent
		$file = str_replace ('\\', '/', $file);
		$file = preg_replace ('|^X:/spripictures|', $this->settings['imageStoreRoot'], $file);
		$tryPaths = array ('');		// Single path to try, with no root to prepend
		
		# If the image is from a legacy record which does not have the full path, determine the path based on the collection identifier(s)
		#!# This can be deleted when all records have a path
		if (!preg_match ("|^{$this->settings['imageStoreRoot']}|", $file)) {
			$tryPaths = $this->getImagesSubfolders_legacyRecords ($namespace, $recordId);
		}
		
		# Try each path, both with the original file extension and upper-cased
		#!# Need a report that finds cases like /article/p2005.5.1/ that have .jpg in the record but the file is .tif, etc.
		$tryExtensions = array ('jpg', 'tif', 'JPG', 'TIF');
		$basename = preg_replace ('/\.(' . implode ('|', $tryExtensions) . ')$/', '', $file);		// Cannot use pathinfo ($file, PATHINFO_FILENAME), as images may be specified in MODES in format 'subfolder/file.jpg'
		foreach ($tryPaths as $tryPath) {
			foreach ($tryExtensions as $tryExtension) {
				$tryFile = $tryPath . $basename . '.' . $tryExtension;
				if (file_exists ($tryFile)) {
					$file = $tryFile;
					break 2;
				}
			}
		}
		
		# Return the file path
		return $file;
	}
	
	
	# Function to get the images subfolders
	private function getImagesSubfolders_legacyRecords ($namespace, $recordId)
	{
		# Apply only to the records namespace; end for others
		if ($namespace != 'records') {
			return array ();
		}
		
		# Constrain to collections for this record, to encourage exactness of cataloguing, e.g. "|ANTC||ARMC|"
		$collectionsString = $this->databaseConnection->selectOneField ($this->settings['database'], 'records', 'Collection', array ('id' => $recordId), array ('Collection'));
		$collections = $this->unpipeList ($collectionsString);
		$constraint = array ();		// All by default
		if ($collections) {
			$constraint['collection'] = $collections;	// List results in IN(..., ..)
		}
		
		# Get the pairs
		$paths = $this->databaseConnection->selectPairs ($this->settings['database'], 'collections', $constraint, array ('imagesSubfolder'));
		
		# Remove thumbnails/ from start
		foreach ($paths as $index => $path) {
			$paths[$index] = preg_replace ('|^/thumbnails|', '', $path);
		}
		
		# Add image store root to the start of each
		foreach ($paths as $index => $path) {
			$paths[$index] = $this->settings['imageStoreRoot'] . $path;
		}
		
		# Return the data
		return $paths;
	}
	
	
	# Function to determine the thumbnail file
	#!# Move into articleModel then make private
	public function thumbnailFile ($namespace, $id, $index, $size = 300, $shape = false)
	{
		# Assemble the thumbnail location
		$thumbnailFile = $_SERVER['DOCUMENT_ROOT'] . $this->thumbnailLocation ($namespace, $id, $index, $size, $shape);
		
		# Return the path
		return $thumbnailFile;
	}
	
	
	# Function to determine the thumbnail location (for use as the URL in the API output)
	#!# Move into articleModel then make private
	public function thumbnailLocation ($namespace, $id, $index /* from 1 */, $size, $shape = false)
	{
		# Determine the local directory
		$thumbnailLocation = $this->baseUrl . '/images/' . $namespace . '/' . 'size' . $size . $shape . '/' . $id . '_' . $index . '.jpg';
		
		# Return the path
		return $thumbnailLocation;
	}
	
	
	# Callback from image::resize
	public function watermarkImagick (&$imageHandle, $height)
	{
		# Magickwand implementation
		if (extension_loaded ('magickwand')) {
			$textWand = NewDrawingWand ();
			DrawAnnotation ($textWand, 8, $height - 30, '(c) ' . $this->settings['organisationName']);
			DrawAnnotation ($textWand, 8, $height - 18, $_SERVER['SERVER_NAME']);
			MagickDrawImage ($imageHandle, $textWand);
			
		# ImageMagick implementation
		} else if (extension_loaded ('imagick')) {
			$draw = new ImagickDraw ();
			$draw->annotation (8, $height - 30, '(c) ' . $this->settings['organisationName']);
			$draw->annotation (8, $height - 18, $_SERVER['SERVER_NAME']);
			$imageHandle->drawImage ($draw);
		}
	}
	
	
	# Function to list the reports
	public function reports ($id = false)
	{
		# Subclass
		require_once ('reportsController.php');
		$reportsController = new reportsController ();
		echo $reportsController->getHtml ();
	}
}


?>
