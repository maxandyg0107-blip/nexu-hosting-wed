<?php
/**
 * NEXU HOSTING - Página de instalación / configuración inicial.
 * Se muestra cuando las variables de entorno de BD no están configuradas.
 * En producción (Render), esta página guía al usuario para configurarlas.
 */
define('SKIP_DB_CHECK', true);
define('APP_ENV',   getenv('APP_ENV')  ?: 'production');
define('APP_DEBUG', APP_ENV === 'development');
define('APP_NAME',  'Nexu Hosting');
define('APP_URL',   rtrim(getenv('APP_URL') ?: 'https://nexuhosting.com', '/'));

date_default_timezone_set('America/Lima');

// Chequear estado real de las variables
function _env_check(string $key): bool
{
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?? null;
    return $v !== null && $v !== false && $v !== '';
}

$checks = [
    'DB_HOST' => _env_check('DB_HOST'),
    'DB_NAME' => _env_check('DB_NAME'),
    'DB_USER' => _env_check('DB_USER'),
    'DB_PASS' => _env_check('DB_PASS'),
];

$allConfigured = !in_array(false, $checks, true);

// Si ya está todo configurado, intentar conexión real
$dbOk    = false;
$dbError = '';
if ($allConfigured) {
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: '',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: ''
        );
        $pdo = new PDO($dsn, getenv('DB_USER') ?: '', getenv('DB_PASS') ?: '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->query("SELECT 1");
        $dbOk = true;
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instalación — Nexu Hosting</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: { extend: { colors: {
        brand: { 400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',700:'#6d28d9' },
        surface: { DEFAULT:'#0d0d14', card:'#13131f', border:'#1e1e30' }
      }}}
    }
  </script>
  <style>body{background:#0d0d14;color:#f3f4f6;font-family:system-ui,sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-2xl">

  <!-- Logo -->
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-500 shadow-xl shadow-brand-600/40 mb-4">
      <span class="text-white font-black text-2xl">N</span>
    </div>
    <h1 class="text-3xl font-extrabold text-white">Nexu Hosting</h1>
    <p class="text-gray-400 mt-1">Asistente de Configuración</p>
  </div>

  <!-- Estado general -->
  <div class="<?= $dbOk ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400' ?> border rounded-2xl p-5 mb-6 flex items-center gap-4">
    <span class="text-3xl"><?= $dbOk ? '✅' : '⚙️' ?></span>
    <div>
      <p class="font-bold text-base"><?= $dbOk ? 'Sistema listo para usar' : 'Configuración requerida' ?></p>
      <p class="text-sm mt-0.5 opacity-80">
        <?= $dbOk
            ? 'La base de datos está conectada. Puedes comenzar a usar Nexu Hosting.'
            : 'Configura las variables de entorno en el panel de Render para continuar.' ?>
      </p>
    </div>
  </div>

  <!-- Checks de variables -->
  <div class="bg-surface-card border border-surface-border rounded-2xl p-6 mb-6">
    <h2 class="font-bold text-white mb-4 flex items-center gap-2">
      <span>🔧</span> Variables de entorno
    </h2>
    <div class="space-y-3">
      <?php foreach ($checks as $key => $ok): ?>
      <div class="flex items-center justify-between py-2 border-b border-surface-border last:border-0">
        <span class="font-mono text-sm <?= $ok ? 'text-gray-300' : 'text-red-400' ?>"><?= htmlspecialchars($key) ?></span>
        <span class="text-sm font-semibold <?= $ok ? 'text-emerald-400' : 'text-red-400' ?>">
          <?= $ok ? '✓ Configurado' : '✗ Falta' ?>
        </span>
      </div>
      <?php endforeach; ?>
      <div class="flex items-center justify-between py-2">
        <span class="font-mono text-sm text-gray-300">Conexión DB</span>
        <span class="text-sm font-semibold <?= $dbOk ? 'text-emerald-400' : 'text-red-400' ?>">
          <?= $dbOk ? '✓ Exitosa' : '✗ Error' ?>
        </span>
      </div>
      <?php if ($dbError): ?>
      <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
        <p class="text-xs text-red-400 font-mono"><?= htmlspecialchars(substr($dbError, 0, 200)) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Instrucciones Render -->
  <?php if (!$dbOk): ?>
  <div class="bg-surface-card border border-surface-border rounded-2xl p-6 mb-6">
    <h2 class="font-bold text-white mb-4 flex items-center gap-2">
      <span>📋</span> Pasos en Render.com
    </h2>
    <ol class="space-y-3 text-sm text-gray-400">
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-400 text-xs font-bold flex items-center justify-center">1</span>Ve a tu servicio en <strong class="text-white">render.com → Environment</strong></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-400 text-xs font-bold flex items-center justify-center">2</span>Agrega las variables de entorno marcadas con ✗ arriba</li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-400 text-xs font-bold flex items-center justify-center">3</span>Crea una base de datos MySQL en <strong class="text-white">freemysqlhosting.net</strong>, <strong class="text-white">PlanetScale</strong> o <strong class="text-white">Railway</strong></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-400 text-xs font-bold flex items-center justify-center">4</span>Importa el archivo <code class="bg-surface px-1.5 py-0.5 rounded text-brand-400">sql/nexuhosting_v2.sql</code></li>
      <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600/30 border border-brand-500/30 text-brand-400 text-xs font-bold flex items-center justify-center">5</span>Guarda y espera el re-deploy automático</li>
    </ol>
    <!-- Variables de referencia -->
    <div class="mt-5 p-4 bg-surface rounded-xl border border-surface-border">
      <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-3">Variables requeridas</p>
      <div class="grid grid-cols-2 gap-1.5 font-mono text-xs">
        <?php foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','APP_URL'] as $v): ?>
        <div class="px-2.5 py-1.5 bg-surface-card border border-surface-border rounded-lg text-brand-400"><?= $v ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php else: ?>
  <!-- Listo para usar -->
  <div class="text-center">
    <a href="/index.php"
       class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500
              text-white font-bold shadow-xl shadow-brand-600/30 hover:-translate-y-0.5 hover:shadow-brand-600/50 transition-all">
      Ir a Nexu Hosting →
    </a>
    <p class="text-xs text-gray-600 mt-3">
      Admin: <code class="text-gray-400">admin@nexuhosting.com</code> / <code class="text-gray-400">Admin2024!</code>
      — Cambia la contraseña inmediatamente.
    </p>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
