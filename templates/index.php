<?php
$title = 'Chat App - Signup';
$scripts = ['assets/javascript/pass-show-hide.js','assets/javascript/signup.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper-entry">
    <section class="form signup">
      <header>Chat App</header>
      <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="error-text"></div>
        <div class="name-details">
          <div class="field input">
            <label>First Name</label>
            <input type="text" name="fname" placeholder="First name" required>
          </div>
          <div class="field input">
            <label>Last Name</label>
            <input type="text" name="lname" placeholder="Last name" required>
          </div>
        </div>
        <div class="field input">
          <label>Email Address</label>
          <input type="text" name="email" placeholder="Enter your email" required>
        </div>
        <div class="field input">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter new password" required>
        </div>
        <div class="field image">
          <label>Select Image</label>
          <input type="file" name="image" class="file-native" accept="image/x-png,image/gif,image/jpeg,image/jpg" required>
        </div>
        <div class="field button">
          <input type="submit" name="submit" id="signup-btn" value="Signup">
        </div>
      </form>
      <div class="form-link">I have account <a href="login">Login</a></div>
    </section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
