<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Older roles stored permission UUIDs in app_role_permissions.permission,
     * but the application (menus, User::hasPermission) keys on the permission
     * action string (e.g. "users.list"). Convert any stored permission id to
     * its corresponding action. Idempotent: rows already holding an action are
     * left untouched.
     */
    public function up(): void
    {
        $actionsById = DB::table('app_permissions')->pluck('action', 'id');

        foreach (DB::table('app_role_permissions')->get() as $rolePermission) {
            $action = $actionsById[$rolePermission->permission] ?? null;

            if ($action !== null && $action !== $rolePermission->permission) {
                DB::table('app_role_permissions')
                    ->where('id', $rolePermission->id)
                    ->update(['permission' => $action]);
            }
        }
    }

    public function down(): void
    {
        // No safe automatic reversal: the action form is the canonical value.
    }
};
