<?php
/**
 * controllers/OrderController.php
 * ---------------------------------------------------------------------
 * Controlador de checkout: valida el plan seleccionado, procesa la
 * subida segura del comprobante (voucher) y registra la orden con
 * estado 'pending' en la base de datos.
 * ---------------------------------------------------------------------
 */

if (!defined('NEXU_APP')) {
    http_response_code(403);
    die('Acceso directo no permitido.');
}

class OrderController
{
    private Order $orderModel;
    private Plan $planModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->planModel  = new Plan();
    }

    /**
     * Procesa el envío del formulario de checkout.
     * Devuelve ['success' => bool, 'errors' => array, 'order_id' => ?int]
     */
    public function submitOrder(int $userId, array $post, array $files): array
    {
        csrf_verify_or_die();

        $errors = [];

        $planId = (int) ($post['plan_id'] ?? 0);
        $paymentMethod = $post['payment_method'] ?? '';
        $operationCode = trim($post['operation_code'] ?? '');

        $allowedMethods = ['yape', 'plin', 'banco_nacion', 'interbank', 'bcp'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            $errors[] = 'Selecciona un método de pago válido.';
        }

        $plan = $this->planModel->findById($planId);
        if (!$plan) {
            $errors[] = 'El plan seleccionado no existe o ya no está disponible.';
        }

        // Valida y mueve el archivo del voucher de forma segura
        $voucherResult = $this->handleVoucherUpload($files['voucher'] ?? null);
        if (!$voucherResult['success']) {
            $errors[] = $voucherResult['error'];
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'order_id' => null];
        }

        try {
            $orderId = $this->orderModel->create(
                $userId,
                (int) $plan['id'],
                (float) $plan['price_PEN'],
                $paymentMethod,
                $voucherResult['filename'],
                $operationCode !== '' ? $operationCode : null
            );

            return ['success' => true, 'errors' => [], 'order_id' => $orderId];
        } catch (\Throwable $e) {
            log_error('OrderController::submitOrder', $e);
            return ['success' => false, 'errors' => ['No se pudo registrar la orden. Intenta nuevamente.'], 'order_id' => null];
        }
    }

    /**
     * Valida rigurosamente el archivo subido (extensión real, MIME real
     * detectado por contenido -no por cabecera declarada por el cliente-,
     * y tamaño máximo) y lo mueve a un directorio protegido con un
     * nombre aleatorio, neutralizando intentos de subida de webshells.
     */
    private function handleVoucherUpload(?array $file): array
    {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'Debes adjuntar el comprobante de pago.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Ocurrió un error al subir el archivo. Intenta de nuevo.'];
        }

        if ($file['size'] > UPLOAD_MAX_BYTES) {
            return ['success' => false, 'error' => 'El archivo supera el tamaño máximo permitido (5 MB).'];
        }

        // 1) Validar extensión declarada
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, UPLOAD_ALLOWED_EXT, true)) {
            return ['success' => false, 'error' => 'Formato de archivo no permitido. Usa JPG, PNG o PDF.'];
        }

        // 2) Validar el tipo MIME REAL a partir del contenido del archivo
        //    (finfo inspecciona los "magic bytes", no confía en la extensión
        //    ni en el Content-Type enviado por el navegador del cliente)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!in_array($realMime, UPLOAD_ALLOWED_MIME, true)) {
            return ['success' => false, 'error' => 'El contenido del archivo no corresponde a una imagen o PDF válido.'];
        }

        // 3) Verificación adicional: si dice ser imagen, confirmar con getimagesize()
        if (str_starts_with($realMime, 'image/') && @getimagesize($file['tmp_name']) === false) {
            return ['success' => false, 'error' => 'La imagen del comprobante está corrupta o no es válida.'];
        }

        // 4) Generar nombre de archivo aleatorio (nunca se conserva el nombre original)
        $safeFilename = generate_safe_filename($extension);
        $destination = rtrim(UPLOAD_DIR, '/') . '/' . $safeFilename;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0750, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'No se pudo guardar el comprobante. Intenta nuevamente.'];
        }

        // Permisos restrictivos: lectura para el servidor, sin ejecución
        chmod($destination, 0640);

        return ['success' => true, 'filename' => $safeFilename];
    }
}
