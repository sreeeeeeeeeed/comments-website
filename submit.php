<?php
// Database connection setup
$servername = getenv("DB_HOST");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$dbname   = getenv("DB_NAME");

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$message = $_POST['message'];

// Insert into database
$sql = "INSERT INTO messages (name, email, message) VALUES ('$name', '$email', '$message')";

if ($conn->query($sql) === TRUE) {
  // ✅ Redirect only if successful
  header("Location: index.php");
  exit();
} else {
  // ❌ Only show error if something fails (you could also log it instead)
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
