<?php
// Script to fix bind_param in guarda.php
$file = 'guarda.php';
$content = file_get_contents($file);

// Fix the bind_param line
$content = str_replace(
    '$stmt->bind_param("sssssiiiiissssssiisd ddds",',
    '$stmt->bind_param("sssssiiiiissssssiisddds",',
    $content
);

file_put_contents($file, $content);
echo "Fixed bind_param in guarda.php\n";
?>
