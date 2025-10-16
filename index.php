<?php
session_start();

// Check login status
if (
    !isset($_SESSION["ghostlan_admin"]) ||
    $_SESSION["ghostlan_admin"] !== true
) {
    header("Location: admin.php");
    exit();
}

// Check if password was changed by comparing timestamps
$current_timestamp = file_get_contents("session.txt");
if (
    !isset($_SESSION["login_time"]) ||
    $_SESSION["login_time"] < $current_timestamp
) {
    session_destroy();
    header("Location: admin.php");
    exit();
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
      position: fixed;
      width: 100%;
    }

    .app-container {
      display: flex;
      flex-direction: column;
      height: 100vh;
      width: 100%;
      background-color: #fff;
      position: relative;
      border: 2px solid #000000;
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
      user-select: none;
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

    .clear-button {
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
      min-width: 80px; /* Ensures both buttons have the same width */
      box-sizing: border-box;
      text-align: center;
      display: inline-block;
      user-select: none;
    }

    .clear-button:hover {
      background-color: #000000;
      color: #fff;
    }

    .clear-button:active {
      transform: scale(0.95);
    }
    .header button:not(:last-child) {
  margin-right: 8px;
}

    /* Chat Container - Enhanced scrolling with more bottom padding */
    #chat-box {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 20px 20px 80px 20px; /* Increased bottom padding significantly */
      background: #fff;
      -webkit-overflow-scrolling: touch;
      position: relative;
      scroll-behavior: smooth;
    }

    /* Message Container */
    .message-container {
      margin-bottom: 20px; /* Increased spacing between messages */
      display: flex;
      align-items: flex-start;
      animation: terminalType 0.5s ease;
      font-family: 'JetBrains Mono', monospace;
      font-size: 13px;
    }

    .message-container:last-child {
      margin-bottom: 40px; /* Even more space after last message */
    }

    @keyframes terminalType {
      from { 
        opacity: 0; 
        transform: translateX(-10px); 
      }
      to { 
        opacity: 1; 
        transform: translateX(0); 
      }
    }

    .message-container.sent {
      justify-content: flex-start;
    }

    .message-container.received {
      justify-content: flex-start;
    }

    /* Avatar Styles */
    .message-avatar {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 400;
      font-size: 12px;
      margin-right: 10px;
      color: #555;
      min-width: 24px;
    }

    .received .message-avatar {
      order: 1;
    }

    .sent .message-avatar {
      order: 1;
    }

    /* Message Bubble */
    .message-bubble {
      max-width: calc(100% - 40px);
      padding: 0;
      border-radius: 0;
      position: relative;
      word-wrap: break-word;
      animation: none;
    }

    .sent .message-bubble {
      background: transparent;
      color: #000000;
      order: 2;
    }

    .received .message-bubble {
      background: transparent;
      color: #000000;
      order: 2;
    }

    /* Message Content */
    .message-content {
      font-size: 13px;
      line-height: 1.6;
      margin-bottom: 4px;
    }

    .message-content::before {
      color: #666;
      margin-right: 8px;
    }

    .sent .message-content::before {
      content: "> ";
    }

    .received .message-content::before {
      content: "< ";
    }

    .message-info {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 2px;
    }

    .message-sender {
      font-size: 11px;
      font-weight: 400;
      color: #666;
    }

    .message-time {
      font-size: 11px;
      color: #888;
      font-weight: 400;
    }

    .message-sender::before {
      content: "[";
      color: #aaa;
    }

    .message-sender::after {
      content: "]";
      color: #aaa;
    }

    .message-time::before {
      content: "(";
      color: #aaa;
    }

    .message-time::after {
      content: ")";
      color: #aaa;
    }

    .sent .message-info {
      justify-content: flex-start;
    }

    .received .message-info {
      justify-content: flex-start;
    }

    /* Empty Chat State */
    .empty-chat {
      text-align: center;
      color: #555;
      padding: 60px 20px;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 80%;
    }

    .empty-chat .chat-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 20px;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 0;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .empty-chat svg {
      width: 30px;
      height: 30px;
      fill: #aaa;
    }

    .empty-chat h3 {
      font-size: 16px;
      font-weight: 400;
      margin-bottom: 8px;
      color: #333;
    }

    .empty-chat h3::before {
      content: "$ ";
    }

    .empty-chat p {
      font-size: 12px;
      line-height: 1.5;
      color: #666;
    }

    /* Message Input Area */
    .input-area {
      background-color: #f5f5f5;
      padding: 16px 20px;
      border-top: 1px solid #ddd;
      position: sticky;
      bottom: 0;
      width: 100%;
      z-index: 100;
    }

    .input-section {
      margin-bottom: 12px;
    }

    .input-label {
      font-size: 11px;
      color: #666;
      margin-bottom: 6px;
      font-weight: 400;
      display: block;
    }

    .input-label::before {
      content: "$ ";
    }

    .name-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    #nameInput {
      width: 100%;
      padding: 8px 12px 8px 20px;
      border-radius: 0;
      border: 1px solid #ccc;
      background-color: #fff;
      color: #000000;
      font-size: 13px;
      font-family: 'JetBrains Mono', monospace;
      transition: all 0.2s ease;
      user-select: none;
    }

    .name-icon {
      position: absolute;
      left: 6px;
      width: 12px;
      height: 12px;
      fill: #666;
      z-index: 10;
    }

    .message-input-container {
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
    }

    #messageInput {
      flex: 1;
      padding: 8px 12px;
      border-radius: 0;
      border: 1px solid #ccc;
      background-color: #fff;
      color: #000000;
      font-size: 13px;
      font-family: 'JetBrains Mono', monospace;
      transition: all 0.2s ease;
      user-select: none;
    }

    input:focus {
      outline: none;
      border-color: #000000;
      background-color: #fff;
    }

    input::placeholder {
      color: #aaa;
      font-style: italic;
    }

    #sendButton {
      width: auto;
      min-width: 80px;
      height: 32px;
      border-radius: 0;
      background: transparent;
      color: #000;
      display: inline-block;
      align-items: center;
      justify-content: center;
      border: 1px solid #000;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'JetBrains Mono', monospace;
      font-size: 12px;
      box-shadow: none;
      margin-left: 0;
      padding: 6px 12px;
      font-weight: 400;
      text-align: center;
      user-select: none;
    }

    #sendButton:disabled {
      color: #aaa;
      border-color: #aaa;
      background: #fff;
      cursor: not-allowed;
    }

    #sendButton:hover, #sendButton:focus {
      background-color: #000;
      color: #fff;
      border-color: #000;
      outline: none;
    }

    #sendButton:active {
      transform: scale(0.95);
    }

    /* Terminal cursor animation */
    .terminal-cursor {
      display: inline-block;
      background-color:rgb(255, 255, 255);
      width: 8px;
      height: 14px;
      animation: blink 1s step-end infinite;
    }

    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0; }
    }

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(255, 255, 255, 0.6); 
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      user-select: none;
    }

    .modal.active {
      opacity: 1;
      visibility: visible;
    }

    .modal-content {
      background-color: #fff;
      width: 90%;
      max-width: 400px;
      border: 1px solid #000000;
      border-radius: 0;
      overflow: hidden;
      animation: modalSlide 0.3s ease;
    }

    @keyframes modalSlide {
      from { transform: translateY(-30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
      padding: 15px 20px 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .modal-header h3 {
      font-size: 14px;
      font-weight: 400;
      margin: 0;
      color: #007700;
    }

    .modal-header h3::before {
      content: "$ ";
      color: #666;
    }

    .modal-body {
      padding: 15px 20px;
      text-align: left;
      color: #333;
      font-size: 12px;
      line-height: 1.6;
    }

    .modal-footer {
      display: flex;
      border-top: 1px solid #ddd;
    }

    .modal-button {
      flex: 1;
      padding: 12px;
      text-align: center;
      font-size: 12px;
      font-weight: 400;
      background: transparent;
      border: none;
      cursor: pointer;
      transition: background-color 0.2s ease;
      font-family: 'JetBrains Mono', monospace;
    }

    .cancel-button {
      color: #666;
      border-right: 1px solid #ddd;
    }

    .cancel-button:hover {
      background-color: #17cc35;
      color: #ffffff;
    }

    .confirm-button {
      color: #cc0000;
    }

    .confirm-button:hover {
      background-color: #cc0000;
      color: #fff;
    }

    /* Desktop specific styles */
    @media (min-width: 768px) {
      .app-container {
        max-width: 800px;
        height: 90vh;
        margin: 5vh auto;
        border-radius: 0;
        overflow: hidden;
      }

      .header {
        border-radius: 0;
      }

      .input-area {
        border-radius: 0;
      }

      #chat-box {
        padding: 25px 25px 80px 25px; /* Increased bottom padding for desktop too */
      }
    }

    /* Mobile specific styles for better scrolling */
    @media (max-width: 767px) {
      /* keep original mobile padding */
      #chat-box {
        padding: 20px 20px 100px 20px; /* Even more bottom padding on mobile */
      }
      
      .message-container:last-child {
        margin-bottom: 60px; /* Extra space after last message on mobile */
      }

      /* MOBILE FIXES: allow native scrolling inside chat without touching desktop layout */
      /* allow page to scroll normally (remove page-fixed behavior only on mobile) */
      body {
        position: relative;    /* override fixed that blocks mobile scrolling */
        overflow: auto;
        min-height: 100vh;
        -webkit-overflow-scrolling: touch;
      }

      /* make the app container fill viewport so chat box can be sized reliably */
      .app-container {
        height: 100vh;
      }

      /* Constrain chat-box height to visible viewport minus header+input area.
         This ensures touch scrolling inside #chat-box works even when keyboard shows. */
      #chat-box {
        touch-action: pan-y;
        -webkit-overflow-scrolling: touch;
        max-height: calc(100vh - 112px); /* adjust 112px if your header+input height differs */
        overflow-y: auto;
      }
    }

    /* Enhanced Scrollbar Styling */
    #chat-box::-webkit-scrollbar {
      width: 8px;
    }

    #chat-box::-webkit-scrollbar-track {
      background: #f5f5f5;
      border-radius: 0;
    }

    #chat-box::-webkit-scrollbar-thumb {
      background: #aaa;
      border-radius: 0;
      border: 1px solid #ddd;
    }

    #chat-box::-webkit-scrollbar-thumb:hover {
      background: #888;
    }

    #chat-box::-webkit-scrollbar-thumb:active {
      background: #666;
    }

    /* For Firefox */
    #chat-box {
      scrollbar-width: thin;
      scrollbar-color: #aaa #f5f5f5;
    }

    /* Loading animation for messages */
    .loading-message {
      color: #666;
      font-style: italic;
    }

    .loading-message::after {
      content: '';
      display: inline-block;
      width: 8px;
      height: 14px;
      background: #666;
      animation: blink 1s step-end infinite;
      margin-left: 4px;
    }

    /* Terminal welcome message styling */
    .terminal-welcome {
      color: #666;
      font-size: 11px;
      margin-bottom: 10px;
      border-left: 2px solid #ccc;
      padding-left: 10px;
    }

    /* Scroll to bottom indicator */
    .scroll-indicator {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background: #007700;
      color: white;
      padding: 8px 12px;
      border-radius: 20px;
      font-size: 11px;
      cursor: pointer;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      z-index: 50;
      border: 1px solid #005500;
    }

    .scroll-indicator.visible {
      opacity: 1;
      visibility: visible;
    }

    .scroll-indicator:hover {
      background: #005500;
    }

    .header, .header *, .auth-footer {
  user-select: none;
}

