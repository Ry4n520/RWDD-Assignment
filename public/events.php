<?php
session_start();
include __DIR__ . '/../src/db.php';

// Fetch events with community info
$sql = "SELECT e.EventID, e.Name AS EventName, e.Address, e.Date, e.OrganizerID,
               c.Name AS CommunityName
        FROM events e
        JOIN community c ON e.CommunityID = c.CommunityID
        ORDER BY e.Date ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Events</title>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251008" />
  <link rel="stylesheet" href="assets/css/events.css?v=20251009" />
  <script src="assets/js/navbar.js?v=20251008" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="events-page">
    <h1>Community Events</h1>
    <p>Discover upcoming events in your communities.</p>

    <section class="events-grid">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <div class="event-card"
               data-img="https://placehold.co/600x400"
               data-title="<?= htmlspecialchars($row['EventName']) ?>"
               data-community="<?= htmlspecialchars($row['CommunityName']) ?>"
               data-organizer="Organizer #<?= htmlspecialchars($row['OrganizerID']) ?>"
               data-address="<?= htmlspecialchars($row['Address']) ?>"
               data-date="<?= htmlspecialchars($row['Date']) ?>">
            <img src="https://placehold.co/600x400" alt="Event Image" />
            <div class="event-details">
              <h2><?= htmlspecialchars($row['EventName']) ?></h2>
              <p><strong>Date:</strong> <?= htmlspecialchars($row['Date']) ?></p>
              <p><strong>Address:</strong> <?= htmlspecialchars($row['Address']) ?></p>
              <p><strong>Community:</strong> <?= htmlspecialchars($row['CommunityName']) ?></p>
              <button class="join-btn">Join Event</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No events found.</p>
      <?php endif; ?>
    </section>
  </main>

  <!-- Popup Modal for Events -->
  <div id="event-popup" class="event-popup" style="display: none;">
    <div class="event-popup-content">
      <span class="event-popup-close">&times;</span>
      <img id="event-popup-img" src="" alt="Event Image" />
      <h2 id="event-popup-title"></h2>
      <p id="event-popup-community"></p>
      <p id="event-popup-organizer"></p>
      <p id="event-popup-address"></p>
      <p id="event-popup-date"></p>
      <button id="event-popup-action-btn" class="join-btn" style="display: none;">
        Join Event
      </button>
    </div>
  </div>

  <script src="assets/js/events.js?v=20251008"></script>
</body>
</html>
