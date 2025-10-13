<?php
// Simplified communities.php - show posts and comment counts; no auth checks
include __DIR__ . '/../src/db.php';

// Fetch posts with like count and comment count
$postsSql = "
  SELECT p.PostID, p.Content, p.Created_at, a.username,
    (SELECT COUNT(*) FROM postlikes pl WHERE pl.PostID = p.PostID) AS like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.PostID = p.PostID) AS comment_count
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
  <title>Communities</title>
  <?php $cssVer = file_exists(__DIR__ . '/assets/css/communities.css') ? filemtime(__DIR__ . '/assets/css/communities.css') : time(); ?>
  <link rel="stylesheet" href="assets/css/navbar.css?v=<?= $cssVer ?>">
  <link rel="stylesheet" href="assets/css/communities.css?v=<?= $cssVer ?>">
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <main class="communities-page">
    <h1>Community Posts</h1>

    <!-- Top Composer (visual only) -->
    <section class="composer">
      <form class="composer-form" onsubmit="return false;">
        <img src="assets/images/default-profile.jpg" alt="avatar" class="composer-avatar">
        <textarea class="composer-input" placeholder="What's happening?"></textarea>
        <div class="composer-actions">
          <button type="button">Post</button>
        </div>
      </form>
    </section>
  <!-- <div style="text-align:center; color:#999; margin-bottom:12px; font-size:12px;">CSS ver: <?= $cssVer ?></div>
 -->
    <section class="posts">
      <?php if ($posts && mysqli_num_rows($posts) > 0): ?>
        <?php while ($post = mysqli_fetch_assoc($posts)): ?>
          <article class="post" data-postid="<?= $post['PostID'] ?>">
            <div class="post-header">
              <h2><?= htmlspecialchars($post['username']) ?></h2>
              <span class="time"><?= htmlspecialchars($post['Created_at']) ?></span>
            </div>
            <p class="post-content"><?= nl2br(htmlspecialchars($post['Content'])) ?></p>
            <div class="post-actions">
              <a href="#" class="like-btn" data-type="post" data-id="<?= $post['PostID'] ?>">👍 <?= intval($post['like_count']) ?></a>
              <button type="button" class="comment-toggle" data-target="#comments-<?= $post['PostID'] ?>">💬 <?= intval($post['comment_count']) ?></button>
            </div>
            <div class="comments" id="comments-<?= $post['PostID'] ?>" aria-hidden="true"></div>
          </article>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No posts yet — be the first to post!</p>
      <?php endif; ?>
    </section>
  </main>

  <script src="assets/js/communities.js?v=20251009"></script>
</body>
</html>
