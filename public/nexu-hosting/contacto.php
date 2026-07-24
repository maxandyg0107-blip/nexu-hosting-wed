<?php
/**
 * NEXU HOSTING - Página de Contacto
 */
require_once __DIR__ . '/config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrFail();

    $name    = sanitize($_POST['name']    ?? '');
    $email   = strtolower(sanitize($_POST['email']   ?? ''));
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    $errors = [];
    if (strlen($name) < 2)           $errors[] = 'El nombre es requerido.';
    if (!isValidEmail($email))       $errors[] = 'Correo inválido.';
    if (strlen($subject) < 3)        $errors[] = 'El asunto es requerido.';
    if (strlen($message) < 10)       $errors[] = 'El mensaje debe tener al menos 10 caracteres.';

    if (empty($errors)) {
        try {
            $stmt = db()->prepare(
                "INSERT INTO support_tickets (user_id, subject, department, priority, status) VALUES (?,?,?,?,?)"
            );
            // Guest contact: null user
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            // Store as a ticket without user if not logged in, or log to error_log
            error_log("[NEXU CONTACT] From: $email | Subject: $subject | Message: $message");

            setFlash('¡Mensaje enviado! Te responderemos en menos de 24 horas.', 'success');
            AuditModel::log('contact.submitted', 'support_tickets', null, $userId, ['email' => $email, 'subject' => $subject]);
        } catch (Throwable $e) {
            error_log('[NEXU CONTACT ERROR] ' . $e->getMessage());
            setFlash('Error al enviar el mensaje. Intenta de nuevo.', 'danger');
        }
    } else {
        setFlash(implode(' ', $errors), 'danger');
    }

    redirect('/contacto.php');
}

$page_title = 'Contacto';
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
          <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Nombre</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? (currentUser()['full_name'] ?? '')) ?>"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? (currentUser()['email'] ?? '')) ?>"
                       class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all">
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1.5">Asunto</label>
              <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>"
                     class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1.5">Mensaje</label>
              <textarea name="message" required rows="5"
                        class="w-full px-4 py-2.5 rounded-xl bg-surface border border-surface-border text-white placeholder-gray-500 text-sm resize-none focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all"
                        placeholder="Describe tu consulta con el mayor detalle posible..."><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-bold
                           shadow-lg shadow-brand-600/30 hover:shadow-brand-600/50 hover:-translate-y-0.5 transition-all">
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
