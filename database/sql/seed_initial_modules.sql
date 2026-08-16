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
    v_mod_categories  UUID;
    v_mod_uom         UUID;
    v_mod_sup_types   UUID;
    v_mod_cli_types   UUID;
    v_mod_price_lists UUID;
    v_mod_exch_rates  UUID;
    v_mod_items       UUID;
    v_mod_warehouses  UUID;
    v_mod_wh_locs     UUID;
    v_mod_suppliers   UUID;
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
        (gen_random_uuid(), 'clients',   'Clientes', 'Gestión de clientes',             'Contact',   true, 4, NOW(), NOW()),
        (gen_random_uuid(), 'categories', 'Categorías', 'Catálogo de categorías de artículos', 'Tags', true, 5, NOW(), NOW()),
        (gen_random_uuid(), 'measurement-units', 'Unidades de medida', 'Catálogo de unidades de medida', 'Ruler', true, 6, NOW(), NOW()),
        (gen_random_uuid(), 'supplier-types', 'Tipos de proveedor', 'Catálogo de tipos de proveedor', 'Truck', true, 7, NOW(), NOW()),
        (gen_random_uuid(), 'client-types', 'Tipos de cliente', 'Catálogo de tipos de cliente', 'ContactRound', true, 8, NOW(), NOW()),
        (gen_random_uuid(), 'price-lists', 'Listas de precio', 'Catálogo de listas de precio de venta', 'DollarSign', true, 9, NOW(), NOW()),
        (gen_random_uuid(), 'exchange-rates', 'Tasas', 'Catálogo de tasas de cambio por fecha', 'Coins', true, 10, NOW(), NOW()),
        (gen_random_uuid(), 'items', 'Catálogo de artículos', 'Maestro de productos y servicios', 'Package', true, 11, NOW(), NOW()),
        (gen_random_uuid(), 'warehouses', 'Bodegas', 'Lugares físicos o lógicos donde se almacena inventario', 'Warehouse', true, 12, NOW(), NOW()),
        (gen_random_uuid(), 'warehouse-locations', 'Ubicaciones', 'Ubicaciones internas de las bodegas', 'MapPin', true, 13, NOW(), NOW()),
        (gen_random_uuid(), 'suppliers', 'Proveedores', 'Maestro de proveedores y sus condiciones comerciales', 'Truck', true, 14, NOW(), NOW())
    ON CONFLICT (name) DO NOTHING;

    -- Obtener los IDs generados para usarlos en los permisos
    SELECT id INTO v_mod_users     FROM app_modules WHERE name = 'users';
    SELECT id INTO v_mod_roles     FROM app_modules WHERE name = 'roles';
    SELECT id INTO v_mod_companies FROM app_modules WHERE name = 'companies';
    SELECT id INTO v_mod_clients   FROM app_modules WHERE name = 'clients';
    SELECT id INTO v_mod_categories FROM app_modules WHERE name = 'categories';
    SELECT id INTO v_mod_uom        FROM app_modules WHERE name = 'measurement-units';
    SELECT id INTO v_mod_sup_types  FROM app_modules WHERE name = 'supplier-types';
    SELECT id INTO v_mod_cli_types  FROM app_modules WHERE name = 'client-types';
    SELECT id INTO v_mod_price_lists FROM app_modules WHERE name = 'price-lists';
    SELECT id INTO v_mod_exch_rates  FROM app_modules WHERE name = 'exchange-rates';
    SELECT id INTO v_mod_items       FROM app_modules WHERE name = 'items';
    SELECT id INTO v_mod_warehouses  FROM app_modules WHERE name = 'warehouses';
    SELECT id INTO v_mod_wh_locs     FROM app_modules WHERE name = 'warehouse-locations';
    SELECT id INTO v_mod_suppliers   FROM app_modules WHERE name = 'suppliers';

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

    -- Categorías
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_categories, 'categories.list',          'Listar categorías',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_categories, 'categories.create',        'Crear categorías',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_categories, 'categories.show',          'Ver detalle de categoría',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_categories, 'categories.update',        'Editar categorías',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_categories, 'categories.update-status', 'Cambiar estado de categoría', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Unidades de medida
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_uom, 'measurement-units.list',          'Listar unidades de medida',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_uom, 'measurement-units.create',        'Crear unidades de medida',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_uom, 'measurement-units.show',          'Ver detalle de unidad de medida',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_uom, 'measurement-units.update',        'Editar unidades de medida',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_uom, 'measurement-units.update-status', 'Cambiar estado de unidad de medida',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tipos de proveedor
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sup_types, 'supplier-types.list',          'Listar tipos de proveedor',              true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_types, 'supplier-types.create',        'Crear tipos de proveedor',               true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_types, 'supplier-types.show',          'Ver detalle de tipo de proveedor',       true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_types, 'supplier-types.update',        'Editar tipos de proveedor',              true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_types, 'supplier-types.update-status', 'Cambiar estado de tipo de proveedor',    true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tipos de cliente
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_cli_types, 'client-types.list',          'Listar tipos de cliente',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_types, 'client-types.create',        'Crear tipos de cliente',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_types, 'client-types.show',          'Ver detalle de tipo de cliente',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_types, 'client-types.update',        'Editar tipos de cliente',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_types, 'client-types.update-status', 'Cambiar estado de tipo de cliente',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Listas de precio
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_price_lists, 'price-lists.list',          'Listar listas de precio',              true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_price_lists, 'price-lists.create',        'Crear listas de precio',               true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_price_lists, 'price-lists.show',          'Ver detalle de lista de precio',       true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_price_lists, 'price-lists.update',        'Editar listas de precio',              true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_price_lists, 'price-lists.update-status', 'Cambiar estado de lista de precio',    true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tasas
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_exch_rates, 'exchange-rates.list',          'Listar tasas',             true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_exch_rates, 'exchange-rates.create',        'Crear tasas',              true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_exch_rates, 'exchange-rates.show',          'Ver detalle de tasa',      true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_exch_rates, 'exchange-rates.update',        'Editar tasas',             true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_exch_rates, 'exchange-rates.update-status', 'Cambiar estado de tasa',   true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Catálogo de artículos
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_items, 'items.list',          'Listar artículos',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_items, 'items.create',        'Crear artículos',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_items, 'items.show',          'Ver detalle de artículo',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_items, 'items.update',        'Editar artículos',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_items, 'items.update-status', 'Cambiar estado de artículo',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Bodegas
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_warehouses, 'warehouses.list',          'Listar bodegas',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_warehouses, 'warehouses.create',        'Crear bodegas',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_warehouses, 'warehouses.show',          'Ver detalle de bodega',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_warehouses, 'warehouses.update',        'Editar bodegas',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_warehouses, 'warehouses.update-status', 'Cambiar estado de bodega',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Ubicaciones
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_wh_locs, 'warehouse-locations.list',          'Listar ubicaciones',           true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_wh_locs, 'warehouse-locations.create',        'Crear ubicaciones',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_wh_locs, 'warehouse-locations.show',          'Ver detalle de ubicación',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_wh_locs, 'warehouse-locations.update',        'Editar ubicaciones',           true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_wh_locs, 'warehouse-locations.update-status', 'Cambiar estado de ubicación',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Proveedores
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_suppliers, 'suppliers.list',          'Listar proveedores',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_suppliers, 'suppliers.create',        'Crear proveedores',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_suppliers, 'suppliers.show',          'Ver detalle de proveedor',      true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_suppliers, 'suppliers.update',        'Editar proveedores',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_suppliers, 'suppliers.update-status', 'Cambiar estado de proveedor',   true, 5, NOW(), NOW())
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
