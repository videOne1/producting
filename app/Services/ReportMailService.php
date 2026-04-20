<?php
namespace App\Services;

use App\Mail\ReportEmail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ReportMailService 
{
    public function send(User $user) 
    {
         $amount = Order::query()->where('user_id', $user->id)->sum('amount');


        Mail::to($user->email)->send(new ReportEmail($amount));
        // Business logic here
        return "Report sent";
    }
}
