<?php
// crop the image [ opt = object of {x, y, width, height} ]
function CROP($src, $opt, $out = null) {
	// create image resource
	$ext = pathinfo($src, PATHINFO_EXTENSION);
	switch ($ext) {
		case 'jpg': case 'jpeg':
			$img = imagecreatefromjpeg($src); break;
		case 'png':
			$img = imagecreatefrompng($src); break;
		case 'gif':
			$img = imagecreatefromgif($src); break;
		case 'bmp':
			$img = imagecreatefrombmp($src); break;
		default: return false;
	}

	// fail to create image resource
	if (!$img) {
		return false;
	}

	// crop the image
	$crop = imagecrop($img, $opt);

	// clear the old image
	imagedestroy($img);

	// fail to crop image
	if (!$crop) {
		return false;
	}

	// output the cropped image
	$out = (is_null($out)? $src: $out);
	switch ($ext) {
		case 'jpg': case 'jpeg':
			$opt = imagejpeg($crop, $out); break;
		case 'png':
			$opt = imagepng($crop, $out); break;
		case 'gif':
			$opt = imagegif($crop, $out); break;
		case 'bmp':
			$opt = imagebmp($crop, $out); break;
		default: return false;
	}

	// clear the new image
	imagedestroy($crop);

	return $opt;
}