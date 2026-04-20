<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerOrderSeeder extends Seeder
{
    /**
     * Seed customers and orders for order listing queries.
     */
    public function run(): void
    {
        $linkedUserIds = Customer::query()
            ->whereNotNull('user_id')
            ->pluck('user_id');

        Order::query()->delete();
        Customer::query()->delete();

        if ($linkedUserIds->isNotEmpty()) {
            User::query()->whereKey($linkedUserIds)->delete();
        }

        $customers = Customer::factory(100)->make();

        foreach ($customers as $customerAttributes) {
            $user = User::factory()->create([
                'name' => $customerAttributes->name,
                'email' => $customerAttributes->email,
            ]);

            $customer = Customer::query()->create([
                'user_id' => $user->id,
                'name' => $customerAttributes->name,
                'email' => $customerAttributes->email,
            ]);

            Order::factory(random_int(5, 15))->create([
                'customer_id' => $customer->id,
            ]);
        }
    }
}
