import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';
import CoinExchangeModal from './components/CoinExchangeModal';

app.initializers.add('doingfb/flarum-coin-exchange', () => {
  // 在用户菜单中添加"硬币兑换"选项
  extend(UserControls, 'userControls', function (items, user) {
    // 只为当前登录用户显示
    if (app.session.user && app.session.user.id() === user.id()) {
      // 检查功能是否启用
      if (app.forum.attribute('coinExchange.enabled')) {
        items.add(
          'coin-exchange',
          Button.component(
            {
              icon: 'fas fa-coins',
              onclick: () => {
                app.modal.show(CoinExchangeModal);
              },
            },
            app.translator.trans('doingfb-coin-exchange.forum.user_controls.coin_exchange_button')
          )
        );
      }
    }
  });

  // 也可以在页面顶部添加快捷入口（可选）
  // extend(HeaderPrimary.prototype, 'items', function (items) {
  //   if (app.session.user && app.forum.attribute('coinExchange.enabled')) {
  //     items.add(
  //       'coin-exchange',
  //       Button.component({
  //         className: 'Button Button--link',
  //         icon: 'fas fa-coins',
  //         onclick: () => {
  //           app.modal.show(CoinExchangeModal);
  //         },
  //       }, '兑换积分'),
  //       10
  //     );
  //   }
  //});
});
