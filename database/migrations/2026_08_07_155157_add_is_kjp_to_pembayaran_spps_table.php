<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spp_payments') && !Schema::hasColumn('spp_payments', 'is_kjp')) {
            Schema::table('spp_payments', function (Blueprint $table) {
                $table->boolean('is_kjp')->default(false)->after('metode_pembayaran');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('spp_payments') && Schema::hasColumn('spp_payments', 'is_kjp')) {
            Schema::table('spp_payments', function (Blueprint $table) {
                $table->dropColumn('is_kjp');
            });
        }
    }
};