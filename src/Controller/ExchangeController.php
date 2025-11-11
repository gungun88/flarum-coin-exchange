<?php

namespace DoingFB\CoinExchange\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ExchangeController extends AbstractShowController
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        // 获取当前用户
        $actor = RequestUtil::getActor($request);

        // 检查用户是否已登录
        if ($actor->isGuest()) {
            throw new PermissionDeniedException('请先登录');
        }

        // 检查功能是否启用
        if (!$this->settings->get('coin_exchange_enabled')) {
            throw new \Exception('硬币兑换功能未启用');
        }

        // 获取请求参数
        $requestData = $request->getParsedBody();
        $coinAmount = (int) Arr::get($requestData, 'coinAmount');

        // 验证硬币数量
        if ($coinAmount < 10) {
            throw new \Exception('最少需要兑换 10 硬币');
        }

        if ($coinAmount % 10 !== 0) {
            throw new \Exception('硬币数量必须是 10 的倍数');
        }

        // 检查每日限额
        $dailyLimit = (int) $this->settings->get('coin_exchange_daily_limit', 1000);
        $today = date('Y-m-d');

        // TODO: 从数据库查询今日已兑换数量
        $todayExchanged = $this->getTodayExchanged($actor->id, $today);

        if ($todayExchanged + $coinAmount > $dailyLimit) {
            throw new \Exception("超出每日限额，今日已兑换 {$todayExchanged} 硬币");
        }

        // 检查用户硬币余额
        // 假设 Flarum 的硬币存储在 users 表的 money 字段
        $userMoney = $actor->money ?? 0;

        if ($userMoney < $coinAmount) {
            throw new \Exception('硬币余额不足');
        }

        // 生成交易ID（唯一）
        $transactionId = 'tx_' . date('YmdHis') . '_' . $actor->id . '_' . mt_rand(1000, 9999);

        // 调用商家平台 API
        $result = $this->callMerchantAPI($actor, $coinAmount, $transactionId);

        if (!$result['success']) {
            throw new \Exception($result['message'] ?? '兑换失败');
        }

        // 扣除用户硬币
        $actor->money = $userMoney - $coinAmount;
        $actor->save();

        // 记录兑换历史（可选）
        // TODO: 保存到本地数据库表

        return [
            'success' => true,
            'message' => "成功兑换 {$coinAmount} 硬币为 " . ($coinAmount / 10) . " 积分",
            'data' => [
                'coinAmount' => $coinAmount,
                'pointsAmount' => $coinAmount / 10,
                'remainingCoins' => $actor->money,
                'transactionId' => $transactionId,
            ],
        ];
    }

    /**
     * 调用商家平台 API
     */
    protected function callMerchantAPI($user, $coinAmount, $transactionId)
    {
        $apiUrl = $this->settings->get('coin_exchange_api_url');
        $apiSecret = $this->settings->get('coin_exchange_api_secret');

        if (!$apiUrl || !$apiSecret) {
            throw new \Exception('API 配置不完整');
        }

        // 准备请求数据
        $timestamp = round(microtime(true) * 1000); // 毫秒时间戳
        $requestData = [
            'forum_user_id' => (string) $user->id,
            'forum_transaction_id' => $transactionId,
            'user_email' => $user->email,
            'coin_amount' => $coinAmount,
            'timestamp' => $timestamp,
        ];

        // 生成签名
        $signature = $this->generateSignature($requestData, $apiSecret);

        // 发送 HTTP 请求
        try {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Signature: ' . $signature,
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception('网络请求失败: ' . $error);
            }

            $result = json_decode($response, true);

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? $result['error'] ?? '兑换失败',
                ];
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '请求失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 生成 API 签名
     */
    protected function generateSignature($data, $secret)
    {
        // 1. 按 key 排序
        ksort($data);

        // 2. 拼接字符串
        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = "$key=$value";
        }
        $signString = implode('&', $pairs);

        // 3. 添加密钥
        $stringToSign = $signString . '&secret=' . $secret;

        // 4. SHA256 哈希
        return hash('sha256', $stringToSign);
    }

    /**
     * 获取今日已兑换数量
     * TODO: 从数据库查询
     */
    protected function getTodayExchanged($userId, $date)
    {
        // 这里需要查询数据库
        // 暂时返回 0
        return 0;
    }
}
