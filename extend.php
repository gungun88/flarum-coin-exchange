<?php

/*
 * This file is part of doingfb/flarum-coin-exchange.
 *
 * Copyright (c) 2025 DoingFB.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoingFB\CoinExchange;

use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/coin-exchange', 'coin.exchange'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->post('/coin-exchange/convert', 'coin.exchange.convert', Controller\ExchangeController::class),

    (new Extend\Settings)
        ->serializeToForum('coinExchange.apiUrl', 'coin_exchange_api_url')
        ->serializeToForum('coinExchange.apiSecret', 'coin_exchange_api_secret', function ($value) {
            return $value ? '***' : null; // 不要暴露密钥到前端
        })
        ->serializeToForum('coinExchange.dailyLimit', 'coin_exchange_daily_limit', function ($value) {
            return $value ?: 1000;
        })
        ->serializeToForum('coinExchange.enabled', 'coin_exchange_enabled', function ($value) {
            return (bool) $value;
        }),

    // 注册数据库迁移
    (new Extend\Migration())
        ->add(__DIR__.'/migrations/2025_01_12_000000_create_coin_exchange_records_table.php'),
];
