# Government Service Management System (GSMS)
## Health & Sanitation Management Module

Welcome to the **Health & Sanitation Management Module** for the LGU Government Service Management System (GSMS). This project is designed as an independent, decoupled microservice that handles clinical patient management, sanitation inspections, and wastewater regulation, while securely integrating with external government subsystems.

---

## 🛠️ Technology Stack
- **Backend:** PHP 8+ (Custom MVC Architecture)
- **Database:** Supabase (PostgreSQL with PostgREST RESTful APIs)
- **Frontend UI:** Tailwind CSS, Vanilla JavaScript
- **Icons & Assets:** FontAwesome 6
- **Architecture:** Microservices with Asynchronous Webhooks

---

## 🏥 Core Modules

### 1. Health Center Services
*   **Patient Management:** Registers citizens and stores their clinical history, vital signs, and diagnoses.
*   **Immunization & Prenatal:** Tracks vaccine schedules for children and maternal care records.
*   **Inventory:** Manages the dispensing of free government medicines.

### 2. Sanitation Permits & Inspections
*   **Sanitary Clearances:** Issues digital clearance certificates for commercial businesses.
*   **Field Inspections:** Allows sanitary inspectors to log health code violations.

### 3. Wastewater & Septic Management
*   **Desludging Quotations:** Computes and schedules septic siphoning services for households and businesses.

---

## 🔗 Microservices Integration Architecture (Tier 1 Core)

To ensure this module remains highly decoupled and resilient, we strictly adhere to a **Tiered Integration Strategy** that relies on HTTP REST APIs and Webhooks, preventing direct database coupling with other departments.

### Integration 1: Master Citizen Registry (API Client)
*   **Purpose:** Eliminates duplicate data entry and prevents human error.
*   **Mechanism:** When registering a patient, our system sends a `GET` request to `api/v1/citizens.php?citizen_id={id}`. The registry returns verified demographic data (Name, DOB, Address), which is parsed to instantly auto-fill our clinical registration forms.

### Integration 2: Central Treasury (API Client & Webhook Receiver)
*   **Purpose:** Enforces strict separation of duties. The Health Department does not collect physical cash for permits or fines.
*   **Mechanism:** Our system generates an invoice and `POST`s the billing data to the Treasury. Once the citizen pays, the Treasury fires a `POST` Webhook back to our system, which automatically updates the status to "PAID" and releases the permit.

### Integration 3: Business Permits / BPLO (API Provider)
*   **Purpose:** Acts as the digital gatekeeper for commercial operations.
*   **Mechanism:** The BPLO system `GET`s our `clearance_status` API. If our system returns `clearance_valid: false`, the BPLO automatically blocks the issuance of the Mayor's Business Permit.

---

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-repo/capstone.git
   cd capstone
   ```

2. **Database Configuration:**
   * Open `config/database.php`
   * Update your Supabase **URL** and **Anon/Service Keys**.

3. **Local Server setup:**
   * Place the folder in your `htdocs` (XAMPP/LAMPP) or `www` directory.
   * Start Apache.
   * Access via `http://localhost/capstone/`.

4. **Running the Integrations Simulator:**
   * Navigate to `http://localhost/capstone/management/integration_simulator.php` to visually demonstrate the API handshakes in real-time.

---
*Developed for Capstone Defense 2026. Microservices Architecture strictly implemented.*