<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema persistensi tata kelola sistem, interval tematik WebGIS, backup sistem, dan aturan akses IP.
     */
    public function up(): void
    {
        // 1. System Settings
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, integer, encrypted
            $table->string('group')->index(); // general, maintenance, integration, security, policy
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Thematic Intervals
        Schema::create('thematic_intervals', function (Blueprint $table): void {
            $table->id();
            $table->string('kategori')->index(); // populasi, produksi, penyakit, fasilitas
            $table->smallInteger('tier'); // 1 to 5
            $table->string('label');
            $table->decimal('min_value', 12, 2)->default(0);
            $table->decimal('max_value', 12, 2)->nullable();
            $table->string('color_hex', 10);
            $table->string('satuan', 30); // ekor, kg, liter, butir, kasus, unit
            $table->string('calculation_mode')->default('manual'); // manual | auto
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. System Backups
        Schema::create('system_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('filename')->unique();
            $table->string('disk_path');
            $table->bigInteger('size_bytes');
            $table->string('backup_type'); // database, full, snapshot
            $table->string('checksum', 64);
            $table->string('status')->default('completed'); // completed, in_progress, failed, restoring
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. IP Access Rules
        Schema::create('ip_access_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45); // IPv4, IPv6, or CIDR
            $table->string('rule_type')->default('whitelist'); // whitelist, blacklist
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_access_rules');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('thematic_intervals');
        Schema::dropIfExists('system_settings');
    }
};
