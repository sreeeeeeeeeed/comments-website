<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kah Yang's personal comments page</title>

  <!-- Google Font: Orbitron for techno vibe -->
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />

  <style>
    /* Reset & base */
    * {
      box-sizing: border-box;
    }
    body {
background-image: url('https://www.bing.com/images/search?view=detailV2&ccid=y0YJL6av&id=4D0EF7593CF420853F62F167A633163435EFB775&thid=OIP.y0YJL6avWeAKQkMmXCnX4QHaE8&mediaurl=https%3A%2F%2Fimages.freeimages.com%2Fimages%2Flarge-previews%2F01a%2Ftechnology-background-1632715.jpg&exph=1068&expw=1600&q=tech+background&simid=608050388194101068&FORM=IRPRST&ck=B4A90B8FA80D4590490B63D084851C93&selectedIndex=4&itb=0&cw=941&ch=755&ajaxhist=0&ajaxserp=0');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  color: #e0faff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
}

h1 {
  font-size: 3rem;
  color: #00f0ff;
  text-shadow: 0 0 4px #00f0ff; /* Reduced glow */
  margin-bottom: 30px;
}

form {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}

input[type="text"],
input[type="email"],
textarea,
input[type="submit"] {
  width: 100%;
  max-width: 400px;
  box-sizing: border-box;
}


input, textarea {
  background-color: #1a1a1a;
  border: 1px solid #00f0ff;
  color: #00f0ff;
  padding: 10px;
  border-radius: 5px;
  margin: 10px 0;
  width: 100%;
  max-width: 400px;
  font-family: inherit;
  box-shadow: 0 0 4px #00f0ff; /* Less intense */
}

button {
  background-color: #00f0ff;
  color: #0d0d0d;
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 0 6px #00f0ff, 0 0 12px #00f0ff inset;
  transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
}

button:hover {
  background-color: #00d4e0;
  box-shadow: 0 0 10px #00f0ff, 0 0 20px #00f0ff inset;
  transform: scale(1.05);
}


.message {
  background-color: #1a1a1a;
  border-left: 4px solid #00f0ff;
  margin: 20px auto;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 0 5px #00f0ff; /* Reduced */
  max-width: 500px;
  text-align: left;
}

.name {
  font-weight: bold;
  color: #00f0ff;
}

.posted_on {
  font-size: 0.9em;
  color: #88ddee;
  margin-bottom: 8px;
}

.content {
  color: #e0faff;
  line-height: 1.5;
}

  </style>

  <script>
    function Validate() {
      var form = document.forms["guest"];
      var name = form["name"].value.trim();
      var email = form["email"].value.trim();
      var message = form["message"].value.trim();

      if (!name) {
        alert("Blud. Enter your name");
        return false;
      }
      if (!email) {
        alert("Email so I can trace you hehe");
        return false;
      }
      if (!message) {
        alert("Seriously? No message?");
        return false;
      }
      var atpos = email.indexOf("@");
      var dotpos = email.lastIndexOf(".");
      if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= email.length) {
        alert("Thats a fake email address1!!!!1! Or if you're trying to SQL inject, i've already safeguarded against that.");
        return false;
      }
      return true;
    }
  </script>
</head>
<body>
  <h1>Kah Yang's personal comments page</h1>

  <div class="container">
    <form name="guest" method="POST" action="submit.php" onsubmit="return Validate()">
      <input type="text" name="name" placeholder="Your Name" required />
      <input type="email" name="email" placeholder="Your Email" required />
      <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
      <button type="submit">Post Comment</button>
    </form>

    <div class="message-list">
      <?php
$servername = getenv("DB_HOST");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$dbname = getenv("DB_NAME");


// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Run the query
$sql = "SELECT name, message, posted_on FROM messages ORDER BY posted_on DESC";
$result = $conn->query($sql);

// Check for query error
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

      <?php
      // Assume $conn is your mysqli connection and you've queried messages
      // Example:
      // $result = $conn->query("SELECT name, message, posted_on FROM messages ORDER BY posted_on DESC");

      while ($row = $result->fetch_assoc()) {
          echo '<div class="message">';
          echo '<div class="name">' . htmlspecialchars($row['name']) . '</div>';
          echo '<div class="posted_on">' . htmlspecialchars($row['posted_on']) . '</div>';
          echo '<div class="content">' . nl2br(htmlspecialchars($row['message'])) . '</div>';
          echo '</div>';
      }
      ?>
    </div>
  </div>
  <div class="about-me">
  <img src="me.jpg" alt="Picture of Me" class="profile-pic">
  <div class="about-text">
    <h2>Me</h2>
    <p>Hallo! I learnt how to backend and database to make this. Was it worth the time and effort? Probably not. Is it cool though? Kinda </p>
  </div>
</div>

</body>
</html>
