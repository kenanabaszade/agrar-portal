<?php
// Simple upload test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Upload Test Results</h2>";
    echo "<p>POST Content-Length: " . $_SERVER['CONTENT_LENGTH'] . " bytes (" . round($_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 2) . " MB)</p>";
    echo "<p>PHP post_max_size: " . ini_get('post_max_size') . "</p>";
    echo "<p>PHP upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
    
    if (count($_FILES) > 0) {
        echo "<h3>Uploaded Files:</h3>";
        foreach ($_FILES as $key => $file) {
            echo "<p><strong>$key:</strong> " . $file['name'] . " (" . round($file['size'] / 1024 / 1024, 2) . " MB)</p>";
            echo "<p>Error Code: " . $file['error'] . "</p>";
            if ($file['error'] > 0) {
                $errors = [
                    1 => 'File exceeds upload_max_filesize',
                    2 => 'File exceeds MAX_FILE_SIZE',
                    3 => 'File only partially uploaded',
                    4 => 'No file uploaded',
                    6 => 'Missing temporary folder',
                    7 => 'Failed to write file to disk',
                    8 => 'PHP extension stopped file upload'
                ];
                echo "<p>Error: " . ($errors[$file['error']] ?? 'Unknown error') . "</p>";
            }
        }
    } else {
        echo "<p>No files uploaded</p>";
    }
} else {
    ?>
    <h2>Upload Test</h2>
    <form method="POST" enctype="multipart/form-data">
        <p>Select a file to test upload:</p>
        <input type="file" name="test_file" required>
        <br><br>
        <button type="submit">Upload Test</button>
    </form>
    <?php
}
