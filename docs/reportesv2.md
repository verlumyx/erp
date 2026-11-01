# Reportes — Versión 1 (V1)

Especificación técnica y funcional de los cuatro reportes esenciales para la primera versión operativa del sistema:
**Cuentas por Cobrar**, **Cuentas por Pagar**, **Ventas por Artículos / Items** e **Ingresos y Gastos**.

> Las convenciones base del ERP (identificadores UUID v7, prefijos de código, política de no borrado,
> campos de auditoría, formato numérico y manejo multimoneda) están descritas en [README.md](README.md) y
> [monedas.md](monedas.md), y se asumen a lo largo de este documento.

---

## 1. Principios generales de los reportes en V1

1. **Solo lectura (`Read-Only`):** Los reportes no crean, mutan ni bloquean transacciones operativas. Consultan
   datos consolidados en las tablas maestras y de detalle existentes.
2. **Aislamiento por empresa (`company_id`):** Toda consulta se restringe estrictamente a la empresa activa en la
   sesión del usuario. Nunca se mezclan datos entre empresas.
3. **Filtro de fecha obligatorio:** Todo reporte transaccional (ventas, movimientos de dinero) exige un rango de
   fechas (`from_date` y `to_date`), evitando lecturas de tablas completas (*full table scans*). Los reportes de
   cartera y saldos vivos exigen una **Fecha de corte** (`as_of_date`).
4. **Multimoneda nativa:** Cada reporte soporta la doble expresión monetaria del ERP:
   - Los importes pueden consultarse en la **Moneda original del documento**, en la **Moneda base** de la
     empresa (usualmente `USD`) y en la **Moneda secundaria / legal** (`VES`), calculados mediante el servicio
     central `ExchangeRateResolver`.
   - En documentos históricos cerrados se respeta la tasa congelada del documento (`exchange_rate`).
   - En saldos vivos abiertos a una fecha de corte retrospectiva se permite valorar a la tasa histórica de emisión
     o a la tasa legal vigente a la fecha de corte.
5. **Exclusión de anulados:** Los documentos y líneas con `status = 'cancelled'` o `status = 'inactive'` quedan
   estrictamente fuera de los cálculos de saldos, ingresos, costos y utilidades.
6. **Triple canal de salida:** Cada reporte cuenta con:
   - Vista interactiva en pantalla (Inertia React con ordenamiento por columna, búsqueda y paginación).
   - Exportación a hoja de cálculo (**Excel `.xlsx` / CSV**) para análisis financiero.
   - Exportación a documento imprimible (**PDF**) con membrete fiscal y resumen gerencial.

---

## 2. Reporte de Cuentas por Cobrar (Cartera de Clientes)

### 2.1 Propósito y valor de negocio
Provee una visión integral de la cartera de crédito de la empresa: cuánto deben los clientes, cuánto tiempo tienen de
retraso sus pagos, qué documentos componen su deuda, y cuál es el impacto de los anticipos recibidos y las notas de
crédito pendientes por cruzar.

Es la herramienta central para:
- La gestión diaria de cobranzas de ejecutivos de venta y cobradores.
- El análisis financiero de liquidez y riesgo de incobrabilidad.
- El control de cupos y bloqueos preventivos de crédito.

### 2.2 Fuentes de datos y relaciones

| Concepto | Tabla principal | Filtros / Condiciones base |
|---|---|---|
| **Facturas de venta** | `app_sales_invoices` (`FVE`) | `status IN ('confirmed', 'completed')`, `sale_type = 'credit'`, `balance > 0` |
| **Clientes** | `app_clients` (`CLI`) | `status = 'active'`, información comercial y crediticia |
| **Anticipos recibidos** | `app_client_advances` (`ANC`) | `status = 'confirmed'`, `balance > 0` (anticipos disponibles sin aplicar) |
| **Notas de crédito** | `app_sales_credit_notes` (`NCC`) | `status = 'confirmed'`, `balance > 0` (saldos a favor del cliente) |
| **Cobros y abonos** | `app_client_collection_applications` | Pagos aplicados a cada factura para detalle de amortización |
| **Vendedores / Rutas** | `users`, `app_routes` | Trazabilidad por ejecutivo y zona logística |

### 2.3 Modos de visualización en V1

El reporte ofrece dos vistas complementarias:

#### Vista A: Detalle por Documento (Factura a Factura)
Lista una fila por cada factura de venta abierta. Permite al gestor de cobranza identificar exactamente qué factura está
vencida, su correlativo fiscal, días de mora y abonos realizados.

