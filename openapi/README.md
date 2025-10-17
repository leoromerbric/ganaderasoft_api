# GanaderaSoft API OpenAPI Specification

Este directorio contiene la especificación OpenAPI 3.0 de la API de GanaderaSoft.

## Archivos

- `ganaderasoft-api-v1.yaml` - Especificación OpenAPI 3.0 completa

## ¿Qué incluye?

La especificación incluye:

### Autenticación
- POST `/auth/register` - Registro de usuarios
- POST `/auth/login` - Inicio de sesión
- POST `/auth/logout` - Cierre de sesión
- GET `/profile` - Perfil del usuario

### Composición Raza
- GET `/composicion-raza` - Listar todas las composiciones de raza (sin filtros por fk_tipo_animal_id o fk_id_Finca)
- POST `/composicion-raza` - Crear composición de raza
- GET `/composicion-raza/{id}` - Obtener composición de raza específica
- PUT `/composicion-raza/{id}` - Actualizar composición de raza
- DELETE `/composicion-raza/{id}` - Eliminar composición de raza

### Configuración (APIs basadas en JSON)
- GET `/configuracion/tipo-explotacion` - Tipos de explotación
- GET `/configuracion/metodo-riego` - Métodos de riego
- GET `/configuracion/ph-suelo` - Valores de pH del suelo
- GET `/configuracion/textura-suelo` - Texturas de suelo
- GET `/configuracion/fuente-agua` - Fuentes de agua
- GET `/configuracion/sexo` - Opciones de sexo
- GET `/configuracion/tipo-relieve` - **NUEVO** Tipos de relieve (Plano, Ondulado, Montañoso, Otro)

## Cambios implementados

### 1. API de ComposicionRaza ajustada
- Removido el filtrado por `fk_tipo_animal_id` y `fk_id_Finca` en el método de listado
- Ahora retorna todos los registros sin considerar estos campos

### 2. Nueva API de Tipo Relieve
- Endpoint: `GET /api/configuracion/tipo-relieve`
- Valores disponibles: Plano, Ondulado, Montañoso, Otro
- Datos almacenados en archivo JSON constante

## Cómo usar la especificación

### 1. Visualización
Puedes importar el archivo YAML en herramientas como:
- Swagger UI
- Postman
- Insomnia
- OpenAPI Generator

### 2. Generación de código
Usa OpenAPI Generator para generar clientes en diferentes lenguajes:
```bash
openapi-generator-cli generate -i ganaderasoft-api-v1.yaml -g javascript -o client-js
```

### 3. Documentación
Usa Swagger UI para generar documentación interactiva:
```bash
swagger-ui-serve ganaderasoft-api-v1.yaml
```

## Esquemas de datos

La especificación incluye esquemas completos para:
- Requests de autenticación
- Modelos de ComposicionRaza
- Respuestas de configuración
- Respuestas de error y validación

## Autenticación

Todos los endpoints protegidos requieren un token Bearer en el header Authorization:
```
Authorization: Bearer {token}
```

El token se obtiene mediante los endpoints de login o registro.

## Tipos de usuario

- `admin`: Acceso completo a todos los recursos
- `propietario`: Puede gestionar sus propios recursos
- `tecnico`: Acceso limitado para soporte técnico