<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ساخت ایمیل رایگان @kadad.ir</title>
    <style>
        @font-face {
            font-family: 'BYekan';
            src: url('B-YEKAN.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'BYekan', 'Tahoma', sans-serif;
            background: url('bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 500px;
            width: 100%;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            padding: 45px 35px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        h1 {
            color: #00ff88;
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
        }

        .subtitle {
            color: #ccc;
            margin-bottom: 35px;
            font-size: 14px;
        }

        .email-example {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 60px;
            padding: 12px 20px;
            margin-bottom: 30px;
            display: inline-block;
        }

        .email-example span {
            color: #00ff88;
            font-size: 20px;
            font-weight: bold;
            direction: ltr;
            display: inline-block;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: right;
        }

        .input-group label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            font-family: 'BYekan', 'Tahoma', sans-serif;
            border: none;
            border-radius: 60px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            outline: none;
            transition: all 0.3s;
            direction: ltr;
            text-align: right;
        }

        .input-group input:focus {
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid #00ff88;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .hint {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 8px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            font-size: 20px;
            font-family: 'BYekan', 'Tahoma', sans-serif;
            font-weight: bold;
            border: none;
            border-radius: 60px;
            background: linear-gradient(45deg, #00ff88, #00b359);
            color: #000;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 15px;
        }

        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(0, 255, 136, 0.3);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .footer {
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
        }

        @media (max-width: 550px) {
            .card { padding: 30px 20px; }
            h1 { font-size: 26px; }
            .email-example span { font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>📧 @kadad.ir</h1>
        <div class="subtitle">ایمیل رایگان با پشتیبانی مادام‌العمر</div>
        
        <div class="email-example">
            <span>yourname@kadad.ir</span>
        </div>
        
        <div class="input-group">
            <label>✨ نام کاربری دلخواه</label>
            <input type="text" id="username" placeholder="مثلاً: kavan, reza, alireza" 
                   pattern="[a-zA-Z0-9_.-]+" title="فقط حروف انگلیسی و اعداد">
            <div class="hint">فقط حروف انگلیسی، اعداد، خط تیره و زیرخط</div>
        </div>
        
        <div class="input-group">
            <label>🔒 رمز عبور (اختیاری)</label>
            <input type="text" id="password" placeholder="خالی بذاری، خودمون رندوم میسازیم">
            <div class="hint">اگر خالی بذاری، یک رمز ۱۰ رقمی رندوم برات ساخته میشه</div>
        </div>
        
        <button class="btn" onclick="sendRequest()">🎯 درخواست ایمیل</button>
        
        <div class="footer">
            با کلیک روی دکمه، ایمیل پیش‌فرض شما باز میشه 🚀
        </div>
    </div>
</div>

<script>
    function generateRandomPassword() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 10; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    function sendRequest() {
        let username = document.getElementById('username').value.trim();
        let password = document.getElementById('password').value.trim();
        
        // اعتبارسنجی نام کاربری
        if (username === '') {
            alert('❌ لطفاً یک نام کاربری وارد کن!');
            return;
        }
        
        // فقط حروف انگلیسی و اعداد و خط تیره و زیرخط
        const validPattern = /^[a-zA-Z0-9_.-]+$/;
        if (!validPattern.test(username)) {
            alert('❌ نام کاربری معتبر نیست! (فقط حروف انگلیسی، اعداد، خط تیره و زیرخط)');
            return;
        }
        
        // اگر پسورد خالی بود، رندوم بساز
        let isRandom = false;
        if (password === '') {
            password = generateRandomPassword();
            isRandom = true;
        }
        
        const passwordText = isRandom ? `پسورد رندوم: ${password}` : `پسورد انتخاب شده: ${password}`;
        
        // ساخت ایمیل
        const to = "info@kadad.ir";
        const subject = encodeURIComponent("ساخت ایمیل رایگان/قیمت پیشنهادی");
        
        const body = encodeURIComponent(
            `سلام و عرض ادب.\n\n` +
            `من یک ایمیل رایگان/قیمت توافقی بنام ${username}@kadad.ir میخواهم.\n` +
            `${passwordText} باشد.\n\n` +
            `با سپاس`
        );
        
        // باز کردن mailto
        window.location.href = `mailto:${to}?subject=${subject}&body=${body}`;
    }
</script>
</body>
</html>