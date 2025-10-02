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
- **Gestión de Contratos:**
  - Creación de contratos asociando inquilinos y propiedades.
  - Cálculo automático de fecha de finalización.
  - Administración de garantes.
- **Gestión de Cobros:**
  - Registro de cobros mensuales (alquiler, servicios, etc.).
  - Cambio de estado de los cobros (Pendiente, Pagado, Vencido, Anulado).
  - Edición de cobros registrados.
- **Generador de Recibos:**
  - Creación de recibos de pago en formato PDF listos para imprimir.
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
- **Estilo de Código:** Orientado a API para interacciones dinámicas (CRUD en modales).

---

## ⚙️ Instalación y Puesta en Marcha

Sigue estos pasos para instalar y ejecutar el proyecto en un entorno local como Laragon, XAMPP o WAMP.

1.  **Clonar el Repositorio:**
    ```bash
    git clone https://github.com/sergioecm60/secmalquileres.git
    cd secmalquileres
    ```

2.  **Configurar la Base de Datos:**
    - Abre **phpMyAdmin** o tu cliente de base de datos preferido.
    - Ve a la pestaña **Importar**.
    - Selecciona el archivo `script_db/install.sql` que se encuentra en el proyecto.
    - Ejecuta la importación. Esto creará la base de datos `gestion_alquileres` con todas las tablas y datos de ejemplo.

3.  **Configurar la Conexión:**
    - **Importante:** El archivo `config.php` está ignorado por Git por seguridad. Deberás crearlo manualmente en la raíz del proyecto.
    - Crea un archivo llamado `config.php` y pega el siguiente contenido, ajustando las credenciales si es necesario:

      ```php
      <?php
      // config.php - Archivo de configuración
      $host = 'localhost';
      $db   = 'gestion_alquileres';
      $user = 'root'; // Usuario por defecto en Laragon/XAMPP
      $pass = '';      // Contraseña por defecto en Laragon/XAMPP

      try {
          $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
          die("Error de conexión a la base de datos: " . $e->getMessage());
      }

      // Funciones auxiliares
      function formatearMoneda($monto) { return '$' . number_format($monto, 2, ',', '.'); }
      function formatearFecha($fecha) { return $fecha ? date('d/m/Y', strtotime($fecha)) : '-'; }

      $meses_es = [
          1=>'enero', 2=>'febrero', 3=>'marzo', 4=>'abril', 5=>'mayo', 6=>'junio',
          7=>'julio', 8=>'agosto', 9=>'septiembre', 10=>'octubre', 11=>'noviembre', 12=>'diciembre'
      ];
      ?>
      ```

4.  **Acceder al Sistema:**
    - Abre tu navegador y ve a la URL de tu proyecto local (ej. `http://secmalquileres.test` o `http://localhost/secmalquileres`).
    - Serás redirigido a la página de login.

5.  **Credenciales de Acceso:**
    - **Usuario:** `admin`
    - **Contraseña:** `admin123`

---

## 📜 Licencia

Este proyecto se distribuye bajo la licencia **GNU General Public License v3.0**. Eres libre de usar, modificar y distribuir este software. Para más detalles, consulta el archivo `licence.txt`.

---

## 👨‍💻 Autor y Contacto

**Sergio Cabrera**
- **Email:** <a href="mailto:sergiomiers@gmail.com">sergiomiers@gmail.com</a>
- **WhatsApp:** <a href="https://wa.me/541167598452" target="_blank">+54 11 6759-8452</a>