<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>بازی توقف روی ۱۰ ثانیه | چالش زمان</title>
    <style>
        @font-face {
            font-family: 'BYekan';
            src: url('B-YEKAN.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'BYekan', 'Tahoma', sans-serif;
            background: url('bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* لایه تاریک برای خوانایی متن */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1;
        }

        .game-container {
            position: relative;
            z-index: 2;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.2);
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #ffd700;
            font-size: 28px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(255,215,0,0.5);
        }

        .timer {
            font-size: 120px;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 0 20px #00ff00;
            font-family: monospace;
            margin: 30px 0;
            letter-spacing: 5px;
        }

        .btn {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            padding: 18px 40px;
            font-size: 24px;
            font-family: 'BYekan', 'Tahoma', sans-serif;
            font-weight: bold;
            color: white;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            width: 80%;
            max-width: 250px;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .win-btn {
            background: linear-gradient(45deg, #00b894, #00cec9);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 5px 20px rgba(0,184,148,0.5); }
            100% { transform: scale(1.05); box-shadow: 0 5px 30px rgba(0,184,148,0.8); }
        }

        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 15px;
            font-size: 16px;
            display: none;
        }

        .success {
            background: rgba(0,184,148,0.3);
            color: #00b894;
            border: 1px solid #00b894;
        }

        .error {
            background: rgba(255,107,107,0.3);
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
        }

        .info {
            color: #aaa;
            margin-top: 20px;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .timer { font-size: 80px; }
            .btn { font-size: 20px; padding: 14px 30px; }
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="overlay"></div>
<div class="game-container">
    <h1>⏱️ چالش توقف روی ۱۰ ثانیه 🎯</h1>
    <div class="timer" id="timer">0.00</div>
    
    <button class="btn" id="actionBtn">🚀 شروع</button>
    
    <div class="message" id="message"></div>
    <div class="info">
        💡 قوانین: دکمه شروع رو بزن، زمان از صفر شروع به افزایش میکنه.<br>
        🎯 باید دقیقاً روی <strong style="color:#ffd700">۱۰.۰۰ ثانیه</strong> دکمه رو بزنی!
    </div>
</div>

<script>
    let isRunning = false;
    let startTime = 5;
    let timerInterval = null;
    let winEmailShown = false;

    const timerElement = document.getElementById('timer');
    const actionBtn = document.getElementById('actionBtn');
    const messageDiv = document.getElementById('message');

    function formatTime(seconds) {
        return seconds.toFixed(2);
    }

    function updateTimerDisplay() {
        if (!isRunning) return;
        const now = performance.now();
        const elapsed = (now - startTime) / 1000;
        timerElement.textContent = formatTime(elapsed);
        
        // محدودیت 15 ثانیه برای جلوگیری از بی‌نظمی
        if (elapsed >= 15) {
            stopGame(false, '⏰ زمان از ۱۵ ثانیه گذشت! دوباره تلاش کن.');
        }
    }

    function stopGame(isWin, customMessage = null) {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        isRunning = false;
        
        if (isWin && !winEmailShown) {
            winEmailShown = true;
            timerElement.textContent = "🎉 برد! 🎉";
            actionBtn.style.display = 'none';
            showWinButton();
            showMessage('✅ عالی! روی ۱۰ ثانیه زدی! حالا جایزه بگیر.', 'success');
        } else if (!isWin && customMessage) {
            showMessage(customMessage, 'error');
            actionBtn.textContent = '🚀 شروع مجدد';
        } else if (!isWin) {
            showMessage('❌ دقیق روی ۱۰ ثانیه نزدی! دوباره تلاش کن.', 'error');
            actionBtn.textContent = '🚀 شروع مجدد';
        }
    }

    function showWinButton() {
        const winBtn = document.createElement('button');
        winBtn.className = 'btn win-btn';
        winBtn.id = 'claimBtn';
        winBtn.innerHTML = '🏆 دریافت جایزه 🏆';
        winBtn.style.marginTop = '20px';
        
        winBtn.onclick = async function() {
            winBtn.disabled = true;
            winBtn.innerHTML = '🔄 در حال ارسال...';
            
            try {
                window.open("data:text/html,<h1>info@kadad.ir</h1>");
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('اسکرین شات بفرستین', 'success');
                    winBtn.remove();
                } else {
                    showMessage('❌ خطا در ارسال ایمیل: ' + result.error, 'error');
                    winBtn.disabled = false;
                    winBtn.innerHTML = '🏆 دریافت جایزه 🏆';
                }
            } catch (err) {
                showMessage('❌ خطای شبکه! دوباره تلاش کن.', 'error');
                winBtn.disabled = false;
                winBtn.innerHTML = '🏆 دریافت جایزه 🏆';
            }
        };
        
        actionBtn.parentNode.insertBefore(winBtn, actionBtn.nextSibling);
    }

    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    }

    actionBtn.onclick = function() {
        if (isRunning) {
            // بازیکن میخواد متوقف کنه - بررسی برد
            const currentTime = parseFloat(timerElement.textContent);
            const isWin = Math.abs(currentTime - 10.00) < 0.07; // دقت 0.01 ثانیه
            
            if (isWin) {
                stopGame(true);
            } else {
                stopGame(false);
            }
        } else {
            // شروع بازی جدید
            if (timerInterval) clearInterval(timerInterval);
            isRunning = true;
            winEmailShown = false;
            startTime = performance.now();
            timerElement.textContent = "0.00";
            actionBtn.textContent = "🛑 توقف";
            messageDiv.style.display = 'none';
            
            timerInterval = setInterval(() => {
                if (isRunning) {
                    const now = performance.now();
                    const elapsed = (now - startTime) / 1000;
                    timerElement.textContent = formatTime(elapsed);
                    
                    if (elapsed >= 15) {
                        stopGame(false, '⏰ زمان از ۱۵ ثانیه گذشت! دوباره تلاش کن.');
                    }
                }
            }, 10); // آپدیت هر 10 میلی‌ثانیه برای دقت بالا
        }
    };
</script>
</body>
</html>