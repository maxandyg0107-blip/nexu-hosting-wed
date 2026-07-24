<?php
$titulo = 'Panel de Administración';
require_once 'includes/funciones.php';

if (!esAdmin()) {
    setMensaje('No tienes permisos para acceder a esta área.', 'danger');
    redirigir('panel.php');
}

$seccion = $_GET['seccion'] ?? 'inicio';

// Acciones admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['responder_ticket'])) {
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $mensaje = trim($_POST['mensaje'] ?? '');
        if (!empty($mensaje)) {
            $stmt = $pdo->prepare("INSERT INTO ticket_mensajes (ticket_id, usuario_id, mensaje, es_staff) VALUES (?, ?, ?, 1)");
            $stmt->execute([$ticketId, $usuario['id'], $mensaje]);
            
            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'respondido' WHERE id = ?");
            $stmt->execute([$ticketId]);
            setMensaje('Respuesta enviada.', 'success');
        }
        redirigir('admin.php?seccion=ticket&id=' . $ticketId);
    }
    
    if (isset($_POST['actualizar_estado'])) {
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $estado = $_POST['estado'] ?? 'abierto';
        $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $ticketId]);
        setMensaje('Estado actualizado.', 'success');
        redirigir('admin.php?seccion=ticket&id=' . $ticketId);
    }
    
    if (isset($_POST['marcar_pagada'])) {
        $facturaId = intval($_POST['factura_id'] ?? 0);
        $factura = obtenerFacturaCompleta($facturaId);
        
        if ($factura) {
            // Crear transacción completada
            $transaccionId = registrarTransaccion([
                'usuario_id' => $factura['usuario_id'],
                'factura_id' => $factura['id'],
                'pedido_id' => $factura['pedido_id'],
                'metodo_pago' => 'manual',
                'monto' => $factura['total'],
                'moneda' => $factura['moneda'] ?? 'USD',
                'estado' => 'pendiente',
                'notas' => 'Marcado como pagado desde panel de admin',
            ]);
            
            if (procesarPagoCompletado($transaccionId, ['confirmado' => 'admin'])) {
                setMensaje('Factura marcada como pagada, servicio activado/renovado y ganancia registrada.', 'success');
            } else {
                setMensaje('Error al procesar el pago.', 'danger');
            }
        }
        redirigir('admin.php?seccion=facturas');
    }
    
    if (isset($_POST['guardar_noticia'])) {
        $tituloNoticia = trim($_POST['titulo'] ?? '');
        $contenidoNoticia = trim($_POST['contenido'] ?? '');
        $destacada = isset($_POST['destacada']) ? 1 : 0;
        $noticiaId = intval($_POST['noticia_id'] ?? 0);
        
        if (empty($tituloNoticia) || empty($contenidoNoticia)) {
            setMensaje('Completa el título y contenido de la noticia.', 'danger');
        } else {
            if ($noticiaId > 0) {
                $stmt = $pdo->prepare("UPDATE noticias SET titulo = ?, contenido = ?, destacada = ? WHERE id = ?");
                $stmt->execute([$tituloNoticia, $contenidoNoticia, $destacada, $noticiaId]);
                setMensaje('Noticia actualizada.', 'success');
            } else {
                $stmt = $pdo->prepare("INSERT INTO noticias (titulo, contenido, destacada) VALUES (?, ?, ?)");
                $stmt->execute([$tituloNoticia, $contenidoNoticia, $destacada]);
                setMensaje('Noticia creada.', 'success');
            }
        }
        redirigir('admin.php?seccion=noticias');
    }
    
    if (isset($_POST['eliminar_noticia'])) {
        $noticiaId = intval($_POST['noticia_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->execute([$noticiaId]);
        setMensaje('Noticia eliminada.', 'success');
        redirigir('admin.php?seccion=noticias');
    }
    
    if (isset($_POST['aprobar_testimonio'])) {
        $testimonioId = intval($_POST['testimonio_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE testimonios SET aprobado = 1 WHERE id = ?");
        $stmt->execute([$testimonioId]);
        setMensaje('Testimonio aprobado.', 'success');
        redirigir('admin.php?seccion=testimonios');
    }
    
    if (isset($_POST['eliminar_testimonio'])) {
        $testimonioId = intval($_POST['testimonio_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM testimonios WHERE id = ?");
        $stmt->execute([$testimonioId]);
        setMensaje('Testimonio eliminado.', 'success');
        redirigir('admin.php?seccion=testimonios');
    }
    
    if (isset($_POST['guardar_estado'])) {
        $servicioId = intval($_POST['servicio_id'] ?? 0);
        $estadoServicio = $_POST['estado'] ?? 'operacional';
        $uptime = floatval($_POST['uptime'] ?? 99.99);
        
        $stmt = $pdo->prepare("UPDATE servicios_estado SET estado = ?, uptime = ? WHERE id = ?");
        $stmt->execute([$estadoServicio, $uptime, $servicioId]);
        setMensaje('Estado del servicio actualizado.', 'success');
        redirigir('admin.php?seccion=estado');
    }
    
    if (isset($_POST['confirmar_pago'])) {
        $transaccionId = intval($_POST['transaccion_id'] ?? 0);
        if (procesarPagoCompletado($transaccionId, ['confirmado_por' => $_SESSION['usuario_id'], 'metodo' => 'manual'])) {
            setMensaje('Pago confirmado correctamente. Factura pagada y servicio renovado.', 'success');
        } else {
            setMensaje('No se pudo confirmar el pago.', 'danger');
        }
        redirigir('admin.php?seccion=transacciones');
    }
    
    if (isset($_POST['rechazar_pago'])) {
        $transaccionId = intval($_POST['transaccion_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE transacciones SET estado = 'cancelado', actualizado_en = NOW() WHERE id = ?");
        $stmt->execute([$transaccionId]);
        setMensaje('Transacción cancelada.', 'warning');
        redirigir('admin.php?seccion=transacciones');
    }
    
    if (isset($_POST['guardar_config_pagos'])) {
        foreach ($_POST['pasarelas'] as $pasarela => $datos) {
            $stmt = $pdo->prepare("INSERT INTO config_pagos (pasarela, activo, modo_sandbox, public_key, secret_key, webhook_secret) 
                                   VALUES (?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE 
                                   activo = VALUES(activo), 
                                   modo_sandbox = VALUES(modo_sandbox), 
                                   public_key = VALUES(public_key), 
                                   secret_key = VALUES(secret_key), 
                                   webhook_secret = VALUES(webhook_secret)");
            $stmt->execute([
                $pasarela,
                isset($datos['activo']) ? 1 : 0,
                isset($datos['sandbox']) ? 1 : 0,
                $datos['public_key'] ?? null,
                $datos['secret_key'] ?? null,
                $datos['webhook_secret'] ?? null,
            ]);
        }
        setMensaje('Configuración de pagos guardada.', 'success');
        redirigir('admin.php?seccion=config_pagos');
    }
    
    if (isset($_POST['guardar_config_general'])) {
        foreach ($_POST['config'] as $clave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO config_general (clave, valor) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $stmt->execute([$clave, $valor]);
        }
        setMensaje('Configuración general guardada.', 'success');
        redirigir('admin.php?seccion=config_pagos');
    }
}

require_once 'includes/header.php';

// Estadísticas
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$totalTicketsAbiertos = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado != 'cerrado'")->fetchColumn();
$totalFacturasPendientes = $pdo->query("SELECT COUNT(*) FROM facturas WHERE estado = 'pendiente'")->fetchColumn();
?>

<section class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <h1>Panel de Administración</h1>
            <p>Gestión completa de Nexu Hosting</p>
        </div>
        
        <?php echo mostrarMensaje(); ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Usuarios registrados</h4>
                <strong><?php echo $totalUsuarios; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Total pedidos</h4>
                <strong><?php echo $totalPedidos; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Tickets abiertos</h4>
                <strong><?php echo $totalTicketsAbiertos; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Facturas pendientes</h4>
                <strong><?php echo $totalFacturasPendientes; ?></strong>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="admin.php" class="<?php echo $seccion === 'inicio' ? 'active' : ''; ?>">🏠 Inicio</a></li>
                    <li><a href="admin.php?seccion=finanzas" class="<?php echo $seccion === 'finanzas' ? 'active' : ''; ?>">💰 Finanzas</a></li>
                    <li><a href="admin.php?seccion=usuarios" class="<?php echo $seccion === 'usuarios' ? 'active' : ''; ?>">👥 Usuarios</a></li>
                    <li><a href="admin.php?seccion=pedidos" class="<?php echo $seccion === 'pedidos' ? 'active' : ''; ?>">📦 Pedidos</a></li>
                    <li><a href="admin.php?seccion=facturas" class="<?php echo $seccion === 'facturas' ? 'active' : ''; ?>">📄 Facturas</a></li>
                    <li><a href="admin.php?seccion=transacciones" class="<?php echo $seccion === 'transacciones' ? 'active' : ''; ?>">💳 Transacciones</a></li>
                    <li><a href="admin.php?seccion=tickets" class="<?php echo $seccion === 'tickets' ? 'active' : ''; ?>">🎫 Tickets</a></li>
                    <li><a href="admin.php?seccion=contactos" class="<?php echo $seccion === 'contactos' ? 'active' : ''; ?>">📨 Contactos</a></li>
                    <li><a href="admin.php?seccion=noticias" class="<?php echo $seccion === 'noticias' ? 'active' : ''; ?>">📰 Noticias</a></li>
                    <li><a href="admin.php?seccion=testimonios" class="<?php echo $seccion === 'testimonios' ? 'active' : ''; ?>">⭐ Testimonios</a></li>
                    <li><a href="admin.php?seccion=estado" class="<?php echo $seccion === 'estado' ? 'active' : ''; ?>">📊 Estado</a></li>
                    <li><a href="admin.php?seccion=config_pagos" class="<?php echo $seccion === 'config_pagos' ? 'active' : ''; ?>">⚙️ Pagos</a></li>
                    <li><a href="panel.php">← Panel cliente</a></li>
                </ul>
            </aside>
            
            <main class="dashboard-content">
                <?php if ($seccion === 'inicio'): ?>
                    <h2>Bienvenido, Administrador</h2>
                    <p class="text-muted mb-3">Desde aquí puedes gestionar usuarios, pedidos, facturas y tickets de soporte.</p>
                    
                    <div class="grid grid-2">
                        <div class="card">
                            <h3>📦 Últimos pedidos</h3>
                            <?php
                            $pedidos = $pdo->query("SELECT pe.*, u.nombre as usuario_nombre, pl.nombre as plan_nombre FROM pedidos pe JOIN usuarios u ON pe.usuario_id = u.id JOIN planes pl ON pe.plan_id = pl.id ORDER BY pe.fecha_inicio DESC LIMIT 5")->fetchAll();
                            ?>
                            <table class="table">
                                <thead>
                                    <tr><th>Usuario</th><th>Plan</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td><?php echo e($p['usuario_nombre']); ?></td>
                                        <td><?php echo e($p['plan_nombre']); ?></td>
                                        <td><?php echo badgeEstado($p['estado']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card">
                            <h3>🎫 Tickets recientes</h3>
                            <?php
                            $tickets = $pdo->query("SELECT t.*, u.nombre as usuario_nombre FROM tickets t JOIN usuarios u ON t.usuario_id = u.id ORDER BY t.actualizado_en DESC LIMIT 5")->fetchAll();
                            ?>
                            <table class="table">
                                <thead>
                                    <tr><th>Usuario</th><th>Asunto</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t): ?>
                                    <tr>
                                        <td><?php echo e($t['usuario_nombre']); ?></td>
                                        <td><?php echo e($t['asunto']); ?></td>
                                        <td><?php echo badgeEstado($t['estado']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <?php elseif ($seccion === 'usuarios'): ?>
                    <h2>Usuarios registrados</h2>
                    <?php
                    $usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY creado_en DESC")->fetchAll();
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo e($u['nombre']); ?></td>
                                <td><?php echo e($u['email']); ?></td>
                                <td><?php echo ucfirst($u['rol']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($u['creado_en'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'pedidos'): ?>
                    <h2>Todos los pedidos</h2>
                    <?php
                    $pedidos = $pdo->query("SELECT pe.*, u.nombre as usuario_nombre, pl.nombre as plan_nombre FROM pedidos pe JOIN usuarios u ON pe.usuario_id = u.id JOIN planes pl ON pe.plan_id = pl.id ORDER BY pe.fecha_inicio DESC")->fetchAll();
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Usuario</th><th>Plan</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td>#<?php echo $p['id']; ?></td>
                                <td><?php echo e($p['usuario_nombre']); ?></td>
                                <td><?php echo e($p['plan_nombre']); ?></td>
                                <td><?php echo formatoPrecio($p['total']); ?></td>
                                <td><?php echo badgeEstado($p['estado']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['fecha_inicio'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'facturas'): ?>
                    <h2>Facturas</h2>
                    <?php
                    $facturas = $pdo->query("SELECT f.*, u.nombre as usuario_nombre FROM facturas f JOIN usuarios u ON f.usuario_id = u.id ORDER BY f.creado_en DESC")->fetchAll();
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>Número</th><th>Usuario</th><th>Concepto</th><th>Total</th><th>Estado</th><th>Vencimiento</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $f): ?>
                            <tr>
                                <td><?php echo e($f['numero']); ?></td>
                                <td><?php echo e($f['usuario_nombre']); ?></td>
                                <td><?php echo e($f['concepto']); ?></td>
                                <td><?php echo formatoPrecio($f['total']); ?></td>
                                <td><?php echo badgeEstado($f['estado']); ?></td>
                                <td><?php echo $f['vencimiento'] ? date('d/m/Y', strtotime($f['vencimiento'])) : '—'; ?></td>
                                <td>
                                    <a href="factura.php?id=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Ver</a>
                                    <?php if ($f['estado'] === 'pendiente'): ?>
                                    <form action="admin.php?seccion=facturas" method="POST" style="display:inline;">
                                        <input type="hidden" name="factura_id" value="<?php echo $f['id']; ?>">
                                        <input type="hidden" name="marcar_pagada" value="1">
                                        <button type="submit" class="btn btn-sm btn-success">Marcar pagada</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'tickets'): ?>
                    <h2>Tickets de soporte</h2>
                    <?php
                    $tickets = $pdo->query("SELECT t.*, u.nombre as usuario_nombre FROM tickets t JOIN usuarios u ON t.usuario_id = u.id ORDER BY t.actualizado_en DESC")->fetchAll();
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Usuario</th><th>Asunto</th><th>Prioridad</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td><?php echo e($t['usuario_nombre']); ?></td>
                                <td><?php echo e($t['asunto']); ?></td>
                                <td><?php echo ucfirst($t['prioridad']); ?></td>
                                <td><?php echo badgeEstado($t['estado']); ?></td>
                                <td><a href="admin.php?seccion=ticket&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline">Responder</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'ticket'): ?>
                    <?php
                    $ticketId = intval($_GET['id'] ?? 0);
                    $stmt = $pdo->prepare("SELECT t.*, u.nombre as usuario_nombre FROM tickets t JOIN usuarios u ON t.usuario_id = u.id WHERE t.id = ? LIMIT 1");
                    $stmt->execute([$ticketId]);
                    $ticket = $stmt->fetch();
                    
                    if ($ticket):
                        $stmt = $pdo->prepare("SELECT m.*, u.nombre, u.rol FROM ticket_mensajes m JOIN usuarios u ON m.usuario_id = u.id WHERE m.ticket_id = ? ORDER BY m.creado_en ASC");
                        $stmt->execute([$ticketId]);
                        $mensajes = $stmt->fetchAll();
                    ?>
                    <h2>Ticket #<?php echo $ticket['id']; ?></h2>
                    <p class="text-muted"><?php echo e($ticket['asunto']); ?> - <?php echo e($ticket['usuario_nombre']); ?> - <?php echo badgeEstado($ticket['estado']); ?></p>
                    
                    <div class="card mb-3" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($mensajes as $msg): ?>
                        <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
                            <div class="flex justify-between" style="margin-bottom: 0.5rem;">
                                <strong><?php echo e($msg['nombre']); ?> <?php echo $msg['rol'] === 'admin' ? '<span class="badge badge-info">STAFF</span>' : ''; ?></strong>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($msg['creado_en'])); ?></small>
                            </div>
                            <p style="white-space: pre-wrap;"><?php echo e($msg['mensaje']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form action="admin.php?seccion=ticket&id=<?php echo $ticketId; ?>" method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                        <div class="form-group">
                            <label>Responder como staff</label>
                            <textarea name="mensaje" class="form-control" required></textarea>
                        </div>
                        <button type="submit" name="responder_ticket" class="btn btn-primary">Enviar respuesta</button>
                    </form>
                    
                    <form action="admin.php?seccion=ticket&id=<?php echo $ticketId; ?>" method="POST" class="mt-2">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                        <div class="form-group">
                            <label>Cambiar estado</label>
                            <select name="estado" class="form-control">
                                <option value="abierto" <?php echo $ticket['estado'] === 'abierto' ? 'selected' : ''; ?>>Abierto</option>
                                <option value="respondido" <?php echo $ticket['estado'] === 'respondido' ? 'selected' : ''; ?>>Respondido</option>
                                <option value="cerrado" <?php echo $ticket['estado'] === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                            </select>
                        </div>
                        <button type="submit" name="actualizar_estado" class="btn btn-secondary">Actualizar estado</button>
                    </form>
                    
                    <?php else: ?>
                        <div class="alert alert-danger">Ticket no encontrado.</div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'contactos'): ?>
                    <h2>Mensajes de contacto</h2>
                    <?php
                    $contactos = $pdo->query("SELECT * FROM contactos ORDER BY creado_en DESC")->fetchAll();
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>Nombre</th><th>Email</th><th>Asunto</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contactos as $c): ?>
                            <tr>
                                <td><?php echo e($c['nombre']); ?></td>
                                <td><?php echo e($c['email']); ?></td>
                                <td><?php echo e($c['asunto']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($c['creado_en'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'finanzas'): ?>
                    <h2>Dashboard financiero</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Ganancias hoy</h4>
                            <strong><?php echo formatoPrecio(obtenerEstadisticasFinancieras('hoy')['total']); ?></strong>
                        </div>
                        <div class="stat-card">
                            <h4>Ganancias este mes</h4>
                            <strong><?php echo formatoPrecio(obtenerEstadisticasFinancieras('mes')['total']); ?></strong>
                        </div>
                        <div class="stat-card">
                            <h4>Ganancias este año</h4>
                            <strong><?php echo formatoPrecio(obtenerEstadisticasFinancieras('anio')['total']); ?></strong>
                        </div>
                        <div class="stat-card">
                            <h4>Total transacciones</h4>
                            <strong><?php echo obtenerEstadisticasFinancieras('todos')['cantidad']; ?></strong>
                        </div>
                    </div>
                    
                    <div class="grid grid-2">
                        <div class="card">
                            <h3>Ganancias por mes</h3>
                            <table class="table">
                                <thead>
                                    <tr><th>Mes</th><th>Transacciones</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach (obtenerGananciasPorMes() as $g): ?>
                                    <tr>
                                        <td><?php echo $g['mes']; ?></td>
                                        <td><?php echo $g['cantidad']; ?></td>
                                        <td><?php echo formatoPrecio($g['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card">
                            <h3>Métodos de pago más usados</h3>
                            <table class="table">
                                <thead>
                                    <tr><th>Método</th><th>Transacciones</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach (obtenerMetodosPagoPopulares() as $m): ?>
                                    <tr>
                                        <td><?php echo nombrePasarelaAmigable($m['metodo_pago']); ?></td>
                                        <td><?php echo $m['cantidad']; ?></td>
                                        <td><?php echo formatoPrecio($m['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                <?php elseif ($seccion === 'transacciones'): ?>
                    <h2>Transacciones de pago</h2>
                    <?php
                    $transacciones = obtenerTransacciones(100);
                    if (count($transacciones) > 0):
                    ?>
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Usuario</th><th>Factura</th><th>Método</th><th>Monto</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transacciones as $t): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td><?php echo e($t['usuario_nombre']); ?></td>
                                <td><?php echo e($t['factura_numero'] ?: '—'); ?></td>
                                <td><?php echo nombrePasarelaAmigable($t['metodo_pago']); ?></td>
                                <td><?php echo formatoPrecio($t['monto']); ?></td>
                                <td><?php echo badgeEstado($t['estado']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['creado_en'])); ?></td>
                                <td>
                                    <?php if ($t['estado'] === 'pendiente'): ?>
                                    <form action="admin.php?seccion=transacciones" method="POST" style="display:inline;">
                                        <input type="hidden" name="transaccion_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="confirmar_pago" class="btn btn-sm btn-success">Confirmar</button>
                                    </form>
                                    <form action="admin.php?seccion=transacciones" method="POST" style="display:inline;">
                                        <input type="hidden" name="transaccion_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="rechazar_pago" class="btn btn-sm btn-danger">Rechazar</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">No hay transacciones registradas.</div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'config_pagos'): ?>
                    <h2>Configuración de pagos</h2>
                    
                    <div class="card mb-3">
                        <h3>Configuración general</h3>
                        <form action="admin.php?seccion=config_pagos" method="POST">
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label>Nombre empresa</label>
                                    <input type="text" name="config[nombre_empresa]" class="form-control" value="<?php echo e(obtenerConfigGeneral('nombre_empresa')); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email de facturación</label>
                                    <input type="email" name="config[email_facturacion]" class="form-control" value="<?php echo e(obtenerConfigGeneral('email_facturacion')); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="config[telefono_empresa]" class="form-control" value="<?php echo e(obtenerConfigGeneral('telefono_empresa')); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="config[direccion_empresa]" class="form-control" value="<?php echo e(obtenerConfigGeneral('direccion_empresa')); ?>">
                                </div>
                            </div>
                            <button type="submit" name="guardar_config_general" class="btn btn-primary">Guardar configuración general</button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h3>Pasarelas de pago</h3>
                        <form action="admin.php?seccion=config_pagos" method="POST">
                            <?php
                            $pasarelasConfig = ['stripe', 'paypal', 'mercadopago', 'transferencia'];
                            foreach ($pasarelasConfig as $pasarela):
                                $config = obtenerConfigPago($pasarela);
                            ?>
                            <div style="background: var(--color-bg-light); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; border: 1px solid var(--color-border);">
                                <h4 style="margin-bottom: 1rem;"><?php echo nombrePasarelaAmigable($pasarela); ?></h4>
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="pasarelas[<?php echo $pasarela; ?>][activo]" value="1" <?php echo ($config['activo'] ?? 0) ? 'checked' : ''; ?>> Activo
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="pasarelas[<?php echo $pasarela; ?>][sandbox]" value="1" <?php echo ($config['modo_sandbox'] ?? 1) ? 'checked' : ''; ?>> Modo sandbox/pruebas
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label>Public Key / Client ID</label>
                                        <input type="text" name="pasarelas[<?php echo $pasarela; ?>][public_key]" class="form-control" value="<?php echo e($config['public_key'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Secret Key / Secret</label>
                                        <input type="password" name="pasarelas[<?php echo $pasarela; ?>][secret_key]" class="form-control" value="<?php echo e($config['secret_key'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Webhook Secret (opcional)</label>
                                        <input type="text" name="pasarelas[<?php echo $pasarela; ?>][webhook_secret]" class="form-control" value="<?php echo e($config['webhook_secret'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <button type="submit" name="guardar_config_pagos" class="btn btn-primary">Guardar pasarelas</button>
                        </form>
                    </div>
                
                <?php elseif ($seccion === 'noticias'): ?>
                    <h2>Gestión de noticias</h2>
                    
                    <div class="card mb-3">
                        <h3>Nueva / Editar noticia</h3>
                        <?php
                        $noticiaEdit = null;
                        if (isset($_GET['editar'])) {
                            $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ? LIMIT 1");
                            $stmt->execute([intval($_GET['editar'])]);
                            $noticiaEdit = $stmt->fetch();
                        }
                        ?>
                        <form action="admin.php?seccion=noticias" method="POST">
                            <input type="hidden" name="noticia_id" value="<?php echo $noticiaEdit['id'] ?? 0; ?>">
                            <div class="form-group">
                                <label>Título</label>
                                <input type="text" name="titulo" class="form-control" value="<?php echo e($noticiaEdit['titulo'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Contenido</label>
                                <textarea name="contenido" class="form-control" required><?php echo e($noticiaEdit['contenido'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="destacada" value="1" <?php echo ($noticiaEdit['destacada'] ?? 0) ? 'checked' : ''; ?>> Noticia destacada
                                </label>
                            </div>
                            <button type="submit" name="guardar_noticia" class="btn btn-primary">Guardar noticia</button>
                        </form>
                    </div>
                    
                    <h3>Noticias publicadas</h3>
                    <table class="table">
                        <thead>
                            <tr><th>Título</th><th>Destacada</th><th>Fecha</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pdo->query("SELECT * FROM noticias ORDER BY creado_en DESC")->fetchAll() as $n): ?>
                            <tr>
                                <td><?php echo e($n['titulo']); ?></td>
                                <td><?php echo $n['destacada'] ? 'Sí' : 'No'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($n['creado_en'])); ?></td>
                                <td>
                                    <a href="admin.php?seccion=noticias&editar=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline">Editar</a>
                                    <form action="admin.php?seccion=noticias" method="POST" style="display:inline;">
                                        <input type="hidden" name="noticia_id" value="<?php echo $n['id']; ?>">
                                        <button type="submit" name="eliminar_noticia" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta noticia?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'testimonios'): ?>
                    <h2>Testimonios</h2>
                    <table class="table">
                        <thead>
                            <tr><th>Nombre</th><th>País</th><th>Estrellas</th><th>Comentario</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pdo->query("SELECT * FROM testimonios ORDER BY creado_en DESC")->fetchAll() as $t): ?>
                            <tr>
                                <td><?php echo e($t['nombre']); ?></td>
                                <td><?php echo e($t['pais']); ?></td>
                                <td><?php echo estrellasHtml($t['estrellas']); ?></td>
                                <td><?php echo e(substr($t['comentario'], 0, 80)) . '...'; ?></td>
                                <td><?php echo $t['aprobado'] ? '<span class="badge badge-success">Aprobado</span>' : '<span class="badge badge-warning">Pendiente</span>'; ?></td>
                                <td>
                                    <?php if (!$t['aprobado']): ?>
                                    <form action="admin.php?seccion=testimonios" method="POST" style="display:inline;">
                                        <input type="hidden" name="testimonio_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="aprobar_testimonio" class="btn btn-sm btn-success">Aprobar</button>
                                    </form>
                                    <?php endif; ?>
                                    <form action="admin.php?seccion=testimonios" method="POST" style="display:inline;">
                                        <input type="hidden" name="testimonio_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="eliminar_testimonio" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este testimonio?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($seccion === 'estado'): ?>
                    <h2>Estado de servicios</h2>
                    <table class="table">
                        <thead>
                            <tr><th>Servicio</th><th>Ubicación</th><th>Estado actual</th><th>Uptime</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pdo->query("SELECT * FROM servicios_estado ORDER BY nombre ASC")->fetchAll() as $s): ?>
                            <tr>
                                <td><?php echo e($s['nombre']); ?></td>
                                <td><?php echo e($s['ubicacion'] ?: '—'); ?></td>
                                <td><?php echo badgeEstado($s['estado']); ?></td>
                                <td><?php echo $s['uptime']; ?>%</td>
                                <td>
                                    <form action="admin.php?seccion=estado" method="POST" style="display:flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="servicio_id" value="<?php echo $s['id']; ?>">
                                        <select name="estado" class="form-control" style="width: auto;">
                                            <option value="operacional" <?php echo $s['estado'] === 'operacional' ? 'selected' : ''; ?>>Operacional</option>
                                            <option value="mantenimiento" <?php echo $s['estado'] === 'mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                                            <option value="intermitente" <?php echo $s['estado'] === 'intermitente' ? 'selected' : ''; ?>>Intermitente</option>
                                            <option value="caido" <?php echo $s['estado'] === 'caido' ? 'selected' : ''; ?>>Caído</option>
                                        </select>
                                        <input type="number" name="uptime" step="0.01" min="0" max="100" class="form-control" value="<?php echo $s['uptime']; ?>" style="width: 80px;">
                                        <button type="submit" name="guardar_estado" class="btn btn-sm btn-primary">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
