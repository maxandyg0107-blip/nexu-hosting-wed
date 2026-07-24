<?php
require_once dirname(dirname(__DIR__)) . '/config/bootstrap.php';

requireAdmin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	setFlash('Método de solicitud no permitido.', 'danger');
	redirect('/admin/clients.php', 303);
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, [
	'options' => [
		'min_range' => 1,
	],
]);

if ($userId === false || $userId === null) {
	setFlash('El cliente seleccionado no es válido.', 'danger');
	redirect('/admin/clients.php', 303);
}

(new AdminController())->activateClient($userId);
