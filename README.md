# civentral-web

1111-ADMIN-2011
password123





# ==============================================================================
# 1. DIRECTORY INDEX & CORE SETTINGS
# ==============================================================================
DirectoryIndex home.php index.html index.php

# Disable Directory Browsing
Options -Indexes

# Disable Server Signature (Hides Apache version on error pages)
ServerSignature Off

# ==============================================================================
# 2. REWRITE ENGINE, HTTPS & URL CLEANUP
# ==============================================================================
RewriteEngine On

# Force HTTPS (Skipped on localhost due to your existing rules)
RewriteCond %{HTTPS} off
RewriteCond %{HTTP_HOST} !=localhost
RewriteCond %{HTTP_HOST} !=127.0.0.1
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Route /c/{anything} to dashboard.php
RewriteRule ^c/([a-zA-Z0-9-]+)/?$ dashboard.php?id=$1 [L,QSA]

# !!! REMOVED THE .PHP REDIRECT RULE THAT WAS RELOADING THE PAGE !!!

# Remove .php extension internally (e.g., /about loads about.php)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# ==============================================================================
# 3. SECURITY HEADERS (Safe - won't crash if mod_headers is missing)
# ==============================================================================
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    # HSTS is disabled here for localhost testing to prevent locking you out
    # Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# ==============================================================================
# 4. BLOCK SENSITIVE FILES & MALICIOUS TRAFFIC
# ==============================================================================
<FilesMatch "^\.|\.(bak|config|sql|fla|md|ini|log|sh|inc|swp|dist|env|git|svn|xml|txt)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block Bad Bots
RewriteCond %{HTTP_USER_AGENT} ^BadBot [OR]
RewriteCond %{HTTP_USER_AGENT} ^ScrapingBot [OR]
RewriteCond %{HTTP_USER_AGENT} ^SemrushBot [OR]
RewriteCond %{HTTP_USER_AGENT} ^AhrefsBot
RewriteRule ^.* - [F,L]

