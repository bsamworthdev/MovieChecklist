<template>
    <div class="container">
        <div class="row justify-content-left">
            <div v-if="user.role=='admin'" class="d-none btn-group col-12">
                <button class="btn btn-success" @click="updateMovies">Update Movies</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateMovieImages">Update Movie Images</button>
            </div>
            <div v-if="user.role=='admin'" class="btn-group col-12">
                <button class="btn btn-success" @click="updateSavedMovieImages">Update Saved Movie Images</button>
            </div>
            <div v-if="user.role=='admin'" class="d-none btn-group col-12">
                <button class="btn btn-success" @click="updateNetflixStatuses">Get latest Netflix statuses</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateAmazonStatuses">Get latest Amazon statuses</button>
            </div>
             <div class="col-sm-12">
                <h4>Hi {{ user.name ? user.name:user.username }}, you have watched <span class="watchedMovies">{{ watchedMoviesCount }}</span> of <b>{{ all_movies.length }}</b> movies.</h4>
            </div>
             <div v-if="watch_list.length > 0" class="col-sm-12">
                <h5 class="d-inline">You have <span class="watchListMovies">{{ watch_list.length }}</span> movie{{ watch_list.length > 1 ? 's':'' }} in your Watch List.</h5>
                <div class="btn-group">
                    <button class="btn btn-primary" @click="showWatchList">Watch List</button>
                </div>
            </div>
            <div class="btn-group col-12">
                <button class="btn btn-success" @click="pickMovie">Pick me a random movie!</button>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="scratchCardContainer col-lg-3 col-md-4 col-sm-6 col-xs-12"
                v-for="movie in all_movies" 
                :key="movie.id" >
                <scratch-card 
                    :movie="movie" 
                    :user="user"   
                    :key="componentKey" 
                    @movieStatusChanged="movieStatusChanged"
                    @editMovieDetailsClicked="editMovieDetailsClicked"
                    @openIMDBModal="openIMDBModal"
                    @showFriendsPopup="showFriendsPopup"
                    @addMovieToWatchList="addMovieToWatchList"
                    @removeMovieFromWatchList="removeMovieFromWatchList">
                </scratch-card>
            </div>
            <div v-if="all_movies.length % 100 == 0" class="btn-group col-12">
                <button class="btn btn-success" @click="showMoreMovies">
                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                    Show more movies
                </button>
            </div>
        </div>
        <friends-watched-modal 
            v-if="activeModal==7" 
            @close="activeModal=0"
            :movie="clickedMovie"
            :friendsStats="friendsStats">
        </friends-watched-modal>
        <imdb-modal 
            v-if="activeModal==3" 
            :movie="clickedMovie"
            @close="activeModal=0">
        </imdb-modal>
        <edit-movie-details-modal 
            v-if="activeModal==2" 
            @close="activeModal=0"
            :movie="clickedMovie">
        </edit-movie-details-modal>
        <random-movie-modal 
            v-if="activeModal==1" 
            @close="activeModal=0"
            @retry="pickMovie"
            @addMovieToWatchList="addMovieToWatchList"
            @removeMovieFromWatchList="removeMovieFromWatchList"
            :movie="randomMovie">
        </random-movie-modal>
        <watch-list-modal 
            v-if="activeModal==8" 
            @close="activeModal=0"
            :watch_list="watchList"
            @removeMovieFromWatchList="removeMovieFromWatchList">
        </watch-list-modal>
        <div class="overlay" v-if="activeModal>0" >
            <div id="loading-img"></div>
        </div>
    </div>
</template>

