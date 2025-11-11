<?php
/**
 * 商家平台 API 示例代码
 * 文件路径: /api/exchange/coins-to-points.php
 */

// 1. 接收请求
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);
$receivedSignature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

// 2. 配置密钥（与 Flarum 扩展中配置的一致）
$apiSecret = 'E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45';

// 3. 生成签名并验证
function generateSignature($data, $secret) {
    // 按 key 排序
    ksort($data);

    // 拼接字符串
    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[] = "$key=$value";
    }
    $signString = implode('&', $pairs);

    // 添加密钥
    $stringToSign = $signString . '&secret=' . $secret;

    // SHA256 哈希
    return hash('sha256', $stringToSign);
}

$calculatedSignature = generateSignature($data, $apiSecret);

// 4. 验证签名
if ($calculatedSignature !== $receivedSignature) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '签名验证失败',
    ]);
    exit;
}

// 5. 验证时间戳（防止重放攻击）
$timestamp = $data['timestamp'];
$currentTime = round(microtime(true) * 1000);
if (abs($currentTime - $timestamp) > 5 * 60 * 1000) { // 5分钟有效期
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '请求已过期',
    ]);
    exit;
}

// 6. 检查用户是否存在
$userEmail = $data['user_email'];
$user = getUserByEmail($userEmail); // 你的函数

if (!$user) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => '用户不存在，请先注册',
    ]);
    exit;
}

// 7. 检查交易ID是否已存在（防止重复提交）
$transactionId = $data['forum_transaction_id'];
if (transactionExists($transactionId)) { // 你的函数
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '交易已处理',
    ]);
    exit;
}

// 8. 计算积分（1积分 = 10硬币）
$coinAmount = $data['coin_amount'];
$pointsToAdd = $coinAmount / 10;

// 9. 增加用户积分
try {
    // 开始数据库事务
    $pdo->beginTransaction();

    // 增加积分
    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE email = ?");
    $stmt->execute([$pointsToAdd, $userEmail]);

    // 记录兑换历史
    $stmt = $pdo->prepare("
        INSERT INTO coin_exchange_log
        (forum_user_id, forum_transaction_id, user_email, coin_amount, points_amount, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $data['forum_user_id'],
        $transactionId,
        $userEmail,
        $coinAmount,
        $pointsToAdd
    ]);

    // 提交事务
    $pdo->commit();

    // 获取新余额
    $newBalance = getUserPoints($userEmail); // 你的函数

    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => "成功兑换 {$coinAmount} 硬币为 {$pointsToAdd} 积分",
        'data' => [
            'points_added' => $pointsToAdd,
            'new_balance' => $newBalance,
        ]
    ]);

} catch (Exception $e) {
    // 回滚事务
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '兑换失败: ' . $e->getMessage(),
    ]);
}

// 辅助函数示例（需要根据你的数据库实现）
function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function transactionExists($transactionId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coin_exchange_log WHERE forum_transaction_id = ?");
    $stmt->execute([$transactionId]);
    return $stmt->fetchColumn() > 0;
}

function getUserPoints($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT points FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn();
}
