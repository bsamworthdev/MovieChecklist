# UK streaming catalogue updates

The ten buttons under **Admin → Update UK streaming catalogues** update streaming availability for movies already in the database. They do not add an entire streaming service's films to the checklist.

## Setup

1. Get a TMDB API Read Access Token at https://www.themoviedb.org/settings/api.
2. Set `TMDB_API_TOKEN` in the server's `.env`. A v3 `TMDB_API_KEY` is also supported; the read token takes precedence. Never add either credential to Git.
3. Run `php artisan config:clear` if configuration was cached, then reload `/admin`.
4. Click the service's update button and keep the page open until it finishes. No database migration or queue worker is needed.

Availability is supplied by JustWatch through TMDB. Requests use IMDb IDs to resolve movies to TMDB and read **GB** subscription (`flatrate`) offers for paid services. BBC iPlayer, ITVX and Channel 4 also include `free` and `ads` offers. ITVX Premium and Channel 4 Plus are included with their parent service. Prime rentals, purchases and separate Prime channel subscriptions are excluded. Ad-supported subscription variants are included where the provider list names them. NOW checks the UK cinema subscription, not Sky Go. HBO Max checks its standalone UK subscription, excluding the separate Amazon channel. Run `php artisan migrate` when deploying the HBO Max table migration.

## Processing and failure handling

Updates run in browser-driven batches of ten movies. Results are staged in the server cache. Only after every batch succeeds does one database transaction update the chosen provider table. A failed request cannot clear the existing catalogue. The retry button resumes the same staged update; duplicate/retried batch requests do not apply changes twice. If the list of movie IDs/IMDb codes changes mid-update, restart the update.

Movies without a usable IMDb ID, or without exactly one TMDB movie match, are skipped and keep their existing availability. The completion report lists them. A successfully resolved movie with no matching GB offers is marked unavailable.

Existing provider rows are updated; newly checked movies get a row, and duplicate rows for those movies are collapsed so joins do not duplicate movie cards. The other providers' tables and movie metadata are untouched.

IMDb-to-TMDB mappings are cached for 30 days (unresolved IDs for one hour). UK subscription/free/ad-supported offers are cached for 15 minutes and shared across all ten buttons. The provider list is cached for an hour. Update runs expire after two hours.

`CATALOGUE_CACHE_STORE` defaults to `file`. On multiple web servers, choose a shared, lock-capable store such as `redis`. Sessions contain only the run token; the full movie list is never stored in the database session payload. The web server needs write access to Laravel's configured cache directory.

## API references

- https://developer.themoviedb.org/docs/authentication-application
- https://developer.themoviedb.org/reference/find-by-id
- https://developer.themoviedb.org/reference/watch-providers-movie-list
- https://developer.themoviedb.org/reference/movie-watch-providers

Credits appear on the movie pages, admin page and About page. `public/images/tmdb-logo.svg` is TMDB's unmodified official logo, downloaded from https://www.themoviedb.org/assets/v4/logos/v2/blue_short-8e7b30f73a4020692ccca9c88bafe5dcb6f8a62a4c6bc55cd9ba82bb2cd95f6c.svg.

## Daily live-server schedule

The application schedules `catalogues:update` for **00:00 Europe/London** every day, including GMT/BST changes. It runs all ten configured services sequentially, using the same staged/transactional updates as the admin buttons. It does not need a browser or queue worker. Each service is committed separately; a failed service is logged and the remaining services are still attempted. Any service failure makes the command exit nonzero.

After deploying these files:

1. Ensure the live `.env` has `TMDB_API_TOKEN` (or `TMDB_API_KEY`) and run `php artisan migrate --force` and `php artisan config:clear` from the live app directory.
2. Test with `php artisan catalogues:update`. This changes live availability and may take several minutes.
3. Run `command -v php` to find the server's PHP CLI binary, then `crontab -e` as the application user. Add one entry, substituting the actual app directory and PHP binary:

   ```cron
   * * * * * cd /path/to/MovieChecklist2 && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

   Laravel's scheduler is invoked every minute; the application runs the catalogue command only at midnight London time. Do not add a second entry if the server already runs `artisan schedule:run` for this app.
4. Confirm registration with `php artisan schedule:list` and review `storage/logs/catalogue-updates.log` after the next run. Per-provider summaries/errors are also written through Laravel's normal logger.

Both the scheduler and command prevent overlapping invocations for three hours; locks are released after completion. On multiple web servers, install this cron entry on one designated server, or use a shared lock-capable catalogue cache store for command locking. The cron user needs access to the live `.env`, database and writable Laravel storage/cache directories.

To rerun one provider after a failure:

```sh
php artisan catalogues:update --service=netflix
```

Valid keys are `prime`, `netflix`, `disney`, `now`, `hbo_max`, `apple_tv`, `paramount_plus`, `bbc_iplayer`, `itvx`, and `channel4`. Multiple `--service` options may be supplied. All services are selected when the option is omitted.
