<?php
define('OCR_API_URL', 'https://australiaeast.api.cognitive.microsoft.com/vision/v1.0/ocr');
define('OCR_API_KEY', 'de4d79cf6c064323b22380281969110a');

define('FACE_API_URL', 'https://eastasia.api.cognitive.microsoft.com/face/v1.0/detect');
define('FACE_API_OPT', '?returnFaceId=false&returnFaceAttributes=gender');
define('FACE_API_KEY', '694a4593403349b4b350aa86afb9f3fd');

define('TOLERANCE', 2);// x = tolerance, y = tolerance * 2
define('TXLEFTBUF', 5);
define('CHARSPACE', ['en' => 7, 'zh' => 4.5]);
define('SEPARATOR', ['en' => ' ', 'zh' => '']);
