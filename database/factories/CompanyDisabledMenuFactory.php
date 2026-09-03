<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Shared\Models\CompanyDisabledMenu>
 */
class CompanyDisabledMenuFactory extends Factory
{
    protected $model = \App\Modules\Shared\Models\CompanyDisabledMenu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'menu_id' => Menu::factory(),
        ];
    }
}
