# Tienda en línea

Plan para una tienda (e‑commerce) **sencilla** que vive en una aplicación Next.js aparte y se alimenta del ERP. Se
instala solo a los clientes que la contraten; el ERP sigue funcionando igual para los que no.

> Este documento es la especificación completa: tablas, reglas, endpoints y fases. Sigue las convenciones de
> [README.md](README.md) (columnas base, `yes`/`no`, tipos numéricos, no borrado) y no las repite.

**Depende de:** [Inventario](inventario.md) (artículos, existencias), [Catálogo](catalogo.md) (categorías, listas de
precio), [Ventas](ventas.md) (clientes, órdenes de venta), [Monedas](monedas.md) (tasa del día).

---

## 1. Decisiones de diseño

### 1.1 Qué se decidió y por qué

| Decisión                                                                     | Motivo                                                                                                                                                                                  |
|------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **La tienda es otra aplicación (Next.js).**                                  | No mezcla tráfico público con el ERP, se despliega solo a quien la contrata, y se puede rediseñar sin tocar el ERP.                                                                     |
| **El ERP sigue siendo la única fuente de verdad.**                           | Artículos, precios, existencias y clientes se administran en el ERP. La tienda no tiene base de datos propia de catálogo: **lee** del ERP.                                              |
| **Publicar un artículo es un registro aparte (`app_store_items`).**          | `app_items` no cambia: no gana columnas de marketing ni de fotos. Se publica «el artículo X con estas fotos y esta descripción». Un artículo se puede despublicar sin tocar el maestro. |
| **Una tienda por empresa, con una llave de acceso propia.**                  | El ERP es multi‑empresa; cada instalación de tienda queda atada a un `company_id` y a una llave (`X-Store-Key`). Ningún usuario del ERP inicia sesión desde la tienda.                  |
| **La tienda habla con el ERP solo desde el servidor de Next.**               | El navegador nunca llama al ERP: Next hace `fetch` en el servidor (Server Components / Route Handlers). Así no hay CORS que configurar ni llave expuesta.                                |
| **Un pedido web es un documento propio (`app_store_orders`), no una orden.** | La tienda no conoce bodegas, vendedores ni control de crédito. El pedido web llega como «bandeja de entrada» y un usuario del ERP lo **convierte** en orden de venta con un clic.        |
| **El comprador tiene cuenta y el vínculo con el cliente se guarda en él.**   | Emparejar comprador con `app_clients` se hace **una vez por comprador**, no una vez por pedido. Después, cada pedido llega con `client_id` y convertir es un clic.                       |
| **Se entrega por fases.**                                                    | La fase 1 (vitrina) ya es útil por sí sola. Carrito y pedidos vienen después.                                                                                                           |

### 1.2 Qué no se hace (por ahora)

- No hay pasarela de pago. El pedido web se confirma y se cobra por el flujo normal de Ventas.
- No hay compra como invitado. El formulario del checkout **es** el registro: los mismos cuatro campos crean la cuenta.
- No hay recuperación de contraseña por correo en la fase 2; la invitación desde el ERP sí manda correo. Se agrega
  cuando haga falta.
- No hay variantes (talla/color). Cada variante es un artículo distinto en el ERP, como hoy.
- No hay carrito persistente en servidor: el carrito vive en el navegador (`localStorage`).
- `app_items.image_path` se queda como está. No se usa para la tienda: la tienda necesita galería, no una foto.

---

## 2. Cambios en el ERP

Todo lo nuevo es **un módulo nuevo** más un archivo de rutas API. Nada del código existente cambia de comportamiento.

| Pieza                     | Tabla                                      | Prefijo | Tipo                  |
|---------------------------|--------------------------------------------|---------|-----------------------|
| Ajustes de tienda         | `app_store_settings`                       | —       | Singleton por empresa |
| Publicaciones             | `app_store_items`                          | `PUB`   | Tabla de módulo       |
| Fotos de la publicación   | `app_store_item_images`                    | —       | Tabla de detalle      |
| Compradores (fase 2)      | `app_store_customers`                      | `CWE`   | Tabla de módulo       |
| Pedidos web (fase 2)      | `app_store_orders` + `app_store_order_lines` | `PWE`   | Tabla de módulo       |

**Módulo Laravel:** `app/Modules/Store/` con la estructura habitual (Commands, Controllers, Models, Repositories,
Requests, Resources, Services, Exceptions, Providers, `routes.php`). Los cinco modelos viven en el mismo módulo porque
solo existen para la tienda.

> `app_store_settings` es el segundo singleton por empresa después de `app_configurations`. Al implementarlo hay que
> actualizar la tabla *Clasificación de tablas* de [README.md](README.md).

### 2.1 Ajustes de tienda — `app_store_settings`

Una fila por empresa. Se crea sola la primera vez que se abre la pantalla (igual que `app_configurations`). No se lista,
no se desactiva.

| Columna              | Tipo          | Nulo | Default   | Descripción                                                                                                                          |
|----------------------|---------------|------|-----------|--------------------------------------------------------------------------------------------------------------------------------------|
| `is_enabled`         | `enum`        | No   | `'no'`    | `yes` / `no`. Con `no` la API pública responde `403` a todo. Interruptor general.                                                     |
| `store_name`         | `string(150)` | No   |           | Nombre que muestra la tienda. Se inicializa con el nombre de la empresa.                                                             |
| `logo_path`          | `string(255)` | Sí   |           | Ruta en el disco `public`.                                                                                                           |
| `brand_color`        | `string(7)`   | No   | `'#111827'` | Color de acento de la tienda en hexadecimal (`#RRGGBB`). Es la única personalización visual por empresa. Ver §3.4.                 |
| `price_list_id`      | `uuid`        | Sí   |           | FK → `app_price_lists.id` (`restrictOnDelete`). Lista con la que se muestran los precios. Sin lista, no se muestran precios.          |
| `warehouse_id`       | `uuid`        | Sí   |           | FK → `app_warehouses.id` (`restrictOnDelete`). Bodega contra la que se calcula la disponibilidad. Nula = suma de todas las bodegas.  |
| `shows_stock`        | `enum`        | No   | `'no'`    | `yes` muestra la cantidad disponible; `no` muestra solo «Disponible» / «Agotado».                                                    |
| `allows_orders`      | `enum`        | No   | `'no'`    | `yes` habilita el carrito y `POST /orders`. Fase 2.                                                                                  |
| `default_client_type_id` | `uuid`    | Sí   |           | FK → `app_client_types.id`. Tipo con el que se crea un cliente desde un pedido web. Sin él, convertir no puede crear clientes. Fase 2. |
| `shows_secondary_currency` | `enum`  | No   | `'yes'`   | Muestra el precio también en la moneda secundaria de la empresa con la tasa del día (ver [monedas.md](monedas.md)).                 |
| `contact_phone`      | `string(30)`  | Sí   |           | Teléfono de contacto que muestra la tienda. Con formato internacional para el enlace de WhatsApp.                                    |
| `contact_email`      | `string(150)` | Sí   |           |                                                                                                                                      |
| `store_url`          | `string(255)` | Sí   |           | URL pública de la tienda. Se usa para armar el enlace de invitación. Nula = no se pueden enviar invitaciones. Fase 2.                 |
| `api_key_hash`       | `string(64)`  | Sí   |           | SHA‑256 de la llave que usa la tienda. La llave en claro se muestra **una sola vez** al generarla. Nula = tienda sin acceso.          |
| `api_key_last_used_at` | `timestamp` | Sí   |           | Última petición aceptada con la llave. Diagnóstico.                                                                                  |

