-- =============================================================
-- Seed: Menús iniciales del sistema
-- Se ejecuta una sola vez durante la instalación del sistema.
-- PostgreSQL compatible
-- =============================================================

INSERT INTO app_menus (id, parent_id, title, url, permission, icon, is_active, "order", section, created_at, updated_at)
VALUES
    -- Main navigation
    ('019cf226-1284-73f5-9291-d4615db63499', NULL, 'Dashboard', '/dashboard', NULL,          'LayoutGrid', true, 1, 'main',   NOW(), NOW()),

    ('019e7fa4-43bb-7208-ae00-65a4bef96502', NULL, 'Clientes',  '/clients', 'clients.list', 'Contact', true, 2, 'main', NOW(), NOW()),

    -- Catálogo (grupo) › Servicios, Planes
    ('019e8a10-0001-7000-a000-000000000001', NULL,                                   'Catálogo',  '/services', 'services.list', 'LibraryBig',   true, 3, 'main', NOW(), NOW()),
    ('019e8a10-0002-7000-a000-000000000002', '019e8a10-0001-7000-a000-000000000001', 'Servicios', '/services', 'services.list', 'Clapperboard', true, 1, 'main', NOW(), NOW()),
    ('019e8a10-0003-7000-a000-000000000003', '019e8a10-0001-7000-a000-000000000001', 'Planes',    '/plans',    'plans.list',    'Package',      true, 2, 'main', NOW(), NOW()),

    ('019ec417-c71d-7115-bf42-4aa300399ff6', NULL, 'Cuentas', '/accounts', 'accounts.list', 'KeyRound', true, 4, 'main', NOW(), NOW() ),

    -- Footer navigation
    ('019cf226-1285-723c-ba82-f582cf993210', NULL, 'Usuarios',  '/users',     'users.list',  'UserCheck',  true, 1, 'footer', NOW(), NOW()),
    ('019cf226-1285-723c-ba82-f582d08b1c08', NULL, 'Roles',     '/roles',     'roles.list',  'Users',      true, 2, 'footer', NOW(), NOW()),
    -- 'system_owner' es un permiso reservado: este menú solo lo ve/accede el dueño del sistema.
    ('a14ea90e-9177-47d7-ba5e-ff768d0ad6fa', NULL, 'Empresas',  '/companies', 'system_owner', 'Building2', true, 3, 'footer', NOW(), NOW())
ON CONFLICT (id) DO NOTHING;

-- Verificación
SELECT id, title, section, parent_id, url, permission, icon, "order"
FROM app_menus
ORDER BY section, "order";
