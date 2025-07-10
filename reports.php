<?php

# Class containing reports
class reports
{
	# Properties
	private $settings;
	private $databaseConnection;
	private $baseUrl;
	
	
	# Define the registry of reports; those prefixed with 'listing_' return data rather than record numbers; listings can be suffixed with a status (e.g. _info)
	private $reportsList = array (
	);
	
	# Listing (values) reports
	private $listingsList = array (
		
	);
	
	
	# Constructor
	public function __construct ($modesCatalogueApi)
	{
		# Create main property handles
		$this->settings = $modesCatalogueApi->settings;
		$this->databaseConnection = $modesCatalogueApi->databaseConnection;
		$this->baseUrl = $modesCatalogueApi->baseUrl;
		
	}
	
	
	
	# Getter for reportsList
	public function getReportsList ()
	{
		return $this->reportsList;
	}
	
	
	# Getter for listingsList
	public function getListingsList ()
	{
		return $this->listingsList;
	}
	
	
}

?>
