
<?php
// public/login.php
session_start();
include __DIR__ . '/../src/db.php'; // adjust if your db.php path differs

// ===== Signup =====
if (isset($_POST['signup'])) {
    $newuser = $_POST['newuser'];
    $newemail = $_POST['newemail'];
    $newpass = $_POST['newpass'];

    $check = "SELECT * FROM users WHERE username='$newuser'";
    $res = mysqli_query($conn, $check);

    if (mysqli_num_rows($res) > 0) {
        $signup_error = "Username already taken!";
        $showSignup = true; // 👈 Force signup form to show
    } else {
        $insertSql = "INSERT INTO users (username, email, password, date_joined) 
                      VALUES ('$newuser', '$newemail', '$newpass', NOW())";

        if (mysqli_query($conn, $insertSql)) {
            $signup_success = "Account created! You can login now.";
            $showSignup = false; // 👈 Back to login form
        } else {
            $signup_error = "Something went wrong.";
            $showSignup = true; 
        }
    }
}

// ===== Login =====
if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $loginSql = "SELECT * FROM users WHERE username='$user' AND password='$pass' LIMIT 1";
    $loginRes = mysqli_query($conn, $loginSql);

    if ($loginRes && mysqli_num_rows($loginRes) === 1) {
        $row = mysqli_fetch_assoc($loginRes);
        // set session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id']  = $row['userid']; // or user_id depending on your schema
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
