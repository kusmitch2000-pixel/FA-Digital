<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arbeitsgang_zuordnung', function (Blueprint $table) {
            $table->unsignedInteger('arbeitsgang_id');
            $table->unsignedInteger('benutzer_id');
            $table->primary(['arbeitsgang_id', 'benutzer_id']);
            $table->foreign('arbeitsgang_id')->references('id')->on('arbeitsgang');
            $table->foreign('benutzer_id')->references('id')->on('benutzer');
        });

        Schema::create('arbeitsgang_notiz', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('arbeitsgang_id');
            $table->unsignedInteger('benutzer_id');
            $table->string('text', 1000);
            $table->dateTime('erstellt_am');
            $table->foreign('arbeitsgang_id')->references('id')->on('arbeitsgang');
            $table->foreign('benutzer_id')->references('id')->on('benutzer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbeitsgang_notiz');
        Schema::dropIfExists('arbeitsgang_zuordnung');
    }
};
