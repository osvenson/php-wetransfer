<?php

function downloadWeTransfer($url)
{
	set_time_limit(0);
	preg_match('/https?:\/\/(www\.)?wetransfer\.com\/downloads\/(.+)/', $url, $matches);

	$url = $matches[2];
	$parts = explode('/', $url);

	switch (count($parts))
	{
		case 2: 	$virtual_link = vsprintf('https://www.wetransfer.com/api/v1/transfers/%s/download?recipient_id=&security_hash=%s&password=&ie=false',   $parts); break;
		case 3: 	$virtual_link = vsprintf('https://www.wetransfer.com/api/v1/transfers/%s/download?recipient_id=%s&security_hash=%s&password=&ie=false', $parts); break;
		default:
			throw new Exception('Invalid WeTransfer URL');
	}

	$response = file_get_contents($virtual_link);
	$response = json_decode($response, true);

	if (isset($response['direct_link']))
	{
		$query_data = parse_url($response['direct_link'], PHP_URL_QUERY);
		parse_str($query_data, $query_params);
		$filename = urldecode($query_params['filename']);

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
