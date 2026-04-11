# Approved V1 Ops Sites Surface

- permissions:
  - `ops.sites.view`
  - `ops.sites.manage`
- features:
  - `sites.inventory`
  - `sites.domain_posture`
  - `sites.ssl_assignment`
  - `sites.infrastructure_assignment`
- audit events:
  - `ops.sites.posture_refreshed`
  - `ops.sites.assignment_updated`
  - `ops.sites.domain_mapping_updated`
  - `ops.sites.created`
  - `ops.sites.updated`
  - `ops.sites.archived`
- ops modules:
  - `diagnostics.sites.overview`
  - `diagnostics.sites.detail`

Core public runtime types include:

- `OpsSitesPlatformPackage`
- `OpsSitesServiceProvider`
- `OpsSitesManager`
- `SiteInventoryResolver`
- `DomainPostureResolver`
- `DnsPostureResolver`
- `SslAssignmentResolver`
- `SiteInfrastructureAssignmentResolver`
- `RefreshSitesPostureAction`
- `MutateSiteAction`
