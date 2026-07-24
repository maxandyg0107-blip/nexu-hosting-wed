<?php
/**
 * NEXU HOSTING - Panel de Control del Cliente
 */
require_once __DIR__ . '/config/bootstrap.php';
requireLogin();

$user    = currentUser();
$section = sanitize($_GET['section'] ?? 'overview');

$servers = (new ServerModel())->getByUser($user['id']);
$orders  = (new OrderModel())->getByUser($user['id']);

// Separar servidores por estado
$activeServers   = array_filter($servers, fn($s) => $s['status'] === 'active');
$pendingOrders   = array_filter($orders,  fn($o) => $o['status'] === 'pending');
$installingServers = array_filter($servers, fn($s) => $s['status'] === 'installing');
$suspendedServers = array_filter($servers, fn($s) => $s['status'] === 'suspended');

$page_title = 'Mi Dashboard';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>
<main class="flex-1 pt-20 pb-12">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

<!-- Header -->
<div class="flex items-center justify-between py-8 animate-fade-in">
  <div>
    <h1 class="text-2xl font-extrabold text-white">
      Hola, <?= e($user['username']) ?> 👋
    </h1>
    <p class="text-gray-400 text-sm mt-1">Panel de control de Nexu Hosting</p>
  </div>
  <a href="/planes.php"
     class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600/20 border border-brand-500/30
            text-brand-400 text-sm font-semibold hover:bg-brand-600/30 transition-colors">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nuevo servidor
  </a>
</div>

<div data-flash><?= renderFlash() ?></div>

<!-- Stats grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <?php
  $stats = [
    ['label'=>'Servidores activos',   'val'=>count($activeServers),   'icon'=>'🟢','color'=>'emerald'],
    ['label'=>'Instalando',           'val'=>count($installingServers),'icon'=>'⚙️','color'=>'blue'],
    ['label'=>'Pagos pendientes',     'val'=>count($pendingOrders),    'icon'=>'⏳','color'=>'yellow'],
    ['label'=>'Total servicios',      'val'=>count($servers),          'icon'=>'📦','color'=>'brand'],
  ];
  foreach ($stats as $i => $s):
  ?>
  <div class="bg-surface-card border border-surface-border rounded-2xl p-5 animate-fade-in"
       style="animation-delay:<?= $i * 0.08 ?>s">
    <div class="flex items-center justify-between mb-3">
      <span class="text-2xl"><?= $s['icon'] ?></span>
      <span class="text-3xl font-extrabold text-white"><?= $s['val'] ?></span>
    </div>
    <p class="text-sm text-gray-400"><?= $s['label'] ?></p>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tab navigation -->
<div class="flex gap-1 bg-surface-card border border-surface-border rounded-xl p-1 mb-8 overflow-x-auto">
  <?php
  $tabs = [
    'overview'  => ['icon'=>'🏠','label'=>'Resumen'],
    'servers'   => ['icon'=>'🖥️','label'=>'Mis Servidores'],
    'orders'    => ['icon'=>'📋','label'=>'Pedidos'],
    'profile'   => ['icon'=>'👤','label'=>'Mi Perfil'],
  ];
  foreach ($tabs as $key => $tab):
  ?>
  <a href="?section=<?= $key ?>"
     class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-all
            <?= $section === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
    <?= $tab['icon'] ?> <?= $tab['label'] ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- ── OVERVIEW ── -->
