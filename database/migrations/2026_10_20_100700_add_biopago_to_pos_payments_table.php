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
            DB::statement('ALTER TABLE app_pos_payments DROP CONSTRAINT IF EXISTS app_pos_payments_payment_method_check');
            DB::statement("ALTER TABLE app_pos_payments ADD CONSTRAINT app_pos_payments_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'transfer'::character varying, 'biopago'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE app_pos_payments DROP CONSTRAINT IF EXISTS app_pos_payments_payment_method_check');
            DB::statement("ALTER TABLE app_pos_payments ADD CONSTRAINT app_pos_payments_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'card_debit'::character varying, 'card_credit'::character varying, 'mobile_payment'::character varying, 'transfer'::character varying, 'credit_note'::character varying, 'other'::character varying]::text[]))");
        }
    }
};
