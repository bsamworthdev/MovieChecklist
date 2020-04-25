<template>
    <div class="container">
        <div class="row justify-content-left">
            <div v-if="user.id==1" class="btn-group col-12">
                <button class="btn btn-success" @click="updateMovies">Update Movies</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateMovieImages">Update Movie Images</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateSavedMovieImages">Update Saved Movie Images</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateNetflixStatuses">Get latest Netflix statuses</button>
            </div>
             <div class="col-sm-12">
                <h4>Hi {{ user.name }}, you have watched <span class="watchedMovies">{{ watchedMoviesCount }}</span> of <b>{{ movies.length }}</b> movies.</h4>
            </div>
            <div class="btn-group col-12">
                <button class="btn btn-success" @click="pickMovie">Pick me a random movie!</button>
            </div>
        </div>
        <div class="row justify-content-center">
            <scratch-card v-for="movie in movies" v-model="watchedMoviesCount" :key="movie.id" :movie="movie" @movieStatusChanged="movieStatusChanged"></scratch-card>
        </div>
        <random-movie-modal 
            v-if="activeModal==1" 
            @close="activeModal=0"
            :movie="randomMovie">
        </random-movie-modal>
        <div class="overlay" v-if="activeModal>0" >
            <div id="loading-img"></div>
        </div>
    </div>
</template>

<script>
    import scratchCard from './ScratchCard';
    import randomMovieModal from './RandomMovieModal';
    export default {
        props: {
            movies: Array,
            user: Object
        },
        components : {
            scratchCard,
            randomMovieModal
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
            }
        },
        data(){
            return {
                watchedMoviesCount:0,
                activeModal: 0,
                randomMovie:null,
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
