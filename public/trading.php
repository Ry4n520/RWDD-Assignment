<?php
/* include '../src/auth.php'; */
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

// Fetch trading items with owner info and first image (if any)
// Hide items where current user has an accepted trade request
$sql = "SELECT t.ItemID, t.Name AS ItemName, t.Category, t.Description, t.DateAdded, t.UserID AS OwnerID, a.username,
    (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath,
    t.MeetupLocation,
    EXISTS(SELECT 1 FROM tradingrequests tr WHERE tr.ItemID = t.ItemID AND tr.SenderID = ? AND tr.Status = 'Pending') AS Requested
  FROM tradinglist t
  JOIN accounts a ON t.UserID = a.UserID
  WHERE NOT EXISTS(
    SELECT 1 FROM tradingrequests tr 
    WHERE tr.ItemID = t.ItemID 
    AND tr.SenderID = ? 
    AND tr.Status = 'Accepted'
  )
  ORDER BY t.DateAdded DESC";
$result = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
  mysqli_stmt_bind_param($stmt, 'ii', $currentUserId, $currentUserId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
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
    <link rel="stylesheet" href="assets/css/navbar.css" />
    <link rel="stylesheet" href="assets/css/theme.css" />
    <link rel="stylesheet" href="assets/css/trading.css" />
    <script src="assets/js/navbar.js?v=20251020" defer></script>
    <script src="assets/js/trading.js?v=20251020s" defer></script>
  </head>
  <body>
    <?php include 'includes/header.php'; ?>

    <main class="trading-page">
      <h1>Trading</h1>
      <p>Browse available items for trading.</p>
      
      <!-- Filter and action controls -->
      <div class="trading-controls">
        <div class="trading-filters">
          <button class="filter-btn" id="ascending-btn">
            <span>▲</span> Ascending
          </button>
          <button class="filter-btn active" id="latest-btn">Latest</button>
        </div>
        <div class="trading-actions">
          <button id="post-item-open" class="action-btn">Start a trade</button>
          <button id="view-my-trades" class="action-btn secondary">View current trades</button>
        </div>
      </div>

      <section class="trading-grid">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="trading-card" 
              data-itemid="<?= intval($row['ItemID']) ?>" 
              data-ownerid="<?= intval($row['OwnerID']) ?>" 
              data-ownername="<?= htmlspecialchars($row['username']) ?>" 
              data-description="<?= htmlspecialchars($row['Description'] ?? '') ?>" 
              data-category="<?= htmlspecialchars($row['Category'] ?? '') ?>" 
              data-meetuplocation="<?= htmlspecialchars($row['MeetupLocation'] ?? '') ?>" 
              data-dateadded="<?= htmlspecialchars($row['DateAdded']) ?>" 
              data-requested="<?= isset($row['Requested']) ? (int)$row['Requested'] : 0 ?>">
              <img src="<?= $row['ImagePath'] ? htmlspecialchars($row['ImagePath']) : 'https://placehold.co/600x400' ?>" alt="Item Image" />
              <div class="trading-details">
                <div class="trading-card-head">
                  <h2><?= htmlspecialchars($row['ItemName']) ?></h2>
                  <?php if ($currentUserId && ($isAdmin || $currentUserId == intval($row['OwnerID']))): ?>
                    <div class="trading-actions">
                      <button type="button" class="trading-edit-btn" data-itemid="<?= $row['ItemID'] ?>" aria-label="Edit item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                          <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                      </button>
                      <button type="button" class="trading-delete-btn" data-itemid="<?= $row['ItemID'] ?>" aria-label="Delete item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/>
                        </svg>
                      </button>
                    </div>
                  <?php endif; ?>
                </div>
                <p>Owner: <?= htmlspecialchars($row['username']) ?></p>
                <button class="request-btn" <?= !empty($row['Requested']) ? 'disabled' : '' ?>>
                  <?= !empty($row['Requested']) ? 'Request sent' : 'I want this' ?>
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
        <span class="trading-popup-close">&times;</span>
        
        <img id="trading-popup-img" src="" alt="Item Image" />
        <h2 id="trading-popup-title"></h2>
        <div class="popup-meta">
          <p id="trading-popup-owner"></p>
          <p id="trading-popup-time"></p>
        </div>
        <p id="trading-popup-content"></p>
        
        <button id="trading-popup-action-btn">Request</button>
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

  <!-- Edit Item modal -->
  <div id="edit-item-modal" class="trading-popup" style="display:none">
    <div class="trading-popup-content" style="max-width:560px">
      <span class="trading-popup-close" id="edit-item-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
      <h3>Edit Item</h3>
      <input id="edit-item-id" type="hidden" />
      <label for="edit-name">Title</label>
      <input id="edit-name" type="text" placeholder="Enter item title" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="edit-desc">Description</label>
      <textarea id="edit-desc" rows="3" placeholder="Describe your item" style="width:100%;padding:8px;margin:6px 0 12px"></textarea>
      <label for="edit-category">Category</label>
      <input id="edit-category" type="text" placeholder="e.g. Electronics" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="edit-location">Meetup Location</label>
      <select id="edit-location" style="width:100%;padding:8px;margin:6px 0 12px">
        <?php foreach ($meetupLocations as $loc): ?>
          <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
        <?php endforeach; unset($loc); ?>
      </select>
      <div id="edit-item-error" style="display:none;color:#b02a37;margin-bottom:8px"></div>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button id="edit-item-cancel" type="button">Cancel</button>
        <button id="edit-item-submit" class="request-btn" type="button">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- My Trades modal -->
  <div id="my-trades-modal" class="trading-popup" style="display:none">
    <div class="trading-popup-content" style="max-width:800px;max-height:80vh;overflow-y:auto">
      <span class="trading-popup-close" id="my-trades-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
      <h3 style="margin-bottom:15px">My Trade Requests</h3>
      <div id="my-trades-list" style="display:flex;flex-direction:column;gap:12px">
        <!-- Dynamically populated via JS -->
      </div>
    </div>
  </div>

  </body>
</html>
