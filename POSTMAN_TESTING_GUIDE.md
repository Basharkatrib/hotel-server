# دليل اختبار نظام الأدوار والصلاحيات باستخدام Postman

## المتطلبات الأساسية

1. **Postman** مثبت على جهازك
2. **Base URL**: `http://localhost` أو عنوان الخادم الخاص بك
3. **API Prefix**: `/api`

---

## 1. إعداد Postman

### إنشاء Environment

1. افتح Postman
2. اضغط على **Environments** في اليسار
3. اضغط على **+** لإنشاء environment جديد
4. أضف المتغيرات التالية:
   - `base_url`: `http://localhost` (أو عنوان الخادم)
   - `user_token`: (سيتم ملؤه تلقائياً بعد تسجيل الدخول)
   - `admin_token`: (سيتم ملؤه تلقائياً بعد تسجيل الدخول)
   - `owner_token`: (سيتم ملؤه تلقائياً بعد تسجيل الدخول)

---

## 2. إنشاء المستخدمين للاختبار

### أ. إنشاء مستخدم عادي (User)

**Request:**
```
POST {{base_url}}/api/auth/register
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "name": "Test User",
    "email": "user@test.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response المتوقع:**
```json
{
    "status": true,
    "data": {
        "email": "user@test.com"
    },
    "messages": ["Registration successful. Please check your email for verification code."]
}
```

**ملاحظة**: ستحتاج إلى التحقق من البريد الإلكتروني أولاً.

---

### ب. إنشاء مستخدم أدمن (Admin)

**الطريقة 1: من خلال Seeder**
```bash
php artisan db:seed --class=AdminUserSeeder
```

**الطريقة 2: من خلال Tinker**
```bash
php artisan tinker
```
```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@test.com',
    'password' => Hash::make('password123'),
    'role' => 'admin',
    'email_verified_at' => now(),
]);
```

---

### ج. إنشاء صاحب فندق (Hotel Owner)

**من خلال Tinker:**
```bash
php artisan tinker
```
```php
User::create([
    'name' => 'Hotel Owner',
    'email' => 'owner@test.com',
    'password' => Hash::make('password123'),
    'role' => 'hotel_owner',
    'email_verified_at' => now(),
]);
```

---

## 3. تسجيل الدخول

### تسجيل دخول User

**Request:**
```
POST {{base_url}}/api/auth/login
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "email": "user@test.com",
    "password": "password123"
}
```

**Response المتوقع:**
```json
{
    "status": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Test User",
            "email": "user@test.com",
            "role": "user",
            "email_verified_at": "2024-01-01T00:00:00.000000Z"
        }
    },
    "messages": ["Login successful."]
}
```

**ملاحظة**: احفظ `role` من الـ response للتأكد من الدور.

---

### تسجيل دخول Admin

**Request:**
```
POST {{base_url}}/api/auth/login
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "email": "admin@test.com",
    "password": "password123"
}
```

**Response المتوقع:**
```json
{
    "status": true,
    "data": {
        "user": {
            "id": 2,
            "name": "Admin User",
            "email": "admin@test.com",
            "role": "admin",
            "email_verified_at": "2024-01-01T00:00:00.000000Z"
        }
    },
    "messages": ["Login successful."]
}
```

---

### تسجيل دخول Hotel Owner

**Request:**
```
POST {{base_url}}/api/auth/login
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "email": "owner@test.com",
    "password": "password123"
}
```

**Response المتوقع:**
```json
{
    "status": true,
    "data": {
        "user": {
            "id": 3,
            "name": "Hotel Owner",
            "email": "owner@test.com",
            "role": "hotel_owner",
            "email_verified_at": "2024-01-01T00:00:00.000000Z"
        }
    },
    "messages": ["Login successful."]
}
```

---

## 4. اختبار الصلاحيات

### أ. اختبار إنشاء فندق

#### ✅ Admin - يجب أن ينجح

**Request:**
```
POST {{base_url}}/api/hotels
Content-Type: application/json
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

**Body (JSON):**
```json
{
    "name": "Grand Hotel",
    "description": "A beautiful hotel",
    "address": "123 Main St",
    "city": "Madrid",
    "country": "Spain",
    "price_per_night": 100,
    "type": "hotel"
}
```

**Response المتوقع:**
```json
{
    "status": true,
    "data": {
        "hotel": {
            "id": 1,
            "name": "Grand Hotel",
            ...
        }
    },
    "messages": ["Hotel created successfully."]
}
```

