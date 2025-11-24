import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export default class CoinExchangeModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    this.coinAmount = 100; // 默认兑换 100 硬币
    this.loading = false;
    this.error = null;
    this.success = null;

    // 每日限额
    this.dailyLimit = app.forum.attribute('coinExchange.dailyLimit') || 1000;

    // 获取用户当前硬币余额（假设存储在 user.data.attributes.money）
    this.userMoney = app.session.user.data.attributes.money || 0;
  }

  className() {
    return 'CoinExchangeModal Modal--small';
  }

  title() {
    return app.translator.trans('doingfb-coin-exchange.forum.modal.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <div className="Alert Alert--success">
              <p>{this.success}</p>
            </div>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={() => app.modal.close()}>
                {app.translator.trans('doingfb-coin-exchange.forum.modal.close')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          {/* 说明 */}
          <div className="helpText" style="margin-bottom: 20px;">
            <p><strong>兑换比例:</strong> 1 积分 = 10 硬币</p>
            <p><strong>每日限额:</strong> {this.dailyLimit} 硬币</p>
            <p><strong>当前余额:</strong> {this.userMoney} 硬币</p>
          </div>

          {/* 错误提示 */}
          {this.error && (
            <div className="Alert Alert--error">
              <p>{this.error}</p>
            </div>
          )}

          {/* 输入框 */}
          <div className="Form-group">
            <label>{app.translator.trans('doingfb-coin-exchange.forum.modal.coin_amount_label')}</label>
            <input
              className="FormControl"
              type="number"
              min="10"
              step="10"
              value={this.coinAmount}
              oninput={(e) => {
                this.coinAmount = parseInt(e.target.value) || 0;
                this.error = null;
              }}
              disabled={this.loading}
            />
            <p className="helpText">
              最少 10 硬币，必须是 10 的倍数。将获得 <strong>{this.coinAmount / 10}</strong> 积分
            </p>
          </div>

          {/* 按钮 */}
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block"
              type="submit"
              loading={this.loading}
              disabled={this.loading || this.coinAmount < 10}
            >
              {app.translator.trans('doingfb-coin-exchange.forum.modal.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    // 验证
    if (this.coinAmount < 10) {
      this.error = '最少需要兑换 10 硬币';
      m.redraw();
      return;
    }

    if (this.coinAmount % 10 !== 0) {
      this.error = '硬币数量必须是 10 的倍数';
      m.redraw();
      return;
    }

    if (this.coinAmount > this.userMoney) {
      this.error = '硬币余额不足';
      m.redraw();
      return;
    }

    if (this.coinAmount > this.dailyLimit) {
      this.error = `超出每日限额 (${this.dailyLimit} 硬币)`;
      m.redraw();
      return;
    }

    // 发送请求
    this.loading = true;
    this.error = null;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/coin-exchange/convert',
        body: {
          coinAmount: this.coinAmount,
        },
      })
      .then((response) => {
        // 成功
        this.success = response.message || '兑换成功！';
        this.loading = false;

        // 更新用户硬币余额
        if (response.data && response.data.remainingCoins !== undefined) {
          app.session.user.data.attributes.money = response.data.remainingCoins;
        }

        m.redraw();
      })
      .catch((error) => {
        // 失败
        this.loading = false;

        // 尝试从不同位置获取错误消息
        let errorMessage = '兑换失败，请稍后再试';

        if (error.response) {
          // HTTP 错误响应
          if (error.response.data?.message) {
            errorMessage = error.response.data.message;
          } else if (error.response.message) {
            errorMessage = error.response.message;
          } else if (typeof error.response.data === 'string') {
            errorMessage = error.response.data;
          }
        } else if (error.message) {
          // 网络错误或其他错误
          errorMessage = error.message;
        }

        this.error = errorMessage;
        m.redraw();
      });
  }
}
