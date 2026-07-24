# Nexu Hosting v2.0

Plataforma SaaS de hosting web y servidores de videojuegos, construida con PHP nativo (MVC) + Tailwind CSS. Diseñada para el mercado peruano con soporte para pagos locales: Yape, Plin, Banco de la Nación, Interbank y BCP.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.1+ nativo (MVC sin framework) |
| Base de datos | MySQL 8.0 / MariaDB 10.5 (PDO + prepared statements) |
| Frontend | Tailwind CSS v3 (CDN) + Alpine.js |
| Seguridad | Argon2ID, CSRF tokens, session hardening, OWASP |
| Auth OAuth | Google OAuth2, Discord OAuth2 |
| Pagos | Yape, Plin, Banco de la Nación, Interbank, BCP |
| Panel servidores | Pterodactyl Panel (integración API) |

---

## Estructura de archivos

```
public/nexu-hosting/
├── config/
│   ├── config.php          # Configuración central (DB, OAuth, pagos, rutas)
│   ├── database.php        # Singleton PDO
│   ├── session.php         # Sesiones seguras (HttpOnly, Secure, SameSite=Strict)
│   └── bootstrap.php       # Carga global: config → session → helpers → autoload
├── models/
│   ├── UserModel.php       # Usuarios, OAuth, rate limiting, password reset
│   ├── PlanModel.php       # Catálogo de planes con precios en PEN
│   ├── OrderModel.php      # Órdenes, vouchers, aprobación/rechazo atómico
│   ├── ServerModel.php     # Servidores aprovisionados, métricas, Pterodactyl
│   └── AuditModel.php      # Log inmutable de acciones del sistema
├── controllers/
│   ├── AuthController.php  # Login/registro local, OAuth Google/Discord, password reset
│   ├── PaymentController.php # Checkout, subida segura de comprobantes
│   └── AdminController.php # Dashboard admin, órdenes, clientes, servidores, planes
├── helpers/
│   ├── functions.php       # Helpers: sanitize, flash, redirect, formatPrice, badges
│   ├── csrf.php            # Generación y validación de tokens CSRF
│   └── currency.php        # Conversión PEN ↔ USD ↔ EUR
├── views/partials/
│   ├── head.php            # <head> con Tailwind config y animaciones
│   ├── navbar.php          # Navbar responsiva con Alpine.js + currency switcher
│   └── footer.php          # Footer con redes sociales y métodos de pago
├── admin/
│   ├── dashboard.php       # KPIs, gráfico de ingresos, auditoría reciente
│   ├── orders.php          # Aprobación/rechazo de pagos + visor de comprobantes
│   ├── clients.php         # Lista de clientes con búsqueda y suspensión
│   ├── servers.php         # Gestión de servidores + asignación de nodo Pterodactyl
│   ├── settings.php        # CRUD de planes + referencia de datos de pago
│   ├── audit.php           # Registro de auditoría completo
│   ├── partials/sidebar.php
│   └── actions/            # Controladores POST: aprobar, rechazar, suspender, etc.
├── auth/
│   ├── login.php           # POST handler para login
│   ├── register.php        # POST handler para registro
│   ├── logout.php
│   ├── currency.php        # Cambio de moneda de sesión
│   ├── send_reset.php      # Solicitar recuperación de contraseña
│   ├── reset_password.php  # Cambiar contraseña con token
│   ├── google/redirect.php # OAuth Google — inicio
│   ├── google/callback.php # OAuth Google — callback
│   ├── discord/redirect.php# OAuth Discord — inicio
│   └── discord/callback.php# OAuth Discord — callback
├── uploads/vouchers/       # Comprobantes de pago (protegidos con .htaccess)
├── logs/                   # Logs internos PHP (protegidos con .htaccess)
├── sql/
│   └── nexuhosting_v2.sql  # Esquema completo + datos de ejemplo
├── index.php               # Landing page
├── planes.php              # Catálogo de planes + vista individual
├── checkout.php            # Pasarela de pago peruana con subida de comprobante
├── dashboard.php           # Panel del cliente
├── colaboradores.php       # Equipo con cards de Discord
├── estado.php              # Estado del sistema en tiempo real
├── contacto.php            # Formulario de contacto
├── recuperar.php           # Recuperación de contraseña
├── error.php               # Páginas de error (403, 404, 500)
├── logout.php              # Compatibilidad backward (redirige a auth/logout.php)
├── .htaccess               # Seguridad Apache, bloqueo de carpetas internas
└── .env.example            # Plantilla de variables de entorno
```

---

## Instalación

### Requisitos
- PHP 8.1+ con extensiones: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `curl`
- MySQL 8.0+ o MariaDB 10.5+
- Apache 2.4+ con `mod_rewrite` habilitado

### Pasos

**1. Importar la base de datos**
```sql
mysql -u root -p < sql/nexuhosting_v2.sql
```

**2. Configurar credenciales**

Edita `config/config.php` y ajusta las constantes `DB_*`, o usa variables de entorno (`.env`).

**3. Permisos de directorio**
```bash
chmod 755 uploads/
chmod 755 uploads/vouchers/
chmod 755 logs/
```

**4. Configurar OAuth (opcional)**

- **Google:** Crea credenciales en [Google Cloud Console](https://console.cloud.google.com) → URI de redireccionamiento: `https://tudominio.com/auth/google/callback.php`
- **Discord:** Crea aplicación en [Discord Developer Portal](https://discord.com/developers/applications) → Redirect URI: `https://tudominio.com/auth/discord/callback.php`

Agrega las claves en `config/config.php` o como variables de entorno.

**5. Acceso inicial**

| URL | Descripción |
|---|---|
| `/` | Landing page |
| `/login.php` | Iniciar sesión (admin: `admin@nexuhosting.com` / `Admin2024!`) |
| `/admin/dashboard.php` | Panel administrativo |
| `/planes.php` | Catálogo de planes |

> ⚠️ **Cambia la contraseña del admin inmediatamente** tras la instalación.

---

## Seguridad implementada (OWASP Top 10)

| Vulnerabilidad | Mitigación |
|---|---|
| SQL Injection | PDO + prepared statements en **todos** los queries |
| XSS | `htmlspecialchars()` en todo output con `e()` helper |
| CSRF | Tokens por sesión con expiración en todos los formularios POST |
| Broken Auth | Argon2ID hashing, rate limiting, session hardening |
| Sensitive Data | HTTPS headers, logs privados, sin stacktraces al usuario |
| Security Misconfiguration | `.htaccess` bloquea carpetas internas, execution de PHP en uploads |
| File Upload | Validación MIME real con `finfo`, extensión, tamaño, renombrado con CSPRNG |
| Insecure Direct Object | Verificación de propiedad antes de cada operación |

---

## Flujo de pago (Perú)

```
Cliente elige plan → Checkout (resumen + método)
→ Sube comprobante (JPG/PNG/PDF, max 10MB)
→ Orden creada con status "pending"
→ Admin recibe notificación
→ Admin revisa comprobante en /admin/orders.php
→ Aprueba: servidor se crea automáticamente con status "installing"
→ Rechaza: cliente ve motivo en su dashboard
```

### Métodos soportados
- 📲 **Yape** — número + QR estático
- 💚 **Plin** — número + QR estático
- 🏛️ **Banco de la Nación** — cuenta + CCI
- 🏦 **Interbank** — cuenta + CCI
- 🏧 **BCP** — cuenta + CCI

---

## Créditos

Desarrollado con ❤️ por el equipo de Nexu Hosting.
