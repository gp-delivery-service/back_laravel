<?php

namespace Database\Seeders;

use App\Models\GpMapDistrict;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GpDistrictsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districts = [
            [
                "id" => 1,
                "name" => "Ким район",
                "coordinates" => [
                    [
                        58.36244260822289,
                        37.96230583095078
                    ],
                    [
                        58.360448373194316,
                        37.96057924377011
                    ],
                    [
                        58.35893086287905,
                        37.95794504007493
                    ],
                    [
                        58.35886774063101,
                        37.95754688282834
                    ],
                    [
                        58.37174467910896,
                        37.950752992209885
                    ],
                    [
                        58.37941403217235,
                        37.94664648993317
                    ],
                    [
                        58.38812485666847,
                        37.94209175936949
                    ],
                    [
                        58.3929537085985,
                        37.93950314017012
                    ],
                    [
                        58.39797192727022,
                        37.93681486218803
                    ],
                    [
                        58.400843989528255,
                        37.940224975845325
                    ],
                    [
                        58.39762475490903,
                        37.94176818695583
                    ],
                    [
                        58.39544703737204,
                        37.94261445025819
                    ],
                    [
                        58.39421615354695,
                        37.9436598208835
                    ],
                    [
                        58.393174636464806,
                        37.94438161573137
                    ],
                    [
                        58.38752519531897,
                        37.9473433893171
                    ],
                    [
                        58.375689768294336,
                        37.95373941703535
                    ],
                    [
                        58.37348048963389,
                        37.95495882999508
                    ],
                    [
                        58.36893568095496,
                        37.95744737394996
                    ],
                    [
                        58.3653061517266,
                        37.9600353609777
                    ],
                    [
                        58.36244260822289,
                        37.96230583095078
                    ]

                ]
            ]
        ];

        foreach ($districts as $district) {
            $new_district = GpMapDistrict::updateOrCreate(
                [
                    'id' => $district['id'],
                ],
                [
                    'name' => $district['name'],
                ]
            );
            foreach ($district['coordinates'] as $key => $coordinate) {
                $new_district->points()->updateOrCreate(
                    [
                        'district_id' => $new_district->id,
                        'order' => $key,
                    ],
                    [
                        'lat' => $coordinate[1],
                        'lng' => $coordinate[0],
                    ]
                );
            }
            $new_district->save();
            $new_district->points()->saveMany($new_district->points);
        }
    }
}
