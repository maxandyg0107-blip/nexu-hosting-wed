<?php
/**
 * views/layouts/header.php
 * Requiere que la variable $pageTitle esté definida antes del include.
 */
if (!defined('NEXU_APP')) { http_response_code(403); die('Acceso directo no permitido.'); }
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<meta name="robots" content="noindex, nofollow">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          base:    '#0A0E17',
          surface: '#111827',
          surface2:'#161F2E',
          line:    '#232C3D',
          cyan:    '#2DD4BF',
          violet:  '#8B7CF6',
          amber:   '#F5B454',
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body: ['"Inter"', 'sans-serif'],
          mono: ['"JetBrains Mono"', 'monospace'],
        },
      },
    },
  };
</script>
<style>
  body { background-color:#0A0E17; background-image: radial-gradient(circle at 15% 0%, rgba(45,212,191,0.07), transparent 40%), radial-gradient(circle at 85% 20%, rgba(139,124,246,0.07), transparent 40%); }
  .grid-glow { background-image: linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px); background-size: 34px 34px; }
</style>
</head>
<body class="font-body bg-base text-slate-200 min-h-screen antialiased">
<nav class="border-b border-line/80 bg-base/80 backdrop-blur sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="/index.php" class="flex items-center gap-2 font-display font-semibold text-lg text-white">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-gradient-to-br from-cyan to-violet text-base font-bold">N</span>
      Nexu<span class="text-cyan">Hosting</span>
    </a>
    <div class="flex items-center gap-3 text-sm">
      <?php if (is_logged_in()): ?>
        <span class="hidden sm:inline text-slate-400 font-mono text-xs">
          <?= e($_SESSION['username'] ?? '') ?> · <?= e($_SESSION['role'] ?? '') ?>
        </span>
        <?php if (is_admin()): ?>
          <a href="/admin_orders.php" class="px-3 py-1.5 rounded-md border border-line hover:border-cyan/50 hover:text-cyan transition">Admin</a>
        <?php endif; ?>
        <a href="/dashboard.php" class="px-3 py-1.5 rounded-md border border-line hover:border-cyan/50 hover:text-cyan transition">Panel</a>
        <a href="/logout.php" class="px-3 py-1.5 rounded-md bg-surface2 border border-line hover:border-red-500/50 hover:text-red-400 transition">Salir</a>
      <?php else: ?>
        <a href="/login.php" class="px-3 py-1.5 rounded-md border border-line hover:border-cyan/50 hover:text-cyan transition">Ingresar</a>
        <a href="/register.php" class="px-3 py-1.5 rounded-md bg-cyan text-base font-semibold hover:bg-cyan/90 transition">Crear cuenta</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
