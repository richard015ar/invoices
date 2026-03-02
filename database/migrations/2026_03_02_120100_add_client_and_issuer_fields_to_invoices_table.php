<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('from_address')->constrained()->nullOnDelete();
            $table->string('from_nie')->nullable()->after('from_address');
            $table->text('from_additional_info')->nullable()->after('from_nie');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['from_nie', 'from_additional_info']);
        });
    }
};
