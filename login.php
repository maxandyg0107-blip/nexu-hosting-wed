<?php
/**
 * NEXU HOSTING - Login v2.1
 * Animaciones, show/hide password, honeypot anti-bot, CSRF, OAuth.
 */
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? '/admin/dashboard.php' : '/dashboard.php');
}

$page_title       = 'Iniciar sesión';
$meta_description = 'Accede a tu panel de cliente de Nexu Hosting.';
$prefillEmail     = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>

<!-- Extra styles para esta página -->
<style>
  @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
  @keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(124,58,237,.4)}100%{box-shadow:0 0 0 18px rgba(124,58,237,0)}}
  .float{animation:float 4s ease-in-out infinite}
  .pulse-ring{animation:pulse-ring 2s ease-out infinite}
  .input-focus-line{transition:width .3s ease;width:0;height:2px;background:linear-gradient(90deg,#7c3aed,#06b6d4)}
  input:focus ~ .input-focus-line{width:100%}
</style>

<div class="min-h-screen flex flex-col relative overflow-hidden">

  <!-- Fondo animado con partículas / orbes -->
  <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-brand-700/20 rounded-full blur-3xl animate-pulse-slow"></div>
    <div class="absolute -bottom-60 -left-40 w-[500px] h-[500px] bg-cyan-700/15 rounded-full blur-3xl animate-pulse-slow" style="animation-delay:1.5s"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] bg-purple-700/10 rounded-full blur-2xl"></div>
    <!-- Cuadrícula decorativa -->
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image:linear-gradient(rgba(255,255,255,.1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.1) 1px,transparent 1px);background-size:50px 50px"></div>
  </div>

  <?php require_once __DIR__ . '/views/partials/navbar.php'; ?>

  <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-20">
    <div class="w-full max-w-[420px]">

      <!-- Logo animado -->
      <div class="text-center mb-10 animate-fade-in">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl
                    bg-gradient-to-br from-brand-600 to-cyan-500
                    shadow-2xl shadow-brand-600/50 mb-5 float pulse-ring">
          <span class="text-white font-black text-2xl select-none">N</span>
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Bienvenido de nuevo</h1>
        <p class="text-gray-400 mt-1.5 text-sm">Inicia sesión en tu cuenta de Nexu Hosting</p>
      </div>

      <!-- Flash -->
      <div data-flash class="animate-fade-in" style="animation-delay:.1s">
        <?= renderFlash() ?>
      </div>

      <!-- Card principal -->
      <div class="bg-surface-card/80 backdrop-blur-xl border border-surface-border rounded-3xl p-8
                  shadow-2xl shadow-black/50 animate-slide-up" style="animation-delay:.15s">

        <!-- OAuth -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <a href="/auth/google/redirect.php"
             class="group flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl
                    border border-surface-border bg-surface/80
                    hover:bg-white/5 hover:border-white/20 hover:-translate-y-0.5
                    transition-all duration-200 text-sm font-semibold text-gray-300">
            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google
          </a>
          <a href="/auth/discord/redirect.php"
             class="group flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl
                    border border-[#5865F2]/30 bg-[#5865F2]/10
                    hover:bg-[#5865F2]/20 hover:border-[#5865F2]/50 hover:-translate-y-0.5
                    transition-all duration-200 text-sm font-semibold text-[#7289DA]">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
            </svg>
            Discord
          </a>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 mb-6">
          <div class="flex-1 h-px bg-surface-border"></div>
          <span class="text-xs text-gray-500 uppercase tracking-widest">o con email</span>
          <div class="flex-1 h-px bg-surface-border"></div>
        </div>

        <!-- Formulario -->
        <form id="loginForm" method="POST" action="/auth/login.php" class="space-y-5" novalidate>
          <?= csrfField() ?>
          <!-- Honeypot anti-bot (debe quedar vacío) -->
          <div class="absolute opacity-0 pointer-events-none -z-10" aria-hidden="true">
            <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
          </div>

          <!-- Email -->
          <div class="space-y-1">
            <label for="email" class="block text-sm font-semibold text-gray-300">Correo electrónico</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
              </div>
              <input type="email" id="email" name="email" required
                     autocomplete="email" inputmode="email" spellcheck="false"
                     maxlength="255" minlength="5"
                     value="<?= e($prefillEmail) ?>"
                     placeholder="tu@email.com"
                     class="w-full pl-10 pr-4 py-3 rounded-xl bg-surface border border-surface-border text-white
                            placeholder-gray-600 text-sm
                            focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20
                            transition-all duration-200 peer">
            </div>
          </div>

          <!-- Contraseña con toggle visible/oculto -->
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label for="password" class="block text-sm font-semibold text-gray-300">Contraseña</label>
              <a href="/recuperar.php"
                 class="text-xs text-brand-400 hover:text-brand-300 transition-colors font-medium">
                ¿Olvidaste tu contraseña?
              </a>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <input type="password" id="password" name="password" required
                     autocomplete="current-password"
                     minlength="8" maxlength="128"
                     placeholder="••••••••"
                     class="w-full pl-10 pr-12 py-3 rounded-xl bg-surface border border-surface-border text-white
                            placeholder-gray-600 text-sm
                            focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20
                            transition-all duration-200">
              <!-- Botón show/hide -->
              <button type="button" id="togglePassword"
                      class="absolute inset-y-0 right-3.5 flex items-center text-gray-500 hover:text-brand-400 transition-colors"
                      aria-label="Mostrar/ocultar contraseña">
                <!-- Ojo cerrado (default) -->
                <svg id="eyeOff" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
                <!-- Ojo abierto (oculto por defecto) -->
                <svg id="eyeOn" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Botón submit con loader -->
          <button type="submit" id="btnLogin"
                  class="group w-full py-3.5 rounded-xl
                         bg-gradient-to-r from-brand-600 to-brand-500
                         text-white font-bold text-sm tracking-wide
                         shadow-lg shadow-brand-600/30
                         hover:shadow-brand-600/60 hover:-translate-y-0.5 hover:from-brand-500 hover:to-brand-400
                         active:translate-y-0 active:shadow-brand-600/20
                         focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-surface-card
                         transition-all duration-200
                         flex items-center justify-center gap-2.5">
            <span id="btnText">Iniciar sesión</span>
            <!-- Spinner (oculto hasta submit) -->
            <svg id="btnSpinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
          ¿No tienes cuenta?
          <a href="/register.php" class="text-brand-400 hover:text-brand-300 font-semibold transition-colors">
            Créala gratis →
          </a>
        </p>
      </div>

      <!-- Seguridad badge -->
      <div class="flex items-center justify-center gap-2 mt-5 text-xs text-gray-600 animate-fade-in" style="animation-delay:.4s">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        Conexión cifrada · Protección CSRF · Argon2ID
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>

<script>
/* ── Toggle contraseña ──────────────────────────────────── */
(function () {
  const btn     = document.getElementById('togglePassword');
  const input   = document.getElementById('password');
  const eyeOff  = document.getElementById('eyeOff');
  const eyeOn   = document.getElementById('eyeOn');
  if (!btn || !input) return;

  btn.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    eyeOff.classList.toggle('hidden', show);
    eyeOn.classList.toggle('hidden',  !show);
  });
})();

/* ── Loader en submit ───────────────────────────────────── */
(function () {
  const form    = document.getElementById('loginForm');
  const btn     = document.getElementById('btnLogin');
  const text    = document.getElementById('btnText');
  const spinner = document.getElementById('btnSpinner');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    // Validación HTML5 nativa
    if (!form.checkValidity()) return;

    // Activar estado de carga
    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');
    text.textContent = 'Verificando...';
    spinner.classList.remove('hidden');

    // Timeout de seguridad: si el servidor tarda >10 s, re-habilitar el botón
    setTimeout(() => {
      btn.disabled = false;
      btn.classList.remove('opacity-80', 'cursor-not-allowed');
      text.textContent = 'Iniciar sesión';
      spinner.classList.add('hidden');
    }, 10000);
  });
})();

/* ── Auto-dismiss flash ─────────────────────────────────── */
setTimeout(() => {
  document.querySelectorAll('[data-flash] > div').forEach(el => {
    el.style.transition = 'opacity .5s ease, transform .5s ease';
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(-8px)';
    setTimeout(() => el.remove(), 500);
  });
}, 4500);
</script>
