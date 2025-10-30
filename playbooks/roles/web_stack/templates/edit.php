<?php
if (!is_admin()) {
    http_response_code(403);
    echo '<h2>Toegang geweigerd</h2><div class="muted">Alleen admins mogen pagina\'s bewerken of maken.</div>';
    return;
}

$edit_page = ($action === 'edit') ? $page : '';
$new_page_title = $_POST['new_page'] ?? '';

if ($action === 'create' && !$new_page_title) {
    ?>
    <h2 class="page-title">Nieuwe pagina maken</h2>
    <form method="POST" action="?action=create">
      <label for="new_page">Titel nieuwe pagina</label>
      <input type="text" id="new_page" name="new_page" required>
      <div style="margin-top:8px">
        <button class="btn" type="submit">Ga verder</button>
      </div>
    </form>
    <?php
    return; 
}

if ($action === 'create') $edit_page = $new_page_title;

$content = '';
$conn = db_connect();
if ($conn && $edit_page && $stmt = $conn->prepare('SELECT content FROM pages WHERE title=? LIMIT 1')) {
    $stmt->bind_param('s', $edit_page);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $content = $row['content'];
    $stmt->close();
    $conn->close();
}
?>
<h2 class="page-title"><?= h($edit_page) ?></h2>
<form method="POST" action="?action=save">
  <input type="hidden" name="page" value="<?= h($edit_page) ?>">
  <label for="content">Inhoud</label>
  <textarea id="content" name="content" rows="12" style="width:98%; resize:vertical;"><?= h($content) ?></textarea>
  <div style="margin-top:8px">
    <button class="btn" type="submit"><?= $content ? 'Opslaan' : 'Creëer' ?></button>
    <?php if ($action === 'edit' && $edit_page !== 'Home'): ?>
      <a href="?action=delete&page=<?= rawurlencode($edit_page) ?>" class="btn btn-delete" onclick="return confirm('Weet je zeker dat je deze pagina wilt verwijderen?')">Verwijder</a>
    <?php endif; ?>
  </div>
</form>
