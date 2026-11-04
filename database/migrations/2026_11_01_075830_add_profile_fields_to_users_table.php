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
        $table->enum('role', ['owner', 'employee'])->default('employee');
        $table->date('join_date')->nullable();
        $table->string('position')->nullable();
        $table->text('address')->nullable();
        $table->string('wa_number')->nullable();
        $table->string('bank_account')->nullable();
        $table->string('company')->nullable();
        $table->string('profile_photo')->nullable();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
