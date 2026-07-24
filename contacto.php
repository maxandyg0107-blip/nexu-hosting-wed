<?php
/**
 * NEXU HOSTING - Página de Contacto
 * Versión auditada y corregida
 */
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

// ─── Rate limiting por sesión (máx. 3 envíos por hora) ───
if (!isset($_SESSION['contact_attempts'])) {
    $_SESSION['contact_attempts'] = [];
}
$now = time();
$_SESSION['contact_attempts'] = array_filter(
    $_SESSION['contact_attempts'],
    static fn(int $t): bool => $t > $now - 3600
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrFail();

    // Rate limit check
    if (count($_SESSION['contact_attempts']) >= 3) {
        setFlash('Has alcanzado el límite de mensajes por hora. Inténtalo más tarde.', 'warning');
        redirect('/contacto.php');
    }

    // Recoger y limpiar entradas (sin "sanitize" mágico; validamos explícitamente)
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // ─── Validaciones estrictas ───
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $errors[] = 'Correo inválido o demasiado largo.';
    }
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
        $errors[] = 'El asunto debe tener entre 3 y 150 caracteres.';
    }
    if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $errors[] = 'El mensaje debe tener entre 10 y 5000 caracteres.';
    }

    if (empty($errors)) {
        try {
            $pdo = db();
            $userId = isLoggedIn() ? ($_SESSION['user_id'] ?? null) : null;

            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] 
                ?? $_SERVER['REMOTE_ADDR'] 
                ?? '0.0.0.0';
            $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ?: '0.0.0.0';

            $stmt = $pdo->prepare(
                "INSERT INTO support_tickets 
                 (user_id, guest_name, guest_email, subject, message, department, priority, status, ip_address, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $userId,
                $name,
                $email,
                $subject,
                $message,
                'General',
                'medium',
                'open',
                $ipAddress
            ]);

            $ticketId = (int) $pdo->lastInsertId();
            $_SESSION['contact_attempts'][] = $now;

            // Log interno controlado (sin datos sensibles completos)
            error_log(sprintf(
                '[NEXU CONTACT] Ticket #%d | From: %s | Subject: %s',
                $ticketId,
                $email,
                $subject
            ));

            setFlash('¡Mensaje enviado! Te responderemos en menos de 24 horas.', 'success');

            AuditModel::log(
                'contact.submitted',
                'support_tickets',
                $ticketId,
                $userId,
                ['email' => $email, 'subject' => $subject, 'ip' => $ipAddress]
            );

            redirect('/contacto.php');
        } catch (Throwable $e) {
            error_log('[NEXU CONTACT ERROR] ' . $e->getMessage());
            setFlash('Error al enviar el mensaje. Intenta de nuevo.', 'danger');
            redirect('/contacto.php');
        }
    } else {
        setFlash(implode(' ', $errors), 'danger');
    }
}

$page_title = 'Contacto';

// Valores pre-llenados seguros para el formulario
$prefillName    = e($_POST['name']    ?? (currentUser()['full_name'] ?? ''));
$prefillEmail   = e($_POST['email']   ?? (currentUser()['email']    ?? ''));
$prefillSubject = e($_POST['subject'] ?? '');
$prefillMessage = e($_POST['message'] ?? '');
?>
<?php require_once __DIR__ . '/views/partials/head.php'; ?>
<div class="min-h-screen flex flex-col">
<?php require_once __DIR__ . '/views/partials/navbar.php'; ?>
<main class="flex-1 pt-24 pb-16">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-12 animate-fade-in">
      <h1 class="text-4xl font-extrabold text-white mb-3">Contáctanos</h1>
      <p class="text-gray-400 max-w-xl mx-auto">¿Tienes preguntas sobre nuestros planes o necesitas ayuda? Escríbenos y respondemos en menos de 24 horas.</p>
    </div>

    <div data-flash><?= renderFlash() ?></div>

    <div class="grid lg:grid-cols-5 gap-10">

      <!-- Contact info -->
      <div class="lg:col-span-2 space-y-5 animate-fade-in">
        <?php
        $contactInfo = [
          ['💬','Discord','La forma más rápida','https://discord.gg/nexuhosting','Unirse al servidor'],
          ['📧','Email','soporte@nexuhosting.com','mailto:soporte@nexuhosting.com','Enviar email'],
          ['⏱️','Tiempo de respuesta','Menos de 24 horas en tickets','#',''],
        ];
        foreach ($contactInfo as $c):
        ?>
        <div class="p-5 bg-surface-card border border-surface-border rounded-2xl">
          <div class="flex items-start gap-4">
            <span class="text-3xl flex-shrink-0"><?= $c[0] ?></span>
            <div>
              <p class="font-bold text-white"><?= e($c[1]) ?></p>
              <p class="text-sm text-gray-400 mb-2"><?= e($c[2]) ?></p>
              <?php if ($c[4]): ?>
              <a href="<?= e($c[3]) ?>" target="_blank" rel="noopener"
                 class="text-xs text-brand-400 font-semibold hover:text-brand-300 transition-colors">
                <?= e($c[4]) ?> →
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Contact form -->
      <div class="lg:col-span-3 animate-slide-up">
        <div class="bg-surface-card border border-surface-border rounded-2xl p-8">
          <h2 class="font-bold text-white mb-6">Envíanos un mensaje</h2>
          <form method="POST" action="/contacto.php" class="space-y-4" novalidate>
            <?= csrfField() ?>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label for="contact-name" class="block text-sm font-medium text-gray-300 mb-1.5">Nombre</label>
                <input type="text" id="contact-name" name="name" required maxlength="100"
                       value="<?= $prefillName ?>"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                       placeholder="Tu nombre completo">
              </div>
              <div>
                <label for="contact-email" class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                <input type="email" id="contact-email" name="email" required maxlength="255"
                       value="<?= $prefillEmail ?>"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                       placeholder="tu@email.com">
              </div>
            </div>
            <div>
              <label for="contact-subject" class="block text-sm font-medium text-gray-300 mb-1.5">Asunto</label>
              <input type="text" id="contact-subject" name="subject" required maxlength="150"
                     value="<?= $prefillSubject ?>"
                     class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                     placeholder="¿Sobre qué trata tu consulta?">
            </div>
            <div>
              <label for="contact-message" class="block text-sm font-medium text-gray-300 mb-1.5">Mensaje</label>
              <textarea id="contact-message" name="message" required rows="5" maxlength="5000"
                        class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm resize-none focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                        placeholder="Describe tu consulta con el mayor detalle posible..."><?= $prefillMessage ?></textarea>
            </div>
            <button type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold
                           shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 transition-all
                           focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-surface-card">
              Enviar mensaje
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/views/partials/footer.php'; ?>
</div>