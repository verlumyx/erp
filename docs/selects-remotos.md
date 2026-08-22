# Selects remotos (Select2Ajax)

Cómo un formulario elige un registro de un catálogo demasiado grande para viajar entero en las props de Inertia:
el contrato del endpoint, cómo se nombra la ruta y qué piezas hay que crear en el módulo.

> Las convenciones de arquitectura modular, la política de no borrado y los permisos están en
> [README.md](README.md) y no se repiten aquí.

**Contexto:** hasta ahora cada pantalla recibía sus catálogos completos en las props. Con 30 categorías eso es
correcto; con 5.000 artículos no. Las pantallas de órdenes de venta y de compra llegaron a arrastrar hasta 500
artículos con sus unidades y precios embebidos en cada carga de la página, para que el usuario eligiera dos o tres.

---

## 1. Cuándo se usa cada select

No todo select debe ser remoto. La regla es el tamaño del catálogo, no la comodidad:

| Componente                          | Cuándo                                                                                         | Cómo llegan los datos           |
|-------------------------------------|-------------------------------------------------------------------------------------------------|---------------------------------|
| `Select2` (`ui/select2.tsx`)        | Catálogo acotado y estable: estados, monedas, tipos, categorías, bodegas, listas de precio.    | En las props, vía `FormOptionsService` |
| `Select2Ajax` (`select2-ajax.tsx`)  | Catálogo abierto que crece con el negocio: artículos, clientes, proveedores.                    | Por `fetch` contra el endpoint de lookup |

> Si dudas, mira si el catálogo puede pasar de unos cientos de filas en una empresa real. Si puede, es remoto.

Los dos comparten estilos: `Select2Ajax` consume `createSelect2Styles()` del propio `ui/select2.tsx`, así que un
select con búsqueda remota se ve exactamente igual que uno con opciones en memoria. Cambiar uno cambia los dos.

---

## 2. El contrato del endpoint

Una sola forma para todos los módulos:

```
GET /{company}/{módulo}/lookup?q=&page=&per_page=&ids=&<filtros propios>
```

**Parámetros**

| Parámetro        | Quién lo manda        | Qué hace                                                                                  |
|------------------|-----------------------|---------------------------------------------------------------------------------------------|
| `q`              | El componente         | Término de búsqueda libre. Vacío devuelve la primera página del catálogo.                  |
| `page`           | El componente         | Página, base 1. La pide al llegar al final del menú.                                        |
| `per_page`       | El componente         | Tamaño de página. El backend lo acota a su tope (50).                                       |
| `ids`            | El hook de memoria    | Ids separados por coma, para resolver lo que un documento ya tenía elegido.                 |
| Filtros propios  | La pantalla           | Acotan el catálogo al caso: `is_sellable`, `is_purchasable`, `category_id`, `type`.        |

**Respuesta**

```json
{
  "data": [
    {
      "value": "0198c4f2-...",
      "label": "ART000009 — Taladro percutor",
      "meta": { "code": "ART000009", "sku": "TAL-001", "units": [], "prices": [] }
    }
  ],
  "has_more": true
}
```

`meta` es el motivo por el que este endpoint no es un buscador genérico: lleva lo que el formulario necesita **en el
instante en que se elige el registro**, para no tener que pedirlo en una segunda vuelta. En artículos son las unidades
(de ahí sale la unidad base de la línea), los precios por lista (de ahí sale el precio sugerido), el costo estándar y
el precio mínimo.

**Devuelve JSON, no Inertia.** Es la única ruta de un módulo que no renderiza una pantalla.

---

## 3. Las dos colisiones de nombres

Son la parte que cuesta una hora si se descubre tarde, y ninguna de las dos da un error que apunte a la causa:

**El filtro de búsqueda libre no puede llamarse `search`.** El repositorio hereda de la clase de filtros del módulo
(`ItemRepository extends ItemFilters`) y ya define `search(SearchItemCommand): array`. Un filtro `search(string)`
choca con esa firma y PHP rechaza la clase entera al cargarla:

```
Declaration of ItemRepository::search(SearchItemCommand $command): array must be
compatible with ItemFilters::search(string $value): Builder
```

