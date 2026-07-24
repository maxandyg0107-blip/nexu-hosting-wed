<?php
/**
 * Nexu Hosting - Funciones comunes
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pagos.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

// Verificar si el usuario es admin
function esAdmin() {
    return estaLogueado() && isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// Redireccionar
function redirigir($url) {
    header("Location: " . $url);
    exit;
}

// Mostrar mensajes flash
function mostrarMensaje() {
    if (isset($_SESSION['mensaje'])) {
        $tipo = $_SESSION['mensaje_tipo'] ?? 'info';
        $html = '<div class="alert alert-' . $tipo . '">' . htmlspecialchars($_SESSION['mensaje']) . '</div>';
        unset($_SESSION['mensaje']);
        unset($_SESSION['mensaje_tipo']);
        return $html;
    }
    return '';
}

function setMensaje($mensaje, $tipo = 'info') {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['mensaje_tipo'] = $tipo;
}

// Escapar HTML
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Formatear precio
function formatoPrecio($precio) {
    return '$' . number_format($precio, 2);
}

// Obtener usuario actual
function usuarioActual() {
    global $pdo;
    if (!estaLogueado()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

// Obtener categorías
function obtenerCategorias($soloActivas = true) {
    global $pdo;
    $sql = "SELECT * FROM categorias";
    if ($soloActivas) $sql .= " WHERE activo = 1";
    $sql .= " ORDER BY orden ASC";
    return $pdo->query($sql)->fetchAll();
}

// Obtener planes por categoría
function obtenerPlanes($categoriaSlug = null, $soloActivos = true) {
    global $pdo;
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
            FROM planes p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE 1=1";
    $params = [];
    if ($categoriaSlug) {
        $sql .= " AND c.slug = ?";
        $params[] = $categoriaSlug;
    }
    if ($soloActivos) {
        $sql .= " AND p.activo = 1";
    }
    $sql .= " ORDER BY p.orden ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Obtener un plan por slug
function obtenerPlan($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug 
                           FROM planes p JOIN categorias c ON p.categoria_id = c.id 
                           WHERE p.slug = ? AND p.activo = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Contar pedidos del usuario
function contarPedidos($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchColumn();
}

// Contar tickets abiertos del usuario
function contarTicketsAbiertos($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE usuario_id = ? AND estado != 'cerrado'");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchColumn();
}

// Contar facturas pendientes
function contarFacturasPendientes($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM facturas WHERE usuario_id = ? AND estado = 'pendiente'");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchColumn();
}

// Obtener pedidos del usuario
function obtenerPedidos($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT pe.*, pl.nombre as plan_nombre, c.nombre as categoria_nombre 
                           FROM pedidos pe 
                           JOIN planes pl ON pe.plan_id = pl.id 
                           JOIN categorias c ON pl.categoria_id = c.id 
                           WHERE pe.usuario_id = ? 
                           ORDER BY pe.fecha_inicio DESC");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

// Obtener tickets del usuario
function obtenerTickets($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE usuario_id = ? ORDER BY actualizado_en DESC");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

// Obtener facturas del usuario
function obtenerFacturas($usuarioId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM facturas WHERE usuario_id = ? ORDER BY creado_en DESC");
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll();
}

// Obtener una factura por ID con datos de usuario
function obtenerFacturaCompleta($facturaId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT f.*, u.nombre as usuario_nombre, u.email as usuario_email, u.telefono, u.pais, u.direccion 
                           FROM facturas f JOIN usuarios u ON f.usuario_id = u.id 
                           WHERE f.id = ? LIMIT 1");
    $stmt->execute([$facturaId]);
    return $stmt->fetch();
}

// Obtener testimonios aprobados
function obtenerTestimonios($limite = null) {
    global $pdo;
    $sql = "SELECT * FROM testimonios WHERE aprobado = 1 ORDER BY creado_en DESC";
    if ($limite) $sql .= " LIMIT " . intval($limite);
    return $pdo->query($sql)->fetchAll();
}

// Obtener estado de servicios
function obtenerEstadoServicios() {
    global $pdo;
    return $pdo->query("SELECT * FROM servicios_estado ORDER BY tipo DESC, nombre ASC")->fetchAll();
}

// Obtener noticias
function obtenerNoticias($destacadas = false, $limite = null) {
    global $pdo;
    $sql = "SELECT * FROM noticias WHERE 1=1";
    if ($destacadas) $sql .= " AND destacada = 1";
    $sql .= " ORDER BY creado_en DESC";
    if ($limite) $sql .= " LIMIT " . intval($limite);
    return $pdo->query($sql)->fetchAll();
}

function obtenerNoticia($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Estado badge
function badgeEstado($estado) {
    $clases = [
        'pendiente' => 'badge-warning',
        'activo' => 'badge-success',
        'suspendido' => 'badge-danger',
        'cancelado' => 'badge-secondary',
        'pagada' => 'badge-success',
        'vencida' => 'badge-danger',
        'cerrado' => 'badge-secondary',
        'abierto' => 'badge-success',
        'respondido' => 'badge-info',
        'operacional' => 'badge-success',
        'mantenimiento' => 'badge-warning',
        'intermitente' => 'badge-warning',
        'caido' => 'badge-danger',
    ];
    return '<span class="badge ' . ($clases[$estado] ?? 'badge-secondary') . '">' . ucfirst($estado) . '</span>';
}

// Generar estrellas HTML
function estrellasHtml($cantidad) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $cantidad ? '★' : '☆';
    }
    return '<span style="color: var(--color-accent);">' . $html . '</span>';
}
