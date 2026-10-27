<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VehicleLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'marketing', 'sale'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        foreach ([
            ['name' => 'Vios', 'slug' => 'vios', 'category' => 'sedan'],
            ['name' => 'Yaris Cross', 'slug' => 'yaris-cross', 'category' => 'suv-crossover'],
            ['name' => 'Corolla Cross', 'slug' => 'corolla-cross', 'category' => 'suv-crossover'],
        ] as $index => $line) {
            VehicleLine::firstOrCreate(
                ['slug' => $line['slug']],
                [...$line, 'sort_order' => $index + 1, 'is_published' => false],
            );
        }

        $this->call([
            ContentPagesSeeder::class,
            SiteSettingsSeeder::class,
            ThemeSettingsSeeder::class,
            UiStringsSeeder::class,
            ToyotaCatalogSeeder::class,
            VehicleColorCatalogSeeder::class,
            ToyotaOfficialNewsSeeder::class,
        ]);

        $email = config('dealer.initial_admin.email');
        $password = config('dealer.initial_admin.password');

        if (is_string($email) && $email !== '' && is_string($password) && strlen($password) >= 12) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => config('dealer.initial_admin.name') ?: 'Administrator',
                    'password' => Hash::make($password),
                    'is_active' => true,
                ],
            );

            $user->assignRole('admin');
        }
    }
}
