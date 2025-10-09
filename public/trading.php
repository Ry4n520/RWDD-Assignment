<?php
session_start();
include __DIR__ . '/../src/db.php';

// Fetch trading items with owner info
$sql = "SELECT t.ItemID, t.Name AS ItemName, t.Category, t.DateAdded, a.username
        FROM TradingList t
        JOIN Accounts a ON t.UserID = a.UserID
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

    <section class="trading-grid">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <div class="trading-card">
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
  <div id="trading-popup" class="trading-popup" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; align-items: center; justify-content: center;">
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

  <script src="assets/js/trading.js?v=20251006"></script>
</body>
</html>
