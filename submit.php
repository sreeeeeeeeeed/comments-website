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
  $to      = $email;
    $subject = "Thanks for posting on our website";
    $body    = "Hi " . $name . ",\n\n"
             . "Thank you for leaving a comment on our site. We appreciate your feedback:\n\n"
             . "\"". $message . "\"\n\n"
             . "— The KAH YANG Team (totally not just 1 person)\n"
             . "https://comments-website.onrender.com\n";
    // Additional headers
    $headers = "From: no-reply@yourdomain.com\r\n"
             . "Reply-To: no-reply@yourdomain.com\r\n"
             . "X-Mailer: PHP/" . phpversion();

    // Send the email
    @mail($to, $subject, $body, $headers);
    // (Suppress errors with @mail; you can remove @ to see warnings.)

    // ───────────────────────────────────────────────────────────────────
    // 3) Redirect back to index.php (no output before this header)
    // ───────────────────────────────────────────────────────────────────
    header("Location: index.php");
    exit();
} else {
  // ❌ Only show error if something fails (you could also log it instead)
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
