-- =============================================================
-- Seed: Módulos del sistema y sus permisos
-- Se ejecuta una sola vez durante la instalación del sistema.
-- PostgreSQL compatible
-- =============================================================

DO $$
DECLARE
    v_mod_users       UUID;
    v_mod_roles       UUID;
    v_mod_companies   UUID;
    v_mod_clients     UUID;
    v_mod_services    UUID;
    v_mod_plans       UUID;
    v_mod_accounts     UUID;
    v_mod_sales       UUID;
    v_mod_manual_transactions UUID;
    v_mod_refunds     UUID;
    v_mod_reports     UUID;
BEGIN

    -- ==========================================================
    -- 1. MÓDULOS
    -- Idempotente: ON CONFLICT (name) DO NOTHING
    -- ==========================================================

    INSERT INTO app_modules (id, name, label, description, icon, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), 'users',       'Usuarios',   'Gestión de usuarios del sistema',  'UserCheck',  true, 1, NOW(), NOW()),
        (gen_random_uuid(), 'roles',       'Roles',      'Gestión de roles y permisos',       'Users',      true, 2, NOW(), NOW()),
        (gen_random_uuid(), 'companies',   'Empresas',   'Gestión de empresas',               'Building2',  true, 3, NOW(), NOW()),
        (gen_random_uuid(), 'clients',     'Clientes',   'Gestión de clientes',               'Contact',    true, 4, NOW(), NOW()),
        (gen_random_uuid(), 'services',    'Servicios',  'Catálogo de servicios',             'Clapperboard', true, 5, NOW(), NOW()),
        (gen_random_uuid(), 'plans',       'Planes',     'Catálogo de planes vendibles',      'Package',    true, 6, NOW(), NOW()),
        (gen_random_uuid(), 'accounts',     'Cuentas', 'Inventario de cuentas y perfiles',  'Boxes',      true, 7, NOW(), NOW()),
        (gen_random_uuid(), 'sales',        'Ventas',     'Gestión comercial de ventas',       'ShoppingCart', true, 8, NOW(), NOW()),
        (gen_random_uuid(), 'manual-transactions', 'Transacciones manuales', 'Registro manual de transacciones (cabecera + detalle)', 'NotebookPen', true, 9, NOW(), NOW()),
        (gen_random_uuid(), 'refunds',      'Reembolsos', 'Gestión de reembolsos de ventas',   'Undo2',      true, 10, NOW(), NOW()),
        (gen_random_uuid(), 'reports',      'Reportes',   'Reportes del sistema',              'ChartColumn', true, 11, NOW(), NOW())
    ON CONFLICT (name) DO NOTHING;

    -- Obtener los IDs generados para usarlos en los permisos
    SELECT id INTO v_mod_users       FROM app_modules WHERE name = 'users';
    SELECT id INTO v_mod_roles       FROM app_modules WHERE name = 'roles';
    SELECT id INTO v_mod_companies   FROM app_modules WHERE name = 'companies';
    SELECT id INTO v_mod_clients     FROM app_modules WHERE name = 'clients';
    SELECT id INTO v_mod_services    FROM app_modules WHERE name = 'services';
    SELECT id INTO v_mod_plans       FROM app_modules WHERE name = 'plans';
    SELECT id INTO v_mod_accounts     FROM app_modules WHERE name = 'accounts';
    SELECT id INTO v_mod_sales       FROM app_modules WHERE name = 'sales';
    SELECT id INTO v_mod_manual_transactions FROM app_modules WHERE name = 'manual-transactions';
    SELECT id INTO v_mod_refunds     FROM app_modules WHERE name = 'refunds';
    SELECT id INTO v_mod_reports     FROM app_modules WHERE name = 'reports';

    -- ==========================================================
    -- 2. PERMISOS POR MÓDULO
    -- Idempotente: ON CONFLICT (module_id, action) DO NOTHING
    -- Acciones: list, create, show, update, update-status
    -- Formato:  {modulo}.{accion}
    -- ==========================================================

    -- Usuarios
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_users, 'users.list',          'Listar usuarios',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_users, 'users.create',        'Crear usuarios',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_users, 'users.show',          'Ver detalle de usuario',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_users, 'users.update',        'Editar usuarios',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_users, 'users.update-status', 'Cambiar estado de usuario', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Roles
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_roles, 'roles.list',          'Listar roles',          true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_roles, 'roles.create',        'Crear roles',           true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_roles, 'roles.show',          'Ver detalle de rol',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_roles, 'roles.update',        'Editar roles',          true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_roles, 'roles.update-status', 'Cambiar estado de rol', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Empresas
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_companies, 'companies.list',          'Listar empresas',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_companies, 'companies.create',        'Crear empresas',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_companies, 'companies.show',          'Ver detalle de empresa',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_companies, 'companies.update',        'Editar empresas',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_companies, 'companies.update-status', 'Cambiar estado de empresa', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Clientes
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_clients, 'clients.list',          'Listar clientes',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_clients, 'clients.create',        'Crear clientes',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_clients, 'clients.show',          'Ver detalle de cliente',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_clients, 'clients.update',        'Editar clientes',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_clients, 'clients.update-status', 'Cambiar estado de cliente', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Servicios
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_services, 'services.list',          'Listar servicios',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_services, 'services.create',        'Crear servicios',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_services, 'services.show',          'Ver detalle de servicio',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_services, 'services.update',        'Editar servicios',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_services, 'services.update-status', 'Cambiar estado de servicio', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Planes
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_plans, 'plans.list',          'Listar planes',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_plans, 'plans.create',        'Crear planes',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_plans, 'plans.show',          'Ver detalle de plan',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_plans, 'plans.update',        'Editar planes',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_plans, 'plans.update-status', 'Cambiar estado de plan',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Inventario (Cuentas)
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_accounts, 'accounts.list',          'Listar cuentas',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_accounts, 'accounts.create',        'Crear cuentas',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_accounts, 'accounts.show',          'Ver detalle de cuenta',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_accounts, 'accounts.update',        'Editar cuentas',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_accounts, 'accounts.credentials',   'Ver credenciales',          true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_accounts, 'accounts.renew',         'Registrar renovaciones',    true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Ventas
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sales, 'sales.list',       'Listar ventas',         true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales, 'sales.create',     'Crear ventas',          true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales, 'sales.show',       'Ver detalle de venta',  true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales, 'sales.renew',      'Renovar ventas',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales, 'sales.reactivate', 'Reactivar ventas',      true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales, 'sales.cancel',     'Expulsar ventas',       true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Transacciones manuales
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_manual_transactions, 'manual-transactions.list',    'Listar transacciones manuales',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_manual_transactions, 'manual-transactions.create',  'Crear transacciones manuales',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_manual_transactions, 'manual-transactions.show',    'Ver detalle de transacción manual',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_manual_transactions, 'manual-transactions.approve', 'Aprobar transacciones manuales',       true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_manual_transactions, 'manual-transactions.cancel',  'Cancelar transacciones manuales',      true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Reembolsos
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_refunds, 'refunds.list',    'Listar reembolsos',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_refunds, 'refunds.create',  'Crear reembolsos',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_refunds, 'refunds.show',    'Ver detalle de reembolso',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_refunds, 'refunds.update',  'Editar reembolsos',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_refunds, 'refunds.approve', 'Aprobar reembolsos',          true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_refunds, 'refunds.reject',  'Rechazar reembolsos',         true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Reportes
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_reports, 'reports.movements',       'Ver reporte de movimientos',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_reports, 'reports.income_expenses', 'Ver reporte de ingresos y gastos',  true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_reports, 'reports.service_plan',    'Ver reporte por servicio/plan',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_reports, 'reports.expirations',     'Ver reporte de vencimientos',       true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

END $$;

-- Verificación
SELECT
    m.name      AS modulo,
    m."order"   AS orden_modulo,
    p.action    AS permiso,
    p.label     AS descripcion
FROM app_modules m
JOIN app_permissions p ON p.module_id = m.id
ORDER BY m."order", p."order";
