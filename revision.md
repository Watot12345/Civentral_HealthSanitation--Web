# Capstone Documentation Revision Guide (Chapters 1 to 3)
**Target File:** `doc.txt`  
**Project Title:** DESIGN AND DEVELOPMENT OF A WEB-BASED HEALTH AND SANITATION MANAGEMENT INFORMATION SYSTEM WITH GEMINI-POWERED AI ANALYTICS, DECISION SUPPORT, AND AUTOMATED REPORT GENERATION FOR LOCAL GOVERNMENT UNIT  
**Institution:** Bestlink College of the Philippines — College of Computing Studies  
**Date of Audit:** October 2026  

---

## 📌 How to Use This Revision Guide
This document details every correction, inconsistency, missing section, and typographical error identified in `doc.txt` across **Chapters 1, 2, and 3**, comparing the manuscript directly against your live codebase.

### Priority Levels:
* 🔴 **CRITICAL**: Factual contradiction between `doc.txt` and the codebase (must fix before panel defense).
* 🟠 **MODERATE**: Missing Table of Contents entries, blank metadata fields, or degree inconsistencies.
* 🟡 **MINOR / TYPO**: Formatting glitches, punctuation errors, or capitalization typos.
* 🔵 **DEFENSE CLARIFICATION**: Strategic phrasing adjustments to preempt difficult questions from panelists.

---

## Section 1: Preliminary Pages Revisions

### 1.1 Abstract — Degree Title Mismatch
* **Location:** Line 207
* **Priority:** 🔴 **CRITICAL**
* **Current Text:**
  ```text
  Degree                Bachelor of Science in Information Management
  ```
* **Recommended Revision:**
  ```text
  Degree                Bachelor of Science in Information Technology
  ```
* **Rationale:** On the Title Page (Line 15), Approval Sheet (Line 34), and Acceptance Sheet (Line 51), the degree is correctly stated as *Bachelor of Science in Information Technology*. Having *Information Management* in the abstract is an internal contradiction that panel members will immediately notice.

---

### 1.2 Abstract — Blank Completion Date & Keywords
* **Location:** Lines 212–214
* **Priority:** 🟠 **MODERATE**
* **Current Text:**
  ```text
  Date of Completion:

  Keywords:
  ```
* **Recommended Revision:**
  ```text
  Date of Completion:   October 2026

  Keywords:             Health and Sanitation, Management Information System (MIS), Local Government Unit (LGU), Google Gemini AI, Decision Support System (DSS), Automated Report Generation, Role-Based Access Control (RBAC), Microservices Interoperability
  ```
* **Rationale:** These placeholder fields are currently blank in `doc.txt`. Adding relevant keywords aligns with formal academic research standards.

---

### 1.3 Table of Contents — Roman Numeral Typo
* **Location:** Line 219
* **Priority:** 🟡 **MINOR / TYPO**
* **Current Text:**
  ```text
  APPROVAL SHEET                                        !
  ```
* **Recommended Revision:**
  ```text
  APPROVAL SHEET                                        i
  ```
* **Rationale:** The exclamation mark (`!`) is a typo for lowercase Roman numeral `i`.

---

### 1.4 Table of Contents — Missing Chapter 1 Entries
* **Location:** Between Line 239 and Line 240
* **Priority:** 🟠 **MODERATE**
* **Issue:** `1.6 Definition of Terms` (which begins on Line 1007, Page 29) is completely missing from the Table of Contents.
* **Recommended Revision:** Insert into TOC:
  ```text
                 1.5.2 Practical Significance         26
                 1.6 Definition of Terms              29
              1.7 Structure of the Document           34
  ```

---

