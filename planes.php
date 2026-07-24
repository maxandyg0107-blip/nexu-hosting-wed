<?php
/**
 * NEXU HOSTING - Catálogo de Planes
 * Listado por tipo + vista de detalle de un plan individual.
 */
require_once __DIR__ . '/config/bootstrap.php';

$planModel  = new PlanModel();
$slug       = sanitize($_GET['slug'] ?? '');
$typeFilter = sanitize($_GET['type'] ?? '');

// ── Vista de un plan específico ──────────────────────────────
if ($slug) {
    $plan = $planModel->getBySlug($slug);
    if (!$plan || !$plan['is_active']) {
        setFlash('Plan no encontrado.', 'danger');
        redirect('/planes.php');
    }

    $page_title       = $plan['name'] . ' — Contratar';
    $meta_description = $plan['description'] ?? 'Contrata ' . $plan['name'] . ' en Nexu Hosting.';

    require_once __DIR__ . '/views/partials/head.php';
    require_once __DIR__ . '/views/partials/navbar.php';
    ?>

<main class="pt-20 pb-16">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-gray-500 py-6">
      <a href="/planes.php" class="hover:text-brand-400 transition-colors">Planes</a>
      <span>›</span>
      <span class="text-gray-300"><?= e($plan['name']) ?></span>
    </nav>

    <div data-flash><?= renderFlash() ?></div>

    <div class="grid lg:grid-cols-2 gap-10 items-start animate-fade-in">

      <!-- Plan detail card -->
      <div class="bg-surface-card border border-brand-500/20 rounded-2xl p-8"
           style="background:radial-gradient(ellipse at top left,rgba(124,58,237,0.06),transparent)">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center text-2xl shadow-lg shadow-brand-600/30">
            <?= $plan['plan_type'] === 'minecraft' ? '🎮' : ($plan['plan_type'] === 'web' ? '🌐' : '🖥️') ?>
          </div>
          <div>
            <h1 class="text-2xl font-extrabold text-white"><?= e($plan['name']) ?></h1>
            <p class="text-sm text-brand-400 capitalize font-medium"><?= e($plan['plan_type']) ?> Hosting</p>
          </div>
          <?php if ($plan['is_featured']): ?>
          <span class="ml-auto px-3 py-1 rounded-full bg-brand-600/20 border border-brand-500/30 text-brand-400 text-xs font-bold uppercase tracking-wider">
            Popular
          </span>
          <?php endif; ?>
        </div>

        <?php if ($plan['description']): ?>
        <p class="text-gray-400 text-sm mb-6"><?= e($plan['description']) ?></p>
        <?php endif; ?>

        <!-- Specs grid -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <div class="bg-surface rounded-xl p-4">
            <div class="text-2xl font-extrabold text-brand-400"><?= $plan['ram_gb'] ?> GB</div>
            <div class="text-xs text-gray-500 mt-0.5">RAM DDR4/DDR5</div>
          </div>
          <div class="bg-surface rounded-xl p-4">
            <div class="text-2xl font-extrabold text-cyan-400"><?= $plan['cpu_cores'] ?></div>
            <div class="text-xs text-gray-500 mt-0.5">vCPU AMD Ryzen</div>
          </div>
          <div class="bg-surface rounded-xl p-4">
            <div class="text-2xl font-extrabold text-emerald-400"><?= $plan['disk_gb'] ?> GB</div>
            <div class="text-xs text-gray-500 mt-0.5">NVMe SSD</div>
          </div>
          <?php if ($plan['player_slots']): ?>
          <div class="bg-surface rounded-xl p-4">
            <div class="text-2xl font-extrabold text-yellow-400"><?= $plan['player_slots'] ?></div>
            <div class="text-xs text-gray-500 mt-0.5">Slots jugadores</div>
          </div>
          <?php elseif ($plan['bandwidth_tb']): ?>
          <div class="bg-surface rounded-xl p-4">
            <div class="text-2xl font-extrabold text-purple-400"><?= $plan['bandwidth_tb'] ?> TB</div>
            <div class="text-xs text-gray-500 mt-0.5">Ancho de banda</div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Features list -->
        <?php if (!empty($plan['features_array'])): ?>
        <ul class="space-y-2.5">
          <?php foreach ($plan['features_array'] as $f): ?>
          <li class="flex items-center gap-3 text-sm text-gray-300">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            <?= e($f) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <!-- Pricing + CTA -->
      <div class="space-y-4 animate-slide-up">
        <div class="bg-surface-card border border-surface-border rounded-2xl p-8">
          <div class="mb-6">
            <p class="text-sm text-gray-500 mb-1">Precio mensual desde</p>
            <div class="flex items-end gap-2">
              <span class="text-5xl font-black text-white"><?= priceInCurrency($plan['price_pen']) ?></span>
              <span class="text-gray-500 pb-1">/mes</span>
            </div>
            <?php if (activeCurrency() !== 'PEN'): ?>
            <p class="text-xs text-gray-500 mt-1">≈ S/ <?= number_format($plan['price_pen'], 2) ?> PEN</p>
            <?php endif; ?>
          </div>

          <?php if (isLoggedIn()): ?>
          <a href="/checkout.php?plan=<?= $plan['id'] ?>"
             class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500
                    text-white font-bold text-base shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5
                    transition-all duration-150 mb-3">
            Contratar ahora →
          </a>
          <?php else: ?>
          <a href="/login.php"
             class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500
                    text-white font-bold text-base shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5
                    transition-all duration-150 mb-3">
            Inicia sesión para contratar
          </a>
          <a href="/register.php"
             class="w-full flex items-center justify-center py-3 rounded-xl border border-brand-500/30 text-brand-400
                    text-sm font-semibold hover:bg-brand-600/10 transition-colors">
            O crea una cuenta gratis
          </a>
          <?php endif; ?>

          <div class="pt-4 mt-2 border-t border-surface-border space-y-2">
            <?php foreach (PAYMENT_CONFIG as $method): ?>
            <span class="inline-flex items-center gap-1 mr-2 text-xs text-gray-500">
              <?= $method['icon'] ?> <?= e($method['label']) ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="bg-surface-card border border-surface-border rounded-xl p-4">
          <div class="flex items-center gap-3 text-sm text-gray-400">
            <span class="text-emerald-400 text-lg">🛡️</span>
            <span>Protección DDoS incluida · Uptime 99.9% garantizado · Soporte 24/7 en español</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>

    <?php
    require_once __DIR__ . '/views/partials/footer.php';
    exit;
}

