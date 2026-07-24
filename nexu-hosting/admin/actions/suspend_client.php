<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/clients.php');
$userId = (int)($_POST['user_id'] ?? 0);
(new AdminController())->suspendClient($userId);
