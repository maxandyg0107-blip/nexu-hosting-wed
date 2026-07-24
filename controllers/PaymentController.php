<?php
/**
 * NEXU HOSTING - Controlador de Pagos Peruanos
 * Checkout, subida segura de comprobantes, procesamiento de órdenes.
 */

class PaymentController
{
    private OrderModel $orders;
    private PlanModel  $plans;

    public function __construct()
    {
        $this->orders = new OrderModel();
        $this->plans  = new PlanModel();
    }

    // ── Página de Checkout ────────────────────────────────────

    public function checkout(int $planId, string $billingCycle = 'monthly'): array
    {
        requireLogin();

        $plan = $this->plans->getById($planId);
        if (!$plan || !$plan['is_active']) {
            setFlash('Plan no encontrado.', 'danger');
            redirect('/planes.php');
        }

        $billingCycles = [
            'monthly'   => ['label' => 'Mensual',     'months' => 1,  'discount' => 0],
            'quarterly' => ['label' => 'Trimestral',   'months' => 3,  'discount' => 0.05],
            'annually'  => ['label' => 'Anual',        'months' => 12, 'discount' => 0.15],
        ];

        if (!isset($billingCycles[$billingCycle])) $billingCycle = 'monthly';

        $cycle       = $billingCycles[$billingCycle];
        $subtotalPen = $plan['price_pen'] * $cycle['months'];
        $discountPen = $subtotalPen * $cycle['discount'];
        $totalPen    = round($subtotalPen - $discountPen, 2);

        // Convertir a moneda activa
        $currency   = activeCurrency();
        $rate       = getExchangeRate('PEN', $currency);
        $totalLocal = round($totalPen * $rate, 2);

        return [
            'plan'          => $plan,
            'billing_cycle' => $billingCycle,
            'billing_cycles'=> $billingCycles,
            'subtotal_pen'  => $subtotalPen,
            'discount_pen'  => $discountPen,
            'total_pen'     => $totalPen,
            'total_local'   => $totalLocal,
            'currency'      => $currency,
            'rate'          => $rate,
            'payment_methods' => PAYMENT_CONFIG,
        ];
    }

    // ── Procesar orden con subida de comprobante ──────────────

    public function processOrder(): void
    {
        requireLogin();
        validateCsrfOrFail();

        $planId       = (int)($_POST['plan_id']       ?? 0);
        $billingCycle = sanitize($_POST['billing_cycle'] ?? 'monthly');
        $paymentMethod= sanitize($_POST['payment_method'] ?? '');

        $allowedCycles  = ['monthly', 'quarterly', 'annually'];
        $allowedMethods = array_keys(PAYMENT_CONFIG);

        if (!in_array($billingCycle, $allowedCycles, true)) $billingCycle = 'monthly';

        if (!in_array($paymentMethod, $allowedMethods, true)) {
            setFlash('Método de pago inválido.', 'danger');
            redirect('/checkout.php?plan=' . $planId);
        }

        $plan = $this->plans->getById($planId);
        if (!$plan || !$plan['is_active']) {
            setFlash('Plan no encontrado.', 'danger');
            redirect('/planes.php');
        }

        // Calcular monto
        $cycles    = ['monthly'=>['months'=>1,'discount'=>0],'quarterly'=>['months'=>3,'discount'=>0.05],'annually'=>['months'=>12,'discount'=>0.15]];
        $c         = $cycles[$billingCycle];
        $amountPen = round(($plan['price_pen'] * $c['months']) * (1 - $c['discount']), 2);
        $rate      = getExchangeRate('PEN', 'USD');
        $amountUsd = round($amountPen * $rate, 2);

        // Subir comprobante
        $voucherPath = null;
        $voucherHash = null;

        if (!empty($_FILES['voucher']['name'])) {
            $upload = $this->uploadVoucher($_FILES['voucher']);

            if ($upload['error']) {
                setFlash($upload['error'], 'danger');
                redirect('/checkout.php?plan=' . $planId . '&cycle=' . $billingCycle);
            }

            $voucherPath = $upload['path'];
            $voucherHash = $upload['hash'];
        }

        // Crear orden en BD
        $userId  = $_SESSION['user_id'];
        $orderId = $this->orders->create([
            'user_id'       => $userId,
            'plan_id'       => $planId,
            'amount_pen'    => $amountPen,
            'amount_usd'    => $amountUsd,
            'exchange_rate' => $rate,
            'currency_paid' => 'PEN',
            'payment_method'=> $paymentMethod,
            'voucher_image' => $voucherPath,
            'voucher_hash'  => $voucherHash,
            'billing_cycle' => $billingCycle,
        ]);

        AuditModel::log('order.created', 'orders', $orderId, $userId, [
            'plan'   => $plan['name'],
            'amount' => $amountPen,
            'method' => $paymentMethod,
        ]);

        setFlash('¡Pedido enviado! Revisaremos tu comprobante y activaremos tu servicio en menos de 2 horas.', 'success');
        redirect('/dashboard.php?section=orders');
    }

    // ── Subida Segura de Comprobante ──────────────────────────

    private function uploadVoucher(array $file): array
    {
        // Verificar errores de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $phpErrors = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamaño máximo del servidor.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamaño máximo del formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta.',
                UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal disponible.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco.',
            ];
            return ['error' => $phpErrors[$file['error']] ?? 'Error desconocido al subir el archivo.', 'path' => null, 'hash' => null];
        }

        // Validar tamaño
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['error' => 'El comprobante no puede superar los 10 MB.', 'path' => null, 'hash' => null];
        }

        // Validar extensión (por nombre de archivo)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
            return ['error' => 'Solo se permiten archivos JPG, JPEG, PNG o PDF.', 'path' => null, 'hash' => null];
        }

        // Validar MIME real (no confiar en el header del cliente)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
            return ['error' => 'Tipo de archivo no permitido. Solo JPG, PNG o PDF.', 'path' => null, 'hash' => null];
        }

        // Para PDFs, verificar magic bytes adicionales
        if ($extension === 'pdf' && !str_starts_with(file_get_contents($file['tmp_name'], false, null, 0, 4), '%PDF')) {
            return ['error' => 'El archivo PDF está corrupto o no es válido.', 'path' => null, 'hash' => null];
        }

        // Asegurar directorio de destino
        if (!is_dir(VOUCHERS_PATH)) {
            mkdir(VOUCHERS_PATH, 0755, true);
        }

        // Nombre de archivo aleatorio + hash para evitar colisiones y webshells
        // Usamos random_bytes (CSPRNG) + timestamp
        $safeName  = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
        $destPath  = VOUCHERS_PATH . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['error' => 'No se pudo guardar el archivo. Contacta soporte.', 'path' => null, 'hash' => null];
        }

        // Hash SHA-256 del archivo para auditoría de integridad
        $hash = hash_file('sha256', $destPath);

        // Ruta relativa para almacenar en BD
        $relativePath = 'uploads/vouchers/' . $safeName;

        return ['error' => null, 'path' => $relativePath, 'hash' => $hash];
    }
}