Por eso el filtro se llama **`q`**, igual que el parámetro que manda el componente.

**La ruta no puede llamarse `options`.** Wayfinder nombra la función generada como la ruta y le pasa siempre un
segundo parámetro `options` con las opciones de query. Con la ruta llamada `options` el parámetro tapa a la función:

```ts
export const options = (args, options?: RouteQueryOptions) => ({
    url: options.url(args, options),   // 'url' does not exist on type 'RouteQueryOptions'
})
```

El TypeScript generado no compila, y el error aparece en `resources/js/routes/`, no en tu código. Por eso la ruta se
llama **`lookup`**.

> Lección transversal: `tsc --noEmit` hay que correrlo sobre todo el proyecto, incluido `resources/js/routes` y
> `resources/js/actions`, no solo sobre los archivos que uno escribió.

---

## 4. Las piezas del backend

Cinco, todas dentro de la estructura modular normal. El módulo **Item** es la implementación de referencia:

| Pieza                                 | Archivo                                              | Qué hace                                                                                    |
|---------------------------------------|------------------------------------------------------|---------------------------------------------------------------------------------------------|
| Filtro `q`                            | `Repositories/<Módulo>Filters.php`                   | Búsqueda libre, agrupada en un `where(fn)` para no romper el resto de filtros con el `or`.  |
| Filtro `ids`                          | `Repositories/<Módulo>Filters.php`                   | `whereIn('id', explode(','))` para hidratar lo ya elegido.                                   |
| `<Módulo>OptionSearchService`         | `Services/`                                          | La búsqueda del listado más las relaciones que el `meta` necesita.                           |
| `<Módulo>OptionResource`              | `Resources/`                                         | `value`, `label` y `meta`. Más pobre que el Resource completo, a propósito.                  |
| `lookup()`                            | `Controllers/<Módulo>GetController.php`              | Traduce `page`/`per_page` a `limit`/`offset`, acota el tope y responde JSON.                 |

**Por qué un servicio de búsqueda aparte.** El listado del módulo no carga unidades ni precios y no debe empezar a
hacerlo: el `loadMissing` vive en `<Módulo>OptionSearchService`, no en el repositorio, para que el listado no pague
el costo de lo que solo el select necesita.

**Por qué un Resource aparte.** `ItemResource` tiene treinta campos y crecerá. Una página de opciones son 20 filas:
si usara el Resource completo, cada búsqueda transportaría datos que nadie mira, y engordaría sola cada vez que el
modelo gane un campo.

**Permiso.** El mismo que el listado del módulo (`items.list`). Quien no puede ver el catálogo tampoco puede
buscarlo desde otro formulario.

### Reglas

- **Buscar filtra por estado; hidratar no.** `q` ofrece solo registros activos. `ids` no filtra por estado: un
  artículo desactivado después de haberse pedido sigue estando en la orden que se edita, y tiene que resolverse o la
  línea pierde su unidad y su precio.
- **El tope de página lo pone el backend**, no el cliente: `min(max($request->integer('per_page', 20), 1), 50)`.
  Un `per_page=5000` no puede convertirse en el volcado del catálogo que este endpoint existe para evitar.
- **La ruta va antes de `/{id}`** en `routes.php`. Con la restricción `uuid` sobre `{id}` no habría ambigüedad, pero
  el orden explícito ahorra el siguiente susto.
- **`company` sale de la ruta**, no de la sesión: el endpoint ya está detrás de `company.access`.

---

## 5. Las piezas del frontend

### `Select2Ajax` — el componente

Vive en `resources/js/components/select2-ajax.tsx`. Resuelve por dentro lo que suele romperse en estos componentes:
debounce de 300 ms, `AbortController` más contador de petición para que una respuesta lenta no pise a la vigente,
paginación infinita al llegar al final del menú, limpieza del input controlado al elegir o cerrar, y reinicio de lo
cacheado cuando cambian los filtros fijos.

