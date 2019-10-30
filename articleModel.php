<?php

# Article model class for the MODES catalogue viewer
class articleModel
{
	# Define sections
	#!# Ideally this would be done as a field and dependency definition pattern
	private $sections = array (
		'id',
		'type',
		'status',
		'collections',
		'context',
		'title',
		'briefDescription',
		'objectType',
		'objectName',				// Uses article
		'medium',
		'category',
		'artist',
		'classifiedNames',			// Uses article
		'fieldCollection',			// Uses article
		'materials',				// Uses article
		'numberOfItems',			// Uses article
		'note',						// Uses article
		'fullDescription',			// Uses article
		'relatedRecords',			// Uses article
		'dimensions',				// Uses article
		'placeName',				// Uses article
		'associatedPerson',			// Uses article
		'associatedOrganisation',	// Uses article
		'associatedExpedition',		// Uses article
		'images',
		'imageBy',					// Uses article
		'imageColour',				// Uses article
		'navigationIds',
		'navigationIdsAdditional',
	);
	
	
	# Constructor
	public function __construct ($modesCatalogueApi, $settings, $databaseConnection)
	{
		# Convert parameters to class properties
		$this->modesCatalogueApi = $modesCatalogueApi;	// Handle to calling class
		$this->settings = $settings;
		$this->databaseConnection = $databaseConnection;
		
	}
	
	
	# Getter to return the structured data
	public function getOne ($id, $collectionId = false, $includeXml = false)
	{
		# Get the data for the article; note that BINARY is used to force case sensitivity
		$query = "
			SELECT *
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE
				    (Status != 'R' OR Status IS NULL)
				AND BINARY id = :id
			LIMIT 1
		;";
		$preparedStatementValues = array ('id' => $id);
		
		# Get the record from the table or end
		if (!$record = $this->databaseConnection->getOne ($query, false, true, $preparedStatementValues)) {
			return array ('error' => 'There is no such record ID.');
		}
		
		# Decorate the record
		$record = $this->decorateArticle ($record, $collectionId, array (), $includeXml);
		
		# Return the record
		return $record;
	}
	
	
	# Function to get articles data
	public function getArticlesData ($baseUrl, $collection = false, $searchPhrase = false, $category = false, $material = false, $artist = false, $requireImages = false, $random = 0, $requestedPage = 1)
	{
		# Start a list of constraints
		$where = array ();
		$preparedStatementValues = array ();
		$paginationRecordsPerPage = $this->settings['paginationRecordsPerPage'];
		
		# Determine default ordering
		#!# OLD DOCUMENTATION: "ORDER BY " is only needed for the general listings because the Museum/Art records are two sets of serial records. ORDER BY is not needed by default because the MyISAM storage means that the natural order coming out of Modes. Note that this however fails if ONLY id is returned
		#!# This algorithm (splitting of _prefix and _suffix) can't solve the problem of records with a / in, i.e. Y: 69/10/1/55 still comes before Y: 69/10/1/6. However, it's a lot better than just sorting by id only. The only real fix is a unified catalogue file rather than two imports.
		$orderByClause = ' ORDER BY id_prefix, id_suffix, id';
		
		# Items must not be private
		$where[] = "(Status != 'R' OR Status IS NULL)";
		
		# Fix searches within a particular collection to match that collection's ID
		#!# Need to rename as $collectionId
		if ($collection) {
			
			#!# NB Most of the time, $collection is already validated, so this is wasted CPU
			
			# Get the collections
			$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl);
			
			# Validate the collection ID
			if (!isSet ($collections[$collection])) {
				return array ('error' => 'There is no such collection.');
			}
			
			$preparedStatementValues['collectionWithin'] = "%|{$collections[$collection]['collection']}|%";	// Collections are surrounded by |...|, whether one or more
			$where[] = "Collection LIKE BINARY :collectionWithin";
		} else {
			#!# Not clear what this is for
			$where[] = "(Collection != '' OR Collection IS NOT NULL)";
		}
		
		# Add limitation for randomisation
		if ($random) {
			$where[] = "(Status != 'P' OR Status IS NULL)";
			$where[] = "`PhotographFilename` != ''";
			$orderByClause = ' ORDER BY RAND()';
			$paginationRecordsPerPage = $random;	// Basically LIMIT
		}
		
		# Add limitation for requiring images
		if ($requireImages) {
			$where[] = "`PhotographFilename` != '' AND `PhotographFilename` IS NOT NULL";
		}
		
