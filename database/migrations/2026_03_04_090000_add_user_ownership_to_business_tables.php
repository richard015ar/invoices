<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultUserId = $this->resolveDefaultUserId();

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('issuer_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('invoices_invoice_number_unique');
        });

        DB::table('catalog_items')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        DB::table('clients')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        DB::table('issuer_profiles')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        DB::table('invoices')->whereNull('user_id')->update(['user_id' => $defaultUserId]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['user_id', 'invoice_number'], 'invoices_user_id_invoice_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_user_id_invoice_number_unique');
            $table->unique('invoice_number');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('issuer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    private function resolveDefaultUserId(): int
    {
        $existingId = DB::table('users')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('users')->insertGetId([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
