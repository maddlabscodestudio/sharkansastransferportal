<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_raw_posts', function (Blueprint $table) {
            $table->string('from_team')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('portal_raw_posts', function (Blueprint $table) {
            $table->dropColumn('from_team');
        });
    }
};