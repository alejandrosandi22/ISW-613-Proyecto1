# SIGEL — Sistema de Gestión de Préstamo de Equipos de Laboratorio

Proyecto 1 del curso ISW-613 Programación en Ambiente Web I (UTN).
Aplicación web en PHP sin framework para gestionar el inventario y el préstamo
de equipos de un laboratorio universitario.

## Requisitos

- PHP 8.x con las extensiones `pdo_mysql` y `fileinfo` habilitadas
- MySQL 8 o MariaDB 10.x (motor InnoDB)
- Apache 2.4 (XAMPP)
- Git

## Estructura del proyecto

| Carpeta | Descripción |
|---|---|
| `config/` | Configuración y credenciales. Fuera del directorio público; el archivo real no se versiona |
| `docs/` | Documentación de decisiones de diseño y modelo de datos |
| `includes/` | Funciones reutilizables: conexión, autenticación, CSRF, validación y utilidades |
| `includes/templates/` | Plantillas compartidas (encabezado, menú y pie) |
| `public/` | Único directorio expuesto por Apache (`DocumentRoot`) |
| `public/pages/` | Páginas que responden a peticiones GET |
| `public/actions/` | Acciones que procesan formularios POST y redirigen (patrón PRG) |
| `public/assets/css/` | Hoja de estilos |
| `sql/` | Scripts `schema.sql` y `seed.sql` |
| `storage/uploads/` | Imágenes de equipos subidas (sin acceso directo desde la web) |
| `storage/logs/` | Registro de errores de la aplicación |

## Instalación y despliegue local (XAMPP en Windows)

### 1. Clonar el repositorio

```
cd C:\xampp\htdocs
git clone https://github.com/alejandrosandi22/ISW-613-Proyecto1-SIGEL.git sigel
```

### 2. Configurar el virtual host

1. Verificar que en `C:\xampp\apache\conf\httpd.conf` esté activa la línea
   `Include conf/extra/httpd-vhosts.conf`.
2. Agregar al final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"
</VirtualHost>

<VirtualHost *:80>
    ServerName sigel.local
    DocumentRoot "C:/xampp/htdocs/sigel/public"
    <Directory "C:/xampp/htdocs/sigel/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/sigel-error.log"
</VirtualHost>
```

3. Editar como administrador `C:\Windows\System32\drivers\etc\hosts` y agregar:

```
127.0.0.1    sigel.local
```

4. Reiniciar Apache desde el panel de XAMPP y abrir `http://sigel.local`.

### 3. Configuración de la aplicación

_Pendiente: se documenta junto con `config/config.example.php`._

### 4. Base de datos

_Pendiente: importar `sql/schema.sql` y luego `sql/seed.sql`._

## Credenciales de prueba

_Pendiente: se agregan con `seed.sql`._

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | — | — |
| Operador | — | — |

## Mapeo de nombres español ↔ inglés

La base de datos y el código usan nombres en inglés; la interfaz está en español.
_Mapeo provisional: se confirma al escribir `schema.sql`._

| Concepto (español) | Tabla (inglés) |
|---|---|
| Rol | `roles` |
| Usuario | `users` |
| Token de autenticación («Recordarme») | `auth_tokens` |
| Categoría | `categories` |
| Equipo | `equipment` |
| Imagen de equipo | `equipment_images` |
| Préstamo | `loans` |
| Detalle de préstamo | `loan_items` |
| Bitácora | `audit_log` |
