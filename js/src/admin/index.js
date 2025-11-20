import app from 'flarum/admin/app';

app.initializers.add('doingfb/flarum-coin-exchange', () => {
  app.extensionData
    .for('doingfb-coin-exchange')
    .registerSetting({
      setting: 'coin_exchange_enabled',
      label: app.translator.trans('doingfb-coin-exchange.admin.settings.enabled_label'),
      help: app.translator.trans('doingfb-coin-exchange.admin.settings.enabled_help'),
      type: 'boolean',
    })
    .registerSetting({
      setting: 'coin_exchange_api_url',
      label: app.translator.trans('doingfb-coin-exchange.admin.settings.api_url_label'),
      help: app.translator.trans('doingfb-coin-exchange.admin.settings.api_url_help'),
      type: 'text',
      placeholder: 'https://your-merchant-platform.com/api/exchange/coins-to-points',
    })
    .registerSetting({
      setting: 'coin_exchange_api_secret',
      label: app.translator.trans('doingfb-coin-exchange.admin.settings.api_secret_label'),
      help: app.translator.trans('doingfb-coin-exchange.admin.settings.api_secret_help'),
      type: 'text',
      placeholder: 'E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45',
    })
    .registerSetting({
      setting: 'coin_exchange_daily_limit',
      label: app.translator.trans('doingfb-coin-exchange.admin.settings.daily_limit_label'),
      help: app.translator.trans('doingfb-coin-exchange.admin.settings.daily_limit_help'),
      type: 'number',
      placeholder: '1000',
    });
});
