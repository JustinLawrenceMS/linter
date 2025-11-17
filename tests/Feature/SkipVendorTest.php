<?php

it('skips files inside vendor when --skip-vendor is used', function () {
    $tmp = sys_get_temp_dir() . '/plinter_skipvendor_' . uniqid();
    $vendor = $tmp . '/vendor';
    mkdir($vendor, 0777, true);

    // Create a bad php file inside vendor and a good file at root
    file_put_contents($vendor . '/bad.php', "<?php\n echo 'oops'\n");
    file_put_contents($tmp . '/good.php', "<?php\n echo 'ok';\n");

    require_once __DIR__ . '/../../src/Runner.php';
    $runner = new Plinter\Runner();

    $result = $runner->scanDirectory($tmp, true);

    // cleanup
    @unlink($vendor . '/bad.php');
    @unlink($tmp . '/good.php');
    @rmdir($vendor);
    @rmdir($tmp);

    expect($result['success'])->toBeTrue();
    expect($result['filesSkipped'])->toBeGreaterThan(0);
    expect(implode(',', array_column($result['errors'], 'file')))->not->toContain('bad.php');
});
