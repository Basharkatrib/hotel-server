# دليل نظام الأدوار والصلاحيات

## نظرة عامة

تم تنفيذ نظام كامل للأدوار والصلاحيات يتضمن ثلاثة أدوار رئيسية:
- **user** (المستخدم العادي): يمكنه عرض الفنادق والحجز فقط
- **admin** (الأدمن): يمكنه القيام بكل شيء
- **hotel_owner** (صاحب الفندق): يمكنه إدارة فنادقه فقط والغرف التابعة لها وحجوزاتها

---

## 1. هيكل قاعدة البيانات

### جدول `users`
تم إضافة حقل `role` من نوع `enum` بالقيم:
- `user` (افتراضي)
- `admin`
- `hotel_owner`

```php
$table->enum('role', ['user', 'admin', 'hotel_owner'])->default('user')->index();
```

### جدول `hotels`
تم إضافة حقل `user_id` لربط الفندق بصاحبه:
```php
$table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->index();
```

---

## 2. Models والعلاقات

### User Model

#### الحقول المضافة:
- `role` في `$fillable`

#### Methods المضافة:
```php
// التحقق من الأدوار
public function isAdmin(): bool
public function isHotelOwner(): bool
public function isRegularUser(): bool

// العلاقة مع الفنادق
public function hotels(): HasMany
```

#### مثال الاستخدام:
```php
$user = Auth::user();
if ($user->isAdmin()) {
    // كود خاص بالأدمن
}
```

### Hotel Model

#### الحقول المضافة:
- `user_id` في `$fillable`

#### Methods المضافة:
```php
// العلاقة مع المالك
public function owner(): BelongsTo

// التحقق من الملكية
public function isOwnedBy(int $userId): bool
```

#### مثال الاستخدام:
```php
$hotel = Hotel::find(1);
if ($hotel->isOwnedBy($user->id)) {
    // المستخدم يملك هذا الفندق
}
```

### Room Model

#### Methods المضافة:
```php
// التحقق من الملكية (من خلال الفندق)
public function isOwnedBy(int $userId): bool
```

### Booking Model

#### Methods المضافة:
```php
// التحقق من ملكية الحجز
public function isOwnedBy(int $userId): bool

// التحقق من أن الحجز لفندق يملكه المستخدم
public function isOwnedByHotelOwner(int $userId): bool
```

---

## 3. Policies (سياسات الصلاحيات)

Policies هي الطريقة الموصى بها في Laravel للتحقق من الصلاحيات. تم إنشاء ثلاث Policies:

### HotelPolicy

#### الصلاحيات:
- `viewAny()`: الجميع يمكنهم عرض الفنادق ✅
- `view()`: الجميع يمكنهم عرض الفندق ✅
- `create()`: **admin فقط** ✅
- `update()`: **admin** أو **hotel_owner** (لفندقه فقط) ✅
- `delete()`: **admin فقط** ✅

#### مثال في Controller:
```php
if (Gate::denies('create', Hotel::class)) {
    return $this->error(['You do not have permission to create hotels.'], 403);
}
```

### RoomPolicy

#### الصلاحيات:
- `viewAny()`: الجميع يمكنهم عرض الغرف ✅
- `view()`: الجميع يمكنهم عرض الغرفة ✅
- `create()`: **admin** أو **hotel_owner** ✅
- `update()`: **admin** أو **hotel_owner** (للغرف التابعة لفندقه فقط) ✅
- `delete()`: **admin** أو **hotel_owner** (للغرف التابعة لفندقه فقط) ✅

### BookingPolicy

#### الصلاحيات:
- `viewAny()`: الجميع (لكن يتم الفلترة في Controller حسب الدور) ✅
- `view()`: 
  - **admin**: كل الحجوزات ✅
  - **hotel_owner**: حجوزات فنادقه فقط ✅
  - **user**: حجوزاته فقط ✅
- `create()`: **user فقط** ✅
- `update()`: **admin فقط** ✅
- `cancel()`: 
  - **admin**: كل الحجوزات ✅
  - **hotel_owner**: حجوزات فنادقه ✅
  - **user**: حجوزاته ✅

---

## 4. Middleware

### CheckRole Middleware

تم إنشاء middleware للتحقق من الأدوار:

```php
Route::middleware(['auth:sanctum', 'role:admin,hotel_owner'])->group(function () {
    // Routes هنا
});
```

**ملاحظة**: في هذا المشروع، نستخدم Policies بدلاً من Middleware لأنها أكثر مرونة.

---

## 5. كيفية عمل التحقق من الصلاحيات

### في Controllers

#### الطريقة الأولى: استخدام Gate
```php
use Illuminate\Support\Facades\Gate;

public function store(Request $request)
{
    // التحقق من الصلاحية
    if (Gate::denies('create', Hotel::class)) {
        return $this->error(['You do not have permission.'], 403);
    }
    
    // الكود هنا
}
```

