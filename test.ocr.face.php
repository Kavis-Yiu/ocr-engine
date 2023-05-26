<?php
// testing library
require('ocr.face.php');

// testing code
$curl_result = '[{"faceRectangle":{"top":181,"left":427,"width":78,"height":78},"faceAttributes":{"gender":"male"}}]';
echo '<!DOCTYPE html><html><body><pre>'.json_encode(FACE($curl_result), JSON_PRETTY_PRINT).'</pre></body></html>';