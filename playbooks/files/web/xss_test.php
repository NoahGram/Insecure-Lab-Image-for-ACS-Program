<?php
// XSS Vulnerability - TESTING ONLY
echo "<h1>XSS Test Page</h1>";
if (isset($_GET['name'])) { 
    echo "<p>Hello " . $_GET['name'] . "</p>"; // Unescaped output
}
?>
<h2>Test XSS</h2>
<form method="GET">
    <label>Name: <input type="text" name="name"></label>
    <input type="submit" value="Submit">
</form>
<p>Try: <code>&lt;script&gt;alert('XSS')&lt;/script&gt;</code></p>