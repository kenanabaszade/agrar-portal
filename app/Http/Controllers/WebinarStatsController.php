<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Meeting;
use App\Models\MeetingRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WebinarStatsController extends Controller
{
    /**
     * Get webinar statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            // Total Webinars
            $totalWebinars = Meeting::count();
            
            // This month's held webinars
            $thisMonthHeld = Meeting::where('status', 'ended')
                ->whereMonth('start_time', Carbon::now()->month)
                ->whereYear('start_time', Carbon::now()->year)
                ->count();
            
            // Total Participants (unique users who registered)
            $totalParticipants = MeetingRegistration::distinct('user_id')->count();
            
            // Average Rating (if you have rating system)
            $averageRating = 4.7; // Default value, implement rating system if needed
            
            // Calculate growth percentages
            $totalWebinarsGrowth = $this->calculateGrowth('total_webinars');
            $thisMonthHeldGrowth = $this->calculateGrowth('this_month_held');
            $totalParticipantsGrowth = $this->calculateGrowth('total_participants');
            $averageRatingGrowth = $this->calculateGrowth('average_rating');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_webinars' => [
                        'value' => $totalWebinars,
                        'growth' => $totalWebinarsGrowth,
                        'icon' => 'video-camera',
                        'color' => 'blue'
                    ],
                    'this_month_held' => [
                        'value' => $thisMonthHeld,
                        'growth' => $thisMonthHeldGrowth,
                        'icon' => 'calendar',
                        'color' => 'green'
                    ],
                    'total_participants' => [
                        'value' => $totalParticipants,
                        'growth' => $totalParticipantsGrowth,
                        'icon' => 'users',
                        'color' => 'purple'
                    ],
                    'average_rating' => [
                        'value' => $averageRating,
                        'growth' => $averageRatingGrowth,
                        'icon' => 'star',
                        'color' => 'orange'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch webinar statistics',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get detailed webinar analytics
     */
    public function getAnalytics(): JsonResponse
    {
        try {
            // Monthly webinar creation trend
            $monthlyTrend = Meeting::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
            // Status distribution
            $statusDistribution = Meeting::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();
            
            // Top categories
            $topCategories = Meeting::select('category', DB::raw('COUNT(*) as count'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();
            
            // Monthly participants
            $monthlyParticipants = MeetingRegistration::select(
                DB::raw('DATE_FORMAT(registered_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('registered_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_trend' => $monthlyTrend,
                    'status_distribution' => $statusDistribution,
                    'top_categories' => $topCategories,
                    'monthly_participants' => $monthlyParticipants
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch analytics',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate growth percentage
     */
    private function calculateGrowth($metric): float
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $lastMonth = $currentMonth - 1;
        $lastMonthYear = $currentYear;
        
        if ($lastMonth <= 0) {
            $lastMonth = 12;
            $lastMonthYear = $currentYear - 1;
        }
        
        $currentValue = $this->getMetricValue($metric, $currentMonth, $currentYear);
        $lastValue = $this->getMetricValue($metric, $lastMonth, $lastMonthYear);
        
        if ($lastValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        
        return round((($currentValue - $lastValue) / $lastValue) * 100, 1);
    }
    
    /**
     * Get metric value for specific month/year
     */
    private function getMetricValue($metric, $month, $year)
    {
        switch ($metric) {
            case 'total_webinars':
                return Meeting::whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->count();
                    
            case 'this_month_held':
                return Meeting::where('status', 'ended')
                    ->whereMonth('start_time', $month)
                    ->whereYear('start_time', $year)
                    ->count();
                    
            case 'total_participants':
                return MeetingRegistration::whereMonth('registered_at', $month)
                    ->whereYear('registered_at', $year)
                    ->count();
                    
            case 'average_rating':
                return 4.7; // Default value
                
            default:
                return 0;
        }
    }
}