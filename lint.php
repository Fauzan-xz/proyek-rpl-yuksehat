<?php
$files = glob('app/controllers/*.php');
$files = array_merge($files, glob('app/models/*.php'));
$files = array_merge($files, glob('app/helpers/*.php'));
$files = array_merge($files, glob('app/core/*.php'));
$files = array_merge($files, glob('app/services/*.php'));
$files = array_merge($files, ['config/config.php', 'app/init.php']);

$errors = 0;
foreach($files as $f) {
    $output = shell_exec("C:\\xampp\\php\\php.exe -l \"$f\" 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        echo "[FAIL] $f\n$output\n";
        $errors++;
    } else {
        echo "[OK]   $f\n";
    }
}
echo "\n=== $errors syntax error(s) found ===\n";
