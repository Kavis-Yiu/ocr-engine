<?php
// parse the HKID
function HKID($ocrDoc, $opt) {
	// check the image OCR result
	if (!$ocrDoc) {
		return null;
	}

	// set the default output data
	$output = [
		'chiName' => null,
		'ccCode' => null,
		'engName' => null,
		'gender' => null,
		'dob' => null,
		'adult' => null,
		'idNo' => null,
		'checkDigit' => null,
		'face' => null
	];

	// set the regex
	$rgx = [
		'o' => '/o/i',
		'idNo' => '/(wx|xa|xb|xc|xd|xe|xg|[a-z])\d{6}\([a0-9]\)/i',
		'dob' => '/(\d{2}\-){2}\d{4}/',
		'ccCode' => '/(\\d{4} *){2,6}/',
		'engName' => '/([a-z]+,[^\\d]+)/i'
	];

	$doc = array_reverse($ocrDoc);

	// parse the HKID No. from the bottom
	foreach ($doc as $terms) {
		$data = $line = '';

		foreach ($terms as $term) {
			$text = preg_replace($rgx['o'], '0', $term['t']);

			if (preg_match($rgx['idNo'], $text, $m)) {
				$data = $m[0];
				break;
			}

			$line .= $text;
		}

		if (strlen($data) || preg_match($rgx['idNo'], $line, $m)) {
			$output['idNo'] = strtoupper($m[0]);
			break;
		}
	}

	// verify the HKID
	if ($l = strlen($output['idNo'])) {
		$check_sum = 0;
		$sum = function($s) {
			$k = 0;
			for ($j = 1, $i = strlen($s) - 1; $i >= 0; --$i) {
				$k += intval($s[$i]) * ++$j;
			}
			return $k;
		};

		if ($l > 10) {
			$check_sum += (ord($output['idNo'][0]) - 55) * 9;
			$check_sum += (ord($output['idNo'][1]) - 55) * 8;
			$check_sum += $sum(substr($output['idNo'], 2, 6));
		}
		else {
			$check_sum += (ord($output['idNo'][0]) - 55) * 8;
			$check_sum += $sum(substr($output['idNo'], 1, 6));
		}

		$output['checkDigit'] = (intval($output['idNo'][$l - 2]) == $sum % 11? 1: 0);
	}

	// parse the birthday from the bottom
	foreach ($doc as $terms) {
		$data = $line = '';

		foreach ($terms as $term) {
			$text = preg_replace($rgx['o'], '0', $term['t']);

			if (preg_match($rgx['dob'], $text, $m)) {
				$data = $m[0];
				break;
			}

			$line .= $text;
		}

		if (strlen($data) || preg_match($rgx['dob'], $line, $m)) {
			$output['dob'] = $m[0];
			break;
		}
	}

	// verfiy the birthday
	if (strlen($output['dob'])) {
		$check = intval(date('Y')) - intval(substr($output['dob'], 6, 4));

		if ($check < 18) {
			$output['adult'] = 0;
		}
		else if ($check > 18) {
			$output['adult'] = 1;
		}
		else {
			$check = intval(date('m')) - intval(substr($output['dob'], 3, 2));
			
			if ($check < 0) {
				$output['adult'] = 0;
			}
			else if ($check > 0) {
				$output['adult'] = 1;
			}
			else {
				$check = intval(date('d')) - intval(substr($output['dob'], 0, 2));
				
				if ($check < 0) {
					$output['adult'] = 0;
				}
				else {
					$output['adult'] = 1;
				}
			}
		}
	}

	// parse the CC code
	foreach ($ocrDoc as $i => $terms) {
		$data = $line = '';

		foreach ($terms as $term) {
			$text = preg_replace($rgx['o'], '0', $term['t']);

			if (preg_match($rgx['ccCode'], $text, $m)) {
				$data = $m[0];
				break;
			}

			$line .= $text.' ';
		}

		if (strlen($data) || preg_match($rgx['ccCode'], $line, $m)) {
			$output['ccCode'] = str_replace(' ', '', $m[0]);
			$hit = $i;
			break;
		}
	}

	$lines = array_keys($ocrDoc);

	// parse the chinese name by CC code
	if (strlen($output['ccCode'])) {
		$output['chiName'] = '';

		// get the CC code mapping
		if ($data = @file_get_contents($opt['cc_code'])) {
			$map = [];

			$data = explode("\r\n", $data);

			// build the CC code map
			foreach ($data as $line) {
				$l = explode('=', $line);
				$map[$l[0]] = $l[1];
			}

			// translate CC code to chinese characer
			for ($l = strlen($output['ccCode']), $i = 0; $i < $l; $i += 4) {
				$data = substr($output['ccCode'], $i, 4);
				$output['chiName'] .= (array_key_exists($data, $map)?  $map[$data]: '？');
			}

			$c = array_search($hit, $lines);

			// parse english name from CC code backward
			for ($data = ''; $c >= 0; --$c) {
				for ($i = count($ocrDoc[$lines[$c]]) - 1; $i >= 0; --$i) {
					$data = ' '.$ocrDoc[$lines[$c]][$i]['t'].$data;
				}

				if (preg_match($rgx['engName'], $data, $m)) {
					$output['engName'] = trim($m[0]);
					break;
				}
			}
		}
	}

	// parse the english name alone
	if (!strlen($output['engName'])) {
		for ($l = count($ocrDoc) >> 1, $i = 0; $i <= $l; ++$i) {
			$terms = [];

			foreach ($ocrDoc[$lines[$i]] as $term) {
				$terms[] = $term['t'];
			}

			if (preg_match($rgx['engName'], implode(' ', $terms), $m)) {
				$output['engName'] = trim($m[0]);
				break;
			}
		}
	}

	return $output;
}