#### ✅ Hotel Owner - يجب أن ينجح

**ملاحظة**: Hotel Owner لا يمكنه إنشاء فنادق من خلال API (فقط Admin). إذا أردت السماح له، يجب تعديل `HotelPolicy::create()`.

#### ❌ User - يجب أن يفشل (403 Forbidden)

**Response المتوقع:**
```json
{
    "status": false,
    "data": null,
    "messages": ["You do not have permission to create hotels."],
    "code": 403
}
```

---

### ب. اختبار تحديث فندق

#### ✅ Admin - يجب أن ينجح (لأي فندق)

**Request:**
```
PUT {{base_url}}/api/hotels/1
Content-Type: application/json
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

**Body (JSON):**
```json
{
    "name": "Updated Hotel Name",
    "price_per_night": 150
}
```

#### ✅ Hotel Owner - يجب أن ينجح (لفندقه فقط)

**خطوات:**
1. سجل دخول كـ Hotel Owner
2. أنشئ فندق (من خلال Admin أو مباشرة في Database)
3. حدّث `user_id` في الفندق ليكون `id` الـ Hotel Owner:
   ```sql
   UPDATE hotels SET user_id = 3 WHERE id = 1;
   ```
4. حاول تحديث الفندق

**Request:**
```
PUT {{base_url}}/api/hotels/1
Content-Type: application/json
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

#### ❌ Hotel Owner - يجب أن يفشل (لفندق لا يملكه)

**خطوات:**
1. أنشئ فندق آخر بـ `user_id` مختلف
2. حاول تحديثه كـ Hotel Owner

**Response المتوقع:**
```json
{
    "status": false,
    "data": null,
    "messages": ["You do not have permission to update this hotel."],
    "code": 403
}
```

---

### ج. اختبار حذف فندق

#### ✅ Admin - يجب أن ينجح

**Request:**
```
DELETE {{base_url}}/api/hotels/1
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

#### ❌ Hotel Owner - يجب أن يفشل (حتى لفندقه)

**Response المتوقع:**
```json
{
    "status": false,
    "data": null,
    "messages": ["You do not have permission to delete this hotel."],
    "code": 403
}
```

---

### د. اختبار عرض الفنادق

#### ✅ الجميع - يجب أن ينجح

**Request:**
```
GET {{base_url}}/api/hotels
```

**ملاحظة**: 
- **User**: يرى كل الفنادق
- **Hotel Owner**: يرى فقط فنادقه (يتم الفلترة تلقائياً)
- **Admin**: يرى كل الفنادق

---

### هـ. اختبار إنشاء غرفة

#### ✅ Admin - يجب أن ينجح

**Request:**
```
POST {{base_url}}/api/rooms
Content-Type: application/json
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

**Body (JSON):**
```json
{
    "hotel_id": 1,
    "name": "Deluxe Room",
    "type": "deluxe",
    "max_guests": 2,
    "price_per_night": 50
}
```

#### ✅ Hotel Owner - يجب أن ينجح (لفندقه فقط)

**خطوات:**
1. تأكد من أن الفندق يملكه Hotel Owner
2. أنشئ غرفة

#### ❌ Hotel Owner - يجب أن يفشل (لفندق لا يملكه)

**Response المتوقع:**
```json
{
    "status": false,
    "data": null,
    "messages": ["You can only create rooms for your own hotels."],
    "code": 403
}
```

---

### و. اختبار إنشاء حجز

#### ✅ User - يجب أن ينجح

**Request:**
```
POST {{base_url}}/api/bookings
Content-Type: application/json
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

**Body (JSON):**
```json
{
    "room_id": 1,
    "hotel_id": 1,
    "check_in_date": "2024-02-01",
    "check_out_date": "2024-02-05",
    "guest_name": "Test Guest",
    "guest_email": "guest@test.com",
    "guest_phone": "123456789",
    "guests_count": 2
}
```

#### ❌ Admin/Hotel Owner - يجب أن يفشل

**Response المتوقع:**
```json
{
    "status": false,
    "data": null,
    "messages": ["You do not have permission to create bookings."],
    "code": 403
}
```

---

### ز. اختبار عرض الحجوزات

#### ✅ Admin - يرى كل الحجوزات

**Request:**
```
GET {{base_url}}/api/bookings
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