**Índices:** `unique(company_id)`, `index(api_key_hash)`.

**Reglas**

- La llave se genera desde la pantalla (botón «Generar llave»). Generar otra invalida la anterior de inmediato.
- `price_list_id` y `warehouse_id` deben ser de la misma empresa y estar `active`.
- La pantalla es una sola vista con dos zonas: *Tienda* (nombre, logo, contacto, interruptores) y *Acceso* (llave).

### 2.2 Publicaciones — `app_store_items` — Prefijo `PUB`

«Este artículo se muestra en la tienda así». Es el módulo que el usuario ve como **Tienda → Publicaciones**: un select
remoto para escoger el artículo existente (`items.lookup`, ver [selects-remotos.md](selects-remotos.md)) y los campos de
presentación.

| Columna            | Tipo          | Nulo | Default | Descripción                                                                                                           |
|--------------------|---------------|------|---------|-----------------------------------------------------------------------------------------------------------------------|
| `item_id`          | `uuid`        | No   |         | FK → `app_items.id` (`restrictOnDelete`). El artículo publicado.                                                      |
| `slug`             | `string(160)` | No   |         | Identificador en la URL de la tienda (`/productos/{slug}`). Se genera del título; editable.                           |
| `title`            | `string(150)` | No   |         | Título comercial. Se inicializa con `app_items.name`.                                                                 |
| `summary`          | `string(300)` | Sí   |         | Frase corta para la tarjeta del listado.                                                                              |
| `description`      | `text`        | Sí   |         | Descripción larga (Markdown simple). Se inicializa con `app_items.description`.                                       |
| `is_featured`      | `enum`        | No   | `'no'`  | `yes` lo muestra en la portada.                                                                                       |
| `order`            | `integer`     | No   | `0`     | Orden de presentación dentro de la categoría.                                                                         |
| `published_at`     | `timestamp`   | Sí   |         | Fecha de la primera publicación. Se llena al pasar a `active` por primera vez.                                        |

**Índices:** `unique(company_id, item_id)`, `unique(company_id, slug)`, `index(item_id)`, `index(is_featured)`,
`index(order)`.

**Estados (`status`):** `active` (visible en la tienda) / `inactive` (oculto). Es un maestro: se desactiva, no se anula.

**Reglas**

- Solo se publican artículos con `is_sellable = 'yes'` y `status = active`. Si el artículo se desactiva en el ERP, la
  publicación deja de salir en la API aunque siga `active` (el filtro se hace en el repositorio, no con un cascade).
- Un artículo tiene como máximo una publicación por empresa.
- La categoría, la unidad base, el precio y la existencia **no se guardan aquí**: se leen del artículo en el momento de
  servir la API. La publicación solo lleva lo que el ERP no tiene (fotos, textos comerciales, orden).
- `slug`: minúsculas, `a-z0-9-`, único por empresa. Al chocar se agrega sufijo numérico (`-2`, `-3`).

**Relaciones:** `belongsTo(Item)`, `hasMany(StoreItemImage)`.

### 2.3 Fotos — `app_store_item_images`

Galería de la publicación. Se cargan desde la pantalla de la publicación (arrastrar o seleccionar, varias a la vez).

| Columna         | Tipo          | Nulo | Default    | Descripción                                                              |
|-----------------|---------------|------|------------|--------------------------------------------------------------------------|
| `id`            | `uuid`        | No   |            | PK.                                                                      |
| `company_id`    | `uuid`        | Sí   |            | FK → `app_companies.id`. Heredado de la publicación.                     |
| `store_item_id` | `uuid`        | No   |            | FK → `app_store_items.id` (`cascadeOnDelete`).                           |
| `path`          | `string(255)` | No   |            | Ruta en el disco `public`: `store/{company_id}/{store_item_id}/{uuid}.webp`. |
| `alt_text`      | `string(150)` | Sí   |            | Texto alternativo.                                                       |
| `order`         | `integer`     | No   | `0`        | Orden en la galería. La de `order` menor es la portada.                  |
| `width`         | `integer`     | Sí   |            | Píxeles. Se llena al guardar.                                            |
| `height`        | `integer`     | Sí   |            | Píxeles.                                                                 |
| `status`        | `enum`        | No   | `'active'` | `active` / `inactive`. Quitar una foto es desactivarla.                  |

**Índices:** `index(store_item_id)`, `index(order)`.

**Reglas**

- Formatos aceptados: `jpg`, `png`, `webp`; máximo 5 MB por archivo; máximo 8 fotos activas por publicación.
- Al guardar se genera **una** versión reducida (lado mayor 1600 px, WebP) y se guarda esa. No se guardan miniaturas:
  Next las genera con `next/image`.
- Se sirven por la ruta pública del disco (`/storage/store/...`). Requiere `php artisan storage:link` en el despliegue
  del ERP (ya está en el `Dockerfile` si se usa el `public` disk; verificar).
- Subir fotos es un endpoint aparte del `PUT` de la publicación (`POST /{company}/store-items/{id}/images`,
  `multipart/form-data`), porque el formulario de Inertia con archivos es distinto del JSON habitual.

### 2.4 Compradores — `app_store_customers` — Prefijo `CWE` (fase 2)

La cuenta con la que alguien compra en la tienda. **No es un cliente del ERP**: es quien puede llegar a serlo. El
vínculo con `app_clients` vive aquí, en `client_id`, y se hace **una sola vez por comprador**. A partir de ahí todos sus
pedidos entran con el cliente resuelto.

