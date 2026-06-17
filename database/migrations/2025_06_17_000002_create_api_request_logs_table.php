<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->text('full_url')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('client_username')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('endpoint');
            $table->index('client_ip');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
