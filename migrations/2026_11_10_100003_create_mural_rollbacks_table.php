<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/*
 * What a moderator rolled back, and with what: the area, whose tiles and
 * when. Kept so the moderation log reads well and a rollback can be undone.
 */
return Migration::createTable('mural_rollbacks', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('season_id');
    $table->unsignedInteger('actor_id')->nullable();
    $table->text('filters');
    $table->unsignedInteger('tiles')->default(0);
    $table->dateTime('created_at');
    $table->dateTime('undone_at')->nullable();

    $table->index('season_id', 'mural_rb_season');
    $table->foreign('season_id', 'mural_rb_season_fk')->references('id')->on('mural_seasons')->cascadeOnDelete();
    $table->foreign('actor_id', 'mural_rb_actor_fk')->references('id')->on('users')->nullOnDelete();
});
