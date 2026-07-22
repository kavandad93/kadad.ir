<?php
header('Content-Type: application/json');

// فقط درخواست POST قبول کن
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit;
}

// دریافت دیتا
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action']) || $data['action'] !== 'claim_prize') {
    echo json_encode(['success' => false, 'error' => 'درخواست نامعتبر']);
    exit;
}

// کد مخفی - این هیچوقت توی فرانت دیده نمیشه!
$secret_code = "pa55_1s_n0t_very_5ec";

// اطلاعات ایمیل
$to = "ticket@kadad.ir";
$subject = "من برنده بازی شدم!، کد: [$secret_code] است";
$message = "یک کاربر در بازی ۱۰ ثانیه برنده شد.\n\n";
$message .= "زمان: " . date('Y-m-d H:i:s') . "\n";
$message .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$message .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'نامشخص') . "\n";
$message .= "کد تأیید: $secret_code\n";
$headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
$headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ارسال ایمیل
$mail_sent = mail($to, $subject, $message, $headers);

if ($mail_sent) {
    echo json_encode(['success' => true, 'message' => 'ایمیل ارسال شد']);
} else {
    // اگه mail روی هاست کار نکنه، لاگ کن (اختیاری)
    error_log("Email failed to send to $to from game winner");
    echo json_encode(['success' => false, 'error' => 'خطا در ارسال ایمیل. بعداً تلاش کن']);
}
?>