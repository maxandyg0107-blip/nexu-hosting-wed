<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/login.php');
(new AuthController())->login();
