<?php
include '../src/auth.php';
include __DIR__ . '/../src/db.php';

$currentUserId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
$currentUserId = $currentUserId ? (int)$currentUserId : 0;

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

$result = null;
if ($conn) {
  $sql = "SELECT e.EventID,
                 e.Name AS EventName,
                 e.Address,
                 e.Date,
                 e.Organizer,
       (SELECT ei.path FROM event_images ei WHERE ei.EventID = e.EventID ORDER BY ei.id ASC LIMIT 1) AS ImagePath,
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
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251020c" />
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018" />
  <link rel="stylesheet" href="assets/css/events.css?v=20251020g" />
  <script src="assets/js/navbar.js?v=20251020" defer></script>
  <script src="assets/js/events.js?v=20251020i" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="events-page">
    <h1>Community Events</h1>
    <p>Discover upcoming events in your communities.</p>
    
      <!-- Admin: Post Event trigger (admin only) -->
      <?php if ($isAdmin): ?>
        <div style="margin:12px 0 20px; display:flex; justify-content:flex-end">
          <button id="event-post-open" class="join-btn">Post an event</button>
        </div>
      <?php endif; ?>

    <section class="events-grid">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <div class="event-card"
      data-event-id="<?= (int)$row['EventID'] ?>"
      data-creator-id="<?= isset($row['CreatorID']) ? (int)$row['CreatorID'] : 0 ?>"
      data-img="<?= !empty($row['ImagePath']) ? htmlspecialchars($row['ImagePath']) : 'https://placehold.co/600x400' ?>"
      data-title="<?= htmlspecialchars($row['EventName']) ?>"
      data-organizer="<?= htmlspecialchars($row['Organizer']) ?>"
      data-address="<?= htmlspecialchars($row['Address']) ?>"
      data-date="<?= htmlspecialchars($row['Date']) ?>"
      data-joined="<?= isset($row['Joined']) ? (int)$row['Joined'] : 0 ?>"
      data-participants="<?= isset($row['ParticipantCount']) ? (int)$row['ParticipantCount'] : 0 ?>">
            <img src="<?= !empty($row['ImagePath']) ? htmlspecialchars($row['ImagePath']) : 'https://placehold.co/600x400' ?>" alt="Event Image" />
            <div class="event-details">
              <div class="event-card-head">
                <h2><?= htmlspecialchars($row['EventName']) ?></h2>
                <?php if ($isAdmin): ?>
                  <div class="event-actions">
                    <button type="button" class="event-edit-btn" data-eventid="<?= $row['EventID'] ?>" aria-label="Edit event">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button type="button" class="event-delete-btn" data-eventid="<?= $row['EventID'] ?>" aria-label="Delete event">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/>
                      </svg>
                    </button>
                  </div>
                <?php endif; ?>
              </div>
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

  <!-- Delete confirmation modal -->
  <div id="delete-modal" class="event-popup" style="display:none">
    <div class="event-popup-content" style="max-width:420px">
      <span class="event-popup-close" id="delete-cancel" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
      <h3 style="margin-bottom:6px">Delete this event?</h3>
      <p id="delete-modal-title" style="color:#444;margin-bottom:16px"></p>
      <div id="delete-modal-error" style="display:none;color:#b02a37;margin-bottom:8px"></div>
      <div style="margin-top:8px;display:flex;gap:8px;justify-content:flex-end">
        <button id="delete-cancel-2" type="button">Cancel</button>
        <button id="delete-confirm" class="join-btn leave" type="button">Delete</button>
      </div>
    </div>
  </div>

  <!-- Post Event modal -->
  <div id="post-event-modal" class="event-popup" style="display:none">
    <div class="event-popup-content" style="max-width:560px">
      <span class="event-popup-close" id="event-post-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
      <h3>Post an event</h3>
      <label for="post-event-name">Event name</label>
      <input id="post-event-name" type="text" placeholder="Enter event name" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="post-event-address">Address</label>
      <input id="post-event-address" type="text" placeholder="Enter address/location" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="post-event-date">Date</label>
      <input id="post-event-date" type="date" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="post-event-organizer">Organizer</label>
      <input id="post-event-organizer" type="text" placeholder="Organizer name" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="post-event-images">Images</label>
      <input id="post-event-images" type="file" accept="image/*" multiple style="width:100%;padding:8px;margin:6px 0 12px;background:#f6f6f6;border-radius:6px" />
      <div id="post-event-images-preview" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px"></div>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button id="event-post-cancel" type="button">Cancel</button>
        <button id="event-post-submit" class="join-btn" type="button">Publish</button>
      </div>
    </div>
  </div>

  <!-- Edit Event modal -->
  <div id="edit-event-modal" class="event-popup" style="display:none">
    <div class="event-popup-content" style="max-width:560px">
      <span class="event-popup-close" id="event-edit-close" style="position:absolute;top:10px;right:18px;font-size:2rem;cursor:pointer">&times;</span>
      <h3>Edit Event</h3>
      <input id="edit-event-id" type="hidden" />
      <label for="edit-event-name">Event name</label>
      <input id="edit-event-name" type="text" placeholder="Enter event name" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="edit-event-address">Address</label>
      <input id="edit-event-address" type="text" placeholder="Enter address/location" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="edit-event-date">Date</label>
      <input id="edit-event-date" type="date" style="width:100%;padding:8px;margin:6px 0 12px" />
      <label for="edit-event-organizer">Organizer</label>
      <input id="edit-event-organizer" type="text" placeholder="Organizer name" style="width:100%;padding:8px;margin:6px 0 12px" />
      <div id="edit-event-error" style="display:none;color:#b02a37;margin-bottom:8px"></div>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button id="event-edit-cancel" type="button">Cancel</button>
        <button id="event-edit-submit" class="join-btn" type="button">Save Changes</button>
      </div>
    </div>
  </div>

  <script src="assets/js/events.js?v=20251020i"></script>
</body>
</html>
