<?php
$title = 'Chat App - Login';
$scripts = ['assets/javascript/pass-show-hide.js','assets/javascript/login.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper-entry">
    <section class="form login">
      <header>Chat App</header>
      <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="error-text"></div>
        <div id="resend-verify-wrap" style="display:none;margin-bottom:10px;text-align:center;">
          <button type="button" id="resend-verify-btn" style="background:none;border:1px solid #0969da;color:#0969da;border-radius:6px;padding:6px 16px;font-size:13px;cursor:pointer;">
            Resend verification email
          </button>
          <span id="resend-verify-msg" style="display:none;font-size:13px;color:#1a7f37;margin-left:8px;"></span>
        </div>
        <div class="field input">
          <label for="email">Email Address</label>
          <input type="text" name="email" id="email" placeholder="Enter your email" required>
        </div>
        <div class="field input">
          <labe for="password">Password</label>
          <input type="password" name="password" id="password" placeholder="Enter your password" required>
        </div>
        <div class="field button">
          <input type="submit" id="login-btn" name="submit" value="Login">
        </div>
      </form>
      <div class="form-link">Have no account <a href="signup">Signup</a></div>
    </section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
