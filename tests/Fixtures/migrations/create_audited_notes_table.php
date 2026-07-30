<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audited_notes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('body');
            $table->unsignedInteger('user_id')->nullable();
        });
    }
};
