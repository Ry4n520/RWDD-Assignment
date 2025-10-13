<?php /* include '../src/auth.php';  */?>


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
        <div class="avatar-uploader">
          <img id="profileAvatar" src="assets/images/default-profile.jpg" alt="Profile Picture">
          <label for="avatarInput" class="avatar-edit" title="Change profile picture" aria-hidden="false">
            <input id="avatarInput" name="avatar" type="file" accept="image/*" hidden />
            <span class="edit-icon">✎</span>
          </label>
        </div>
      </div>
      <div class="profile-details">
        <div class="profile-top">
          <h2 id="profileName">Username</h2>
          <button id="openEditProfile" class="edit-btn">Edit profile</button>
        </div>
        <p class="bio">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
          Curabitur vel sem vel odio cursus feugiat.
        </p>
        <div class="contact-info">
          <p>Email: <span id="profileEmail">user@example.com</span></p>
          <p>Phone: <span id="profilePhone">+6012-3456789</span></p>
        </div>
      </div>
    </section>

    <!-- Logout button (placed between profile header and user activity) -->
    <div class="logout-wrap">
      <a href="login.php" class="logout-btn" role="button">Logout</a>
    </div>

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
</body>
</html>
