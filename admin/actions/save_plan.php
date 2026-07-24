<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/settings.php');
(new AdminController())->savePlan();
