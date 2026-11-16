# SIGEL

Sistema de Gestión de Préstamo de Equipos de Laboratorio. Proyecto 1 de ISW-613 (UTN).

## Requisitos

- XAMPP con PHP 8 (Apache)
- Docker Desktop

## Instalación

1. Clonar el repositorio dentro de `C:\xampp\htdocs`:

```
git clone https://github.com/alejandrosandi22/ISW-613-Proyecto1-SIGEL.git sigel
cd sigel
```

2. Crear los archivos de configuración y cambiar las claves:

```
Copy-Item .env.example .env
Copy-Item config\config.example.php config\config.php
```

La clave de `DB_APP_PASSWORD` en `.env` debe ser igual a `password` en `config/config.php`.

3. Levantar la base de datos:

```
docker compose up -d
```

4. Iniciar Apache en XAMPP.

## Acceso

| Servicio | Dirección |
|---|---|
| Aplicación | http://127.0.0.1/sigel |
| phpMyAdmin | http://localhost:8081 (usuario `root`, clave `DB_ROOT_PASSWORD` de `.env`) |

Para recrear la base de datos desde cero: `docker compose down -v` y luego `docker compose up -d`.

## Virtual host (opcional)

Agregar en `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"
</VirtualHost>

<VirtualHost *:80>
    ServerName sigel.local
    DocumentRoot "C:/xampp/htdocs/sigel/public"
    <Directory "C:/xampp/htdocs/sigel/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Agregar `127.0.0.1 sigel.local` en `C:\Windows\System32\drivers\etc\hosts`, reiniciar Apache y abrir http://sigel.local.

## Credenciales de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | — | — |
| Operador | — | — |

## Estructura

| Carpeta | Contenido |
|---|---|
| `public/` | Único directorio accesible desde la web |
| `includes/` | Funciones reutilizables |
| `config/` | Configuración (el archivo real no se versiona) |
| `sql/` | `schema.sql` y `seed.sql` |
| `storage/` | Imágenes subidas y logs |
| `docker/` | Inicialización de la base de datos |
