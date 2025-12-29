# حل مشكلة Cross-Site Cookies (401 Unauthorized)

## المشكلة
الـ cookies يتم إرسالها في Response Headers مع `samesite=lax`، لكن المتصفح لا يرسلها في Request Headers التالية لأن:
- Request من `http://localhost:5174` إلى `http://127.0.0.1:8000` يعتبر **cross-site request**
- `SameSite=Lax` يمنع إرسال cookies في cross-site requests

## الحل المطبق

### 1. تغيير SameSite إلى None
في `config/session.php`:
```php
'same_site' => env('SESSION_SAME_SITE', 'none'),
'secure' => env('SESSION_SECURE_COOKIE', false), // false للتطوير المحلي
```

### 2. إضافة Web Middleware إلى API Routes
في `bootstrap/app.php`:
```php
$middleware->web(append: [
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
]);
```

## خطوات التحقق

### 1. امسح Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. أعد تشغيل Backend
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

### 3. امسح Cookies في المتصفح
- Developer Tools (F12)
- Application > Cookies
- احذف جميع cookies
- أعد تحميل الصفحة

### 4. اختبر تسجيل الدخول
1. افتح `/login`
2. سجل دخول
3. في Network Tab، تحقق من:
   - Request إلى `/api/auth/login`: Response Headers يجب أن تحتوي على `Set-Cookie: laravel-session=...; SameSite=None`
   - Request إلى `/api/user`: Request Headers يجب أن تحتوي على `Cookie: laravel-session=...`

## ملاحظات مهمة

### SameSite=None يتطلب Secure=true في Production
في الإنتاج، يجب أن يكون:
```php
'same_site' => 'none',
'secure' => true, // HTTPS required
```

### للتطوير المحلي
يمكنك استخدام:
```php
'same_site' => 'none',
'secure' => false, // للتطوير المحلي فقط
```

### بديل: استخدام نفس الـ Domain
بدلاً من `SameSite=None`، يمكنك:
1. استخدام `localhost` للـ frontend والـ backend:
   - Frontend: `http://localhost:5174`
   - Backend: `http://localhost:8000`
2. أو استخدام `127.0.0.1` للكل:
   - Frontend: `http://127.0.0.1:5174`
   - Backend: `http://127.0.0.1:8000`

## التحقق من الحل

بعد تطبيق الحل، يجب أن ترى في Network Tab:

### Response Headers (من `/api/auth/login`):
```
Set-Cookie: laravel-session=...; SameSite=None; Path=/
Set-Cookie: XSRF-TOKEN=...; SameSite=None; Path=/
```

### Request Headers (إلى `/api/user`):
```
Cookie: laravel-session=...; XSRF-TOKEN=...
```

## إذا استمرت المشكلة

### الحل البديل: استخدام Token-based Auth
إذا استمرت مشاكل الـ cookies، يمكنك استخدام token-based authentication:

```php
// في AuthController
$token = $user->createToken('auth-token')->plainTextToken;
return $this->success([
    'user' => $user,
    'token' => $token,
]);
```

ثم في Frontend:
```typescript
headers: {
    'Authorization': `Bearer ${token}`,
}
```

