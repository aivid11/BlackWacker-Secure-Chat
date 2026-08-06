<?php
// extension_check.php - temporary diagnostic
header('Content-Type: text/plain; charset=utf-8');
echo "PHP version: " . PHP_VERSION . "\n";
echo "PDO available: " . (extension_loaded('pdo') ? 'yes' : 'no') . "\n";
echo "pdo_mysql available: " . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
echo "Loaded extensions: \n";
print_r(get_loaded_extensions());
?>
