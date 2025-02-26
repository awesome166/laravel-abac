<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbacTables extends Migration
{
    public function up()
    {
        Schema::create('permission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 60);
            $table->enum('type', ['user','admin','agent','vendor'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->foreign('parent_id')->references('id')->on('accounts');
            $table->timestamps();
        });

        // jeff can have admin account
        // agent can belong to jeff account
        // user can belong to agent account

        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('account_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'account_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });


        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('slug', 30)->unique();
            $table->string('name', 60);
            $table->string('description', 120)->nullable();
            $table->enum('type', ['on-off', 'read-write', 'crud']);
            $table->json('account_type')->nullable();
            $table->foreign('category_id')->references('id')->on('permission_categories')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('assigned_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('assignee_id');
            $table->enum('assignee_type', ['user', 'token', 'role']);
            $table->json('access');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assigned_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_accounts');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permission_categories');
    }
}