| Columna             | Tipo          | Nulo | Default | Descripción                                                                                                            |
|---------------------|---------------|------|---------|------------------------------------------------------------------------------------------------------------------------|
| `client_id`         | `uuid`        | Sí   |         | FK → `app_clients.id` (`restrictOnDelete`). El vínculo. Nulo hasta que se resuelve.                                    |
| `name`              | `string(150)` | No   |         |                                                                                                                        |
| `email`             | `string(150)` | No   |         | Identificador de inicio de sesión. Único por empresa.                                                                  |
| `phone`             | `string(30)`  | Sí   |         |                                                                                                                        |
| `document_type`     | `enum`        | Sí   |         | Letra del RIF (`V`, `E`, `J`, `P`, `G`, `C`). Opcional al registrarse; obligatorio para crear el cliente al convertir. |
| `document_number`   | `string(15)`  | Sí   |         | Solo dígitos. Único por empresa junto con `document_type` cuando está presente.                                        |
| `password_hash`     | `string(255)` | Sí   |         | Nulo mientras la cuenta está `invited`.                                                                                |
| `email_verified_at` | `timestamp`   | Sí   |         | Se llena al aceptar la invitación. En registro directo no se exige en esta fase.                                       |
| `last_login_at`     | `timestamp`   | Sí   |         |                                                                                                                        |
| `linked_at`         | `timestamp`   | Sí   |         | Cuándo se resolvió `client_id`.                                                                                        |
| `linked_by`         | `uuid`        | Sí   |         | FK → `users.id`. Nulo cuando el vínculo fue automático.                                                                |
| `link_source`       | `enum`        | Sí   |         | Cómo se vinculó: `rif`, `invitation`, `conversion`, `manual`.                                                          |
| `invitation_token_hash` | `string(64)` | Sí |         | SHA‑256 del token de invitación. Se borra al aceptar.                                                                  |
| `invitation_expires_at` | `timestamp` | Sí  |         |                                                                                                                        |

**Índices:** `unique(company_id, email)`, `unique(company_id, document_type, document_number)`, `unique(client_id)`
(un cliente tiene como máximo un comprador), `index(email)`, `index(invitation_token_hash)`.

**Estados (`status`):** `invited` (creado desde el ERP, sin contraseña) → `active`. `inactive` bloquea el inicio de
sesión. `active` ↔ `inactive` se cambia desde el ERP.

**Las cuatro vías de vínculo**, de la más automática a la menos:

| Vía          | Cuándo ocurre                                                                                                           | `link_source` |
|--------------|-------------------------------------------------------------------------------------------------------------------------|---------------|
| Por RIF      | Al registrarse con un RIF que ya existe en `app_clients` de la empresa (es único por empresa, así que es seguro).        | `rif`         |
| Invitación   | Desde la pantalla del cliente en el ERP, botón «Invitar a la tienda»: crea el comprador ya vinculado en `invited` y envía el enlace para poner contraseña. Vía para los clientes existentes. | `invitation` |
| Conversión   | Al convertir el **primer** pedido de un comprador sin vínculo: el diálogo pide el cliente (o lo crea) y guarda el vínculo. Los pedidos siguientes ya no preguntan. | `conversion` |
| Manual       | Desde el listado de compradores en el ERP, «Vincular» con select remoto de clientes.                                     | `manual`      |

**Reglas**

- El correo **no** vincula solo: no es único en `app_clients`. Cuando coincide, el diálogo de conversión lo
  preselecciona como sugerencia.
- Registrarse **no** crea un cliente en el ERP. El cliente se crea al convertir el primer pedido, para que quien se
  registra y nunca compra no ensucie el maestro.
- Crear el cliente desde la conversión no pide datos adicionales: nombre, RIF, correo y teléfono vienen del comprador;
  `client_type_id` = `app_store_settings.default_client_type_id`; `price_list_id` = la lista de la tienda;
  `payment_term_days = 0`; `credit_limit = 0`; `salesperson_id` = quien convierte. Llama a `ClientCreateService`.
- Un comprador vinculado ve en el checkout las direcciones activas de `app_client_addresses` de su cliente y puede
  escoger una o escribir otra. La escrita se puede guardar como dirección del cliente al convertir.
- Un comprador vinculado cuyo cliente tiene `price_list_id` propia ve los precios de **esa** lista; si no, la de la
  tienda. Es lo que hace útil invitar a los clientes B2B.
- Un comprador vinculado cuyo cliente pasa a `inactive` o `credit_blocked = 'yes'` sigue viendo la tienda, pero sus
  pedidos entran marcados para revisión (aviso en la pantalla del pedido web). El bloqueo real ocurre al confirmar la
  orden, como hoy.
- Sesión: `StoreCustomer` usa `HasApiTokens` de Sanctum (los tokens son polimórficos; el mapa de morphs no es
  `enforce`, así que el FQCN funciona). El token lleva la habilidad `store-customer`. Next lo guarda en una cookie
  `httpOnly` y lo reenvía al ERP desde el servidor junto con `X-Store-Key`.

**Relaciones:** `belongsTo(Client)`, `hasMany(StoreOrder)`.

### 2.5 Pedidos web — `app_store_orders` — Prefijo `PWE` (fase 2)

Lo que el comprador envía desde el carrito. **No afecta inventario, saldos ni clientes.** Es una bandeja que un usuario
del ERP revisa y convierte en orden de venta.

#### 2.5.1 Cabecera

| Columna               | Tipo            | Nulo | Default     | Descripción                                                                                                     |
|-----------------------|-----------------|------|-------------|-----------------------------------------------------------------------------------------------------------------|
| `store_customer_id`   | `uuid`          | No   |             | FK → `app_store_customers.id` (`restrictOnDelete`). Quién compró. Siempre presente: no hay compra como invitado. |
| `client_id`           | `uuid`          | Sí   |             | FK → `app_clients.id`. Copiado del comprador si ya estaba vinculado al crear el pedido; si no, se llena al convertir. |
| `client_address_id`   | `uuid`          | Sí   |             | FK → `app_client_addresses.id`. Dirección elegida por un comprador vinculado. Nula si escribió una nueva.       |
| `sales_order_id`      | `uuid`          | Sí   |             | FK → `app_sales_orders.id`. La orden generada al convertir.                                                     |
| `buyer_name`          | `string(150)`   | No   |             | Copia de los datos del comprador **al momento del pedido**, para que el pedido quede legible aunque la cuenta cambie. |
| `buyer_document_type` | `enum`          | Sí   |             | Letra del RIF (`V`, `E`, `J`, `P`, `G`, `C`). Opcional en el carrito; obligatorio para convertir.               |
| `buyer_document_number` | `string(15)`  | Sí   |             | Solo dígitos.                                                                                                   |
| `buyer_email`         | `string(150)`   | No   |             |                                                                                                                 |
| `buyer_phone`         | `string(30)`    | No   |             |                                                                                                                 |
| `delivery_address`    | `string(500)`   | Sí   |             | Dirección de entrega en texto libre. Se copia a `app_client_addresses` al convertir si el cliente no la tiene.   |
| `delivery_city`       | `string(100)`   | Sí   |             |                                                                                                                 |
| `delivery_state`      | `string(100)`   | Sí   |             |                                                                                                                 |
| `currency`            | `string(3)`     | No   |             | Moneda de los precios mostrados (la de la lista de precios de la tienda).                                       |
| `exchange_rate`       | `decimal(18,8)` | No   | `1`         | Tasa del día en que se creó el pedido, congelada (ver [monedas.md](monedas.md)).                                |
| `subtotal`            | `decimal(18,2)` | No   | `0`         | Suma de líneas. **Sin impuestos**: la tienda muestra precios de lista; el impuesto lo calcula la orden de venta. |
| `total`               | `decimal(18,2)` | No   | `0`         | Igual a `subtotal` en esta fase. Queda por si se agregan cargos de envío.                                        |
| `buyer_notes`         | `text`          | Sí   |             | Comentario del comprador.                                                                                       |
| `converted_by`        | `uuid`          | Sí   |             | FK → `users.id`. Quién convirtió.                                                                               |
| `converted_at`        | `timestamp`     | Sí   |             |                                                                                                                 |
| `rejected_at`         | `timestamp`     | Sí   |             |                                                                                                                 |
| `rejection_reason`    | `string(500)`   | Sí   |             |                                                                                                                 |
| `notes`               | `text`          | Sí   |             | Notas internas del usuario del ERP.                                                                             |