#### Vista B: Resumen de Antigüedad de Saldos por Cliente (Aging)
Agrupa la deuda total de cada cliente en tramos de envejecimiento en función de los días transcurridos desde su fecha de
vencimiento:
1. **Corriente / Por vencer:** Facturas cuya fecha de vencimiento es posterior o igual a la fecha de corte (`due_date >= as_of_date`).
2. **1 a 30 días de vencido:** `1 <= (as_of_date - due_date) <= 30`.
3. **31 a 60 días de vencido:** `31 <= (as_of_date - due_date) <= 60`.
4. **61 a 90 días de vencido:** `61 <= (as_of_date - due_date) <= 90`.
5. **Más de 90 días de vencido:** `(as_of_date - due_date) > 90`.

### 2.4 Filtros y parámetros de entrada

| Parámetro | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `as_of_date` | `date` | Sí | Fecha de corte (por defecto, fecha actual del sistema). |
| `view_mode` | `enum` | Sí | `summary` (Antigüedad por cliente) o `detail` (Detallado por documento). |
| `client_id` | `uuid` | No | Filtro por cliente específico (con selector remoto `Select2Ajax`). |
| `salesperson_id` | `uuid` | No | Filtro por vendedor asignado al cliente o a la factura. |
| `route_id` | `uuid` | No | Filtro por ruta / zona de despacho. |
| `payment_status` | `enum` | No | `all`, `overdue` (solo vencidas), `pending`, `partial`. |
| `currency_view` | `enum` | Sí | `base` (Moneda base de la empresa), `document` (Moneda original) o `secondary` (`VES`). |

### 2.5 Lógica de cálculo y reglas de negocio

1. **Días de mora:**
   $$\text{dias\_mora} = \text{as\_of\_date} - \text{due\_date}$$
   - Si $\text{dias\_mora} \le 0$, el documento está **vigente / por vencer**.
   - Si $\text{dias\_mora} > 0$, el documento presenta mora equivalente a esa cantidad de días.
2. **Saldo pendiente por factura:**
   $$\text{balance} = \text{total} - \text{paid\_amount}$$
   El reporte valida que solo se listen facturas cuyo saldo pendiente a la fecha de corte sea estrictamente mayor a cero.
3. **Créditos a favor del cliente (Deducciones del pasivo bruto):**
   - **Anticipos no aplicados:** Sumatoria de `balance` en `app_client_advances` confirmados.
   - **Notas de crédito disponibles:** Sumatoria de `balance` en `app_sales_credit_notes` confirmadas.
4. **Saldo neto exigible por cliente:**
   $$\text{Saldo Neto} = \sum \text{Facturas Pendientes} - \text{Anticipos Disponibles} - \sum \text{Notas de Crédito a Favor}$$
5. **Uso de límite de crédito:**
   $$\% \text{ Utilización} = \begin{cases} \left( \frac{\text{current\_balance}}{\text{credit\_limit}} \right) \times 100, & \text{si } \text{credit\_limit} > 0 \\ 0, & \text{si } \text{credit\_limit} = 0 \end{cases}$$

### 2.6 Columnas del reporte

#### Columnas — Vista Detallada (por documento)
1. **Cliente:** Código (`CLI`), RIF/Cédula y Razón Social.
2. **N° Control / Código:** Código interno (`FVE000001`) y N° Factura fiscal (`invoice_series` - `invoice_number`).
3. **Fecha de Emisión:** `invoice_date`.
4. **Fecha de Vencimiento:** `due_date`.
5. **Días de Crédito:** Días de plazo pactados (`payment_term_days`).
6. **Días Vencidos / Por Vencer:** Diferencia contra la fecha de corte (en rojo si $> 0$).
7. **Vendedor / Ruta:** Vendedor asignado y ruta comercial.
8. **Moneda Original:** Moneda del documento (`USD`, `VES`, `EUR`).
9. **Importe Total:** `total` en moneda original.
10. **Abonado / Pagado:** `paid_amount`.
11. **Saldo Pendiente:** `balance` en moneda original.
12. **Saldo en Moneda Base (`USD`):** Contravalor calculado a la tasa respectiva.
13. **Saldo en Bolívares (`VES`):** Contravalor a la tasa oficial del día de corte.
14. **Estado de Pago:** `pending`, `partial`, `overdue`.

