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
        Schema::table('mail_attachment_synchronizer_log', function (Blueprint $table) {
            $table->unsignedBigInteger('remote_order_id')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_attachment_synchronizer_log', function (Blueprint $table) {
            //
        });
    }
};