**Estados (`status`):** `pending` → `converted`, o `rejected`. Los tres son terminales salvo `pending`. No se edita: si
el comprador se equivocó, se rechaza y hace otro.

**Índices:** `index(store_customer_id)`, `index(client_id)`, `index(sales_order_id)`, `index(buyer_email)`.

#### 2.5.2 Líneas — `app_store_order_lines`

Sigue la estructura común de líneas de [README.md](README.md) con estas particularidades:

- `measurement_unit_id` es siempre la **unidad base** del artículo. La tienda no ofrece unidades alternas.
- `unit_price` y `list_price` son iguales: el precio de la lista de la tienda al momento del pedido.
- `discount_percent`, `tax_id`, `tax_percent` van en `0` / nulo. El impuesto se resuelve al convertir.
- Se agrega `store_item_id` (`uuid`, FK → `app_store_items.id`) para saber desde qué publicación se pidió.

#### 2.5.3 Convertir en orden de venta

Botón «Convertir» en la pantalla del pedido web. Sigue el patrón de *servicio espejo al repositorio* que ya usan las
órdenes al generar despachos y entradas: `StoreOrderConvertService` construye un `CreateSalesOrderCommand` y llama a
`SalesOrderCreateService`, sin duplicar la lógica de la orden.

Hay dos caminos según el comprador:

**Comprador ya vinculado** (el caso normal después del primer pedido): el botón convierte **directo**, sin diálogo.
Bodega, lista y vendedor salen de los defaults de abajo. Si el usuario quiere cambiarlos, los edita en la orden `draft`
que se abre a continuación.

**Comprador sin vínculo** (su primer pedido): se abre un diálogo con un solo campo obligatorio:

| Campo                | Cómo se resuelve                                                                                                                                                                     |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Cliente              | Select remoto de clientes, **preseleccionado** si hay uno con el mismo correo. Alternativa «Crear cliente con los datos del comprador»: no pide nada más (ver reglas en §2.4). Exige que el comprador tenga RIF; si no lo tiene, el diálogo lo pide y lo guarda en el comprador. |

Al confirmar, el vínculo se guarda en `app_store_customers` (`client_id`, `linked_by`, `linked_at`,
`link_source = 'conversion'`), y ese comprador nunca vuelve a pasar por el diálogo.

**Defaults de la orden** (no se preguntan):

| Campo           | Valor                                                                                       |
|-----------------|---------------------------------------------------------------------------------------------|
| Bodega          | `app_store_settings.warehouse_id`; si es nula, la primera bodega activa.                    |
| Lista de precio | `app_clients.price_list_id` si el cliente tiene; si no, `app_store_settings.price_list_id`. |
| Vendedor        | `app_clients.salesperson_id` si tiene; si no, el usuario que convierte.                     |
| Dirección       | `client_address_id` del pedido si la hay; si el comprador escribió una, se crea en `app_client_addresses` y se usa. |
| Plazo           | `app_clients.payment_term_days`.                                                            |

**Reglas de conversión**

- La orden nace en `draft` con las líneas del pedido web. `unit_price` se **recalcula** con la lista resuelta; si
  difiere del precio del pedido web, se muestra aviso pero no bloquea (el precio pudo cambiar entre el pedido y la
  conversión).
- El impuesto de cada línea es `app_items.sale_tax_id`, como en una orden capturada a mano.
- Control de crédito, reserva de existencia y aprobación se aplican **al confirmar la orden**, no al convertir. Convertir
  solo crea el borrador.
- `app_store_orders` pasa a `converted` y guarda `sales_order_id`, `client_id`, `converted_by`, `converted_at`. Todo en
  una transacción, incluida la creación del cliente y el vínculo del comprador.
- Rechazar exige `rejection_reason`.

### 2.6 API pública de la tienda

Rutas en `app/Modules/Store/routes.php`, prefijo `api/store/v1`, **sin** `auth:sanctum`. Autenticación por llave:

- Cabecera `X-Store-Key: {llave}`. Middleware nuevo `App\Http\Middleware\AuthenticateStoreKey`:
  1. Calcula SHA‑256 de la cabecera y busca `app_store_settings.api_key_hash`.
  2. Si no hay fila, o `is_enabled = 'no'`, o la empresa no está `active` → `403` JSON.
  3. Pone `company_id` en el request (`$request->attributes->set('store_company_id', ...)`) y en la sesión en memoria
     (`session('current_company_id')`), como hace `EnsureUserBelongsToCompanyApi`, para reutilizar cualquier lógica
     que lea la empresa activa.
  4. Actualiza `api_key_last_used_at` como máximo una vez por minuto.
- Throttle: `throttle:120,1` en lectura, `throttle:10,1` en `POST /orders`.
- La empresa **no** va en la URL: la llave ya la determina. Así la tienda no conoce ni expone el `company_id`.

Se documenta con Scramble igual que la API móvil (el `api_path` ya es `api`).

