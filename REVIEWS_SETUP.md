# Reviews & Ratings API - Setup Guide

## ✅ الملفات المُنشأة

### 1. Migration
- ✅ `database/migrations/2025_01_15_000000_create_reviews_table.php`

### 2. Models
- ✅ `app/Models/Review.php`
- ✅ تم تحديث `app/Models/Hotel.php` - إضافة علاقة `reviews()`
- ✅ تم تحديث `app/Models/Room.php` - إضافة علاقة `reviews()`
- ✅ تم تحديث `app/Models/User.php` - إضافة علاقة `reviews()`

### 3. Controllers
- ✅ `app/Http/Controllers/Api/HotelReviewController.php`
- ✅ `app/Http/Controllers/Api/RoomReviewController.php`
- ✅ `app/Http/Controllers/Api/ReviewController.php`

### 4. Request Validators
- ✅ `app/Http/Requests/StoreReviewRequest.php`
- ✅ `app/Http/Requests/UpdateReviewRequest.php`

### 5. Routes
- ✅ تم تحديث `routes/api.php` - إضافة جميع routes للتقييمات

## 🚀 خطوات التشغيل

### 1. تشغيل Migration
```bash
php artisan migrate
```

هذا سينشئ جدول `reviews` في قاعدة البيانات.

### 2. التحقق من Routes
```bash
php artisan route:list | grep review
```

يجب أن ترى جميع routes للتقييمات.

## 📝 API Endpoints

### Hotel Reviews (Public)
- `GET /api/hotels/{slug}/reviews` - الحصول على تقييمات فندق
- `GET /api/hotels/{slug}/reviews/stats` - إحصائيات التقييمات

### Hotel Reviews (Protected - Auth Required)
- `POST /api/hotels/{slug}/reviews` - إنشاء تقييم جديد
- `GET /api/hotels/{slug}/reviews/check` - التحقق من وجود تقييم

### Room Reviews (Public)
- `GET /api/rooms/{id}/reviews` - الحصول على تقييمات غرفة
- `GET /api/rooms/{id}/reviews/stats` - إحصائيات التقييمات

### Room Reviews (Protected - Auth Required)
- `POST /api/rooms/{id}/reviews` - إنشاء تقييم جديد
- `GET /api/rooms/{id}/reviews/check` - التحقق من وجود تقييم

### General Reviews (Protected - Auth Required)
- `PUT /api/reviews/{review}` - تحديث تقييم
- `DELETE /api/reviews/{review}` - حذف تقييم

## 🧪 اختبار API

### مثال: إنشاء تقييم لفندق
```bash
curl -X POST "http://127.0.0.1:8000/api/hotels/hotel-slug/reviews" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rating": 5,
    "title": "Amazing hotel!",
    "comment": "Had an amazing stay, highly recommended."
  }'
```

### مثال: الحصول على تقييمات فندق
```bash
curl -X GET "http://127.0.0.1:8000/api/hotels/hotel-slug/reviews?page=1&per_page=10"
```

### مثال: الحصول على إحصائيات
```bash
curl -X GET "http://127.0.0.1:8000/api/hotels/hotel-slug/reviews/stats"
```

## 📋 ملاحظات مهمة

1. **Hotel Routes**: تستخدم `slug` وليس `id`
2. **Room Routes**: تستخدم `id` (numeric)
3. **Authentication**: جميع endpoints للإنشاء/التحديث/الحذف تحتاج إلى Bearer Token
4. **Unique Constraint**: كل مستخدم يمكنه كتابة تقييم واحد فقط لكل فندق/غرفة
5. **Rating Update**: يتم تحديث متوسط التقييمات تلقائياً في جدول الفنادق/الغرف

## ✅ Frontend Integration

تم تحديث Frontend لاستخدام slug للفنادق:
- `reviewsApi.js` - تم تحديث جميع queries لاستخدام `hotelSlug`
- `ReviewsSection.jsx` - تم تحديث لاستخدام `hotel.slug`

## 🎉 جاهز!

الآن نظام التقييمات جاهز للاستخدام. قم بتشغيل Migration وابدأ في اختبار الـ API!
