<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>مافیا - سیستم مدیریت بازی</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .mode-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin-bottom: 50px;
        }
        .mode-card {
            background: rgba(45, 52, 54, 0.95);
            border-radius: 25px;
            padding: 30px 25px;
            width: 300px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #636e72;
            text-align: center;
        }
        .mode-card:hover {
            transform: translateY(-10px);
            border-color: #f9ca24;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .mode-icon { font-size: 4em; margin-bottom: 20px; }
        .mode-card h2 { font-size: 1.8em; margin-bottom: 15px; color: #f9ca24; }
        .mode-card p { color: #dfe6e9; line-height: 1.6; margin-bottom: 20px; font-size: 0.9em; }
        .mode-badge {
            display: inline-block;
            background: #00b894;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .mode-card.offline .mode-badge { background: #0984e3; }
        .mode-card.online .mode-badge { background: #e84118; }
        .features {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #636e72;
        }
        .features h3 { color: #f9ca24; margin-bottom: 20px; }
        .features-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .feature {
            background: rgba(0,0,0,0.5);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.85em;
        }
        @media (max-width: 700px) {
            .mode-card { width: 260px; padding: 20px; }
            .mode-icon { font-size: 3em; }
            .mode-card h2 { font-size: 1.5em; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🎭 سیستم مدیریت بازی مافیا</h1>
    <div class="subtitle" style="text-align:center; margin-bottom:50px;">حرفه‌ای بازی کنید | راوی هوشمند | نقش‌های متنوع</div>
    
    <div class="mode-cards">
        <div class="mode-card offline" onclick="location.href='offline/mafia_offline.html'">
            <div class="mode-icon">📱</div>
            <h2>مود آفلاین</h2>
            <p>مناسب برای دورهمی‌های دوستانه<br>راوی با تبلت بازی را مدیریت می‌کند<br>بدون نیاز به اینترنت</p>
            <div class="mode-badge">✅ مناسب برای مهمانی‌ها</div>
        </div>
        
        <div class="mode-card online" onclick="location.href='online/index.php'">
            <div class="mode-icon">🌐</div>
            <h2>مود آنلاین</h2>
            <p>بازی با دوستان از راه دور<br>هر بازیکن با موبایل خودش وارد می‌شود<br>قابلیت چت و پیام‌رسانی</p>
            <div class="mode-badge">🎮 حداکثر ۱۰ بازیکن</div>
        </div>
    </div>
    
    <div class="features">
        <h3>✨ امکانات بازی</h3>
        <div class="features-grid">
            <div class="feature">⚡ مود پیشرفته</div>
            <div class="feature">🕵️ ۸ نقش مختلف</div>
            <div class="feature">🗳️ رأی‌گیری دو مرحله‌ای</div>
            <div class="feature">🌙 شب‌های ماه کامل</div>
            <div class="feature">🧛 تیم ومپایر</div>
            <div class="feature">🎭 جوکر ویژه</div>
            <div class="feature">💬 گفتگوی صحبت</div>
            <div class="feature">📱 کاملاً ریسپانسیو</div>
        </div>
    </div>
</div>
</body>
</html>