| Método | Ruta                          | Qué devuelve                                                                                                              | Fase |
|--------|-------------------------------|---------------------------------------------------------------------------------------------------------------------------|------|
| `GET`  | `/api/store/v1/settings`      | Nombre, logo, `brand_color`, contacto, moneda, `shows_stock`, `allows_orders`, `shows_secondary_currency`, tasa del día.  | 1    |
| `GET`  | `/api/store/v1/categories`    | Categorías activas **que tengan al menos una publicación visible**, con `count` de publicaciones.                          | 1    |
| `GET`  | `/api/store/v1/products`      | Listado paginado. Filtros: `category`, `q` (busca en `title`, `summary`, `app_items.name`, `sku`), `featured=yes`, `sort` (`order`, `name`, `price`, `newest`), `page`, `per_page` (máx. 60). | 1 |
| `GET`  | `/api/store/v1/products/{slug}` | Detalle: textos, galería completa, precio, disponibilidad, categoría, unidad base, `sku`.                                | 1    |
| `POST` | `/api/store/v1/customers/register` | Crea el comprador (`active`) y devuelve token. Si el RIF existe en `app_clients`, vincula (`rif`).                   | 2    |
| `POST` | `/api/store/v1/customers/login`    | Correo + contraseña → token Sanctum con habilidad `store-customer`.                                                  | 2    |
| `POST` | `/api/store/v1/customers/invitations/{token}` | Acepta la invitación: pone contraseña, pasa a `active`, devuelve token.                                   | 2    |
| `GET`  | `/api/store/v1/customers/me`       | Datos del comprador, `is_linked`, y direcciones de `app_client_addresses` si está vinculado. Requiere token.        | 2    |
| `GET`  | `/api/store/v1/customers/orders`   | Pedidos web del comprador con estado y, si existe, `code` de la orden de venta. Requiere token.                     | 2    |
| `POST` | `/api/store/v1/orders`        | Crea un `app_store_orders` en `pending` para el comprador del token. Devuelve `code` (`PWE000001`).                       | 2    |
| `GET`  | `/api/store/v1/orders/{code}` | Estado del pedido web. Solo si pertenece al comprador del token.                                                          | 2    |

**Forma de un producto** (`StoreProductResource`):

```json
{
  "id": "0192...",
  "slug": "taladro-percutor-500w",
  "title": "Taladro percutor 500W",
  "summary": "Ideal para mampostería liviana.",
  "description": "…markdown…",
  "sku": "TAL-500",
  "category": { "id": "…", "name": "Herramientas" },
  "unit": { "id": "…", "name": "Unidad", "abbreviation": "und" },
  "price": { "amount": "45.00", "currency": "USD" },
  "secondary_price": { "amount": "1674.00", "currency": "VES", "exchange_rate": "37.20000000" },
  "availability": { "in_stock": "yes", "quantity": "12.0000" },
  "is_featured": "no",
  "images": [
    { "url": "https://erp.cliente.com/storage/store/…/a.webp", "alt": "…", "width": 1600, "height": 1200 }
  ]
}
```

**Reglas de lectura**

- Un producto es visible cuando: `app_store_items.status = active` **y** `app_items.status = active` **y**
  `app_items.is_sellable = 'yes'`. Se aplica en un scope del repositorio (`visibleInStore()`), reutilizado por listado,
  detalle y conteo de categorías.
- `price`: fila de `app_item_prices` con `price_list_id = app_store_settings.price_list_id` y `status = active`. Sin
  fila, `price` es `null` y la tienda muestra «Consultar». Sin lista configurada, todo `price` es `null`.
- `secondary_price`: solo si `shows_secondary_currency = 'yes'` y hay tasa del día para la moneda del precio. Se
  resuelve con el mismo servicio de tasa que usan los documentos (ver [monedas.md](monedas.md)); no se inventa otro.
- `availability.quantity`: `SUM(available_quantity)` de `app_item_stocks` para el artículo, filtrado por
  `warehouse_id` si está configurada. `quantity` viaja solo con `shows_stock = 'yes'`; si no, solo `in_stock`.
  Los artículos `service` y `non_inventoried` siempre son `in_stock = 'yes'`.
- `images[].url` es absoluta (`Storage::disk('public')->url(...)` con `APP_URL`), para que Next no tenga que armarla.
- Las respuestas de lectura llevan `Cache-Control: public, max-age=60`. Next hace su propia caché (ver §3.3).

**Reglas de `POST /orders`** (fase 2)

- Rechaza si `allows_orders = 'no'`.
- Requiere token de comprador. Payload: dirección (`client_address_id` **o** dirección escrita), `buyer_notes` y
  `lines[] = { slug, quantity }`. Los datos del comprador se copian desde su cuenta. La tienda **no manda precios**: el ERP los resuelve
  de nuevo con la lista configurada, y si un producto dejó de ser visible o quedó sin precio responde `422` con la
  línea afectada.
- No valida existencia: la tienda no reserva. Si `shows_stock = 'yes'` la tienda avisa en el carrito, pero es solo
  informativo. La reserva real ocurre al confirmar la orden de venta.
- Guarda `currency` y `exchange_rate` del momento, y `client_id` si el comprador ya está vinculado.
- Los precios se resuelven con la lista del cliente vinculado si la tiene; si no, con la de la tienda. Igual que en el
  catálogo cuando el comprador ha iniciado sesión (`GET /products` con token).

### 2.7 Pantallas del ERP (Inertia)

Se agrega el grupo de menú **Tienda** (icono `Store`) con cuatro hijos, todo bajo `resources/js/pages/store/`:

| Pantalla        | Ruta web                          | Permisos                                                          |
|-----------------|-----------------------------------|-------------------------------------------------------------------|
| Publicaciones   | `/{company}/store-items`          | `store-items.list`, `.create`, `.edit`, `.update-status`          |
| Compradores     | `/{company}/store-customers`      | `store-customers.list`, `.show`, `.link`, `.update-status`        |
| Pedidos web     | `/{company}/store-orders`         | `store-orders.list`, `.show`, `.convert`, `.reject`               |
| Ajustes         | `/{company}/store-settings`       | `store-settings.edit`                                             |

- **Publicaciones / crear:** select remoto de artículo (solo vendibles y activos, y que no tengan publicación) →
  al elegirlo se precargan título y descripción → resto de campos → guardar. La galería se carga en la pantalla de
  edición, después de creada la publicación.
- **Publicaciones / editar:** formulario + zona de galería (subir, reordenar arrastrando, desactivar). Muestra en modo
  lectura lo que viene del artículo: código, SKU, categoría, precio en la lista de la tienda, disponibilidad. Con enlace
  al artículo.
- **Pedidos web / listado:** filtros por estado y fecha; contador de `pending` en el menú es opcional.
- **Compradores / listado:** correo, nombre, RIF, cliente vinculado (o «Sin vincular»), estado, último acceso. Acción
  «Vincular» con select remoto de clientes.
- **Compradores / ver:** datos, vínculo, pedidos web del comprador.
- **Pedidos web / ver:** datos del comprador con enlace a su cuenta y a su cliente, líneas, botones «Convertir» y
  «Rechazar». Enlace a la orden generada.
- **Clientes / ver (pantalla existente):** una tarjeta «Tienda» de solo lectura con el comprador vinculado, o el botón
  «Invitar a la tienda» si no lo hay. Es el único cambio visible en un módulo existente y es aditivo.
- **Ajustes:** un formulario. La llave se muestra en un diálogo una sola vez al generarla, con botón de copiar.

