<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel Audit Trail Logging (Append-Only untuk Akuntabilitas & Keamanan).
     * Merekam setiap manipulasi data master dan spasial oleh operator.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action', 50); // CREATE, UPDATE, DELETE, IMPORT, LOGIN, LOGOUT, CONFIG_CHANGE
            $table->string('table_name', 50);
            $table->bigInteger('record_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('user_id', 'idx_audit_user');
        });

        // Indeks performa query log forensik (created_at DESC)
        DB::statement('CREATE INDEX idx_audit_created ON audit_logs (created_at DESC);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
