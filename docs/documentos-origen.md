# Documentos origen

Qué tiene que pasar cuando un documento nace de otro: hasta dónde llega lo que se arrastra, de dónde sale el
saldo pendiente y en qué orden se piden las cosas en la pantalla.

> Las convenciones de arquitectura modular, la política de no borrado y los permisos están en
> [README.md](README.md) y no se repiten aquí. El contrato del select remoto con el que se elige el documento
> origen está en [selects-remotos.md](selects-remotos.md).

**Contexto:** el ERP es una cadena de documentos —orden → entrada → factura → pago, pedido → despacho →
factura → cobro— y cada eslabón repite buena parte del anterior. Cuando la pantalla del eslabón nuevo solo
copiaba dos campos de cabecera, quien lo capturaba tenía que volver a teclear a mano lo que ya estaba en la
base: los mismos artículos, las mismas cantidades, los mismos precios. Y lo recapturado se desvía del original,
que es justo lo que el vínculo entre los dos documentos pretendía evitar.

---

## 1. La regla

**Elegir el documento origen tiene que traer todo lo que el módulo destino sepa recibir.** No una parte, no solo
la cabecera: todo lo que el destino tiene dónde guardar y el origen ya sabe.

Se aplica en tres planos:

**La cabecera.** Cada campo del documento nuevo que el origen ya conoce se copia. Si la factura de compra tiene
`warehouse_id`, `currency` y días de crédito, y la orden los tiene, los tres viajan. Un campo que el destino
tiene y el origen también, y que se queda vacío, es un error: obliga a recapturar algo que ya estaba escrito.

**Las líneas.** Siempre, sin botón que haya que pulsar. Cada línea del origen con saldo se convierte en una
línea del destino, con todo lo que el destino sepa guardar de ella: artículo, unidad, cantidad, y —si el destino
lleva importes— precio, descuento e impuesto **congelados del origen**, que son las condiciones que se pactaron
y no las que el catálogo diga hoy.

**La trazabilidad.** Cada línea nueva apunta a la línea del origen de la que salió (el par polimórfico
`sourceable_type` / `sourceable_id`, o el FK directo donde el módulo lo use). Sin eso el avance del origen no se
puede mover al confirmar el destino, y el saldo deja de cuadrar.

### Lo que no se arrastra

Tan importante como lo anterior, porque arrastrar de más también es un error:

- **Lo que el destino calcula.** Importes, impuestos, costo con flete prorrateado, saldos: los recalcula el
  backend a partir de cantidad, precio y porcentajes. No se copian.
- **Lo que es del destino y de nadie más.** El número impreso del proveedor, la serie fiscal, la fecha de
  emisión propia, la ubicación de bodega donde se guarda la mercancía. El origen no los sabe.
- **La cantidad original.** Lo que se copia es el **saldo**, no lo pedido. Una línea de 10 con 6 ya cubiertas
  entra con 4.
- **Lo que ya no queda.** Una línea del origen sin saldo no se ofrece: no hay nada que cubrir ahí.
- **Lo que ese documento no puede mover.** Un documento de mercancía —entrada, despacho— no recibe ni saca un
  artículo `service` ni uno `non_inventoried`: no llevan existencia, y su línea no le debe nada por ese lado.
  Por el de la factura sí, que un servicio se cobra igual. La respuesta la da `Item::movesStock()`, que es el
  único sitio donde vive esa regla.

### La cantidad queda editable

El saldo es el punto de partida, no un candado. Lo que manda es lo que de verdad ocurrió: lo que el proveedor
imprimió en su factura, lo que de verdad entró al muelle, lo que cabe en el bulto. Si el módulo necesita un
tope, lo pone el **backend** en su Request —nunca el formulario— y se mide contra el avance de la línea del
origen. Hoy lo tienen las dos facturas: no se factura más de lo que al documento origen le queda por facturar.

---

## 2. El orden de la pantalla: tercero → documento

Primero se elige el proveedor o el cliente; después su documento, con el select acotado a ese tercero
(`params={{ supplier_id }}` / `params={{ client_id }}`) y deshabilitado mientras no haya tercero.

Es el orden natural de quien captura —sabe de quién es el papel que tiene en la mano antes que su número— y
además evita el caso imposible: elegir un documento de otro tercero. Cambiar de tercero invalida el documento
origen elegido y le quita el vínculo a las líneas que venían de él.

---

## 3. El saldo: una cuenta por cada camino

**Un documento no tiene un saldo, tiene uno por cada camino que lo cumple.** Una orden de compra se cumple por
dos: la mercancía llega con las Entradas (`received_quantity`) y la deuda se reconoce con las Facturas de compra
(`invoiced_quantity`). Un pedido de venta igual, con `dispatched_quantity` e `invoiced_quantity`.

Y una línea puede deber por un camino y no por el otro: la de un servicio no le debe nada a la entrada —no hay
nada que recibir— y sí a la factura.

Ninguna de esas cuentas se materializa en una columna. **Ojo con `pending_quantity`**: existe en las líneas de
orden y de pedido, pero es solo el pendiente por *recibir* / por *despachar*, y no sirve para facturar. El saldo
se deriva siempre: `quantity - <el avance que corresponda>`.

### El endpoint

```
GET /{company}/{módulo}/{id}/{qué}-lines?ids=
```

Uno por pregunta, y el nombre dice cuál: `receivable-lines`, `invoiceable-lines`, `dispatchable-lines`.