#### Columnas — Vista Resumen (Antigüedad de saldos)
1. **Código y Cliente:** RIF y Nombre del cliente.
2. **Límite de Crédito:** Cupo asignado.
3. **Corriente (Por vencer):** Saldo de facturas no vencidas.
4. **1 a 30 días:** Saldo de facturas vencidas en este intervalo.
5. **31 a 60 días:** Saldo de facturas vencidas en este intervalo.
6. **61 a 90 días:** Saldo de facturas vencidas en este intervalo.
7. **+90 días:** Saldo de facturas con más de 90 días de retraso.
8. **Total Deuda Bruta:** Suma de todos los tramos de facturas.
9. **(-) Anticipos Disponibles:** Saldo de anticipos no aplicados.
10. **(-) Notas de Crédito Disponibles:** Saldo a favor por devoluciones o notas de crédito abiertas.
11. **Deuda Neta Total:** Monto real a cobrar en Moneda Base y en `VES`.

---

## 3. Reporte de Cuentas por Pagar (Pasivos con Proveedores)

### 3.1 Propósito y valor de negocio
Informa con precisión sobre todas las obligaciones pendientes de pago con proveedores por concepto de compras de
mercancías, materias primas o contratación de servicios operativos.

Permite a la gerencia de administración y finanzas:
- Proyectar los egresos de caja a corto y mediano plazo (semanal / quincenal / mensual).
- Priorizar pagos según plazos comerciales y evitar recargos o suspensión de suministros.
- Analizar el saldo neto contra proveedores considerando los anticipos entregados y notas de crédito pendientes de cruzar.

### 3.2 Fuentes de datos y relaciones

| Concepto | Tabla principal | Filtros / Condiciones base |
|---|---|---|
| **Facturas de compra** | `app_purchase_invoices` (`FCO`) | `status IN ('confirmed', 'completed')`, `balance > 0` |
| **Proveedores** | `app_suppliers` (`PRO`) | `status = 'active'`, datos fiscales y días de crédito |
| **Clasificación Proveedor** | `app_supplier_types` | Tipo de proveedor (materia prima, servicios, repuestos, etc.) |
| **Anticipos entregados** | `app_supplier_advances` (`ANP`) | `status = 'confirmed'`, `balance > 0` (dinero entregado no aplicado) |
| **Notas de crédito** | `app_purchase_credit_notes` (`NCP`) | `status = 'confirmed'`, `balance > 0` (descuentos o devoluciones a favor de la empresa) |
| **Pagos realizados** | `app_supplier_payment_applications` | Detalle de pagos y retenciones aplicadas |

### 3.3 Modos de visualización en V1

#### Vista A: Detalle por Factura de Proveedor
Permite revisar cada factura emitida por el proveedor (`supplier_invoice_number`), su fecha de recepción física en
almacén, fecha de emisión, fecha de vencimiento, retenciones aplicadas y saldo pendiente.

#### Vista B: Resumen de Antigüedad de Deuda por Proveedor (Aging de Pasivos)
Consolida la deuda por proveedor clasificada en columnas de vencimiento:
1. **Por vencer / Corriente:** `due_date >= as_of_date`.
2. **1 a 30 días vencido:** `1 <= (as_of_date - due_date) <= 30`.
3. **31 a 60 días vencido:** `31 <= (as_of_date - due_date) <= 60`.
4. **61 a 90 días vencido:** `61 <= (as_of_date - due_date) <= 90`.
5. **Más de 90 días vencido:** `(as_of_date - due_date) > 90`.

### 3.4 Filtros y parámetros de entrada

| Parámetro | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `as_of_date` | `date` | Sí | Fecha de corte del reporte. |
| `view_mode` | `enum` | Sí | `summary` (Resumen por proveedor) o `detail` (Detallado por documento). |
| `supplier_id` | `uuid` | No | Filtro por proveedor específico (`Select2Ajax`). |
| `supplier_type_id` | `uuid` | No | Filtro por tipo o categoría de proveedor. |
| `payment_status` | `enum` | No | `all`, `overdue`, `pending`, `partial`. |
| `due_within_days` | `integer` | No | Muestra solo facturas que vencen en los próximos $N$ días (ej. próximos 7 o 15 días). |
| `currency_view` | `enum` | Sí | `base`, `document` o `secondary` (`VES`). |

### 3.5 Lógica de cálculo y reglas de negocio

1. **Saldo deudor por factura:**
   $$\text{balance} = \text{total} - \text{paid\_amount}$$
2. **Retenciones en origen:** En facturas con retenciones fiscales de IVA o ISLR (`withholding_amount > 0`), el saldo
   exigible en efectivo al proveedor ya descuenta el comprobante de retención emitido. El reporte desglosa el monto
   retenido para cuadre contable.
3. **Anticipos y Notas de Crédito a favor de la empresa:**
   - Anticipos no amortizados (`app_supplier_advances.balance`).
   - Notas de crédito no cruzadas (`app_purchase_credit_notes.balance`).
