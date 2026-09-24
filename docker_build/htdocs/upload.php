<!-- <!DOCTYPE html> -->
<?php
//Upload a file ---------------------------------------------------------------
// Define configuration rules
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_SIZE', 2 * 1024 * 1024); // 2 Megabytes
$allowed_extensions = ['csv', 'txt'];
$message = '';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $file = $_FILES['uploaded_file'];

    // 1. Check for standard PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed with error code: " . $file['error'];
    } else {
        // Gather file properties
        $file_name = basename($file['name']);
        $file_size = $file['size'];
        $file_tmp  = $file['tmp_name'];
        
        // Extract extension safely
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 2. Validate file extension
        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "Error: Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
        }
        // 3. Validate file size limit
        elseif ($file_size > MAX_SIZE) {
            $message = "Error: File size exceeds the 2MB limit.";
        }
        // 4. Finalize upload securely
        else {
            // Sanitize filename to prevent directory traversal or collisions
            $safe_name = preg_replace("/[^A-Za-z0-9.]/", "_", pathinfo($file_name, PATHINFO_FILENAME));
            $final_name = time() . "_" . $safe_name . "." . $file_ext;
            $target_path = UPLOAD_DIR . $final_name;

            // Move the file from temp storage to destination
            if (move_uploaded_file($file_tmp, $target_path)) {
                $message = "Success! File uploaded as: " . htmlspecialchars($final_name);
            } else {
                $message = "Error: Could not save the file. Check folder permissions.";
            }
        }
    }
}
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP File Upload Page</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f9f9f9; }
        .container { max-width: 500px; background: #fff; padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .msg { padding: 10px; margin-bottom: 10px; border-radius: 3px; font-weight: bold; background: #e2e2e2; }
    </style>
</head>
<body>

<!-- Upload a file---------------------------------------------------------- -->
<div class="container">
    <h2>Upload a File</h2>
    
    <!-- Display feedback to the user -->
    <?php if (!empty($message)): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Form configuration requires POST method and multipart/form-data enctype -->
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <label for="fileSelect">Select File:</label>
        <p><input type="file" name="uploaded_file" id="fileSelect" required></p>
        <button type="submit">Upload Now</button>
    </form>
</div>


</body>
</html>

