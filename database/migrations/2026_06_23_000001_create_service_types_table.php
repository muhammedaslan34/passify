<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('color')->default('gray');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default service types inside the migration so the credentials
        // backfill (next migration) works without a separate seed step.
        $now = now();
        DB::table('service_types')->insert([
            ['slug' => 'hosting',      'name' => 'Hosting',      'color' => 'blue',    'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'domain',       'name' => 'Domain',       'color' => 'purple',  'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'email',        'name' => 'Email',        'color' => 'pink',    'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'database',     'name' => 'Database',     'color' => 'orange',  'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'social_media', 'name' => 'Social Media', 'color' => 'cyan',    'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'analytics',    'name' => 'Analytics',    'color' => 'emerald', 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'other',        'name' => 'Other',        'color' => 'gray',    'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
