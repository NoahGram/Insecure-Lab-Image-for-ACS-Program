<?php
require_once __DIR__ . '/includes/actions.php';
?>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="wrap">
  <header>
    <div class="brand">
      <div class="logo">LW</div>
      <div>
        <h1>LabSys Wiki — Internal Knowledgebase</h1>
        <div class="lead">Documentation • How-tos • Diagnostics</div>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <?php if (!empty($_SESSION['username'])): ?>
        <div class="small muted">Logged in as <strong><?= h($_SESSION['username']); ?></strong>
          (<?= h($_SESSION['role'] ?? 'user'); ?>)</div>
        <form method="POST" action="?action=logout" style="display:inline; margin:0;">
          <?= csrf_field() ?>
          <button class="btn" type="submit">Log out</button>
        </form>
      <?php else: ?>
        <a class="btn" href="?action=login">Log in</a>
        <a class="btn" href="?action=register">Register</a>
      <?php endif; ?>
    </div>
  </header>



  <main class="card">
    <?php
    if (empty($_SESSION['username'])) {
      if (!in_array($action, ['login', 'register', 'profile']) && !($action === 'view' && $page === 'Home')) {
        echo "<div class='small muted'>Please log in to view pages.</div>";
        $action = 'view';
        $page = 'Home';
      }
    }

    if (!empty($_SESSION['username']) && $action !== 'login' && $action !== 'register') {
      include 'templates/toolbar.php';
    }

    if ($action === 'login')
      include 'templates/login.php';
    elseif ($action === 'register')
      include 'templates/register.php';
    elseif ($action === 'view')
      include 'templates/view.php';
    elseif (in_array($action, ['edit', 'create']))
      include 'templates/edit.php';
    elseif ($action === 'search')
      include 'templates/search.php';
    elseif ($action === 'profile')
      include 'templates/profile.php';
    elseif ($action === 'logs')
      include 'templates/logs.php';
    elseif ($action === 'admin_diagnostics')
      include 'templates/diagnostics.php';
    ?>
  </main>

  <?php include 'templates/sidebar.php'; ?>
  <?php include 'templates/footer.php'; ?>
</div>