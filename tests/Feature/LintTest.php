<?php

it('reports syntax errors and returns non-zero exit code', function () {
    // Create a temporary directory for the test
    $tmp = sys_get_temp_dir() . '/plinter_test_' . uniqid();
    if (!mkdir($tmp) && !is_dir($tmp)) {
        throw new RuntimeException(sprintf('Unable to create temp dir %s', $tmp));
    }

    // Good PHP file
    file_put_contents($tmp . '/good.php', "<?php\n echo 'ok';\n");

    // Bad PHP file (syntax error)
    file_put_contents($tmp . '/bad.php', "<?php\n echo 'oops'\n");

    // Use Runner directly
    require_once __DIR__ . '/../../src/Runner.php';
    $runner = new Plinter\Runner();

    $result = $runner->scanDirectory($tmp, false);

    // Clean up temporary files
    @unlink($tmp . '/good.php');
    @unlink($tmp . '/bad.php');
    @rmdir($tmp);

    // Expect that result indicates failure and includes the bad.php file
    expect($result['success'])->toBeFalse();
    $files = array_map(function ($e) { return $e['file']; }, $result['errors']);
    expect(implode(',', $files))->toContain('bad.php');
});
