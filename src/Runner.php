<?php
namespace Plinter;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

class Runner
{
    public function isInVendorDirectory(string $path): bool
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);
        return in_array('vendor', $pathParts, true);
    }

    public function scanDirectory(string $directory, bool $skipVendor = false, bool $verbose = false): array
    {
        $errors = false;
        $filesChecked = 0;
        $filesSkipped = 0;
        $vendorDirsSkipped = [];
        $errorList = [];
        $permissionDenied = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            $lastDir = null;
            foreach ($iterator as $file) {
                try {
                    if ($file->isFile()) {
                        $currentDir = dirname($file->getPathname());
                        if ($verbose && $currentDir !== $lastDir) {
                            echo "Entering directory: $currentDir\n";
                            $lastDir = $currentDir;
                        }
                        $filename = $file->getFilename();
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if ($extension !== 'php') {
                            continue;
                        }

                        if ($skipVendor && $this->isInVendorDirectory($file->getPathname())) {
                            $filesSkipped++;
                            $vendorDir = dirname($file->getPathname());
                            while (basename($vendorDir) !== 'vendor') {
                                $vendorDir = dirname($vendorDir);
                            }
                            if (!in_array($vendorDir, $vendorDirsSkipped, true)) {
                                $vendorDirsSkipped[] = $vendorDir;
                            }
                            continue;
                        }

                        if ($verbose) {
                            echo "Checking {$file->getPathname()}... ";
                        }
                        // Run php -l and capture output
                        $output = [];
                        $returnVar = 0;
                        exec("php -l " . escapeshellarg($file->getPathname()), $output, $returnVar);

                        if ($returnVar !== 0) {
                            if ($verbose) {
                                echo "ERROR\n";
                                echo implode("\n", $output) . "\n";
                            }
                            $errorList[] = [
                                'file' => str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                                'message' => implode("\n", array_filter($output, function ($line) {
                                    return !strpos($line, 'Errors parsing') && trim($line) !== '';
                                }))
                            ];
                            $errors = true;
                        } else {
                            if ($verbose) {
                                echo "OK\n";
                            }
                        }

                        $filesChecked++;
                    }
                } catch (\Throwable $e) {
                    $path = null;
                    if (is_object($file) && method_exists($file, 'getPathname')) {
                        $path = $file->getPathname();
                    }
                    if (!$path) {
                        if (preg_match('#"?(/[^\s\']+)"?#', $e->getMessage(), $m)) {
                            $path = $m[1];
                        }
                    }
                    $permissionDenied[] = $path ? ($path . ' (' . $e->getMessage() . ')') : $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $permissionDenied[] = $directory . ' (' . $e->getMessage() . ')';
        }

        $permissionDenied = array_values(array_unique($permissionDenied));

        return [
            'success' => !$errors,
            'errors' => $errorList,
            'permissionDenied' => $permissionDenied,
            'filesChecked' => $filesChecked,
            'filesSkipped' => $filesSkipped,
            'vendorDirsSkipped' => $vendorDirsSkipped,
        ];
    }

    public function scanMultiple(array $paths, bool $skipVendor = false, bool $verbose = false): array
    {
        $all = ['success' => true, 'errors' => [], 'permissionDenied' => [], 'filesChecked' => 0, 'filesSkipped' => 0, 'vendorDirsSkipped' => []];
        foreach ($paths as $path) {
            $res = $this->scanDirectory($path, $skipVendor, $verbose);
            if (!$res['success']) {
                $all['success'] = false;
            }
            $all['errors'] = array_merge($all['errors'], $res['errors']);
            $all['permissionDenied'] = array_merge($all['permissionDenied'], $res['permissionDenied']);
            $all['filesChecked'] += $res['filesChecked'];
            $all['filesSkipped'] += $res['filesSkipped'];
            $all['vendorDirsSkipped'] = array_values(array_unique(array_merge($all['vendorDirsSkipped'], $res['vendorDirsSkipped'])));
        }
        $all['permissionDenied'] = array_values(array_unique($all['permissionDenied']));
        return $all;
    }
}
