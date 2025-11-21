<h2 class="page-title">Register</h2>
<div class="login-box">
<?php
// Check if client-side validation should be disabled
$disable_client_validation = is_readable('/var/www/roles/vulnerabilities/web_stack/no_client_side_password_validation.php');
?>
<script src="assets/js/register.js" defer data-disable-validation="<?= $disable_client_validation ? 'true' : 'false' ?>"></script>

  <?php if (!empty($register_error)) : ?>
    <div class="muted" style="color:#a33;margin-bottom:8px"><?= h($register_error) ?></div>
  <?php endif; ?>

  <?php if (!empty($register_success)) : ?>
    <div class="muted" style="color:green;margin-bottom:8px"><?= h($register_success) ?></div>
  <?php endif; ?>

  <form id="registerForm" method="POST" action="?action=register">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" type="text" required>

      <label for="password" style="margin-top:8px">Password</label>
      <input id="password" name="password" type="password" required>

      <label for="password_confirm" style="margin-top:8px">Confirm password</label>
      <input id="password_confirm" name="password_confirm" type="password" required>

      <label for="email" style="margin-top:8px">Email</label>
      <input id="email" name="email" type="email" required>

      <div style="margin-top:8px">
        <button class="btn" type="submit">Register</button>
        <a href="?page=Home" class="small" style="margin-left:8px">Cancel</a>
      </div>
  </form>
</div>
