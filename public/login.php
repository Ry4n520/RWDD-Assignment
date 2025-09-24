<?php
session_start();
include 'db.php'; // make sure this connects to your database

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ⚠️ For production, always use password_hash & password_verify
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $_SESSION['username'] = $username;
        $_SESSION['loggedin'] = true;
        header("Location: home.php");
        exit;
    } else {
        $login_error = "Invalid username or password!";
    }
}

// Handle signup
if (isset($_POST['signup'])) {
    $newuser = $_POST['newuser'];
    $newpass = $_POST['newpass'];

    $check = "SELECT * FROM users WHERE username='$newuser'";
    $res = mysqli_query($conn, $check);

    if (mysqli_num_rows($res) > 0) {
        $signup_error = "Username already taken!";
    } else {
        $insert = "INSERT INTO users (username, password) VALUES ('$newuser', '$newpass')";
        if (mysqli_query($conn, $insert)) {
            $signup_success = "Account created! You can login now.";
        } else {
            $signup_error = "Something went wrong.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Sign Up</title>

  <link rel="stylesheet" href="assets/css/login.css">
  <script src="assets/js/login.js" defer></script>
</head>
<body>
  <div class="auth-container" id="auth-container">
    <!-- Login Form -->
    <div class="form-box login-box">
      <h2>Login</h2>
      <form>
        <div class="input-group">
          <label>Email</label>
          <input type="email" required>
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
      </form>
      <p class="switch-text">
        Don’t have an account? <a href="#" id="show-signup">Sign Up</a>
      </p>
    </div>

    <!-- Signup Form -->
    <div class="form-box signup-box">
      <h2>Sign Up</h2>
      <form>
        <div class="input-group">
          <label>Username</label>
          <input type="text" required>
        </div>
        <div class="input-group">
          <label>Email</label>
          <input type="email" required>
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" required>
        </div>
        <button type="submit" class="btn">Sign Up</button>
      </form>
      <p class="switch-text">
        Already have an account? <a href="#" id="show-login">Login</a>
      </p>
    </div>
  </div>
</body>
</html>
