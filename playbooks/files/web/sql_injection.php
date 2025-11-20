<?php
// SQL Injection Vulnerability - TESTING ONLY
echo "<h1>SQL Injection Test Page</h1>";
if (isset($_POST['username'])) {
    include_once 'includes/db.php';
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // VULNERABLE: Direct query without prepared statements
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "<p>Login successful!</p>";
        while($row = $result->fetch_assoc()) {
            echo "<p>Welcome, " . htmlspecialchars($row['username']) . "</p>";
        }
    } else {
        echo "<p>Login failed!</p>";
    }
    echo "<p>Query: " . htmlspecialchars($query) . "</p>";
}
?>
<h2>Vulnerable Login</h2>
<form method="POST">
    <label>Username: <input type="text" name="username"></label><br>
    <label>Password: <input type="password" name="password"></label><br>
    <input type="submit" value="Login">
</form>
<h3>Try SQL Injection:</h3>
<p><code>admin' OR '1'='1</code></p>
<p><code>'; DROP TABLE users; --</code></p>