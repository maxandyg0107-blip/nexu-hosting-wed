# Nexu Hosting

Plataforma SaaS de venta de Hosting Web y Servidores de Minecraft para el mercado peruano, con verificación manual de pagos (Yape, Plin, Banco de la Nación, Interbank, BCP) mediante subida de comprobante (voucher).

Stack: **PHP nativo (MVC ligero) + PDO/MySQL + Tailwind CSS (CDN)**. Sin frameworks externos ni dependencias de Composer — 100% desplegable en cualquier hosting compartido con PHP 8.1+.

## 1. Estructura de archivos

```
nexu-hosting/
├── .htaccess                     # Endurecimiento Apache raíz
├── index.php                     # Catálogo público de planes
├── login.php                     # Inicio de sesión
├── register.php                  # Registro de clientes
├── logout.php                    # Cierre de sesión seguro
├── checkout.php                  # Resumen de compra + pago + subida de voucher
├── dashboard.php                 # Panel de cliente (servidores, métricas, órdenes)
├── admin_orders.php              # Panel admin: aprobar/rechazar órdenes
├── voucher_view.php              # Visor seguro de comprobantes (owner/admin only)
│
├── config/
│   ├── config.php                # Constantes de entorno, pagos, uploads
│   └── db.php                    # Conexión PDO (Singleton, prepared statements)
│
├── includes/
│   ├── bootstrap.php             # Punto de arranque único (autoload + sesión + headers)
│   ├── security.php              # CSRF, sanitización, sesiones, middlewares de auth
│   └── functions.php             # Helpers de presentación (money_pen, badges, etc.)
│
├── controllers/
│   ├── AuthController.php        # Login / Registro / Logout
│   ├── OrderController.php       # Checkout + validación y subida de voucher
│   └── AdminController.php       # Aprobación / rechazo de órdenes
│
├── models/
│   ├── User.php
│   ├── Plan.php
│   ├── Order.php                 # Incluye Order::approve() transaccional
│   └── Server.php
│
├── views/
│   └── layouts/
│       ├── header.php            # <head> + navbar (tema oscuro Tailwind)
│       └── footer.php
│
├── uploads/
│   └── vouchers/
│       └── .htaccess             # Bloquea ejecución de scripts en este directorio
│
├── database/
│   └── schema.sql                # DDL completo + datos semilla de planes
│
└── logs/
    └── app_errors.log            # (generado en runtime, fuera del document root ideal)
```

## 2. Puesta en marcha

1. Importa `database/schema.sql` en tu instancia de MySQL/MariaDB.
2. Copia `config/config.php` y ajusta `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL` y los datos reales de `PAYMENT_INFO` (números de Yape/Plin, CCI bancarios).
3. Crea un usuario `admin` manualmente (o vía `register.php` + `UPDATE users SET role='admin' WHERE id=...`), ya que el registro público solo crea cuentas `client`.
4. Verifica que PHP tenga las extensiones `pdo_mysql` y `fileinfo` habilitadas.
5. **Mueve idealmente `logs/` y `config/` fuera del document root** en producción, o refuerza el `.htaccess` (ya incluido) que bloquea el acceso directo a `config/`, `includes/`, `models/`, `controllers/`, `database/` y `logs/`.
6. En Nginx (si no usas Apache), replica las reglas del `.htaccess` de `uploads/vouchers/` así:
   ```nginx
   location ^~ /uploads/vouchers/ {
       location ~ \.php$ { deny all; }
       autoindex off;
   }
   ```

## 3. Notas sobre la arquitectura de pagos

Yape y Plin no exponen APIs públicas de cobro automatizado para comercios pequeños/emergentes sin pasar por una pasarela intermediaria (Culqi, Mercado Pago, Izipay). Por eso el flujo implementado es **pago manual verificado**:

1. El cliente ve los datos de la cuenta/número en `checkout.php` y transfiere por su app.
2. Sube el comprobante (voucher) → se valida (extensión + MIME real + tamaño) y se guarda con nombre aleatorio en `uploads/vouchers/`.
3. La orden queda `pending`.
4. Un administrador revisa el comprobante en `admin_orders.php` (vía `voucher_view.php`, con control de acceso) y **Aprueba** o **Rechaza**.
5. Al aprobar, `Order::approve()` ejecuta una transacción SQL que marca la orden `verified` **y** crea el registro en `servers` de forma atómica — si algo falla, se revierte todo (`ROLLBACK`).

Si más adelante quieres automatizar el cobro, el punto de integración natural es sustituir el paso 2-3 por un webhook de la pasarela elegida, sin tocar el resto del flujo (el estado `pending → verified` seguiría siendo el mismo contrato).

## 4. Superficie de seguridad cubierta

- **Inyección SQL:** PDO con `ATTR_EMULATE_PREPARES = false` en todos los modelos.
- **XSS:** función `e()` (htmlspecialchars) en toda salida a vistas.
- **CSRF:** token de sesión + `hash_equals` en todo formulario POST.
- **Fuerza bruta:** bloqueo temporal tras `MAX_LOGIN_ATTEMPTS` intentos fallidos.
- **Sesiones:** cookies `HttpOnly`, `Secure`, `SameSite=Strict`, regeneración periódica de ID.
- **Subida de archivos:** validación de extensión + MIME real (`finfo`, no cabecera del cliente) + tamaño máximo + nombre aleatorio + directorio sin permiso de ejecución de scripts.
- **Path traversal:** `voucher_view.php` sanea el nombre de archivo y valida con `realpath()` que quede dentro de `uploads/vouchers/`.
- **Control de acceso:** middlewares `require_login()` / `require_admin()` en cada endpoint sensible.
- **Errores:** `try/catch` + `error_log()` interno; nunca se exponen trazas ni credenciales al cliente en `APP_ENV=production`.
