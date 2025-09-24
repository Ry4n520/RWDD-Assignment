<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>

  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/navbar.css">
  <link rel="stylesheet" href="assets/css/profile.css">

  <!-- JS -->
  <script src="assets/js/navbar.js" defer></script>
</head>
<body>
  <!-- Navbar -->
  <?php include 'includes/header.php'; ?>

  <!-- Profile Page -->
  <main class="profile-page">
    <!-- Profile Info -->
    <section class="profile-header">
      <div class="profile-picture">
        <img src="assets/images/default-avatar.png" alt="Profile Picture">
        <button class="edit-btn">Edit</button>
      </div>
      <div class="profile-details">
        <h2>Username <button class="edit-btn">Edit</button></h2>
        <p class="bio">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
          Curabitur vel sem vel odio cursus feugiat.
          <button class="edit-btn">Edit</button>
        </p>
        <div class="contact-info">
          <p>Email: user@example.com <button class="edit-btn">Edit</button></p>
          <p>Phone: +6012-3456789 <button class="edit-btn">Edit</button></p>
        </div>
      </div>
    </section>

    <!-- User Activity -->
    <section class="user-activity">
      <h3>Your Posts</h3>
      <div class="posts">
        <article class="post">
          <h4>Post Title Example</h4>
          <p>This is a sample post you made in Communities.</p>
        </article>
        <article class="post">
          <h4>Another Post</h4>
          <p>Second example post for layout preview.</p>
        </article>
      </div>

      <h3>Your Comments</h3>
      <div class="comments">
        <div class="comment">
          <span class="comment-on">On "Trading Meetup Post":</span>
          <p>Yeah, I’ll be there!</p>
        </div>
        <div class="comment">
          <span class="comment-on">On "Coding Workshop":</span>
          <p>Looking forward to it!</p>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
