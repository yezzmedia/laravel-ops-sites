---
name: ops-sites-development
description: "Build and maintain yezzmedia/laravel-ops-sites. Activate when changing site inventory, domain posture, DNS or SSL assignment visibility, site mutation workflows, sites install or doctor flows, sites Filament pages, audit integration, or package tests that depend on the approved sites V1 surface."
license: MIT
metadata:
  author: yezzmedia
---

# Ops Sites Development

## Documentation

Use `search-docs` for Laravel, Filament, Pest, Package Tools, and Boost details. Use the reference files in this skill for the approved sites runtime surface.

Use the `foundation-package-development` skill when descriptor capability choices or foundation registration behavior change.

## When To Use This Skill

Activate this skill when working inside `yezzmedia/laravel-ops-sites`, especially when changing:

- site inventory, domain posture, DNS posture, SSL assignment, or infrastructure assignment logic
- site creation, update, archive, or posture refresh flows
- sites install steps, doctor checks, or store setup
- sites Filament pages and detail workflows
- sites audit integration or package tests that prove the current sites surface

## Core Rules

- Keep inventory, posture, assignments, and mutations as explicit runtime concerns.
- Keep site writes in `MutateSiteAction` and posture projection in the resolver layer.
- Keep package-owned store readiness explicit through `OpsSitesStoreSetup`.
- Keep audit integration optional and package-config driven.
- Keep operator pages safe when the package store is unavailable.

## References

- Use [references/runtime-surface.md](references/runtime-surface.md) for the approved sites package surface.
- Use [references/install-and-doctor.md](references/install-and-doctor.md) for install-step and doctor-check boundaries.
- Use [references/filament-and-mutations.md](references/filament-and-mutations.md) for page, detail, and mutation workflow rules.
- Use [references/testing.md](references/testing.md) for verification expectations.
- Use [references/checklist.md](references/checklist.md) before finalizing sites changes.

## Common Pitfalls

- mixing site mutation rules into page classes instead of the mutation action
- hiding store-readiness requirements instead of degrading safely
- changing overview/detail pages without keeping ops-module declarations aligned
- coupling sites directly to ops as if it were a hard composer dependency
