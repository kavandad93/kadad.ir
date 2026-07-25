<?php
require __DIR__ . '/api/bootstrap.php';
$installed = isset($_COOKIE['mmap_installed']) || ($_GET['app'] ?? '') === '1';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#111827">
  <title>میمَپ | نقشه زنده قرارها</title>
  <link rel="manifest" href="manifest.php">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= $installed ? 'app-mode' : 'landing-mode' ?>">
  <main id="landing" class="landing <?= $installed ? 'hidden' : '' ?>">
    <section class="hero">
      <div class="hero-copy">
        <span class="badge">PWA قابل نصب بدون ورود اجباری</span>
        <h1>میمَپ؛ پنل نقشه، چت و رویدادهای زنده برای گروه‌های واقعی</h1>
        <p>وقتی نصب نشده باشد همین معرفی کامل نمایش داده می‌شود؛ بعد از نصب یا ورود به حالت اپ، صفحه اصلی یک نقشه Leaflet است که افراد، قرارها، علاقه‌مندی‌ها و پیام‌ها را real-time نشان می‌دهد.</p>
        <div class="hero-actions">
          <button id="installBtn" class="primary">نصب اپلیکیشن</button>
          <button id="openAppBtn" class="secondary">مشاهده نسخه اپ</button>
        </div>
      </div>
      <div class="feature-grid">
        <article>اشتراک لوکیشن با نام مهمان، بدون لاگین</article>
        <article>ثبت نقطه با کلیک روی نقشه و نوتیفیکیشن رسیدن</article>
        <article>چت خصوصی/گروهی، سین، بلاک و دوست‌یابی</article>
        <article>ثبت‌نام رایگان برای fav، گروه، رویداد خصوصی و امکانات بیشتر</article>
      </div>
    </section>
  </main>

  <main id="app" class="shell <?= $installed ? '' : 'hidden' ?>">
    <aside class="panel profile-panel">
      <h2>پروفایل</h2>
      <input id="displayName" placeholder="نام شما قبل از شیر لوکیشن">
      <div class="auth-row">
        <input id="email" placeholder="ایمیل اختیاری برای ثبت‌نام رایگان">
        <button id="loginBtn">ورود/ثبت‌نام</button>
      </div>
      <button id="shareLocationBtn" class="primary wide">اشتراک لوکیشن زنده</button>
      <button id="saveFavBtn" class="wide">ذخیره نقطه انتخابی در علاقه‌مندی‌ها</button>
      <h3>علاقه‌مندی‌ها</h3>
      <div id="favorites" class="list"></div>
      <h3>دوستان</h3>
      <div class="inline"><input id="friendName" placeholder="نام/اکانت دوست"><button id="friendBtn">درخواست</button></div>
      <div id="friends" class="list"></div>
    </aside>

    <section class="map-wrap">
      <div id="map"></div>
      <button id="chatFab" class="chat-fab">💬</button>
      <div id="eventComposer" class="composer hidden">
        <h3>ثبت/ویرایش قرار</h3>
        <input id="eventTitle" placeholder="نام قرار یا نقطه">
        <textarea id="eventNote" placeholder="توضیح، تغییرات یا قوانین حضور"></textarea>
        <input id="eventAllowed" placeholder="اسامی/اکانت‌های مجاز، با ویرگول (اختیاری)">
        <label><input id="eventGroupOnly" type="checkbox"> رویداد گروهی محدود</label>
        <button id="saveEventBtn" class="primary">ذخیره روی نقشه</button>
      </div>
    </section>

    <aside id="chatPanel" class="panel chat-panel">
      <div class="chat-head"><h2>چت</h2><button id="closeChat">×</button></div>
      <div class="tabs"><button data-room="global" class="active">عمومی</button><button data-room="private">خصوصی</button><button data-room="group">گروه</button></div>
      <div class="inline"><input id="roomName" placeholder="نام دوست/گروه"><button id="createGroupBtn">ساخت گروه</button></div>
      <div id="messages" class="messages"></div>
      <div class="inline"><input id="messageText" placeholder="پیام..."><button id="sendBtn">ارسال</button></div>
      <button id="blockBtn" class="danger">بلاک مخاطب فعلی</button>
    </aside>
  </main>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="assets/app.js"></script>
</body>
</html>
