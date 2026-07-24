<?php
/**
 * NEXU HOSTING - Página Principal
 * Landing page completa con hero, planes, testimonios, comparativa, pagos.
 */
require_once __DIR__ . '/config/bootstrap.php';

$planModel = new PlanModel();
$featured  = array_filter($planModel->getAll(true), fn($p) => $p['is_featured']);

$stmt      = db()->query("SELECT * FROM announcements WHERE is_published=1 ORDER BY published_at DESC LIMIT 3");
$news      = $stmt->fetchAll();

$stmt2     = db()->query("SELECT * FROM service_status ORDER BY type DESC, name ASC");
$services  = $stmt2->fetchAll();

$allOperational = !in_array(false, array_map(fn($s) => $s['status'] === 'operational', $services), true);

$page_title       = 'Servidores de Juegos y Web Hosting en Perú';
$meta_description = 'Nexu Hosting — Servidores Minecraft y Web Hosting desde S/ 25/mes. Paga con Yape, Plin, Banco de la Nación e Interbank. Activación inmediata.';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col overflow-x-hidden">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>
<main class="flex-1">

<!-- ══ HERO ══════════════════════════════════════════════════ -->
<section class="relative min-h-screen flex items-center pt-16 overflow-hidden">
  <!-- Gradient orbs -->
  <div class="absolute top-1/4 -right-32 w-96 h-96 bg-brand-600/20 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-1/4 -left-32 w-80 h-80 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 grid lg:grid-cols-2 gap-12 items-center">

    <!-- Left: copy -->
    <div class="animate-fade-in">
      <!-- Status pill -->
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold mb-6">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <?= $allOperational ? 'Todos los sistemas operacionales' : 'Ver estado del sistema' ?>
      </div>

      <h1 class="text-5xl sm:text-6xl font-black text-white leading-[1.05] mb-6">
        Tu servidor<br>
        <span class="bg-gradient-to-r from-brand-400 via-purple-400 to-cyan-400 bg-clip-text text-transparent">
          en segundos
        </span>
      </h1>

      <p class="text-xl text-gray-400 leading-relaxed mb-8 max-w-lg">
        Servidores Minecraft y Web Hosting de alto rendimiento en Perú.
        Paga con <strong class="text-white">Yape, Plin</strong> o transferencia bancaria.
        Sin tarjeta de crédito.
      </p>

      <!-- CTA buttons -->
      <div class="flex flex-col sm:flex-row gap-3 mb-10">
        <a href="/planes.php"
           class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl
                  bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold text-base
                  shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5
                  transition-all duration-150">
          Ver planes desde S/ 25
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
        <a href="/estado.php"
           class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl
                  border border-surface-border text-gray-300 font-semibold text-base
                  hover:border-brand-500/50 hover:text-white transition-all duration-150">
          Estado del sistema
        </a>
      </div>

      <!-- Stats -->
      <div class="flex gap-8 flex-wrap">
        <?php foreach ([['99.9%','Uptime garantizado'],['< 2h','Activación del servicio'],['24/7','Soporte en español']] as $s): ?>
        <div>
          <div class="text-2xl font-extrabold text-white"><?= $s[0] ?></div>
          <div class="text-xs text-gray-500 mt-0.5"><?= $s[1] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right: payment methods showcase -->
    <div class="animate-slide-up hidden lg:block" style="animation-delay:0.2s">
      <div class="bg-surface-card border border-surface-border rounded-2xl p-6 shadow-2xl shadow-black/50">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-5">Métodos de pago disponibles</p>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach (PAYMENT_CONFIG as $method): ?>
          <div class="flex items-center gap-3 p-3 bg-surface rounded-xl border border-surface-border hover:border-brand-500/30 transition-colors">
            <span class="text-2xl"><?= $method['icon'] ?></span>
            <div>
              <p class="text-sm font-semibold text-white"><?= e($method['label']) ?></p>
              <?php if (isset($method['phone'])): ?>
              <p class="text-xs text-gray-500"><?= e($method['phone']) ?></p>
              <?php elseif (isset($method['cci'])): ?>
              <p class="text-xs text-gray-500 font-mono truncate"><?= e($method['account']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4 p-3 bg-brand-600/5 border border-brand-500/20 rounded-xl text-xs text-brand-400">
          🔒 Pago seguro con verificación manual en menos de 2 horas.
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ══ FEATURES ═══════════════════════════════════════════════ -->
<section class="py-20 bg-surface-card/30">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <span class="inline-block px-4 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold uppercase tracking-wider mb-4">
        Características
      </span>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Infraestructura de clase mundial</h2>
      <p class="text-gray-400 mt-3 max-w-xl mx-auto">Hardware AMD Ryzen última generación, discos NVMe y red de 1 Gbps.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php
      $features = [
        ['⚡','Activación inmediata','Tu servidor estará listo en segundos tras confirmar el pago. Sin esperas.'],
        ['🛡️','Anti-DDoS avanzado','Protección de hasta 1 Tbps. Tu servidor siempre en línea.'],
        ['💾','Backups automáticos','Copias de seguridad cada 6 horas. Nunca pierdas tu progreso.'],
        ['🎮','Panel Pterodactyl','El panel más potente del mercado. Archivos, consola, bases de datos.'],
        ['🖥️','AMD Ryzen 9 + NVMe','Procesadores de alta frecuencia y discos NVMe de última generación.'],
        ['🌎','Nodo en Perú / Latinoamérica','Baja latencia para jugadores peruanos y latinoamericanos.'],
      ];
      foreach ($features as $i => $f):
      ?>
      <div class="group p-6 bg-surface-card border border-surface-border rounded-2xl
                  hover:border-brand-500/40 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-600/5
                  transition-all duration-300 animate-fade-in" style="animation-delay:<?= $i*0.07 ?>s"
           data-animate>
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-600/20 to-cyan-500/10 border border-brand-500/20 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
          <?= $f[0] ?>
        </div>
        <h3 class="font-bold text-white mb-2"><?= $f[1] ?></h3>
        <p class="text-sm text-gray-400 leading-relaxed"><?= $f[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ FEATURED PLANS ════════════════════════════════════════ -->
<section class="py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-14">
      <span class="inline-block px-4 py-1.5 rounded-full bg-brand-600/15 border border-brand-500/20 text-brand-400 text-xs font-semibold uppercase tracking-wider mb-4">Planes más populares</span>
      <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Elige tu plan ideal</h2>
      <p class="text-gray-400 mt-3">Precios en <strong class="text-white">Soles Peruanos (S/)</strong>. Sin costos ocultos.</p>

      <!-- Currency switcher inline -->
      <div class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-full bg-surface-card border border-surface-border">
        <span class="text-xs text-gray-500">Moneda:</span>
        <?php foreach (SUPPORTED_CURRENCIES as $cur): ?>
        <form method="POST" action="/auth/currency.php" class="inline">
          <?= csrfField() ?>
          <input type="hidden" name="currency" value="<?= $cur ?>">
          <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold transition-all
                  <?= activeCurrency() === $cur ? 'bg-brand-600 text-white' : 'text-gray-400 hover:text-white' ?>">
            <?= $cur ?>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach (array_slice($featured, 0, 3) as $i => $plan):
        $feats = json_decode($plan['features'] ?? '[]', true);
      ?>
      <div class="relative flex flex-col bg-surface-card border border-brand-500/30 rounded-2xl overflow-hidden
                  shadow-xl shadow-brand-600/10 hover:-translate-y-2 hover:shadow-brand-600/20
                  transition-all duration-300 animate-fade-in" style="animation-delay:<?= $i*0.1 ?>s">
        <div class="absolute top-0 inset-x-0 h-0.5 bg-gradient-to-r from-brand-600 via-brand-400 to-cyan-500"></div>
        <div class="absolute top-4 right-4">
          <span class="px-2.5 py-1 rounded-full bg-brand-600/20 border border-brand-500/30 text-brand-400 text-xs font-bold uppercase">Popular</span>
        </div>
        <div class="p-7 flex-1">
          <h3 class="text-xl font-extrabold text-white mb-1"><?= e($plan['name']) ?></h3>
          <p class="text-xs text-gray-500 capitalize mb-4"><?= e($plan['plan_type']) ?> Hosting</p>
          <div class="mb-5">
            <div class="text-4xl font-black text-white"><?= priceInCurrency($plan['price_pen']) ?></div>
            <div class="text-xs text-gray-500 mt-0.5">por mes</div>
          </div>
          <div class="flex gap-2 flex-wrap mb-5">
            <span class="px-2 py-1 rounded-lg bg-brand-600/10 border border-brand-500/20 text-brand-400 text-xs font-semibold"><?= $plan['ram_gb'] ?>GB RAM</span>
            <span class="px-2 py-1 rounded-lg bg-cyan-600/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold"><?= $plan['cpu_cores'] ?> vCPU</span>
            <span class="px-2 py-1 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold"><?= $plan['disk_gb'] ?>GB SSD</span>
          </div>
          <ul class="space-y-2">
            <?php foreach (array_slice($feats, 0, 5) as $f): ?>
            <li class="flex items-center gap-2 text-sm text-gray-400">
              <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <?= e($f) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="px-7 pb-7">
          <a href="/planes.php?slug=<?= e($plan['slug']) ?>"
             class="w-full flex items-center justify-center py-3 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500
                    text-white font-bold shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 transition-all">
            Contratar ahora →
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
      <a href="/planes.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-surface-border text-gray-400 font-semibold hover:text-white hover:border-brand-500/50 transition-all">
        Ver todos los planes
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- ══ COMPARISON TABLE ══════════════════════════════════════ -->
<section class="py-20 bg-surface-card/30">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-extrabold text-white">¿Por qué Nexu Hosting?</h2>
      <p class="text-gray-400 mt-3">Comparativa de precio vs rendimiento frente a la competencia.</p>
    </div>
    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-surface-border">
            <th class="text-left px-6 py-4 text-gray-400 font-semibold">Proveedor</th>
            <th class="text-center px-4 py-4 text-gray-400 font-semibold">Procesador</th>
            <th class="text-center px-4 py-4 text-gray-400 font-semibold">RAM</th>
            <th class="text-center px-4 py-4 text-gray-400 font-semibold">Disco</th>
            <th class="text-center px-4 py-4 text-gray-400 font-semibold">Precio/mes</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $comparison = [
            ['Nexu Hosting','Ryzen 9 5950X','8 GB','80 GB NVMe','S/ 129','brand',true],
            ['BloomHost',   'Ryzen 9 5950X','8 GB','300 GB NVMe','$48 USD','gray',false],
            ['PebbleHost',  'Ryzen 7 5800X','8 GB','Unmetered',  '$62 USD','gray',false],
            ['Shockbyte',   'Xeon E-2386',  '8 GB','Unmetered',  '$63 USD','gray',false],
            ['Apex Hosting','Ryzen 9 5900X','8 GB','Unmetered',  '$71 USD','gray',false],
          ];
          foreach ($comparison as $row):
          [$name, $cpu, $ram, $disk, $price, $color, $highlight] = $row;
          ?>
          <tr class="border-b border-surface-border last:border-0 <?= $highlight ? 'bg-brand-600/5' : 'hover:bg-surface-hover' ?> transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg <?= $highlight ? 'bg-gradient-to-br from-brand-600 to-cyan-500' : 'bg-surface border border-surface-border' ?> flex items-center justify-center text-xs font-bold text-white">
                  <?= mb_strtoupper(mb_substr($name, 0, 1)) ?>
                </div>
                <span class="font-semibold <?= $highlight ? 'text-white' : 'text-gray-400' ?>"><?= e($name) ?></span>
                <?php if ($highlight): ?><span class="px-2 py-0.5 rounded-full bg-brand-600/20 text-brand-400 text-xs font-bold border border-brand-500/30">Mejor valor</span><?php endif; ?>
              </div>
            </td>
            <td class="px-4 py-4 text-center text-xs text-gray-400"><?= e($cpu) ?></td>
            <td class="px-4 py-4 text-center text-xs text-gray-400"><?= e($ram) ?></td>
            <td class="px-4 py-4 text-center text-xs text-gray-400"><?= e($disk) ?></td>
            <td class="px-4 py-4 text-center">
              <span class="font-extrabold <?= $highlight ? 'text-emerald-400 text-base' : 'text-gray-400' ?>"><?= e($price) ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ══ LATEST NEWS ════════════════════════════════════════════ -->
<?php if (!empty($news)): ?>
<section class="py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-extrabold text-white">Últimas noticias</h2>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($news as $i => $n): ?>
      <article class="bg-surface-card border border-surface-border rounded-2xl p-6 hover:border-brand-500/30 hover:-translate-y-1 transition-all duration-300 animate-fade-in" style="animation-delay:<?= $i*0.1 ?>s" data-animate>
        <div class="flex items-center justify-between mb-3">
          <?php if ($n['is_featured']): ?><span class="px-2 py-0.5 rounded-full bg-yellow-500/15 text-yellow-400 border border-yellow-500/30 text-xs font-semibold">Destacado</span><?php else: ?><span></span><?php endif; ?>
          <span class="text-xs text-gray-600"><?= formatDate($n['published_at'] ?? $n['created_at']) ?></span>
        </div>
        <h3 class="font-bold text-white mb-2 line-clamp-2"><?= e($n['title']) ?></h3>
        <p class="text-sm text-gray-400 line-clamp-3"><?= e(strip_tags($n['excerpt'] ?? substr($n['content'],0,150))) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ CTA FINAL ═════════════════════════════════════════════ -->
<section class="py-20">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 text-center">
    <div class="relative p-12 rounded-3xl overflow-hidden"
         style="background:radial-gradient(ellipse at center,rgba(124,58,237,0.15),rgba(6,182,212,0.05));border:1px solid rgba(124,58,237,0.2)">
      <div class="absolute inset-0 bg-brand-600/5 pointer-events-none"></div>
      <h2 class="relative text-4xl font-extrabold text-white mb-4">Empieza hoy mismo</h2>
      <p class="relative text-gray-400 mb-8 max-w-lg mx-auto">
        Regístrate gratis, elige tu plan y paga con Yape o Plin.
        Tu servidor estará activo en menos de 2 horas.
      </p>
      <div class="relative flex flex-col sm:flex-row gap-3 justify-center">
        <?php if (!isLoggedIn()): ?>
        <a href="/register.php"
           class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold
                  shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 transition-all">
          Crear cuenta gratis
        </a>
        <a href="/planes.php"
           class="px-8 py-3.5 rounded-xl border border-surface-border text-gray-300 font-semibold
                  hover:border-brand-500/50 hover:text-white transition-all">
          Ver planes
        </a>
        <?php else: ?>
        <a href="/planes.php"
           class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold
                  shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 transition-all">
          Ver todos los planes →
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>
