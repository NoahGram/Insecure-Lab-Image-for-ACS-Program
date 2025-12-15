<?php
$conn = db_connect();

echo '<h2 class="page-title">'.h($page).'</h2>';

if ($conn && $stmt = $conn->prepare('SELECT content FROM pages WHERE title=? LIMIT 1')) {
    $stmt->bind_param('s', $page);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Check for XSS vulnerability override (if vulnerable mode is enabled)
        if (function_exists('display_page_content_override')) {
            // Vulnerable mode: use the override (no escaping)
            $content = display_page_content_override($row['content']);
        } else {
            // Clean mode: escape HTML to prevent XSS
            $content = h($row['content']);
        }
        echo '<div class="content">'.$content.'</div>';
    } else {
        echo '<div class="content muted">Pagina niet gevonden.</div>';
    }

    $stmt->close();
    $conn->close();
}
