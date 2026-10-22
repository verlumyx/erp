# Providencia Administrativa SENIAT N° SNAT/2011/00071
## Normas Generales de Emisión de Facturas y Otros Documentos (Facturación Computarizada y Digital)

Este documento establece las directrices y requerimientos técnicos que el ERP debe cumplir para ajustarse a la normativa venezolana de facturación dictada por el SENIAT mediante la **Providencia Administrativa N° SNAT/2011/00071** y la Ley del IVA, bajo un enfoque **100% computarizado y digital** (sin depender de hardware de máquinas fiscales).

El texto íntegro oficial de la norma se encuentra disponible en [`docs/PA00071_2011.pdf`](file:///home/verlumyx/Documentos/dev/erp/docs/PA00071_2011.pdf).

---

## 1. Alcance Operativo (Software Unificado)

Al prescindir de impresoras fiscales físicas, tanto la facturación administrativa tradicional ([`SalesInvoice`](file:///home/verlumyx/Documentos/dev/erp/app/Modules/SalesInvoice/Models/SalesInvoice.php)) como el Punto de Venta ([`Pos`](file:///home/verlumyx/Documentos/dev/erp/app/Modules/Pos)) operan bajo el **mismo motor fiscal de software**:
1. **Facturas de Venta** (Art. 13 y 15).
2. **Órdenes de Entrega o Guías de Despacho** (Art. 20 y 21).
3. **Notas de Débito y Notas de Crédito** (Art. 22 y 23).
4. **Libro de Ventas Fiscal** mensual para el SENIAT.

---

## 2. Diagnóstico del ERP: Brechas frente a la Norma

| Componente | Requisito Legal (PA 00071) | Estado Actual en Código | Acción Requerida |
|---|---|---|---|
| **Empresa Emisora** ([`Company`](file:///home/verlumyx/Documentos/dev/erp/app/Modules/Company/Models/Company.php)) | RIF (Art. 13.5), Domicilio Fiscal formal y Condición Tributaria (Ordinario, Formal, Especial). | ✅ **Completado.** Columnas `rif`, `fiscal_address` y `taxpayer_type` en `app_companies` con validación, comandos, repositorio y UI. | — |
| **Impuestos Estándar por Empresa** ([`TaxProvisionService`](file:///home/verlumyx/Documentos/dev/erp/app/Modules/Tax/Services/TaxProvisionService.php)) | Alícuotas oficiales automáticas: Base General (16%), Reducida (8%), Adicional (31%), Exenta (0%). | ✅ **Completado.** `tax_type` enum (`general`, `reduced`, `additional`, `exempt`). Creación automática de los 4 impuestos al registrar una empresa vía `CompanyCreateService`. | — |
| **Impresión de Ticket POS** ([`printReceipt.ts`](file:///home/verlumyx/Documentos/dev/erp/resources/js/pages/pos/utils/printReceipt.ts)) | RIF y Domicilio fiscal emisor, marcas `(E)` / `(16%)` por línea, desglose de bases por alícuota en totales y Tasa BCV oficial. | ✅ **Completado.** Ticket 58mm térmico y modal de previsualización adaptados con desglose dinámico según los impuestos de la venta. | — |
| **Número de Control** | Número de control correlativo / fiscal independiente del número de documento (Art. 13.3). | En progreso: implementado en `app_dispatches` (`00-00000001`). Pendiente en facturas y notas de crédito. | Agregar `control_number` en `app_sales_invoices` y `app_sales_credit_notes`. |
| **Bases Imponibles e Impuestos en Facturas** | Discriminación obligatoria: Base General (16%), Reducida (8%), Adicional (31%), Exenta (Art. 13.10 y 13.11). | `app_sales_invoices` solo tiene un acumulador global `tax_amount`. | Desglosar bases imponibles e impuestos por alícuota en cabecera y líneas (en divisa y VES). |
| **Marca de Exención** | Los artículos exentos o exonerados deben llevar la marca `(E)` al lado del ítem o precio (Art. 13.8). | No implementado. | Agregar flag `is_exempt` en líneas y formateador `(E)` en vistas y reportes. |
| **Doble Expresión Monetaria** | Constar ambas cantidades (divisa y Bs.) con tipo de cambio oficial BCV aplicable (Art. 13.14). | Cabecera tiene campos VES; las líneas de factura no tienen valores unitarios en VES. | Persistir importes unitarios y totales en VES a nivel de línea. |
| **Notas de Débito** | Emisión de Notas de Débito por ajustes al alza o cargos (Art. 22 y 23). | **Módulo no existe.** Solo existe `SalesCreditNote`. | Crear módulo completo `SalesDebitNote`. |
| **Notas de Crédito** | Referencia inmutable a Factura afectada: Fecha, Número, Monto y **Número de Control** (Art. 23). | Solo relaciona `sales_invoice_id` internamente; no almacena número de control de factura afectada. | Guardar `affected_invoice_control_number`, fecha y monto de forma congelada. |
| **Guías de Despacho** | Título "Guía de Despacho" / "Orden de Entrega", leyenda **"sin derecho a crédito fiscal"**, número de control y motivo si no es venta (Art. 20, 21). | ✅ **Completado.** Columnas `control_number`, `is_sale`, `transfer_reason` y `transfer_reason_detail` en `app_dispatches`. Plantilla PDF adaptada con denominación oficial dual, leyenda legal "SIN DERECHO A CRÉDITO FISCAL", RIF/domicilio emisor y desglose de motivos de traslado (reparación, almacenes, etc.). Interfaz web y reportes actualizados. | — |
| **Plantillas de Factura** | Formatos oficiales de impresión física o PDF (Carta y Ticket 58mm/80mm). | ✅ **Completado.** Plantilla oficial Carta en PDF ([`sales-invoice.blade.php`](file:///home/verlumyx/Documentos/dev/erp/resources/views/pdf/sales-invoice.blade.php)) y formato de ticket térmico 58mm idéntico a la plantilla del Punto 4 con desglose de bases y tasa BCV. | — |
| **Libro de Ventas Fiscal** | Registro cronológico mensual obligatorio con todas las especificaciones de IVA (Art. 37 PA 00071 y Ley del IVA). | **Inexistente.** | Crear generador y exportador del Libro de Ventas en formato oficial SENIAT. |

---

## 3. Hoja de Ruta de Implementación

1. **Fase 1: Datos Fiscales y Número de Control en Base de Datos**
   - Migración para agregar `rif`, `fiscal_address`, `taxpayer_type` a `app_companies`.
   - Agregar columna `control_number` en `app_sales_invoices`, `app_sales_credit_notes`, `app_dispatches`.
2. **Fase 2: Motor de Cálculo y Formatos de Impresión (PDF y Ticket 80mm)**
   - Desglose de alícuotas (General, Reducida, Exenta) e impuestos en cabecera y líneas en USD y VES.
   - Creación de plantillas oficiales PDF para Factura de Venta (Carta para ventas B2B y Ticket 80mm para POS).
3. **Fase 3: Notas de Débito y Ajuste de Guías de Despacho**
   - Implementar módulo `SalesDebitNote`.
   - ✅ **Completado.** Ajuste de `app_dispatches` y su plantilla Blade (`dispatch-delivery-note.blade.php`) a los requerimientos de los Artículos 20 y 21.
4. **Fase 4: Libro de Ventas Fiscal**
   - Módulo de generación de Libro de Ventas mensual exportable a Excel/PDF.


--- 

## 4. PLantilla del ticket:

    ================================                                                                                                                                                                                                  
         DISTRIBUIDORA EJEMPLO, C.A.                                                                                                                                                                                                  
            RIF: J-30999999-0                                                                                                                                                                                                         
      Av. Bolívar, Local 4, Caracas                                                                                                                                                                                                   
            Tel: 0212-5551234                                                                                                                                                                                                         
    ================================                                                                                                                                                                                                  
                FACTURA                                                                                                                                                                                                               
    FACTURA N°:       FVE-00001045                                                                                                                                                                                                    
    N° CONTROL:       00-00045210                                                                                                                                                                                                     
    FECHA: 21/10/2026  HORA: 15:42                                                                                                                                                                                                    
    CAJA: 01    CAJERO: USR-002                                                                                                                                                                                                       
    --------------------------------                                                                                                                                                                                                  
    CLIENTE: JUAN PÉREZ                                                                                                                                                                                                               
    CI/RIF:  V-18.452.123                                                                                                                                                                                                             
    DIR:     Caracas, Dto. Capital                                                                                                                                                                                                    
    --------------------------------                                                                                                                                                                                                  
    CANT DESCRIPCIÓN         TOTAL $                                                                                                                                                                                                  
    --------------------------------                                                                                                                                                                                                  
     2.0 Harina Pan (E)         2.20                                                                                                                                                                                                  
     1.0 Aceite 1L (16%)        2.40                                                                                                                                                                                                  
     3.0 Refresco (16%)         4.50                                                                                                                                                                                                  
    --------------------------------                                                                                                                                                                                                  
    Subtotal:                $  9.10                                                                                                                                                                                                  
    Exento:                  $  2.20                                                                                                                                                                                                  
    Base Imponible (16%):    $  6.90                                                                                                                                                                                                  
    IVA (16%):               $  1.10                                                                                                                                                                                                  
    --------------------------------                                                                                                                                                                                                  
    TOTAL USD:               $ 10.20                                                                                                                                                                                                  
    Tasa BCV:           40.50 Bs./$                                                                                                                                                                                                   
    TOTAL VES:           Bs.  413.10                                                                                                                                                                                                  
    --------------------------------                                                                                                                                                                                                  
    PAGOS:                                                                                                                                                                                                                            
    Efectivo USD:            $ 10.00                                                                                                                                                                                                  
    Pago Móvil:          Bs.    8.10                                                                                                                                                                                                  
    --------------------------------                                                                                                                                                                                                  
          ¡GRACIAS POR SU COMPRA!                                                                                                                                                                                                     
    ================================ 
