<?php

namespace Database\Seeders;

use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(MenuSeeder::class);
        $this->call(CurrencySeeder::class);

        if (DB::getDriverName() === 'pgsql') {
            $sqlPath = database_path('sql/seed_initial_modules.sql');
            if (File::exists($sqlPath)) {
                DB::unprepared(File::get($sqlPath));
            }
        }
    }
}
