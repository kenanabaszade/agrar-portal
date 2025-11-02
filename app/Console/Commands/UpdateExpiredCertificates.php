<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UpdateExpiredCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expired certificates status and move PDFs to expired folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired certificates...');
        
        // Find certificates that are expired but still have active status
        $expiredCertificates = Certificate::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->get();
        
        $count = $expiredCertificates->count();
        
        if ($count === 0) {
            $this->info('No expired certificates found.');
            return 0;
        }
        
        $this->info("Found {$count} expired certificate(s). Processing...");
        
        $updated = 0;
        $errors = 0;
        
        foreach ($expiredCertificates as $certificate) {
            try {
                // Move PDF file to expired_certificates folder if it exists
                if ($certificate->pdf_path) {
                    $originalPath = $certificate->pdf_path;
                    $newPath = $this->moveToExpiredFolder($originalPath);
                    
                    if ($newPath) {
                        $certificate->pdf_path = $newPath;
                        Log::info('Moved expired certificate PDF', [
                            'certificate_id' => $certificate->id,
                            'old_path' => $originalPath,
                            'new_path' => $newPath
                        ]);
                    }
                }
                
                // Update certificate status to expired
                $certificate->status = 'expired';
                $certificate->save();
                
                $updated++;
                $this->line("Updated certificate #{$certificate->id} (Number: {$certificate->certificate_number})");
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error updating certificate #{$certificate->id}: " . $e->getMessage());
                Log::error('Error updating expired certificate', [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Completed: {$updated} updated, {$errors} errors.");
        
        return 0;
    }
    
    /**
     * Move PDF file to expired_certificates folder
     */
    private function moveToExpiredFolder(string $originalPath): ?string
    {
        try {
            // Determine if path is relative to storage/app/public or absolute
            $fullPath = storage_path('app/public/' . $originalPath);
            if (!file_exists($fullPath)) {
                // Try absolute path
                if (file_exists($originalPath)) {
                    $fullPath = $originalPath;
                } else {
                    // Try relative to storage/app
                    $altPath = storage_path('app/' . $originalPath);
                    if (file_exists($altPath)) {
                        $fullPath = $altPath;
                    } else {
                        Log::warning('PDF file not found for expired certificate', ['path' => $originalPath]);
                        return null;
                    }
                }
            }
            
            // Get file name and directory structure
            $fileName = basename($fullPath);
            $userDir = dirname(str_replace(storage_path('app/public/'), '', str_replace(storage_path('app/'), '', $fullPath)));
            
            // Determine new path in expired_certificates folder
            if (strpos($userDir, 'certificates') !== false) {
                // Replace 'certificates' with 'expired_certificates' in path
                $newDir = str_replace('certificates', 'expired_certificates', $userDir);
            } else {
                // Create expired_certificates folder structure
                $newDir = 'expired_certificates/' . $userDir;
            }
            
            $newPath = $newDir . '/' . $fileName;
            $newFullPath = storage_path('app/public/' . $newPath);
            
            // Create directory if it doesn't exist
            $newDirPath = dirname($newFullPath);
            if (!is_dir($newDirPath)) {
                mkdir($newDirPath, 0755, true);
            }
            
            // Move the file
            if (rename($fullPath, $newFullPath)) {
                return $newPath;
            } else {
                Log::error('Failed to move expired certificate PDF', [
                    'old_path' => $fullPath,
                    'new_path' => $newFullPath
                ]);
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Error moving expired certificate PDF', [
                'original_path' => $originalPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
