<?php
/** Legacy logout — redirect to new auth handler */
require_once __DIR__ . '/config/bootstrap.php';
(new AuthController())->logout();
