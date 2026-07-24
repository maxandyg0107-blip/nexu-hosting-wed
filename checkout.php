<?php
/**
 * checkout.php
 * Resumen de compra + selección de método de pago local + subida de voucher.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$planModel = new Plan();
$planId = (int) ($_GET['plan_id'] ?? $_POST['plan_id'] ?? 0);
$plan = $planModel->findById($planId);

if (!$plan) {
    http_response_code(404);
    die('El plan solicitado no existe o ya no está disponible. <a href="/index.php" style="color:#2DD4BF">Volver al catálogo</a>');
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderController = new OrderController();
    $result = $orderController->submitOrder((int) $_SESSION['user_id'], $_POST, $_FILES);
}

$pageTitle = 'Checkout · ' . $plan['name'];
require __DIR__ . '/views/layouts/header.php';
?>
<main class="max-w-3xl mx-auto px-6 py-14">

  <?php if ($result && $result['success']): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-8 text-center">
      <h1 class="font-display text-2xl font-semibold text-white mb-2">¡Orden registrada con éxito!</h1>
      <p class="text-slate-300 text-sm">Tu orden <span class="font-mono text-cyan">#<?= (int)$result['order_id'] ?></span> quedó en estado
        <span class="font-mono text-amber">pendiente de verificación</span>. Nuestro equipo confirmará tu pago en las próximas horas.</p>
      <a href="/dashboard.php" class="inline-block mt-6 rounded-md bg-cyan text-base font-semibold px-5 py-2.5 hover:bg-cyan/90 transition">Ir a mi panel</a>
    </div>
  <?php else: ?>

    <h1 class="font-display text-2xl font-semibold text-white mb-1">Finalizar compra</h1>
    <p class="text-slate-400 text-sm mb-8">Revisa el resumen y sube tu comprobante de pago para activar el aprovisionamiento.</p>

    <?php if ($result && !empty($result['errors'])): ?>
      <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300 space-y-1">
        <?php foreach ($result['errors'] as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-5 gap-6">
      <!-- Resumen del plan -->
      <div class="md:col-span-2 rounded-xl border border-line bg-surface p-5 h-fit">
        <p class="text-xs font-mono text-slate-500 uppercase mb-1"><?= e($plan['plan_type'] === 'minecraft' ? 'Servidor Minecraft' : 'Hosting Web') ?></p>
        <h2 class="font-display text-xl font-semibold text-white"><?= e($plan['name']) ?></h2>
        <ul class="mt-4 space-y-2 text-sm font-mono text-slate-300">
          <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">RAM</span><span><?= (int)$plan['ram_gb'] ?> GB DDR4/DDR5</span></li>
          <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">vCPU</span><span><?= (int)$plan['cpu_cores'] ?> núcleos dedicados</span></li>
          <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">Almacenamiento</span><span><?= (int)$plan['disk_gb'] ?> GB SSD NVMe</span></li>
        </ul>
        <div class="mt-5 flex items-baseline justify-between">
          <span class="text-slate-400 text-sm">Total a pagar</span>
          <span class="font-display text-2xl font-bold text-white"><?= money_pen((float)$plan['price_PEN']) ?></span>
        </div>
      </div>

      <!-- Formulario de pago -->
      <form method="POST" action="/checkout.php" enctype="multipart/form-data" class="md:col-span-3 rounded-xl border border-line bg-surface p-5 space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">

        <div>
          <label class="block text-xs font-mono text-slate-400 mb-2">Método de pago</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2" id="payment-methods">
            <?php foreach (PAYMENT_INFO as $key => $info): ?>
              <label class="cursor-pointer">
                <input type="radio" name="payment_method" value="<?= e($key) ?>" class="peer sr-only" <?= $key === 'yape' ? 'checked' : '' ?>>
                <div class="text-center text-sm rounded-md border border-line py-2.5 peer-checked:border-cyan peer-checked:bg-cyan/10 peer-checked:text-cyan transition">
                  <?= e($info['label']) ?>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <?php foreach (PAYMENT_INFO as $key => $info): ?>
          <div class="payment-details rounded-lg border border-line/70 bg-surface2 p-4 text-sm font-mono <?= $key === 'yape' ? '' : 'hidden' ?>" data-method="<?= e($key) ?>">
            <?php if (isset($info['number'])): ?>
              <p class="flex justify-between py-1"><span class="text-slate-500">Número</span><span class="text-white"><?= e($info['number']) ?></span></p>
            <?php endif; ?>
            <?php if (isset($info['account'])): ?>
              <p class="flex justify-between py-1"><span class="text-slate-500">N° Cuenta</span><span class="text-white"><?= e($info['account']) ?></span></p>
              <p class="flex justify-between py-1"><span class="text-slate-500">CCI</span><span class="text-white"><?= e($info['cci']) ?></span></p>
            <?php endif; ?>
            <p class="flex justify-between py-1"><span class="text-slate-500">Titular</span><span class="text-white"><?= e($info['holder']) ?></span></p>
          </div>
        <?php endforeach; ?>

        <div>
          <label class="block text-xs font-mono text-slate-400 mb-1.5">N° de operación (opcional)</label>
          <input type="text" name="operation_code" maxlength="100"
                 class="w-full rounded-md bg-surface2 border border-line px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan/50"
                 placeholder="Ej. 000123456789">
        </div>

        <div>
          <label class="block text-xs font-mono text-slate-400 mb-1.5">Comprobante de pago (JPG, PNG o PDF, máx. 5 MB)</label>
          <input type="file" name="voucher" required accept=".jpg,.jpeg,.png,.pdf"
                 class="w-full text-sm text-slate-300 file:mr-3 file:rounded-md file:border-0 file:bg-cyan file:text-base file:font-semibold file:px-3 file:py-2 file:cursor-pointer">
        </div>

        <button type="submit" class="w-full rounded-md bg-cyan text-base font-semibold py-3 hover:bg-cyan/90 transition">
          Confirmar orden — <?= money_pen((float)$plan['price_PEN']) ?>
        </button>
      </form>
    </div>
  <?php endif; ?>
</main>

<script>
  document.querySelectorAll('#payment-methods input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.payment-details').forEach(el => {
        el.classList.toggle('hidden', el.dataset.method !== radio.value);
      });
    });
  });
</script>

<?php require __DIR__ . '/views/layouts/footer.php'; ?>
