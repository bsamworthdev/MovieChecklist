<template>
    <div class="card tv-catalogue" :class="{'tv-catalogue-guest': !authenticated}">
        <div class="card-header" :class="{'pb-0 pl-0 pr-0': authenticated}">
            <div class="container"><div class="row"><div class="col-12">
                <form class="tv-filter-form" @submit.prevent="load(1)">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-3 col-12"><h1 class="tv-title">TV Shows</h1></div>
                            <div class="col-lg-3 col-12"><label class="sr-only" for="tv-search">Search TV shows</label><input id="tv-search" v-model="search" class="form-control" type="search" placeholder="Search"></div>
                            <div class="col-lg-3 col-12"><label class="sr-only" for="tv-decade">Time period</label><select id="tv-decade" v-model="decade" class="form-control" @change="load(1)"><option value="">All Years</option><option v-for="value in availableFilters.decades" :key="value" :value="value">{{ value }}s</option></select></div>
                            <div class="col-lg-3 col-12"><label class="sr-only" for="tv-genre">Genre</label><select id="tv-genre" v-model="genre" class="form-control" @change="load(1)"><option value="">All Genres</option><option v-for="value in availableFilters.genres" :key="value" :value="value">{{ value }}</option></select></div>
                        </div>
                        <div v-if="!filtersShown" class="row mt-1 mb-0"><div class="col-12"><button type="button" class="btn btn-light btn-block pt-0 pb-0" aria-controls="tv-extra-filters" :aria-expanded="filtersShown" @click="setFiltersShown(true)"><i class="fa fa-chevron-down" aria-hidden="true"></i> <small>More Filters</small></button></div></div>
                        <div v-show="filtersShown" id="tv-extra-filters" class="row mt-3">
                            <div class="col-lg-3 col-12">
                                <div class="card tv-stream-card">
                                    <div class="tv-stream-grid">
                                        <label v-for="service in services" :key="service.key" class="tv-stream-option"><img :src="service.icon" :alt="service.label" :title="service.label"><input v-model="selectedServices" type="checkbox" :value="service.key" :aria-label="service.label" @change="load(1)"></label>
                                    </div>
                                    <div class="tv-stream-links d-flex justify-content-center align-items-center mt-1"><button type="button" class="btn btn-link p-0" @click="selectServices(true)">Select all</button><span class="mx-1 text-muted" aria-hidden="true">|</span><button type="button" class="btn btn-link p-0" @click="selectServices(false)">Unselect all</button></div>
                                </div>
                            </div>
                            <div v-if="!authenticated" class="col-lg-1 col-12"></div>
                            <div class="col-lg-2 col-12"><label class="tv-vert-align"><span class="tv-nowrap"><i class="fa fa-heart tv-filter-heart" aria-hidden="true"></i> Favourites <input id="favourites_only_checkbox" v-model="favourites" class="form-input" type="checkbox" @change="load(1)"></span></label></div>
                            <div class="col-lg-2 col-12"><label class="tv-vert-align"><span class="tv-nowrap">English Only <input id="english_only_checkbox" v-model="english" class="form-input" type="checkbox" @change="load(1)"></span></label></div>
                            <div class="col-lg-2 col-12"><label class="tv-vert-align"><span class="tv-nowrap">Popular Only <i class="fa fa-info-circle" title="Popular TV shows have over 250k votes on IMDb." aria-hidden="true"></i> <input id="popular_only_checkbox" v-model="popular" class="form-input" type="checkbox" @change="load(1)"></span></label></div>
                            <div class="col-12" :class="authenticated ? 'col-lg-3' : 'col-lg-2'"><label class="tv-vert-align"><span class="tv-nowrap">{{ authenticated ? 'Unwatched By Me' : 'Unwatched Only' }} <input id="unwatched_only_checkbox" v-model="unwatched" class="form-input" type="checkbox" @change="load(1)"></span></label></div>
                        </div>
                        <div v-if="filtersShown" class="row mt-0 mb-0"><div class="col-12"><button type="button" class="btn btn-light btn-block pt-0 pb-0" aria-controls="tv-extra-filters" :aria-expanded="filtersShown" aria-label="Hide filters" @click="setFiltersShown(false)"><i class="fa fa-chevron-up" aria-hidden="true"></i></button></div></div>
                    </div>
                </form>
            </div></div></div>
        </div>
        <div class="card-body"><div class="container">
            <div class="row justify-content-left tv-summary">
                <div class="col-sm-12"><h4 :class="{'d-inline': !authenticated}" aria-live="polite">You have watched <span class="watchedMovies">{{ watchedShowsCount }}</span> of <b>{{ items.length }}</b> TV shows.</h4> <div v-if="!authenticated" class="btn-group"><a href="/register" class="btn btn-success btn-lg">Save my score!</a></div></div>
                <div v-if="summary.watchlist > 0" class="col-sm-12"><h5 class="d-inline">You have <span class="watchListMovies">{{ summary.watchlist }}</span> {{ summary.watchlist === 1 ? 'TV show' : 'TV shows' }} in your Watch List.</h5> <div class="btn-group"><button type="button" class="btn btn-primary" @click="openWatchlist">Watch List</button></div></div>
                <div class="btn-group col-12"><button type="button" class="btn btn-success" :disabled="randomLoading" @click="pickShow">Pick me a random TV show!</button></div>
            </div>
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }} <button class="btn btn-link p-0" @click="refresh">Retry</button></div>
        <div v-if="!loading && !items.length && !error" class="alert alert-light">{{ search || status !== 'all' || genre || decade || favourites || unwatched || english || popular || selectedServices.length ? 'No TV shows match your filters.' : 'The TV catalogue is empty. An admin can import IMDb’s Top 250 TV shows from the Admin page.' }}</div>
        <div class="row justify-content-center">
            <div v-for="(series, index) in items" :key="series.id" class="scratchCardContainer col-lg-3 col-md-4 col-sm-6 col-xs-12">
                <article class="card movieCard" :class="{watched: Number(series.watched)}" :aria-busy="!!saving[series.id]">
                    <div class="card-body movieImage" :style="{backgroundImage: posterUrl(series) ? `url(${posterUrl(series)})` : 'none', backgroundColor: '#252525'}"
                         role="button" tabindex="0" :aria-label="(Number(series.watched) ? 'Mark unwatched: ' : 'Mark watched: ') + series.name" :aria-pressed="!!Number(series.watched)"
                         @click="save(series, 'watched')" @keydown.enter.self.prevent="save(series, 'watched')" @keydown.space.self.prevent="save(series, 'watched')">
                        <img v-if="posterUrl(series)" :src="posterUrl(series)" class="sr-only" alt="" aria-hidden="true" @error="posterFailed(series)">
                        <h4>{{ series.name }} ({{ series.year }})</h4>
                        <div class="rank" :class="{rounded: index + 1 === Number(series.top250_rank)}">
                            <span class="filtered_rank">{{ index + 1 }}</span>
                            <span v-if="index + 1 !== Number(series.top250_rank)" class="actual_rank">({{ series.top250_rank || '–' }})</span>
                        </div>
                        <button v-if="Number(series.watched)" type="button" class="favourite tv-overlay-button" :class="{selected: Number(series.favourite)}"
                                :disabled="!!saving[series.id]" :aria-pressed="!!Number(series.favourite)" aria-label="Favourite" @click.stop="save(series, 'favourite')">
                            <i class="fa fa-heart heart filled" aria-hidden="true"></i>
                            <i class="far fa-heart heart outline" aria-hidden="true"></i>
                        </button>
                        <button v-else type="button" class="watchList tv-overlay-button" :class="{selected: Number(series.watchlist)}"
                                :disabled="!!saving[series.id]" :aria-pressed="!!Number(series.watchlist)" aria-label="Watchlist" @click.stop="save(series, 'watchlist')">
                            <i class="fas fa-list-alt watchListIcon" aria-hidden="true"></i>
                            <i class="fas fa-check-circle watchListTick" aria-hidden="true"></i>
                            <i class="fas fa-plus-circle watchListAdd" aria-hidden="true"></i>
                        </button>
                        <div v-if="series.rating" class="rating"><i class="fa fa-star star" aria-hidden="true"></i><span>{{ Number(series.rating).toFixed(1) }}</span></div>
                        <div class="platforms tv-platforms" :class="{'tv-platforms-viewer': !canEditStreams}">
                            <button v-for="service in services.filter(service => canEditStreams || Number(series[service.column]))" :key="service.key" type="button" class="platform tv-platform-button" :class="[service.table, {dimmed: !Number(series[service.column])}]"
                                :title="(Number(series[service.column]) ? 'On ' : 'Not on ') + service.label" :aria-label="(Number(series[service.column]) ? 'On ' : 'Not on ') + service.label" :disabled="!canEditStreams || !!saving[series.id]" @click.stop="toggleStreaming(series, service)"></button>
                        </div>
                        <div class="tickContainer"><i v-if="Number(series.watched)" class="fa fa-check tick" aria-hidden="true"></i></div>
                    </div>
                    <a :href="'https://www.imdb.com/title/' + series.imdb_id + '/'" target="_blank" rel="noopener noreferrer" class="footer">View on IMDb <i class="externalLink fas fa-external-link-alt" aria-hidden="true"></i></a>
                </article>
            </div>
        </div>
        <div v-if="randomOpen" class="tv-modal-backdrop" @click.self="closeRandom" @keydown.esc="closeRandom">
            <section ref="randomDialog" class="card tv-watchlist-dialog" role="dialog" aria-modal="true" aria-labelledby="tv-random-title" tabindex="-1" @keydown.tab="trapRandomFocus">
                <div class="card-header d-flex align-items-center justify-content-between"><h2 id="tv-random-title" class="h5 mb-0">Pick me a random TV show!</h2><button type="button" class="close" aria-label="Close random TV show" @click="closeRandom">×</button></div>
                <div class="card-body">
                    <p v-if="randomLoading" role="status">Picking a TV show…</p>
                    <p v-else-if="randomError" class="alert alert-danger" role="alert">{{ randomError }}</p>
                    <div v-else-if="randomShow" class="row">
                        <div class="col-sm-5"><img v-if="posterUrl(randomShow)" class="tv-random-poster" :src="posterUrl(randomShow)" :alt="randomShow.name" @error="posterFailed(randomShow)"></div>
                        <div class="col-sm-7"><h3 class="h4">{{ randomShow.name }} ({{ randomShow.year }})</h3><p v-if="randomShow.rating">★ {{ Number(randomShow.rating).toFixed(1) }}</p><p v-if="randomShow.genre">{{ randomShow.genre }}</p>
                            <button type="button" class="btn btn-primary mb-2" :disabled="!!saving[randomShow.id]" @click="save(randomShow, 'watchlist')">{{ Number(randomShow.watchlist) ? 'Remove from Watch List' : 'Add to Watch List' }}</button>
                            <button type="button" class="btn btn-success mb-2" :disabled="!!saving[randomShow.id]" @click="save(randomShow, 'watched')">{{ Number(randomShow.watched) ? 'Mark unwatched' : 'Mark watched' }}</button>
                            <a class="btn btn-warning mb-2" :href="'https://www.imdb.com/title/' + randomShow.imdb_id + '/'" target="_blank" rel="noopener noreferrer">View on IMDb <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <p v-else>No unwatched TV shows match your current filters.</p>
                </div>
                <div class="card-footer"><button type="button" class="btn btn-success" :disabled="randomLoading" @click="pickShow">{{ randomError ? 'Try again' : 'Pick again' }}</button></div>
            </section>
        </div>
        <div v-if="watchlistOpen" class="tv-modal-backdrop" @click.self="closeWatchlist" @keydown.esc="closeWatchlist">
            <section ref="watchlistDialog" class="card tv-watchlist-dialog" role="dialog" aria-modal="true" aria-labelledby="tv-watchlist-title" tabindex="-1" @keydown.tab="trapWatchlistFocus">
                <div class="card-header d-flex align-items-center justify-content-between"><h2 id="tv-watchlist-title" class="h5 mb-0">TV Shows Watch List</h2><button type="button" class="close" aria-label="Close watchlist" @click="closeWatchlist">×</button></div>
                <div class="card-body">
                    <p v-if="watchlistLoading" role="status">Loading watchlist…</p>
                    <div v-else-if="watchlistError" class="alert alert-danger">{{ watchlistError }} <button class="btn btn-link p-0" @click="fetchWatchlist">Retry</button></div>
                    <p v-else-if="!watchlistItems.length">Your TV watchlist is empty. Use the list icon on an unwatched show to add it.</p>
                    <ul v-else class="list-group">
                        <li v-for="show in watchlistItems" :key="show.id" class="list-group-item d-flex align-items-center justify-content-between flex-wrap">
                            <a :href="'https://www.imdb.com/title/' + show.imdb_id + '/'" target="_blank" rel="noopener noreferrer">{{ show.name }} ({{ show.year }})</a>
                            <div class="btn-group ml-2"><button class="btn btn-sm btn-outline-success" :disabled="!!saving[show.id]" @click="save(show, 'watched')">{{ Number(show.watched) ? 'Mark unwatched' : 'Mark watched' }}</button><button class="btn btn-sm btn-outline-danger" :disabled="!!saving[show.id]" @click="save(show, 'watchlist')">Remove</button></div>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
        <div v-if="page < lastPage" class="btn-group col-12 tv-show-more"><button type="button" class="btn btn-success" :disabled="loading" @click="showMore"><i class="fa fa-caret-down" aria-hidden="true"></i> Show more TV shows</button></div>
                </div>
        <p class="small text-muted text-center mt-3 mb-0">Streaming availability from <a href="https://www.justwatch.com/uk">JustWatch</a> via <a href="https://www.themoviedb.org">TMDB</a>. <a href="/about">Data credits</a></p>
        </div>
    </div>
