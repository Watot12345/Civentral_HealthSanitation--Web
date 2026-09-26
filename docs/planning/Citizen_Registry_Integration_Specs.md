# Health Center Services & Citizen Registry Integration Specification

**Module:** Health Center Services
**Integration Partner:** Master Citizen Registry (Tier 1 Core Integration)
**Integration Method:** RESTful API (HTTP GET Request)

---

## 1. The Request (What we SEND to their integration)

When a health worker wants to register a patient, our system sends a very small, secure request to the Citizen Registry. 

**API Endpoint Called:** `GET /api/v1/citizens/{citizen_id}`

### Data Sent (Input Parameters)
Our system only needs to provide **ONE** piece of information to the external registry:

| Parameter Name | Data Type | Example Value | Description |
| :--- | :--- | :--- | :--- |
| **`citizen_id`** | String | `CTZN-PH-2026-008912` | The unique alphanumeric identifier found on the citizen's physical LGU ID card or QR code. |

**Security Note:** We also send a hidden `Authorization: Bearer <token>` in the HTTP header to prove to their server that our Health System is authorized to request this private data.

---

## 2. The Response (What we GET from their integration)

If the `citizen_id` is valid, their server responds with a JSON payload containing the citizen's verified demographic data. 

**HTTP Response:** `200 OK`

### Data Received (Output Payload)
We receive the "Who" data. We map this data directly into our Health Center's "New Patient" form fields.

| Data Field Received | Example Value | Where it goes in our System |
| :--- | :--- | :--- |
| **`citizen_id`** | `CTZN-PH-2026-008912` | Saved as a Unique Key in our `local_patients` table to prevent duplicates. |
| **`first_name`** | `Althea` | Auto-fills the Patient First Name field. |
| **`middle_name`** | `Santos` | Auto-fills the Patient Middle Name field. |
| **`last_name`** | `Cruz` | Auto-fills the Patient Last Name field. |
| **`birth_date`** | `2024-05-14` | Auto-fills the DOB field (Our system automatically calculates exact Age from this). |
| **`gender`** | `Female` | Auto-selects the Gender dropdown. |
| **`blood_type`** | `O+` | Auto-selects the Blood Type dropdown. |
| **`contact_number`** | `639368587433` | Auto-fills the Contact Number field. |
| **`address`** | `124 Rizal Ave, Barangay Poblacion 1` | Auto-fills the permanent address field. |

---

## 3. Data Ownership (What we ADD in our system)

It is crucial to clarify that the Master Citizen Registry **does not** hold medical data. After we get the demographic data above, our **Health Center Services** module takes over. 

Our local database creates the patient profile and adds the actual medical records:

*   **Vital Signs:** (Blood pressure, weight, height, temperature)
*   **Consultation History:** (Symptoms, diagnosis, doctor's notes)
*   **Prescriptions:** (Medicines issued by the pharmacy)
*   **Immunizations:** (Vaccine batches and dates given)

### Anti-Duplication Logic
1. System receives the `citizen_id` from the user input.
2. System queries local database: `SELECT * FROM local_patients WHERE citizen_id = ?`
3. **If Found:** System bypasses the API integration and opens the patient's existing local medical record.
4. **If Not Found:** System calls the Master Registry API, fetches the data, and creates a brand new local patient record.
