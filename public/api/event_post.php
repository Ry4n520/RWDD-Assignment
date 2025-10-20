<?php
// Create a new event
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();
require_once __DIR__ . '/../../src/db.php';

// Check authentication
$userId = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$date = trim($_POST['date'] ?? '');
$organizer = trim($_POST['organizer'] ?? '');

// Debug: Log what we're receiving
error_log("Event Post - Name: $name, Address: $address, Date: $date, Organizer: $organizer");

if ($name === '' || $address === '' || $date === '' || $organizer === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'All fields are required']);
  exit;
}

if (!isset($conn)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'DB not available']);
  exit;
}

// Insert event (no UserID needed since only admins can create events)
$sql = "INSERT INTO events (Name, Address, Date, Organizer) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssss', $name, $address, $date, $organizer);

// Debug: Log the SQL and values
error_log("SQL: $sql");
error_log("Values - Name: '$name', Address: '$address', Date: '$date', Organizer: '$organizer'");

$ok = mysqli_stmt_execute($stmt);

// Debug: Check for SQL errors
if (!$ok) {
  error_log("SQL Error: " . mysqli_error($conn));
}

if ($ok) {
    $id = mysqli_insert_id($conn);
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
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Insert failed']);
}
mysqli_stmt_close($stmt);
?>
