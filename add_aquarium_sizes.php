<?php
include 'db.php';
session_start();

// Fetch fishes for dropdown
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fish_ids = $_POST['fish_id'] ?? [];
    $tank_sizes = $_POST['tank_size'] ?? [];
    $infos = $_POST['info'] ?? [];
    $cleanings = $_POST['cleaning_frequency'] ?? [];

    foreach ($fish_ids as $fish_id) {
        $fid = intval($fish_id);
        foreach ($tank_sizes as $index => $tank_size) {
            $size = trim($tank_sizes[$index]);
            $info = trim($infos[$index] ?? '');
            $cleaning = trim($cleanings[$index] ?? '');
            if ($size && $info && $cleaning) {
                $stmt = $conn->prepare("INSERT INTO aquarium_sizes (fish_id, tank_size, info, cleaning_frequency) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $fid, $size, $info, $cleaning);
                $stmt->execute();
            }
        }
    }

    header("Location: admin.php");
    exit();
}
?>

<style>
/* ===== GENERAL STYLES ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #f1f8f9);
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
}

/* ===== CONTAINER ===== */
.container {
    background-color: #ffffff;
    padding: 30px 25px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    width: 100%;
    max-width: 800px; /* fits larger screens */
    box-sizing: border-box;
    position: relative;
    border: 1px solid #b2ebf2;
}

/* ===== BACK BUTTON ===== */
.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: #00bcd4;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}
.back-button:hover {
    background-color: #0097a7;
    transform: translateY(-2px);
}

/* ===== TITLE ===== */
h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #00796b;
    font-size: 26px;
    font-weight: bold;
}

/* ===== FORM ELEMENTS ===== */
form label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

select[multiple] {
    width: 100%;
    height: 120px;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #cfd8dc;
    font-size: 15px;
    background-color: #fafafa;
    box-sizing: border-box;
}

small {
    display: block;
    margin-bottom: 15px;
    color: #555;
}

/* ===== FORM ROWS ===== */
.form-row {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 10px;
    margin-bottom: 10px;
}

.form-row input,
.form-row textarea {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #cfd8dc;
    font-size: 15px;
    box-sizing: border-box;
}

textarea { 
    resize: vertical; 
    min-height: 60px; 
}

/* ===== BUTTONS ===== */
button[type="submit"], 
button.add-row {
    display: inline-block;
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff;
    border: none;
    padding: 12px 18px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
    margin-top: 10px;
}

button[type="submit"]:hover,
button.add-row:hover {
    background: linear-gradient(135deg, #0097a7, #00796b);
    transform: translateY(-2px);
}

button.add-row {
    width: 100%;
    margin-bottom: 15px; /* add gap between add-row and submit */
}

/* ===== RESPONSIVE ===== */
@media (max-width: 700px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .form-row textarea {
        min-height: 100px;
    }
    button.add-row, button[type="submit"] {
        font-size: 14px;
        padding: 10px 14px;
    }
}
</style>

<div class="container">
    <a href="admin.php" class="back-button">← Back</a>
    <h2>Add Aquarium Size Info</h2>
    <form method="POST" action="">
        <label for="fish_id">Select Fish(es)</label>
        <select name="fish_id[]" id="fish_id" multiple required>
            <?php while ($fish = $fishes->fetch_assoc()): ?>
                <option value="<?= $fish['id']; ?>"><?= htmlspecialchars($fish['name']); ?></option>
            <?php endwhile; ?>
        </select>
        <small>Hold Ctrl (Windows) or Command (Mac) to select multiple fishes</small>

        <div id="aquariumRows">
            <div class="form-row">
                <input type="text" name="tank_size[]" placeholder="Tank Size e.g. 2.5 Gal" required>
                <textarea name="info[]" placeholder="Tank Info / Recommendations" required></textarea>
                <input type="text" name="cleaning_frequency[]" placeholder="Cleaning Frequency e.g. Weekly" required>
            </div>
        </div>

        <button type="button" class="add-row" onclick="addRow()">+ Add Another Tank Size</button>
        <br><br>
        <button type="submit">Add Aquarium Size Info</button>
    </form>
</div>

<script>
function addRow() {
    const container = document.getElementById('aquariumRows');
    const row = document.createElement('div');
    row.className = 'form-row';
    row.innerHTML = `
        <input type="text" name="tank_size[]" placeholder="Tank Size e.g. 5 Gal" required>
        <textarea name="info[]" placeholder="Tank Info / Recommendations" required></textarea>
        <input type="text" name="cleaning_frequency[]" placeholder="Cleaning Frequency e.g. Weekly" required>
    `;
    container.appendChild(row);
}
</script>
