<?php
// Simplified communities.php - show posts and comment counts; no auth checks
include __DIR__ . '/../src/db.php';

// determine composer avatar from logged-in user (if any)
session_start();
$composerAvatar = 'assets/images/default-profile.jpg';
$sessUid = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
// integer session uid for SQL
$sessUidInt = $sessUid ? intval($sessUid) : 0;
if ($sessUid) {
  $u = intval($sessUid);
  $r = mysqli_query($conn, "SELECT profile_picture FROM accounts WHERE UserID = " . $u . " LIMIT 1");
  if ($r && mysqli_num_rows($r) > 0) {
    $row = mysqli_fetch_assoc($r);
    if (!empty($row['profile_picture'])) $composerAvatar = $row['profile_picture'];
  }
}

// Fetch posts with like count and comment count
$postsSql = "
  SELECT p.PostID, p.Content, p.Title, p.Created_at, a.username,
    (SELECT COUNT(*) FROM postlikes pl WHERE pl.PostID = p.PostID) AS like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.PostID = p.PostID) AS comment_count,
  (SELECT GROUP_CONCAT(path SEPARATOR '||') FROM post_images pi WHERE pi.PostID = p.PostID ORDER BY pi.id ASC) AS images
  FROM posts p
  JOIN accounts a ON p.UserID = a.UserID
  ORDER BY p.Created_at DESC
";
$posts = mysqli_query($conn, $postsSql);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Communities</title>
  <?php $cssVer = file_exists(__DIR__ . '/assets/css/communities.css') ? filemtime(__DIR__ . '/assets/css/communities.css') : time(); ?>
  <link rel="stylesheet" href="assets/css/navbar.css?v=<?= $cssVer ?>">
  <link rel="stylesheet" href="assets/css/communities.css?v=<?= $cssVer ?>">
  <script src="assets/js/navbar.js" defer></script>
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
          <?php
            // determine whether current user liked this post (if logged in)
            $liked = false;
            if ($sessUidInt) {
              $check = mysqli_query($conn, "SELECT 1 FROM postlikes WHERE PostID = " . intval($post['PostID']) . " AND UserID = " . $sessUidInt . " LIMIT 1");
              if ($check && mysqli_num_rows($check) > 0) $liked = true;
            }
          ?>
          <article class="post" data-postid="<?= $post['PostID'] ?>">
            <div class="post-header">
              <h3 class="post-title"><?= htmlspecialchars($post['username']) ?></h3>
              <div class="post-meta"><span class="time"><?= htmlspecialchars($post['Created_at']) ?></span></div>
            </div>
            <p class="post-content"><?= nl2br(htmlspecialchars($post['Content'])) ?></p>
            <div class="post-actions">
              <button type="button" class="like-btn<?= $liked ? ' liked' : '' ?>" data-type="post" data-id="<?= $post['PostID'] ?>" data-liked="<?= $liked ? '1' : '0' ?>" aria-pressed="<?= $liked ? 'true' : 'false' ?>">👍 <?= intval($post['like_count']) ?></button>
              <button type="button" class="comment-toggle" data-target="#comments-<?= $post['PostID'] ?>">💬 <?= intval($post['comment_count']) ?></button>
            </div>
            <div class="post-images">
              <?php
                if (!empty($post['images'])) {
                  // images may be returned as a GROUP_CONCAT string separated by '||' or as an array (API responses)
                  if (is_array($post['images'])) {
                    $imgs = $post['images'];
                  } else {
                    $imgs = explode('||', $post['images']);
                  }
                  foreach ($imgs as $img) {
                    $img = trim($img);
                    if ($img === '') continue;
                    // ensure safe output
                    $src = htmlspecialchars($img);
                    echo "<img src=\"{$src}\" class=\"post-image\" alt=\"post image\">";
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

  <script>window.isLoggedIn = <?= $sessUid ? 'true' : 'false' ?>;</script>
  <script src="assets/js/communities.js?v=20251009"></script>
</body>
</html>
