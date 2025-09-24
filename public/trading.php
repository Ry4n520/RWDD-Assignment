<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trading</title>
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/trading.css">
  <script src="assets/js/navbar.js" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="trading-page">
    <h1>Trading</h1>
    <p>Browse available items for trading. (Layout only for now)</p>

    <section class="trading-grid">
      <!-- Example Item Card -->
      <div class="trading-card">
        <img src="https://via.placeholder.com/200x150" alt="Item Image">
        <h2>Item Name</h2>
        <p>Owner: John Doe</p>
        <button class="request-btn">Request Trade</button>
      </div>

      <div class="trading-card">
        <img src="https://via.placeholder.com/200x150" alt="Item Image">
        <h2>Another Item</h2>
        <p>Owner: Jane Smith</p>
        <button class="request-btn">Request Trade</button>
      </div>

      <div class="trading-card">
        <img src="https://via.placeholder.com/200x150" alt="Item Image">
        <h2>Third Item</h2>
        <p>Owner: Alex Lee</p>
        <button class="request-btn">Request Trade</button>
      </div>
    </section>
  </main>

</body>
</html>
