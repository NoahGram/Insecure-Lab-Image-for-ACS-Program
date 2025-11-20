<?php
// Path Traversal Vulnerability - TESTING ONLY
echo "<h1>File Viewer</h1>";
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    // VULNERABLE: No path validation
    if (file_exists($file)) {
        echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
    } else {
        echo "File not found";
    }
}
?>
<h2>View File</h2>
<form method="GET">
    <label>File: <input type="text" name="file" value="info.php"></label>
    <input type="submit" value="View">
</form>
<p>Try: <code>../../../etc/passwd</code></p>