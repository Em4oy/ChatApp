<?php
$title = 'Chat App - Chat';
$scripts = ['assets/javascript/users.js', 'assets/javascript/chat.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper">
    <!-- Users sidebar -->
    <section class="users">
      <header>
        <a href="user" class="profile-link" style="text-decoration:none;color:inherit;">
        <div class="content">
          <?php if (!empty($me)): ?>
          <img src="data/user/<?php echo htmlspecialchars($me['img']); ?>" alt="">
          <div class="details">
            <span><?php echo htmlspecialchars($me['fname'] . ' ' . $me['lname']); ?></span>
            <p><?php echo htmlspecialchars($me['status']); ?></p>
            <span id="e2e-status" class="e2e-off" title="End-to-end encryption status" style="display:none">E2E: off</span>
          </div>
          <?php endif; ?>
        </div>
        </a>
        <!--<a href="logout/<?php //echo htmlspecialchars($row['unique_id']); ?>" class="logout logout-btn">Logout</a>-->
        <a href="logout/<?php echo htmlspecialchars($_SESSION['unique_id']); ?>" class="logout logout-btn">Logout</a>
      </header>

      <div class="search">
        <input type="text" placeholder="Search user...">
        <button><i class="fas fa-search"></i></button>
      </div>

      <div class="users-list">
        <!-- populated by assets/javascript/users.js via api/users -->
      </div>
    </section>

    <!-- Chat area -->
    <section class="chat-area">
      <header>
        <?php if (!empty($row)): ?>
        <a href="chat" class="back-icon"><i class="fas fa-close"></i></a>
        <img src="data/user/<?php echo htmlspecialchars($row['img']); ?>" alt="">
        <div class="details">
          <span><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></span>
          <p><?php echo htmlspecialchars($row['status']); ?></p>
        </div>
        <div class="private-chat-toggle" style="margin-left:auto;display:flex;align-items:center;gap:8px;">
          <span style="font-size:13px;white-space:nowrap;">Private chat</span>
          <label class="pc-switch" title="Toggle private chat" style="position:relative;display:inline-block;width:42px;height:22px;cursor:pointer;">
            <input type="checkbox" id="private-chat-switch"
              data-to-user="<?php echo (int)$user_id; ?>"
              <?php echo $privateChatActive ? 'checked' : ''; ?>
              style="opacity:0;width:0;height:0;position:absolute;">
            <span class="pc-slider" style="
              position:absolute;inset:0;border-radius:22px;
              background:<?php echo $privateChatActive ? '#4caf50' : '#ccc'; ?>;
              transition:background .25s;
            ">
              <span class="pc-knob" style="
                position:absolute;top:3px;left:<?php echo $privateChatActive ? '22px' : '3px'; ?>;
                width:16px;height:16px;border-radius:50%;background:#fff;
                transition:left .25s;
              "></span>
            </span>
          </label>
        </div>
        <?php else: ?>
        <div class="details">
          <span>Select a user to start chatting</span>
        </div>
        <?php endif; ?>
      </header>
      <div class="chat-box">

      </div>
      <div id="upload-pic-preview" style="display: none;"></div>
      <!-- caption input present in DOM but hidden until an image is selected -->
      <input type="text" id="upload-pic-msg" name="upload-pic-msg" placeholder="Text in pic...">
      <form action="#" class="typing-area">
        <input type="text" class="incoming_id" name="incoming_id" value="<?php echo htmlspecialchars($user_id ?? ''); ?>" hidden>
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/bmp" style="display:none;" id="chat-image-input">
        <label for="chat-image-input" title="Attach image" style="cursor:pointer;color:var(--muted);">
         <i class="fa-classic fa-regular fa-image" style="font-size: 24px;"></i>
        </label>
        <button type="submit"><i class="fa-classic fa-solid fa-turn-up"></i></button>
      </form>
    </section>
  </div>

<?php include __DIR__ . '/layout_footer.php'; ?>