### 1.5 Table of Contents — Missing Chapter 2 Entries
* **Location:** Lines 250–263
* **Priority:** 🟠 **MODERATE**
* **Issue:** Several key sections present in Chapter 2 are omitted or misnumbered in the Table of Contents:
  1. Section `2.3 Agile Scrum Methodology` (Page 42) is missing its section number `2.3`.
  2. Section `2.4 Emerging Technologies, Intelligent Systems, and Standards in Modern Health and Sanitation Information Systems` is missing its heading.
  3. Section `2.4.7 Data Privacy` (Line 2297, Page 62) is omitted between `2.4.6 Cybersecurity` and `2.4.8 Software Quality Standards`.
  4. Section `2.7 Conceptual Framework` (Line 2761, Page 75) is omitted from TOC.
  5. Section `2.8 Theoretical Paradigm` (Line 2799, Page 76) is omitted from TOC.
* **Recommended Revision in TOC:**
  ```text
           2.2 Analysis of Related Studies and Existing
           Systems                                               41
           2.3 Agile Scrum Methodology                           42
              2.3.1 Agile Methodology                            42
              2.3.2 Scrum Framework                              43
              2.3.3 Application of Agile Scrum in the Proposed
                    Study                                        45
           2.4 Emerging Technologies, Intelligent Systems,
               and Standards                                     48
              2.4.1 Microservices Architecture                   49
              2.4.2 Artificial Intelligence (AI)                 52
              2.4.3 Internet of Things (IoT)                     54
              2.4.4 Data Analytics and Business Intelligence     56
              2.4.5 Polyglot Persistence                         58
              2.4.6 Cybersecurity                                60
              2.4.7 Data Privacy (RA 10173)                      62
              2.4.8 Software Quality Standards (ISO/IEC 25010)   64
              2.4.9 Cloud Computing                              65
              2.4.10 Edge Computing                              68
           2.5 DevOps Culture and CI/CD Practices                69
           2.6 Enterprise Architecture and System Integration    71
           2.7 Conceptual Framework                              75
           2.8 Theoretical Paradigm                              76
  ```

---

## Section 2: Chapter 1 Revisions (Introduction, Scope & Objectives)

### 2.1 Scope & Codebase Reality Alignment (Citizen Mobile App)
* **Location:** Lines 333–337, 453–456, 703–726, 819–821, 859–865, 978–991, 1044–1051, 1160–1169
* **Priority:** 🔵 **DEFENSE CLARIFICATION**
* **Context:** `doc.txt` repeatedly describes a companion "Citizen Mobile Application". However, the current capstone repository is the **Web-Based Administrative System and REST API Server**, with responsive PWA capabilities (`sw.js`, `manifest.json`). The Flutter/React Native mobile client is specified as an integration architecture in `docs/planning/Citizen_Mobile_App_Integration_Plan.md`.
* **Recommended Clarification in Section 1.3.1 (Page 21, Line 703):**
  * *Add Clarifying Note:*
    ```text
    Note on Mobile Access & Companion Client: The core system provides mobile-responsive access and Progressive Web App (PWA) offline capabilities for authorized staff and field inspectors. In addition, the centralized database and backend services are architected with dedicated RESTful API endpoints and Supabase Row-Level Security (RLS) policies specifically structured to support the companion citizen mobile application for online appointments, permit applications, and service requests.
    ```
* **Why this protects you:** If the panel asks to see the compiled mobile APK during defense of this repository, you can demonstrate that the Web PWA works seamlessly on mobile devices, and that the backend REST APIs and Supabase schemas are already configured for external mobile app consumption as planned.

---

### 2.2 Objective 1.4.2 Bullet Formatting
* **Location:** Lines 835–886 (Pages 24–25)
* **Priority:** 🟡 **MINOR / TYPO**
* **Issue:** Bullet alignment in Specific Objectives has inconsistent indentation in the raw text. Ensure consistent tab spacing across all 7 bullet points in the printed manuscript.

---

## Section 3: Chapter 2 Revisions (Literature, Technical Background & Frameworks)

### 3.1 Literature Consistency on IoT & Polyglot Persistence
* **Location:** Section 2.4.3 (p. 54) and Section 2.4.5 (p. 58)
* **Priority:** 🔵 **DEFENSE CLARIFICATION**
* **Observation:** `doc.txt` contains extensive literature reviews on IoT and Polyglot Persistence. Line 2189 already states: *"Polyglot Persistence may be considered as a future enhancement... While the current implementation primarily utilizes a relational database..."*
* **Recommended Polish (Section 2.4.3 IoT, Page 55):**
  Ensure the last paragraph explicitly concludes:
  ```text
  In the current implementation of the proposed system, physical IoT hardware sensors are identified as an architectural opportunity for future expansion, while current monitoring relies on digitized inspections, clinical triage logs, and surveillance reports.
  ```
