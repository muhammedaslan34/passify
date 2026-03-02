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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->enum('service_type', ['hosting', 'domain', 'email', 'database', 'social_media', 'analytics', 'other']);
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('organization_id');
            $table->index('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
