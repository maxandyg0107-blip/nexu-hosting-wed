<?php
/**
 * NEXU HOSTING - Sección de Colaboradores / Equipo
 * Muestra el equipo con cards de Discord, roles y redes sociales.
 */
require_once __DIR__ . '/config/bootstrap.php';

$stmt = db()->query(
    "SELECT * FROM collaborators WHERE is_active = 1 ORDER BY sort_order ASC"
);
$team = $stmt->fetchAll();

$page_title       = 'Nuestro Equipo';
$meta_description = 'Conoce al equipo detrás de Nexu Hosting. Personas apasionadas por la tecnología y el gaming.';
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>

<main class="flex-1 pt-20">

  <!-- Hero section -->
  <section class="relative py-20 overflow-hidden">
    <div class="absolute inset-0 bg-hero-gradient pointer-events-none"></div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-600/15 border border-brand-500/20 text-brand-400 text-sm font-semibold mb-6 animate-fade-in">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Nuestro Equipo
      </div>
      <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-5 animate-slide-up">
        Las personas detrás de
        <span class="bg-gradient-to-r from-brand-400 to-cyan-400 bg-clip-text text-transparent">Nexu Hosting</span>
      </h1>
      <p class="text-lg text-gray-400 max-w-2xl mx-auto animate-slide-up" style="animation-delay:0.1s">
        Un equipo apasionado por la tecnología, el gaming y brindar el mejor servicio de hosting en Latinoamérica.
      </p>
    </div>
  </section>

  <!-- Team grid -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
    <?php if (empty($team)): ?>
    <div class="text-center py-16">
      <p class="text-gray-500">El equipo se está configurando. Vuelve pronto.</p>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php foreach ($team as $i => $member): ?>
      <div class="group bg-surface-card border border-surface-border rounded-2xl overflow-hidden
                  hover:border-brand-500/40 hover:-translate-y-2 hover:shadow-2xl hover:shadow-brand-600/10
                  transition-all duration-300 animate-fade-in"
           style="animation-delay:<?= $i * 0.1 ?>s">

        <!-- Avatar / header gradient -->
        <div class="relative h-28 bg-gradient-to-br from-brand-900/60 to-surface-card flex items-end justify-center pb-0">
          <div class="absolute inset-0 bg-gradient-to-br from-brand-600/10 to-cyan-600/5"></div>
          <?php if ($member['avatar_url']): ?>
          <img src="<?= e($member['avatar_url']) ?>" alt="<?= e($member['name']) ?>"
               class="relative w-20 h-20 rounded-2xl object-cover border-2 border-brand-500/30 shadow-xl translate-y-10 z-10">
          <?php else: ?>
          <div class="relative w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-600 to-cyan-500 flex items-center justify-center border-2 border-brand-500/30 shadow-xl translate-y-10 z-10">
            <span class="text-white font-extrabold text-2xl">
              <?= mb_strtoupper(mb_substr($member['name'], 0, 1)) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="pt-12 pb-5 px-5 text-center">
          <h3 class="font-extrabold text-white text-lg leading-tight"><?= e($member['name']) ?></h3>
          <p class="text-brand-400 text-sm font-semibold mt-0.5"><?= e($member['role_title']) ?></p>

          <?php if ($member['description']): ?>
          <p class="text-gray-400 text-xs leading-relaxed mt-3 line-clamp-3">
            <?= e($member['description']) ?>
          </p>
          <?php endif; ?>

          <!-- Social links -->
          <div class="flex items-center justify-center gap-3 mt-4">

            <!-- Discord -->
            <?php if ($member['discord_id'] || $member['discord_tag']): ?>
            <a href="<?= $member['discord_id'] ? 'https://discord.com/users/' . e($member['discord_id']) : '#' ?>"
               target="_blank" rel="noopener"
               class="group/btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#5865F2]/15 border border-[#5865F2]/30
                      text-[#7289DA] text-xs font-semibold hover:bg-[#5865F2]/25 transition-colors"
               title="<?= e($member['discord_tag'] ?? 'Discord') ?>">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
              </svg>
              Discord
            </a>
            <?php endif; ?>

            <!-- Twitter -->
            <?php if ($member['twitter_url']): ?>
            <a href="<?= e($member['twitter_url']) ?>" target="_blank" rel="noopener"
               class="p-1.5 rounded-lg text-sky-400 hover:bg-sky-500/10 transition-colors"
               title="Twitter/X">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <?php endif; ?>

            <!-- GitHub -->
            <?php if ($member['github_url']): ?>
            <a href="<?= e($member['github_url']) ?>" target="_blank" rel="noopener"
               class="p-1.5 rounded-lg text-gray-400 hover:bg-white/10 hover:text-white transition-colors"
               title="GitHub">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Join CTA -->
    <div class="mt-16 text-center p-10 bg-surface-card border border-brand-500/20 rounded-2xl"
         style="background:radial-gradient(ellipse at center top,rgba(124,58,237,0.08),transparent)">
      <p class="text-3xl mb-3">💬</p>
      <h3 class="text-xl font-bold text-white mb-2">¿Quieres unirte al equipo?</h3>
      <p class="text-gray-400 text-sm max-w-md mx-auto mb-6">
        Buscamos personas apasionadas por el hosting, las redes y el gaming. Únete a nuestra comunidad de Discord para empezar.
      </p>
      <a href="https://discord.gg/nexuhosting" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#5865F2] text-white font-bold hover:-translate-y-0.5 transition-transform shadow-lg shadow-[#5865F2]/30">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
        Únete al Discord
      </a>
    </div>
  </section>

</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>