// ── Vista de catálogo completo ────────────────────────────────
$types     = $planModel->getTypes();
$allPlans  = $planModel->getAll(true);

// Agrupar por tipo
$plansByType = [];
foreach ($allPlans as $p) {
    $plansByType[$p['plan_type']][] = $p;
}

// Filtro de tipo activo
if ($typeFilter && !isset($types[$typeFilter])) $typeFilter = '';

$page_title       = 'Planes y Precios';
$meta_description = 'Servidores Minecraft y Web Hosting desde S/ 25/mes. Paga con Yape, Plin o transferencia bancaria.';

require_once __DIR__ . '/views/partials/head.php';
require_once __DIR__ . '/views/partials/navbar.php';
?>

<main class="pt-20">

  <!-- Hero -->
  <section class="relative py-20 overflow-hidden">
    <div class="absolute inset-0 bg-hero-gradient pointer-events-none"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-600/15 border border-brand-500/20 text-brand-400 text-sm font-semibold mb-6 animate-fade-in">
        💎 Planes y Precios
      </div>
      <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-4 animate-slide-up">
        El plan perfecto para
        <span class="bg-gradient-to-r from-brand-400 to-cyan-400 bg-clip-text text-transparent">tu servidor</span>
      </h1>
      <p class="text-lg text-gray-400 max-w-2xl mx-auto mb-8 animate-slide-up" style="animation-delay:0.1s">
        Hardware AMD Ryzen de última generación, almacenamiento NVMe y pagos locales en Perú.
        Sin complicaciones, sin tarjeta de crédito.
      </p>

      <!-- Currency switcher pill -->
      <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-surface-card border border-surface-border mb-2">
        <span class="text-xs text-gray-500">Ver precios en:</span>
        <?php foreach (SUPPORTED_CURRENCIES as $cur): ?>
        <form method="POST" action="/auth/currency.php" class="inline">
          <?= csrfField() ?>
          <input type="hidden" name="currency" value="<?= $cur ?>">
          <button type="submit"
                  class="px-3 py-1 rounded-full text-xs font-bold transition-all
                         <?= activeCurrency() === $cur ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-gray-400 hover:text-white' ?>">
            <?= $cur ?>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Type filter tabs -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex gap-2 overflow-x-auto pb-2 mb-10 scrollbar-hide">
      <a href="/planes.php"
         class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all
                <?= !$typeFilter ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'bg-surface-card border border-surface-border text-gray-400 hover:text-white hover:border-brand-500/50' ?>">
        Todos los planes
      </a>
      <?php foreach ($types as $key => $label): ?>
        <?php if (isset($plansByType[$key])): ?>
        <a href="/planes.php?type=<?= $key ?>"
           class="flex-shrink-0 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all
                  <?= $typeFilter === $key ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'bg-surface-card border border-surface-border text-gray-400 hover:text-white hover:border-brand-500/50' ?>">
          <?= e($label) ?>
        </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Plans sections -->
    <?php foreach ($plansByType as $type => $plans): ?>
      <?php if ($typeFilter && $typeFilter !== $type) continue; ?>
      <?php if (empty($plans)) continue; ?>

      <div class="mb-16">
        <div class="flex items-center gap-3 mb-8">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-600/30 to-cyan-500/30 border border-brand-500/20 flex items-center justify-center text-xl">
            <?= $type === 'minecraft' ? '🎮' : ($type === 'web' ? '🌐' : '🖥️') ?>
          </div>
          <div>
            <h2 class="text-xl font-extrabold text-white"><?= e($types[$type] ?? ucfirst($type)) ?></h2>
            <p class="text-xs text-gray-500"><?= count($plans) ?> plan<?= count($plans) > 1 ? 'es' : '' ?> disponible<?= count($plans) > 1 ? 's' : '' ?></p>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <?php foreach ($plans as $i => $plan): ?>
          <?php $features = json_decode($plan['features'] ?? '[]', true); ?>
          <div class="relative flex flex-col bg-surface-card border rounded-2xl overflow-hidden
                      transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl
                      <?= $plan['is_featured']
                          ? 'border-brand-500 shadow-xl shadow-brand-600/20 hover:shadow-brand-600/30'
                          : 'border-surface-border hover:border-brand-500/40 hover:shadow-brand-600/10' ?>
                      animate-fade-in"
               style="animation-delay:<?= $i * 0.07 ?>s">

            <?php if ($plan['is_featured']): ?>
            <div class="absolute top-0 inset-x-0 h-0.5 bg-gradient-to-r from-brand-600 via-brand-400 to-cyan-500"></div>
            <div class="absolute top-3 right-3">
              <span class="px-2.5 py-1 rounded-full bg-brand-600/20 border border-brand-500/30 text-brand-400 text-xs font-bold uppercase tracking-wider">
                Popular
              </span>
            </div>
            <?php endif; ?>

            <div class="p-6 flex-1">
              <h3 class="text-lg font-extrabold text-white mb-1"><?= e($plan['name']) ?></h3>

              <!-- Price -->
              <div class="my-4">
                <div class="text-3xl font-black text-white"><?= priceInCurrency($plan['price_pen']) ?></div>
                <div class="text-xs text-gray-500 mt-0.5">
                  por mes
                  <?php if (activeCurrency() !== 'PEN'): ?>
                  <span class="ml-1">(≈ S/ <?= number_format($plan['price_pen'],2) ?>)</span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Mini specs -->
              <div class="flex gap-2 flex-wrap mb-4">
                <span class="px-2 py-1 rounded-lg bg-brand-600/10 border border-brand-500/20 text-brand-400 text-xs font-semibold"><?= $plan['ram_gb'] ?>GB RAM</span>
                <span class="px-2 py-1 rounded-lg bg-cyan-600/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold"><?= $plan['cpu_cores'] ?> vCPU</span>
                <span class="px-2 py-1 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold"><?= $plan['disk_gb'] ?>GB NVMe</span>
              </div>

              <!-- Top features -->
              <ul class="space-y-1.5 mb-4">
                <?php foreach (array_slice($features, 0, 4) as $f): ?>
                <li class="flex items-center gap-2 text-xs text-gray-400">
                  <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                  <?= e($f) ?>
                </li>
                <?php endforeach; ?>
                <?php if (count($features) > 4): ?>
                <li class="text-xs text-gray-600">+<?= count($features) - 4 ?> más incluidos...</li>
                <?php endif; ?>
              </ul>
            </div>

            <!-- CTA -->
            <div class="px-6 pb-6">
              <a href="/planes.php?slug=<?= e($plan['slug']) ?>"
                 class="w-full flex items-center justify-center py-2.5 rounded-xl font-semibold text-sm transition-all duration-150
                        <?= $plan['is_featured']
                            ? 'bg-gradient-to-r from-brand-600 to-brand-500 text-white shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5'
                            : 'border border-brand-500/30 text-brand-400 hover:bg-brand-600/10' ?>">
                Ver plan →
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Payment methods strip -->
    <div class="py-12 border-t border-surface-border mb-12">
      <p class="text-center text-sm text-gray-500 mb-5">Métodos de pago disponibles en Perú</p>
      <div class="flex flex-wrap justify-center gap-3">
        <?php foreach (PAYMENT_CONFIG as $method): ?>
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-surface-card border border-surface-border text-sm text-gray-400">
          <span class="text-lg"><?= $method['icon'] ?></span>
          <span class="font-medium"><?= e($method['label']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
