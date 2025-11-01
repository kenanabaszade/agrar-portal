# Backend Database Seed Data və Test Məlumatları

## 📦 Database Seeder

Bu faylda About səhifəsi üçün seed data-larını ətraflı izah edirik.

---

## 🗄️ Database Seeder Class

```php
<?php
// database/seeders/AboutBlocksSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AboutBlocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Köhnə data-ları təmizlə (opsional)
        DB::table('about_blocks')->truncate();

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
            DB::table('about_blocks')->insert([
                'type' => $block['type'],
                'order' => $block['order'],
                'data' => json_encode($block['data']),
                'styles' => json_encode($block['styles']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('About blocks seeded successfully!');
    }
}
```

---

## 🚀 Seeder-i Run Etmək

### 1. DatabaseSeeder-də əlavə et

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AboutBlocksSeeder::class,
        ]);
    }
}
```

### 2. Seeder-i run et

```bash
# Xüsusi seeder
php artisan db:seed --class=AboutBlocksSeeder

# Və ya bütün seeder-lər
php artisan db:seed

# Və ya fresh migration ilə birlikdə
php artisan migrate:fresh --seed
```

---

## 📝 SQL Dump (Direct Insert)

Əgər seeder istifadə etmək istəmirsən, sadəcə SQL əlavə edə bilərsən:

```sql
-- Köhnə data-ları təmizlə
TRUNCATE TABLE about_blocks;

-- Hero Section
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('hero', 0, 
'{
  "title": "Haqqımızda",
  "description": "Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyi - kənd təsərrüfatı sahəsində təlim və məsləhətçilik xidmətlərinin aparıcı təşkilatı",
  "image": "/storage/images/about/hero-bg.jpg",
  "icon": "Sprout",
  "iconColor": "#10B981"
}', 
'{}',
NOW(), NOW()
);

-- Mission Card
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('cards', 1,
'{
  "title": "Missiyamız",
  "description": "Keyfiyyətli təlim proqramları, praktik tövsiyələr və innovativ həllər təqdim edərək Azərbaycan kənd təsərrüfatının modernləşdirilməsinə və fermerlərin bilik səviyyəsinin artırılmasına töhfə vermək. Ölkəmizdə qida təhlükəsizliyinin təmin edilməsinə və aqrar məhsuldarlığın artırılmasına yardım göstərmək.",
  "icon": "Target",
  "iconColor": "#3B82F6"
}',
'{}',
NOW(), NOW()
);

-- Vision Card
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('cards', 2,
'{
  "title": "Vizyonumuz",
  "description": "Regionda aparıcı aqrar təlim və məsləhətçilik mərkəzi olmaq. Müasir texnologiyalar və ən yaxşı praktikalar əsasında global standartlara cavab verən xidmət göstərmək. Fermerlər, aqronomlar və aqrar şirkətlərin inkişafına öz töhfəmizi vermək və davamlı, ekoloji təmiz kənd təsərrüfatının inkişafına rəhbərlik etmək.",
  "icon": "Eye",
  "iconColor": "#8B5CF6"
}',
'{}',
NOW(), NOW()
);

-- Stats Block
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('stats', 3,
'{
  "stats": [
    {
      "value": "5,000+",
      "label": "Aktiv İstifadəçilər",
      "icon": "Users",
      "iconColor": "#3B82F6"
    },
    {
      "value": "150+",
      "label": "Verilmiş Təlimlər",
      "icon": "BookOpen",
      "iconColor": "#10B981"
    },
    {
      "value": "3,500+",
      "label": "Qazanılan Sertifikatlar",
      "icon": "Award",
      "iconColor": "#F59E0B"
    },
    {
      "value": "7",
      "label": "Fəaliyyət İli",
      "icon": "Calendar",
      "iconColor": "#8B5CF6"
    }
  ]
}',
'{}',
NOW(), NOW()
);

