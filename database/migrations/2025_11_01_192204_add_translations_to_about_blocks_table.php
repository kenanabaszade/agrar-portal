<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // About blocks uses JSON data field which contains text fields
        // The translation will be handled at model level
        // This migration ensures data field can store translations
        // No schema changes needed as data is already JSON
        // We just need to ensure backward compatibility
        
        // Update existing data to include translation structure
        \DB::table('about_blocks')->get()->each(function ($block) {
            if (!empty($block->data)) {
                $data = json_decode($block->data, true);
                if (is_array($data)) {
                    // Convert text fields in data to translation format
                    $translatedData = $this->convertDataToTranslations($data);
                    if ($translatedData !== $data) {
                        \DB::table('about_blocks')
                            ->where('id', $block->id)
                            ->update(['data' => json_encode($translatedData, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Revert translations back to simple strings
        \DB::table('about_blocks')->get()->each(function ($block) {
            if (!empty($block->data)) {
                $data = json_decode($block->data, true);
                if (is_array($data)) {
                    $revertedData = $this->revertDataFromTranslations($data);
                    if ($revertedData !== $data) {
                        \DB::table('about_blocks')
                            ->where('id', $block->id)
                            ->update(['data' => json_encode($revertedData, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            }
        });
    }

    /**
     * Convert text fields in data array to translation format
     */
    private function convertDataToTranslations(array $data): array
    {
        $textFields = ['title', 'subtitle', 'description', 'text', 'content', 'name', 'label', 'heading'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $textFields) && is_string($value) && !empty($value)) {
                $data[$key] = ['az' => $value];
            } elseif (is_array($value)) {
                $data[$key] = $this->convertDataToTranslations($value);
            }
        }
        
        return $data;
    }

    /**
     * Revert translation format back to simple strings
     */
    private function revertDataFromTranslations(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value['az'])) {
                // It's a translation object, extract 'az' value
                $data[$key] = $value['az'];
            } elseif (is_array($value)) {
                $data[$key] = $this->revertDataFromTranslations($value);
            }
        }
        
        return $data;
    }
};
