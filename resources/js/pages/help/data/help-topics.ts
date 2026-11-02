import { HelpModuleGroup, HelpTopic } from '../types';

export const HELP_MODULES: HelpModuleGroup[] = [
    {
        id: 'catalogo',
        name: 'Catálogo',
        icon: 'FolderTree',
        badge: '7 temas',
        description: 'Bases maestras y reglas de negocio: categorías, unidades, tasas e impuestos.',
        topics: [
            {
                id: 'catalogo-categorias',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'FolderTree',
                title: 'Categorías',
                subtitle: 'Organización y clasificación jerárquica de productos y servicios',
                summary: 'Permite agrupar tus artículos por familias comerciales (ej. Alimentos, Bebidas, Limpieza, Ferretería).',
                whatIsIt: 'Las categorías son etiquetas ordenadas que te permiten clasificar todos tus productos o servicios en grupos lógicos. No solo hacen que buscar un producto sea rápido en el Punto de Venta o al hacer facturas, sino que también te permiten generar reportes de ganancias y ventas por departamento.',
                whyItMatters: 'Sin categorías, un inventario de más de 100 productos se vuelve caótico. Al tenerlas bien definidas, puedes saber qué línea de productos te deja mayor margen o cuál tiene menor rotación.',
                keyConcepts: [
                    {
                        term: 'Código de Categoría',
                        explanation: 'Abreviatura corta para identificarla internamente (ej. BEB para Bebidas).',
                    },
                    {
                        term: 'Categoría Padre',
                        explanation: 'Permite crear subcategorías (ej. Alimentos > Lácteos > Quesos).',
                    },
                    {
                        term: 'Estado Activo/Inactivo',
                        explanation: 'Si una categoría se desactiva, ya no aparecerá como opción para nuevos artículos, pero se mantiene en el historial.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Accede a Catálogo > Categorías',
                        description: 'En el menú lateral, abre la sección Catálogo y selecciona Categorías.',
                    },
                    {
                        number: 2,
                        title: 'Haz clic en "Nueva Categoría"',
                        description: 'Presiona el botón de acción en la esquina superior derecha.',
                    },
                    {
                        number: 3,
                        title: 'Completa la información básica',
                        description: 'Escribe el nombre claro (ej. "Bebidas no alcohólicas"), código único y si depende de una categoría superior.',
                    },
                    {
                        number: 4,
                        title: 'Guarda y asigna a artículos',
                        description: 'Guarda los cambios. Ahora podrás seleccionar esta categoría al crear artículos en Inventario.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Mantén las categorías simples al inicio. Entre 5 y 15 categorías principales es lo ideal para la mayoría de comercios.',
                    },
                    {
                        type: 'warning',
                        text: 'El sistema tiene política de preservación de datos: las categorías no se eliminan físicamente; si ya no la usas, simplemente desactívala.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Catálogo de Artículos',
                        topicId: 'inventario-catalogo-articulos',
                        relationship: 'Cada artículo creado debe pertenecer a una categoría.',
                    },
                    {
                        name: 'Terminal POS',
                        topicId: 'pos-terminal',
                        relationship: 'En el punto de venta puedes filtrar productos por categoría.',
                    },
                ],
                systemPath: '/categories',
                faqs: [
                    {
                        question: '¿Qué pasa si desactivo una categoría que tiene productos asociados?',
                        answer: 'Los productos existentes conservan su registro histórico, pero no podrás asignar esa categoría a nuevos productos.',
                    },
                ],
                keywords: ['categoría', 'familia', 'clasificación', 'grupo de productos', 'subcategoría'],
            },
            {
                id: 'catalogo-unidades-medida',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'Scale',
                title: 'Unidades de Medida',
                subtitle: 'Formas de cuantificar, comprar y vender tus productos',
                summary: 'Define cómo se cuentan o fraccionan los artículos: Unidad (UND), Kilogramo (KG), Litro (LTS), Caja, Paquete.',
                whatIsIt: 'Las unidades de medida establecen el estándar para comprar, almacenar y vender. Le indican al sistema si un producto se cuenta pieza por pieza (unidades) o si se pesa (gramos/kilos) o mide por volumen (litros/mililitros).',
                whyItMatters: 'Garantiza que el almacén y el punto de venta hablen el mismo idioma. Evita vender por error 1 caja al precio de 1 unidad.',
                keyConcepts: [
                    {
                        term: 'Símbolo / Abreviatura',
                        explanation: 'Representación corta impresa en facturas y etiquetas (ej. KG, UND, LTS, CJ).',
                    },
                    {
                        term: 'Unidad de Almacén',
                        explanation: 'Es la unidad mínima con la que el Kardex descuenta las existencias.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ingresa a Catálogo > Unidades de Medida',
                        description: 'Revisa la lista existente antes de crear una nueva para evitar duplicados.',
                    },
                    {
                        number: 2,
                        title: 'Presiona "Nueva Unidad"',
                        description: 'Ingresa el nombre descriptivo (ej. "Kilogramos") y su símbolo oficial ("KG").',
                    },
                    {
                        number: 3,
                        title: 'Guardar y vincular',
                        description: 'Una vez creada, estará disponible inmediatamente para asociarla en la ficha técnica de tus artículos.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Usa símbolos estándar reconocidos comercialmente (UND, KG, MTS, LTS) para cumplir con normativas fiscales.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Catálogo de Artículos',
                        topicId: 'inventario-catalogo-articulos',
                        relationship: 'Al registrar un producto se le asigna su unidad de medida principal.',
                    },
                ],
                systemPath: '/measurement-units',
                keywords: ['unidad', 'medida', 'kilos', 'litros', 'peso', 'cajas', 'fracción'],
            },
            {
                id: 'catalogo-tipos-proveedor',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'Truck',
                title: 'Tipos de Proveedor',
                subtitle: 'Segmentación de aliados comerciales y acreedores',
                summary: 'Clasifica a tus proveedores en categorías estratégicas: Mayorista, Fabricante, Servicios Básicos, Contratista.',
                whatIsIt: 'Permite etiquetar el tipo de relación que tienes con quienes te surten mercancía o servicios. No es lo mismo el proveedor de mercancía importada que la empresa que te provee el internet o el servicio eléctrico.',
                whyItMatters: 'Te permite separar en tus reportes de cuentas por pagar cuánto le debes a proveedores de inventario para la venta versus gastos operativos recurrentes.',
                keyConcepts: [
                    {
                        term: 'Proveedor de Mercancía',
                        explanation: 'Aquel que te surte productos que van directo a tu inventario y Kardex.',
                    },
                    {
                        term: 'Proveedor de Servicios',
                        explanation: 'Aquel que emite facturas por gastos operativos (luz, fletes, alquiler, asesorías).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Catálogo > Tipos de Proveedor',
                        description: 'Verás la lista actual de tipos registrados.',
                    },
                    {
                        number: 2,
                        title: 'Crea un tipo nuevo',
                        description: 'Haz clic en "Nuevo Tipo", ponle nombre y descripción (ej. "Distribuidores Nacionales").',
                    },
                    {
                        number: 3,
                        title: 'Asigna a los proveedores',
                        description: 'Al crear o editar un proveedor en el módulo de Compras, selecciona su clasificación.',
                    },
                ],
                tips: [
                    {
                        type: 'info',
                        text: 'Tener al menos dos tipos básicos: "Mercancía / Inventario" y "Servicios Generales" ayuda a mantener limpia tu contabilidad.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Proveedores',
                        topicId: 'compras-proveedores',
                        relationship: 'Se asigna directamente en la ficha del proveedor.',
                    },
                ],
                systemPath: '/supplier-types',
                keywords: ['tipo proveedor', 'proveedores', 'acreedor', 'clasificación de compras'],
            },
            {
                id: 'catalogo-tipos-clientes',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'Users',
                title: 'Tipos de Clientes',
                subtitle: 'Segmentación de compradores y políticas comerciales',
                summary: 'Agrupa a tus clientes: Consumidor Final (Detal), Mayorista, Corporativo, Empleado, Distribuidor.',
                whatIsIt: 'Es la herramienta para diferenciar a quién le estás vendiendo. Te permite establecer políticas automáticas: por ejemplo, que a los clientes "Mayoristas" se les aplique automáticamente una lista de precios con descuento y crédito a 15 días.',
                whyItMatters: 'Evita que el vendedor tenga que aplicar descuentos manuales arriesgados. El sistema ajusta los precios según a quién se le facture.',
                keyConcepts: [
                    {
                        term: 'Lista de Precios por Defecto',
                        explanation: 'Lista que se seleccionará automáticamente al facturar a un cliente de este grupo.',
                    },
                    {
                        term: 'Días de Crédito',
                        explanation: 'Plazo predeterminado para el pago de facturas.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Entra a Catálogo > Tipos de Clientes',
                        description: 'Consulta los tipos vigentes.',
                    },
                    {
                        number: 2,
                        title: 'Crear nuevo tipo',
                        description: 'Indica el nombre (ej. "Mayorista Nivel 1") y asígnale su lista de precios predeterminada.',
                    },
                    {
                        number: 3,
                        title: 'Vincular clientes',
                        description: 'Al registrar un cliente en Ventas, asígnale su tipo correspondiente.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Crea un tipo "Consumidor Final" como predeterminado para ventas rápidas en el mostrador o POS.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Clientes',
                        topicId: 'ventas-clientes',
                        relationship: 'Cada cliente tiene asignado un tipo.',
                    },
                    {
                        name: 'Listas de Precio',
                        topicId: 'catalogo-listas-precio',
                        relationship: 'Un tipo de cliente se asocia con una lista de precios específica.',
                    },
                ],
                systemPath: '/client-types',
                keywords: ['tipo cliente', 'mayorista', 'minorista', 'segmentación', 'descuentos'],
            },
            {
                id: 'catalogo-listas-precio',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'BadgePercent',
                title: 'Listas de Precio',
                subtitle: 'Estrategias de precios diferenciados por canal o volumen',
                summary: 'Maneja múltiples precios para un mismo producto: PVP Público, Precio Mayor, Distribuidor o Promocional.',
                whatIsIt: 'Te permite vender el mismo artículo a diferentes precios sin duplicar el producto en inventario. Puedes tener una lista para tu tienda física al detal y otra para ventas al por mayor.',
                whyItMatters: 'Flexibilidad comercial total. Te permite negociar con grandes clientes o lanzar promociones por temporada sin alterar tu catálogo base.',
                keyConcepts: [
                    {
                        term: 'Lista Base / Principal',
                        explanation: 'El precio público de venta habitual (PVP).',
                    },
                    {
                        term: 'Moneda de la Lista',
                        explanation: 'Moneda en la que se calculan los precios (USD de referencia o moneda local VES).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Catálogo > Listas de Precio',
                        description: 'Revisa las listas activas.',
                    },
                    {
                        number: 2,
                        title: 'Crea la lista',
                        description: 'Ingresa el nombre (ej. "Precios Mayoristas") y define la moneda base.',
                    },
                    {
                        number: 3,
                        title: 'Define precios por artículo',
                        description: 'Establece los montos para cada producto dentro de esa lista o un porcentaje de margen.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Recomienda siempre fijar los precios base en la moneda de referencia estable (USD) para proteger tus márgenes contra la devaluación.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'Al hacer una factura puedes elegir qué lista de precios aplicar.',
                    },
                    {
                        name: 'Terminal POS',
                        topicId: 'pos-terminal',
                        relationship: 'El punto de venta toma por defecto la lista asignada a la caja o cliente.',
                    },
                ],
                systemPath: '/price-lists',
                keywords: ['precio', 'tarifa', 'lista de precio', 'descuento', 'margen', 'pvp'],
            },
            {
                id: 'catalogo-tasa',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'Coins',
                title: 'Tasa de Cambio',
                subtitle: 'Gestión bimonetaria y conversión de divisas en tiempo real',
                summary: 'Establece el valor de conversión oficial entre la moneda local (VES) y la divisa extranjera (USD).',
                whatIsIt: 'Es el módulo que hace que el sistema funcione en dos monedas de forma simultánea. Al ingresar la tasa oficial (ej. BCV o tasa comercial del día), el sistema calcula automáticamente precios, cobros y saldos en ambas monedas.',
                whyItMatters: 'En economías multimoneda, una tasa desactualizada puede hacerte perder dinero al cobrar en bolívares un monto calculado con una tasa antigua.',
                keyConcepts: [
                    {
                        term: 'Tasa del Día',
                        explanation: 'El valor fijado para las transacciones del día actual.',
                    },
                    {
                        term: 'Histórico de Tasas',
                        explanation: 'Registro inalterable de qué tasa estaba vigente en cada fecha anterior, para auditoría de facturas pasadas.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Abre Catálogo > Tasas de Cambio',
                        description: 'Consulta la tasa actual y el histórico registrado.',
                    },
                    {
                        number: 2,
                        title: 'Registra la nueva tasa diaria',
                        description: 'A primera hora de la jornada, ingresa el valor de cambio del día y la fecha de vigencia.',
                    },
                    {
                        number: 3,
                        title: 'Guarda y confirma',
                        description: 'Al guardar, todo el sistema (POS, facturación, cobros) adoptará la nueva tasa al instante.',
                    },
                ],
                tips: [
                    {
                        type: 'warning',
                        text: '¡Regla de oro! Actualiza la tasa antes de abrir las cajas en el POS o emitir la primera factura del día.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Terminal POS',
                        topicId: 'pos-terminal',
                        relationship: 'Muestra los totales a cobrar tanto en USD como en VES según la tasa.',
                    },
                    {
                        name: 'Cobros a Clientes',
                        topicId: 'ventas-cobros-clientes',
                        relationship: 'Calcula conversiones exactas si el cliente paga en bolívares una deuda en divisas.',
                    },
                ],
                systemPath: '/exchange-rates',
                keywords: ['tasa', 'dólar', 'cambio', 'divisa', 'bcv', 'moneda', 'ves', 'usd'],
            },
            {
                id: 'catalogo-impuestos',
                moduleId: 'catalogo',
                moduleName: 'Catálogo',
                moduleIcon: 'Receipt',
                title: 'Impuestos',
                subtitle: 'Configuración tributaria y alícuotas fiscales aplicables',
                summary: 'Administra las alícuotas legales: IVA General (16%), IVA Reducido (8%), Exento (0%), IGTF y retenciones.',
                whatIsIt: 'Configura las tasas impositivas oficiales exigidas por la autoridad tributaria. Cada producto o servicio del sistema tiene asignado su impuesto para que el cálculo en compras y ventas sea 100% automático y exacto.',
                whyItMatters: 'Garantiza el cumplimiento tributario, previene multas fiscales y asegura que tus libros de compra y venta cuadren al centavo.',
                keyConcepts: [
                    {
                        term: 'Porcentaje de Alícuota',
                        explanation: 'El valor numérico que se sumará al subtotal (ej. 16% para IVA general).',
                    },
                    {
                        term: 'Exento',
                        explanation: 'Productos de primera necesidad o medicamentos que no pagan impuesto (0%).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ingresa a Catálogo > Impuestos',
                        description: 'Revisa las alícuotas configuradas en el sistema.',
                    },
                    {
                        number: 2,
                        title: 'Crear o verificar impuesto',
                        description: 'Verifica nombre fiscal, código y porcentaje exacto.',
                    },
                    {
                        number: 3,
                        title: 'Vincular a artículos',
                        description: 'Al registrar cada producto en Inventario, selecciona el impuesto que le corresponde.',
                    },
                ],
                tips: [
                    {
                        type: 'info',
                        text: 'Si el gobierno modifica la alícuota general, actualízala aquí para que las nuevas ventas tomen el porcentaje correcto automáticamente.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'El desglose de impuestos aparece automáticamente en el pie de la factura.',
                    },
                    {
                        name: 'Facturas de Compra',
                        topicId: 'compras-facturas-compra',
                        relationship: 'Permite registrar el crédito fiscal que te cobraron los proveedores.',
                    },
                ],
                systemPath: '/taxes',
                keywords: ['impuesto', 'iva', 'fiscal', 'alícuota', 'tributo', 'seniat', 'exento'],
            },
        ],
    },
    {
        id: 'inventario',
        name: 'Inventario',
        icon: 'Package',
        badge: '7 temas',
        description: 'Control de existencias físicas, bodegas, ubicaciones, lotes, series y Kardex.',
        topics: [
            {
                id: 'inventario-catalogo-articulos',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'Package',
                title: 'Catálogo de Artículos',
                subtitle: 'Ficha maestra de productos, insumos y servicios de la empresa',
                summary: 'El registro maestro donde se guardan nombres, códigos de barra, costos, precios, fotos y características de tus artículos.',
                whatIsIt: 'Es el corazón de tu negocio. Aquí vive cada producto que comercializas o utilizas internamente. Define si un artículo se almacena (mercancía tangible) o si es un servicio intangible que no maneja existencias.',
                whyItMatters: 'Una ficha de producto completa ahorra tiempo al cajero y al personal de almacén: con solo escanear el código de barras, el sistema sabe precio, ubicación y stock.',
                keyConcepts: [
                    {
                        term: 'SKU / Código Interno',
                        explanation: 'Identificador único asignado por tu empresa.',
                    },
                    {
                        term: 'Código de Barras',
                        explanation: 'El código EAN/UPC impreso en el empaque para escanear con la lectora.',
                    },
                    {
                        term: 'Maneja Lote / Serial',
                        explanation: 'Indica si requiere seguimiento especial por fecha de vencimiento o número de serie individual.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Inventario > Artículos',
                        description: 'Accede al listado de artículos y presiona "Nuevo Artículo".',
                    },
                    {
                        number: 2,
                        title: 'Llena los datos generales',
                        description: 'Escribe nombre, código, categoría, unidad de medida e impuesto correspondiente.',
                    },
                    {
                        number: 3,
                        title: 'Configura opciones avanzadas',
                        description: 'Si el artículo maneja lotes (alimentos/medicinas) o números de serie (equipos electrónicos), marca la casilla respectiva.',
                    },
                    {
                        number: 4,
                        title: 'Guarda el artículo',
                        description: 'El artículo queda listo para comprarse, almacenarse y venderse.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Asegúrate de colocar descripciones claras. En lugar de "Refresco", pon "Refresco Cola 1.5L" para evitar errores del cajero.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Existencias',
                        topicId: 'inventario-existencias',
                        relationship: 'Al registrar el artículo, sus existencias se distribuyen por bodega.',
                    },
                    {
                        name: 'Terminal POS',
                        topicId: 'pos-terminal',
                        relationship: 'Se busca por nombre o código de barra para la venta inmediata.',
                    },
                ],
                systemPath: '/items',
                keywords: ['artículo', 'producto', 'código de barra', 'sku', 'servicio', 'mercancía'],
            },
            {
                id: 'inventario-bodegas',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'Warehouse',
                title: 'Bodegas / Almacenes',
                subtitle: 'Centros físicos de acopio y almacenamiento',
                summary: 'Gestiona múltiples almacenes físicos: Bodega Principal, Almacén de Tienda, Depósito Secundario o Bodega de Mermas.',
                whatIsIt: 'Representa cada lugar físico donde guardas mercancía. Si tu empresa tiene una tienda en el centro y un galpón en las afueras, cada uno es una bodega distinta en el sistema.',
                whyItMatters: 'Permite saber exactamente en cuál local tienes existencias cuando un cliente pregunta por un producto que se agotó en el mostrador.',
                keyConcepts: [
                    {
                        term: 'Bodega Principal',
                        explanation: 'Almacén predeterminado donde usualmente llega la mercancía de proveedores.',
                    },
                    {
                        term: 'Bodega de Mostrador',
                        explanation: 'El stock disponible de inmediato para ventas en el punto de venta.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Accede a Inventario > Bodegas',
                        description: 'Visualiza las bodegas operativas.',
                    },
                    {
                        number: 2,
                        title: 'Crear nueva bodega',
                        description: 'Haz clic en "Nueva Bodega", escribe su nombre, dirección física y responsable.',
                    },
                    {
                        number: 3,
                        title: 'Asignar a cajas o tiendas',
                        description: 'En el módulo POS o de ventas, vincula qué bodega descargará inventario por defecto.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Crea una bodega especial llamada "Merma / Garantías" para aislar productos dañados o devueltos sin que se ofrezcan a la venta.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Ubicaciones',
                        topicId: 'inventario-ubicaciones',
                        relationship: 'Dentro de cada bodega se crean las estanterías y pasillos.',
                    },
                    {
                        name: 'Existencias',
                        topicId: 'inventario-existencias',
                        relationship: 'El stock de cada producto se calcula por bodega.',
                    },
                ],
                systemPath: '/warehouses',
                keywords: ['bodega', 'almacén', 'depósito', 'galpón', 'sucursal'],
            },
            {
                id: 'inventario-ubicaciones',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'MapPin',
                title: 'Ubicaciones',
                subtitle: 'Direccionamiento exacto dentro de cada bodega (Pasillo, Estante, Nivel)',
                summary: 'Organiza el espacio interno de tus almacenes: Pasillo A, Estante 3, Nivel 2, Gaveta 5.',
                whatIsIt: 'Es la dirección física precisa donde descansa cada caja o paleta dentro de un almacén. Ayuda al equipo de despacho a encontrar cualquier producto en segundos.',
                whyItMatters: 'Reduce drásticamente los tiempos de preparación de pedidos (picking) y evita que los operarios pierdan tiempo buscando mercancía extraviada.',
                keyConcepts: [
                    {
                        term: 'Código de Ubicación',
                        explanation: 'Ejemplo: PAS-01-EST-03-NIV-02 (Pasillo 1, Estante 3, Nivel 2).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Entra a Inventario > Ubicaciones',
                        description: 'Selecciona la bodega a organizar.',
                    },
                    {
                        number: 2,
                        title: 'Registra la ubicación',
                        description: 'Nombra el pasillo, estante o posición.',
                    },
                    {
                        number: 3,
                        title: 'Asigna el producto a su ubicación',
                        description: 'Al dar entrada a mercancía, especifica en qué ubicación se guardó.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Coloca etiquetas con código de barras en las esquinas de cada estante para validar con el lector que el operario está en la posición correcta.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Bodegas',
                        topicId: 'inventario-bodegas',
                        relationship: 'Cada ubicación pertenece a una bodega específica.',
                    },
                    {
                        name: 'Despachos',
                        topicId: 'logistica-despachos',
                        relationship: 'La orden de despacho imprime la ubicación para agilizar la búsqueda.',
                    },
                ],
                systemPath: '/warehouse-locations',
                keywords: ['ubicación', 'estantería', 'pasillo', 'nivel', 'picking', 'almacenamiento'],
            },
            {
                id: 'inventario-existencias',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'Boxes',
                title: 'Existencias',
                subtitle: 'Consulta de cantidades reales, disponibles y reservadas',
                summary: 'Muestra en tiempo real cuántas unidades tienes en stock por artículo y por almacén.',
                whatIsIt: 'Es la pantalla de consulta donde ves el stock físico disponible en cada momento. Diferencia entre lo que tienes físicamente en el estante y lo que ya está reservado o comprometido en una orden de venta pendiente de despacho.',
                whyItMatters: 'Evita sobrevender productos que ya fueron apartados por otro cliente y te ayuda a saber cuándo es momento de reponer mercancía.',
                keyConcepts: [
                    {
                        term: 'Stock Físico',
                        explanation: 'Cantidad real presente en el local.',
                    },
                    {
                        term: 'Stock Comprometido / Reservado',
                        explanation: 'Unidades que están en órdenes de venta aprobadas listas para entregar.',
                    },
                    {
                        term: 'Stock Disponible',
                        explanation: 'Lo que realmente puedes vender ahora mismo (Físico menos Reservado).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Accede a Inventario > Existencias',
                        description: 'Filtra por bodega, categoría o busca por nombre de producto.',
                    },
                    {
                        number: 2,
                        title: 'Revisa las alertas de stock mínimo',
                        description: 'Identifica rápidamente los productos en color amarillo o rojo que necesitan recompra.',
                    },
                ],
                tips: [
                    {
                        type: 'info',
                        text: 'Si el stock físico no coincide con tu conteo en tienda, realiza un ajuste de inventario respaldado con auditoría.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Kardex',
                        topicId: 'inventario-kardex',
                        relationship: 'Cada cambio en las existencias queda registrado en el Kardex.',
                    },
                ],
                systemPath: '/item-stocks',
                keywords: ['stock', 'existencia', 'cantidad', 'disponible', 'inventario disponible', 'reserva'],
            },
            {
                id: 'inventario-lotes',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'Layers',
                title: 'Lotes y Vencimientos',
                subtitle: 'Trazabilidad y control FEFO para productos perecederos',
                summary: 'Gestiona grupos de artículos con fecha de fabricación y fecha de caducidad (ideal para alimentos y fármacos).',
                whatIsIt: 'Permite agrupar las unidades que llegaron en una misma producción o cargamento. El sistema lleva el registro de cuándo vence cada lote para garantizar que se venda primero el que está más próximo a vencer (política FEFO: First Expired, First Out).',
                whyItMatters: 'Protege a tu empresa de pérdidas por mercancía vencida en estantería y de reclamos sanitarios de clientes.',
                keyConcepts: [
                    {
                        term: 'Número de Lote',
                        explanation: 'Código asignado por el fabricante en el empaque.',
                    },
                    {
                        term: 'Fecha de Vencimiento',
                        explanation: 'Límite máximo recomendado para su venta y consumo.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Activa la opción en el artículo',
                        description: 'En la ficha del producto, marca "Maneja Lote".',
                    },
                    {
                        number: 2,
                        title: 'Registra el lote en la entrada o compra',
                        description: 'Al dar entrada a la mercancía, el sistema te solicitará el número de lote y su fecha de vencimiento.',
                    },
                    {
                        number: 3,
                        title: 'Salida automática o seleccionada',
                        description: 'Al facturar o despachar, el sistema sugiere el lote con vencimiento más cercano.',
                    },
                ],
                tips: [
                    {
                        type: 'warning',
                        text: 'Revisa periódicamente el reporte de lotes para poner en oferta o liquidar aquellos productos a los que les queden menos de 30 días de vida útil.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Entradas de Mercancía',
                        topicId: 'logistica-entradas',
                        relationship: 'Aquí se capturan los datos de lote al recibir la compra.',
                    },
                ],
                systemPath: '/item-lots',
                keywords: ['lote', 'vencimiento', 'caducidad', 'fefo', 'fecha de vencimiento', 'perecedero'],
            },
            {
                id: 'inventario-series',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'Barcode',
                title: 'Series (Números de Serie)',
                subtitle: 'Seguimiento unitario individualizado por serial o IMEI',
                summary: 'Control único para cada unidad física de alto valor: teléfonos, computadoras, motores, electrodomésticos.',
                whatIsIt: 'A diferencia de los productos que se cuentan por volumen, ciertos artículos de tecnología o maquinaria tienen una "cédula de identidad" propia: un número de serie único.',
                whyItMatters: 'Es indispensable para validar reclamos de garantía. Cuando un cliente regresa un equipo por falla, verificas en el sistema si ese serial exacto fue vendido por ti y si su garantía sigue vigente.',
                keyConcepts: [
                    {
                        term: 'Serial / IMEI',
                        explanation: 'Cadena alfanumérica única grabada en el equipo.',
                    },
                    {
                        term: 'Estado del Serial',
                        explanation: 'Disponible en Almacén, Vendido al Cliente, En Garantía, o De Baja.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Habilita "Maneja Serial" en el artículo',
                        description: 'Configura la ficha técnica del producto como seriado.',
                    },
                    {
                        number: 2,
                        title: 'Captura los seriales en la recepción',
                        description: 'Al recibir la compra, escanea o digita cada serial recibido.',
                    },
                    {
                        number: 3,
                        title: 'Selecciona el serial en la venta formal',
                        description: 'Al emitir la factura de venta o despacho desde el módulo de Ventas, el sistema te pedirá indicar o escanear el serial que estás entregando al cliente. Los artículos serializados no están disponibles para venta en el terminal POS.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'El serial queda impreso en la factura de venta del cliente, sirviendo como su comprobante legal de garantía.',
                    },
                    {
                        type: 'warning',
                        text: 'Los artículos controlados por serie solo pueden venderse por pedido formal o facturación directa en Ventas; por políticas de trazabilidad unitaria no aparecen en el terminal POS.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'El número de serial queda vinculado al cliente que compró.',
                    },
                ],
                systemPath: '/item-serials',
                keywords: ['serial', 'imei', 'número de serie', 'garantía', 'electrónica', 'trazabilidad'],
            },
            {
                id: 'inventario-kardex',
                moduleId: 'inventario',
                moduleName: 'Inventario',
                moduleIcon: 'ClipboardList',
                title: 'Kardex (Movimientos de Inventario)',
                subtitle: 'Libro mayor de auditoría y movimientos cronológicos de stock',
                summary: 'El historial inmutable de cada entrada, salida, venta, compra y traslado con fechas, documentos y costos.',
                whatIsIt: 'El Kardex es la "caja negra" del inventario. Cada vez que una unidad se mueve en el sistema (únicamente a través de Entrada, Despacho o Ajuste), el Kardex anota la fecha, la hora, el usuario, el documento de respaldo y el saldo restante.',
                whyItMatters: 'Si en algún momento el inventario no cuadra, no hay misterio: vas al Kardex y ves exactamente quién y cuándo registró cada movimiento. Nunca se puede alterar manualmente.',
                keyConcepts: [
                    {
                        term: 'Entrada (+)',
                        explanation: 'Movimientos que aumentan existencias (entradas de almacén o ajustes positivos).',
                    },
                    {
                        term: 'Salida (-)',
                        explanation: 'Movimientos que restan existencias (despachos de almacén o ajustes negativos).',
                    },
                    {
                        term: 'Saldo Resultante',
                        explanation: 'La cantidad que quedó en bodega tras esa transacción específica.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ingresa a Inventario > Kardex / Movimientos',
                        description: 'Abre la consulta de movimientos.',
                    },
                    {
                        number: 2,
                        title: 'Filtra por artículo y rango de fechas',
                        description: 'Elige el producto que deseas auditar y la bodega.',
                    },
                    {
                        number: 3,
                        title: 'Revisa el detalle del documento',
                        description: 'Haz clic en el número de documento de cualquier línea para ver la entrada, despacho o ajuste original.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Realiza arqueos de inventario comparando las existencias físicas de tus 10 productos más vendidos contra el saldo del Kardex.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Entradas (Recepción)',
                        topicId: 'logistica-entradas',
                        relationship: 'Registra los ingresos y aumentos de stock en el Kardex.',
                    },
                    {
                        name: 'Despachos (Salidas)',
                        topicId: 'logistica-despachos',
                        relationship: 'Registra las salidas físicas de mercancía en el Kardex.',
                    },
                ],
                systemPath: '/inventory-movements',
                keywords: ['kardex', 'movimientos', 'historial', 'auditoría', 'entradas y salidas', 'trazabilidad'],
            },
        ],
    },
    {
        id: 'compras',
        name: 'Compras',
        icon: 'ShoppingCart',
        badge: '4 temas',
        description: 'Ciclo completo de abastecimiento: proveedores, órdenes, facturas y pagos.',
        topics: [
            {
                id: 'compras-proveedores',
                moduleId: 'compras',
                moduleName: 'Compras',
                moduleIcon: 'Building2',
                title: 'Proveedores',
                subtitle: 'Directorio y ficha técnica de tus socios de abastecimiento',
                summary: 'Registra los datos fiscales, de contacto, cuentas bancarias y condiciones de crédito de tus abastecedores.',
                whatIsIt: 'Es la libreta comercial donde guardas a todas las empresas o personas a quienes les compras mercancía o contratas servicios. Permite consultar el estado de cuenta y cuánto dinero le debes a cada uno.',
                whyItMatters: 'Tener los datos fiscales (RIF, razón social, dirección) listos y validados evita contratiempos tributarios y asegura pagos puntuales a tus aliados clave.',
                keyConcepts: [
                    {
                        term: 'RIF / Identificación Fiscal',
                        explanation: 'Número de registro tributario único del proveedor.',
                    },
                    {
                        term: 'Días de Crédito',
                        explanation: 'Plazo otorgado por el proveedor antes de que venza una factura.',
                    },
                    {
                        term: 'Saldo Pendiente',
                        explanation: 'Total acumulado en facturas no pagadas.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Compras > Proveedores',
                        description: 'Visualiza la lista y presiona "Nuevo Proveedor".',
                    },
                    {
                        number: 2,
                        title: 'Completa los datos fiscales',
                        description: 'Ingresa RIF, Razón Social, Teléfono, Correo para facturación y dirección.',
                    },
                    {
                        number: 3,
                        title: 'Fija condiciones comerciales',
                        description: 'Selecciona el Tipo de Proveedor y los días de crédito acordados.',
                    },
                    {
                        number: 4,
                        title: 'Guardar',
                        description: 'El proveedor queda listo para recibir órdenes de compra o facturas.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Guarda los correos oficiales de cobranza del proveedor para que el sistema le envíe comprobantes de pago automáticamente.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Órdenes de Compra',
                        topicId: 'compras-ordenes-compra',
                        relationship: 'Las órdenes se emiten a nombre del proveedor.',
                    },
                    {
                        name: 'Pagos a Proveedor',
                        topicId: 'compras-pagos-proveedor',
                        relationship: 'Se aplican sobre las facturas pendientes del proveedor.',
                    },
                ],
                systemPath: '/suppliers',
                keywords: ['proveedor', 'compras', 'rif', 'contacto', 'cuentas por pagar'],
            },
            {
                id: 'compras-ordenes-compra',
                moduleId: 'compras',
                moduleName: 'Compras',
                moduleIcon: 'ShoppingCart',
                title: 'Órdenes de Compra',
                subtitle: 'Solicitud formal y cotización previa de mercancía',
                summary: 'Documenta los pedidos de mercancía antes de recibirlos o pagarlos. No altera inventario ni genera deuda.',
                whatIsIt: 'Es el documento formal con el que le dices al proveedor: "Quiero comprarte estos 50 productos a este precio pactado". Es una promesa de compra que te sirve para planificar tus finanzas.',
                whyItMatters: 'Evita que el proveedor te envíe mercancía no solicitada o te cobre precios distintos a los acordados. Cuando llega el pedido a almacén, se coteja contra la orden original.',
                keyConcepts: [
                    {
                        term: 'Estado de la Orden',
                        explanation: 'Borrador, Aprobada, Recibida Parcial, Recibida Total o Cancelada.',
                    },
                    {
                        term: 'Fecha Estimada de Entrega',
                        explanation: 'El día en que el proveedor se comprometió a entregar la carga en tu almacén.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Accede a Compras > Órdenes de Compra',
                        description: 'Haz clic en "Nueva Orden de Compra".',
                    },
                    {
                        number: 2,
                        title: 'Elige proveedor y bodega de destino',
                        description: 'Selecciona a quién le compras y en cuál almacén esperas recibir los artículos.',
                    },
                    {
                        number: 3,
                        title: 'Agrega los artículos y cantidades',
                        description: 'Indica qué productos requieres y el costo unitario negociado.',
                    },
                    {
                        number: 4,
                        title: 'Aprobar orden',
                        description: 'Al aprobar la orden de compra se genera automáticamente una entrada de inventario, que es la que mueve existencia al ser confirmada en almacén. Además, la orden queda lista para exportarse en PDF o enviarse por correo al proveedor.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: '¡Ahorra tiempo! Cuando la mercancía llegue físicamente, puedes convertir esta Orden directamente en una Factura de Compra con un solo clic.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Entradas (Recepción)',
                        topicId: 'logistica-entradas',
                        relationship: 'Al aprobar la orden se genera la entrada de inventario que moverá la existencia.',
                    },
                    {
                        name: 'Facturas de Compra',
                        topicId: 'compras-facturas-compra',
                        relationship: 'Una orden aprobada se transforma en factura al recibir el pedido.',
                    },
                ],
                systemPath: '/purchase-orders',
                keywords: ['orden de compra', 'pedido a proveedor', 'solicitud', 'cotización de compra'],
            },
            {
                id: 'compras-facturas-compra',
                moduleId: 'compras',
                moduleName: 'Compras',
                moduleIcon: 'FileText',
                title: 'Facturas de Compra',
                subtitle: 'Comprobante fiscal, costos de compra y cuenta por pagar',
                summary: 'Registra la factura legal emitida por el proveedor y genera la deuda contable. La factura no actualiza el Kardex: únicamente Entrada, Despacho y Ajuste mueven el Kardex.',
                whatIsIt: 'Es el documento formal emitido por el proveedor. Cuando llega la factura física o electrónica, la digitas aquí para registrar los costos pactados y generar la cuenta por pagar. Recuerda que la factura no actualiza el Kardex ni mueve existencias directas: la entrada física y el saldo en Kardex se gestionan mediante el documento de Entrada de almacén.',
                whyItMatters: 'Determina el costo formal de compra y la cuenta por pagar con el proveedor. Un registro exacto asegura que los costos declarados coincidan con los comprobantes fiscales y que la deuda comercial esté al día.',
                keyConcepts: [
                    {
                        term: 'Número de Control / Factura',
                        explanation: 'El número fiscal impreso en el comprobante del proveedor.',
                    },
                    {
                        term: 'Fecha de Vencimiento de Factura',
                        explanation: 'El día límite para pagar sin caer en mora comercial.',
                    },
                    {
                        term: 'Crédito Fiscal IVA',
                        explanation: 'El impuesto que pagaste y que podrás descontar de tu débito fiscal en la declaración mensual.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Compras > Facturas de Compra',
                        description: 'Presiona "Nueva Factura de Compra" (o convierte desde una Orden de Compra previa).',
                    },
                    {
                        number: 2,
                        title: 'Ingresa los datos del documento',
                        description: 'Coloca número de factura del proveedor, número de control, fecha de emisión y fecha de pago.',
                    },
                    {
                        number: 3,
                        title: 'Verifica renglones, costos e impuestos',
                        description: 'Asegúrate de que las cantidades, costos e IVA coincidan con la factura física.',
                    },
                    {
                        number: 4,
                        title: 'Procesar y asentar',
                        description: 'Al guardar, la factura pasa al estado de cuenta del proveedor para gestionar su pago.',
                    },
                ],
                tips: [
                    {
                        type: 'warning',
                        text: 'Revisa minuciosamente que el total con impuestos coincida exactamente con el papel del proveedor antes de procesar.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Entradas de Almacén',
                        topicId: 'logistica-entradas',
                        relationship: 'Es el documento que realmente ingresa la mercancía al inventario y mueve el Kardex.',
                    },
                    {
                        name: 'Pagos a Proveedor',
                        topicId: 'compras-pagos-proveedor',
                        relationship: 'Las facturas procesadas esperan por su pago.',
                    },
                ],
                systemPath: '/purchase-invoices',
                keywords: ['factura de compra', 'cuenta por pagar', 'crédito fiscal', 'costo', 'proveedor'],
            },
            {
                id: 'compras-pagos-proveedor',
                moduleId: 'compras',
                moduleName: 'Compras',
                moduleIcon: 'CreditCard',
                title: 'Pagos a Proveedor',
                subtitle: 'Liquidación de facturas, egresos bancarios y finiquitos',
                summary: 'Registra los pagos realizados a proveedores (transferencias en Bs, divisas en efectivo, Zelle, etc.) saldando sus deudas.',
                whatIsIt: 'Es el comprobante de egreso. Cuando transfieres dinero a la cuenta del proveedor o le entregas efectivo, registras aquí la operación asociándola a la factura o facturas que estás pagando.',
                whyItMatters: 'Mantiene tus finanzas limpias. Te dice en tiempo real cuánto dinero tienes realmente comprometido y evita que pagues dos veces la misma factura.',
                keyConcepts: [
                    {
                        term: 'Pago Total vs. Abono Parcial',
                        explanation: 'Puedes saldar la factura completa o pagar solo una fracción y dejar el resto pendiente.',
                    },
                    {
                        term: 'Método de Pago',
                        explanation: 'Cuenta bancaria de salida, caja chica o efectivo divisa.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Entra a Compras > Pagos a Proveedor',
                        description: 'Haz clic en "Registrar Pago".',
                    },
                    {
                        number: 2,
                        title: 'Selecciona el proveedor',
                        description: 'El sistema cargará automáticamente todas las facturas pendientes que tienes con él.',
                    },
                    {
                        number: 3,
                        title: 'Indica el monto y método de pago',
                        description: 'Coloca el importe pagado, fecha, número de referencia bancaria y cuenta de origen.',
                    },
                    {
                        number: 4,
                        title: 'Aplica el pago y confirma',
                        description: 'El saldo adeudado disminuye de inmediato y se genera el recibo de egreso.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Siempre escribe el número de referencia bancaria para facilitar las conciliaciones bancarias al cierre de mes.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Compra',
                        topicId: 'compras-facturas-compra',
                        relationship: 'El pago cambia el estado de la factura a "Pagada".',
                    },
                ],
                systemPath: '/supplier-payments',
                keywords: ['pago', 'egreso', 'transferencia', 'abono', 'proveedor', 'liquidación'],
            },
        ],
    },
    {
        id: 'ventas',
        name: 'Ventas',
        icon: 'ShoppingBag',
        badge: '4 temas',
        description: 'Gestión de clientes, cotizaciones, órdenes de venta, facturación y cuentas por cobrar.',
        topics: [
            {
                id: 'ventas-clientes',
                moduleId: 'ventas',
                moduleName: 'Ventas',
                moduleIcon: 'Users',
                title: 'Clientes',
                subtitle: 'Directorio comercial, crédito y ficha de clientes',
                summary: 'Administra la cartera de clientes: datos fiscales (RIF/CI), teléfonos, correos para factura electrónica y límites de crédito.',
                whatIsIt: 'Es la base de datos de todas las personas y empresas que te compran. Puedes ver en un solo lugar cuántas compras te han hecho, su saldo adeudado y su historial de pagos.',
                whyItMatters: 'Permite personalizar la atención comercial, fijar límites de crédito para no sobreexponer a la empresa y enviar estados de cuenta automáticos.',
                keyConcepts: [
                    {
                        term: 'Documento de Identidad',
                        explanation: 'Cédula de Identidad (V/E) o RIF (J/G) obligatorio para facturación legal.',
                    },
                    {
                        term: 'Límite de Crédito',
                        explanation: 'Monto máximo que el cliente puede adeudar antes de que el sistema bloquee nuevas ventas a crédito.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Ventas > Clientes',
                        description: 'Consulta los clientes registrados y presiona "Nuevo Cliente".',
                    },
                    {
                        number: 2,
                        title: 'Llena los datos fiscales',
                        description: 'Escribe RIF/Cédula, Razón Social o Nombre, Dirección Fiscal, Teléfono y Correo Electrónico.',
                    },
                    {
                        number: 3,
                        title: 'Configura Tipo y Condiciones',
                        description: 'Selecciona su Tipo de Cliente (Detal, Mayorista) y si tiene habilitado crédito.',
                    },
                    {
                        number: 4,
                        title: 'Guardar',
                        description: 'Queda habilitado inmediatamente en el sistema y en el Punto de Venta.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Crea un cliente genérico "Cliente Contado / Consumidor Final" para ventas rápidas de mostrador donde el cliente no requiera factura con RIF.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'Las facturas se emiten a nombre del cliente seleccionado.',
                    },
                    {
                        name: 'Cobros a Clientes',
                        topicId: 'ventas-cobros-clientes',
                        relationship: 'Muestra el estado de cuenta y deuda del cliente.',
                    },
                ],
                systemPath: '/clients',
                keywords: ['cliente', 'cartera', 'crédito', 'rif', 'cédula', 'contacto'],
            },
            {
                id: 'ventas-ordenes-venta',
                moduleId: 'ventas',
                moduleName: 'Ventas',
                moduleIcon: 'ClipboardList',
                title: 'Órdenes de Venta',
                subtitle: 'Pedidos comerciales, cotizaciones y reservas de mercancía',
                summary: 'Registra los pedidos confirmados de clientes. Permite reservar inventario y enviar la orden a almacén para empaque.',
                whatIsIt: 'Es el documento comercial donde anotas el pedido que te hizo un cliente antes de emitir la factura definitiva o antes de despachar. Sirve tanto como presupuesto/cotización o como orden en firme.',
                whyItMatters: 'Permite apartar el inventario para que otro vendedor no lo venda en el mostrador mientras el almacén prepara el paquete.',
                keyConcepts: [
                    {
                        term: 'Reserva de Stock',
                        explanation: 'Las unidades del pedido pasan al estado "Reservado" sin descontarse aún del inventario físico total.',
                    },
                    {
                        term: 'Vigencia de Cotización',
                        explanation: 'Días durante los cuales mantienes los precios ofrecidos al cliente.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Accede a Ventas > Órdenes de Venta',
                        description: 'Haz clic en "Nueva Orden de Venta".',
                    },
                    {
                        number: 2,
                        title: 'Selecciona cliente y lista de precios',
                        description: 'Elige el cliente. El sistema cargará su lista de precios acordada.',
                    },
                    {
                        number: 3,
                        title: 'Agrega productos y condiciones',
                        description: 'Agrega los renglones requeridos con sus descuentos autorizados.',
                    },
                    {
                        number: 4,
                        title: 'Confirmar orden',
                        description: 'Al confirmar, el almacén recibe la notificación para preparar el despacho.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Puedes transformar una orden aprobada en Factura de Venta o en Orden de Despacho con un solo clic, sin volver a teclear nada.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Despachos',
                        topicId: 'logistica-despachos',
                        relationship: 'El personal de almacén utiliza la orden para preparar la entrega.',
                    },
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'Se factura directamente desde la orden confirmada.',
                    },
                ],
                systemPath: '/sales-orders',
                keywords: ['orden de venta', 'pedido', 'cotización', 'presupuesto', 'reserva'],
            },
            {
                id: 'ventas-facturas-venta',
                moduleId: 'ventas',
                moduleName: 'Ventas',
                moduleIcon: 'Receipt',
                title: 'Facturas de Venta',
                subtitle: 'Emisión legal de comprobantes de ingreso y débito fiscal',
                summary: 'Documento fiscal oficial que acredita la venta de bienes o servicios. Registra el débito fiscal y genera la cuenta por cobrar.',
                whatIsIt: 'Es el comprobante formal de la transacción comercial. Al emitirse, asienta el ingreso contable, genera el débito fiscal de IVA y, si no fue pagada de contado, crea la cuenta por cobrar a nombre del cliente. Ten presente que la factura no actualiza el Kardex: la salida física y el movimiento de existencias los realiza el Despacho de almacén.',
                whyItMatters: 'Es el documento con validez legal y tributaria. Debe emitirse con exactitud para evitar sanciones fiscales o discrepancias con el comprador.',
                keyConcepts: [
                    {
                        term: 'Factura de Contado',
                        explanation: 'El cliente paga en el momento en que se emite la factura.',
                    },
                    {
                        term: 'Factura a Crédito',
                        explanation: 'El cliente se lleva la mercancía y la factura queda pendiente en su estado de cuenta hasta la fecha de vencimiento.',
                    },
                    {
                        term: 'Notas de Crédito',
                        explanation: 'Documento legal para anular o devolver una factura emitida previamente (el sistema nunca borra facturas).',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Ventas > Facturas de Venta',
                        description: 'Presiona "Nueva Factura" (o factura directamente desde una Orden de Venta).',
                    },
                    {
                        number: 2,
                        title: 'Selecciona cliente y moneda',
                        description: 'Verifica los datos del cliente y confirma si la factura se expresará en bolívares o en divisas a la tasa del día.',
                    },
                    {
                        number: 3,
                        title: 'Agrega los productos',
                        description: 'Revisa precios, alícuotas de impuestos y descuentos aplicables.',
                    },
                    {
                        number: 4,
                        title: 'Emite y genera comprobante',
                        description: 'Al procesar, puedes imprimir la factura o descargarla en PDF para entregar al comprador.',
                    },
                ],
                tips: [
                    {
                        type: 'warning',
                        text: 'Una factura procesada no se puede eliminar. Si hubo un error en cantidades o precios, emite una Nota de Crédito por el monto a corregir.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Despachos',
                        topicId: 'logistica-despachos',
                        relationship: 'Es el documento que realmente rebaja las existencias y actualiza el Kardex.',
                    },
                    {
                        name: 'Cobros a Clientes',
                        topicId: 'ventas-cobros-clientes',
                        relationship: 'Si fue a crédito, espera por su cobro.',
                    },
                ],
                systemPath: '/sales-invoices',
                keywords: ['factura', 'venta', 'fiscal', 'débito fiscal', 'cobranza', 'comprobante'],
            },
            {
                id: 'ventas-cobros-clientes',
                moduleId: 'ventas',
                moduleName: 'Ventas',
                moduleIcon: 'CreditCard',
                title: 'Cobros a Clientes',
                subtitle: 'Recuperación de cartera, recibos de caja y conciliación de pagos',
                summary: 'Registra los ingresos recibidos por facturas a crédito o pagos adelantados de clientes.',
                whatIsIt: 'Es el recibo de cobro oficial de la empresa. Cuando un cliente te transfiere por banco, te paga con tarjeta o te entrega divisas para cancelar una factura pendiente, registras aquí el cobro.',
                whyItMatters: 'Permite mantener la cartera de crédito al día, alimentar los saldos de tus bancos y cajas, y emitir al cliente un recibo formal de solvencia.',
                keyConcepts: [
                    {
                        term: 'Recibo de Cobro',
                        explanation: 'Comprobante numerado que certifica la recepción del dinero.',
                    },
                    {
                        term: 'Cobro Multimoneda',
                        explanation: 'Si una factura está emitida en USD, el cliente puede pagarla en USD o su equivalente en bolívares según la tasa acordada.',
                    },
                    {
                        term: 'Anticipos de Cliente',
                        explanation: 'Dinero recibido antes de emitir la factura que queda a favor del cliente para futuras compras.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Entra a Ventas > Cobros a Clientes',
                        description: 'Haz clic en "Registrar Cobro".',
                    },
                    {
                        number: 2,
                        title: 'Busca el cliente',
                        description: 'El sistema listará todas las facturas con saldo pendiente.',
                    },
                    {
                        number: 3,
                        title: 'Indica las formas de pago',
                        description: 'Ingresa los montos recibidos (ej. $50 en efectivo y el equivalente en pago móvil bancario).',
                    },
                    {
                        number: 4,
                        title: 'Aplica a las facturas y guarda',
                        description: 'El saldo del cliente se reduce al instante y puedes imprimir el recibo de cobro.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Verifica los fondos en la cuenta bancaria antes de procesar el cobro en el sistema.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Facturas de Venta',
                        topicId: 'ventas-facturas-venta',
                        relationship: 'Cambia el estado de la factura a "Cobrada".',
                    },
                ],
                systemPath: '/client-collections',
                keywords: ['cobro', 'ingreso', 'recibo de caja', 'cartera', 'cuentas por cobrar', 'abono'],
            },
        ],
    },
    {
        id: 'logistica',
        name: 'Logística',
        icon: 'Truck',
        badge: '2 temas',
        description: 'Movimiento físico en almacenes: despachos de salida y recepciones de entrada.',
        topics: [
            {
                id: 'logistica-despachos',
                moduleId: 'logistica',
                moduleName: 'Logística',
                moduleIcon: 'ArrowUpFromLine',
                title: 'Despachos (Salidas de Almacén)',
                subtitle: 'Control operativo de preparación (picking), embalaje y entrega de pedidos',
                summary: 'Controla la salida física de mercancía desde los estantes hacia el transporte o las manos del cliente.',
                whatIsIt: 'Es la orden de entrega física. En empresas donde la oficina de ventas está separada del almacén, ventas emite la orden y el personal de bodega utiliza este módulo para armar el bulto, verificar las cantidades con lector y autorizar la salida del camión.',
                whyItMatters: 'Elimina las discrepancias entre lo facturado y lo entregado. Garantiza que ningún paquete salga sin verificación previa de contenido.',
                keyConcepts: [
                    {
                        term: 'Guía de Despacho',
                        explanation: 'Documento que acompaña la carga en el transporte con el detalle de bultos y precintos.',
                    },
                    {
                        term: 'Despacho Parcial',
                        explanation: 'Ocurre cuando se entrega solo una parte del pedido por limitaciones de transporte o stock.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Logística > Despachos',
                        description: 'Consulta las órdenes de venta pendientes de preparación.',
                    },
                    {
                        number: 2,
                        title: 'Crear Despacho',
                        description: 'Selecciona la orden de venta a procesar. El sistema muestra la ubicación exacta de cada producto en bodega.',
                    },
                    {
                        number: 3,
                        title: 'Escanear o verificar artículos',
                        description: 'Confirma los productos recogidos en estantería (picking) y asigna el transportista.',
                    },
                    {
                        number: 4,
                        title: 'Autorizar salida',
                        description: 'Al despachar, el stock se descuenta físicamente y se imprime la guía de transporte.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Pide al transportista y al cliente que firmen la guía de despacho física como comprobante irrefutable de entrega conforme.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Órdenes de Venta',
                        topicId: 'ventas-ordenes-venta',
                        relationship: 'Cada despacho se origina de una orden de venta.',
                    },
                    {
                        name: 'Existencias',
                        topicId: 'inventario-existencias',
                        relationship: 'Descarga las unidades del inventario físico.',
                    },
                ],
                systemPath: '/dispatches',
                keywords: ['despacho', 'salida', 'picking', 'entrega', 'guía de remisión', 'transporte'],
            },
            {
                id: 'logistica-entradas',
                moduleId: 'logistica',
                moduleName: 'Logística',
                moduleIcon: 'ArrowDownToLine',
                title: 'Entradas (Recepción de Mercancía)',
                subtitle: 'Verificación física, conteo e inspección de calidad en almacén',
                summary: 'Valida físicamente la llegada de bultos por compras a proveedores, devoluciones o traslados.',
                whatIsIt: 'Es el proceso de recepción en la compuerta del almacén. Cuando llega un camión de un proveedor, el equipo de bodega cuenta las cajas, revisa el estado de los empaques, valida los números de lote y da ingreso formal al sistema.',
                whyItMatters: 'Si no revisas la mercancía antes de guardarla en estantes, podrías aceptar cajas rotas o productos faltantes que luego te costará reclamar.',
                keyConcepts: [
                    {
                        term: 'Recepción Conforme vs. Discrepancia',
                        explanation: 'Registra si llegaron exactamente las cantidades compradas o si faltó producto.',
                    },
                    {
                        term: 'Asignación de Ubicación',
                        explanation: 'Indica a qué pasillo y estante se envió la mercancía recibida.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Entra a Logística > Entradas',
                        description: 'Presiona "Nueva Entrada de Almacén".',
                    },
                    {
                        number: 2,
                        title: 'Asocia la Orden de Compra',
                        description: 'Selecciona la orden pendiente de entrega para comparar lo pedido con lo que realmente llegó.',
                    },
                    {
                        number: 3,
                        title: 'Ingresa cantidades recibidas y lotes',
                        description: 'Digita las unidades contadas y especifica lotes o seriales si aplica.',
                    },
                    {
                        number: 4,
                        title: 'Confirmar entrada',
                        description: 'Las unidades quedan disponibles para la venta de inmediato.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Si llega mercancía en mal estado, recházala en la propia entrada para que compras proceda a pedir la reposición de inmediato.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Órdenes de Compra',
                        topicId: 'compras-ordenes-compra',
                        relationship: 'Se coteja contra la orden original emitida al proveedor.',
                    },
                    {
                        name: 'Kardex',
                        topicId: 'inventario-kardex',
                        relationship: 'Registra el incremento oficial de existencias.',
                    },
                ],
                systemPath: '/entries',
                keywords: ['entrada', 'recepción', 'conteo', 'almacén', 'ingreso de mercancía'],
            },
        ],
    },
    {
        id: 'pos',
        name: 'Punto de Venta',
        icon: 'Monitor',
        badge: '3 temas',
        description: 'Ventas de mostrador, cajeros, apertura y cierre de caja y terminal POS táctil.',
        topics: [
            {
                id: 'pos-terminal',
                moduleId: 'pos',
                moduleName: 'Punto de Venta',
                moduleIcon: 'Monitor',
                title: 'Terminal POS (Punto de Venta)',
                subtitle: 'Pantalla de facturación ultra-rápida para cajeros y mostradores',
                summary: 'La interfaz ágil para atender clientes en segundos: escaneo de código de barras, cobro mixto (USD/VES) y emisión de ticket.',
                whatIsIt: 'Es la pantalla especialmente diseñada para cajeros en tiendas físicas, restaurantes o farmacias. En lugar de navegar por formularios largos, el cajero solo escanea con la lectora, presiona Cobrar, elige cómo paga el cliente (efectivo, tarjeta, pago móvil o mixto) y el ticket se imprime al instante.',
                whyItMatters: 'Velocidad de atención en horas pico. Reduce las filas en caja y minimiza los errores de digitación de los operadores.',
                keyConcepts: [
                    {
                        term: 'Búsqueda por Lector de Barras',
                        explanation: 'Solo pasa el producto por la lectora y se agrega automáticamente al carrito.',
                    },
                    {
                        term: 'Cobro Mixto / Bimonetario',
                        explanation: 'Permite pagar una parte en dólares en efectivo y el resto en bolívares por tarjeta o pago móvil.',
                    },
                    {
                        term: 'Venta Rápida a Consumidor Final',
                        explanation: 'No obliga a pedir datos completos a clientes que compran artículos menores.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Abrir el Terminal POS',
                        description: 'Ingresa al módulo POS. Asegúrate de tener una sesión de caja abierta.',
                    },
                    {
                        number: 2,
                        title: 'Escanear o buscar productos',
                        description: 'Dispara con la lectora de código de barras o busca por nombre en la barra rápida.',
                    },
                    {
                        number: 3,
                        title: 'Seleccionar cliente (opcional)',
                        description: 'Por defecto vende a Consumidor Final, pero puedes presionar Buscar Cliente si pide factura personalizada.',
                    },
                    {
                        number: 4,
                        title: 'Presionar "Cobrar" y registrar pagos',
                        description: 'Elige las formas de pago, digita el monto recibido y el sistema te calculará el vuelto exacto.',
                    },
                    {
                        number: 5,
                        title: 'Imprimir comprobante',
                        description: 'El ticket se imprime en la impresora térmica',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Utiliza los atajos de teclado para no tocar el ratón: te ahorrará entre 15 y 20 segundos por cada cliente atendido.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Sesiones y Cortes',
                        topicId: 'pos-sesiones-cortes',
                        relationship: 'El terminal opera dentro de una sesión de caja activa.',
                    },
                    {
                        name: 'Existencias',
                        topicId: 'inventario-existencias',
                        relationship: 'Descuenta automáticamente el stock de la bodega vinculada.',
                    },
                ],
                systemPath: '/pos',
                keywords: ['pos', 'caja', 'terminal', 'ticket', 'lector de barras', 'cajero', 'mostrador'],
            },
            {
                id: 'pos-sesiones-cortes',
                moduleId: 'pos',
                moduleName: 'Punto de Venta',
                moduleIcon: 'CalendarCheck',
                title: 'Sesiones y Cortes de Caja',
                subtitle: 'Aperturas de turno, arqueos ciegos y cierre diario (Corte Z / X)',
                summary: 'Controla el flujo de dinero por turno: apertura con fondo de cambio, control de ventas y arqueo de cierre.',
                whatIsIt: 'Es el proceso que garantiza que el dinero físico en la gaveta coincida con las ventas registradas. Cada cajero abre su turno declarando su fondo inicial de sencillo para dar vuelto, y al terminar el turno realiza el conteo del dinero para cerrar la sesión.',
                whyItMatters: 'Previene fugas de dinero, robos y descuadres de caja. Detecta al instante si hubo sobrantes o faltantes en el turno de cada empleado.',
                keyConcepts: [
                    {
                        term: 'Fondo de Caja (Monto Inicial)',
                        explanation: 'El dinero base entregado al cajero al inicio del día para dar cambio.',
                    },
                    {
                        term: 'Arqueo a Ciegas',
                        explanation: 'El cajero cuenta y anota los billetes sin ver en pantalla el total del sistema, garantizando honestidad en el conteo.',
                    },
                    {
                        term: 'Corte Z / Cierre Definitivo',
                        explanation: 'El reporte final del día que bloquea la sesión y totaliza impuestos y ventas por método de pago.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Apertura de Sesión',
                        description: 'Al iniciar la jornada, el cajero abre la sesión e introduce el monto del fondo de caja en efectivo.',
                    },
                    {
                        number: 2,
                        title: 'Operación durante el turno',
                        description: 'Todas las ventas, entradas o salidas de efectivo quedan vinculadas a esa sesión.',
                    },
                    {
                        number: 3,
                        title: 'Arqueo y Cierre',
                        description: 'Al finalizar el turno, presiona "Cerrar Caja". Cuenta el efectivo por denominación y los recibos de punto de venta.',
                    },
                    {
                        number: 4,
                        title: 'Generar reporte de diferencias',
                        description: 'El sistema calcula si la caja cuadra perfectamente o si hay diferencia (sobrante/faltante) y emite el resumen de corte.',
                    },
                ],
                tips: [
                    {
                        type: 'warning',
                        text: 'Nunca dejes una caja abierta de un día para otro. Cada jornada debe comenzar con una apertura fresca y terminar con su respectivo corte.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Terminal POS',
                        topicId: 'pos-terminal',
                        relationship: 'El cajero solo puede facturar si la sesión está abierta.',
                    },
                    {
                        name: 'Cajas Registradoras',
                        topicId: 'pos-cajas-registradoras',
                        relationship: 'La sesión se abre dentro de una caja registrada.',
                    },
                ],
                systemPath: '/pos/sessions',
                keywords: ['corte', 'cierre de caja', 'arqueo', 'sesión', 'fondo de caja', 'cuadre', 'corte z'],
            },
            {
                id: 'pos-cajas-registradoras',
                moduleId: 'pos',
                moduleName: 'Punto de Venta',
                moduleIcon: 'Store',
                title: 'Cajas Registradoras',
                subtitle: 'Configuración de puestos físicos de cobro e impresoras térmicas',
                summary: 'Configura cada equipo o mostrador de cobro: Caja 1, Caja Rápida, Caja Delivery, etc.',
                whatIsIt: 'Representa el punto físico o estación de trabajo de cobro dentro de tu tienda. Cada caja física tiene configurado a qué almacén descuenta stock, qué gaveta de dinero abre y qué serie de recibos utiliza.',
                whyItMatters: 'Si tienes 3 cajeros trabajando simultáneamente, cada uno tiene su propia caja registradora para que sus arqueos de dinero no se mezclen.',
                keyConcepts: [
                    {
                        term: 'Almacén por Defecto de la Caja',
                        explanation: 'La bodega física de la cual esta caja resta el inventario al vender.',
                    },
                    {
                        term: 'Usuarios Asignados',
                        explanation: 'Cajeros autorizados para operar esta caja.',
                    },
                ],
                stepByStep: [
                    {
                        number: 1,
                        title: 'Ve a Punto de Venta > Cajas Registradoras',
                        description: 'Consulta las cajas habilitadas.',
                    },
                    {
                        number: 2,
                        title: 'Crear nueva caja',
                        description: 'Presiona "Nueva Caja", nómbrala (ej. "Caja Mostrador 1") y asígnale su almacén.',
                    },
                    {
                        number: 3,
                        title: 'Asignar permisos',
                        description: 'Define qué usuarios pueden abrir turno en esta máquina.',
                    },
                ],
                tips: [
                    {
                        type: 'tip',
                        text: 'Si participas en ferias o eventos temporales, puedes crear una caja llamada "Caja Móvil / Eventos" para llevar control separado.',
                    },
                ],
                relatedModules: [
                    {
                        name: 'Sesiones y Cortes',
                        topicId: 'pos-sesiones-cortes',
                        relationship: 'Los turnos se abren sobre una caja específica.',
                    },
                ],
                systemPath: '/pos/registers',
                keywords: ['caja registradora', 'punto de cobro', 'mostrador', 'gaveta', 'impresora de tickets'],
            },
        ],
    },
];

export const ALL_TOPICS: HelpTopic[] = HELP_MODULES.flatMap((group) => group.topics);

export function getTopicById(id: string): HelpTopic | undefined {
    return ALL_TOPICS.find((topic) => topic.id === id);
}

export function searchTopics(query: string): HelpTopic[] {
    const cleanQuery = query.toLowerCase().trim();
    if (!cleanQuery) return [];

    return ALL_TOPICS.filter((topic) => {
        return (
            topic.title.toLowerCase().includes(cleanQuery) ||
            topic.subtitle.toLowerCase().includes(cleanQuery) ||
            topic.summary.toLowerCase().includes(cleanQuery) ||
            topic.whatIsIt.toLowerCase().includes(cleanQuery) ||
            topic.moduleName.toLowerCase().includes(cleanQuery) ||
            topic.keywords.some((k) => k.toLowerCase().includes(cleanQuery))
        );
    });
}
