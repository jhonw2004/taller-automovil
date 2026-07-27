# Taller Automóviles — Plataforma de Gestión para Talleres Mecánicos

Marketplace público + mini-ERP multi-tenant para talleres mecánicos en Santa Cruz, Bolivia. Construido con Laravel, Filament, PostgreSQL + PostGIS, Tailwind CSS y Alpine.js.

## Requisitos

- PHP 8.4+
- Composer
- Node.js 20+
- PostgreSQL 16+ con extensión **PostGIS**
- npm

## Instalación

```bash
# Clonar el repositorio
git clone <repo-url>
cd taller-automoviles

# Instalar dependencias PHP
composer install

# Instalar dependencias frontend
npm install

# Copiar y configurar variables de entorno
cp .env.example .env
# Editar .env con datos de conexión a PostgreSQL (ver sección .env abajo)

# Generar APP_KEY
php artisan key:generate

# Ejecutar migraciones y seeders
php artisan migrate --seed
```

### Configuración de `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taller_automoviles
DB_USERNAME=postgres
DB_PASSWORD=tu_password

# Opcional: base de datos para tests
DB_DATABASE_TEST=taller_test
```

Para ejecutar tests se necesita una base PostgreSQL separada (`taller_test`). Configurar en `.env.testing`:

```env
DB_CONNECTION=pgsql
DB_DATABASE=taller_test
```

### Compilar assets frontend

```bash
npm run build
```

## Ejecutar

```bash
# Servidor de desarrollo
php artisan serve

# Compilar assets en modo watch (para desarrollo frontend)
npm run dev
```

### Credenciales iniciales

Al ejecutar `php artisan migrate --seed` se crea un usuario Super Admin con credenciales generadas aleatoriamente. Buscar en la salida del comando:

```
Username: superadmin
Password: <generada-aleatoriamente>
```

Si se pierde la contraseña, resetear con:

```bash
php artisan tinker
```

```php
$u = App\Models\UsuarioSistema::where('username', 'superadmin')->first();
$c = $u->credencialSistema;
$c->password_hash = bcrypt('nueva-password');
$c->debe_cambiar_password = true;
$c->save();
```

## Paneles

| Panel | URL | Acceso |
|---|---|---|
| Super Admin | `/admin/login` | Username `superadmin` + password generada |
| ERP del taller | `/erp/login` | Usuarios sistema con asignación vigente |

## Tests

```bash
# Ejecutar toda la suite
php artisan test

# Solo tests de una feature
php artisan test tests/Feature/NombreFeature
```

## Stack

| Componente | Tecnología |
|---|---|
| Backend | Laravel 13, PHP 8.4+ |
| Base de datos | PostgreSQL + PostGIS |
| Paneles admin | Filament v5 |
| Frontend marketplace | Blade + Tailwind CSS + Alpine.js |
| Autenticación | Google OAuth (marketplace), username/password (ERP) |
