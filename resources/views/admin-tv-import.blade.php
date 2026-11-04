<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Update TV Shows</h2>
        <p>Copy the complete HTML from <a href="https://www.imdb.com/chart/toptv/" target="_blank" rel="noopener noreferrer">IMDb’s Top 250 TV shows</a>, then preview the list.</p>
        <label for="tv-import-html">IMDb TV page HTML</label>
        <textarea id="tv-import-html" class="form-control mb-2" rows="3"></textarea>
        <button id="tv-import-preview" class="btn btn-primary"><i class="fas fa-tv mr-2" aria-hidden="true"></i>Preview TV Shows</button>
        <div id="tv-import-result" class="mt-3" aria-live="polite"></div>
        <button id="tv-import-apply" class="btn btn-success mt-2" hidden>Apply Updates</button>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/admin-tv-import.js') }}?v={{ filemtime(public_path('js/admin-tv-import.js')) }}" defer></script>
@endpush
