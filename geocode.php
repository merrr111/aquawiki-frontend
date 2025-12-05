<?php
// MUST be at the top to avoid HTML output
header("Content-Type: application/json");
error_reporting(0);

// Check parameter
if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$query = urlencode($_GET['q']);
$url = "https://nominatim.openstreetmap.org/search?format=json&q=$query";

// cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Nominatim requires a valid user agent
curl_setopt($ch, CURLOPT_USERAGENT, 'AquaWiki/1.0 (admin@aquawiki.com)');

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// In case Nominatim blocks or fails → return empty array so JS doesn't break
if (!$response || $httpcode !== 200) {
    echo json_encode([]);
    exit;
}

// Output only clean JSON
echo $response;
exit;
?>
