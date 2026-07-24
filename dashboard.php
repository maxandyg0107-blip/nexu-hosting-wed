<?php
/**
 * dashboard.php
 * Panel de control del cliente: servidores, órdenes y métricas de consumo.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$userId = (int) $_SESSION['user_id'];

$serverModel = new Server();
$orderModel  = new Order();

$servers = $serverModel->findByUser($userId);
$orders  = $orderModel->findByUser($userId);
$counts  = $serverModel->countByStatusForUser($userId);

// Nota: Pterodactyl Panel gestiona la consola, archivos y BD secundaria de cada
// servidor de Minecraft. La URL se construye a partir del ID de servidor en
// Pterodactyl (pterodactyl_server_id), configurado por el admin tras aprovisionar.
define('PTERODACTYL_PANEL_URL', 'https://panel.nexuhosting.com');

$pageTitle = 'Mi Panel';
require __DIR__ . '/views/layouts/header.php';
?>
<main class="max-w-6xl mx-auto px-6 py-12">
  <h1 class="font-display text-2xl font-semibold text-white mb-1">Hola, <?= e($_SESSION['username']) ?> 👋</h1>
  <p class="text-slate-400 text-sm mb-8">Este es el estado actual de tus servicios contratados.</p>

  <!-- Resumen -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-10">
    <div class="rounded-xl border border-line bg-surface p-4">
      <p class="text-xs font-mono text-slate-500">Instalando</p>
      <p class="font-display text-2xl font-bold text-blue-400 mt-1"><?= $counts['installing'] ?></p>
    </div>
    <div class="rounded-xl border border-line bg-surface p-4">
      <p class="text-xs font-mono text-slate-500">Activos</p>
      <p class="font-display text-2xl font-bold text-emerald-400 mt-1"><?= $counts['active'] ?></p>
    </div>
    <div class="rounded-xl border border-line bg-surface p-4">
      <p class="text-xs font-mono text-slate-500">Suspendidos</p>
      <p class="font-display text-2xl font-bold text-amber mt-1"><?= $counts['suspended'] ?></p>
    </div>
    <div class="rounded-xl border border-line bg-surface p-4">
      <p class="text-xs font-mono text-slate-500">Terminados</p>
      <p class="font-display text-2xl font-bold text-red-400 mt-1"><?= $counts['terminated'] ?></p>
    </div>
  </div>

  <!-- Servidores -->
  <h2 class="font-display text-lg font-semibold text-white mb-4">Mis servidores</h2>
  <?php if (empty($servers)): ?>
    <div class="rounded-xl border border-dashed border-line p-8 text-center text-slate-500 text-sm mb-12">
      Aún no tienes servidores activos. <a href="/index.php" class="text-cyan hover:underline">Explora nuestros planes</a>.
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-12">
      <?php foreach ($servers as $s): ?>
        <div class="rounded-xl border border-line bg-surface p-5">
          <div class="flex items-start justify-between mb-3">
            <div>
              <p class="font-display font-semibold text-white"><?= e($s['server_name']) ?></p>
              <p class="text-xs text-slate-500"><?= e($s['plan_name']) ?></p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full <?= server_status_badge($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span>
          </div>

          <div class="space-y-2 text-xs font-mono">
            <div>
              <div class="flex justify-between text-slate-500 mb-1"><span>CPU</span><span><?= number_format((float)$s['cpu_usage_pct'],1) ?>%</span></div>
              <div class="h-1.5 rounded-full bg-surface2 overflow-hidden"><div class="h-full bg-cyan" style="width: <?= (float)$s['cpu_usage_pct'] ?>%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-slate-500 mb-1"><span>RAM</span><span><?= number_format((float)$s['ram_usage_pct'],1) ?>%</span></div>
              <div class="h-1.5 rounded-full bg-surface2 overflow-hidden"><div class="h-full bg-violet" style="width: <?= (float)$s['ram_usage_pct'] ?>%"></div></div>
            </div>
            <div>
              <div class="flex justify-between text-slate-500 mb-1"><span>Disco</span><span><?= number_format((float)$s['disk_usage_pct'],1) ?>%</span></div>
              <div class="h-1.5 rounded-full bg-surface2 overflow-hidden"><div class="h-full bg-amber" style="width: <?= (float)$s['disk_usage_pct'] ?>%"></div></div>
            </div>
          </div>

          <?php if ($s['plan_type'] === 'minecraft' && $s['pterodactyl_server_id']): ?>
            <a href="<?= e(PTERODACTYL_PANEL_URL) ?>/server/<?= (int)$s['pterodactyl_server_id'] ?>" target="_blank" rel="noopener noreferrer"
               class="mt-4 block text-center rounded-md bg-surface2 border border-line py-2 text-xs font-semibold hover:border-cyan/50 hover:text-cyan transition">
              Abrir consola Pterodactyl ↗
            </a>
          <?php elseif ($s['status'] === 'installing'): ?>
            <p class="mt-4 text-center text-xs text-slate-500 font-mono">Aprovisionando infraestructura…</p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Órdenes -->
  <h2 class="font-display text-lg font-semibold text-white mb-4">Historial de órdenes</h2>
  <div class="rounded-xl border border-line bg-surface overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-surface2 text-slate-500 font-mono text-xs uppercase">
        <tr>
          <th class="text-left px-4 py-3">Orden</th>
          <th class="text-left px-4 py-3">Plan</th>
          <th class="text-left px-4 py-3">Monto</th>
          <th class="text-left px-4 py-3">Método</th>
          <th class="text-left px-4 py-3">Estado</th>
          <th class="text-left px-4 py-3">Fecha</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-line/60">
        <?php if (empty($orders)): ?>
          <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Aún no registras órdenes.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr class="hover:bg-surface2/50">
            <td class="px-4 py-3 font-mono text-slate-400">#<?= (int)$o['id'] ?></td>
            <td class="px-4 py-3 text-white"><?= e($o['plan_name']) ?></td>
            <td class="px-4 py-3 font-mono"><?= money_pen((float)$o['total_amount']) ?></td>
            <td class="px-4 py-3 text-slate-400"><?= e(ucfirst(str_replace('_',' ',$o['payment_method']))) ?></td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full <?= order_status_badge($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
            <td class="px-4 py-3 text-slate-500 font-mono text-xs"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php require __DIR__ . '/views/layouts/footer.php'; ?>
