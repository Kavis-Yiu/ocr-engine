<?php
// parse the address
function BANK($ocrDoc, $opt) {
	// check the image OCR result
	if (!$ocrDoc) {
		return null;
	}

	// set the default output data
	$output = [
		'posi' => -1,
		'nega' => -1,
		'balance' => null,
		'ledger' => []
	];

	// set the regex
	$rgx = [
		'date' => '/^(date|日期)$/i',
		'tx' => '/^(transaction( *details)?|交易(摘要)?)/i',
		'posi' => '/^(deposits?|credit|paid in|存入)/i',
		'nega' => '/^(withdrawals?|debit|paid out|提取)/i',
		'balance' => '/^(balance|.*幣結餘)/i',
		'dollar' => '/^(\d{1,3} *,? *)*\d{1,3}\.\d{2}$/',
		'cf' => '/^(c\/f ?balance|今期結餘)/i',
		's' => '/\s/i',
		'oo' => '/oo/i',
		'comma' => '/,/',
	];

	// find out the ledger starting line
	$m = [-1, 0, 0, 0, 0];
	$b = [-1, count($ocrDoc['doc'])-1];
	$k = array_keys($ocrDoc['doc']);
	while ($b[0]++ < $b[1]) {
		// find out the columns
		for ($i = count($ocrDoc['doc'][$k[$b[0]]]) - 1; $i >= 0; --$i) {
			$r = $ocrDoc['doc'][$k[$b[0]]][$i];

			if (!$m[4]) {
				// match the column: balance
				if ($i > $m[3] && preg_match($rgx['balance'], $r['t'])) {
					$m[4] = $i;
				}
			}
			else if (!$m[3]) {
				// match the column: credit / debit
				if ($i > $m[2] && preg_match($rgx['posi'], $r['t'])) {
					$m[3] = $i;
				}
				if ($i > $m[2] && preg_match($rgx['nega'], $r['t'])) {
					$m[3] = -$i;
				}
			}
			else if (!$m[2]) {
				// match the column: credit / debit
				if ($i > $m[1] && preg_match($rgx['posi'], $r['t'])) {
					$m[2] = $i;
				}
				if ($i > $m[1] && preg_match($rgx['nega'], $r['t'])) {
					$m[2] = -$i;
				}
			}
			else if (!$m[1]) {
				// match the column: transaction
				if ($i > $m[0] && preg_match($rgx['tx'], $r['t'])) {
					$m[1] = $i;
				}
			}
			else if ($m[0] < 0) {
				// match the column: date
				if (preg_match($rgx['date'], $r['t'])) {
					$m[0] = $i;
					break;
				}
			}
		}

		// matched date & tx & posi & nega & balance
		if ($m[0] > -1) {
			break;
		}
	}

	// determine if the starting line is found
	if ($m[0] < 0 || strpos(implode('', $m), '00') !== false) {
		return $output;
	}

	// set the columns position constraints
	$h = $ocrDoc['doc'][$k[$b[0]]];

	$opt['date'] = $h[$m[1]]['x'];
	$opt['tx'] = [$h[$m[1]]['x']-$opt['txLeftBuf'], $h[$m[2]]['x']];
	$opt['balance'] = $h[$m[4]]['x']+$opt['tolerance'];
	if ($m[2] > 0) {
		$opt['posi'] = [$h[$m[2]]['x']-$opt['tolerance'], $h[$m[3]*-1]['x']+$opt['tolerance']];
		$opt['nega'] = [$h[$m[3]*-1]['x']-$opt['tolerance'], $h[$m[4]]['x']+$opt['tolerance']];
	}
	else {
		$opt['nega'] = [$h[$m[2]]['x']-$opt['tolerance'], $h[$m[3]*-1]['x']+$opt['tolerance']];
		$opt['posi'] = [$h[$m[3]*-1]['x']-$opt['tolerance'], $h[$m[4]]['x']+$opt['tolerance']];
	}

	// summarized the ledger entries
	$ledger = [];
	for ($b[0] += 1; $b[0] <= $b[1]; ++$b[0]) {
		$h = 0;
		$a = [-1, -1];
		$e = ['', '', ''];

		foreach ($ocrDoc['doc'][$k[$b[0]]] as $w) {
			// chinese = right-aligned header
			$c = ($ocrDoc['lang'] == 'zh'? $w['w']: 0) + $w['x'];

			// correct the dot sign
			$t = str_replace('·', '.', str_replace(' ', '', preg_replace($rgx['oo'], '00', $w['t'])));

			// positive entry
			if ($opt['posi'][0] <= $c && $c <= $opt['posi'][1] && preg_match($rgx['dollar'], $t)) {
				$h = 1;

				// add to the result
				$e[2] = $a[0] = floatval(str_replace(',', '', $t));
				$output['posi'] += $a[0];
			}

			// negative entry
			if ($opt['nega'][0] <= $c && $c <= $opt['nega'][1] && preg_match($rgx['dollar'], $t)) {
				$h = -1;

				// add to the result
				$e[2] = -1 * $a[1] = floatval(str_replace(',', '', $t));
				$output['nega'] += $a[1];
			}

			// balance entry
			if (!$h && $opt['balance'] <= $c && preg_match($rgx['dollar'], $t)) {
				// add to the result
				$output['balance'] = floatval(str_replace(',', '', $t));
			}

			// transaction entry
			if ($opt['tx'][0] <= $c && $c <= $opt['tx'][1]) {
				$e[1] .= $t.' ';
			}

			// date entry
			if ($c < $opt['date'] && ($d = strtotime($t)) > 0) {
				$e[0] = date('Y-m-d', $d);
			}
		}

		// end of table
		if (preg_match($rgx['cf'], $e[1]) && $e[2] == '') {
			$a[0] = $a[1] = 0;
			break;
		}

		// add a ledger entry
		$ledger[] = $e;

		// check balance summary line
		if ($a[0] > -1 && $a[1] > -1) {
			$output['posi'] -= $a[0];
			$output['nega'] -= $a[1];
			break;
		}
	}

	// offset the default -1 amount
	$output['posi'] = ($output['posi'] > -1? round($output['posi'] + 1, 2): '?');
	$output['nega'] = ($output['nega'] > -1? round($output['nega'] + 1, 2): '?');

	// concatenate the ledger segment
	for ($i = count($ledger) - 1; $i >= 0; --$i) {
		// dangling transaction description
		$e = $ledger[$i];
		if ($i && $e[0] == '' && $e[1] != '' && $e[2] == '') {
			$ledger[$i-1][1] .= $e[1];
			unset($ledger[$i]);
		}
		else if (
			$e[0] == '' && $e[1] == '' && $e[2] == '' ||
			$e[0] != '' && $e[1] == '' && $e[2] == '' ||
			$e[0] == '' && $e[1] == '' && $e[2] != '' ||
			$e[0] != '' && $e[1] != '' && $e[2] == ''
		) {
			unset($ledger[$i]);
		}
	}
	foreach ($ledger as $e) {
		$output["ledger"][] = $e;
	}
	for ($j = count($output["ledger"]) - 1, $i = 1; $i < $j; ++$i) {
		// without date
		$e = $output["ledger"][$i];
		if ($e[0] == '' && $e[1] != '' && $e[2] != '') {
			$output["ledger"][$i][0] = $output["ledger"][$i-1][0];
		}
	}

	return $output;
}