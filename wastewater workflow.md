# Align Payments UI/UX with Wastewater Billing

This plan outlines the steps to overhaul the UI/UX of `modules/sanitation/payments.php` to match the modern, streamlined structure recently implemented in the Wastewater Billing module.

## User Review Required

> [!IMPORTANT]
> Please review the proposed layout changes below. If you approve of this structure, I will proceed with modifying the `payments.php` codebase.

## Open Questions

> [!NOTE]
> Currently, `payments.php` handles "Fee Structure" dynamically by fetching it via an API (`paymentApi.getFeeStructure()`). Do you want me to transition it to load the fee configuration directly via PHP on page load (just like we did for Wastewater Billing) to support the instant collapsible view, or keep the Javascript API fetching approach?

## Proposed Changes

### `modules/sanitation/payments.php`

#### 1. Collapsible Fee Structure Grid [MODIFY]
- **Current State:** A raw HTML table displaying categories, base fees, and inspection fees.
- **Proposed UX:** Replace the table with a sleek, collapsible "Fee Structure Reference" section (collapsed by default). Inside, fees will be displayed as a responsive grid of modern cards.
- **Access Control:** Embed "Add Fee" and "Manage Rates" buttons exclusively for users with Head or Admin roles (`$canManageFees`). 

#### 2. Unified Action Toolbar [MODIFY]
- **Current State:** Filters and the "Process Payment" button are scattered. Date filters use raw `<input type="date">` browser defaults.
- **Proposed UX:** Consolidate the search bar, status filter, payment method filter, date filter, and action buttons ("Process Payment", "Export") into a single, unified sleek toolbar.
- **Date Filter:** Implement the custom Date Range Modal (already used in Wastewater) instead of native browser date inputs for a more professional look.

#### 3. Modernized Table Design [MODIFY]
- Update the payment history table row hover effects, status badge designs, and typography to perfectly match the updated `wastewater_billing.php` tables.

## Verification Plan

### Manual Verification
1. I will ask you to open the **Sanitation -> Payments** page.
2. Verify the Fee Structure is now collapsed by default and displays as cards when opened.
3. Ensure the action toolbar consolidates all tools correctly.
4. Test the Date Filter modal to ensure it properly filters the payment history table.
