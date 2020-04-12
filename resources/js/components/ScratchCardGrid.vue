<template>
    <div class="container">
        <div class="row justify-content-left">
            <button class="btn btn-success" @click="updateMovies">Update Movies</button>
            &nbsp;
            <button class="btn btn-success">Save</button>
        </div>
        <div class="row justify-content-center">
            <scratch-card v-for="movie in movies" :key="movie.id" :movie="movie"></scratch-card>
        </div>
    </div>
</template>

<script>
    import scratchCard from './ScratchCard';
    export default {
        props: {
            movies: Array,
        },
        components : {
            scratchCard,
        },
        methods: {
            updateMovies(){
                axios.post('/updatemovies')
                .then((response) => {
                    alert(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