4. **Saldo neto por pagar:**
   $$\text{Pasivo Neto} = \sum \text{Facturas Compra Pendientes} - \text{Anticipos a Favor} - \sum \text{Notas de Crédito a Favor}$$
5. **Manejo de facturas en moneda extranjera pagaderas en bolívares:** Cuando una factura de compra está pactada en
   dólares pero se cancela a la tasa oficial del día de pago, el reporte muestra el saldo vivo en divisa y su
   equivalencia en bolívares calculada a la tasa oficial de la fecha de corte.

### 3.6 Columnas del reporte

#### Columnas — Vista Detallada (por documento)
1. **Proveedor:** Código (`PRO`), RIF y Razón Social.
2. **N° Factura Proveedor:** Serie y correlativo impreso en el documento del proveedor (`supplier_invoice_series` - `supplier_invoice_number`).
3. **Código Interno:** Código ERP (`FCO000001`).
4. **Fecha Emisión:** `invoice_date`.
5. **Fecha Recepción:** `received_date` (momento en que llegó a la empresa).
6. **Fecha Vencimiento:** `due_date`.
7. **Días de Vencimiento:** Días restantes para vencer o días en mora.
8. **Moneda:** Moneda del documento.
9. **Total Factura:** Total facturado por el proveedor.
10. **Retenciones:** `withholding_amount` (IVA / ISLR retenido).
11. **Total Abonado:** Pagos aplicados.
12. **Saldo Pendiente:** Saldo en moneda original.
13. **Saldo en Moneda Base (`USD`):** Reexpresión en moneda de control.
14. **Saldo en Bolívares (`VES`):** Reexpresión en moneda de curso legal.
15. **Estado:** `pending`, `partial`, `overdue`.

#### Columnas — Vista Resumen (Antigüedad de deuda)
1. **Proveedor:** RIF y Nombre del proveedor.
2. **Tipo de Proveedor:** Clasificación (`app_supplier_types.name`).
3. **Corriente (Por vencer):** Pasivos no vencidos a la fecha de corte.
4. **1 a 30 días:** Deuda vencida en el primer rango.
5. **31 a 60 días:** Deuda vencida en el segundo rango.
6. **61 a 90 días:** Deuda vencida en el tercer rango.
7. **+90 días:** Deuda con mora superior a tres meses.
8. **Total Pasivo Bruto:** Suma de facturas pendientes.
9. **(-) Anticipos Entregados:** Saldo a favor por anticipos pendientes de amortizar.
10. **(-) Notas de Crédito:** Saldo a favor por notas de crédito pendientes de cobro/cruce.
11. **Total Neto a Pagar:** Saldo real adeudado en Moneda Base y en `VES`.

---

## 4. Reporte de Ventas por Artículos / Items

### 4.1 Propósito y valor de negocio
Ofrece un análisis detallado del desempeño comercial y operativo de los artículos y servicios comercializados por la
empresa dentro de un período determinado.

Permite a la gerencia comercial y de compras:
- Descubrir los artículos más vendidos en volumen físico y en importe monetario (Top Sellers / Ranking ABC).
- Conocer la utilidad bruta y el margen porcentual real aportado por cada producto, sustentado en el costo
  congelado al momento del despacho (`total_cost`).
- Detectar líneas o artículos con baja rotación o con márgenes comerciales negativos o insuficientes.
- Evaluar el impacto de las devoluciones de clientes sobre las ventas brutas.

### 4.2 Fuentes de datos y relaciones

| Concepto | Tabla principal | Descripción |
|---|---|---|
| **Líneas de factura de venta** | `app_sales_invoice_lines` | Cantidades facturadas, precio, costo congelado, margen y devoluciones |
| **Cabecera de factura** | `app_sales_invoices` (`FVE`) | `status IN ('confirmed', 'completed')`, fecha `invoice_date`, vendedor y cliente |
| **Ventas en Mostrador (POS)** | `app_pos_sale_lines` + `app_pos_sales` | Ventas directas de caja (unificadas opcionalmente en el canal retail) |
| **Artículos** | `app_items` (`ART`) | SKU, nombre, tipo (`inventoried`, `service`, `non_inventoried`), unidad base |
| **Categorías** | `app_categories` | Clasificación y agrupación de catálogo |
| **Bodegas** | `app_warehouses` (`BOD`) | Bodega de origen de la mercancía despachada |
| **Devoluciones** | `app_sales_returns` / `app_sales_return_lines` | Cantidades devueltas que reducen la venta neta |

### 4.3 Modos de visualización en V1

