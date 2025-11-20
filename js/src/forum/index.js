import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserControls from 'flarum/forum/utils/UserControls';
import UserPage from 'flarum/forum/components/UserPage';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import CoinExchangeModal from './components/CoinExchangeModal';

app.initializers.add('doingfb/flarum-coin-exchange', () => {
  // 方法1: 在用户下拉菜单中添加
  extend(UserControls, 'userControls', function (items, user) {
    if (app.session.user && app.session.user.id() === user.id()) {
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

  // 方法2: 在个人主页导航栏中添加（使用 LinkButton 以保持样式一致）
  extend(UserPage.prototype, 'navItems', function (items) {
    const user = this.user;

    // 只为当前登录用户的个人主页显示
    if (app.session.user && user && app.session.user.id() === user.id()) {
      if (app.forum.attribute('coinExchange.enabled')) {
        items.add(
          'coin-exchange',
          LinkButton.component(
            {
              href: '#',
              icon: 'fas fa-coins',
              onclick: (e) => {
                e.preventDefault();
                app.modal.show(CoinExchangeModal);
              },
            },
            app.translator.trans('doingfb-coin-exchange.forum.user_controls.coin_exchange_button')
          ),
          80 // 优先级
        );
      }
    }
  });
});