-- Timeline Block
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('timeline', 4,
'{
  "timeline": [
    {
      "year": "2018",
      "title": "Agentliyin Təsisi",
      "description": "Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyinin yaradılması və ilk əsasları qoyulması.",
      "icon": "Sprout",
      "iconColor": "#10B981"
    },
    {
      "year": "2019",
      "title": "İlk Təlim Proqramları",
      "description": "Fermerlər üçün ilk peşəkar təlim proqramlarının başladılması və 500+ fermerin təlim alması.",
      "icon": "BookOpen",
      "iconColor": "#3B82F6"
    },
    {
      "year": "2020",
      "title": "Rəqəmsal Transformasiya",
      "description": "COVID-19 pandemiyası dövründə onlayn təlim platformasının yaradılması və rəqəmsal məsləhətçilik xidmətlərinin tətbiqi.",
      "icon": "TrendingUp",
      "iconColor": "#8B5CF6"
    },
    {
      "year": "2021",
      "title": "Beynəlxalq Əməkdaşlıq",
      "description": "FAO və digər beynəlxalq təşkilatlarla əməkdaşlıq müqavilələrinin imzalanması və beynəlxalq layihələrin həyata keçirilməsi.",
      "icon": "Globe",
      "iconColor": "#059669"
    },
    {
      "year": "2022",
      "title": "Sertifikatlaşdırma Sistemi",
      "description": "Rəsmi sertifikatlaşdırma sisteminin tətbiqi və 1000+ sertifikatın verilməsi.",
      "icon": "Award",
      "iconColor": "#F59E0B"
    },
    {
      "year": "2023",
      "title": "Mobil Tətbiq",
      "description": "Fermerlər üçün mobil tətbiqin işə salınması və sahə məsləhətçiliyi xidmətinin genişləndirilməsi.",
      "icon": "Phone",
      "iconColor": "#3B82F6"
    },
    {
      "year": "2024",
      "title": "İnnovasiya Mərkəzi",
      "description": "Aqrar innovasiyalar və tədqiqat mərkəzinin açılması və yeni texnologiyaların tədrisi.",
      "icon": "TrendingUp",
      "iconColor": "#8B5CF6"
    },
    {
      "year": "2025",
      "title": "Yeni Platform",
      "description": "Yenilənmiş təlim platformasının istifadəyə verilməsi və 5000+ aktiv istifadəçiyə çatma.",
      "icon": "Star",
      "iconColor": "#F59E0B"
    }
  ]
}',
'{}',
NOW(), NOW()
);

-- Team Block
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('team', 5,
'{
  "title": "Komandamız",
  "members": [
    {
      "name": "Dr. Rəşad Məmmədov",
      "position": "İdarə Rəisi",
      "category": "Rəhbərlik",
      "experience": "15+ il təcrübə",
      "image": "/storage/images/team/rasad-memmedov.jpg",
      "specializations": [
        "Aqrar siyasət",
        "Strateji planlaşdırma",
        "Beynəlxalq əməkdaşlıq"
      ]
    },
    {
      "name": "Prof. Leyla Əliyeva",
      "position": "Təlim Departamenti Müdiri",
      "category": "Təlim",
      "experience": "12+ il təcrübə",
      "image": "/storage/images/team/leyla-eliyeva.jpg",
      "specializations": [
        "Bitki yetişdiriciliyi",
        "Orqanik fermençilik",
        "Təlim metodikası"
      ]
    },
    {
      "name": "Dr. Mikayıl Həsənov",
      "position": "Tədqiqat Şöbəsi Müdiri",
      "category": "Tədqiqat",
      "experience": "10+ il təcrübə",
      "image": "/storage/images/team/mikayil-hasanov.jpg",
      "specializations": [
        "Aqrar tədqiqatlar",
        "İnnovasiya texnologiyaları",
        "Ətraf mühitin qorunması"
      ]
    },
    {
      "name": "Fatimə Quliyeva",
      "position": "Rəqəmsal Platformalar Mütəxəssisi",
      "category": "Texnologiya",
      "experience": "8+ il təcrübə",
      "image": "/storage/images/team/fatime-quliyeva.jpg",
      "specializations": [
        "Rəqəmsal platformalar",
        "Data analizi",
        "Texnologiya təkmilləşdirmə"
      ]
    }
  ]
}',
'{}',
NOW(), NOW()
);

-- Values Block
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('values', 6,
'{
  "values": [
    {
      "title": "Keyfiyyət",
      "description": "Bütün fəaliyyətlərimizdə ən yüksək keyfiyyət standartlarına əməl edirik və davamlı təkmilləşmə prinsipini həyata keçiririk.",
      "icon": "Shield",
      "iconColor": "#3B82F6"
    },
    {
      "title": "Fermerlərə Həssaslıq",
      "description": "Fermerlərinin ehtiyaclarını anlamaq və onlara ən yaxşı həlləri təqdim etmək bizim əsas prioritetimizdir.",
      "icon": "Heart",
      "iconColor": "#EF4444"
    },
    {
      "title": "İnnovasiya",
      "description": "Ən müasir texnologiyalar və metodları tətbiq edərək kənd təsərrüfatının gələcəyini formalaşdırırıq.",
      "icon": "TrendingUp",
      "iconColor": "#8B5CF6"
    }
  ]
}',
'{}',
NOW(), NOW()
);

