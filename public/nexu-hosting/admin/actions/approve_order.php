<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/orders.php');
$orderId = (int)($_POST['order_id'] ?? 0);
(new AdminController())->approveOrder($orderId);
