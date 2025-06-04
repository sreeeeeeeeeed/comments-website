<?php
// ──────────────────────────────────────────────────────────────────────────────
// 4A) Poll‐related setup at the very top of index.php (before any HTML output)
// ──────────────────────────────────────────────────────────────────────────────
$servername = getenv("DB_HOST");
$dbuser     = getenv("DB_USER");
$dbpass     = getenv("DB_PASSWORD");
$dbname     = getenv("DB_NAME");

$conn_p = new mysqli($servername, $dbuser, $dbpass, $dbname);
if ($conn_p->connect_error) {
    die("Connection failed: " . $conn_p->connect_error);
}

// Fetch the active poll (should be exactly one)
$pollQ = $conn_p->query("SELECT id, question FROM poll WHERE active = 1 LIMIT 1");
if ($pollQ && $pollQ->num_rows === 1) {
    $poll = $pollQ->fetch_assoc();
    $poll_id = (int)$poll['id'];
    $question = $poll['question'];

    // Fetch all options & their votes for this poll
    $optStmt = $conn_p->prepare("SELECT id, option_text, votes FROM poll_option WHERE poll_id = ? ORDER BY id");
    $optStmt->bind_param("i", $poll_id);
    $optStmt->execute();
    $optRes = $optStmt->get_result();

    $options = [];
    $totalVotes = 0;
    while ($row = $optRes->fetch_assoc()) {
        $options[] = $row;
        $totalVotes += (int)$row['votes'];
    }
    $optStmt->close();
} else {
    // No active poll found
    $poll_id = 0;
    $options = [];
    $question = "";
    $totalVotes = 0;
}

$conn_p->close();
?>

<?php
// ──────────────────────────────────────────────────────────────────────────────
// 2A) CONNECT TO DATABASE (reuse your existing env‐var logic)
// ──────────────────────────────────────────────────────────────────────────────
$servername = getenv("DB_HOST");
$username   = getenv("DB_USER");
$password   = getenv("DB_PASSWORD");
$dbname     = getenv("DB_NAME");

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("DB Connection failed: " . $conn->connect_error);
}

// ──────────────────────────────────────────────────────────────────────────────
// 2B) LOG TOTAL DAILY VISIT (same as before)
// ──────────────────────────────────────────────────────────────────────────────
if (!isset($_GET['keepalive']) || $_GET['keepalive'] !== '1') {
    $today = date('Y-m-d');
    $sqlTotal = "
      INSERT INTO visitors (visit_date, count)
      VALUES (?, 1)
      ON DUPLICATE KEY UPDATE count = count + 1
    ";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param("s", $today);
    $stmtTotal->execute();
    $stmtTotal->close();
}

// ──────────────────────────────────────────────────────────────────────────────
// 2C) LOG UNIQUE DAILY VISIT IN visit_log
// ──────────────────────────────────────────────────────────────────────────────
// Get client IP (account for common proxies)
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // May contain a comma‐separated list; take the first
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$ip = getClientIP();
$sqlUnique = "
  INSERT IGNORE INTO visit_log (visit_date, ip_address)
  VALUES (?, ?)
";
$stmtUnique = $conn->prepare($sqlUnique);
$stmtUnique->bind_param("ss", $today, $ip);
$stmtUnique->execute();
$stmtUnique->close();

// ──────────────────────────────────────────────────────────────────────────────
// 2D) FETCH LAST 7 DAYS (TOTAL & UNIQUE) FOR CHART
// ──────────────────────────────────────────────────────────────────────────────
// Build PHP arrays of the last 7 dates
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-{$i} days"));
}

// Initialize PHP arrays with default 0 for total and unique
$totalCounts  = array_fill_keys($dates, 0);
$uniqueCounts = array_fill_keys($dates, 0);

// 2D-1) Query TOTAL counts (same as before)
$inClause = "'" . implode("','", $dates) . "'";
$sqlTotalQuery = "
  SELECT visit_date, count
  FROM visitors
  WHERE visit_date IN ($inClause)
";
$resTotal = $conn->query($sqlTotalQuery);
if ($resTotal) {
    while ($row = $resTotal->fetch_assoc()) {
        $totalCounts[$row['visit_date']] = (int)$row['count'];
    }
}

