<?php

it('handles nested directories and reports structure correctly', function () {
    $tmp = sys_get_temp_dir() . '/plinter_nested_' . uniqid();
    mkdir($tmp . '/sub1/sub2', 0777, true);

    // Create PHP files at different levels
    file_put_contents($tmp . '/root.php', "<?php\n echo 'ok';\n");
    file_put_contents($tmp . '/sub1/level1.php', "<?php\n echo 'ok';\n");
    file_put_contents($tmp . '/sub1/sub2/level2.php', "<?php\n echo 'ok';\n");

    require_once __DIR__ . '/../../src/Runner.php';
    $runner = new Plinter\Runner();
    $result = $runner->scanDirectory($tmp, false);

    // cleanup
    @unlink($tmp . '/root.php');
    @unlink($tmp . '/sub1/level1.php');
    @unlink($tmp . '/sub1/sub2/level2.php');
    @rmdir($tmp . '/sub1/sub2');
    @rmdir($tmp . '/sub1');
    @rmdir($tmp);

    expect($result['success'])->toBeTrue();
    expect($result['filesChecked'])->toBe(3);
});