# Block Basic SQL Injection / Script Injections in URLs
RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]
RewriteCond %{QUERY_STRING} UNION.*SELECT [NC,OR]
RewriteCond %{QUERY_STRING} (base64_encode|eval\() [NC,OR]
RewriteCond %{QUERY_STRING} ../../ [NC]
RewriteRule ^ - [F,L]

# HOTLINKING DISABLED FOR LOCALHOST 
# (Uncomment and change 'localhost' to your live domain when you go live)
# RewriteCond %{HTTP_REFERER} !^$ # RewriteCond %{HTTP_REFERER} !^https?://(www\.)?localhost [NC]
# RewriteRule \.(jpg|jpeg|png|gif|webp|svg)$ - [F,NC]

# ==============================================================================
# 5. PERFORMANCE (Safe - won't crash if modules are missing)
# ==============================================================================
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/json
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType font/woff "access plus 1 year"
</IfModule>



# Civentral: Health & Sanitation Management System (HSMS)
## Comprehensive System Documentation

**Date:** July 2026  
**Status:** Development / Active  
**Base URL:** `https://api.hsms.caloocan.gov.ph/v1`

---

## 1. Project Overview
**Civentral** is a unified municipal platform for the City of Caloocan, designed to manage public health, sanitation, and urban services. It bridges the gap between local government operations and citizen accessibility through a robust administrative portal and a citizen-facing mobile application.

---

## 2. Technology Stack

### Backend
- **Language:** PHP 8.x
- **Architecture:** Custom MVC-lite (Models, Controllers, Core)
- **Database:** PostgreSQL (via Supabase)
- **Integration:** PostgREST for direct database-to-API interaction

### Frontend
- **Web:** HTML5, Tailwind CSS 3.4/4.0, Vanilla JavaScript
- **Styling:** Custom Tailwind themes & ApexCharts for analytics
- **Maps:** Leaflet.js with Heatmap integration for disease surveillance
- **Icons:** FontAwesome 6.4.0 & Tabler Icons

### Tooling
- **Environment:** `dotenv` for configuration
- **Package Management:** NPM (for frontend assets like ApexCharts, Leaflet)
- **Version Control:** Git

---

## 3. Core Architecture & Folder Structure

The project follows a clean separation of concerns:

```text
├── api/              # Entry points for API requests
├── app/
│   ├── Controllers/  # Logic handling (BaseController child classes)
│   └── Models/       # Database interaction logic (Supabase cURL)
├── assets/           # CSS, JS, and Images
├── config/           # Database and core configurations
├── Core/             # Base classes (Env, Response, BaseController)
├── includes/         # Reusable UI components (Header, Sidebar, Masking)
├── management/       # System settings and user management pages
├── modules/          # Core operational modules (Health, Sanitation, etc.)
└── pages/            # View pages for the web dashboard
```

### MVC Implementation
1.  **API Layer (`api/`)**: Routes incoming HTTP requests to specific Controllers.
2.  **Controller Layer (`app/Controllers/`)**: Validates input and orchestrates data flow between Models and the Response class.
3.  **Model Layer (`app/Models/`)**: Uses a Singleton `Database` class to perform cURL-based requests to the Supabase PostgREST endpoint.
4.  **Core Layer (`Core/`)**: Provides standardized JSON responses and environment variable loading.

---

## 4. Database & API Integration

### Supabase Connectivity (`config/database.php`)
The system uses a custom `Database` class that abstracts cURL requests to Supabase. It supports:
-   **RESTful Methods**: `GET` (select), `POST` (insert), `PATCH` (update), `DELETE`.
-   **Advanced Filtering**: Maps PHP arrays to PostgREST syntax (e.g., `eq.`, `ilike.`, `gt.`).
-   **Security**: Switches between `Anon Key` and `Service Key` based on the operation's sensitivity.

### API Response Format
Standardized via `Core/Response.php`:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "total": 100 // Optional for lists
}
```

---

## 5. Security & Compliance

### Data Masking System (`includes/data-mask.php`)
A critical feature for **Data Privacy Act (DPA)** compliance.
-   **Functionality**: Automatically masks sensitive patient data (Names, IDs) using a `*` pattern.
-   **Toggle**: Accessible via `Ctrl+Shift+D`.
-   **Implementation**: Uses `MutationObserver` to ensure dynamically loaded content is masked in real-time.

### Role-Based Access Control (RBAC)
The system defines 10 optimized roles including:
1.  **Health Center Director**: Full control over health modules.
2.  **Medical Practitioner**: Focused on consultations and prescriptions.
3.  **Sanitation Lead**: Manages permits and inspections.
4.  **System Admin**: Full cross-module visibility and audit logs.

### Audit Trails
Logs every sensitive action:
-   **What**: User, Action, Target ID.
-   **When**: Timestamp.
-   **Where**: IP Address and Module.

---

## 6. Functional Modules

### 🏥 Module 1: Health Center Services
-   **Patient Management**: Registration, EHR tracking.
-   **Triage**: Priority classification and vital signs.
-   **Consultations**: ICD-10 diagnosis and treatment plans.
-   **Prescriptions**: Electronic RX generation.
-   **Referrals**: Specialist and hospital coordination.

### 📋 Module 2: Sanitation Permits
-   **Applications**: Digital submission and review.
-   **Inspections**: Scheduling and field report generation.
-   **Payments**: Integration for fee processing (GCash/Bank).

### 💉 Module 3: Immunization & Nutrition
-   **Vaccination Tracking**: Schedule management and missed dose alerts.
-   **Growth Charts**: BMI and percentile tracking for children.
-   **Inventory**: Cold chain monitoring and stock reorder levels.

### 🏭 Module 4: Wastewater Services
-   **Septic Registry**: Geographic mapping of tanks.
-   **Service Requests**: Desludging and maintenance scheduling.

### 🦟 Module 5: Health Surveillance
-   **Case Reporting**: Disease onset tracking.
-   **Mapping**: Heatmaps for outbreak detection.
-   **Contact Tracing**: Exposure assessment and quarantine management.

---

## 7. Configuration & Setup

### Requirements
-   PHP 8.1+
-   cURL extension enabled
-   NPM (for asset building)

### Environment Variables (`.env`)
```env
SUPABASE_URL=your_project_url
SUPABASE_KEY=your_anon_key
SUPABASE_SERVICE_KEY=your_service_role_key
```

### Installation
1.  Clone the repository.
2.  Install assets: `npm install`.
3.  Configure `.env` with Supabase credentials.
4.  Run on any PHP-capable web server (Apache/Nginx).

---

## 8. Development Roadmap (Next Steps)
-   [ ] **Notification Engine**: Implement SMS/Email alerts for appointment reminders.
-   [ ] **Payment Integration**: Live API hooks for GCash and PayMaya.
-   [ ] **Mobile App**: Citizen-facing application for service requests and permit tracking.
-   [ ] **AI Insights**: Enhanced predictive analytics for disease outbreaks.

1. PATIENTS

GET /api/patients
Description: Get list of all patients with pagination
Access: Authenticated (Doctor, Nurse, Admin)

Query Parameters:
- page: 1 (default)
- limit: 20 (default)
- search: "Pedro"
- status: "active|inactive"
- sortBy: "name|createdAt"

Response (200 OK):
{
  "status": "success",
  "data": {
    "patients": [
      {
        "id": "P-101",
        "name": "Pedro Garcia",
        "age": 34,
        "gender": "Male",
        "bloodType": "O+",
        "contact": "09123456789",
        "address": "123 Rizal St., Barangay San Jose",
        "lastVisit": "2026-06-15",
        "status": "active"
      }
    ],
    "pagination": {
      "total": 1248,
      "page": 1,
      "limit": 20,
      "pages": 63
    }
  }
}


GET /api/patients/{id}
Description: Get complete patient details
Access: Authenticated (Doctor, Nurse, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "patient": {
      "id": "P-101",
      "name": "Pedro Garcia",
      "age": 34,
      "gender": "Male",
      "birthDate": "1992-03-15",
      "bloodType": "O+",
      "contact": "09123456789",
      "email": "pedro.garcia@email.com",
      "address": "123 Rizal St., Barangay San Jose",
      "emergencyContact": "Maria Garcia - 09176543210",
      "medicalHistory": {
        "allergies": ["None"],
        "conditions": ["Hypertension"],
        "medications": ["Amlodipine 5mg"],
        "surgeries": ["None"]
      },
      "lastVisit": "2026-06-15",
      "status": "active",
      "createdAt": "2024-01-15T08:30:00Z",
      "updatedAt": "2026-06-15T14:20:00Z"
    }
  }
}

POST /api/patients
Description: Register new patient
Access: Authenticated (Doctor, Clerk, Admin)

Request Body:
{
  "name": "Juan Dela Cruz",
  "gender": "Male",
  "birthDate": "1995-08-20",
  "bloodType": "A+",
  "contact": "09123456789",
  "email": "juan@email.com",
  "address": "456 Mabini Ave., Barangay Poblacion",
  "emergencyContact": "Ana Dela Cruz - 09176543210",
  "allergies": ["Penicillin"],
  "conditions": ["Diabetes Type 2"],
  "medications": ["Metformin 500mg"]
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "patient": {
      "id": "P-104",
      "name": "Juan Dela Cruz",
      "createdAt": "2026-07-17T10:30:00Z"
    }
  }
}

PUT /api/patients/{id}
Description: Update patient information
Access: Authenticated (Doctor, Clerk, Admin)

Request Body:
{
  "contact": "09987654321",
  "address": "789 Bonifacio Rd., Barangay Riverside",
  "medications": ["Metformin 500mg", "Lisinopril 10mg"]
}

Response (200 OK):
{
  "status": "success",
  "message": "Patient updated successfully",
  "data": {
    "patient": {
      "id": "P-104",
      "updatedAt": "2026-07-17T10:35:00Z"
    }
  }
}

DELETE /api/patients/{id}
Description: Soft delete patient (archive)
Access: Authenticated (Admin only)

Response (200 OK):
{
  "status": "success",
  "message": "Patient archived successfully"
}

2. APPOINMENTS
GET /api/appointments
Description: Get list of appointments
Access: Authenticated (Doctor, Nurse, Clerk, Admin)

Query Parameters:
- date: "2026-07-20"
- status: "pending|approved|completed|cancelled"
- doctorId: 1
- patientId: "P-101"

Response (200 OK):
{
  "status": "success",
  "data": {
    "appointments": [
      {
        "id": "APT-001",
        "patient": {
          "id": "P-101",
          "name": "Pedro Garcia"
        },
        "doctor": {
          "id": 1,
          "name": "Dr. Elena Santos",
          "specialty": "General Medicine"
        },
        "service": "General Checkup",
        "date": "2026-07-20",
        "time": "09:00 AM",
        "status": "pending",
        "triage": "Medium",
        "notes": "Routine checkup",
        "createdAt": "2026-07-15T08:30:00Z"
      }
    ]
  }
}
POST /api/appointments
Description: Schedule new appointment
Access: Authenticated (Doctor, Nurse, Clerk, Admin)

Request Body:
{
  "patientId": "P-101",
  "doctorId": 1,
  "service": "General Checkup",
  "date": "2026-07-22",
  "time": "10:30 AM",
  "notes": "Patient requested specific doctor",
  "priority": "Medium"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "appointment": {
      "id": "APT-006",
      "status": "pending",
      "createdAt": "2026-07-17T11:00:00Z"
    }
  }
}
PATCH /api/appointments/{id}/status
Description: Update appointment status
Access: Authenticated (Doctor, Nurse, Admin)

Request Body:
{
  "status": "approved",
  "notes": "Doctor confirmed availability"
}

Response (200 OK):
{
  "status": "success",
  "message": "Appointment status updated"
}
3.CONSULTATIONS
POST /api/consultations
Description: Record new consultation
Access: Authenticated (Doctor only)

Request Body:
{
  "patientId": "P-101",
  "doctorId": 1,
  "appointmentId": "APT-001",
  "diagnosis": "Hypertension - Stage 1",
  "icdCode": "I10",
  "symptoms": ["Headache", "Dizziness", "Blurred vision"],
  "vitalSigns": {
    "bloodPressure": "140/90",
    "heartRate": 82,
    "temperature": 36.5,
    "respiratoryRate": 18,
    "weight": 75.5,
    "height": 175
  },
  "treatment": "Continue Amlodipine 5mg daily. Follow up in 1 month.",
  "notes": "Patient advised to reduce salt intake and exercise regularly"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "consultation": {
      "id": "CON-001",
      "createdAt": "2026-07-17T09:15:00Z"
    }
  }
}
GET /api/consultations/patient/{patientId}
Description: Get consultation history for a patient
Access: Authenticated (Doctor, Nurse, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "consultations": [
      {
        "id": "CON-001",
        "date": "2026-07-17",
        "doctor": "Dr. Elena Santos",
        "diagnosis": "Hypertension - Stage 1",
        "treatment": "Continue Amlodipine 5mg daily",
        "followUp": "2026-08-17"
      }
    ]
  }
}
POST /api/prescriptions
Description: Create new prescription
Access: Authenticated (Doctor only)

Request Body:
{
  "patientId": "P-101",
  "consultationId": "CON-001",
  "medications": [
    {
      "name": "Amlodipine",
      "dosage": "5mg",
      "frequency": "Once daily",
      "duration": "30 days",
      "quantity": 30,
      "instructions": "Take one tablet daily in the morning"
    }
  ],
  "notes": "Monitor blood pressure weekly"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "prescription": {
      "id": "RX-001",
      "status": "pending",
      "createdAt": "2026-07-17T09:20:00Z"
    }
  }
}
GET /api/consultations/patient/{patientId}
Description: Get consultation history for a patient
Access: Authenticated (Doctor, Nurse, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "consultations": [
      {
        "id": "CON-001",
        "date": "2026-07-17",
        "doctor": "Dr. Elena Santos",
        "diagnosis": "Hypertension - Stage 1",
        "treatment": "Continue Amlodipine 5mg daily",
        "followUp": "2026-08-17"
      }
    ]
  }
}
4.PRESCRIPTIONS
POST /api/prescriptions
Description: Create new prescription
Access: Authenticated (Doctor only)

Request Body:
{
  "patientId": "P-101",
  "consultationId": "CON-001",
  "medications": [
    {
      "name": "Amlodipine",
      "dosage": "5mg",
      "frequency": "Once daily",
      "duration": "30 days",
      "quantity": 30,
      "instructions": "Take one tablet daily in the morning"
    }
  ],
  "notes": "Monitor blood pressure weekly"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "prescription": {
      "id": "RX-001",
      "status": "pending",
      "createdAt": "2026-07-17T09:20:00Z"
    }
  }
}
GET /api/prescriptions/patient/{patientId}
Description: Get patient prescriptions
Access: Authenticated (Doctor, Pharmacist, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "prescriptions": [
      {
        "id": "RX-001",
        "date": "2026-07-17",
        "doctor": "Dr. Elena Santos",
        "medications": [
          {
            "name": "Amlodipine",
            "dosage": "5mg",
            "frequency": "Once daily",
            "duration": "30 days"
          }
        ],
        "status": "dispensed",
        "dispensedBy": "Pharmacist Maria Cruz",
        "dispensedAt": "2026-07-17T10:00:00Z"
      }
    ]
  }
}
PATCH /api/prescriptions/{id}/dispense
Description: Mark prescription as dispensed
Access: Authenticated (Pharmacist only)

Request Body:
{
  "dispensedBy": "Pharmacist Maria Cruz",
  "notes": "Patient received medication"
}

Response (200 OK):
{
  "status": "success",
  "message": "Prescription dispensed successfully"
}
5. TRIAGE
POST /api/triage
Description: Record patient triage
Access: Authenticated (Nurse only)

Request Body:
{
  "patientId": "P-101",
  "vitalSigns": {
    "bloodPressure": "140/90",
    "heartRate": 82,
    "temperature": 36.5,
    "respiratoryRate": 18,
    "oxygenSaturation": 98,
    "weight": 75.5,
    "height": 175
  },
  "symptoms": ["Headache", "Dizziness"],
  "priority": "Medium",
  "allergies": ["None"],
  "medications": ["Amlodipine 5mg"],
  "notes": "Patient conscious and oriented"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "triage": {
      "id": "TRI-001",
      "priority": "Medium",
      "createdAt": "2026-07-17T08:45:00Z"
    }
  }
}
6.REFERALS
POST /api/referrals
Description: Create patient referral
Access: Authenticated (Doctor only)

Request Body:
{
  "patientId": "P-101",
  "fromDoctorId": 1,
  "toDoctorId": 2,
  "toHospital": "Caloocan City Medical Center",
  "reason": "Cardiologist consultation needed",
  "diagnosis": "Hypertension with possible heart complications",
  "urgency": "High",
  "notes": "Patient needs ECG and cardiologist evaluation"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "referral": {
      "id": "REF-001",
      "status": "pending",
      "createdAt": "2026-07-17T09:25:00Z"
    }
  }
}

GET /api/referrals/patient/{patientId}
Description: Get patient referrals
Access: Authenticated (Doctor, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "referrals": [
      {
        "id": "REF-001",
        "date": "2026-07-17",
        "fromDoctor": "Dr. Elena Santos",
        "toDoctor": "Dr. Miguel Reyes (Cardiologist)",
        "toHospital": "Caloocan City Medical Center",
        "reason": "Cardiologist consultation needed",
        "status": "pending",
        "followUp": "2026-07-24"
      }
    ]
  }
}

7.MEDICAL RECORDS
GET /api/medical-records/{patientId}
Description: Get complete medical records of patient
Access: Authenticated (Doctor, Admin)

Response (200 OK):
{
  "status": "success",
  "data": {
    "patient": {
      "id": "P-101",
      "name": "Pedro Garcia"
    },
    "records": {
      "consultations": [...],
      "prescriptions": [...],
      "labResults": [...],
      "immunizations": [...],
      "allergies": ["None"],
      "conditions": ["Hypertension"],
      "surgeries": ["None"],
      "familyHistory": "Mother - Diabetes Type 2"
    }
  }
}

MODULE 2: SANITATION PERMITS API
1. Permits
Get Permits
text
GET /api/permits
Description: Get list of sanitation permits
Access: Authenticated (Sanitation Officer, Inspector, Clerk)

Query Parameters:
- status: "pending|approved|rejected|expired"
- type: "Food Establishment|Market Vendor|Bakery"
- dateFrom: "2026-01-01"
- dateTo: "2026-12-31"
- search: "ABC Restaurant"

Response (200 OK):
{
  "status": "success",
  "data": {
    "permits": [
      {
        "id": "SP-1040",
        "applicant": "ABC Restaurant",
        "type": "Food Establishment",
        "address": "123 Rizal St.",
        "dateApplied": "2026-06-20",
        "status": "pending",
        "inspector": "Unassigned",
        "fee": 1500.00,
        "paid": false
      }
    ]
  }
}
Create Permit Application
text
POST /api/permits
Description: Submit new permit application
Access: Authenticated (Clerk, Citizen)

Request Body:
{
  "applicant": "ABC Restaurant",
  "businessType": "Food Establishment",
  "address": "123 Rizal St.",
  "ownerName": "Juan Dela Cruz",
  "contact": "09123456789",
  "email": "abc.restaurant@email.com",
  "documents": [
    {
      "type": "Business Registration",
      "fileUrl": "https://storage.hsms.com/docs/abc_business_reg.pdf"
    },
    {
      "type": "Floor Plan",
      "fileUrl": "https://storage.hsms.com/docs/abc_floor_plan.pdf"
    }
  ],
  "fee": 1500.00
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "permit": {
      "id": "SP-1044",
      "status": "pending",
      "createdAt": "2026-07-17T10:30:00Z"
    }
  }
}
Update Permit Status
text
PATCH /api/permits/{id}/status
Description: Update permit status
Access: Authenticated (Sanitation Officer)

Request Body:
{
  "status": "approved",
  "notes": "All requirements met",
  "inspector": "Juan Dela Cruz",
  "inspectionDate": "2026-07-20"
}

Response (200 OK):
{
  "status": "success",
  "message": "Permit status updated",
  "data": {
    "permit": {
      "id": "SP-1044",
      "status": "approved",
      "inspector": "Juan Dela Cruz"
    }
  }
}
2. Inspections
Create Inspection
text
POST /api/inspections
Description: Schedule/create new inspection
Access: Authenticated (Sanitation Officer, Inspector)

Request Body:
{
  "permitId": "SP-1040",
  "inspectorId": 5,
  "date": "2026-07-25",
  "time": "10:00 AM",
  "type": "Initial",
  "address": "123 Rizal St."
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "inspection": {
      "id": "INS-503",
      "status": "scheduled",
      "createdAt": "2026-07-17T11:00:00Z"
    }
  }
}
Submit Inspection Report
text
POST /api/inspections/{id}/report
Description: Submit inspection findings
Access: Authenticated (Sanitation Inspector)

Request Body:
{
  "findings": [
    {
      "category": "Sanitation",
      "status": "compliant",
      "notes": "All sanitation requirements met"
    },
    {
      "category": "Food Safety",
      "status": "non-compliant",
      "notes": "Food storage temperature not maintained"
    }
  ],
  "overallStatus": "partially-compliant",
  "recommendations": "Fix food storage issues within 7 days",
  "attachments": [
    "https://storage.hsms.com/photos/inspection_abc_1.jpg"
  ]
}

Response (200 OK):
{
  "status": "success",
  "data": {
    "inspection": {
      "id": "INS-503",
      "status": "completed",
      "overallStatus": "partially-compliant"
    }
  }
}
3. Payments
Process Payment
text
POST /api/payments
Description: Process permit payment
Access: Authenticated (Cashier)

Request Body:
{
  "permitId": "SP-1044",
  "amount": 1500.00,
  "method": "GCash",
  "referenceNumber": "GCH-20260717-001",
  "paidBy": "Juan Dela Cruz"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "payment": {
      "id": "PAY-001",
      "amount": 1500.00,
      "status": "completed",
      "receipt": "https://storage.hsms.com/receipts/RCP-001.pdf"
    }
  }
}
💉 MODULE 3: IMMUNIZATION API
1. Children
Get Children
text
GET /api/children
Description: Get list of children
Access: Authenticated (Immunization Coordinator, Midwife)

Response (200 OK):
{
  "status": "success",
  "data": {
    "children": [
      {
        "id": "CH-001",
        "name": "Sofia Garcia",
        "age": "2 yrs",
        "gender": "Female",
        "mother": "Rosa Mendoza",
        "contact": "09123456789",
        "address": "123 Rizal St.",
        "vaccines": 75,
        "nextDue": "MMR Booster",
        "nutritionRisk": "Low",
        "weight": "12.4 kg"
      }
    ]
  }
}
Register Child
text
POST /api/children
Description: Register new child
Access: Authenticated (Midwife, Coordinator)

Request Body:
{
  "name": "Noah Torres",
  "gender": "Male",
  "birthDate": "2026-01-15",
  "motherName": "Elena Torres",
  "motherContact": "09123456789",
  "address": "456 Mabini Ave.",
  "birthWeight": 3.2,
  "birthHeight": 50,
  "bloodType": "O+"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "child": {
      "id": "CH-005",
      "createdAt": "2026-07-17T09:00:00Z"
    }
  }
}
2. Vaccinations
Record Vaccination
text
POST /api/vaccinations
Description: Record vaccine administration
Access: Authenticated (Midwife, Nurse)

Request Body:
{
  "childId": "CH-001",
  "vaccineName": "BCG",
  "dose": 1,
  "date": "2026-07-17",
  "administeredBy": "Midwife Maria Cruz",
  "healthCenter": "Health Center 1",
  "batchNumber": "BCG-2026-01",
  "expiryDate": "2027-12-31",
  "nextDueDate": "2026-08-17",
  "notes": "Good response"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "vaccination": {
      "id": "VAC-001",
      "createdAt": "2026-07-17T09:15:00Z"
    }
  }
}
Get Child Vaccination Schedule
text
GET /api/vaccinations/child/{childId}/schedule
Description: Get vaccination schedule for child
Access: Authenticated (Midwife, Coordinator)

Response (200 OK):
{
  "status": "success",
  "data": {
    "schedule": [
      {
        "vaccine": "BCG",
        "dueDate": "2026-01-15",
        "status": "completed",
        "dateCompleted": "2026-01-15"
      },
      {
        "vaccine": "DPT 1st Dose",
        "dueDate": "2026-03-15",
        "status": "completed",
        "dateCompleted": "2026-03-15"
      },
      {
        "vaccine": "MMR Booster",
        "dueDate": "2026-07-28",
        "status": "pending",
        "daysUntilDue": 11
      }
    ]
  }
}
3. Vaccine Inventory
Get Vaccine Inventory
text
GET /api/vaccine-inventory
Description: Get vaccine stock
Access: Authenticated (Vaccine Manager, Coordinator)

Response (200 OK):
{
  "status": "success",
  "data": {
    "inventory": [
      {
        "id": "INV-001",
        "vaccineName": "BCG",
        "batchNumber": "BCG-2026-01",
        "quantity": 150,
        "minimumStock": 50,
        "expiryDate": "2027-12-31",
        "temperature": 2.5,
        "status": "in-stock",
        "reorderLevel": 75
      }
    ],
    "alerts": [
      {
        "vaccine": "DPT",
        "quantity": 12,
        "alert": "Low stock - reorder immediately"
      }
    ]
  }
}
Update Vaccine Stock
text
PATCH /api/vaccine-inventory/{id}
Description: Update vaccine stock
Access: Authenticated (Vaccine Manager)

Request Body:
{
  "quantity": 120,
  "temperature": 2.5,
  "notes": "Received new shipment"
}

Response (200 OK):
{
  "status": "success",
  "message": "Inventory updated"
}
🏭 MODULE 4: WASTEWATER API
1. Septic Tanks
Get Septic Tanks
text
GET /api/septic-tanks
Description: Get list of septic tanks
Access: Authenticated (Wastewater Officer, Clerk)

Response (200 OK):
{
  "status": "success",
  "data": {
    "septicTanks": [
      {
        "id": "ST-001",
        "owner": "Pedro Garcia",
        "address": "123 Rizal St.",
        "latitude": 14.6542,
        "longitude": 120.9821,
        "capacity": "1200L",
        "type": "Concrete",
        "lastMaintenance": "2026-03-15",
        "status": "good"
      }
    ]
  }
}
2. Service Requests
Create Service Request
text
POST /api/service-requests
Description: Request wastewater service
Access: Authenticated (Citizen, Clerk)

Request Body:
{
  "tankId": "ST-001",
  "serviceType": "Desludging",
  "preferredDate": "2026-07-25",
  "preferredTime": "09:00 AM",
  "notes": "Accessible from front gate"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "request": {
      "id": "SR-001",
      "status": "pending",
      "createdAt": "2026-07-17T10:00:00Z"
    }
  }
}
🦟 MODULE 5: SURVEILLANCE API
1. Cases
Report Case
text
POST /api/cases
Description: Report new disease case
Access: Authenticated (Surveillance Officer, Field Investigator)

Request Body:
{
  "disease": "Dengue Fever",
  "patientName": "Juan Dela Cruz",
  "age": 34,
  "gender": "Male",
  "address": "123 Rizal St.",
  "barangay": "Barangay San Jose",
  "symptoms": ["High fever", "Headache", "Joint pain"],
  "onsetDate": "2026-07-14",
  "reportingFacility": "Health Center 1",
  "status": "confirmed",
  "severity": "Moderate",
  "contactTracing": {
    "householdContacts": 4,
    "workContacts": 10
  }
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "case": {
      "id": "CS-005",
      "createdAt": "2026-07-17T09:30:00Z"
    }
  }
}
Get Cases by Barangay
text
GET /api/cases/barangay/{barangay}
Description: Get cases by barangay
Access: Authenticated (Surveillance Officer)

Response (200 OK):
{
  "status": "success",
  "data": {
    "barangay": "Barangay San Jose",
    "cases": [
      {
        "disease": "Dengue Fever",
        "cases": 12,
        "date": "2026-06-22",
        "severity": "Moderate"
      }
    ],
    "trend": "Rising"
  }
}
2. Outbreaks
Create Outbreak Alert
text
POST /api/outbreaks
Description: Create outbreak alert
Access: Authenticated (Surveillance Officer)

Request Body:
{
  "disease": "Dengue Fever",
  "barangays": ["Barangay San Jose", "Barangay Riverside"],
  "cases": 25,
  "severity": "High",
  "startDate": "2026-07-10",
  "status": "active",
  "recommendations": ["Immediate fogging", "Community education"],
  "emergency": true
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "outbreak": {
      "id": "OUT-001",
      "alertLevel": "Red",
      "createdAt": "2026-07-17T08:00:00Z"
    }
  }
}
👨‍💼 SYSTEM ADMIN API
1. Users
Get Users
text
GET /api/users
Description: Get list of users
Access: Authenticated (Admin only)

Response (200 OK):
{
  "status": "success",
  "data": {
    "users": [
      {
        "id": 1,
        "name": "Maria Santos",
        "email": "maria.santos@caloocan.gov.ph",
        "role": "admin",
        "status": "active",
        "lastLogin": "2026-07-17T08:00:00Z",
        "createdAt": "2024-01-15T08:30:00Z"
      }
    ]
  }
}
Create User
text
POST /api/users
Description: Create new user
Access: Authenticated (Admin only)

Request Body:
{
  "name": "Anna Reyes",
  "email": "anna.reyes@caloocan.gov.ph",
  "password": "TempPass123!",
  "role": "nurse",
  "department": "Health Center 1"
}

Response (201 Created):
{
  "status": "success",
  "data": {
    "user": {
      "id": 7,
      "name": "Anna Reyes",
      "role": "nurse",
      "createdAt": "2026-07-17T11:00:00Z"
    }
  }
}
Update User Role
text
PATCH /api/users/{id}/role
Description: Update user role
Access: Authenticated (Admin only)

Request Body:
{
  "role": "doctor",
  "department": "Health Center 2"
}

Response (200 OK):
{
  "status": "success",
  "message": "User role updated"
}
2. System Logs
Get Audit Logs
text
GET /api/logs
Description: Get system audit logs
Access: Authenticated (Admin only)

Query Parameters:
- startDate: "2026-07-01"
- endDate: "2026-07-17"
- user: 1
- action: "login|view|edit|delete"
- module: "patients|permits|vaccines"

Response (200 OK):
{
  "status": "success",
  "data": {
    "logs": [
      {
        "id": 1,
        "timestamp": "2026-07-17T09:15:32Z",
        "user": "Dr. Elena Santos",
        "action": "view",
        "module": "patients",
        "target": "P-101",
        "ipAddress": "192.168.1.1",
        "details": "Viewed patient record"
      }
    ],
    "pagination": {
      "total": 1250,
      "page": 1,
      "limit": 20
    }
  }
}

Success Response
json
{
  "status": "success",
  "data": { ... },
  "message": "Optional success message"
}
Error Response
json
{
  "status": "error",
  "message": "Error description",
  "code": "ERR_001",
  "details": {}
}
Paginated Response
json
{
  "status": "success",
  "data": {
    "items": [],
    "pagination": {
      "total": 100,
      "page": 1,
      "limit": 20,
      "pages": 5
    }
  }
}

🔒 API SECURITY
Headers Required
text
Authorization: Bearer {jwt_token}
Content-Type: application/json
Accept: application/json
X-API-Key: {optional_api_key}
Rate Limiting
text
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 2026-07-17T12:00:00Z
Error Codes
text
AUTH_001: Invalid credentials
AUTH_002: Token expired
AUTH_003: Insufficient permissions
ERR_404: Resource not found
ERR_400: Bad request
ERR_500: Internal server error

TECHNOLOGY STACK
│
├── 🖥️ FRONTEND
│   ├── Admin/Staff Web App
│   │   ├── Option A: html + Tailwind CSS + js
│   │   
│   │   
│   │   
│   │
│   └── Citizen Mobile App
│       ├── Option A: React Native & Expo go
│       ├── Option B: Flutter
│       
│       
│
├── 🖥️ BACKEND 
│   ├── Option C: PHP 
│   
│   
│
├── 🗄️ DATABASE
│   ├── Relational: PostgreSQL / MySQL
│   
│
├── ☁️ HOSTING/DEPLOYMENT
│   ├── Cloud: Hostinger
│   └── Local: On-premise Server
│
└── 🔧 DEVELOPMENT TOOLS
    ├── Version Control: Git + GitHub
    ├── API Testing: Postman 
    └── CI/CD: GitHub Actions 

    SYSTEM FLOW DIAGRAMS
Missing: Visual Process Flows
text
FLOW DIAGRAMS NEEDED
│
├── 📊 USER FLOW DIAGRAMS
│   ├── Admin Login Flow
│   ├── Staff Workflow
│   ├── Citizen Journey
│   └── Patient Registration Flow
│
├── 🔄 PROCESS FLOW DIAGRAMS
│   ├── Appointment Booking Process
│   ├── Permit Application Process
│   ├── Vaccination Process
│   ├── Service Request Process
│   ├── Outbreak Detection Process
│   └── Contact Tracing Process
│
├── 📈 DATA FLOW DIAGRAMS (DFD)
│   ├── Level 0: Context Diagram
│   ├── Level 1: Main Modules
│   └── Level 2: Detailed Data Flows
│
└── 🏗️ SYSTEM ARCHITECTURE DIAGRAMS
    ├── 3-Tier Architecture
    ├── Deployment Diagram
    └── Component Diagram


    TESTING PLAN
Missing: How to test the system?
text
TESTING PLAN
│
├── 🔬 UNIT TESTING
│   ├── Backend API Testing (Jest/Mocha)
│   ├── Frontend Component Testing
│   └── Database Query Testing
│
├── 🔗 INTEGRATION TESTING
│   ├── API Integration
│   ├── Database Integration
│   └── Module Integration
│
├── 📱 SYSTEM TESTING
│   ├── Functional Testing
│   ├── Performance Testing
│   ├── Security Testing
│   └── Usability Testing
│
├── ✅ USER ACCEPTANCE TESTING (UAT)
│   ├── Admin Testing
│   ├── Staff Testing
│   └── Citizen Testing
│
└── 📊 TEST CASES
    ├── Test Case Template
    ├── Sample Test Cases
    └── Bug Tracking System

    DEPLOYMENT STRATEGY
Missing: How to deploy the system?
text
DEPLOYMENT STRATEGY
│
├── 🚀 DEPLOYMENT ENVIRONMENTS
│   ├── Development Environment
│   ├── Testing/Staging Environment
│   └── Production Environment
│
├── 📦 DEPLOYMENT STEPS
│   ├── 1. Server Setup
│   ├── 2. Database Installation
│   ├── 3. Backend Deployment
│   ├── 4. Frontend Deployment
│   ├── 5. SSL Certificate Setup
│   └── 6. Domain Configuration
│
├── 🔄 CI/CD PIPELINE
│   ├── Code Commit → GitHub
│   ├── Automated Testing
│   ├── Build Process
│   ├── Deploy to Staging
│   └── Deploy to Production
│
└── 📋 DEPLOYMENT CHECKLIST
    ├── Server Requirements
    ├── Software Dependencies
    ├── Environment Variables
    └── Database Migration


PROJECT TIMELINE
Missing: Development Schedule
text
PROJECT TIMELINE (6 Months)
│
├── PHASE 1: PLANNING (2 Weeks)
│   ├── Requirements Gathering
│   ├── System Design
│   └── Technology Selection
│
├── PHASE 2: DEVELOPMENT (3 Months)
│   ├── Sprint 1: Authentication & User Management
│   ├── Sprint 2: Health Center Module
│   ├── Sprint 3: Sanitation Module
│   ├── Sprint 4: Immunization Module
│   ├── Sprint 5: Wastewater Module
│   ├── Sprint 6: Surveillance Module
│   └── Sprint 7: Mobile App
│
├── PHASE 3: TESTING (1 Month)
│   ├── Unit Testing
│   ├── Integration Testing
│   ├── User Acceptance Testing
│   └── Bug Fixes
│
├── PHASE 4: DEPLOYMENT (1 Month)
│   ├── Deployment Setup
│   ├── Data Migration
│   ├── Training
│   └── Go-Live
│
└── PHASE 5: MAINTENANCE (Ongoing)
    ├── Bug Fixes
    ├── Updates
    └── Support


    BUDGET ESTIMATION
Missing: Cost Analysis
text
BUDGET ESTIMATION
│
├── 💻 DEVELOPMENT COSTS
│   ├── Developer Salaries: ₱200,000 - ₱500,000
│   ├── UI/UX Designer: ₱50,000 - ₱100,000
│   ├── QA Testing: ₱30,000 - ₱50,000
│   └── Project Manager: ₱50,000 - ₱80,000
│
├── 🖥️ INFRASTRUCTURE COSTS (Annual)
│   ├── Server Hosting: ₱60,000 - ₱120,000
│   ├── Database Hosting: ₱30,000 - ₱60,000
│   ├── SSL Certificate: ₱5,000 - ₱10,000
│   └── Domain Name: ₱1,000 - ₱2,000
│
├── 📱 THIRD-PARTY SERVICES (Annual)
│   ├── SMS Gateway: ₱20,000 - ₱50,000
│   ├── Email Service: ₱10,000 - ₱20,000
│   ├── Payment Gateway: ₱10,000 - ₱30,000
│   └── Cloud Storage: ₱5,000 - ₱10,000
│
└── 📋 TOTAL ESTIMATED BUDGET
    └── ₱500,000 - ₱1,000,000


    RISK ASSESSMENT
Missing: Potential Risks and Mitigations
text
RISK ASSESSMENT
│
├── 🚨 TECHNICAL RISKS
│   ├── Risk: System crashes under heavy load
│   │   └── Mitigation: Load testing, scalability planning
│   │
│   ├── Risk: Data breach / Security vulnerability
│   │   └── Mitigation: Security audits, encryption, penetration testing
│   │
│   └── Risk: Technology becomes obsolete
│       └── Mitigation: Use popular, well-supported technologies
│
├── 👥 OPERATIONAL RISKS
│   ├── Risk: Staff resistance to using system
│   │   └── Mitigation: Training, user-friendly interface
│   │
│   ├── Risk: Data migration errors
│   │   └── Mitigation: Data validation, backup, incremental migration
│   │
│   └── Risk: System downtime
│       └── Mitigation: Backup systems, disaster recovery plan
│
├── 📋 COMPLIANCE RISKS
│   ├── Risk: Data Privacy Act violation
│   │   └── Mitigation: DPA compliance, consent management
│   │
│   └── Risk: Non-compliance with DOH regulations
│       └── Mitigation: DOH consultation, regular compliance checks
│
└── 💰 PROJECT RISKS
    ├── Risk: Budget overrun
    │   └── Mitigation: Regular budget review, contingency fund
    │
    └── Risk: Project delay
        └── Mitigation: Agile methodology, regular progress monitoring



        USER MANUAL / TRAINING
Missing: Documentation for Users
text
USER MANUAL
│
├── 📖 ADMIN MANUAL
│   ├── How to manage users
│   ├── How to configure settings
│   ├── How to generate reports
│   └── How to monitor system health
│
├── 👨‍⚕️ STAFF MANUAL
│   ├── How to register patients
│   ├── How to schedule appointments
│   ├── How to conduct consultations
│   ├── How to process permits
│   └── How to record vaccinations
│
├── 👤 CITIZEN MANUAL
│   ├── How to book appointments
│   ├── How to apply for permits
│   ├── How to view vaccines
│   └── How to track requests
│
└── 🎓 TRAINING PROGRAM
    ├── Admin Training
    ├── Staff Training
    └── Citizen Orientation

✅ SECURITY FEATURES TO DOCUMENT



2. ACCESS CONTROL
   ├── Role-Based Access Control (RBAC)
   ├── Permission Matrix
   ├── User Management
   └── Access Revocation

3. DATA ENCRYPTION
   ├── At Rest (Database)
   ├── In Transit (Network)
   └── Data Masking

4. AUDIT TRAIL
   ├── Activity Logging
   ├── Access Logging
   ├── Suspicious Activity Alerts
   └── Report Generation

5. DATA PRIVACY
   ├── DPA Compliance
   ├── Patient Consent
   ├── Data Subject Rights
   └── Privacy Policy

6. ADDITIONAL SECURITY
   ├── 2FA (Recommended for future)
   ├── IP Whitelisting
   ├── Security Headers
   └── Regular Security Audits
   ┌─────────────────────────────────────────────────────────────────────┐
│                    SECURITY FEATURES CHECKLIST                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│                                               │
│  ✅ ROLE-BASED ACCESS CONTROL (RBAC)                               │
│  ✅ PERMISSION MATRIX                                              │
│  ✅ DATA ENCRYPTION (At Rest & In Transit)                         │
│  ✅ DATA MASKING                                                   │
│  ✅ AUDIT TRAIL                                                    │
│  ✅ USER ACTIVITY LOGGING                                          │
│  ✅ FAILED LOGIN LOCKOUT                                           │
│  ✅ ACCOUNT ACTIVATION/DEACTIVATION                               │
│  ✅ DATA PRIVACY CONSENT                                          │
│  ✅ DPA COMPLIANCE                                                │
│  ✅ SECURITY ALERTS                                               │
│  ✅ PASSWORD RESET/RECOVERY                                       │
│                                                                      │
│  RECOMMENDED FOR FUTURE:                                          │
│  ⭐ TWO-FACTOR AUTHENTICATION (2FA)                               │
│  ⭐ IP WHITELISTING                                               │
│  ⭐ BIOMETRIC LOGIN                                               │
│  ⭐ ADVANCED ENCRYPTION                                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

    SECURITY & AUTHENTICATION
    SECURITY GAPS
│
├── ❌ USER AUTHENTICATION
│   ├── Login System (Username/Password)
│   ├── Multi-Factor Authentication (MFA)
│   ├── Password Reset/Recovery
│   ├── Session Management
│   └── Remember Me / Stay Logged In
│
├── ❌ ACCESS CONTROL
│   ├── Permission Matrix UI
│   ├── Role Assignment Interface
│   ├── User Approval Workflow
│   └── Access Revocation
│
├── ❌ DATA SECURITY
│   ├── Data Encryption (At Rest & In Transit)
│   ├── Audit Trail Details
│   ├── IP Whitelisting
│   └── Failed Login Attempts Lockout
│
└── ❌ COMPLIANCE
    ├── Data Privacy Act Compliance
    ├── User Consent Management
    ├── Data Sharing Agreements
    └── GDPR/DPA Compliance Reports
DATA ENCRYPTION
│
├── 🔒 ENCRYPTION AT REST (Database)
│   ├── Patient Names → AES-256 Encryption
│   ├── Addresses → AES-256 Encryption
│   ├── Contact Numbers → AES-256 Encryption
│   ├── Medical History → AES-256 Encryption
│   ├── Diagnoses → AES-256 Encryption
│   └── Prescriptions → AES-256 Encryption
│
├── 🔒 ENCRYPTION IN TRANSIT (Network)
│   ├── HTTPS/SSL/TLS Protocol
│   ├── All API Requests Encrypted
│   ├── All Responses Encrypted
│   └── No Plain Text Transmission
│
└── 🔒 SENSITIVE DATA MASKING
    ├── Partial Display: P*** G*****
    ├── Phone: 0912*******
    ├── Email: p*****@gmail.com
    └── Address: *** Rizal St.
ADDITIONAL SECURITY
│
├── 🔐 TWO-FACTOR AUTHENTICATION (2FA)
│   ├── SMS OTP
│   ├── Email OTP
│   └── Authenticator App
│
├── 🛡️ DATA ACCESS RESTRICTIONS
│   ├── IP-Based Restrictions
│   ├── Time-Based Access
│   ├── Device-Based Access
│   └── Location-Based Access
│
├── 📝 DATA MASKING RULES
│   ├── Name: Partial (P*** G*****)
│   ├── Phone: Partial (0912*******)
│   ├── Email: Partial (p****@gmail.com)
│   ├── Address: Partial (*** Rizal St.)
│   └── ID Number: Partial (*****-****)
│
└── 🚨 SECURITY ALERTS
    ├── Failed Login Attempts
    ├── Unauthorized Access Attempts
    ├── Bulk Data Export
    ├── Off-Hours Access
    ├── Unusual Access Patterns
    └── Administrative Actions

# Prescriptions Backend Implementation

## Overview
Successfully implemented backend functionality for the prescriptions module using your existing MVC architecture and Supabase database.

## Files Created/Modified

### 1. **app/Controllers/PrescriptionController.php** (NEW)
- Main controller handling all prescription operations
- Methods: `index()`, `show()`, `store()`, `update()`, `destroy()`, `dispense()`, `search()`
- Enriches prescription data with patient names, doctor names, and formatted dates
- Maps frontend field names to database schema
- Validates required fields and checks for existing patients/employees

### 2. **api/prescriptions.php** (NEW)
- RESTful API endpoint for prescriptions
- Supports GET, POST, PUT, DELETE methods
- Handles CORS for frontend access
- Routes requests to appropriate controller methods

### 3. **app/Controllers/EmployeeController.php** (NEW)
- Controller for employee data (needed for doctor selection)
- Methods: `index()`, `show()`, `search()`

### 4. **api/employees.php** (NEW)
- RESTful API endpoint for employees
- Provides employee data for dropdowns in prescription forms

### 5. **modules/healthservices/prescriptions.php** (MODIFIED)
- Replaced hardcoded sample data with dynamic API calls
- Fetches prescriptions, patients, and doctors from backend
- Implements client-side pagination
- Real-time search and filter functionality
- All CRUD operations now use fetch() API calls
- Maintains existing UI/UX design

### 6. **app/Models/Prescription.php** (MODIFIED)
- Added `dispense()` method to handle prescription dispensing
- Updates status, dispensed_by, and dispensed_at fields

## Database Schema Alignment

Your Supabase `prescriptions` table schema is fully supported:
- ✅ `id` (serial, primary key)
- ✅ `prescription_id` (varchar, auto-generated)
- ✅ `patient_id` (integer, foreign key)
- ✅ `employee_id` (integer, foreign key to doctors)
- ✅ `consultation_id` (integer, optional)
- ✅ `date` (date, defaults to CURRENT_DATE)
- ✅ `medications` (jsonb, stores medication array)
- ✅ `notes` (text, optional)
- ✅ `status` (text: pending/dispensed/cancelled)
- ✅ `dispensed_by` (integer, foreign key to employees)
- ✅ `dispensed_at` (timestamp)
- ✅ `created_at` (timestamp, auto-generated)
- ✅ `updated_at` (timestamp, auto-updated)

## Features Implemented

### Frontend Features
1. **Dashboard Stats** - Real-time counts of total, dispensed, pending prescriptions
2. **Search & Filter** - Search by patient name, prescription ID, or medication
3. **Pagination** - Client-side pagination with 5 items per page
4. **Create Prescription** - Modal form with patient/doctor selection and medication management
5. **View Prescription** - Detailed view modal with all prescription information
6. **Edit Prescription** - Edit modal for updating prescription details
7. **Dispense Prescription** - One-click dispensing with confirmation
8. **Cancel Prescription** - Soft delete by updating status to 'cancelled'
9. **Medication Management** - Add/remove medications with dosage, frequency, duration
10. **Toast Notifications** - Success/error feedback for all actions

### Backend Features
1. **RESTful API** - Standard HTTP methods (GET, POST, PUT, DELETE)
2. **Data Validation** - Required field validation on server side
3. **Foreign Key Validation** - Verifies patient and employee exist
4. **Data Enrichment** - Automatically joins related data (patient names, doctor names)
5. **JSONB Support** - Handles medications as JSONB in database
6. **Auto-generated IDs** - Prescription IDs follow RX-XXX format
7. **Error Handling** - Comprehensive error logging and user-friendly messages

## API Endpoints

### Prescriptions
- `GET /api/prescriptions.php` - List all prescriptions
- `GET /api/prescriptions.php/{id}` - Get single prescription
- `GET /api/prescriptions.php?q={query}` - Search prescriptions
- `POST /api/prescriptions.php` - Create new prescription
- `PUT /api/prescriptions.php/{id}` - Update prescription
- `DELETE /api/prescriptions.php/{id}` - Cancel prescription

### Employees
- `GET /api/employees.php` - List all employees
- `GET /api/employees.php/{id}` - Get single employee
- `GET /api/employees.php?q={query}` - Search employees

## Architecture

```
Frontend (prescriptions.php)
    ↓ fetch() API calls
