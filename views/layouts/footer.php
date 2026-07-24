<?php if (!defined('NEXU_APP')) { http_response_code(403); die('Acceso directo no permitido.'); } ?>
<footer class="border-t border-line/80 mt-24">
  <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
    <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Todos los derechos reservados.</p>
    <p class="font-mono">Perú 🇵🇪 · Yape · Plin · Transferencia bancaria</p>
  </div>
</footer>
</body>
</html>