1. **Agrupado por Artículo:** Una fila por cada SKU / artículo vendido en el período, acumulando sus totales.
2. **Agrupado por Categoría:** Vista consolidada por categoría de productos para análisis de familias de catálogo.
3. **Detalle Transaccional de Ventas:** Listado línea por línea con fecha, factura, cliente y precio unitario pactado.

### 4.4 Filtros y parámetros de entrada

| Parámetro | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `from_date` | `date` | Sí | Fecha inicial del período (basado en `invoice_date`). |
| `to_date` | `date` | Sí | Fecha final del período. |
| `category_id` | `uuid` | No | Filtro por categoría de producto. |
| `item_id` | `uuid` | No | Filtro por artículo individual (`Select2Ajax`). |
| `item_type` | `enum` | No | `all`, `inventoried`, `service`, `non_inventoried`. |
| `warehouse_id` | `uuid` | No | Filtro por bodega de despacho. |
| `client_id` | `uuid` | No | Filtro por cliente específico. |
| `salesperson_id` | `uuid` | No | Filtro por vendedor responsable de la venta. |
| `order_by` | `enum` | No | `total_amount` (Monto vendido), `quantity` (Unidades), `margin` (Utilidad bruta). |
| `sort_direction`| `enum` | No | `desc` (por defecto) o `asc`. |

### 4.5 Lógica de cálculo y reglas de negocio

1. **Unidades vendidas netas:**
   $$\text{Cantidad Neta} = \sum \text{base\_quantity} - \sum \text{returned\_quantity}$$
2. **Venta bruta y venta neta:**
   - **Venta Bruta:** Suma de `subtotal` de las líneas del período.
   - **Descuentos Otorgados:** Suma de `discount_amount`.
   - **Venta Neta:**
     $$\text{Venta Neta} = \text{Venta Bruta} - \text{Descuentos}$$
3. **Costo de la mercancía vendida (CMV):**
   El costo se toma directamente de `app_sales_invoice_lines.total_cost`, el cual congela el costo unitario del lote o
   despacho físico (`unit_cost * base_quantity`).
   - En artículos de tipo `service` o `non_inventoried`, si no tienen costo asignado, `total_cost = 0`.
4. **Margen de utilidad bruta monetaria:**
   $$\text{Margen Bruto} = \text{Venta Neta} - \text{Costo Total}$$
5. **Porcentaje de margen bruto sobre venta:**
   $$\% \text{ Margen} = \begin{cases} \left( \frac{\text{Margen Bruto}}{\text{Venta Neta}} \right) \times 100, & \text{si } \text{Venta Neta} > 0 \\ 0, & \text{si } \text{Venta Neta} \le 0 \end{cases}$$
6. **Precio y costo promedio ponderado:**
   $$\text{Precio Promedio} = \frac{\text{Venta Neta}}{\text{Cantidad Neta}}$$
   $$\text{Costo Promedio} = \frac{\text{Costo Total}}{\text{Cantidad Neta}}$$
7. **Participación porcentual en ventas (% Share):**
   $$\% \text{ Participación} = \left( \frac{\text{Venta Neta del Artículo}}{\text{Venta Neta Total de la Empresa en el Período}} \right) \times 100$$
8. **Restricción de permisos de costo:** Quienes no posean el permiso `report.item_sales.view_cost` verán las columnas
   de Costo y Margen ocultas o bloqueadas, mostrando únicamente volúmenes e ingresos de venta.

### 4.6 Columnas del reporte (Vista Agrupada por Artículo)

1. **Código / SKU:** Identificador interno (`app_items.code` / `sku`).
2. **Nombre del Artículo:** Nombre comercial del producto o servicio.
3. **Categoría:** Nombre de la categoría a la que pertenece.
4. **Unidad Base:** Símbolo de la unidad de medida (`UND`, `KG`, `MTS`, etc.).
5. **Cantidad Vendida (Bruta):** Unidades facturadas.
6. **Cantidad Devuelta:** Unidades reintegradas por notas de crédito o devoluciones.
7. **Cantidad Neta:** Unidades reales colocadas.
8. **Precio Promedio Ponderado:** Precio unitario efectivo post-descuento.
9. **Venta Neta:** Total facturado sin IVA en Moneda Base (`USD`) y en `VES`.
10. **Costo Total:** Costo de inventario despachado en Moneda Base (*requiere permiso*).
11. **Margen Bruto ($):** Ganancia en valor absoluto (*requiere permiso*).
12. **% Margen:** Rentabilidad porcentual sobre venta (*requiere permiso*).
13. **% Participación:** Peso relativo del producto en la facturación total.

---

## 5. Reporte de Ingresos y Gastos

