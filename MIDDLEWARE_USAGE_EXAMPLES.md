# أمثلة استخدام CheckRole Middleware

## الوضع الحالي

تم إنشاء `CheckRole` Middleware وتسجيله في `bootstrap/app.php`، لكننا **لم نستخدمه** في `routes/api.php` لأننا استخدمنا **Policies** بدلاً منه.

## لماذا Policies بدلاً من Middleware؟

### Middleware (CheckRole)
- ✅ بسيط وسريع
- ✅ يتحقق من الدور فقط
- ❌ لا يتحقق من الملكية (مثل: هل يملك المستخدم هذا الفندق؟)
- ❌ يحتاج إلى كود إضافي في Controller للتحقق من الملكية

### Policies
- ✅ أكثر مرونة وقوة
- ✅ تتحقق من الدور **و** الملكية في مكان واحد
- ✅ منطق الصلاحيات منظم في ملفات منفصلة
- ✅ الطريقة الموصى بها في Laravel

## متى يمكن استخدام CheckRole Middleware؟

يمكن استخدامه في الحالات التالية:

### 1. Routes بسيطة تحتاج فقط للتحقق من الدور

```php
// مثال: فقط Admin يمكنه الوصول
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/users', [AdminController::class, 'users']);
});
```

### 2. Routes تحتاج عدة أدوار

```php
// مثال: Admin و Hotel Owner فقط
Route::middleware(['auth:sanctum', 'role:admin,hotel_owner'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### 3. Route واحد فقط

```php
Route::get('/admin/settings', [AdminController::class, 'settings'])
    ->middleware(['auth:sanctum', 'role:admin']);
```

## أمثلة عملية

### المثال 1: Dashboard خاص بالأدمن فقط

```php
// في routes/api.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/statistics', [AdminController::class, 'statistics']);
});
```

**النتيجة**: فقط المستخدمون بـ `role = 'admin'` يمكنهم الوصول.

---

### المثال 2: Routes مشتركة بين Admin و Hotel Owner

```php
Route::middleware(['auth:sanctum', 'role:admin,hotel_owner'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/analytics', [AnalyticsController::class, 'index']);
});
```

**النتيجة**: Admin و Hotel Owner فقط يمكنهم الوصول.

---

### المثال 3: استخدام Middleware مع Policies

يمكنك استخدام Middleware للتحقق الأولي من الدور، ثم استخدام Policy للتحقق من الملكية:

```php
// في routes/api.php
Route::middleware(['auth:sanctum', 'role:admin,hotel_owner'])->group(function () {
    Route::put('/hotels/{id}', [HotelController::class, 'update']);
});

// في HotelController::update()
public function update(Request $request, int $id)
{
    $hotel = Hotel::find($id);
    
    // Middleware تحقق من الدور (admin أو hotel_owner)
    // Policy تتحقق من الملكية (هل يملك هذا الفندق؟)
    if (Gate::denies('update', $hotel)) {
        return $this->error(['You do not have permission.'], 403);
    }
    
    // الكود هنا
}
```

---

## الفرق بين الاستخدامين

### الطريقة الحالية (Policies فقط)

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/hotels', [HotelController::class, 'store']);
});

// HotelController::store()
public function store(Request $request)
{
    // Policy تتحقق من الدور والملكية
    if (Gate::denies('create', Hotel::class)) {
        return $this->error(['You do not have permission.'], 403);
    }
    // ...
}
```

### الطريقة البديلة (Middleware + Policies)

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/hotels', [HotelController::class, 'store']);
});

// HotelController::store()
public function store(Request $request)
{
    // Middleware تحقق من الدور (admin فقط)
    // Policy تتحقق من أي شروط إضافية
    if (Gate::denies('create', Hotel::class)) {
        return $this->error(['You do not have permission.'], 403);
    }
    // ...
}
```

---

## متى نستخدم Middleware ومتى نستخدم Policies؟

### استخدم Middleware عندما:
- ✅ تحتاج فقط للتحقق من الدور
- ✅ Routes بسيطة لا تحتاج منطق معقد
- ✅ تريد منع الوصول مبكراً (قبل الوصول للـ Controller)

### استخدم Policies عندما:
- ✅ تحتاج للتحقق من الدور **و** الملكية
- ✅ المنطق معقد (مثل: يمكنه التحديث فقط إذا كان يملك المورد)
- ✅ تريد تنظيم منطق الصلاحيات في مكان واحد

---

## مثال كامل: استخدام Middleware في مشروعك

إذا أردت إضافة route خاص بالأدمن فقط:

```php
// في routes/api.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Routes خاصة بالأدمن فقط
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::post('/admin/users/{id}/change-role', [AdminController::class, 'changeRole']);
    Route::get('/admin/system-settings', [AdminController::class, 'systemSettings']);
});
```

**النتيجة**: 
- ✅ Admin يمكنه الوصول
- ❌ Hotel Owner لا يمكنه الوصول (403)
- ❌ User لا يمكنه الوصول (403)

---

## الخلاصة

1. **CheckRole Middleware** موجود ومسجل لكن **غير مستخدم حالياً**
2. استخدمنا **Policies** بدلاً منه لأنها أكثر مرونة
3. يمكنك استخدام Middleware في حالات بسيطة تحتاج فقط للتحقق من الدور
4. يمكنك استخدام Middleware + Policies معاً للحصول على أفضل النتائج

---

## نصيحة

في مشروعك الحالي، **استمر في استخدام Policies** لأنها:
- تتعامل مع حالات معقدة (مثل ملكية الفندق)
- منظمة وسهلة الصيانة
- الطريقة الموصى بها في Laravel

استخدم Middleware فقط إذا أضفت routes بسيطة تحتاج فقط للتحقق من الدور.



