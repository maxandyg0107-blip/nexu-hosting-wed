<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
(new AuthController())->switchCurrency();