// 2D-2) Query UNIQUE counts (count distinct IPs per day)
$sqlUniqueQuery = "
  SELECT visit_date, COUNT(*) AS unique_count
  FROM visit_log
  WHERE visit_date IN ($inClause)
  GROUP BY visit_date
";
$resUnique = $conn->query($sqlUniqueQuery);
if ($resUnique) {
    while ($row = $resUnique->fetch_assoc()) {
        $uniqueCounts[$row['visit_date']] = (int)$row['unique_count'];
    }
}

$conn->close();

// Convert both PHP arrays to JSON for Chart.js
$labelsJSON      = json_encode(array_values($dates));        // e.g. ["2025-05-28",…]
$dataTotalJSON   = json_encode(array_values($totalCounts));   // e.g. [100, 85, 92, …]
$dataUniqueJSON  = json_encode(array_values($uniqueCounts));  // e.g. [80, 70, 75, …]
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
.read-more {
  display: inline-block;
  margin-top: 10px;
  padding: 8px 16px;
  background: #00f0ff;
  color: #0a0a0a;
  border-radius: 6px;
  text-decoration: none;
  font-family: 'Orbitron', monospace;
  font-weight: bold;
  box-shadow: 0 0 6px #00f0ff, 0 0 12px #00f0ff inset;
  transition: transform 0.2s ease;
}
.read-more:hover {
  transform: scale(1.05);
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
        alert("Thats a fake email address1!!!!1!");
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
 <div class="chart-container">
  
  <canvas id="visitorsChart"></canvas>
</div>
</div>
<script>
  // 3A) Grab PHP‐generated JSON arrays
  const labels       = <?php echo $labelsJSON; ?>;      // ["2025-05-28", …]
  const totalData    = <?php echo $dataTotalJSON; ?>;   // [100, 85, 92, …]
  const uniqueData   = <?php echo $dataUniqueJSON; ?>;  // [80, 70, 75, …]

  // 3B) Render Chart.js Bar Chart with two datasets
  const ctx = document.getElementById('visitorsChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Total Visits',
          data: totalData,
          backgroundColor: 'rgba(0, 255, 255, 0.6)',
          borderColor: 'rgba(0, 255, 255, 1)',
          borderWidth: 1
        },
        {
          label: 'Unique Visitors (IP)',
          data: uniqueData,
          backgroundColor: 'rgba(200, 0, 0, 0.6)',
          borderColor: 'rgb(200, 0, 0)',
          borderWidth: 1
        }
      ]
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
            color: '#00f0ff',
            stepSize: 1
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

  <h2 style="text-align:center; color:#00f0ff; margin-bottom:10px;">
Post a comment:
</h2>
  <div class="container">
    <form name="guest" method="POST" action="submit.php" onsubmit="return Validate()">
      <input type="text" name="name" placeholder="Your Name" required />
      <input type="email" name="email" placeholder="Your Email (an email will be sent to you afterwards)" required />
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
  // ─── 1) Determine whether we should show all comments or just the first 15 ─────
  $showAll = isset($_GET['show_all']) && $_GET['show_all'] === '1';

  // ─── 2) If NOT showing all, get the total count and then limit to 15 ─────────
  if (! $showAll) {
    // 2A) Count how many comments exist
    $countResult = $conn->query("SELECT COUNT(*) AS total FROM messages");
    $totalRow    = $countResult->fetch_assoc();
    $totalCount  = (int)$totalRow['total'];

    // 2B) Fetch only the latest 15
    $sql = "
      SELECT name, message, posted_on
      FROM messages
      ORDER BY posted_on DESC
      LIMIT 15
    ";
  }
  else {
    // ─── 3) If showing all, fetch every comment ───────────────────────────────
    $sql = "
      SELECT name, message, posted_on
      FROM messages
      ORDER BY posted_on DESC
    ";
  }

  $res = $conn->query($sql);

  // ─── 4) Loop through whichever rows we fetched and render them ───────────────
  if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
      echo '<div class="message">';
        echo '<div class="name">'     . htmlspecialchars($row['name'])      . '</div>';
        echo '<div class="posted_on">' . htmlspecialchars($row['posted_on']) . '</div>';
        echo '<div class="content">'   . nl2br(htmlspecialchars($row['message'])) . '</div>';
      echo '</div>';
    }
  } else {
    echo "<p>No comments yet. Be the first to post!</p>";
  }

  // ─── 5) If we showed only 15 but there are more, print “Read More” ───────────
  if (! $showAll && isset($totalCount) && $totalCount > 15) {
    echo '<div style="text-align:center; margin-top:15px;">';
      echo '<a href="index.php?show_all=1" style="
        color:#00f0ff;
        text-decoration:none;
        font-family:Orbitron, sans-serif;
        font-weight:bold;
      ">';
        echo 'Read More (' . ($totalCount - 15) . ' more)';
      echo '</a>';
    echo '</div>';
  }