### 5.1 Propósito y valor de negocio
Proporciona una radiografía completa de los flujos financieros y la salud operativa del negocio dentro de un período
de tiempo determinado.

En una primera versión del ERP (previo a la existencia de un módulo contable completo con catálogo formal de cuentas y
partida doble), este reporte resuelve la necesidad fundamental del empresario:
- **¿Cuánto dinero real entró y salió de las cajas y cuentas bancarias?** (Criterio de Caja / Flujo de Fondos).
- **¿Cuál fue el resultado operativo del período entre lo vendido, lo gastado y el costo de venta?** (Criterio Económico / Devengado).

### 5.2 Estructura del reporte en V1: Dos criterios de análisis

El reporte cuenta con un selector principal de criterio metodológico:

```
                               ┌──────────────────────────────────────────────────────────┐
                               │               Reporte de Ingresos y Gastos               │
                               └────────────────────────────┬─────────────────────────────┘
                                                            │
                            ┌───────────────────────────────┴───────────────────────────────┐
                            ▼                                                               ▼
             ┌─────────────────────────────┐                                 ┌─────────────────────────────┐
             │      Criterio de Caja       │                                 │     Criterio Devengado      │
             │   (Flujo Real de Fondos)    │                                 │    (Resultado Económico)    │
             └──────────────┬──────────────┘                                 └──────────────┬──────────────┘
                            │                                                               │
              ┌─────────────┴─────────────┐                                   ┌─────────────┴─────────────┐
              ▼                           ▼                                   ▼                           ▼
        (+) Cobros a Clientes       (-) Pagos Proveedores               (+) Ventas Netas Facturadas  (-) Costo Mercancía Vendida
        (+) Cobros POS              (-) Gastos Operativos POS                                        (-) Gastos de Servicios Comprados
        (+) Entradas Caja POS       (-) Salidas / Sangrías POS                                       (-) Gastos Menores POS
              │                           │                                   │                           │
              └─────────────┬─────────────┘                                   └─────────────┬─────────────┘
                            ▼                                                               ▼
                     FLUJO NETO DE CAJA                                              UTILIDAD OPERATIVA
                  (Superávit / Déficit)                                                  ESTIMADA
```

---

### 5.3 Criterio A: Flujo de Efectivo Real (Cash Flow / Base Caja)

Registra exclusivamente los movimientos reales de fondos que entraron o salieron de la tesorería de la empresa en el
rango de fechas consultado.

#### 5.3.1 Componentes del Flujo de Fondos

##### 1. Ingresos de fondos (Inflows)
- **Cobros a clientes (`app_client_collections`):**
  - Cobros aplicados a facturas existentes (`origin_type = 'invoice'` o `'client'`).
  - Anticipos recibidos de clientes en efectivo/banco (`origin_type = 'advance'`).
  - *Condición:* `status IN ('confirmed', 'completed')`, filtrados por `collection_date`.
- **Cobros en Punto de Venta (`app_pos_payments`):**
  - Dinero recibido de ventas directas de mostrador (`cash`, `card`, `biopago`, `transfer`, etc.).
  - *Condición:* `status = 'active'`, asociado a sesiones POS cerradas o activas en la fecha.
- **Entradas manuales de efectivo POS (`app_pos_cash_movements` con `type = 'in'`):**
  - Aportes de capital o recargas de cambio (`change_replenishment`).

##### 2. Egresos de fondos (Outflows)
- **Pagos a proveedores (`app_supplier_payments`):**
  - Desembolsos realizados para cancelar facturas de compra (`origin_type = 'invoice'` o `'supplier'`).
  - Anticipos de dinero entregados a proveedores (`origin_type = 'advance'`).
  - *Condición:* `status IN ('confirmed', 'completed')`, filtrados por `payment_date`.
- **Gastos menores de caja chica POS (`app_pos_cash_movements` con `type = 'out'`, `concept = 'expense'`):**
  - Pagos de gastos operativos urgentes en tienda (ej. artículos de limpieza, hielo, transporte menor).
- **Retiros o sangrías de caja POS (`app_pos_cash_movements` con `type = 'out'`, `concept = 'cash_drop'`):**
  - Salidas de efectivo para depósito bancario o custodia en caja fuerte.

##### 3. Flujo Neto de Caja
$$\text{Flujo Neto del Período} = \text{Total Ingresos Recibidos} - \text{Total Egresos Pagados}$$

