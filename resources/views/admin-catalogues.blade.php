@php($catalogueConfigured = filled(config('services.tmdb.token')) || filled(config('services.tmdb.api_key')))
<section id="uk-catalogues" class="mb-4 pb-3 border-bottom"
    data-start-url="{{ route('admin.catalogues.start') }}" data-step-url="{{ route('admin.catalogues.step') }}">
    <h2 class="h5">Update UK streaming catalogues</h2>
    <p>Check which movies in your database are available on each service, including free and ad-supported viewing. Rentals, purchases and extra Prime channels are excluded.</p>
    @unless($catalogueConfigured)
        <div class="alert alert-warning">
            Add your <a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener noreferrer">TMDB API Read Access Token</a>
            as <code>TMDB_API_TOKEN</code> in the server’s <code>.env</code> to enable these buttons.
        </div>
    @endunless
    <div class="d-flex flex-wrap">
        @foreach(config('catalogues.services') as $key => $service)
            <button type="button" class="btn btn-primary mr-2 mb-2" data-catalogue-update="{{ $key }}"
                data-label="{{ $service['label'] }}" data-configured="{{ $catalogueConfigured ? 'true' : 'false' }}"
                @disabled(!$catalogueConfigured)>
                <img src="{{ asset('images/'.$service['icon']) }}" width="18" height="18" class="mr-2" style="object-fit:contain" alt="" aria-hidden="true">
                <span data-catalogue-label>Update {{ $service['label'] }} UK</span>
            </button>
        @endforeach
    </div>
    <div id="catalogue-progress" class="mt-2" role="status" aria-live="polite" hidden>
        <p id="catalogue-progress-text" class="mb-1"></p>
        <div class="progress">
            <div id="catalogue-progress-bar" class="progress-bar bg-primary" role="progressbar"
                aria-label="UK catalogue update progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div>
        </div>
    </div>
    <details id="catalogue-skipped" class="mt-2" hidden>
        <summary id="catalogue-skipped-title"></summary>
        <ul id="catalogue-skipped-list"></ul>
    </details>
    <small class="text-muted d-block mt-2">UK streaming availability from <a href="https://www.justwatch.com/uk">JustWatch</a> via <a href="https://www.themoviedb.org">TMDB</a>.</small>
</section>
@push('scripts')
<script src="{{ asset('js/admin-catalogues.js') }}" defer></script>
@endpush