| Prop              | Tipo                                    | Descripción                                                                    |
|-------------------|-----------------------------------------|--------------------------------------------------------------------------------|
| `url`             | `string`                                | `items.lookup(companyId).url`                                                  |
| `params`          | `Record<string, ...>`                   | Filtros fijos que acotan el catálogo: `{ is_sellable: 'yes' }`                 |
| `value`           | `AjaxOption \| null`                    | La **opción completa**, no el id                                                |
| `onChange`        | `(option: AjaxOption \| null) => void`  | Recibe la opción con su `meta`                                                  |
| `formatLabel`     | `(option: AjaxOption) => string`        | Si el módulo nombra el registro distinto del servidor. Aplica al menú y al valor |
| `minSearchLength` | `number`                                | `0` (por defecto) carga al abrir el menú; `2`+ espera a que el usuario escriba  |
| `perPage`         | `number`                                | Tamaño de página que pide                                                       |
| `error`, `size`, `placeholder`, `isClearable`, `isDisabled`, `inputId`, `className` | | Igual que en `Select2`                    |

> **El valor es la opción, no el id.** Es la diferencia de API con `Select2` y es deliberada: el componente no puede
> resolver la etiqueta de un id que nunca ha traído. De ahí sale la pieza siguiente.

### `useItemCatalog` — la memoria de la pantalla

Vive en `resources/js/hooks/use-item-catalog.ts`. Desde que el catálogo dejó de viajar en las props, un formulario
solo conoce los artículos que el usuario elige. El hook los recuerda y resuelve los que el documento ya traía:

| Devuelve     | Qué es                                                                             |
|--------------|-------------------------------------------------------------------------------------|
| `url`        | La URL del lookup, ya con la empresa                                                |
| `itemOf(id)` | El artículo de una línea, si la pantalla ya lo conoce. Sustituye a `options.items.find(...)` |
| `remember(option)` | Guarda el artículo recién elegido y devuelve lo que la línea necesita         |
| `optionOf(id)` | El valor que espera `Select2Ajax` para una línea                                  |
| `labelOf(option)` | El mismo formato de etiqueta, para las opciones que llegan del servidor         |

**Cómo evita el parpadeo en edición.** Siembra la entrada con el `item_name` / `item_sku` que ya trae el Resource del
documento, así la línea muestra su artículo desde el primer render; después hidrata por `ids` (en tandas de 50, por
el tope del endpoint) para completar unidades y precios.

**`hydrated`.** Las entradas sembradas llevan `hydrated: false`. Sus `units` y `prices` vacíos significan «todavía no
se sabe», no «no tiene», y esa distinción importa: revaluar una línea contra una entrada sin hidratar pondría su
precio en 0. Por eso `repriceLine` deja intacta la línea cuyo artículo aún no conoce.

### `useRemoteOption` — la memoria de un solo valor

Vive en `resources/js/hooks/use-remote-option.ts`. Es la versión mínima de lo anterior para el caso de la cabecera:
un único valor elegido, no una colección de líneas. Lo usan el cliente del pedido de venta, el proveedor de la orden
de compra y el filtro de proveedor del listado de compras.

| Devuelve       | Qué es                                                                              |
|----------------|---------------------------------------------------------------------------------------|
| `url`          | La URL del lookup, ya con la empresa                                                  |
| `optionOf(id)` | La opción, **solo mientras siga siendo la del id vigente** del formulario             |
| `select(option)` | Guarda la opción recién elegida                                                     |

Recibe `seed` —la opción de partida que la pantalla arma con lo que ya trae el Resource del documento
(`client_code` + `client_name`, `supplier_code` + `supplier_name`)— y `hydrate`, que pide su `meta` por `ids`.

**Cuándo hidratar.** Solo si la pantalla usa algo del valor además de su etiqueta. El proveedor no se hidrata: su
`meta` (moneda y días de crédito) se copia en la cabecera al elegirlo y ahí queda; al abrir una orden ya guardada
esos campos vienen de la orden, no del proveedor. El cliente sí se hidrata: de su `meta` salen las direcciones de
entrega que el select de al lado ofrece y el aviso de crédito bloqueado, y ambos tienen que estar al editar. Lo que
solo se copia al elegir —lista de precio, vendedor, días de crédito— no obliga a hidratar: ya está en el pedido.

