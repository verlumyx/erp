-- =============================================================
-- Limpieza: retira los permisos de creación de Lotes y Series
--
-- Lotes y series dejaron de crearse desde pantalla: nacen en el
-- documento que recibe la mercancía (Entrada, Factura de compra),
-- que llama a ItemLotCreateService / ItemSerialCreateService.
-- Las rutas `*.create` y `*.store` ya no existen, así que sus
-- permisos quedaron huérfanos.
--
-- Ejecutar UNA sola vez sobre bases creadas antes del cambio.
-- Es idempotente. PostgreSQL compatible.
-- =============================================================

BEGIN;

-- ==========================================================
-- 1. PERMISOS ASIGNADOS A ROLES
-- app_role_permissions guarda la acción como texto, sin FK:
-- hay que limpiarla explícitamente antes que los permisos.
-- ==========================================================

DELETE FROM app_role_permissions
WHERE permission IN ('item-lots.create', 'item-serials.create');

-- ==========================================================
-- 2. LOS PERMISOS
-- ==========================================================

DELETE FROM app_permissions
WHERE action IN ('item-lots.create', 'item-serials.create');

-- ==========================================================
-- 3. REORDENAR Y REETIQUETAR LO QUE QUEDA
-- El hueco que deja `create` desordena la pantalla de roles, y el
-- seed no puede corregir etiquetas: su ON CONFLICT DO NOTHING solo
-- inserta lo que falta, nunca actualiza lo que ya está.
-- ==========================================================

UPDATE app_permissions p
SET "order" = v."order", label = v.label, updated_at = NOW()
FROM (VALUES
    ('item-lots.list',             1, 'Listar lotes'),
    ('item-lots.show',             2, 'Ver detalle de lote'),
    ('item-lots.update',           3, 'Editar lotes'),
    ('item-lots.update-status',    4, 'Retener o liberar lote'),
    ('item-serials.list',          1, 'Listar series'),
    ('item-serials.show',          2, 'Ver detalle de serie'),
    ('item-serials.update',        3, 'Editar series'),
    ('item-serials.update-status', 4, 'Cambiar estado de serie')
) AS v(action, "order", label)
WHERE p.action = v.action;

COMMIT;

-- =============================================================
-- Verificación
-- =============================================================

SELECT m.name AS modulo, p."order", p.action, p.label
FROM app_modules m
         JOIN app_permissions p ON p.module_id = m.id
WHERE m.name IN ('item-lots', 'item-serials', 'item-stocks')
ORDER BY m.name, p."order";
