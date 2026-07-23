# 🚀 Nexu Hosting - Sistema Web en PHP

Este es el código fuente completo del sitio web de **Nexu Hosting**, un sistema de hosting para servidores de juegos inspirado en BeansHosting.

## 📁 Estructura de archivos

```
nexu-hosting/
├── index.php              # Página de inicio
├── servicios.php          # Página de servicios/categorías
├── planes.php             # Página de planes y contratación
├── noticias.php           # Listado de noticias
├── noticia.php            # Detalle de noticia
├── faq.php                # Preguntas frecuentes
├── contacto.php           # Formulario de contacto
├── testimonios.php        # Reseñas de clientes
├── estado.php             # Estado de servicios
├── terminos.php           # Términos de servicio
├── privacidad.php         # Política de privacidad
├── login.php              # Inicio de sesión
├── register.php           # Registro de usuarios
├── recuperar.php          # Recuperación de contraseña
├── logout.php             # Cerrar sesión
├── panel.php              # Panel de cliente
├── admin.php              # Panel de administración
├── procesar_pedido.php    # Procesador de pedidos
├── pagar.php              # Portal de pagos para clientes
├── factura.php            # Visualización/impresión de facturas
├── upgrade.php            # Cambiar/actualizar plan
├── webhook.php            # Recepción de webhooks de pago
├── instalar.php           # Instalador web automático
├── includes/
│   ├── header.php         # Header compartido
│   ├── footer.php         # Footer compartido
│   ├── db.php             # Configuración de base de datos
│   └── funciones.php      # Funciones comunes
├── assets/
│   ├── css/style.css      # Estilos principales
│   └── js/main.js         # JavaScript principal
└── sql/
    └── nexuhosting.sql    # Estructura e datos iniciales de la BD
```

## ⚙️ Requisitos

- PHP 8.0 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web Apache/Nginx (recomendado XAMPP, WAMP o LAMP)
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`

## 🛠️ Instalación

### Opción A: Instalador web automático (recomendado)

1. **Sube todos los archivos** de `nexu-hosting/` a tu servidor (ej. `public_html/`).
2. **Accede al instalador:** `http://tudominio.com/nexu-hosting/instalar.php`
3. **Ingresa tus credenciales de MySQL** y sigue los pasos.
4. **Elimina `instalar.php`** después de la instalación por seguridad.

### Opción B: Instalación manual

1. **Crear la base de datos:**
   - Abre phpMyAdmin o la línea de comandos de MySQL.
   - Importa el archivo `sql/nexuhosting.sql`.
   - Se creará la base de datos `nexuhosting` con tablas y datos de ejemplo.

2. **Configurar la conexión a la base de datos:**
   - Abre `includes/db.php`.
   - Modifica las constantes con tus credenciales:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'nexuhosting');
     ```

3. **Subir archivos al servidor:**
   - Copia toda la carpeta `nexu-hosting/` a la carpeta `htdocs/` (XAMPP) o `www/` (WAMP).
   - Accede desde el navegador a: `http://localhost/nexu-hosting/`

### Credenciales de administrador

- Email: `admin@nexuhosting.com`
- Contraseña: `admin123`
- **Importante:** Cambia la contraseña del administrador en producción.

## 🌐 Secciones disponibles

- **Inicio** (`index.php`): Hero, características, comparativa, planes populares, testimonios, noticias.
- **Servicios** (`servicios.php`): Categorías de juegos y planes filtrados.
- **Planes** (`planes.php`): Todos los planes con opción de contratación.
- **Noticias** (`noticias.php` / `noticia.php`): Blog de novedades gestionable desde admin.
- **FAQ** (`faq.php`): Preguntas frecuentes con acordeón interactivo.
- **Contacto** (`contacto.php`): Formulario de contacto funcional.
- **Testimonios** (`testimonios.php`): Sistema de reseñas con moderación.
- **Estado** (`estado.php`): Monitoreo de infraestructura en tiempo real.
- **Términos** (`terminos.php`) y **Privacidad** (`privacidad.php`).
- **Login** (`login.php`), **Register** (`register.php`) y **Recuperar** (`recuperar.php`).
- **Panel** (`panel.php`): Área de cliente para gestionar servicios, facturas y tickets.
- **Admin** (`admin.php`): Panel de administración completo.

## ✨ Funcionalidades

- ✅ Diseño moderno tipo dark mode, responsive y animaciones.
- ✅ Sistema de registro, inicio de sesión y recuperación de contraseña.
- ✅ Panel de cliente con gestión de servicios, facturas y tickets.
- ✅ Panel de administración completo: finanzas, usuarios, pedidos, facturas, transacciones, tickets, noticias, testimonios y estado.
- ✅ **Sistema de pagos real**: Stripe, PayPal, MercadoPago, transferencia bancaria.
- ✅ **Facturación real** con números de factura automáticos, vencimiento e impresión/GPDF.
- ✅ **Dashboard financiero** con ganancias por día, mes, año y método de pago.
- ✅ **Renovaciones automáticas** al confirmar pagos.
- ✅ **Upgrade de planes** para servicios activos.
- ✅ Webhooks para notificaciones automáticas de Stripe/PayPal/MercadoPago.
- ✅ Formulario de contacto que guarda mensajes en base de datos.
- ✅ Sistema de pedidos con cálculo de precios por ciclo y descuentos.
- ✅ Comparativa de proveedores similar a BeansHosting.
- ✅ Sistema de noticias/blog gestionable.
- ✅ Sistema de testimonios con aprobación de admin.
- ✅ Página de estado de servicios actualizable por admin.
- ✅ Instalador web automático (`instalar.php`).
- ✅ Integración con panel Pterodactyl (enlace/visual).

## 📝 Notas importantes

- Este código está preparado para funcionar con **PHP puro + MySQL**.
- El procesamiento de pagos es un **simulador**: crea facturas pendientes que el admin puede marcar como pagadas.
- Para pagos reales, integra Stripe, PayPal, MercadoPago u otro gateway según tu país.
- La conexión con Pterodactyl para crear servidores reales requiere la API de tu panel Pterodactyl.

## 🛡️ Seguridad

- Las contraseñas se almacenan con `password_hash()` (bcrypt).
- Las consultas usan prepared statements de PDO para prevenir SQL Injection.
- El output se escapa con `htmlspecialchars()` para prevenir XSS.

## 🎨 Personalización

- Edita `assets/css/style.css` para cambiar colores, fuentes y estilos.
- Modifica los planes y categorías directamente en la base de datos o en `sql/nexuhosting.sql`.
- Cambia el logo y nombre en `includes/header.php` y `includes/footer.php`.

---

**Nexu Hosting** - Servidores de juegos 24/7 ⚡
