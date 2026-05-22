<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
Auth::logout();
// Flash mesajı session'a yazmak güvenilmez — cookie silindikten sonra session okunmuyor.
// Bunun yerine query param kullanıyoruz; index.php bunu okuyup mesaj gösterir.
header('Location: ' . BASE_URL . '/?loggedout=1');
exit;
