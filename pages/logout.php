<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    logout_user();
    set_flash('success', 'Vous êtes maintenant déconnecté.');
}

redirect_to('index.php');
