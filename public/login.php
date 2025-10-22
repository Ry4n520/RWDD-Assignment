
<?php
// public/login.php
session_start();
include __DIR__ . '/../src/db.php';

// ===== Signup =====
if (isset($_POST['signup'])) {
    $newuser = trim($_POST['newuser']);
    $newemail = trim($_POST['newemail']);
    $newpass = $_POST['newpass'];

    // Check if username exists using prepared statement
    $checkStmt = mysqli_prepare($conn, "SELECT username FROM accounts WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($checkStmt, "s", $newuser);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);

    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        $signup_error = "Username already taken!";
        $showSignup = true;
    } else {
        // Insert new user using prepared statement
        $insertStmt = mysqli_prepare($conn, "INSERT INTO accounts (username, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insertStmt, "sss", $newuser, $newemail, $newpass);

        if (mysqli_stmt_execute($insertStmt)) {
            $signup_success = "Account created! You can login now.";
            $showSignup = false;
        } else {
            $signup_error = "Something went wrong. Please try again.";
            $showSignup = true;
        }
        mysqli_stmt_close($insertStmt);
    }
    mysqli_stmt_close($checkStmt);
}

// ===== Login =====
if (isset($_POST['login'])) {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Use prepared statement for login
    $loginStmt = mysqli_prepare($conn, "SELECT UserID, username FROM accounts WHERE username = ? AND password = ? LIMIT 1");
    mysqli_stmt_bind_param($loginStmt, "ss", $user, $pass);
    mysqli_stmt_execute($loginStmt);
    $loginRes = mysqli_stmt_get_result($loginStmt);

    if ($row = mysqli_fetch_assoc($loginRes)) {
        // Set session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_id'] = (int)$row['UserID'];
        $_SESSION['UserID'] = (int)$row['UserID'];
        
        mysqli_stmt_close($loginStmt);
        header("Location: home.php");
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
    mysqli_stmt_close($loginStmt);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login / Signup</title>
    <link rel="stylesheet" href="assets/css/login.css?v=20251021c">
  <script src="assets/js/login.js?v=20251021" defer></script>
</head>
<body>
  <div class="login-container" id="auth-container">
    
    <!-- Left Panel - Decorative -->
    <div class="left-panel">
      <div class="panel-content">
        <h1>Link Mosaic</h1>
        <p>Log in to discover events, share ideas, and stay connected with those around you. </p>
      </div>
    </div>

    <!-- Right Panel - Forms -->
    <div class="right-panel">
      
      <!-- LOGIN FORM -->
      <div class="form-wrapper login-form<?= (isset($showSignup) && $showSignup) ? ' hidden' : '' ?>">
        <h2>Welcome Back</h2>
        <p class="subtitle">Enter your email and password to access your account.</p>
        
        <?php if (!empty($login_error)) echo "<p class='error-msg'>$login_error</p>"; ?>
        
        <form method="POST" action="login.php">
          <div class="input-group">
            <label>Email</label>
            <input type="text" name="username" placeholder="Enter your email here..." required>
          </div>
          
          <div class="input-group">
            <label>Password</label>
            <div class="password-wrapper">
              <input type="password" name="password" placeholder="Enter your password here..." required id="login-password">
              <button type="button" class="toggle-password" data-target="login-password">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>
          
          <div class="form-options">
            <label class="checkbox-label">
              <input type="checkbox" name="remember">
              <span>Remember Me</span>
            </label>
            <a href="#" class="forgot-link">Forgot Password</a>
          </div>
          
          <button type="submit" name="login" class="btn-primary">Login</button>
          
          <p class="switch-text">or sign up <a href="#" id="show-signup">here</a></p>
        </form>
      </div>

      <!-- SIGNUP FORM -->
      <div class="form-wrapper signup-form<?= (isset($showSignup) && $showSignup) ? '' : ' hidden' ?>">
        <h2>Create Account</h2>
        <p class="subtitle">Sign up to get started with your account.</p>
        
        <?php if (!empty($signup_error)) echo "<p class='error-msg'>$signup_error</p>"; ?>
        <?php if (!empty($signup_success)) echo "<p class='success-msg'>$signup_success</p>"; ?>
        
        <form method="POST" action="login.php">
          <div class="input-group">
            <label>Username</label>
            <input type="text" name="newuser" placeholder="Enter your username..." required>
          </div>
          
          <div class="input-group">
            <label>Email</label>
            <input type="email" name="newemail" placeholder="Enter your email..." required>
          </div>
          
          <div class="input-group">
            <label>Password</label>
            <div class="password-wrapper">
              <input type="password" name="newpass" placeholder="Enter your password..." required id="signup-password">
              <button type="button" class="toggle-password" data-target="signup-password">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>
          
          <button type="submit" name="signup" class="btn-primary">Sign Up</button>
          
          <p class="switch-text">Already have an account? <a href="#" id="show-login">Login</a></p>
        </form>
      </div>

    </div>
  </div>
</body>
</html>
