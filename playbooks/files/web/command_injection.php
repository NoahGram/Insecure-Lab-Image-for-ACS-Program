<?php
// Command Injection Vulnerability - TESTING ONLY
echo "<h1>Command Injection Test</h1>";
if (isset($_POST['host'])) {
    $host = $_POST['host'];
    // VULNERABLE: No input validation
    $output = shell_exec('ping -c 1 ' . $host);
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}
?>
<h2>Network Ping Tool</h2>
<form method="POST">
    <label>Host: <input type="text" name="host" value="127.0.0.1"></label>
    <input type="submit" value="Ping">
</form>
<p>Try: <code>127.0.0.1; ls -la</code></p>