<?php
/**
 * NEXU HOSTING - Admin: Gestión de Clientes
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl   = new AdminController();
$search = sanitize($_GET['q']    ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$data   = $ctrl->getClients($page, $search);

$page_title = 'Gestión de Clientes';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between py-6 animate-fade-in">
      <div>
        <h1 class="text-2xl font-extrabold text-white">Gestión de Clientes</h1>
        <p class="text-gray-400 text-sm mt-1"><?= $data['pagination']['total'] ?> cliente(s) registrados</p>
      </div>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <!-- Search -->
    <form method="GET" class="mb-6">
      <div class="flex gap-3 max-w-md">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por usuario, email o nombre..."
               class="flex-1 px-4 py-2.5 rounded-xl bg-surface-card border border-surface-border text-white placeholder-gray-500 text-sm
                      focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all">
        <button type="submit"
                class="px-4 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:bg-brand-500 transition-colors">
          Buscar
        </button>
        <?php if ($search): ?>
        <a href="/admin/clients.php" class="px-4 py-2.5 rounded-xl border border-surface-border text-gray-400 text-sm hover:text-white transition-colors">
          Limpiar
        </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Table -->
    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-surface-border">
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Usuario</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Email</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Moneda</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Estado</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Último login</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Registro</th>
              <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <?php foreach ($data['users'] as $u): ?>
            <tr class="hover:bg-surface-hover transition-colors group">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-600/40 to-cyan-500/40 border border-brand-500/20 flex items-center justify-center text-xs font-bold text-brand-300 flex-shrink-0">
                    <?= mb_strtoupper(mb_substr($u['username'], 0, 1)) ?>
                  </div>
                  <div>
                    <p class="font-medium text-white"><?= e($u['username']) ?></p>
                    <?php if ($u['full_name']): ?>
                    <p class="text-xs text-gray-500"><?= e($u['full_name']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4 text-gray-400"><?= e($u['email']) ?></td>
              <td class="px-5 py-4">
                <span class="px-2 py-0.5 rounded-full bg-surface border border-surface-border text-xs text-gray-400 font-mono">
                  <?= e($u['preferred_currency']) ?>
                </span>
              </td>
              <td class="px-5 py-4"><?= statusBadge($u['status']) ?></td>
              <td class="px-5 py-4 text-gray-500 text-xs">
                <?= $u['last_login_at'] ? timeAgo($u['last_login_at']) : 'Nunca' ?>
              </td>
              <td class="px-5 py-4 text-gray-500 text-xs"><?= formatDate($u['created_at']) ?></td>
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <?php if ($u['status'] === 'active'): ?>
                  <form method="POST" action="/admin/actions/suspend_client.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit"
                            class="px-3 py-1.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold hover:bg-orange-500/20 transition-colors"
                            onclick="return confirm('¿Suspender la cuenta de <?= e($u['username']) ?>?')">
                      Suspender
                    </button>
                  </form>
                  <?php else: ?>
                  <form method="POST" action="/admin/actions/activate_client.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit"
                            class="px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold hover:bg-emerald-500/20 transition-colors">
                      Reactivar
                    </button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php $pg = $data['pagination']; ?>
      <?php if ($pg['total_pages'] > 1): ?>
      <div class="flex items-center justify-between px-5 py-4 border-t border-surface-border">
        <p class="text-xs text-gray-500">
          Mostrando <?= ($pg['offset'] + 1) ?>–<?= min($pg['offset'] + $pg['per_page'], $pg['total']) ?> de <?= $pg['total'] ?>
        </p>
        <div class="flex gap-2">
          <?php if ($pg['has_prev']): ?>
          <a href="?page=<?= $pg['current_page'] - 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>"
             class="px-3 py-1.5 rounded-lg bg-surface border border-surface-border text-gray-400 text-xs hover:text-white transition-colors">
            ← Anterior
          </a>
          <?php endif; ?>
          <span class="px-3 py-1.5 text-xs text-gray-400">Página <?= $pg['current_page'] ?> de <?= $pg['total_pages'] ?></span>
          <?php if ($pg['has_next']): ?>
          <a href="?page=<?= $pg['current_page'] + 1 ?><?= $search ? '&q='.urlencode($search) : '' ?>"
             class="px-3 py-1.5 rounded-lg bg-surface border border-surface-border text-gray-400 text-xs hover:text-white transition-colors">
            Siguiente →
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
