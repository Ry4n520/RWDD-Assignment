<?php
session_start();
include __DIR__ . '/../src/db.php';

// Fetch trading items with owner info and first image (if any)
$sql = "SELECT t.ItemID, t.Name AS ItemName, t.Category, t.DateAdded, t.UserID AS OwnerID, a.username,
               (SELECT path FROM trading_images ti WHERE ti.ItemID = t.ItemID ORDER BY ti.id ASC LIMIT 1) AS ImagePath
        FROM tradinglist t
        JOIN accounts a ON t.UserID = a.UserID
        ORDER BY t.DateAdded DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trading</title>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251006" />
  <link rel="stylesheet" href="assets/css/trading.css?v=20251006" />
  <script src="assets/js/navbar.js?v=20251006" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="trading-page">
    <h1>Trading</h1>
    <p>Browse available items for trading.</p>

    <!-- Post Trade feature removed -->

    <section class="trading-grid">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <div class="trading-card" data-itemid="<?= intval($row['ItemID']) ?>" data-ownerid="<?= intval($row['OwnerID']) ?>">
            <!-- Replace with actual image column if you add it later -->
            <img src="https://placehold.co/600x400" alt="Item Image" />
            <div class="trading-details">
              <h2><?= htmlspecialchars($row['ItemName']) ?></h2>
              <p><strong>Category:</strong> <?= htmlspecialchars($row['Category']) ?></p>
              <p><strong>Owner:</strong> <?= htmlspecialchars($row['username']) ?></p>
              <p><strong>Date Added:</strong> <?= htmlspecialchars($row['DateAdded']) ?></p>
              <button class="request-btn">Request Trade</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No items available for trade.</p>
      <?php endif; ?>
    </section>
  </main>

  <!-- Popup Modal for Trading -->
  <div id="trading-popup" class="trading-popup">
    <div class="trading-popup-content">
      <span class="trading-popup-close">&times;</span>
      <img id="trading-popup-img" src="" alt="Item Image" />
      <h2 id="trading-popup-title"></h2>
      <p id="trading-popup-owner"></p>
      <p id="trading-popup-category"></p>
      <p id="trading-popup-date"></p>
      <button id="trading-popup-action-btn" class="request-btn" style="display: none">
        Request Trade
      </button>
    </div>
  </div>

  <!-- Request Trade Modal (choose your offered item) -->
  <div id="requestTradeModal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-panel" role="dialog" aria-modal="true" style="max-width:700px;">
      <button id="requestTradeClose" style="position:absolute;right:16px;top:16px;">&times;</button>
      <h2>Select an item to offer</h2>
      <div id="myItemsList" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px;"></div>
    </div>
  </div>

  <script src="assets/js/trading.js?v=20251006"></script>
</body>
</html>