Componentes: `StoreItemForm`, `StoreItemGallery`, `StoreOrderConvertDialog`, `StoreSettingsForm`, siguiendo el patrón
de Context API del skill `react-modular-frontend`.

### 2.8 Lista de archivos a crear o tocar en el ERP

**Nuevos** (todo dentro del módulo salvo lo marcado):

```
app/Modules/Store/
  Commands/        CreateStoreItemCommand, UpdateStoreItemCommand, UpdateStatusStoreItemCommand,
                   SearchStoreItemCommand, StoreItemImageData, UpdateStoreSettingsCommand,
                   CreateStoreOrderCommand, StoreOrderLineData, ConvertStoreOrderCommand, RejectStoreOrderCommand,
                   RegisterStoreCustomerCommand, InviteStoreCustomerCommand, LinkStoreCustomerCommand,
                   UpdateStatusStoreCustomerCommand
  Controllers/     StoreItemGetController, StoreItemPostController, StoreItemPutController,
                   StoreItemUpdateStatusController, StoreItemImageController,
                   StoreSettingsGetController, StoreSettingsPutController, StoreSettingsKeyController,
                   StoreOrderGetController, StoreOrderConvertController, StoreOrderRejectController,
                   StoreCustomerGetController, StoreCustomerLinkController, StoreCustomerInviteController,
                   StoreCustomerUpdateStatusController,
                   Api/StoreCustomerAuthApiController, Api/StoreCustomerApiController,
                   Api/StoreSettingsApiController, Api/StoreCategoryApiController,
                   Api/StoreProductApiController, Api/StoreOrderApiController
  Exceptions/      StoreItemNotFoundException, StoreOrderNotFoundException, StoreOrderNotPendingException,
                   ItemAlreadyPublishedException, StoreDisabledException, StoreCustomerNotFoundException,
                   StoreCustomerAlreadyLinkedException, ClientAlreadyLinkedException, InvalidInvitationException
  Models/          StoreSetting, StoreItem, StoreItemImage, StoreCustomer, StoreOrder, StoreOrderLine
  Repositories/    (+ Contracts/) StoreSettingRepository, StoreItemRepository, StoreCustomerRepository,
                   StoreOrderRepository, StoreItemFilters, StoreCustomerFilters
  Requests/        CreateStoreItemRequest, UpdateStoreItemRequest, UpdateStatusStoreItemRequest,
                   UploadStoreItemImagesRequest, UpdateStoreSettingsRequest,
                   ConvertStoreOrderRequest, RejectStoreOrderRequest, LinkStoreCustomerRequest,
                   UpdateStatusStoreCustomerRequest, Api/CreateStoreOrderApiRequest,
                   Api/RegisterStoreCustomerApiRequest, Api/LoginStoreCustomerApiRequest,
                   Api/AcceptInvitationApiRequest
  Resources/       StoreItemResource, StoreItemImageResource, StoreSettingResource, StoreOrderResource,
                   StoreOrderLineResource, StoreCustomerResource, Api/StoreCustomerApiResource,
                   Api/StoreProductResource, Api/StoreCategoryResource,
                   Api/StoreSettingsPublicResource
  Services/        StoreItemCreateService, StoreItemUpdateService, StoreItemUpdateStatusService,
                   StoreItemFindService, StoreItemSearchService, StoreItemImageService,
                   StoreSettingFindOrCreateService, StoreSettingUpdateService, StoreSettingGenerateKeyService,
                   StoreOrderCreateService, StoreOrderFindService, StoreOrderSearchService,
                   StoreOrderConvertService, StoreOrderRejectService,
                   StoreCustomerRegisterService, StoreCustomerLoginService, StoreCustomerInviteService,
                   StoreCustomerAcceptInvitationService, StoreCustomerLinkService,
                   StoreCustomerUpdateStatusService, StoreCustomerFindService, StoreCustomerSearchService,
                   StoreClientFromCustomerService (crea el cliente con los defaults, llama a ClientCreateService),
                   StoreCatalogService (arma producto: precio + disponibilidad + tasa)
  Providers/       StoreServiceProvider
  routes.php
app/Http/Middleware/AuthenticateStoreKey.php
app/Http/Middleware/AuthenticateStoreCustomer.php   (auth:sanctum + habilidad store-customer + misma empresa que la llave)
app/Modules/Store/Notifications/StoreCustomerInvitation.php
database/migrations/  create_app_store_settings_table, create_app_store_items_table,
                      create_app_store_item_images_table, create_app_store_customers_table,
                      create_app_store_orders_table,
                      create_app_store_order_lines_table
database/factories/   StoreSettingFactory, StoreItemFactory, StoreItemImageFactory, StoreCustomerFactory,
                      StoreOrderFactory,
                      StoreOrderLineFactory
resources/js/pages/store/  (items/, customers/, orders/, settings/, components/, hooks/, types/)
tests/Feature/Store/  (ver §4)
```

**Existentes que se tocan** (solo registro, sin cambiar lógica):

| Archivo                                    | Cambio                                                                                     |
|--------------------------------------------|--------------------------------------------------------------------------------------------|
| `bootstrap/providers.php`                  | Registrar `StoreServiceProvider`.                                                          |
| `bootstrap/app.php`                        | Alias del middleware `store.key`.                                                          |
| `database/seeders/MenuSeeder.php`          | Grupo **Tienda** con sus tres hijos.                                                       |
| `database/seeders/PermissionSeeder.php`    | Permisos `store-items.*`, `store-customers.*`, `store-orders.*`, `store-settings.*`.        |
| `resources/js/pages/clients/show.tsx`      | Tarjeta «Tienda» con el comprador vinculado o el botón «Invitar». Solo presentación.        |
| `resources/js/.../permissions-tree.tsx`    | Rama **Tienda**.                                                                           |
| `config/filesystems.php`                   | Nada si se usa el disco `public` existente. Verificar `storage:link` en `Dockerfile`.      |
| `docs/README.md`                           | Fila de `tienda.md` en el índice; `app_store_settings` en singletons; tablas nuevas en listas. |

**No se toca:** `app/Modules/Item/**`, `app/Modules/SalesOrder/**` (solo se **llama** a `SalesOrderCreateService`),
`app/Modules/Client/**` en backend (solo se llama a `ClientCreateService`; la invitación se sirve desde una ruta del
módulo `Store`), modelos y migraciones existentes.

---

## 3. La aplicación de tienda (Next.js)

Repositorio aparte (`erp-store`). Next.js 15+, App Router, TypeScript, Tailwind. Una instalación por cliente.

### 3.1 Variables de entorno

