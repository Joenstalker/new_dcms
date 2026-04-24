# Registration UI & Admin Payments Fixes Plan

## User Review Required
Before proceeding from Phase 1 to Phase 2 (Implementation), please clarify the following details to ensure I execute strictly according to your needs:

1. **"Tenant Custom branding was broken"**: 
   - *What exactly is failing?* Is the tenant unable to save their branding colors in `Settings > Custom Branding`, or is the branding successfully saving but failing to reflect on their portal/landing UI?

2. **"The Payment should popup in the admin correctly"**: 
   - In our last session, I added the horizontal "Actual Stripe Payments" table in the Admin Revenue dashboard. Do you want clicking a row in that table to open a **Detailed Receipt Modal popup** showing exactly what the clinic ordered? Or do you mean you want a real-time system notification (toast) popping up for the admin when an actual payment arrives?

3. **Global Modal Alignment**: 
   - I reviewed the `git diff` of your recent `registration UI 1 up 4` commits. I noticed the global `Modal.vue` container lost its native horizontal centering (`sm:mx-auto`). This may cause modals across the entire app to misalign. Should we restore the standard positioning?

## Proposed Changes (Pending Clarifications)

### Frontend Fixes
#### [MODIFY] resources/js/Components/Modal.vue
- Resolve global alignment/overflow issues caused by recent structural container changes.
- Ensure Dark mode compatibility is retained without forcing `bg-white` over everything.

#### [MODIFY] resources/js/Pages/Admin/Revenue/Index.vue
- Add a click handler on the `SystemEarning` payload table.
- Introduce a specialized "Payment Detail Popup" using the global `Modal.vue` component to show precise Stripe metadata for that specific payment.

#### [MODIFY] Tenant Branding Files (TBD)
- Await clarification on exactly how custom branding broke to apply accurate logic fixes.

## Verification Plan
- Build testing: `npm run build` to verify Vue components compile correctly.
- Security/Audit: Run `.agent/skills/frontend-design/scripts/ux_audit.py` to ensure Web Interface Guidelines are met.
