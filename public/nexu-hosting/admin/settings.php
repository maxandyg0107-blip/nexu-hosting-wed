<?php
/**
 * NEXU HOSTING - Admin: Configuración (Planes + Pagos)
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl = new AdminController();
$data = $ctrl->getPlans();

// Plan en edición
$editId  = (int)($_GET['edit'] ?? 0);
$editPlan = null;
if ($editId > 0) {
    $pm = new PlanModel();
    $editPlan = $pm->getById($editId);
}

$page_title = 'Configuración';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <div class="py-6 animate-fade-in">
      <h1 class="text-2xl font-extrabold text-white">Configuración</h1>
      <p class="text-gray-400 text-sm mt-1">Gestiona el catálogo de planes y datos de pago.</p>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <div class="grid lg:grid-cols-5 gap-8">

      <!-- Plan form -->
      <div class="lg:col-span-2">
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 sticky top-6">
          <h2 class="font-bold text-white mb-5">
            <?= $editPlan ? '✏️ Editar plan: '.e($editPlan['name']) : '➕ Nuevo plan' ?>
          </h2>
          <form method="POST" action="/admin/actions/save_plan.php">
            <?= csrfField() ?>
            <input type="hidden" name="plan_id" value="<?= $editPlan['id'] ?? 0 ?>">

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Nombre <span class="text-red-400">*</span></label>
                <input type="text" name="name" required value="<?= e($editPlan['name'] ?? '') ?>"
                       class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500 transition-all">
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Tipo</label>
                <select name="plan_type"
                        class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-gray-300 text-sm focus:outline-none focus:border-brand-500">
                  <?php foreach ($data['types'] as $key => $label): ?>
                  <option value="<?= $key ?>" <?= ($editPlan['plan_type'] ?? 'minecraft') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Descripción</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm resize-none focus:outline-none focus:border-brand-500"><?= e($editPlan['description'] ?? '') ?></textarea>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">RAM (GB) *</label>
                  <input type="number" step="0.5" min="0" name="ram_gb" required value="<?= e($editPlan['ram_gb'] ?? '2') ?>"
                         class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">vCPU *</label>
                  <input type="number" step="0.5" min="0" name="cpu_cores" required value="<?= e($editPlan['cpu_cores'] ?? '2') ?>"
                         class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Disco NVMe (GB) *</label>
                  <input type="number" min="0" name="disk_gb" required value="<?= e($editPlan['disk_gb'] ?? '20') ?>"
                         class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Slots jugadores</label>
                  <input type="number" min="0" name="player_slots" value="<?= e($editPlan['player_slots'] ?? '') ?>" placeholder="(opcional)"
                         class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500">
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Precio mensual en PEN (S/) *</label>
                <div class="flex items-center gap-2">
                  <span class="text-gray-400 text-sm font-bold">S/</span>
                  <input type="number" step="0.01" min="0" name="price_pen" required value="<?= e($editPlan['price_pen'] ?? '') ?>"
                         class="flex-1 px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-sm focus:outline-none focus:border-brand-500">
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Características (una por línea)</label>
                <textarea name="features" rows="5"
                          class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs font-mono resize-y focus:outline-none focus:border-brand-500"
                          placeholder="2 GB RAM DDR4&#10;Panel Pterodactyl&#10;Anti-DDoS incluido"><?= e(implode("\n", json_decode($editPlan['features'] ?? '[]', true))) ?></textarea>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Visible</label>
                  <select name="is_active" class="w-full px-2 py-2 rounded-lg bg-surface border border-surface-border text-gray-300 text-xs focus:outline-none focus:border-brand-500">
                    <option value="1" <?= ($editPlan['is_active'] ?? 1) == 1 ? 'selected':'' ?>>Sí</option>
                    <option value="0" <?= ($editPlan['is_active'] ?? 1) == 0 ? 'selected':'' ?>>No</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Destacado</label>
                  <select name="is_featured" class="w-full px-2 py-2 rounded-lg bg-surface border border-surface-border text-gray-300 text-xs focus:outline-none focus:border-brand-500">
                    <option value="0" <?= ($editPlan['is_featured'] ?? 0) == 0 ? 'selected':'' ?>>No</option>
                    <option value="1" <?= ($editPlan['is_featured'] ?? 0) == 1 ? 'selected':'' ?>>Sí</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-400 mb-1">Orden</label>
                  <input type="number" name="sort_order" min="0" value="<?= e($editPlan['sort_order'] ?? 0) ?>"
                         class="w-full px-2 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
              </div>
            </div>

            <div class="flex gap-2 mt-5">
              <button type="submit"
                      class="flex-1 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-500 transition-colors">
                <?= $editPlan ? 'Actualizar plan' : 'Crear plan' ?>
              </button>
              <?php if ($editPlan): ?>
              <a href="/admin/settings.php"
                 class="px-4 py-2.5 rounded-xl border border-surface-border text-gray-400 text-sm hover:text-white transition-colors">
                Cancelar
              </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Plans list -->
      <div class="lg:col-span-3 space-y-4">
        <h2 class="font-bold text-white">Catálogo de planes (<?= count($data['plans']) ?>)</h2>
        <?php foreach ($data['plans'] as $plan): ?>
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 flex items-center justify-between gap-4 hover:border-brand-500/30 transition-colors">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-600/30 to-cyan-500/30 border border-brand-500/20 flex items-center justify-center text-sm flex-shrink-0">
              <?= $plan['plan_type'] === 'minecraft' ? '🎮' : '🌐' ?>
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-white truncate"><?= e($plan['name']) ?></p>
              <p class="text-xs text-gray-500"><?= $plan['ram_gb'] ?>GB RAM · <?= $plan['cpu_cores'] ?> vCPU · <?= $plan['disk_gb'] ?>GB NVMe</p>
            </div>
          </div>
          <div class="flex items-center gap-3 flex-shrink-0">
            <span class="font-bold text-white text-sm">S/ <?= number_format($plan['price_pen'],2) ?></span>
            <?= statusBadge($plan['is_active'] ? 'active' : 'suspended') ?>
            <a href="/admin/settings.php?edit=<?= $plan['id'] ?>"
               class="px-3 py-1.5 rounded-lg bg-surface border border-surface-border text-gray-400 text-xs hover:text-white hover:border-brand-500/40 transition-colors">
              Editar
            </a>
            <form method="POST" action="/admin/actions/toggle_plan.php" class="inline">
              <?= csrfField() ?>
              <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
              <button type="submit"
                      class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                             <?= $plan['is_active'] ? 'bg-orange-500/10 border border-orange-500/20 text-orange-400 hover:bg-orange-500/20' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20' ?>">
                <?= $plan['is_active'] ? 'Ocultar' : 'Publicar' ?>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Payment methods reference -->
        <div class="bg-surface-card border border-brand-500/10 rounded-2xl p-6 mt-6">
          <h3 class="font-bold text-white mb-4">📋 Datos de Pago Configurados</h3>
          <div class="space-y-3">
            <?php foreach (PAYMENT_CONFIG as $key => $method): ?>
            <div class="flex items-start gap-3 p-3 bg-surface rounded-xl">
              <span class="text-xl flex-shrink-0"><?= $method['icon'] ?></span>
              <div class="min-w-0 text-xs">
                <p class="font-semibold text-white"><?= e($method['label']) ?></p>
                <?php if (isset($method['phone'])): ?>
                <p class="text-gray-500">Teléfono: <span class="text-gray-300 font-mono"><?= e($method['phone']) ?></span></p>
                <p class="text-gray-500">Titular: <?= e($method['holder_name']) ?></p>
                <?php elseif (isset($method['cci'])): ?>
                <p class="text-gray-500">Cuenta: <span class="text-gray-300 font-mono"><?= e($method['account']) ?></span></p>
                <p class="text-gray-500">CCI: <span class="text-gray-300 font-mono"><?= e($method['cci']) ?></span></p>
                <p class="text-gray-500">Titular: <?= e($method['holder_name']) ?></p>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <p class="text-xs text-gray-600 mt-4">
            💡 Para modificar estos datos edita <code class="text-brand-400">config/config.php</code> → constante <code class="text-brand-400">PAYMENT_CONFIG</code>.
          </p>
        </div>
      </div>
    </div>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
