<?php
include '../src/auth.php';
// Simplified communities.php - show posts and comment counts; no auth checks
include __DIR__ . '/../src/db.php';

// determine composer avatar from logged-in user (if any)
$composerAvatar = 'assets/images/default-profile.jpg';
$sessUidInt = (int)($_SESSION['user_id'] ?? $_SESSION['UserID'] ?? 0);
$isAdmin = false;
if ($sessUidInt) {
  $r = mysqli_query($conn, "SELECT profile_picture, usertype FROM accounts WHERE UserID = " . $sessUidInt . " LIMIT 1");
  if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (!empty($row['profile_picture'])) $composerAvatar = $row['profile_picture'];
    // Check if user is admin (usertype could be 'admin', 'Admin', or is_admin = 1)
    if (isset($row['usertype']) && strtolower($row['usertype']) === 'admin') $isAdmin = true;
  }
}

// Fetch posts with like count and comment count
$postsSql = "
  SELECT p.PostID, p.Content, p.Created_at, p.UserID, a.username,
    (SELECT COUNT(*) FROM postlikes pl WHERE pl.PostID = p.PostID) AS like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.PostID = p.PostID) AS comment_count,
    EXISTS(SELECT 1 FROM postlikes pl2 WHERE pl2.PostID = p.PostID AND pl2.UserID = ?) AS liked,
  (SELECT GROUP_CONCAT(path SEPARATOR '||') FROM post_images pi WHERE pi.PostID = p.PostID ORDER BY pi.id ASC) AS images
  FROM posts p
  JOIN accounts a ON p.UserID = a.UserID
  ORDER BY p.Created_at DESC
";
$stmt = mysqli_prepare($conn, $postsSql);
mysqli_stmt_bind_param($stmt, 'i', $sessUidInt);
mysqli_stmt_execute($stmt);
$posts = mysqli_stmt_get_result($stmt);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Communities</title>
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251020c">
  <link rel="stylesheet" href="assets/css/theme.css?v=20251018">
  <link rel="stylesheet" href="assets/css/communities.css?v=20251020">
  <script src="assets/js/navbar.js?v=20251020" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="communities-page">
    <!-- Composer: title, content, images, post button -->
    <section class="composer">
      <form id="postForm" class="composer-form" enctype="multipart/form-data" novalidate>
        <div class="composer-top">
          <img class="composer-avatar" src="<?= htmlspecialchars($composerAvatar) ?>" alt="avatar">
          <div class="composer-fields">
            <textarea name="content" id="postContent" class="composer-input" placeholder="Share something with the community..." rows="4" required></textarea>

            <div class="composer-file-row">
              <input id="postFiles" name="images[]" class="composer-files" type="file" accept="image/*" multiple>
              <div class="composer-actions">
                <button type="submit" id="postButton">Post</button>
              </div>
            </div>

            <div id="filePreview" class="file-preview" aria-hidden="true"></div>
            <div id="composerMessage" class="composer-message" aria-hidden="true"></div>
          </div>
        </div>
      </form>
    </section>

    <section class="posts">
      <?php if ($posts && mysqli_num_rows($posts) > 0): ?>
        <?php while ($post = mysqli_fetch_assoc($posts)): ?>
          <article class="post" data-postid="<?= $post['PostID'] ?>">
            <div class="post-header">
              <h3 class="post-title"><?= htmlspecialchars($post['username']) ?></h3>
              <div class="post-meta">
                <span class="time"><?= htmlspecialchars($post['Created_at']) ?></span>
                <?php if ($sessUidInt && ($isAdmin || $sessUidInt == intval($post['UserID']))): ?>
                  <div class="post-menu">
                    <button type="button" class="post-menu-btn" aria-label="Post options" aria-haspopup="true">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="5" r="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <circle cx="12" cy="19" r="2"/>
                      </svg>
                    </button>
                    <div class="post-menu-dropdown" aria-hidden="true">
                      <button type="button" class="menu-item edit-post" data-postid="<?= $post['PostID'] ?>">Edit</button>
                      <button type="button" class="menu-item delete-post" data-postid="<?= $post['PostID'] ?>">Delete</button>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <p class="post-content"><?= nl2br(htmlspecialchars($post['Content'])) ?></p>
            <div class="post-actions">
              <button type="button" class="like-btn<?= !empty($post['liked']) ? ' liked' : '' ?>" data-type="post" data-id="<?= $post['PostID'] ?>" data-liked="<?= !empty($post['liked']) ? '1' : '0' ?>" aria-pressed="<?= !empty($post['liked']) ? 'true' : 'false' ?>">👍 <?= intval($post['like_count']) ?></button>
              <button type="button" class="comment-toggle" data-target="#comments-<?= $post['PostID'] ?>">💬 <?= intval($post['comment_count']) ?></button>
            </div>
            <div class="post-images">
              <?php
                if (!empty($post['images'])) {
                  $imgs = explode('||', $post['images']);
                  foreach ($imgs as $img) {
                    $img = trim($img);
                    if ($img === '') continue;
                    echo '<img src="' . htmlspecialchars($img) . '" class="post-image" alt="post image">';
                  }
                }
              ?>
            </div>
            <div class="comments" id="comments-<?= $post['PostID'] ?>" aria-hidden="true"></div>
          </article>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No posts yet — be the first to post!</p>
      <?php endif; ?>
    </section>
  </main>

  <script>window.isLoggedIn = <?= $sessUidInt ? 'true' : 'false' ?>;</script>
  <script src="assets/js/communities.js?v=20251020a"></script>
</body>
</html>