<script>
    import scratchCard from './ScratchCard';
    import randomMovieModal from './RandomMovieModal';
    import watchListModal from './WatchListModal';
    import editMovieDetailsModal from './EditMovieDetailsModal';
    import friendsWatchedModal from './FriendsWatchedModal';
    import imdbModal from './IMDBModal';
    export default {
        props: {
            movies: Array,
            user: Object,
            watch_list: Array,
            filters: Object
        },
        components : {
            scratchCard,
            randomMovieModal,
            watchListModal,
            editMovieDetailsModal,
            imdbModal,
            friendsWatchedModal
        },
        methods: {
            setWatchedMoviesCount: function(){
                var arr = [];
                for (var i = 0; i < this.all_movies.length; i++) {
                    if (this.all_movies[i].watched == 1){
                        arr.push(this.all_movies[i]);
                    }
                }
                this.watchedMoviesCount = arr.length;
            },
            updateMovies(){
                axios.post('/updatemovies')
                .then((response) => {
                    console.log('movies updated successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            updateMovieImages(){
                axios.post('/updatemovieimages')
                .then((response) => {
                    console.log('movie images updated successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            updateSavedMovieImages(){
                axios.post('/updatesavedmovieimages')
                .then((response) => {
                    console.log('movie saved images updated successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            updateNetflixStatuses(){
                axios.post('/updatenetflixstatuses')
                .then((response) => {
                    console.log('netflix statuses updated successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            updateAmazonStatuses(){
                axios.post('/updateamazonstatuses')
                .then((response) => {
                    console.log('amazon statuses updated successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            movieStatusChanged(hasWatched) {
                console.log('movie status changed');
                if (hasWatched){
                    this.watchedMoviesCount++;
                } else {
                    this.watchedMoviesCount--;
                }
            },
            editMovieDetailsClicked(platform, movie) {
                this.activeModal = 2;
                this.clickedMovie = movie;
            },
            openIMDBModal(movie) {
                this.activeModal = 3;
                this.clickedMovie = movie;
            },
            showFriendsPopup(movie) {

                axios.post('/getFriendsStats/' + movie.id)
                .then((response) => {
                    console.log(response);
                    this.activeModal = 7;
                    this.friendsStats = response.data;
                    this.clickedMovie = movie;
                })
                .catch((error) => {
                    console.log(error);
                });

            },
            pickMovie() {

                var unwatchedMovies = this.movies.filter(function (el) {
                    return el.watched == false
                });

                var movie = unwatchedMovies[Math.floor(Math.random() * unwatchedMovies.length)];
                console.log('your random movie is ' + movie.name);
                this.randomMovie = movie;
                this.activeModal = 1;
            },
            showWatchList() {
                this.activeModal = 8;
            },
            showMoreMovies() {
                var filterString = '';
                var arr = [];
                for (const [key, value] of Object.entries(this.filters)) { 
                    if (value==='') value = 'null';
                    arr.push(value);
                }
                filterString = this.all_movies.length + '/' + arr.join('/');

                axios.post('/getMoreMovies/' + filterString)
                .then((response) => {
                    this.all_movies = this.all_movies.concat(response.data);
                    this.setWatchedMoviesCount();
                    console.log('Movies fetched successfully');
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            addMovieToWatchList(movie) {
                
                this.watchList.push(movie);

                this.watchList.sort(function(a, b){
                    if(a.rank < b.rank) { return -1; }
                    if(a.rank > b.rank) { return 1; }
                    return 0;
                })

                var index = this.all_movies.map(x => {
                    return x.id;
                }).indexOf(movie.id);
                // this.all_movies[index].on_watch_list = '1';
                // movie = this.all_movies[index];
                // movie.on_watch_list = '1'
                // this.all_movies.splice(index, 1);
                // this.all_movies.splice(index, 0, movie);
                // this.all_movies.push(movie);

                this.forceRerender();
            },
            removeMovieFromWatchList(movie) {
                var index = this.watchList.map(x => {
                    return x.id;
                }).indexOf(movie.id);
                this.watchList.splice(index, 1);

                var index2 = this.all_movies.map(x => {
                    return x.id;
                }).indexOf(movie.id);
                //this.all_movies[index2].on_watch_list = '0';
                // movie = this.all_movies[index2];
                // movie.on_watch_list = '0';
                // this.all_movies.splice(index2, 1);
                // this.all_movies.splice(index2, 0, movie);
                // this.all_movies.push(movie);

                this.forceRerender();
            },
            forceRerender () {
                this.componentKey += 1;  
            }
        },
        events: {

        },
        data(){
            return {
                watchedMoviesCount:0,
                activeModal: 0,
                clickedMovie: null,
                randomMovie: null,
                editedMovie: null,
                friendsStats: null,
                watchList: this.watch_list,
                all_movies: this.movies,
                componentKey: 0
            }
        },
        mounted() {
            // this.initialiseAllMovies();
            this.setWatchedMoviesCount();
            console.log('Component mounted.')
        }
    }
</script>
<style scoped>
    .btn-group{
        padding-bottom:10px;
    }
    .watchedMovies{
        color:red;
        font-weight:bold;
    }
    .btn-separator:after {
        content: ' ';
        display: block;
        float: left;
        margin: 0 2px;
        height: 34px;
        width: 1px;
    }
    .overlay {
        background: #0e0e0e;
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        opacity: 0.7;
        width: 100%;
        height: 100%;
    }
    .scratchCardContainer{
        width:auto;
    }
</style>
