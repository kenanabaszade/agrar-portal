# Backend API və Database Struktur Tələbləri

## 📋 Database Table: `about_blocks`

### Migration

```php
<?php
// database/migrations/2024_01_01_000001_create_about_blocks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_blocks', function (Blueprint $table) {
            $table->id();                              // Auto-increment ID
            $table->string('type');                    // hero, cards, stats, timeline, team, values, contact
            $table->integer('order')->default(0);      // Sıralama (0, 1, 2, ...)
            $table->json('data');                      // JSON data - block-un məzmunu
            $table->json('styles')->nullable();        // JSON styles - format və rənglər
            $table->timestamps();                      // created_at, updated_at
            
            // Indexes
            $table->index('order');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_blocks');
    }
};
```

### Database Structure Examples

#### Example 1: Hero Block
```json
{
  "id": 1,
  "type": "hero",
  "order": 0,
  "data": {
    "title": "Haqqımızda",
    "description": "Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyi",
    "image": "https://example.com/images/hero.jpg",
    "icon": "Sprout",
    "iconColor": "#10B981"
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 2: Mission & Vision Cards (2 separate blocks)
```json
{
  "id": 2,
  "type": "cards",
  "order": 1,
  "data": {
    "title": "Missiyamız",
    "description": "Keyfiyyətli təlim proqramları, praktik tövsiyələr və innovativ həllər təqdim etmək...",
    "icon": "Target",
    "iconColor": "#3B82F6"
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}

{
  "id": 3,
  "type": "cards",
  "order": 2,
  "data": {
    "title": "Vizyonumuz",
    "description": "Regionda aparıcı aqrar təlim və məsləhətçilik mərkəzi olmaq...",
    "icon": "Eye",
    "iconColor": "#8B5CF6"
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 3: Stats Block (Single block with array)
```json
{
  "id": 4,
  "type": "stats",
  "order": 3,
  "data": {
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
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 4: Timeline Block
```json
{
  "id": 5,
  "type": "timeline",
  "order": 4,
  "data": {
    "timeline": [
      {
        "year": "2018",
        "title": "Agentliyin Təsisi",
        "description": "Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında Aqrar Xidmətlər Agentliyinin yaradılması",
        "icon": "Sprout",
        "iconColor": "#10B981"
      },
      {
        "year": "2019",
        "title": "İlk Təlim Proqramları",
        "description": "Fermerlər üçün ilk peşəkar təlim proqramlarının başladılması və 500+ fermerin təlim alması",
        "icon": "BookOpen",
        "iconColor": "#3B82F6"
      },
      {
        "year": "2020",
        "title": "Rəqəmsal Transformasiya",
        "description": "COVID-19 pandemiyası dövründə onlayn təlim platformasının yaradılması",
        "icon": "TrendingUp",
        "iconColor": "#8B5CF6"
      }
    ]
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 5: Team Block
```json
{
  "id": 6,
  "type": "team",
  "order": 5,
  "data": {
    "title": "Komandamız",
    "members": [
      {
        "name": "Dr. Rəşad Məmmədov",
        "position": "İdarə Rəisi",
        "category": "Rəhbərlik",
        "experience": "15+ il təcrübə",
        "image": "https://example.com/team/rasad.jpg",
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
        "image": "https://example.com/team/leyla.jpg",
        "specializations": [
          "Bitki yetişdiriciliyi",
          "Orqanik fermençilik",
          "Təlim metodikası"
        ]
      }
    ]
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 6: Values Block
```json
{
  "id": 7,
  "type": "values",
  "order": 6,
  "data": {
    "values": [
      {
        "title": "Keyfiyyət",
        "description": "Bütün fəaliyyətlərimizdə ən yüksək keyfiyyət standartlarına əməl edirik",
        "icon": "Shield",
        "iconColor": "#3B82F6"
      },
      {
        "title": "Fermerlərə Həssaslıq",
        "description": "Fermerlərinin ehtiyaclarını anlamaq və onlara ən yaxşı həlləri təqdim etmək",
        "icon": "Heart",
        "iconColor": "#EF4444"
      },
      {
        "title": "İnnovasiya",
        "description": "Ən müasir texnologiyalar və metodları tətbiq edərək kənd təsərrüfatının gələcəyini formalaşdırırıq",
        "icon": "TrendingUp",
        "iconColor": "#8B5CF6"
      }
    ]
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

#### Example 7: Contact Block
```json
{
  "id": 8,
  "type": "contact",
  "order": 7,
  "data": {
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
  },
  "styles": {},
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

---

## 🔗 API Endpoints

### 1. GET `/api/v1/about` - Blokları gətir

**Request:**
```http
GET /api/v1/about
Accept: application/json
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "blocks": [
      // Bütün bloklar order-ə görə
    ]
  }
}
```

**Response Structure:**
```json
{
  "success": true,
  "data": {
    "blocks": [
      {
        "id": "1",
        "type": "hero",
        "order": 0,
        "data": {
          "title": "...",
          "description": "...",
          "image": "...",
          "icon": "...",
          "iconColor": "..."
        },
        "styles": {}
      }
      // ... digər bloklar
    ]
  }
}
```

### 2. POST `/api/v1/about/blocks` - Blokları yadda saxla

**Request:**
```http
POST /api/v1/about/blocks
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "blocks": [
    {
      "id": "1",
      "type": "hero",
      "order": 0,
      "data": {
        "title": "Haqqımızda",
        "description": "...",
        "image": "https://...",
        "icon": "Sprout",
        "iconColor": "#10B981"
      },
      "styles": {}
    },
    {
      "id": "2",
      "type": "cards",
      "order": 1,
      "data": {
        "title": "Missiyamız",
        "description": "...",
        "icon": "Target",
        "iconColor": "#3B82F6"
      },
      "styles": {}
    }
    // ... digər bloklar
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Bloklar uğurla yadda saxlanıldı",
  "data": {
    "blocks": [
      // Saved blocks
    ]
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "Bu əməliyyat üçün yetkiniz yoxdur"
}
```

**Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation xətası",
  "errors": {
    "blocks.0.title": ["Title field is required"],
    "blocks.1.data.stats": ["Stats array is required"]
  }
}
```

---

## 🔐 Validation Rules

### Required Fields

| Field | Validation |
|-------|-----------|
| `blocks` | `required|array|min:1` |
| `blocks.*.id` | `required|string` |
| `blocks.*.type` | `required|string|in:hero,cards,stats,timeline,team,values,contact` |
| `blocks.*.order` | `required|integer|min:0` |
| `blocks.*.data` | `required|array` |
| `blocks.*.styles` | `nullable|array` |

### Icon Colors
- Format: Hex color `#RRGGBB`
- Example: `#10B981`, `#3B82F6`, `#EF4444`
- Can be `null` for default color

### Icon Names
- String: icon name from lucide-vue-next
- Examples: `Users`, `Target`, `Award`, `Sprout`, etc.
- Can be `null` for no icon

---

## 📝 Laravel Controller Full Implementation

```php
<?php
// app/Http/Controllers/Api/V1/AboutController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AboutController extends Controller
{
    /**
     * GET /api/v1/about
     * Public endpoint - blokları gətir
     */
    public function index(Request $request)
    {
        try {
            // Cache 1 saat
            $blocks = cache()->remember('about_blocks', 3600, function () {
                return DB::table('about_blocks')
                    ->orderBy('order')
                    ->get()
                    ->map(function ($block) {
                        return [
                            'id' => (string) $block->id,
                            'type' => $block->type,
                            'order' => $block->order,
                            'data' => json_decode($block->data, true),
                            'styles' => json_decode($block->styles, true),
                        ];
                    })
                    ->toArray();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'blocks' => $blocks
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('About blocks fetch error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server xətası',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /api/v1/about/blocks
     * Admin endpoint - blokları yadda saxla
     */
    public function store(Request $request)
    {
        // Authentication kontrolü
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Admin kontrolü (opsional - role bazlı)
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Bu əməliyyat üçün yetkiniz yoxdur'
            ], 403);
        }

        // Validation rules
        $validator = Validator::make($request->all(), [
            'blocks' => 'required|array|min:1',
            'blocks.*.id' => 'required|string',
            'blocks.*.type' => 'required|string|in:hero,cards,stats,timeline,team,values,contact',
            'blocks.*.order' => 'required|integer|min:0',
            'blocks.*.data' => 'required|array',
            'blocks.*.styles' => 'nullable|array',
            
            // Hero validation
            'blocks.*.data.title' => 'nullable|string',
            'blocks.*.data.description' => 'nullable|string',
            'blocks.*.data.image' => 'nullable|url',
            'blocks.*.data.icon' => 'nullable|string',
            'blocks.*.data.iconColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            
            // Stats validation
            'blocks.*.data.stats' => 'required_if:blocks.*.type,stats|array',
            'blocks.*.data.stats.*.value' => 'required_with:blocks.*.data.stats|string',
            'blocks.*.data.stats.*.label' => 'required_with:blocks.*.data.stats|string',
            'blocks.*.data.stats.*.icon' => 'nullable|string',
            'blocks.*.data.stats.*.iconColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            
            // Timeline validation
            'blocks.*.data.timeline' => 'required_if:blocks.*.type,timeline|array',
            'blocks.*.data.timeline.*.year' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.title' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.description' => 'required_with:blocks.*.data.timeline|string',
            'blocks.*.data.timeline.*.icon' => 'nullable|string',
            'blocks.*.data.timeline.*.iconColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            
            // Team validation
            'blocks.*.data.title' => 'nullable|string',
            'blocks.*.data.members' => 'required_if:blocks.*.type,team|array',
            'blocks.*.data.members.*.name' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.position' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.category' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.experience' => 'required_with:blocks.*.data.members|string',
            'blocks.*.data.members.*.image' => 'nullable|url',
            'blocks.*.data.members.*.specializations' => 'nullable|array',
            
            // Values validation
            'blocks.*.data.values' => 'required_if:blocks.*.type,values|array',
            'blocks.*.data.values.*.title' => 'required_with:blocks.*.data.values|string',
            'blocks.*.data.values.*.description' => 'required_with:blocks.*.data.values|string',
            'blocks.*.data.values.*.icon' => 'nullable|string',
            'blocks.*.data.values.*.iconColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            
            // Contact validation
            'blocks.*.data.buttons' => 'nullable|array',
            'blocks.*.data.buttons.*.text' => 'required_with:blocks.*.data.buttons|string',
            'blocks.*.data.buttons.*.link' => 'required_with:blocks.*.data.buttons|string',
            'blocks.*.data.buttons.*.icon' => 'nullable|string',
            'blocks.*.data.buttons.*.iconColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'blocks.*.data.buttons.*.type' => 'nullable|in:primary,secondary',
            
        ], [
            'blocks.required' => 'Bloklar massivi tələb olunur',
            'blocks.*.type.in' => 'Dəstəklənməyən blok tipi',
            'blocks.*.data.iconColor.regex' => 'İkon rəngi hex formatında olmalıdır (#RRGGBB)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation xətası',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Köhnə blokları sil (replace strategy)
            DB::table('about_blocks')->truncate();

            // Yeni blokları yadda saxla
            foreach ($request->blocks as $blockData) {
                DB::table('about_blocks')->insert([
                    'type' => $blockData['type'],
                    'order' => $blockData['order'],
                    'data' => json_encode($blockData['data']),
                    'styles' => json_encode($blockData['styles'] ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // Cache-i təmizlə
            cache()->forget('about_blocks');

            // Blokları geri qaytar
            $blocks = DB::table('about_blocks')
                ->orderBy('order')
                ->get()
                ->map(function ($block) {
                    return [
                        'id' => (string) $block->id,
                        'type' => $block->type,
                        'order' => $block->order,
                        'data' => json_decode($block->data, true),
                        'styles' => json_decode($block->styles, true),
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Bloklar uğurla yadda saxlanıldı',
                'data' => [
                    'blocks' => $blocks
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('About blocks save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server xətası',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
```

---

## 🛣️ Routes

```php
// routes/api.php

Route::prefix('v1')->group(function () {
    // Public endpoint
    Route::get('/about', [AboutController::class, 'index']);
    
    // Admin endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/about/blocks', [AboutController::class, 'store']);
    });
});
```

---

## 🎯 Key Points

1. **Single Table Design**: Bütün bloklar bir `about_blocks` table-də saxlanır
2. **JSON Flexibility**: `data` və `styles` JSON format-dadır, elastikdir
3. **Order Field**: Bloklar sıralanmaq üçün `order` field istifadə olunur
4. **Replace Strategy**: POST zamanı bütün köhnə bloklar silinir və yeniləri yazılır
5. **Cache**: GET endpoint cache istifadə edir (1 saat)
6. **Authentication**: Admin endpoint-lər üçün auth tələb olunur
7. **Validation**: Her block type üçün spesifik validation qaydaları var

---

## 📊 Frontend-dən Göndərilən Data Format

```javascript
// AboutEditor.vue-dən POST request

const blocks = [
  // 1. Hero
  {
    id: '1',
    type: 'hero',
    order: 0,
    data: {
      title: 'Haqqımızda',
      description: '...',
      image: 'https://...',
      icon: 'Sprout',
      iconColor: '#10B981'
    },
    styles: {}
  },
  
  // 2. Mission
  {
    id: '2',
    type: 'cards',
    order: 1,
    data: {
      title: 'Missiyamız',
      description: '...',
      icon: 'Target',
      iconColor: '#3B82F6'
    },
    styles: {}
  },
  
  // 3. Vision
  {
    id: '3',
    type: 'cards',
    order: 2,
    data: {
      title: 'Vizyonumuz',
      description: '...',
      icon: 'Eye',
      iconColor: '#8B5CF6'
    },
    styles: {}
  },
  
  // 4. Stats
  {
    id: '4',
    type: 'stats',
    order: 3,
    data: {
      stats: [
        { value: '5,000+', label: 'Aktiv İstifadəçilər', icon: 'Users', iconColor: '#3B82F6' },
        { value: '150+', label: 'Verilmiş Təlimlər', icon: 'BookOpen', iconColor: '#10B981' },
        { value: '3,500+', label: 'Qazanılan Sertifikatlar', icon: 'Award', iconColor: '#F59E0B' },
        { value: '7', label: 'Fəaliyyət İli', icon: 'Calendar', iconColor: '#8B5CF6' }
      ]
    },
    styles: {}
  },
  
  // 5. Timeline 1
  {
    id: '5',
    type: 'timeline',
    order: 4,
    data: {
      timeline: [
        { year: '2018', title: 'Agentliyin Təsisi', description: '...', icon: 'Sprout', iconColor: '#10B981' },
        { year: '2019', title: 'İlk Təlim Proqramları', description: '...', icon: 'BookOpen', iconColor: '#3B82F6' }
      ]
    },
    styles: {}
  },
  
  // 6. Team
  {
    id: '6',
    type: 'team',
    order: 5,
    data: {
      title: 'Komandamız',
      members: [
        {
          name: 'Dr. Rəşad Məmmədov',
          position: 'İdarə Rəisi',
          category: 'Rəhbərlik',
          experience: '15+ il təcrübə',
          image: 'https://...',
          specializations: ['Aqrar siyasət', 'Strateji planlaşdırma']
        }
      ]
    },
    styles: {}
  },
  
  // 7. Values
  {
    id: '7',
    type: 'values',
    order: 6,
    data: {
      values: [
        { title: 'Keyfiyyət', description: '...', icon: 'Shield', iconColor: '#3B82F6' },
        { title: 'Fermerlərə Həssaslıq', description: '...', icon: 'Heart', iconColor: '#EF4444' }
      ]
    },
    styles: {}
  },
  
  // 8. Contact
  {
    id: '8',
    type: 'contact',
    order: 7,
    data: {
      title: 'Bizimlə əlaqə saxlayın',
      description: '...',
      buttons: [
        { text: 'Bizimlə əlaqə saxlayın', link: 'tel:+994123456789', icon: 'Phone', iconColor: '#FFFFFF', type: 'primary' },
        { text: 'E-poçt göndərin', link: 'mailto:info@agrar.gov.az', icon: 'Mail', iconColor: '#059669', type: 'secondary' }
      ]
    },
    styles: {}
  }
]

// API call
await AboutService.saveBlocks({ blocks })
```

---

## ✅ Summary

- **Database**: 1 table `about_blocks` - JSON flexible structure
- **API**: 2 endpoints (GET public, POST admin)
- **Strategy**: Replace all blocks on each save
- **Cache**: 1 hour cache for GET requests
- **Security**: Admin-only for POST, public for GET
- **Validation**: Comprehensive validation for all block types

