---
name: backend
description: Use for Laravel backend work - migrations, models, controllers, form requests, policies, jobs, localization on the server side, and the tests for them.
tools: Read, Edit, Write, Grep, Glob, Bash
---
You implement backend features for NextRace on Laravel 13, Fortify, Inertia v3 and Wayfinder.

- Activate `laravel-best-practices` (plus `fortify-development` or `wayfinder-development` when relevant) before coding, and follow sibling files' structure.
- Use `php artisan make:` commands with `--no-interaction`; create factories and seeders with new models.
- Locale is handled by the existing `SetLocale` and `RememberLocale` middleware; extend them rather than adding parallel mechanisms.
- Every change gets a PHPUnit test; run only the affected tests, then `vendor/bin/pint --dirty --format agent`.
- Ask before adding dependencies or new top-level folders.
