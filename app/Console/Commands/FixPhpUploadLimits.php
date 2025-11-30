<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixPhpUploadLimits extends Command
{
    protected $signature = 'php:fix-upload-limits';
    protected $description = 'Fix PHP upload limits in php.ini file';

    public function handle()
    {
        $this->info('========================================');
        $this->info('PHP Upload Limits Fix');
        $this->info('========================================');
        $this->newLine();

        // Get current PHP settings
        $currentUploadMax = ini_get('upload_max_filesize');
        $currentPostMax = ini_get('post_max_size');
        
        $this->info("Current settings:");
        $this->line("  upload_max_filesize: {$currentUploadMax}");
        $this->line("  post_max_size: {$currentPostMax}");
        $this->newLine();

        // Get php.ini path
        $phpIniPath = php_ini_loaded_file();
        
        if (!$phpIniPath) {
            $this->error('ERROR: Could not find php.ini file!');
            $this->line('Please edit php.ini manually:');
            $this->line('  1. Find php.ini file location: php --ini');
            $this->line('  2. Open php.ini in text editor (as Administrator)');
            $this->line('  3. Find: upload_max_filesize = 2M');
            $this->line('  4. Change to: upload_max_filesize = 25M');
            $this->line('  5. Find: post_max_size = 8M');
            $this->line('  6. Change to: post_max_size = 30M');
            $this->line('  7. Save and restart PHP server');
            return 1;
        }

        $this->info("PHP.ini location: {$phpIniPath}");
        $this->newLine();

        // Check if file exists and is writable
        if (!File::exists($phpIniPath)) {
            $this->error("ERROR: php.ini file not found at: {$phpIniPath}");
            return 1;
        }

        if (!is_writable($phpIniPath)) {
            $this->error("ERROR: php.ini file is not writable!");
            $this->line('Please run this command as Administrator or edit php.ini manually.');
            $this->newLine();
            $this->line('Manual steps:');
            $this->line("  1. Open: {$phpIniPath}");
            $this->line('  2. Find: upload_max_filesize = 2M');
            $this->line('  3. Change to: upload_max_filesize = 25M');
            $this->line('  4. Find: post_max_size = 8M');
            $this->line('  5. Change to: post_max_size = 30M');
            $this->line('  6. Save and restart PHP server (php artisan serve)');
            return 1;
        }

        // Create backup
        $backupPath = $phpIniPath . '.backup_' . date('Ymd_His');
        $this->info('Creating backup...');
        File::copy($phpIniPath, $backupPath);
        $this->info("Backup created: {$backupPath}");
        $this->newLine();

        // Read php.ini content
        $content = File::get($phpIniPath);

        // Replace upload_max_filesize
        $content = preg_replace(
            '/^\s*;?\s*upload_max_filesize\s*=.*/m',
            'upload_max_filesize = 25M',
            $content
        );

        // Replace post_max_size
        $content = preg_replace(
            '/^\s*;?\s*post_max_size\s*=.*/m',
            'post_max_size = 30M',
            $content
        );

        // Write updated content
        File::put($phpIniPath, $content);

        $this->info('SUCCESS! php.ini has been updated.');
        $this->newLine();
        $this->warn('IMPORTANT: Please restart your PHP server for changes to take effect!');
        $this->line('  - Stop server: Ctrl+C');
        $this->line('  - Start server: php artisan serve');
        $this->newLine();

        return 0;
    }
}



