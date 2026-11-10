<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * One row per member per season: their live tile count (the leaderboard)
 * and when they may paint again. The row is locked while placing, so the
 * cooldown holds under any number of tabs or retries.
 */
return Migration::createTable('mural_painters', function (Blueprint $table) {
    $table->unsignedInteger('season_id');
    $table->unsignedInteger('user_id');
    $table->unsignedInteger('tiles')->default(0);
    $table->dateTime('last_at')->nullable();
    $table->dateTime('next_at')->nullable();

    $table->primary(['season_id', 'user_id']);
    $table->index(['season_id', 'tiles'], 'mural_pa_top');
    $table->index('user_id', 'mural_pa_user');

    $table->foreign('season_id', 'mural_pa_season_fk')->references('id')->on('mural_seasons')->cascadeOnDelete();
    $table->foreign('user_id', 'mural_pa_user_fk')->references('id')->on('users')->cascadeOnDelete();
});
