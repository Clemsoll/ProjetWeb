<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Europe/Madrid');

define('APP_NAME', 'OmnesEvent');
define('BASE_URL', '/OmnesEvent');
define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['user']['id'])) {
    $freshUser = fetch_user_by_id($pdo, (int) $_SESSION['user']['id']);

    if ($freshUser === null) {
        unset($_SESSION['user']);
    } else {
        sync_user_session($freshUser);
    }
}
