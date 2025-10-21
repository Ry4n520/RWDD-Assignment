<?php
/* include '../src/auth.php'; */
// load session and DB, then fetch current user to populate profile fields
include __DIR__ . '/../src/db.php';

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['UserID'] ?? 0);

// Check if user is admin
$isAdmin = false;
if ($userId) {
  $r = mysqli_query($conn, "SELECT usertype FROM accounts WHERE UserID = " . $userId . " LIMIT 1");
  if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (isset($row['usertype']) && strtolower($row['usertype']) === 'admin') {
      $isAdmin = true;
    }
  }
}

$user = [
  'username' => 'Username',
  'bio' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vel sem vel odio cursus feugiat.',
  'email' => 'user@example.com',
  'phone' => '+6012-3456789',
  'profile_picture' => 'assets/images/default-profile.jpg'
];

$row = null;
if ($userId) {
  $stmt = mysqli_prepare($conn, "SELECT * FROM accounts WHERE UserID = ? LIMIT 1");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
  }
}

if (!empty($row)) {
  $user['username'] = $row['username'] ?? $user['username'];
  $user['bio'] = $row['bio'] ?? $user['bio'];
  $user['email'] = $row['email'] ?? $user['email'];
  $user['phone'] = $row['phone'] ?? $user['phone'];
  if (!empty($row['profile_picture'])) {
    $user['profile_picture'] = $row['profile_picture'];
  }
}

// Fetch posts for this user
$profilePosts = [];
if ($userId) {
  $sql = "SELECT p.PostID, p.Content, p.Created_at, p.UserID 
          FROM posts p 
          WHERE p.UserID = ? 
          ORDER BY p.Created_at DESC";
  if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
      // load images for post
      $imgs = [];
      $imgStmt = mysqli_prepare($conn, "SELECT path FROM post_images WHERE PostID = ? ORDER BY id ASC");
      if ($imgStmt) {
        mysqli_stmt_bind_param($imgStmt, 'i', $r['PostID']);
        mysqli_stmt_execute($imgStmt);
        $ir = mysqli_stmt_get_result($imgStmt);
        while ($im = mysqli_fetch_assoc($ir)) {
          $imgs[] = ltrim($im['path'], '/');
        }
        mysqli_stmt_close($imgStmt);
      }
      if (!empty($imgs)) $r['images'] = $imgs;
      $profilePosts[] = $r;
    }
    mysqli_stmt_close($stmt);
  }
}

// Comments section has been replaced with notifications
// (Removed unused $profileComments code)

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251020c">
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018">
  <link rel="stylesheet" href="assets/css/profile.css?v=20251017">
  <link rel="stylesheet" href="assets/css/communities.css">

  <!-- JS -->
  <script src="assets/js/navbar.js?v=20251020" defer></script>
</head>
<body>
  <!-- Navbar -->
  <?php include 'includes/header.php'; ?>

  <!-- Profile Page -->
  <main class="profile-page">
    <!-- Profile Info -->
    <section class="profile-header">
      <div class="profile-picture">
        <div class="avatar-uploader">
          <img id="profileAvatar" src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture">
          <label for="avatarInput" class="avatar-edit" title="Change profile picture" aria-hidden="false">
            <input id="avatarInput" name="avatar" type="file" accept="image/*" hidden />
            <span class="edit-icon">✎</span>
          </label>
        </div>
      </div>
      <div class="profile-details">
        <div class="profile-top">
          <h2 id="profileName"><?= htmlspecialchars($user['username']) ?></h2>
          <button id="openEditProfile" class="edit-btn">Edit profile</button>
        </div>
        <p class="bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        <div class="contact-info">
          <p>Email: <span id="profileEmail"><?= htmlspecialchars($user['email']) ?></span></p>
          <p>Phone: <span id="profilePhone"><?= htmlspecialchars($user['phone']) ?></span></p>
        </div>
      </div>
    </section>

    <!-- Logout button (placed between profile header and user activity) -->
    <div class="logout-wrap">
      <a href="login.php" class="logout-btn" role="button">Logout</a>
    </div>

    <!-- User Activity / Notifications -->
    <section class="user-activity">
      <h3>Notifications</h3>
      <div class="notifications-controls">
        <div class="notif-tabs">
          <ul style="list-style:none; padding:0; margin:0;">
            <li><a href="#" id="tabUnread" class="notif-tab-link active-link">Unread</a></li>
            <li><a href="#" id="tabRead" class="notif-tab-link">Read</a></li>
          </ul>
        </div>
        <button id="markAllNotifications" class="btn" type="button">Mark all read</button>
      </div>
      <div id="notificationsList" class="notifications-list">
        <!-- notifications will be loaded here by assets/js/notifications.js -->
      </div>
    </section>
  </main>
  
  <!-- Edit Profile Modal -->
  <div id="editProfileModal" class="modal" aria-hidden="true">
    <div class="modal-backdrop" data-close="true"></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle">
      <h2 id="editProfileTitle">Edit Profile</h2>
      <form id="editProfileForm">
        <div class="field-group">
          <label for="editUsername">Display name</label>
          <input id="editUsername" name="username" type="text" />
        </div>
        <div class="field-group">
          <label for="editBio">Bio</label>
          <textarea id="editBio" name="bio" rows="4"></textarea>
        </div>
        <div class="field-group">
          <label for="editEmail">Email</label>
          <input id="editEmail" name="email" type="email" />
        </div>
        <div class="field-group">
          <label for="editPhone">Phone</label>
          <input id="editPhone" name="phone" type="tel" />
        </div>
        <div class="modal-actions">
          <button type="button" class="btn cancel" id="cancelEdit">Cancel</button>
          <button type="submit" class="btn primary" id="saveEdit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/profile-edit.js" defer></script>
  <script src="assets/js/communities.js" defer></script>
  <script src="assets/js/notifications.js?v=20251018" defer></script>
  <script>
    // ensure carousel is initialized on profile posts after communities.js loads
    document.addEventListener('DOMContentLoaded', function () {
      if (window.initPostCarousels) window.initPostCarousels();
    });
  </script>
</body>
</html>
