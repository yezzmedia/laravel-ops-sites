# Changelog

All notable changes to `yezzmedia/laravel-ops-sites` will be documented in this file.

## [Unreleased]

## [0.2.0] - 2026-06-30

### Changed

- Bumped minimum `yezzmedia/laravel-foundation` dependency to `^0.2`

## [0.1.1] - 2026-04-13

### Fixed

- published the host `config/ops-sites.php` file automatically inside the audit install flow when it was missing
- kept ops sites audit configuration working for Basecamp and other audit-only installs that do not run ordinary publish steps first

## [0.1.0] - 2026-04-09

### Added

- initial `laravel-ops-sites` package scaffold with package tools bootstrap and foundation registration
- package config and migrations for site inventory, domains, and assignments
- site posture enums, DTOs, models, resolvers, and cache-backed manager
- install steps and doctor checks for store readiness, assignment posture, and visibility workflows
- Filament plugin with overview and detail pages for ops-facing site inventory and domain posture visibility
- refresh action, CRUD mutations, and normalized audit event handling for site lifecycle changes
- package testbench support, feature tests, and shared `1-dev-test` bootstrap integration
- conditional ops panel integration in `yezzmedia/laravel-ops`