.header img[alt="GhostLAN ghost logo"] {
  pointer-events: none;
  -webkit-user-drag: none;
  user-drag: none;
}

.input-label,
#nameInput,
#messageInput,
#sendButton {
  user-select: none;
}

/* Drop overlay for drag & drop uploads */
.drop-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.15s ease, background-color 0.15s ease;
  z-index: 40;
  font-family: 'JetBrains Mono', monospace;
  font-size: 14px;
  color: #004400;
}
.drop-overlay.visible {
  pointer-events: auto;
  opacity: 1;
  background-color: rgba(0, 119, 0, 0.06);
}
/* Add slight highlight when dragging over chat */
#chat-box.drag-over {
  outline: 2px dashed rgba(0,119,0,0.6);
}
  </style>
</head>
<body>
  <div class="app-container">
    <div class="header">
      <img src="ghost.png" alt="GhostLAN ghost logo" style="height:28px;width:28px;margin-right:10px;vertical-align:middle;display:inline-block;">
  <h1 style="display:inline-block;vertical-align:middle;margin:0;font-size:18px;font-weight:400;">GhostLAN</h1>
      <button class="clear-button" id="clearButton">Clear</button>
       <button class="clear-button" onclick="logout()">Logout</button>
    </div>

    <div id="chat-box">
      <!-- Drop overlay element for drag & drop -->
      <div class="drop-overlay" id="dropOverlay">Drop files here to send</div>

      <!-- Scroll to bottom indicator -->
      <div class="scroll-indicator" id="scrollIndicator">
        ↓ New messages
      </div>
    </div>

    <div class="input-area">
      <div class="input-section">
        <label class="input-label">Developed by @Finnapple</label>
        <div class="name-input-wrapper" style="position: relative;">
          <svg class="name-icon" viewBox="0 0 24 24">
            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 4V6L21 9ZM15 10.26L21 7.26V9.26L15 12.26V10.26ZM12 7C16.42 7 20 8.79 20 11V13C20 15.21 16.42 17 12 17S4 15.21 4 13V11C4 8.79 7.58 7 12 7Z"/>
          </svg>
          <input type="text" id="nameInput" placeholder="enter username..." required>
          <!-- Upload button at the far right -->
          <form id="uploadForm" enctype="multipart/form-data" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); display: flex; align-items: center; gap: 0;">
            <input type="file" id="fileInput" name="files[]" multiple style="display:none;" />
            <button type="button" id="uploadButton" style="background:transparent;border:none;cursor:pointer;padding:0 8px;outline:none;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#007700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </button>
          </form>
        </div>
      </div>
      
      <form id="chatForm" class="message-input-container">
        <input type="text" id="messageInput" placeholder="send message..." required autocomplete="off">
        <button type="submit" id="sendButton">Send</button>
      </form>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal" id="confirmModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Clear All Messages</h3>
      </div>
      <div class="modal-body">
        <p>WARNING: You are about to permanently delete all message history.</p>
        <p>This operation cannot be undone.</p>
        <label for="secretInput" style="display:block;margin-top:10px;">Enter secret key to confirm:</label>
        <input type="password" id="secretInput" autocomplete="off" style="width:100%;margin-top:5px;padding:6px;font-family:'JetBrains Mono',monospace;" />
        <div id="secretError" style="color:#b00;font-size:12px;display:none;margin-top:5px;text-align:center;">Invalid secret key.</div>
      </div>
      <div class="modal-footer">
        <button class="modal-button cancel-button" id="cancelClear">[n] Cancel</button>
        <button class="modal-button confirm-button" id="confirmClear" disabled>[y] Delete</button>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal" id="logoutModal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Confirm Logout</h3>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to logout?</p>
      </div>
      <div class="modal-footer">
        <button class="modal-button cancel-button" id="logoutCancel">No</button>
        <button class="modal-button confirm-button" id="logoutConfirm">Yes</button>
      </div>
    </div>
  </div>

  <script>
    // DOM elements
    const chatBox = document.getElementById("chat-box");
    const nameInput = document.getElementById("nameInput");
    const messageInput = document.getElementById("messageInput");
    const sendButton = document.getElementById("sendButton");
    const clearButton = document.getElementById("clearButton");
    const emptyChat = document.getElementById("emptyChat");
    const confirmModal = document.getElementById("confirmModal");
    const cancelClear = document.getElementById("cancelClear");
    const confirmClear = document.getElementById("confirmClear");
    const scrollIndicator = document.getElementById("scrollIndicator");
    const secretInput = document.getElementById("secretInput");
    const secretError = document.getElementById("secretError");

    // Logout modal DOM refs (ensure defined so modal functions work)
    const logoutModal = document.getElementById("logoutModal");
    const logoutConfirmBtn = document.getElementById("logoutConfirm");
    const logoutCancelBtn = document.getElementById("logoutCancel");
    
    // Track if user is at bottom of chat
    let userScrolledUp = false;
    let shouldAutoScroll = true;

    // Enhanced scroll management with better threshold for mobile
    function isAtBottom() {
      // Use adaptive threshold. On mobile use a smaller % of viewport to avoid false positives.
      const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
      const threshold = isMobile ? Math.max(48, Math.round(chatBox.clientHeight * 0.08)) : 200;
      return (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) <= threshold;
    }

    function scrollToBottom(force = false) {
  if (force || shouldAutoScroll) {
    const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    const target = isMobile ? chatBox.scrollHeight : (chatBox.scrollHeight + 50); 

    const start = chatBox.scrollTop;
    const distance = target - start;
    const duration = 500; // animation time in ms (500 = half second)
    const startTime = performance.now();

    function animateScroll(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // ease-out effect 
      const ease = 1 - Math.pow(1 - progress, 3);

      chatBox.scrollTop = start + distance * ease;

      if (progress < 1) {
        requestAnimationFrame(animateScroll);
      } else {
        hideScrollIndicator();
      }
    }

    requestAnimationFrame(animateScroll);
  }
}


    function showScrollIndicator() {
      scrollIndicator.classList.add('visible');
    }

    function hideScrollIndicator() {
      scrollIndicator.classList.remove('visible');
    }
  function logout() {
    // Open logout confirmation modal instead of immediate redirect
    showLogoutModal();
  }

  function showLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.add('active');
    logoutModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    // focus confirm to enable keyboard action
    setTimeout(() => logoutConfirmBtn && logoutConfirmBtn.focus(), 150);
  }

  function closeLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.remove('active');
    logoutModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = 'auto';
  }

 // Close logout modal when clicking outside modal-content
 if (logoutModal) {
   logoutModal.addEventListener('click', function(e) {
     if (e.target === logoutModal) {
       closeLogoutModal();
     }
   });
 }

 // Logout modal buttons
 if (logoutConfirmBtn) {
   logoutConfirmBtn.addEventListener('click', function () {
     window.location.href = 'logout.php';
   });
 }
 if (logoutCancelBtn) {
   logoutCancelBtn.addEventListener('click', function () {
     closeLogoutModal();
   });
 }

    // Monitor scroll position
    chatBox.addEventListener('scroll', function() {
      const atBottom = isAtBottom();
      
      if (atBottom) {
        shouldAutoScroll = true;
        userScrolledUp = false;
        hideScrollIndicator();
      } else {
        shouldAutoScroll = false;
        userScrolledUp = true;
        
        // Show indicator if there are messages and user scrolled up
        const hasMessages = chatBox.querySelectorAll('.message-container').length > 0;
        if (hasMessages && userScrolledUp) {
          showScrollIndicator();
        }
      }
    });

    // Click scroll indicator to go to bottom
    scrollIndicator.addEventListener('click', function() {
      shouldAutoScroll = true;
      userScrolledUp = false;
      scrollToBottom(true);
    });

    // Generate initials from name
    function getInitials(name) {
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
    }

    // Get current time formatted
    function getCurrentTime() {
      const now = new Date();
      return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Create message element
    function createMessageElement(name, message, timestamp, isSent = false) {
      const messageContainer = document.createElement('div');
      messageContainer.className = `message-container ${isSent ? 'sent' : 'received'}`;

      const avatar = document.createElement('div');
      avatar.className = 'message-avatar';
      avatar.textContent = getInitials(name);

      const bubble = document.createElement('div');
      bubble.className = 'message-bubble';

      const content = document.createElement('div');
      content.className = 'message-content';
      content.textContent = message;

      const info = document.createElement('div');
      info.className = 'message-info';

      const sender = document.createElement('span');
      sender.className = 'message-sender';
      sender.textContent = isSent ? 'you' : name.toLowerCase();

      const time = document.createElement('span');
      time.className = 'message-time';
      time.textContent = timestamp || getCurrentTime();

      info.appendChild(sender);
      info.appendChild(time);
      bubble.appendChild(content);
      bubble.appendChild(info);

      messageContainer.appendChild(avatar);
      messageContainer.appendChild(bubble);

      return messageContainer;
    }

    function formatMessages(responseText) {
      if (!responseText.trim()) {
        emptyChat.style.display = "block";
        return "";
      }
      
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = responseText;
      
      const userName = localStorage.getItem("chatName") || '';
      const messageElements = tempDiv.querySelectorAll('.message-line');
      
      if (messageElements.length === 0) {
        emptyChat.style.display = "block";
        return responseText;
      } else {
        emptyChat.style.display = "none";
      }
      
      // Clear the temp div and rebuild with new format
      tempDiv.innerHTML = '';
      
      messageElements.forEach(message => {
        const originalContent = message.innerHTML;
        
        // Extract name, message, and timestamp
        const strongMatch = originalContent.match(/<strong>(.*?)<\/strong>/);
        const nameText = strongMatch ? strongMatch[1] : '';
        
        const timestampMatch = originalContent.match(/<span class="timestamp">(.*?)<\/span>/);
        const timestampText = timestampMatch ? timestampMatch[1] : getCurrentTime();
        
        let messageText = '';
        if (strongMatch) {
          const afterStrong = originalContent.split('</strong>')[1] || '';
          messageText = timestampMatch ? 
            afterStrong.split('<span class="timestamp">')[0] : 
            afterStrong;
        } else {
          messageText = originalContent;
        }
        
        messageText = messageText.trim();
        
        // Determine if sent or received
        const isSent = userName && nameText && nameText.trim() === userName.trim();
        
        // Create new message element
        const newMessage = createMessageElement(nameText, messageText, timestampText, isSent);
        tempDiv.appendChild(newMessage);
      });
      
      // After formatting chat messages, append uploaded files if present
      const uploadsDiv = tempDiv.querySelector('.uploaded-files');
      let uploadsHtml = '';
      if (!uploadsDiv) {
        // If not present, check if responseText contains uploaded files section
        const uploadsMatch = this.responseText.match(/<div class=['"]uploaded-files['"][\s\S]*?<\/div>/);
        if (uploadsMatch) {
          uploadsHtml = uploadsMatch[0];
        }
      } else {
        uploadsHtml = uploadsDiv.outerHTML;
      }
      // Append uploads section after chat messages
      return tempDiv.innerHTML + uploadsHtml;
    }

    function loadChat() {
      const wasAtBottom = isAtBottom();
      const previousMessageCount = chatBox.querySelectorAll('.message-container').length;

      const xhr = new XMLHttpRequest();
      xhr.open("GET", "load.php", true);
      xhr.onload = function () {
        if (this.status === 200) {
          if (!this.responseText.trim()) {
            chatBox.innerHTML = "";
            emptyChat.style.display = "block";
            hideScrollIndicator();
            return;
          }
          
          try {
            const formattedMessages = formatMessages(this.responseText);
            
            if (!formattedMessages || formattedMessages.trim() === "") {
              chatBox.innerHTML = this.responseText;
            } else {
              const tempDiv = document.createElement('div');
              tempDiv.innerHTML = this.responseText;

              // Append only new messages to the chat box
              while (tempDiv.firstChild) {
                chatBox.appendChild(tempDiv.firstChild);
              }

              if (wasAtBottom || shouldAutoScroll) {
                setTimeout(() => {
                  scrollToBottom(true);
                }, 200);
              } else {
                showScrollIndicator();
              }
            }
            
            const currentMessageCount = chatBox.querySelectorAll('.message-container').length;
            
            if (currentMessageCount === 0) {
              emptyChat.style.display = "block";
              hideScrollIndicator();
            } else {
              emptyChat.style.display = "none";
              
                // New messages arrived
                if (currentMessageCount > previousMessageCount) {
                  if (wasAtBottom || shouldAutoScroll) {
                    // User was at bottom, auto-scroll to new messages
                    setTimeout(() => {
                      scrollToBottom(true);
                    }, 200); // Increased delay for better mobile rendering
                  } else {
                    // User was scrolled up, show indicator
                    showScrollIndicator();
                  }
                } else if (wasAtBottom) {
                  // No new messages but user was at bottom
                  setTimeout(() => {
                    scrollToBottom();
                  }, 200);
                }
            }
          } catch (error) {
            console.error("Error formatting messages:", error);
            chatBox.innerHTML = this.responseText;
          }
        }
      };
      xhr.send();
    }

    function loadName() {
      const savedName = localStorage.getItem("chatName");
      if (savedName) {
        nameInput.value = savedName;
      }
    }

    function deleteChat() {
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "delete.php", true);
      xhr.onload = function () {
        if (this.status === 200) {
          shouldAutoScroll = true;
          userScrolledUp = false;
          hideScrollIndicator();
          loadChat();
          closeModal();
        }
      };
      xhr.send();
    }

    function showModal() {
      confirmModal.classList.add('active');
      document.body.style.overflow = 'hidden';
      secretInput.value = '';
      secretError.style.display = 'none';
      confirmClear.disabled = true;
      setTimeout(() => secretInput.focus(), 200);
    }

    function closeModal() {
      confirmModal.classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    // Upload button logic
    const uploadButton = document.getElementById('uploadButton');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');

    // New: drop overlay element
    const dropOverlay = document.getElementById('dropOverlay');

    // Helper: upload FileList or File[] to upload.php
    function uploadFiles(fileList) {
      if (!fileList || fileList.length === 0) return;

      const formData = new FormData();
      for (let i = 0; i < fileList.length; i++) {
        formData.append('files[]', fileList[i]);
      }
      // include uploader name if available
      const savedName = localStorage.getItem("chatName") || "Unknown";
      formData.append('uploader', savedName.trim());

      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'upload.php', true);
      xhr.onload = function () {
        // clear any file input and reload chat
        try { fileInput.value = ''; } catch(e){}
        loadChat();
      };
      xhr.onerror = function() {
        // on error, still attempt to reload chat to reflect changes
        loadChat();
      };
      xhr.send(formData);
    }

    // Existing upload button logic
    uploadButton.addEventListener('click', function(e) {
      fileInput.click();
    });

    fileInput.addEventListener('change', function(e) {
      if (fileInput.files.length > 0) {
        // Use the existing upload flow but send immediately (no alert)
        uploadFiles(fileInput.files);
      }
    });

    // Prevent default behavior for drag events on window so files don't open
    window.addEventListener('dragover', function (e) { e.preventDefault(); }, false);
    window.addEventListener('drop', function (e) { e.preventDefault(); }, false);

    // Drag & drop handlers on the chat box
    chatBox.addEventListener('dragenter', function (e) {
      e.preventDefault();
      e.stopPropagation();
      chatBox.classList.add('drag-over');
      dropOverlay.classList.add('visible');
    }, false);

    chatBox.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'copy';
      // keep overlay visible
      chatBox.classList.add('drag-over');
      dropOverlay.classList.add('visible');
    }, false);

    chatBox.addEventListener('dragleave', function (e) {
      // Only hide if leaving the chat-box bounds
      const rect = chatBox.getBoundingClientRect();
      const x = e.clientX, y = e.clientY;
      if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        chatBox.classList.remove('drag-over');
        dropOverlay.classList.remove('visible');
      }
    }, false);

    chatBox.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      chatBox.classList.remove('drag-over');
      dropOverlay.classList.remove('visible');

      const items = e.dataTransfer.files;
      if (items && items.length > 0) {
        // Upload dropped files
        uploadFiles(items);
      }
    }, false);

    // Event Listeners
    document.getElementById("chatForm").addEventListener("submit", function (e) {
      e.preventDefault();

      const name = nameInput.value.trim();
      const message = messageInput.value.trim();

      if (!name || !message) {
        if (!name) nameInput.focus();
        return;
      }

      localStorage.setItem("chatName", name);

      // Add loading state
      sendButton.textContent = "...";
      sendButton.style.opacity = "0.7";
      messageInput.disabled = true;
      
      // Mark that we should auto-scroll after sending
      shouldAutoScroll = true;
      userScrolledUp = false;
      
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "send.php", true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onload = function () {
        if (this.status === 200) {
          messageInput.value = "";
          messageInput.disabled = false;
          messageInput.focus();
          loadChat();
        }
        sendButton.textContent = "Send";
        sendButton.style.opacity = "1";
      };
      xhr.send("name=" + encodeURIComponent(name) + "&message=" + encodeURIComponent(message));
    });

    nameInput.addEventListener("input", function () {
      localStorage.setItem("chatName", this.value);
    });

 // Remove green color on send button when typing
    messageInput.addEventListener('input', function() {
  if (this.value.length > 0) {
    sendButton.style.color = '';
    sendButton.style.borderColor = '';
    sendButton.style.backgroundColor = '';
  } else {
    sendButton.style.color = '';
    sendButton.style.borderColor = '';
    sendButton.style.backgroundColor = '';
  }
});

    // Secret key validation (AJAX to validate_secret.php)
