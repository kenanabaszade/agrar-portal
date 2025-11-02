<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        \DB::table('service_packages')->get()->each(function ($package) {
            $updates = [];
            if (!empty($package->name)) {
                $updates['name_translations'] = json_encode(['az' => $package->name], JSON_UNESCAPED_UNICODE);
            }
            if (!empty($package->description)) {
                $updates['description_translations'] = json_encode(['az' => $package->description], JSON_UNESCAPED_UNICODE);
            }
            
            // Handle features array - translate text fields inside
            if (!empty($package->features) && is_string($package->features)) {
                $features = json_decode($package->features, true);
                if (is_array($features)) {
                    $translatedFeatures = [];
                    foreach ($features as $feature) {
                        if (is_array($feature) && isset($feature['text'])) {
                            $translatedFeatures[] = array_merge($feature, [
                                'text' => ['az' => $feature['text']]
                            ]);
                        } else {
                            $translatedFeatures[] = $feature;
                        }
                    }
                    $updates['features'] = json_encode($translatedFeatures, JSON_UNESCAPED_UNICODE);
                }
            }
            
            if (!empty($updates)) {
                \DB::table('service_packages')->where('id', $package->id)->update($updates);
            }
        });

        \DB::statement('ALTER TABLE service_packages DROP COLUMN name, DROP COLUMN description');
        \DB::statement('ALTER TABLE service_packages RENAME COLUMN name_translations TO name');
        \DB::statement('ALTER TABLE service_packages RENAME COLUMN description_translations TO description');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE service_packages RENAME COLUMN name TO name_translations');
        \DB::statement('ALTER TABLE service_packages RENAME COLUMN description TO description_translations');

        Schema::table('service_packages', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->text('description')->nullable()->after('name');
        });

        \DB::table('service_packages')->get()->each(function ($package) {
            $updates = [];
            if (!empty($package->name_translations)) {
                $data = json_decode($package->name_translations, true);
                if (is_array($data)) $updates['name'] = $data['az'] ?? reset($data) ?? null;
            }
            if (!empty($package->description_translations)) {
                $data = json_decode($package->description_translations, true);
                if (is_array($data)) $updates['description'] = $data['az'] ?? reset($data) ?? null;
            }
            
            // Revert features translations
            if (!empty($package->features) && is_string($package->features)) {
                $features = json_decode($package->features, true);
                if (is_array($features)) {
                    $revertedFeatures = [];
                    foreach ($features as $feature) {
                        if (is_array($feature) && isset($feature['text']) && is_array($feature['text'])) {
                            $revertedFeatures[] = array_merge($feature, [
                                'text' => $feature['text']['az'] ?? reset($feature['text']) ?? ''
                            ]);
                        } else {
                            $revertedFeatures[] = $feature;
                        }
                    }
                    $updates['features'] = json_encode($revertedFeatures, JSON_UNESCAPED_UNICODE);
                }
            }
            
            if (!empty($updates)) {
                \DB::table('service_packages')->where('id', $package->id)->update($updates);
            }
        });

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });
    }
};
