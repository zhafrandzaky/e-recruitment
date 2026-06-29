<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->unique('application_id');
            $table->timestamp('scheduled_at');
            $table->string('meeting_link', 500);
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->timestamps();

            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
