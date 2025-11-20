<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('coin_exchange_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->string('transaction_id', 100)->unique();
            $table->integer('coin_amount');
            $table->integer('points_amount');
            $table->string('status', 20)->default('pending'); // pending, success, failed
            $table->text('error_message')->nullable();
            $table->string('merchant_response')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('completed_at')->nullable();

            // 索引
            $table->index(['user_id', 'created_at']);
            $table->index('status');

            // 外键
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('coin_exchange_records');
    }
];
