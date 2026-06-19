# Panel de Administración - Emergencias Huamancaca Chico

Panel de administración para el sistema de emergencias de la Municipalidad Distrital de Huamancaca Chico.

## Requisitos

- PHP 8.0+
- MySQL / MariaDB
- Servidor web (Apache con mod_rewrite, o PHP built-in server)

## Instalación

### 1. Base de datos

```bash
mysql -u root -p < database.sql
```

Esto crea la base de datos `boton_emergencia`, las tablas `emergencias` y `usuarios`, y un usuario admin por defecto.

### 2. Configurar conexión

Edita `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'boton_emergencia');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
```

### 3. Iniciar el servidor

**Opción A — PHP built-in server:**

```bash
php -S localhost:8000
```

**Opción B — Apache:**

Apunta el DocumentRoot a la raíz del proyecto.

### 4. Acceder

```
http://localhost:8000/admin/
```

**Usuario por defecto:** `admin` / `password`

## Estructura

```
├── admin/
│   ├── index.php       # Login
│   ├── dashboard.php   # Panel con mapa y lista de emergencias
│   └── logout.php
├── api/
│   ├── emergencia.php  # Endpoint para crear emergencias (POST)
│   ├── listar.php      # Endpoint para listar emergencias (GET)
│   └── marcar_leido.php# Marcar emergencia como leída (POST)
├── config/
│   └── database.php    # Conexión a la base de datos
├── assets/
│   └── css/
│       └── style.css
├── database.sql        # Esquema de la base de datos
└── .htaccess           # Reglas de reescritura
```

## API

Las APIs en `api/` están diseñadas para ser consumidas desde la aplicación móvil:

- `POST /api/emergencia.php` — Reportar una emergencia
- `GET /api/listar.php?token=...` — Listar emergencias (requiere token)
- `POST /api/marcar_leido.php` — Marcar emergencia como leída (requiere sesión admin)
