<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::create(['name' => 'manage campaigns']);
        $permission = Permission::create(['name' => 'manage donations']);
        $permission = Permission::create(['name' => 'manage users']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
