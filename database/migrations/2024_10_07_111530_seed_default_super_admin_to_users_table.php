<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::create([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::query()->where('id', 1)->delete();
    }
};
