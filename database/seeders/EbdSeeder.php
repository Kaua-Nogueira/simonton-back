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
        // 1. Create Standard IPB Classes
        $classes = [
            [
                'name' => 'Berçário (Cordeirinhos)',
                'target_audience' => '0 a 2 anos',
                'location' => 'Sala do Berçário'
            ],
            [
                'name' => 'Maternal (Sementinhas)',
                'target_audience' => '3 a 4 anos',
                'location' => 'Sala 01'
            ],
            [
                'name' => 'Primários',
                'target_audience' => '5 a 8 anos',
                'location' => 'Sala 02'
            ],
            [
                'name' => 'Juniores',
                'target_audience' => '9 a 11 anos',
                'location' => 'Sala 03'
            ],
            [
                'name' => 'Adolescentes',
                'target_audience' => '12 a 17 anos',
                'location' => 'Sala 04'
            ],
            [
                'name' => 'Jovens (Geração Eleita)',
                'target_audience' => '18 a 25 anos',
                'location' => 'Sala 05'
            ],
            [
                'name' => 'Classe de Adultos (Fé e Obra)',
                'target_audience' => 'Adultos em geral',
                'location' => 'Salão Social'
            ],
            [
                'name' => 'Classe de Casais',
                'target_audience' => 'Casais',
                'location' => 'Sala 06'
            ],
            [
                'name' => 'Catecúmenos',
                'target_audience' => 'Novos membros e interessados',
                'location' => 'Gabinete Pastoral'
            ],
        ];

        foreach ($classes as $classData) {
            $class = SundaySchoolClass::create($classData);

            // Fetch some random members to act as students
            $students = Member::inRandomOrder()->limit(rand(5, 12))->get();
            foreach ($students as $m) {
                // Use firstOrCreate to prevent duplicates if randomly selected as both roles
                ClassEnrollment::firstOrCreate([
                    'sunday_school_class_id' => $class->id,
                    'member_id' => $m->id,
                    'year' => date('Y')
                ], [
                    'role' => 'student'
                ]);
            }

            // Assign a teacher (could be a random member)
            $teacher = Member::inRandomOrder()->first();
            if ($teacher) {
                ClassEnrollment::firstOrCreate([
                    'sunday_school_class_id' => $class->id,
                    'member_id' => $teacher->id,
                    'year' => date('Y')
                ], [
                    'role' => 'teacher'
                ]);
            }
        }
    }
}
