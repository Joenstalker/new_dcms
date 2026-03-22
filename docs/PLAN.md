# Orchestration Plan: Multi-Tenancy Security Audit

Analyze the Multi-Tenancy SaaS implementation for security vulnerabilities and data isolation leaks.

## Agents Involved
- `explorer-agent`: Discovery and mapping (Current)
- `project-planner`: Strategy and task breakdown
- `security-auditor`: Deep security analysis (Auth, XSS, Tenancy leaks)
- `database-architect`: SQL isolation and schema review
- `penetration-tester`: Active boundary testing simulation

## Proposed Discovery Phase (Phase 1)
1. **Model Scoping Audit**: Verify if all tenant-owned models correctly use scoping traits or are stored in isolated databases.
2. **Database User Isolation**: Review `CreateDatabaseUser` job to ensure tenants have restricted permissions to their own DB only.
3. **Middleware Chain Review**: Audit the order and logic of tenancy initialization vs. authentication.
4. **Shared Resource Verification**: Check Cache/S3/Redis scoping configuration.

## Proposed Analysis Phase (Phase 2 - After Approval)
- **Security Auditor**: Check for Cross-Tenant Scripting (XTS) or Direct Object Reference (IDOR) across subdomains.
- **Database Architect**: Verify that "Central" data (Plans, System Settings) is read-only for tenants.
- **Penetration Tester**: Simulate a malicious tenant attempting to access the central `users` table or another tenant's files.

## Verification Plan
1. **Automated Scans**: Run `security_scan.py`.
2. **Manual Review**: Validate that `tenancy()->initialize($tenant)` is correctly called in all entry points.
3. **Database Check**: Run `audit_users.php` to verify DB user permissions and existence.
