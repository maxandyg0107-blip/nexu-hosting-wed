<?php
$titulo = 'Términos de Servicio';
require_once 'includes/header.php';
?>

<section class="hero" style="padding: 140px 0 80px;">
    <div class="container">
        <span class="hero-badge">📋</span>
        <h1>Términos de <span>Servicio</span></h1>
        <p class="text-muted">Última actualización: <?php echo date('F Y'); ?></p>
    </div>
</section>

<section class="section" style="padding-top: 40px;">
    <div class="container">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <h2>1. Aceptación de los términos</h2>
            <p class="text-muted mb-3">Al contratar los servicios de Nexu Hosting, aceptas los siguientes términos y condiciones. Si no estás de acuerdo, no debes utilizar nuestros servicios.</p>
            
            <h2>2. Descripción del servicio</h2>
            <p class="text-muted mb-3">Nexu Hosting ofrece servidores de juegos y servicios de alojamiento web relacionados. Proporcionamos acceso a panel de control, soporte técnico y recursos dedicados según el plan contratado.</p>
            
            <h2>3. Registro de cuenta</h2>
            <p class="text-muted mb-3">Para utilizar nuestros servicios debes proporcionar información veraz y actualizada. Eres responsable de mantener la confidencialidad de tu contraseña y de todas las actividades que ocurran en tu cuenta.</p>
            
            <h2>4. Uso aceptable</h2>
            <p class="text-muted mb-3">No está permitido utilizar nuestros servicios para:</p>
            <ul style="margin-left: 1.5rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
                <li>Actividades ilegales o fraudulentas</li>
                <li>Distribución de malware o virus</li>
                <li>Ataques DDoS o hacking</li>
                <li>Spam o phishing</li>
                <li>Contenido que viole derechos de autor</li>
                <li>Minería de criptomonedas sin autorización</li>
            </ul>
            
            <h2>5. Pagos y renovaciones</h2>
            <p class="text-muted mb-3">Los servicios se facturan según el ciclo elegido (mensual, trimestral o anual). La falta de pago antes de la fecha de vencimiento puede resultar en la suspensión o eliminación del servicio.</p>
            
            <h2>6. Política de reembolso</h2>
            <p class="text-muted mb-3">Ofrecemos garantía de reembolso de 24 horas desde la contratación del primer servicio. Después de este período, no se realizan reembolsos excepto circunstancias especiales evaluadas por el equipo.</p>
            
            <h2>7. Suspensión y terminación</h2>
            <p class="text-muted mb-3">Nos reservamos el derecho de suspender o terminar cualquier servicio que viole estos términos o que genere abuso en la infraestructura compartida.</p>
            
            <h2>8. Limitación de responsabilidad</h2>
            <p class="text-muted mb-3">Nexu Hosting no se hace responsable por pérdida de datos, ingresos o daños indirectos derivados del uso del servicio. Recomendamos mantener copias de seguridad propias.</p>
            
            <h2>9. Modificaciones</h2>
            <p class="text-muted mb-3">Podemos modificar estos términos en cualquier momento. Los cambios entrarán en vigor al publicarse en el sitio web.</p>
            
            <h2>10. Contacto</h2>
            <p class="text-muted">Para cualquier duda sobre estos términos, contacta con nosotros a través de soporte@nexuhosting.com.</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
