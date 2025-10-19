<?php
session_start();
include __DIR__ . '/../src/db.php';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
$currentUserId = $currentUserId ? (int)$currentUserId : 0;

// Fetch trading items with owner info and first image (if any)
$sql = "SELECT t.ItemID, t.Name AS ItemName, t.Category, t.DateAdded, t.UserID AS OwnerID, a.username,
    (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath,
    t.MeetupLocation,
    EXISTS(SELECT 1 FROM tradingrequests tr WHERE tr.ItemID = t.ItemID AND tr.SenderID = ? AND tr.Status = 'Pending') AS Requested
  FROM tradinglist t
  JOIN accounts a ON t.UserID = a.UserID
  ORDER BY t.DateAdded DESC";
$result = null;
if (isset($conn)) {
  if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
  }
}

// Preset meetup locations (replace with DB table later if needed)
$meetupLocations = ['Community Center', 'City Park', 'Main Street Cafe', 'Library Lobby'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Trading</title>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251006" />
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018" />
    <link rel="stylesheet" href="assets/css/trading.css?v=20251006" />
    <script src="assets/js/navbar.js?v=20251006" defer></script>
  </head>
  <body>
    <?php include 'includes/header.php'; ?>

    <main class="trading-page">
      <h1>Trading</h1>
      <p>Browse available items for trading.</p>
      <div style="margin:12px 0 20px; display:flex; justify-content:flex-end">
        <button id="post-item-open" class="request-btn">Post an item</button>
      </div>

      <section class="trading-grid">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="trading-card" data-itemid="<?= intval($row['ItemID']) ?>" data-ownerid="<?= intval($row['OwnerID']) ?>" data-requested="<?= isset($row['Requested']) ? (int)$row['Requested'] : 0 ?>">
              <img src="<?= $row['ImagePath'] ? htmlspecialchars($row['ImagePath']) : 'https://placehold.co/600x400' ?>" alt="Item Image" />
              <div class="trading-details">
                <h2><?= htmlspecialchars($row['ItemName']) ?></h2>
                <p>Owner: <?= htmlspecialchars($row['username']) ?></p>
                <?php $requested = !empty($row['Requested']); ?>
                <button class="request-btn" <?= $requested ? 'disabled' : '' ?>>
                  <?= $requested ? 'Request sent' : 'I want this' ?>
                </button>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No items available for trade.</p>
        <?php endif; ?>
      </section>
    </main>

  <!-- View popup -->
    <div id="trading-popup" class="trading-popup">
      <div class="trading-popup-content">
        <span class="trading-popup-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
        <img id="trading-popup-img" src="" alt="Item Image" />
        <h2 id="trading-popup-title"></h2>
        <p id="trading-popup-owner"></p>
        <button id="trading-popup-action-btn" class="request-btn">I want this</button>
      </div>
    </div>

  <!-- Post Item modal -->
    <div id="post-item-modal" class="trading-popup" style="display:none">
      <div class="trading-popup-content" style="max-width:560px">
        <span class="trading-popup-close" id="post-item-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
        <h3>Post an item</h3>
        <label for="post-name">Title</label>
        <input id="post-name" type="text" placeholder="Enter item title" style="width:100%;padding:8px;margin:6px 0 12px" />
        <label for="post-desc">Description</label>
        <textarea id="post-desc" rows="3" placeholder="Describe your item" style="width:100%;padding:8px;margin:6px 0 12px"></textarea>
        <label for="post-category">Category</label>
        <input id="post-category" type="text" placeholder="e.g. Electronics" style="width:100%;padding:8px;margin:6px 0 12px" />
        <label for="post-location">Meetup Location</label>
        <select id="post-location" style="width:100%;padding:8px;margin:6px 0 12px">
          <?php foreach ($meetupLocations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
          <?php endforeach; unset($loc); ?>
        </select>
        <label for="post-images">Images</label>
        <input id="post-images" type="file" accept="image/*" multiple style="width:100%;padding:8px;margin:6px 0 12px;background:#f6f6f6;border-radius:6px" />
        <div id="post-images-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px"></div>
        <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
          <button id="post-item-cancel" type="button">Cancel</button>
          <button id="post-item-submit" class="request-btn" type="button">Publish</button>
        </div>
      </div>
  </div>

    <script src="assets/js/trading.js?v=20251006"></script>
  </body>
</html>
