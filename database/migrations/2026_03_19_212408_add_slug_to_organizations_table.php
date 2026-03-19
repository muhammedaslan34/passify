<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add nullable first so we can populate
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Populate slugs for all existing orgs using raw DB to avoid
        // model dependencies (history table does not yet exist at this point)
        $orgs = DB::table('organizations')->orderBy('id')->get(['id', 'name']);
        foreach ($orgs as $org) {
            $base = Str::slug($org->name) ?: 'org';
            $slug = $base;
            $i    = 2;
            while (DB::table('organizations')->where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            DB::table('organizations')->where('id', $org->id)->update(['slug' => $slug]);
        }

        // Now enforce NOT NULL + UNIQUE
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
