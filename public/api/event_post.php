<?php
// Create a new event
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/api_helpers.php';

// Only admins can create events
requireAdmin($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$date = trim($_POST['date'] ?? '');
$organizer = trim($_POST['organizer'] ?? '');

if ($name === '' || $address === '' || $date === '' || $organizer === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'All fields are required']);
}

// Insert event
$sql = "INSERT INTO events (Name, Address, Date, Organizer) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssss', $name, $address, $date, $organizer);
$ok = mysqli_stmt_execute($stmt);

if ($ok) {
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    $saved = [];
    // Ensure we have an event_images table like trading uses for images
    // Create if missing (safe for dev/local; in production this should be a migration)
    try {
      $tblRes = @mysqli_query($conn, "SHOW TABLES LIKE 'event_images'");
      if (!$tblRes || mysqli_num_rows($tblRes) === 0) {
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS event_images (
          id INT NOT NULL AUTO_INCREMENT,
          EventID INT NOT NULL,
          path VARCHAR(255) NOT NULL,
          PRIMARY KEY (id),
          KEY idx_event_images_eventid (EventID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      }
    } catch (Throwable $e) {
      // ignore
    }

    // Handle images[] upload if provided
    if (!empty($_FILES['images']) && isset($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
      $uploadDir = __DIR__ . '/../../public/uploads/events';
      // In case path differs, resolve to site root public/uploads/events
      $uploadDir = realpath(__DIR__ . '/..' . '/../uploads') ? (realpath(__DIR__ . '/..' . '/../uploads') . DIRECTORY_SEPARATOR . 'events') : $uploadDir;
      if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
      }
      $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
      $count = count($_FILES['images']['name']);
      for ($i = 0; $i < $count; $i++) {
        $error = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) continue;
        $tmp = $_FILES['images']['tmp_name'][$i] ?? '';
        $type = mime_content_type($tmp);
        if (!$tmp || !isset($allowed[$type])) continue;
        $ext = $allowed[$type];
        $base = bin2hex(random_bytes(8)) . '_' . time();
        $filename = $base . '.' . $ext;
        $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (@move_uploaded_file($tmp, $dest)) {
          // Store relative public path (forward slashes)
          $rel = 'uploads/events/' . $filename;
          $saved[] = $rel;
          // Insert into event_images (table ensured above)
          try {
            if ($ins = @mysqli_prepare($conn, 'INSERT INTO event_images (EventID, path) VALUES (?, ?)')) {
              @mysqli_stmt_bind_param($ins, 'is', $id, $rel);
              @mysqli_stmt_execute($ins);
              @mysqli_stmt_close($ins);
            }
          } catch (Throwable $e) {
            // ignore if insert fails
          }
      }
    }
  }

  echo json_encode(['ok' => true, 'id' => (int)$id, 'images' => $saved]);
} else {
  jsonResponse(500, ['ok' => false, 'error' => 'Insert failed']);
}

