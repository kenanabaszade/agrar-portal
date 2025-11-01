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
        // Fix image_path
        DB::statement("UPDATE educational_contents SET image_path = REPLACE(image_path, 'public/education', 'education') WHERE image_path LIKE 'public/education%'");
        
        // Fix media_files JSON paths
        $contents = DB::table('educational_contents')->whereNotNull('media_files')->get();
        foreach ($contents as $content) {
            $mediaFiles = json_decode($content->media_files, true);
            if (is_array($mediaFiles)) {
                $updated = false;
                foreach ($mediaFiles as &$file) {
                    if (isset($file['path']) && strpos($file['path'], 'public/education') === 0) {
                        $file['path'] = str_replace('public/education', 'education', $file['path']);
                        $updated = true;
                    }
                }
                if ($updated) {
                    DB::table('educational_contents')
                        ->where('id', $content->id)
                        ->update(['media_files' => json_encode($mediaFiles)]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE educational_contents SET image_path = REPLACE(image_path, 'education', 'public/education') WHERE image_path LIKE 'education%'");
    }
};
