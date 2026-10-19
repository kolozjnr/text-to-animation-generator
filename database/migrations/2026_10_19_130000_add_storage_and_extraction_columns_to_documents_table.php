<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('status', 'extraction_status');
            $table->renameColumn('error_message', 'failure_reason');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('extraction_status', 32)->default('pending')->change();
            $table->dropUnique(['stored_path']);
            $table->dropColumn('stored_path');

            $table->string('storage_provider', 32)->default('cloudinary')->after('checksum');
            $table->string('storage_public_id', 255)->nullable()->unique()->after('storage_provider');
            $table->string('storage_asset_id', 255)->nullable()->unique()->after('storage_public_id');
            $table->text('storage_url')->nullable()->after('storage_asset_id');
            $table->string('storage_resource_type', 32)->nullable()->after('storage_url');
            $table->string('storage_format', 32)->nullable()->after('storage_resource_type');
            $table->json('storage_metadata')->nullable()->after('storage_format');

            $table->unsignedInteger('page_count')->nullable()->after('storage_metadata');
            $table->string('extracted_content_path', 2048)->nullable()->after('page_count');
            $table->json('extraction_metadata')->nullable()->after('extracted_content_path');
            $table->timestamp('extraction_started_at')->nullable()->after('extraction_metadata');
            $table->timestamp('extracted_at')->nullable()->after('extraction_started_at');

            $table->index('extraction_status');
            $table->index('checksum');
        });

        DB::table('documents')
            ->where('extraction_status', 'uploaded')
            ->update(['extraction_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['extraction_status']);
            $table->dropIndex(['checksum']);
            $table->dropUnique(['storage_public_id']);
            $table->dropUnique(['storage_asset_id']);

            $table->dropColumn([
                'storage_provider',
                'storage_public_id',
                'storage_asset_id',
                'storage_url',
                'storage_resource_type',
                'storage_format',
                'storage_metadata',
                'page_count',
                'extracted_content_path',
                'extraction_metadata',
                'extraction_started_at',
                'extracted_at',
            ]);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('stored_path')->nullable()->unique()->after('original_name');
            $table->renameColumn('extraction_status', 'status');
            $table->renameColumn('failure_reason', 'error_message');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('status', 32)->default('uploaded')->change();
        });
    }
};
