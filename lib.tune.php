<?php
// tune the image to OCR ready [ opt = -100 <-> +100 ]
function TUNE($src, $opt, $out = null) {
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

	// turn the image to black & white only
	$r = imagefilter($img, IMG_FILTER_GRAYSCALE);

	// fail to tune image
	if (!$r) {
		// clear the old image
		imagedestroy($img);

		return false;
	}

	// blur the image a little bit
	$r = imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
	
	// fail to tune image
	if (!$r) {
		// clear the old image
		imagedestroy($img);

		return false;
	}
	
	// increase the image contrast
	$r = imagefilter($img, IMG_FILTER_CONTRAST, $opt);

	// fail to tune image
	if (!$r) {
		// clear the old image
		imagedestroy($img);

		return false;
	}

	// output the cropped image
	$out = (is_null($out)? $src: $out);
	switch ($ext) {
		case 'jpg': case 'jpeg':
			$opt = imagejpeg($img, $out); break;
		case 'png':
			$opt = imagepng($img, $out); break;
		case 'gif':
			$opt = imagegif($img, $out); break;
		case 'bmp':
			$opt = imagebmp($img, $out); break;
		default: return false;
	}

	// clear the new image
	imagedestroy($img);

	return true;
}