<?php
// Determina la página activa para resaltar el enlace
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navLinks = [
    'index'         => ['url' => '/index.php',         'label' => 'Inicio'],
    'planes'        => ['url' => '/planes.php',        'label' => 'Planes'],
    'colaboradores' => ['url' => '/colaboradores.php', 'label' => 'Equipo'],
    'estado'        => ['url' => '/estado.php',        'label' => 'Estado'],
    'contacto'      => ['url' => '/contacto.php',      'label' => 'Contacto'],
];
?>
<header id="navbar"
  class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
  x-data="{ open: false, scrolled: false }"
  @scroll.window="scrolled = window.scrollY > 20">

  <div :class="scrolled ? 'bg-surface/95 backdrop-blur-xl border-b border-surface-border shadow-lg shadow-black/30' : 'bg-transparent'"
       class="transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 lg:h-18">

        <!-- Logo -->
        <a href="/index.php" class="flex items-center gap-2.5 group">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center text-white font-black text-sm shadow-lg shadow-brand-600/40 group-hover:shadow-brand-600/60 transition-shadow">
            N
          </div>
          <span class="font-extrabold text-lg tracking-tight text-white">
            Nexu <span class="text-brand-400">Hosting</span>
          </span>
        </a>

        <!-- Nav links — desktop -->
        <nav class="hidden lg:flex items-center gap-1">
          <?php foreach ($navLinks as $slug => $link): ?>
            <a href="<?= $link['url'] ?>"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150
                      <?= ($currentPage === $slug || ($slug === 'index' && $currentPage === 'index'))
                          ? 'text-white bg-white/10'
                          : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
              <?= $link['label'] ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <!-- Right side -->
        <div class="flex items-center gap-3">

          <!-- Currency switcher -->
          <form method="POST" action="/auth/currency.php" class="hidden sm:flex items-center">
            <?= csrfField() ?>
            <select name="currency" onchange="this.form.submit()"
              class="bg-surface-card border border-surface-border text-gray-400 text-xs rounded-lg px-2 py-1.5 focus:outline-none focus:border-brand-500 cursor-pointer hover:border-brand-500 transition-colors">
              <?php foreach (SUPPORTED_CURRENCIES as $cur): ?>
                <option value="<?= $cur ?>" <?= activeCurrency() === $cur ? 'selected' : '' ?>><?= $cur ?></option>
              <?php endforeach; ?>
            </select>
          </form>

          <?php if (isLoggedIn()): ?>
            <a href="/dashboard.php"
               class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-surface-border text-sm font-medium text-gray-300 hover:text-white hover:border-brand-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
              Dashboard
            </a>
            <?php if (isAdmin()): ?>
            <a href="/admin/dashboard.php"
               class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-brand-600/20 border border-brand-500/30 text-brand-400 text-xs font-semibold hover:bg-brand-600/30 transition-colors">
              ⚙ Admin
            </a>
            <?php endif; ?>
            <a href="/auth/logout.php"
               class="px-4 py-2 rounded-lg text-sm font-medium text-gray-400 hover:text-white transition-colors">
              Salir
            </a>
          <?php else: ?>
            <a href="/login.php"
               class="px-4 py-2 rounded-lg border border-surface-border text-sm font-medium text-gray-300 hover:text-white hover:border-brand-500 transition-colors">
              Iniciar sesión
            </a>
            <a href="/register.php"
               class="px-4 py-2 rounded-lg bg-gradient-to-r from-brand-600 to-brand-500 text-white text-sm font-semibold shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-px transition-all duration-150">
              Empezar gratis
            </a>
          <?php endif; ?>

          <!-- Mobile menu button -->
          <button @click="open = !open"
                  class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
            <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open"  class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <div x-show="open" x-transition
           class="lg:hidden pb-4 border-t border-surface-border mt-2 pt-4 space-y-1">
        <?php foreach ($navLinks as $slug => $link): ?>
          <a href="<?= $link['url'] ?>"
             class="block px-4 py-2.5 rounded-lg text-sm font-medium
                    <?= $currentPage === $slug ? 'text-white bg-white/10' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
            <?= $link['label'] ?>
          </a>
        <?php endforeach; ?>
        <div class="pt-2 flex gap-2">
          <!-- Currency switcher mobile -->
          <form method="POST" action="/auth/currency.php">
            <?= csrfField() ?>
            <select name="currency" onchange="this.form.submit()"
              class="bg-surface-card border border-surface-border text-gray-400 text-xs rounded-lg px-2 py-1.5 focus:outline-none">
              <?php foreach (SUPPORTED_CURRENCIES as $cur): ?>
                <option value="<?= $cur ?>" <?= activeCurrency() === $cur ? 'selected' : '' ?>><?= $cur ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Alpine.js for mobile menu -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
