<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>HMS Reversion Script Running...</h1>";

$baseDir = __DIR__;
$modulesDir = $baseDir . '/modules';
$docsDir = $baseDir . '/docs';

// Helper function to recursively find files
function getFiles($dir) {
    if (!is_dir($dir)) return [];
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getFiles($path));
        } else {
            $files[] = $path;
        }
    }
    return $files;
}

// 1. Move all files from modules subfolders to root
if (is_dir($modulesDir)) {
    echo "<h2>Moving files from modules/ to root...</h2>";
    $moduleFiles = getFiles($modulesDir);
    foreach ($moduleFiles as $file) {
        $filename = basename($file);
        $dest = $baseDir . '/' . $filename;
        
        // Read file contents and update the init.php path
        $content = file_get_contents($file);
        
        // Replace dirname(__DIR__, 2) . '/includes/init.php' with __DIR__ . '/includes/init.php'
        $newContent = str_replace(
            "dirname(__DIR__, 2) . '/includes/init.php'",
            "__DIR__ . '/includes/init.php'",
            $content
        );
        $newContent = str_replace(
            "dirname(__DIR__, 2).'/includes/init.php'",
            "__DIR__ . '/includes/init.php'",
            $newContent
        );
        $newContent = str_replace(
            'dirname(__DIR__, 2) . "/includes/init.php"',
            '__DIR__ . "/includes/init.php"',
            $newContent
        );
        $newContent = str_replace(
            'dirname(__DIR__, 2)."/includes/init.php"',
            '__DIR__ . "/includes/init.php"',
            $newContent
        );
        
        // Also remove redundant session_start() calls if they exist, or keep them.
        // Actually, let's keep them and suppress notices in init.php.
        
        // Write to destination
        if (file_put_contents($dest, $newContent) !== false) {
            echo "Moved & updated: $filename <br>";
            // Delete original file
            unlink($file);
        } else {
            echo "<span style='color:red;'>Failed to move: $filename</span><br>";
        }
    }
    
    // Clean up empty directories in modules
    echo "<h2>Cleaning up modules/ subdirectories...</h2>";
    $subdirs = ['admin', 'associate', 'doctor', 'donor', 'patient', 'shared'];
    foreach ($subdirs as $subdir) {
        $path = $modulesDir . '/' . $subdir;
        if (is_dir($path)) {
            @rmdir($path);
            echo "Removed directory: modules/$subdir<br>";
        }
    }
    @rmdir($modulesDir);
    echo "Removed directory: modules<br>";
} else {
    echo "modules/ directory not found.<br>";
}

// 2. Move all files from docs/ to root
if (is_dir($docsDir)) {
    echo "<h2>Moving files from docs/ to root...</h2>";
    $docFiles = getFiles($docsDir);
    foreach ($docFiles as $file) {
        $filename = basename($file);
        $dest = $baseDir . '/' . $filename;
        if (rename($file, $dest)) {
            echo "Moved doc: $filename <br>";
        } else {
            echo "<span style='color:red;'>Failed to move doc: $filename</span><br>";
        }
    }
    @rmdir($docsDir);
    echo "Removed directory: docs<br>";
} else {
    echo "docs/ directory not found.<br>";
}

echo "<h2>Done!</h2>";
?>