API Endpoints (api/prescriptions.php, api/employees.php)
    ↓ route to
Controllers (PrescriptionController, EmployeeController)
    ↓ use
Models (Prescription, Employee, Patient)
    ↓ query via
Database (Supabase via Database class)
```

## Testing Checklist

- [ ] Load prescriptions page - should fetch data from API
- [ ] Create new prescription - should save to database
- [ ] View prescription details - should load from API
- [ ] Edit prescription - should update in database
- [ ] Dispense prescription - should update status to 'dispensed'
- [ ] Cancel prescription - should update status to 'cancelled'
- [ ] Search prescriptions - should filter results
- [ ] Filter by status - should show only matching status
- [ ] Pagination - should navigate through pages

## Notes

1. **Current User ID**: The module uses `$_SESSION['user_id']` for the current user. Adjust this based on your authentication system.

2. **Drug Database**: Currently uses sample data. Consider creating a `drugs` or `medications` table in Supabase for production use.

3. **Authentication**: API endpoints currently skip authentication. Add AuthMiddleware when ready.

4. **Employee Model**: Falls back to `users` table if `employees` table is not accessible (see Employee.php model).

5. **Soft Delete**: Prescriptions are not hard-deleted; they're marked as 'cancelled' to maintain audit trail.

## Next Steps

1. Test all CRUD operations with real data
2. Add authentication middleware to API endpoints
3. Create medications/drugs API endpoint for production
4. Add prescription printing/PDF generation
5. Implement prescription history tracking
6. Add email notifications for prescription status changes




capstone/
├── app/                          ← Core application logic
│   ├── Models/                  ← Database models
│   │   ├── Patient.php
│   │   ├── Employee.php
│   │   ├── Appointment.php
│   │   └── BaseModel.php
│   ├── Controllers/              ← Business logic
│   │   ├── PatientController.php
│   │   ├── AuthController.php
│   │   └── DashboardController.php
│   ├── Services/                 ← External services
│   │   ├── DatabaseService.php
│   │   └── SupabaseService.php
│   └── Utils/                    ← Helpers
│       ├── Auth.php
│       ├── Validation.php
│       └── Response.php
├── config/                       ← Configuration
│   ├── database.php
│   ├── routes.php                ← URL routing
│   └── .env                      ← Environment variables
├── public/                       ← Web root
│   ├── index.php                 ← Front controller
│   ├── login.php
│   ├── assets/
│   └── .htaccess
├── modules/                      ← Your current feature modules
│   ├── healthservices/
│   │   ├── views/               ← Move UI to views/
│   │   └── controllers/         ← Module-specific controllers
│   └── sanitation/
├── includes/                     ← Keep as shared partials
├── management/                   ← Admin modules
├── api/                          ← JSON API endpoints
│   ├── patients.php
│   ├── auth.php
│   └── dashboard.php
├── docs/                         ← Move all .md files here
└── vendor/                       ← Composer dependencies





























.htaccess (URL Rewriting)

apache
RewriteEngine On

# Force HTTPS
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

# Remove .php extension
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [NC,L]

# Admin routes
RewriteRule ^admin/?$ admin/index.php [NC,L]
RewriteRule ^admin/([a-zA-Z0-9_-]+)/?$ admin/modules/$1/index.php [NC,L]

# API routes
RewriteRule ^api/([a-zA-Z0-9_-]+)/?$ mobile/api/$1.php [NC,L]

# 404 Error
ErrorDocument 404 /404.php

# admin/.htaccess
RewriteEngine On

# Remove .php extension
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*?)/?$ $1.php [L]

# Clean URL routing
RewriteRule ^module/([a-zA-Z0-9_-]+)/([a-zA-Z0-9_-]+)/?$ modules/$1/$2.php [L]
RewriteRule ^module/([a-zA-Z0-9_-]+)/?$ modules/$1/index.php [L]

# Example:
# /module/health-center/patients → /admin/modules/health-center/patients.php
# /module/health-center → /admin/modules/health-center/index.php


SAMPLE FILES

1. admin/includes/config.php
php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hsms_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'Health & Sanitation Management System');
define('APP_URL', 'http://localhost/hsms');
define('APP_VERSION', '1.0.0');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>




4. admin/includes/sidebar.php

<aside class="w-64 bg-white border-r border-gray-200">
    <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-blue-600">HSMS</h1>
        <p class="text-xs text-gray-500">Caloocan City Health</p>
    </div>
    
    <nav class="p-4">
        <!-- Main Controls -->
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Main Controls</p>
            
            <!-- Dashboard -->
            <a href="<?php echo APP_URL; ?>/admin/modules/dashboard/" 
               class="flex items-center px-4 py-2 mt-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-chart-pie w-5"></i>
                <span class="ml-3">Dashboard</span>
            </a>
            
            <!-- System Overview Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-eye w-5"></i>
                        <span class="ml-3">System Overview</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/dashboard/kpi.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-chart-bar w-4"></i> Dashboard KPIs
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/dashboard/activity.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-activity w-4"></i> Module Activity
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/dashboard/alerts.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-bell w-4"></i> Alerts & Notifications
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/dashboard/health.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-heartbeat w-4"></i> System Health
                    </a>
                </div>
            </div>
            
            <!-- Analytics -->
            <a href="<?php echo APP_URL; ?>/admin/modules/analytics/" 
               class="flex items-center px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-chart-line w-5"></i>
                <span class="ml-3">Analytics</span>
            </a>
            
            <!-- Reports -->
            <a href="<?php echo APP_URL; ?>/admin/modules/reports/" 
               class="flex items-center px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-file-alt w-5"></i>
                <span class="ml-3">Reports</span>
            </a>
            
            <!-- Compliance -->
            <a href="<?php echo APP_URL; ?>/admin/modules/compliance/" 
               class="flex items-center px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-gavel w-5"></i>
                <span class="ml-3">Compliance</span>
            </a>
        </div>
        
        <!-- Modules -->
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Modules</p>
            
            <!-- Health Center -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-hospital w-5"></i>
                        <span class="ml-3">Health Center</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/patients.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-users w-4"></i> Patients
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/appointments.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-calendar w-4"></i> Appointments
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/consultations.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-stethoscope w-4"></i> Consultations
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/prescriptions.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-prescription w-4"></i> Prescriptions
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/referrals.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-ambulance w-4"></i> Referrals
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/triage.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-heart w-4"></i> Triage
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/health-center/medical-records.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-folder w-4"></i> Medical Records
                    </a>
                </div>
            </div>
            
            <!-- Sanitation -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-clipboard-check w-5"></i>
                        <span class="ml-3">Sanitation</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/sanitation/permits.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-file-signature w-4"></i> Permits
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/sanitation/inspections.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-search w-4"></i> Inspections
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/sanitation/payments.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-money-bill w-4"></i> Payments
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/sanitation/documents.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-file-alt w-4"></i> Documents
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/sanitation/renewals.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-undo w-4"></i> Renewals
                    </a>
                </div>
            </div>
            
            <!-- Immunization -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-syringe w-5"></i>
                        <span class="ml-3">Immunization</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/immunization/children.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-child w-4"></i> Children
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/immunization/vaccinations.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-vaccine w-4"></i> Vaccinations
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/immunization/vaccine-inventory.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-boxes w-4"></i> Vaccine Inventory
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/immunization/growth-charts.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-chart-line w-4"></i> Growth Charts
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/immunization/nutrition.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-apple-alt w-4"></i> Nutrition
                    </a>
                </div>
            </div>
            
            <!-- Wastewater -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-tint w-5"></i>
                        <span class="ml-3">Wastewater</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/wastewater/septic-tanks.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-water w-4"></i> Septic Tanks
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/wastewater/service-requests.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-tools w-4"></i> Service Requests
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/wastewater/maintenance.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-wrench w-4"></i> Maintenance
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/wastewater/providers.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-user-cog w-4"></i> Providers
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/wastewater/billing.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-file-invoice w-4"></i> Billing
                    </a>
                </div>
            </div>
            
            <!-- Surveillance -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                    <span class="flex items-center">
                        <i class="fas fa-binoculars w-5"></i>
                        <span class="ml-3">Surveillance</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div x-show="open" class="ml-4 mt-1">
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/cases.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-file-medical w-4"></i> Cases
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/outbreaks.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-exclamation-triangle w-4"></i> Outbreaks
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/contact-tracing.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-people-arrows w-4"></i> Contact Tracing
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/mapping.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-map w-4"></i> Mapping
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/alerts.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-bell w-4"></i> Alerts
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/modules/surveillance/response.php" 
                       class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                        <i class="fas fa-phone-alt w-4"></i> Response
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Admin -->
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>
            <a href="<?php echo APP_URL; ?>/admin/system/users.php" 
               class="flex items-center px-4 py-2 mt-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-users-cog w-5"></i>
                <span class="ml-3">User Management</span>
            </a>
            <a href="<?php echo APP_URL; ?>/admin/system/logs.php" 
               class="flex items-center px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-history w-5"></i>
                <span class="ml-3">System Logs</span>
            </a>
            <a href="<?php echo APP_URL; ?>/admin/system/settings.php" 
               class="flex items-center px-4 py-2 mt-1 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg">
                <i class="fas fa-cog w-5"></i>
                <span class="ml-3">Settings</span>
            </a>
        </div>
    </nav>
</aside>




Main Actors
Citizens / Patients
Health Center Staff
Sanitary Inspectors
Barangay Health Workers (BHWs)
Doctors / Nurses / Midwives
Environmental / Wastewater Personnel
Health Administrator / CHO Head
System Administrator
MODULE 1 — Health Center Services
Who enters data?
Receptionist
Nurse
Doctor
Midwife
Laboratory staff
What data is entered?
Patient registration
Consultation notes
Diagnosis
Prescriptions
Referrals
Laboratory results
Medical certificates
Who uses the data?
Doctors
Nurses
Health Administrator
Reports Module
Health Surveillance Module

Flow:

Citizen
      │
Registers
      │
Receptionist
      │
Doctor/Nurse
      │
Medical Record
      │
Reports
Analytics
Health Surveillance
MODULE 2 — Sanitation Permits
Who enters data?
Business Owner (application)
Sanitation Inspector
Permit Officer
Data
Business information
Permit application
Inspection findings
Violations
Permit approval
Renewal
Who uses it?
Permit Office
Compliance Module
Reports
Analytics

Flow

Business Owner
      │
Permit Application
      │
Inspector
      │
Inspection Report
      │
Permit Officer
      │
Permit Database
MODULE 3 — Immunization & Nutrition
Who enters?
Barangay Health Worker
Nurse
Midwife
Nutritionist
Data
Child profile
Vaccinations
Weight
Height
Nutrition assessment
Supplements
Who uses?
Parents (view)
Nurses
Midwives
Health Administrator
Health Surveillance

Flow

Child
     │
BHW / Nurse
     │
Vaccination Record
     │
Growth Chart
     │
Analytics
MODULE 4 — Wastewater Services
Who enters?
Citizen (service request)
Field Personnel
Sanitation Inspector
Data
Septic tank registration
Service request
Inspection
Desludging
Billing
Who uses?
Wastewater Team
Environmental Office
Analytics
Health Surveillance

Flow

Citizen
      │
Service Request
      │
Field Team
      │
Inspection
      │
Completed Service
MODULE 5 — Health Surveillance

This module mostly receives data.

Receives data from

Health Center

↓

Immunization

↓

Laboratory

↓

Sanitation

↓

Wastewater

↓

Barangay Reports

It combines everything.

Who enters?

Mostly automatic.

Some manual entries by

Epidemiology Officer
Disease Surveillance Officer
Who uses?
City Health Officer
Mayor
Administrators
Decision Makers

Flow

Health Center
Immunization
Sanitation
Wastewater
      │
      ▼
Health Surveillance
      │
      ▼
Dashboard
AI
Reports
Dashboard / Analytics / Reports

These modules usually do not create data.

They read data from every operational module.

All Modules
      │
      ▼
Dashboard

Analytics

Reports

Compliance
Overall Data Flow
                CITIZENS
                    │
     ┌──────────────┼──────────────┐
     │              │              │
Patient      Business Owner    Resident
     │              │              │
     ▼              ▼              ▼
Health Center  Sanitation    Wastewater
     │              │              │
     └───────┬──────┴──────────────┘
             │
             ▼
Immunization & Nutrition
             │
             ▼
Health Surveillance
             │
      ┌──────┼─────────┐
      ▼      ▼         ▼
 Dashboard Analytics Reports
             │
             ▼
   City Health Officer / LGU Management
Is this realistic?

Yes. It closely mirrors how local government health offices operate:

Citizens provide requests or personal information.
Health personnel (receptionists, nurses, doctors, inspectors, BHWs, environmental staff) create and update operational records.
The system consolidates data into dashboards, reports, and analytics.
Decision-makers (e.g., the City Health Officer and supervisors) consume those outputs for planning, monitoring, and compliance.

This creates a complete end-to-end flow from data collection → service delivery → monitoring → decision support, which is exactly what a Health & Sanitation Management Information System should accomplish.










Since you said:

✅ Web = Management & Administrative System (staff use it)
✅ Mobile App = Citizen App

Then the workflow should be divided like this.

Health Center Services
Citizen

Uses the mobile app to:

Book an appointment
View appointment status
View prescriptions
View medical history (if allowed)
View referrals
Receive notifications
Staff

Uses the web system to:

Register walk-in patients
Record consultations
Enter diagnoses
Enter prescriptions
Record laboratory results
Update patient records

Reason: Doctors and nurses are the ones who should create official medical records.

Immunization & Nutrition
Citizen
View vaccination schedule
Receive reminders
View immunization history
View child's growth chart
Staff
Record vaccinations
Record weight/height
Enter nutrition assessments
Update vaccine inventory
Sanitation Permits

Here the citizen (or business owner) can do much more.

Citizen
Apply for a permit
Upload documents
Track application status
Download permit
Receive renewal reminders
Staff
Review applications
Schedule inspections
Record inspection results
Approve or reject permits
Generate permits
Wastewater Services
Citizen
Request desludging
Report wastewater problems
Track service requests
View invoices
Staff
Assign field teams
Schedule services
Record inspections
Update service completion
Process billing
Health Surveillance
Citizen

Almost nothing.

Maybe only:

Report symptoms
Report health incidents
Staff

Everything else.

Verify reports
Investigate cases
Record outbreaks
Update surveillance records
Overall
Citizen Mobile App
Appointments

Permit Applications

Service Requests

Notifications

View Records

View Vaccines

Download Permits

Track Requests

Citizens mostly request services and view information.

Staff Web System
Patient Registration

Consultations

Medical Records

Vaccinations

Permit Processing

Inspections

Wastewater Operations

Disease Reports

Analytics

Reports

Dashboard

Staff create and maintain the official records.

Should citizens manually enter patient information?

For a Philippine LGU, I would not let citizens create official health records.

A realistic workflow is:

Walk-in patient
Citizen arrives

↓

Receptionist registers patient

↓

Nurse records vital signs

↓

Doctor performs consultation

↓

Doctor enters diagnosis

↓

System saves medical record

↓

Citizen can later view the result in the mobile app
Online appointment
Citizen books appointment using the mobile app

↓

Staff confirms the appointment

↓

Citizen visits the health center

↓

Staff records the consultation

↓

Citizen views the completed record in the mobile app

This mirrors how most health centers and hospitals operate: citizens initiate requests and access their own information, while healthcare staff create and update the official medical records. This approach is also better for data quality and accountability because only authorized personnel can modify clinical records.

WHO GETS MAIN CONTROLS ACCESS?
1. Health Center Director
text
HEALTH CENTER DIRECTOR
│
├── 👤 Role: Department Head - Health Center
├── 📋 Reports To: City Health Officer
├── 👥 Manages: Doctors, Nurses, Staff
│
├── ✅ SYSTEM OVERVIEW (Limited to Health Center)
│   ├── Dashboard KPIs
│   │   ├── Total Patients: 3,456
│   │   ├── Today's Appointments: 45
│   │   ├── Staff Available: 12/18
│   │   └── Satisfaction Rate: 92%
│   │
│   ├── Module Summary
│   │   ├── Consultations Today: 32
│   │   ├── Pending Referrals: 5
│   │   └── Prescriptions Issued: 28
│   │
│   └── Alerts & Notifications
│       ├── Staff Attendance
│       ├── Low Stock Alerts
│       └── Appointment Reminders
│
├── ✅ ANALYTICS (Limited)
│   ├── Patient Visit Trends
│   ├── Disease Patterns
│   ├── Staff Performance
│   └── Treatment Outcomes
│
├── ✅ REPORTS (Limited)
│   ├── Monthly Health Reports
│   ├── Patient Statistics
│   └── Staff Performance Reports
│
└── ✅ COMPLIANCE (Limited)
    ├── Health Center Compliance
    ├── Staff Violations
    └── Corrective Actions
2. Sanitation Officer
text
SANITATION OFFICER
│
├── 👤 Role: Department Head - Sanitation
├── 📋 Reports To: City Health Officer / ENRO
├── 👥 Manages: Inspectors, Clerks, Cashiers
│
├── ✅ SYSTEM OVERVIEW (Limited to Sanitation)
│   ├── Dashboard KPIs
│   │   ├── Total Permits: 2,456
│   │   ├── Pending Inspections: 23
│   │   ├── Compliance Rate: 87%
│   │   └── Revenue Collected: ₱245,000
│   │
│   ├── Module Summary
│   │   ├── Applications Today: 12
│   │   ├── Inspections Today: 8
│   │   └── Expiring Permits: 15
│   │
│   └── Alerts & Notifications
│       ├── Permit Expiry Alerts
│       ├── Inspection Reminders
│       └── Violation Alerts
│
├── ✅ ANALYTICS (Limited)
│   ├── Permit Application Trends
│   ├── Inspection Outcomes
│   ├── Revenue Trends
│   └── Compliance Patterns
│
├── ✅ REPORTS (Limited)
│   ├── Monthly Permit Reports
│   ├── Inspection Reports
│   └── Revenue Reports
│
└── ✅ COMPLIANCE (Limited)
    ├── Compliance Monitoring
    ├── Violation Tracking
    └── Corrective Actions
3. Immunization Coordinator
text
IMMUNIZATION COORDINATOR
│
├── 👤 Role: Department Head - Immunization
├── 📋 Reports To: City Health Officer
├── 👥 Manages: Midwives, Nurses, Nutritionists
│
├── ✅ SYSTEM OVERVIEW (Limited to Immunization)
│   ├── Dashboard KPIs
│   │   ├── Children Registered: 1,234
│   │   ├── Vaccinations Today: 45
│   │   ├── Coverage Rate: 85%
│   │   └── Stock Alert: 5 vaccines low
│   │
│   ├── Module Summary
│   │   ├── Due Vaccines: 23
│   │   ├── Missed Appointments: 8
│   │   └── Malnutrition Cases: 12
│   │
│   └── Alerts & Notifications
│       ├── Low Stock Alerts
│       ├── Due Date Reminders
│       └── Coverage Gaps
│
├── ✅ ANALYTICS (Limited)
│   ├── Vaccination Coverage
│   ├── Drop-out Rates
│   ├── Nutrition Trends
│   └── Program Effectiveness
│
├── ✅ REPORTS (Limited)
│   ├── Monthly Immunization Reports
│   ├── Coverage Reports
│   └── Nutrition Reports
│
└── ✅ COMPLIANCE (Limited)
    ├── DOH Compliance
    ├── Vaccination Standards
    └── Corrective Actions
4. Wastewater Officer
text
WASTEWATER OFFICER
│
├── 👤 Role: Department Head - Wastewater
├── 📋 Reports To: City Health Officer / ENRO
├── 👥 Manages: Technicians, Clerks, Field Staff
│
├── ✅ SYSTEM OVERVIEW (Limited to Wastewater)
│   ├── Dashboard KPIs
│   │   ├── Total Septic Tanks: 3,456
│   │   ├── Service Requests: 45
│   │   ├── Completed Jobs: 32
│   │   └── Revenue: ₱68,000
│   │
│   ├── Module Summary
│   │   ├── Pending Requests: 13
│   │   ├── Today's Schedule: 8
│   │   └── Compliance Rate: 92%
│   │
│   └── Alerts & Notifications
│       ├── Service Reminders
│       ├── Complaint Alerts
│       └── Compliance Alerts
│
├── ✅ ANALYTICS (Limited)
│   ├── Service Demand Trends
│   ├── Maintenance Patterns
│   ├── Technician Performance
│   └── Revenue Trends
│
├── ✅ REPORTS (Limited)
│   ├── Monthly Service Reports
│   ├── Maintenance Reports
│   └── Revenue Reports
│
└── ✅ COMPLIANCE (Limited)
    ├── Environmental Compliance
    ├── Service Standards
    └── Corrective Actions
5. Surveillance Officer
text
SURVEILLANCE OFFICER
│
├── 👤 Role: Department Head - Surveillance
├── 📋 Reports To: City Health Officer
├── 👥 Manages: Coordinators, Tracers, Investigators
│
├── ✅ SYSTEM OVERVIEW (Limited to Surveillance)
│   ├── Dashboard KPIs
│   │   ├── Active Cases: 45
│   │   ├── Outbreak Alerts: 2
│   │   ├── Contact Traced: 34
│   │   └── Response Time: 4.2 hrs
│   │
│   ├── Module Summary
│   │   ├── New Cases: 12
│   │   ├── Pending Investigations: 5
│   │   └── Active Outbreaks: 2
│   │
│   └── Alerts & Notifications
│       ├── Disease Alerts
│       ├── Outbreak Warnings
│       └── Response Status
│
├── ✅ ANALYTICS (Limited)
│   ├── Disease Patterns
│   ├── Geographic Spread
│   ├── Seasonality Trends
│   └── Response Effectiveness
│
├── ✅ REPORTS (Limited)
│   ├── Monthly Disease Reports
│   ├── Outbreak Reports
│   └── Contact Tracing Reports
│
└── ✅ COMPLIANCE (Limited)
    ├── DOH Reporting Compliance
    ├── Disease Reporting Standards
    └── Corrective Actions

    REGULAR STAFF - NO ACCESS
Why Regular Staff Don't Get Main Controls
text
REGULAR STAFF
│
├── 👨‍⚕️ Doctors
│   └── Focus: Patient Care, Consultations
│
├── 👩‍⚕️ Nurses
│   └── Focus: Triage, Vital Signs, Patient Care
│
├── 📋 Clerks
│   └── Focus: Data Entry, Records Management
│
├── 🔍 Inspectors
│   └── Focus: Field Inspections, Reports
│
├── 💉 Midwives
│   └── Focus: Vaccinations, Child Health
│
├── 🏭 Technicians
│   └── Focus: Service Delivery, Maintenance
│
├── 🔎 Contact Tracers
│   └── Focus: Tracing, Interviews
│
└── 📊 Support Staff
    └── Focus: Daily Operations


Is everything connected?

Yes. Here's how I see the flow:

Citizen
   │
   ▼
Health Center Services
   │
   ├── creates patient records
   ├── generates prescriptions
   ├── referrals
   └── consultation history
          │
          ▼
Immunization & Nutrition
   │
   ├── uses patient records
   ├── vaccination history
   └── growth monitoring
          │
          ▼
Health Surveillance
   │
   ├── receives disease reports
   ├── detects outbreaks
   ├── creates heatmaps
   └── sends alerts

Another flow:

Business Owner
       │
       ▼
Sanitation Permit
       │
       ├── inspection
       ├── compliance
       ├── violations
       └── renewal

And:

Citizen
      │
      ▼
Wastewater Service
      │
      ├── request
      ├── desludging
      ├── inspection
      └── billing

Then all five modules feed into:

Dashboard
      │
Analytics
      │
Reports
      │
Compliance


                      SIDEBAR ALIGNMENT
ADMIN SIDE 

MAIN CONTROLS 
│
├── 1. SYSTEM OVERVIEW ▼  (FEATURE)
│   ├── Dashboard with KPIs   (SUB-FEATURES)
│   ├── Module Activity Summary (SUB-FEATURES)
│   ├── Alerts & Notifications (SUB-FEATURES)
│   └── System Health Status (SUB-FEATURES)
│
├── 2. ANALYTICS  ▼ 
│   ├── AI Insights
│   ├── Trend Analysis
│   ├── Predictive Analytics
│   └── Performance Metrics
│
├── 3. REPORTS  ▼ 
│   ├── Custom Report Generation
│   ├── Scheduled Reports
│   ├── Export Options (PDF/Excel)
│   └── Report Templates
│
└── 4. COMPLIANCE & VIOLATIONS  ▼ 
    ├── Compliance Monitoring
    ├── Violation Tracking
    ├── Corrective Actions
    └── Regulatory Compliance


///////
OPERATIONAL MODULES

MODULE 1:   (7 Sub-features)
HEALTH CENTER SERVICES ▼  (MODULE)
│
├── PATIENT MANAGEMENT ▼      (FEATURES)
│   ├── Patient Registration (SUB-FEATURES)
│   ├── Patient Records      (SUB-FEATURES)
│   ├── Search & Filter      (SUB-FEATURES)
│   ├── Patient Dashboard    (SUB-FEATURES)
│   └── Patient History      (SUB-FEATURES)
│
├── CONSULTATIONS ▼ 
│   ├── Physical Examination
│   ├── Diagnosis (ICD-10)
│   ├── Treatment Plan
│   └── Consultation Notes
│
├── MEDICAL RECORDS ▼ 
│   ├── Electronic Health Record (EHR)
│   ├── Documentation
│   ├── Record Sharing
│   └── Reporting
│
├── APPOINTMENTS ▼  ⭐
│   ├── Schedule Appointments
│   ├── Manage Appointments
│   ├── Reminders (SMS/Email)
│   └── Doctor Schedule
│
├── TRIAGE ▼  ⭐
│   ├── Vital Signs Recording
│   ├── Priority Classification
│   ├── Queue Management
│   └── Symptom Checker
│
├── PRESCRIPTIONS ▼  ⭐
│   ├── Electronic Prescription
│   ├── Drug Selection
│   ├── Dosage Management
│   └── Prescription History
│
└── REFERRALS ▼  ⭐
    ├── Specialist Referral
    ├── Referral Tracking
    ├── Hospital Referral
    └── Follow-up Management

///////////
MODULE 2: SANITATION PERMITS (6 Sub-features)
SANITATION PERMITS ▼ 
│
├── PERMIT APPLICATIONS ▼ 
│   ├── New Application
│   ├── Application Review
│   ├── Status Tracking
│   └── Application History
│
├── INSPECTIONS ▼ 
│   ├── Schedule Inspection
│   ├── Conduct Inspection
│   ├── Inspection Reports
│   └── Follow-up Inspections
│
├── PERMIT RECORDS ▼  
│   ├── Permit History
│   ├── Active Permits
│   ├── Expired Permits
│   └── Search & Filter
│
├── PAYMENTS ▼  ⭐
│   ├── Fee Structure
│   ├── Payment Processing
│   ├── Receipt Generation
│   └── Payment History
│
├── DOCUMENTS ▼  ⭐
│   ├── Document Upload
│   ├── Digital Permits
│   ├── QR Code Verification
│   └── Document Expiry
│
└── RENEWALS ▼  ⭐
    ├── Renewal Applications
    ├── Auto-Reminders
    ├── Renewal History
    └── Grace Period Management

///////////
MODULE 3: IMMUNIZATION & NUTRITION (5 Sub-features)
IMMUNIZATION & NUTRITION ▼ 
│
├── CHILD RECORDS ▼ 
│   ├── Child Registration
│   ├── Demographics
│   ├── Family History
│   └── Health Records
│
├── VACCINATION TRACKING ▼ 
│   ├── Vaccine Schedule
│   ├── Record Vaccination
│   ├── Missed Vaccines
│   ├── Due Date Alerts
│   └── Immunization History
│
├── GROWTH CHARTS ▼ 
│   ├── Growth Charts
│   ├── Percentile Tracking
│   ├── Growth Alerts
│   └── Weight/Height Tracking
│
├── VACCINE INVENTORY  ▼ ⭐
│   ├── Stock Management
│   ├── Expiry Tracking
│   ├── Cold Chain Monitoring
│   └── Stock Alerts
│
└── NUTRITION ASSESSMENT  ▼ ⭐
    ├── Nutrition Screening
    ├── Malnutrition Detection
    ├── Nutrition Plans
    └── Supplement Tracking

//////////
MODULE 4: WASTEWATER SERVICES (5 Sub-features)

WASTEWATER SERVICES ▼ 
│
├── SEPTIC TANK REGISTRY ▼ 
│   ├── Tank Registration
│   ├── Tank Details
│   ├── Location Mapping
│   └── Tank History
│
├── MAINTENANCE & DESLUDGING ▼ 
│   ├── Schedule Services
│   ├── Service Records
│   ├── Route Planning
│   └── Completion Reports
│
├── SERVICE REQUESTS ▼ 
│   ├── New Request
│   ├── Request Tracking
│   ├── Status Updates
│   └── Customer Feedback
│
├── SERVICE PROVIDERS ▼  ⭐
│   ├── Provider Registration
│   ├── Provider Assignment
│   ├── Performance Tracking
│   └── Equipment Management
│
└── BILLING ▼  ⭐
    ├── Fee Structure
    ├── Quotation Generation
    ├── Payment Processing
    └── Invoice Management

///////////
MODULE 5: HEALTH SURVEILLANCE (6 Sub-features)

HEALTH SURVEILLANCE ▼ 
│
├── CASE REPORTS ▼ 
│   ├── Case Reporting
│   ├── Case Management
│   ├── Case Tracking
│   └── Case Investigation
│
├── MAPPING & CLUSTERING ▼ 
│   ├── Geographic Mapping
│   ├── Cluster Analysis
│   ├── Risk Heatmaps
│   └── Trend Visualization
│
├── OUTBREAK DETECTION ▼ 
│   ├── Automated Detection
│   ├── Pattern Recognition
│   ├── Threshold Monitoring
│   └── Alert Generation
│
├── REAL-TIME ALERTS ▼  ⭐
│   ├── Automated Alerts
│   ├── Escalation Protocol
│   └── Emergency Response
│
├── CONTACT TRACING ▼   ⭐
│   ├── Contact Identification
│   ├── Exposure Assessment
│   ├── Contact Monitoring
│   └── Quarantine Management
│
└── RESPONSE MANAGEMENT ▼  ⭐
    ├── Team Activation
    ├── Resource Allocation
    ├── Intervention Tracking
    └── Effectiveness Reports


SYSTEM MANAGEMENT (TITLE PAGE)
│
├── USER MANAGEMENT ▼ 
│   ├── User Registration
│   ├── Role Assignment
│   ├── Permission Management
│   └── User Activity
│
├── SYSTEM LOGS ▼ 
│   ├── Audit Trail
│   ├── Activity Logs
│   ├── Error Logs
│   └── Log Search
│
└── SETTINGS ▼ 
    ├── System Configuration
    ├── Module Settings
    ├── Notification Settings
    └── Backup & Recovery



THE 10 OPTIMIZED ROLES

Optimized Role
Replaces (Original 26)
Primary Module
1	Health Center Director	Health Center Director	Module 1
2	Medical Practitioner	Doctor, Nurse, Dentist, Lab Tech	Module 1
3	Health Center Staff	Med Records Clerk, Appt Clerk	Module 1
4	Sanitation Director	Sanitation Officer	Module 2
5	Sanitation officer	Inspector, Permits Clerk, Cashier	Module 2
6	Immunization Lead	Immunization Coordinator, Midwife	Module 3
7	Nutrition Staff	Nutritionist, Nutrition Educator	Module 3
8	Wastewater Lead	Wastewater Officer	Module 4
9	Surveillance Lead	Surveillance Officer, Coordinator	Module 5
10	System Admin	System Admin	All Modules


COMPLETE ROLE-MODULE MAPPING TABLE
MODULE 1: HEALTH CENTER SERVICES (7 Sub-features)
Sub-Feature
Health Center Director
Medical Practitioner
Health Center Staff
PATIENT MANAGEMENT			
└ Patient Registration	✅ Full	✅ Create	✅ Full
└ Patient Records	✅ Full	✅ Read	✅ Full
└ Search & Filter	✅ Full	✅ Full	✅ Full
└ Patient Dashboard	✅ Full	✅ Read	✅ Read
└ Patient History	✅ Full	✅ Read	✅ Read
CONSULTATIONS			
└ Physical Examination	✅ Read	✅ Full	❌
└ Diagnosis (ICD-10)	✅ Read	✅ Full	❌
└ Treatment Plan	✅ Read	✅ Full	❌
└ Consultation Notes	✅ Read	✅ Full	❌
MEDICAL RECORDS			
└ Electronic Health Record	✅ Full	✅ Read	✅ Update
└ Documentation	✅ Full	✅ Create	✅ Full
└ Record Sharing	✅ Full	❌	❌
└ Reporting	✅ Full	✅ Read	❌
APPOINTMENTS ⭐			
└ Schedule Appointments	✅ Full	✅ Read	✅ Full
└ Manage Appointments	✅ Full	✅ Update	✅ Full
└ Reminders (SMS/Email)	✅ Full	❌	✅ Full
└ Doctor Schedule	✅ Full	✅ Read	✅ Full
TRIAGE ⭐			
└ Vital Signs Recording	✅ Read	✅ Full	✅ Full
└ Priority Classification	✅ Read	✅ Full	✅ Full
└ Queue Management	✅ Full	✅ Read	✅ Full
└ Symptom Checker	✅ Read	✅ Full	✅ Full
PRESCRIPTIONS ⭐			
└ Electronic Prescription	✅ Read	✅ Full	❌
└ Drug Selection	✅ Read	✅ Full	❌
└ Dosage Management	✅ Read	✅ Full	❌
└ Prescription History	✅ Read	✅ Read	✅ Read
REFERRALS ⭐			
└ Specialist Referral	✅ Read	✅ Full	❌
└ Referral Tracking	✅ Full	✅ Read	❌
└ Hospital Referral	✅ Read	✅ Full	❌
└ Follow-up Management	✅ Full	✅ Full	❌

MODULE 2: SANITATION PERMITS (6 Sub-features)
Sub-Feature
Sanitation Director
Sanitation Processor
PERMIT APPLICATIONS		
└ New Application	✅ Full	✅ Full
└ Application Review	✅ Full	✅ Full
└ Status Tracking	✅ Full	✅ Full
└ Application History	✅ Full	✅ Read
INSPECTIONS		
└ Schedule Inspection	✅ Full	✅ Full
└ Conduct Inspection	✅ Full	✅ Full
└ Inspection Reports	✅ Full	✅ Create
└ Follow-up Inspections	✅ Full	✅ Full
PERMIT RECORDS		
└ Permit History	✅ Full	✅ Read
└ Active Permits	✅ Full	✅ Read
└ Expired Permits	✅ Full	❌
└ Search & Filter	✅ Full	✅ Full
PAYMENTS ⭐		
└ Fee Structure	✅ Full	✅ Read
└ Payment Processing	✅ Full	✅ Full
└ Receipt Generation	✅ Full	✅ Full
└ Payment History	✅ Full	✅ Read
DOCUMENTS ⭐		
└ Document Upload	✅ Full	✅ Full
└ Digital Permits	✅ Full	✅ Read
└ QR Code Verification	✅ Full	✅ Full
└ Document Expiry	✅ Full	✅ Read
RENEWALS ⭐		
└ Renewal Applications	✅ Full	✅ Full
└ Auto-Reminders	✅ Full	✅ Read
└ Renewal History	✅ Full	✅ Read
└ Grace Period Management	✅ Full	❌

MODULE 3: IMMUNIZATION & NUTRITION (5 Sub-features)
Sub-Feature
Immunization Lead
Nutrition Staff
CHILD RECORDS		
└ Child Registration	✅ Full	✅ Full
└ Demographics	✅ Full	✅ Read
└ Family History	✅ Full	✅ Read
└ Health Records	✅ Full	✅ Read
VACCINATION TRACKING		
└ Vaccine Schedule	✅ Full	❌
└ Record Vaccination	✅ Full	❌
└ Missed Vaccines	✅ Full	❌
└ Due Date Alerts	✅ Full	❌
└ Immunization History	✅ Full	✅ Read
GROWTH CHARTS		
└ Growth Charts	✅ Full	✅ Read
└ Percentile Tracking	✅ Full	✅ Read
└ Growth Alerts	✅ Full	✅ Read
└ Weight/Height Tracking	✅ Full	✅ Full
VACCINE INVENTORY ⭐		
└ Stock Management	✅ Full	❌
└ Expiry Tracking	✅ Full	❌
└ Cold Chain Monitoring	✅ Full	❌
└ Stock Alerts	✅ Full	❌
NUTRITION ASSESSMENT ⭐		
└ Nutrition Screening	✅ Read	✅ Full
└ Malnutrition Detection	✅ Read	✅ Full
└ Nutrition Plans	✅ Read	✅ Full
└ Supplement Tracking	✅ Read	✅ Full

MODULE 4: WASTEWATER SERVICES (5 Sub-features)
Sub-Feature
Wastewater Lead
SEPTIC TANK REGISTRY	
└ Tank Registration	✅ Full
└ Tank Details	✅ Full
└ Location Mapping	✅ Full
└ Tank History	✅ Full
MAINTENANCE & DESLUDGING	
└ Schedule Services	✅ Full
└ Service Records	✅ Full
└ Route Planning	✅ Full
└ Completion Reports	✅ Full
SERVICE REQUESTS	
└ New Request	✅ Full
└ Request Tracking	✅ Full
└ Status Updates	✅ Full
└ Customer Feedback	✅ Full
SERVICE PROVIDERS ⭐	
└ Provider Registration	✅ Full
└ Provider Assignment	✅ Full
└ Performance Tracking	✅ Full
└ Equipment Management	✅ Full
BILLING ⭐	
└ Fee Structure	✅ Full
└ Quotation Generation	✅ Full
└ Payment Processing	✅ Full
└ Invoice Management	✅ Full

MODULE 5: HEALTH SURVEILLANCE (6 Sub-features)
Sub-Feature
Surveillance Lead
CASE REPORTS	
└ Case Reporting	✅ Full
└ Case Management	✅ Full
└ Case Tracking	✅ Full
└ Case Investigation	✅ Full
MAPPING & CLUSTERING	
└ Geographic Mapping	✅ Full
└ Cluster Analysis	✅ Full
└ Risk Heatmaps	✅ Full
└ Trend Visualization	✅ Full
OUTBREAK DETECTION	
└ Automated Detection	✅ Full
└ Pattern Recognition	✅ Full
└ Threshold Monitoring	✅ Full
└ Alert Generation	✅ Full
REAL-TIME ALERTS ⭐	
└ Automated Alerts	✅ Full
└ Escalation Protocol	✅ Full
└ Emergency Response	✅ Full
CONTACT TRACING ⭐	
└ Contact Identification	✅ Full
└ Exposure Assessment	✅ Full
└ Contact Monitoring	✅ Full
└ Quarantine Management	✅ Full
RESPONSE MANAGEMENT ⭐	
└ Team Activation	✅ Full
└ Resource Allocation	✅ Full
└ Intervention Tracking	✅ Full
└ Effectiveness Reports	✅ Full

SYSTEM ADMIN (Cross-Module)
Access Level
System Admin
Modules 1–5 All Features	✅ Full
User Management	✅ Full
Role Assignment	✅ Full
System Settings	✅ Full
Audit Logs	✅ Full
AI Analytics Access	✅ Ful


<?php
// includes/check_permission.php

function hasPermission($userId, $featureSlug, $requiredLevel = 'read') {
    global $pdo;
    
    // Hierarchy: full > create > update > read > none
    $hierarchy = ['none' => 0, 'read' => 1, 'update' => 2, 'create' => 3, 'full' => 4];
    $required = $hierarchy[$requiredLevel] ?? 1;
    
    $stmt = $pdo->prepare("
        SELECT rsp.permission 
        FROM role_subfeature_permissions rsp
        JOIN users u ON u.role_id = rsp.role_id
        JOIN sub_features sf ON sf.id = rsp.subfeature_id
        WHERE u.id = ? AND sf.slug = ?
    ");
    $stmt->execute([$userId, $featureSlug]);
    $result = $stmt->fetch();
    
    if (!$result) return false;
    
    return ($hierarchy[$result['permission']] ?? 0) >= $required;
}

// Usage examples in your module pages:

// In consultations.php
if (!hasPermission($_SESSION['user_id'], 'consultations', 'create')) {
    die('Access Denied: You cannot create consultations.');
}

// In patient_records.php  
if (!hasPermission($_SESSION['user_id'], 'patient_records', 'update')) {
    // Hide the Edit button, show read-only view
    $readonly = true;
}

// In sidebar.php — show/hide menu items
 $modules = $pdo->query("
    SELECT m.module_name, m.slug, m.icon,
           (SELECT COUNT(*) FROM role_subfeature_permissions rsp
            JOIN sub_features sf ON sf.id = rsp.subfeature_id
            WHERE sf.module_id = m.id AND rsp.role_id = ? AND rsp.permission != 'none'
           ) > 0 AS has_access
    FROM modules m
    ORDER BY m.sort_order
")->fetchAll(PDO::FETCH_ASSOC);





MOBILE FOR CITIZEN

┌─────────────────────────────────────────────────────────────────────┐
│                                                                      │
│  📱 HEALTH & SANITATION MOBILE                                     │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  HOME DASHBOARD                                            │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐                │   │
│  │  │My Health │  │My Permits│  │My Records│                │   │
│  │  └──────────┘  └──────────┘  └──────────┘                │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  MAIN MENU                                                 │   │
│  │                                                             │   │
│  │  🏥 Book Appointment   📋 Apply Permit                    │   │
│  │  💉 View Vaccines      🏭 Request Service                │   │
│  │  📊 Track Requests     🦟 Report Health Issue           │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  BOTTOM NAVIGATION                                        │   │
│  │  [Home] [Services] [Records] [Alerts] [Profile]            │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

Feature 1: Book Appointment 📅
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

Health Center Services             → Book Appointment
├── Patient Management              ├── Choose Center
├── Consultations                   ├── Select Service
├── Medical Records                 ├── Pick Date/Time
├── Appointments ⭐                 ├── Confirm
├── Triage ⭐                       └── Get Reminder
├── Prescriptions ⭐
└── Referrals ⭐

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  Book Appointment                                          │
│                                                             │
│  Health Center: [Select Center ▼]                         │
│  Service Type: [Select Service ▼]                         │
│  Date: [Calendar Picker]                                  │
│  Time: [Time Picker]                                      │
│                                                             │
│  Preferred Doctor: [Select Doctor ▼]                      │
│  Reason for Visit: [Text Input]                           │
│                                                             │
│  [📅 BOOK APPOINTMENT]                                     │
│                                                             │
│  ✅ Appointment Confirmed!                                 │
│  📅 Date: July 20, 2026                                    │
│  ⏰ Time: 9:00 AM                                          │
│  📍 Health Center 1                                        │
│  🔔 Reminder will be sent 24hrs before                    │
└─────────────────────────────────────────────────────────────┘
Feature 2: Apply Permit 📋
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

Sanitation Permits                 → Apply Permit
├── Permit Applications             ├── Fill Form
├── Inspections                     ├── Upload Documents
├── Permit Records                  ├── Pay Fee
├── Payments ⭐                     ├── Submit
├── Documents ⭐                    └── Track Status
└── Renewals ⭐

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  Apply Sanitation Permit                                   │
│                                                             │
│  Business Name: [Text Input]                              │
│  Business Type: [Select ▼]                                │
│  Address: [Text Input]                                    │
│                                                             │
│  📎 Upload Requirements                                    │
│  ├── Business Permit: [📎 Upload]                         │
│  ├── Floor Plan: [📎 Upload]                              │
│  └── Health Certificate: [📎 Upload]                      │
│                                                             │
│  💰 Permit Fee: ₱500.00                                   │
│  Payment Method: [GCash / Bank / Over-the-Counter]        │
│                                                             │
│  [📤 SUBMIT APPLICATION]                                   │
│                                                             │
│  ✅ Application Submitted!                                 │
│  📋 Tracking #: SP-1042                                   │
│  📍 Status: Pending Review                                │
│  🔔 You'll be notified when approved                      │
└─────────────────────────────────────────────────────────────┘

Feature 3: View Vaccines 💉
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

Immunization & Nutrition           → View Vaccines
├── Child Records                   ├── View My Vaccines
├── Vaccination Tracking            ├── View Children's Vaccines
├── Growth Charts                   ├── View Due Dates
├── Vaccine Inventory ⭐            └── Get Reminders
└── Nutrition Assessment ⭐

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  My Immunization Records                                   │
│                                                             │
│  👤 Pedro Garcia                                           │
│  ─────────────────────────────────                         │
│  ✅ COVID-19 Booster       Completed (Nov 2025)           │
│  ✅ Influenza              Completed (Sep 2025)           │
│  ✅ Tetanus                Completed (Jun 2024)           │
│  ⏰ Hepatitis B            Due (Pending)                  │
│                                                             │
│  👶 My Children                                            │
│  ─────────────────────────────────                         │
│  Sofia Garcia (2 yrs)                                     │
│  ✅ BCG                   Completed                        │
│  ✅ DPT 1st Dose          Completed                        │
│  ⏰ MMR Booster           Due July 28, 2026               │
│                                                             │
│  📱 [VIEW FULL RECORDS]                                    │
│  🔔 [SET REMINDERS]                                       │
└─────────────────────────────────────────────────────────────┘

Feature 4: Request Service 🏭
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

Wastewater Services                → Request Service
├── Septic Tank Registry            ├── Request Service
├── Maintenance & Desludging        ├── Track Request
├── Service Requests                ├── Make Payment
├── Service Providers ⭐            └── Rate Service
└── Billing ⭐

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  Request Wastewater Service                                │
│                                                             │
│  Service Type: [Select Service ▼]                         │
│  Address: [Text Input]                                    │
│                                                             │
│  Septic Tank Details:                                     │
│  Tank Size: [Select ▼]                                    │
│  Last Maintenance: [Date Picker]                          │
│                                                             │
│  Preferred Schedule:                                      │
│  Date: [Calendar Picker]                                  │
│  Time: [Time Picker]                                      │
│                                                             │
│  💰 Estimated Cost: ₱1,200.00                            │
│                                                             │
│  [📤 REQUEST SERVICE]                                      │
│                                                             │
│  ✅ Request Submitted!                                     │
│  🏷️ Request #: WW-201                                     │
│  📍 Status: Pending Assignment                            │
│  👷 Technician: To be assigned                            │
└─────────────────────────────────────────────────────────────┘

Feature 5: Track Requests 📊
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

All Modules                        → Track Requests
├── Appointments                    ├── View All Requests
├── Permits                         ├── Status Tracking
├── Immunization                    ├── History
├── Wastewater                      └── Notifications
└── Surveillance

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  My Requests                                               │
│                                                             │
│  🔴 PENDING                                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 📋 Sanitation Permit #SP-1042                      │   │
│  │ Status: ⏳ Under Review                            │   │
│  │ Submitted: July 15, 2026                          │   │
│  │ Next Action: Inspection Scheduling                │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🟢 APPROVED                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 📅 Appointment #APT-001                            │   │
│  │ Status: ✅ Approved                                │   │
│  │ Date: July 20, 2026 9:00 AM                       │   │
│  │ Location: Health Center 1                          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🔵 COMPLETED                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🏭 Wastewater Service #WW-203                     │   │
│  │ Status: ✅ Completed                               │   │
│  │ Completed: July 12, 2026                          │   │
│  │ Rate Service: ⭐⭐⭐⭐⭐                             │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
Feature 6: Report Health Issue 
ADMIN SYSTEM (Complex)              CITIZEN APP (Simple)
──────────────────────              ────────────────────

Health Surveillance                → Report Health Issue
├── Case Reports                    ├── Report Symptoms
├── Mapping & Clustering            ├── Report Cases
├── Outbreak Detection              ├── Report Violations
├── Real-time Alerts ⭐             └── Get Health Alerts
├── Contact Tracing ⭐
└── Response Management ⭐

------------------------------------------------------------

CITIZEN VIEW:
┌─────────────────────────────────────────────────────────────┐
│  Report Health Issue                                       │
│                                                             │
│  Issue Type: [Select ▼]                                   │
│  └── Symptom Reporting                                    │
│  └── Disease Case                                         │
│  └── Sanitation Violation                                 │
│  └── Other                                               │
│                                                             │
│  Location: [Text Input / GPS]                              │
│  Description: [Text Input]                                │
│                                                             │
│  📎 Attach Photo (Optional)                                │
│  [📸 TAKE PHOTO]  [🖼️ UPLOAD]                            │
│                                                             │
│  🚨 URGENCY                                                │
│  ○ Normal    ● Urgent    ○ Emergency                      │
│                                                             │
│  [📤 SUBMIT REPORT]                                        │
│                                                             │
│  ✅ Report Submitted!                                      │
│  🏷️ Reference #: CS-001                                  │
│  📍 Authorities have been notified                        │
└─────────────────────────────────────────────────────────────┘
MOBILE APP DESIGN PRINCIPLES
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│  ✅ ONE TAP ACTIONS                                         │
│  └── Book, Apply, Request, Report                          │
│                                                              │
│  ✅ MINIMAL TEXT INPUT                                      │
│  └── Use dropdowns, pickers, and selects                   │
│                                                              │
│  ✅ CLEAR STATUS INDICATORS                                 │
│  └── Color-coded (🟢 Approved, 🟡 Pending, 🔴 Rejected)  │
│                                                              │
│  ✅ PUSH NOTIFICATIONS                                      │
│  └── Real-time updates                                     │
│                                                              │
│  ✅ OFFLINE CAPABILITY                                      │
│  └── Submit offline, sync later                            │
│                                                              │
│  ✅ GPS INTEGRATION                                         │
│  └── Auto-location for services                            │
│                                                              │
│  ✅ DIGITAL PAYMENT                                         │
│  └── GCash, Bank Transfer                                  │
│                                                              │
│  ✅ MULTI-LANGUAGE                                          │
│  └── English, Tagalog                                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
CITIZEN MOBILE APP
│
├── HOME DASHBOARD
│   ├── Quick Stats (Appointments, Permits, Services)
│   ├── Recent Requests
│   ├── Notifications
│   └── Health Alerts
│
├── 6 MAIN FEATURES
│   │
│   ├── 1. BOOK APPOINTMENT
│   │   ├── Choose Center
│   │   ├── Select Service
│   │   ├── Pick Date/Time
│   │   ├── Confirm Booking
│   │   └── Get Reminders
│   │
│   ├── 2. APPLY PERMIT
│   │   ├── Fill Form
│   │   ├── Upload Documents
│   │   ├── Pay Fee (GCash/Bank)
│   │   ├── Submit
│   │   └── Track Status
│   │
│   ├── 3. VIEW VACCINES
│   │   ├── My Vaccines
│   │   ├── Children's Vaccines
│   │   ├── Due Dates
│   │   └── Set Reminders
│   │
│   ├── 4. REQUEST SERVICE
│   │   ├── Select Service
│   │   ├── Provide Details
│   │   ├── Schedule
│   │   ├── Pay
│   │   └── Track
│   │
│   ├── 5. TRACK REQUESTS
│   │   ├── All Requests
│   │   ├── Status Updates
│   │   ├── History
│   │   └── Notifications
│   │
│   └── 6. REPORT HEALTH ISSUE
│       ├── Select Issue Type
│       ├── Describe
│       ├── Add Location
│       ├── Upload Photo
│       └── Submit
│
├── PROFILE
│   ├── Personal Information
│   ├── Family Members
│   ├── Health Records
│   └── Settings
│
└── NOTIFICATIONS
    ├── Appointment Reminders
    ├── Permit Status Updates
    ├── Service Confirmations
    ├── Health Alerts
    └── Promotions

    ┌─────────────────────────────────────────────────────────────┐
│  📱 9:41               Health & Sanitation                  │
│  ─────────────────────────────────────────────────────────  │
│                                                              │
│  👋 Good Morning, Pedro!                                   │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                │
│  │  📅      │  │  📋      │  │  🏭      │                │
│  │ 2 Appts  │  │ 1 Permit │  │ 3 Serv.  │                │
│  │ This Week│  │ Pending  │  │ Active   │                │
│  └──────────┘  └──────────┘  └──────────┘                │
│                                                              │
│  🔔 ALERTS                                                  │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🚨 Health Alert: Dengue in your area                │   │
│  │ 📅 Appointment Reminder: Tomorrow 9:00 AM          │   │
│  │ 📋 Permit SP-1042: Under Review                    │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  QUICK ACTIONS                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                │
│  │  🏥      │  │  📋      │  │  💉      │                │
│  │  Book     │  │  Apply   │  │  View    │                │
│  │  Appt     │  │  Permit  │  │  Vaccines│                │
│  └──────────┘  └──────────┘  └──────────┘                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                │
│  │  🏭      │  │  📊      │  │  🦟      │                │
│  │  Request  │  │  Track   │  │  Report  │                │
│  │  Service  │  │  Requests│  │  Issue   │                │
│  └──────────┘  └──────────┘  └──────────┘                │
│                                                              │
│  [Home] [Services] [Records] [Alerts] [Profile]            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  📱 9:41              Services                             │
│  ─────────────────────────────────────────────────────────  │
│                                                              │
│  AVAILABLE SERVICES                                         │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  🏥 Book Appointment                               │   │
│  │  Schedule visit to health center                    │   │
│  │  [BOOK NOW]                                        │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  📋 Apply Sanitation Permit                        │   │
│  │  Apply for business/stall permit                    │   │
│  │  [APPLY NOW]                                        │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  💉 View Immunization Records                     │   │
│  │  Check your and children's vaccines                 │   │
│  │  [VIEW NOW]                                        │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  🏭 Request Wastewater Service                     │   │
│  │  Septic cleaning and maintenance                    │   │
│  │  [REQUEST NOW]                                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  🦟 Report Health Issue                           │   │
│  │  Report symptoms, cases, violations                 │   │
│  │  [REPORT NOW]                                       │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  [Home] [Services] [Records] [Alerts] [Profile]            │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  📱 9:41              My Records                           │
│  ─────────────────────────────────────────────────────────  │
│                                                              │
│  MY HEALTH RECORDS                                          │
│                                                              │
│  👤 Pedro Garcia                                            │
│  ───────────────────────────────────────────────────────    │
│  ├── Blood Type: O+                                       │
│  ├── Allergies: None                                      │
│  ├── Last Checkup: June 15, 2026                         │
│  └── Medical History: [VIEW]                              │
│                                                              │
│  💉 Immunization Records                                    │
│  ───────────────────────────────────────────────────────    │
│  ├── 4 Completed Vaccines                                 │
│  ├── 1 Due: Hepatitis B                                  │
│  └── [VIEW FULL RECORDS]                                  │
│                                                              │
│  📅 My Appointments                                         │
│  ───────────────────────────────────────────────────────    │
│  ├── July 20, 2026 9:00 AM - General Checkup             │
│  └── [VIEW ALL]                                            │
│                                                              │
│  📋 My Permits                                              │
│  ───────────────────────────────────────────────────────    │
│  ├── SP-1042 - Under Review                               │
│  ├── SP-1038 - Expired (Renew)                           │
│  └── [VIEW ALL]                                            │
│                                                              │
│  📊 My Requests                                             │
│  ───────────────────────────────────────────────────────    │
│  ├── 3 Active Requests                                    │
│  ├── 5 Completed                                          │
│  └── [VIEW ALL]                                            │
│                                                              │
│  [Home] [Services] [Records] [Alerts] [Profile]            │
└─────────────────────────────────────────────────────────────┘

MOBILE APP COMPLETE SUMMARY
CITIZEN MOBILE APP
│
├── 6 FEATURES
│   ├── Book Appointment (Health Center)
│   ├── Apply Permit (Sanitation)
│   ├── View Vaccines (Immunization)
│   ├── Request Service (Wastewater)
│   ├── Track Requests (All Modules)
│   └── Report Health Issue (Surveillance)
│
├── 12 SUB-FEATURES
│   ├── Choose Center
│   ├── Select Service
│   ├── Pick Date/Time
│   ├── Fill Form
│   ├── Upload Documents
│   ├── Pay Fee
│   ├── View Records
│   ├── Submit Request
│   ├── Track Status
│   ├── View History
│   ├── Report Issue
│   └── Get Notifications
│
└── 24 CITIZEN ACTIONS
    ├── 4 Actions per Feature
    └── Simple, intuitive, user-friendly

    NOTIFICATION GAPS
│
├── ❌ TYPES OF NOTIFICATIONS
│   ├── Email Notifications
│   ├── SMS Notifications
│   ├── Push Notifications (Mobile)
│   ├── In-App Notifications
│   └── System Alerts
│
├── ❌ NOTIFICATION TRIGGERS
│   ├── Appointment Reminders (24hrs before)
│   ├── Permit Status Updates (Approved/Rejected)
│   ├── Vaccine Due Date Alerts
│   ├── Service Request Confirmations
│   ├── Outbreak Alerts
│   └── System Maintenance Alerts
│
├── ❌ NOTIFICATION PREFERENCES
│   ├── User Channel Preferences
│   ├── Snooze/Quiet Hours
│   └── Unsubscribe Options
│
└── ❌ NOTIFICATION HISTORY
    ├── Sent Logs
    ├── Delivery Status
    └── Read/Unread Tracking


    INTEGRATION GAPS
│
├── ❌ EXTERNAL SYSTEM INTEGRATION
│   ├── PhilHealth Integration
│   │   ├── Member Verification
│   │   ├── Claim Submission
│   │   └── Reimbursement Tracking
│   │
│   ├── DOH Integration
│   │   ├── Disease Reporting
│   │   ├── Immunization Data
│   │   └── Health Statistics
│   │
│   ├── PAG-IBIG / SSS Integration
│   │   ├── Member Validation
│   │   └── Benefits Tracking
│   │
│   └── LGU Systems Integration
│       ├── Civil Registry
│       ├── Business Permits
│       └── GIS/Mapping Systems
│
├── ❌ PAYMENT GATEWAY INTEGRATION
│   ├── GCash
│   ├── PayMaya
│   ├── Bank Transfers (via PayMongo/other)
│   ├── Over-the-Counter (Bayad Center, 7-Eleven)
│   └── Credit/Debit Cards
│
└── ❌ THIRD-PARTY SERVICES
    ├── SMS Gateway (Twilio, Semaphore)
    ├── Email Service (SendGrid, Mailchimp)
    ├── Cloud Storage (AWS, GCP)
    └── Analytics (Google Analytics, Firebase)


    UX GAPS
│
├── ❌ USER INTERFACE
│   ├── Dark Mode
│   ├── Mobile Responsive Design
│   ├── Accessibility (WCAG Compliance)
│   ├── Multi-Language Support (English, Tagalog)
│   └── Customizable Themes
│
├── ❌ USER EXPERIENCE
│   ├── Onboarding / Tutorial
│   ├── Tooltips / Help System
│   ├── Search / Filters
│   ├── Quick Actions / Shortcuts
│   └── Undo/Redo Functionality
│
├── ❌ PERFORMANCE
│   ├── Loading Speed Optimization
│   ├── Pagination for Large Data
│   ├── Search Indexing
│   └── Offline Capability
│
└── ❌ USER FEEDBACK
    ├── Feedback / Suggestion System
    ├── Bug Reporting
    ├── Feature Request
    └── Satisfaction Survey

    SYSTEM OPERATIONAL GAPS
│
├── ❌ SYSTEM ADMINISTRATION
│   ├── Backup & Restore
│   ├── System Health Monitoring
│   ├── Performance Dashboard
│   ├── Error Logging & Monitoring
│   └── System Updates / Patches
│
├── ❌ DATA MANAGEMENT
│   ├── Data Import (CSV, Excel)
│   ├── Data Export (CSV, Excel, PDF, JSON)
│   ├── Data Validation Rules
│   ├── Data Duplicate Detection
│   └── Data Migration Tools
│
├── ❌ AUDIT & COMPLIANCE
│   ├── Detailed Audit Trail
│   ├── User Activity Logging
│   ├── Data Access Logs
│   ├── Compliance Reports
│   └── Incident Reporting
│
└── ❌ DISASTER RECOVERY
    ├── Automated Backups
    ├── Data Recovery Plan
    ├── Business Continuity
    └── Failover System

ROLE-BASED DATA ACCESS
│
├── 👨‍⚕️ DOCTOR
│   ├── Can View: All patients under their care
│   ├── Can Edit: Medical records, diagnoses, prescriptions
│   ├── Can Delete: ❌ No (Can only archive)
│   └── Can Export: ❌ No
│
├── 👩‍⚕️ NURSE
│   ├── Can View: Patient basic info, vital signs, triage
│   ├── Can Edit: Vital signs, triage notes
│   ├── Can Delete: ❌ No
│   └── Can Export: ❌ No
│
├── 📋 MEDICAL RECORDS CLERK
│   ├── Can View: All patient records (Read Only)
│   ├── Can Edit: Patient demographics, contact info
│   ├── Can Delete: ❌ No
│   └── Can Export: ❌ No
│
├── 💊 PHARMACIST
│   ├── Can View: Prescriptions only
│   ├── Can Edit: Dispensing status
│   ├── Can Delete: ❌ No
│   └── Can Export: ❌ No
│
├── 🏥 HEALTH CENTER DIRECTOR
│   ├── Can View: All records in their center
│   ├── Can Edit: ❌ No (Cannot alter medical records)
│   ├── Can Delete: ❌ No
│   └── Can Export: Yes (Anonymized reports only)
│
└── 🔐 SYSTEM ADMIN
    ├── Can View: System-level data only
    ├── Can Edit: User accounts, permissions
    ├── Can Delete: ❌ No (Backup first)
    └── Can Export: ❌ No (Sensitive data export disabled)

    AUDIT TRAIL SYSTEM
│
├── 📝 WHAT IS LOGGED
│   ├── Who accessed what data
│   ├── When they accessed it
│   ├── What they did (View/Edit/Delete)
│   ├── IP Address & Device
│   ├── Session Information
│   └── Reason for Access (if required)
│
├── 📊 AUDIT LOG EXAMPLES
│   ├── [2026-07-17 09:15:32] Dr. Santos viewed P-101 record
│   ├── [2026-07-17 09:16:45] Dr. Santos edited P-101 diagnosis
│   ├── [2026-07-17 10:30:12] Nurse Cruz viewed P-102 vital signs
│   └── [2026-07-17 11:00:01] Clerk Reyes exported permit report
│
└── 🚨 SUSPICIOUS ACTIVITY ALERTS
    ├── Unusual access time (Midnight)
    ├── Unusual access pattern (Multiple records)
    ├── Unauthorized access attempts
    └── Data export warnings

    ┌─────────────────────────────────────────────────────────────────────┐
│                    AUDIT TRAIL                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Filter: [All Actions ▼] [Date Range] [Search...]                  │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ Time              │ User       │ Action    │ Target    │ IP │   │
│  ├─────────────────────────────────────────────────────────────┤   │
│  │ 2026-07-17 09:15 │ Dr. Santos │ View      │ P-101     │ .1 │   │
│  │ 2026-07-17 09:16 │ Dr. Santos │ Edit      │ P-101     │ .1 │   │
│  │ 2026-07-17 10:30 │ Nurse Cruz │ View      │ P-102     │ .5 │   │
│  │ 2026-07-17 11:00 │ Clerk Reyes│ Export    │ Report    │ .3 │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ⚠️ Suspicious Activities:                                         │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ 🚨 Midnight Access: Dr. Santos viewed 50 records           │   │
│  │ 🚨 Bulk Export: Clerk Reyes exported all patient data      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────┐
│                    COMPLETE PERMISSION MATRIX                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  PERMISSION: R=Read | W=Write | E=Edit | D=Delete | X=Export      │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ FEATURE        │ Admin │ Doctor │ Nurse │ Clerk │ Pharmacist│   │
│  ├─────────────────────────────────────────────────────────────┤   │
│  │ ALL MODULES                                                │   │
│  │ ────────────                                               │   │
│  │ View Module   │  R    │  R     │  R    │  R    │  R       │   │
│  │ Access Module │  ✅   │  ✅    │  ✅   │  ✅   │  ✅      │   │
│  │                                                             │   │
│  │ PATIENT DATA                                               │   │
│  │ ────────────                                               │   │
│  │ View Patient  │  R    │  R     │  R    │  R    │  R       │   │
│  │ Edit Patient  │  E    │  E     │  ❌   │  E    │  ❌      │   │
│  │ Delete Patient│  D    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Export Patient│  X    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ MEDICAL RECORDS                                            │   │
│  │ ────────────                                               │   │
│  │ View Records  │  R    │  R     │  R    │  R    │  R       │   │
│  │ Edit Records  │  E    │  E     │  ❌   │  ❌   │  ❌      │   │
│  │ Delete Records│  D    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Export Records│  X    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ DIAGNOSIS                                                  │   │
│  │ ────────────                                               │   │
│  │ View Dx       │  R    │  R     │  R    │  R    │  R       │   │
│  │ Add Dx        │  W    │  W     │  ❌   │  ❌   │  ❌      │   │
│  │ Edit Dx       │  E    │  E     │  ❌   │  ❌   │  ❌      │   │
│  │ Delete Dx     │  D    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ PRESCRIPTIONS                                              │   │
│  │ ────────────                                               │   │
│  │ View Rx       │  R    │  R     │  R    │  R    │  R       │   │
│  │ Write Rx      │  W    │  W     │  ❌   │  ❌   │  ❌      │   │
│  │ Verify Rx     │  E    │  ❌    │  ❌   │  ❌   │  E       │   │
│  │ Dispense Rx   │  E    │  ❌    │  ❌   │  ❌   │  E       │   │
│  │                                                             │   │
│  │ PERMITS                                                    │   │
│  │ ────────────                                               │   │
│  │ View Permits  │  R    │  ❌    │  ❌   │  R    │  ❌      │   │
│  │ Process Permit│  W    │  ❌    │  ❌   │  W    │  ❌      │   │
│  │ Approve Permit│  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Inspect Permit│  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ VACCINES                                                   │   │
│  │ ────────────                                               │   │
│  │ View Records  │  R    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Administer    │  W    │  ❌    │  W    │  ❌   │  ❌      │   │
│  │ Manage Stock  │  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ WASTEWATER                                                 │   │
│  │ ────────────                                               │   │
│  │ View Requests │  R    │  ❌    │  ❌   │  R    │  ❌      │   │
│  │ Process Req   │  W    │  ❌    │  ❌   │  W    │  ❌      │   │
│  │ Assign Staff  │  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ SURVEILLANCE                                               │   │
│  │ ────────────                                               │   │
│  │ View Cases    │  R    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Report Case   │  W    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Trace Contact │  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │                                                             │   │
│  │ SYSTEM ADMIN                                               │   │
│  │ ────────────                                               │   │
│  │ User Mgmt     │  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ System Logs   │  R    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  │ Settings      │  E    │  ❌    │  ❌   │  ❌   │  ❌      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘


DPA COMPLIANCE FEATURES
│
├── 📋 CONSENT MANAGEMENT
│   ├── Patient Consent Form
│   ├── Consent for Data Processing
│   ├── Consent for Data Sharing
│   └── Consent Withdrawal Mechanism
│
├── 🔐 DATA PRIVACY CONTROLS
│   ├── Data Minimization
│   ├── Purpose Limitation
│   ├── Storage Limitation
│   ├── Accuracy Principle
│   └── Accountability
│
├── 📊 PRIVACY IMPACT ASSESSMENT
│   ├── Data Inventory
│   ├── Risk Assessment
│   ├── Mitigation Measures
│   └── Regular Review
│
└── 📝 DATA SUBJECT RIGHTS
    ├── Right to Access
    ├── Right to Rectification
    ├── Right to Erasure
    ├── Right to Object
    └── Right to Data Portability

*/ citizen mobile
    ┌─────────────────────────────────────────────────────────────────────┐
│                    PATIENT CONSENT FORM                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  📋 HEALTH & SANITATION MANAGEMENT SYSTEM                          │
│     Data Privacy Consent Form                                      │
│                                                                      │
│  Patient Name: Pedro Garcia                                        │
│  Date: July 17, 2026                                              │
│                                                                      │
│  I understand that:                                                │
│  ☑️ My personal data will be collected                            │
│  ☑️ My medical records will be stored securely                    │
│  ☑️ My data will only be used for treatment purposes              │
│  ☑️ My data will not be shared without my consent                 │
│  ☑️ I can request access to my data                               │
│  ☑️ I can request correction of my data                          │
│  ☑️ I can withdraw consent anytime                                │
│                                                                      │
│  Signature: [______________________]                              │
│                                                                      │
│  [✅ AGREE] [❌ DECLINE]                                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘


API ARCHITECTURE OVERVIEW
┌─────────────────────────────────────────────────────────────────────┐
│                    API ARCHITECTURE                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Base URL: https://api.hsms.caloocan.gov.ph/v1                    │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    AUTHENTICATION                           │   │
│  │  POST /auth/login                                          │   │
│  │  POST /auth/logout                                         │   │
│  │  POST /auth/refresh                                        │   │
│  │  POST /auth/forgot-password                                │   │
│  │  POST /auth/reset-password                                 │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODULE 1: HEALTH CENTER                  │   │
│  │  /patients                                                  │   │
│  │  /appointments                                              │   │
│  │  /consultations                                             │   │
│  │  /prescriptions                                             │   │
│  │  /referrals                                                 │   │
│  │  /triage                                                    │   │
│  │  /medical-records                                           │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODULE 2: SANITATION                     │   │
│  │  /permits                                                   │   │
│  │  /inspections                                               │   │
│  │  /payments                                                  │   │
│  │  /documents                                                 │   │
│  │  /renewals                                                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODULE 3: IMMUNIZATION                   │   │
│  │  /children                                                  │   │
│  │  /vaccinations                                              │   │
│  │  /vaccine-inventory                                         │   │
│  │  /growth-charts                                             │   │
│  │  /nutrition                                                 │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODULE 4: WASTEWATER                     │   │
│  │  /septic-tanks                                              │   │
│  │  /maintenance                                               │   │
│  │  /service-requests                                          │   │
│  │  /providers                                                 │   │
│  │  /billing                                                   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    MODULE 5: SURVEILLANCE                   │   │
│  │  /cases                                                     │   │
│  │  /outbreaks                                                 │   │
│  │  /contact-tracing                                           │   │
│  │  /mapping                                                   │   │
│  │  /alerts                                                    │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    SYSTEM ADMIN                             │   │
│  │  /users                                                     │   │
│  │  /roles                                                     │   │
│  │  /logs                                                      │   │
│  │  /settings                                                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

ERD
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              HSMS DATABASE SCHEMA                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │     users        │     │    patients     │     │ appointments   │               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │────<│ id (PK)         │────<│ id (PK)         │               │
│  │ name            │     │ user_id (FK)    │     │ patient_id (FK) │               │
│  │ email           │     │ first_name      │     │ doctor_id (FK)  │               │
│  │ password        │     │ last_name       │     │ date            │               │
│  │ role_id (FK)    │     │ birth_date      │     │ time            │               │
│  │ status          │     │ gender          │     │ status          │               │
│  └─────────────────┘     │ blood_type      │     │ type            │               │
│         │                │ contact         │     └─────────────────┘               │
│         │                │ address         │              │                         │
│         │                └─────────────────┘              │                         │
│         │                         │                       │                         │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │     roles        │     │ consultations   │     │ prescriptions   │               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │     │ id (PK)         │     │ id (PK)         │               │
│  │ name            │     │ patient_id (FK) │     │ patient_id (FK) │               │
│  │ description     │     │ doctor_id (FK)  │     │ doctor_id (FK)  │               │
│  │ permissions     │     │ appointment_id  │     │ consultation_id │               │
│  └─────────────────┘     │ diagnosis       │     │ date            │               │
│                          │ icd_code        │     │ status          │               │
│                          │ treatment       │     └─────────────────┘               │
│                          │ notes           │              │                         │
│                          └─────────────────┘              │                         │
│                                   │                       │                         │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │   permits        │     │  inspections    │     │   payments      │               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │────<│ id (PK)         │     │ id (PK)         │               │
│  │ applicant       │     │ permit_id (FK)  │     │ permit_id (FK)  │               │
│  │ business_type   │     │ inspector_id    │     │ amount          │               │
│  │ address         │     │ date            │     │ method          │               │
│  │ status          │     │ findings        │     │ reference_no    │               │
│  │ fee             │     │ status          │     │ status          │               │
│  └─────────────────┘     └─────────────────┘     └─────────────────┘               │
│                                                                                      │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │    children      │     │  vaccinations   │     │ vaccine_inventory│               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │────<│ id (PK)         │     │ id (PK)         │               │
│  │ name            │     │ child_id (FK)   │     │ vaccine_name    │               │
│  │ mother_name     │     │ vaccine_name    │     │ batch_number    │               │
│  │ birth_date      │     │ dose_number     │     │ quantity        │               │
│  │ weight          │     │ date            │     │ expiry_date     │               │
│  │ height          │     │ administered_by │     │ temperature     │               │
│  └─────────────────┘     │ next_due_date   │     │ status          │               │
│                          └─────────────────┘     └─────────────────┘               │
│                                                                                      │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │ septic_tanks    │     │ service_requests│     │    cases        │               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │────<│ id (PK)         │     │ id (PK)         │               │
│  │ owner_name      │     │ tank_id (FK)    │     │ disease         │               │
│  │ address         │     │ service_type    │     │ patient_name    │               │
│  │ capacity        │     │ date            │     │ barangay        │               │
│  │ type            │     │ status          │     │ onset_date      │               │
│  │ last_maintenance│     │ assigned_to     │     │ status          │               │
│  └─────────────────┘     └─────────────────┘     │ severity       │               │
│                                                    └─────────────────┘               │
│                                                                                      │
│  ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐               │
│  │   outbreaks      │     │  contact_tracing│     │   audit_logs    │               │
│  ├─────────────────┤     ├─────────────────┤     ├─────────────────┤               │
│  │ id (PK)         │────<│ id (PK)         │     │ id (PK)         │               │
│  │ disease         │     │ case_id (FK)    │     │ user_id (FK)    │               │
│  │ barangay        │     │ contact_name    │     │ action          │               │
│  │ cases_count     │     │ contact_phone   │     │ module          │               │
│  │ status          │     │ exposure_date   │     │ target_id       │               │
│  │ severity        │     │ quarantine_end  │     │ details         │               │
│  │ reported_date   │     │ status          │     │ ip_address      │               │
│  └─────────────────┘     └─────────────────┘     │ timestamp      │               │
│                                                    └─────────────────┘               │
└─────────────────────────────────────────────────────────────────────────────────────┘

RELATION MAPPING
┌─────────────────────────────────────────────────────────────────────┐
│                    TABLE RELATIONSHIPS                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. users → roles (Many-to-One)                                    │
│  2. users → patients (One-to-Many)                                 │
│  3. patients → appointments (One-to-Many)                         │
│  4. patients → consultations (One-to-Many)                        │
│  5. patients → prescriptions (One-to-Many)                        │
│  6. patients → referrals (One-to-Many)                            │
│  7. patients → medical_records (One-to-Many)                      │
│  8. appointments → consultations (One-to-One)                     │
│  9. consultations → prescriptions (One-to-Many)                   │
│  10. consultations → referrals (One-to-Many)                      │
│  11. permits → inspections (One-to-Many)                          │
│  12. permits → payments (One-to-Many)                             │
│  13. permits → violations (One-to-Many)                           │
│  14. children → vaccinations (One-to-Many)                        │
│  15. children → growth_charts (One-to-Many)                       │
│  16. septic_tanks → service_requests (One-to-Many)                │
│  17. septic_tanks → maintenance_records (One-to-Many)             │
│  18. cases → contact_tracing (One-to-Many)                        │
│  19. cases → outbreaks (One-to-Many)                              │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

 INDEXES FOR PERFORMANCE
 -- Patients
CREATE INDEX idx_patients_name ON patients(last_name, first_name);
CREATE INDEX idx_patients_barangay ON patients(barangay);
CREATE INDEX idx_patients_registration ON patients(registration_date);

-- Appointments
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);

-- Permits
CREATE INDEX idx_permits_status ON permits(status);
CREATE INDEX idx_permits_applicant ON permits(applicant);
CREATE INDEX idx_permits_date ON permits(created_at);

-- Cases
CREATE INDEX idx_cases_barangay ON cases(barangay);
CREATE INDEX idx_cases_disease ON cases(disease);
CREATE INDEX idx_cases_status ON cases(status);

-- Audit Logs
CREATE INDEX idx_audit_user ON audit_logs(user_id);
CREATE INDEX idx_audit_timestamp ON audit_logs(timestamp);
CREATE INDEX idx_audit_module ON audit_logs(module);

COMPLETE TABLE SCHEMAS
1. AUTHENTICATION & USER MANAGEMENT
Table: users
sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role_id INT NOT NULL,
    department VARCHAR(50),
    contact VARCHAR(20),
    profile_image VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
Table: roles
sql
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON, -- Store permissions as JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Table: permissions
sql
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    permission VARCHAR(50) NOT NULL, -- read, write, edit, delete
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
Table: password_resets
sql
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Table: sessions
sql
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
2. HEALTH CENTER SERVICES
Table: patients
sql
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id VARCHAR(20) UNIQUE NOT NULL, -- P-101 format
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    birth_date DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    barangay VARCHAR(50),
    emergency_contact VARCHAR(50),
    emergency_contact_number VARCHAR(20),
    allergies TEXT,
    medical_history JSON,
    registration_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: appointments
sql
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id VARCHAR(20) UNIQUE NOT NULL, -- APT-001 format
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL, -- References users
    service_type VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);
Table: triage
sql
CREATE TABLE triage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    nurse_id INT NOT NULL, -- References users
    blood_pressure VARCHAR(20),
    heart_rate INT,
    temperature DECIMAL(4,1),
    respiratory_rate INT,
    oxygen_saturation INT,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    symptoms TEXT,
    priority ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    allergies VARCHAR(255),
    medications VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (nurse_id) REFERENCES users(id)
);
Table: consultations
sql
CREATE TABLE consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consultation_id VARCHAR(20) UNIQUE NOT NULL, -- CON-001 format
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL, -- References users
    appointment_id INT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    diagnosis TEXT,
    icd_code VARCHAR(20),
    symptoms TEXT,
    vital_signs JSON,
    treatment_plan TEXT,
    notes TEXT,
    follow_up_date DATE,
    status ENUM('completed', 'referred') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);
Table: prescriptions
sql
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id VARCHAR(20) UNIQUE NOT NULL, -- RX-001 format
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    consultation_id INT,
    date DATE NOT NULL,
    medications JSON NOT NULL, -- [{name, dosage, frequency, duration, quantity, instructions}]
    notes TEXT,
    status ENUM('pending', 'dispensed', 'cancelled') DEFAULT 'pending',
    dispensed_by INT, -- References users (pharmacist)
    dispensed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (consultation_id) REFERENCES consultations(id),
    FOREIGN KEY (dispensed_by) REFERENCES users(id)
);
Table: referrals
sql
CREATE TABLE referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referral_id VARCHAR(20) UNIQUE NOT NULL, -- REF-001 format
    patient_id INT NOT NULL,
    from_doctor_id INT NOT NULL,
    to_doctor_id INT,
    to_hospital VARCHAR(100),
    reason TEXT NOT NULL,
    diagnosis TEXT,
    urgency ENUM('emergency', 'high', 'medium', 'low') DEFAULT 'medium',
    notes TEXT,
    status ENUM('pending', 'accepted', 'completed', 'rejected') DEFAULT 'pending',
    accepted_at DATETIME,
    completed_at DATETIME,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (from_doctor_id) REFERENCES users(id),
    FOREIGN KEY (to_doctor_id) REFERENCES users(id)
);
Table: medical_records
sql
CREATE TABLE medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    record_type ENUM('consultation', 'lab', 'imaging', 'procedure', 'other') NOT NULL,
    date DATE NOT NULL,
    description TEXT NOT NULL,
    attachments JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
3. SANITATION PERMITS
Table: permits
sql
CREATE TABLE permits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permit_id VARCHAR(20) UNIQUE NOT NULL, -- SP-1040 format
    applicant VARCHAR(100) NOT NULL,
    business_name VARCHAR(100),
    business_type VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    fee DECIMAL(10,2) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    inspector_id INT,
    inspection_date DATE,
    approved_date DATE,
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES users(id)
);
Table: permit_documents
sql
CREATE TABLE permit_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permit_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (permit_id) REFERENCES permits(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
Table: inspections
sql
CREATE TABLE inspections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_id VARCHAR(20) UNIQUE NOT NULL, -- INS-501 format
    permit_id INT NOT NULL,
    inspector_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    conducted_date DATETIME,
    findings JSON,
    overall_status ENUM('compliant', 'partially_compliant', 'non_compliant') DEFAULT 'partially_compliant',
    recommendations TEXT,
    attachments JSON,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (permit_id) REFERENCES permits(id),
    FOREIGN KEY (inspector_id) REFERENCES users(id)
);
Table: violations
sql
CREATE TABLE violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permit_id INT NOT NULL,
    inspection_id INT,
    violation_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'dismissed') DEFAULT 'pending',
    corrective_action TEXT,
    corrected_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (permit_id) REFERENCES permits(id),
    FOREIGN KEY (inspection_id) REFERENCES inspections(id)
);
Table: payments
sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id VARCHAR(20) UNIQUE NOT NULL, -- PAY-001 format
    permit_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('cash', 'gcash', 'paymaya', 'bank_transfer', 'over_the_counter') NOT NULL,
    reference_number VARCHAR(50) UNIQUE,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    receipt_path VARCHAR(255),
    paid_by VARCHAR(100),
    paid_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (permit_id) REFERENCES permits(id)
);
4. IMMUNIZATION & NUTRITION
Table: children
sql
CREATE TABLE children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id VARCHAR(20) UNIQUE NOT NULL, -- CH-001 format
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    birth_weight DECIMAL(4,1),
    birth_height DECIMAL(4,1),
    blood_type VARCHAR(5),
    mother_name VARCHAR(100) NOT NULL,
    mother_contact VARCHAR(20) NOT NULL,
    father_name VARCHAR(100),
    address TEXT NOT NULL,
    barangay VARCHAR(50),
    health_center VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: vaccinations
sql
CREATE TABLE vaccinations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    vaccine_name VARCHAR(50) NOT NULL,
    dose_number INT NOT NULL,
    dose_sequence VARCHAR(20), -- 1st dose, 2nd dose, etc.
    date DATE NOT NULL,
    administered_by INT NOT NULL, -- References users
    health_center VARCHAR(100) NOT NULL,
    batch_number VARCHAR(50),
    expiry_date DATE,
    site VARCHAR(20), -- Right arm, left arm, etc.
    route VARCHAR(20), -- IM, SC, etc.
    next_due_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id),
    FOREIGN KEY (administered_by) REFERENCES users(id)
);
Table: vaccine_inventory
sql
CREATE TABLE vaccine_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vaccine_name VARCHAR(50) NOT NULL,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    quantity INT NOT NULL,
    minimum_stock INT NOT NULL,
    received_date DATE,
    expiry_date DATE NOT NULL,
    temperature DECIMAL(4,1), -- Celsius
    storage_location VARCHAR(100),
    supplier VARCHAR(100),
    status ENUM('in_stock', 'low_stock', 'expired', 'out_of_stock') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: growth_charts
sql
CREATE TABLE growth_charts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    date DATE NOT NULL,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    head_circumference DECIMAL(5,2),
    weight_percentile INT,
    height_percentile INT,
    bmi DECIMAL(4,1),
    nutrition_status VARCHAR(50),
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);
Table: nutrition_assessments
sql
CREATE TABLE nutrition_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    date DATE NOT NULL,
    nutrition_status ENUM('normal', 'underweight', 'overweight', 'obese', 'malnourished') NOT NULL,
    risk_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assessment_notes TEXT,
    plan_of_action TEXT,
    supplement_given VARCHAR(100),
    next_assessment_date DATE,
    assessed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES children(id),
    FOREIGN KEY (assessed_by) REFERENCES users(id)
);
5. WASTEWATER SERVICES
Table: septic_tanks
sql
CREATE TABLE septic_tanks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tank_id VARCHAR(20) UNIQUE NOT NULL, -- ST-001 format
    owner_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6),
    capacity VARCHAR(20),
    type ENUM('concrete', 'plastic', 'fiberglass') DEFAULT 'concrete',
    installation_year YEAR,
    last_maintenance DATE,
    maintenance_frequency INT, -- in months
    status ENUM('good', 'needs_maintenance', 'critical') DEFAULT 'good',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: service_providers
sql
CREATE TABLE service_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id VARCHAR(20) UNIQUE NOT NULL, -- PRV-001 format
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    license_number VARCHAR(50),
    specialization VARCHAR(50),
    rating DECIMAL(3,2),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: service_requests
sql
CREATE TABLE service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id VARCHAR(20) UNIQUE NOT NULL, -- SR-001 format
    tank_id INT NOT NULL,
    service_type ENUM('desludging', 'maintenance', 'inspection', 'installation') NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME,
    provider_id INT,
    assigned_to INT, -- References users (technician)
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    scheduled_date DATE,
    completed_date DATETIME,
    notes TEXT,
    feedback TEXT,
    rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tank_id) REFERENCES septic_tanks(id),
    FOREIGN KEY (provider_id) REFERENCES service_providers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
Table: maintenance_records
sql
CREATE TABLE maintenance_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tank_id INT NOT NULL,
    service_request_id INT,
    maintenance_date DATETIME NOT NULL,
    performed_by INT NOT NULL,
    services_performed TEXT NOT NULL,
    materials_used JSON,
    cost DECIMAL(10,2),
    status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
    next_maintenance_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tank_id) REFERENCES septic_tanks(id),
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);
Table: billing (Wastewater)
sql
CREATE TABLE wastewater_billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id VARCHAR(20) UNIQUE NOT NULL, -- INV-001 format
    request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(50),
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id)
);
6. HEALTH SURVEILLANCE
Table: cases
sql
CREATE TABLE cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id VARCHAR(20) UNIQUE NOT NULL, -- CS-001 format
    disease VARCHAR(100) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    age INT,
    gender ENUM('Male', 'Female') NOT NULL,
    address TEXT NOT NULL,
    barangay VARCHAR(50) NOT NULL,
    contact VARCHAR(20),
    symptoms TEXT,
    onset_date DATE NOT NULL,
    reporting_facility VARCHAR(100),
    status ENUM('reported', 'investigating', 'confirmed', 'resolved', 'closed') DEFAULT 'reported',
    severity ENUM('low', 'moderate', 'high', 'critical') DEFAULT 'moderate',
    reported_by INT NOT NULL,
    investigator_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (investigator_id) REFERENCES users(id)
);
Table: outbreaks
sql
CREATE TABLE outbreaks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    outbreak_id VARCHAR(20) UNIQUE NOT NULL, -- OUT-001 format
    disease VARCHAR(100) NOT NULL,
    barangays JSON NOT NULL, -- List of affected barangays
    cases_count INT NOT NULL,
    severity ENUM('low', 'moderate', 'high', 'critical') NOT NULL,
    alert_level ENUM('yellow', 'orange', 'red') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'contained', 'resolved') DEFAULT 'active',
    recommendations TEXT,
    declared_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (declared_by) REFERENCES users(id)
);
Table: contact_tracing
sql
CREATE TABLE contact_tracing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_age INT,
    contact_gender ENUM('Male', 'Female'),
    contact_address TEXT NOT NULL,
    contact_phone VARCHAR(20),
    relationship VARCHAR(50),
    exposure_date DATE,
    exposure_type VARCHAR(50),
    exposure_duration VARCHAR(50),
    quarantine_start_date DATE,
    quarantine_end_date DATE,
    status ENUM('active', 'cleared', 'symptomatic', 'confirmed') DEFAULT 'active',
    monitored_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (monitored_by) REFERENCES users(id)
);
Table: outbreak_response
sql
CREATE TABLE outbreak_response (
    id INT PRIMARY KEY AUTO_INCREMENT,
    outbreak_id INT NOT NULL,
    response_team VARCHAR(100),
    actions JSON NOT NULL,
    resources_allocated JSON,
    response_date DATETIME NOT NULL,
    status ENUM('initiated', 'in_progress', 'completed') DEFAULT 'initiated',
    effectiveness_rating INT,
    lessons_learned TEXT,
    reported_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outbreak_id) REFERENCES outbreaks(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);
7. SYSTEM ADMIN & LOGS
Table: audit_logs
sql
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    target_type VARCHAR(50), -- patient, permit, etc.
    target_id VARCHAR(20),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
Table: system_settings
sql
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table: notifications
sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL, -- appointment, permit, vaccine, etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    read BOOLEAN DEFAULT FALSE,
    sent_via VARCHAR(50), -- email, sms, push
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
Table: schedules
sql
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL, -- doctor, inspector, technician
    entity_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);


SAMPLE DATA INSERTION
Users
sql
INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, department, status) 
VALUES 
('admin', 'admin@caloocan.gov.ph', '$2y$10$...', 'Maria', 'Santos', 1, 'System Admin', 'active'),
('dr.santos', 'dr.santos@caloocan.gov.ph', '$2y$10$...', 'Elena', 'Santos', 2, 'Health Center 1', 'active'),
('nurse.cruz', 'nurse.cruz@caloocan.gov.ph', '$2y$10$...', 'Maria', 'Cruz', 3, 'Health Center 1', 'active');
Patients
sql
INSERT INTO patients (patient_id, first_name, last_name, birth_date, gender, blood_type, contact, address, barangay, registration_date) 
VALUES 
('P-101', 'Pedro', 'Garcia', '1992-03-15', 'Male', 'O+', '09123456789', '123 Rizal St.', 'Barangay San Jose', '2024-01-15'),
('P-102', 'Rosa', 'Mendoza', '1998-06-01', 'Female', 'A+', '09176543210', '456 Mabini Ave.', 'Barangay Poblacion', '2024-01-20');
Roles
sql
INSERT INTO roles (name, description, permissions) VALUES 
('admin', 'Full system access', '{"all": true}'),
('doctor', 'Doctor access for consultations', '{"patients": "rw", "prescriptions": "rw", "consultations": "rw"}');










SUPABASE CONNECTION (PHP)
php
<?php
// supabase-config.php

define('SUPABASE_URL', 'https://your-project.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key');
define('SUPABASE_SERVICE_KEY', 'your-service-role-key');

function supabase_request($endpoint, $method = 'GET', $data = null, $useService = false) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    $key = $useService ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Helper functions
function supabase_get($table, $filters = []) {
    $query = $table;
    if (!empty($filters)) {
        $query .= '?' . http_build_query($filters);
    }
    return supabase_request($query, 'GET');
}

function supabase_insert($table, $data) {
    return supabase_request($table, 'POST', $data);
}

function supabase_update($table, $id, $data) {
    return supabase_request($table . '?id=eq.' . $id, 'PUT', $data);
}

function supabase_delete($table, $id) {
    return supabase_request($table . '?id=eq.' . $id, 'DELETE');
}
?>








┌─────────────────────────────────────────────────────────────────────┐
│                    TABLE RELATIONSHIPS                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. employees → patients (One-to-Many)                             │
│  2. employees → appointments (One-to-Many) - as doctor/staff       │
│  3. employees → consultations (One-to-Many) - as doctor            │
│  4. employees → prescriptions (One-to-Many) - as doctor            │
│  5. employees → medical_records (One-to-Many) - as creator         │
│  6. patients → appointments (One-to-Many)                          │
│  7. patients → consultations (One-to-Many)                         │
│  8. patients → prescriptions (One-to-Many)                         │
│  9. patients → medical_records (One-to-Many)                       │
│  10. appointments → consultations (One-to-One)                     │
│  11. consultations → prescriptions (One-to-Many)                   │
│  12. employees → permits (One-to-Many) - as inspector              │
│  13. permits → inspections (One-to-Many)                           │
│  14. permits → payments (One-to-Many)                              │
│  15. permits → violations (One-to-Many)                            │
│  16. employees → inspections (One-to-Many) - as inspector          │
│  17. children → vaccinations (One-to-Many)                         │
│  18. employees → vaccinations (One-to-Many) - as administrator     │
│  19. children → growth_charts (One-to-Many)                        │
│  20. employees → growth_charts (One-to-Many) - as recorder         │
│  21. septic_tanks → service_requests (One-to-Many)                 │
│  22. employees → service_requests (One-to-Many) - as assigned      │
│  23. employees → maintenance_records (One-to-Many) - as performer  │
│  24. cases → contact_tracing (One-to-Many)                         │
│  25. employees → cases (One-to-Many) - as reporter/investigator    │
│  26. employees → notifications (One-to-Many)                       │
│  27. employees → audit_logs (One-to-Many)                          │
│  28. employees → schedules (One-to-Many)                           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘

-- Patients
CREATE INDEX idx_patients_name ON patients(last_name, first_name);
CREATE INDEX idx_patients_barangay ON patients(barangay);
CREATE INDEX idx_patients_registration ON patients(registration_date);

-- Appointments
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_employee ON appointments(employee_id);

-- Permits
CREATE INDEX idx_permits_status ON permits(status);
CREATE INDEX idx_permits_applicant ON permits(applicant);
CREATE INDEX idx_permits_date ON permits(created_at);
CREATE INDEX idx_permits_inspector ON permits(inspector_id);

-- Cases
CREATE INDEX idx_cases_barangay ON cases(barangay);
CREATE INDEX idx_cases_disease ON cases(disease);
CREATE INDEX idx_cases_status ON cases(status);
CREATE INDEX idx_cases_reported_by ON cases(reported_by);

-- Audit Logs
CREATE INDEX idx_audit_employee ON audit_logs(employee_id);
CREATE INDEX idx_audit_timestamp ON audit_logs(created_at);
CREATE INDEX idx_audit_module ON audit_logs(module);

-- Notifications
CREATE INDEX idx_notifications_employee ON notifications(employee_id);
CREATE INDEX idx_notifications_read ON notifications(read);
CREATE INDEX idx_notifications_created ON notifications(created_at);



1. AUTHENTICATION & USER MANAGEMENT
sql
-- employees table (Your existing table)
CREATE TABLE public.employees (
    id SERIAL PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role VARCHAR(50) DEFAULT 'employee',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Enable Row Level Security
ALTER TABLE public.employees ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Employees can view their own profile
CREATE POLICY "Employees can view own profile" ON public.employees
    FOR SELECT USING (auth.uid()::text = employee_id);

-- RLS Policy: Admins can view all employees
CREATE POLICY "Admins can view all employees" ON public.employees
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM public.employees
            WHERE employee_id = auth.uid()::text AND role = 'admin'
        )
    );
2. HEALTH CENTER SERVICES
sql
-- patients table
CREATE TABLE public.patients (
    id SERIAL PRIMARY KEY,
    patient_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    birth_date DATE NOT NULL,
    gender TEXT NOT NULL CHECK (gender IN ('Male', 'Female', 'Other')),
    blood_type TEXT CHECK (blood_type IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    barangay VARCHAR(50),
    emergency_contact VARCHAR(50),
    emergency_contact_number VARCHAR(20),
    allergies TEXT,
    medical_history JSONB,
    registration_date DATE NOT NULL,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'archived')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.patients ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Only authenticated employees can view patients
CREATE POLICY "Employees can view patients" ON public.patients
    FOR SELECT USING (auth.role() = 'authenticated');

-- appointments table
CREATE TABLE public.appointments (
    id SERIAL PRIMARY KEY,
    appointment_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL, -- doctor/staff
    service_type VARCHAR(100) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'completed', 'cancelled', 'no_show')),
    priority TEXT DEFAULT 'medium' CHECK (priority IN ('critical', 'high', 'medium', 'low')),
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES public.employees(id)
);

ALTER TABLE public.appointments ENABLE ROW LEVEL SECURITY;

-- Indexes
CREATE INDEX idx_appointments_date ON public.appointments(appointment_date);
CREATE INDEX idx_appointments_status ON public.appointments(status);
CREATE INDEX idx_appointments_patient ON public.appointments(patient_id);

-- consultations table
CREATE TABLE public.consultations (
    id SERIAL PRIMARY KEY,
    consultation_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL, -- doctor
    appointment_id INTEGER,
    date DATE NOT NULL,
    time TIME NOT NULL,
    diagnosis TEXT,
    icd_code VARCHAR(20),
    symptoms TEXT,
    vital_signs JSONB,
    treatment_plan TEXT,
    notes TEXT,
    follow_up_date DATE,
    status TEXT DEFAULT 'completed' CHECK (status IN ('completed', 'referred')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    FOREIGN KEY (appointment_id) REFERENCES public.appointments(id)
);

ALTER TABLE public.consultations ENABLE ROW LEVEL SECURITY;

-- prescriptions table
CREATE TABLE public.prescriptions (
    id SERIAL PRIMARY KEY,
    prescription_id VARCHAR(20) UNIQUE NOT NULL,
    patient_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL, -- doctor
    consultation_id INTEGER,
    date DATE NOT NULL,
    medications JSONB NOT NULL,
    notes TEXT,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'dispensed', 'cancelled')),
    dispensed_by INTEGER,
    dispensed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES public.employees(id),
    FOREIGN KEY (consultation_id) REFERENCES public.consultations(id),
    FOREIGN KEY (dispensed_by) REFERENCES public.employees(id)
);

ALTER TABLE public.prescriptions ENABLE ROW LEVEL SECURITY;

-- medical_records table
CREATE TABLE public.medical_records (
    id SERIAL PRIMARY KEY,
    patient_id INTEGER NOT NULL,
    record_type TEXT NOT NULL CHECK (record_type IN ('consultation', 'lab', 'imaging', 'procedure', 'other')),
    date DATE NOT NULL,
    description TEXT NOT NULL,
    attachments JSONB,
    created_by INTEGER NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (patient_id) REFERENCES public.patients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES public.employees(id)
);

ALTER TABLE public.medical_records ENABLE ROW LEVEL SECURITY;
3. SANITATION PERMITS
sql
-- permits table
CREATE TABLE public.permits (
    id SERIAL PRIMARY KEY,
    permit_id VARCHAR(20) UNIQUE NOT NULL,
    applicant VARCHAR(100) NOT NULL,
    business_name VARCHAR(100),
    business_type VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    fee DECIMAL(10,2) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'under_review', 'approved', 'rejected', 'expired')),
    inspector_id INTEGER,
    inspection_date DATE,
    approved_date DATE,
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (inspector_id) REFERENCES public.employees(id)
);

ALTER TABLE public.permits ENABLE ROW LEVEL SECURITY;

-- Indexes
CREATE INDEX idx_permits_status ON public.permits(status);
CREATE INDEX idx_permits_applicant ON public.permits(applicant);
CREATE INDEX idx_permits_date ON public.permits(created_at);

-- inspections table
CREATE TABLE public.inspections (
    id SERIAL PRIMARY KEY,
    inspection_id VARCHAR(20) UNIQUE NOT NULL,
    permit_id INTEGER NOT NULL,
    inspector_id INTEGER NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    conducted_date TIMESTAMPTZ,
    findings JSONB,
    overall_status TEXT DEFAULT 'partially_compliant' CHECK (overall_status IN ('compliant', 'partially_compliant', 'non_compliant')),
    recommendations TEXT,
    attachments JSONB,
    status TEXT DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'completed', 'cancelled')),
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (permit_id) REFERENCES public.permits(id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES public.employees(id)
);

ALTER TABLE public.inspections ENABLE ROW LEVEL SECURITY;

-- violations table
CREATE TABLE public.violations (
    id SERIAL PRIMARY KEY,
    permit_id INTEGER NOT NULL,
    inspection_id INTEGER,
    violation_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    severity TEXT NOT NULL CHECK (severity IN ('low', 'medium', 'high', 'critical')),
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'resolved', 'dismissed')),
    corrective_action TEXT,
    corrected_date DATE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (permit_id) REFERENCES public.permits(id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_id) REFERENCES public.inspections(id)
);

ALTER TABLE public.violations ENABLE ROW LEVEL SECURITY;

-- payments table
CREATE TABLE public.payments (
    id SERIAL PRIMARY KEY,
    payment_id VARCHAR(20) UNIQUE NOT NULL,
    permit_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method TEXT NOT NULL CHECK (method IN ('cash', 'gcash', 'paymaya', 'bank_transfer', 'over_the_counter')),
    reference_number VARCHAR(50) UNIQUE,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
    receipt_path TEXT,
    paid_by VARCHAR(100),
    paid_at TIMESTAMPTZ,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (permit_id) REFERENCES public.permits(id) ON DELETE CASCADE
);

ALTER TABLE public.payments ENABLE ROW LEVEL SECURITY;
4. IMMUNIZATION & NUTRITION
sql
-- children table
CREATE TABLE public.children (
    id SERIAL PRIMARY KEY,
    child_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    birth_date DATE NOT NULL,
    gender TEXT NOT NULL CHECK (gender IN ('Male', 'Female')),
    birth_weight DECIMAL(4,1),
    birth_height DECIMAL(4,1),
    blood_type VARCHAR(5),
    mother_name VARCHAR(100) NOT NULL,
    mother_contact VARCHAR(20) NOT NULL,
    father_name VARCHAR(100),
    address TEXT NOT NULL,
    barangay VARCHAR(50),
    health_center VARCHAR(100),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.children ENABLE ROW LEVEL SECURITY;

-- vaccinations table
CREATE TABLE public.vaccinations (
    id SERIAL PRIMARY KEY,
    child_id INTEGER NOT NULL,
    vaccine_name VARCHAR(50) NOT NULL,
    dose_number INTEGER NOT NULL,
    dose_sequence VARCHAR(20),
    date DATE NOT NULL,
    administered_by INTEGER NOT NULL,
    health_center VARCHAR(100) NOT NULL,
    batch_number VARCHAR(50),
    expiry_date DATE,
    site VARCHAR(20),
    route VARCHAR(20),
    next_due_date DATE,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by) REFERENCES public.employees(id)
);

ALTER TABLE public.vaccinations ENABLE ROW LEVEL SECURITY;

-- vaccine_inventory table
CREATE TABLE public.vaccine_inventory (
    id SERIAL PRIMARY KEY,
    vaccine_name VARCHAR(50) NOT NULL,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    quantity INTEGER NOT NULL,
    minimum_stock INTEGER NOT NULL,
    received_date DATE,
    expiry_date DATE NOT NULL,
    temperature DECIMAL(4,1),
    storage_location VARCHAR(100),
    supplier VARCHAR(100),
    status TEXT DEFAULT 'in_stock' CHECK (status IN ('in_stock', 'low_stock', 'expired', 'out_of_stock')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.vaccine_inventory ENABLE ROW LEVEL SECURITY;

-- Trigger: Auto-update status
CREATE OR REPLACE FUNCTION update_vaccine_status()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.quantity <= 0 THEN
        NEW.status := 'out_of_stock';
    ELSIF NEW.quantity <= NEW.minimum_stock THEN
        NEW.status := 'low_stock';
    ELSIF NEW.expiry_date < CURRENT_DATE THEN
        NEW.status := 'expired';
    ELSE
        NEW.status := 'in_stock';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER vaccine_status_trigger
BEFORE INSERT OR UPDATE ON public.vaccine_inventory
FOR EACH ROW EXECUTE FUNCTION update_vaccine_status();

-- growth_charts table
CREATE TABLE public.growth_charts (
    id SERIAL PRIMARY KEY,
    child_id INTEGER NOT NULL,
    date DATE NOT NULL,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    head_circumference DECIMAL(5,2),
    weight_percentile INTEGER,
    height_percentile INTEGER,
    bmi DECIMAL(4,1),
    nutrition_status VARCHAR(50),
    recorded_by INTEGER NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES public.employees(id)
);

ALTER TABLE public.growth_charts ENABLE ROW LEVEL SECURITY;
5. WASTEWATER SERVICES
sql
-- septic_tanks table
CREATE TABLE public.septic_tanks (
    id SERIAL PRIMARY KEY,
    tank_id VARCHAR(20) UNIQUE NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6),
    capacity VARCHAR(20),
    type TEXT DEFAULT 'concrete' CHECK (type IN ('concrete', 'plastic', 'fiberglass')),
    installation_year INTEGER,
    last_maintenance DATE,
    maintenance_frequency INTEGER,
    status TEXT DEFAULT 'good' CHECK (status IN ('good', 'needs_maintenance', 'critical')),
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.septic_tanks ENABLE ROW LEVEL SECURITY;

-- service_providers table
CREATE TABLE public.service_providers (
    id SERIAL PRIMARY KEY,
    provider_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    license_number VARCHAR(50),
    specialization VARCHAR(50),
    rating DECIMAL(3,2),
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.service_providers ENABLE ROW LEVEL SECURITY;

-- service_requests table
CREATE TABLE public.service_requests (
    id SERIAL PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    tank_id INTEGER NOT NULL,
    service_type TEXT NOT NULL CHECK (service_type IN ('desludging', 'maintenance', 'inspection', 'installation')),
    preferred_date DATE NOT NULL,
    preferred_time TIME,
    provider_id INTEGER,
    assigned_to INTEGER,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'assigned', 'in_progress', 'completed', 'cancelled')),
    scheduled_date DATE,
    completed_date TIMESTAMPTZ,
    notes TEXT,
    feedback TEXT,
    rating INTEGER,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES public.service_providers(id),
    FOREIGN KEY (assigned_to) REFERENCES public.employees(id)
);

ALTER TABLE public.service_requests ENABLE ROW LEVEL SECURITY;

-- maintenance_records table
CREATE TABLE public.maintenance_records (
    id SERIAL PRIMARY KEY,
    tank_id INTEGER NOT NULL,
    service_request_id INTEGER,
    maintenance_date TIMESTAMPTZ NOT NULL,
    performed_by INTEGER NOT NULL,
    services_performed TEXT NOT NULL,
    materials_used JSONB,
    cost DECIMAL(10,2),
    status TEXT DEFAULT 'completed' CHECK (status IN ('completed', 'pending', 'cancelled')),
    next_maintenance_date DATE,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(id) ON DELETE CASCADE,
    FOREIGN KEY (service_request_id) REFERENCES public.service_requests(id),
    FOREIGN KEY (performed_by) REFERENCES public.employees(id)
);

ALTER TABLE public.maintenance_records ENABLE ROW LEVEL SECURITY;

-- wastewater_billing table
CREATE TABLE public.wastewater_billing (
    id SERIAL PRIMARY KEY,
    invoice_id VARCHAR(20) UNIQUE NOT NULL,
    request_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'overdue', 'cancelled')),
    payment_method VARCHAR(50),
    payment_reference VARCHAR(50),
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMPTZ,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (request_id) REFERENCES public.service_requests(id) ON DELETE CASCADE
);

ALTER TABLE public.wastewater_billing ENABLE ROW LEVEL SECURITY;
6. HEALTH SURVEILLANCE
sql
-- cases table
CREATE TABLE public.cases (
    id SERIAL PRIMARY KEY,
    case_id VARCHAR(20) UNIQUE NOT NULL,
    disease VARCHAR(100) NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    age INTEGER,
    gender TEXT NOT NULL CHECK (gender IN ('Male', 'Female')),
    address TEXT NOT NULL,
    barangay VARCHAR(50) NOT NULL,
    contact VARCHAR(20),
    symptoms TEXT,
    onset_date DATE NOT NULL,
    reporting_facility VARCHAR(100),
    status TEXT DEFAULT 'reported' CHECK (status IN ('reported', 'investigating', 'confirmed', 'resolved', 'closed')),
    severity TEXT DEFAULT 'moderate' CHECK (severity IN ('low', 'moderate', 'high', 'critical')),
    reported_by INTEGER NOT NULL,
    investigator_id INTEGER,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (reported_by) REFERENCES public.employees(id),
    FOREIGN KEY (investigator_id) REFERENCES public.employees(id)
);

ALTER TABLE public.cases ENABLE ROW LEVEL SECURITY;

-- Indexes
CREATE INDEX idx_cases_barangay ON public.cases(barangay);
CREATE INDEX idx_cases_disease ON public.cases(disease);
CREATE INDEX idx_cases_status ON public.cases(status);

-- outbreaks table
CREATE TABLE public.outbreaks (
    id SERIAL PRIMARY KEY,
    outbreak_id VARCHAR(20) UNIQUE NOT NULL,
    disease VARCHAR(100) NOT NULL,
    barangays JSONB NOT NULL,
    cases_count INTEGER NOT NULL,
    severity TEXT NOT NULL CHECK (severity IN ('low', 'moderate', 'high', 'critical')),
    alert_level TEXT NOT NULL CHECK (alert_level IN ('yellow', 'orange', 'red')),
    start_date DATE NOT NULL,
    end_date DATE,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'contained', 'resolved')),
    recommendations TEXT,
    declared_by INTEGER NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (declared_by) REFERENCES public.employees(id)
);

ALTER TABLE public.outbreaks ENABLE ROW LEVEL SECURITY;

-- contact_tracing table
CREATE TABLE public.contact_tracing (
    id SERIAL PRIMARY KEY,
    case_id INTEGER NOT NULL,
    contact_name VARCHAR(100) NOT NULL,
    contact_age INTEGER,
    contact_gender TEXT CHECK (contact_gender IN ('Male', 'Female')),
    contact_address TEXT NOT NULL,
    contact_phone VARCHAR(20),
    relationship VARCHAR(50),
    exposure_date DATE,
    exposure_type VARCHAR(50),
    exposure_duration VARCHAR(50),
    quarantine_start_date DATE,
    quarantine_end_date DATE,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'cleared', 'symptomatic', 'confirmed')),
    monitored_by INTEGER NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (case_id) REFERENCES public.cases(id) ON DELETE CASCADE,
    FOREIGN KEY (monitored_by) REFERENCES public.employees(id)
);

ALTER TABLE public.contact_tracing ENABLE ROW LEVEL SECURITY;
7. SYSTEM ADMIN & LOGS
sql
-- audit_logs table
CREATE TABLE public.audit_logs (
    id SERIAL PRIMARY KEY,
    employee_id INTEGER NOT NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    target_type VARCHAR(50),
    target_id VARCHAR(20),
    details JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

ALTER TABLE public.audit_logs ENABLE ROW LEVEL SECURITY;

-- Indexes
CREATE INDEX idx_audit_employee ON public.audit_logs(employee_id);
CREATE INDEX idx_audit_timestamp ON public.audit_logs(created_at);
CREATE INDEX idx_audit_module ON public.audit_logs(module);

-- notifications table
CREATE TABLE public.notifications (
    id SERIAL PRIMARY KEY,
    employee_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    read BOOLEAN DEFAULT FALSE,
    sent_via VARCHAR(50),
    sent_at TIMESTAMPTZ DEFAULT NOW(),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Employees can view own notifications
CREATE POLICY "Employees can view own notifications" ON public.notifications
    FOR SELECT USING (auth.uid()::text = employee_id::text);

-- system_settings table
CREATE TABLE public.system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE public.system_settings ENABLE ROW LEVEL SECURITY;

-- Insert default settings
INSERT INTO public.system_settings (setting_key, setting_value, setting_group, description) VALUES
('app_name', 'Health & Sanitation Management System', 'general', 'Application name'),
('app_version', '1.0.0', 'general', 'Application version'),
('timezone', 'Asia/Manila', 'general', 'System timezone'),
('enable_2fa', 'false', 'security', 'Enable two-factor authentication'),
('session_timeout', '3600', 'security', 'Session timeout in seconds'),
('max_login_attempts', '5', 'security', 'Maximum login attempts before lockout');

-- schedules table
CREATE TABLE public.schedules (
    id SERIAL PRIMARY KEY,
    employee_id INTEGER NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INTEGER NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status TEXT DEFAULT 'available' CHECK (status IN ('available', 'booked', 'unavailable')),
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (employee_id) REFERENCES public.employees(id) ON DELETE CASCADE
);

ALTER TABLE public.schedules ENABLE ROW LEVEL SECURITY;

-- Insert sample employees
INSERT INTO public.employees (employee_id, password, full_name, department, role) VALUES 
('ADMIN-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'IT Department', 'admin'),
('DOC-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Santos', 'Medical Department', 'doctor'),
('NURSE-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nurse Jane Reyes', 'Medical Department', 'nurse');
8. STORAGE BUCKETS (Supabase Storage)
sql
-- Create storage buckets
INSERT INTO storage.buckets (id, name, public) VALUES 
('patients', 'patients', true),
('permits', 'permits', true),
('vaccines', 'vaccines', true),
('profiles', 'profiles', true),
('reports', 'reports', true);

-- Storage Policies
CREATE POLICY "Employees can upload files" ON storage.objects
    FOR INSERT WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "Files are publicly readable" ON storage.objects
    FOR SELECT USING (true);