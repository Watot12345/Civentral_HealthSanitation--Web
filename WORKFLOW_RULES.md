# WORKFLOW RULES

Rule 1

Patient must never skip Registration.

Rule 2

Assessment cannot happen without Check-in.

Rule 3

Consultation cannot start until Assessment is complete.

Rule 4

Prescription cannot exist without Consultation.

Rule 5

Referral cannot exist without Consultation.

Rule 6

Medical Record is automatically updated after Consultation.

Rule 7

Appointments are OPTIONAL.

Walk-in patients are always accepted.

Rule 8

The system must support only Primary Healthcare.



Patient Management

OUTPUT

patient_id

↓

Appointments

INPUT

patient_id

OUTPUT

appointment_id

↓

Patient Check-in

INPUT

patient_id

appointment_id (optional)

OUTPUT

assessment_id

↓

Consultation

INPUT

patient_id

assessment_id

OUTPUT

consultation_id

↓

Prescription

INPUT

consultation_id

↓

Medical Record

INPUT

consultation_id

prescription_id

referral_id


# AI FEATURES

AI must NEVER diagnose patients.

AI must NEVER replace doctors.

AI only assists.

---------------------------------

Patient Management

AI

Duplicate Patient Detection

---------------------------------

Appointments

AI

Appointment Trend Analysis

Peak Hour Prediction

---------------------------------

Assessment

AI

Highlight abnormal vital signs

Only notify staff.

Never diagnose.

---------------------------------

Consultation

AI

Generate consultation summary

Suggest possible ICD code

Suggest follow-up reminders

Doctor has final decision.

---------------------------------

Medical Records

AI

Summarize patient history

Generate patient timeline

---------------------------------

Dashboard

AI

Generate monthly reports

Generate consultation trends

Generate disease trends

Generate analytics


# OPERATIONAL GOVERNANCE RULES

Rule 9: Data Permanence (No Deletion)

Medical records and patient histories are permanent. Deletion is prohibited so historical records remain available for future visits.

Rule 10: 2x Audio Voice Calling

Patient queue announcements speak clearly and repeat 2 times for waiting room audibility.

Rule 11: Queue Status Toggling

Staff can call, re-announce, mark as "Skipped / No Answer", or re-queue patients flexibly.
