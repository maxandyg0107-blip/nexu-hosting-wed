<?php
/**
 * NEXU HOSTING - Admin: Órdenes Pendientes de Verificación
 * Aprobación / Rechazo de pagos con visor de comprobantes
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl    = new AdminController();
$pending = $ctrl->getPendingOrders();
$allData = $ctrl->getAllOrders(sanitize($_GET['status'] ?? ''), max(1,(int)($_GET['page']??1)));

$page_title = 'Gestión de Órdenes';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <!-- Page header -->
    <div class="flex items-center justify-between py-6 animate-fade-in">
      <div>
        <h1 class="text-2xl font-extrabold text-white">Gestión de Órdenes</h1>
        <p class="text-gray-400 text-sm mt-1">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-500/15 text-yellow-400 border border-yellow-500/30 text-xs font-semibold">
            <?= count($pending) ?> pendiente(s)
          </span>
        </p>
      </div>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <!-- ── PENDING SECTION ── -->
    <?php if (!empty($pending)): ?>
    <section class="mb-10">
      <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
        Pagos pendientes de verificación
      </h2>
      <div class="space-y-4">
        <?php foreach ($pending as $ord): ?>
        <div class="bg-surface-card border border-yellow-500/20 rounded-2xl p-6 animate-fade-in">
          <div class="grid lg:grid-cols-3 gap-6">

            <!-- Order info -->
            <div class="lg:col-span-2 space-y-3">
              <div class="flex items-center gap-3 flex-wrap">
                <span class="font-mono text-xs text-gray-500"><?= e($ord['invoice_number'] ?? '#'.$ord['id']) ?></span>
                <?= statusBadge($ord['status']) ?>
                <span class="text-xs text-gray-500"><?= timeAgo($ord['created_at']) ?></span>
              </div>
              <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div>
                  <p class="text-gray-500 text-xs mb-0.5">Cliente</p>
                  <p class="font-semibold text-white"><?= e($ord['username']) ?></p>
                  <p class="text-gray-400"><?= e($ord['email']) ?></p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-0.5">Plan</p>
                  <p class="font-semibold text-white"><?= e($ord['plan_name']) ?></p>
                  <p class="text-gray-400 capitalize"><?= e($ord['plan_type']) ?> · <?= e($ord['billing_cycle']) ?></p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-0.5">Método de pago</p>
                  <p class="font-semibold text-white capitalize"><?= e(str_replace('_',' ',$ord['payment_method'])) ?></p>
                </div>
                <div>
                  <p class="text-gray-500 text-xs mb-0.5">Monto</p>
                  <p class="text-2xl font-extrabold text-white">S/ <?= number_format($ord['amount_pen'],2) ?></p>
                </div>
              </div>
            </div>

            <!-- Voucher + actions -->
            <div class="space-y-4">
              <?php if ($ord['voucher_image']): ?>
              <div>
                <p class="text-xs text-gray-500 mb-2">Comprobante adjunto</p>
                <?php
                $ext    = strtolower(pathinfo($ord['voucher_image'], PATHINFO_EXTENSION));
                $vpath  = '/' . ltrim($ord['voucher_image'], '/');
                ?>
                <?php if ($ext === 'pdf'): ?>
                  <a href="<?= e($vpath) ?>" target="_blank" rel="noopener"
                     class="flex items-center gap-2 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm font-medium hover:bg-red-500/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Ver PDF comprobante
                  </a>
                <?php else: ?>
                  <a href="<?= e($vpath) ?>" target="_blank" rel="noopener" class="block">
                    <img src="<?= e($vpath) ?>" alt="Comprobante"
                         class="w-full max-h-40 object-cover rounded-xl border border-surface-border hover:border-brand-500 transition-colors cursor-zoom-in">
                  </a>
                <?php endif; ?>
                <p class="text-xs text-gray-600 mt-1 font-mono">SHA256: <?= substr($ord['voucher_hash'] ?? '', 0, 16) ?>...</p>
              </div>
              <?php else: ?>
              <div class="flex items-center justify-center h-32 rounded-xl bg-surface border border-surface-border">
                <p class="text-xs text-gray-500">Sin comprobante adjunto</p>
              </div>
              <?php endif; ?>

              <!-- Approve -->
              <form method="POST" action="/admin/actions/approve_order.php">
                <?= csrfField() ?>
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-bold text-sm
                               shadow-lg shadow-emerald-600/30 hover:shadow-emerald-600/50 hover:-translate-y-0.5 transition-all"
                        onclick="return confirm('¿Aprobar el pago y activar el servidor?')">
                  ✅ Aprobar Pago
                </button>
              </form>

              <!-- Reject -->
              <details class="group">
                <summary class="cursor-pointer w-full py-2.5 px-4 rounded-xl border border-red-500/30 bg-red-500/5 text-red-400 text-sm font-semibold
                                hover:bg-red-500/10 transition-colors text-center list-none">
                  ❌ Rechazar Pago
                </summary>
                <form method="POST" action="/admin/actions/reject_order.php" class="mt-3">
                  <?= csrfField() ?>
                  <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                  <textarea name="admin_notes" required minlength="5" rows="3"
                            class="w-full px-3 py-2 rounded-xl bg-surface border border-red-500/30 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-red-500 mb-2 resize-none"
                            placeholder="Motivo del rechazo (obligatorio)..."></textarea>
                  <button type="submit"
                          class="w-full py-2 rounded-xl bg-red-600 text-white font-semibold text-sm hover:bg-red-500 transition-colors">
                    Confirmar rechazo
                  </button>
                </form>
              </details>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ── ALL ORDERS TABLE ── -->
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-white">Todas las Órdenes</h2>
        <!-- Status filter -->
        <div class="flex gap-2">
          <?php foreach ([''=> 'Todas','pending'=>'Pendiente','verified'=>'Verificado','rejected'=>'Rechazado'] as $st => $lb): ?>
          <a href="?status=<?= $st ?>"
             class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                    <?= ($_GET['status'] ?? '') === $st ? 'bg-brand-600 text-white' : 'bg-surface-card border border-surface-border text-gray-400 hover:text-white' ?>">
            <?= $lb ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-surface-border">
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Factura</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Cliente</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Plan</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Método</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Monto</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Estado</th>
                <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Fecha</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
              <?php foreach ($allData['orders'] as $ord): ?>
              <tr class="hover:bg-surface-hover transition-colors">
                <td class="px-5 py-4 font-mono text-gray-400 text-xs"><?= e($ord['invoice_number'] ?? '#'.$ord['id']) ?></td>
                <td class="px-5 py-4">
                  <p class="font-medium text-white"><?= e($ord['username']) ?></p>
                  <p class="text-xs text-gray-500"><?= e($ord['email']) ?></p>
                </td>
                <td class="px-5 py-4 text-gray-300"><?= e($ord['plan_name']) ?></td>
                <td class="px-5 py-4 text-gray-400 text-xs capitalize"><?= e(str_replace('_',' ',$ord['payment_method'])) ?></td>
                <td class="px-5 py-4 font-bold text-white">S/ <?= number_format($ord['amount_pen'],2) ?></td>
                <td class="px-5 py-4"><?= statusBadge($ord['status']) ?></td>
                <td class="px-5 py-4 text-gray-500 text-xs"><?= formatDate($ord['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
