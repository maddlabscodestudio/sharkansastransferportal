<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('portal_events', function (Blueprint $table) {
            $table->boolean('needs_waiver')->default(false)->index();
            $table->string('eligibility_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('portal_events', function (Blueprint $table) {
            $table->dropColumn(['needs_waiver','eligibility_note']);
        });
    }
};