<?php
// re-fine the OCR result
function OCR($jsonStr, $opt) {
	// parse result to json
	$data = json_decode($jsonStr);
	if (json_last_error() || !isset($data->regions) || !count($data->regions)) {
		return [
			'language' => (isset($data->language)? $data->language: null),
			'doc' => [], 'tags' => []
		];
	}

	// set the OCR language [zh|en]
	$language = substr($data->language, 0, 2);

	// the re-fined document
	$doc = [];

	// look for all grids
	foreach ($data->regions as $region) {
		// look for all lines
		foreach ($region->lines as $line) {
			$terms = [];

			// look for all texts
			foreach ($line->words as $i => $word) {
				$last = count($terms) - 1;

				$details = explode(',', $word->boundingBox);
				$details = [
					'x' => intval($details[0]), 'y' => intval($details[1]),
					'w' => intval($details[2]), 'h' => intval($details[3]),
					't' => $word->text
				];

				// push the first / new word
				if ($i == 0 || $details['x'] - $terms[$last]['x'] - $terms[$last]['w'] > $opt['charSpace'][$language]) {
					$terms[] = $details;
					continue;
				}

				// stick the text if it is close to previous text
				$i = min($terms[$last]['y'], $details['y']);
				$terms[$last] = [
					'x' => $terms[$last]['x'], 'y' => $i,
					'w' => $details['x'] - $terms[$last]['x'] + $details['w'],
					'h' => max($terms[$last]['y'] + $terms[$last]['h'], $details['y'] + $details['h']) - $i,
					't' => $terms[$last]['t'].$opt['separator'][$language].$details['t']
				];
			}

			// look for all words
			foreach ($terms as $term) {
				// new line
				if (!array_key_exists($term['y'], $doc)) {
					$doc[$term['y']] = [];
				}

				// add the entry to specific line
				$doc[$term['y']][] = $term;
			}
		}
	}

	// sort the doc by Y-coordinate
	ksort($doc);

	$lines = array_keys($doc);

	// loop for all lines from bottom
	for ($i = count($lines) - 1; $i > 0; --$i) {
		// remove dangling comma
		$doc[$lines[$i]] = array_filter($doc[$lines[$i]], function($w) {
			return $w['t'] != ',';
		});

		// remove empty line
		if (!count($doc[$lines[$i]])) {
			unset($doc[$lines[$i]]);
			continue;
		}

		// merge the lower array to upper array
		if ($lines[$i] - $lines[$i-1] <= $opt['tolerance']) {
			foreach ($doc[$lines[$i]] as $term) {
				$doc[$lines[$i-1]][] = $term;
			}

			// remove the lower array
			unset($doc[$lines[$i]]);
		}

		// sort the words in the line by X-coordinate
		usort($doc[$lines[$i-1]], function($a, $b) {
			if ($a['x'] == $b['x']) {
				return 0;
			}
			return ($a['x'] < $b['x']? -1: 1);
		});
	}

	// sort the doc again by Y-coordinate
	ksort($doc);

	$lines = array_keys($doc);

	// stick the word together after clustered by Y coordinate
	for ($i = count($lines) - 1; $i >= 0; --$i) {
		$terms = $doc[$lines[$i]];

		// concatenate the latter word to the prvious word
		for ($n = count($terms) - 1; $n > 0; --$n) {
			if ($terms[$n]["x"] - $terms[$n-1]["x"] - $terms[$n-1]["w"] < $opt['charSpace'][$language]) {
				$y = min($terms[$n-1]['y'], $terms[$n]['y']);
				$terms[$n-1] = [
					'x' => $terms[$n-1]['x'], 'y' => $y,
					'w' => $terms[$n]['x'] + $terms[$n]['w'] - $terms[$n-1]['x'],
					'h' => max($terms[$n-1]['y'] + $terms[$n-1]['h'], $terms[$n]['y'] + $terms[$n]['h']) - $y,
					't' => $terms[$n-1]['t'].$opt['separator'][$language].$terms[$n]['t']
				];

				array_splice($terms, $n, 1);
			}
		}

		$doc[$lines[$i]] = $terms;
	}

	// build tags coordinate
	$tags = [];
	foreach ($doc as $line) {
		foreach ($line as $term) {
			$tags[] = [$term['x'], $term['y'], $term['w'], $term['h']];
		}
	}

	return ['language' => $language, 'doc' => $doc, 'tags' => $tags];
}