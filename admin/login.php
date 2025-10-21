<?php
session_start();

if (isset($_SESSION['admin'])) {
  header('Location: dashboard.php');
  exit;
}

require_once __DIR__ . '/../includes/config.php';

$error = null;
$emailValue = '';
$rememberChecked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $rememberChecked = isset($_POST['remember']);
  $emailValue = $email;

  if ($email === '' || $password === '') {
    $error = 'Email and password are required.';
  } else {
    $stmt = $conn->prepare(
      'SELECT Id, DisplayName, PasswordHash, IsActive FROM admin_users WHERE Email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if ($admin && (int)$admin['IsActive'] === 1 && password_verify($password, $admin['PasswordHash'])) {
      $_SESSION['admin_id'] = (int)$admin['Id'];
      $_SESSION['admin'] = $admin['DisplayName'];
      header('Location: dashboard.php');
      exit;
    }

    $error = 'Invalid email or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rewarity Admin Login</title>
  <link rel="stylesheet" href="../login_form_demo/style.css">
  <style>
    .login-header h1 { margin-bottom: 4px; }
    .login-header p { margin-bottom: 0; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo">
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <circle cx="16" cy="16" r="16" fill="#1DB954"/>
            <path d="M12 10l8 6-8 6V10z" fill="white"/>
          </svg>
        </div>
        <h1>Sign in to Rewarity Admin</h1>
        <p>Access your administration console</p>
      </div>

      <?php if ($error): ?>
        <div class="server-error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form class="login-form" id="loginForm" method="post" action="login.php" novalidate>
        <div class="form-group">
          <label for="email">Email address</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            autocomplete="email"
            value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES); ?>"
          >
          <span class="error-message" id="emailError"></span>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              required
              autocomplete="current-password"
            >
            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
              <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M10 3C5 3 1.73 7.11 1 10c.73 2.89 4 7 9 7s8.27-4.11 9-7c-.73-2.89-4-7-9-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" fill="currentColor"/>
              </svg>
            </button>
          </div>
          <span class="error-message" id="passwordError"></span>
        </div>

        <div class="form-options">
          <label class="checkbox-wrapper">
            <input type="checkbox" id="remember" name="remember" <?php echo $rememberChecked ? 'checked' : ''; ?>>
            <span class="checkmark"></span>
            Remember me
          </label>
          <a class="forgot-link" href="mailto:support@rewarity.com">Need help? Contact support</a>
        </div>

        <button type="submit" class="login-btn">
          <span class="btn-text">Sign In</span>
          <div class="btn-loader">
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
          </div>
        </button>
      </form>

      <div class="divider">
        <span>or</span>
      </div>

      <div class="signup-link">
        <p>Looking for the customer portal? <a href="/">Return to site</a></p>
      </div>

      <div class="success-message" id="successMessage">
        <div class="success-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="12" fill="#1DB954"/>
            <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3>Welcome back!</h3>
        <p>Redirecting to your dashboard...</p>
      </div>
    </div>
  </div>

  <script src="login.js"></script>
</body>
</html>
