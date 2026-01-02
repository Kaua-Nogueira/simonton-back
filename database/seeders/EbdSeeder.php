<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SundaySchoolClass;
use App\Models\Member;
use App\Models\ClassEnrollment;

class EbdSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Classes
        $classes = [
            [
                'name' => 'Classe de Adultos',
                'target_audience' => 'Adultos',
                'location' => 'Salão Principal'
            ],
            [
                'name' => 'Jovens e Adolescentes',
                'target_audience' => '13 a 25 anos',
                'location' => 'Sala 2'
            ],
            [
                'name' => 'Crianças',
                'target_audience' => '4 a 12 anos',
                'location' => 'Sala 1'
            ],
        ];

        foreach ($classes as $classData) {
            $class = SundaySchoolClass::create($classData);

            // 2. Enroll random members
            $members = Member::inRandomOrder()->limit(5)->get();
            
            foreach ($members as $member) {
                // Ensure unique enrollment
                if (!ClassEnrollment::where('sunday_school_class_id', $class->id)
                    ->where('member_id', $member->id)
                    ->where('year', date('Y'))
                    ->exists()) {
                    
                    ClassEnrollment::create([
                        'sunday_school_class_id' => $class->id,
                        'member_id' => $member->id,
                        'role' => 'student',
                        'year' => date('Y')
                    ]);
                }
            }
        }
    }
}