#### ✅ Hotel Owner - يرى حجوزات فنادقه فقط

**Request:**
```
GET {{base_url}}/api/bookings
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

**ملاحظة**: سيتم فلترة الحجوزات تلقائياً لعرض فقط حجوزات فنادقه.

#### ✅ User - يرى حجوزاته فقط

**Request:**
```
GET {{base_url}}/api/bookings
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

---

### ح. اختبار إلغاء حجز

#### ✅ User - يمكنه إلغاء حجزه

**Request:**
```
PUT {{base_url}}/api/bookings/1/cancel
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

#### ✅ Hotel Owner - يمكنه إلغاء حجز لفندقه

**Request:**
```
PUT {{base_url}}/api/bookings/1/cancel
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

#### ✅ Admin - يمكنه إلغاء أي حجز

**Request:**
```
PUT {{base_url}}/api/bookings/1/cancel
Cookie: laravel_session=YOUR_SESSION_COOKIE
```

---

## 5. سيناريوهات اختبار متقدمة

### السيناريو 1: Hotel Owner يحاول تحديث فندق لا يملكه

1. سجل دخول كـ Hotel Owner
2. أنشئ فندقين:
   - فندق 1: `user_id = 3` (Hotel Owner)
   - فندق 2: `user_id = 2` (Admin)
3. حاول تحديث فندق 2
4. **النتيجة المتوقعة**: 403 Forbidden

---

### السيناريو 2: User يحاول إنشاء فندق

1. سجل دخول كـ User
2. حاول إنشاء فندق
3. **النتيجة المتوقعة**: 403 Forbidden

---

### السيناريو 3: Hotel Owner يحاول حذف فندقه

1. سجل دخول كـ Hotel Owner
2. أنشئ فندق
3. حاول حذفه
4. **النتيجة المتوقعة**: 403 Forbidden (فقط Admin يمكنه الحذف)

---

### السيناريو 4: Admin يحاول الحجز

1. سجل دخول كـ Admin
2. حاول إنشاء حجز
3. **النتيجة المتوقعة**: 403 Forbidden (فقط User يمكنه الحجز)

---

## 6. نصائح للاختبار

### استخدام Postman Collections

1. أنشئ Collection جديد في Postman
2. أضف المجلدات التالية:
   - `Auth` (تسجيل الدخول، تسجيل الخروج)
   - `Hotels` (CRUD)
   - `Rooms` (CRUD)
   - `Bookings` (CRUD)
3. احفظ كل Request في المجلد المناسب

### استخدام Variables

استخدم متغيرات Postman لتسهيل الاختبار:
- `{{base_url}}`: عنوان الخادم
- `{{user_id}}`: ID المستخدم
- `{{hotel_id}}`: ID الفندق
- `{{room_id}}`: ID الغرفة
- `{{booking_id}}`: ID الحجز

### اختبار الأخطاء

تأكد من اختبار:
- ✅ الحالات الناجحة
- ❌ الحالات الفاشلة (403, 404, 422)
- 🔒 التحقق من الصلاحيات لكل دور

---

## 7. كودات الاستجابة المتوقعة

### 200 OK
الطلب نجح

### 201 Created
تم إنشاء المورد بنجاح

### 401 Unauthorized
المستخدم غير مسجل دخول

### 403 Forbidden
المستخدم لا يملك الصلاحية

### 404 Not Found
المورد غير موجود

### 422 Unprocessable Entity
خطأ في التحقق من البيانات

---

## 8. استكشاف الأخطاء

### المشكلة: "Unauthenticated"
**الحل**: تأكد من إرسال Cookie مع الطلب (Laravel Sanctum يستخدم session-based auth)

### المشكلة: "You do not have permission"
**الحل**: 
1. تحقق من `role` المستخدم
2. تحقق من أن Policy تسمح بالعملية
3. تحقق من الملكية (لـ Hotel Owner)

### المشكلة: Policies لا تعمل
**الحل**: 
1. تأكد من تسجيل `AuthServiceProvider` في `bootstrap/providers.php`
2. امسح cache: `php artisan config:clear`
3. أعد تشغيل الخادم

---

## 9. أمثلة JSON كاملة

