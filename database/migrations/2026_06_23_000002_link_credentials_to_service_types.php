<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand-contract: add service_type_id FK, backfill from the legacy enum
     * column, then drop the enum column.
     */
    public function up(): void
    {
        // 1. Add nullable FK column (expand).
        Schema::table('credentials', function (Blueprint $table) {
            $table->foreignId('service_type_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('service_types')
                ->onDelete('restrict');
        });

        // 2. Backfill existing rows from the enum value via slug lookup.
        $serviceTypeIdsBySlug = DB::table('service_types')
            ->pluck('id', 'slug');

        foreach ($serviceTypeIdsBySlug as $slug => $serviceTypeId) {
            DB::table('credentials')
                ->where('service_type', $slug)
                ->update(['service_type_id' => $serviceTypeId]);
        }

        $fallbackServiceTypeId = $serviceTypeIdsBySlug['other']
            ?? $serviceTypeIdsBySlug->first();

        if ($fallbackServiceTypeId !== null) {
            DB::table('credentials')
                ->whereNull('service_type_id')
                ->update(['service_type_id' => $fallbackServiceTypeId]);
        }

        // 3. Make the new column NOT NULL (every row now resolves to a seeded type).
        Schema::table('credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('service_type_id')->nullable(false)->change();
        });

        // 4. Drop the legacy enum column (contract).
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropColumn('service_type');
        });
    }

    public function down(): void
    {
        // Restore the enum column, backfill from service_types, then drop the FK.
        Schema::table('credentials', function (Blueprint $table) {
            $table->enum('service_type', ['hosting', 'domain', 'email', 'database', 'social_media', 'analytics', 'other'])
                ->nullable()
                ->after('organization_id');
        });

        $serviceTypeSlugsById = DB::table('service_types')
            ->pluck('slug', 'id');

        foreach ($serviceTypeSlugsById as $serviceTypeId => $slug) {
            DB::table('credentials')
                ->where('service_type_id', $serviceTypeId)
                ->update(['service_type' => $slug]);
        }

        Schema::table('credentials', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->dropColumn('service_type_id');
            $table->index('service_type');
        });
    }
};
