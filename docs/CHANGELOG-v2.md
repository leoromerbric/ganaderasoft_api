# GanaderaSoft API Migration Changelog v1 to v2

## Scope
This changelog summarizes the migration impact from the v1 API assets to the new v2 assets delivered in this repository.

## New v2 Artifacts
- Swagger: openapi/ganaderasoft-api-v2.yaml
- Postman collection: docs/postman-collections/GanaderaSoft-API-v2.postman_collection.json
- Postman environment: docs/postman-collections/GanaderaSoft-Environment-v2.postman_environment.json

## Functional Changes in v2
- Automatic stage classification by age in days and weight objective is now exposed in mutation responses.
- New response field: clasificacion_etaria.
- Animal create and update now return stage classification diagnostics.
- Peso corporal create and update now return stage classification diagnostics.
- In peso corporal create, peso_etapa_etid is optional in v2 and can be resolved automatically by business rules.

## Contract Changes
- API metadata version upgraded to 2.0.0 in Swagger v2.
- New/updated schemas include ClasificacionEtariaV2 and mutation response payloads with clasificacion_etaria.
- New v2 documentation paths for animales and peso-corporal mutations were added to the v2 OpenAPI file.

## Team Migration Notes
- Keep v1 assets for backward compatibility while clients migrate.
- Update API client models to include clasificacion_etaria in mutation responses.
- Validate frontend/backoffice flows that depend on explicit etapa assignment in peso-corporal create.
- Re-run integration tests for animal lifecycle and body-weight capture after upgrading clients to v2 assets.
