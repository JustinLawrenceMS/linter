<?php

function isInVendorDirectory($path) {
    $pathParts = explode(DIRECTORY_SEPARATOR, $path);
    return in_array('vendor', $pathParts);
}

function lintDirectory($directory, $skipVendor = false) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $errors = false;
    $filesChecked = 0;
    $filesSkipped = 0;
    $vendorDirsSkipped = [];
    $errorList = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            // Check if file is in a vendor directory
            if ($skipVendor && isInVendorDirectory($file->getPathname())) {
                $filesSkipped++;
                $vendorDir = dirname($file->getPathname());
                while (basename($vendorDir) !== 'vendor') {
                    $vendorDir = dirname($vendorDir);
                }
                if (!in_array($vendorDir, $vendorDirsSkipped)) {
                    $vendorDirsSkipped[] = $vendorDir;
                }
                continue;
            }

            $output = [];
            $return_var = 0;
            
            echo "Checking {$file->getPathname()}... ";
            exec("php -l " . escapeshellarg($file->getPathname()), $output, $return_var);
            
            if ($return_var === 0) {
                echo "OK\n";
            } else {
                echo "ERROR\n";
                // Store error information
                $errorList[] = [
                    'file' => str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    'message' => implode("\n", array_filter($output, function($line) {
                        return !strpos($line, 'Errors parsing') && trim($line) !== '';
                    }))
                ];
                $errors = true;
            }
            $filesChecked++;
        }
    }

    echo "\nFiles checked: $filesChecked\n";
    if ($skipVendor && $filesSkipped > 0) {
        echo "Files skipped (in vendor directories): $filesSkipped\n";
        echo "\nSkipped vendor directories:\n";
        foreach ($vendorDirsSkipped as $dir) {
            echo "- " . str_replace($directory . DIRECTORY_SEPARATOR, '', $dir) . "\n";
        }
    }

    return ['success' => !$errors, 'errors' => $errorList];
}

// Get the directory to scan from command line argument, or use current directory
$directory = isset($argv[1]) ? $argv[1] : getcwd();

// Parse command line options
$skipVendor = false;
foreach ($argv as $arg) {
    if ($arg === '--skip-vendor') {
        $skipVendor = true;
        break;
    }
}

if (!isset($argv[1]) || $argv[1] === '--skip-vendor') {
    echo "No directory specified. Current directory is: " . $directory . "\n";
    echo "Skip vendor directories? (Y/n): ";
    $response = trim(fgets(STDIN));
    if ($response === '' || strtolower($response) !== 'n') {
        $skipVendor = true;
    }
    
    echo "Scan all PHP files in this directory and subdirectories? (Y/n): ";
    $response = trim(fgets(STDIN));
    
    if (strtolower($response) === 'n') {
        echo "Scan cancelled.\n";
        exit(0);
    }
}

echo "\nStarting PHP lint scan...\n";
echo "------------------------\n\n";

// Run the linter
$result = lintDirectory($directory, $skipVendor);

echo "\n------------------------\n";
if ($result['success']) {
    echo "All files passed!\n";
} else {
    echo "Errors were found:\n\n";
    foreach ($result['errors'] as $error) {
        echo "File: {$error['file']}\n";
        echo "Error: {$error['message']}\n";
        echo "------------------------\n";
    }
}

// Exit with appropriate status code
exit($result['success'] ? 0 : 1);
