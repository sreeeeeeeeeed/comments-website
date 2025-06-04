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

// Get form data, sanitize inputs (basic)
$name = $conn->real_escape_string($_POST['name']);
$email = $conn->real_escape_string($_POST['email']);
$message = $conn->real_escape_string($_POST['message']);

// Insert into database
$sql = "INSERT INTO messages (name, email, message) VALUES ('$name', '$email', '$message')";

if ($conn->query($sql) === TRUE) {
    // Send confirmation email via Mailgun API

    $mgDomain = "sandbox0c8fa087907b4101a47ebfafb554de33.mailgun.org/settings?tab=setup";  // e.g. sandboxXXX.mailgun.org or your own domain
    $mgApiKey = "f5c4bbf6ddc6224bdc12f1d46c608136-08c79601-d451062d"; // your private API key

    $postData = [
        'from' => 'KAH YANG Team <teoky2020@gmail.com>',
        'to' => $email,
        'subject' => 'Thanks for posting on our website',
        'text' => "Hi $name,\n\nThank you for leaving a comment on our site. We appreciate your feedback:\n\n\"$message\"\n\n— The KAH YANG Team\nhttps://comments-website.onrender.com"
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/$mgDomain/messages");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $mgApiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $result = curl_exec($ch);
    if(curl_errno($ch)) {
        error_log('Mailgun cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    // Redirect back to index.php
    header("Location: index.php");
    exit();

} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
