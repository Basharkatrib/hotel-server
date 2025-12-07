# Hotel API Testing Guide

## Base URL
```
http://hotel-server.test/api
```

---

## **Hotels API Endpoints**

### **1. Get All Hotels (Public)**
**Endpoint**: `GET /hotels`

**Query Parameters** (Optional):
- `type` - Filter by type: `hotel`, `room`, `entire_home`, or `any`
- `min_price` - Minimum price per night
- `max_price` - Maximum price per night
- `city` - Filter by city name
- `has_free_cancellation` - Boolean (1 or 0)
- `has_spa_access` - Boolean (1 or 0)
- `has_breakfast_included` - Boolean (1 or 0)
- `is_featured` - Boolean (1 or 0)
- `sort_by` - Sort by field: `price_per_night`, `rating`, `created_at`
- `sort_order` - Sort order: `asc` or `desc`
- `per_page` - Results per page (default: 15)

**Example Requests**:

```bash
# Get all hotels (paginated)
curl -X GET "http://hotel-server.test/api/hotels"

# Filter by type and price range
curl -X GET "http://hotel-server.test/api/hotels?type=hotel&min_price=100&max_price=300"

# Filter by city with free cancellation
curl -X GET "http://hotel-server.test/api/hotels?city=Barcelona&has_free_cancellation=1"

# Sort by rating, 10 per page
curl -X GET "http://hotel-server.test/api/hotels?sort_by=rating&sort_order=desc&per_page=10"

# Get featured hotels only
curl -X GET "http://hotel-server.test/api/hotels?is_featured=1"

# Complex filter: Hotels in Barcelona with spa and breakfast, sorted by price
curl -X GET "http://hotel-server.test/api/hotels?city=Barcelona&has_spa_access=1&has_breakfast_included=1&sort_by=price_per_night&sort_order=asc"
```

**Success Response** (200 OK):
```json
{
  "status": true,
  "data": {
    "hotels": [
      {
        "id": 1,
        "name": "Hotel Arts Barcelona",
        "description": "Luxury hotel with sea views...",
        "address": "Marina, 19-21",
        "city": "Barcelona",
        "country": "Spain",
        "latitude": "41.38740000",
        "longitude": "2.16860000",
        "price_per_night": "150.00",
        "original_price": "200.00",
        "discount_percentage": 25,
        "type": "hotel",
        "rating": "4.8",
        "reviews_count": 1260,
        "room_type": "Sea View Room",
        "bed_type": "King Bed",
        "room_size": 40,
        "available_rooms": 5,
        "distance_from_center": "1.80",
        "distance_from_beach": "250.00",
        "has_metro_access": true,
        "has_free_cancellation": true,
        "has_spa_access": true,
        "has_breakfast_included": true,
        "is_featured": true,
        "is_getaway_deal": false,
        "images": [
          "https://picsum.photos/800/600",
          "https://picsum.photos/801/600"
        ],
        "amenities": [
          "Free WiFi",
          "Swimming Pool",
          "Restaurant",
          "Bar"
        ],
        "created_at": "2025-12-03T18:45:00.000000Z",
        "updated_at": "2025-12-03T18:45:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 15,
      "total": 20,
      "from": 1,
      "to": 15
    }
  },
  "messages": ["Hotels retrieved successfully."],
  "code": 200
}
```

---

### **2. Get Single Hotel (Public)**
**Endpoint**: `GET /hotels/{id}`

**Example Request**:
```bash
curl -X GET "http://hotel-server.test/api/hotels/1"
```

**Success Response** (200 OK):
```json
{
  "status": true,
  "data": {
    "hotel": {
      "id": 1,
      "name": "Hotel Arts Barcelona",
      "description": "Luxury hotel...",
      "address": "Marina, 19-21",
      "city": "Barcelona",
      "latitude": "41.38740000",
      "longitude": "2.16860000",
      "price_per_night": "150.00",
      "type": "hotel",
      "rating": "4.8",
      "reviews_count": 1260,
      "images": [...],
      "amenities": [...]
    }
  },
  "messages": ["Hotel retrieved successfully."],
  "code": 200
}
```

**Error Response** (404 Not Found):
```json
{
  "status": false,
  "data": null,
  "messages": ["Hotel not found."],
  "code": 404
}
```

---

### **3. Create Hotel (Protected - Requires Authentication)**
**Endpoint**: `POST /hotels`

**Headers**:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body**:
```json
{
  "name": "New Luxury Hotel",
  "description": "A beautiful hotel in the heart of the city",
  "address": "123 Main Street",
  "city": "Barcelona",
  "country": "Spain",
  "latitude": 41.3851,
  "longitude": 2.1734,
  "price_per_night": 250,
  "original_price": 300,
  "discount_percentage": 17,
  "type": "hotel",
  "rating": 4.5,
  "reviews_count": 0,
  "room_type": "Deluxe Suite",
  "bed_type": "King Bed",
  "room_size": 45,
  "available_rooms": 10,
  "distance_from_center": 2.5,
  "distance_from_beach": 500,
  "has_metro_access": true,
  "has_free_cancellation": true,
  "has_spa_access": true,
  "has_breakfast_included": true,
  "is_featured": false,
  "is_getaway_deal": false,
  "images": [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg"
  ],
  "amenities": [
    "Free WiFi",
    "Swimming Pool",
    "Gym"
  ]
}
```

