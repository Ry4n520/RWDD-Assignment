<?php
session_start();
include __DIR__ . '/../src/db.php';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
$currentUserId = $currentUserId ? (int)$currentUserId : 0;

$result = null;
if ($conn) {
  $sql = "SELECT e.EventID,
                 e.Name AS EventName,
                 e.Address,
                 e.Date,
                 e.Organizer,
                 (SELECT COUNT(*) FROM eventparticipants epc WHERE epc.EventID = e.EventID) AS ParticipantCount,
                 EXISTS(SELECT 1 FROM eventparticipants ep WHERE ep.EventID = e.EventID AND ep.UserID = ?) AS Joined
          FROM events e
          ORDER BY e.Date ASC";
  if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Events</title>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251008" />
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018" />
  <link rel="stylesheet" href="assets/css/events.css?v=20251017" />
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
      data-event-id="<?= (int)$row['EventID'] ?>"
      data-img="https://placehold.co/600x400"
      data-title="<?= htmlspecialchars($row['EventName']) ?>"
      data-organizer="<?= htmlspecialchars($row['Organizer']) ?>"
      data-address="<?= htmlspecialchars($row['Address']) ?>"
      data-date="<?= htmlspecialchars($row['Date']) ?>"
      data-joined="<?= isset($row['Joined']) ? (int)$row['Joined'] : 0 ?>"
      data-participants="<?= isset($row['ParticipantCount']) ? (int)$row['ParticipantCount'] : 0 ?>">
            <img src="https://placehold.co/600x400" alt="Event Image" />
            <div class="event-details">
              <h2><?= htmlspecialchars($row['EventName']) ?></h2>
              <p><strong>Date:</strong> <?= htmlspecialchars($row['Date']) ?></p>
              <p><strong>Address:</strong> <?= htmlspecialchars($row['Address']) ?></p>
              <p><strong>Participants:</strong> <span class="participants-count"><?= isset($row['ParticipantCount']) ? (int)$row['ParticipantCount'] : 0 ?></span></p>
              <!-- Community information removed (schema no longer has community table) -->
              <?php $joined = !empty($row['Joined']); ?>
              <button class="join-btn<?= $joined ? ' leave' : '' ?>">
                <?= $joined ? 'Leave Event' : 'Join Event' ?>
              </button>
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
      <p id="event-popup-participants"><strong>Participants:</strong> <span id="event-popup-participants-count"></span></p>
      <button id="event-popup-action-btn" class="join-btn" style="display: none;">
        Join Event
      </button>
    </div>
  </div>

  <script src="assets/js/events.js?v=20251017"></script>
</body>
</html>
