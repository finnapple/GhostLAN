<?php
session_start();

// Initialize variables
$login_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');    // Validate input
    if (empty($username) || empty($password)) {
        $login_error = "Both username and password are required";
    } else {
        // Read user.txt file
        if (file_exists('user.txt')) {
            $lines = file('user.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $credentials = array();
            
            foreach ($lines as $line) {
                $parts = explode(':', $line);
                if (count($parts) === 2) {
                    $credentials[trim($parts[0])] = trim($parts[1]);
                }
            }
            
            if (isset($credentials['username']) && isset($credentials['password'])) {
                $correct_username = $credentials['username'];
                $correct_password = $credentials['password'];
                if ($username === $correct_username && $password === $correct_password) {
                    // Valid login
                    $_SESSION['ghostlan_admin'] = true;
                    $_SESSION['username'] = $username;
                    
                    // Store current session timestamp for validation
                    $_SESSION['login_time'] = file_get_contents('session.txt');
                    
                    header('Location: index.php');
                    exit;
                }
            }
        }
        $login_error = "Invalid username or password";
    }
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $login_error = "Both username and password are required";
    } else {
        // Read credentials from user.txt file
        $credentials_file = "user.txt";
        
        if (file_exists($credentials_file)) {
            $file_content = file_get_contents($credentials_file);
            $lines = explode("\n", $file_content);
            
            $admin_found = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Assuming format: username:password
                $parts = explode(":", $line, 2);
                if (count($parts) == 2) {
                    $file_username = trim($parts[0]);
                    $file_password = trim($parts[1]);
                      if ($file_username === $username && $file_password === $password) {
                        // Valid admin login
                        $_SESSION['ghostlan_admin'] = true;
                        $_SESSION['username'] = $username;
                        
                        // Store current session timestamp for validation
                        $_SESSION['login_time'] = file_get_contents('session.txt');
                        
                        // Redirect to main page
                        header("Location: index.php");
                        exit;
                    }
                }
            }
            
            // If we reach here, credentials were not found
            $login_error = "Invalid admin credentials";
        } else {
            $login_error = "Error: Credentials file not found";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>GhostLAN</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta charset="UTF-8">
  <link rel="icon" href="ghost.png" type="image/png" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&display=swap');
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-tap-highlight-color: transparent;
    }

    body {
      font-family: 'JetBrains Mono', 'Courier New', monospace;
      background-color: #ffffff;
      height: 100vh;
      margin: 0;
      padding: 0;
      overflow: hidden;
      color: #000000;
      width: 100vw;
      user-select: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .auth-container {
      flex: none;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      width: 100vw;
      height: 100vh;
      background: #fff;
      position: relative;
    }

    .auth-box {
      width: 92vw;
      max-width: 400px;
      min-width: 220px;
      border: 1px solid #000000;
      padding: 25px 18px;
      position: relative;
      background-color: #f5f5f5;
      animation: fadeIn 0.3s ease;
      box-sizing: border-box;
      margin: 0 auto;
    }

    @media (min-width: 480px) {
      .auth-box {
        padding: 25px 32px;
      }
    }

    @media (min-width: 768px) {
      .auth-box {
        max-width: 400px;
        min-width: 300px;
        border-radius: 0;
      }
      .auth-container {
        width: 100vw;
        height: 100vh;
      }
    }

    /* Header Styles */
    .header {
      padding: 15px 20px;
      background: #f5f5f5;
      color: #000000;
      text-align: left;
      font-weight: 400;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 2px solid #000000;
      font-size: 14px;
    }

    .header h1 {
      font-size: 18px;
      margin: 0;
      flex-grow: 1;
      font-weight: 400;
    }

    .header h1::before {
      content: "";
      color: #666;
    }

    .switch-button {
      background-color: transparent;
      color: #000000;
      border: 1px solid #000000;
      padding: 6px 12px;
      border-radius: 0;
      font-size: 12px;
      cursor: pointer;
      font-weight: 400;
      transition: all 0.2s ease;
      font-family: 'JetBrains Mono', monospace;
    }

    .switch-button:hover {
      background-color: #000000;
      color: #fff;
    }

    .switch-button:active {
      transform: scale(0.95);
    }

    /* Auth Container */
    .auth-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
      background: #fff;
      position: relative;
    }

    .auth-box {
      width: 100%;
      max-width: 400px;
      border: 1px solid #000000;
      padding: 25px;
      position: relative;
      background-color: #f5f5f5;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .auth-title {
      font-size: 16px;
      font-weight: 400;
      margin-bottom: 20px;
      color: #000000;
      text-align: center; /* Center the title */
      position: relative;
    }

    .terminal-welcome {
      color: #666;
      font-size: 11px;
      margin-bottom: 20px;
      width: 100%;
      text-align: center; /* Center the welcome text */
      border-left: none; /* Remove left border for better centering */
      padding-left: 0;
    }

    .form-group {
      margin-bottom: 16px;
      position: relative;
    }

    .form-label {
      display: block;
      font-size: 12px;
      color: #666;
      margin-bottom: 8px;
      font-weight: 400;
    }

    .form-label::before {
      content: "> ";
    }

    .form-input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 0;
      background-color: #fff;
      color: #000000;
      font-size: 13px;
      font-family: 'JetBrains Mono', monospace;
      transition: all 0.2s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: #000000;
    }

    .form-input::placeholder {
      color: #aaa;
      font-style: italic;
    }

    .form-button {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      background-color: transparent;
      color: #000000;
      border: 1px solid #000000;
      border-radius: 0;
      font-size: 13px;
      font-weight: 400;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'JetBrains Mono', monospace;
    }

    .form-button:hover {
      background-color: #000000;
      color: #fff;
    }

    .form-button:active {
      transform: scale(0.98);
    }

    .auth-footer {
      margin-top: 25px;
      font-size: 12px;
      color: #666;
      text-align: center;
    }

    .auth-switch {
      color: #000000;
      text-decoration: none;
      border-bottom: 1px solid #000000;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .auth-switch:hover {
      color: #333;
      border-color: #333;
    }

    /* Terminal welcome message styling */
    .terminal-welcome {
      color: #666;
      font-size: 11px;
      margin-bottom: 20px;
      border-left: 2px solid #ccc;
      padding-left: 10px;
      text-align: left;
      width: 100%;
    }

    /* Error message styling */
    .error-message {
      color: #cc0000;
      font-size: 12px;
      margin-top: 4px;
      display: none;
    }

    .error-message.active {
      display: block;
      animation: shakeError 0.4s ease;
    }

    @keyframes shakeError {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    /* Visible error message for PHP errors */
    .php-error-message {
      color: #cc0000;
      font-size: 12px;
      margin-top: 10px;
      margin-bottom: 10px;
      padding: 8px;
      border: 1px solid #cc0000;
      background-color: rgba(204, 0, 0, 0.05);
      display: <?php echo empty($login_error) ? 'none' : 'block'; ?>;
    }

    /* Loading animation */
    .loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(255, 255, 255, 0.8);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 100;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .loading-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .loading-text {
      color: #666;
      font-size: 14px;
      margin-top: 15px;
      font-style: italic;
    }

    .loading-text::after {
      content: '';
      display: inline-block;
      width: 8px;
      height: 14px;
      background: #666;
      animation: blink 1s step-end infinite;
      margin-left: 4px;
    }

    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #000000;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }

    /* Make the form look like a terminal */
    .auth-box::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background-color: #000000;
    }

    /* Desktop specific styles */
    @media (min-width: 768px) {
      .auth-container {
        max-width: 800px;
        height: 90vh;
        margin: 5vh auto;
        border-radius: 0;
        overflow: hidden;
      }

      .header {
        border-radius: 0;
      }

      .auth-box {
        border-radius: 0;
      }
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <!-- Login Form -->
    <div class="auth-box" id="loginForm">
      <h2 class="auth-title">
        Welcome to GhostLAN
      </h2>
      <div class="terminal-welcome">
        <!-- Terminal welcome message can go here -->
      </div>
      <!-- PHP Error Message -->
      <div class="php-error-message" id="phpErrorMessage">
        <?php echo $login_error; ?>
      </div>
      <form id="loginFormElement" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
          <label class="form-label">username</label>
          <input type="text" class="form-input" id="loginUsername" name="username" placeholder="enter admin username..." required>
        </div>
        <div class="form-group">
          <label class="form-label">password</label>
          <input type="password" class="form-input" id="loginPassword" name="password" placeholder="enter admin password..." required>
        </div>
        <button type="submit" class="form-button" id="loginButton">Login</button>
      </form>
      <div class="loading-overlay" id="loginLoading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Authenticating...</div>
      </div>
    </div>
  </div>
  <script>
    // DOM Elements
    const loginForm = document.getElementById('loginFormElement');
    const loginUsername = document.getElementById('loginUsername');
    const loginPassword = document.getElementById('loginPassword');
    const loginLoading = document.getElementById('loginLoading');
    
    // Show loading animation on form submit
    loginForm.addEventListener('submit', function(e) {
      // Don't prevent default as we want the form to submit to PHP
      loginLoading.classList.add('active');
    });
    
    // Focus on username field on load
    window.addEventListener('load', function() {
      setTimeout(() => {
        loginUsername.focus();
      }, 500);
    });
  </script>
</body>

</html>
