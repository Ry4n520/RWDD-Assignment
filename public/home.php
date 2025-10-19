<?php /* include '../src/auth.php'; */
// Dynamic homepage: fetch latest trading item, event, and community post
session_start();
include __DIR__ . '/../src/db.php';

// helpers
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function excerpt($text, $len = 140) {
  $text = trim(strip_tags((string)$text));
  if (mb_strlen($text) <= $len) return $text;
  return mb_substr($text, 0, $len - 1) . '…';
}

$latestTrading = null;
if (isset($conn)) {
  $sql = "SELECT t.ItemID,
                 t.Name AS ItemName,
                 t.DateAdded,
                 a.username,
                 (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath
          FROM tradinglist t
          JOIN accounts a ON t.UserID = a.UserID
          ORDER BY t.DateAdded DESC
          LIMIT 1";
  if ($res = mysqli_query($conn, $sql)) {
    $latestTrading = mysqli_fetch_assoc($res) ?: null;
  }
}

$latestEvent = null;
if (isset($conn)) {
  $sql = "SELECT e.EventID,
                 e.Name AS EventName,
                 e.Address,
                 e.Date,
                 e.Organizer,
                 (SELECT COUNT(*) FROM eventparticipants epc WHERE epc.EventID = e.EventID) AS ParticipantCount
          FROM events e
          ORDER BY e.Date DESC
          LIMIT 1";
  if ($res = mysqli_query($conn, $sql)) {
    $latestEvent = mysqli_fetch_assoc($res) ?: null;
  }
}

$latestPost = null;
if (isset($conn)) {
  $sql = "SELECT p.PostID,
                 p.Content,
                 p.Title,
                 p.Created_at,
                 a.username,
                 (SELECT path FROM post_images pi WHERE pi.PostID = p.PostID ORDER BY pi.id ASC LIMIT 1) AS ImagePath
          FROM posts p
          JOIN accounts a ON p.UserID = a.UserID
          ORDER BY p.Created_at DESC
          LIMIT 1";
  if ($res = mysqli_query($conn, $sql)) {
    $latestPost = mysqli_fetch_assoc($res) ?: null;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>

  <!-- Navbar CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251020c">
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018">

  <!-- Home Page CSS -->
  <link rel="stylesheet" href="assets/css/home.css">

  <!-- JS -->
  <script src="assets/js/navbar.js?v=20251020" defer></script>
</head>
<body>

  <!-- Include Navbar -->
  <?php include 'includes/header.php'; ?>
  <!-- Main Content -->
  <main class="homepage">
    <section class="main-content">
      <h2>Main Content Area</h2>
      <p>This is your large content block. Replace with text, image, or a carousel.</p>
    </section>

    <section class="content-row">
      <!-- Latest Trading Item -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:8px">
        <h3 style="margin:0">Latest Trade</h3>
        <?php if ($latestTrading): ?>
          <a href="trading.php" style="text-decoration:none;color:inherit">
            <div style="display:flex;gap:12px;align-items:center">
              <img src="<?= h($latestTrading['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Item Image" style="width:120px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #00000022" />
              <div style="flex:1">
                <div style="font-weight:700;line-height:1.2;"><?= h($latestTrading['ItemName']) ?></div>
                <div style="opacity:.8;font-size:.9rem">by <?= h($latestTrading['username']) ?></div>
                <div style="opacity:.7;font-size:.85rem;">Added: <?= h($latestTrading['DateAdded']) ?></div>
              </div>
            </div>
          </a>
        <?php else: ?>
          <p>No trading items yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Event -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:8px">
        <h3 style="margin:0">Latest Event</h3>
        <?php if ($latestEvent): ?>
          <a href="events.php" style="text-decoration:none;color:inherit">
            <div style="display:flex;gap:12px;align-items:center">
              <img src="https://placehold.co/200x140" alt="Event Image" style="width:120px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #00000022" />
              <div style="flex:1">
                <div style="font-weight:700;line-height:1.2;"><?= h($latestEvent['EventName']) ?></div>
                <div style="opacity:.85;font-size:.9rem">Date: <?= h($latestEvent['Date']) ?></div>
                <div style="opacity:.75;font-size:.85rem;">Participants: <?= (int)($latestEvent['ParticipantCount'] ?? 0) ?></div>
                <div style="opacity:.7;font-size:.85rem;">Address: <?= h($latestEvent['Address']) ?></div>
              </div>
            </div>
          </a>
        <?php else: ?>
          <p>No events yet.</p>
        <?php endif; ?>
      </div>

      <!-- Latest Community Post -->
      <div class="content-box" style="display:flex;flex-direction:column;gap:8px">
        <h3 style="margin:0">Latest Community Post</h3>
        <?php if ($latestPost): ?>
          <a href="communities.php" style="text-decoration:none;color:inherit">
            <div style="display:flex;gap:12px;align-items:center">
              <img src="<?= h($latestPost['ImagePath'] ?: 'https://placehold.co/200x140') ?>" alt="Post Image" style="width:120px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #00000022" />
              <div style="flex:1">
                <div style="font-weight:700;line-height:1.2;"><?= h($latestPost['Title'] ?: 'Community Post') ?></div>
                <div style="opacity:.85;font-size:.9rem">by <?= h($latestPost['username']) ?></div>
                <div style="opacity:.8;font-size:.9rem"><?= h(excerpt($latestPost['Content'])) ?></div>
                <div style="opacity:.7;font-size:.85rem;">Posted: <?= h($latestPost['Created_at']) ?></div>
              </div>
            </div>
          </a>
        <?php else: ?>
          <p>No community posts yet.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

</body>
</html>
