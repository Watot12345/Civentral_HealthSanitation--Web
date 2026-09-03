# Government Service Management System (GSMS)
## Pragmatic Microservices Architecture & Integration Plan

**Lead Team:** Health & Sanitation Management (HSM)  
**Date:** August 28, 2026  
**Document Version:** 2.0.0 (Pragmatic & Focused Edition)

---

## 1. Executive Summary & Microservices Scope

In the LGU Government Service Management System (GSMS), attempting to connect to all 9 other student/department modules creates unmanageable complexity and severe over-engineering. 

To deliver a **clean, robust, and defensible microservices architecture**, our **Health & Sanitation Management** module implements a **Tiered Integration Strategy**:

```
                       ┌─────────────────────────────────────────────────────────┐
                       │       OUR SYSTEM: HEALTH & SANITATION MANAGEMENT        │
                       │   (Health Center, Sanitation, Immunization,             │
                       │    Wastewater & Septic, Health Surveillance)            │
                       └────────────────────────────┬────────────────────────────┘
                                                    │
                 ┌──────────────────────────────────┼──────────────────────────────────┐
                 │                                  │                                  │
                 ▼                                  ▼                                  ▼
        [ TIER 1: CORE ]                   [ TIER 1: CORE ]                   [ TIER 1: CORE ]
   ┌──────────────────────────┐       ┌──────────────────────────┐       ┌──────────────────────────┐
   │ 1. Citizen Registry      │       │ 2. Revenue & Treasury    │       │ 3. Permits & Licensing   │
   │    (Citizen Identity)    │       │    (Central Payments)    │       │    (Sanitation Clearance)│
   └──────────────────────────┘       └──────────────────────────┘       └──────────────────────────┘
                 │                                  │                                  │
                 └──────────────────────────────────┼──────────────────────────────────┘
                                                    │
                                     [ TIER 2: OPTIONAL FUTURE ]
                               ┌────────────────────┴────────────────────┐
                               ▼                                         ▼
                  ┌──────────────────────────┐              ┌──────────────────────────┐
                  │ 4. DRRM Outbreak Alert   │              │ 5. Social Services AICS  │
                  └──────────────────────────┘              └──────────────────────────┘

                                    [ TIER 3: DECOUPLED / OUT OF SCOPE ]
                 • Transport & Mobility (PUV, Traffic Ticketing) ➔ Disconnected
                 • Education & Scholarship Management            ➔ Disconnected
                 • Urban Planning & Zoning                       ➔ Disconnected
                 • Public Assets & Facilities (Cemeteries, Parks)➔ Disconnected
```

---

## 2. Universal Shared Master Keys

Only 3 universal identifiers are required across all integrations:

| Key Name | Sample Value | Description |
|---|---|---|
| **`citizen_id`** | `CTZN-PH-2026-008912` | Connects a patient/child in our clinic to their official Citizen Profile. |
| **`business_id`** | `BIZ-NCR-2026-00452` | Connects a sanitary inspection record to a registered commercial business. |
| **`transaction_id`** | `TXN-LGU-2026-89104` | Connects sanitation permit & desludging invoices to Central Treasury payments. |

---

## 3. Tier 1: The 3 Core Essential Integrations

---

### Core Integration 1 — Citizen Information (Master Citizen Registry)

#### Why It Exists
Instead of manually typing a patient’s full name, birth date, gender, address, and guardian details every time they visit the Health Center or Immunization clinic, our system fetches verified resident data using their `citizen_id`.

#### Integration Flow
1. Staff opens **Register Patient** or **Register Child** modal.
2. Staff enters `citizen_id` or scans resident QR code.
3. System calls `GET /api/v1/citizens/{citizen_id}` and auto-populates all form fields.

#### API Contract
```http
GET /api/v1/citizens/CTZN-PH-2026-008912
Host: citizen-registry.lgu.gov.ph
Authorization: Bearer <service_token>

Response 200 OK:
{
  "status": "success",
  "data": {
    "citizen_id": "CTZN-PH-2026-008912",
    "first_name": "Althea",
    "middle_name": "Santos",
    "last_name": "Cruz",
    "birth_date": "2024-05-14",
    "gender": "Female",
    "blood_type": "O+",
    "contact_number": "639368587433",
    "address": "124 Rizal Ave, Barangay Poblacion 1"
  }
}
```

---

### Core Integration 2 — Revenue Collection & Treasury (Unified Payments)

#### Why It Exists
Health & Sanitation does not handle cash transactions directly. Payments for **Sanitation Permits**, **Inspection Violations**, and **Septic Desludging Quotations** are routed through the LGU Central Treasury (GCash, Maya, Over-the-counter).

