<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, convert existing string category values to JSON format
        DB::table('trainings')->whereNotNull('category')->get()->each(function ($training) {
            $categoryValue = $training->category;
            
            // If it's already JSON, skip
            if (is_string($categoryValue) && (str_starts_with($categoryValue, '{') || str_starts_with($categoryValue, '['))) {
                try {
                    json_decode($categoryValue, true);
                    return; // Already JSON
                } catch (\Exception $e) {
                    // Not valid JSON, continue conversion
                }
            }
            
            // Convert string to JSON format
            if (!empty($categoryValue)) {
                $jsonCategory = json_encode(['az' => $categoryValue], JSON_UNESCAPED_UNICODE);
                DB::table('trainings')
                    ->where('id', $training->id)
                    ->update(['category' => $jsonCategory]);
            }
        });
        
        // Change column type from string to JSON
        Schema::table('trainings', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['category']);
        });
        
        Schema::table('trainings', function (Blueprint $table) {
            // Change column type to JSON
            $table->json('category')->nullable()->change();
        });
        
        // Re-add index (MySQL doesn't support JSON indexes directly, but we can use generated column)
        // For now, we'll skip the index on JSON column as it requires special handling
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON back to string (use az value or first available)
        DB::table('trainings')->whereNotNull('category')->get()->each(function ($training) {
            $categoryValue = $training->category;
            
            // If it's JSON, extract az value
            if (is_string($categoryValue) && (str_starts_with($categoryValue, '{') || str_starts_with($categoryValue, '['))) {
                try {
                    $decoded = json_decode($categoryValue, true);
                    if (is_array($decoded)) {
                        $stringCategory = $decoded['az'] ?? $decoded['en'] ?? $decoded['ru'] ?? reset($decoded);
                        DB::table('trainings')
                            ->where('id', $training->id)
                            ->update(['category' => $stringCategory]);
                    }
                } catch (\Exception $e) {
                    // Keep as is if conversion fails
                }
            }
        });
        
        // Change column type back to string
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            // Re-add index
            $table->index(['category']);
        });
    }
};