* **Why this protects you:** Guarantees that panel members do not demand a live hardware demo with Arduino/ESP32 water sensors.

---

### 3.2 Reference URL Formatting
* **Location:** Lines 3969–3988 (Pages 110–111)
* **Priority:** 🟡 **MINOR / TYPO**
* **Issue:** Several ResearchGate and conference publication URLs are split across multiple lines with arbitrary hyphenation (e.g. `_Heal\nth_Service_`).
* **Recommended Revision:** Merge split URLs into continuous, clickable hyperlinks in your final Word/PDF document.

---

## Section 4: Chapter 3 Revisions (Methodology & Project Management)

### 4.1 Toolstack: Elimination of "Laravel" Copy-Paste Error
* **Location:** Line 3634–3636 (Page 100)
* **Priority:** 🔴 **CRITICAL**
* **Current Text:**
  ```text
   PHPMailer             Composer is used to manage the PHP packages
                         and dependencies required by the Laravel
                         application.
  ```
* **Recommended Revision:**
  ```text
   Composer / PHPMailer  Composer is used to manage third-party PHP packages
                         and libraries (including PHPMailer for email dispatch,
                         Dompdf for PDF generation, and PhpSpreadsheet for Excel
                         exports) required by the custom PHP MVC application.
  ```
* **Rationale:** The system does **not** use the Laravel framework; it is built on a custom lightweight MVC architecture (`Core/BaseController.php`, `Core/Router.php`, `Core/Env.php`). Claiming Laravel is a factual error that any technical panelist will penalize.

---

### 4.2 Toolstack: Elimination of "MySQL" Mention
* **Location:** Line 3626–3627 (Page 100)
* **Priority:** 🔴 **CRITICAL**
* **Current Text:**
  ```text
   XAMPP/Dokploy         XAMPP is used as the local development
                         environment for running the PHP application and
                         MySQL database, while Dokploy is used for
                         deployment.
  ```
* **Recommended Revision:**
  ```text
   XAMPP/Dokploy         XAMPP (Apache/PHP) is used as the local development
                         environment for serving the PHP web application,
                         connecting to the cloud PostgreSQL (Supabase) database,
                         while Dokploy is used for cloud staging and deployment.
  ```
* **Rationale:** In Section 3.1.4 (Line 3311) and Section 3.3.1 (Line 3630), PostgreSQL is correctly identified. The codebase (`config/database.php`) interacts exclusively with PostgreSQL/PostgREST on Supabase. MySQL is never used.

---

### 4.3 Capitalization Typo: "PostgreSqL"
* **Location:** Line 3344 (Page 92)
* **Priority:** 🟡 **MINOR / TYPO**
* **Current Text:**
  ```text
  running the PHP application and PostgreSqL database.
  ```
* **Recommended Revision:**
  ```text
  running the PHP application and PostgreSQL database.
  ```

---

### 4.4 Toolstack Table: Clarifying PHP in Frontend
* **Location:** Lines 3294–3295 (Page 90)
* **Priority:** 🟡 **MINOR / TYPO**
* **Current Text:**
  ```text
   PHP             It is used as a PHP templating engine for creating
                   dynamic and reusable web pages.
  ```
* **Recommended Revision:**
  ```text
   PHP / Component Templates   Used as a server-side templating engine for rendering
                               reusable UI views, headers, navigation bars, and modals.
  ```

---

