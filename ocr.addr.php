<?php
// parse the address
function ADDR($ocrDoc, $quarter) {
	// check the image OCR result
	if (!$ocrDoc) {
		return null;
	}

	// set the default output data
	$output = [
		'flat' => null,
		'floor' => null,
		'block' => null,
		'building' => null,
		'district' => null,
		'region' => null
	];

	// set the regex
	$rgx = [
		'flat' => '/(flat\/room|flat\/unit|room\/flat|room\/unit|unit\/flat|unit\/room|suite|ste|flat|flt|ft|room|rm|unit) *([a-z]|\d+)/i',
		'floor' => '/((floor|flr|level|lev|lv) *([a-z]|\d+)|([a-z]|\d+) *(st|nd|rd|th)? *(\/? ?floor|\/? ?flr|\/ ?f))/i',
		'block' => '/(block|blk\.?|bck) *([a-z]|\d+)/i',
		'district' => '/(central *(and|&) *western|wan *chai|eastern( *district)?|southern( *district)?|yau *tsim *mong|sham *shui *po|kowloon *city|wong *tai *sin|kwun *tong|kwai *tsing|tsuen *wan|tuen *mun|yuen *long|north( *district)?|tai *po|sha *tin|sai *kung|islands( *district)?)/i',
		'region' => '/(kowloon|kln|new *territories|n\.?t\.?|hong *kong|H\.?K\.?)/i'
	];

	// set the quarter options
	if ($quarter) {
		$quarter = [0, 0];

		foreach ($ocrDoc as $terms) {
			$quarter[1] = $terms[count($terms) - 1]['x'] >> 1;
			$quarter[0] = ($quarter[1] > $quarter[0]? $quarter[1]: $quarter[0]);
		}

		$line = array_keys($ocrDoc);
		$quarter[1] = ($line[count($line) - 1] - $line[0]) >> 1;
	}

	$address = '';

	// extract the address in one sentence in top-left quarter
	foreach ($ocrDoc as $y => $terms) {
		if ($quarter && $y > $quarter[1]) {
			break;
		}

		foreach ($terms as $term) {
			if ($quarter && $term['x'] > $quarter[0]) {
				break;
			}

			$address .= ' '.$term['t'];
		}
	}

	// cut the prefex, retrieve flat
	if (preg_match($rgx['flat'], $address, $m)) {
		$output['flat'] = $m[0];
		$address = substr($address, stripos($address, $output['flat'])+strlen($output['flat']));
	}

	// cut the suffix, retrieve region
	if (preg_match($rgx['region'], $address, $m)) {
		$output['region'] = $m[0];
		$address = substr($address, 0, stripos($address, $output['region']));
	}

	// search district from the tail
	for ($district = '', $i = strlen($address)-1; $i >= 0; --$i) {
		$district = $address[$i].$district;

		if (preg_match($rgx['district'], $district, $m)) {
			$output['district'] = $m[0];
			break;
		}
	}

	// match floor from the head
	if (preg_match($rgx['floor'], $address, $m)) {
		$output['floor'] = $m[0];
	}

	// match block from the head
	if (preg_match($rgx['block'], $address, $m)) {
		$output['block'] = $m[0];
	}

	// without the above info, remaining is the building name
	$i = stripos($address, $output['floor']);
	$address = (!strlen($output['floor']) || $i === false? $address: substr($address, $i+strlen($output['floor'])));
	$i = stripos($address, $output['block']);
	$address = (!strlen($output['block']) || $i === false? $address: substr($address, $i+strlen($output['block'])));
	$i = stripos($address, $output['district']);
	$output['building'] = trim((!strlen($output['district']) || $i === false? $address: substr($address, 0, $i)));

	return $output;
}