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
    v_mod_cli_coll    UUID;
    v_mod_sales_ret   UUID;
    v_mod_cli_adv     UUID;
    v_mod_dispatches  UUID;
    v_mod_entries     UUID;
    v_mod_adjust      UUID;
    v_mod_transfers   UUID;
    v_mod_routes      UUID;
    v_mod_store_items UUID;
    v_mod_store_cust  UUID;
    v_mod_store_ord   UUID;
    v_mod_store_set   UUID;
    v_mod_imports     UUID;
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
        (gen_random_uuid(), 'purchase-returns', 'Devoluciones de compras', 'Salida física de mercancía hacia el proveedor por defectos, exceso o error de despacho', 'Undo2', true, 28, NOW(), NOW()),
        (gen_random_uuid(), 'client-collections', 'Cobros a clientes', 'Entrada de dinero que cancela una o varias facturas de venta', 'Banknote', true, 29, NOW(), NOW()),
        (gen_random_uuid(), 'client-advances', 'Anticipos de clientes', 'Dinero recibido antes de facturar: queda como saldo a favor del cliente', 'HandCoins', true, 30, NOW(), NOW()),
        (gen_random_uuid(), 'sales-returns', 'Devoluciones de ventas', 'Reingreso físico de mercancía que el cliente devuelve por defectos, exceso o cancelación', 'Undo2', true, 31, NOW(), NOW()),
        (gen_random_uuid(), 'dispatches', 'Despachos', 'Salida física de mercancía hacia el cliente: descarga inventario y libera la reserva del pedido', 'Truck', true, 32, NOW(), NOW()),
        (gen_random_uuid(), 'entries', 'Entradas', 'Recepción física de mercancía en bodega: ingresa inventario al costo con flete y gastos prorrateados', 'PackagePlus', true, 33, NOW(), NOW()),
        (gen_random_uuid(), 'transfers', 'Traslados', 'Movimiento de mercancía entre bodegas propias: cambia su ubicación, no el valor del inventario', 'ArrowLeftRight', true, 34, NOW(), NOW()),
        (gen_random_uuid(), 'adjustments', 'Ajustes', 'Corrección de existencias por conteo físico, merma, daño o error de captura: el único documento que mueve inventario sin una operación comercial detrás', 'ClipboardCheck', true, 35, NOW(), NOW()),
        (gen_random_uuid(), 'routes', 'Rutas', 'Recorridos de entrega y cobro: agrupan clientes y ordenan las paradas para despachar y cobrar', 'Route', true, 36, NOW(), NOW()),
        (gen_random_uuid(), 'store-items', 'Publicaciones', 'Artículos publicados en la tienda en línea con sus fotos y textos comerciales', 'Store', true, 37, NOW(), NOW()),
        (gen_random_uuid(), 'store-customers', 'Compradores', 'Cuentas con las que se compra en la tienda en línea y su vínculo con el cliente del ERP', 'UserRound', true, 38, NOW(), NOW()),
        (gen_random_uuid(), 'store-orders', 'Pedidos web', 'Bandeja de pedidos que llegan desde la tienda en línea y se convierten en órdenes de venta', 'ShoppingCart', true, 39, NOW(), NOW()),
        (gen_random_uuid(), 'store-settings', 'Ajustes de tienda', 'Nombre, logo, contacto, lista, bodega y llave de acceso de la tienda en línea', 'Settings2', true, 40, NOW(), NOW()),
        (gen_random_uuid(), 'imports', 'Importaciones', 'Expediente de costos de una importación: reparte flete, seguro, aduana y almacenaje entre lo que llegó y manda revalorizar el inventario', 'Ship', true, 41, NOW(), NOW())
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
    SELECT id INTO v_mod_cli_coll    FROM app_modules WHERE name = 'client-collections';
    SELECT id INTO v_mod_sales_ret   FROM app_modules WHERE name = 'sales-returns';
    SELECT id INTO v_mod_cli_adv     FROM app_modules WHERE name = 'client-advances';
    SELECT id INTO v_mod_item_lots   FROM app_modules WHERE name = 'item-lots';
    SELECT id INTO v_mod_item_serial FROM app_modules WHERE name = 'item-serials';
    SELECT id INTO v_mod_item_stocks FROM app_modules WHERE name = 'item-stocks';
    SELECT id INTO v_mod_inv_moves   FROM app_modules WHERE name = 'inventory-movements';
    SELECT id INTO v_mod_dispatches  FROM app_modules WHERE name = 'dispatches';
    SELECT id INTO v_mod_entries     FROM app_modules WHERE name = 'entries';
    SELECT id INTO v_mod_adjust      FROM app_modules WHERE name = 'adjustments';
    SELECT id INTO v_mod_transfers   FROM app_modules WHERE name = 'transfers';
    SELECT id INTO v_mod_routes      FROM app_modules WHERE name = 'routes';
    SELECT id INTO v_mod_store_items FROM app_modules WHERE name = 'store-items';
    SELECT id INTO v_mod_store_cust  FROM app_modules WHERE name = 'store-customers';
    SELECT id INTO v_mod_store_ord   FROM app_modules WHERE name = 'store-orders';
    SELECT id INTO v_mod_store_set   FROM app_modules WHERE name = 'store-settings';
    SELECT id INTO v_mod_imports     FROM app_modules WHERE name = 'imports';

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
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.update-status', 'Confirmar o anular una orden',   true, 5, NOW(), NOW()),
        -- Dos autorizaciones explícitas por encima de las cinco de siempre: la
        -- que levanta el bloqueo de crédito del cliente y la que permite vender
        -- por debajo del precio mínimo del artículo (docs/ventas.md §1, §2.2).
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.override-credit-limit', 'Vender a crédito por encima del límite', true, 6, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ord, 'sales-orders.override-min-price',    'Vender por debajo del precio mínimo',    true, 7, NOW(), NOW())
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

    -- Cobros a clientes
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_cli_coll, 'client-collections.list',          'Listar cobros a clientes',    true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_coll, 'client-collections.create',        'Registrar cobros a clientes', true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_coll, 'client-collections.show',          'Ver detalle de un cobro',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_coll, 'client-collections.update',        'Editar cobros a clientes',    true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_coll, 'client-collections.update-status', 'Confirmar o anular un cobro', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Devoluciones de ventas
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_sales_ret, 'sales-returns.list',          'Listar devoluciones de ventas',     true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ret, 'sales-returns.create',        'Crear devoluciones de ventas',      true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ret, 'sales-returns.show',          'Ver detalle de una devolución',     true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ret, 'sales-returns.update',        'Editar devoluciones de ventas',     true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_sales_ret, 'sales-returns.update-status', 'Confirmar o anular una devolución', true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Despachos
    -- Lleva un permiso propio además de los cinco de siempre: registrar cómo
    -- terminó la entrega no es un cambio de estado del documento.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.list',          'Listar despachos',                 true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.create',        'Crear despachos',                  true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.show',          'Ver detalle de un despacho',       true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.update',        'Editar despachos',                 true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.update-status', 'Confirmar o anular un despacho',   true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_dispatches, 'dispatches.deliver',       'Registrar la entrega al cliente',  true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Entradas
    -- Lleva un permiso propio además de los cinco de siempre: aceptar más
    -- mercancía de la que se pidió es una decisión de negocio, no un paso del
    -- documento.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_entries, 'entries.list',               'Listar entradas',                    true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_entries, 'entries.create',             'Crear entradas',                     true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_entries, 'entries.show',               'Ver detalle de una entrada',         true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_entries, 'entries.update',             'Editar entradas',                    true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_entries, 'entries.update-status',      'Confirmar o anular una entrada',     true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_entries, 'entries.allow-over-receipt', 'Recibir más de lo pedido en la orden', true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Ajustes de inventario
    -- Lleva un permiso propio además de los cinco de siempre: aprobar un ajuste
    -- es lo que deja que el inventario cambie sin una operación comercial
    -- detrás, y por encima del umbral configurado tiene que firmarlo alguien
    -- distinto de quien lo registró.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_adjust, 'adjustments.list',          'Listar ajustes de inventario',        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_adjust, 'adjustments.create',        'Crear ajustes de inventario',         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_adjust, 'adjustments.show',          'Ver detalle de un ajuste',            true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_adjust, 'adjustments.update',        'Editar ajustes de inventario',        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_adjust, 'adjustments.update-status', 'Enviar a aprobación o anular',        true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_adjust, 'adjustments.approve',       'Aprobar y aplicar un ajuste',         true, 6, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Importaciones
    -- Los cinco de siempre: el expediente no toca el kardex, así que no lleva
    -- permiso de aprobación propio. Quien firma el cambio de valor del
    -- inventario es el ajuste de revaluación que genera, con el umbral que la
    -- empresa ya tiene configurado para los ajustes.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_imports, 'imports.list',          'Listar expedientes de importación',   true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_imports, 'imports.create',        'Crear expedientes de importación',    true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_imports, 'imports.show',          'Ver detalle de un expediente',        true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_imports, 'imports.update',        'Editar expedientes de importación',   true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_imports, 'imports.update-status', 'Confirmar o anular un expediente',    true, 5, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Traslados
    -- Lleva dos permisos propios además de los cinco de siempre: recibir la
    -- mercancía en el destino es un hecho físico y no un paso del documento, y
    -- cerrar un traslado al que le faltó mercancía es una decisión de negocio.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_transfers, 'transfers.list',                   'Listar traslados',                        true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.create',                 'Crear traslados',                         true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.show',                   'Ver detalle de un traslado',              true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.update',                 'Editar traslados',                        true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.update-status',          'Confirmar o anular un traslado',          true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.receive',                'Registrar la recepción en el destino',    true, 6, NOW(), NOW()),
        (gen_random_uuid(), v_mod_transfers, 'transfers.close-with-difference',  'Cerrar un traslado con faltante',         true, 7, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Rutas
    -- Maestro, no documento: se activa y se desactiva. Lleva dos permisos
    -- propios además de los cinco de siempre: planificar el día genera las
    -- paradas desde la plantilla y los despachos pendientes, y registrar la
    -- visita es un hecho de la calle que anota quien recorre, no quien edita.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_routes, 'routes.list',          'Listar rutas',                          true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.create',        'Crear rutas',                           true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.show',          'Ver detalle de una ruta',               true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.update',        'Editar rutas',                          true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.update-status', 'Activar o desactivar una ruta',         true, 5, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.plan',          'Planificar las paradas de una fecha',   true, 6, NOW(), NOW()),
        (gen_random_uuid(), v_mod_routes, 'routes.visit',         'Registrar la visita de una parada',     true, 7, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Anticipos de clientes
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_cli_adv, 'client-advances.list',          'Listar anticipos de clientes',    true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_adv, 'client-advances.create',        'Registrar anticipos de clientes', true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_adv, 'client-advances.show',          'Ver detalle de un anticipo',      true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_adv, 'client-advances.update',        'Editar anticipos de clientes',    true, 4, NOW(), NOW()),
        (gen_random_uuid(), v_mod_cli_adv, 'client-advances.update-status', 'Aprobar o anular un anticipo',    true, 5, NOW(), NOW())
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

    -- Tienda: Publicaciones
    -- Maestro: se activa y se desactiva. Sin `show` propio: la pantalla de
    -- edición muestra en modo lectura lo que viene del artículo.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_store_items, 'store-items.list',          'Listar publicaciones',                true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_items, 'store-items.create',        'Publicar artículos en la tienda',     true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_items, 'store-items.edit',          'Editar publicaciones y su galería',   true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_items, 'store-items.update-status', 'Mostrar u ocultar una publicación',   true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tienda: Compradores
    -- Sin permiso de creación: el comprador nace al registrarse en la tienda
    -- o al invitarlo desde la pantalla del cliente (`link`).
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_store_cust, 'store-customers.list',          'Listar compradores',                          true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_cust, 'store-customers.show',          'Ver detalle de un comprador',                 true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_cust, 'store-customers.link',          'Vincular un comprador o invitar a un cliente', true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_cust, 'store-customers.update-status', 'Activar o bloquear un comprador',             true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tienda: Pedidos web
    -- Sin creación ni edición: el pedido lo arma el comprador y aquí solo se
    -- convierte en orden de venta o se rechaza.
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_store_ord, 'store-orders.list',    'Listar pedidos web',                      true, 1, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_ord, 'store-orders.show',    'Ver detalle de un pedido web',            true, 2, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_ord, 'store-orders.convert', 'Convertir un pedido web en orden de venta', true, 3, NOW(), NOW()),
        (gen_random_uuid(), v_mod_store_ord, 'store-orders.reject',  'Rechazar un pedido web',                  true, 4, NOW(), NOW())
    ON CONFLICT (module_id, action) DO NOTHING;

    -- Tienda: Ajustes
    -- Singleton por empresa: un solo permiso, editar (que incluye generar la llave).
    INSERT INTO app_permissions (id, module_id, action, label, is_active, "order", created_at, updated_at)
    VALUES
        (gen_random_uuid(), v_mod_store_set, 'store-settings.edit', 'Editar los ajustes de la tienda y su llave', true, 1, NOW(), NOW())
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