#### Integration Flow
1. Our system generates an invoice (`INV-2026-00412`) for a sanitation permit or septic desludging request.
2. Our system sends billing data to Treasury via `POST /api/v1/treasury/bills/create`.
3. When the citizen pays, Treasury posts a webhook to our endpoint: `POST /api/treasury-webhook.php`.
4. Our system marks the invoice as **Paid** and auto-issues the Sanitation Permit.

#### API & Webhook Contract
```http
# 1. We create the payable bill in Treasury
POST /api/v1/treasury/bills/create
Host: treasury.lgu.gov.ph
Content-Type: application/json

{
  "service_module": "SANITATION_PERMITS",
  "invoice_number": "INV-2026-00412",
  "payer_name": "Mateo Reyes",
  "amount": 1500.00,
  "callback_url": "https://health-sanitation.lgu.gov.ph/api/treasury-webhook.php"
}
```

```http
# 2. Treasury notifies us when paid
POST /api/treasury-webhook.php
Host: health-sanitation.lgu.gov.ph
Content-Type: application/json

{
  "event": "payment.succeeded",
  "invoice_number": "INV-2026-00412",
  "official_receipt_no": "OR-2026-990142",
  "amount_paid": 1500.00,
  "paid_at": "2026-08-28T23:15:00+08:00"
}
```

---

### Core Integration 3 — Permits & Licensing (Sanitation Clearance Gatekeeping)

#### Why It Exists
A business establishment cannot be issued a **Business Permit** without a valid **Sanitation Inspection Clearance** issued by our Sanitation team.

#### Integration Flow
1. Business Permit portal checks our API before allowing final permit approval: `GET /api/v1/sanitation/clearance-status?business_id=BIZ-NCR-2026-00452`.
2. If `clearance_valid: true`, the Business Permit office approves the application.
3. If `clearance_valid: false`, the Business Permit office prompts the applicant to complete their sanitary inspection first.

#### API Contract
```http
GET /api/v1/sanitation/clearance-status?business_id=BIZ-NCR-2026-00452
Host: health-sanitation.lgu.gov.ph
Authorization: Bearer <service_token>

Response 200 OK:
{
  "business_id": "BIZ-NCR-2026-00452",
  "business_name": "Reyes Food Manufacturing Corp.",
  "sanitation_status": "APPROVED",
  "sanitation_permit_no": "SAN-2026-00109",
  "expiry_date": "2027-01-31",
  "clearance_valid": true
}
```

---

## 4. Tier 2: Secondary / Optional Integrations (Future Recommendations)

These two can be mentioned in capstone documentation as future roadmap capabilities:

1. **DRRM Emergency Response**:
   - Outbreak spikes detected on our **Spatial Epidemiology Map** push an alert payload (`/api/v1/drrm/epidemic-alert`) to trigger disaster misting/fogging teams.
2. **Social Services (AICS / PWD)**:
   - Malnutrition cases diagnosed in **Nutrition Assessment** transmit an enrollment record (`/api/v1/social-services/aid-enrollment`) to the city supplementary feeding program.

---

## 5. Tier 3: Out-of-Scope Domains (Decoupled by Design)

The following 4 subsystems are intentionally **disconnected** from Health & Sanitation to maintain clean domain boundaries:

| Decoupled Subsystem | Architectural Rationale for Keeping Separate |
|---|---|
| **Transport & Mobility** *(PUVs, Traffic Tickets)* | Completely unrelated to clinical healthcare and sanitation permits. |
| **Education & Scholarship** *(Scholarships, Grants)* | Student financial scholarships are managed independently by the City Scholarship Office. |
| **Urban Planning, Zoning & Housing** | Zoning clearances and subdivision reviews belong to the City Planning & Engineering Office. |
| **Public Assets & Facilities Management** | Cemetery lot booking and park rentals are administrative asset tasks unrelated to health center clinics. |

---

## 6. Implementation Summary for Capstone Defense

| Priority | Microservice Partner | Purpose | Endpoint / Mechanism |
|---|---|---|---|
| **P1 (Core)** | **Citizen Information** | Master Citizen Profile Fetch | `GET /api/v1/citizens/{id}` |
| **P1 (Core)** | **Revenue & Treasury** | Digital Payment Gateway & e-Receipt | `POST /api/v1/treasury/bills/create` & Webhook |
| **P1 (Core)** | **Permits & Licensing**| Business Sanitation Clearance Check | `GET /api/v1/sanitation/clearance-status` |
| **P2 (Optional)**| **DRRM** | Outbreak Cluster Alert | `POST /api/v1/drrm/epidemic-alert` |
| **P2 (Optional)**| **Social Services** | Malnutrition Feeding Program Referral | `POST /api/v1/social-services/aid-enrollment` |
| **P3 (Excluded)**| **All Other 4 Domains** | Decoupled by Design | N/A (Isolated) |

---

*Plan updated and simplified: August 28, 2026*