### 4.5 Architectural Precision: Microservices Subsystem vs. Monolith
* **Location:** Section 3.2.1 (Pages 93–94)
* **Priority:** 🔵 **DEFENSE CLARIFICATION**
* **Context:** Section 3.2 discusses Microservices Architecture. Panelists frequently ask: *"If this is a microservice, why are all 5 modules inside one PHP project?"*
* **Recommended Revision / Refinement to Section 3.2.1 (Page 94):**
  Add the following explicit paragraph:
  ```text
  In the context of the greater Caloocan City Government Service Management System (GSMS), the Health and Sanitation Management Information System operates as an autonomous domain microservice. Internally, the module is structured using a decoupled Model-View-Controller (MVC) architecture to ensure maintainability and low operational overhead. Externally, it maintains zero database coupling with other city departments (such as Central Treasury, Master Citizen Registry, and Business Permits Licensing Office), communicating exclusively through HTTP REST APIs and asynchronous webhooks.
  ```
* **Why this is essential:** This demonstrates high architectural maturity. It shows you understand that microservices apply to inter-departmental city systems, while avoiding unnecessary distributed microservice complexity inside a single department.

---

## Section 5: Defense-Proofing Cheat Sheet (Chapters 1 to 3)

| Likely Question from Panel | Where It Is Covered in Doc | Bulletproof Response to Give |
| :--- | :--- | :--- |
| **"Why is your system using Gemini AI instead of a traditional rule-based system?"** | Chapter 1 (1.1, 1.4.2)<br>Chapter 3 (3.4.1–3.4.3) | *"Traditional rule-based systems can only output fixed thresholds. Gemini AI analyzes multi-variable health patterns, computes predictive epidemiological trends, and automatically drafts narrative executive summaries for health directors. Crucially, as stated in Section 1.3.2, AI functions strictly as a decision-support aid—final decisions remain with licensed human officers."* |
| **"How do you comply with the Data Privacy Act of 2012 (RA 10173)?"** | Chapter 2 (2.4.7)<br>Chapter 3 (3.2.6) | *"We implement PostgreSQL `pgcrypto` column-level AES-256 encryption on all sensitive patient data (contacts, IDs, health notes), enforce Role-Based Access Control where staff only view authorized department data, record patient consent logs, and provide an automated data masking engine for non-admin viewers."* |
| **"How does Health & Sanitation integrate with other city departments?"** | Chapter 2 (2.6)<br>Chapter 3 (3.2.3, 3.2.8) | *"We adhere to Tier-1 API decoupling. For citizen registration, we query the Citizen Registry API. For payments, we submit invoices to Treasury and listen for asynchronous payment webhooks. For commercial permits, BPLO queries our clearance API. We have a live Integration Simulator in the management panel to prove this handshake."* |
| **"Why did you choose PostgreSQL / Supabase over traditional MySQL?"** | Chapter 3 (3.1.4, 3.3.1) | *"Supabase provides PostgreSQL with native Row-Level Security (RLS), pgcrypto encryption, and PostgREST APIs. This allows both our web backend and future mobile clients to safely share the same real-time data layer without exposing internal database credentials."* |

---

## Section 6: Action Checklist for `doc.txt`

- [ ] **Item 1:** Fix Abstract degree on **Line 207** (`Information Management` $\rightarrow$ `Information Technology`).
- [ ] **Item 2:** Fill in **Line 212** (Completion Date) and **Line 214** (Keywords).
- [ ] **Item 3:** Replace exclamation mark on **Line 219** with Roman numeral `i`.
- [ ] **Item 4:** Insert missing `1.6 Definition of Terms` entry in Table of Contents (**Line 240**).
- [ ] **Item 5:** Add missing entries `2.3`, `2.4`, `2.4.7`, `2.7`, and `2.8` to Chapter 2 Table of Contents (**Lines 250–263**).
- [ ] **Item 6:** Correct capitalization of `PostgreSqL` on **Line 3344**.
- [ ] **Item 7:** Remove `MySQL` on **Line 3627**; replace with `PostgreSQL (Supabase)`.
- [ ] **Item 8:** Remove `Laravel` on **Line 3635**; replace with `custom PHP MVC application`.
- [ ] **Item 9:** Rejoin broken multi-line URLs in References (**Lines 3969–3988**).
- [ ] **Item 10:** Add the microservice clarifying paragraph to **Section 3.2.1** to clarify the inter-departmental LGU architecture.
