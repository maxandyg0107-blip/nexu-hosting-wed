<?php
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
$adminDir  = basename(dirname($_SERVER['PHP_SELF']));
$current   = ($adminDir === 'admin' ? $adminPage : $adminPage);

$adminNav = [
    ['file'=>'dashboard', 'label'=>'Dashboard',    'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['file'=>'orders',    'label'=>'Órdenes',       'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'],
    ['file'=>'clients',   'label'=>'Clientes',      'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
    ['file'=>'servers',   'label'=>'Servidores',    'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>'],
    ['file'=>'settings',  'label'=>'Configuración', 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'],
    ['file'=>'audit',     'label'=>'Auditoría',     'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
];
?>

<!-- Mobile overlay -->
<div x-data="{open:false}" @keydown.escape.window="open=false">
  <!-- Mobile toggle -->
  <button @click="open=!open"
          class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-xl bg-surface-card border border-surface-border text-gray-400 hover:text-white">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
  </button>

  <!-- Sidebar -->
  <aside :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
         class="fixed top-0 left-0 z-40 h-screen w-64 bg-surface-card border-r border-surface-border
                flex flex-col transition-transform duration-300 ease-in-out">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-surface-border">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center font-black text-sm">N</div>
      <div>
        <span class="font-extrabold text-white">Nexu</span>
        <span class="block text-xs text-brand-400 font-semibold -mt-0.5">Admin Panel</span>
      </div>
    </div>

    <!-- Nav items -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <?php foreach ($adminNav as $item): ?>
        <?php $active = ($current === $item['file']); ?>
        <a href="/admin/<?= $item['file'] ?>.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                  <?= $active ? 'bg-brand-600/20 text-brand-400 border border-brand-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' ?>">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?= $item['icon'] ?>
          </svg>
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Bottom actions -->
    <div class="px-3 py-4 border-t border-surface-border space-y-1">
      <a href="/dashboard.php"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Portal Cliente
      </a>
      <a href="/auth/logout.php"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:text-red-400 hover:bg-red-500/5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Cerrar sesión
      </a>
    </div>
  </aside>
</div>
