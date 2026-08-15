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
BEGIN

    -- ==========================================================
    -- 1. MÓDULOS
    -- Idempotente: ON CONFLICT (name) DO NOTHING
    -- ==========================================================

    INSERT INTO app_modules (id, name, label, description, icon, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), 'users',     'Usuarios', 'Gestión de usuarios del sistema', 'UserCheck', true, 1, NOW(), NOW()),
        (gen_random_uuid(), 'roles',     'Roles',    'Gestión de roles y permisos',     'Users',     true, 2, NOW(), NOW()),
        (gen_random_uuid(), 'companies', 'Empresas', 'Gestión de empresas',             'Building2', true, 3, NOW(), NOW()),
        (gen_random_uuid(), 'clients',   'Clientes', 'Gestión de clientes',             'Contact',   true, 4, NOW(), NOW())
    ON CONFLICT (name) DO NOTHING;

    -- Obtener los IDs generados para usarlos en los permisos
    SELECT id INTO v_mod_users     FROM app_modules WHERE name = 'users';
    SELECT id INTO v_mod_roles     FROM app_modules WHERE name = 'roles';
    SELECT id INTO v_mod_companies FROM app_modules WHERE name = 'companies';
    SELECT id INTO v_mod_clients   FROM app_modules WHERE name = 'clients';

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
