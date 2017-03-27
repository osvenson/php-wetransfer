<?php

function downloadWeTransfer($url)
{
	set_time_limit(0);
	preg_match('/https?:\/\/(www\.)?wetransfer\.com\/downloads\/(.+)/', $url, $matches);

	$url = $matches[2];
	$parts = explode('/', $url);

	switch (count($parts))
	{
		case 2: 
			$virtual_link = vsprintf('https://www.wetransfer.com/api/ui/transfers/%s/%s/download?recipient_id=&password=&ie=false',   $parts); break;
		case 3: 
                        $security = $parts[2];
                        $parts[2] = $parts[1];
                        $parts[1] = $security;
			$virtual_link = vsprintf('https://www.wetransfer.com/api/ui/transfers/%s/%s/download?recipient_id=%s&password=&ie=false', $parts); break;
		default:
			throw new Exception('Invalid WeTransfer URL');
	}

	$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_URL,$virtual_link);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$response = curl_exec($ch);
	$response = json_decode($response, true);
	if (isset($response['direct_link']))
	{
		$filename = preg_replace("/^.+\/(.+)\?.+$/", "$1", urldecode($response['direct_link']));

		$local_handle = fopen($filename, 'w+b');
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $response['direct_link']);
		curl_setopt($ch, CURLOPT_FILE, $local_handle);
		curl_exec($ch);

		curl_close($ch);
		fclose($local_handle);
		return true;
	}

	if (isset($response['fields']))
	{
		$action  = $response['formdata']['action'];
		$postdata = http_build_query($response['fields']);
		$pieces = explode("/", $action);
		$filename = urldecode(array_pop($pieces));

		$local_handle = fopen($filename, 'w+b');
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $action . '?' . $postdata);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_FILE, $local_handle);
		curl_exec($ch);

		curl_close($ch);
		fclose($local_handle);
		return true;
	}

	return false;
}

// -- Example:  

downloadWeTransfer('https://www.wetransfer.com/downloads/XXXXXXXXXX/YYYYYYYYY');
downloadWeTransfer('https://www.wetransfer.com/downloads/XXXXXXXXXX/YYYYYYYYY/ZZZZZZZZ');
