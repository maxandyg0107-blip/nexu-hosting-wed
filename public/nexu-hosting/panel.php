<?php
$titulo = 'Panel de Cliente';
require_once 'includes/funciones.php';

if (!estaLogueado()) {
    setMensaje('Debes iniciar sesión para acceder al panel.', 'danger');
    redirigir('login.php');
}

$usuario = usuarioActual();
$seccion = $_GET['seccion'] ?? 'inicio';

// Procesar creación de ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $seccion === 'tickets' && isset($_POST['nuevo_ticket'])) {
    $asunto = trim($_POST['asunto'] ?? '');
    $departamento = $_POST['departamento'] ?? 'soporte';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (empty($asunto) || empty($mensaje)) {
        setMensaje('Completa todos los campos del ticket.', 'danger');
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tickets (usuario_id, asunto, departamento, prioridad) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario['id'], $asunto, $departamento, $prioridad]);
            $ticketId = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO ticket_mensajes (ticket_id, usuario_id, mensaje) VALUES (?, ?, ?)");
            $stmt->execute([$ticketId, $usuario['id'], $mensaje]);
            
            $pdo->commit();
            setMensaje('Ticket creado correctamente.', 'success');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setMensaje('Error al crear el ticket.', 'danger');
        }
    }
    redirigir('panel.php?seccion=tickets');
}

// Procesar respuesta de ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $seccion === 'ver_ticket' && isset($_POST['respuesta'])) {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    // Verificar que el ticket pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND usuario_id = ? LIMIT 1");
    $stmt->execute([$ticketId, $usuario['id']]);
    $ticket = $stmt->fetch();
    
    if ($ticket && !empty($mensaje)) {
        $stmt = $pdo->prepare("INSERT INTO ticket_mensajes (ticket_id, usuario_id, mensaje) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $usuario['id'], $mensaje]);
        
        $stmt = $pdo->prepare("UPDATE tickets SET estado = 'respondido' WHERE id = ?");
        $stmt->execute([$ticketId]);
        
        setMensaje('Respuesta enviada.', 'success');
    }
    redirigir('panel.php?seccion=ver_ticket&id=' . $ticketId);
}

require_once 'includes/header.php';

$totalPedidos = contarPedidos($usuario['id']);
$ticketsAbiertos = contarTicketsAbiertos($usuario['id']);
$facturasPendientes = contarFacturasPendientes($usuario['id']);
?>

