# Music Lesson Booking API v1

Base URL: `/api/v1`  
Auth: Bearer token (Sanctum)

## Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | No | Register (student/teacher) |
| POST | `/auth/login` | No | Login |
| POST | `/auth/logout` | Yes | Logout |
| POST | `/auth/refresh` | Yes | Refresh token |
| GET | `/me` | Yes | Current user |
| PATCH | `/me` | Yes | Update profile |
| POST | `/email/resend` | Yes | Resend verification |
| GET | `/email/verify/{id}/{hash}` | No | Verify email |
| POST | `/forgot-password` | No | Forgot password |
| POST | `/reset-password` | No | Reset password |

## Teachers (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/teachers` | List teachers |
| GET | `/teachers/{id}` | Teacher detail |
| GET | `/teachers/{id}/slots` | Teacher slots |

## Instruments

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/instruments` | No | List instruments |

## Student

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/student/bookings` | Student | Create booking |
| GET | `/student/bookings` | Student | My bookings |
| POST | `/student/bookings/{id}/cancel` | Student | Cancel booking |

## Teacher

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/teacher/profile` | Teacher | My profile |
| PATCH | `/teacher/profile` | Teacher | Update profile |
| POST | `/teacher/profile/instruments` | Teacher | Sync instruments |
| GET | `/teacher/bookings` | Teacher | My bookings |
| POST | `/teacher/bookings/{id}/confirm` | Teacher | Confirm booking |
| POST | `/teacher/bookings/{id}/complete` | Teacher | Complete booking |
| POST | `/teacher/bookings/{id}/cancel` | Teacher | Cancel booking |
| GET | `/teacher/slots` | Teacher | My slots |
| POST | `/teacher/slots` | Teacher | Create slot |
| GET | `/teacher/slots/{id}` | Teacher | Slot detail |
| PATCH | `/teacher/slots/{id}` | Teacher | Update slot |
| DELETE | `/teacher/slots/{id}` | Teacher | Delete slot |

## Admin

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/admin/instruments` | Admin | List instruments |
| POST | `/admin/instruments` | Admin | Create instrument |
| GET | `/admin/instruments/{id}` | Admin | Instrument detail |
| PUT/PATCH | `/admin/instruments/{id}` | Admin | Update instrument |
| DELETE | `/admin/instruments/{id}` | Admin | Delete instrument |
| GET | `/admin/users` | Admin | List users |
| GET | `/admin/users/{id}` | Admin | User detail |
| PATCH | `/admin/users/{id}/role` | Admin | Change user role |
| GET | `/admin/bookings` | Admin | All bookings |
| GET | `/admin/stats` | Admin | Dashboard stats |

## Wallet

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wallet` | Yes | My wallet |
| POST | `/wallet/deposit` | Yes | Deposit |

## Reviews

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/reviews` | Yes | Submit review |

## File Upload

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/uploads` | Yes | Upload file |

## Booking Statuses

`pending` → `confirmed` → `completed`  
`pending` → `cancelled`  
`confirmed` → `cancelled`