| Variable           | Ejemplo                       | Uso                                                               |
|--------------------|-------------------------------|-------------------------------------------------------------------|
| `ERP_API_URL`      | `https://erp.cliente.com`     | Base del ERP. Solo en servidor.                                   |
| `STORE_API_KEY`    | `stk_…`                       | La llave generada en Ajustes. **Solo en servidor**, nunca `NEXT_PUBLIC_`. |
| `NEXT_PUBLIC_SITE_URL` | `https://tienda.cliente.com` | Para URLs canónicas y Open Graph.                             |
| `REVALIDATE_SECONDS` | `60`                        | Tiempo de caché de catálogo.                                      |

### 3.2 Estructura

```
src/
  app/
    layout.tsx                 cabecera (nombre/logo desde /settings), pie con contacto
    page.tsx                   portada: destacados + categorías
    productos/page.tsx         listado con filtros (categoría, búsqueda, orden) y paginación
    productos/[slug]/page.tsx  detalle: galería, precio doble, disponibilidad, botón carrito o WhatsApp
    categorias/[id]/page.tsx   listado filtrado
    carrito/page.tsx           carrito (fase 2)
    checkout/page.tsx          registro o inicio de sesión + dirección + confirmar (fase 2)
    cuenta/page.tsx            datos del comprador y sus pedidos (fase 2)
    invitacion/[token]/page.tsx  aceptar invitación: poner contraseña (fase 2)
    pedido/[code]/page.tsx     estado del pedido (fase 2)
    api/orders/route.ts        Route Handler: recibe el carrito del navegador y llama al ERP con la llave (fase 2)
    api/auth/route.ts          Route Handler: login/registro; guarda el token en cookie httpOnly (fase 2)
  lib/erp.ts                   cliente HTTP: fetch con X-Store-Key (+ Bearer del comprador si hay cookie), tipos, 403/404
  lib/cart.ts                  carrito en localStorage (fase 2)
  lib/session.ts               lee la cookie del comprador en el servidor (fase 2)
  components/                  ProductCard, ProductGallery, PriceTag (doble moneda), AvailabilityBadge, CartButton
```

### 3.3 Reglas

- **Todo `fetch` al ERP ocurre en el servidor.** Páginas como Server Components con `fetch(..., { next: { revalidate:
  REVALIDATE_SECONDS } })`. El carrito se envía a `/api/orders` de Next, que reenvía al ERP. El navegador nunca ve
  `ERP_API_URL` ni la llave.
- `next.config.ts` → `images.remotePatterns` con el host del ERP para que `next/image` optimice las fotos.
- Sin precio (`price: null`) → botón «Consultar por WhatsApp» con mensaje prellenado (`contact_phone`).
- `allows_orders = 'no'` → no hay carrito; el detalle muestra solo el botón de WhatsApp.
- Si el ERP responde `403` (tienda deshabilitada) la tienda muestra una página «Tienda en mantenimiento».
- El nombre, logo y contacto salen de `/settings`; no hay nada de la empresa quemado en el código de la tienda.
- El token del comprador vive en una cookie `httpOnly`; el navegador nunca lo ve ni lo manda al ERP. Con cookie, las
  páginas de catálogo se piden con Bearer para que salgan los precios de la lista del cliente vinculado, y por eso
  esas peticiones **no** se cachean (`cache: 'no-store'`); sin cookie, se usa la caché de 60 s.
- El enlace de invitación que manda el ERP apunta a `{store_url}/invitacion/{token}`, con `store_url` tomado de
  Ajustes de tienda.

### 3.4 Estilo visual

La tienda se instala a clientes distintos, así que el estilo es **neutro y parametrizable**: no tiene marca propia y
lo único que cambia por empresa es el logo y un color. Un cliente que pida un rediseño completo es otro proyecto.

**Dirección:** minimalista, la foto manda. Fondo blanco, mucho aire, texto gris oscuro y un solo color de acento. Las
fotos del producto son lo único con color fuerte. Sin modo oscuro en la primera versión.

**Personalización por empresa**

| Qué           | De dónde sale                          | Cómo se aplica                                                                                   |
|---------------|----------------------------------------|--------------------------------------------------------------------------------------------------|
| Logo          | `app_store_settings.logo_path`         | Cabecera y pestaña del navegador (favicon derivado).                                             |
| Color         | `app_store_settings.brand_color`       | El layout lo inyecta como `--brand` en `:root`; botones, enlaces y chips activos lo usan. Nada más lo usa. |
| Nombre y contacto | `store_name`, `contact_phone`, `contact_email` | Cabecera, pie y enlace de WhatsApp.                                                    |

No hay más variables: ni tipografía, ni fondo, ni disposición.

**Base técnica:** Tailwind y shadcn/ui, como el ERP, para compartir convenciones y componentes. Tipografía Inter (o
Geist) con pila de respaldo del sistema.

**Mobile first.** La mayoría entra desde el celular y termina en WhatsApp:

- Grilla de productos de 2 columnas en móvil, 3 en tableta, 4 en escritorio.
- Búsqueda siempre visible en la cabecera. Categorías como chips horizontales deslizables, no menú lateral.
- Botón de WhatsApp fijo abajo a la derecha en todas las páginas, con mensaje prellenado con el producto cuando se
  abre desde un detalle.
- En el detalle, precio y botón de acción visibles sin hacer scroll en un teléfono.

**Tarjeta de producto**

- Foto cuadrada (1:1) recortada con `object-cover`, para que la grilla quede pareja con fotos de cualquier tamaño.
  Sin foto activa, un marcador neutro con el icono de la categoría.
- Título en máximo dos líneas (`line-clamp-2`).
- Precio principal en negrita; el secundario debajo, más pequeño y gris. Sin precio: «Consultar».
- Etiqueta discreta «Agotado» sobre la foto cuando `in_stock = 'no'`; «Destacado» solo en la portada.
- Sin botón «Agregar» en la tarjeta: se agrega desde el detalle, donde se ve la unidad y la disponibilidad.

**Detalle de producto:** galería con miniaturas debajo (o deslizable en móvil), título, `sku` en gris, precio doble,
disponibilidad, selector de cantidad y el botón de acción (carrito o WhatsApp según `allows_orders`), y la descripción
en Markdown debajo. Ninguna otra sección.

**Portada:** cabecera, chips de categorías, bloque de destacados (`is_featured = 'yes'`) y las categorías con sus
primeros productos. Sin carrusel automático, sin banners promocionales, sin animaciones. Todo eso pide mantenimiento
por cliente y no vende más en una tienda de catálogo.

**Estados vacíos y errores:** «No hay productos en esta categoría» con enlace a ver todo; «Tienda en mantenimiento»
cuando el ERP responde `403`; pedido creado con el `code` grande y el botón de WhatsApp para seguirlo.

### 3.5 Despliegue por cliente

Dos opciones, según cómo esté instalado el ERP del cliente:

