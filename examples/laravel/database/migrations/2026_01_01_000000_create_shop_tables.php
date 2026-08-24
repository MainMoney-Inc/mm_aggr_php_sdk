<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 64)->unique();
            $table->string('name', 200);
            $table->text('description')->default('');
            $table->string('price', 32);
            $table->string('currency', 8);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('product_id')->constrained('products');
            $table->string('amount', 32);
            $table->string('currency', 8);
            $table->string('status', 32)->default('pending');
        });
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->string('amount', 32);
            $table->string('currency', 8);
            $table->string('destination', 64)->default('');
            $table->string('status', 32)->default('pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
