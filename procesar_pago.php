<?php
/**
 * NEXU HOSTING - Procesador de Pago
 * Recibe el POST del checkout, valida, sube comprobante, crea orden.
 */
require_once __DIR__ . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/planes.php');
}

requireLogin();
(new PaymentController())->processOrder();
