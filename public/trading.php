<?php /* include '../src/auth.php'; */?>

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
      <p>Browse available items for trading. (Layout only for now)</p>

      <section class="trading-grid">
        <!-- Example Item Card -->
        <div class="trading-card">
          <img src="https://placehold.co/600x400" alt="Item Image" />
          <div class="trading-details">
            <h2>Item Name</h2>
            <p>Owner: John Doe</p>
            <button class="request-btn">Request Trade</button>
          </div>
        </div>

        <div class="trading-card">
          <img src="https://placehold.co/600x400" alt="Item Image" />
          <div class="trading-details">
            <h2>Another Item</h2>
            <p>Owner: Jane Smith</p>
            <button class="request-btn">Request Trade</button>
          </div>
        </div>

        <div class="trading-card">
          <img src="https://placehold.co/600x400" alt="Item Image" />
          <div class="trading-details">
            <h2>Third Item</h2>
            <p>Owner: Alex Lee</p>
            <button class="request-btn">Request Trade</button>
          </div>
        </div>
      </section>
    </main>

    <!-- Popup Modal for Trading -->
  <div id="trading-popup" class="trading-popup" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; align-items: center; justify-content: center;">
      <div class="trading-popup-content">
        <span
          class="trading-popup-close"
          style="
            position: absolute;
            top: 10px;
            right: 18px;
            font-size: 2rem;
            cursor: pointer;
          "
          >&times;</span
        >
        <img id="trading-popup-img" src="" alt="Item Image" />
        <h2 id="trading-popup-title"></h2>
        <p id="trading-popup-owner"></p>
        <button
          id="trading-popup-action-btn"
          class="request-btn"
          style="display: none"
        >
          Request Trade
        </button>
      </div>
    </div>
    <script src="assets/js/trading.js?v=20251006"></script>
  </body>
</html>
