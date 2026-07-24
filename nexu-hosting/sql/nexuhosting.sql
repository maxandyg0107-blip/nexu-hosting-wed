-- ============================================================
-- Nexu Hosting - Base de Datos Completa
-- Importar en phpMyAdmin o MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS nexuhosting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexuhosting;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    pais VARCHAR(50) DEFAULT NULL,
    direccion TEXT DEFAULT NULL,
    rol ENUM('cliente','admin') DEFAULT 'cliente',
    credito DECIMAL(10,2) DEFAULT 0.00,
    activo TINYINT(1) DEFAULT 1,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de categorías de servicios
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'gamepad',
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de planes/servicios
CREATE TABLE IF NOT EXISTS planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT,
    precio_mensual DECIMAL(10,2) NOT NULL,
    precio_trimestral DECIMAL(10,2) DEFAULT NULL,
    precio_anual DECIMAL(10,2) DEFAULT NULL,
    ram_mb INT DEFAULT NULL,
    cpu VARCHAR(50) DEFAULT NULL,
    almacenamiento VARCHAR(50) DEFAULT NULL,
    slots INT DEFAULT NULL,
    caracteristicas TEXT,
    popular TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de pedidos/contrataciones
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    plan_id INT NOT NULL,
    dominio VARCHAR(255) DEFAULT NULL,
    ciclo ENUM('mensual','trimestral','anual') DEFAULT 'mensual',
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','activo','suspendido','cancelado') DEFAULT 'pendiente',
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_renovacion DATETIME DEFAULT NULL,
    metodo_pago VARCHAR(50) DEFAULT NULL,
    txn_id VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de tickets de soporte
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    departamento ENUM('soporte','ventas','facturacion','tecnico') DEFAULT 'soporte',
    prioridad ENUM('baja','media','alta','urgente') DEFAULT 'media',
    estado ENUM('abierto','respondido','cerrado') DEFAULT 'abierto',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de mensajes de tickets
CREATE TABLE IF NOT EXISTS ticket_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    es_staff TINYINT(1) DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de facturas
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pedido_id INT DEFAULT NULL,
    numero VARCHAR(50) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    estado ENUM('pendiente','pagada','vencida','cancelada') DEFAULT 'pendiente',
    vencimiento DATETIME DEFAULT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    pagado_en DATETIME DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de noticias/anuncios
CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    destacada TINYINT(1) DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de contactos recibidos
CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de testimonios/reseñas
CREATE TABLE IF NOT EXISTS testimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    pais VARCHAR(50) DEFAULT NULL,
    estrellas INT DEFAULT 5,
    comentario TEXT NOT NULL,
    aprobado TINYINT(1) DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de estado de servicios
CREATE TABLE IF NOT EXISTS servicios_estado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('node','service','location') DEFAULT 'service',
    ubicacion VARCHAR(100) DEFAULT NULL,
    estado ENUM('operacional','mantenimiento','intermitente','caido') DEFAULT 'operacional',
    uptime DECIMAL(5,2) DEFAULT 99.99,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de transacciones/pagos reales
CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    factura_id INT DEFAULT NULL,
    pedido_id INT DEFAULT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    estado ENUM('pendiente','completado','fallido','reembolsado','cancelado') DEFAULT 'pendiente',
    referencia_externa VARCHAR(255) DEFAULT NULL,
    token_pago VARCHAR(255) DEFAULT NULL,
    datos_pago TEXT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de configuración de pasarelas de pago
CREATE TABLE IF NOT EXISTS config_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pasarela VARCHAR(50) NOT NULL UNIQUE,
    activo TINYINT(1) DEFAULT 0,
    modo_sandbox TINYINT(1) DEFAULT 1,
    public_key TEXT DEFAULT NULL,
    secret_key TEXT DEFAULT NULL,
    webhook_secret TEXT DEFAULT NULL,
    configuracion TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de historial de renovaciones
CREATE TABLE IF NOT EXISTS renovaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    factura_id INT NOT NULL,
    transaccion_id INT DEFAULT NULL,
    ciclo ENUM('mensual','trimestral','anual') DEFAULT 'mensual',
    monto DECIMAL(10,2) NOT NULL,
    fecha_renovacion_anterior DATETIME NOT NULL,
    fecha_renovacion_nueva DATETIME NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (transaccion_id) REFERENCES transacciones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de configuración general
