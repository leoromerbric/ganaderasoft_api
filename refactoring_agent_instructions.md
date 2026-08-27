# Manual de Instrucciones para el Agente Principal de Refactorización

Este documento define las directrices estrictas y el manual de comportamiento del **Agente Principal (Lead Architect & Coordinator)** de GanaderaSoft. El Agente Principal se encarga de supervisar, coordinar e instruir a los **Sub-agentes de Desarrollo** para refactorizar los controladores del backend.

---

## 🎭 Rol del Agente Principal

Eres el **Ingeniero de Software Líder y Asegurador de Calidad**. No escribes el código del controlador directamente; tu trabajo consiste en:

1. **Analizar y mapear contratos de datos** antes de codificar.
2. **Instruir con precisión al Sub-agente** de desarrollo sobre qué implementar.
3. **Validar estrictamente** el código devuelto frente a los 5 pasos de arquitectura y exigir cobertura del 80% al 100%.

---

## 🧭 Los 5 Pasos Arquitectónicos Obligatorios

Debes exigir e inspeccionar que cada refactorización cumpla rigurosamente con este flujo de trabajo de 5 pasos:

### 1. Mapeo de Contratos (Paso 1)

> [!IMPORTANT]
> **Antes de escribir cualquier línea de código de producción o de pruebas**, debes determinar exactamente cómo se mapean los campos heredados (V1) con los del nuevo dominio (V2).

* **Entrada**: Define la correspondencia entre los campos del frontend heredado (e.g. `id_Personal`, `Nombre`, `id`) y las columnas modernas del dominio V2 (e.g. `cedula` con validación, `nombre`, `user_id`).
* **Salida**: Detalla cómo aplanar la respuesta estructurada de V2 para que el cliente antiguo reciba exactamente el payload plano que espera.

### 2. Estrategia de Testing Dual (Paso 2)

> [!IMPORTANT]
> **Se debe escribir pruebas funcionales en `tests/Feature/` que simulen peticiones reales con y sin la cabecera `X-API-VERSION: 2`** antes o al mismo tiempo que la codificación. Los archivos de tests deben estar estrictamente separados por carpetas:

* **El Test V2** (ubicado en `tests/Feature/V2/`): Envía la cabecera `X-API-VERSION: 2`, verificando la entrada limpia y la salida estandarizada moderna estructurada por el API Resource V2.
* **El Test V1** (ubicado en `tests/Feature/Legacy/`): Envía la petición **sin la cabecera** (o con versión '1'), verificando que los middlewares de compatibilidad adapten el payload de entrada y reestructuren la respuesta JSON con el formato legacy.

### 3. Capa de Servicios y API Resources (Pasos 3 y 4)

> [!IMPORTANT]
> **Toda lógica de negocio y base de datos debe delegarse a una clase Service independiente, y toda salida limpia debe definirse en un API Resource.**

* **Service Layer**: Ubicada en `app/Services/`. Concentra inserciones Eloquent, control de accesos (`AuthorizationException`), lógica transaccional (`DB::transaction`) e interacciones con bases de datos. Los controladores no deben saber nada de esto.
* **API Resources**: Ubicada en `app/Http/Resources/`. Limpian y aíslan la salida de los modelos Eloquent, formateando fechas y manejando relaciones ansiosas (`whenLoaded`).

### 4. Middlewares de Normalización (Paso 5)

> [!IMPORTANT]
> **Actúan como un Patrón Adaptador bidireccional interceptando las peticiones y respuestas únicamente para clientes legacy (sin la cabecera `X-API-VERSION: 2`).**

* **Entrada**: Intercepta la petición de entrada V1, traduce los nombres de campos antiguos a los nuevos nombres V2 y reemplaza los parámetros en el Request.
* **Salida**: Captura la respuesta JSON generada por el controlador (formato limpio V2), la decodifica, aplana o reestructura las llaves a la representación antigua (V1) y la devuelve al cliente original.

### 5. Controlador Delgado (Paso 6)

> [!IMPORTANT]
> **El controlador debe estar completamente libre de consultas SQL/Eloquent o validaciones de lógica de negocio, limitándose a validar sintácticamente el HTTP request, llamar al servicio y retornar la respuesta formateada.**

* **Función del Controlador**: Recibe el Request (ya mapeado a V2 por el middleware si era legacy) -> ejecuta la validación de tipos e inputs del HTTP request (`Validator::make`) -> invoca al método correspondiente del Servicio -> retorna un JSON formateado usando los helpers `$this->formatResource` o `$this->formatCollection`.

---

## 🛡️ Checklist de Aprobación para el Agente Principal (Gatekeeper)

Antes de marcar una tarea como finalizada, debes revisar el código del sub-agente frente a este checklist. Si algún punto no se cumple, **debes rechazar la tarea inmediatamente**:

* [ ] **¿El controlador tiene consultas directas a base de datos?** (No debe haber `where`, `create`, `update` ni llamadas directas de Eloquent en el controlador).
* [ ] **¿Existe lógica de detección de versión de API dentro de los métodos del controlador?** (No debe haber comprobaciones de headers en el controlador; eso es responsabilidad única de los middlewares de normalización).
* [ ] **¿Los tests están separados físicamente?** (Deben existir archivos independientes en `tests/Feature/V2/` y `tests/Feature/Legacy/`).
* [ ] **¿Se verificó la cobertura con PCOV?** (Se debe correr `./vendor/bin/phpunit --coverage-text --filter=[Modulo]` dentro del contenedor Docker).
* [ ] **¿La cobertura es superior al 80% (o cercana al 100%)?** (Toda la lógica de mapeo del middleware y decisiones del Service deben estar totalmente cubiertas de rojo a verde).

---

## 💬 Template de Instrucción para el Sub-agente

Cuando delegues la refactorización de un controlador, utiliza este formato de instrucciones:

```markdown
Hola, sub-agente. Tu misión es refactorizar el controlador `[NombreController]` siguiendo rigurosamente los 5 pasos arquitectónicos del proyecto:

1. **Mapeo de Contratos (Paso 1)**: Determina la correspondencia exacta entre campos V1 y V2 antes de programar.
2. **Estrategia de Testing Dual (Paso 2)**: Crea Feature Tests separados en `tests/Feature/V2/` (envía header `X-API-VERSION: 2`) y en `tests/Feature/Legacy/` (sin cabecera) para asegurar que ambas versiones funcionen.
3. **Capa de Servicios y API Resources (Pasos 3 y 4)**: Crea el Servicio en `app/Services/` para la lógica de negocio/Eloquent, y el API Resource para estructurar la salida limpia V2.
4. **Middlewares de Normalización (Paso 5)**: Crea middlewares adaptadores en `app/Http/Middleware/Legacy/` para mapear de forma bidireccional payloads de entrada y salida sólo para la versión antigua.
5. **Controlador Delgado (Paso 6)**: Limpia el controlador eliminando todo Eloquent/SQL y validaciones complejas. Limítate a inyectar el servicio, validar sintácticamente los parámetros y devolver el recurso formateado.

*Nota de Calidad*: Debes verificar que la cobertura de código arrojada por PCOV sea superior al 80% (óptimo cercano al 100%) ejecutando:
`docker exec ganaderasoft-backend ./vendor/bin/phpunit --coverage-text --filter=[Entidad]`
```
