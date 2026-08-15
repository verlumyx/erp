# Reporte por Servicio / Plan

## Objetivo

Mostrar el desempeño comercial agrupado por **servicio** y por **plan**: qué se
vende más, cuánto ingreso genera cada uno, el ticket promedio y el mix de
capacidad (`profile` vs `full_account`). Sirve para decidir qué inventario
priorizar y qué planes conviene impulsar o retirar.

## Valor para el negocio

- Identifica los servicios/planes más rentables y los de bajo rendimiento.
- Permite comparar precio de venta real vs `roi_target_pct` del plan.
- Ayuda a planificar compras de inventario según demanda.
- Revela el mix `profile` / `full_account` para ajustar la oferta.

## Fuente de datos

Modelo principal: `App\Modules\Sale\Models\Sale` (tabla `app_sales`),
agrupado por `service_id` y/o `plan_id`.

Modelos de apoyo:
- `App\Modules\Service\Models\Service` (`code`, `name`, `max_profiles`, `active`).
- `App\Modules\Plan\Models\Plan` (`code`, `name`, `capacity`, `duration_days`,
  `sale_price`, `roi_target_pct`, `service_id`, `active`).

Campos de `Sale` usados en la agregación:

| Campo        | Uso                                                       |
|--------------|----------------------------------------------------------|
| `service_id` | Agrupación por servicio                                   |
| `plan_id`    | Agrupación por plan                                       |
| `capacity`   | Mix `profile` vs `full_account`                           |
| `price`      | Ingreso y ticket promedio                                 |
| `status`     | Filtrar/segmentar (`active`, `expired`, `cancelled`)     |
| `created_at` | Acotar al periodo del reporte                            |

Scopes disponibles: `scopeOfService`, `scopeActive`, `scopeExpired`,
`scopeCancelled`.

## Filtros

- **Rango de fechas** (`date_from`, `date_to`) sobre `created_at` de la venta.
  Por defecto: inicio de mes → hoy (igual que el reporte de ingresos/gastos).
- **Agrupación** (`group_by`): `service` | `plan`. Por defecto `service`.
- **Servicio** (`service_id`) — opcional, para profundizar en un servicio.
- **Estado** (`status`) — opcional: incluir/excluir `cancelled`.
- **Capacidad** (`capacity`) — opcional: `profile` | `full_account`.
- Paginación: `limit` / `offset`.
- `searched` (boolean): no consulta hasta que el usuario filtra.

## Métricas / Resumen (cards)

Calculadas en `ServicePlanSummaryService`:

- `total_sales` — nº total de ventas en el periodo.
- `total_revenue` — `SUM(price)`.
- `avg_ticket` — `total_revenue / total_sales`.
- `top_service` / `top_plan` — el de mayor ingreso.
- Desglose de mix capacidad: `profile_count`, `full_account_count`.

## Salida (tabla agrupada)

Una fila por servicio (o por plan, según `group_by`):

`code` · `nombre` · `nº ventas` · `ingreso total` · `ticket promedio` ·
`% del ingreso total` · (si `group_by=plan`) `sale_price` y `roi_target_pct`
del plan para comparar contra el ingreso real.

Ordenar por ingreso total descendente. Fila de **totales** al pie.

> Considerar gráfico de barras (ingreso por servicio) y dona (mix de capacidad)
> reutilizando el patrón visual del Dashboard.

## Implementación sugerida (arquitectura modular)

Módulo: `app/Modules/Report` (reutilizar el existente).

```
app/Modules/Report/
├── Controllers/
│   └── ServicePlanGetController.php      # index(): Inertia::render('reports/service-plan/index', ...)
├── Services/
│   └── ServicePlanSummaryService.php     # agregaciones por service/plan
└── routes.php                            # nueva ruta
```

La agregación conviene resolverla con el query builder de Eloquent
(`Sale::query()->selectRaw('service_id, COUNT(*) ..., SUM(price) ...')
->groupBy('service_id')`), respetando siempre el `company_id`. Encapsular esa
query en un método del repositorio del módulo `Sale` o en el service del Report.

### Controller (patrón a seguir)

Calcado de `IncomeExpenseGetController`:

```php
abort_unless($request->user()?->hasPermission('reports.service_plan') ?? false, 403);
```

- Lee filtros con defaults (`group_by = 'service'`, mes actual).
- Sólo ejecuta si `searched === true`.
- Devuelve `data`, `summary`, `meta`, `filters`.

### Ruta

En `app/Modules/Report/routes.php`, dentro del grupo `reports`:

```php
Route::get('/service-plan', [ServicePlanGetController::class, 'index'])
    ->name('reports.service-plan.index');
```

### Permiso

Registrar `reports.service_plan` (texto UI: "Ver reporte por servicio/plan"):

- `database/sql/seed_initial_modules.sql` (módulo reports, siguiente `order`).
- `database/seeders/MenuSeeder.php` (entrada de menú con ese `permission`).

> Ver memoria *Permisos/menús de un módulo*: registro vía SQL manual +
> `MenuSeeder`, no migraciones.

### Frontend

`resources/js/pages/reports/service-plan/index.tsx` siguiendo
`reports/income-expenses/index.tsx`: filtros (incl. toggle servicio/plan),
cards de resumen, tabla agrupada y, opcionalmente, gráficos. Estado vacío con
skeleton mientras no se ha buscado.

## Tests (obligatorio)

`tests/Feature/Report/ServicePlanReportTest.php` (Pest):

- Devuelve 403 sin el permiso `reports.service_plan`.
- No consulta hasta `searched=true`.
- Agrupa correctamente por servicio (`group_by=service`).
- Agrupa correctamente por plan (`group_by=plan`).
- `total_revenue` y `avg_ticket` se calculan bien.
- Filtra por rango de fechas y por `capacity`.
- Respeta el aislamiento por compañía (`company_id`).

Usar factories `SaleFactory`, `ServiceFactory` y `PlanFactory`.
