flowchart TD
    Start([Start]) --> A[Applicant submits new permit application]
    A --> B[permit_applications.php sends POST /api/permits.php]
    B --> C{PermitController validates input}
    C -->|Invalid| D[Show validation errors]
    D --> A
    C -->|Valid| E[Save permit with status: pending]
    E --> F[Permit is stored in permits table]
    F --> G[Staff reviews application]
    G --> H[Review via permit_applications.php PATCH /api/permits.php?action=review]
    H --> I{Is the application approved?}
    I -->|No - Rejected| J[Permit status becomes rejected; rejection reason recorded]
    J --> End([End])
    I -->|Yes - Approved| K[Permit status becomes under_review; inspector assigned]
    K --> L[Schedule inspection via inspections.php POST /api/inspections.php]
    L --> M[Inspection record saved in inspections table]
    M --> N[Inspector conducts inspection]
    N --> O[Inspection results submitted via inspections.php PATCH /api/inspections.php?action=conduct]
    O --> P{Did the inspection pass?}
    P -->|No - Follow-up needed| Q[Permit status updated; follow-up scheduled]
    Q --> N
    P -->|Yes - Compliant| R[Permit status updated to 'approved' or 'active']
    R --> S[Process payment via payments.php POST /api/payments.php]
    S --> T[Payment record saved in payments table; paid status updated in permits]
    T --> U{Payment successful?}
    U -->|No| V[Payment marked as failed; permit remains pending]
    V --> S
    U -->|Yes| W[Permit becomes active; expiry date set]
    W --> X[Upload required documents via documents.php POST /api/permit_documents.php]
    X --> Y[Document records saved in permit_documents table]
    Y --> Z[Permit is fully active and valid]
    Z --> AA[Permit records page permit_records.php shows active status]
    AA --> AB{Is renewal needed?}
    AB -->|Within grace period| AC[Apply for renewal via renewals.php POST /api/renewals.php]
    AC --> AD[Renewal record saved in renewals table]
    AD --> AE[Renewal updates expiry date and renewal count in permits]
    AE --> AF[Repeat inspection/payment cycle if required]
    AF --> K
    AB -->|No - Still valid| AG[Monitor until expiry]
    AG --> AA

    %% Alerts triggered from database
    F --> AH[DB triggers expiring alerts]
    AH --> AI[Expiring Permits Alert shown on permit_records.php and renewals.php]
    T --> AJ[Unpaid payments alert on payments.php]
    M --> AK[Pending inspections alert on inspections.php]

    %% End node
    AG --> End2([End])
    Z --> End2