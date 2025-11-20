<?php

namespace DoingFB\CoinExchange\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExchangeController implements RequestHandlerInterface
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
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // 获取当前用户
            $actor = RequestUtil::getActor($request);

            // 检查用户是否已登录
            if ($actor->isGuest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '请先登录',
                ], 401);
            }

            // 检查功能是否启用
            if (!$this->settings->get('coin_exchange_enabled')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '硬币兑换功能未启用',
                ], 403);
            }

            // 获取请求参数
            $requestData = $request->getParsedBody();
            $coinAmount = (int) Arr::get($requestData, 'coinAmount');

            // 验证硬币数量
            if ($coinAmount < 10) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '最少需要兑换 10 硬币',
                ], 400);
            }

            if ($coinAmount % 10 !== 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '硬币数量必须是 10 的倍数',
                ], 400);
            }

            // 检查每日限额
            $dailyLimit = (int) $this->settings->get('coin_exchange_daily_limit', 1000);
            $today = date('Y-m-d');
            $todayExchanged = $this->getTodayExchanged($actor->id, $today);

            if ($todayExchanged + $coinAmount > $dailyLimit) {
                return new JsonResponse([
                    'success' => false,
                    'message' => "超出每日限额，今日已兑换 {$todayExchanged} 硬币",
                ], 400);
            }

            // 检查用户硬币余额
            $userMoney = $actor->money ?? 0;

            if ($userMoney < $coinAmount) {
                return new JsonResponse([
                    'success' => false,
                    'message' => '硬币余额不足',
                ], 400);
            }

            // 生成交易ID（唯一）
            $transactionId = 'tx_' . date('YmdHis') . '_' . $actor->id . '_' . mt_rand(1000, 9999);
            $pointsAmount = $coinAmount / 10;

            // 使用数据库事务确保数据一致性
            $result = DB::transaction(function () use ($actor, $coinAmount, $pointsAmount, $transactionId, $userMoney) {
                // 1. 创建兑换记录（pending 状态）
                $recordId = DB::table('coin_exchange_records')->insertGetId([
                    'user_id' => $actor->id,
                    'transaction_id' => $transactionId,
                    'coin_amount' => $coinAmount,
                    'points_amount' => $pointsAmount,
                    'status' => 'pending',
                    'created_at' => now(),
                ]);

                Log::info('Coin exchange started', [
                    'record_id' => $recordId,
                    'user_id' => $actor->id,
                    'coin_amount' => $coinAmount,
                    'transaction_id' => $transactionId,
                ]);

                // 2. 调用商家平台 API
                $apiResult = $this->callMerchantAPI($actor, $coinAmount, $transactionId);

                if (!$apiResult['success']) {
                    // API 调用失败，更新记录状态
                    DB::table('coin_exchange_records')
                        ->where('id', $recordId)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $apiResult['message'] ?? '未知错误',
                            'completed_at' => now(),
                        ]);

                    Log::error('Coin exchange API failed', [
                        'record_id' => $recordId,
                        'error' => $apiResult['message'] ?? '未知错误',
                    ]);

                    throw new \Exception($apiResult['message'] ?? '兑换失败');
                }

                // 3. 扣除用户硬币
                $newBalance = $userMoney - $coinAmount;
                $actor->money = $newBalance;
                $actor->save();

                // 4. 更新兑换记录为成功
                DB::table('coin_exchange_records')
                    ->where('id', $recordId)
                    ->update([
                        'status' => 'success',
                        'merchant_response' => json_encode($apiResult),
                        'completed_at' => now(),
                    ]);

                Log::info('Coin exchange completed successfully', [
                    'record_id' => $recordId,
                    'user_id' => $actor->id,
                    'new_balance' => $newBalance,
                ]);

                return [
                    'success' => true,
                    'message' => "成功兑换 {$coinAmount} 硬币为 {$pointsAmount} 积分",
                    'data' => [
                        'coinAmount' => $coinAmount,
                        'pointsAmount' => $pointsAmount,
                        'remainingCoins' => $newBalance,
                        'transactionId' => $transactionId,
                    ],
                ];
            });

            // 返回 JSON 响应
            return new JsonResponse($result, 200);

        } catch (\Exception $e) {
            Log::error('Coin exchange handler failed', [
                'user_id' => $actor->id ?? 'guest',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 返回错误响应
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
     */
    protected function getTodayExchanged($userId, $date)
    {
        $startOfDay = $date . ' 00:00:00';
        $endOfDay = $date . ' 23:59:59';

        $result = DB::table('coin_exchange_records')
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('coin_amount');

        return (int) $result;
    }
}
