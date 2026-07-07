<?php
require_once __DIR__ . '/../config/config.php';
logout_admin();
flash_set('info', 'You have been signed out.');
redirect('admin/login.php');
