# حل مشكلة 401 Unauthorized بعد تسجيل الدخول

## المشكلة
بعد تسجيل الدخول بنجاح، عند محاولة الوصول إلى `/api/user` يظهر خطأ 401 Unauthorized.

## الأسباب المحتملة

### 1. CORS Configuration
- يجب أن يكون `127.0.0.1:5173` في `allowed_origins`
- يجب أن يكون `supports_credentials => true`

### 2. Sanctum Stateful Domains
- يجب أن يكون `127.0.0.1:5173` في `stateful` domains في `config/sanctum.php`

### 3. Session Configuration
- تأكد من أن `SESSION_SAME_SITE` = `lax` (ليس `strict`)
- تأكد من أن `SESSION_DOMAIN` = `null` (للـ localhost)

### 4. CSRF Cookie
- يجب الحصول على CSRF cookie قبل تسجيل الدخول
- Route `/sanctum/csrf-cookie` يجب أن يكون في `web.php` وليس `api.php`

## الحلول المطبقة

### 1. تحديث CORS (`config/cors.php`)
```php
'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:5174',
    'http://127.0.0.1:5174',
    'http://localhost:4173',
    'http://127.0.0.1:4173',
    'https://bookinghotelsinfo.netlify.app',
],
'supports_credentials' => true,
```

### 2. تحديث Sanctum (`config/sanctum.php`)
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:5173,localhost:5174,localhost:4173,127.0.0.1,127.0.0.1:8000,127.0.0.1:5173,127.0.0.1:5174,127.0.0.1:4173,::1',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

### 3. إضافة CSRF Cookie Route (`routes/web.php`)
```php
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
```

### 4. تحديث Frontend
- إضافة `getCsrfCookie()` قبل login
- إضافة `credentials: 'include'` في جميع requests
- إضافة `X-Requested-With: XMLHttpRequest` header

## خطوات التحقق

### 1. تحقق من Cookies
افتح Developer Tools > Application > Cookies:
- يجب أن ترى `laravel_session` cookie
- يجب أن ترى `XSRF-TOKEN` cookie

### 2. تحقق من Network Requests
في Developer Tools > Network:
1. Request إلى `/sanctum/csrf-cookie` - يجب أن يكون 200
2. Request إلى `/api/auth/login` - يجب أن يكون 200
3. Request إلى `/api/user` - يجب أن يكون 200 (وليس 401)

### 3. تحقق من Response Headers
في Network tab، تحقق من Response Headers:
- `Set-Cookie: laravel_session=...` (يجب أن يكون موجود)
- `Access-Control-Allow-Credentials: true`
- `Access-Control-Allow-Origin: http://127.0.0.1:5173`

## حلول إضافية إذا استمرت المشكلة

### 1. امسح Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. تحقق من .env
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 3. تحقق من Database
```bash
php artisan migrate
```

### 4. تحقق من Session Table
```sql
SELECT * FROM sessions ORDER BY last_activity DESC LIMIT 5;
```

## اختبار يدوي في Browser Console

```javascript
// 1. Get CSRF cookie
fetch('http://127.0.0.1:8000/sanctum/csrf-cookie', {
  method: 'GET',
  credentials: 'include'
}).then(r => console.log('CSRF:', r.ok));

// 2. Login
fetch('http://127.0.0.1:8000/api/auth/login', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  body: JSON.stringify({
    email: 'admin@test.com',
    password: 'password123'
  })
}).then(r => r.json()).then(d => console.log('Login:', d));

// 3. Get user (should work now)
fetch('http://127.0.0.1:8000/api/user', {
  method: 'GET',
  credentials: 'include',
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
}).then(r => r.json()).then(d => console.log('User:', d));
```

## ملاحظات مهمة

1. **CSRF Cookie Route**: يجب أن يكون في `web.php` وليس `api.php` لأن Sanctum يحتاج web middleware
2. **Session Domain**: يجب أن يكون `null` للـ localhost
3. **Same-Site**: يجب أن يكون `lax` وليس `strict` للسماح بـ cross-site requests
4. **Credentials**: يجب أن يكون `include` في جميع الـ fetch requests


