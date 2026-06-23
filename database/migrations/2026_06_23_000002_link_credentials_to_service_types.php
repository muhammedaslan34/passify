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
        DB::statement('UPDATE credentials c JOIN service_types s ON s.slug = c.service_type SET c.service_type_id = s.id');

        // 3. Make the new column NOT NULL (every row now resolves to a seeded type).
        Schema::table('credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('service_type_id')->nullable(false)->change();
        });

        // 4. Drop the legacy enum column (contract). Its index drops automatically.
        Schema::table('credentials', function (Blueprint $table) {
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

        DB::statement('UPDATE credentials c JOIN service_types s ON s.id = c.service_type_id SET c.service_type = s.slug');

        Schema::table('credentials', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->dropColumn('service_type_id');
        });
    }
};