?>

    </div>
  </div>
  <!-- ───── POLL SECTION ──────────────────────────────────────────────────────── -->
<?php if ($poll_id > 0): ?>
  <div class="poll-container" style="background:#111; padding:20px; margin:20px auto; max-width:600px; border-radius:8px; box-shadow:0 0 6px #00f0ff; color:#e0faff; font-family:'Orbitron', sans-serif;">
    <h2 style="margin-bottom:15px; color:#00f0ff;"><?php echo htmlspecialchars($question); ?></h2>

    <?php if (!isset($_GET['voted'])): ?>
      <!-- 4B) Show the voting form -->
      <form method="POST" action="vote.php">
    <style>
  .poll-option {
    display: none;
  }

  .poll-label {
    display: block;
    padding: 12px 16px;
    margin: 8px 0;
    background-color: #0e0e0e;
    border: 2px solid #00f0ff;
    border-radius: 8px;
    color: #e0faff;
    cursor: pointer;
    font-family: 'Orbitron', sans-serif;
    font-size: 1rem;
    text-align: center;
    transition: background 0.3s, transform 0.2s;
  }

  .poll-option:checked + .poll-label {
    background-color: #00f0ff;
    color: #0a0a0a;
    font-weight: bold;
    transform: scale(1.03);
  }
</style>

<?php foreach ($options as $opt): ?>
  <input type="radio"
         class="poll-option"
         name="option_id"
         value="<?php echo $opt['id']; ?>"
         id="opt<?php echo $opt['id']; ?>"
         required>
  <label for="opt<?php echo $opt['id']; ?>" class="poll-label">
    <?php echo htmlspecialchars($opt['option_text']); ?>
  </label>
<?php endforeach; ?>


        <button type="submit" style="
          margin-top:15px;
          padding:10px 20px;
          background:#00f0ff;
          color:#0a0a0a;
          border:none;
          border-radius:6px;
          font-family:'Orbitron', sans-serif;
          font-weight:bold;
          cursor:pointer;
          box-shadow:0 0 6px #00f0ff, 0 0 12px #00f0ff inset;
          transition:background 0.3s ease, transform 0.2s ease;
        ">Vote</button>
      </form>

    <?php else: ?>
      <!-- 4C) Show results if user just voted or saw results -->
      <div style="margin-top:10px;">
        <?php 
        // Prevent division by zero
        if ($totalVotes === 0) {
          echo "<p>No votes yet.</p>";
        } else {
          foreach ($options as $opt):
            $count = (int)$opt['votes'];
            $percent = round(($count / max(1, $totalVotes)) * 100);
        ?>
            <div style="margin-bottom:12px;">
              <div style="display:flex; justify-content:space-between; font-size:0.95rem;">
                <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                <span><?php echo $percent; ?>%</span>
              </div>
              <div style="background:#222; border-radius:4px; overflow:hidden; height:16px;">
                <div style="
                  width:<?php echo $percent; ?>%;
                  background:#00ffc8;
                  height:100%;
                "></div>
              </div>
            </div>
        <?php 
          endforeach;
        } 
        ?>
        <p style="margin-top:10px; font-size:0.9rem; color:#88ddee;">
          Total votes: <?php echo $totalVotes; ?>
        </p>
      </div>
    <?php endif; ?>

  </div>
<?php endif; ?>
<!-- ────────────────────────────────────────────────────────────────────────────── -->

  <div class="about-me">
  <img src="tmp_ed06b594-533b-4b2e-b8a2-325460be1b00.jpeg" alt="Picture of Me" class="profile-pic">
  <div class="about-text">
    <h2>About this site:</h2>
    <p>Hello! I made this site for fun. No SQL inject pls, I didn't have time to defend against it. Thanks and enjoy the website 🚩</p>
  </div>
</div>

</body>
</html>
