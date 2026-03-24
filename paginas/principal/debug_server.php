<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Server Diagnostics</h1>";

echo "<h2>PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";

echo "<h2>Extensions Check</h2>";
$extensions = ['zip', 'xml', 'gd', 'iconv', 'mbstring', 'simplexml', 'dom'];
foreach ($extensions as $ext) {
    echo "Extension '$ext': " . (extension_loaded($ext) ? "✅ Loaded" : "❌ NOT LOADED") . "<br>";
}

echo "<h2>Autoload Check</h2>";
$autoload = '../../vendor/autoload.php';
echo "Autoload file exists: " . (file_get_contents($autoload) ? "✅ Yes" : "❌ No") . " (at $autoload)<br>";

echo "<h2>Database Connection Check</h2>";
try {
    require_once '../conexion.php';
    if ($conn) {
        echo "Database Connection: ✅ Success<br>";
        $res = $conn->query("SELECT COUNT(*) as total FROM planes");
        $row = $res->fetch_assoc();
        echo "Planes count: " . $row['total'] . "<br>";
    } else {
        echo "Database Connection: ❌ Failed (variable \$conn is null)<br>";
    }
} catch (Exception $e) {
    echo "Database Connection: ❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>PhpSpreadsheet Check</h2>";
try {
    require_once '../../vendor/autoload.php';
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo "PhpSpreadsheet IOFactory: ✅ Class Found<br>";
    } else {
        echo "PhpSpreadsheet IOFactory: ❌ Class NOT Found<br>";
    }
} catch (Exception $e) {
    echo "PhpSpreadsheet Load Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Write Permission Check</h2>";
$testFile = 'test_write.txt';
if (@file_put_contents($testFile, 'test')) {
    echo "Write Permission in current dir: ✅ Success<br>";
    unlink($testFile);
} else {
    echo "Write Permission in current dir: ❌ Failed<br>";
}
?>
