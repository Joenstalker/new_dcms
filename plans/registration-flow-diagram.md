# Registration Flow - Complete Flowchart

## From New Client Registration to Approval or Rejection

```mermaid
flowchart TB
    %% Styles
    classDef start fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    classDef process fill:#fff3e0,stroke:#e65100,stroke-width:2px
    classDef decision fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    classDef database fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef email fill:#fce4ec,stroke:#c2185b,stroke-width:2px
    classDef end fill:#ffebee,stroke:#c62828,stroke-width:2px

    %% Step 1-4: Registration
    START([User starts registration]) ::: start --> A2
    A2[Fill clinic details<br/>name, email, password] ::: process --> A3
    A3[Select subscription plan<br/>monthly or yearly] ::: process --> A4
    A4[Proceed to Stripe payment] ::: process --> B1

    %% Payment
    B1[Enter payment in Stripe] ::: process --> B2{Payment<br/>Successful?}
    B2 -->|No| B3[Show error/retry]
    B2 -->|Yes| C1[Stripe redirects to<br/>success URL]:::process

    %% Backend Processing
    C1 --> D1[handleSuccess called]:::process
    D1 --> D2[Verify Stripe payment<br/>status = paid]:::process
    D2 --> D3[Find PendingRegistration]:::process
    D3 --> D4[Create Tenant<br/>status: pending]:::database
    D4 --> D5[Create Domain<br/>subdomain.dcms.com]:::database
    D5 --> D6[Initialize tenancy<br/>create tenant DB]:::process
    D6 --> D7[Create admin user<br/>in tenant DB]:::process
    D7 --> D8[Create Subscription<br/>stripe_status: active]:::database
    D8 --> D9[Keep PendingRegistration<br/>status: pending<br/>expires_at: now + 7 days]:::database

    %% Notifications
    D9 --> E1[Notify admins]:::email
    E1 --> E2[Email client<br/>payment received + modal]:::email
    E2 --> E3[Show payment-received<br/>modal with countdown]:::process

    %% Client Side
    E3 --> F1[Client sees modal<br/>with countdown timer]:::process
    F1 --> F2{Can client<br/>access?}
    F2 -->|No - pending| F4[Show pending.blade.php<br/>countdown timer]
    F2 -->|Yes - active| F5[Allow login]
    F2 -->|Suspended| F6[Show suspended message]

    %% Admin Side
    F4 -.-> G1[Admin logs in<br/>Admin Portal]:::process
    G1 --> G2[Go to Pending<br/>Registrations]:::process
    G2 --> G3[View list of pending<br/>registrations]:::process
    G3 --> G4{Admin Action?}

    %% Admin Actions
    G4 -->|Extend Time| H1[Click Extend]:::process
    H1 --> H2[Enter hours]:::process
    H2 --> H3[Update expires_at]:::database
    H3 --> G3

    G4 -->|Toggle Reminder| I1[Enable/Disable<br/>reminder emails]:::process
    I1 --> G3

    G4 -->|Toggle Auto-Approve| J1[Enable/Disable<br/>auto-approve]:::process
    J1 --> G3

    %% Approval Path
    G4 -->|Approve| K1[Update Tenant<br/>status: pending -> active]:::database
    K1 --> K2[Update PendingRegistration<br/>status: pending -> approved<br/>approved_at: now]:::database
    K2 --> K3[Email client<br/>approved]:::email
    K3 --> K4[✓ Client can login]:::end

    %% Rejection Path
    G4 -->|Reject| L1[Click Reject]:::process
    L1 --> L2[Enter rejection reason]:::process
    L2 --> L3{Payment<br/>exists?}
    L3 -->|Yes| L4[Process refund<br/>via Stripe]:::process
    L3 -->|No| L5[Skip refund]
    L4 --> L6[Update PendingRegistration<br/>status: refunded]:::database
    L5 --> L7[Update PendingRegistration<br/>status: rejected]:::database
    L6 --> L8[Email client<br/>rejected + refund]:::email
    L7 --> L8
    L8 --> L9[✗ Show rejection message]:::end

    %% Auto-Approve Path
    M1[Scheduled cron job<br/>process-expired]:::process --> M2{Auto-Approve<br/>Enabled?}
    M2 -->|No| M3[Skip - wait for<br/>manual approval]
    M2 -->|Yes| M4{Expired?}
    M4 -->|No| M3
    M4 -->|Yes| M5[Auto-approve tenant<br/>status: active]:::database
    M5 --> M6[Update PendingRegistration<br/>status: approved]:::database
    M6 --> M7[Email client<br/>approved]:::email
    M7 --> M8[✓ Client can login]:::end

    %% Auto-Expire Path
    N1[Registration expires<br/>no admin action]:::process --> N2{Auto-Approve<br/>Enabled?}
    N2 -->|Yes| M5
    N2 -->|No| N3[Process refund<br/>if payment exists]:::process
    N3 --> N4[Update status<br/>to refunded]:::database
    N4 --> N5[Email client<br/>refund processed]:::email

    %% Connecting lines
    F4 -.-> G1
```

---

## Summary Table

| What Happens | Tenant Status | PendingRegistration Status | Client Access |
|-------------|--------------|--------------------------|---------------|
| After Payment | pending | pending | ❌ No - sees countdown |
| Admin Approves | active | approved | ✅ Yes - can login |
| Admin Rejects | (deleted) | rejected | ❌ No |
| Auto-Approve | active | approved | ✅ Yes - can login |
| Expired (no auto-approve) | (deleted) | refunded | ❌ No |

---

## Database Records Created

### After Successful Payment:
- **Tenant** → `status: 'pending'`
- **Domain** → `domain: 'subdomain.dcms.com'`
- **Subscription** → `stripe_status: 'active'`
- **PendingRegistration** → `status: 'pending'`, `expires_at: now + 7 days`

### After Admin Approves:
- **Tenant** → `status: 'active'`
- **PendingRegistration** → `status: 'approved'`, `approved_at: now`

### After Admin Rejects:
- **PendingRegistration** → `status: 'rejected'` or `'refunded'`
- **Refund** → processed via Stripe (if payment existed)
