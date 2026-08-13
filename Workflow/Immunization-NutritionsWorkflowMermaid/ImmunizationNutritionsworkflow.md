flowchart TD
    Start([User starts action]) --> A[Fill form / click button]

    A --> B{Client-side validation}
    B -->|Invalid| C[Show error message]
    C --> A
    B -->|Valid| D[Send request to API]

    D --> E{Route to correct endpoint}
    E -->|Child Records| F1["/api/immunization.php"]
    E -->|Growth Charts| F2["/api/growth.php"]
    E -->|Nutrition Assessment| F3["/api/nutrition.php"]
    E -->|Vaccination Tracking| F4["/api/immunization.php?action=record"]
    E -->|Vaccine Inventory| F5["/api/inventory.php"]

    F1 --> G1[ChildController]
    F2 --> G2[GrowthController]
    F3 --> G3[NutritionController]
    F4 --> G4[ImmunizationController]
    F5 --> G5[InventoryController]

    G1 --> H1[Child Model]
    G2 --> H2[GrowthMeasurement Model]
    G3 --> H3[NutritionAssessment Model]
    G4 --> H4[Immunization Model]
    G5 --> H5[VaccineInventory Model]

    H1 --> I1[(children)]
    H2 --> I2[(growth_measurements)]
    H3 --> I3[(nutrition_assessments)]
    H4 --> I4[(immunizations)]
    H5 --> I5[(vaccine_inventory)]

    I1 --> J1{Save successful?}
    I2 --> J2{Save successful?}
    I3 --> J3{Save successful?}
    I4 --> J4{Save successful?}
    I5 --> J5{Save successful?}

    J1 -->|Yes| K[Return success response]
    J2 -->|Yes| K
    J3 -->|Yes| K
    J4 -->|Yes| K
    J5 -->|Yes| K

    J1 -->|No| L[Return error response]
    J2 -->|No| L
    J3 -->|No| L
    J4 -->|No| L
    J5 -->|No| L

    K --> M[Update UI]
    L --> N[Show error toast]

    M --> O{Trigger alerts?}
    O -->|Critical nutrition / missed vaccine / low stock| P[Display alert banners]
    O -->|No alert| Q[End]

    P --> Q
    N --> Q
    Q([End])

    %% Cross-module sync (special case)
    H3 -.->|syncChildNutritionStatus| I1

    %% Styling
    classDef startEnd fill:#f9f,stroke:#333,stroke-width:2px;
    classDef process fill:#e6f3ff,stroke:#333;
    classDef decision fill:#fff3cd,stroke:#333;
    classDef api fill:#d4edda,stroke:#333;
    classDef db fill:#f8d7da,stroke:#333;

    class Start,Q startEnd;
    class A,C,D,K,M,N,P process;
    class B,E,J1,J2,J3,J4,J5,O decision;
    class F1,F2,F3,F4,F5 api;
    class I1,I2,I3,I4,I5 db;