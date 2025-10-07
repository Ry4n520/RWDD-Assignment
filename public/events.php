

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251006">
  <link rel="stylesheet" href="assets/css/events.css?v=20251006">

  <!-- JS -->
  <script src="assets/js/navbar.js?v=20251006" defer></script>
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
        <img src="https://placehold.co/1000x400" alt="Event Image">
        <div class="event-details">
          <h2>Community Gathering</h2>
          <p>Date: 20th September 2025</p>
          <p>Location: Main Hall, APU</p>
          <button class="join-btn">Join Event</button>
  </div>
      </div>

      <div class="event-card">
        <img src="https://placehold.co/600x400" alt="Event Image">
        <div class="event-details">
          <h2>Trading Meetup</h2>
          <p>Date: 25th September 2025</p>
          <p>Location: Cafeteria Area</p>
          <button class="join-btn">Join Event</button>
        </div>
      </div>

      <div class="event-card">
        <img src="https://placehold.co/600x400" alt="Event Image">
        <div class="event-details">
          <h2>Workshop: Smart IoT</h2>
          <p>Date: 30th September 2025</p>
          <p>Location: Room 2.07</p>
          <button class="join-btn">Join Event</button>
        </div>
      </div>
      </section>
      <!-- Popup Modal -->
      <div id="event-popup" class="event-popup" style="display:none;">
        <div class="event-popup-content">
          <span class="event-popup-close">&times;</span>
          <img id="popup-img" src="" alt="Event Image">
          <h2 id="popup-title"></h2>
          <p id="popup-date"></p>
          <p id="popup-location"></p>
          <button id="popup-action-btn" class="join-btn">Join Event</button>
        </div>
      </div>
    </section>
  </main>
</body>
  <script src="assets/js/events.js"></script>
</html>
</html>
