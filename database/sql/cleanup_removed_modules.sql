-- =============================================================
-- Limpieza: elimina el rastro de los módulos retirados del sistema
-- (Service, Plan, Account, Sale, Refund, Transaction,
--  ManualTransaction, Report)
--
-- Ejecutar UNA sola vez sobre bases de datos creadas antes de la
-- retirada de dichos módulos. Es idempotente.
-- PostgreSQL compatible
-- =============================================================

BEGIN;

-- ==========================================================
-- 1. TABLAS DEL DOMINIO RETIRADO
-- CASCADE resuelve las claves foráneas entre ellas.
-- ==========================================================

DROP TABLE IF EXISTS app_manual_transaction_lines CASCADE;
DROP TABLE IF EXISTS app_manual_transactions      CASCADE;
DROP TABLE IF EXISTS app_refunds                  CASCADE;
DROP TABLE IF EXISTS app_sale_renewals            CASCADE;
DROP TABLE IF EXISTS app_sale_profiles            CASCADE;
DROP TABLE IF EXISTS app_sales                    CASCADE;
DROP TABLE IF EXISTS app_transactions             CASCADE;
DROP TABLE IF EXISTS app_account_renewals         CASCADE;
DROP TABLE IF EXISTS app_profiles                 CASCADE;
DROP TABLE IF EXISTS app_accounts                 CASCADE;
DROP TABLE IF EXISTS app_plans                    CASCADE;
DROP TABLE IF EXISTS app_services                 CASCADE;

-- ==========================================================
-- 2. HISTORIAL DE MIGRACIONES
-- Los archivos ya no existen: sin esto `migrate:status` los
-- reporta como aplicados pero ausentes.
-- ==========================================================

DELETE FROM migrations
WHERE migration IN (
    '2026_06_13_162643_create_app_services_table',
    '2026_06_13_163017_create_app_plans_table',
    '2026_06_13_170000_create_app_accounts_table',
    '2026_06_13_170001_create_app_profiles_table',
    '2026_06_14_000000_create_app_account_renewals_table',
    '2026_06_14_000000_create_app_transactions_table',
    '2026_06_14_010000_add_streaming_account_renewal_category_to_app_transactions',
    '2026_06_14_020000_create_app_sales_table',
    '2026_06_14_020001_create_app_sale_profiles_table',
    '2026_06_14_020002_create_app_sale_renewals_table',
    '2026_06_14_030000_create_app_refunds_table',
    '2026_06_20_010000_create_app_manual_transactions_table',
    '2026_06_20_010001_create_app_manual_transaction_lines_table',
    '2026_06_21_000000_add_status_to_app_manual_transactions_table'
);

-- ==========================================================
-- 3. PERMISOS ASIGNADOS A ROLES
-- app_role_permissions guarda la acción como texto, sin FK:
-- hay que limpiarla explícitamente antes que los permisos.
-- ==========================================================

DELETE FROM app_role_permissions
WHERE split_part(permission, '.', 1) IN (
    'services', 'plans', 'accounts', 'sales',
    'manual-transactions', 'refunds', 'reports'
);

-- ==========================================================
-- 4. MÓDULOS Y SUS PERMISOS
-- ==========================================================

DELETE FROM app_permissions
WHERE module_id IN (
    SELECT id FROM app_modules
    WHERE name IN (
        'services', 'plans', 'accounts', 'sales',
        'manual-transactions', 'refunds', 'reports'
    )
);

DELETE FROM app_modules
WHERE name IN (
    'services', 'plans', 'accounts', 'sales',
    'manual-transactions', 'refunds', 'reports'
);

-- ==========================================================
-- 5. MENÚS
-- Los hijos primero: 'Catálogo' es padre de 'Servicios' y
-- 'Planes' y su FK impide borrarlo antes.
-- ==========================================================

DELETE FROM app_menus
WHERE url LIKE '/services%'
   OR url LIKE '/plans%'
   OR url LIKE '/accounts%'
   OR url LIKE '/sales%'
   OR url LIKE '/manual-transactions%'
   OR url LIKE '/refunds%'
   OR url LIKE '/reports%';

COMMIT;

-- =============================================================
-- Verificación
-- =============================================================

SELECT m.name AS modulo, m."order", COUNT(p.id) AS permisos
FROM app_modules m
         LEFT JOIN app_permissions p ON p.module_id = m.id
GROUP BY m.id, m.name, m."order"
ORDER BY m."order";

SELECT title, section, "order", url, permission
FROM app_menus
ORDER BY section, "order";
