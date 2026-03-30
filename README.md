# GanaderaSoft API

**Sistema de Gestión Ganadera - API Gateway**

GanaderaSoft API es una aplicación REST API desarrollada en Laravel 10.x para la gestión integral de operaciones ganaderas, enfocada principalmente en el manejo de ganado búfalo y otras especies pecuarias.

## 📋 Descripción General

Esta API proporciona endpoints para la gestión completa de:
- Propietarios y fincas ganaderas
- Inventario y registro de animales
- Control de peso y medidas corporales
- Gestión de lactancia y producción lechera
- Estados de salud y seguimiento veterinario
- Configuraciones del sistema ganadero

## 🎯 Características Principales

- **Autenticación**: Sistema de autenticación basado en Laravel Sanctum
- **API RESTful**: Endpoints completamente RESTful para todas las operaciones
- **Documentación**: Especificación OpenAPI disponible
- **Testing**: Suite de pruebas unitarias incluida
- **Configuración**: Datos constantes JSON para configuraciones del sistema

## 📁 Estructura de Directorios

### **Directorios Principales**

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

### **app/** - Lógica de la Aplicación
- **Console/**: Comandos de consola Artisan
- **Exceptions/**: Manejadores de excepciones personalizados
- **Http/**: Controladores, middleware y kernels HTTP
  - **Controllers/Api/**: Controladores específicos de la API
  - **Middleware/**: Middleware personalizado
- **Models/**: Modelos Eloquent (entidades de base de datos)
- **Providers/**: Proveedores de servicios de Laravel

### **database/** - Base de Datos
- **factories/**: Factories para generar datos de prueba
- **migrations/**: Migraciones de base de datos
- **seeders/**: Seeders para poblar la base de datos

### **docs/** - Documentación
- **postman-collections/**: Colecciones de Postman para testing

### **openapi/** - Especificación API
- **ganaderasoft-api-v1.yaml**: Documentación OpenAPI completa

### **resources/** - Recursos de Aplicación
- **datos-constantes/**: Archivos JSON con datos de configuración
  - Tipos de explotación, métodos de riego, texturas de suelo, etc.

### **routes/** - Rutas de la Aplicación
- **api.php**: Rutas de la API REST
- **web.php**: Rutas web limitadas (básicamente endpoint de health)

### **storage/** - Almacenamiento
- **app/**: Archivos de aplicación
- **logs/**: Logs de la aplicación

### **tests/** - Pruebas
- **Feature/**: Pruebas de funcionalidad integral
- **Unit/**: Pruebas unitarias específicas

## 🔗 Endpoints Principales

### Autenticación
- `POST /api/auth/register` - Registro de usuarios
- `POST /api/auth/login` - Inicio de sesión
- `GET /api/profile` - Perfil del usuario
- `POST /api/auth/logout` - Cerrar sesión

### Gestión de Entidades
- **Fincas**: `/api/fincas`
- **Propietarios**: `/api/propietarios`
- **Rebaños**: `/api/rebanos`
- **Animales**: `/api/animales`
- **Inventario Búfalo**: `/api/inventarios-bufalo`
- **Tipos de Animal**: `/api/tipos-animal`
- **Estados de Salud**: `/api/estados-salud`
- **Etapas**: `/api/etapas`
- **Personal de Finca**: `/api/personal-finca`

### Seguimiento y Control
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

## 🛠 Tecnologías Utilizadas

- **Framework**: Laravel 10.x
- **PHP**: ^8.1
- **Autenticación**: Laravel Sanctum
- **Base de Datos**: Compatible con MySQL/PostgreSQL
- **Testing**: PHPUnit
- **Documentación**: OpenAPI/Swagger

## 📦 Dependencias Principales

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

## 🚀 Configuración e Instalación

1. Clonar el repositorio
2. Instalar dependencias: `composer install`
3. Configurar archivo `.env` 
4. Generar clave de aplicación: `php artisan key:generate`
5. Ejecutar migraciones: `php artisan migrate`
6. Iniciar servidor: `php artisan serve`

## 📚 Documentación Adicional

- **Postman Collection**: Disponible en `docs/postman-collections/`
- **OpenAPI Spec**: Ver `openapi/ganaderasoft-api-v1.yaml`
- **Environment Variables**: Configurar según `.env.example`

## 🔧 Comandos Útiles

```bash
# Ejecutar pruebas
php artisan test

# Limpiar caché
php artisan cache:clear

# Ver rutas disponibles
php artisan route:list

# Generar documentación API
php artisan l5-swagger:generate
```

## 📝 Notas Importantes

- Todos los endpoints de la API requieren autenticación excepto login/register
- Los datos de configuración se almacenan como archivos JSON estáticos
- La aplicación está optimizada para gestión de ganado búfalo pero es extensible
- Se incluyen relaciones complejas entre entidades para seguimiento completo

---

**Versión**: 1.0.0  
**Licencia**: MIT  
**Framework**: Laravel 10.x  
**PHP Version**: ^8.1