<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('towns', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('district_id')->index();
            $table->string('district_name');
            $table->string('name');
            $table->json('fees')->nullable();
            $table->boolean('available')->default(true);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 40)->index();
            $table->string('email')->nullable();
            $table->string('gender', 10);
            $table->unsignedBigInteger('town_id');
            $table->string('district_name');
            $table->string('town_name');
            $table->string('address_line1');
            $table->text('notes')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('status', 20)->index();
            $table->decimal('products_total', 10, 2);
            $table->decimal('shipping_fee', 10, 2);
            $table->boolean('free_shipping')->default(false);
            $table->decimal('customer_total', 10, 2);
            $table->decimal('expected_net', 10, 2)->default(0);
            $table->boolean('quote_verified')->default(false);
            $table->string('attention_reason', 40)->nullable();
            $table->json('request_body')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('send_attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->unsignedInteger('mart_status_id')->nullable();
            $table->string('mart_status_name')->nullable();
            $table->string('earning_state', 20)->nullable();
            $table->decimal('net_earning', 10, 2)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('combination_id')->nullable();
            $table->string('name', 500);
            $table->string('options_label')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_cost', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('towns');
    }
};