| Opción                       | Cuándo                                                        | Cómo                                                                                                      |
|------------------------------|---------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| Junto al ERP (recomendada)   | El ERP corre con el `docker-compose.yml` del proyecto.        | Servicio `store` (imagen Node, `next start`) en la misma red; Nginx enruta `tienda.cliente.com` → `store:3000`. `ERP_API_URL=http://nginx` (red interna). |
| Separada                     | El cliente quiere la tienda en Vercel u otro host.            | `ERP_API_URL` apunta al ERP público por HTTPS. Requiere que `/api/store/v1` y `/storage` sean accesibles. |

En ambos casos el ERP necesita `APP_URL` correcto (las URLs de imágenes salen de ahí) y `storage:link`.

---

## 4. Pruebas

Carpeta `tests/Feature/Store/`, con Pest, siguiendo los tests existentes de módulos:

| Archivo                           | Cubre                                                                                                                      |
|-----------------------------------|----------------------------------------------------------------------------------------------------------------------------|
| `StoreItemCreateTest`             | Crear publicación; rechaza artículo no vendible, inactivo o ya publicado; genera `slug` único; precarga título.             |
| `StoreItemUpdateTest`             | Editar textos, `is_featured`, `order`; cambiar `slug` con colisión.                                                        |
| `StoreItemImageTest`              | Subir (fake `Storage`), límite de 8, formatos, reordenar, desactivar. Usa `Storage::fake('public')`.                        |
| `StoreItemUpdateStatusTest`       | Activar/desactivar; `published_at` se llena la primera vez.                                                                |
| `StoreSettingsTest`               | Se crea sola; validar lista y bodega de la empresa; `brand_color` exige `#RRGGBB`; generar llave invalida la anterior; el hash se guarda, la llave no. |
| `StoreApiAuthTest`                | Sin cabecera → 403; llave inválida → 403; `is_enabled = 'no'` → 403; empresa inactiva → 403; `last_used_at` se actualiza.  |
| `StoreApiCatalogTest`             | Visibilidad (publicación activa + artículo activo + vendible); precio por lista; `null` sin lista; disponibilidad por bodega y total; `shows_stock`; moneda secundaria; filtros y orden; categorías solo con publicaciones; detalle por `slug`; 404. |
| `StoreApiOrderCreateTest`         | Sin token → 401; rechaza con `allows_orders = 'no'`; recalcula precios con la lista del cliente o de la tienda; 422 por producto no visible; congela tasa; copia `client_id` si hay vínculo; genera `PWE`. |
| `StoreCustomerRegisterTest`       | Registro crea `active` y devuelve token; RIF existente vincula con `rif`; correo repetido → 422; RIF repetido → 422.       |
| `StoreCustomerLoginTest`          | Credenciales válidas; `inactive` → 403; `invited` sin contraseña → 403; token con habilidad `store-customer`.             |
| `StoreCustomerInvitationTest`     | Invitar desde el cliente crea `invited` vinculado y envía la notificación (`Notification::fake`); aceptar pone contraseña y borra el token; token vencido → 422; cliente ya vinculado → 422; sin `store_url` → 422. |
| `StoreCustomerLinkTest`           | Vincular manual; comprador ya vinculado → 422; cliente ya vinculado a otro → 422; cliente de otra empresa → 422.           |
| `StoreCustomerApiTest`            | `me` con direcciones del cliente si está vinculado; `orders` solo los propios; catálogo con token usa la lista del cliente. |
| `StoreOrderConvertTest`           | Comprador vinculado convierte directo sin diálogo; sin vínculo exige cliente y guarda el vínculo con `conversion`; crear cliente usa los defaults de Ajustes y llama a `ClientCreateService`; sin `default_client_type_id` → 422; la orden nace `draft` con líneas, impuesto del artículo, lista del cliente o de la tienda; dirección escrita se crea en `app_client_addresses`; aviso por precio distinto; todo en transacción; no convierte dos veces. |
| `StoreOrderRejectTest`            | Exige motivo; solo desde `pending`.                                                                                        |
| `StoreOrderApiStatusTest`         | Consulta por `code` con token; pedido de otro comprador → 404.                                                             |

Correr por carpeta: `php artisan test --compact tests/Feature/Store`.

---

## 5. Fases y orden de trabajo

| Fase | Entrega                                                                                       | Qué queda usable al terminar                                                        |
|------|-----------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------|
| 1a   | ERP: migraciones, modelos, Ajustes de tienda (pantalla + llave), Publicaciones (CRUD + galería), menú y permisos. | El usuario publica artículos con fotos desde el ERP.                        |
| 1b   | ERP: middleware de llave, `StoreCatalogService`, endpoints `settings`, `categories`, `products`, tests de API. | Cualquier cliente HTTP con la llave lee el catálogo.                        |
| 1c   | Next: portada, listado, detalle, WhatsApp, despliegue con Docker.                              | **Vitrina en producción.** Se puede instalar al primer cliente.                     |
| 2a   | ERP: `app_store_customers` (registro, login, invitación, vínculo por RIF), pantalla Compradores, tarjeta en Clientes, tests. | Los compradores existen y se vinculan a clientes.                      |
| 2b   | ERP: `app_store_orders`, `POST /orders`, pantalla Pedidos web, convertir (directo o con diálogo) y rechazar, tests. | Los pedidos web llegan al ERP y se vuelven órdenes de venta.                   |
| 2c   | Next: registro/login en el checkout, carrito, página de cuenta, aceptar invitación, estado del pedido. | **Tienda con pedidos.**                                                      |
| 3    | (Opcional, a definir) Recuperación de contraseña, pago en línea.                               | Se decide con el primer cliente que lo pida.                                        |

Cada fase se cierra con sus tests en verde y con Pint. La fase 1 no depende de nada de la 2: se puede instalar la
vitrina sin haber escrito una línea de pedidos.

---

## 6. Diagrama

```
                 ERP (Laravel)                                       Tienda (Next.js)
 ┌────────────────────────────────────────────┐            ┌─────────────────────────────────┐
 │ app_items ──┐                              │            │                                 │
 │ app_item_prices ──┤                        │  GET /api/store/v1/{settings,categories,     │
 │ app_item_stocks ──┤──> StoreCatalogService ─┼──products}───> Server Components (caché 60s)│
 │ app_categories ───┤        ▲               │  X-Store-Key │        │                       │
 │ app_exchange_rates┘        │               │            │        ▼                       │
 │                    app_store_items         │            │   navegador (HTML, next/image)  │
 │                    app_store_item_images   │            │        │ carrito (localStorage) │
 │                    app_store_settings ─────┼── llave ───┤        ▼                       │
 │                                            │  POST /api/store/v1/orders <── /api/orders   │
 │ app_store_customers ──client_id──> app_clients  │            │   cookie httpOnly (token)       │
│ app_store_orders ──convertir──> app_sales_orders (draft) │        (Route Handler)          │
 └────────────────────────────────────────────┘            └─────────────────────────────────┘
```
