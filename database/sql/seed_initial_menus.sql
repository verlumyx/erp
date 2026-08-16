-- =============================================================
-- Seed: Menús iniciales del sistema
-- Se ejecuta una sola vez durante la instalación del sistema.
-- PostgreSQL compatible
-- =============================================================

INSERT INTO app_menus (id, parent_id, title, url, permission, icon, is_active, "order", section, created_at, updated_at)
VALUES
    -- Main navigation
    ('019cf226-1284-73f5-9291-d4615db63499', NULL, 'Dashboard', '/dashboard', NULL,           'LayoutGrid', true, 1, 'main', NOW(), NOW()),
    ('019e7fa4-43bb-7208-ae00-65a4bef96502', NULL, 'Clientes',  '/clients',   'clients.list', 'Contact',    true, 2, 'main', NOW(), NOW()),

    -- Catálogo: grupo padre sin URL ni permiso propio. Se muestra solo si al
    -- menos uno de sus hijos es visible para el usuario.
    ('019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', NULL, 'Catálogo', NULL, NULL, 'Library', true, 3, 'main', NOW(), NOW()),
    ('019eb0d5-9c41-7a3e-b8f2-5c6d1a4e7b30', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Categorías', '/categories', 'categories.list', 'Tags',   true, 1, 'main', NOW(), NOW()),
    ('019eb0e2-4f13-7c58-9a71-2d3b6e8f0c14', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Unidades de medida', '/measurement-units', 'measurement-units.list', 'Ruler', true, 2, 'main', NOW(), NOW()),
    ('019eb0f8-6d24-7a19-9e83-4b7c2f1a5d60', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Tipos de proveedor', '/supplier-types', 'supplier-types.list', 'Truck', true, 3, 'main', NOW(), NOW()),
    ('019eb105-8e35-7f20-a496-6c8d3e2b7a41', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Tipos de cliente', '/client-types', 'client-types.list', 'ContactRound', true, 4, 'main', NOW(), NOW()),
    ('019eb112-9f46-7c31-b5a8-7d9e4f3c8b52', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Listas de precio', '/price-lists', 'price-lists.list', 'DollarSign', true, 5, 'main', NOW(), NOW()),
    ('019eb11f-a057-7d42-b6b9-8e0f5a4d9c63', '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22', 'Tasas', '/exchange-rates', 'exchange-rates.list', 'Coins', true, 6, 'main', NOW(), NOW()),

    -- Footer navigation
    ('019cf226-1285-723c-ba82-f582cf993210', NULL, 'Usuarios',  '/users',     'users.list',   'UserCheck',  true, 1, 'footer', NOW(), NOW()),
    ('019cf226-1285-723c-ba82-f582d08b1c08', NULL, 'Roles',     '/roles',     'roles.list',   'Users',      true, 2, 'footer', NOW(), NOW()),
    -- 'system_owner' es un permiso reservado: este menú solo lo ve/accede el dueño del sistema.
    ('a14ea90e-9177-47d7-ba5e-ff768d0ad6fa', NULL, 'Empresas',  '/companies', 'system_owner', 'Building2',  true, 3, 'footer', NOW(), NOW())
ON CONFLICT (id) DO NOTHING;

-- Instalaciones previas: estos menús ya existían como raíz, así que el INSERT
-- de arriba los omite. Se recolocan bajo "Catálogo" de forma idempotente.
UPDATE app_menus AS m
SET parent_id = '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22',
    "order"   = v."order",
    updated_at = NOW()
FROM (VALUES
    ('019eb0d5-9c41-7a3e-b8f2-5c6d1a4e7b30'::uuid, 1),  -- Categorías
    ('019eb0e2-4f13-7c58-9a71-2d3b6e8f0c14'::uuid, 2),  -- Unidades de medida
    ('019eb0f8-6d24-7a19-9e83-4b7c2f1a5d60'::uuid, 3),  -- Tipos de proveedor
    ('019eb105-8e35-7f20-a496-6c8d3e2b7a41'::uuid, 4),  -- Tipos de cliente
    ('019eb112-9f46-7c31-b5a8-7d9e4f3c8b52'::uuid, 5),  -- Listas de precio
    ('019eb11f-a057-7d42-b6b9-8e0f5a4d9c63'::uuid, 6)   -- Tasas
) AS v(id, "order")
WHERE m.id = v.id
  AND m.parent_id IS DISTINCT FROM '019eb0f1-2a67-7b94-8c05-3e7d9b1a6f22';

-- Verificación
SELECT id, title, section, parent_id, url, permission, icon, "order"
FROM app_menus
ORDER BY section, "order";
