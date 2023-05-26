<?php
// testing library
require('engine.conf.php');
require('ocr.core.php');

// testing code
$ratio = (591 / 768);
$curl_result = '{"textAngle":0,"orientation":"NotDetected","language":"en","regions":[{"boundingBox":"46,107,315,227","lines":[{"boundingBox":"46,107,264,18","words":[{"boundingBox":"46,108,60,17","text":"WONG,"},{"boundingBox":"122,107,36,16","text":"Kin"},{"boundingBox":"173,108,49,15","text":"Yuen"},{"boundingBox":"237,108,73,16","text":"Thomas"}]},{"boundingBox":"186,139,175,17","words":[{"boundingBox":"186,139,48,16","text":"3769"},{"boundingBox":"248,140,47,15","text":"0051"},{"boundingBox":"313,140,48,16","text":"3293"}]},{"boundingBox":"186,208,123,16","words":[{"boundingBox":"186,208,123,16","text":"12-03-1962"}]},{"boundingBox":"184,257,147,13","words":[{"boundingBox":"247,257,30,13","text":"Date"},{"boundingBox":"280,257,14,13","text":"of"},{"boundingBox":"297,258,34,12","text":"tssue"}]},{"boundingBox":"187,280,82,16","words":[{"boundingBox":"187,280,82,16","text":"(11-70)"}]},{"boundingBox":"185,314,123,20","words":[{"boundingBox":"185,314,123,20","text":"29-03-05"}]}]},{"boundingBox":"374,315,152,20","lines":[{"boundingBox":"374,315,152,20","words":[{"boundingBox":"374,315,107,20","text":"E854844"},{"boundingBox":"488,316,38,19","text":"(0)"}]}]}]}';
echo '<!DOCTYPE html><html><body><pre>'.json_encode(
	OCR($curl_result, [
		'tolerance' => round(4 / $ratio),
		'charSpace' => [
			'en' => round(7 / $ratio),
			'zh' => round(4.5 / $ratio)
		],
		'separator' => [
			'en' => ' ',
			'zh' => ''
		]
	]), JSON_PRETTY_PRINT
).'</pre></body></html>';