</template>
<script>
const filterVisibility = require('../filter-visibility');
export default {
    props: {authenticated: {type: Boolean, default: false}, canEditStreams: {type: Boolean, default: false}},
    data() { return {items: [], search: '', status: 'all', page: 1, lastPage: 1, total: 0, loading: false, error: '', saving: {}, requestId: 0, genre: '', decade: '', english: false, popular: false, unwatched: false, favourites: false, summary: {watched: 0, total: 0, watchlist: 0}, availableFilters: {genres: [], decades: [], has_languages: false}, watchlistOpen: false, watchlistItems: [], watchlistLoading: false, watchlistError: '', previousFocus: null, filtersShown: filterVisibility.get(), services: [], selectedServices: [], randomOpen: false, randomShow: null, randomLoading: false, randomError: '', randomPreviousFocus: null, failedThumbnails: {}}; },
    computed: {
        watchedShowsCount() {return this.items.filter(show => Number(show.watched) === 1).length;}
    },
    mounted() { this.load(1); },
    beforeDestroy() { if (this.watchlistOpen || this.randomOpen) document.body.style.overflow = this.previousOverflow; },
    methods: {
        posterUrl(show) {
            return show.image_url_small_extended && !this.failedThumbnails[show.image_url_small_extended] ? show.image_url_small_extended : show.image_url;
        },
        posterFailed(show) {
            if (show.image_url_small_extended && !this.failedThumbnails[show.image_url_small_extended]) this.$set(this.failedThumbnails, show.image_url_small_extended, true);
        },
        filterParams() {
            return {search: this.search, status: this.status, genre: this.genre, decade: this.decade, english: Number(this.english), popular: Number(this.popular), unwatched: Number(this.unwatched), favourites: Number(this.favourites), services: this.selectedServices};
        },
        async pickShow() {
            if (this.randomLoading) return;
            if (!this.randomOpen) {
                this.randomPreviousFocus = document.activeElement;
                this.previousOverflow = document.body.style.overflow;
                this.randomOpen = true;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.randomDialog.focus());
            }
            this.randomLoading = true; this.randomError = ''; this.randomShow = null;
            try {
                const {data} = await axios.get('/tv-shows/catalogue', {params: {...this.filterParams(), random: 1}});
                if (this.randomOpen) this.randomShow = data.show;
            } catch (error) {this.randomError = 'Could not pick a TV show. Please try again.';}
            finally {this.randomLoading = false;}
        },
        closeRandom() {
            this.randomOpen = false;
            document.body.style.overflow = this.previousOverflow;
            if (this.randomPreviousFocus) this.randomPreviousFocus.focus();
        },
        trapRandomFocus(event) {
            const nodes = this.$refs.randomDialog.querySelectorAll('button:not(:disabled), a[href]');
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.randomDialog)) {event.preventDefault(); last.focus();}
            else if (!event.shiftKey && document.activeElement === last) {event.preventDefault(); first.focus();}
        },
        setFiltersShown(shown) {this.filtersShown = shown; filterVisibility.set(shown);},
        selectServices(all) {
            this.selectedServices = all ? this.services.map(service => service.key) : [];
            return this.load(1);
        },
        async toggleStreaming(series, service) {
            if (!this.canEditStreams || this.saving[series.id]) return;
            this.$set(this.saving, series.id, true); this.error = '';
            try {
                await axios.post('/tv-shows/streaming', {id: series.id, service: service.key, available: !Number(series[service.column])});
                await this.refresh();
            } catch (error) {this.error = 'Could not save streaming availability. Please try again.';}
            finally {this.$delete(this.saving, series.id);}
        },
        resetFilters() {
            this.search = ''; this.status = 'all'; this.genre = ''; this.decade = ''; this.english = false; this.popular = false; this.unwatched = false; this.favourites = false; this.selectedServices = [];
            return this.load(1);
        },
        async openWatchlist() {
            this.previousFocus = document.activeElement;
            this.previousOverflow = document.body.style.overflow;
            this.watchlistOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.watchlistDialog.focus());
            await this.fetchWatchlist();
        },
        closeWatchlist() {
            this.watchlistOpen = false;
            document.body.style.overflow = this.previousOverflow;
            if (this.previousFocus) this.previousFocus.focus();
        },
        trapWatchlistFocus(event) {
            const nodes = this.$refs.watchlistDialog.querySelectorAll('button:not(:disabled), a[href]');
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.watchlistDialog)) {event.preventDefault(); last.focus();}
            else if (!event.shiftKey && document.activeElement === last) {event.preventDefault(); first.focus();}
        },
        async fetchWatchlist() {
            this.watchlistLoading = true; this.watchlistError = '';
            try { const {data} = await axios.get('/tv-shows/watchlist'); this.watchlistItems = data; }
            catch (error) { this.watchlistError = 'Could not load your watchlist. Please try again.'; }
            finally {this.watchlistLoading = false;}
        },
        async showMore() {
            if (this.loading || this.page >= this.lastPage) return;
            await this.load(this.page + 1, true);
        },
        refresh() {return this.load(1, false, this.page);},
        async load(page, append = false, pages = 1) {
            const id = ++this.requestId;
            this.loading = true; this.error = '';
            try {
                const params = this.filterParams();
                const responses = await Promise.all(Array.from({length: pages}, (_, index) => axios.get('/tv-shows/catalogue', {params: {...params, page: page + index}})));
                if (id !== this.requestId) return;
                const data = responses[0].data;
                const incoming = responses.flatMap(response => response.data.data);
                this.items = [...new Map((append ? this.items.concat(incoming) : incoming).map(show => [show.id, show])).values()];
                this.page = Math.max(1, Math.ceil(this.items.length / 100)); this.lastPage = data.last_page; this.total = data.total;
                this.summary = data.summary || {watched: 0, total: data.total, watchlist: 0};
                this.availableFilters = data.filters || this.availableFilters;
                this.services = data.services || this.services;
            } catch (error) { if (id === this.requestId) this.error = 'Could not load TV shows. Please try again.'; }
            finally { if (id === this.requestId) this.loading = false; }
        },
        async save(series, field) {
            if (this.saving[series.id]) return;
            this.$set(this.saving, series.id, true); this.error = '';
            const value = !Number(series[field]);
            try {
                await axios.post('/tv-shows/progress', {id: series.id, field, value});
                this.$set(series, field, Number(value));
                await this.refresh();
                if (this.watchlistOpen) await this.fetchWatchlist();
            } catch (error) { this.error = 'Could not save your change. Please try again.'; }
            finally { this.$delete(this.saving, series.id); }
        }
    }
};
</script>
<style scoped src="./catalogue-card.css"></style>
<style scoped>
.tv-summary .btn-group, .tv-show-more {padding-bottom:10px;}
.watchedMovies {color:red; font-weight:bold;}
.watchListMovies {color:blue; font-weight:bold;}
.tv-random-poster {width:100%; max-width:229px; height:auto; margin-bottom:1rem;}
.tv-title {font-size:22px; font-weight:bold; margin-bottom:0;}
.tv-filter-form {margin-bottom:0; text-align:left;}
.tv-filter-form select {width:100%;}
.tv-catalogue > .card-header {font-size:16px;}
.tv-vert-align {height:30px; display:flex; align-items:center; margin-bottom:0;}
.tv-catalogue-guest .tv-vert-align {height:40px;}
.tv-nowrap {white-space:nowrap;}
.tv-filter-heart {color:red; font-size:17px;}
.tv-stream-card {text-align:center; padding:5px; background:#d8d8d8; margin-top:4px;}
.tv-stream-grid {display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:6px;}
.tv-stream-option {display:flex; align-items:center; justify-content:center; gap:4px; margin-bottom:0;}
.tv-stream-option img {width:20px; height:20px; object-fit:contain;}
.tv-stream-option:nth-child(9) {grid-column:2;}
.tv-stream-option:nth-child(10) {grid-column:3;}
.tv-stream-links {white-space:nowrap;}
.tv-stream-links .btn {font-size:.75rem;}
.movieCard .tv-platforms {display:grid; grid-template-columns:repeat(2,30px); gap:2px;}
.movieCard .tv-platforms-viewer {grid-template-columns:none; grid-template-rows:repeat(5,30px); grid-auto-flow:column; grid-auto-columns:30px; direction:rtl;}
.tv-platform-button {border:0; padding:0; background-color:transparent;}
.tv-platform-button:disabled {cursor:inherit; opacity:1;}
.tv-platform-button.dimmed:disabled {opacity:.2;}

.tv-modal-backdrop {position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,.65); display:flex; align-items:center; justify-content:center; padding:1rem;}
.tv-watchlist-dialog {width:100%; max-width:700px; max-height:90vh; overflow:auto;}
.tv-overlay-button { background: none; border: 0; padding: 0; height: 32px; text-align: left; }
.tv-overlay-button:disabled { cursor: wait; }
.tv-overlay-button:focus-visible { outline: 2px solid white; outline-offset: 4px; }
.footer { color: inherit; text-decoration: none; }
.tickContainer { pointer-events: none; }
</style>
