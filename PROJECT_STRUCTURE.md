# 📂 Civentral Project Structure
## System Architecture Visualization

### 🏗️ Backend Core & Framework
*   **`Core/`**: The engine of the application.
    *   `BaseController.php`: Parent class for all controllers, handling input parsing and response routing.
    *   `Response.php`: Standardized JSON response handler for the entire API.
    *   `Env.php`: Secure environment variable loader (for Supabase keys).
*   **`app/`**: The MVC Application logic.
    *   **`Controllers/`**: Business logic. Routes requests from the API to the Models.
    *   **`Models/`**: Database interaction. Abstracts Supabase cURL requests into object-oriented methods.
*   **`config/`**:
    *   `database.php`: Singleton database connection class for Supabase integration.
*   **`api/`**: The RESTful entry points. These files receive HTTP requests and delegate them to the appropriate controllers.

### 🌐 Frontend & User Interface
*   **`pages/`**: High-level dashboard views (Analytics, Reports, AI Insights).
*   **`modules/`**: The core operational features divided by department:
    *   `healthservices/`: Patient care, consultations, triage, and prescriptions.
    *   `sanitation/`: Permit applications, inspections, and renewals.
    *   `immunization/`: Child records, vaccinations, and growth tracking.
    *   `services/`: Septic tank maintenance and wastewater billing.
    *   `surveillence/`: Disease mapping, outbreak detection, and contact tracing.
*   **`includes/`**: Reusable UI components (Navbar, Sidebar, Footer) and the **Data Masking System**.
*   **`assets/`**: Static files.
    *   `css/`: Custom styles and Tailwind output.
    *   `js/`: Frontend logic (Leaflet for maps, ApexCharts for data).
    *   `images/`: Branding and UI assets.

### ⚙️ Management & Configuration
*   **`management/`**: Administrative tools for User Management, System Logs, and Settings.
*   **`obsidian_notes/`**: Project documentation and knowledge base.
*   **Root Files**:
    *   `index.php`: Main landing page (Public portal).
    *   `login.php` / `logout.php`: Session management.
    *   `tailwind.config.js`: CSS framework configuration.
    *   `package.json`: Frontend dependency management.

---
Professor: "What happens if your health center has 1,000 or 10,000 children? Won't your UI clutter or lag?"

Your Answer: "We implemented a virtual slicing pattern. The frontend caps active profile chips to 12 visible matches inside a max-height scrollable container while maintaining instant client-side search. Whether there are 10 children or 10,000 children, the DOM footprint remains identical with zero performance lag." 🚀
### 🌳 Visual Directory Tree
```text
.
├── api/                    # API Endpoints
├── app/                    # Application Logic (MVC)
│   ├── Controllers/        # Business Logic
│   └── Models/             # DB Logic
├── assets/                 # CSS, JS, Images
├── config/                 # Configurations
├── Core/                   # Base Framework Classes
├── includes/               # UI Partials & Masking
├── management/             # Admin Features
├── modules/                # Departmental Modules
│   ├── healthservices/     # Module 1
│   ├── immunization/       # Module 3
│   ├── sanitation/         # Module 2
│   ├── services/           # Module 4
│   └── surveillence/       # Module 5
├── obsidian_notes/         # Documentation Vault
├── pages/                  # Dashboard & Analytics
├── index.php               # Public Portal
├── login.php               # Entry
└── Supabase_Schema.sql     # DB Setup
```
