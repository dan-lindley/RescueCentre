# Lite Feature Matrix

| Feature | Full | Lite | Notes |
| --- | --- | --- | --- |
| Local config | Yes | Yes | Lite must use config files/env only, no hosted assumptions. |
| Login/users | Yes | Yes | Keep simple centre-local users and roles. |
| Centre profile | Yes | Yes | Include centre name, address, country/county, logo, colour. |
| Patient admission | Yes | Yes | Core workflow. |
| My Patients | Yes | Yes | Core operational view. |
| Care notes | Yes | Yes | Core record keeping. |
| Medication/treatments/feeding | Yes | Review | Include if schema is clean and not too coupled. |
| Patient archive | Yes | Yes | Keep basic filtering/export. |
| PDF documents | Yes | Optional | TCPDF dependency can be optional. |
| Messaging | Yes | No initially | Can be re-added later as optional component. |
| Networks/friends | Yes | No | Hosted/cross-centre feature. |
| Module store | Yes | No | Lite can have bundled optional modules only. |
| Admin tools | Yes | No | Remove private tools and maintenance utilities. |
| Public centre pages | Yes | Optional | Later phase. |
| Payments/SaaS | Yes | No | Full-only. |

## First Extraction Pass

1. Bring over config/bootstrap safely.
2. Bring over core CSS/theme assets.
3. Bring over auth/users with local install assumptions.
4. Bring over centre profile/setup.
5. Bring over admissions and patient views.
6. Add schema installer.
7. Audit for hardcoded hosted paths/domains.
