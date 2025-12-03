# Hotel Booking API Documentation

## Base URL
```
http://hotel-server.test/api
```

## Response Format
All API responses follow this unified format:

```json
{
  "status": true|false,
  "data": {...} | null,
  "messages": ["message1", "message2"],
  "code": 200
}
```

---

## Authentication Endpoints

### 1. Register New User
**POST** `/auth/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

**Success Response (201):**
```json
{
  "status": true,
  "data": {
    "email": "john@example.com"
  },
  "messages": [
    "Registration successful. Please check your email for verification code."
  ],
  "code": 201
}
```

**Error Response (422):**
```json
{
  "status": false,
  "data": null,
  "messages": [
    "The email has already been taken.",
    "The password must be at least 8 characters."
  ],
  "code": 422
}
```

---

### 2. Send OTP (Login)
**POST** `/auth/send-otp`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "email": "john@example.com"
  },
  "messages": [
    "Verification code has been sent to your email."
  ],
  "code": 200
}
```

**Note:** If user doesn't exist, it will be created automatically.

---

### 3. Verify OTP (Login)
**POST** `/auth/verify-otp`

**Request Body:**
```json
{
  "email": "john@example.com",
  "code": "1234"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": "2025-12-03T15:30:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz..."
  },
  "messages": [
    "Login successful."
  ],
  "code": 200
}
```

**Error Response (401):**
```json
{
  "status": false,
  "data": null,
  "messages": [
    "These credentials do not match our records."
  ],
  "code": 401
}
```

---

### 4. Forgot Password
**POST** `/auth/forgot-password`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "email": "john@example.com"
  },
  "messages": [
    "Password reset code has been sent to your email."
  ],
  "code": 200
}
```

---

### 5. Reset Password
**POST** `/auth/reset-password`

**Request Body:**
```json
{
  "email": "john@example.com",
  "code": "1234",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": null,
  "messages": [
    "Password has been reset successfully."
  ],
  "code": 200
}
```

---

### 6. Get Current User (Protected)
**GET** `/user`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": "2025-12-03T15:30:00.000000Z"
    }
  },
  "messages": [],
  "code": 200
}
```

---

### 7. Logout (Protected)
**POST** `/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "status": true,
  "data": null,
  "messages": [
    "Logged out successfully."
  ],
  "code": 200
}
```

---

## Error Codes

- `200` - Success
- `201` - Created
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Notes

1. **OTP Expiration:** OTP codes expire after 10 minutes.
2. **OTP Format:** 4-digit numeric code.
3. **Token Authentication:** Use Bearer token in Authorization header for protected routes.
4. **CORS:** Frontend at `localhost:5173` is whitelisted.
5. **Email:** Make sure to configure `.env` with proper mail settings for OTP delivery.

---

## Frontend Integration Example (React)

```javascript
// Send OTP
const sendOtp = async (email) => {
  const response = await fetch('http://hotel-server.test/api/auth/send-otp', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email }),
  });
  
  const data = await response.json();
  return data;
};

// Verify OTP
const verifyOtp = async (email, code) => {
  const response = await fetch('http://hotel-server.test/api/auth/verify-otp', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ email, code }),
  });
  
  const data = await response.json();
  
  if (data.status && data.data.token) {
    // Save token to localStorage or state management
    localStorage.setItem('auth_token', data.data.token);
  }
  
  return data;
};

// Get user (with token)
const getUser = async () => {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch('http://hotel-server.test/api/user', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  
  return await response.json();
};
```

