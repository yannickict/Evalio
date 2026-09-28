<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $instructorId = DB::table('roles')->where('name', 'instructor')->value('id');

        Schema::table('users', function (Blueprint $table) use ($instructorId) {
            $table->foreignId('role_id')->default($instructorId)->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('first_name')->default('');
            $table->string('last_name')->default('');
            $table->boolean('is_approved')->default(false);
        });

        DB::table('users')->orderBy('id')->each(function (object $user) {
            $parts = preg_split('/\s+/', trim($user->name), 2);
            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
            ]);
        });

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('name'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('name')->default(''));
        DB::table('users')->orderBy('id')->each(function (object $user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => trim($user->first_name.' '.$user->last_name),
            ]);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['first_name', 'last_name', 'is_approved']);
        });
    }
};
