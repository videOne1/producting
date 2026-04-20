<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });

        $now = now();

        $legacyTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Legacy Unassigned Products',
            'slug' => 'legacy-unassigned-products',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $user) use ($now): void {
                $tenantId = DB::table('tenants')->insertGetId([
                    'name' => sprintf('%s Tenant', $user->name),
                    'slug' => sprintf(
                        'user-%d-%s',
                        $user->id,
                        Str::slug($user->name ?: 'tenant')
                    ),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['tenant_id' => $tenantId]);
            });

        DB::table('products')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => $legacyTenantId]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['tenant_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_id_sku_unique');
            $table->unique('sku');
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
