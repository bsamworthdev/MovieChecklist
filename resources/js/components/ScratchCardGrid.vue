<template>
    <div class="container">
        <div class="row justify-content-left">
            <div v-if="user.id==1" class="btn-group col-12">
                <button class="btn btn-success" @click="updateMovies">Update Movies</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateMovieImages">Update Movie Images</button>
                <span class="btn-separator"></span>
                <button class="btn btn-success" @click="updateSavedMovieImages">Update Saved Movie Images</button>
            </div>
             <div class="col-sm-12">
                <h4>Hi {{ user.name }}, you have watched <span class="watchedMovies">{{ watchedMoviesCount }}</span> of <b>{{ movies.length }}</b> movies.</h4>
            </div>
        </div>
        <div class="row justify-content-center">
            <scratch-card v-for="movie in movies" v-model="watchedMoviesCount" :key="movie.id" :movie="movie" @movieStatusChanged="movieStatusChanged"></scratch-card>
        </div>
    </div>
</template>

<script>
    import scratchCard from './ScratchCard';
    export default {
        props: {
            movies: Array,
            user: Object
        },
        components : {
            scratchCard,
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
            movieStatusChanged(hasWatched) {
                console.log('movie status changed');
                if (hasWatched){
                    this.watchedMoviesCount++;
                } else {
                    this.watchedMoviesCount--;
                }
                //this.$forceUpdate();
            }
        },
        data(){
            return {
                watchedMoviesCount:0
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
        background: #ADADAD;
        margin: 0 2px;
        height: 34px;
        width: 1px;
    }
</style>
