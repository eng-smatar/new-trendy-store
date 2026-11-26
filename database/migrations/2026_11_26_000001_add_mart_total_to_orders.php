<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The last cash-on-delivery total Mart reported, so a warning is raised only when it changes.
        Schema::table('orders', fn (Blueprint $table) => $table->decimal('mart_total', 10, 2)->nullable());
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('mart_total'));
    }
};
