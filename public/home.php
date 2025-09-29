<?php /* include '../src/auth.php'; */?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>

  <!-- Navbar CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css">

  <!-- Home Page CSS -->
  <link rel="stylesheet" href="assets/css/home.css">

  <!-- JS -->
  <script src="assets/js/navbar.js" defer></script>
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
      <div class="content-box">Content 1</div>
      <div class="content-box">Content 2</div>
      <div class="content-box">Content 3</div>
    </section>
  </main>

</body>
</html>
