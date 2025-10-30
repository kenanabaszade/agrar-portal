<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Training;
use App\Models\TrainingModule;
use App\Models\TrainingLesson;
use App\Models\TrainingRegistration;
use App\Models\UserTrainingProgress;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamChoice;
use App\Models\ExamRegistration;
use Illuminate\Support\Str;

class PerformanceTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding performance test data...');
        
        // Create test users
        $this->command->info('Creating users...');
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin_perf',
            'email' => 'admin_perf@test.com',
            'password_hash' => bcrypt('password'),
            'user_type' => 'admin',
            'is_active' => true,
        ]);
        
        $trainers = [];
        for ($i = 1; $i <= 10; $i++) {
            $trainers[] = User::create([
                'first_name' => 'Trainer',
                'last_name' => "Test{$i}",
                'username' => "trainer_perf_{$i}",
                'email' => "trainer_perf_{$i}@test.com",
                'password_hash' => bcrypt('password'),
                'user_type' => 'trainer',
                'is_active' => true,
            ]);
        }
        
        $farmers = [];
        for ($i = 1; $i <= 100; $i++) {
            $farmers[] = User::create([
                'first_name' => 'Farmer',
                'last_name' => "Test{$i}",
                'username' => "farmer_perf_{$i}",
                'email' => "farmer_perf_{$i}@test.com",
                'password_hash' => bcrypt('password'),
                'user_type' => 'farmer',
                'is_active' => true,
            ]);
        }
        
        $this->command->info('✅ Created ' . (count($trainers) + count($farmers) + 1) . ' users');
        
        // Create trainings with modules and lessons
        $this->command->info('Creating trainings with modules and lessons...');
        $trainings = [];
        $categories = ['agriculture', 'irrigation', 'soil', 'crops', 'livestock'];
        
        for ($t = 1; $t <= 100; $t++) {
            $trainer = $trainers[array_rand($trainers)];
            $category = $categories[array_rand($categories)];
            
            $training = Training::create([
                'title' => "Performance Training {$t}: " . ucfirst($category),
                'description' => "Detailed description for training {$t} about {$category}. " . str_repeat("Lorem ipsum dolor sit amet. ", 10),
                'category' => $category,
                'trainer_id' => $trainer->id,
                'start_date' => now()->addDays(rand(-30, 60)),
                'end_date' => now()->addDays(rand(61, 120)),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Baku',
                'is_online' => rand(0, 1),
                'type' => ['online', 'offline', 'video'][rand(0, 2)],
                'difficulty' => ['beginner', 'intermediate', 'advanced'][rand(0, 2)],
                'has_certificate' => true,
                'has_exam' => false,
                'status' => 'published',
                'media_files' => $this->generateMediaFiles(),
            ]);
            
            $trainings[] = $training;
            
            // Create modules for this training
            $moduleCount = rand(5, 10);
            for ($m = 1; $m <= $moduleCount; $m++) {
                $module = TrainingModule::create([
                    'training_id' => $training->id,
                    'title' => "Module {$m}: Advanced Topics",
                    'description' => "Module {$m} description with detailed content about the topic.",
                    'order' => $m,
                    'is_published' => true,
                ]);
                
                // Create lessons for this module
                $lessonCount = rand(3, 8);
                for ($l = 1; $l <= $lessonCount; $l++) {
                    TrainingLesson::create([
                        'module_id' => $module->id,
                        'title' => "Lesson {$l}: Practical Application",
                        'description' => "Lesson {$l} covers important aspects of the module topic.",
                        'content' => str_repeat("Detailed lesson content. ", 50),
                        'video_url' => rand(0, 1) ? "https://example.com/video_{$t}_{$m}_{$l}.mp4" : null,
                        'pdf_url' => rand(0, 1) ? "https://example.com/pdf_{$t}_{$m}_{$l}.pdf" : null,
                        'duration_minutes' => rand(15, 90),
                        'order' => $l,
                        'is_published' => true,
                    ]);
                }
            }
            
            // Create registrations for this training
            $registrationCount = rand(5, 15);
            $selectedFarmers = array_rand(array_flip(array_map(fn($f) => $f->id, $farmers)), min($registrationCount, count($farmers)));
            if (!is_array($selectedFarmers)) {
                $selectedFarmers = [$selectedFarmers];
            }
            
            foreach ($selectedFarmers as $farmerId) {
                $registration = TrainingRegistration::create([
                    'training_id' => $training->id,
                    'user_id' => $farmerId,
                    'status' => 'approved',
                    'registration_date' => now()->subDays(rand(1, 30)),
                ]);
                
                // Create progress records
                $statuses = ['not_started', 'in_progress', 'completed'];
                $status = $statuses[array_rand($statuses)];
                
                UserTrainingProgress::create([
                    'user_id' => $farmerId,
                    'training_id' => $training->id,
                    'registration_id' => $registration->id,
                    'status' => $status,
                    'progress_percentage' => $status === 'completed' ? 100 : ($status === 'in_progress' ? rand(20, 80) : 0),
                    'last_accessed_at' => $status !== 'not_started' ? now()->subDays(rand(0, 10)) : null,
                ]);
            }
            
            if ($t % 20 === 0) {
                $this->command->info("  Created {$t}/100 trainings...");
            }
        }
        
        $this->command->info('✅ Created 100 trainings with modules and lessons');
        
        // Create exams with questions
        $this->command->info('Creating exams with questions...');
        $exams = [];
        
        for ($e = 1; $e <= 50; $e++) {
            // Some exams are training-based, some independent
            $trainingId = $e <= 30 ? $trainings[array_rand($trainings)]->id : null;
            
            $exam = Exam::create([
                'title' => "Performance Exam {$e}",
                'description' => "Comprehensive exam {$e} covering important topics. " . str_repeat("Test your knowledge. ", 10),
                'training_id' => $trainingId,
                'category' => $categories[array_rand($categories)],
                'start_date' => now()->addDays(rand(-15, 45)),
                'end_date' => now()->addDays(rand(46, 90)),
                'duration_minutes' => rand(30, 120),
                'passing_score' => rand(60, 80),
                'status' => 'published',
                'is_randomized' => rand(0, 1),
                'show_results_immediately' => rand(0, 1),
            ]);
            
            $exams[] = $exam;
            
            // Create questions for this exam
            $questionCount = rand(10, 20);
            for ($q = 1; $q <= $questionCount; $q++) {
                $question = ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_text' => "Question {$q}: What is the best approach for this scenario? Provide detailed answer considering all factors.",
                    'question_type' => 'multiple_choice',
                    'points' => rand(1, 5),
                    'order' => $q,
                    'is_required' => true,
                ]);
                
                // Create choices for multiple choice questions
                $correctChoice = rand(0, 3);
                for ($c = 0; $c < 4; $c++) {
                    ExamChoice::create([
                        'question_id' => $question->id,
                        'choice_text' => "Choice " . chr(65 + $c) . ": This is a possible answer to the question.",
                        'is_correct' => $c === $correctChoice,
                        'order' => $c,
                    ]);
                }
            }
            
            // Create exam registrations
            $examRegistrationCount = rand(3, 12);
            $selectedFarmers = array_rand(array_flip(array_map(fn($f) => $f->id, $farmers)), min($examRegistrationCount, count($farmers)));
            if (!is_array($selectedFarmers)) {
                $selectedFarmers = [$selectedFarmers];
            }
            
            foreach ($selectedFarmers as $farmerId) {
                $statuses = ['registered', 'in_progress', 'passed', 'failed', 'completed'];
                ExamRegistration::create([
                    'exam_id' => $exam->id,
                    'user_id' => $farmerId,
                    'status' => $statuses[array_rand($statuses)],
                    'score' => rand(40, 100),
                    'started_at' => now()->subDays(rand(1, 20)),
                    'completed_at' => rand(0, 1) ? now()->subDays(rand(0, 15)) : null,
                ]);
            }
            
            if ($e % 10 === 0) {
                $this->command->info("  Created {$e}/50 exams...");
            }
        }
        
        $this->command->info('✅ Created 50 exams with questions');
        
        // Summary
        $this->command->newLine();
        $this->command->info('📊 Performance Test Data Summary:');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Trainings: ' . Training::count());
        $this->command->info('  - Training Modules: ' . TrainingModule::count());
        $this->command->info('  - Training Lessons: ' . TrainingLesson::count());
        $this->command->info('  - Training Registrations: ' . TrainingRegistration::count());
        $this->command->info('  - Exams: ' . Exam::count());
        $this->command->info('  - Exam Questions: ' . ExamQuestion::count());
        $this->command->info('  - Exam Registrations: ' . ExamRegistration::count());
        $this->command->newLine();
        $this->command->info('✅ Performance test data seeding completed!');
    }
    
    /**
     * Generate realistic media files array
     */
    private function generateMediaFiles(): array
    {
        $mediaFiles = [];
        
        // Banner image
        if (rand(0, 1)) {
            $mediaFiles[] = [
                'type' => 'banner',
                'path' => 'trainings/banners/banner_' . Str::random(10) . '.jpg',
                'original_name' => 'banner.jpg',
                'mime_type' => 'image/jpeg',
                'size' => rand(100000, 500000),
                'uploaded_at' => now()->subDays(rand(1, 30))->toISOString(),
            ];
        }
        
        // Intro video
        if (rand(0, 1)) {
            $mediaFiles[] = [
                'type' => 'intro_video',
                'path' => 'trainings/videos/intro_' . Str::random(10) . '.mp4',
                'original_name' => 'intro.mp4',
                'mime_type' => 'video/mp4',
                'size' => rand(5000000, 20000000),
                'uploaded_at' => now()->subDays(rand(1, 30))->toISOString(),
            ];
        }
        
        // General media files
        $generalCount = rand(0, 5);
        for ($i = 0; $i < $generalCount; $i++) {
            $types = [
                ['type' => 'general', 'path' => 'trainings/documents/doc_' . Str::random(10) . '.pdf', 'mime_type' => 'application/pdf'],
                ['type' => 'general', 'path' => 'trainings/images/img_' . Str::random(10) . '.jpg', 'mime_type' => 'image/jpeg'],
                ['type' => 'general', 'path' => 'trainings/videos/video_' . Str::random(10) . '.mp4', 'mime_type' => 'video/mp4'],
            ];
            
            $type = $types[array_rand($types)];
            $mediaFiles[] = array_merge($type, [
                'original_name' => basename($type['path']),
                'size' => rand(100000, 10000000),
                'uploaded_at' => now()->subDays(rand(1, 30))->toISOString(),
            ]);
        }
        
        return $mediaFiles;
    }
}

