<?php
// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";  // change if needed
$dbname = "databaseforcomments";  // replace with your DB name

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $message = $_POST['message'] ?? '';

  // Basic validation (you can improve this)
  if ($name && $email && $message) {
    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);
    $stmt->execute();
    $stmt->close();

    echo "<p style='color:green;'>Thank you for your message!</p>";
  } else {
    echo "<p style='color:red;'>Please fill in all fields.</p>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Guestbook</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    form { margin-bottom: 30px; }
    input, textarea { width: 300px; display: block; margin-bottom: 15px; padding: 8px; }
    textarea { height: 100px; }
    .comment { border-bottom: 1px solid #ccc; margin-bottom: 15px; padding-bottom: 10px; }
    .meta { font-size: 0.8em; color: gray; }
  </style>
</head>
<body>

<h1>Guestbook</h1>

<form method="post" action="" onsubmit="return Validate();">
  <input type="text" name="name" placeholder="Your Name" required />
  <input type="email" name="email" placeholder="Your Email" required />
  <textarea name="message" placeholder="Your Message" required></textarea>
  <input type="submit" value="Submit" />
</form>

<h2>Previous Comments</h2>

<?php
// Fetch and display messages
$sql = "SELECT name, message, posted_on FROM messages ORDER BY posted_on DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    echo "<div class='comment'>";
    echo "<p><strong>" . htmlspecialchars($row["name"]) . "</strong></p>";
    echo "<p>" . nl2br(htmlspecialchars($row["message"])) . "</p>";
    echo "<p class='meta'>Posted on " . $row["posted_on"] . "</p>";
    echo "</div>";
  }
} else {
  echo "<p>No comments yet. Be the first to post!</p>";
}

$conn->close();
?>

<script>
function Validate() {
  var form = document.forms[0];
  var name = form["name"].value.trim();
  var email = form["email"].value.trim();
  if(name === "") {
    alert("Please enter your Name!");
    return false;
  }
  if(email === "") {
    alert("Please enter your Email!");
    return false;
  }
  var atpos = email.indexOf("@");
  var dotpos = email.lastIndexOf(".");
  if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= email.length) {
    alert("Not a valid e-mail address");
    return false;
  }
  return true;
}
</script>

</body>
</html>
