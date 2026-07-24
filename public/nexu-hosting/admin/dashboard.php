<?php
/**
 * NEXU HOSTING - Admin Dashboard
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl = new AdminController();
$data = $ctrl->getDashboardData();
$rev  = $data['revenue'];

$page_title = 'Admin Dashboard';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>
<div class="min-h-screen flex">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-12 px-4 sm:px-8 ml-0 lg:ml-64">
  <div class="max-w-7xl mx-auto">

    <div class="py-6 animate-fade-in">
      <h1 class="text-2xl font-extrabold text-white">Dashboard Administrativo</h1>
      <p class="text-gray-400 text-sm mt-1"><?= date('l, d \d\e F \d\e Y') ?></p>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <?php
      $kpis = [
        ['label'=>'Ingresos hoy',       'val'=>'S/ '.number_format($rev['today_pen'],2),   'icon'=>'💰','sub'=>'PEN','color'=>'emerald'],
        ['label'=>'Ingresos este mes',  'val'=>'S/ '.number_format($rev['month_pen'],2),   'icon'=>'📈','sub'=>'PEN','color'=>'brand'],
        ['label'=>'Órdenes pendientes', 'val'=>$rev['pending_count'],                       'icon'=>'⏳','sub'=>'verificar','color'=>'yellow'],
        ['label'=>'Total órdenes',      'val'=>$rev['total_orders'],                        'icon'=>'📦','sub'=>'histórico','color'=>'cyan'],
      ];
      foreach ($kpis as $i => $k):
      ?>
      <div class="bg-surface-card border border-surface-border rounded-2xl p-5 animate-fade-in"
           style="animation-delay:<?= $i*0.08 ?>s">
        <div class="flex items-center justify-between mb-2">
          <span class="text-2xl"><?= $k['icon'] ?></span>
          <?= statusBadge($k['color'] === 'yellow' ? 'pending' : 'active') ?>
        </div>
        <p class="text-2xl font-extrabold text-white mt-2"><?= $k['val'] ?></p>
        <p class="text-xs text-gray-500 mt-0.5"><?= $k['label'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
      <!-- Revenue chart -->
      <div class="lg:col-span-2 bg-surface-card border border-surface-border rounded-2xl p-6">
        <h3 class="font-bold text-white mb-5">Ingresos por mes (S/ PEN)</h3>
        <?php
        $monthly = array_reverse($data['monthly_revenue']);
        $maxRev  = max(array_column($monthly, 'revenue') ?: [1]);
        ?>
        <div class="flex items-end gap-2 h-40">
          <?php foreach ($monthly as $m): ?>
            <?php $h = $maxRev > 0 ? round(($m['revenue'] / $maxRev) * 100) : 0; ?>
            <div class="flex-1 flex flex-col items-center gap-1">
              <div class="w-full rounded-t-md bg-gradient-to-t from-brand-700 to-brand-500 transition-all duration-700 hover:from-brand-600 hover:to-brand-400"
                   style="height:<?= max(4,$h) ?>%" title="S/ <?= number_format($m['revenue'],2) ?>"></div>
              <span class="text-xs text-gray-600 whitespace-nowrap"><?= substr($m['month'],-2) ?>/<?= substr($m['month'],2,2) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- User / server stats -->
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <h3 class="font-bold text-white mb-4">Estado del sistema</h3>
        <div class="space-y-4">
          <?php
          $systemStats = [
            ['label'=>'Usuarios totales', 'val'=>$data['user_stats']['total'],    'sub'=>$data['user_stats']['today'].' hoy',    'color'=>'brand'],
            ['label'=>'Activos',          'val'=>$data['user_stats']['active'],   'sub'=>$data['user_stats']['suspended'].' susp.','color'=>'emerald'],
            ['label'=>'Servidores',       'val'=>$data['server_stats']['total'],  'sub'=>$data['server_stats']['active'].' activos','color'=>'cyan'],
            ['label'=>'Instalando',       'val'=>$data['server_stats']['installing'],'sub'=>'en proceso',                        'color'=>'yellow'],
          ];
          foreach ($systemStats as $st):
          ?>
          <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
            <span class="text-sm text-gray-400"><?= $st['label'] ?></span>
            <div class="text-right">
              <span class="font-bold text-white"><?= $st['val'] ?></span>
              <span class="text-xs text-gray-500 ml-1"><?= $st['sub'] ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Recent orders + audit log -->
    <div class="grid lg:grid-cols-2 gap-6">
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-white">Órdenes recientes</h3>
          <a href="/admin/orders.php" class="text-xs text-brand-400 hover:text-brand-300">Ver todas →</a>
        </div>
        <div class="space-y-2">
          <?php foreach (array_slice($data['recent_orders'], 0, 8) as $ord): ?>
          <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
            <div>
              <p class="text-sm font-medium text-white"><?= e($ord['username']) ?></p>
              <p class="text-xs text-gray-500"><?= e($ord['plan_name']) ?></p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-white">S/ <?= number_format($ord['amount_pen'],2) ?></p>
              <?= statusBadge($ord['status']) ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-white">Auditoría reciente</h3>
          <a href="/admin/audit.php" class="text-xs text-brand-400 hover:text-brand-300">Ver todo →</a>
        </div>
        <div class="space-y-2 overflow-y-auto max-h-72">
          <?php foreach ($data['audit_log'] as $log): ?>
          <div class="flex items-start gap-3 py-2 border-b border-surface-border last:border-0">
            <div class="w-2 h-2 rounded-full bg-brand-500 mt-1.5 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-mono text-brand-400"><?= e($log['action']) ?></p>
              <p class="text-xs text-gray-500 truncate"><?= e($log['username'] ?? 'sistema') ?> · <?= timeAgo($log['created_at']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</main>
</div>
<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>
