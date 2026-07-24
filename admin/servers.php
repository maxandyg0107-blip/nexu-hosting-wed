<?php
/**
 * NEXU HOSTING - Admin: Gestión de Servidores
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl       = new AdminController();
$statusFilter = sanitize($_GET['status'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$data       = $ctrl->getServers($statusFilter, $page);
$statuses   = ['installing'=>'Instalando','active'=>'Activo','suspended'=>'Suspendido','terminated'=>'Terminado'];

$page_title = 'Gestión de Servidores';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between py-6 animate-fade-in">
      <div>
        <h1 class="text-2xl font-extrabold text-white">Gestión de Servidores</h1>
        <p class="text-gray-400 text-sm mt-1"><?= $data['pagination']['total'] ?> servidor(es) en total</p>
      </div>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <!-- Status filter -->
    <div class="flex gap-2 flex-wrap mb-6">
      <a href="/admin/servers.php"
         class="px-4 py-2 rounded-xl text-xs font-semibold transition-all
                <?= !$statusFilter ? 'bg-brand-600 text-white' : 'bg-surface-card border border-surface-border text-gray-400 hover:text-white' ?>">
        Todos
      </a>
      <?php foreach ($statuses as $st => $lb): ?>
      <a href="?status=<?= $st ?>"
         class="px-4 py-2 rounded-xl text-xs font-semibold transition-all
                <?= $statusFilter === $st ? 'bg-brand-600 text-white' : 'bg-surface-card border border-surface-border text-gray-400 hover:text-white' ?>">
        <?= $lb ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="space-y-4">
      <?php foreach ($data['servers'] as $srv): ?>
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6 hover:border-brand-500/30 transition-colors animate-fade-in">
        <div class="grid lg:grid-cols-3 gap-6">

          <!-- Server info -->
          <div class="lg:col-span-2">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-600/30 to-cyan-500/30 border border-brand-500/20 flex items-center justify-center text-lg">
                <?= $srv['plan_type'] === 'minecraft' ? '🎮' : '🌐' ?>
              </div>
              <div>
                <h3 class="font-bold text-white"><?= e($srv['server_name']) ?></h3>
                <p class="text-xs text-gray-500"><?= e($srv['username']) ?> · <?= e($srv['plan_name']) ?></p>
              </div>
              <div class="ml-auto"><?= statusBadge($srv['status']) ?></div>
            </div>

            <div class="grid sm:grid-cols-3 gap-3 text-xs mb-4">
              <div class="bg-surface rounded-lg p-3">
                <p class="text-gray-500 mb-0.5">IP del nodo</p>
                <p class="font-mono text-gray-300"><?= e($srv['node_ip'] ?: '—') ?></p>
              </div>
              <div class="bg-surface rounded-lg p-3">
                <p class="text-gray-500 mb-0.5">IP del servidor</p>
                <p class="font-mono text-gray-300"><?= e($srv['server_ip'] ?: '—') ?><?= $srv['server_port'] ? ':'.$srv['server_port'] : '' ?></p>
              </div>
              <div class="bg-surface rounded-lg p-3">
                <p class="text-gray-500 mb-0.5">Pterodactyl ID</p>
                <p class="font-mono text-gray-300"><?= e($srv['pterodactyl_server_id'] ?: '—') ?></p>
              </div>
            </div>

            <!-- Metrics -->
            <div class="grid grid-cols-3 gap-3">
              <?php
              $metrics = [
                ['l'=>'RAM',  'v'=>$srv['ram_used_percent'],  'c'=>'from-brand-700 to-brand-400'],
                ['l'=>'CPU',  'v'=>$srv['cpu_used_percent'],  'c'=>'from-cyan-700 to-cyan-400'],
                ['l'=>'Disco','v'=>$srv['disk_used_percent'], 'c'=>'from-emerald-700 to-emerald-400'],
              ];
              foreach ($metrics as $m):
              ?>
              <div>
                <div class="flex justify-between text-xs mb-1">
                  <span class="text-gray-500"><?= $m['l'] ?></span>
                  <span class="text-gray-300 font-bold"><?= $m['v'] ?>%</span>
                </div>
                <div class="h-1.5 bg-surface rounded-full overflow-hidden">
                  <div class="h-full rounded-full bg-gradient-to-r <?= $m['c'] ?> transition-all duration-700"
                       style="width:<?= min(100, max(0, $m['v'])) ?>%"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Actions panel -->
          <div class="space-y-3">
            <!-- Change status -->
            <form method="POST" action="/admin/actions/update_server_status.php" class="flex gap-2">
              <?= csrfField() ?>
              <input type="hidden" name="server_id" value="<?= $srv['id'] ?>">
              <select name="status"
                      class="flex-1 px-3 py-2 rounded-xl bg-surface border border-surface-border text-gray-300 text-xs focus:outline-none focus:border-brand-500 transition-all">
                <?php foreach ($statuses as $st => $lb): ?>
                <option value="<?= $st ?>" <?= $srv['status'] === $st ? 'selected' : '' ?>><?= $lb ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit"
                      class="px-3 py-2 rounded-xl bg-brand-600/20 border border-brand-500/30 text-brand-400 text-xs font-semibold hover:bg-brand-600/30 transition-colors whitespace-nowrap">
                Guardar
              </button>
            </form>

            <!-- Assign node -->
            <details class="group">
              <summary class="cursor-pointer px-4 py-2.5 rounded-xl border border-surface-border text-gray-400 text-xs font-semibold
                              hover:border-brand-500/40 hover:text-white transition-colors list-none text-center">
                ⚙ Asignar nodo / Pterodactyl
              </summary>
              <form method="POST" action="/admin/actions/update_server_node.php" class="mt-3 space-y-2">
                <?= csrfField() ?>
                <input type="hidden" name="server_id" value="<?= $srv['id'] ?>">
                <input type="text" name="node_ip" value="<?= e($srv['node_ip'] ?? '') ?>" placeholder="IP del nodo (ej: 192.168.1.10)"
                       class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs placeholder-gray-600 focus:outline-none focus:border-brand-500">
                <input type="text" name="server_ip" value="<?= e($srv['server_ip'] ?? '') ?>" placeholder="IP pública del servidor"
                       class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs placeholder-gray-600 focus:outline-none focus:border-brand-500">
                <input type="number" name="pterodactyl_server_id" value="<?= e($srv['pterodactyl_server_id'] ?? '') ?>" placeholder="ID en Pterodactyl Panel"
                       class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs placeholder-gray-600 focus:outline-none focus:border-brand-500">
                <input type="url" name="panel_url" value="<?= e($srv['panel_url'] ?? '') ?>" placeholder="URL del panel (https://panel.nexuhosting.com)"
                       class="w-full px-3 py-2 rounded-lg bg-surface border border-surface-border text-white text-xs placeholder-gray-600 focus:outline-none focus:border-brand-500">
                <button type="submit"
                        class="w-full py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500 transition-colors">
                  Guardar y activar
                </button>
              </form>
            </details>

            <?php if ($srv['expires_at']): ?>
            <p class="text-xs text-gray-600 text-center">
              Vence: <span class="text-gray-400"><?= formatDate($srv['expires_at']) ?></span>
            </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($data['servers'])): ?>
      <div class="bg-surface-card border border-surface-border rounded-2xl p-12 text-center">
        <p class="text-gray-500">No hay servidores<?= $statusFilter ? ' con estado "'.e($statuses[$statusFilter]??$statusFilter).'"' : '' ?>.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php $pg = $data['pagination']; if ($pg['total_pages'] > 1): ?>
    <div class="flex items-center justify-center gap-3 mt-6">
      <?php if ($pg['has_prev']): ?>
      <a href="?page=<?= $pg['current_page']-1 ?><?= $statusFilter ? '&status='.urlencode($statusFilter):'' ?>"
         class="px-4 py-2 rounded-xl border border-surface-border text-gray-400 text-sm hover:text-white transition-colors">← Anterior</a>
      <?php endif; ?>
      <span class="text-sm text-gray-500">Página <?= $pg['current_page'] ?> de <?= $pg['total_pages'] ?></span>
      <?php if ($pg['has_next']): ?>
      <a href="?page=<?= $pg['current_page']+1 ?><?= $statusFilter ? '&status='.urlencode($statusFilter):'' ?>"
         class="px-4 py-2 rounded-xl border border-surface-border text-gray-400 text-sm hover:text-white transition-colors">Siguiente →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
