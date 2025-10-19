<?php
// Create a new event
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();
require_once __DIR__ . '/../../src/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$date = trim($_POST['date'] ?? '');
$organizer = trim($_POST['organizer'] ?? '');

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

$sql = "INSERT INTO events (Name, Address, Date, Organizer) VALUES (?, ?, ?, ?)";
if ($stmt = mysqli_prepare($conn, $sql)) {
  mysqli_stmt_bind_param($stmt, 'ssss', $name, $address, $date, $organizer);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok) {
    $id = mysqli_insert_id($conn);
    $saved = [];
    // Check if optional event_images table exists
    $hasEventImagesTable = false;
    try {
      $tblRes = @mysqli_query($conn, "SHOW TABLES LIKE 'event_images'");
      if ($tblRes && mysqli_num_rows($tblRes) > 0) {
        $hasEventImagesTable = true;
      }
    } catch (Throwable $e) {
      $hasEventImagesTable = false;
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
          // Try to insert to event_images if table exists
          if ($hasEventImagesTable) {
            try {
              if ($ins = @mysqli_prepare($conn, 'INSERT INTO event_images (EventID, path) VALUES (?, ?)')) {
                @mysqli_stmt_bind_param($ins, 'is', $id, $rel);
                @mysqli_stmt_execute($ins);
                @mysqli_stmt_close($ins);
              }
            } catch (Throwable $e) {
              // ignore if table or insert fails
            }
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
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Query failed']);
}
?>
