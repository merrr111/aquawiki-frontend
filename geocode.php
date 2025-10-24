<?php
if (!isset($_GET['q'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing query"]);
    exit;
}

$query = urlencode($_GET['q']);
$url = "https://nominatim.openstreetmap.org/search?format=json&q=$query";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'AquaWiki/1.0 (your_email@example.com)'); // Nominatim requires a user-agent
$response = curl_exec($ch);
curl_close($ch);

header("Content-Type: application/json");
echo $response;
?>
