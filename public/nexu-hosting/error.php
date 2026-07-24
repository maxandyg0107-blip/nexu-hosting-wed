<?php
/**
 * NEXU HOSTING - Páginas de Error
 */
require_once __DIR__ . '/config/bootstrap.php';

$code = (int)($_GET['code'] ?? 404);
$messages = [
    403 => ['Acceso denegado',            'No tienes permiso para acceder a esta página.'],
    404 => ['Página no encontrada',       'La página que buscas no existe o fue eliminada.'],
    500 => ['Error del servidor',         'Ocurrió un error interno. Nuestro equipo ha sido notificado.'],
];
[$title, $desc] = $messages[$code] ?? ['Error', 'Ocurrió un error inesperado.'];

http_response_code($code);
$page_title = "$code — $title";
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-24">
  <div class="text-center max-w-md animate-fade-in">
    <div class="text-8xl font-black text-brand-600/30 mb-4"><?= $code ?></div>
    <h1 class="text-2xl font-extrabold text-white mb-3"><?= e($title) ?></h1>
    <p class="text-gray-400 mb-8"><?= e($desc) ?></p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="/index.php" class="px-6 py-3 rounded-xl bg-brand-600 text-white font-semibold hover:-translate-y-0.5 transition-transform">
        Volver al inicio
      </a>
      <a href="/contacto.php" class="px-6 py-3 rounded-xl border border-surface-border text-gray-400 font-semibold hover:text-white hover:border-brand-500/50 transition-all">
        Reportar problema
      </a>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>
