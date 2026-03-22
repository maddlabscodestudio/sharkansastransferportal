<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_raw_posts', function (Blueprint $table) {
            $table->string('parse_status')->default('unparsed')->index(); // unparsed|parsed|skipped|failed
            $table->timestamp('parsed_at')->nullable()->index();
            $table->text('parse_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('portal_raw_posts', function (Blueprint $table) {
            $table->dropColumn(['parse_status', 'parsed_at', 'parse_error']);
        });
    }
};