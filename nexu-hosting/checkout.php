<?php
/**
 * NEXU HOSTING - Checkout / Pasarela de Pago Peruana
 * Yape · Plin · Banco de la Nación · Interbank · BCP
 */
require_once __DIR__ . '/config/bootstrap.php';
requireLogin();

$planId       = (int)($_GET['plan']  ?? 0);
$billingCycle = sanitize($_GET['cycle'] ?? 'monthly');

$ctrl   = new PaymentController();
$data   = $ctrl->checkout($planId, $billingCycle);
$plan   = $data['plan'];
$cycles = $data['billing_cycles'];

$page_title       = 'Checkout — ' . $plan['name'];
$meta_description = 'Finaliza tu compra de ' . $plan['name'] . ' en Nexu Hosting.';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>

<div class="min-h-screen flex flex-col">
  <?php require_once __DIR__ . '/views/partials/navbar.php'; ?>

  <main class="flex-1 pt-24 pb-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-xs text-gray-500 mb-8">
        <a href="/planes.php" class="hover:text-brand-400 transition-colors">Planes</a>
        <span>›</span>
        <span class="text-gray-300"><?= e($plan['name']) ?></span>
        <span>›</span>
        <span class="text-brand-400">Checkout</span>
      </nav>

      <div data-flash><?= renderFlash() ?></div>

      <div class="grid lg:grid-cols-5 gap-8">

        <!-- ── Resumen del Plan ── -->
        <aside class="lg:col-span-2 space-y-6 animate-fade-in">

          <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center text-white text-lg">
                <?= $plan['plan_type'] === 'minecraft' ? '🎮' : '🌐' ?>
              </div>
              <div>
                <h2 class="font-bold text-white"><?= e($plan['name']) ?></h2>
                <p class="text-xs text-gray-400 capitalize"><?= e($plan['plan_type']) ?> Hosting</p>
              </div>
            </div>

            <!-- Specs -->
            <div class="grid grid-cols-2 gap-3 mb-5">
              <div class="bg-surface rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-brand-400"><?= $plan['ram_gb'] ?>GB</div>
                <div class="text-xs text-gray-500 mt-0.5">RAM DDR4</div>
              </div>
              <div class="bg-surface rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-cyan-400"><?= $plan['cpu_cores'] ?></div>
                <div class="text-xs text-gray-500 mt-0.5">vCPU AMD</div>
              </div>
              <div class="bg-surface rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-emerald-400"><?= $plan['disk_gb'] ?>GB</div>
                <div class="text-xs text-gray-500 mt-0.5">NVMe SSD</div>
              </div>
              <?php if ($plan['player_slots']): ?>
              <div class="bg-surface rounded-xl p-3 text-center">
                <div class="text-xl font-bold text-yellow-400"><?= $plan['player_slots'] ?></div>
                <div class="text-xs text-gray-500 mt-0.5">Slots</div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Features -->
            <?php if (!empty($plan['features_array'])): ?>
            <ul class="space-y-2">
              <?php foreach ($plan['features_array'] as $f): ?>
              <li class="flex items-center gap-2 text-sm text-gray-400">
                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <?= e($f) ?>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Billing cycle selector -->
          <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Ciclo de facturación</h3>
            <div class="space-y-2">
              <?php foreach ($cycles as $key => $cycle): ?>
                <?php
                  $subPen   = $plan['price_pen'] * $cycle['months'];
                  $totPen   = round($subPen * (1 - $cycle['discount']), 2);
                  $saving   = round($subPen - $totPen, 2);
                  $selected = ($data['billing_cycle'] === $key);
                ?>
                <a href="?plan=<?= $planId ?>&cycle=<?= $key ?>"
                   class="flex items-center justify-between p-3 rounded-xl border transition-all
                          <?= $selected ? 'border-brand-500 bg-brand-600/10' : 'border-surface-border hover:border-brand-500/50' ?>">
                  <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded-full border-2 flex-shrink-0 transition-colors
                                <?= $selected ? 'border-brand-500 bg-brand-500' : 'border-gray-600' ?>"></div>
                    <span class="text-sm font-medium <?= $selected ? 'text-white' : 'text-gray-400' ?>"><?= $cycle['label'] ?></span>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-bold <?= $selected ? 'text-brand-300' : 'text-gray-300' ?>">
                      <?= priceInCurrency($totPen) ?>
                    </div>
                    <?php if ($saving > 0): ?>
                    <div class="text-xs text-emerald-400">Ahorras <?= priceInCurrency($saving) ?></div>
                    <?php endif; ?>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Total -->
          <div class="bg-gradient-to-br from-brand-900/50 to-surface-card border border-brand-500/20 rounded-2xl p-5">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-gray-400">Subtotal</span>
              <span class="text-sm text-gray-300"><?= priceInCurrency($data['subtotal_pen']) ?></span>
            </div>
            <?php if ($data['discount_pen'] > 0): ?>
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-emerald-400">Descuento</span>
              <span class="text-sm text-emerald-400">-<?= priceInCurrency($data['discount_pen']) ?></span>
            </div>
            <?php endif; ?>
            <div class="border-t border-surface-border pt-3 flex justify-between items-center">
              <span class="font-semibold text-white">Total</span>
              <div class="text-right">
                <div class="text-2xl font-extrabold text-white"><?= priceInCurrency($data['total_pen']) ?></div>
                <?php if ($data['currency'] !== 'PEN'): ?>
                <div class="text-xs text-gray-500">≈ S/ <?= number_format($data['total_pen'], 2) ?> PEN</div>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </aside>

        <!-- ── Formulario de Pago ── -->
        <div class="lg:col-span-3 animate-slide-up">
          <div class="bg-surface-card border border-surface-border rounded-2xl p-6 lg:p-8">
            <h2 class="text-xl font-bold text-white mb-6">Selecciona tu método de pago</h2>

            <form method="POST" action="/procesar_pago.php" enctype="multipart/form-data" id="checkout-form">
              <?= csrfField() ?>
              <input type="hidden" name="plan_id"       value="<?= $planId ?>">
              <input type="hidden" name="billing_cycle" value="<?= e($data['billing_cycle']) ?>">

              <!-- Payment method selector -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6" id="payment-methods">
                <?php foreach ($data['payment_methods'] as $key => $method): ?>
                <label class="cursor-pointer group">
                  <input type="radio" name="payment_method" value="<?= $key ?>"
                         class="sr-only peer" <?= $key === 'yape' ? 'checked' : '' ?>>
                  <div class="flex flex-col items-center gap-2 p-4 rounded-xl border border-surface-border
                              peer-checked:border-brand-500 peer-checked:bg-brand-600/10
                              group-hover:border-brand-500/50 transition-all">
                    <span class="text-2xl"><?= $method['icon'] ?></span>
                    <span class="text-xs font-medium text-gray-300 peer-checked:text-white text-center"><?= e($method['label']) ?></span>
                  </div>
                </label>
                <?php endforeach; ?>
              </div>

              <!-- Payment details panel (changes per method) -->
              <?php foreach ($data['payment_methods'] as $key => $method): ?>
              <div id="details-<?= $key ?>" class="payment-detail hidden mb-6">
                <div class="bg-surface rounded-xl border border-surface-border p-5">
                  <?php if (isset($method['phone'])): ?>
                    <!-- Yape / Plin -->
                    <div class="flex items-start gap-4">
                      <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Envía tu pago a</p>
                        <p class="text-3xl font-bold tracking-wider text-white mb-1"><?= e($method['phone']) ?></p>
                        <p class="text-sm text-gray-400"><?= e($method['holder_name']) ?></p>
                      </div>
                      <?php if (isset($method['qr_image'])): ?>
                      <div class="w-24 h-24 bg-white rounded-xl flex items-center justify-center p-1 flex-shrink-0">
                        <img src="<?= asset($method['qr_image']) ?>" alt="QR <?= $method['label'] ?>"
                             class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'text-gray-800 text-xs text-center p-2\'>QR disponible</span>'">
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="mt-3 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                      <p class="text-xs text-yellow-400">
                        ⚠️ Envía exactamente <strong><?= priceInCurrency($data['total_pen']) ?></strong> (S/ <?= number_format($data['total_pen'],2) ?>) para evitar retrasos en la verificación.
                      </p>
                    </div>

                  <?php elseif (isset($method['cci'])): ?>
                    <!-- Transferencia bancaria -->
                    <div class="space-y-3">
                      <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                          <p class="text-xs text-gray-500 mb-0.5">Número de cuenta</p>
                          <p class="font-mono font-semibold text-white"><?= e($method['account']) ?></p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500 mb-0.5">CCI</p>
                          <p class="font-mono text-sm text-gray-300"><?= e($method['cci']) ?></p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500 mb-0.5">Titular</p>
                          <p class="font-medium text-gray-300"><?= e($method['holder_name']) ?></p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500 mb-0.5">DNI / RUC</p>
                          <p class="font-mono text-gray-300"><?= e($method['holder_dni']) ?></p>
                        </div>
                      </div>
                      <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                        <p class="text-xs text-blue-400">
                          💡 Monto a transferir: <strong><?= priceInCurrency($data['total_pen']) ?></strong> (S/ <?= number_format($data['total_pen'],2) ?>)
                        </p>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>

              <!-- Voucher upload -->
              <div class="mb-6">
                <label class="block text-sm font-semibold text-white mb-2">
                  Comprobante de pago <span class="text-red-400">*</span>
                  <span class="ml-1 text-xs font-normal text-gray-500">(JPG, PNG o PDF · Máx. 10 MB)</span>
                </label>
                <div id="drop-zone"
                     class="relative border-2 border-dashed border-surface-border rounded-xl p-8 text-center
                            hover:border-brand-500 transition-colors cursor-pointer group">
                  <input type="file" name="voucher" id="voucher-input" accept=".jpg,.jpeg,.png,.pdf"
                         class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                  <div id="drop-default" class="pointer-events-none">
                    <svg class="w-10 h-10 text-gray-600 mx-auto mb-3 group-hover:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-gray-400 group-hover:text-gray-300">Arrastra tu comprobante aquí o <span class="text-brand-400">haz clic para seleccionar</span></p>
                    <p class="text-xs text-gray-600 mt-1">Solo JPG, PNG y PDF</p>
                  </div>
                  <div id="drop-preview" class="hidden pointer-events-none">
                    <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p id="drop-filename" class="text-sm text-emerald-400 font-medium"></p>
                    <p class="text-xs text-gray-500 mt-1">Comprobante listo para enviar</p>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                  Número de operación / referencia
                  <span class="text-xs font-normal text-gray-500">(opcional pero recomendado)</span>
                </label>
                <input type="text" name="operation_ref"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                       placeholder="ej: OP123456789">
              </div>

              <!-- Submit -->
              <button type="submit"
                      class="w-full py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold text-base
                             shadow-xl shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 active:translate-y-0
                             transition-all duration-150 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Enviar comprobante y confirmar pedido
              </button>

              <p class="text-xs text-gray-500 text-center mt-4">
                🔒 Tu comprobante se almacena de forma segura y encriptada.
                Verificamos pagos en menos de 2 horas.
              </p>
            </form>
          </div>
        </div>

      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>

<script>
// Mostrar detalles del método de pago seleccionado
const methods = document.querySelectorAll('input[name="payment_method"]');
const details = document.querySelectorAll('.payment-detail');

function showMethod(val) {
  details.forEach(d => d.classList.add('hidden'));
  const active = document.getElementById('details-' + val);
  if (active) active.classList.remove('hidden');
}

// Init
methods.forEach(radio => {
  radio.addEventListener('change', () => showMethod(radio.value));
  if (radio.checked) showMethod(radio.value);
});

// Show first by default
if (methods.length > 0) showMethod(methods[0].value);

// File drop zone
const input      = document.getElementById('voucher-input');
const dropDef    = document.getElementById('drop-default');
const dropPrev   = document.getElementById('drop-preview');
const dropName   = document.getElementById('drop-filename');

input.addEventListener('change', () => {
  if (input.files.length > 0) {
    dropDef.classList.add('hidden');
    dropPrev.classList.remove('hidden');
    dropName.textContent = input.files[0].name;
  }
});

// Form validation
document.getElementById('checkout-form').addEventListener('submit', (e) => {
  if (!input.files.length) {
    e.preventDefault();
    alert('Por favor adjunta tu comprobante de pago antes de continuar.');
    input.focus();
  }
});
</script>
