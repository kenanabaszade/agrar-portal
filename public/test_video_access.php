<?php
// Test video file access
$videoPath = 'storage/trainings/videos/pvcfHiYqt9D6OHoXp4kNmZ8JDyWnxBAfILVBIHH1.mp4';
$fullPath = __DIR__ . '/' . $videoPath;

echo "<h2>Video File Access Test</h2>";
echo "<p><strong>File Path:</strong> $videoPath</p>";
echo "<p><strong>Full Path:</strong> $fullPath</p>";
echo "<p><strong>File Exists:</strong> " . (file_exists($fullPath) ? 'Yes' : 'No') . "</p>";

if (file_exists($fullPath)) {
    $fileSize = filesize($fullPath);
    echo "<p><strong>File Size:</strong> " . round($fileSize / 1024 / 1024, 2) . " MB</p>";
    echo "<p><strong>File Permissions:</strong> " . substr(sprintf('%o', fileperms($fullPath)), -4) . "</p>";
    
    echo "<h3>Direct Access Test</h3>";
    echo "<p><a href='$videoPath' target='_blank'>Click here to access the video directly</a></p>";
    
    echo "<h3>Video Player Test</h3>";
    echo "<video width='400' controls>";
    echo "<source src='$videoPath' type='video/mp4'>";
    echo "Your browser does not support the video tag.";
    echo "</video>";
} else {
    echo "<p style='color: red;'>File not found!</p>";
}
