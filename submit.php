<?php
// Include PHPMailer manually (assuming you copied src/ into your project)
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ──────────────────────────────────────────────────────────────────────────────
// 1) DATABASE CONNECTION
// ──────────────────────────────────────────────────────────────────────────────
$servername = getenv("DB_HOST");
$dbuser     = getenv("DB_USER");
$dbpass     = getenv("DB_PASSWORD");
$dbname     = getenv("DB_NAME");
mysqli_close(mysqli_connect());
$conn = new mysqli($servername, $dbuser, $dbpass, $dbname,3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ──────────────────────────────────────────────────────────────────────────────
// 2) GET & SANITIZE FORM DATA
// ──────────────────────────────────────────────────────────────────────────────
$name    = $_POST['name']   ?? '';
$email   = $_POST['email']  ?? '';
$message = $_POST['message'] ?? '';

$sql = "INSERT INTO messages (name, email, message) VALUES ('$name', '$email', '$message')";

if ($conn->query($sql) === TRUE) {
    // Continue with mail + redirect...

    // ──────────────────────────────────────────────────────────────────────────
    // 4) SEND THANK-YOU EMAIL VIA GMAIL SMTP
    // ──────────────────────────────────────────────────────────────────────────
    $mail = new PHPMailer(true);
    try {
        // a) Server settings
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = "teoky2020@gmail.com";
        $mail->Password   = getenv('GMAIL_SMTP_PASS');       // the 16-char App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // TLS
        $mail->Port       = (int)getenv('GMAIL_SMTP_PORT');  // 587

        // b) Recipients
        $mail->setFrom("teoky2020@gmail.com", "Kah Yang Team");
        $mail->addAddress($email, $name);

        // c) Content
        $mail->isHTML(false);
        $mail->Subject = 'Thanks for posting on my website';
        $mail->Body    =
          "Hi $name,\n\n" .
          "Thank you comrade, for leaving a comment on our site. We appreciate your contribution:\n\n" .
          "\"$message\"\n\n" .
          "The KAH YANG Team\n" .
          "https://comments-website.onrender.com";

        $mail->send();
        // Email sent successfully
    } catch (Exception $e) {
        // Log error but do not interrupt user flow
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5) REDIRECT BACK TO index.php
    // ──────────────────────────────────────────────────────────────────────────
    header("Location: index.php");
    exit();
} else {
    echo "Error inserting comment: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
