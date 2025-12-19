# إعدادات Cookie للمصادقة

## التعديلات المطلوبة في ملف `.env`:

أضف أو عدّل هذه السطور في ملف `.env`:

```env
# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=10080
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

## ملاحظات مهمة:

1. **`SESSION_DOMAIN=`**: اتركه فارغاً (لا تكتب `null`) للعمل مع localhost
2. **`SESSION_SECURE_COOKIE=false`**: يجب أن يكون `false` للعمل مع HTTP في localhost
3. **`SESSION_SAME_SITE=lax`**: يعمل مع Vite proxy حيث جميع الطلبات من نفس الـ origin

## بعد التعديل:

1. احفظ ملف `.env`
2. امسح cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. أعد تشغيل الباك اند

## التحقق من الإعدادات:

بعد تسجيل الدخول، افتح Developer Tools → Application → Cookies وتحقق من:
- ✅ وجود cookie `auth_token`
- ✅ `HttpOnly: true`
- ✅ `SameSite: Lax`
- ✅ `Secure: false` (لـ localhost)

