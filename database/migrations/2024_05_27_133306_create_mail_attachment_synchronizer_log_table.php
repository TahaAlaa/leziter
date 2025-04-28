<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mail_attachment_synchronizer_log', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('mail_id')->unsigned()->unique();
            $table->string('subject')->nullable()->index();
            $table->string('order_id')->nullable()->index();
            $table->enum('status', ['in_progress', 'processed', 'error'])->default('in_progress')->index();
            $table->tinyInteger('attachments_count')->unsigned()->default(0);
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_attachment_synchronizer_log');
    }
};
