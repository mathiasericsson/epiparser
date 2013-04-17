<head>

</head>

<?php

$filename = 'data/sites.csv';
//$filename = 'data/sitestest.csv';
//$filename = 'data/sitestest301.csv';

$row = 1;
if (($handle = fopen($filename, "r")) !== FALSE) {

	$results = array();
	
	$date = date_format(date_create(), 'Ymd_H.i.s');
	$resultfilename = 'results/epitest' . $date . '.csv';
	
	$fp = fopen($resultfilename, 'w');

	//Not sure what this does, maybe setting limit?
	while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
		$num = count($data);
		$row++;

		//Loops through all rows in $filename
		for ($c = 0; $c < $num; $c++) {

			//Sets Sitename into URL and adding http://
			$sitename = $data[$c];
			$url = $sitename; //$url = 'http://' . $sitename;

			//Checking if it's a EPiServersite and tries to check what version of site.
			$result = episerverCheck($url);
					
			//Writes information into CSV file
			fputcsv($fp, $result);
			
		}

	}
	
	fclose($handle);
	
	fclose($fp);
	echo "Finised!!! Check file :)";
	
}

//Function that checks if the specified URL is EPiServer or not, it also tries to figure out what version of EPiServer
function episerverCheck($url) {

	//Getting HTML from URL
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$content = curl_exec($ch);

	//Check if page does not exists, if so it's not a EPiServer site (95 % certain)
	if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 404) {
		return array(URL => $url, Version => 'Not EPiServer');

	}
	//Check if 301 redirection, if so try checking the redirection URL
	else if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 301) {
		$url2 = checkRedirection($ch);
		$result = episerverCheck($url2);

		if ($result) {
				
			return $result;
			

		} else 
		//If redirection URL does not return a valid result we try adding /util/login.aspx to URL as many redirects goes to startpage only.
		{
			$url2 = $url2 . '/util/login.aspx';
			$result = episerverCheck($url2);
			if ($result)
				return $result;
			else
				return array(URL => $url, Version => 'Check this manualy (301)');
		}

	} 
	//See same notes as for 301
	else if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 302) {
			
		checkRedirection($ch);
		$result = episerverCheck($url2);
				
		if ($result) {
			return $result;
		
		} else {
			$url2 = $url2 . '/util/login.aspx';
			$result = episerverCheck($url2);
			if ($result)
				return $result;
			else
				return array(URL => $url, Version => 'Check this manualy (302)');
			
		}
	} else {
		
		//Check if CMS 6
		if (checkCMS($content, '/EPiServer CMS 6/')) {
			return array(URL => $url, Version => 'CMS 6');
		}
		//Check if CMS 5
		if (checkCMS($content, '/<h2>/')) {
			return array(URL => $url, Version => 'CMS 5');
		}
		//Check if CMS 4
		if (checkCMS($content, '/table width="100%" height="100%" border="0"/')) {
			return array(URL => $url, Version => 'CMS 4');
		}
	}

	curl_close($ch);

}
//A function for preg_matches
function checkCMS($content, $pattern) {

	preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE, 3);

	if ($matches) {
		return true;
	}
}

//A function that checks what actual location the redirection points to
function checkRedirection($ch) {

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);
	// We'll parse redirect url from header.
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
	// We want to just get redirect url but not to follow it.
	$response = curl_exec($ch);
	preg_match_all('/^Location:(.*)$/mi', $response, $matches);
	curl_close($ch);
	$url = !empty($matches[1]) ? trim($matches[1][0]) : 'No redirect found';
	return $url;
}

?>
