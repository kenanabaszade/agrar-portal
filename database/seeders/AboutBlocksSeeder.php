<?php

namespace Database\Seeders;

use App\Models\AboutBlock;
use Illuminate\Database\Seeder;

class AboutBlocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Köhnə data-ları təmizlə
        AboutBlock::truncate();

        // Blokları yarat
        $blocks = [
            // ============================================
            // 1. HERO SECTION
            // ============================================
            [
                'type' => 'hero',
                'order' => 0,
                'data' => [
                    'title' => 'Haqqımızda',
                    'description' => 'Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyi - kənd təsərrüfatı sahəsində təlim və məsləhətçilik xidmətlərinin aparıcı təşkilatı',
                    'image' => '/storage/images/about/hero-bg.jpg',
                    'icon' => 'Sprout',
                    'iconColor' => '#10B981'
                ],
                'styles' => []
            ],

            // ============================================
            // 2. MISSION & VISION CARDS
            // ============================================
            [
                'type' => 'cards',
                'order' => 1,
                'data' => [
                    'title' => 'Missiyamız',
                    'description' => 'Keyfiyyətli təlim proqramları, praktik tövsiyələr və innovativ həllər təqdim edərək Azərbaycan kənd təsərrüfatının modernləşdirilməsinə və fermerlərin bilik səviyyəsinin artırılmasına töhfə vermək. Ölkəmizdə qida təhlükəsizliyinin təmin edilməsinə və aqrar məhsuldarlığın artırılmasına yardım göstərmək.',
                    'icon' => 'Target',
                    'iconColor' => '#3B82F6'
                ],
                'styles' => []
            ],

            [
                'type' => 'cards',
                'order' => 2,
                'data' => [
                    'title' => 'Vizyonumuz',
                    'description' => 'Regionda aparıcı aqrar təlim və məsləhətçilik mərkəzi olmaq. Müasir texnologiyalar və ən yaxşı praktikalar əsasında global standartlara cavab verən xidmət göstərmək. Fermerlər, aqronomlar və aqrar şirkətlərin inkişafına öz töhfəmizi vermək və davamlı, ekoloji təmiz kənd təsərrüfatının inkişafına rəhbərlik etmək.',
                    'icon' => 'Eye',
                    'iconColor' => '#8B5CF6'
                ],
                'styles' => []
            ],

            // ============================================
            // 3. STATS/ACHIEVEMENTS SECTION
            // ============================================
            [
                'type' => 'stats',
                'order' => 3,
                'data' => [
                    'stats' => [
                        [
                            'value' => '5,000+',
                            'label' => 'Aktiv İstifadəçilər',
                            'icon' => 'Users',
                            'iconColor' => '#3B82F6'
                        ],
                        [
                            'value' => '150+',
                            'label' => 'Verilmiş Təlimlər',
                            'icon' => 'BookOpen',
                            'iconColor' => '#10B981'
                        ],
                        [
                            'value' => '3,500+',
                            'label' => 'Qazanılan Sertifikatlar',
                            'icon' => 'Award',
                            'iconColor' => '#F59E0B'
                        ],
                        [
                            'value' => '7',
                            'label' => 'Fəaliyyət İli',
                            'icon' => 'Calendar',
                            'iconColor' => '#8B5CF6'
                        ]
                    ]
                ],
                'styles' => []
            ],

            // ============================================
            // 4. TIMELINE SECTION - Tarixi Yolumuz
            // ============================================
            [
                'type' => 'timeline',
                'order' => 4,
                'data' => [
                    'timeline' => [
                        [
                            'year' => '2018',
                            'title' => 'Agentliyin Təsisi',
                            'description' => 'Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyinin yaradılması və ilk əsasları qoyulması.',
                            'icon' => 'Sprout',
                            'iconColor' => '#10B981'
                        ],
                        [
                            'year' => '2019',
                            'title' => 'İlk Təlim Proqramları',
                            'description' => 'Fermerlər üçün ilk peşəkar təlim proqramlarının başladılması və 500+ fermerin təlim alması.',
                            'icon' => 'BookOpen',
                            'iconColor' => '#3B82F6'
                        ],
                        [
                            'year' => '2020',
                            'title' => 'Rəqəmsal Transformasiya',
                            'description' => 'COVID-19 pandemiyası dövründə onlayn təlim platformasının yaradılması və rəqəmsal məsləhətçilik xidmətlərinin tətbiqi.',
                            'icon' => 'TrendingUp',
                            'iconColor' => '#8B5CF6'
                        ],
                        [
                            'year' => '2021',
                            'title' => 'Beynəlxalq Əməkdaşlıq',
                            'description' => 'FAO və digər beynəlxalq təşkilatlarla əməkdaşlıq müqavilələrinin imzalanması və beynəlxalq layihələrin həyata keçirilməsi.',
                            'icon' => 'Globe',
                            'iconColor' => '#059669'
                        ],
                        [
                            'year' => '2022',
                            'title' => 'Sertifikatlaşdırma Sistemi',
                            'description' => 'Rəsmi sertifikatlaşdırma sisteminin tətbiqi və 1000+ sertifikatın verilməsi.',
                            'icon' => 'Award',
                            'iconColor' => '#F59E0B'
                        ],
                        [
                            'year' => '2023',
                            'title' => 'Mobil Tətbiq',
                            'description' => 'Fermerlər üçün mobil tətbiqin işə salınması və sahə məsləhətçiliyi xidmətinin genişləndirilməsi.',
                            'icon' => 'Phone',
                            'iconColor' => '#3B82F6'
                        ],
                        [
                            'year' => '2024',
                            'title' => 'İnnovasiya Mərkəzi',
                            'description' => 'Aqrar innovasiyalar və tədqiqat mərkəzinin açılması və yeni texnologiyaların tədrisi.',
                            'icon' => 'TrendingUp',
                            'iconColor' => '#8B5CF6'
                        ],
                        [
                            'year' => '2025',
                            'title' => 'Yeni Platform',
                            'description' => 'Yenilənmiş təlim platformasının istifadəyə verilməsi və 5000+ aktiv istifadəçiyə çatma.',
                            'icon' => 'Star',
                            'iconColor' => '#F59E0B'
                        ]
                    ]
                ],
                'styles' => []
            ],

            // ============================================
            // 5. TEAM SECTION
            // ============================================
            [
                'type' => 'team',
                'order' => 5,
                'data' => [
                    'title' => 'Komandamız',
                    'members' => [
                        [
                            'name' => 'Dr. Rəşad Məmmədov',
                            'position' => 'İdarə Rəisi',
                            'category' => 'Rəhbərlik',
                            'experience' => '15+ il təcrübə',
                            'image' => '/storage/images/team/rasad-memmedov.jpg',
                            'specializations' => [
                                'Aqrar siyasət',
                                'Strateji planlaşdırma',
                                'Beynəlxalq əməkdaşlıq'
                            ]
                        ],
                        [
                            'name' => 'Prof. Leyla Əliyeva',
                            'position' => 'Təlim Departamenti Müdiri',
                            'category' => 'Təlim',
                            'experience' => '12+ il təcrübə',
                            'image' => '/storage/images/team/leyla-eliyeva.jpg',
                            'specializations' => [
                                'Bitki yetişdiriciliyi',
                                'Orqanik fermençilik',
                                'Təlim metodikası'
                            ]
                        ],
                        [
                            'name' => 'Dr. Mikayıl Həsənov',
                            'position' => 'Tədqiqat Şöbəsi Müdiri',
                            'category' => 'Tədqiqat',
                            'experience' => '10+ il təcrübə',
                            'image' => '/storage/images/team/mikayil-hasanov.jpg',
                            'specializations' => [
                                'Aqrar tədqiqatlar',
                                'İnnovasiya texnologiyaları',
                                'Ətraf mühitin qorunması'
                            ]
                        ],
                        [
                            'name' => 'Fatimə Quliyeva',
                            'position' => 'Rəqəmsal Platformalar Mütəxəssisi',
                            'category' => 'Texnologiya',
                            'experience' => '8+ il təcrübə',
                            'image' => '/storage/images/team/fatime-quliyeva.jpg',
                            'specializations' => [
                                'Rəqəmsal platformalar',
                                'Data analizi',
                                'Texnologiya təkmilləşdirmə'
                            ]
                        ]
                    ]
                ],
                'styles' => []
            ],

            // ============================================
            // 6. VALUES SECTION
            // ============================================
            [
                'type' => 'values',
                'order' => 6,
                'data' => [
                    'values' => [
                        [
                            'title' => 'Keyfiyyət',
                            'description' => 'Bütün fəaliyyətlərimizdə ən yüksək keyfiyyət standartlarına əməl edirik və davamlı təkmilləşmə prinsipini həyata keçiririk.',
                            'icon' => 'Shield',
                            'iconColor' => '#3B82F6'
                        ],
                        [
                            'title' => 'Fermerlərə Həssaslıq',
                            'description' => 'Fermerlərinin ehtiyaclarını anlamaq və onlara ən yaxşı həlləri təqdim etmək bizim əsas prioritetimizdir.',
                            'icon' => 'Heart',
                            'iconColor' => '#EF4444'
                        ],
                        [
                            'title' => 'İnnovasiya',
                            'description' => 'Ən müasir texnologiyalar və metodları tətbiq edərək kənd təsərrüfatının gələcəyini formalaşdırırıq.',
                            'icon' => 'TrendingUp',
                            'iconColor' => '#8B5CF6'
                        ]
                    ]
                ],
                'styles' => []
            ],

            // ============================================
            // 7. CONTACT SECTION
            // ============================================
            [
                'type' => 'contact',
                'order' => 7,
                'data' => [
                    'title' => 'Bizimlə əlaqə saxlayın',
                    'description' => 'Hər hansı sualınız var? Komandamızla əlaqə saxlayın və kənd təsərrüfatı sahəsində necə daha yaxşı xidmət göstərə biləcəyimizi öyrənin.',
                    'buttons' => [
                        [
                            'text' => 'Bizimlə əlaqə saxlayın',
                            'link' => 'tel:+994123456789',
                            'icon' => 'Phone',
                            'iconColor' => '#FFFFFF',
                            'type' => 'primary'
                        ],
                        [
                            'text' => 'E-poçt göndərin',
                            'link' => 'mailto:info@agrar.gov.az',
                            'icon' => 'Mail',
                            'iconColor' => '#059669',
                            'type' => 'secondary'
                        ]
                    ]
                ],
                'styles' => []
            ]
        ];

        // Database-ə insert et
        foreach ($blocks as $block) {
            AboutBlock::create([
                'type' => $block['type'],
                'order' => $block['order'],
                'data' => $block['data'],
                'styles' => $block['styles'],
            ]);
        }

        $this->command->info('About blocks seeded successfully!');
    }
}
