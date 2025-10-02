# SECM Gestión de Alquileres

![Logo SECM](assets/image/logo.png)

Sistema web para la administración integral de alquileres de propiedades, desarrollado en PHP con una arquitectura moderna y segura. Permite gestionar inquilinos, propiedades, contratos, cobros y usuarios del sistema de manera eficiente.

---

## 🚀 Características Principales

- **Dashboard Interactivo:** Panel de control con estadísticas clave y accesos directos a los módulos principales.
- **Gestión de Inquilinos (CRUD):**
  - Creación, edición y visualización de inquilinos.
  - Sistema de estados (Activo, Inactivo, Eliminado).
  - Búsqueda y filtros dinámicos.
- **Gestión de Propiedades (CRUD):**
  - Administración completa de propiedades con estados (Activa, Inactiva).
  - Filtros y búsqueda avanzada.
- **Gestión de Contratos:**
  - Creación de contratos asociando inquilinos y propiedades.
  - Cálculo automático de fecha de finalización.
  - Administración de garantes.
  - Valores de alquiler flexibles por período.
- **Gestión de Cobros:**
  - Registro de cobros mensuales (alquiler, luz, agua, ABL, mantenimiento).
  - Cambio de estado de los cobros (Pendiente, Pagado, Vencido, Anulado).
  - Edición de cobros registrados.
  - Cálculo automático de totales.
- **Generador de Recibos:**
  - Creación de recibos de pago en formato imprimible.
  - Conversión automática de números a letras.
- **Sistema de Usuarios y Roles:**
  - Roles de `Administrador` y `Usuario`.
  - Panel de administración de usuarios exclusivo para administradores.
- **Seguridad:**
  - Sistema de autenticación con sesiones seguras y CAPTCHA.
  - Protección contra inyección SQL mediante consultas preparadas (PDO).
  - Lógica de negocio para prevenir acciones no permitidas (ej. eliminar inquilino con contrato activo).

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 8+
- **Base de Datos:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Arquitectura:** API REST para operaciones CRUD
- **Patrón:** MVC simplificado

---

## 📋 Requisitos Previos

- PHP 8.0 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web (Apache/Nginx)
- Extensión PHP GD (para CAPTCHA)
- Extensión PHP PDO MySQL

---

## ⚙️ Instalación y Puesta en Marcha

Sigue estos pasos para instalar y ejecutar el proyecto en un entorno local como Laragon, XAMPP o WAMP.

### 1. Clonar el Repositorio
```bash
git clone https://github.com/sergioecm60/secmalquileres.git
cd secmalquileres
```
### 2. Configurar la Base de Datos

Abre phpMyAdmin o tu cliente de base de datos preferido.
Ve a la pestaña Importar.
Selecciona el archivo script_db/install.sql que se encuentra en el proyecto.
Ejecuta la importación. Esto creará la base de datos gestion_alquileres con todas las tablas y datos de ejemplo.

### 3. Configurar la Conexión
Importante: El archivo config.php está ignorado por Git por seguridad. Deberás crearlo manualmente en la raíz del proyecto.
Crea un archivo llamado config.php en la raíz del proyecto y pega el siguiente contenido, ajustando las credenciales si es necesario:
```php
<?php
// config.php - Archivo de configuración
$host = 'localhost';
$db   = 'gestion_alquileres';
$user = 'root'; // Usuario por defecto en Laragon/XAMPP
$pass = '';     // Contraseña por defecto en Laragon/XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Funciones auxiliares
function formatearMoneda($monto) { 
    return '$' . number_format($monto, 2, ',', '.'); 
}

function formatearFecha($fecha) { 
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '-'; 
}

// Función para verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Función para obtener el nombre del usuario
function obtenerNombreUsuario() {
    return isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $_SESSION['username'];
}

// Meses en español
$meses_es = [
    1=>'enero', 2=>'febrero', 3=>'marzo', 4=>'abril', 5=>'mayo', 6=>'junio',
    7=>'julio', 8=>'agosto', 9=>'septiembre', 10=>'octubre', 11=>'noviembre', 12=>'diciembre'
];
?>
```
### 4. Acceder al Sistema

Abre tu navegador y ve a la URL de tu proyecto local:

http://localhost/secmalquileres (XAMPP/WAMP)
http://secmalquileres.test (Laragon con Virtual Hosts)


Serás redirigido a la página de login.

### 5. Credenciales de Acceso
Usuario Administrador por defecto:

Usuario: admin
Contraseña: admin123

IMPORTANTE: Cambia estas credenciales después del primer acceso.

