<?php
// Set the content type to image/png
header('Content-Type: image/png');

// Create a 200x200 image
$image = imagecreatetruecolor(200, 200);

// Colors
$white = imagecolorallocate($image, 255, 255, 255);
$gray = imagecolorallocate($image, 200, 200, 200);
$darkGray = imagecolorallocate($image, 150, 150, 150);

// Fill background
imagefill($image, 0, 0, $white);

// Draw a product box icon
imagerectangle($image, 50, 50, 150, 150, $darkGray);
imagefilledrectangle($image, 51, 51, 149, 149, $gray);

// Output the image
imagepng($image);
imagedestroy($image); 