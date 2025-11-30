<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TrainingRegistration;
use App\Models\UserTrainingProgress;
use App\Models\Certificate;
use App\Models\ExamRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DeleteUserTrainingData extends Command
{
    protected $signature = 'user:delete-training-data {email} {--force}';
    protected $description = 'Delete all training data and certificates for a user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }
        
        $this->info("Found user: {$user->first_name} {$user->last_name} (ID: {$user->id})");
        
        // Count existing data
        $trainingRegistrations = TrainingRegistration::where('user_id', $user->id)->count();
        $trainingProgress = UserTrainingProgress::where('user_id', $user->id)->count();
        $certificates = Certificate::where('user_id', $user->id)->count();
        $examRegistrations = ExamRegistration::where('user_id', $user->id)->count();
        
        $this->info("\nExisting data:");
        $this->line("  Training Registrations: {$trainingRegistrations}");
        $this->line("  Training Progress: {$trainingProgress}");
        $this->line("  Certificates: {$certificates}");
        $this->line("  Exam Registrations: {$examRegistrations}");
        
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete ALL training data and certificates for this user?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        // Get certificates before deletion to delete files
        $certificatesToDelete = Certificate::where('user_id', $user->id)->get();
        
        // Delete certificate files
        $deletedFiles = 0;
        foreach ($certificatesToDelete as $certificate) {
            // Delete PDF file
            if ($certificate->pdf_path) {
                $pdfPath = storage_path('app/public/' . $certificate->pdf_path);
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                    $deletedFiles++;
                    $this->line("  ✓ Deleted PDF: {$certificate->pdf_path}");
                }
                
                // Also try alternative path
                $altPath = storage_path('app/' . $certificate->pdf_path);
                if (file_exists($altPath)) {
                    unlink($altPath);
                    $deletedFiles++;
                }
            }
            
            // Delete QR code file
            if ($certificate->qr_code_path) {
                $qrPath = storage_path('app/public/' . $certificate->qr_code_path);
                if (file_exists($qrPath)) {
                    unlink($qrPath);
                    $deletedFiles++;
                    $this->line("  ✓ Deleted QR: {$certificate->qr_code_path}");
                }
            }
            
            // Delete photo file
            if ($certificate->photo_path) {
                $photoPath = storage_path('app/public/' . $certificate->photo_path);
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                    $deletedFiles++;
                    $this->line("  ✓ Deleted Photo: {$certificate->photo_path}");
                }
            }
        }
        
        // Delete from database
        $deletedTrainingRegistrations = TrainingRegistration::where('user_id', $user->id)->delete();
        $deletedTrainingProgress = UserTrainingProgress::where('user_id', $user->id)->delete();
        $deletedCertificates = Certificate::where('user_id', $user->id)->delete();
        $deletedExamRegistrations = ExamRegistration::where('user_id', $user->id)->delete();
        
        $this->info("\n✓ Deletion completed:");
        $this->line("  Training Registrations deleted: {$deletedTrainingRegistrations}");
        $this->line("  Training Progress deleted: {$deletedTrainingProgress}");
        $this->line("  Certificates deleted: {$deletedCertificates}");
        $this->line("  Exam Registrations deleted: {$deletedExamRegistrations}");
        $this->line("  Files deleted: {$deletedFiles}");
        
        // Verify
        $remainingTrainingRegistrations = TrainingRegistration::where('user_id', $user->id)->count();
        $remainingCertificates = Certificate::where('user_id', $user->id)->count();
        
        if ($remainingTrainingRegistrations === 0 && $remainingCertificates === 0) {
            $this->info("\n✓ All training data and certificates deleted successfully!");
        } else {
            $this->warn("\n⚠ Some data may still remain:");
            $this->line("  Remaining Training Registrations: {$remainingTrainingRegistrations}");
            $this->line("  Remaining Certificates: {$remainingCertificates}");
        }
        
        return 0;
    }
}

