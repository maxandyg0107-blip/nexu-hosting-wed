<?php
/**
 * NEXU HOSTING - Recuperación de Contraseña
 * Solicitud de reset + formulario de nueva contraseña via token
 */
require_once __DIR__ . '/config/bootstrap.php';

if (isLoggedIn()) redirect('/dashboard.php');

$token     = sanitize($_GET['token'] ?? '');
$tokenData = null;
$mode      = 'request'; // 'request' | 'reset'

if ($token) {
    $users     = new UserModel();
    $tokenData = $users->findValidResetToken($token);
    $mode      = $tokenData ? 'reset' : 'invalid';
}

$page_title       = 'Recuperar contraseña';
$meta_description = 'Recupera el acceso a tu cuenta de Nexu Hosting.';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>

<main class="flex-1 flex items-center justify-center px-4 py-24">
  <div class="w-full max-w-md animate-fade-in">

    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-500 shadow-lg shadow-brand-600/40 mb-4">
        <span class="text-2xl">🔑</span>
      </div>
      <h1 class="text-2xl font-extrabold text-white">
        <?php if ($mode === 'request'): ?>Recuperar contraseña
        <?php elseif ($mode === 'reset'): ?>Nueva contraseña
        <?php else: ?>Enlace inválido<?php endif; ?>
      </h1>
      <p class="text-gray-400 mt-1 text-sm">
        <?php if ($mode === 'request'): ?>Ingresa tu email y te enviaremos un enlace de recuperación.
        <?php elseif ($mode === 'reset'): ?>Elige una contraseña segura para tu cuenta <strong class="text-white"><?= e($tokenData['email']) ?></strong>.
        <?php else: ?>El enlace ha expirado o ya fue usado.<?php endif; ?>
      </p>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <div class="bg-surface-card border border-surface-border rounded-2xl p-8 shadow-2xl shadow-black/40">

      <?php if ($mode === 'request'): ?>
      <!-- ── Solicitar reset ── -->
      <form method="POST" action="/auth/send_reset.php" class="space-y-4">
        <?= csrfField() ?>
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Correo electrónico</label>
          <input type="email" name="email" required autofocus autocomplete="email"
                 class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm
                        focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                 placeholder="tu@email.com">
        </div>
        <button type="submit"
                class="w-full py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-semibold text-sm
                       shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-px transition-all">
          Enviar enlace de recuperación
        </button>
      </form>

      <?php elseif ($mode === 'reset'): ?>
      <!-- ── Formulario nueva contraseña ── -->
      <form method="POST" action="/auth/reset_password.php" class="space-y-4">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Nueva contraseña</label>
          <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password"
                 class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm
                        focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                 placeholder="Mínimo 8 caracteres">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1.5">Confirmar contraseña</label>
          <input type="password" name="password2" required minlength="8"
                 class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm
                        focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                 placeholder="Repite la contraseña">
        </div>
        <button type="submit"
                class="w-full py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-semibold text-sm
                       shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-px transition-all">
          Cambiar contraseña
        </button>
      </form>

      <?php else: ?>
      <!-- ── Token inválido ── -->
      <div class="text-center py-4">
        <p class="text-red-400 mb-4">Este enlace de recuperación no es válido o ya expiró.</p>
        <a href="/recuperar.php"
           class="inline-flex px-5 py-2.5 rounded-xl bg-brand-600 text-white font-semibold text-sm hover:-translate-y-px transition-all">
          Solicitar nuevo enlace
        </a>
      </div>
      <?php endif; ?>

      <p class="text-center text-sm text-gray-500 mt-6">
        <a href="/login.php" class="text-brand-400 hover:text-brand-300 transition-colors">← Volver a iniciar sesión</a>
      </p>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>
