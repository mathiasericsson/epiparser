<head></head>

<?php

/**
 * Check integration with Google 
 * https://developers.google.com/speed/docs/insights/v1/getting_started?hl=en
 * 
 * 
 * */


$filename = 'data/sites_test.csv';

$row = 1;
if (($handle = fopen($filename, "r")) !== FALSE) {

	$results = array();
	
	$date = date_format(date_create(), 'Ymd_H.i.s');
	$resultfilename = 'results/performance' . $date . '.csv';
	
	$fp = fopen($resultfilename, 'w');

	$header = array(
		URL => 'Webbsida', 
		TOTAL_TIME => 'Total time', 
		//NAMELOOKUP_TIME => 'Namelookup time',
		//PRETRANSFER_TIME => 'Pretransfer time',
		HTTP_CODE => 'HTTP Code');

	fputcsv($fp, $header);

	//Not sure what this does, maybe setting limit?
	while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
		$num = count($data);
		$row++;

		//Loops through all rows in $filename
		for ($c = 0; $c < $num; $c++) {

			//Sets Sitename into URL and adding http://
			$sitename = $data[$c];
			$url = 'http://' . $sitename;

			//Checking if it's a EPiServersite and tries to check what version of site.
			$result = performanceCheck($url);
			
			var_dump($result);	
				
			//Writes information into CSV file
			fputcsv($fp, $result);
			
		}
		
		
	}
	
	fclose($handle);
	
	fclose($fp);
	
	echo "completed analysis";
}

//Function that checks if the specified URL is EPiServer or not, it also tries to figure out what version of EPiServer
function performanceCheck($url) {

	//Getting HTML from URL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	curl_setopt($ch, CURLOPT_COOKIESESSION, true);
	
	$content = curl_exec($ch);
		
	return array(
		URL => $url, 
		TOTAL_TIME => curl_getinfo($ch, CURLINFO_TOTAL_TIME), // Total transaction time in seconds for last transfer
		//NAMELOOKUP_TIME => curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME), //Time in seconds until name resolving was complete
		//PRETRANSFER_TIME => curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME), //Time in seconds from start until just before file transfer begins
		HTTP_CODE => curl_getinfo($ch, CURLINFO_HTTP_CODE) );

	curl_close($ch);

}
?>
