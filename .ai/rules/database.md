---
paths:
  - 'database/**'
---

# Database

## Never run migrate:fresh on the dev database
NEVER run `php artisan migrate:fresh` (or `migrate:refresh`) against this dev database — it drops ALL tables including real `users`/`role_user` data and there is no backup (this happened once and wiped user accounts). To apply new migrations use `php artisan migrate`; to seed, run the specific seeder class (e.g. `db:seed --class=AccountingSeeder`) since seeders are idempotent via firstOrCreate. There is no automated DB backup on this machine.
