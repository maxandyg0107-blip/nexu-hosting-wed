<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
(new AuthController())->googleRedirect();
