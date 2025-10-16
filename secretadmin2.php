<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['ghostlan_admin']) || $_SESSION['ghostlan_admin'] !== true) {
    header('Location: admin.php');
    exit;
}

$error_message = '';
$success_message = '';

// NEW: read current blocked extensions from .htaccess
$current_exts = [];
$htfile = '.htaccess';
if (file_exists($htfile)) {
	$ht = file_get_contents($htfile);
	if (preg_match('/<FilesMatch\s+"\\\\\.\(([^)]+)\)\\$"\s*>/i', $ht, $m)) {
		$raw = $m[1];
		$parts = array_filter(array_map('trim', explode('|', $raw)));
		$current_exts = $parts;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret_key = trim($_POST['secret_key'] ?? '');
    $block_extension = trim($_POST['block_extension'] ?? '');
    // NEW: accept unblock input
    $unblock_extension = trim($_POST['unblock_extension'] ?? '');

    // Validate inputs: require secret key and at least one action
    if (empty($secret_key) || (empty($block_extension) && empty($unblock_extension))) {
        $error_message = "Secret key and at least one of block/unblock are required";
    } else {
        // Read secret key from file
        if (file_exists('secret.txt')) {
            $correct_secret = trim(file_get_contents('secret.txt'));
            
            if ($secret_key === $correct_secret) {
                // load existing htaccess content and current list
                $existing = file_exists($htfile) ? file_get_contents($htfile) : '';
                $merged = $current_exts;

                // helper to parse and sanitize lists
                $parse_list = function($raw) {
                    $parts = preg_split('/[\s,;|]+/', $raw);
                    $out = [];
                    foreach ($parts as $p) {
                        $p = ltrim($p, '.');
                        $clean = strtolower(preg_replace('/[^a-z0-9]/', '', $p));
                        if ($clean !== '') $out[] = $clean;
                    }
                    return array_values(array_unique($out));
                };

                // additions
                if (!empty($block_extension)) {
                    $adds = $parse_list($block_extension);
                    foreach ($adds as $a) {
                        if (!in_array($a, $merged, true)) $merged[] = $a;
                    }
                }

                // removals
                if (!empty($unblock_extension)) {
                    $removes = $parse_list($unblock_extension);
                    if (!empty($removes)) {
                        $merged = array_values(array_diff($merged, $removes));
                    }
                }

                // ensure unique/order
                $merged = array_values(array_unique($merged));

                // build new .htaccess content
                if (empty($merged)) {
                    // remove existing FilesMatch block entirely if present
                    if (preg_match('/<FilesMatch\s+"\\\\\.\(([^)]+)\)\\$"\s*>.*?<\/FilesMatch>\s*/si', $existing)) {
                        $new_content = preg_replace('/<FilesMatch\s+"\\\\\.\(([^)]+)\)\\$"\s*>.*?<\/FilesMatch>\s*/si', '', $existing, 1);
                    } else {
                        $new_content = $existing;
                    }
                } else {
                    $pattern = implode('|', array_map(function($v){ return preg_quote($v, '/'); }, $merged));
                    if (preg_match('/<FilesMatch\s+"\\\\\.\(([^)]+)\)\\$"\s*>/i', $existing)) {
                        $new_header = '<FilesMatch "\\.(' . $pattern . ')$">';
                        $new_content = preg_replace('/<FilesMatch\s+"\\\\\.\(([^)]+)\)\\$"\s*>/i', $new_header, $existing, 1);
                    } else {
                        $append_block = "\n<FilesMatch \"\\.($pattern)$\">\n    Require all denied\n</FilesMatch>\n";
                        $new_content = rtrim($existing) . "\n\n" . $append_block;
                    }
                }

                if (file_put_contents($htfile, $new_content) !== false) {
                    // update current_exts for immediate display
                    $current_exts = $merged;

                    // no logout / no session invalidation
                    $action_parts = [];
                    if (!empty($block_extension)) $action_parts[] = 'added';
                    if (!empty($unblock_extension)) $action_parts[] = 'removed';
                    $success_message = "Extensions updated in .htaccess (" . implode(' & ', $action_parts) . ").";

                    // Redirect back to index (no logout)
                    header("refresh:2;url=index.php");
                } else {
                    $error_message = "Failed to update .htaccess";
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
  <title>GhostLAN - Block Extensions</title>
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
      transition: opacity 0.28s ease, visibility 0.28s ease;
    }
    /* backdrop entry animation + visible state */
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
    /* icon + text sizing */
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
            <?php echo htmlspecialchars($success_message); ?><br>Redirecting to homepage...
          </div>
        </div>
      </div>
    <?php else: ?>
    <div class="auth-box" id="changePasswordForm">
      <h2 class="auth-title">Block File Extensions</h2>
      <!-- SHOW current blocked extensions (no design change) -->
      <div class="terminal-welcome">
        Currently blocked: <?php echo !empty($current_exts) ? htmlspecialchars(implode(', ', $current_exts)) : 'none'; ?>
      </div>
      <div class="php-error-message" id="phpErrorMessage">
        <?php echo $error_message; ?>
      </div>
      <form id="changePasswordFormElement" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
          <label class="form-label">secret key</label>
          <input type="password" class="form-input" id="secretKey" name="secret_key" placeholder="enter secret key..." required>
        </div>
        <div class="form-group">
          <label class="form-label">block extension</label>
          <!-- changed to text (no asterisk) and removed required -->
          <input type="text" class="form-input" id="newPassword" name="block_extension" placeholder="enter extension to block">
        </div>
        <div class="form-group">
          <label class="form-label">unblock extension</label>
          <!-- unblock input unmasked (text) as requested -->
          <input type="text" class="form-input" id="unblockExtension" name="unblock_extension" placeholder="enter extension to unblock">
        </div>
        <button type="submit" class="form-button" id="submitButton">Block/Unblock</button>
      </form>
      <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Please wait...</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <script>
    // DOM Elements
    const form = document.getElementById('changePasswordFormElement');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const secretKeyInput = document.getElementById('secretKey');
    const blockInput = document.getElementById('newPassword');
    const unblockInput = document.getElementById('unblockExtension');
    const submitButton = document.getElementById('submitButton');
    
    // Show loading animation on form submit
    form.addEventListener('submit', function(e) {
      loadingOverlay.classList.add('active');
    });
    
    // Update submit button label based on inputs
    function updateButtonLabel() {
      const blockVal = blockInput.value.trim();
      const unblockVal = unblockInput.value.trim();

      // enforce mutual exclusivity: if one has content, disable the other and clear it
      if (blockVal !== '') {
        unblockInput.value = '';
        unblockInput.disabled = true;
        unblockInput.setAttribute('aria-disabled', 'true');
      } else {
        unblockInput.disabled = false;
        unblockInput.removeAttribute('aria-disabled');
      }

      if (unblockVal !== '') {
        blockInput.value = '';
        blockInput.disabled = true;
        blockInput.setAttribute('aria-disabled', 'true');
      } else {
        blockInput.disabled = false;
        blockInput.removeAttribute('aria-disabled');
      }

      // update button label
      if (blockVal !== '' && unblockVal === '') {
        submitButton.textContent = 'Block';
      } else if (unblockVal !== '' && blockVal === '') {
        submitButton.textContent = 'Unblock';
      } else if (blockVal !== '' && unblockVal !== '') {
        submitButton.textContent = 'Apply';
      } else {
        submitButton.textContent = 'Block/Unblock';
      }
    }

    // listen for input changes
    blockInput.addEventListener('input', updateButtonLabel);
    unblockInput.addEventListener('input', updateButtonLabel);
    // initialize on load
    updateButtonLabel();

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
