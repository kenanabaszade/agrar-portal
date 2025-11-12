<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, Training, TrainingModule, TrainingLesson, Exam, ExamQuestion, ExamChoice, Role, Permission};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles & permissions
        $roles = collect(['admin', 'trainer', 'farmer'])->map(fn ($name) => Role::firstOrCreate(['name' => $name]));
        $permNames = ['manage_users', 'manage_trainings', 'take_exams', 'post_forum'];
        $permissions = collect($permNames)->map(fn ($name) => Permission::firstOrCreate(['name' => $name]));

        // Users
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone' => '0000000000',
            'password_hash' => Hash::make('password123'),
            'user_type' => 'admin',
            'email_verified' => true,
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
        $trainer = User::firstOrCreate(['email' => 'trainer@example.com'], [
            'first_name' => 'Tracy',
            'last_name' => 'Trainer',
            'phone' => '1111111111',
            'password_hash' => Hash::make('password123'),
            'user_type' => 'trainer',
            'email_verified' => true,
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
        $farmer = User::firstOrCreate(['email' => 'farmer@example.com'], [
            'first_name' => 'Frank',
            'last_name' => 'Farmer',
            'phone' => '2222222222',
            'password_hash' => Hash::make('password123'),
            'user_type' => 'farmer',
            'email_verified' => true,
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);

        // Attach roles via pivot if needed
        $adminRole = Role::where('name', 'admin')->first();
        $trainerRole = Role::where('name', 'trainer')->first();
        $farmerRole = Role::where('name', 'farmer')->first();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $trainer->roles()->syncWithoutDetaching([$trainerRole->id]);
        $farmer->roles()->syncWithoutDetaching([$farmerRole->id]);

        // Training with modules and lessons
        $training = Training::firstOrCreate([
            'title' => 'Intro to Sustainable Farming',
            'trainer_id' => $trainer->id,
        ], [
            'description' => 'Basics of sustainable practices',
            'category' => 'Sustainability',
            'is_online' => true,
            'has_certificate' => true,
        ]);

        $module1 = TrainingModule::firstOrCreate(['training_id' => $training->id, 'sequence' => 1], [
            'title' => 'Soil Health',
        ]);
        $module2 = TrainingModule::firstOrCreate(['training_id' => $training->id, 'sequence' => 2], [
            'title' => 'Water Management',
        ]);

        TrainingLesson::firstOrCreate(['module_id' => $module1->id, 'sequence' => 1], [
            'title' => 'Soil Basics',
            'content' => 'Understanding soil composition',
        ]);
        TrainingLesson::firstOrCreate(['module_id' => $module1->id, 'sequence' => 2], [
            'title' => 'Composting',
            'content' => 'Introduction to composting',
        ]);

        // Exam with questions
        $exam = Exam::firstOrCreate([
            'training_id' => $training->id,
            'title' => 'Intro Exam',
        ], [
            'passing_score' => 60,
            'duration_minutes' => 30,
            'sertifikat_description' => 'İşğaldan azad olunmuş ərazilərdə kənd təsərrüfatının potensialının gücləndirməsi modulları üzrə təlimlərdə imtahanı uğurla başa vurmuşdur.',
        ]);

        $q1 = ExamQuestion::firstOrCreate(['exam_id' => $exam->id, 'sequence' => 1], [
            'question_text' => 'What is composting?',
            'question_type' => 'single_choice',
        ]);
        ExamChoice::firstOrCreate(['question_id' => $q1->id, 'choice_text' => 'A way to recycle organic matter'], [
            'is_correct' => true,
        ]);
        ExamChoice::firstOrCreate(['question_id' => $q1->id, 'choice_text' => 'A type of soil erosion'], [
            'is_correct' => false,
        ]);
    }
}
