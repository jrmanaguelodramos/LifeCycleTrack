<?php

echo '<pre>';

echo "PHP version: " . PHP_VERSION . PHP_EOL;

echo "PDO: ";
var_dump(class_exists('PDO'));

echo "PDO PostgreSQL: ";
var_dump(in_array('pgsql', PDO::getAvailableDrivers(), true));

echo "Available PDO drivers:" . PHP_EOL;
print_r(PDO::getAvailableDrivers());

echo '</pre>';