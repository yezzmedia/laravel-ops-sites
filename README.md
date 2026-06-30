<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/yezzmedia/.github/main/profile/yezzmedia-dark.svg">
    <img src="https://raw.githubusercontent.com/yezzmedia/.github/main/profile/yezzmedia-light.svg" alt="Yezz Media" height="40">
  </picture>
</p>

<p align="center">
  <a href="https://packagist.org/packages/yezzmedia/laravel-ops-sites"><img src="https://img.shields.io/packagist/v/yezzmedia/laravel-ops-sites?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/yezzmedia/laravel-ops-sites"><img src="https://img.shields.io/packagist/php-v/yezzmedia/laravel-ops-sites?style=flat-square" alt="PHP Version"></a>
  <a href="https://packagist.org/packages/yezzmedia/laravel-ops-sites"><img src="https://img.shields.io/packagist/l/yezzmedia/laravel-ops-sites?style=flat-square" alt="License"></a>
</p>

---

# Laravel Ops &middot; Sites

`yezzmedia/laravel-ops-sites` provides site inventory, domain posture, and assignment visibility for the Yezz Media ops panel.

It manages site registration with domain posture monitoring, DNS resolution status, SSL assignment tracking, and infrastructure assignment visibility.

## Version

Current release: `0.2.0`

## Requirements

- PHP `^8.5`
- Laravel `^13.0` components
- `spatie/laravel-package-tools ^1.93`
- `yezzmedia/laravel-foundation ^0.2`
- `yezzmedia/laravel-ops ^0.2`

Optional:

- `yezzmedia/laravel-ops-infrastructure` — infrastructure assignment enrichment
- `yezzmedia/laravel-ops-security` — SSL assignment and edge posture visibility

## Installation

```bash
composer require yezzmedia/laravel-ops-sites
```

## What The Package Provides

### Site Inventory

`SiteRegistry` maintains the registered site collection with per-site domain posture, DNS status, SSL assignment state, and infrastructure assignment references.

### Domain Posture

Per-site domain posture includes:

- Primary domain resolution status
- DNS record verification
- SSL certificate assignment and expiry tracking

### Assignment Visibility

Sites can be linked to infrastructure targets and security posture entries for cross-package correlation in the ops panel.

### Doctor Checks

Foundation-aligned doctor checks verify:

- Primary domain configuration
- DNS resolvability
- Site configuration integrity

## Development

```bash
composer test
composer analyse
composer format
```

## License

MIT
