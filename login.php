<?php
/**
 * login.php
 * Punto de entrada público para el inicio de sesión.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin_orders.php' : '/dashboard.php');
}

$result = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $result = $auth->login($_POST);
    if ($result['success']) {
        redirect(($result['role'] ?? 'client') === 'admin' ? '/admin_orders.php' : '/dashboard.php');
    }
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/views/layouts/header.php';
?>
<main class="max-w-md mx-auto px-6 py-20">
  <div class="mb-8 text-center">
    <h1 class="font-display text-2xl font-semibold text-white">Bienvenido de nuevo</h1>
    <p class="text-slate-400 text-sm mt-1">Ingresa a tu panel de cliente de Nexu Hosting.</p>
  </div>

  <?php if (!empty($result['errors'])): ?>
    <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
      <?php foreach ($result['errors'] as $err): ?>
        <p><?= e($err) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/login.php" class="bg-surface border border-line rounded-xl p-6 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-mono text-slate-400 mb-1.5">Correo electrónico</label>
      <input type="email" name="email" required autocomplete="email"
             class="w-full rounded-md bg-surface2 border border-line px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan/50 focus:border-cyan/50"
             placeholder="tucorreo@ejemplo.com">
    </div>
    <div>
      <label class="block text-xs font-mono text-slate-400 mb-1.5">Contraseña</label>
      <input type="password" name="password" required autocomplete="current-password"
             class="w-full rounded-md bg-surface2 border border-line px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan/50 focus:border-cyan/50"
             placeholder="••••••••">
    </div>
    <button type="submit"
            class="w-full rounded-md bg-cyan text-base font-semibold py-2.5 hover:bg-cyan/90 transition">
      Ingresar
    </button>
  </form>

  <p class="text-center text-sm text-slate-500 mt-6">
    ¿No tienes cuenta? <a href="/register.php" class="text-cyan hover:underline">Regístrate aquí</a>
  </p>
</main>
<?php require __DIR__ . '/views/layouts/footer.php'; ?>