<?php if ($section === 'overview'): ?>
<div class="grid lg:grid-cols-2 gap-6">

  <!-- Active servers mini list -->
  <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
    <h2 class="font-bold text-white mb-4 flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
      Servidores Activos
    </h2>
    <?php if (empty($activeServers)): ?>
      <div class="text-center py-8">
        <p class="text-gray-500 text-sm mb-4">Aún no tienes servidores activos.</p>
        <a href="/planes.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:-translate-y-0.5 transition-transform">
          Ver planes disponibles
        </a>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach (array_slice($activeServers, 0, 3, true) as $srv): ?>
        <div class="flex items-center justify-between p-3 bg-surface rounded-xl border border-surface-border">
          <div>
            <p class="font-medium text-white text-sm"><?= e($srv['server_name']) ?></p>
            <p class="text-xs text-gray-500"><?= e($srv['plan_name']) ?> · <?= e($srv['server_ip'] ?? 'IP asignándose...') ?></p>
          </div>
          <div class="flex items-center gap-2">
            <?= statusBadge($srv['status']) ?>
            <?php if ($srv['panel_url']): ?>
            <a href="<?= e($srv['panel_url']) ?>" target="_blank" rel="noopener"
               class="text-xs px-2.5 py-1 rounded-lg bg-brand-600/20 text-brand-400 border border-brand-500/30 hover:bg-brand-600/30 transition-colors font-medium">
              Panel
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pending orders -->
  <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
    <h2 class="font-bold text-white mb-4">⏳ Pagos Pendientes de Verificación</h2>
    <?php if (empty($pendingOrders)): ?>
      <p class="text-gray-500 text-sm py-8 text-center">No hay pagos pendientes.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($pendingOrders as $ord): ?>
        <div class="p-3 bg-surface rounded-xl border border-yellow-500/20">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-white text-sm"><?= e($ord['plan_name']) ?></p>
              <p class="text-xs text-gray-500">Pedido el <?= formatDateTime($ord['created_at']) ?></p>
            </div>
            <div class="text-right">
              <p class="font-bold text-yellow-400">S/ <?= number_format($ord['amount_pen'],2) ?></p>
              <?= statusBadge($ord['status']) ?>
            </div>
          </div>
          <p class="text-xs text-yellow-400/70 mt-2">
            🔍 Revisaremos tu comprobante y activaremos el servicio en menos de 2 horas.
          </p>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── SERVERS TAB ── -->
<?php elseif ($section === 'servers'): ?>
<div class="space-y-4">
  <h2 class="text-xl font-bold text-white">Mis Servidores</h2>
  <?php if (empty($servers)): ?>
    <div class="bg-surface-card border border-surface-border rounded-2xl p-12 text-center">
      <p class="text-5xl mb-4">🎮</p>
      <p class="text-white font-semibold mb-2">Aún no tienes servidores</p>
      <p class="text-gray-400 text-sm mb-6">Contrata tu primer servidor y empieza a jugar en minutos.</p>
      <a href="/planes.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold hover:-translate-y-0.5 transition-transform shadow-lg shadow-brand-600/30">
        Ver Planes
      </a>
    </div>
  <?php else: ?>
    <?php foreach ($servers as $srv): ?>
    <div class="bg-surface-card border border-surface-border rounded-2xl p-6 animate-fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600/30 to-cyan-500/30 border border-brand-500/20 flex items-center justify-center text-xl">
            <?= $srv['plan_type'] === 'minecraft' ? '🎮' : '🌐' ?>
          </div>
          <div>
            <h3 class="font-bold text-white"><?= e($srv['server_name']) ?></h3>
            <p class="text-xs text-gray-500"><?= e($srv['plan_name']) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <?= statusBadge($srv['status']) ?>
          <?php if ($srv['panel_url']): ?>
          <a href="<?= e($srv['panel_url']) ?>" target="_blank" rel="noopener"
             class="px-4 py-2 rounded-xl bg-brand-600 text-white text-sm font-semibold hover:-translate-y-0.5 transition-transform shadow-lg shadow-brand-600/30">
            Abrir Panel Pterodactyl →
          </a>
          <?php endif; ?>
        </div>
      </div>
      <!-- Metrics bars -->
      <div class="grid grid-cols-3 gap-4">
        <?php
        $metrics = [
          ['label'=>'RAM','val'=>$srv['ram_used_percent'],'color'=>'brand'],
          ['label'=>'CPU','val'=>$srv['cpu_used_percent'],'color'=>'cyan'],
          ['label'=>'Disco','val'=>$srv['disk_used_percent'],'color'=>'emerald'],
        ];
        foreach ($metrics as $m):
          $barColor = match($m['color']) {
            'brand'   => 'from-brand-600 to-brand-400',
            'cyan'    => 'from-cyan-600 to-cyan-400',
            'emerald' => 'from-emerald-600 to-emerald-400',
            default   => 'from-gray-600 to-gray-400',
          };
          $textColor = match($m['color']) {
            'brand'=>'text-brand-400','cyan'=>'text-cyan-400','emerald'=>'text-emerald-400',default=>'text-gray-400'
          };
        ?>
        <div>
          <div class="flex justify-between items-center mb-1.5">
            <span class="text-xs text-gray-400"><?= $m['label'] ?></span>
            <span class="text-xs font-bold <?= $textColor ?>"><?= $m['val'] ?>%</span>
          </div>
          <div class="h-2 bg-surface rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r <?= $barColor ?> transition-all duration-700"
                 style="width:<?= min(100, max(0, $m['val'])) ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($srv['server_ip']): ?>
      <div class="mt-4 pt-4 border-t border-surface-border flex items-center gap-4 text-xs text-gray-500">
        <span>IP: <code class="text-gray-300 font-mono"><?= e($srv['server_ip']) ?><?= $srv['server_port'] ? ':' . $srv['server_port'] : '' ?></code></span>
        <?php if ($srv['expires_at']): ?>
        <span>Vence: <span class="text-gray-300"><?= formatDate($srv['expires_at']) ?></span></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ── ORDERS TAB ── -->
