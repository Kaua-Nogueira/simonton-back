<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Member;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Foundational Data
            FinancialCategoriesSeeder::class,
            SocietiesSeeder::class,
            EcclesiasticalRolesSeeder::class,
            
            // ACL, Menus and Permissions
            AclSeeder::class,
            
            // Master Admin
            SuperAdminSeeder::class,
            
            // Optional/Modules Data
            EbdSeeder::class,
        ]);
    }
}
