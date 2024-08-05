<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CampaignHit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    // Default pageConfig
    protected $pageConfigs = [
        'navbarType' => 'sticky',
        'footerType' => 'static'
    ];

    /**
     * User dashboard
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userDashboard(Request $request)
    {
        $campaigns = Auth::user()->campaigns;
        $campaignHits = CampaignHit::whereHas('campaign', function($query) {
            $query->where('user_id', Auth::user()->id);
        })->get();
        $campaignHitCounts = array();

        foreach ($campaigns as $campaign) {
            array_push($campaignHitCounts, $campaign->campaignHits()->count());
        }
        
        $notReadNotifications = Auth::user()->notReadNotifications();

        $scanningCampaign = CampaignHit::select(DB::raw('DATE(created_at) as date'), DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
        ->groupBy('date', 'hour')
        ->get();

        return view('/pages/dashboard', [
            'pageConfigs' => $this->pageConfigs,
            'campaignNames' => $campaigns->pluck('campaign_name'),
            'campaignHitCounts' => json_encode($campaignHitCounts),
            'campaignHits' => $campaignHits,
            'scanningCampaign' => json_encode($scanningCampaign),
            'notReadNotifications' => $notReadNotifications
        ]);
    }

    /**
     * Get coordinates of QR code hitmap
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCoordinates(Request $request)
    {
        $type = $request->type;
        
        if ($type == 'today') {
            $data = CampaignHit::whereDate('created_at', '=', Carbon::today()->toDateString())
                ->whereHas('campaign', function($query) {
                    $query->where('user_id', Auth::user()->id);
                })
                ->get();
        } else if ($type == 'week') {
            $data = CampaignHit::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->whereHas('campaign', function($query) {
                    $query->where('user_id', Auth::user()->id);
                })
                ->get();
        } else if ($type == 'month') {
            $data = CampaignHit::whereMonth('created_at', '=', Carbon::now()->month)
                ->whereHas('campaign', function($query) {
                    $query->where('user_id', Auth::user()->id);
                })
                ->get();
        } else {
            $data = CampaignHit::whereHas('campaign', function($query) {
                    $query->where('user_id', Auth::user()->id);
                })
                ->get();
        }

        return new JsonResponse($data);
    }

    /**
     * Set read notification.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readNotification(Request $request)
    {
        $notification = \App\Models\Notification::findOrfail($request->notification_id);
        $request->user()->readNotifications()->attach($notification);

        return new JsonResponse(null, 204);
    }
    public function fetchChartData()
    {

        $userId = auth()->id(); // Assuming you're using Laravel's authentication system

        $scanningCampaign = CampaignHit::whereHas('campaign', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
        ->groupBy('date', 'hour')
        ->get();
        // Format scanning data for the chart
        $hours = range(0, 23); // Array representing 24 hours of the day
$countByHour = array_fill_keys($hours, 0); // Initialize count for each hour to 0

foreach ($scanningCampaign as $item) {
    // Increment count for the respective hour
    $countByHour[$item->hour] += $item->count;
}

// Convert the array into an indexed array of counts
$counts = array_values($countByHour);

return response()->json(['y' => $counts]);
    }

    public function fetchSecondChartData()
    {

        $campaigns = Auth::user()->campaigns;
        $campaignHits = CampaignHit::whereHas('campaign', function($query) {
            $query->where('user_id', Auth::user()->id);
        })->get();
        $campaignHitCounts = array();

        foreach ($campaigns as $campaign) {
            array_push($campaignHitCounts, $campaign->campaignHits()->count());
        }

        

return response()->json(['y' => $campaignHitCounts]);
    }

}