-- Contact Block
INSERT INTO about_blocks (type, `order`, data, styles, created_at, updated_at) VALUES
('contact', 7,
'{
  "title": "Bizimlə əlaqə saxlayın",
  "description": "Hər hansı sualınız var? Komandamızla əlaqə saxlayın və kənd təsərrüfatı sahəsində necə daha yaxşı xidmət göstərə biləcəyimizi öyrənin.",
  "buttons": [
    {
      "text": "Bizimlə əlaqə saxlayın",
      "link": "tel:+994123456789",
      "icon": "Phone",
      "iconColor": "#FFFFFF",
      "type": "primary"
    },
    {
      "text": "E-poçt göndərin",
      "link": "mailto:info@agrar.gov.az",
      "icon": "Mail",
      "iconColor": "#059669",
      "type": "secondary"
    }
  ]
}',
'{}',
NOW(), NOW()
);
```

---

## 🎨 Şəkil Path-ləri

Şəkillər üçün path-lərə diqqət et:

```
public/storage/images/
├── about/
│   └── hero-bg.jpg
└── team/
    ├── rasad-memmedov.jpg
    ├── leyla-eliyeva.jpg
    ├── mikayil-hasanov.jpg
    └── fatime-quliyeva.jpg
```

Ya storage link yarat:
```bash
php artisan storage:link
```

---

## 🧪 Test Data-lar

### Minimal Test Data (Development)

```php
// Minimal test üçün - sadəcə 3 blok
$minimalBlocks = [
    [
        'type' => 'hero',
        'order' => 0,
        'data' => [
            'title' => 'Haqqımızda Test',
            'description' => 'Test məlumat',
            'icon' => 'Sprout',
            'iconColor' => '#10B981'
        ],
        'styles' => []
    ],
    [
        'type' => 'stats',
        'order' => 1,
        'data' => [
            'stats' => [
                [
                    'value' => '100+',
                    'label' => 'Test Users',
                    'icon' => 'Users',
                    'iconColor' => '#3B82F6'
                ]
            ]
        ],
        'styles' => []
    ],
    [
        'type' => 'contact',
        'order' => 2,
        'data' => [
            'title' => 'Əlaqə Test',
            'description' => 'Test məlumat',
            'buttons' => [
                [
                    'text' => 'Test Button',
                    'link' => 'tel:+994123456789',
                    'icon' => 'Phone',
                    'iconColor' => '#FFFFFF',
                    'type' => 'primary'
                ]
            ]
        ],
        'styles' => []
    ]
];
```

---

## 🔄 Update vəziyyəti

Əgər mövcud data-ları update etmək istəyirsən:

```php
// Seeder-də əlavə et
public function run(): void
{
    // Update strategy: mövcud data-ları sil və yeniləri əlavə et
    DB::table('about_blocks')->truncate();
    
    // ... insert blocks
    
    // Və ya update specific blocks
    DB::table('about_blocks')
        ->where('type', 'hero')
        ->update([
            'data' => json_encode(['title' => 'New Title']),
            'updated_at' => now()
        ]);
}
```

---

## 📊 İconlar siyahısı

Lucide Vue Next ikonların istifadəsi:

- **Hero**: `Sprout`, `Eye`, `Home`
- **Mission/Vision**: `Target`, `Eye`, `Globe`, `Star`
- **Stats**: `Users`, `BookOpen`, `Award`, `Calendar`, `TrendingUp`
- **Timeline**: `Sprout`, `BookOpen`, `TrendingUp`, `Globe`, `Award`, `Phone`, `Star`
- **Team**: `Users`, `User`
- **Values**: `Shield`, `Heart`, `TrendingUp`, `Target`
- **Contact**: `Phone`, `Mail`, `MessageSquare`

---

## ✅ Checklist

Seeder yaratmazdan əvvəl:

- [ ] Migration run olunub
- [ ] `about_blocks` table mövcuddur
- [ ] Storage folder-ları yaradılıb (images/about, images/team)
- [ ] Storage link yaradılıb (`php artisan storage:link`)
- [ ] Icon adları düzgündür (lucide-vue-next)
- [ ] JSON format-ları valid-dir
- [ ] Order field-ları düzgün sıralanıb (0, 1, 2, ...)

---

## 🚨 Xəta Əlavə Etməsi

Əgər seeder xəta verərsə:

1. **JSON Format**: JSON string valid olduğunu yoxla
2. **Icon Names**: Icon adları lucide-vue-next-dən mövcuddur
3. **Foreign Keys**: Əgər relation varsa, əvvəl parent data-lar
4. **Timestamps**: `NOW()` istifadə et və ya `now()` PHP

---

## 💡 Əlavə Qeydlər

1. **Production**: Production-də seeder-i diqqətli run et
2. **Backup**: Əvvəlcə backup et
3. **Testing**: Test environment-də əvvəlcə test et
4. **Rollback**: Migration rollback imkanı olsun

