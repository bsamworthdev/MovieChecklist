<template>
    <div class="container">
        <div class="row justify-content-left">
             <div class="col-sm-12">
                <h4 class="d-inline">You have watched <span class="watchedMovies">{{ watchedMoviesCount }}</span> of <b>{{ movies.length }}</b> movies.</h4>
                <div class="btn-group">
                    <button class="btn btn-success btn-lg" @click="registerAccount">Save my score!</button>
                </div>
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
            <scratch-card-non-auth
                v-for="movie in movies" 
                v-model="watchedMoviesCount" 
                :key="movie.id" 
                :movie="movie" 
                :watch_list="watch_list"       
                @movieStatusChanged="movieStatusChanged">
            </scratch-card-non-auth>
        </div>
        <random-movie-modal
            v-if="activeModal==1" 
            @close="activeModal=0"
            :movie="randomMovie">
        </random-movie-modal>
        <watch-list-modal 
            v-if="activeModal==8" 
            @close="activeModal=0"
            :watch_list="watch_list">
        </watch-list-modal>
        <div class="overlay" v-if="activeModal>0" >
            <div id="loading-img"></div>
        </div>
    </div>
</template>

<script>
    import scratchCardNonAuth from './ScratchCardNonAuth';
    import randomMovieModal from './RandomMovieModal';
    import watchListModal from './WatchListModal';
    export default {
        props: {
            movies: Array,
            watch_list: Array
        },
        components : {
            scratchCardNonAuth,
            randomMovieModal,
            watchListModal
        },
        methods: {
            setWatchedMoviesCount: function(){
                var arr = [];
                for (var i = 0; i < this.movies.length; i++) {
                    if (this.movies[i].watched == 1){
                        arr.push(this.movies[i]);
                    }
                }
                this.watchedMoviesCount = arr.length;
            },
            movieStatusChanged(hasWatched) {
                console.log('movie status changed');
                if (hasWatched){
                    this.watchedMoviesCount++;
                } else {
                    this.watchedMoviesCount--;
                }
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
            registerAccount() {
                location.href = '/register';
            }
        },
        events: {

        },
        data(){
            return {
                watchedMoviesCount:0,
                activeModal: 0,
                clickedMovie: null,
                randomMovie: null
            }
        },
        mounted() {
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
</style>
