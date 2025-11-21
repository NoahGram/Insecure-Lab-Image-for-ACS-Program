<?php
// File Upload Vulnerability - TESTING ONLY
echo "<h1>File Upload Test</h1>";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    // NO FILE TYPE VALIDATION - VULNERABILITY
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "File uploaded: " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"]));
    } else {
        echo "Upload failed";
    }
}
?>
<h2>Upload Any File</h2>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" required>
    <input type="submit" value="Upload" name="submit">
</form>
<h3>Uploaded Files:</h3>
<?php 
if (is_dir('uploads/')) {
    $files = scandir('uploads/'); 
    foreach($files as $file) { 
        if($file != "." && $file != "..") { 
            echo "<a href='uploads/$file'>$file</a><br>"; 
        } 
    } 
}
?>