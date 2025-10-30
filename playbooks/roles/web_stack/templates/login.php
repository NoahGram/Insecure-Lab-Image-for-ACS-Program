<h2 class="page-title">Login</h2>
<div class="login-box">
  <?php if(!empty($login_error)) echo '<div class="muted" style="color:#a33;margin-bottom:8px">'.h($login_error).'</div>'; ?>
  <form method="POST" action="?action=login">
    <label for="username">Gebruikersnaam</label>
    <input id="username" name="username" type="text" required>
    <label for="password" style="margin-top:8px">Wachtwoord</label>
    <input id="password" name="password" type="password" required>
    <div style="margin-top:8px">
      <button class="btn" type="submit">Log in</button>
      <a href="?page=Home" class="small" style="margin-left:8px">Annuleer</a>
    </div>
  </form>
</div>
