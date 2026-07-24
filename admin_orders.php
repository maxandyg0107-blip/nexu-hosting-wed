<?php
/**
 * admin_orders.php
 * Panel de administración: verificación, aprobación y rechazo de órdenes.
 * Protegido por middleware require_admin() -> exige role === 'admin'.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

$adminController = new AdminController();
$orderModel = new Order();

$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($action === 'approve') {
        $actionResult = $adminController->approveOrder((int) $_SESSION['user_id'], $orderId);
    } elseif ($action === 'reject') {
        $actionResult = $adminController->rejectOrder((int) $_SESSION['user_id'], $orderId, $_POST['reason'] ?? '');
    }
}

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'verified', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}
$orders = $orderModel->allForAdmin($statusFilter);

$pageTitle = 'Panel de Administración · Órdenes';
require __DIR__ . '/views/layouts/header.php';
?>
<main class="max-w-7xl mx-auto px-6 py-12">
  <h1 class="font-display text-2xl font-semibold text-white mb-1">Verificación de órdenes</h1>
  <p class="text-slate-400 text-sm mb-6">Audita el comprobante subido por el cliente y aprueba o rechaza el pago.</p>

  <?php if ($actionResult): ?>
    <div class="mb-6 rounded-lg px-4 py-3 text-sm border <?= $actionResult['success'] ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-red-500/30 bg-red-500/10 text-red-300' ?>">
      <?= e($actionResult['message']) ?>
    </div>
  <?php endif; ?>

  <div class="flex gap-2 mb-6 text-sm font-mono">
    <?php foreach (['pending' => 'Pendientes', 'verified' => 'Verificadas', 'rejected' => 'Rechazadas', 'all' => 'Todas'] as $key => $label): ?>
      <a href="?status=<?= e($key) ?>"
         class="px-3 py-1.5 rounded-md border <?= $statusFilter === $key ? 'border-cyan text-cyan bg-cyan/10' : 'border-line text-slate-400 hover:text-white' ?> transition">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($orders)): ?>
    <div class="rounded-xl border border-dashed border-line p-10 text-center text-slate-500 text-sm">
      No hay órdenes en esta categoría.
    </div>
  <?php endif; ?>

  <div class="space-y-4">
    <?php foreach ($orders as $o): ?>
      <div class="rounded-xl border border-line bg-surface p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-mono text-slate-500 text-sm">#<?= (int)$o['id'] ?></span>
              <span class="text-xs px-2 py-1 rounded-full <?= order_status_badge($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span>
            </div>
            <p class="font-display text-white font-semibold"><?= e($o['plan_name']) ?> <span class="text-slate-500 font-normal text-sm">(<?= e($o['plan_type']) ?>)</span></p>
            <p class="text-sm text-slate-400 mt-1">Cliente: <?= e($o['username']) ?> · <?= e($o['email']) ?></p>
            <p class="text-sm font-mono text-slate-400 mt-1">
              <?= money_pen((float)$o['total_amount']) ?> ·
              <?= e(ucfirst(str_replace('_',' ',$o['payment_method']))) ?>
              <?php if (!empty($o['operation_code'])): ?> · Op: <?= e($o['operation_code']) ?><?php endif; ?>
            </p>
            <p class="text-xs text-slate-600 font-mono mt-1"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></p>
          </div>

          <div class="flex items-center gap-3">
            <?php if (!empty($o['voucher_image'])): ?>
              <a href="/voucher_view.php?order_id=<?= (int)$o['id'] ?>" target="_blank" rel="noopener noreferrer"
                 class="text-xs rounded-md border border-line px-3 py-2 hover:border-cyan/50 hover:text-cyan transition">
                Ver comprobante ↗
              </a>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($o['status'] === 'pending'): ?>
          <div class="mt-4 pt-4 border-t border-line/60 flex flex-wrap gap-3 items-center">
            <form method="POST" onsubmit="return confirm('¿Confirmas aprobar esta orden y aprovisionar el servidor?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <button type="submit" class="text-sm rounded-md bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-2 hover:bg-emerald-500/20 transition">
                ✓ Aprobar pago
              </button>
            </form>

            <form method="POST" class="flex items-center gap-2 flex-1 min-w-[260px]"
                  onsubmit="return this.reason.value.trim().length > 0 || alert('Debes indicar el motivo del rechazo.');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
              <input type="text" name="reason" required placeholder="Motivo del rechazo…"
                     class="flex-1 rounded-md bg-surface2 border border-line px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500/40">
              <button type="submit" class="text-sm rounded-md bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-2 hover:bg-red-500/20 transition whitespace-nowrap">
                ✕ Rechazar
              </button>
            </form>
          </div>
        <?php elseif (!empty($o['admin_notes'])): ?>
          <p class="mt-3 text-xs text-slate-500 border-t border-line/60 pt-3">Nota admin: <?= e($o['admin_notes']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/views/layouts/footer.php'; ?>
