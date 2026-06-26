<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\QueueItem;
use App\Models\Service;
use App\Models\Booking;
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QueueController extends Controller
{
    /**
     * Get queue status data
     */
    public function getQueueData()
    {
        $today = Carbon::today();
        
        $waiting = QueueItem::whereDate('created_at', $today)
            ->where('status', 'waiting')
            ->orderBy('id', 'asc')
            ->get();
            
        $inService = QueueItem::whereDate('created_at', $today)
            ->where('status', 'in-service')
            ->orderBy('called_at', 'asc')
            ->get();
            
        $completed = QueueItem::whereDate('created_at', $today)
            ->whereIn('status', ['completed', 'no-show'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        // Calculate actual average service duration today (in seconds)
        // If there are completed items, compute average. Otherwise use 10 minutes (600s) default.
        $avgServiceSeconds = \Illuminate\Support\Facades\DB::table('queue_items')
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->whereNotNull('called_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, called_at, completed_at)) as avg_time')
            ->value('avg_time');

        $avgServiceMinutes = $avgServiceSeconds ? ($avgServiceSeconds / 60) : 10;
        
        $activeCounters = max(1, count($inService));

        // Update estimated wait times for all waiting items dynamically in the database
        foreach ($waiting as $index => $item) {
            $estWait = max(1, round((($index + 1) / $activeCounters) * $avgServiceMinutes));
            $item->update(['estimated_wait_minutes' => $estWait]);
        }

        // Total wait time for the next new joiner
        $nextWaitMinutes = max(1, round(((count($waiting) + 1) / $activeCounters) * $avgServiceMinutes));

        return [
            'waiting' => $waiting,
            'in_service' => $inService,
            'completed' => $completed,
            'stats' => [
                'total_waiting' => count($waiting),
                'total_in_service' => count($inService),
                'next_ticket' => count($waiting) > 0 ? $waiting[0]->ticket_number : 'None',
                'avg_wait_minutes' => $nextWaitMinutes,
                'avg_service_minutes' => round($avgServiceMinutes, 1)
            ]
        ];
    }

    /**
     * Fetch status for JSON APIs
     */
    public function status()
    {
        return response()->json($this->getQueueData());
    }

    /**
     * Render the lobby TV display screen
     */
    public function display()
    {
        $services = Service::all();
        $queueData = $this->getQueueData();
        return \Inertia\Inertia::render('Tenant/Display', [
            'services' => $services,
            'initialQueue' => $queueData,
            'tenant' => tenant(),
        ]);
    }

    /**
     * Let a walk-in join the queue
     */
    public function join(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'service_id' => 'nullable|exists:services,id',
        ]);

        $today = Carbon::today();
        $countToday = QueueItem::whereDate('created_at', $today)->count();
        $ticketNum = 'Q-' . str_pad((string)($countToday + 1), 3, '0', STR_PAD_LEFT);

        $serviceName = 'Walk-in General';
        if ($request->service_id) {
            $service = Service::find($request->service_id);
            if ($service) {
                $serviceName = $service->name;
            }
        }

        // Calculate estimated wait time using smart dynamic logic
        $waitingCount = QueueItem::whereDate('created_at', $today)
            ->where('status', 'waiting')
            ->count();
        $inServiceCount = QueueItem::whereDate('created_at', $today)
            ->where('status', 'in-service')
            ->count();
        
        $avgServiceSeconds = \Illuminate\Support\Facades\DB::table('queue_items')
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->whereNotNull('called_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, called_at, completed_at)) as avg_time')
            ->value('avg_time');

        $avgServiceMinutes = $avgServiceSeconds ? ($avgServiceSeconds / 60) : 10;
        $activeCounters = max(1, $inServiceCount);
        $estWait = max(1, round((($waitingCount + 1) / $activeCounters) * $avgServiceMinutes));

        $item = QueueItem::create([
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'ticket_number' => $ticketNum,
            'service_name' => $serviceName,
            'status' => 'waiting',
            'estimated_wait_minutes' => $estWait,
        ]);

        // Send check-in welcome SMS
        if ($item->customer_phone) {
            $msg = "EasyQ Welcome! You joined the queue. Ticket: {$item->ticket_number}. Est. wait: {$item->estimated_wait_minutes} mins.";
            \App\Services\SmsService::send($item->customer_phone, $msg);
        }

        // Broadcast update
        broadcast(new QueueUpdated($this->getQueueData()))->toOthers();

        return response()->json([
            'success' => true,
            'ticket' => $item
        ]);
    }

    /**
     * Call the next waiting customer (Move to in-service)
     */
    public function callNext(Request $request)
    {
        $request->validate([
            'counter_name' => 'nullable|string|max:50'
        ]);

        $counterName = $request->input('counter_name', 'Main Desk');

        $today = Carbon::today();
        $next = QueueItem::whereDate('created_at', $today)
            ->where('status', 'waiting')
            ->orderBy('id', 'asc')
            ->first();

        if ($next) {
            $next->update([
                'status' => 'in-service',
                'counter_name' => $counterName,
                'called_at' => Carbon::now()
            ]);

            // Send call next SMS
            if ($next->customer_phone) {
                $msg = "EasyQ Alert: Your ticket {$next->ticket_number} is being served at {$counterName}!";
                \App\Services\SmsService::send($next->customer_phone, $msg);
            }

            // Find new next-in-line waiting customer to send "Near Turn" alert
            $newNext = QueueItem::whereDate('created_at', $today)
                ->where('status', 'waiting')
                ->orderBy('id', 'asc')
                ->first();

            if ($newNext && $newNext->customer_phone) {
                $msg = "EasyQ Update: You are next in line! Please prepare to proceed shortly.";
                \App\Services\SmsService::send($newNext->customer_phone, $msg);
            }

            broadcast(new QueueUpdated($this->getQueueData()));
            return response()->json(['success' => true, 'item' => $next]);
        }

        return response()->json(['success' => false, 'message' => 'No customers waiting']);
    }

    /**
     * Mark a customer service as completed
     */
    public function complete($tenant, $id)
    {
        $item = QueueItem::findOrFail($id);
        $item->update([
            'status' => 'completed',
            'completed_at' => Carbon::now()
        ]);

        broadcast(new QueueUpdated($this->getQueueData()));
        return response()->json(['success' => true]);
    }

    /**
     * Mark a customer as no-show
     */
    public function noshow($tenant, $id)
    {
        $item = QueueItem::findOrFail($id);
        $item->update([
            'status' => 'no-show',
            'completed_at' => Carbon::now()
        ]);

        broadcast(new QueueUpdated($this->getQueueData()));
        return response()->json(['success' => true]);
    }

    /**
     * Remove an item from the queue
     */
    public function remove($tenant, $id)
    {
        $item = QueueItem::findOrFail($id);
        $item->delete();

        broadcast(new QueueUpdated($this->getQueueData()));
        return response()->json(['success' => true]);
    }
}
