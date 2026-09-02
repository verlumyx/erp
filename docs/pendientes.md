# Pendientes

Flujos que las fichas de módulo describen y que el código todavía no ejecuta. **Todos los módulos, tablas y pantallas
existen**: lo que falta aquí es comportamiento, no estructura.

> Cada pendiente cita el documento que lo especifica y el archivo donde hoy se corta el camino. Al cerrarlo, tacha la
> fila del índice y borra su sección.

| #   | Pendiente                                            | Grupo             | Bloquea la operación |
|-----|------------------------------------------------------|-------------------|----------------------|
| ~~1~~ | ~~Aplicar el crédito a favor a facturas~~          | Ventas / Compras  | —                    |
| ~~2~~ | ~~El excedente del cobro no genera anticipo~~      | Ventas / Compras  | —                    |
| ~~3~~ | ~~Confirmar una factura de compra no hace nada~~   | Compras           | —                    |
| ~~4~~ | ~~La factura de venta directa no toca el kardex~~  | Ventas            | —                    |
| ~~5~~ | ~~Reserva y mercancía en tránsito~~                | Ventas / Compras  | —                    |
| ~~6~~ | ~~Control de crédito del cliente~~                 | Ventas            | —                    |
| ~~7~~ | ~~Vencimiento diario de facturas~~                 | Ventas / Compras  | —                    |
| ~~8~~ | ~~Menores~~                                        | Transversal       | —                    |

No queda ningún pendiente abierto de la lista original.

---

## Decisiones que la implementación tuvo que tomar

Tres huecos del modelo de datos no se podían cerrar solo con comportamiento. Quedan anotados aquí porque cambian lo
que las fichas describen.

**`credit_source_id` en `app_client_collections` y `app_supplier_payments`.** Cobrar con `payment_method` `advance` o
`credit_note` (`ventas.md` §6.2) exige decir **cuál** anticipo o cuál nota respalda el cobro, y con eso se decide el
`source_type` de las filas del reparto. `origin_type` / `origin_id` ya tienen dueño —el selector «Aplicar cobro a»—, así
que la columna es nueva. Sin foreign key, como `origin_id`: apunta a dos tablas según la forma de cobro y la valida
`ClientCollectionCreditSourceService`.

**`origin_collection_id` en `app_client_advances` y `origin_payment_id` en `app_supplier_advances`.** El anticipo que
nace del excedente de un cobro (§6.2) tiene que poder anularse cuando ese cobro se anula, y no hay `COB` espejo que los
ate. La columna es ese vínculo, y es nula en un anticipo capturado a mano.

**`app_sales_invoices` y `app_purchase_invoices` ya tenían todo lo demás.** Ninguna otra pieza pidió estructura nueva.

## Qué quedó fuera del alcance de cada regla

- **Anular un origen aplicado.** Una nota de crédito que se aplica entera a su factura queda `completed`, y `completed`
  es terminal: no se anula. Es la regla que ya tenía `UpdateStatusSalesCreditNoteRequest` y ahora tiene efecto. Para
  deshacerla hay que revertir la aplicación.
- **«Documentos abiertos» al desactivar un artículo o una bodega.** Se interpreta como los pedidos de venta y de compra
  vivos (`draft`, `confirmed`, `partial`). Las facturas y los despachos no cuentan: o ya movieron existencia —y entonces
  la bloquea la propia existencia—, o ya están cerrados.
- **Reserva y tránsito sin ubicación por defecto.** Reservar y anunciar no son movimientos físicos: una bodega sin
  ubicación por defecto no los registra en vez de tumbar el documento. El kardex sí sigue exigiéndola para mover saldo.