| Parámetro | Quién lo manda | Qué hace                                                                             |
|-----------|----------------|--------------------------------------------------------------------------------------|
| `ids`     | La pantalla    | Ids separados por coma que vuelven **aunque su saldo esté en cero**: son las líneas que el documento que se edita ya tenía atadas. Sin ellos, reabrir el documento mostraría un vínculo a una línea que la pantalla ya no conoce. Es la misma convención con la que el `lookup` hidrata lo ya elegido. |

**Respuesta**

```json
{
  "data": [
    {
      "id": "0198…",
      "line_number": 1,
      "item_id": "0198…",
      "item_code": "ART000009",
      "item_name": "Taladro percutor",
      "measurement_unit_id": "0198…",
      "quantity": "10.0000",
      "received_quantity": "7.0000",
      "invoiced_quantity": "4.0000",
      "pending_quantity": "3.0000",
      "unit_price": "12.500000",
      "discount_percent": "0.0000",
      "tax_id": "0198…",
      "tax_percent": "16.0000",
      "withholding_percent": "0.0000",
      "notes": null
    }
  ]
}
```

El Resource manda **todos los avances de la línea** pase lo que pase, y además `pending_quantity` ya resuelto
contra el que preguntó: así ninguna pantalla repite la resta ni se equivoca de columna.

**Devuelve JSON, no Inertia**, y va **aparte del `lookup`**: el saldo solo interesa del documento que se eligió,
y meterlo en el `meta` de cada opción engordaría el menú entero para tirar diecinueve de veinte.

---

## 4. Las piezas

Viven en el módulo del documento **origen**, que es el que sabe cuánto debe:

| Pieza                            | Archivo                                          | Qué hace                                                                                 |
|----------------------------------|--------------------------------------------------|------------------------------------------------------------------------------------------|
| `<Módulo>PendingLinesService`    | `Services/`                                      | Busca el documento acotado a la empresa, carga artículo y unidad, y filtra las líneas activas con saldo. Recibe el avance contra el que medir, para no duplicarse por cada pregunta. |
| `<Módulo>PendingLineResource`    | `Resources/`                                     | La línea con sus avances y el `pending_quantity` resuelto.                                |
| `pendingLines()` + una acción por pregunta | `Controllers/<Módulo>GetController.php` | Traduce la ruta al avance y responde JSON.                                                 |
| La ruta                          | `<Módulo>/routes.php`                            | `/{id}/{qué}-lines`, con la restricción `$uuid` en `{id}`.                                 |
| `usePendingOrderLines`           | `resources/js/hooks/use-pending-order-lines.ts`  | El `fetch` desde el manejador del select, con `AbortController` y un contador que descarta la respuesta de un documento que el usuario ya cambió. Y la hidratación al abrir un documento guardado. |

El `loadMissing` va en el servicio y no en el repositorio, para que el listado del módulo no pague el costo de
lo que solo estas pantallas necesitan. Es la misma razón por la que existe `<Módulo>OptionSearchService`.

---

## 5. Las cadenas de hoy

| Origen                   | Destino                | Saldo contra          | ¿Carga sola? |
|--------------------------|------------------------|-----------------------|--------------|
| Orden de compra `OCO`    | Entrada `ENT`          | `received_quantity`   | Sí           |
| Orden de compra `OCO`    | Factura de compra `FCO`| `invoiced_quantity`   | Sí           |
| Pedido de venta `OVE`    | Despacho `DES`         | `dispatched_quantity` | Sí           |
| Pedido de venta `OVE`    | Factura de venta `FVE` | `invoiced_quantity`   | Sí           |
| Factura de venta `FVE`   | Devolución de venta `DVV`  | `returned_quantity` | No       |
| Factura de compra `FCO`  | Devolución de compra `DVC` | `returned_quantity` | No       |
| Factura de venta `FVE`   | Nota de crédito `NCC`  | lo ya acreditado      | No           |
| Factura de compra `FCO`  | Nota de crédito `NCP`  | lo ya acreditado      | No           |

`TRA → DES → ENT` no está en la tabla porque no la conduce una pantalla: los documentos del traslado los escribe
el backend al confirmar el anterior (ver [logistica.md](logistica.md)).

---

## 6. Lo que falta

Las cuatro cadenas que cuelgan de una factura siguen con el patrón viejo: elegir la factura copia su moneda y
nada más, y las líneas las trae un botón **«Copiar las líneas de la factura»** que hay que pulsar. Además leen
las líneas del `meta` del `lookup` de facturas en vez de pedir su saldo aparte.

Para cerrarlas hace falta, en los módulos `SalesInvoice` y `PurchaseInvoice`, la ruta
`/{id}/returnable-lines` (saldo contra `returned_quantity`) y su gemela para las notas de crédito, con las
piezas de §4; y en las cuatro pantallas, cambiar el botón por la carga automática de §1.

Archivos donde hoy se corta el camino:

- `resources/js/pages/sales-returns/hooks/useSalesReturnForm.ts` — `selectInvoice` / `copyInvoiceLines`
- `resources/js/pages/purchase-returns/hooks/usePurchaseReturnForm.ts` — ídem
- `resources/js/pages/sales-credit-notes/hooks/useSalesCreditNoteForm.ts` — ídem
- `resources/js/pages/purchase-credit-notes/hooks/usePurchaseCreditNoteForm.ts` — ídem

Y una de §2: **el despacho todavía va al revés**, pedido → cliente, con el cliente bloqueado por el pedido
(`resources/js/pages/dispatches/components/DispatchForm.tsx`). Las otras tres pantallas de la cadena ya van
tercero → documento.