<section class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <h1>Hola, <?php echo e($usuario['nombre']); ?> 👋</h1>
            <p>Bienvenido a tu panel de control de Nexu Hosting</p>
        </div>
        
        <?php echo mostrarMensaje(); ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Servidores activos</h4>
                <strong><?php echo $totalPedidos; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Tickets abiertos</h4>
                <strong><?php echo $ticketsAbiertos; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Facturas pendientes</h4>
                <strong><?php echo $facturasPendientes; ?></strong>
            </div>
            <div class="stat-card">
                <h4>Crédito disponible</h4>
                <strong><?php echo formatoPrecio($usuario['credito']); ?></strong>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="panel.php" class="<?php echo $seccion === 'inicio' ? 'active' : ''; ?>">🏠 Inicio</a></li>
                    <li><a href="panel.php?seccion=servicios" class="<?php echo $seccion === 'servicios' ? 'active' : ''; ?>">🎮 Mis servicios</a></li>
                    <li><a href="panel.php?seccion=facturas" class="<?php echo $seccion === 'facturas' ? 'active' : ''; ?>">📄 Facturas</a></li>
                    <li><a href="panel.php?seccion=tickets" class="<?php echo $seccion === 'tickets' ? 'active' : ''; ?>">🎫 Soporte</a></li>
                    <li><a href="panel.php?seccion=perfil" class="<?php echo $seccion === 'perfil' ? 'active' : ''; ?>">👤 Mi perfil</a></li>
                    <?php if (esAdmin()): ?>
                    <li><a href="admin.php">⚙️ Administración</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">🚪 Cerrar sesión</a></li>
                </ul>
            </aside>
            
            <main class="dashboard-content">
                <?php if ($seccion === 'inicio'): ?>
                    <h2>Resumen de cuenta</h2>
                    <p class="text-muted mb-3">Aquí puedes ver un resumen rápido de tus servicios.</p>
                    
                    <?php
                    $pedidos = obtenerPedidos($usuario['id']);
                    if (count($pedidos) > 0):
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Renovación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($pedido['plan_nombre']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($pedido['categoria_nombre']); ?></small>
                                </td>
                                <td><?php echo badgeEstado($pedido['estado']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pedido['fecha_renovacion'])); ?></td>
                                <td><a href="panel.php?seccion=servicios" class="btn btn-sm btn-outline">Gestionar</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No tienes servicios activos. <a href="planes.php">Contrata tu primer servidor</a>.
                        </div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'servicios'): ?>
                    <h2>Mis servicios</h2>
                    <?php
                    $pedidos = obtenerPedidos($usuario['id']);
                    if (count($pedidos) > 0):
                    ?>
                    <div class="grid grid-2">
                        <?php foreach ($pedidos as $pedido): ?>
                        <div class="card">
                            <h3><?php echo e($pedido['plan_nombre']); ?></h3>
                            <p class="text-muted"><?php echo e($pedido['categoria_nombre']); ?></p>
                            <p><strong>Estado:</strong> <?php echo badgeEstado($pedido['estado']); ?></p>
                            <p><strong>Ciclo:</strong> <?php echo ucfirst($pedido['ciclo']); ?></p>
                            <p><strong>Renovación:</strong> <?php echo date('d/m/Y', strtotime($pedido['fecha_renovacion'])); ?></p>
                            <?php if ($pedido['dominio']): ?>
                                <p><strong>Dominio:</strong> <?php echo e($pedido['dominio']); ?></p>
                            <?php endif; ?>
                            <div class="mt-3 flex gap-2">
                                <a href="#" class="btn btn-primary btn-sm">Abrir panel</a>
                                <a href="upgrade.php?pedido=<?php echo $pedido['id']; ?>" class="btn btn-secondary btn-sm">Upgrade</a>
                                <a href="panel.php?seccion=facturas" class="btn btn-outline btn-sm">Renovar</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No tienes servicios activos. <a href="planes.php">Contrata tu primer servidor</a>.
                        </div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'facturas'): ?>
                    <h2>Mis facturas</h2>
                    <?php
                    $facturas = obtenerFacturas($usuario['id']);
                    if (count($facturas) > 0):
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Concepto</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Vencimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $factura): ?>
                            <tr>
                                <td><?php echo e($factura['numero']); ?></td>
                                <td><?php echo e($factura['concepto']); ?></td>
                                <td><?php echo formatoPrecio($factura['total']); ?></td>
                                <td><?php echo badgeEstado($factura['estado']); ?></td>
                                <td><?php echo $factura['vencimiento'] ? date('d/m/Y', strtotime($factura['vencimiento'])) : '—'; ?></td>
                                <td>
                                    <a href="factura.php?id=<?php echo $factura['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Ver</a>
                                    <?php if ($factura['estado'] === 'pendiente'): ?>
                                        <a href="pagar.php?factura=<?php echo $factura['id']; ?>" class="btn btn-sm btn-success">Pagar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">No tienes facturas registradas.</div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'tickets'): ?>
                    <h2>Soporte técnico</h2>
                    
                    <div class="card mb-3">
                        <h3>Nuevo ticket</h3>
                        <form action="panel.php?seccion=tickets" method="POST">
                            <input type="hidden" name="nuevo_ticket" value="1">
                            <div class="form-group">
                                <label>Asunto</label>
                                <input type="text" name="asunto" class="form-control" required>
                            </div>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label>Departamento</label>
                                    <select name="departamento" class="form-control">
                                        <option value="soporte">Soporte técnico</option>
                                        <option value="ventas">Ventas</option>
                                        <option value="facturacion">Facturación</option>
                                        <option value="tecnico">Técnico avanzado</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Prioridad</label>
                                    <select name="prioridad" class="form-control">
                                        <option value="baja">Baja</option>
                                        <option value="media" selected>Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mensaje</label>
                                <textarea name="mensaje" class="form-control" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Crear ticket</button>
                        </form>
                    </div>
                    
                    <h3>Tus tickets</h3>
                    <?php
                    $tickets = obtenerTickets($usuario['id']);
                    if (count($tickets) > 0):
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Asunto</th>
                                <th>Departamento</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo e($ticket['asunto']); ?></td>
                                <td><?php echo ucfirst($ticket['departamento']); ?></td>
                                <td><?php echo ucfirst($ticket['prioridad']); ?></td>
                                <td><?php echo badgeEstado($ticket['estado']); ?></td>
                                <td><a href="panel.php?seccion=ver_ticket&id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline">Ver</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">No has creado tickets de soporte.</div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'ver_ticket'): ?>
                    <?php
                    $ticketId = intval($_GET['id'] ?? 0);
                    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND usuario_id = ? LIMIT 1");
                    $stmt->execute([$ticketId, $usuario['id']]);
                    $ticket = $stmt->fetch();
                    
                    if ($ticket):
                        $stmt = $pdo->prepare("SELECT m.*, u.nombre, u.rol FROM ticket_mensajes m JOIN usuarios u ON m.usuario_id = u.id WHERE m.ticket_id = ? ORDER BY m.creado_en ASC");
                        $stmt->execute([$ticketId]);
                        $mensajes = $stmt->fetchAll();
                    ?>
                    <h2>Ticket #<?php echo $ticket['id']; ?></h2>
                    <p class="text-muted"><?php echo e($ticket['asunto']); ?> - <?php echo badgeEstado($ticket['estado']); ?></p>
                    
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
                    
                    <?php if ($ticket['estado'] !== 'cerrado'): ?>
                    <form action="panel.php?seccion=ver_ticket&id=<?php echo $ticketId; ?>" method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                        <input type="hidden" name="respuesta" value="1">
                        <div class="form-group">
                            <label>Responder</label>
                            <textarea name="mensaje" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar respuesta</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <div class="alert alert-danger">Ticket no encontrado.</div>
                    <?php endif; ?>
                
                <?php elseif ($seccion === 'perfil'): ?>
                    <h2>Mi perfil</h2>
                    <div class="card">
                        <p><strong>Nombre:</strong> <?php echo e($usuario['nombre']); ?></p>
                        <p><strong>Email:</strong> <?php echo e($usuario['email']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo e($usuario['telefono'] ?: 'No especificado'); ?></p>
                        <p><strong>País:</strong> <?php echo e($usuario['pais'] ?: 'No especificado'); ?></p>
                        <p><strong>Miembro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['creado_en'])); ?></p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
