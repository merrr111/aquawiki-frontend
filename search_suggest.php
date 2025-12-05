<?php
include 'db.php';
header('Content-Type: application/json');

$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = 'utf8mb4_general_ci'");

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all','fish','plant','type'];
if (!in_array($filter, $allowedFilters)) $filter = 'all';

if ($q === '') {
    echo json_encode([]);
    exit;
}

$sqlParts = [];
$params = [];
$types = '';

// 🐟 FISH
if ($filter === 'all' || $filter === 'fish') {
    $sqlParts[] = "
        SELECT 'fish' AS category, id, name, image_url
        FROM fishes
        WHERE status = 1
          AND (name LIKE ? OR scientific_name LIKE ?)
    ";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= 'ss';
}

// 🐠 FISH TYPES
if ($filter === 'all' || $filter === 'type') {
    $sqlParts[] = "
        SELECT 'type' AS category, MIN(id) AS id, type AS name, MIN(image_url) AS image_url
        FROM fishes
        WHERE status = 1 AND type LIKE ?
        GROUP BY type
    ";
    $params[] = "%$q%";
    $types .= 's';
}

// 🌱 PLANTS
if ($filter === 'all' || $filter === 'plant') {
    $sqlParts[] = "
        SELECT 'plant' AS category, id, name, image_url
        FROM plants
        WHERE name LIKE ? OR scientific_name LIKE ?
    ";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= 'ss';
}

if (empty($sqlParts)) {
    echo json_encode([]);
    exit;
}

// Combine queries with UNION ALL (better for performance than UNION)
$query = implode(" UNION ALL ", $sqlParts) . " LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'image_url' => $row['image_url'],
        'category' => $row['category']
    ];
}

echo json_encode($data);
