<?php /* include '../src/auth.php'; */?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Communities</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css?v=20251006">
  <link rel="stylesheet" href="assets/css/communities.css?v=20251006">

  <!-- JS -->
  <script src="assets/js/navbar.js?v=20251006" defer></script>
</head>
<body>
  <!-- Navbar -->
  <?php include 'includes/header.php'; ?>

  <!-- Main Content -->
  <main class="communities-page">
    <h1>Community Posts</h1>

    <!-- New Post Form -->
    <section class="new-post">
      <form action="" method="post">
        <textarea name="postContent" placeholder="Share something with the community..." required></textarea>
        <button type="submit">Post</button>
      </form>
    </section>

    <!-- Posts Section -->
    <section class="posts">
      <!-- Example Post -->
      <article class="post">
        <div class="post-header">
          <h2>User123</h2>
          <span class="time">2 hours ago</span>
        </div>
        <p class="post-content">
          Does anyone know when the next trading meetup is happening?
        </p>
        <div class="post-actions">
          <button>👍 12</button>
          <button>💬 5</button>
        </div>

        <!-- Comments -->
        <div class="comments">
          <div class="comment">
            <span class="comment-user">TraderMike:</span>
            <p>It’s on 25th Sept at the cafeteria.</p>
          </div>
          <div class="comment">
            <span class="comment-user">Anna:</span>
            <p>Thanks for asking, I was wondering too!</p>
          </div>

          <!-- Add Comment -->
          <form class="add-comment" action="" method="post">
            <input type="text" name="comment" placeholder="Write a comment..." required>
            <button type="submit">Reply</button>
          </form>
        </div>
      </article>

      <!-- Another Example Post -->
      <article class="post">
        <div class="post-header">
          <h2>Ashley</h2>
          <span class="time">5 hours ago</span>
        </div>
        <p class="post-content">
          I’m organizing a coding workshop next week, anyone interested?
        </p>
        <div class="post-actions">
          <button>👍 8</button>
          <button>💬 2</button>
        </div>

        <div class="comments">
          <div class="comment">
            <span class="comment-user">Ryan:</span>
            <p>Count me in!</p>
          </div>

          <form class="add-comment" action="" method="post">
            <input type="text" name="comment" placeholder="Write a comment..." required>
            <button type="submit">Reply</button>
          </form>
        </div>
      </article>
    </section>
  </main>
</body>
</html>
