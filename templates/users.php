<?php
$title = 'Chat App - Users';
$scripts = ['assets/javascript/users.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper">
    <section class="users">
      <header>
        <div class="content">
          <?php if (!empty($row)): ?>
          <img src="data/user/<?php echo htmlspecialchars($row['img']); ?>" alt="">
          <div class="details">
            <span><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></span>
            <p><?php echo htmlspecialchars($row['status']); ?></p>
          </div>
          <?php endif; ?>
        </div>
        <a href="logout/<?php echo htmlspecialchars($row['unique_id']); ?>" class="logout logout-btn">Logout</a>
      </header>
      <div class="search">
        <input type="text" placeholder="Enter name to search...">
        <button><i class="fas fa-search"></i></button>
      </div>
      <div class="users-list">

      </div>
    </section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
