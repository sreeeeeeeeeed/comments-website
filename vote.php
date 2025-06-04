<?php
// ──────────────────────────────────────────────────────────────────────────────
// 3A) Simple vote handler (vote.php)
// ──────────────────────────────────────────────────────────────────────────────
$servername = getenv("DB_HOST");
$dbuser     = getenv("DB_USER");
$dbpass     = getenv("DB_PASSWORD");
$dbname     = getenv("DB_NAME");

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Expect a POST like vote.php?option_id=42
if (isset($_POST['option_id'])) {
    $option_id = (int) $_POST['option_id'];

    // 3B) Increment votes for that option
    $stmt = $conn->prepare("UPDATE poll_option SET votes = votes + 1 WHERE id = ?");
    $stmt->bind_param("i", $option_id);
    $stmt->execute();
    $stmt->close();
}

// 3C) Redirect back to index.php so the user sees results
header("Location: index.php?voted=1");
exit();
?>
