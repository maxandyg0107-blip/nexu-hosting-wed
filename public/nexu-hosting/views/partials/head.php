<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($meta_description ?? 'Nexu Hosting — Servidores Minecraft y Web Hosting en Perú. Paga con Yape, Plin, Banco de la Nación e Interbank.') ?>">
  <meta name="theme-color" content="#7c3aed">

  <!-- Open Graph -->
  <meta property="og:title"       content="<?= e(($page_title ?? 'Nexu Hosting') . ' | Nexu Hosting') ?>">
  <meta property="og:description" content="<?= e($meta_description ?? 'Servidores de alto rendimiento en Perú') ?>">
  <meta property="og:image"       content="<?= APP_URL ?>/assets/img/og-image.png">
  <meta property="og:type"        content="website">

  <title><?= e(($page_title ?? '') ? $page_title . ' | Nexu Hosting' : 'Nexu Hosting — Game & Web Hosting Perú') ?></title>

  <!-- Tailwind CSS CDN (para producción usar build local) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              50:  '#f5f3ff',
              100: '#ede9fe',
              200: '#ddd6fe',
              300: '#c4b5fd',
              400: '#a78bfa',
              500: '#8b5cf6',
              600: '#7c3aed',
              700: '#6d28d9',
              800: '#5b21b6',
              900: '#4c1d95',
            },
            surface: {
              DEFAULT: '#0d0d14',
              card:    '#13131f',
              border:  '#1e1e30',
              hover:   '#1a1a2e',
            }
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
          animation: {
            'fade-in':   'fadeIn 0.4s ease-out',
            'slide-up':  'slideUp 0.5s ease-out',
            'pulse-slow':'pulse 3s cubic-bezier(0.4,0,0.6,1) infinite',
            'glow':      'glow 2s ease-in-out infinite alternate',
          },
          keyframes: {
            fadeIn:  { from: { opacity: '0', transform: 'translateY(12px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
            slideUp: { from: { opacity: '0', transform: 'translateY(24px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
            glow:    { from: { boxShadow: '0 0 20px rgba(124,58,237,0.2)' }, to: { boxShadow: '0 0 40px rgba(124,58,237,0.5)' } },
          },
          backgroundImage: {
            'hero-gradient': 'radial-gradient(ellipse 80% 50% at 50% -20%,rgba(124,58,237,0.25),transparent)',
            'card-gradient': 'linear-gradient(135deg,rgba(124,58,237,0.08),rgba(6,182,212,0.05))',
          }
        }
      }
    }
  </script>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    /* Custom scrollbar */
    ::-webkit-scrollbar{width:6px;height:6px}
    ::-webkit-scrollbar-track{background:#0d0d14}
    ::-webkit-scrollbar-thumb{background:#7c3aed;border-radius:3px}

    /* Chart bars animation */
    @keyframes growBar{from{height:0}to{height:var(--bar-h)}}
    .bar-animate{animation:growBar 0.8s ease-out forwards}

    /* Shimmer loader */
    @keyframes shimmer{0%{background-position:-468px 0}100%{background-position:468px 0}}
    .shimmer{background:linear-gradient(to right,#1e1e30 8%,#2a2a40 18%,#1e1e30 33%);background-size:800px 104px;animation:shimmer 1.5s infinite linear}

    body{background:#0d0d14;color:#f3f4f6;font-family:'Inter',sans-serif}
  </style>
</head>
<body class="dark min-h-screen bg-surface text-gray-100 antialiased">
