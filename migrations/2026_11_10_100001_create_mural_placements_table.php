<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * The append-only log: every tile ever placed. The timelapse plays it, "who
 * placed this" reads the newest live row for a tile, and a rollback marks
 * rows with its id instead of deleting them, so it can be undone.
 *
 * 🚨 Index names are explicit and short: generated ones carry the table
 * prefix and run past MySQL's 64 characters on a long one.
 */
return Migration::createTable('mural_placements', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedInteger('season_id');
    $table->unsignedInteger('user_id')->nullable();
    $table->unsignedSmallInteger('x');
    $table->unsignedSmallInteger('y');
    $table->unsignedTinyInteger('color');
    $table->unsignedInteger('rollback_id')->nullable();
    $table->dateTime('created_at');

    $table->index(['season_id', 'id'], 'mural_pl_season');
    $table->index(['season_id', 'y', 'x'], 'mural_pl_tile');
    $table->index(['season_id', 'created_at'], 'mural_pl_time');
    $table->index(['user_id', 'season_id'], 'mural_pl_user');
    $table->index('rollback_id', 'mural_pl_rollback');

    $table->foreign('season_id', 'mural_pl_season_fk')->references('id')->on('mural_seasons')->cascadeOnDelete();
    $table->foreign('user_id', 'mural_pl_user_fk')->references('id')->on('users')->nullOnDelete();
});
