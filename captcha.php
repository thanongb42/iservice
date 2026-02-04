<?php
session_start();

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    die("GD extension is required for CAPTCHA.");
}

// Generate random code (alphanumeric, 6 chars)
$characters = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Removed I, O to avoid confusion
$captcha_code = '';
for ($i = 0; $i < 6; $i++) {
    $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
}

$_SESSION["captcha_code"] = $captcha_code;

// Create image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 243, 244, 246); // gray-100 equivalent
$text_color = imagecolorallocate($image, 13, 148, 136); // teal-600 equivalent
$line_color = imagecolorallocate($image, 209, 213, 219); // gray-300
$pixel_color = imagecolorallocate($image, 156, 163, 175); // gray-400

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add noise (lines)
for($i=0; $i<10; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add noise (dots)
for($i=0; $i<500; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $pixel_color);
}

// Add text (using simple font if built-in, or try to load a font if we had one)
// Using built-in font for simplicity and robustness
$font_size = 5; // Built-in font size (1-5)
$text_x = ($width - (imagefontwidth($font_size) * strlen($captcha_code))) / 2;
$text_y = ($height - imagefontheight($font_size)) / 2;

imagestring($image, $font_size, $text_x, $text_y, $captcha_code, $text_color);

// Use a wave filter manually if possible or just simple distortion? 
// For simplicity, just the text is fine for now, user asked for captcha.

// Output
header("Content-type: image/png");
// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

imagepng($image);
imagedestroy($image);
?>