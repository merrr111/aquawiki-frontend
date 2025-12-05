<?php
include 'db.php';

$identifyUrl = "https://aquawiki-ai-1bh3.onrender.com/identify";

$result = $conn->query("SELECT id, image_path FROM user_uploads WHERE matched_fish_id IS NULL");

while ($row = $result->fetch_assoc()) {
    $uploadId = $row['id'];
    $imagePath = $row['image_path'];

    if (!file_exists($imagePath)) continue;

    $ch = curl_init();
    $cfile = new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath));
    curl_setopt_array($ch, [
        CURLOPT_URL => $identifyUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => ['file' => $cfile],
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $matchedFishId = $data['matched_fish']['id'] ?? null;

    if ($matchedFishId) {
        $stmt = $conn->prepare("UPDATE user_uploads SET matched_fish_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $matchedFishId, $uploadId);
        $stmt->execute();
        $stmt->close();
        echo "Upload $uploadId updated to $matchedFishId<br>";
    }
}
$conn->close();
echo "✅ All missing uploads fixed.";
