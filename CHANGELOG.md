# Changelog

## v0.1 - 2026-09-15

- Renamed the product from GTM Fit to GYM Fit across app branding, docs, environment defaults, package names, database defaults, session/cache keys, and local draft storage.
- Fixed backend Composer dependency constraints for Laravel 13 by using `laravel/sanctum` `^4.0` and `nunomaduro/collision` `^8.0`.
- Added the required Laravel `bootstrap/cache` directory placeholder so package discovery can write generated cache files.
- Generated backend dependency lock metadata after Composer resolution.
- Marked the frontend and backend package metadata as version `0.1.0`.
