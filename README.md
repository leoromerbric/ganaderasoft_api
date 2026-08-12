# GanaderaSoft API

**Sistema de Gestión Ganadera - API Gateway**

GanaderaSoft API es una aplicación REST API desarrollada en Laravel 10.x para la gestión integral de operaciones ganaderas, enfocada principalmente en el manejo de ganado búfalo y otras especies pecuarias.

## 📋 Descripción general

Esta API proporciona endpoints para la gestión completa de:
- Propietarios y fincas ganaderas
- Inventario y registro de animales
- Control de peso y medidas corporales
- Gestión de lactancia y producción lechera
- Estados de salud y seguimiento veterinario
- Configuraciones del sistema ganadero

## 🎯 Características principales

- **Autenticación**: Sistema de autenticación basado en Laravel Sanctum
- **API RESTful**: Endpoints completamente RESTful para todas las operaciones
- **Documentación**: Especificación OpenAPI disponible
- **Testing**: Suite de pruebas unitarias incluida
- **Configuración**: Datos constantes JSON para configuraciones del sistema

## 📁 Estructura de directorios

### **Directorios principales**

```
ganaderasoft_api/
├── app/                    # Código de la aplicación Laravel
├── bootstrap/              # Archivos de arranque de Laravel
├── config/                 # Archivos de configuración
├── database/              # Migraciones, factories y seeders
├── docs/                  # Documentación del proyecto
├── openapi/               # Especificación OpenAPI de la API
├── public/                # Punto de entrada público
├── resources/             # Recursos de la aplicación
├── routes/                # Definición de rutas
├── storage/               # Archivos de almacenamiento
└── tests/                 # Pruebas unitarias y de integración
```

