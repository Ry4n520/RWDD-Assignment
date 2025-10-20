
<?php
// public/login.php
session_start();
include __DIR__ . '/../src/db.php'; // adjust if your db.php path differs

// ===== Signup =====
if (isset($_POST['signup'])) {
    $newuser = $_POST['newuser'];
    $newemail = $_POST['newemail'];
    $newpass = $_POST['newpass'];

    $check = "SELECT * FROM accounts WHERE username='$newuser'";
    $res = mysqli_query($conn, $check);

    if (mysqli_num_rows($res) > 0) {
        $signup_error = "Username already taken!";
        $showSignup = true; // 👈 Force signup form to show
    } else {
    // escape inputs before building the INSERT to avoid syntax errors
    $newuser_e = mysqli_real_escape_string($conn, $newuser);
    $newemail_e = mysqli_real_escape_string($conn, $newemail);
    $newpass_e = mysqli_real_escape_string($conn, $newpass);

    // note: some schemas do not have a `date_joined` column. Let the DB default
    // timestamp handle creation time (or add the column). Insert only the
    // fields we are sure exist.
    $insertSql = "INSERT INTO accounts (username, email, password) 
            VALUES ('$newuser_e', '$newemail_e', '$newpass_e')";

    if (mysqli_query($conn, $insertSql)) {
      $signup_success = "Account created! You can login now.";
      $showSignup = false; // 👈 Back to login form
    } else {
      $signup_error = "Something went wrong: " . mysqli_error($conn);
      $showSignup = true; 
    }
    }
}

// ===== Login =====
if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $loginSql = "SELECT * FROM accounts WHERE username='$user' AND password='$pass' LIMIT 1";
    $loginRes = mysqli_query($conn, $loginSql);

  if ($loginRes && mysqli_num_rows($loginRes) === 1) {
    $row = mysqli_fetch_assoc($loginRes);
    // set session
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $row['username'] ?? $row['user'] ?? null;
    // robustly pick the id column that exists and store it in both keys
    $uid = $row['UserID'] ?? $row['userid'] ?? $row['id'] ?? $row['user_id'] ?? null;
    if ($uid !== null) {
      $_SESSION['user_id'] = $uid;
      $_SESSION['UserID'] = $uid;
    }
    header("Location: home.php");
    exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login / Signup</title>
  <link rel="stylesheet" href="assets/css/login.css">
  <script src="assets/js/login.js" defer></script>
</head>
<body>
  <div class="auth-container" id="auth-container">

    <!-- LOGIN -->
    <div class="form-box login-box">
      <h2>Login</h2>
      <?php if (!empty($login_error)) echo "<p style='color:red'>$login_error</p>"; ?>
      <form method="POST" action="login.php">
        <div class="input-group">
          <label>Username</label>
          <input type="text" name="username" required>
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit" name="login" class="btn">Login</button>
      </form>
      <p class="switch-text">Don't have an account? <a href="#" id="show-signup">Sign Up</a></p>
    </div>

    <!-- SIGNUP -->
    <div class="form-box signup-box">
      <h2>Sign Up</h2>
      <?php if (!empty($signup_error)) echo "<p style='color:red'>$signup_error</p>"; ?>
      <?php if (!empty($signup_success)) echo "<p style='color:green'>$signup_success</p>"; ?>
      <form method="POST" action="login.php">
        <div class="input-group">
          <label>Username</label>
          <input type="text" name="newuser" required>
        </div>
        <div class="input-group">
          <label>Email</label>
          <input type="email" name="newemail" required>
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="newpass" required>
        </div>
        <button type="submit" name="signup" class="btn">Sign Up</button>
      </form>
      <p class="switch-text">Already have an account? <a href="#" id="show-login">Login</a></p>
    </div>

  </div>
<?php if (isset($showSignup) && $showSignup): ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("auth-container").classList.add("show-signup");
  });
</script>
<?php endif; ?>
</body>
</html>
