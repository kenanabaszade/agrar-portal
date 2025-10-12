<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\User;
use App\Models\TrainingRegistration;
use App\Models\UserTrainingProgress;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TrainingStatsController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $currentMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Ümumi Təlimlər və Growth
        $totalTrainings = Training::count();
        $totalTrainingsLastMonth = Training::where('created_at', '>=', $lastMonth)->where('created_at', '<', $currentMonth)->count();
        $totalTrainingsThisMonth = Training::where('created_at', '>=', $currentMonth)->count();
        $totalTrainingsGrowth = $this->calculateGrowthPercentage($totalTrainingsLastMonth, $totalTrainingsThisMonth);

        // Aktiv Təlimlər və Growth
        $activeTrainings = Training::where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->count();
        
        // Keçən ayın sonunda aktiv olan təlimlər
        $activeTrainingsLastMonth = Training::where('start_date', '<=', $lastMonthEnd)
            ->where(function ($query) use ($lastMonthEnd) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $lastMonthEnd);
            })
            ->count();
        
        // Bu ayın sonunda aktiv olan təlimlər
        $activeTrainingsThisMonth = Training::where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->count();
        
        $activeTrainingsGrowth = $this->calculateGrowthPercentage($activeTrainingsLastMonth, $activeTrainingsThisMonth);

        // Ümumi İştirakçılar və Growth
        $totalParticipants = TrainingRegistration::count();
        $totalParticipantsLastMonth = TrainingRegistration::where('created_at', '<', $lastMonth)->count();
        $totalParticipantsThisMonth = TrainingRegistration::where('created_at', '>=', $lastMonth)->where('created_at', '<', $currentMonth)->count();
        $totalParticipantsGrowth = $this->calculateGrowthPercentage($totalParticipantsLastMonth, $totalParticipantsThisMonth);

        // Orta Tamamlanma və Growth
        $completedProgress = UserTrainingProgress::where('status', 'completed')->count();
        $totalProgress = UserTrainingProgress::count();
        $averageCompletion = $totalProgress > 0 ? round(($completedProgress / $totalProgress) * 100, 2) : 0;

        // Keçən ay üçün orta tamamlanma
        $completedProgressLastMonth = UserTrainingProgress::where('status', 'completed')
            ->where('updated_at', '>=', $lastMonth)
            ->where('updated_at', '<', $currentMonth)
            ->count();
        $totalProgressLastMonth = UserTrainingProgress::where('updated_at', '>=', $lastMonth)
            ->where('updated_at', '<', $currentMonth)
            ->count();
        $averageCompletionLastMonth = $totalProgressLastMonth > 0 ? round(($completedProgressLastMonth / $totalProgressLastMonth) * 100, 2) : 0;

        // Bu ay üçün orta tamamlanma
        $completedProgressThisMonth = UserTrainingProgress::where('status', 'completed')
            ->where('updated_at', '>=', $currentMonth)
            ->count();
        $totalProgressThisMonth = UserTrainingProgress::where('updated_at', '>=', $currentMonth)->count();
        $averageCompletionThisMonth = $totalProgressThisMonth > 0 ? round(($completedProgressThisMonth / $totalProgressThisMonth) * 100, 2) : 0;

        $averageCompletionGrowth = $this->calculateGrowthPercentage($averageCompletionLastMonth, $averageCompletionThisMonth);

        return response()->json([
            'total_trainings' => [
                'count' => $totalTrainings,
                'growth' => $totalTrainingsGrowth,
                'growth_type' => $totalTrainingsGrowth >= 0 ? 'increase' : 'decrease'
            ],
            'active_trainings' => [
                'count' => $activeTrainings,
                'growth' => $activeTrainingsGrowth,
                'growth_type' => $activeTrainingsGrowth >= 0 ? 'increase' : 'decrease'
            ],
            'total_participants' => [
                'count' => $totalParticipants,
                'growth' => $totalParticipantsGrowth,
                'growth_type' => $totalParticipantsGrowth >= 0 ? 'increase' : 'decrease'
            ],
            'average_completion' => [
                'percentage' => $averageCompletion,
                'growth' => $averageCompletionGrowth,
                'growth_type' => $averageCompletionGrowth >= 0 ? 'increase' : 'decrease'
            ],
            'period' => [
                'current_month' => $currentMonth->format('Y-m'),
                'last_month' => $lastMonth->format('Y-m')
            ]
        ]);
    }

    private function calculateGrowthPercentage($oldValue, $newValue)
    {
        // Əgər keçən ay 0 idi, bu ay var - 100% artım (yeni başlayan)
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        // Normal growth hesablaması
        $growth = (($newValue - $oldValue) / $oldValue) * 100;
        return round($growth, 2);
    }
}