<?php elseif ($section === 'orders'): ?>
<div>
  <h2 class="text-xl font-bold text-white mb-6">Historial de Pedidos</h2>
  <?php if (empty($orders)): ?>
    <p class="text-gray-400 text-center py-12">No tienes pedidos aún.</p>
  <?php else: ?>
  <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-surface-border">
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">#</th>
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Plan</th>
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Método</th>
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Monto</th>
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Estado</th>
            <th class="text-left px-5 py-4 text-xs text-gray-500 font-semibold uppercase tracking-wider">Fecha</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-border">
          <?php foreach ($orders as $ord): ?>
          <tr class="hover:bg-surface-hover transition-colors">
            <td class="px-5 py-4 font-mono text-gray-400 text-xs"><?= e($ord['invoice_number'] ?? '#'.$ord['id']) ?></td>
            <td class="px-5 py-4">
              <span class="font-medium text-white"><?= e($ord['plan_name']) ?></span>
              <span class="block text-xs text-gray-500 capitalize"><?= e($ord['billing_cycle']) ?></span>
            </td>
            <td class="px-5 py-4 text-gray-400 capitalize"><?= e(str_replace('_',' ',$ord['payment_method'])) ?></td>
            <td class="px-5 py-4 font-bold text-white">S/ <?= number_format($ord['amount_pen'],2) ?></td>
            <td class="px-5 py-4"><?= statusBadge($ord['status']) ?></td>
            <td class="px-5 py-4 text-gray-500 text-xs"><?= formatDateTime($ord['created_at']) ?></td>
          </tr>
          <?php if ($ord['status'] === 'rejected' && $ord['admin_notes']): ?>
          <tr class="bg-red-500/5">
            <td colspan="6" class="px-5 py-2">
              <p class="text-xs text-red-400">❌ Motivo de rechazo: <?= e($ord['admin_notes']) ?></p>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ── PROFILE TAB ── -->
<?php elseif ($section === 'profile'): ?>
<div class="max-w-2xl">
  <h2 class="text-xl font-bold text-white mb-6">Mi Perfil</h2>
  <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
    <?php
    $fields = [
      'Usuario'   => $user['username'],
      'Email'     => $user['email'],
      'Nombre'    => $user['full_name'] ?: '—',
      'Teléfono'  => $user['phone'] ?: '—',
      'País'      => $user['country'] ?: '—',
      'Moneda'    => $user['preferred_currency'],
      'Miembro desde' => formatDate($user['created_at']),
    ];
    foreach ($fields as $label => $val):
    ?>
    <div class="flex items-center justify-between py-3 border-b border-surface-border last:border-0">
      <span class="text-sm text-gray-400"><?= e($label) ?></span>
      <span class="text-sm font-medium text-white"><?= e($val) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</div><!-- /max-w-7xl -->
</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>