### 📁 Estructura del Proyecto
secmalquileres/
├── api/                      # APIs REST para operaciones CRUD
│   ├── api_inquilinos.php
│   ├── api_propiedades.php
│   ├── api_contratos.php
│   └── api_cobros.php
├── assets/
│   ├── css/                  # Estilos
│   └── image/                # Imágenes y logo
├── script_db/
│   └── install.sql           # Script de instalación de BD
├── check_auth.php            # Verificación de autenticación
├── config.php                # Configuración de BD (crear manualmente)
├── index.php                 # Dashboard principal
├── login.php                 # Página de inicio de sesión
├── logout.php                # Cerrar sesión
├── form_inquilinos.php       # CRUD de inquilinos
├── form_propiedades.php      # CRUD de propiedades
├── form_contratos.php        # CRUD de contratos
├── form_cobros.php           # CRUD de cobros
├── form_users.php            # Gestión de usuarios (admin)
├── generar_recibo.php        # Generador de recibos
├── licence.php               # Información de licencia
├── licence.txt               # Texto completo GNU GPL v3
└── README.md                 # Este archivo

### 🔧 Funcionalidades Detalladas
Gestión de Inquilinos

Crear, editar, desactivar y eliminar inquilinos
Estados: Activo, Inactivo, Eliminado (soft delete)
Validación de DNI único
Filtros por estado y búsqueda

Gestión de Propiedades

CRUD completo de propiedades
Campos: código, dirección, departamento, localidad
Validación de contratos activos antes de eliminar

Gestión de Contratos

Asociación inquilino-propiedad
Garantes opcionales
Períodos de alquiler flexibles
Cálculo automático de fecha fin
Estados: Activo, Inactivo

Gestión de Cobros

Selección por inquilino (no por contrato)
Múltiples conceptos: alquiler, luz, agua, ABL, mantenimiento
Estados: Pendiente, Pagado, Vencido, Anulado
Edición de montos y fechas

Generación de Recibos

Selección de cobro pagado
Conversión automática de números a letras
Desglose detallado de conceptos
Formato imprimible


### 🔒 Seguridad

Autenticación: Sistema de sesiones con timeout de 30 minutos
CAPTCHA: Protección contra bots en el login
SQL Injection: Uso exclusivo de prepared statements (PDO)
XSS: Sanitización con htmlspecialchars()
CSRF: Validación de sesión en todas las operaciones
Roles: Separación de permisos admin/usuario


### 🐛 Solución de Problemas
CAPTCHA no se muestra

Verifica que la extensión GD esté habilitada en PHP
En php.ini, busca ;extension=gd y quita el punto y coma
Reinicia el servidor web

Error de conexión a la base de datos

Verifica las credenciales en config.php
Asegúrate de que MySQL esté corriendo
Confirma que la base de datos gestion_alquileres existe

Sesión expira muy rápido
Ajusta el timeout en check_auth.php:
```php
$timeout_duration = 1800; // 30 minutos (modifica según necesites)
```

### 📝 Próximas Mejoras

 Exportación de reportes a PDF/Excel
 Sistema de notificaciones por email
 Alertas de vencimiento de contratos
 Dashboard con gráficos estadísticos
 Backup automático de base de datos
 Módulo de gastos y mantenimiento
 API REST documentada (Swagger)
 Responsive design mejorado


### 🤝 Contribuciones
Las contribuciones son bienvenidas. Por favor:

Fork el proyecto
Crea una rama para tu feature (git checkout -b feature/NuevaCaracteristica)
Commit tus cambios (git commit -m 'Agregar nueva característica')
Push a la rama (git push origin feature/NuevaCaracteristica)
Abre un Pull Request

---

## 📜 Licencia

Este proyecto se distribuye bajo la licencia GNU General Public License v3.0 (GPL-3.0).
Eres libre de:

✅ Usar el software para cualquier propósito
✅ Estudiar cómo funciona y adaptarlo
✅ Distribuir copias
✅ Mejorar el software y publicar tus mejoras

Bajo las condiciones de:

📋 Divulgar el código fuente
📋 Mantener la misma licencia en trabajos derivados
📋 Documentar los cambios realizados

Para más detalles, consulta el archivo licence.txt o visita GNU GPL v3.
Copyleft © 2025 Sergio Cabrera Miers

---

## 👨‍💻 Autor y Contacto

**Sergio Cabrera**
¿Necesitas soporte, personalización o consultoría?

📧 Email: sergiomiers@gmail.com
💬 WhatsApp: +54 11 6759-8452
🌐 GitHub: @sergioecm60


⭐ Agradecimientos
Si este proyecto te fue útil, considera darle una estrella en GitHub. ¡Tu apoyo es muy apreciado!

Hecho con ❤️ en Argentina