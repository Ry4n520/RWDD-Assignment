<?php /* include '../src/auth.php';  */?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/events.css">

  <!-- JS -->
  <script src="assets/js/navbar.js" defer></script>
</head>
<body>
  <!-- Navbar -->
  <?php include 'includes/header.php'; ?>

  <!-- Main Content -->
  <main class="events-page">
    <h1>Events</h1>
    <p>Discover upcoming events. (Layout only for now)</p>

    <section class="events-list">
      <!-- Example Event Card -->
      <div class="event-card">
        <img src="https://via.placeholder.com/300x180" alt="Event Image">
        <div class="event-details">
          <h2>Community Gathering</h2>
          <p>Date: 20th September 2025</p>
          <p>Location: Main Hall, APU</p>
          <button class="join-btn">Join Event</button>
        </div>
      </div>

      <div class="event-card">
        <img src="https://via.placeholder.com/300x180" alt="Event Image">
        <div class="event-details">
          <h2>Trading Meetup</h2>
          <p>Date: 25th September 2025</p>
          <p>Location: Cafeteria Area</p>
          <button class="join-btn">Join Event</button>
        </div>
      </div>

      <div class="event-card">
        <img src="https://via.placeholder.com/300x180" alt="Event Image">
        <div class="event-details">
          <h2>Workshop: Smart IoT</h2>
          <p>Date: 30th September 2025</p>
          <p>Location: Room 2.07</p>
          <button class="join-btn">Join Event</button>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
