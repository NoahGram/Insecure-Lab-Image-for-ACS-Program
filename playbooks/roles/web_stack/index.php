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
          <div class="lead">Documentation • How-tos • Diagnostics — Training Lab</div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <?php if(!empty($_SESSION['username'])): ?>
          <div class="small muted">Ingelogd als <strong><?= h($_SESSION['username']); ?></strong> (<?= h($_SESSION['role'] ?? 'user'); ?>)</div>
          <a class="btn" href="?action=logout">Log uit</a>
        <?php else: ?>
          <a class="btn" href="?action=login">Log in</a>
          <a class="btn" href="?action=register">Register</a>
        <?php endif; ?>
      </div>
    </header>



  <main class="card">
    <?php
      if ($action !== 'login' && $action !== 'register') {
          include 'templates/toolbar.php';
      }
      
      if ($action === 'login') include 'templates/login.php';
      elseif ($action === 'register') include 'templates/register.php';
      elseif ($action === 'view') include 'templates/view.php';
      elseif (in_array($action, ['edit','create'])) include 'templates/edit.php';
      elseif ($action === 'search') include 'templates/search.php';
    ?>
  </main>

  <?php include 'templates/sidebar.php'; ?>
  <?php include 'templates/footer.php'; ?>
</div>
