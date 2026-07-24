<?php
/**
 * NEXU HOSTING - Admin Dashboard v2.1
 * Animaciones, gráfico Chart.js, KPIs en tiempo real, auditoría en vivo.
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
requireAdmin();

$ctrl = new AdminController();
$data = $ctrl->getDashboardData();
$rev  = $data['revenue'];

// Preparar datos de gráfico (últimos 6 meses, del más antiguo al más reciente)
$chartData    = array_reverse($data['monthly_revenue']);
$chartLabels  = json_encode(array_map(fn($m) => date('M y', strtotime($m['month'].'-01')), $chartData));
$chartRevenue = json_encode(array_map(fn($m) => (float)$m['revenue'], $chartData));
$chartOrders  = json_encode(array_map(fn($m) => (int)$m['orders'],    $chartData));

$page_title = 'Admin Dashboard';
?>
<?php require_once dirname(__DIR__) . '/views/partials/head.php'; ?>

<style>
  @keyframes countUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
  .count-up{animation:countUp .6s ease-out both}
  @keyframes barGrow{from{height:0;opacity:0}to{opacity:1}}
  .kpi-card{transition:transform .2s ease,box-shadow .2s ease}
  .kpi-card:hover{transform:translateY(-3px)}
</style>

<div class="min-h-screen flex bg-surface">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-1 min-w-0 pt-4 pb-16 px-4 sm:px-8 ml-0 lg:ml-64 overflow-x-hidden">
  <div class="max-w-7xl mx-auto">

    <!-- ── Page header ─────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-7 gap-3 animate-fade-in">
      <div>
        <h1 class="text-2xl font-extrabold text-white">Dashboard Administrativo</h1>
        <p class="text-gray-500 text-sm mt-0.5">
          <?= date('l, d \d\e F \d\e Y') ?> · <?= date('H:i') ?> (Lima)
        </p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Badge órdenes pendientes con animación -->
        <?php if ((int)$rev['pending_count'] > 0): ?>
        <a href="/admin/orders.php"
           class="flex items-center gap-2 px-4 py-2 rounded-xl
                  bg-yellow-500/15 border border-yellow-500/30 text-yellow-400
                  text-sm font-bold hover:bg-yellow-500/25 transition-all animate-pulse-slow">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-yellow-500"></span>
          </span>
          <?= (int)$rev['pending_count'] ?> pago<?= $rev['pending_count'] > 1 ? 's':'' ?> pendiente<?= $rev['pending_count'] > 1 ? 's':'' ?>
        </a>
        <?php endif; ?>
        <a href="/admin/orders.php"
           class="px-4 py-2 rounded-xl bg-brand-600/20 border border-brand-500/30 text-brand-400
                  text-sm font-semibold hover:bg-brand-600/30 transition-all">
          Ver órdenes →
        </a>
      </div>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <!-- ── KPI Cards ──────────────────────────────────────── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
      <?php
      $kpis = [
        [
          'label'   => 'Ingresos hoy',
          'val'     => 'S/ '.number_format((float)$rev['today_pen'], 2),
          'sub'     => 'soles peruanos',
          'icon'    => '💰',
          'trend'   => '+12%',
          'tcolor'  => 'text-emerald-400',
          'bg'      => 'from-emerald-600/10 to-emerald-900/5',
          'border'  => 'border-emerald-500/20',
          'dot'     => 'bg-emerald-500',
        ],
        [
          'label'   => 'Ingresos del mes',
          'val'     => 'S/ '.number_format((float)$rev['month_pen'], 2),
          'sub'     => 'este mes',
          'icon'    => '📈',
          'trend'   => 'Mensual',
          'tcolor'  => 'text-brand-400',
          'bg'      => 'from-brand-600/10 to-brand-900/5',
          'border'  => 'border-brand-500/20',
          'dot'     => 'bg-brand-500',
        ],
        [
          'label'   => 'Pagos pendientes',
          'val'     => (string)(int)$rev['pending_count'],
          'sub'     => 'por verificar',
          'icon'    => '⏳',
          'trend'   => 'Acción requerida',
          'tcolor'  => 'text-yellow-400',
          'bg'      => 'from-yellow-600/10 to-yellow-900/5',
          'border'  => 'border-yellow-500/20',
          'dot'     => 'bg-yellow-500',
          'urgent'  => (int)$rev['pending_count'] > 0,
        ],
        [
          'label'   => 'Órdenes totales',
          'val'     => (string)(int)$rev['total_orders'],
          'sub'     => 'histórico',
          'icon'    => '📦',
          'trend'   => 'Acumulado',
          'tcolor'  => 'text-cyan-400',
          'bg'      => 'from-cyan-600/10 to-cyan-900/5',
          'border'  => 'border-cyan-500/20',
          'dot'     => 'bg-cyan-500',
        ],
      ];
      foreach ($kpis as $i => $k):
      ?>
      <div class="kpi-card relative bg-surface-card border <?= $k['border'] ?> rounded-2xl p-5
                  bg-gradient-to-br <?= $k['bg'] ?> overflow-hidden count-up"
           style="animation-delay:<?= $i * 0.1 ?>s">
        <?php if (!empty($k['urgent'])): ?>
        <!-- Pulso animado para alertas urgentes -->
        <div class="absolute inset-0 rounded-2xl border-2 border-yellow-500/40 animate-pulse-slow pointer-events-none"></div>
        <?php endif; ?>
        <div class="flex items-start justify-between mb-3">
          <span class="text-3xl select-none"><?= $k['icon'] ?></span>
          <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                       <?= $k['tcolor'] ?> bg-surface/50 border border-current/20">
            <?= e($k['trend']) ?>
          </span>
        </div>
        <p class="text-3xl font-black text-white leading-none mb-1">
          <?= e($k['val']) ?>
        </p>
        <p class="text-xs text-gray-500 font-medium"><?= e($k['label']) ?></p>
        <p class="text-xs text-gray-600 mt-0.5"><?= e($k['sub']) ?></p>
        <!-- Barra decorativa inferior -->
        <div class="absolute bottom-0 left-0 right-0 h-0.5 <?= $k['dot'] ?> opacity-40"></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Gráfico de ingresos + Stats ────────────────────── -->
    <div class="grid lg:grid-cols-3 gap-6 mb-8">

      <!-- Chart.js revenue chart -->
      <div class="lg:col-span-2 bg-surface-card border border-surface-border rounded-2xl p-6 animate-fade-in" style="animation-delay:.25s">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="font-bold text-white">Ingresos mensuales</h3>
            <p class="text-xs text-gray-500 mt-0.5">Últimos 6 meses en S/ PEN</p>
          </div>
          <div class="flex items-center gap-3 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-brand-500 inline-block"></span>Ingresos</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-cyan-500 inline-block"></span>Órdenes</span>
          </div>
        </div>
        <div class="relative h-52">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>

      <!-- System stats -->
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6 animate-fade-in" style="animation-delay:.3s">
        <h3 class="font-bold text-white mb-5">Estado del sistema</h3>
        <div class="space-y-4">
          <?php
          $rings = [
            ['label'=>'Usuarios totales',  'val'=>$data['user_stats']['total'],              'sub'=>$data['user_stats']['today'].' hoy',         'pct'=>min(100, $data['user_stats']['total']),  'color'=>'#8b5cf6'],
            ['label'=>'Cuentas activas',   'val'=>$data['user_stats']['active'],             'sub'=>$data['user_stats']['suspended'].' susp.',   'pct'=>$data['user_stats']['total']>0 ? round($data['user_stats']['active']/$data['user_stats']['total']*100):0, 'color'=>'#10b981'],
            ['label'=>'Servidores activos','val'=>$data['server_stats']['active'],           'sub'=>'de '.$data['server_stats']['total'].' total','pct'=>$data['server_stats']['total']>0 ? round($data['server_stats']['active']/$data['server_stats']['total']*100):0, 'color'=>'#06b6d4'],
            ['label'=>'Instalando',        'val'=>$data['server_stats']['installing'],       'sub'=>'en proceso',                                'pct'=>min(100,$data['server_stats']['installing']*10), 'color'=>'#f59e0b'],
          ];
          foreach ($rings as $st):
          ?>
          <div class="group">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-sm text-gray-400"><?= e($st['label']) ?></span>
              <div class="text-right">
                <span class="font-bold text-white"><?= $st['val'] ?></span>
                <span class="text-xs text-gray-600 ml-1"><?= e($st['sub']) ?></span>
              </div>
            </div>
            <div class="h-1.5 bg-surface rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-1000 ease-out"
                   style="width:<?= max(4, (int)$st['pct']) ?>%;background:<?= $st['color'] ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Quick actions -->
        <div class="mt-6 pt-5 border-t border-surface-border space-y-2">
          <h4 class="text-xs text-gray-600 font-semibold uppercase tracking-wider mb-3">Accesos rápidos</h4>
          <?php
          $quickLinks = [
            ['/admin/orders.php',  '📋', 'Gestionar órdenes'],
            ['/admin/clients.php', '👥', 'Ver clientes'],
            ['/admin/servers.php', '🖥️', 'Servidores'],
            ['/admin/settings.php','⚙️', 'Configuración'],
          ];
          foreach ($quickLinks as [$url, $icon, $label]):
          ?>
          <a href="<?= e($url) ?>"
             class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-gray-400
                    hover:text-white hover:bg-white/5 transition-all group/link">
            <span class="text-base group-hover/link:scale-110 transition-transform"><?= $icon ?></span>
            <?= e($label) ?>
            <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover/link:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Órdenes recientes + Auditoría ──────────────────── -->
    <div class="grid lg:grid-cols-2 gap-6 animate-fade-in" style="animation-delay:.35s">

      <!-- Órdenes recientes -->
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="font-bold text-white">Órdenes recientes</h3>
          <a href="/admin/orders.php" class="text-xs text-brand-400 hover:text-brand-300 font-semibold transition-colors">
            Ver todas →
          </a>
        </div>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
          <?php foreach (array_slice($data['recent_orders'], 0, 10) as $ord): ?>
          <div class="flex items-center gap-3 py-2.5 border-b border-surface-border last:border-0 hover:bg-surface-hover/50 rounded-lg px-2 transition-colors group">
            <!-- Avatar inicial -->
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-600/40 to-cyan-500/30 border border-brand-500/20
                        flex items-center justify-center text-xs font-bold text-brand-300 flex-shrink-0">
              <?= e(mb_strtoupper(mb_substr($ord['username'], 0, 1))) ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-white truncate"><?= e($ord['username']) ?></p>
              <p class="text-xs text-gray-500 truncate"><?= e($ord['plan_name']) ?></p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-sm font-bold text-white">S/ <?= number_format((float)$ord['amount_pen'], 2) ?></p>
              <?= statusBadge($ord['status']) ?>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($data['recent_orders'])): ?>
          <p class="text-center text-gray-500 py-8 text-sm">No hay órdenes aún.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Auditoría en tiempo real -->
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-white">Auditoría en vivo</h3>
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </div>
          <a href="/admin/audit.php" class="text-xs text-brand-400 hover:text-brand-300 font-semibold transition-colors">
            Ver todo →
          </a>
        </div>
        <div class="space-y-1 max-h-72 overflow-y-auto pr-1" id="auditFeed">
          <?php foreach ($data['audit_log'] as $log):
            $actionColors = [
              'login'     => 'text-cyan-400',
              'approved'  => 'text-emerald-400',
              'rejected'  => 'text-red-400',
              'created'   => 'text-brand-400',
              'suspended' => 'text-orange-400',
              'register'  => 'text-purple-400',
              'logout'    => 'text-gray-400',
            ];
            $aColor = 'text-gray-400';
            foreach ($actionColors as $key => $cls) {
              if (str_contains($log['action'], $key)) { $aColor = $cls; break; }
            }
          ?>
          <div class="flex items-start gap-3 py-2 px-2 rounded-lg hover:bg-surface-hover/40 transition-colors border-b border-surface-border/50 last:border-0">
            <div class="w-1.5 h-1.5 rounded-full bg-brand-500 mt-2 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
              <code class="text-xs font-mono <?= $aColor ?>"><?= e($log['action']) ?></code>
              <p class="text-xs text-gray-600 truncate mt-0.5">
                <?= e($log['username'] ?? 'sistema') ?>
                · <?= e(clientIp() !== ($log['ip_address'] ?? '') ? ($log['ip_address'] ?? '') : 'IP actual') ?>
                · <span class="text-gray-500"><?= timeAgo($log['created_at']) ?></span>
              </p>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($data['audit_log'])): ?>
          <p class="text-center text-gray-500 py-8 text-sm">Sin registros de auditoría.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /max-w-7xl -->
</main>
</div><!-- /flex -->

<?php require_once dirname(__DIR__) . '/views/partials/footer.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ── Revenue Chart ──────────────────────────────────────── */
(function () {
  const ctx = document.getElementById('revenueChart');
  if (!ctx) return;

  const labels  = <?= $chartLabels ?>;
  const revenue = <?= $chartRevenue ?>;
  const orders  = <?= $chartOrders ?>;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Ingresos S/',
          data: revenue,
          backgroundColor: 'rgba(124,58,237,0.5)',
          borderColor: 'rgba(124,58,237,1)',
          borderWidth: 1,
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          label: 'Órdenes',
          data: orders,
          type: 'line',
          borderColor: 'rgba(6,182,212,0.9)',
          backgroundColor: 'rgba(6,182,212,0.1)',
          borderWidth: 2,
          pointBackgroundColor: 'rgba(6,182,212,1)',
          pointRadius: 4,
          tension: 0.4,
          fill: true,
          yAxisID: 'y2',
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#13131f',
          borderColor: '#1e1e30',
          borderWidth: 1,
          titleColor: '#f3f4f6',
          bodyColor: '#9ca3af',
          padding: 12,
          callbacks: {
            label: (ctx) => {
              if (ctx.dataset.label === 'Ingresos S/') {
                return ` S/ ${Number(ctx.raw).toFixed(2)}`;
              }
              return ` ${ctx.raw} órdenes`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.03)' },
          ticks: { color: '#6b7280', font: { size: 10 } }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: {
            color: '#6b7280',
            font: { size: 10 },
            callback: v => `S/ ${v}`
          }
        },
        y2: {
          position: 'right',
          grid: { display: false },
          ticks: {
            color: '#6b7280',
            font: { size: 10 },
            stepSize: 1
          }
        }
      }
    }
  });
})();

/* ── Auto-refresh contador: muestra tiempo desde carga ──── */
(function(){
  const el = document.createElement('span');
  el.className = 'text-xs text-gray-700 ml-2';
  const header = document.querySelector('h1');
  if (header) header.after(el);
  let s = 0;
  setInterval(() => {
    s++;
    el.textContent = `(actualizado hace ${s}s)`;
  }, 1000);
})();
</script>
