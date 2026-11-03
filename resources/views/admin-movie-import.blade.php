<dialog id="movie-import-dialog" class="movie-import-dialog"
    data-preview-url="{{ route('admin.movies.preview') }}"
    data-apply-url="{{ route('admin.movies.apply') }}" aria-labelledby="movie-import-title">
    <form id="movie-import-form">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 id="movie-import-title" class="h4 mb-0">Update movies</h2>
            <button type="button" id="movie-import-close" class="btn btn-light" aria-label="Close">&times;</button>
        </div>
        <p>Open <a href="https://www.imdb.com/chart/top/" target="_blank" rel="noopener noreferrer">IMDb’s Top 250</a>, copy the page HTML, and paste it below. Preview the movies before applying the update.</p>
        <div id="movie-import-error" class="alert alert-danger" role="alert" hidden></div>
        <div id="movie-import-status" class="alert alert-info" role="status" aria-live="polite" hidden></div>
        <details class="mb-3">
            <summary>How to copy the HTML</summary>
            <p>Wait for all 250 movies to load. Open your browser’s developer tools, select the page’s <code>&lt;html&gt;</code> element (or the complete movie list), then choose Copy → Copy outerHTML.</p>
        </details>
        <label for="movie-import-html">Complete HTML from IMDb’s Top 250 page</label>
        <textarea id="movie-import-html" class="form-control" rows="7" spellcheck="false" aria-describedby="movie-import-html-help"></textarea>
        <small id="movie-import-html-help" class="form-text text-muted">Paste the HTML, rather than the page URL or plain movie titles.</small>
        <button type="button" id="movie-import-preview" class="btn btn-primary mt-3"><i class="fas fa-search mr-2" aria-hidden="true"></i>Preview Movies</button>
        <section id="movie-import-review" class="mt-4" hidden>
            <p id="movie-import-count"></p>
            <div id="movie-import-missing" hidden>
                <h3 id="movie-import-missing-title" class="h5"></h3>
                <p>Enter the existing movie’s IMDb code (for example, tt6019206) to reuse its row. Leave the field blank to add it as a new movie. The current IMDb code will be saved on that row.</p>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Position</th><th>Movie</th><th>Existing movie’s IMDb code</th></tr></thead>
                        <tbody id="movie-import-rows"></tbody>
                    </table>
                </div>
            </div>
            <p>Applying this list replaces the previous Top 250 order. Movies leaving the list remain in your database.</p>
            <button type="submit" id="movie-import-apply" class="btn btn-primary"><i class="fas fa-check mr-2" aria-hidden="true"></i>Apply Updates</button>
        </section>
    </form>
</dialog>
@push('styles')
<style>
.movie-import-dialog { width: min(920px, 95vw); max-height: 90vh; overflow-y: auto; border: 0; border-radius: 8px; padding: 24px; }
.movie-import-dialog::backdrop { background: rgba(0,0,0,.55); }
.movie-import-dialog [hidden] { display: none !important; }
.movie-import-dialog input { min-width: 180px; }
.movie-import-notification { position: fixed; top: 80px; right: 16px; width: min(480px, calc(100vw - 32px)); z-index: 1080; box-shadow: 0 4px 16px rgba(0,0,0,.2); white-space: pre-line; }
.movie-import-notification[hidden] { display: none !important; }
</style>
@endpush
@push('scripts')
<div id="movie-import-notification" class="movie-import-notification alert alert-info" role="status" aria-live="polite" hidden>
    <button type="button" id="movie-import-dismiss" class="close ml-3" aria-label="Dismiss notification">&times;</button>
    <span id="movie-import-notification-text"></span>
</div>
<script src="{{ asset('js/admin-movie-import.js') }}" defer></script>
@endpush
