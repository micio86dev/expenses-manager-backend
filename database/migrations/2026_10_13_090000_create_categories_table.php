<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // NULL only for shared system categories; the owner may be a user or, later, a tenant.
            $table->uuid('owner_id')->nullable();
            $table->string('name', 60);
            $table->string('color', 7);
            $table->string('icon', 20);
            $table->boolean('is_system')->default(false);
            $table->string('translation_key', 60)->nullable()->unique();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
