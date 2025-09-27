<?php

declare(strict_types=1);

/**
 * BitrixProLib Autoloader
 * 
 * Simple PSR-4 autoloader for the BitrixProLib library.
 * 
 * @package BitrixProLib
 * @author N00rd1
 * @version 2.0.0
 */

spl_autoload_register(function (string $className): void {
    // Convert namespace to file path
    $prefix = 'BitrixProLib\\';
    $baseDir = __DIR__ . '/src/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($className, $len);
    
    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});
