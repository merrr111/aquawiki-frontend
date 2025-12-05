<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    echo "No disease selected.";
    exit();
}

$disease_id = intval($_GET['id']);

// Fetch disease details
$stmt = $conn->prepare("SELECT * FROM diseases WHERE id = ?");
$stmt->bind_param("i", $disease_id);
$stmt->execute();
$result = $stmt->get_result();
$disease = $result->fetch_assoc();

if (!$disease) {
    echo "Disease not found.";
    exit();
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $scientific_name = $_POST['scientific_name'];
    $description = $_POST['description'];
    $prevention = $_POST['prevention'];
    $treatment = $_POST['treatment'];

    $upload_dir = 'uploads/';
    $image_url = $disease['image_url']; // keep old image by default

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
            $new_name = uniqid() . '.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                $image_url = $path;
            }
        }
    }

    // Update disease record
    $stmt = $conn->prepare("UPDATE diseases SET name=?, scientific_name=?, description=?, prevention=?, treatment=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $scientific_name, $description, $prevention, $treatment, $image_url, $disease_id);
    $stmt->execute();

    header("Location: admin_diseases.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Disease</title>
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f0f4f7, #e6f7f1); /* soft gray/blue gradient */
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}

.container {
    background-color: #ffffff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    width: 100%;
    max-width: 700px;
}

/* ===== Headings ===== */
h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #555; /* gray heading color */
}

/* ===== Back Button ===== */
.back-button {
    display: inline-block;
    margin-bottom: 20px;
    background-color: #999; /* gray */
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease;
}
.back-button:hover {
    background-color: #777;
}

/* ===== Form Styles ===== */
form {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

input[type="text"],
input[type="file"],
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cfd8dc;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 14px;
    background-color: #fafafa;
    transition: border 0.3s ease, box-shadow 0.2s ease;
}

input[type="text"]:focus,
input[type="file"]:focus,
textarea:focus {
    border-color: #999;
    box-shadow: 0 0 6px rgba(153, 153, 153, 0.3);
    outline: none;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

/* ===== Image Preview ===== */
img {
    margin-top: 10px;
    max-width: 150px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

/* ===== Submit Button ===== */
button[type="submit"] {
    background: #555; /* gray */
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease, transform 0.2s ease;
}
button[type="submit"]:hover {
    background: #777;
    transform: translateY(-1px);
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
    .container {
        padding: 20px;
    }
    input, textarea, button[type="submit"] {
        font-size: 14px;
        padding: 10px;
    }
}
    </style>
</head>
<body>
<div class="container">
    <a href="admin_diseases.php" class="back-button">← Back</a>
    <h2>Edit Disease</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="name" value="<?php echo htmlspecialchars($disease['name']); ?>" placeholder="Disease Name" required>
        <input type="text" name="scientific_name" value="<?php echo htmlspecialchars($disease['scientific_name']); ?>" placeholder="Scientific Name" required>
        <textarea name="description" placeholder="Description" required><?php echo htmlspecialchars($disease['description']); ?></textarea>
        <textarea name="prevention" placeholder="Prevention" required><?php echo htmlspecialchars($disease['prevention']); ?></textarea>
        <textarea name="treatment" placeholder="Treatment" required><?php echo htmlspecialchars($disease['treatment']); ?></textarea>

        <!-- Image Upload -->
        <input type="file" name="image">
        <?php if ($disease['image_url']): ?>
            <p>Current Image:</p>
            <img src="<?php echo $disease['image_url']; ?>" alt="Disease Image" width="100">
        <?php endif; ?>

        <div class="full-width">
            <button type="submit">Update Disease</button>
        </div>
    </form>
</div>
</body>
</html>
