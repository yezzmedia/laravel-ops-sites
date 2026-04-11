# Install And Doctor Rules

Declared install steps:

- `PublishOpsSitesMigrationsInstallStep`
- `EnsureOpsSitesStoreReadyInstallStep`
- `ConfigureOpsSitesAuditInstallStep`

Declared doctor checks:

- `SitesStoreReadyCheck`
- `PrimaryDomainAssignedCheck`
- `DnsTargetsResolvableCheck`
- `SiteAssignmentsConfiguredCheck`

Keep store readiness explicit and keep doctor checks diagnostic-only.
