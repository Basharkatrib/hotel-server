# حل مشكلة 401 Unauthorized بعد Login

## المشكلة
بعد تسجيل الدخول بنجاح، عند محاولة الوصول إلى `/api/user` يظهر خطأ:
```
GET http://127.0.0.1:8000/api/user 401 (Unauthorized)
```

## الأسباب المحتملة

1. **Session لا يتم حفظها بشكل صحيح**
2. **Cookies لا يتم إرسالها مع الطلبات**
3. **EnsureFrontendRequestsAreStateful middleware لا يعمل**
4. **Same-Site cookie policy يمنع إرسال cookies**

## الحلول المطبقة

### 1. تحديث Session Same-Site
في `config/session.php`:
```php
'same_site' => env('SESSION_SAME_SITE', null), // Changed from 'lax' to null
```

### 2. تحسين Login Method
في `app/Http/Controllers/Api/AuthController.php`:
```php
Auth::login($user, $request->has('remember'));
$request->session()->save(); // Ensure session is saved
```

### 3. تحسين User Method
محاولة الحصول على المستخدم من عدة guards:
- `sanctum` guard
- `web` guard
- default guard

## خطوات التحقق

### 1. امسح Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. تحقق من Cookies في Browser
في Developer Tools > Application > Cookies:
- يجب أن ترى `laravel_session` cookie
- يجب أن ترى `XSRF-TOKEN` cookie
- يجب أن تكون `SameSite` = `None` أو `Lax`

### 3. تحقق من Network Tab
في Developer Tools > Network:
1. Request إلى `/api/auth/login`:
   - Status: 200
   - Response Headers: يجب أن تحتوي على `Set-Cookie: laravel_session=...`
   
2. Request إلى `/api/user`:
   - Status: 200 (وليس 401)
   - Request Headers: يجب أن تحتوي على `Cookie: laravel_session=...`

### 4. تحقق من Logs
```bash
tail -f storage/logs/laravel.log
```
إذا ظهرت رسالة "Unauthenticated user request"، تحقق من:
- Session ID موجود
- Cookies موجودة في الطلب

## إعدادات مهمة

### .env File
تأكد من وجود هذه الإعدادات:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false  # false للتطوير المحلي
SESSION_SAME_SITE=null
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:5173
```

### CORS Configuration
في `config/cors.php`:
```php
'supports_credentials' => true,
'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
],
```

### Frontend API Configuration
في `src/store/api/apiSlice.ts`:
```typescript
credentials: 'include', // Important!
headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
}
```

## إذا استمرت المشكلة

### الحل البديل 1: استخدام Token-based Auth
بدلاً من session-based auth، يمكنك استخدام token-based auth:
```php
// في AuthController
$token = $user->createToken('auth-token')->plainTextToken;
return $this->success([
    'user' => $user,
    'token' => $token,
]);
```

### الحل البديل 2: تعطيل Same-Site
في `config/session.php`:
```php
'same_site' => null,
'secure' => false, // للتطوير المحلي فقط
```

### الحل البديل 3: استخدام Custom Middleware
إنشاء middleware مخصص للتحقق من session:
```php
// app/Http/Middleware/CheckSession.php
public function handle($request, Closure $next)
{
    if (!$request->user()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }
    return $next($request);
}
```

## ملاحظات مهمة

1. **Same-Site Policy**: في المتصفحات الحديثة، `SameSite=Lax` قد يمنع إرسال cookies في بعض الحالات
2. **CORS**: يجب أن يكون `supports_credentials: true` في CORS config
3. **Frontend**: يجب أن يكون `credentials: 'include'` في fetch requests
4. **Session Driver**: تأكد من أن `sessions` table موجودة في database

## التحقق من Database
```bash
php artisan migrate
# أو
php artisan session:table
php artisan migrate
```



