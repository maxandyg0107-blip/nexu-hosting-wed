<?php
/**
 * index.php
 * Catálogo público de planes de hosting web y servidores de Minecraft.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$planModel = new Plan();
$webPlans = $planModel->allActiveByType('web');
$mcPlans  = $planModel->allActiveByType('minecraft');

$pageTitle = 'Planes de Hosting y Servidores Minecraft';
require __DIR__ . '/views/layouts/header.php';

function render_plan_card(array $plan): void {
    ?>
    <div class="group relative rounded-xl border border-line bg-surface p-6 hover:border-cyan/40 transition flex flex-col">
      <h3 class="font-display text-lg font-semibold text-white"><?= e($plan['name']) ?></h3>
      <p class="text-slate-500 text-sm mt-1 min-h-[2.5rem]"><?= e($plan['description'] ?? '') ?></p>
      <div class="mt-4 flex items-baseline gap-1">
        <span class="font-display text-3xl font-bold text-white"><?= money_pen((float)$plan['price_PEN']) ?></span>
        <span class="text-slate-500 text-xs">/ mes</span>
      </div>
      <ul class="mt-5 space-y-2 text-sm text-slate-300 font-mono flex-1">
        <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">RAM</span><span><?= (int)$plan['ram_gb'] ?> GB</span></li>
        <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">vCPU</span><span><?= (int)$plan['cpu_cores'] ?> núcleos</span></li>
        <li class="flex justify-between border-b border-line/60 pb-2"><span class="text-slate-500">Disco</span><span><?= (int)$plan['disk_gb'] ?> GB NVMe</span></li>
      </ul>
      <a href="/checkout.php?plan_id=<?= (int)$plan['id'] ?>"
         class="mt-6 block text-center rounded-md bg-surface2 border border-line py-2.5 text-sm font-semibold group-hover:bg-cyan group-hover:text-base group-hover:border-cyan transition">
        Contratar ahora
      </a>
    </div>
    <?php
}
?>
<main>
  <section class="grid-glow border-b border-line/60">
    <div class="max-w-7xl mx-auto px-6 py-20 text-center">
      <span class="inline-block text-xs font-mono text-cyan border border-cyan/30 bg-cyan/5 rounded-full px-3 py-1 mb-5">Pagos vía Yape · Plin · Transferencia bancaria</span>
      <h1 class="font-display text-4xl sm:text-5xl font-bold text-white leading-tight">Infraestructura confiable,<br class="hidden sm:block"> pensada para Perú 🇵🇪</h1>
      <p class="text-slate-400 mt-5 max-w-xl mx-auto">Hosting web y servidores de Minecraft en SSD NVMe, con soporte para los métodos de pago locales que ya usas todos los días.</p>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-16">
    <h2 class="font-display text-2xl font-semibold text-white mb-6">Hosting Web</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($webPlans as $plan) render_plan_card($plan); ?>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-16">
    <h2 class="font-display text-2xl font-semibold text-white mb-6">Servidores de Minecraft</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($mcPlans as $plan) render_plan_card($plan); ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/views/layouts/footer.php'; ?>
