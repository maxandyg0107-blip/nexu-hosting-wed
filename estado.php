<?php
/**
 * NEXU HOSTING - Estado del Sistema
 */
require_once __DIR__ . '/config/bootstrap.php';

$stmt = db()->prepare("SELECT * FROM service_status ORDER BY type DESC, name ASC");
$stmt->execute();
$services = $stmt->fetchAll();

$statusMap = [
    'operational' => ['label'=>'Operacional',   'class'=>'bg-emerald-500/15 text-emerald-400 border-emerald-500/30','dot'=>'bg-emerald-500'],
    'degraded'    => ['label'=>'Degradado',      'class'=>'bg-yellow-500/15 text-yellow-400 border-yellow-500/30',  'dot'=>'bg-yellow-500'],
    'maintenance' => ['label'=>'Mantenimiento',  'class'=>'bg-blue-500/15 text-blue-400 border-blue-500/30',        'dot'=>'bg-blue-500'],
    'outage'      => ['label'=>'Caído',          'class'=>'bg-red-500/15 text-red-400 border-red-500/30',           'dot'=>'bg-red-500'],
];

$allOk = !in_array(false, array_map(fn($s) => $s['status']==='operational', $services), true);

$page_title = 'Estado del Sistema';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>
<main class="flex-1 pt-24 pb-16">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">

    <!-- Overall status -->
    <div class="text-center mb-12 animate-fade-in">
      <div class="inline-flex items-center gap-3 px-6 py-4 rounded-2xl border
                  <?= $allOk ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-yellow-500/10 border-yellow-500/20' ?> mb-5">
        <span class="w-4 h-4 rounded-full <?= $allOk ? 'bg-emerald-500 animate-pulse' : 'bg-yellow-500' ?>"></span>
        <span class="text-lg font-bold <?= $allOk ? 'text-emerald-400' : 'text-yellow-400' ?>">
          <?= $allOk ? 'Todos los sistemas operacionales' : 'Algunos sistemas con incidencias' ?>
        </span>
      </div>
      <p class="text-gray-500 text-sm">Última actualización: <?= e(date('d/m/Y H:i')) ?> (Hora Lima)</p>
    </div>

    <!-- Services list -->
    <div class="space-y-3">
      <?php foreach ($services as $svc):
        $s = $statusMap[$svc['status']] ?? $statusMap['operational'];
        $uptimePct = (float)($svc['uptime_pct'] ?? 0);
      ?>
      <div class="flex items-center justify-between p-5 bg-surface-card border border-surface-border rounded-xl
                  hover:border-brand-500/20 transition-colors animate-fade-in">
        <div class="flex items-center gap-4">
          <span class="w-2.5 h-2.5 rounded-full <?= $s['dot'] ?> <?= $svc['status']==='operational' ? 'animate-pulse' : '' ?>"></span>
          <div>
            <p class="font-semibold text-white"><?= e($svc['name']) ?></p>
            <?php if ($svc['location']): ?>
            <p class="text-xs text-gray-500"><?= e($svc['location']) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="hidden sm:block text-right">
            <p class="text-xs text-gray-500">Uptime</p>
            <p class="text-sm font-bold text-white"><?= number_format($uptimePct, 2) ?>%</p>
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?= $s['class'] ?>">
            <?= e($s['label']) ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Uptime summary bars -->
    <div class="mt-10 p-6 bg-surface-card border border-surface-border rounded-2xl">
      <h3 class="font-bold text-white mb-5 text-sm uppercase tracking-wider">Uptime promedio (30 días)</h3>
      <?php foreach ($services as $svc):
        $uptimePct = (float)($svc['uptime_pct'] ?? 0);
        $barWidth  = min(100, max(0, $uptimePct));
      ?>
      <div class="flex items-center gap-4 mb-3 last:mb-0">
        <div class="w-36 text-xs text-gray-400 truncate flex-shrink-0"><?= e($svc['name']) ?></div>
        <div class="flex-1 h-2 bg-surface rounded-full overflow-hidden">
          <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-700"
               style="width:<?= $barWidth ?>%"></div>
        </div>
        <div class="text-xs font-bold text-white w-16 text-right"><?= number_format($uptimePct, 2) ?>%</div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>