CREATE TABLE IF NOT EXISTS config_general (
    clave VARCHAR(100) PRIMARY KEY,
    valor TEXT DEFAULT NULL,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar datos iniciales
INSERT INTO categorias (nombre, slug, descripcion, icono, orden) VALUES
('Minecraft Java', 'minecraft-java', 'Servidores Minecraft Java Edition con mods y plugins.', 'cube', 1),
('Minecraft Bedrock', 'minecraft-bedrock', 'Servidores Minecraft Bedrock para móviles y consolas.', 'mobile', 2),
('Counter-Strike', 'counter-strike', 'Servidores CS 1.6, Source y Global Offensive.', 'crosshair', 3),
('ARK: Survival Evolved', 'ark', 'Servidores ARK con todos los DLCs disponibles.', 'dragon', 4),
('Rust', 'rust', 'Servidores Rust con alto rendimiento y DDoS protection.', 'shield', 5),
('VPS Gamer', 'vps-gamer', 'VPS dedicados para gaming y aplicaciones exigentes.', 'server', 6);

-- Insertar planes
INSERT INTO planes (categoria_id, nombre, slug, descripcion, precio_mensual, ram_mb, cpu, almacenamiento, slots, caracteristicas, popular, orden) VALUES
(1, 'Starter', 'mc-starter', 'Perfecto para jugar con amigos.', 4.99, 2048, 'AMD Ryzen 5', '20GB NVMe', 10, '["2GB RAM", "10 Slots", "20GB NVMe", "Soporte 24/7", "Panel Pterodactyl", "Backups diarios"]', 0, 1),
(1, 'Gamer', 'mc-gamer', 'El plan más popular para comunidades medianas.', 9.99, 4096, 'AMD Ryzen 7', '40GB NVMe', 25, '["4GB RAM", "25 Slots", "40GB NVMe", "Soporte prioritario", "Panel Pterodactyl", "Backups cada 6h", "Mods/plugins ilimitados"]', 1, 2),
(1, 'Pro', 'mc-pro', 'Para servidores grandes y modpacks pesados.', 19.99, 8192, 'AMD Ryzen 9', '80GB NVMe', 50, '["8GB RAM", "50 Slots", "80GB NVMe", "Soporte prioritario", "Panel Pterodactyl", "Backups cada 3h", "IP dedicada opcional"]', 0, 3),
(1, 'Elite', 'mc-elite', 'Rendimiento máximo para comunidades profesionales.', 39.99, 16384, 'AMD Ryzen 9', '160GB NVMe', 100, '["16GB RAM", "100 Slots", "160GB NVMe", "Soporte VIP", "Panel Pterodactyl", "Backups horarios", "IP dedicada incluida"]', 0, 4),

(2, 'Bedrock Basic', 'bedrock-basic', 'Inicia tu servidor Bedrock.', 3.99, 1536, 'AMD Ryzen 5', '15GB NVMe', 12, '["1.5GB RAM", "12 Slots", "15GB NVMe", "Soporte 24/7"]', 0, 1),
(2, 'Bedrock Plus', 'bedrock-plus', 'Para comunidades Bedrock activas.', 7.99, 3072, 'AMD Ryzen 7', '30GB NVMe', 30, '["3GB RAM", "30 Slots", "30GB NVMe", "Soporte prioritario"]', 1, 2),

(3, 'CS 1.6 Server', 'cs-16', 'Servidor Counter-Strike 1.6 clásico.', 5.99, 2048, 'AMD Ryzen 5', '15GB SSD', 32, '["32 Slots", "Anti-cheat", "SourceMod", "FastDL"]', 1, 1),
(3, 'CS:GO Server', 'cs-go', 'Servidor CS:GO competitivo.', 9.99, 4096, 'AMD Ryzen 7', '25GB SSD', 32, '["32 Slots", "128 tick", "SourceMod", "Workshop"]', 0, 2),

(4, 'ARK Starter', 'ark-starter', 'Servidor ARK para tribus pequeñas.', 14.99, 6144, 'AMD Ryzen 7', '50GB NVMe', 20, '["6GB RAM", "20 Slots", "Todos los mapas", "Mods automáticos"]', 0, 1),
(4, 'ARK Pro', 'ark-pro', 'Para tribus grandes y múltiples mapas.', 29.99, 12288, 'AMD Ryzen 9', '100GB NVMe', 50, '["12GB RAM", "50 Slots", "Cluster de mapas", "Soporte VIP"]', 1, 2),

(5, 'Rust Basic', 'rust-basic', 'Servidor Rust para grupos pequeños.', 12.99, 4096, 'AMD Ryzen 7', '40GB NVMe', 50, '["4GB RAM", "50 Slots", "Oxido", "Wipe automático"]', 0, 1),
(5, 'Rust Pro', 'rust-pro', 'Servidor Rust de alto rendimiento.', 24.99, 8192, 'AMD Ryzen 9', '80GB NVMe', 100, '["8GB RAM", "100 Slots", "Plugins premium", "Anti-DDoS"]', 1, 2),

(6, 'VPS Gamer 1', 'vps-1', 'VPS para aplicaciones ligeras.', 14.99, 4096, 'AMD Ryzen 5', '50GB NVMe', NULL, '["2 vCPU", "4GB RAM", "50GB NVMe", "1Gbps uplink", "Linux/Windows"]', 0, 1),
(6, 'VPS Gamer 2', 'vps-2', 'VPS equilibrado para gaming.', 29.99, 8192, 'AMD Ryzen 7', '100GB NVMe', NULL, '["4 vCPU", "8GB RAM", "100GB NVMe", "1Gbps uplink", "Anti-DDoS"]', 1, 2),
(6, 'VPS Gamer 3', 'vps-3', 'VPS potente para servidores dedicados.', 59.99, 16384, 'AMD Ryzen 9', '200GB NVMe', NULL, '["6 vCPU", "16GB RAM", "200GB NVMe", "1Gbps uplink", "IP dedicada"]', 0, 3);

-- Insertar usuario administrador por defecto
-- Contraseña: admin123 (cámbiala en producción)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@nexuhosting.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertar noticias de ejemplo
INSERT INTO noticias (titulo, contenido, destacada) VALUES
('🚀 Lanzamiento oficial de Nexu Hosting', 'Bienvenidos a la nueva generación de hosting para gamers. Servidores de alto rendimiento, panel intuitivo y soporte 24/7.', 1),
('Actualización del panel de control', 'Hemos mejorado nuestro panel Pterodactyl con nuevas funciones de gestión y monitoreo en tiempo real.', 0),
('Nuevos métodos de pago disponibles', 'Ahora aceptamos más de 150 métodos de pago diferentes para tu comodidad.', 0);

-- Insertar testimonios de ejemplo
INSERT INTO testimonios (nombre, pais, estrellas, comentario, aprobado) VALUES
('Carlos Mendoza', 'México', 5, 'El mejor hosting de Minecraft que he probado. Mi servidor nunca se ha caído y el soporte responde en minutos.', 1),
('Ana García', 'España', 5, 'Contraté un servidor ARK y la instalación fue instantánea. El panel Pterodactyl es muy intuitivo.', 1),
('Juan Pérez', 'Argentina', 4, 'Buen rendimiento y precios justos. Recomiendo el plan Gamer para comunidades medianas.', 1),
('Lucía Torres', 'Colombia', 5, 'Increíble atención al cliente. Me ayudaron a configurar mods sin costo extra.', 1),
('Diego Ramírez', 'Chile', 5, 'Llevo 6 meses con Nexu Hosting y el uptime ha sido perfecto. 100% recomendado.', 1);

-- Insertar estado de servicios de ejemplo
INSERT INTO servicios_estado (nombre, tipo, ubicacion, estado, uptime) VALUES
('Panel de control (NCP)', 'service', 'Global', 'operacional', 99.99),
('Nodo Minecraft - Norteamérica', 'node', 'Canadá', 'operacional', 99.97),
('Nodo Minecraft - Europa', 'node', 'Alemania', 'operacional', 99.95),
('Nodo Minecraft - Sudamérica', 'node', 'Brasil', 'operacional', 99.98),
('Nodo ARK/Rust', 'node', 'Estados Unidos', 'operacional', 99.96),
('Protección DDoS', 'service', 'Global', 'operacional', 100.00),
('Backups automáticos', 'service', 'Global', 'operacional', 99.99);

-- Configuración de pasarelas de pago (modo sandbox por defecto)
INSERT INTO config_pagos (pasarela, activo, modo_sandbox) VALUES
('stripe', 0, 1),
('paypal', 0, 1),
('mercadopago', 0, 1),
('transferencia', 1, 0);

-- Configuración general
INSERT INTO config_general (clave, valor) VALUES
('moneda_principal', 'USD'),
('simbolo_moneda', '$'),
('impuesto_porcentaje', '0'),
('nombre_empresa', 'Nexu Hosting'),
('email_facturacion', 'facturas@nexuhosting.com'),
('direccion_empresa', 'Ciudad de México, México'),
('telefono_empresa', '+52 1 234 5678');
