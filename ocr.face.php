<?php
// get the face information
function FACE($jsonStr) {
	// parse result to json
	$data = json_decode($jsonStr);
	if (json_last_error() || gettype($data) != 'array') {
		return null;
	}

	// find out the largest face
	$face = [0, null];
	foreach ($data as $details) {
		$area = $details->faceRectangle->width * $details->faceRectangle->height;
		if ($a > $face[0]) {
			$face[0] = $area;
			$face[1] = $details;
		}
	}

	// no face is found
	if (is_null($face[1])) {
		return null;
	}

	return [
		'gender' => ucfirst($face[1]->faceAttributes->gender),
		'face' => [
			$face[1]->faceRectangle->left,
			$face[1]->faceRectangle->top,
			$face[1]->faceRectangle->width,
			$face[1]->faceRectangle->height
		]
	];
}