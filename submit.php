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

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ──────────────────────────────────────────────────────────────────────────────
// 2) GET & SANITIZE FORM DATA
// ──────────────────────────────────────────────────────────────────────────────
$name    = $conn->real_escape_string($_POST['name']   ?? '');
$email   = $conn->real_escape_string($_POST['email']  ?? '');
$message = $conn->real_escape_string($_POST['message'] ?? '');

// ──────────────────────────────────────────────────────────────────────────────
// 3) INSERT INTO messages TABLE
// ──────────────────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $message);

if ($stmt->execute()) {
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
        $mail->Subject = 'Thanks for posting on our website';
        $mail->Body    =
          "Hi $name,\n\n" .
          "Thank you for leaving a comment on our site. We appreciate your feedback:\n\n" .
          "\"$message\"\n\n" .
          "— The KAH YANG Team\n" .
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
