# Permits & Licensing (BPLO) Integration Specification

**Affected Module:** Sanitation Permits (Sanitary Inspection & Clearance)
**Integration Partner:** Business Permits & Licensing Office (BPLO) - Tier 1 Core
**Integration Method:** RESTful API (HTTP GET Request)

---

## 1. The Request (What BPLO SENDS to our system)

Unlike the Treasury integration where we initiate the request, for this integration, **we are the API provider**. When a business owner tries to renew their business permit on the BPLO portal, the BPLO system acts as the client and queries our Health & Sanitation database to see if the business is safe.

**Our API Endpoint They Call:** `GET /api/v1/clearance_status.php?business_id={business_id}`

### Data Sent (Input / What we "Get" from BPLO)
The BPLO system only sends us one key piece of information in the URL parameter:

| Parameter Name | Data Type | Example Value | Description |
| :--- | :--- | :--- | :--- |
| **`business_id`** | String | `BIZ-NCR-2026-00452` | The unique Business Identification Number assigned by City Hall. |

*Note: BPLO also sends a secret Authorization Token in the header so we know the request is officially from the BPLO server.*

---

## 2. The Response (What we PUT/SEND back to BPLO)

Upon receiving the `business_id`, our system instantly runs a query: 
`SELECT * FROM sanitation_permits WHERE business_id = 'BIZ-NCR-2026-00452' ORDER BY expiry_date DESC LIMIT 1`

Depending on the result, we send back a JSON payload deciding the fate of their business permit.

### Data Received (Output / What we "Put" to BPLO)

| Data Field Sent | Example Value | Description |
| :--- | :--- | :--- |
| **`business_id`** | `BIZ-NCR-2026-00452` | Echoing back the ID for confirmation. |
| **`sanitation_status`** | `APPROVED` | Can be APPROVED, PENDING, or REVOKED. |
| **`sanitation_permit_no`** | `SAN-2026-00109` | Our official clearance certificate number. |
| **`expiry_date`** | `2027-01-31` | The date our clearance expires. |
| **`clearance_valid`** | `true` | **The Master Switch:** True if they are allowed to operate, False if they are not. |

---

## 3. Automation Logic & Scope (How BPLO Executes based on our data)

This integration is completely automated and creates a "Gatekeeper" effect for the whole city:

1. **If we send `clearance_valid: true`:**
   The BPLO system automatically unlocks the "Next" button on the citizen's screen, allowing them to proceed with paying their business taxes and printing their Mayor's Permit.

2. **If we send `clearance_valid: false` (or if it's expired/revoked):**
   The BPLO system automatically **blocks** the business permit renewal. It displays an error message to the citizen: *"Application halted. You do not have a valid Sanitary Clearance. Please proceed to the Health Department for inspection."*

### Why this is a powerful defense point:
This proves to your professor that your Health & Sanitation system doesn't just exist in isolation. By acting as the API Provider for BPLO, **your system actively enforces the city's public health safety laws at a systemic level.**
