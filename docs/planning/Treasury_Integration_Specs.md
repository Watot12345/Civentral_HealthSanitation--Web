# Treasury & Central Payments Integration Specification

**Affected Modules:** Sanitation Permits, Wastewater & Septic Management
**Integration Partner:** LGU Central Treasury (Tier 1 Core Integration)
**Integration Method:** RESTful API (HTTP POST) & Asynchronous Webhook

---

## 1. The Request (What we SEND to the Treasury)

When a citizen needs to pay for a Sanitation Permit or Septic Desludging fee, our system **does not** collect the cash. Instead, we generate an invoice and push the billing details to the Treasury.

**API Endpoint Called:** `POST /api/v1/treasury_bills.php`

### Data Sent (Input / "Put" Payload)
Our system sends a JSON payload containing the exact billing details:

| Parameter Name | Data Type | Example Value | Description |
| :--- | :--- | :--- | :--- |
| **`service_module`** | String | `SANITATION_PERMITS` | Identifies which department/module is requesting the payment. |
| **`invoice_number`** | String | `INV-2026-00412` | Our system's uniquely generated billing reference. |
| **`amount`** | Decimal | `1500.00` | The total fee to be collected in PHP. |
| **`payer_name`** | String | `Mateo Reyes` | The name of the citizen or business paying. |
| **`callback_url`** | String | `https://health.lgu.gov.ph/api/treasury-webhook.php` | The URL the Treasury will ping once the payment is completed. |

*After this request is sent, our system puts the permit/request status on **"PENDING PAYMENT"** hold.*

---

## 2. The Webhook (What we GET from the Treasury)

Because payments aren't always instant (e.g., the citizen might walk to City Hall to pay tomorrow), our system waits for the Treasury to notify us. When the citizen successfully pays (via GCash or Over-The-Counter Cashier), the Treasury server sends an HTTP POST back to our system.

**Our Endpoint Receiving Data:** `POST /api/treasury-webhook.php`

### Data Received (Output / "Get" Payload)
The Treasury sends us this JSON payload to confirm the transaction:

| Data Field Received | Example Value | How our System Handles It |
| :--- | :--- | :--- |
| **`event`** | `payment.succeeded` | Confirms this is a successful payment, not a cancellation. |
| **`invoice_number`** | `INV-2026-00412` | Used to look up the exact pending permit/request in our database. |
| **`official_receipt_no`** | `OR-2026-990142` | Saved to our database as proof of payment for auditing. |
| **`amount_paid`** | `1500.00` | Verified against our original billed amount. |
| **`payment_channel`** | `GCASH` or `OTC` | Logged for reporting purposes. |
| **`paid_at`** | `2026-08-28T23:15:00+08:00` | The official timestamp of the transaction. |

---

## 3. Automation Logic (What our system does after)

Once we successfully receive and validate the payload from the Treasury webhook, our system completely automates the next steps:

1. System receives `invoice_number` from the webhook.
2. System queries database: `SELECT * FROM sanitation_permits WHERE invoice_number = 'INV-2026-00412'`
3. System updates the status in the database: `UPDATE sanitation_permits SET status = 'PAID', or_number = 'OR-2026-990142' WHERE ...`
4. **Final Action:** The Sanitation Permit is automatically generated and becomes available for the citizen to print/download, with zero manual intervention required from the Health Department staff.


Yes, you have the exact right idea!

To put it simply: The Treasury acts as the Payment Gatekeeper for your system.

Your Health & Sanitation system is in charge of the medical and inspection rules, but it cannot release the final permit until the Treasury says "Yes, we received the money."

If your professor asks you to explain the Treasury's role in your system, here is how you can explain their specific purpose:

1. They are the "Cashier", You are the "Manager"
Think of it like a restaurant.

Your Health & Sanitation module is the kitchen (you prepare the permit, you do the inspections, you approve the safety).
The Treasury is the cashier at the front.
You won't hand the customer the food (the permit) until the cashier (Treasury) sends a signal to the kitchen saying "The customer has paid."
2. Authorization to Release
You are perfectly correct when you say it is for authorization before release. Your system puts the Sanitation Permit on a "Locked" or "Pending Payment" status. The only way that permit becomes unlocked and ready to print is when the Treasury integration sends that Webhook confirming the Official Receipt (OR) number.

3. Fraud Prevention
Without this integration, a citizen could easily lie to the Health Inspector and say "I already paid City Hall, just give me my permit." By integrating directly with the Treasury API, your system verifies the payment automatically from machine to machine, making it impossible for citizens to use fake receipts to get their sanitation permits.

So yes, your understanding is 100% correct: The primary purpose of integrating with Treasury is to get a secure, automated "Authorization to Release" signal based on financial payment.