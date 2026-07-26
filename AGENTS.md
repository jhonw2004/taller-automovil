# AGENTS.md

Guía para agentes que trabajan en este repositorio.

## Antes de tocar código

1. Lee `memory/constitution.md` primero. Contiene las convenciones no-negociables del proyecto (arquitectura, convenciones de datos, reglas de negocio transversales, política de errores, design system, testing, seguridad). No las repitas, no las cuestiones, no las re-derives por spec: son la base compartida por todas las features.
2. Navega `/specs/<NNN-nombre-feature>/`. Cada carpeta tiene tres archivos:
   - `spec.md` — QUÉ: requisitos y criterios de aceptación (Given/When/Then). Sin detalles de implementación.
   - `plan.md` — CÓMO: modelos, migraciones, tablas, endpoints/Filament resources, paquetes usados.
   - `tasks.md` — tareas atómicas y verificables derivadas del plan.
3. El frontmatter YAML de cada `spec.md` (`id`, `status`, `depends_on`, `resumen`) debe bastar para decidir si esa feature es relevante a tu tarea actual **sin abrir el archivo**. Solo abre el cuerpo si el resumen confirma que aplica.
4. Los specs están numerados por orden de dependencia. Revisa `depends_on` antes de implementar una feature: sus dependencias deben existir (al menos como `spec.md` aprobado) primero. Si vas a adelantarte a una dependencia no resuelta, dilo explícitamente al usuario.

## Orden de implementación obligatorio

El orden de implementación es **estricto** y está definido por `depends_on`. No se puede saltar:

| Paso | Specs | Razón |
|---|---|---|
| **0** | `017-infraestructura-sistema` | Instalación de paquetes, PostGIS, timezone, paneles Filament, BelongsToTaller, seeds. **Sin esto, nada funciona.** |
| **1** | `001-identidad-autenticacion` | Guards, usuarios, OAuth, credenciales. Sin identidad no hay sesión. |
| **2** | `002-roles-permisos` | Roles, permisos, catálogo. Sin permisos no hay autorización. |
| **3** | `003-gestion-talleres` | Taller como tenant. Sin taller no hay ERP. |
| **4** | `004-solicitud-alta-taller` | Flujo de alta de taller (depende de 002 + 003). |
| **5** | `005-marketplace-busqueda-perfil` + `016-ui-design-system` | Home, búsqueda, perfil público. **016 debe implementarse en paralelo** (componentes Blade que 005 necesita). |
| **6** | `006-resenas-favoritos` | Reseñas y favoritos (depende de 001 + 003). |
| **7** | `007-clientes-vehiculos` a `015-auditoria` | Orden creciente por depends_on. Todas usan Filament y BelongsToTaller (ya configurados en paso 0). |

**Importante:** `016-ui-design-system` no tiene tasks que se implementen solas — se implementa junto con `005`. `014-notificaciones` no tiene triggers propios: los triggers viven en las features origen (004, 006, 008, 010, 011, 012, 013). `017` es prerequisito de todo.

## Sobre specs transversales

| Spec | No es una feature aislada — se implementa así |
|---|---|
| `014-notificaciones` | Solo define la tabla y endpoint de marcar-leída. Los triggers (10 tipos) se implementan en cada feature origen (004, 006, 008, 010, 011, 012, 013). |
| `016-ui-design-system` | Los componentes Blade se crean junto con las vistas de `005-marketplace-busqueda-perfil` y `006-resenas-favoritos`. No tiene implementación propia independiente. |
| `017-infraestructura-sistema` | Se implementa primero y completo. Todo lo demás depende de esto. |

## Si un spec está en `status: draft`

No está aprobado para implementación directa.

- Si los criterios de aceptación son suficientes y consistentes, puedes proponer pasar a `approved` e implementar.
- Si faltan criterios de aceptación medibles, o una regla está marcada como "recomendación pendiente de confirmación del negocio", **pregunta al usuario**. No inventes el requisito ni asumas la opción más común.

## Al completar una feature

Actualiza `status: implemented` en el frontmatter de `spec.md` solo cuando el código y los tests cubren los criterios de aceptación descritos. No lo marques `implemented` con tests parciales o pendientes.

## Orden de lectura recomendado

`memory/constitution.md` → `specs/<feature>/spec.md` → `specs/<feature>/plan.md` → `specs/<feature>/tasks.md`.

No leas specs de features no relacionadas salvo que aparezcan en `depends_on` de la que estás implementando — cada lectura fuera de eso es contexto desperdiciado.
