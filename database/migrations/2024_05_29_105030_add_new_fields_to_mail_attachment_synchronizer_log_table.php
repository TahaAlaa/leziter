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
            $table->bigInteger('remote_invoice_id')->unsigned()->default(0);
            $table->bigInteger('remote_warranty_id')->unsigned()->default(0);
            $table->enum('process_status', [
                'attachments_downloaded',
                'attachments_processed',
                'attachments_uploaded',
                'attachments_connected',
                'warranty_info_uploaded',
                'not_started_yet'
            ])->default('not_started_yet')->index();
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
