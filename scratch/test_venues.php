<?php
require 'app/Config/env.php';
loadEnv('.env');
require 'app/Config/database.php';

$db = Database::getConnection();
$stmt = $db->query("DESCRIBE venues");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
