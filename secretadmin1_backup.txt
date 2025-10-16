<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['ghostlan_admin']) || $_SESSION['ghostlan_admin'] !== true) {
    header('Location: admin.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret_key = trim($_POST['secret_key'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    // Validate inputs
    if (empty($secret_key) || empty($new_password)) {
        $error_message = "All fields are required";
    } else {
        // Read secret key from file
        if (file_exists('secret.txt')) {
            $correct_secret = trim(file_get_contents('secret.txt'));
            
            if ($secret_key === $correct_secret) {                // Read current user.txt
                $lines = file('user.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $data = array();
                
                foreach ($lines as $line) {
                    $parts = explode(':', $line);
                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);
                        $data[$key] = $value;
                    }
                }
                
                // Update only the password
                $data['password'] = $new_password;
                
                // Write back to user.txt, maintaining format
                $new_content = "username:" . $data['username'] . "\n";
                $new_content .= "password:" . $data['password'] . "\n";
                  if (file_put_contents('user.txt', $new_content) !== false) {
                    // Update session timestamp to invalidate all sessions
                    file_put_contents('session.txt', time());
                    
                    // Set password change flag
                    $_SESSION['password_changed'] = true;
                    $success_message = "Password changed successfully. All users will be logged out.";
                    
                    // Redirect to admin page after 2 seconds
                    header("refresh:2;url=admin.php");
                    session_destroy();
                } else {
                    $error_message = "Failed to update password";
                }
            } else {
                $error_message = "Invalid secret key";
            }
        } else {
            $error_message = "Secret key file not found";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>GhostLAN - Change Password</title>
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
      border-left: 2px solid #ccc;
      padding-left: 10px;
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

    .terminal-welcome {
      color: #666;
      font-size: 11px;
      margin-bottom: 20px;
      border-left: 2px solid #ccc;
      padding-left: 10px;
      text-align: center;
      width: 100%;
    }

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

    .php-error-message {
      color: #cc0000;
      font-size: 12px;
      margin-top: 10px;
      margin-bottom: 10px;
      padding: 8px;
      border: 1px solid #cc0000;
      background-color: rgba(204, 0, 0, 0.05);
      display: <?php echo empty($error_message) ? 'none' : 'block'; ?>;
    }

    .success-message {
      color: #008800;
      font-size: 12px;
      margin-top: 10px;
      margin-bottom: 10px;
      padding: 8px;
      border: 1px solid #008800;
      background-color: rgba(0, 136, 0, 0.05);
      display: <?php echo empty($success_message) ? 'none' : 'block'; ?>;
    }

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

    .auth-box::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      background-color: #000000;
    }

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

    .success-note-container {
      /* overlay: cover viewport and center content */
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.24);
      align-items: center;
      justify-content: center;
      z-index: 1200;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.32s ease, visibility 0.32s ease;
    }
    .success-note-container.active {
      display: flex;
      opacity: 1;
      pointer-events: all;
      visibility: visible;
      animation: fadeScaleIn 0.46s cubic-bezier(.2,.9,.2,1) both;
    }
    /* preserve original green look but make sizing fluid and mobile-friendly */
    .success-note {
      background: #e6ffe6;
      border: 1.5px solid #17cc35;
      border-radius: 10px;
      padding: 22px;
      box-shadow: 0 6px 28px rgba(23,204,53,0.06);
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 14px;
      width: min(640px, calc(100% - 32px));
      box-sizing: border-box;
      opacity: 0;
      transform: translateY(10px) scale(.98);
      animation: popIn 0.46s cubic-bezier(.2,.9,.2,1) 0.06s both;
    }
    .success-icon {
      width: 48px;
      height: 48px;
      flex: 0 0 48px;
      display: block;
    }
    .success-text {
      color: #17cc35;
      font-size: 15px;
      font-weight: 600;
      text-align: left;
      line-height: 1.5;
      word-break: break-word;
      flex: 1 1 auto;
      min-width: 0;
      opacity: 1;
    }
    /* secondary line (redirect message) */
    .success-text + .success-text {
      color: #666;
      font-weight: 400;
      font-size: 13px;
      margin-top: 6px;
      text-align: left;
    }
    /* responsive: stack and center on narrow screens */
    @media (max-width: 520px) {
      .success-note {
        flex-direction: column;
        align-items: center;
        padding: 16px;
        gap: 10px;
      }
      .success-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        margin-bottom: 2px;
      }
      .success-text, .success-text + .success-text {
        text-align: center;
        font-size: 14px;
      }
    }
    @media (max-width: 360px) {
      .success-note {
        padding: 12px;
        gap: 8px;
      }
      .success-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
      }
      .success-text { font-size: 13px; }
    }

    /* entry animations */
    @keyframes fadeScaleIn {
      0% { opacity: 0; transform: scale(.985); }
      60% { opacity: 1; transform: scale(1.02); }
      100% { opacity: 1; transform: scale(1); }
    }
    @keyframes popIn {
      0% { opacity: 0; transform: translateY(10px) scale(.98); }
      60% { opacity: 1; transform: translateY(-6px) scale(1.02); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <?php if (!empty($success_message)) : ?>
      <div class="success-note-container active">
        <div class="success-note">
          <svg class="success-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#e6ffe6"/><path d="M7 13l3 3 7-7" stroke="#17cc35" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <div class="success-text">
            Password changed successfully!<br>All users will be logged out.<br>Redirecting to login...
          </div>
        </div>
      </div>
    <?php else: ?>
    <div class="auth-box" id="changePasswordForm">
      <h2 class="auth-title">Change Admin Password</h2>
      <div class="terminal-welcome"></div>
      <div class="php-error-message" id="phpErrorMessage">
        <?php echo $error_message; ?>
      </div>
      <form id="changePasswordFormElement" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
          <label class="form-label">secret key</label>
          <input type="password" class="form-input" id="secretKey" name="secret_key" placeholder="enter secret key..." required>
        </div>
        <div class="form-group">
          <label class="form-label">new password</label>
          <input type="password" class="form-input" id="newPassword" name="new_password" placeholder="enter new password..." required>
        </div>
        <button type="submit" class="form-button" id="submitButton">Change Password</button>
      </form>
      <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Changing password...</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <script>
    // DOM Elements
    const form = document.getElementById('changePasswordFormElement');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const secretKeyInput = document.getElementById('secretKey');
    
    // Show loading animation on form submit
    form.addEventListener('submit', function(e) {
      loadingOverlay.classList.add('active');
    });
    
    // Focus on secret key field on load
    window.addEventListener('load', function() {
      setTimeout(() => {
        secretKeyInput.focus();
      }, 500);
    });
    
    // Show/hide success note container
    window.addEventListener('DOMContentLoaded', function() {
      var successNote = document.querySelector('.success-note-container');
      if (successNote && successNote.classList.contains('active')) {
        setTimeout(function() {
          successNote.style.opacity = 0;
        }, 1800);
      }
    });
  </script>
</body>
</html>
