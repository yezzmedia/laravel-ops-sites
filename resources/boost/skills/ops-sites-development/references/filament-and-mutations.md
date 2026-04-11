# Filament And Mutation Rules

The sites Filament plugin currently owns:

- `OpsSitesPage`
- `SiteDetailsPage`

Approved mutation workflows currently include:

- create site
- edit site
- archive site
- refresh site posture

Keep writes centralized in `MutateSiteAction` and keep page code responsible only for form orchestration, authorization, and display concerns.
