# حل مشكلة 419 CSRF Token Mismatch

## المشكلة
عند محاولة تسجيل الدخول، يظهر خطأ:
```
POST http://127.0.0.1:8000/api/auth/login 419 (unknown status)
```

## السبب
خطأ 419 يعني "Page Expired" وهو يحدث عندما:
1. CSRF token غير موجود أو منتهي الصلاحية
2. CSRF middleware يتحقق من token لكنه غير موجود في الطلب
3. API routes لا يتم استثناؤها من CSRF verification بشكل صحيح

## الحل المطبق

### 1. استثناء API Routes من CSRF
في `bootstrap/app.php`:
```php
$middleware->validateCsrfTokens(except: [
    'api/*',
    'sanctum/csrf-cookie',
]);
```

### 2. إضافة CSRF Cookie Route
في `routes/web.php`:
```php
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
```

### 3. تحديث Frontend
- الحصول على CSRF cookie قبل login
- إضافة `X-Requested-With: XMLHttpRequest` header

## خطوات التحقق

### 1. امسح Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. تحقق من Network Tab
في Browser Developer Tools:
1. Request إلى `/sanctum/csrf-cookie` - يجب أن يكون 200
2. تحقق من Cookies - يجب أن ترى `XSRF-TOKEN` cookie
3. Request إلى `/api/auth/login` - يجب أن يكون 200 (وليس 419)

### 3. تحقق من Cookies
في Application > Cookies:
- `XSRF-TOKEN` - يجب أن يكون موجود
- `laravel_session` - يجب أن يكون موجود بعد login

## إذا استمرت المشكلة

### الحل البديل: تعطيل CSRF للـ API Routes بالكامل

إذا استمرت المشكلة، يمكنك تعطيل CSRF للـ API routes:

في `bootstrap/app.php`:
```php
$middleware->validateCsrfTokens(except: [
    'api/*',
    'sanctum/csrf-cookie',
    '*', // تعطيل CSRF للكل (غير موصى به للإنتاج)
]);
```

**ملاحظة**: هذا الحل غير موصى به للإنتاج، لكنه قد يكون مفيد للتطوير.

## ملاحظات مهمة

1. **CSRF Cookie Route**: يجب أن يكون في `web.php` وليس `api.php`
2. **API Routes**: يجب استثناؤها من CSRF verification
3. **Sanctum**: يستخدم CSRF token تلقائياً عند استخدام session-based auth
4. **Headers**: يجب إضافة `X-Requested-With: XMLHttpRequest` في جميع requests



