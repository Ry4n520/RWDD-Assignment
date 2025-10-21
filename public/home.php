<?php /* include '../src/auth.php'; */
// Dynamic homepage: fetch latest trading item, event, and community post
include __DIR__ . '/../src/db.php';

$currentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['UserID'] ?? 0);

// Check if user is admin
$isAdmin = false;
if ($currentUserId) {
  $r = mysqli_query($conn, "SELECT usertype FROM accounts WHERE UserID = " . $currentUserId . " LIMIT 1");
  if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (isset($row['usertype']) && strtolower($row['usertype']) === 'admin') {
      $isAdmin = true;
    }
  }
}

// helpers
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function excerpt($text, $len = 140) {
  $text = trim(strip_tags((string)$text));
  if (mb_strlen($text) <= $len) return $text;
  return mb_substr($text, 0, $len - 1) . '…';
}

// Helper function to fetch data
function fetchData($conn, $sql) {
  $result = [];
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $result[] = $row;
    }
  }
  return $result;
}

// Admin view: fetch all accounts
$allAccounts = [];
if ($isAdmin) {
  $sql = "SELECT UserID, username, email, usertype, datejoined FROM accounts ORDER BY datejoined DESC";
  $allAccounts = fetchData($conn, $sql);
}

$latestTrading = [];
$sql = "SELECT t.ItemID,
               t.Name AS ItemName,
               t.DateAdded,
               a.username,
               (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath
        FROM tradinglist t
        JOIN accounts a ON t.UserID = a.UserID
        ORDER BY t.DateAdded DESC
        LIMIT 3";
$latestTrading = fetchData($conn, $sql);

$latestEvent = [];
$sql = "SELECT e.EventID,
               e.Name AS EventName,
               e.Address,
               e.Date,
               e.Organizer,
               (SELECT COUNT(*) FROM eventparticipants epc WHERE epc.EventID = e.EventID) AS ParticipantCount
        FROM events e
        ORDER BY e.Date DESC
        LIMIT 3";
$latestEvent = fetchData($conn, $sql);

$latestPost = [];
$sql = "SELECT p.PostID,
               p.Content,
               p.Created_at,
               a.username,
               (SELECT path FROM post_images pi WHERE pi.PostID = p.PostID ORDER BY pi.id ASC LIMIT 1) AS ImagePath
        FROM posts p
        JOIN accounts a ON p.UserID = a.UserID
        ORDER BY p.Created_at DESC
        LIMIT 3";
$latestPost = fetchData($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>

  <!-- Navbar CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/theme.css">

  <!-- Home Page CSS -->
  <link rel="stylesheet" href="assets/css/home.css">

  <!-- JS -->
  <script src="assets/js/navbar.js?" defer></script>
  <script src="assets/js/home.js?" defer></script>
</head>
<body>

  <!-- Include Navbar -->
  <?php include 'includes/header.php'; ?>
  
  <!-- Main Content -->
  <main class="homepage">
    <?php if ($isAdmin): ?>
      <!-- Admin View: Account Management -->
      <section class="admin-content">
        <h1>Account Management</h1>
        <p class="admin-subtitle">Manage user accounts and admin privileges. Toggle the checkbox to promote or demote users.</p>
        
        <div class="table-wrapper">
          <table class="accounts-table">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Date Joined</th>
                <th>Admin</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($allAccounts)): ?>
                <?php foreach ($allAccounts as $account): ?>
                  <tr>
                    <td><?= h($account['UserID']) ?></td>
                    <td class="username"><?= h($account['username']) ?></td>
                    <td><?= h($account['email']) ?></td>
                    <td><?= h($account['datejoined'] ?? 'N/A') ?></td>
                    <td class="text-center">
                      <input type="checkbox" 
                             class="admin-toggle" 
                             data-userid="<?= h($account['UserID']) ?>" 
                             <?= (strtolower($account['usertype'] ?? '') === 'admin') ? 'checked' : '' ?>>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center empty">No accounts found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php else: ?>
      <!-- Regular User View -->
      <section class="main-content">
        <h1>🏡 Welcome to LinkMosaic</h1>
        <p class="subtitle">Connecting Neighbors. Building Communities.</p>
        
        <p>LinkMosaic is a community-based platform designed to bring residents together in a shared digital space. Our mission is to strengthen neighborhood connections by enabling communication, collaboration, and support among community members.</p>
        
        <p class="section-title">Through LinkMosaic, residents can:</p>
        
        <div class="features-list">
          <p>🌱 <strong>Share Experiences & Ideas</strong> – Post updates, share advice, and exchange tips with your neighbors.</p>
          
          <p>🎉 <strong>Join Events</strong> – Participate in community activities such as clean-up drives, local gatherings, and cultural celebrations.</p>
          
          <p>🔄 <strong>Trade & Donate Items</strong> – Give unused items a new life by trading or donating them to others in your neighborhood.</p>
          
          <p>📢 <strong>Stay Informed</strong> – Access the latest community announcements, safety alerts, and local updates all in one place.</p>
        </div>
        
        <p class="closing">At its heart, LinkMosaic promotes <strong>sustainable living</strong> and <strong>mutual support</strong>, encouraging eco-friendly practices and active participation. Whether you're new to the area or a long-time resident, this platform helps you connect, contribute, and create a more vibrant, supportive neighborhood.</p>
      </section>

      <section class="content-row">
      <!-- Latest Trading Items -->
      <div class="content-box">
        <h3>Latest Trades</h3>
        <?php if (!empty($latestTrading)): ?>
          <?php foreach ($latestTrading as $trading): ?>
            <a href="trading.php" class="content-item">
              <div class="item-wrapper">
                <img src="<?= h($trading['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Item Image" class="item-image" />
                <div class="item-details">
                  <div class="item-title"><?= h($trading['ItemName']) ?></div>
                  <div class="item-author">by <?= h($trading['username']) ?></div>
                  <div class="item-date">Added: <?= h($trading['DateAdded']) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No trading items yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Events -->
      <div class="content-box">
        <h3>Latest Events</h3>
        <?php if (!empty($latestEvent)): ?>
          <?php foreach ($latestEvent as $event): ?>
            <a href="events.php" class="content-item">
              <div class="item-wrapper">
                <img src="https://placehold.co/200x140" alt="Event Image" class="item-image" />
                <div class="item-details">
                  <div class="item-title"><?= h($event['EventName']) ?></div>
                  <div class="item-info">Date: <?= h($event['Date']) ?></div>
                  <div class="item-meta">Participants: <?= (int)($event['ParticipantCount'] ?? 0) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No events yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Community Posts -->
      <div class="content-box">
        <h3>Latest Community Posts</h3>
        <?php if (!empty($latestPost)): ?>
          <?php foreach ($latestPost as $post): ?>
            <a href="communities.php" class="content-item">
              <div class="item-wrapper">
                <img src="<?= h($post['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Post Image" class="item-image" />
                <div class="item-details">
                  <div class="item-title"><?= h(excerpt($post['Content'], 50)) ?></div>
                  <div class="item-author">by <?= h($post['username']) ?></div>
                  <div class="item-excerpt"><?= h(excerpt($post['Content'], 80)) ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No community posts yet.</p>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>
  </main>

</body>
</html>