#### 5.3.2 Desglose por Medio de Pago y Cuenta
El reporte clasifica las entradas y salidas según el método de pago utilizado:
- **Efectivo en Bolívares (`VES`)**
- **Efectivo en Dólares (`USD`)**
- **Transferencias Bancarias Nacionales (por cuenta / banco receptor)**
- **Pago Móvil (C2P / P2P)**
- **Punto de Venta / Tarjetas de Débito y Crédito / Biopago**
- **Transferencias Internacionales / Zelle / Moneda Extranjera**

---

### 5.4 Criterio B: Rendimiento Operativo Simplificado (Económico / Devengado)

Compara las operaciones devengadas durante el período sin importar si ya fueron cobradas o pagadas en su totalidad.

#### 5.4.1 Componentes del Estado Operativo

1. **Ingresos Operativos:**
   - **Ventas Facturadas:** Suma del subtotal de facturas de venta emitidas (`app_sales_invoices` confirmadas en el período).
   - **(-) Notas de Crédito emitidas:** Descuentos y devoluciones a clientes en el período (`app_sales_credit_notes`).
   - **(=) Ventas Netas del Período.**
2. **Costo de Mercancía Vendida (CMV):**
   - Suma del costo congelado de las líneas de venta despachadas en el período (`app_sales_invoice_lines.total_cost`).
3. **(=) Utilidad Bruta Operativa:**
   $$\text{Utilidad Bruta} = \text{Ventas Netas} - \text{Costo de Mercancía Vendida}$$
4. **Gastos Operativos del Período:**
   - **Compras de Servicios e Insumos:** Líneas de facturas de compra (`app_purchase_invoice_lines`) de artículos tipo
     `service` o `non_inventoried` (fletes, honorarios, suministros, electricidad, alquileres).
   - **Gastos Menores de Operación:** Gastos registrados en caja chica (`app_pos_cash_movements.concept = 'expense'`).
   - **Variaciones de Costo no capitalizables:** Gasto derivado de variaciones en expedientes de importación
     (`app_import_lines.variance_amount`).
5. **(=) Resultado Operativo Estimado del Período:**
   $$\text{Resultado Operativo} = \text{Utilidad Bruta} - \text{Gastos Operativos}$$

---

### 5.5 Filtros y parámetros del reporte de Ingresos y Gastos

| Parámetro | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `from_date` | `date` | Sí | Fecha inicio del rango de análisis. |
| `to_date` | `date` | Sí | Fecha fin del rango de análisis. |
| `report_basis` | `enum` | Sí | `cash_flow` (Flujo de Efectivo / Caja) o `accrual` (Económico / Devengado). |
| `payment_method`| `enum` | No | Filtro por método de pago específico (`cash`, `transfer`, `card`, `biopago`, `zelle`, etc.). |
| `bank_account` | `string`| No | Filtro por cuenta bancaria receptora/emisora. |
| `currency_view` | `enum` | Sí | `base` (expresado en `USD`), `secondary` (`VES`) o `both` (Doble columna). |

### 5.6 Estructura de salida en pantalla

#### Vista Resumen Ejecutivo (Kpis superiores)
- **Ingresos Totales:** Suma consolidada del período en Moneda Base y en `VES`.
- **Egresos / Gastos Totales:** Suma consolidada del período en Moneda Base y en `VES`.
- **Resultado Neto:** Superávit (verde) o Déficit (rojo) resultante.
- **Margen Operativo:** Porcentaje de rentabilidad neta sobre ingresos.

#### Tabla de Detalle por Rubro / Concepto

| Fecha | Concepto / Origen | Tipo de Movimiento | Medio de Pago / Banco | N° Ref / Doc | Monto Original | Monto USD | Monto VES |
|---|---|---|---|---|---|---|---|
| `2026-10-15` | Cobro Factura FVE000120 | Ingreso (Cliente ABC) | Transferencia Banesco | Ref-98212 | 150.00 USD | $150.00 | Bs. 5,475.00 |
| `2026-10-15` | Pago Factura FCO000045 | Egreso (Proveedor XYZ) | Transferencia Mercantil| Ref-11029 | 300.00 USD | $300.00 | Bs. 10,950.00 |
| `2026-10-16` | Gasto Caja Chica | Egreso (Artículos Limpieza) | Efectivo VES | Comp-004 | 450.00 VES | $12.32 | Bs. 450.00 |
| `2026-10-16` | Venta Mostrador POS | Ingreso (Ticket POS-0089) | Tarjeta Débito / Biopago | Lote-4412 | 82.50 USD | $82.50 | Bs. 3,011.25 |

---

## 6. Arquitectura técnica e implementación

### 6.1 Módulo y estructura de archivos recomendada
Para mantener el estándar de arquitectura modular del ERP, la lógica de reportería se ubica en el módulo `Report`:

