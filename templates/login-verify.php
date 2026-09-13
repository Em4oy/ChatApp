<?php
$title   = 'Chat App - Login Verification';
$scripts = ['assets/javascript/login-verify.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper-entry">
	<section class="form login">
	  <header>Chat App</header>
	  <p style="text-align:center;font-size:14px;color:#555;margin-bottom:16px;">
		A 6-digit verification code has been sent to your email.<br>
		Please enter it below. The code expires in 15&nbsp;minutes.
	  </p>
	  <form id="verify-code-form" action="#" method="POST" autocomplete="off">
		<div class="error-text" id="verify-error" style="display:none;"></div>
		<div class="field input">
		  <label for="login-code">Verification Code</label>
		  <input type="text" id="login-code" name="code"
				 inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
				 placeholder="Enter 6-digit code" required autofocus
				 style="letter-spacing:4px;font-size:18px;text-align:center;">
		</div>
		<div class="field button">
		  <input type="submit" id="verify-btn" value="Verify">
		</div>
	  </form>
	  <div class="form-link" style="text-align:center;margin-top:12px;">
		<a href="login" style="font-size:13px;color:#0969da;">← Back to login</a>
	  </div>
	</section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
