<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Techno Guestbook</title>

  <!-- Google Font: Orbitron for techno vibe -->
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />

  <style>
    /* Reset & base */
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      background: #0a0f22;
      color: #00ffea;
      font-family: 'Orbitron', sans-serif;
      display: flex;
      justify-content: center;
      padding: 2rem;
      min-height: 100vh;
      flex-direction: column;
      align-items: center;
      user-select: none;
    }
    h1 {
      font-weight: 900;
      font-size: 3rem;
      margin-bottom: 1rem;
      text-shadow:
        0 0 5px #00ffea,
        0 0 10px #00ffea,
        0 0 20px #00ffea;
    }

    /* Container */
    .container {
      background: #111828;
      padding: 2rem;
      border-radius: 15px;
      box-shadow:
        0 0 15px #00ffea,
        inset 0 0 30px #00ffc8;
      max-width: 600px;
      width: 100%;
    }

    /* Form styling */
    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    input, textarea {
      background: #0a0f22;
      border: 2px solid #00ffea;
      border-radius: 8px;
      color: #00ffea;
      font-size: 1.1rem;
      padding: 0.8rem 1rem;
      transition: border-color 0.3s ease;
      font-family: 'Orbitron', monospace;
    }
    input::placeholder,
    textarea::placeholder {
      color: #00ffeaaa;
    }
    input:focus, textarea:focus {
      border-color: #00ffc8;
      outline: none;
      box-shadow: 0 0 10px #00ffc8;
    }

    button {
      background: #00ffea;
      border: none;
      padding: 1rem;
      font-size: 1.3rem;
      font-weight: 700;
      color: #0a0f22;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.3s ease;
      font-family: 'Orbitron', monospace;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      box-shadow:
        0 0 15px #00ffea,
        0 0 30px #00ffc8;
    }
    button:hover {
      background: #00ffc8;
      box-shadow:
        0 0 25px #00ffc8,
        0 0 40px #00ffea;
    }

    /* Message list */
    .message-list {
      max-height: 300px;
      overflow-y: auto;
      border-top: 2px solid #00ffea;
      padding-top: 1rem;
      font-size: 1.1rem;
      font-family: 'Orbitron', monospace;
    }

    .message {
      padding: 0.8rem 0;
      border-bottom: 1px solid #004d40;
      color: #00ffc8;
      text-shadow: 0 0 3px #00ffea44;
    }

    .message:last-child {
      border-bottom: none;
    }

    .message .name {
      font-weight: 900;
      font-size: 1.2rem;
      color: #00ffe1;
      text-shadow:
        0 0 3px #00ffe1,
        0 0 7px #00ffe1;
    }

    .message .posted_on {
      font-size: 0.8rem;
      color: #00695c;
      margin-bottom: 0.3rem;
      font-style: italic;
    }

    .message .content {
      white-space: pre-wrap;
      color: #00ffd8cc;
    }

    /* Scrollbar styling */
    .message-list::-webkit-scrollbar {
      width: 8px;
    }
    .message-list::-webkit-scrollbar-track {
      background: #0a0f22;
    }
    .message-list::-webkit-scrollbar-thumb {
      background: #00ffea99;
      border-radius: 4px;
    }
  </style>

  <script>
    function Validate() {
      var form = document.forms["guest"];
      var name = form["name"].value.trim();
      var email = form["email"].value.trim();
      var message = form["message"].value.trim();

      if (!name) {
        alert("Please enter your Name!");
        return false;
      }
      if (!email) {
        alert("Please enter your Email!");
        return false;
      }
      if (!message) {
        alert("Please enter your Message!");
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
</head>
<body>
  <h1>Techno Guestbook</h1>

  <div class="container">
    <form name="guest" method="POST" action="submit.php" onsubmit="return Validate()">
      <input type="text" name="name" placeholder="Your Name" required />
      <input type="email" name="email" placeholder="Your Email" required />
      <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
      <button type="submit">Post Comment</button>
    </form>

    <div class="message-list">
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
</body>
</html>
