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
        Schema::table('users', function (Blueprint $table) {         
            $table->enum('role', ['admin', 'staff'])
            ->default('staff')
            ->after('password');      
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()  // admin may not belong to a branch
                ->constrained('branches')
                ->nullOnDelete()  // branch deletion should not delete users, just set branch_id to null
                ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->dropColumn('role');
        });
    }
};