#### الطريقة الثانية: استخدام authorize
```php
public function update(Request $request, Hotel $hotel)
{
    // هذا سيرمي استثناء تلقائياً إذا لم يكن لديه الصلاحية
    $this->authorize('update', $hotel);
    
    // الكود هنا
}
```

### في Policies

```php
public function update(User $user, Hotel $hotel): bool
{
    // admin يمكنه تحديث أي فندق
    if ($user->isAdmin()) {
        return true;
    }
    
    // hotel_owner يمكنه تحديث فنادقه فقط
    if ($user->isHotelOwner() && $hotel->isOwnedBy($user->id)) {
        return true;
    }
    
    return false;
}
```

---

## 6. الفلترة حسب الدور

### في HotelController::index()

```php
// إذا كان المستخدم صاحب فندق، يعرض فقط فنادقه
if ($request->user() && $request->user()->isHotelOwner()) {
    $query->where('user_id', $request->user()->id);
}
```

### في RoomController::index()

```php
// إذا كان المستخدم صاحب فندق، يعرض فقط غرف فنادقه
if ($request->user() && $request->user()->isHotelOwner()) {
    $query->whereHas('hotel', function ($q) use ($request) {
        $q->where('user_id', $request->user()->id);
    });
}
```

### في BookingController::index()

```php
$user = $request->user();

if ($user->isAdmin()) {
    // admin: كل الحجوزات (لا فلترة)
} elseif ($user->isHotelOwner()) {
    // hotel_owner: حجوزات فنادقه فقط
    $query->whereHas('hotel', function ($q) use ($user) {
        $q->where('user_id', $user->id);
    });
} else {
    // user: حجوزاته فقط
    $query->where('user_id', $user->id);
}
```

---

## 7. تسجيل Policies

تم تسجيل Policies في `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    Hotel::class => HotelPolicy::class,
    Room::class => RoomPolicy::class,
    Booking::class => BookingPolicy::class,
];
```

وتم تسجيل `AuthServiceProvider` في `bootstrap/providers.php`.

---

## 8. تدفق التحقق من الصلاحيات

```
Request → Middleware (auth:sanctum) → Controller → Gate::denies() → Policy → Model Methods
```

### مثال كامل:

1. **المستخدم يرسل Request**: `POST /api/hotels`
2. **Middleware يتحقق**: من أن المستخدم مسجل دخول
3. **Controller يستقبل Request**: `HotelController::store()`
4. **Gate يتحقق**: `Gate::denies('create', Hotel::class)`
5. **Policy يتم استدعاؤها**: `HotelPolicy::create($user)`
6. **Policy تتحقق**: `$user->isAdmin()` → إذا كان admin، يرجع `true`
7. **إذا كان لديه الصلاحية**: يتم تنفيذ الكود
8. **إذا لم يكن لديه الصلاحية**: يرجع 403 Forbidden

---

## 9. الأخطاء الشائعة والحلول

### المشكلة: "You do not have permission"
**السبب**: المستخدم لا يملك الصلاحية المطلوبة
**الحل**: تأكد من أن المستخدم لديه الدور الصحيح

### المشكلة: "Hotel not found" عند محاولة التحديث
**السبب**: قد يكون الفندق غير موجود أو المستخدم لا يملكه
**الحل**: تأكد من أن `user_id` في الفندق يطابق `id` المستخدم

### المشكلة: Policies لا تعمل
**السبب**: `AuthServiceProvider` غير مسجل
**الحل**: تأكد من وجوده في `bootstrap/providers.php`

---

## 10. ملاحظات مهمة

1. **الأمان**: جميع التحققات في الـ Backend فقط، لا تعتمد على Frontend
2. **الصلاحيات**: يتم التحقق في كل request، لا يتم تخزينها في session
3. **العلاقات**: عند استخدام `isOwnedBy()`، يتم تحميل العلاقة تلقائياً إذا لم تكن محملة
4. **الافتراضي**: عند التسجيل، يتم تعيين `role` كـ `user` تلقائياً
5. **Admin**: يمكنه كل شيء، لا توجد قيود عليه

---

## 11. كيفية تغيير دور المستخدم

### من خلال Seeder:
```php
User::create([
    'name' => 'Hotel Owner',
    'email' => 'owner@example.com',
    'password' => Hash::make('password'),
    'role' => 'hotel_owner',
]);
```

### من خلال Database مباشرة:
```sql
UPDATE users SET role = 'hotel_owner' WHERE email = 'owner@example.com';
```

### من خلال Tinker:
```bash
php artisan tinker
```
```php
$user = User::where('email', 'owner@example.com')->first();
$user->role = 'hotel_owner';
$user->save();
```

---

## 12. الاختبار

راجع الملف `POSTMAN_TESTING_GUIDE.md` للحصول على دليل شامل لاختبار النظام باستخدام Postman.

