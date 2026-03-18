<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocietiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Society::unguard();
        
        // Remove UMA if exists
        \App\Models\Society::where('abbreviation', 'UMA')->delete();

        $societies = [
            [
                'name' => 'União de Crianças Presbiterianas', 
                'abbreviation' => 'UCP', 
                'description' => 'Crianças da igreja.',
                'logo_path' => 'societies/logo_ucp.png'
            ],
            [
                'name' => 'União de Adolescentes Presbiterianos', 
                'abbreviation' => 'UPA', 
                'description' => 'Adolescentes da igreja.',
                'logo_path' => 'societies/logo_upa.png'
            ],
            [
                'name' => 'União de Mocidade Presbiteriana', 
                'abbreviation' => 'UMP', 
                'description' => 'Jovens da igreja.',
                'logo_path' => 'societies/logo_ump.png'
            ],
            [
                'name' => 'Sociedade Auxiliadora Feminina', 
                'abbreviation' => 'SAF', 
                'description' => 'Mulheres da igreja.',
                'logo_path' => 'societies/logo_saf.png'
            ],
            [
                'name' => 'União Presbiteriana de Homens', 
                'abbreviation' => 'UPH', 
                'description' => 'Homens da igreja.',
                'logo_path' => 'societies/logo_uph.png'
            ],
        ];

        foreach ($societies as $society) {
            \App\Models\Society::updateOrCreate(
                ['abbreviation' => $society['abbreviation']],
                array_merge($society, ['is_system' => true])
            );
        }

        \App\Models\Society::reguard();
    }
}
