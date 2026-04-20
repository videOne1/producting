<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Providers\ReportMail;
use App\Services\ReportMailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use PDO;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()
            ->select('id', 'name', 'email')
            ->where('is_active', true)
            ->latest();

        if (! empty($validated['search'])) {
            $users->where('name', 'like', '%'.$validated['search'].'%');
        }

        if($request->has('page') && $request->has('limit')){
            $users = $users->paginate($request->limit);
            
        }
        return response()->json($users->get());
    }

    public function deactivateInactive(Request $request): JsonResponse
    {
        $count = 0;
        User::query()
            ->where('is_active', true)
            ->where('last_login_at', '<' , Carbon::now()->subYears(2))
            ->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    $user->update(['is_active' => false]);
                    $count++;
                }
            });
        return response()->json([
            'message' => 'Finished',
            'count' => $count
        ]);
    }


    public function sendReport(Request $request, ReportMailService $reportMail)
    {
        $user = User::findOrFail($request->user_id);

        $msg = $reportMail->send($user);

        if(!$msg){
            return response()->json(['message' => 'Failed to send report'], 500);
        }

        return response()->json(['message' => $msg], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
