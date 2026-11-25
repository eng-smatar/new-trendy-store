<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 500);
            $table->string('parent_path', 500)->nullable();
            $table->string('gender', 20)->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('reference')->nullable();
            $table->string('name', 500);
            $table->mediumText('description')->nullable();
            $table->text('description_short')->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('brand_name')->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->index();
            $table->json('images')->nullable();
            $table->string('cover_image', 1000)->nullable();
            $table->json('combinations')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->text('search_text')->nullable();
            $table->boolean('mart_active')->default(true);
            $table->boolean('hidden')->default(false);
            $table->boolean('featured')->default(false)->index();
            $table->timestamp('removed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['mart_active', 'hidden', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
