<?php
$titulo = 'Preguntas Frecuentes';
require_once 'includes/header.php';

$preguntas = [
    [
        'pregunta' => '¿Cuánto tarda en activarse mi servidor?',
        'respuesta' => 'Una vez confirmado el pago, tu servidor se activa automáticamente en menos de 2 minutos. Recibirás un correo con los datos de acceso al panel de control.'
    ],
    [
        'pregunta' => '¿Puedo instalar mods o plugins?',
        'respuesta' => 'Sí, en todos nuestros planes de Minecraft y otros juegos puedes instalar mods, plugins, datapacks y configuraciones personalizadas sin límite.'
    ],
    [
        'pregunta' => '¿Qué panel de control utilizan?',
        'respuesta' => 'Utilizamos Pterodactyl, el panel más popular y potente para servidores de juegos. Te permite gestionar archivos, consola, usuarios, backups y más.'
    ],
    [
        'pregunta' => '¿Tienen protección DDoS?',
        'respuesta' => 'Sí, todos nuestros servidores incluyen protección DDoS de nivel empresarial para mantener tu servidor online y protegido contra ataques.'
    ],
    [
        'pregunta' => '¿Puedo cambiar de plan más adelante?',
        'respuesta' => 'Por supuesto. Puedes actualizar o degradar tu plan en cualquier momento desde el panel de cliente. Los cambios se aplican de inmediato.'
    ],
    [
        'pregunta' => '¿Cómo funciona el soporte técnico?',
        'respuesta' => 'Ofrecemos soporte 24/7 a través de tickets de soporte. Nuestro equipo técnico responde en menos de 1 hora en horario extendido.'
    ],
    [
        'pregunta' => '¿Qué métodos de pago aceptan?',
        'respuesta' => 'Aceptamos más de 150 métodos de pago incluyendo tarjetas de crédito/débito, PayPal, transferencias bancarias, MercadoPago, Nequi, PSE y criptomonedas.'
    ],
    [
        'pregunta' => '¿Puedo obtener una IP dedicada?',
        'respuesta' => 'Sí, las IPs dedicadas están disponibles en planes seleccionados o como complemento adicional. Puedes solicitarla al contratar o más adelante.'
    ],
    [
        'pregunta' => '¿Hay garantía de reembolso?',
        'respuesta' => 'Sí, ofrecemos garantía de reembolso de 24 horas si no estás satisfecho con el servicio. Aplica para el primer mes de contratación.'
    ],
    [
        'pregunta' => '¿Dónde están ubicados sus servidores?',
        'respuesta' => 'Contamos con ubicaciones estratégicas en América del Norte, Europa y Sudamérica para ofrecer la mejor latencia posible a tu región.'
    ],
];
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container text-center">
        <span class="hero-badge">❓</span>
        <h1>Preguntas <span>Frecuentes</span></h1>
        <p>Encuentra respuestas a preguntas comunes sobre nuestros servicios de alojamiento de juegos y sus características.</p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="faq-list">
            <?php foreach ($preguntas as $index => $item): ?>
            <div class="faq-item <?php echo $index === 0 ? 'active' : ''; ?>">
                <button class="faq-question"><?php echo e($item['pregunta']); ?></button>
                <div class="faq-answer">
                    <p><?php echo e($item['respuesta']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card text-center mt-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
            <div class="card-icon" style="margin-left: auto; margin-right: auto;">💬</div>
            <h3>¿No encontraste tu respuesta?</h3>
            <p class="mb-3">Nuestro equipo de soporte está listo para ayudarte con cualquier duda.</p>
            <a href="contacto.php" class="btn btn-primary">Contactar soporte</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