```
app/Modules/Report/
├── Controllers/
│   ├── AccountsReceivableReportGetController.php
│   ├── AccountsPayableReportGetController.php
│   ├── ItemSalesReportGetController.php
│   ├── IncomeExpenseReportGetController.php
│   └── ReportExportPostController.php
├── Requests/
│   ├── AccountsReceivableReportRequest.php
│   ├── AccountsPayableReportRequest.php
│   ├── ItemSalesReportRequest.php
│   └── IncomeExpenseReportRequest.php
├── Services/
│   ├── AccountsReceivableReportService.php
│   ├── AccountsPayableReportService.php
│   ├── ItemSalesReportService.php
│   ├── IncomeExpenseReportService.php
│   └── ReportPdfExportService.php
├── Resources/
│   ├── AccountsReceivableReportResource.php
│   ├── AccountsPayableReportResource.php
│   ├── ItemSalesReportResource.php
│   └── IncomeExpenseReportResource.php
├── Providers/
│   └── ReportServiceProvider.php
└── routes.php
```

### 6.2 Rutas del sistema (`routes.php`)

```php
Route::middleware(['auth', 'verified'])->prefix('{company}/reports')->group(function () {
    Route::get('/accounts-receivable', [AccountsReceivableReportGetController::class, 'handle'])
        ->name('reports.accounts-receivable');

    Route::get('/accounts-payable', [AccountsPayableReportGetController::class, 'handle'])
        ->name('reports.accounts-payable');

    Route::get('/item-sales', [ItemSalesReportGetController::class, 'handle'])
        ->name('reports.item-sales');

    Route::get('/income-expense', [IncomeExpenseReportGetController::class, 'handle'])
        ->name('reports.income-expense');

    Route::post('/export', [ReportExportPostController::class, 'handle'])
        ->name('reports.export');
});
```

### 6.3 Matriz de permisos requeridos (`app_permissions`)

Cada reporte se protege mediante un permiso específico e independiente:

| Permiso técnico | Nombre visible | Descripción | Roles sugeridos |
|---|---|---|---|
| `report.accounts_receivable` | Ver reporte de cuentas por cobrar | Acceso a cartera y cobranzas de clientes | Admin, Gerente de Ventas, Cobrador |
| `report.accounts_payable` | Ver reporte de cuentas por pagar | Acceso a deudas y compromisos con proveedores | Admin, Gerente de Finanzas, Compras |
| `report.item_sales` | Ver ventas por artículo | Acceso a volúmenes y montos facturados por ítem | Admin, Ventas, Inventario |
| `report.item_sales.view_cost` | Ver costos y márgenes de venta | Permite visualizar `total_cost` y porcentajes de utilidad | Admin, Gerente General |
| `report.income_expense` | Ver reporte de ingresos y gastos | Acceso al flujo financiero y operativo del negocio | Admin, Director, Contador |
| `report.export` | Exportar reportes | Capacidad de descargar en formato Excel o PDF | Admin, Gerentes de Área |

### 6.4 Optimización de consultas y rendimiento

1. **Uso de agregaciones directas en base de datos:** Los cálculos de totales y promedios no deben cargar colecciones
   de modelos en memoria PHP; se resuelven con funciones de agregación (`SUM`, `COUNT`, `AVG`) en la consulta Eloquent
   o Query Builder.
2. **Índices de base de datos utilizados:**
   - Cuentas por cobrar: `index(company_id, payment_status, due_date)`.
   - Cuentas por pagar: `index(company_id, payment_status, due_date)`.
   - Ventas por artículo: `index(company_id, invoice_date)` en cabecera y `index(sales_invoice_id, item_id)` en líneas.
   - Ingresos y gastos: `index(company_id, collection_date)` y `index(company_id, payment_date)`.
3. **Paginación en vistas detalladas:** En pantalla se utiliza paginación por servidor (máximo 50 a 100 registros por
   página). Las exportaciones completas se ejecutan directamente en streaming o a través de colas si superan 1.000 filas.

---

## 7. Próximos pasos hacia versiones posteriores (Roadmap)

1. **Programación automática:** Envío periódico por correo electrónico (ej. resumen diario de cuentas por cobrar a
   las 7:00 AM para cobradores y vendedores).
2. **Gráficas y comparativas interanuales:** Gráficos de barras y líneas para comparar ventas por artículo mes actual
   vs. mes del año anterior.
3. **Conciliación bancaria integrada:** Cruce automático entre los cobros/pagos registrados y los estados de cuenta
   importados del banco.
4. **Vistas personalizadas:** Capacidad para que cada usuario guarde sus columnas y filtros favoritos en `app_report_views`.
