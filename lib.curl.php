<?php
// get the cURL result
function CURL($url, $body = null, $headers = []) {
	// initlialize the cURL
	$c = curl_init($url);
	if (!$c) {
		return null;
	}

	// set post body
	if (!is_null($body)) {
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $body);
	}

	// set headers
	if (count($headers)) {
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	}

	// curl options
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 8);
	curl_setopt($c, CURLOPT_TIMEOUT, 16);

	// get the page
	$html = curl_exec($c);
	if ($html === false) {
		$html = curl_error($c);
	}

	// close the curl handler
	curl_close($c);

	return $html;
}