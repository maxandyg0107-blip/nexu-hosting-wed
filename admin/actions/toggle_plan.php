<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/settings.php');
$planId = (int)($_POST['plan_id'] ?? 0);
(new AdminController())->togglePlan($planId);
