<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulid_books', function (Blueprint $table): void {
            $table->string('id', 40)->primary();
            $table->string('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulid_books');
    }
};
