<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_events', function (Blueprint $table) {
            $table->string('confidence')->default('med')->index(); // high|med|low
        });
    }

    public function down(): void
    {
        Schema::table('portal_events', function (Blueprint $table) {
            $table->dropColumn('confidence');
        });
    }
};