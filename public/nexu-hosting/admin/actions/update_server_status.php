<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/servers.php');
$serverId = (int)($_POST['server_id'] ?? 0);
(new AdminController())->updateServerStatus($serverId);