**Example Request**:
```bash
curl -X POST "http://hotel-server.test/api/hotels" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Luxury Hotel",
    "address": "123 Main Street",
    "city": "Barcelona",
    "country": "Spain",
    "latitude": 41.3851,
    "longitude": 2.1734,
    "price_per_night": 250,
    "type": "hotel",
    "available_rooms": 10
  }'
```

**Success Response** (201 Created):
```json
{
  "status": true,
  "data": {
    "hotel": {
      "id": 21,
      "name": "New Luxury Hotel",
      ...
    }
  },
  "messages": ["Hotel created successfully."],
  "code": 201
}
```

---

### **4. Update Hotel (Protected)**
**Endpoint**: `PUT /hotels/{id}`

**Headers**:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body** (Send only fields you want to update):
```json
{
  "price_per_night": 180,
  "discount_percentage": 20,
  "available_rooms": 8
}
```

**Example Request**:
```bash
curl -X PUT "http://hotel-server.test/api/hotels/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "price_per_night": 180,
    "available_rooms": 8
  }'
```

**Success Response** (200 OK):
```json
{
  "status": true,
  "data": {
    "hotel": {
      "id": 1,
      "name": "Hotel Arts Barcelona",
      "price_per_night": "180.00",
      "available_rooms": 8,
      ...
    }
  },
  "messages": ["Hotel updated successfully."],
  "code": 200
}
```

---

### **5. Delete Hotel (Protected)**
**Endpoint**: `DELETE /hotels/{id}`

**Headers**:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Example Request**:
```bash
curl -X DELETE "http://hotel-server.test/api/hotels/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200 OK):
```json
{
  "status": true,
  "data": null,
  "messages": ["Hotel deleted successfully."],
  "code": 200
}
```

---

## **Testing with Postman**

### **Step 1: Import Collection**

1. Open Postman
2. Create a new Collection called "Hotels API"
3. Set base URL as variable: `{{base_url}}` = `http://hotel-server.test/api`

### **Step 2: Get Bearer Token**

First, login to get your access token:

```
POST {{base_url}}/auth/login
Content-Type: application/json

{
  "email": "admin@gmail.com",
  "password": "your_password"
}
```

Copy the `token` from the response.

### **Step 3: Set Authorization**

1. In your Collection settings, go to "Authorization" tab
2. Select Type: "Bearer Token"
3. Paste your token
4. All requests in this collection will inherit this token

### **Step 4: Create Requests**

Create requests for each endpoint:

**Get All Hotels**:
- Method: GET
- URL: `{{base_url}}/hotels?city=Barcelona&per_page=10`

**Get Single Hotel**:
- Method: GET
- URL: `{{base_url}}/hotels/1`

**Create Hotel** (Protected):
- Method: POST
- URL: `{{base_url}}/hotels`
- Body: JSON (see example above)

**Update Hotel** (Protected):
- Method: PUT
- URL: `{{base_url}}/hotels/1`
- Body: JSON with fields to update

**Delete Hotel** (Protected):
- Method: DELETE
- URL: `{{base_url}}/hotels/1`

---

## **Example Filter Combinations**

### **1. Featured Hotels with Spa**
```
GET /hotels?is_featured=1&has_spa_access=1
```

### **2. Budget Hotels in Barcelona**
```
GET /hotels?city=Barcelona&max_price=100&sort_by=price_per_night&sort_order=asc
```

### **3. Luxury Hotels with Getaway Deals**
```
GET /hotels?min_price=200&is_getaway_deal=1&sort_by=rating&sort_order=desc
```

### **4. Hotels Near Beach with Free Cancellation**
```
GET /hotels?has_free_cancellation=1&sort_by=distance_from_beach&sort_order=asc
```

---

## **Error Codes**

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (invalid/missing token)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## **Validation Rules**

### Required Fields for Create:
- `name` (string, max 255)
- `address` (string)
- `price_per_night` (number, min 0)
- `type` (enum: hotel, room, entire_home)

### Optional Fields:
All other fields are optional with default values.

---

## **Filament Admin Panel**

Access the admin panel to manage hotels with a visual interface:

**URL**: `http://hotel-server.test/admin`

**Login**:
- Email: `admin@gmail.com`
- Password: (your password)

**Features**:
- Visual table with images
- Filters and search
- Map picker for location
- Multiple image upload
- Drag & drop image reordering
- Tags input for amenities
- Toggle switches for features

---

## **Notes**

1. All public endpoints (`GET /hotels`, `GET /hotels/{id}`) don't require authentication
2. Create, Update, and Delete operations require a valid Bearer token
3. Pagination is automatic - use `per_page` to control results
4. All responses follow the unified format with `status`, `data`, `messages`, and `code`
5. Images are stored in `storage/app/public/hotels/`
6. The map uses OpenStreetMap tiles (no API key required)

---

## **Quick Test Script**

```bash
# 1. Get all hotels
curl "http://hotel-server.test/api/hotels"

# 2. Get hotels in Barcelona
curl "http://hotel-server.test/api/hotels?city=Barcelona"

# 3. Get single hotel
curl "http://hotel-server.test/api/hotels/1"

# 4. Get featured hotels with breakfast
curl "http://hotel-server.test/api/hotels?is_featured=1&has_breakfast_included=1"
```






