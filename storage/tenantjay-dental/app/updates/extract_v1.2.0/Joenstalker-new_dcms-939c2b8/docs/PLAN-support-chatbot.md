# Project Plan: Support Chatbot System
**Filename**: `docs/PLAN-support-chatbot.md`

Implement a persistent, direct-support chat system for tenants with file/image upload capabilities and an administrative ticket management dashboard.

## Phase 0: Foundations & Database
- [ ] Extend `SupportTicket` model and ensure central connection.
- [ ] Create `support_messages` migration (Ticket ID, Sender ID, Sender Type, Content).
- [ ] Create `support_attachments` migration (Message ID, Path, Type, Size).
- [ ] **Agent**: `database-architect`

## Phase 1: Core Backend Implementation
- [ ] Implement `SupportTicket` and `SupportMessage` relationships.
- [ ] Create `TenantSupportController` for:
    - Creating tickets.
    - Sending messages.
    - Uploading attachments (Secure Local Storage).
- [ ] Update `Admin\SupportTicketController` to handle conversation listing and responses.
- [ ] **Agent**: `backend-specialist`

## Phase 2: Frontend Implementation (Tenant)
- [ ] Create `SupportChatBubble.vue` (Floating button, persistent across layouts).
- [ ] Implement chat window UI with:
    - Message list (polling for new messages).
    - File/Image picker.
    - Category selection (Bug, Feature Request, etc.).
- [ ] Global registration in `app.js` / layout.
- [ ] **Agent**: `frontend-specialist`

## Phase 3: Frontend Implementation (Admin)
- [ ] Create detailed Admin Ticket view in `Admin/Support/Dashboard.vue`.
- [ ] Implement conversation view for Admin replies.
- [ ] **Agent**: `frontend-specialist`

## Phase 4: Security & Verification
- [ ] Implement file upload validation (MIME, max size, virus scan emulation).
- [ ] Perform security scan for tenant isolation.
- [ ] Run automated feature tests for the chat flow.
- [ ] **Agents**: `security-auditor`, `test-engineer`

---

## Verification Checklist
- [ ] Tenant can open chat and see past conversations.
- [ ] File uploads are restricted to allowed types (vetted by `security-auditor`).
- [ ] Admin receives notification/sees updated ticket list.
- [ ] Real-time feel via robust polling or fetch cycle.
