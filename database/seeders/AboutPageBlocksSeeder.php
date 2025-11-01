<?php

namespace Database\Seeders;

use App\Models\AboutPageBlock;
use Illuminate\Database\Seeder;

class AboutPageBlocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing blocks
        AboutPageBlock::truncate();

        $blocks = [
            // 1. Hero Block
            [
                'type' => 'hero',
                'order' => 0,
                'data' => [
                    'title' => 'Haqqımızda',
                    'description' => 'Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyi - kənd təsərrüfatı sahəsində təlim və məsləhətçilik xidmətlərinin aparıcı təşkilatı',
                    'image' => 'https://images.unsplash.com/photo-1516253593875-bd1e9833a440?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxhZ3JpY3VsdHVyYWwlMjBmaWVsZCUyMGxhbmRzY2FwZXxlbnwxfHx8fDE3NTg3OTkwNjZ8MA&ixlib=rb-4.1.0&q=80&w=1080',
                    'icon' => 'Sprout',
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 48,
                        'fontWeight' => 'bold',
                        'color' => '#FFFFFF',
                        'textAlign' => 'center',
                    ],
                    'description' => [
                        'fontSize' => 20,
                        'color' => 'rgba(255, 255, 255, 0.9)',
                        'textAlign' => 'center',
                    ],
                ],
            ],

            // 2. Mission & Vision Heading
            [
                'type' => 'heading',
                'order' => 1,
                'data' => [
                    'text' => 'Missiyamız və Vizyonumuz',
                    'level' => 2,
                ],
                'styles' => [
                    'fontSize' => 30,
                    'fontWeight' => 'bold',
                    'color' => '#0d1f0d',
                    'textAlign' => 'center',
                ],
            ],

            // 3. Mission Card
            [
                'type' => 'text',
                'order' => 2,
                'data' => [
                    'title' => 'Missiyamız',
                    'content' => 'Azərbaycan kənd təsərrüfatının müasirləşdirilməsi və fermerlərinin biliklərinin artırılması məqsədilə keyfiyyətli təlim proqramları, praktiki məsləhətlər və innovativ həllər təqdim etmək. Ölkəmizdə qida təhlükəsizliyinin təmin edilməsi və kənd təsərrüfatı məhsuldarlığının artırılmasına töhfə vermək.',
                    'icon' => 'Target',
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 20,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                    ],
                    'content' => [
                        'fontSize' => 16,
                        'color' => '#6b7c6b',
                        'lineHeight' => 1.7,
                    ],
                ],
            ],

            // 4. Vision Card
            [
                'type' => 'text',
                'order' => 3,
                'data' => [
                    'title' => 'Vizyonumuz',
                    'content' => 'Regionun aparıcı aqrar təlim və məsləhətçilik mərkəzi olmaq. Müasir texnologiyalar və ən yaxşı təcrübələr əsasında fermerlərə, aqronomlara və kənd təsərrüfatı şirkətlərinə dünyəvi standartlarda xidmətlər göstərmək. Davamlı və ekoloji təmiz kənd təsərrüfatının inkişafına rəhbərlik etmək.',
                    'icon' => 'Eye',
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 20,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                    ],
                    'content' => [
                        'fontSize' => 16,
                        'color' => '#6b7c6b',
                        'lineHeight' => 1.7,
                    ],
                ],
            ],

            // 5. Statistics Heading
            [
                'type' => 'heading',
                'order' => 4,
                'data' => [
                    'text' => 'Nailiyyətlərimiz Rəqəmlərlə',
                    'level' => 2,
                ],
                'styles' => [
                    'fontSize' => 30,
                    'fontWeight' => 'bold',
                    'color' => '#0d1f0d',
                    'textAlign' => 'center',
                ],
            ],

            // 6. Statistics Block
            [
                'type' => 'stats',
                'order' => 5,
                'data' => [
                    'stats' => [
                        [
                            'value' => '5,000+',
                            'label' => 'Aktiv İstifadəçilər',
                            'icon' => 'Users',
                        ],
                        [
                            'value' => '150+',
                            'label' => 'Verilmiş Təlimlər',
                            'icon' => 'BookOpen',
                        ],
                        [
                            'value' => '3,500+',
                            'label' => 'Qazanılan Sertifikatlar',
                            'icon' => 'Award',
                        ],
                        [
                            'value' => '7',
                            'label' => 'Fəaliyyət İli',
                            'icon' => 'Calendar',
                        ],
                    ],
                ],
                'styles' => [
                    'value' => [
                        'fontSize' => 30,
                        'fontWeight' => 'bold',
                        'color' => '#1A6638',
                    ],
                    'label' => [
                        'fontSize' => 14,
                        'color' => '#6b7c6b',
                    ],
                ],
            ],

            // 7. Timeline Heading
            [
                'type' => 'heading',
                'order' => 6,
                'data' => [
                    'text' => 'Tarixi Yolumuz',
                    'level' => 2,
                ],
                'styles' => [
                    'fontSize' => 30,
                    'fontWeight' => 'bold',
                    'color' => '#0d1f0d',
                    'textAlign' => 'center',
                ],
            ],

            // 8. Timeline Block
            [
                'type' => 'text',
                'order' => 7,
                'data' => [
                    'title' => 'Tarixi Yolumuz',
                    'timeline' => [
                        [
                            'year' => '2018',
                            'title' => 'Agentliyin Təsisi',
                            'description' => 'Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyinin yaradılması',
                            'icon' => 'Sprout',
                        ],
                        [
                            'year' => '2019',
                            'title' => 'İlk Təlim Proqramları',
                            'description' => 'Fermerlər üçün ilk peşəkar təlim proqramlarının başladılması və 500+ fermerin təlim alması',
                            'icon' => 'BookOpen',
                        ],
                        [
                            'year' => '2020',
                            'title' => 'Rəqəmsal Transformasiya',
                            'description' => 'COVID-19 pandemiyası dövründə onlayn təlim platformasının yaradılması',
                            'icon' => 'TrendingUp',
                        ],
                        [
                            'year' => '2021',
                            'title' => 'Beynəlxalq Əməkdaşlıq',
                            'description' => 'FAO və digər beynəlxalq təşkilatlarla əməkdaşlıq müqavilələrinin imzalanması',
                            'icon' => 'Globe',
                        ],
                        [
                            'year' => '2022',
                            'title' => 'Sertifikatlaşdırma Sistemi',
                            'description' => 'Rəsmi sertifikatlaşdırma sisteminin tətbiqi və 1000+ sertifikatın verilməsi',
                            'icon' => 'Award',
                        ],
                        [
                            'year' => '2023',
                            'title' => 'Mobil Tətbiq',
                            'description' => 'Fermerlər üçün mobil tətbiqin işə salınması və sahə məsləhətçiliyi xidmətinin genişləndirilməsi',
                            'icon' => 'Phone',
                        ],
                        [
                            'year' => '2024',
                            'title' => 'İnnovasiya Mərkəzi',
                            'description' => 'Aqrar innovasiyalar və tədqiqat mərkəzinin açılması',
                            'icon' => 'Target',
                        ],
                        [
                            'year' => '2025',
                            'title' => 'Yeni Platform',
                            'description' => 'Yenilənmiş təlim platformasının istifadəyə verilməsi və 5000+ aktiv istifadəçi',
                            'icon' => 'Star',
                        ],
                    ],
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 18,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                    ],
                    'description' => [
                        'fontSize' => 14,
                        'color' => '#6b7c6b',
                        'lineHeight' => 1.6,
                    ],
                ],
            ],

            // 9. Team Heading
            [
                'type' => 'heading',
                'order' => 8,
                'data' => [
                    'text' => 'Komandamız',
                    'level' => 2,
                ],
                'styles' => [
                    'fontSize' => 30,
                    'fontWeight' => 'bold',
                    'color' => '#0d1f0d',
                    'textAlign' => 'center',
                ],
            ],

            // 10. Team Block
            [
                'type' => 'team',
                'order' => 9,
                'data' => [
                    'members' => [
                        [
                            'id' => '1',
                            'name' => 'Dr. Rəşad Məmmədov',
                            'position' => 'İdarə Rəisi',
                            'department' => 'Rəhbərlik',
                            'experience' => '15+ il',
                            'specialization' => ['Aqrar siyasət', 'Strateji planlaşdırma', 'Beynəlxalq əməkdaşlıq'],
                        ],
                        [
                            'id' => '2',
                            'name' => 'Prof. Leyla Əliyeva',
                            'position' => 'Təlim Departamenti Müdiri',
                            'department' => 'Təlim',
                            'experience' => '12+ il',
                            'specialization' => ['Bitki yetişdiriciliyi', 'Orqanik fermençilik', 'Təlim metodikası'],
                        ],
                        [
                            'id' => '3',
                            'name' => 'Dr. Mikayıl Həsənov',
                            'position' => 'Tədqiqat Şöbəsi Müdiri',
                            'department' => 'Tədqiqat',
                            'experience' => '10+ il',
                            'specialization' => ['Aqrar innovasiyalar', 'Su təsərrüfatı', 'İqlim dəyişikliyi'],
                        ],
                        [
                            'id' => '4',
                            'name' => 'Fatimə Quliyeva',
                            'position' => 'Rəqəmsal Platformalar Mütəxəssisi',
                            'department' => 'Texnologiya',
                            'experience' => '8+ il',
                            'specialization' => ['E-təlim', 'Rəqəmsal həllər', 'UI/UX dizayn'],
                        ],
                        [
                            'id' => '5',
                            'name' => 'Dr. Səidə Məmmədova',
                            'position' => 'Fermerlə Əlaqələr Koordinatoru',
                            'department' => 'Əlaqələr',
                            'experience' => '7+ il',
                            'specialization' => ['Fermer təşkilatları', 'Sahə məsləhətçiliyi', 'Kənd təsərrüfatı məhsulları'],
                        ],
                        [
                            'id' => '6',
                            'name' => 'Nigar Səfərova',
                            'position' => 'Beynəlxalq Layihələr Mütəxəssisi',
                            'department' => 'Beynəlxalq əlaqələr',
                            'experience' => '6+ il',
                            'specialization' => ['Layihə idarəetməsi', 'Beynəlxalq qrantlar', 'Monitorinq və qiymətləndirmə'],
                        ],
                    ],
                ],
                'styles' => [
                    'name' => [
                        'fontSize' => 18,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                    ],
                    'position' => [
                        'fontSize' => 14,
                        'color' => '#1A6638',
                        'fontWeight' => '500',
                    ],
                ],
            ],

            // 11. Values Heading
            [
                'type' => 'heading',
                'order' => 10,
                'data' => [
                    'text' => 'Dəyərlərimiz',
                    'level' => 2,
                ],
                'styles' => [
                    'fontSize' => 30,
                    'fontWeight' => 'bold',
                    'color' => '#0d1f0d',
                    'textAlign' => 'center',
                ],
            ],

            // 12. Values Block
            [
                'type' => 'text',
                'order' => 11,
                'data' => [
                    'values' => [
                        [
                            'title' => 'Keyfiyyət',
                            'content' => 'Bütün fəaliyyətlərimizdə ən yüksək keyfiyyət standartlarına əməl edirik və davamlı təkmilləşmə prinsipini həyata keçiririk.',
                            'icon' => 'Shield',
                            'iconColor' => 'blue',
                        ],
                        [
                            'title' => 'Fermerlərə Həssaslıq',
                            'content' => 'Fermerlərinin ehtiyaclarını anlamaq və onlara ən yaxşı həlləri təqdim etmək bizim əsas prioritetimizdir.',
                            'icon' => 'Heart',
                            'iconColor' => 'green',
                        ],
                        [
                            'title' => 'İnnovasiya',
                            'content' => 'Ən müasir texnologiyalar və metodları tətbiq edərək kənd təsərrüfatının gələcəyini formalaşdırırıq.',
                            'icon' => 'TrendingUp',
                            'iconColor' => 'purple',
                        ],
                    ],
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 18,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                    ],
                    'content' => [
                        'fontSize' => 14,
                        'color' => '#6b7c6b',
                        'lineHeight' => 1.6,
                    ],
                ],
            ],

            // 13. Contact CTA Block
            [
                'type' => 'contact',
                'order' => 12,
                'data' => [
                    'title' => 'Bizimlə əlaqə saxlayın',
                    'description' => 'Hər hansı sualınız var? Komandamızla əlaqə saxlayın və kənd təsərrüfatı sahəsində necə daha yaxşı xidmət göstərə biləcəyimizi öyrənin.',
                    'buttons' => [
                        [
                            'text' => 'Bizimlə əlaqə saxlayın',
                            'icon' => 'Phone',
                            'type' => 'primary',
                        ],
                        [
                            'text' => 'E-poçt göndərin',
                            'icon' => 'Mail',
                            'type' => 'outline',
                        ],
                    ],
                ],
                'styles' => [
                    'title' => [
                        'fontSize' => 24,
                        'fontWeight' => '600',
                        'color' => '#0d1f0d',
                        'textAlign' => 'center',
                    ],
                    'description' => [
                        'fontSize' => 16,
                        'color' => '#6b7c6b',
                        'textAlign' => 'center',
                        'lineHeight' => 1.6,
                    ],
                ],
            ],
        ];

        foreach ($blocks as $block) {
            AboutPageBlock::create([
                'type' => $block['type'],
                'order' => $block['order'],
                'data' => $block['data'],
                'styles' => $block['styles'],
                'is_active' => true,
            ]);
        }
    }
}
