<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/* Members a moderator stopped from painting, for good or until a date. */
return Migration::createTable('mural_bans', function (Blueprint $table) {
    $table->unsignedInteger('user_id')->primary();
    $table->unsignedInteger('banned_by')->nullable();
    $table->string('reason', 200)->nullable();
    $table->dateTime('until')->nullable();
    $table->dateTime('created_at');

    $table->foreign('user_id', 'mural_bn_user_fk')->references('id')->on('users')->cascadeOnDelete();
    $table->foreign('banned_by', 'mural_bn_by_fk')->references('id')->on('users')->nullOnDelete();
});