secretInput.addEventListener('input', function() {
  if (secretInput.value.length === 0) {
    confirmClear.disabled = true;
    secretError.style.display = 'none';
    secretError.textContent = '';
    return;
  }

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'validate_secret.php', true);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const res = JSON.parse(this.responseText);
        confirmClear.disabled = !res.valid;
        if (res.valid) {
          secretError.style.display = 'block';
          secretError.textContent = 'Correct secret key';
          secretError.style.color = 'green';
        } else {
          secretError.style.display = 'block';
          secretError.textContent = 'Invalid secret key';
          secretError.style.color = 'red';
        }
      } catch (e) {
        confirmClear.disabled = true;
        secretError.style.display = 'block';
        secretError.textContent = 'Invalid secret key';
        secretError.style.color = 'red';
      }
    } else {
      confirmClear.disabled = true;
      secretError.style.display = 'block';
      secretError.textContent = 'Invalid secret key';
      secretError.style.color = 'red';
    }
  };
  xhr.send('secretKey=' + encodeURIComponent(secretInput.value));
});

// Allow Enter key to trigger delete if secret is correct
secretInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (confirmClear.disabled) {
      secretError.style.display = 'block';
      secretInput.focus();
      return;
    }
    deleteChat();
  }
});

clearButton.addEventListener("click", showModal);
cancelClear.addEventListener("click", closeModal);
confirmClear.addEventListener("click", function() {
  if (confirmClear.disabled) {
    secretError.style.display = 'block';
    secretInput.focus();
    return;
  }

  // Revalidate the secret key via AJAX before proceeding
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'validate_secret.php', true);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const res = JSON.parse(this.responseText);
        if (!res.valid) {
          secretError.style.display = 'block';
          secretInput.focus();
          return;
        }
        // Proceed with deletion if valid
        deleteChat();
      } catch (e) {
        secretError.style.display = 'block';
        secretInput.focus();
      }
    } else {
      secretError.style.display = 'block';
      secretInput.focus();
    }
  };
  xhr.onerror = function() {
    // Handle network errors
    secretError.style.display = 'block';
    secretInput.focus();
  };
  xhr.send('secretKey=' + encodeURIComponent(secretInput.value));
});
function showModal() {
  confirmModal.classList.add('active');
  document.body.style.overflow = 'hidden';
  secretInput.value = '';
  secretError.style.display = 'none';
  confirmClear.disabled = true;
  setTimeout(() => secretInput.focus(), 200);
}

