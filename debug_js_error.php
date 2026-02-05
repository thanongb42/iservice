<?php
// Check for JavaScript syntax errors in the HTML output
$html = file_get_contents('http://localhost/iservice/index.php');

// Find all <script> tags
preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $matches);

echo "Found " . count($matches[0]) . " script tags\n\n";

foreach ($matches[1] as $i => $scriptContent) {
    echo "Script $i (first 200 chars):\n";
    echo substr($scriptContent, 0, 200) . "\n";
    echo "...\n\n";
}

// Check for non-ASCII characters around line 2242
$lines = explode("\n", $html);
echo "Lines around 2240-2245:\n";
for ($i = 2239; $i < 2245 && $i < count($lines); $i++) {
    echo "Line " . ($i + 1) . ": " . substr($lines[$i], 0, 100) . "\n";
}
?>
