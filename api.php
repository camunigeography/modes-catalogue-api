<?php

# API
class api
{
	# Constructor
	public function __construct ($modesCatalogueApi, $settings, $databaseConnection, $baseUrl)
	{
		# Create property handles
		$this->modesCatalogueApi = $modesCatalogueApi;
		$this->settings = $settings;
		$this->databaseConnection = $databaseConnection;
		$this->baseUrl = $baseUrl;
		
	}
	
	
	
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
		$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl, $grouping);
		
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
		$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl);
		
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
		$collectionId = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Specify a search phrase if required
		$search = false;
		if (isSet ($_GET['search']) && strlen ($_GET['search'])) {
			if (strlen ($_GET['search']) < 3) {
				return array ('error' => 'The search phrase must be at least 3 characters.');
			}
			$search = $_GET['search'];
		};
		
		# Ensure either a collection or a search`	 has been specified
		if (!$collectionId && !$search) {
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
		$articleModel = new articleModel ($this->modesCatalogueApi, $this->settings, $this->databaseConnection);
		$data = $articleModel->getArticlesData ($baseUrl, $collectionId, $imageSize, $imageShape, $search, $category, $material, $artist, $requireImages, $random, $page);
		
		# Construct URLs
		foreach ($data['articles'] as $id => $article) {
			$data['articles'][$id]['link'] = $this->modesCatalogueApi->urlFromId ($id, $baseUrlArticles);
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
		$articleModel = new articleModel ($this->modesCatalogueApi, $this->settings, $this->databaseConnection);
		$data = $articleModel->getOne ($_GET['id'], $collectionId, $imageSize, $imageShape, $includeXml);
		
		# Get expedition URLs and images
		#!# Consider whether this block should logically be within articleModel
		if (isSet ($data['associatedExpedition'])) {
			if ($data['associatedExpedition']) {
				
				# Get the raw data
				$expeditionsRaw = $this->modesCatalogueApi->getExpeditionData (false, false, false, array ('id', 'name'));
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
		$collectionId = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
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
		$data = $this->modesCatalogueApi->getBiographyData ($baseUrl, $collectionId, false, $fields, $imageSize, $baseUrlExpeditions, $random, $forceId);
		
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
		if (!$data = $this->modesCatalogueApi->getBiographyData ($baseUrl, false, $_GET['id'], $fields, $imageSize, $baseUrlExpeditions)) {
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
		$alias = $this->modesCatalogueApi->unpipeList ($data['alias']);
		$alias = array_unique ($alias);
		$data['alias'] = implode (', ', $alias);
		
		# Return the data, which will be JSON-encoded
		return $data;
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
		$collectionId = (isSet ($_GET['collection']) && strlen ($_GET['collection']) ? $_GET['collection'] : false);
		
		# Get the data
		$fields = array ('id', 'name', 'date', 'leader', 'about');
		$data = $this->modesCatalogueApi->getExpeditionData ($baseUrl, $collectionId, false, $fields);
		
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
		if (!$data = $this->modesCatalogueApi->getExpeditionData ($baseUrl, false, $_GET['id'], $fields)) {
			return array ('error' => 'There is no such record ID.');
		}
		
		# Unpack the record for metadata extraction
		$json = json_encode (simplexml_load_string ($data['data']));
		$metadata = json_decode ($json, true);
		
		# Date is not reliable
		#!# Remove when fixed by MySQL 8 upgrade: https://stackoverflow.com/questions/30090221/mysql-xpath-concatenation-operator-how-to-add-space
		$data['date'] = $metadata['Content']['Event']['Date']['DateBegin'] . '-' . $metadata['Content']['Event']['Date']['DateEnd'];
		
		# Get the list of people to extract
		$peopleIds = array ();
		foreach ($metadata['Association']['Person'] as $person) {
			$peopleIds[] = $person['PersonName'];
		}
		
		# Get all the biographies
		#!# Need to add support in getBiographyData for getting a list of IDs, to avoid pointless lookup of people not present in the expedition
		#!# Fields needs to be filterable
		$biographies = $this->modesCatalogueApi->getBiographyData ($baseUrlPeople, false, $peopleIds, $fields = array (/* 'link', 'image' */), $imageSize);
		
		# Extract people
		$data['people'] = array ();
		foreach ($metadata['Association']['Person'] as $person) {
			$name = $person['PersonName'];
			$data['people'][] = array (
				'name' => $name,
				'role' => $person['Role'],
				'link' => (isSet ($biographies[$name]) ? $biographies[$name]['link'] : '#'),		// If missing, # will be used, which indicates a data error
				'image' => (isSet ($biographies[$name]) ? $biographies[$name]['image'] : '#'),		// If missing, # will be used, which indicates a data error
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
		$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl);
		
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
		$data = $this->modesCatalogueApi->getCategoriesData ($collections[$collectionId], $includeUnclassified);
		
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
		$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl);
		
		# Validate the collection ID
		if (!isSet ($collections[$collectionId])) {
			return array ('error' => 'There is no such collection.');
		}
		
		# End if disabled
		if ($collections[$collectionId]['disableMaterials']) {return array ();}
		
		# Get the data
		$data = $this->modesCatalogueApi->getGroup ($collections[$collectionId]['collection'], 'Material');
		
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
		$collections = $this->modesCatalogueApi->getCollectionsData ($baseUrl);
		
		# Validate the collection ID
		if (!isSet ($collections[$collectionId])) {
			return array ('error' => 'There is no such collection.');
		}
		
		# End if disabled
		if ($collections[$collectionId]['disableArtists']) {return array ();}
		
		# Get the data
		$data = $this->modesCatalogueApi->getGroup ($collections[$collectionId]['collection'], 'Artist');
		
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
}

?>