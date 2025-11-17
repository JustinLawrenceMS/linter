<?php

it('scans multiple supplied directories via Runner::scanMultiple', function () {
    $parent = sys_get_temp_dir() . '/plinter_multi_parent_' . uniqid();
    mkdir($parent, 0777, true);

    $tmpA = $parent . '/A';
    $tmpB = $parent . '/B';
    mkdir($tmpA, 0777, true);
    mkdir($tmpB, 0777, true);

    // bad file in A, good in B
    file_put_contents($tmpA . '/bad.php', "<?php\n echo 'oops'\n");
    file_put_contents($tmpB . '/good.php', "<?php\n echo 'ok';\n");

    require_once __DIR__ . '/../../src/Runner.php';
    $runner = new Plinter\Runner();

    $result = $runner->scanMultiple([$tmpA, $tmpB], false);

    // cleanup
    @unlink($tmpA . '/bad.php');
    @unlink($tmpB . '/good.php');
    @rmdir($tmpA);
    @rmdir($tmpB);
    @rmdir($parent);

    // Expect non-zero overall success due to bad.php
    expect($result['success'])->toBeFalse();
    expect(implode(',', array_map(function ($e) { return $e['file']; }, $result['errors'])))->toContain('bad.php');
});
