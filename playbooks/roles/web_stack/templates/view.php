<?php
$conn = db_connect();

echo '<h2 class="page-title">'.h($page).'</h2>';

if ($conn && $stmt = $conn->prepare('SELECT content FROM pages WHERE title=? LIMIT 1')) {
    $stmt->bind_param('s', $page);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo '<div class="content">'.$row['content'].'</div>';
    } else {
        echo '<div class="content muted">Pagina niet gevonden.</div>';
    }

    $stmt->close();
    $conn->close();
}
