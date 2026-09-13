<?php
// Simple user settings form
$title = 'User Settings';
$scripts = ['assets/javascript/user.js', 'assets/javascript/user-keys.js'];
include __DIR__ . '/layout_header.php';
?>
<body>
  <div class="wrapper wrapper-entry wrapper-user-settings">
    <section class="form user form-user-settings">
      <header>Account settings</header>
      <div class="error-text mt1"></div>
      <form enctype="multipart/form-data">
        <div class="image">
          <img src="data/user/<?php echo htmlspecialchars($me['img']); ?>" alt="avatar" style="width:96px;height:96px;border-radius:8px;object-fit:cover;margin-bottom:8px;">
          <input type="file" name="avatar" class="file-native" accept="image/png,image/jpeg">
        </div>
        <div class="input">
          <label>First name</label>
          <input type="text" name="fname" value="<?php echo htmlspecialchars($me['fname']); ?>">
        </div>
        <div class="input">
          <label>Last name</label>
          <input type="text" name="lname" value="<?php echo htmlspecialchars($me['lname']); ?>">
        </div>
        <div class="input">
          <label>Email</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($me['email']); ?>">
        </div>
        <div class="input">
          <label>New password (leave empty to keep current)</label>
          <input type="password" name="password">
        </div>
        <div class="input">
          <label>Message in Pic Password</label>
          <input type="password" name="MsgPicPassword" id="MsgPicPassword" value="<?php echo '';// do not prefill password ?>">
        </div>
        <div class="input mb1">
          <label>Message in Pic Password Period</label>
          <select name="MsgPicPasswordPeriod" id="MsgPicPasswordPeriod" style="padding: 3px 7px;cursor: pointer;">
            <option value="86400" <?php echo (isset($msgPic['active']) && (int)$msgPic['active']===86400)?'selected':''; ?>>Active - 1 Day</option>
            <option value="172800" <?php echo (isset($msgPic['active']) && (int)$msgPic['active']===172800)?'selected':''; ?>>Active - 2 Days</option>
            <option value="259200" <?php echo (isset($msgPic['active']) && (int)$msgPic['active']===259200)?'selected':''; ?>>Active - 3 Days</option>
            <option value="432000" <?php echo (isset($msgPic['active']) && (int)$msgPic['active']===432000)?'selected':''; ?>>Active - 5 Days</option>
            <option value="604800" <?php echo (isset($msgPic['active']) && (int)$msgPic['active']===604800)?'selected':''; ?>>Active - 7 Days</option>
          </select>
        </div>
        <div class="input mb2">
          <label>Private Chat Active Period Message</label>
          <select name="PrivateChatActivePeriod" id="PrivateChatActivePeriod" style="padding: 3px 7px;cursor: pointer;">
            <?php
            $pcPeriods = [
              300        => '5 minutes',
              600        => '10 minutes',
              1800       => '30 minutes',
              3600       => '1 hour',
              7200       => '2 hours',
              10800      => '3 hours',
              18000      => '5 hours',
              28800      => '8 hours',
              43200      => '12 hours',
              86400      => '1 day',
              172800     => '2 days',
              259200     => '3 days',
              432000     => '5 days',
              604800     => '7 days',
            ];
            foreach ($pcPeriods as $secs => $label):
              $sel = (isset($privateChatActive['Active']) && (int)$privateChatActive['Active'] === $secs) ? 'selected' : '';
            ?>
              <option value="<?php echo $secs; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field button mt1">
          <input id="save-user-btn" type="submit" value="Save changes">
          <a href="chat" style="margin-left:12px;">Back to chat</a>
        </div>
        <hr style="margin:16px 0;" />
        <div class="input">
          <label>End-to-end key management</label>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" id="export-private-key" class="button base-button">Export private key</button>
            <input type="file" id="import-private-key-file" accept=".txt,.b64,text/plain" style="display:none;">
            <button type="button" id="import-private-key-btn" class="base-button" class="button base-button">Import private key</button>
            <button type="button" id="generate-keys" class="base-button">Generate keys</button>
          </div>
          <p style="font-size:12px;color:#666;margin-top:8px;">Export your private key to import it on another device. Keep it secret.</p>
        </div>
      </form>
    </section>
  </div>
<?php include __DIR__ . '/layout_footer.php'; ?>