### **app/** - Lógica de la aplicación
- **Console/**: Comandos de consola Artisan
- **Exceptions/**: Manejadores de excepciones personalizados
- **Http/**: Controladores, middleware y kernels HTTP
  - **Controllers/Api/**: Controladores específicos de la API
  - **Middleware/**: Middleware personalizado
- **Models/**: Modelos Eloquent (entidades de base de datos)
- **Providers/**: Proveedores de servicios de Laravel

### **database/** - Base de datos
- **factories/**: Factories para generar datos de prueba
- **migrations/**: Migraciones de base de datos
- **seeders/**: Seeders para poblar la base de datos

### **docs/** - Documentación
- **postman-collections/**: Colecciones de Postman para testing

### **openapi/** - Especificación API
- **ganaderasoft-api-v1.yaml**: Documentación OpenAPI completa

### **resources/** - Recursos de aplicación
- **datos-constantes/**: Archivos JSON con datos de configuración
  - Tipos de explotación, métodos de riego, texturas de suelo, etc.

### **routes/** - Rutas de la aplicación
- **api.php**: Rutas de la API REST
- **web.php**: Rutas web limitadas (básicamente endpoint de health)

### **storage/** - Almacenamiento
- **app/**: Archivos de aplicación
- **logs/**: Logs de la aplicación

### **tests/** - Pruebas
- **Feature/**: Pruebas de funcionalidad integral
- **Unit/**: Pruebas unitarias específicas

## 🔗 Endpoints principales

### Autenticación
- `POST /api/auth/register` - Registro de usuarios
- `POST /api/auth/login` - Inicio de sesión
- `GET /api/profile` - Perfil del usuario
- `POST /api/auth/logout` - Cerrar sesión

### Gestión de entidades
- **Fincas**: `/api/fincas`
- **Propietarios**: `/api/propietarios`
- **Rebaños**: `/api/rebanos`
- **Animales**: `/api/animales`
- **Inventario Búfalo**: `/api/inventarios-bufalo`
- **Tipos de Animal**: `/api/tipos-animal`
- **Estados de Salud**: `/api/estados-salud`
- **Etapas**: `/api/etapas`
- **Personal de Finca**: `/api/personal-finca`

### Seguimiento y control
- **Peso Corporal**: `/api/peso-corporal`
- **Medidas Corporales**: `/api/medidas-corporales`
- **Lactancia**: `/api/lactancia`
- **Producción de Leche**: `/api/leche`
- **Cambios de Animal**: `/api/cambios-animal`

### Configuración
- `/api/configuracion/tipo-explotacion`
- `/api/configuracion/metodo-riego`
- `/api/configuracion/ph-suelo`
- `/api/configuracion/textura-suelo`
- `/api/configuracion/fuente-agua`
- `/api/configuracion/sexo`
- `/api/configuracion/tipo-relieve`

## 🛠 Tecnologías utilizadas

- **Framework**: Laravel 10.x
- **PHP**: ^8.1
- **Autenticación**: Laravel Sanctum
- **Base de Datos**: Compatible con MySQL/PostgreSQL
- **Testing**: PHPUnit
- **Documentación**: OpenAPI/Swagger

## 📦 Dependencias principales

### Producción
- `laravel/framework`: ^10.0
- `laravel/sanctum`: ^3.2
- `guzzlehttp/guzzle`: ^7.2
- `laravel/tinker`: ^2.8

### Desarrollo
- `phpunit/phpunit`: ^10.1
- `laravel/sail`: ^1.18
- `laravel/pint`: ^1.0
- `spatie/laravel-ignition`: ^2.0

## 🚀 Pasos para desarrollo local

> [!IMPORTANT]
> Para el entorno de desarrollo local, es indispensable el uso de **Docker Compose** para la orquestación de los servicios.
> En caso de usar Windows, para garantizar la compatibilidad de los volúmenes y el rendimiento de los contenedores, es obligatorio ejecutar este proyecto utilizando **WSL2** (Windows Subsystem for Linux) integrado con **Docker Desktop**. Evite ejecutar los comandos directamente sobre PowerShell o CMD si no es a través de una terminal de WSL.

### 1. Estructura de archivos
Para comenzar, debe configurar la siguiente estructura de directorios en su entorno local dentro de una carpeta raíz (por ejemplo, `GanaderasoftPro/`):

```text
GanaderasoftPro/
├── backend/                  # Repositorio del API (Laravel)
├── frontend/                 # Repositorio de la interfaz (Laravel + Vue/Blade)
├── docker-compose.yml        # Orquestador de servicios (Desarrollo)
└── docker-compose.prod.yml   # Orquestador de servicios (Producción)
```
> [!NOTE]
> Tanto el código del `backend` como del `frontend` corresponden a sub-proyectos. Debe clonarlos o mantenerlos dentro de la carpeta principal para que el orquestador pueda localizar los archivos de configuración y los Dockerfiles.

### 2. Configuración de variables de entorno

Debe solicitar al equipo de desarrollo los archivos `.env` correspondientes al entorno de desarrollo.
- El archivo `.env` del backend debe colocarse en `GanaderasoftPro/backend/.env`.
- El archivo `.env` del frontend debe colocarse en `GanaderasoftPro/frontend/.env`.

Alternativamente, puede copiar los archivos de ejemplo si están disponibles (`cp .env.dev .env`).

### 3. Orquestación con docker

En la raíz de la carpeta `GanaderasoftPro/`, asegúrese de tener (o crear) un archivo llamado `docker-compose.yml` preconfigurado que orquestará los servicios de desarrollo:

<details>
<summary><b>Ver contenido de <code>docker-compose.yml</code> (Desarrollo)</b></summary>

```yaml
services:
  # Base de datos compartida (usada principalmente en desarrollo)
  db:
    image: mysql:8.0
    container_name: ganaderasoft-db
    restart: unless-stopped
    env_file:
      - ./backend/.env
    ports:
      - "3306:3306"
    volumes:
      # Persistencia de datos de MySQL
      - db_data:/var/lib/mysql
    healthcheck:
      # Verifica que MySQL esté listo antes de arrancar el backend
      test: [ "CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot_password" ]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s
    networks:
      - ganaderasoft-network

  # Backend (API Laravel)
  ganaderasoft-backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: ganaderasoft-backend
    restart: unless-stopped
    ports:
      - "8001:80"
    env_file:
      - ./backend/.env
    depends_on:
      db:
        condition: service_healthy
    networks:
      - ganaderasoft-network
    volumes:
      # Monta el código local para desarrollo en tiempo real
      - ./backend:/var/www/html
      # Ignora la carpeta local vendor (se instala dentro del contenedor en el entrypoint)
      - /var/www/html/vendor

  # Frontend (Laravel + Vue/Blade con Vite)
  ganaderasoft-frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: ganaderasoft-frontend
    restart: unless-stopped
    ports:
      - "8000:80"
      - "5173:5173" # Puerto para Vite (HMR)
    env_file:
      - ./frontend/.env
    depends_on:
      - ganaderasoft-backend
    networks:
      - ganaderasoft-network
    volumes:
      # Monta el código local para desarrollo en tiempo real
      - ./frontend:/var/www/html
      # Ignora las carpetas locales para evitar conflictos de sistema operativo
      - /var/www/html/vendor
      - /var/www/html/node_modules

volumes:
  db_data:

networks:
  ganaderasoft-network:
    driver: bridge
```
</details>

Y de igual forma, para producción, asegúrese de tener el archivo `docker-compose.prod.yml`:

<details>
<summary><b>Ver contenido de <code>docker-compose.prod.yml</code> (Producción)</b></summary>

```yaml
services:
  # Backend de Producción
  ganaderasoft-backend:
    build:
      context: ./backend
      # Usa el Dockerfile optimizado sin dependencias de desarrollo
      dockerfile: Dockerfile.prod
    container_name: ganaderasoft-backend-prod
    restart: always
    ports:
      - "127.0.0.1:8001:80"
    extra_hosts:
      - "host.docker.internal:host-gateway"
    env_file:
      - ./backend/.env
    networks:
      - ganaderasoft-network

  # Frontend de Producción
  ganaderasoft-frontend:
    build:
      context: ./frontend
      # Usa el Dockerfile que compila Vite internamente
      dockerfile: Dockerfile.prod
    container_name: ganaderasoft-frontend-prod
    restart: always
    ports:
      - "127.0.0.1:8000:80"
    env_file:
      - ./frontend/.env
    depends_on:
      - ganaderasoft-backend
    networks:
      - ganaderasoft-network

networks:
  # Usa la misma red para que puedan conectarse a la BD de desarrollo si es necesario
  ganaderasoft-network:
    driver: bridge
```
</details>

### 4. Configuración de la base de datos
Para el entorno de desarrollo, el contenedor `ganaderasoft-db` de MySQL se encargará de proveer la base de datos con las siguientes credenciales configuradas por defecto:

| Credencial | Valor |
| :--- | :--- |
| **Servidor / Host** | ganaderasoft-db |
| **Puerto** | 3306 |
| **Base de Datos** | ganaderasoft |
| **Usuario** | ganaderasoft_user |
| **Contraseña** | ganaderasoft_pass |

> [!IMPORTANT]
> **Importación de datos y migraciones**: 
> Si es la primera vez que levanta el proyecto o si la base de datos está vacía, es indispensable ingresar al contenedor del backend y ejecutar las migraciones junto con los seeders (datos semilla) iniciales:
> ```bash
> docker compose exec ganaderasoft-backend bash
> php artisan migrate --seed
> ```
> *(Alternativamente, si cuenta con un archivo SQL de respaldo como `bd_ganadera_soft.sql`, puede restaurarlo directamente en el gestor de base de datos de su preferencia utilizando las credenciales provistas).*

### 5. Ejecución del entorno con docker compose

Una vez configurada la estructura de archivos y las variables de entorno, inicie la orquestación de los contenedores ejecutando el siguiente comando en la raíz del proyecto (`/GanaderasoftPro`):

```bash
docker compose up --build
```
> [!TIP]
> Use el flag `--build` la primera vez o cuando realice cambios en los archivos `Dockerfile` o `entrypoint.sh` para asegurar que las imágenes se actualicen correctamente. Si desea ejecutar los contenedores en segundo plano y dejar la terminal libre, añada el flag `-d`.

### 6. Ejecución del entorno de producción (opcional)
Si desea probar cómo se comportará la aplicación en el servidor real (sin mapeo de volúmenes locales y con assets compilados), utilice el orquestador de producción.

> [!WARNING]
> **Requisito de base de datos**: 
> El archivo `docker-compose.prod.yml` **no incluye un contenedor de base de datos** por defecto. Por lo tanto, antes de levantarlo debe asegurarse de que la base de datos de desarrollo esté corriendo (`docker compose up -d db`), o bien, haber configurado las credenciales de un servidor MySQL externo en los archivos `.env`.

> [!IMPORTANT]
> **Variables de entorno para producción**: 
> Antes de construir las imágenes de producción, abra los archivos `.env` del Frontend y del Backend y asegúrese de configurar:
> - `APP_ENV=production`
> - `APP_DEBUG=false`
> 
> De lo contrario, Laravel seguirá intentando comportarse como si estuviera en desarrollo.

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

## 📚 Documentación adicional

- **Colección de Postman**: Disponible en `docs/postman-collections/`
- **Especificación OpenAPI**: Ver `openapi/ganaderasoft-api-v1.yaml`
- **Variables de entorno**: Copiar y configurar `.env` a partir de `.env.dev` o `.env.example`

## 🔧 Comandos útiles

Dado que el entorno está containerizado, todos los comandos de Artisan deben ejecutarse dentro del contenedor del backend (`ganaderasoft-backend`):

```bash
# Entrar a la consola del contenedor
docker compose exec ganaderasoft-backend bash

# Ejecutar pruebas (una vez dentro del contenedor)
php artisan test

# Limpiar caché
php artisan cache:clear

# Ver rutas disponibles
php artisan route:list

# Generar documentación API
php artisan l5-swagger:generate
```

## 📝 Notas importantes

- Todos los endpoints de la API requieren autenticación excepto login/register
- Los datos de configuración se almacenan como archivos JSON estáticos
- La aplicación está optimizada para gestión de ganado búfalo pero es extensible
- Se incluyen relaciones complejas entre entidades para seguimiento completo

---

**Versión**: 1.0.0  
**Licencia**: MIT  
**Framework**: Laravel 10.x  
**PHP Version**: ^8.1