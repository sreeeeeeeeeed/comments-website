<?php
// ─── 2A) CONNECT TO THE DATABASE ──────────────────────────────────────────
$servername = getenv("DB_HOST");      // e.g. "sql12.freesqldatabase.com"
$username   = getenv("DB_USER");      // e.g. "sql12782613"
$password   = getenv("DB_PASSWORD");  // whatever you set
$dbname     = getenv("DB_NAME");      // e.g. "sql12782613"

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("DB Connection failed: " . $conn->connect_error);
}

// ─── 2B) LOG TODAY’S VISIT ──────────────────────────────────────────────────
// Get today’s date in "YYYY-MM-DD" format
$today = date('Y-m-d');

// Insert a new row for today with count=1, or increment if already exists
$sqlInsert = "
  INSERT INTO visitors (visit_date, count)
  VALUES (?, 1)
  ON DUPLICATE KEY UPDATE count = count + 1
";
$stmt = $conn->prepare($sqlInsert);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->close();

// ─── 2C) PREPARE LAST 7 DAYS FOR CHART ────────────────────────────────────
// Build an array of the last 7 dates (from 6 days ago to today)
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-{$i} days"));
}

// Initialize a PHP array mapping each date to a default count of 0
$counts = array_fill_keys($dates, 0);

// Fetch counts for these dates from the DB
// We’ll run a single query with WHERE visit_date IN ('2025-05-28', '2025-05-29', …)
$inClause = "'" . implode("','", $dates) . "'";
$sqlQuery = "
  SELECT visit_date, count
  FROM visitors
  WHERE visit_date IN ($inClause)
";
$result = $conn->query($sqlQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counts[$row['visit_date']] = (int)$row['count'];
    }
}
$conn->close();

// Convert PHP arrays to JSON for JavaScript
$labelsJSON = json_encode(array_values($dates));   // e.g. ["2025-05-28","2025-05-29",…]
$dataJSON   = json_encode(array_values($counts));  // e.g. [12, 8, 15, …]
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kah Yang's personal comments page</title>
  <link rel="icon" href="favicon.png" type="https://static.vecteezy.com/system/resources/previews/018/741/758/original/comment-box-3d-icon-png.png">
  <!-- Chart.js CDN for bar chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Google Font: Orbitron for techno vibe -->
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />

  <style>
    /* Reset & base */
    * {
      box-sizing: border-box;
    }
    body {
background-image: url('https://images.freeimages.com/images/large-previews/01a/technology-background-1632715.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  color: #e0faff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  z-index: 0;
}
body::before {
  content: "";
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.6); /* 60% black overlay to dim */
  z-index: -1;
}
.about-me {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-top: 50px;
  gap: 20px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
  padding: 20px;
  background-color: rgba(255, 255, 255, 0.05); /* faint background for visibility */
  border-radius: 12px;
}

.profile-pic {
  width: 150px;
  height: 150px;
  object-fit: cover;
  border-radius: 50%;
  box-shadow: 0 0 15px rgba(0, 255, 255, 0.4); /* techno glow */
}

.about-text h2 {
  font-size: 1.8rem;
  color: cyan;
  margin-bottom: 10px;
}

.about-text p {
  font-size: 1rem;
  color: white;
  line-height: 1.6;
}

h1 {
  text-align: center;
  margin-top: 40px;
  color: #00f0ff;
  text-shadow: 0 0 4px #00f0ff;
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
    <h2 style="text-align:center; color:#00f0ff; margin-bottom:10px;">
  Visitors in the Last 7 Days
</h2>
<div style="max-width:700px; margin: auto; background:#111; padding:15px; border-radius:8px; box-shadow:0 0 6px #00f0ff;">
  <canvas id="visitorsChart">  </canvas>
</div>
<script>
  // 3A) Grab the PHP-generated JSON arrays
  const labels = <?php echo $labelsJSON; ?>;   // e.g. ["2025-05-28","2025-05-29", …]
  const data   = <?php echo $dataJSON; ?>;     // e.g. [12, 8, 15, …]

  // 3B) Create the bar chart
  const ctx = document.getElementById('visitorsChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Visitors',
        data: data,
        backgroundColor: 'rgba(0, 255, 255, 0.6)',
        borderColor: 'rgba(0, 255, 255, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        x: {
          ticks: {
            color: '#00f0ff'
          },
          grid: {
            color: '#004d40'
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: '#00f0ff'
          },
          grid: {
            color: '#004d40'
          }
        }
      },
      plugins: {
        legend: {
          labels: {
            color: '#00f0ff'
          }
        }
      }
    }
  });
</script>

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
  <img src="tmp_ed06b594-533b-4b2e-b8a2-325460be1b00.jpeg" alt="Picture of Me" class="profile-pic">
  <div class="about-text">
    <h2>About this site:</h2>
    <p>Hello! No SQL inject or DDoS pls. Thanks :D. </p>
  </div>
</div>

</body>
</html>