		# Make the search phrase safe
		if ($searchPhrase) {
			$searchPhrase = trim ($searchPhrase);
			$preparedStatementValues['searchPhrase'] = $searchPhrase;
			$preparedStatementValues['searchPhraseLike'] = '%' . $searchPhrase . '%';
			$where[] = "(
					   id = :searchPhrase
					OR Title LIKE :searchPhraseLike
					OR BriefDescription LIKE :searchPhraseLike
					OR Description LIKE :searchPhraseLike	/* NB using description within the search slows it down a fair amount */
					OR Artist LIKE :searchPhraseLike
					OR Category LIKE :searchPhraseLike
					OR Material LIKE :searchPhraseLike
				)";
		}
		
		# Adjust category name
		if ($category) {
			$preparedStatementValues['category'] = $category;
			$preparedStatementValues['categoryWithin'] = "%|{$category}|%";
			$where[] = "(Category = :category OR Category LIKE :categoryWithin)";
		}
		
		#!# Mismatch between counts: /museum/catalogue/armc/materials/fabric+%3E+cotton/ has 39 items but /museum/catalogue/armc/materials/ says 43
		if ($material) {
			$preparedStatementValues['material'] = $material;
			$preparedStatementValues['materialWithin'] = "%|{$material}|%";
			$where[] = "(Material = :material OR Material LIKE :materialWithin)";
		}
		
		# Adjust artist name
		if ($artist) {
			$preparedStatementValues['artist'] = $artist;
			$preparedStatementValues['artistWithin'] = "%|{$artist}|%";
			$where[] = "(LOWER(Artist) = :artist OR Artist LIKE :artistWithin)";
		}
		
		# Define fields which will appear in the eventual output (not all of these exist in the actual data)
		$filterToFields = array ('id', 'status', 'images', 'title', 'collections', 'briefDescription');
		
		# Determine the fields to use
		$fields  = '*';
		$fields .= ', data';		// #!# Needed for fields marked 'Uses article' as above; ideally this would only be included when required, as it is very large, but this requires a dependency definition system
		
		# Assemble the query
		$query = "SELECT
				{$fields}
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE " . implode (' AND ', $where)
			. $orderByClause
			. ';';
		
		# Get the data via pagination
		list ($data, $totalAvailable, $totalPages, $page, $actualMatchesReachedMaximum) = $this->databaseConnection->getDataViaPagination ($query, "{$this->settings['database']}.{$this->settings['table']}", true, $preparedStatementValues, array (), $paginationRecordsPerPage, $requestedPage);
		
		# Decorate the data
		foreach ($data as $id => $record) {
			$data[$id] = $this->decorateArticle ($record, $collection, $filterToFields, false);
		}
		
		# Ensure the page is not being exceeded
		if ($data) {
			if ($requestedPage > $page) {
				return array ('error' => 'Invalid page.');
			}
		}
		
		# Lookup related terms
		$relatedTerms = $this->getRelatedTerms ($searchPhrase);
		
		# Assemble the data
		$data = array (
			'pagination' => array (
				'count'			=> count ($data),
				'total'			=> (int) $totalAvailable,
				'page'			=> $page,
				'totalPages'	=> $totalPages,
			),
			'relatedTerms' => $relatedTerms,
			'articles' => $data,
		);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get lookups
	#!# May be able to refactor further
	private function getRelatedTerms ($searchPhrase)
	{
		# Return empty array if no search term
		if (!$searchPhrase) {return array ();}
		
		# Get all the lookups
		$query = "SELECT Term,BroaderTerm,NarrowerTerm,SeeAlso,PreferredTerm,UseFor FROM {$this->settings['database']}.lookups;";
		if (!$lookupsRaw = $this->databaseConnection->getData ($query, "{$this->settings['database']}.lookups")) {return false;}
		foreach ($lookupsRaw as $key => $value) {
			#!# Workaround for bug in database.php
			unset ($lookupsRaw[$key]['Term']);
		}
		
		# Lower-case the keys
		foreach ($lookupsRaw as $key => $value) {
			$key = strtolower ($key);
			$lookups[$key] = $value;
		}
		
		# End if none
		$searchPhrase = strtolower (trim ($searchPhrase));
		if (!isSet ($lookups[$searchPhrase])) {return false;}
		
		# Loop through each element of the lookup
		$result = array ();
		foreach ($lookups[$searchPhrase] as $key => $item) {
			if ($key == 'UseFor') {continue;}
			if (!$item = str_replace ("\r\n", "\n", trim ($item))) {
				continue;
			}
			$key = application::unCamelCase ($key);
			$result[$key] = explode ("\n", $item);
		}
		if (!$result) {return false;}
		
		# Return the result
		return $result;
	}
	
	
	# Function to decorate an article
	private function decorateArticle ($record, $collectionId, $filterToFields = array (), $includeXml = false)
	{
		# Determine the collections associated with this record
		$this->collections = $this->parseCollections ($record['Collection']);
		
		# Ensure the collection matches if a collection is specified
		
		# If an automatic collection lookup is specified, use the first
		if ($collectionId == '?') {
			if ($this->collections) {
				$collectionId = $this->collections[0];
			}
		}
		
		# Convert the article XML data to an array
		require_once ('xml.php');
		$article = xml::xml2array ($record['data'], false, false, false, false);
		
		# Assemble the data
		foreach ($this->sections as $section) {
			if ($filterToFields) {
				if (!in_array ($section, $filterToFields)) {continue;}
			}
			$function = 'get' . ucfirst ($section);		// e.g. getDimensions
			$data[$section] = $this->{$function} ($article, $record, $collectionId);
		}
		
		# Include the XML if required
		if ($includeXml) {
			$data['xml'] = $record['data'];
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to parse the collection value
	private function parseCollections ($string)
	{
		# Return empty array if none
		if (!$string) {return array ();}
		
		# Explode out
		preg_match_all ('~\|([^|]+)\|~', $string, $matches);	// i.e. |something| or |something||somethingelse| etc.
		$collections = $matches[1];	// Array of all matches
		
		# Return the list
		return $collections;
	}
	
	
	/* Field implementations */
	
	#!# Some of the lookups here should be replaced with XPath lookups
	#!# ReproductionFilename,Reproduction/Filename no longer being used
	
	
	# Function to obtain the ID
	private function getId ($article, $record)
	{
		return $record['id'];
	}
	
	
	# Function to obtain the type
	private function getType ($article, $record)
	{
		return $record['Type'];
	}
	
	
	# Function to obtain the status
	private function getStatus ($article, $record)
	{
		return $record['Status'];
	}
	
	
	# Function to obtain the collections
	private function getCollections ($article, $record)
	{
		#!# Hacky
		# Convert each ID to a moniker
		$collections = array ();
		foreach ($this->collections as $index => $collectionId) {
			$collections[$index] = $this->databaseConnection->selectOneField ($this->settings['database'], 'collections', 'id', array ('collection' => $collectionId));
		}
		
		return $collections;
	}
	
	
	# Function to obtain the context
	#!# Aim to get rid of this field or document it better; currently it is used to check for 'ff' items and show a message
	private function getContext ($article, $record)
	{
		return $record['Context'];
	}
	
	
	# Function to obtain the title
	private function getTitle ($article, $record)
	{
		return ucfirst ($record['Title']);
	}
	
	
	# Function to obtain the brief description
	private function getBriefDescription ($article, $record)
	{
		return $record['BriefDescription'];
	}
	
	
	# Function to obtain the object type
	private function getObjectType ($article, $record)
	{
		# End if a picture
		if ($record['Type'] == 'picture') {return NULL;}
		
		# End if none
		if (!$record['ObjectType']) {return false;}
		
		# Return the object type
		return ucfirst ($record['ObjectType']);
	}
	
	
	# Function to obtain the object name
	private function getObjectName ($article, $record)
	{
		#!# Add support for multiple entries, e.g. /museum/catalogue/article/n747/ should have "Bag" and "nangmaut"
		#!# Convert to array output type, and display line-by-line
		
		# End if not present
		if (!isSet ($article['Identification']) || !isSet ($article['Identification']['ObjectName']) || !isSet ($article['Identification']['ObjectName']['Keyword'])) {return NULL;}
		
		# Obtain the object type
		$objectType = $article['Identification']['ObjectName']['Keyword'];
		
		# Return the object type
		return $objectType;
	}
	
	
	# Function to obtain the medium, relevant only to pictures (e.g. 'Sketch', 'Watercolour', etc.)
	private function getMedium ($article, $record)
	{
		# End if not a picture
		if ($record['Type'] != 'picture') {return NULL;}
		
		# End if none
		if (!$record['ObjectType']) {return false;}
		
		# Return the object type
		return ucfirst ($record['ObjectType']);
	}
	
	
	# Function to obtain the category
	private function getCategory ($article, $record)
	{
		# End if no category
		if (!$record['Category']) {return NULL;}
		
		return $record['Category'];
	}
	
	
	# Function to obtain the artist
	private function getArtist ($article, $record)
	{
		# End if not an artist
		if (!$record['Artist']) {return NULL;}
		
		# Return the artist
		return $record['Artist'];
	}
	
	
	# Function to parse classified names
	private function getClassifiedNames ($article)
	{
		# End if no such section of the record
		if (!isSet ($article['Identification']['Classification'])) {return false;}
		
		# Normalise to multiple
		$article['Identification']['Classification'] = $this->normaliseToMultiple ($article['Identification']['Classification']);
		
		# Get each item
		$classifiedNames = array ();
		foreach ($article['Identification']['Classification'] as $index => $attributes) {
			
			# Skip if no keyword
			if (!isSet ($attributes['Keyword'])) {continue;}
			
			# Assign the key
			if (isSet ($attributes['System'])) {
				$key = (isSet ($attributes['System']['System']) ? $attributes['System']['System'] : $attributes['System']);	// Deal with cases with a note, e.g. /article/n30.2a-b/ and /api/article?id=N%3A+30%2F2a-b&collection=?
			} else {
				$key = '[Unknown system]';
			}
			
			# Assign the value
			$value = $attributes['Keyword'];
			
			# Convert values arranged as array(a,b,c) into (str) a > b > c
			$value = $this->convertHierarchical ($value, $ucFirst = true);		#!# ucfirst To be cleaned
			
			# Add to the master array
			$classifiedNames[$key] = $value;
		}
		
		# Define optional key name substitutions
		$labels = array (
			'Cultural affiliation - current' => 'Current cultural affiliation',
			'Cultural affiliation - former' => 'Former cultural affiliation',
			'Geographic area - current' => 'Current place name',
			'Geographic area - former' => 'Former place name',
			'Cultural affiliation - subgroup' => 'Cultural subgroup',
			'Gender' => 'Gender',	#!# Shouldn't be needed as these substitutions are optional
			'gender' => 'Gender',	#!# To be cleaned
			'AAT' => 'Getty AAT',	// NB If changing this, it must also be changed in the view/controller, as the autolinker depends on this phrase
		);
		
		$record = array ();
		foreach ($classifiedNames as $key => $value) {
			$label = (isSet ($labels[$key]) ? $labels[$key] : $key);
			#!# Problem here is that can't have multiple, so need to append to $value if already exists, then adjust the view if required
			$record[$label] = $value;
		}
		
		# Return the list
		return $record;
	}
	
	
	# Function to parse field collection
	#!# Not yet reviewed for potential refactorings
	#!# /museum/catalogue/article/n246a-f/ has (person: "", organisation: "")
	private function getFieldCollection ($article)
	{
		# End if no field collection group
		if (!isSet ($article['FieldCollection'])) {return false;}
		
		# Determine if the field collections is an associative array of collections or a single collection
		#!# Is this still needed?
		$fieldCollection = (isSet ($article['FieldCollection'][0]) ? $article['FieldCollection'][0] : $article['FieldCollection']);
		
		# Determine field collection types
		$types = array ('Person', 'Organisation', 'Place', 'Date', 'CollectionNumber');
		
		# Create each item
		$fields = array ();
		foreach ($fieldCollection as $key => $value) {
			
			# Skip if not in the array of field collection types
			if (!in_array ($key, $types)) {continue;}
			
			# Uppercase the keyname
			$key = ucfirst (strtolower ($key));
			
			# Skip if no value
			if (!$value) {continue;}
			
			# Person type
			if ($key == 'Person') {
				if (!isSet ($value['PersonIdentity'])) {continue;}
				$value = $value['PersonIdentity'];		// "<a href=\"{$this->baseUrl}/biographies/{$value['REFERENCE-NUMBER']}.html\">{$value['PERSON']}</a>";
			}
			
			# Organisation type
			if ($key == 'Organisation') {
				if (!isSet ($value['OrganisationIdentity'])) {continue;}
				$value = $value['OrganisationIdentity'];		// "<a href=\"{$this->baseUrl}/expeditions/{$value['REFERENCE-NUMBER']}.html\">{$value['CORPORATE-BODY']}</a>";
			}
			
			# Place type
			if ($key == 'Place') {
				if (!isSet ($value['PlaceName'])) {continue;}
				$value = $value['PlaceName'];		// "<a href=\"{$this->baseUrl}/biographies/{$value['REFERENCE-NUMBER']}.html\">{$value['PERSON']}</a>";
			}
			
			# Date type
			if ($key == 'Date') {
				
				# If an array, take only the first
				#!# Ideally all would be used, but the NOTE field mapping to multiple DATE fields in MODES is inconsistent
				if (is_array ($value)) {
					foreach ($value as $subKey => $subValue) {
						$value = $subValue;
						break;
					}
				}
				
				# String conversions
				$value = str_replace ('=', '-', $value);
				$value = str_replace ('.', '/', $value);
				if ($value == 'nd') {$value = '[No date]';}
			}
			
			# Convert hierarchical values
			$value = $this->convertHierarchical ($value);
			
			# Skip if no value
			if (!$value) {continue;}
			
			# Add to the list
			$fields[$key] = $value;
		}
		
		# Return the fields
		return $fields;
	}
	
	
	# Function to get materials
	private function getMaterials ($article)
	{
		# End if none
		if (!isSet ($article['Description'])) {return array ();}
		#!# /museum/catalogue/article/y2014.8a-i/
		//application::dumpData ($article['Description']);
		if (!isSet ($article['Description']['Material'])) {return array ();}
		
		
		# If a simple string, convert to the nested structure used in most records
		if (is_string ($article['Description']['Material'])) {
			$article['Description']['Material'] = array ('Keyword' => $article['Description']['Material']);
		}
		
		# Normalise to multiple
		$article['Description']['Material'] = $this->normaliseToMultiple ($article['Description']['Material']);
		
		/*	# We now have a structure like the following, with one or more materials, where the keyword for each is an array or a string:
			
			Array
			(
			    [0] => Array
			        (
			            [Keyword] => Array
			                (
			                    [0] => skin
			                    [1] => seal
			                )
			        )
					
			    [1] => Array
			        (
			            [Keyword] => paint
			        )
		*/
		
		# Add each material (each of which might have an (a,b) reference at the end)
		$materials = array ();
		foreach ($article['Description']['Material'] as $material) {
			
			# Skip if no keyword
			#!# Is this still needed?
			// if (!isSet ($material['Keyword'])) {continue;}
			
			# Convert Keyword if not already an array
			if (!is_array ($material['Keyword'])) {
				$material['Keyword'] = array ($material['Keyword']);
			}
			
			# Trim each Keyword
			foreach ($material['Keyword'] as $index => $keyword) {
				$material['Keyword'][$index] = trim ($keyword);
			}
			
			# If any keywords are empty, skip them
			$empty = array ();
			foreach ($material['Keyword'] as $index => $keyword) {
				if (!strlen ($keyword)) {
					unset ($material['Keyword'][$index]);
					$empty[] = $index;
				}
			}
			if ($empty) {
				#!# Report to admin as error so that this can be fixed up; removed values are in $empty
				#!# Known cases are: Y: 2009/31
			}
			
			# Skip this block if none
			if (!$material['Keyword']) {continue;}
			
			# Implode Keyword to string
			$materialString = implode (' > ', $material['Keyword']);
			
			# Add on Part if present
			if (isSet ($material['Note'])) {
				$part = trim ($material['Note']);
				$materialString .= " ({$part})";
			}
			
			# Register the material
			$materials[] = $materialString;
		}
		
		# Return the array
		return $materials;
	}
	
	
	# Function to get the number of items
	private function getNumberOfItems ($article)
	{
		# End if not present
		if (!isSet ($article['NumberOfItems'])) {return false;}
		
		# Normalise to flattened
		$article['NumberOfItems'] = $this->normaliseToFlattened ($article['NumberOfItems']);
		
		# Return the value, as an integer
		return (int) $article['NumberOfItems'];
	}
	
	
	# Function to get the note
	private function getNote ($article)
	{
		# End if not present
		if (!isSet ($article['Identification'])) {return false;}
		if (!isSet ($article['Identification']['Note'])) {return false;}
		
		# If an array, take the first only (by observation, this avoids making some possibly private information public in Y: 2010/10/38)
		#!# Report to admin as error so that this can be fixed up
		#!# Known cases are: Y: 2010/10/38
		if (is_array ($article['Identification']['Note'])) {
			$article['Identification']['Note'] = $article['Identification']['Note'][0];
		}
		
		# Return the note
		return $article['Identification']['Note'];
	}
	
	
	# Function to get the full description
	private function getFullDescription ($article, $record)
	{
		# Full description is not relevant to pictures
		if ($record['Type'] == 'picture') {return NULL;}
		
		# If there is a Note as the first item at top level of the Description, just return that
		#!# Report to admin as error so that this can be fixed up
		# Known cases are: Y: 2014/8a-i
		if (isSet ($article['Description']) && isSet ($article['Description'][0]) && isSet ($article['Description'][0]['Note'])) {
			if (count ($article['Description'][0]) == 1) {	// i.e. avoid cases like record N: 92a-b where the Note is amongst other fields
				return $article['Description'][0]['Note'];
			}
		}
		
		# Detect cases of /Description/[i]/Aspect and convert to /Description/Aspect/[i]
		if (isSet ($article['Description']) && isSet ($article['Description'][0]) && isSet ($article['Description'][0]['Aspect'])) {
			#!# Report to admin as error so that this can be fixed up
			#!# Known cases are: N: 828a-c (which then shows the description three times, one for each part
			foreach ($article['Description'] as $index => $descriptionBlock) {
				$article['Description']['Aspect'][$index] = $descriptionBlock['Aspect'];
				unset ($article['Description'][$index]);
			}
		}
		
		# End if not present
		if (!isSet ($article['Description'])) {return false;}
		if (!isSet ($article['Description']['Aspect'])) {return false;}
		if (!is_array ($article['Description']['Aspect'])) {return false;}
		
		# Normalise to multiple
		$article['Description']['Aspect'] = $this->normaliseToMultiple ($article['Description']['Aspect']);
		
		# Loop through each description aspect
		$descriptions = array ();
		foreach ($article['Description']['Aspect'] as $item) {
			
			# Skip if empty
			#!# Is this required any more?
			// if (!is_array ($item)) {continue;}
			
			# Flatten SummaryText if nested, e.g. as required in record N: 55, where the SummaryText has an associated note
			if (is_array ($item['SummaryText']) && isSet ($item['SummaryText']['SummaryText'])) {
				$item['SummaryText'] = $item['SummaryText']['SummaryText'];
			}
			
			# Obtain the summary text
			$description = $item['SummaryText'];
			
			# Trim and ucfirst
			$description = ucfirst (trim ($description));
			
			# If the Part is nested, flatten
			#!# Report to admin as error so that this can be fixed up
			#!# Known cases are: N: 828a-c
			$item['Part'] = $this->normaliseToFlattened ($item['Part']);
			
			# Determine the part title
			$part = ((isSet ($item['Part']) && $item['Part']) ? trim ($item['Part']) : false);
			
			# If there is a part, and there is more than one item, prepend the part
			if ($part) {
				if (count ($article['Description']['Aspect']) > 1) {
					#!# Why is the HTML working?
					$description = '<em>' . $part . '</em>: ' . $description;
				}
			}
			
			# Register the description
			$descriptions[] = $description;
		}
		
		# Implode by double newline
		$description = implode ("\n\n", $descriptions);
		
		# Return the description
		return $description;
	}
	
	
	# Function to get related records
	private function getRelatedRecords ($article)
	{
		# End if not present
		if (!isSet ($article['RelatedObject'])) {return array ();}
		
		# End if none
		if (!is_array ($article['RelatedObject'])) {return array ();}
		
		# Normalise to multiple
		$article['RelatedObject'] = $this->normaliseToMultiple ($article['RelatedObject']);
		
		/* #!#
			RelatedObject is a very inconsistent field and needs serious tidying in the data.
			These sample records, picked at random, all have different layouts:
				Y: 62/15/1
				LS99/3/15
				N: 26b
				N: 433
				Y: 76/7/1
				N: 1767
		*/
		
		# Loop through each related object
		$relatedObjects = array ();
		foreach ($article['RelatedObject'] as $relatedRecord) {
			
			# Skip if empty (which seems quite common)
			if (empty ($relatedRecord)) {continue;}
			
			# Skip if ObjectIdentity does not exist or is empty
			if (!isSet ($relatedRecord['ObjectIdentity'])) {continue;}
			if (!$relatedRecord['ObjectIdentity']) {continue;}
			
			# Support format of an array containing single 'Number' value (e.g. record N: 26b): array ('Number' => value)
			if (isSet ($relatedRecord['ObjectIdentity']['Number'])) {
				# Skip if empty
				#!# Report to admin as error so that this can be fixed up
				#!# Known cases are: N: 1987
				if (!strlen ($relatedRecord['ObjectIdentity']['Number'])) {continue;}
				$relatedObjects[] = $relatedRecord['ObjectIdentity']['Number'];
				continue;
			}
			
			# Support format of a simple list of values in a container called 'ObjectIdentity' (e.g. record Y: 76/7/2)
			if (isSet ($relatedRecord['ObjectIdentity'][0])) {
				$i = 0;
				while (true) {
					if (!isSet ($relatedRecord['ObjectIdentity'][$i])) {break;}
					$relatedObjects[] = $relatedRecord['ObjectIdentity'][$i];
					$i++;
				}
			}
			
			# Support note field?
			// $relatedObjects[] = $relatedRecord['Number'] . (isSet ($relatedRecord['Note']) ? "<br />&nbsp;&nbsp;&mdash; Note: {$relatedRecord['Note']}" : '');
		}
		
		# Return the list
		return $relatedObjects;
	}
	
	
	# Function to parse dimensions
	private function getDimensions ($article, $record)
	{
		# End if no description
		if (!isSet ($article['Description'])) {return false;}
		
		# Ensure descriptions are specified as an set of items, even if only one
		$article['Description'] = $this->normaliseToMultiple ($article['Description']);
		
		# End if none
		if (!$article['Description']) {return false;}
		
		# Loop through each item
		$dimensions = array ();
		foreach ($article['Description'] as $descriptionIndex => $descriptionBlock) {
			
			# Obtain the part title
			$partTitle = $this->_getPartTitle ($descriptionBlock, $record['Type']);
			
			# Obtain the dimensions, or end if none
			if (!$partDimensions = $this->_getPartDimensions ($descriptionBlock)) {
				continue;
			}
			
			# Register the item
			$dimensions[$partTitle] = $partDimensions;
		}
		
		# Return the result
		return $dimensions;
	}
	
	
	# Function to assemble a part title from a description block
	#!# N: 246a has no title - consider what to do in these cases
	private function _getPartTitle ($descriptionBlock, $recordType)
	{
		# Define the default
		$default = ucfirst ($recordType);
		
		# If Part is at the top level, move it into Aspect
		if (isSet ($descriptionBlock['Part'])) {
			$descriptionBlock['Aspect']['Part'] = $descriptionBlock['Part'];
			#!# Report to admin as error so that this can be fixed up
			#!# Known cases are: N: 352
		}
		
		# Part title is within the Aspect block of the Description; if none, return the default
		if (!isSet ($descriptionBlock['Aspect'])) {
			return $default;
		}
		
		# Get the part from each aspect
		$partSubnames = array ();
		$aspectBlocks = $this->normaliseToMultiple ($descriptionBlock['Aspect']);
		foreach ($aspectBlocks as $aspectBlock) {
			if (isSet ($aspectBlock['Part'])) {
				$partSubnames[] = trim ($this->normaliseToFlattened ($aspectBlock['Part']));	// Flattening needed in e.g. record N: 199a-d; trim needed in e.g. record Y: 62/15/1
			}
		}
		
		# If no part subnames found, return the default
		if (!$partSubnames) {
			return $default;
		}
		
		# If more than one (e.g. record Y: 62/15/1), combine
		$part = implode (' & ', $partSubnames);
		
		# Return the assembled title
		return $part;
	}
	
	
	# Function to assemble a list dimensions of a part from a description block
	private function _getPartDimensions ($descriptionBlock)
	{
		# Part title is within the Aspect block of the Description; if none, return the default; e.g. Y: 2014/8a-i where the first Description item contains only a note
		if (!isSet ($descriptionBlock['Measurement'])) {return false;}
		
		# Convert to multidimensional array, representing parts of the catalogued 'item' (e.g. a mortar and a pestle) if not already
		$descriptionBlock['Measurement'] = $this->normaliseToMultiple ($descriptionBlock['Measurement']);	// E.g. record Y: 62/15/32 has a single item
		
		# End if none
		if (!$descriptionBlock['Measurement']) {return false;}
		
		# Assemble each dimension
		$partDimensions = array ();
		foreach ($descriptionBlock['Measurement'] as $measurementBlock) {
			
			# Extract the name
			$dimensionName = $measurementBlock['Dimension'];
			
			# Skip if no value
			if (!$measurementBlock['Reading']) {
				#!# Report to admin as error so that this can be fixed up
				#!# Known cases are: N: 352
				continue;
			}
			
			# If the value is malformed, correct
			if (is_array ($measurementBlock['Reading']['Value']) && isSet ($measurementBlock['Reading']['Value']['Value'])) {
				$measurementBlock['Reading']['Value'] = $measurementBlock['Reading']['Value']['Value'];
				#!# Report to admin as error so that this can be fixed up
				#!# Known cases are: Y: 2014/8a-i
			}
			
			# Extract the value, unit and optional note
			$value = $measurementBlock['Reading']['Value'];
			$unit = $measurementBlock['Reading']['Unit'];
			$note = '';
			if (isSet ($measurementBlock['Reading']['Note'])) {
				if (is_string ($measurementBlock['Reading']['Note']) && mb_strlen (trim ($measurementBlock['Reading']['Note']))) {	// #!# /Description/Measurement/Reading/Note sometimes as /Description/Measurement/Reading/Note/[] in /api/article?id=Y%3A+2010%2F10%2F59&collection=? also /article/y2010.10.59/
					$note = ' (' . $measurementBlock['Reading']['Note'] . ')';	// E.g. used on /article/y2010.10.93/
				}
			}
			
			# Skip if no value
			if (!$value) {continue;}
			if ($value == '-') {continue;}
			
			# Register the dimension
			$partDimensions[$dimensionName] = $value . $unit . $note;
		}
		
		# End if no dimensions
		if (!$partDimensions) {
			#!# Remport to admin
			#!# Known cases are Y: 2014/8a-i, item (g) - though that is probably a bogus entry as that record should have 8 items but 9 are listed
			return array ('Dimensions' => '?');
		}
		
		# Re-sort, adding others to the list
		$startOrder = array ('width', 'height', 'length', 'diameter');	/* which will be followed others, if present */
		$partDimensions = application::resortStartOrder ($partDimensions, $startOrder);
		
		# Return the dimensions list
		return $partDimensions;
	}
	
	
	# Function to obtain the place name
	private function getPlaceName ($article, $record)
	{
		# Only applies to image records
		if ($record['Type'] != 'image') {return NULL;}
		
		# End if no description or colour value
		if (!isSet ($article['Content'])) {return false;}
		if (!isSet ($article['Content']['Place'])) {return false;}
		if (!isSet ($article['Content']['Place']['PlaceName'])) {return false;}
		
		# Return the value
		return $article['Content']['Place']['PlaceName'];
	}
	
	
	# Function to get associated person data
	private function getAssociatedPerson ($article, $record)
	{
		# Delegate
		return $this->associationData ($article, $record, 'person/organisation', 'Person', 'PersonName');
	}
	
	
	# Function to get associated organisation data
	private function getAssociatedOrganisation ($article, $record)
	{
		# Delegate
		return $this->associationData ($article, $record, 'person/organisation', 'Organisation', 'OrganisationName');
	}
	
	
	# Function to get associated expedition data
	private function getAssociatedExpedition ($article, $record)
	{
		# Delegate
		return $this->associationData ($article, $record, 'expedition', 'Event', 'EventName');
	}
	
	
	# Helper function to get association data
	private function associationData ($article, $record, $elementType, $container, $innerContainer)
	{
		# Start an array of association data
		$associations = array ();
		
		# End if no data
		if (!isSet ($article['Association'])) {return $associations;}
		
		# Loop through each association to find a matching type
		$associations = array ();
		foreach ($article['Association'] as $index => $association) {
			if ($association) {
				if ($association['@attributes']['elementtype'] != $elementType) {continue;}
				if (!strlen ($association[$container][$innerContainer])) {continue;}
				$associations[] = array (
					'type' => $association['Type'],
					'name' => $association[$container][$innerContainer],
					'dateBegin' => $association['Date']['DateBegin'],
					'dateEnd' => $association['Date']['DateEnd'],
				);
			}
		}
		
		# Return the list
		return $associations;
	}
	
	
	# Function to create a list of images
	private function getImages ($article, $record)
	{
		# End if private
		if ($record['Status'] == 'P') {return array ();}
		
		#!# This code block copied from getPhotographNumber in viewer class
		
		# Remove the starting and ending delimiter if present
		$photographs = explode ($this->settings['multiplesDelimiter'], $record['PhotographFilename']);
		foreach ($photographs as $index => $photograph) {
			$photograph = trim ($photograph);
			if (!$photograph) {
				unset ($photographs[$index]);
			}
		}
		
		# Reindex by 0 but keep in same order
		$photographs = array_values ($photographs);
		
		# Return the list
		return $photographs;
	}
	
	
	# Function to obtain who the image is by
	#!# Check that an array is always wanted
	private function getImageBy ($article, $record)
	{
		# Only applies to image records
		if ($record['Type'] != 'image') {return NULL;}
		
		# End if none
		if (!$article['Production']['Person']['PersonName']) {return false;}
		
		# Normalise to array
		if (!is_array ($article['Production']['Person']['PersonName'])) {
			$article['Production']['Person']['PersonName'] = array ($article['Production']['Person']['PersonName']);
		}
		
		# Show dates?
		// " ({$article['Production']['Person']['Dates']})"
		
		# Return the value
		return $article['Production']['Person']['PersonName'];
	}
	
	
	# Function to obtain the image colour type
	private function getImageColour ($article, $record)
	{
		# Only applies to image records
		if ($record['Type'] != 'image') {return NULL;}
		
		# End if no description or colour value
		if (!isSet ($article['Description'])) {return false;}
		if (!isSet ($article['Description']['Colour'])) {return false;}
		
		# Spell out B&W
		if (strtolower ($article['Description']['Colour']) == 'b&w') {
			$article['Description']['Colour'] = 'Black & white';
		}
		
		# Upper-case
		$article['Description']['Colour'] = ucfirst ($article['Description']['Colour']);
		
		# Return the value
		return $article['Description']['Colour'];
	}
	
	
	# Function to obtain contextual links
	private function getNavigationIds ($article, $record, $collectionId = false)
	{
		return $this->getPositionsData ($record['id'], $record['Type'], $collectionId);
	}
	
	
	
	# Function to obtain contextual links
	private function getNavigationIdsAdditional ($article, $record, $collectionId)
	{
		# End if no collection context
		if (!$collectionId) {
			return array ();
		}
		
		# Start an array of links
		$navigationIds = array ();
		
		# Add support for categories
		if ($record['Category']) {
			$categories = explode ($this->settings['multiplesDelimiter'], $record['Category']);	// Take care of items in multiple categories
			sort ($categories);
			foreach ($categories as $category) {
				if (!$category) {continue;}	// Deals with delimiter at start and end of category string
				$navigationIds['categories'][$category] = $this->getPositionsData ($record['id'], $record['Type'], $collectionId, $category);
			}
		}
		
		# Add support for artists
		if ($record['Artist']) {
			$artists = explode ($this->settings['multiplesDelimiter'], $record['Artist']);	// Take care of items in multiple categories
			sort ($artists);
			foreach ($artists as $artist) {
				if (!$artist) {continue;}	// Deals with delimiter at start and end of artist string
				$navigationIds['artists'][$artist] = $this->getPositionsData ($record['id'], $record['Type'], $collectionId, $category = false, $artist);
			}
		}
		
		# Return the array (which may be empty)
		return $navigationIds;
	}
	
	
	# Function to get the previous/next record; if a collectionId is specified, it will be in the context of that, rather than global
	private function getPositionsData ($articleId, $recordType, $collectionId = false, $category = false, $artist = false)
	{
		# Ensure the category/artist is escaped
		$categoryEscaped = str_replace ('(', '\\\\(', $category);
		$artistEscaped = str_replace ('(', '\\\\(', $artist);
		
		# Assemble the query
		#!# Convert to prepared statements
		$multiplesDelimiterEscaped = (str_replace ('|', '\\\\|', $this->settings['multiplesDelimiter']));
		$query = "
			SELECT
				id
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE 1=1
			" . ($collectionId ? "AND Collection LIKE '%|{$collectionId}|%'" : '') . "
			AND (Status != 'R' OR Status IS NULL)
			" . (($recordType != 'image') && $category ? "AND (`Category` REGEXP '^{$categoryEscaped}$' OR `Category` REGEXP '{$multiplesDelimiterEscaped}{$categoryEscaped}{$multiplesDelimiterEscaped}') " : '')
			  . (($recordType != 'image') && $artist ? "AND (`Artist` REGEXP '^{$artistEscaped}$' OR `Artist` REGEXP '{$multiplesDelimiterEscaped}{$artistEscaped}{$multiplesDelimiterEscaped}') " : '') .
			"ORDER BY id;";
		
		# Get the data
		$ids = $this->databaseConnection->getPairs ($query, 'id');
		
		# Re-sort - there is no NATSORT() in MySQL sadly
		natsort ($ids);
		
		# Get the positions
		if (!$positions = application::getPositions ($ids, $articleId)) {return array ();}
		
		# Return the positions
		return $positions;
	}
	
	
	# Function to ensure that a data clump is expressed as an array, when it could be supplied as a single item clump
	public function normaliseToMultiple ($record)
	{
		/*
			This function deals with the way that MODES expresses a value as either a single item (e.g. record N: 184) at top-level:
			
				Array
				(
				    [Aspect] => Array
				        ...
			
			or an array of items (e.g. record N: 199a-d):
			
				Array
				(
				    [0] => Array
				        (
				            [Aspect] => Array
								...
							
				    [1] => Array
				        (
				            [Aspect] => Array
								...
			
			It normalises the first case (single item) to the second case (array of items), i.e. so that it is an array of one item:
			
				Array
				(
				    [0] => Array
				        (
				            [Aspect] => Array
								...
		*/
		
		# Determine if the index is numeric
		$keys = array_keys ($record);
		$firstKey = $keys[0];
		$indexIsNumeric = ($firstKey === 0);	// Exactly equal to (int) 0
		
		# If not numeric, wrap the structure in an array
		if (!$indexIsNumeric) {
			$record = array (0 => $record);
		}
		
		# Return the data
		return $record;
	}
	
	
	# Function to ensure that a data value is expressed as a string even if it is present as a nested structure
	private function normaliseToFlattened ($record)
	{
		# If already a string, return that
		if (is_string ($record)) {return $record;}
		
		# Ensure array
		if (!is_array ($record)) {
			#!# Log error
			return NULL;
		}
		
		# Ensure array contains exactly one part
		if (count ($record) != 1) {
			#!# Log error
			return NULL;
		}
		
		#!# Report to admin
		#!# Known cases are: N: 828a-c for Part
		
		# If an array of one item, return that
		$values = array_values ($record);
		return $values[0];
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
}

?>
