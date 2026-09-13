<?php
$title = 'Chat App - Verify Email';
$scripts = [];
include __DIR__ . '/layout_header.php';

$status = $status ?? '';

$messages = [
	''        => ['icon' => '✉️',  'color' => '#555',   'text' => 'Enter your verification code or click the link sent to your email.'],
	'ok'      => ['icon' => '✅',  'color' => '#1a7f37','text' => 'Your email has been verified! Redirecting to chat…'],
	'already' => ['icon' => 'ℹ️',  'color' => '#0969da','text' => 'Your email is already verified. You can log in.'],
	'expired' => ['icon' => '⏰',  'color' => '#9a3a00','text' => 'This verification link has expired (valid for 24 hours). Please sign up again.'],
	'error'   => ['icon' => '❌',  'color' => '#d1242f','text' => 'Invalid or unknown verification link. Please check your email or sign up again.'],
];

$info = $messages[$status] ?? $messages['error'];
?>
<body>
  <div class="wrapper-entry">
	<section class="form login" style="text-align:center;max-width:420px;">
	  <header>Chat App</header>
	  <div style="padding:32px 16px;">
		<div style="font-size:48px;margin-bottom:16px;"><?php echo $info['icon']; ?></div>
		<p style="color:<?php echo $info['color']; ?>;font-size:15px;line-height:1.6;margin:0 0 24px;">
		  <?php echo htmlspecialchars($info['text']); ?>
		</p>
		<?php if ($status === 'ok'): ?>
		  <script>setTimeout(() => { location.href = '<?php echo BASE_PATH; ?>chat'; }, 1500);</script>
		<?php elseif ($status === 'already'): ?>
		  <a href="<?php echo BASE_PATH; ?>login" style="display:inline-block;padding:10px 28px;background:#1a7f37;color:#fff;border-radius:6px;text-decoration:none;font-size:14px;">Go to Login</a>
		<?php else: ?>
		  <a href="<?php echo BASE_PATH; ?>signup" style="display:inline-block;padding:10px 28px;background:#0969da;color:#fff;border-radius:6px;text-decoration:none;font-size:14px;">Back to Signup</a>
		<?php endif; ?>
	  </div>
	</section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
