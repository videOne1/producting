<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_import_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_import_id')
                ->constrained('product_import')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('failure_type', 32);
            $table->json('row_data')->nullable();
            $table->text('error_message');
            $table->timestamps();

            $table->index(['product_import_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_import_failures');
    }
};
