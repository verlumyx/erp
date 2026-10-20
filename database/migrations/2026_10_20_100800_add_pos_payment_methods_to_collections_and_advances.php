<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_client_collections DROP CONSTRAINT IF EXISTS app_client_collections_payment_method_check');
            DB::statement("ALTER TABLE app_client_collections ADD CONSTRAINT app_client_collections_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");

            DB::statement('ALTER TABLE app_client_advances DROP CONSTRAINT IF EXISTS app_client_advances_payment_method_check');
            DB::statement("ALTER TABLE app_client_advances ADD CONSTRAINT app_client_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying]::text[]))");

            // Update any client collections previously mapped as 'card' or 'transfer' that originated from POS sales with specific payment methods (e.g. biopago, mobile_payment)
            DB::statement("
                UPDATE app_client_collections c
                SET payment_method = p.payment_method
                FROM app_pos_sales s
                JOIN app_pos_payments p ON p.pos_sale_id = s.id
                WHERE c.origin_type = 'invoice'
                  AND c.origin_id = s.sales_invoice_id
                  AND p.payment_method IN ('biopago', 'mobile_payment', 'card_debit', 'card_credit')
                  AND (c.reference = p.reference OR c.reference IS NULL)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_client_collections DROP CONSTRAINT IF EXISTS app_client_collections_payment_method_check');
            DB::statement("ALTER TABLE app_client_collections ADD CONSTRAINT app_client_collections_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");

            DB::statement('ALTER TABLE app_client_advances DROP CONSTRAINT IF EXISTS app_client_advances_payment_method_check');
            DB::statement("ALTER TABLE app_client_advances ADD CONSTRAINT app_client_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying]::text[]))");
        }
    }
};
