<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('see_overview')->default(false);
            $table->boolean('update')->default(false);
            $table->boolean('delete')->default(false);
            $table->boolean('assign_roles')->default(false);
            $table->boolean('approve_registrations')->default(false);
            $table->timestamps();
        });

        // Required before existing users can be assigned their default role.
        foreach (['instructor', 'editor', 'admin'] as $name) {
            DB::table('roles')->insert([
                'name' => $name,
                'see_overview' => $name !== 'instructor',
                'update' => $name !== 'instructor',
                'delete' => $name === 'admin',
                'assign_roles' => $name === 'admin',
                'approve_registrations' => $name === 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
