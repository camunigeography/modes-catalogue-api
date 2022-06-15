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
	public function getBiographyData ($baseUrl, $collectionId, $id = false, $fields = array (), $imageSize, $baseUrlExpeditions = false, $random = false, $forceId = false)
	{
		# Determine which database function to use
		$databaseFunction = ($id ? 'selectOne' : 'select');
		
		# Add limitations
		$conditions = array ();
		if ($id) {
			$conditions['id'] = $id;
		}
		#!# This is sometimes receiving the special token '?' as a value
		if ($collectionId) {
			$conditions['collection'] = $collectionId;
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
	#!# Move into articleModel
	public function monikerFromRecordId ($id)
	{
		$replacements = array (
			' ' => '_',
			'/' => '.',
		);
		return strtr ($id, $replacements);
	}
	
	
	# Function to determine the record ID from the moniker
	public function recordIdFromMoniker ($moniker, $disableDotToSlashConversion)
	{
		$replacements = array ();
		$replacements['_'] = ' ';
		if (!$disableDotToSlashConversion) {
			$replacements['.'] = '/';
		}
		return strtr ($moniker, $replacements);
	}
	
	
	# Function to create a URL from an ID
	public function urlFromId ($id, $baseUrl)
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
	public function getExpeditionData ($baseUrl, $collectionId, $id = false, $fields = array ())
	{
		# Determine which database function to use
		$databaseFunction = ($id ? 'selectOne' : 'select');
		
		# Add limitations
		$conditions = array ();
		if ($id) {
			$conditions['id'] = $id;
		}
		if ($collectionId) {
			$conditions['collection'] = $collectionId;
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
	public function getCategoriesData ($collection, $includeUnclassified = true)
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
	public function getGroup ($collectionId, $field)
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
	
	
	# Helper function to explode a pipe/double-pipe -separated list
	public function unpipeList ($string)
	{
		if (!$string) {return array ();}
		$list = explode ('|', $string);
		foreach ($list as $index => $item) {
			if (!strlen ($item)) {unset ($list[$index]);}
		}
		return array_values ($list);
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
	#!# Move into articleModel
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
