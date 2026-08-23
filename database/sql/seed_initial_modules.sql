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
    v_mod_taxes       UUID;
    v_mod_config      UUID;
    v_mod_items       UUID;
    v_mod_warehouses  UUID;
    v_mod_wh_locs     UUID;
    v_mod_item_lots   UUID;
    v_mod_item_serial UUID;
    v_mod_item_stocks UUID;
    v_mod_inv_moves   UUID;
    v_mod_suppliers   UUID;
    v_mod_sales_ord   UUID;
    v_mod_purch_ord   UUID;
    v_mod_purch_inv   UUID;
    v_mod_purch_ncp   UUID;
    v_mod_sup_adv     UUID;
    v_mod_sup_pay     UUID;
    v_mod_purch_ret   UUID;
    v_mod_sales_inv   UUID;
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
        (gen_random_uuid(), 'suppliers', 'Proveedores', 'Maestro de proveedores y sus condiciones comerciales', 'Truck', true, 14, NOW(), NOW()),
        (gen_random_uuid(), 'sales-orders', 'Órdenes de venta', 'Pedidos del cliente: reservan inventario sin descargarlo', 'ClipboardList', true, 15, NOW(), NOW()),
        (gen_random_uuid(), 'purchase-orders', 'Órdenes de compra', 'Solicitudes de mercancía al proveedor: reservan la entrada esperada', 'ClipboardList', true, 16, NOW(), NOW()),
        (gen_random_uuid(), 'taxes', 'Impuestos', 'Catálogo de impuestos y retenciones', 'Percent', true, 17, NOW(), NOW()),
        (gen_random_uuid(), 'configuration', 'Configuración', 'Moneda principal, tasa y decimales de la empresa', 'Settings', true, 18, NOW(), NOW()),
        (gen_random_uuid(), 'purchase-invoices', 'Facturas de compra', 'Deuda con el proveedor: genera la cuenta por pagar y el costo de la mercancía', 'ReceiptText', true, 19, NOW(), NOW()),
        (gen_random_uuid(), 'sales-invoices', 'Facturas de venta', 'Documento fiscal: genera la cuenta por cobrar y descarga inventario', 'ReceiptText', true, 20, NOW(), NOW()),
        (gen_random_uuid(), 'item-lots', 'Lotes', 'Lotes de artículos con fabricación y vencimiento', 'Layers', true, 21, NOW(), NOW()),
        (gen_random_uuid(), 'item-serials', 'Series', 'Números de serie: cada unidad se controla por separado', 'Barcode', true, 22, NOW(), NOW()),
        (gen_random_uuid(), 'item-stocks', 'Existencias', 'Saldo actual por artículo, bodega, ubicación y lote', 'Boxes', true, 23, NOW(), NOW()),
        (gen_random_uuid(), 'inventory-movements', 'Kardex', 'Libro mayor del inventario: una fila por cada afectación de existencia', 'ArrowLeftRight', true, 24, NOW(), NOW()),
        (gen_random_uuid(), 'purchase-credit-notes', 'Notas de crédito a proveedor', 'Disminuye la deuda con el proveedor: devoluciones, descuentos y correcciones de precio', 'FileMinus', true, 25, NOW(), NOW()),
        (gen_random_uuid(), 'supplier-advances', 'Anticipos a proveedor', 'Dinero entregado antes de la factura: queda como saldo a favor del proveedor', 'HandCoins', true, 26, NOW(), NOW()),
        (gen_random_uuid(), 'supplier-payments', 'Pagos a proveedor', 'Salida de dinero que cancela una o varias facturas de compra', 'Banknote', true, 27, NOW(), NOW()),
        (gen_random_uuid(), 'purchase-returns', 'Devoluciones de compras', 'Salida física de mercancía hacia el proveedor por defectos, exceso o error de despacho', 'Undo2', true, 28, NOW(), NOW())
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
    SELECT id INTO v_mod_sales_ord   FROM app_modules WHERE name = 'sales-orders';
    SELECT id INTO v_mod_purch_ord   FROM app_modules WHERE name = 'purchase-orders';
    SELECT id INTO v_mod_taxes       FROM app_modules WHERE name = 'taxes';
    SELECT id INTO v_mod_config      FROM app_modules WHERE name = 'configuration';
    SELECT id INTO v_mod_purch_inv   FROM app_modules WHERE name = 'purchase-invoices';
    SELECT id INTO v_mod_purch_ncp   FROM app_modules WHERE name = 'purchase-credit-notes';
    SELECT id INTO v_mod_sup_adv     FROM app_modules WHERE name = 'supplier-advances';
    SELECT id INTO v_mod_sup_pay     FROM app_modules WHERE name = 'supplier-payments';
    SELECT id INTO v_mod_purch_ret   FROM app_modules WHERE name = 'purchase-returns';
    SELECT id INTO v_mod_sales_inv   FROM app_modules WHERE name = 'sales-invoices';
    SELECT id INTO v_mod_item_lots   FROM app_modules WHERE name = 'item-lots';
    SELECT id INTO v_mod_item_serial FROM app_modules WHERE name = 'item-serials';
    SELECT id INTO v_mod_item_stocks FROM app_modules WHERE name = 'item-stocks';
    SELECT id INTO v_mod_inv_moves   FROM app_modules WHERE name = 'inventory-movements';

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

    -- Órdenes de venta
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.list',          'Listar órdenes de venta',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.create',        'Crear órdenes de venta',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.show',          'Ver detalle de orden de venta',  true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.update',        'Editar órdenes de venta',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.update-status', 'Confirmar o anular una orden',   true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Órdenes de compra
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_purch_ord, 'purchase-orders.list',          'Listar órdenes de compra',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ord, 'purchase-orders.create',        'Crear órdenes de compra',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ord, 'purchase-orders.show',          'Ver detalle de orden de compra',  true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ord, 'purchase-orders.update',        'Editar órdenes de compra',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ord, 'purchase-orders.update-status', 'Confirmar o anular una orden',    true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Facturas de compra
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_purch_inv, 'purchase-invoices.list',          'Listar facturas de compra',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_inv, 'purchase-invoices.create',        'Crear facturas de compra',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_inv, 'purchase-invoices.show',          'Ver detalle de factura de compra', true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_inv, 'purchase-invoices.update',        'Editar facturas de compra',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_inv, 'purchase-invoices.update-status', 'Confirmar o anular una factura',   true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Notas de crédito a proveedor
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_purch_ncp, 'purchase-credit-notes.list',          'Listar notas de crédito a proveedor',   true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ncp, 'purchase-credit-notes.create',        'Crear notas de crédito a proveedor',    true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ncp, 'purchase-credit-notes.show',          'Ver detalle de una nota de crédito',    true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ncp, 'purchase-credit-notes.update',        'Editar notas de crédito a proveedor',   true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ncp, 'purchase-credit-notes.update-status', 'Confirmar o anular una nota de crédito', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Anticipos a proveedor
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sup_adv, 'supplier-advances.list',          'Listar anticipos a proveedor',    true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_adv, 'supplier-advances.create',        'Registrar anticipos a proveedor', true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_adv, 'supplier-advances.show',          'Ver detalle de un anticipo',      true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_adv, 'supplier-advances.update',        'Editar anticipos a proveedor',    true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_adv, 'supplier-advances.update-status', 'Aprobar o anular un anticipo',    true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Pagos a proveedor
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sup_pay, 'supplier-payments.list',          'Listar pagos a proveedor',       true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_pay, 'supplier-payments.create',        'Registrar pagos a proveedor',    true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_pay, 'supplier-payments.show',          'Ver detalle de un pago',         true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_pay, 'supplier-payments.update',        'Editar pagos a proveedor',       true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sup_pay, 'supplier-payments.update-status', 'Confirmar o anular un pago',     true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Devoluciones de compras
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_purch_ret, 'purchase-returns.list',          'Listar devoluciones de compras',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ret, 'purchase-returns.create',        'Crear devoluciones de compras',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ret, 'purchase-returns.show',          'Ver detalle de una devolución',         true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ret, 'purchase-returns.update',        'Editar devoluciones de compras',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_purch_ret, 'purchase-returns.update-status', 'Confirmar o anular una devolución',     true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Facturas de venta
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sales_inv, 'sales-invoices.list',          'Listar facturas de venta',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_inv, 'sales-invoices.create',        'Crear facturas de venta',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_inv, 'sales-invoices.show',          'Ver detalle de factura de venta', true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_inv, 'sales-invoices.update',        'Editar facturas de venta',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_inv, 'sales-invoices.update-status', 'Emitir o anular una factura',     true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Impuestos
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_taxes, 'taxes.list',          'Listar impuestos',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_taxes, 'taxes.create',        'Crear impuestos',             true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_taxes, 'taxes.show',          'Ver detalle de impuesto',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_taxes, 'taxes.update',        'Editar impuestos',            true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_taxes, 'taxes.update-status', 'Cambiar estado de impuesto',  true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Configuración
    -- Singleton por empresa: solo se consulta y se edita, nunca se crea ni se lista.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_config, 'configuration.show',   'Ver la configuración de la empresa',    true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_config, 'configuration.update', 'Editar la configuración de la empresa', true, 2, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Lotes
    -- Sin permiso de creación: el lote nace en el documento que recibe la
    -- mercancía, no en una pantalla propia.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_item_lots, 'item-lots.list',          'Listar lotes',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_lots, 'item-lots.show',          'Ver detalle de lote',     true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_lots, 'item-lots.update',        'Editar lotes',            true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_lots, 'item-lots.update-status', 'Retener o liberar lote',  true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Series
    -- Sin permiso de creación: la serie nace al recibir la mercancía.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_item_serial, 'item-serials.list',          'Listar series',            true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_serial, 'item-serials.show',          'Ver detalle de serie',     true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_serial, 'item-serials.update',        'Editar series',            true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_serial, 'item-serials.update-status', 'Cambiar estado de serie',  true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Existencias
    -- Tabla derivada: se consulta y, cuando el saldo queda en cero, se retira.
    -- No lleva permisos de creación ni de edición.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_item_stocks, 'item-stocks.list',          'Listar existencias',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_stocks, 'item-stocks.show',          'Ver detalle de existencia', true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_item_stocks, 'item-stocks.update-status', 'Retirar un saldo en cero',  true, 3, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Kardex
    -- Libro mayor inmutable: solo se consulta. El movimiento lo emite el
    -- documento que afecta el inventario, así que no lleva permisos de
    -- creación, edición ni cambio de estado.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_inv_moves, 'inventory-movements.list', 'Listar movimientos de inventario', true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_inv_moves, 'inventory-movements.show', 'Ver detalle de un movimiento',     true, 2, NOW(), NOW())
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