// Close modal when clicking outside
confirmModal.addEventListener("click", function(e) {
  if (e.target === confirmModal) {
    closeModal();
  }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (confirmModal && confirmModal.classList.contains('active')) {
      closeModal();
    }
    if (logoutModal && logoutModal.classList.contains('active')) {
      closeLogoutModal();
    }
  }
  
  // Press Space or Enter when scroll indicator is visible to scroll to bottom
  if ((e.key === ' ' || e.key === 'Enter') && scrollIndicator.classList.contains('visible')) {
    e.preventDefault();
    shouldAutoScroll = true;
    userScrolledUp = false;
    scrollToBottom(true);
  }
});
    
// Prevent zoom on input focus (iOS)
document.addEventListener('touchstart', function() {}, {passive: true});

// Initialize app
window.addEventListener('load', function() {
  loadChat();
  loadName();
  
  // Smart focus logic
  const savedName = localStorage.getItem("chatName");
  setTimeout(() => {
    if (savedName && savedName.trim() !== "") {
      messageInput.focus();
    } else {
      nameInput.focus();
    }
  }, 300);
  
  // Polling for new messages
  setInterval(loadChat, 2000);
  
  // Initial scroll to bottom
  setTimeout(() => {
    chatBox.scrollTop = chatBox.scrollHeight;
  }, 500);
});    // Handle page visibility change
document.addEventListener('visibilitychange', function() {
  if (!document.hidden) {
    loadChat();
  }
});

// Check session validity every 5 seconds
function checkSession() {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'check_session.php', true);
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (!response.valid) {
          window.location.href = 'logout.php';
        }
      } catch (e) {
        console.error('Error checking session:', e);
      }
    }
  };
  xhr.send();
}

// Start checking session
setInterval(checkSession, 3000);

    // Ensure 'show password' toggle remains visible during mouse interactions
secretInput.addEventListener('focus', function() {
  const showPasswordToggle = document.getElementById('showPasswordToggle');
  if (showPasswordToggle) {
    showPasswordToggle.style.display = 'block';
  }
});

secretInput.addEventListener('blur', function() {
  const showPasswordToggle = document.getElementById('showPasswordToggle');
  if (showPasswordToggle) {
    showPasswordToggle.style.display = 'none';
  }
});
  </script>
</body>

</html>