### إنشاء فندق (Admin)
```json
{
    "name": "Grand Hotel",
    "description": "A luxurious hotel in the heart of the city",
    "address": "123 Main Street",
    "city": "Madrid",
    "country": "Spain",
    "latitude": 40.4168,
    "longitude": -3.7038,
    "price_per_night": 150.00,
    "original_price": 200.00,
    "discount_percentage": 25,
    "type": "hotel",
    "room_type": "Deluxe",
    "bed_type": "King Bed",
    "room_size": 30,
    "available_rooms": 10,
    "distance_from_center": 2.5,
    "distance_from_beach": 500,
    "has_metro_access": true,
    "has_free_cancellation": true,
    "has_spa_access": true,
    "has_breakfast_included": true,
    "is_featured": true,
    "is_getaway_deal": false,
    "images": [
        "https://example.com/image1.jpg",
        "https://example.com/image2.jpg"
    ],
    "amenities": ["WiFi", "Pool", "Parking", "Gym"]
}
```

### إنشاء غرفة (Admin/Hotel Owner)
```json
{
    "hotel_id": 1,
    "name": "Deluxe Suite",
    "description": "Spacious suite with city view",
    "type": "suite",
    "size": 50,
    "max_guests": 4,
    "single_beds": 0,
    "double_beds": 0,
    "king_beds": 1,
    "queen_beds": 0,
    "price_per_night": 200.00,
    "original_price": 250.00,
    "discount_percentage": 20,
    "is_available": true,
    "has_breakfast": true,
    "has_wifi": true,
    "has_ac": true,
    "has_tv": true,
    "has_minibar": true,
    "has_safe": true,
    "has_balcony": true,
    "has_bathtub": true,
    "has_shower": true,
    "no_smoking": true,
    "view": "city",
    "images": [
        "https://example.com/room1.jpg"
    ],
    "is_active": true,
    "is_featured": true
}
```

### إنشاء حجز (User)
```json
{
    "room_id": 1,
    "hotel_id": 1,
    "check_in_date": "2024-02-01",
    "check_out_date": "2024-02-05",
    "guest_name": "John Doe",
    "guest_email": "john@example.com",
    "guest_phone": "+1234567890",
    "guests_count": 2,
    "rooms_count": 1,
    "guests_details": [
        {
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890"
        },
        {
            "name": "Jane Doe",
            "email": "jane@example.com",
            "phone": "+1234567891"
        }
    ],
    "special_requests": "Late check-in please"
}
```

---

## 10. Checklist للاختبار الكامل

- [ ] تسجيل دخول User
- [ ] تسجيل دخول Admin
- [ ] تسجيل دخول Hotel Owner
- [ ] User يحاول إنشاء فندق (يجب أن يفشل)
- [ ] Admin ينشئ فندق (يجب أن ينجح)
- [ ] Hotel Owner يحاول تحديث فندق لا يملكه (يجب أن يفشل)
- [ ] Hotel Owner يحدث فندقه (يجب أن ينجح)
- [ ] Admin يحذف فندق (يجب أن ينجح)
- [ ] Hotel Owner يحاول حذف فندقه (يجب أن يفشل)
- [ ] Admin ينشئ غرفة (يجب أن ينجح)
- [ ] Hotel Owner ينشئ غرفة لفندقه (يجب أن ينجح)
- [ ] Hotel Owner يحاول إنشاء غرفة لفندق لا يملكه (يجب أن يفشل)
- [ ] User ينشئ حجز (يجب أن ينجح)
- [ ] Admin يحاول إنشاء حجز (يجب أن يفشل)
- [ ] User يرى حجوزاته فقط
- [ ] Hotel Owner يرى حجوزات فنادقه فقط
- [ ] Admin يرى كل الحجوزات
- [ ] User يلغي حجزه (يجب أن ينجح)
- [ ] Hotel Owner يلغي حجز لفندقه (يجب أن ينجح)
- [ ] Admin يلغي أي حجز (يجب أن ينجح)

---

## 11. ملاحظات نهائية

1. **Session-based Auth**: Laravel Sanctum يستخدم session-based authentication، لذا تأكد من إرسال Cookie مع كل request
2. **CSRF Protection**: في حالة استخدام web routes، قد تحتاج إلى CSRF token
3. **Testing Environment**: استخدم بيئة اختبار منفصلة عن الإنتاج
4. **Database Seeding**: استخدم seeders لإنشاء بيانات اختبار

---

**تم إنشاء هذا الدليل لمساعدتك في فهم واختبار نظام الأدوار والصلاحيات بشكل كامل.**

