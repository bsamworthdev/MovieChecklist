# TV Shows

The header switch opens `/tv-series` for guests and account holders. TV shows use the `tv_shows` catalogue and the existing watched/favourite/watchlist tables, scoped by `movie_or_tv`. Watched status applies to the whole series. Guests save progress to their browser session, while logged-in users save it to their account. Guest TV history is transferred with movie history when registering an account.

## Deploy and populate

Deploy the source and generated `public/js/app.js` and `public/mix-manifest.json`, then run:

```sh
php artisan migrate --force
php artisan optimize:clear
```

On Admin, find **Update TV Shows**. Open https://www.imdb.com/chart/toptv/, copy the complete page HTML, paste it, and click **Preview TV Shows**. Check the counts and first ten titles, then **Apply Updates**. Preview expires after 30 minutes. Import requires exactly 250 unique IMDb codes with consecutive positions.

Imports match by IMDb ID, update chart positions and available ratings, and fill missing artwork. Existing artwork and progress are retained. Series removed from the chart retain their records and progress with no chart position. TV shows keep original artwork in `image_url` and generated thumbnails in `image_url_small`. The TV Admin **Generate missing thumbnails** button creates 229×287 images on the public storage disk, using the same generator as movies. Existing thumbnails are preserved. TV cards prefer the thumbnail and fall back to the original artwork until it is generated. Public storage must be linked with `php artisan storage:link`.

TV Shows supports a watched summary for the current filters, search, combined favourites and unwatched filters, decade filtering, and a separate watchlist popup with a global watchlist count. English Only uses the original language from TMDB and remains visible. TV streaming updates also refresh language and genres. Popular Only requires over 250,000 IMDb votes, imported from the IMDb chart (compact K/M counts are rounded as displayed by IMDb). Re-import the TV HTML after deploying the vote-count migration to populate these counts on live. All ten UK streaming services are supported, with OR matching across selected services and no streaming restriction when none are selected. Admins and editors can edit TV availability through the card icons. Movie friend statistics and trophies still apply only to movies.

## Verification

```sh
vendor/bin/phpunit tests/Feature/TvSeriesTest.php tests/Feature/AdminMovieImportTest.php tests/Feature/AdminPageTest.php
node tests/js/tv-series.cjs
node tests/js/vue-bundle.cjs
node tests/js/random-movie.cjs
```

## Streaming availability and Admin context

Run all pending migrations before serving the updated app. Each shared streaming table now has `movie_or_tv` (`movie` or `tv`); `movie_id` points to the corresponding catalogue. Existing rows default to movies. Newer service tables enforce uniqueness on the catalogue and identifier together. Legacy service tables retain their existing rows and gain a composite lookup index.

The header switch stays on Admin when clicked there. The chosen catalogue is remembered in the session. Movies Admin displays movie import, thumbnail generation, movie catalogue buttons and movie user statistics. TV Shows Admin displays TV import, TV counts and TV catalogue buttons. API update runs retain their own catalogue type throughout the run.

`php artisan catalogues:update` now updates both catalogues, including in the existing midnight schedule. Use `--media=movie` or `--media=tv` for one catalogue, and `--service=netflix` (repeatable) to restrict providers. TV lookups and offers have separate caches from movies. NOW TV uses entertainment providers for TV shows and cinema providers for movies.

## Shared watched history

The rename migration preserves the TV catalogue as `tv_shows`. The following migration copies the old `tv_series_progress` rows into `movie_user`, `movie_user_non_auth`, `watch_list`, and `watch_list_non_auth`, then drops `tv_series_progress`. `movie_or_tv` keeps movie and TV identifiers separate. Watched state is a single boolean for the entire show; there is no season or episode tracking. Existing movie rows retain their movie type and watched status. Favourites remain available even if a TV show is subsequently marked unwatched.

Deploy the code and run `php artisan migrate --force`. Historical creation migrations retain their original names so existing installations can migrate safely; the final schema contains `tv_shows` and no TV progress table.

TV thumbnail generation runs in batches of three per HTTP request to avoid gateway timeouts. Saved TV paths are `/storage/tv-shows/{imdb_id}.jpg`, and the catalogue API exposes their public-disk URL as `image_url_small_extended`. On deployment run migrations and `php artisan storage:link`, then use the thumbnail button in TV Admin. Thumbnail files are generated on that server; they are not committed to Git.

Guests can use **Save my score!** to register and preserve their TV watched history, favourites and watchlist. **Pick me a random TV show!** is available to guests and members. It picks from all unwatched shows matching the active filters, across every page, and offers watchlist and watched controls. An empty result explains that no unwatched shows match.