**Por qué `optionOf(id)` y no la opción a secas.** El id vive en `useForm`, la opción en el hook. Preguntar por el id
mantiene los dos alineados sin sincronizarlos a mano: un `reset()` tras guardar vacía el formulario y el select queda
vacío con él.

---

## 6. Cómo lo hereda un módulo nuevo

Un módulo que necesite un select remoto de su propio catálogo (clientes, proveedores) repite estos pasos:

| # | Paso                                                                                              | Dónde                              |
|---|---------------------------------------------------------------------------------------------------|------------------------------------|
| 1 | Filtro `q()` — búsqueda libre agrupada. **Nunca `search()`** (§3)                                 | `<Módulo>Filters`                  |
| 2 | Filtro `ids()` — `whereIn` sobre ids separados por coma                                            | `<Módulo>Filters`                  |
| 3 | `<Módulo>OptionSearchService` — la búsqueda más el `loadMissing` de lo que el `meta` necesita      | `Services/`                        |
| 4 | `<Módulo>OptionResource` — `value`, `label`, `meta`                                                | `Resources/`                       |
| 5 | `lookup()` con el permiso `.list`, tope de `per_page` y `has_more`                                 | `<Módulo>GetController`            |
| 6 | Ruta `{módulo}.lookup`, antes de `/{id}`. **Nunca `options`** (§3)                                 | `routes.php`                       |
| 7 | Quitar el catálogo del `FormOptionsService` de las pantallas que lo consumían                       | `<Consumidor>FormOptionsService`   |
| 8 | Memoria de lo elegido: `useRemoteOption` para un valor de cabecera; un hook de catálogo propio si son muchos (líneas) | `resources/js/hooks/`   |
| 9 | Sustituir `Select2` por `Select2Ajax` en la pantalla                                               | `components/`                      |
| 10 | Tests: forma de la opción, búsqueda por cada campo, aislamiento por empresa y estado, paginación con `has_more`, hidratación por `ids` incluido un registro desactivado, tope de `per_page` | `tests/Feature/<Módulo>/` |
| 11 | En las pantallas que dejaron de recibir el catálogo: `->missing('options.<catálogo>')`             | Tests del consumidor               |

**Lo que un select remoto nunca hace:** filtrar otra vez en el cliente lo que el servidor ya filtró (esconde
resultados), aceptar el `per_page` del cliente sin tope, recibir solo el id como valor, ni pedir una segunda vuelta al
servidor para saber algo que cabía en el `meta`.

---

## 7. Estado de implementación

| Pieza                                                                        | Estado     |
|------------------------------------------------------------------------------|------------|
| `Select2Ajax` + estilos compartidos con `Select2` (`createSelect2Styles`)     | Hecho      |
| Endpoint `items.lookup` (filtros `q`/`ids`, servicio, Resource, paginación)   | Hecho      |
| `useItemCatalog` con hidratación por `ids` y `hydrated`                       | Hecho      |
| Líneas de órdenes de venta y de compra                                        | Hecho      |
| Artículos fuera de las props de `SalesOrder` y `PurchaseOrder`                | Hecho      |
| Endpoint `clients.lookup` y `suppliers.lookup` (mismas piezas que `items`)    | Hecho      |
| `useRemoteOption` para el valor único de una cabecera                         | Hecho      |
| Select remoto de clientes (cabecera de la orden de venta)                     | Hecho      |
| Select remoto de proveedores (cabecera de la orden de compra y su listado)    | Hecho      |
| Clientes y proveedores fuera de las props de `SalesOrder` y `PurchaseOrder`   | Hecho      |

> **Las condiciones comerciales viajan en el `meta`.** Elegir al cliente sigue arrastrando su lista de precio, su
> vendedor asignado, sus días de crédito y su dirección de entrega sugerida; lo que cambió es de dónde salen: del `meta` de la opción y no
> del catálogo en props. Igual el proveedor con su moneda —que entra por la misma puerta que el select de moneda,
> para que arrastre su tasa— y sus días de crédito.
