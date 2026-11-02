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
            // 1. app_supplier_payments
            DB::statement('ALTER TABLE app_supplier_payments DROP CONSTRAINT IF EXISTS app_supplier_payments_payment_method_check');
            DB::statement("ALTER TABLE app_supplier_payments ADD CONSTRAINT app_supplier_payments_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying, 'cash_ves'::character varying, 'cash_usd'::character varying, 'transfer_usd'::character varying]::text[]))");

            // 2. app_supplier_advances
            DB::statement('ALTER TABLE app_supplier_advances DROP CONSTRAINT IF EXISTS app_supplier_advances_payment_method_check');
            DB::statement("ALTER TABLE app_supplier_advances ADD CONSTRAINT app_supplier_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying, 'cash_ves'::character varying, 'cash_usd'::character varying, 'transfer_usd'::character varying]::text[]))");

            // 3. app_client_collections
            DB::statement('ALTER TABLE app_client_collections DROP CONSTRAINT IF EXISTS app_client_collections_payment_method_check');
            DB::statement("ALTER TABLE app_client_collections ADD CONSTRAINT app_client_collections_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying, 'cash_ves'::character varying, 'cash_usd'::character varying, 'transfer_usd'::character varying]::text[]))");

            // 4. app_client_advances
            DB::statement('ALTER TABLE app_client_advances DROP CONSTRAINT IF EXISTS app_client_advances_payment_method_check');
            DB::statement("ALTER TABLE app_client_advances ADD CONSTRAINT app_client_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying, 'cash_ves'::character varying, 'cash_usd'::character varying, 'transfer_usd'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_supplier_payments DROP CONSTRAINT IF EXISTS app_supplier_payments_payment_method_check');
            DB::statement("ALTER TABLE app_supplier_payments ADD CONSTRAINT app_supplier_payments_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");

            DB::statement('ALTER TABLE app_supplier_advances DROP CONSTRAINT IF EXISTS app_supplier_advances_payment_method_check');
            DB::statement("ALTER TABLE app_supplier_advances ADD CONSTRAINT app_supplier_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying]::text[]))");

            DB::statement('ALTER TABLE app_client_collections DROP CONSTRAINT IF EXISTS app_client_collections_payment_method_check');
            DB::statement("ALTER TABLE app_client_collections ADD CONSTRAINT app_client_collections_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'advance'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");

            DB::statement('ALTER TABLE app_client_advances DROP CONSTRAINT IF EXISTS app_client_advances_payment_method_check');
            DB::statement("ALTER TABLE app_client_advances ADD CONSTRAINT app_client_advances_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'biopago'::character varying, 'transfer'::character varying, 'check'::character varying, 'card'::character varying, 'other'::character varying]::text[]))");
        }
    }
};
