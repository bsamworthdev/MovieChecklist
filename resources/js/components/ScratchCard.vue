<template>
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card movieCard" :class="{ watched: hasWatched }" @click="toggleWatched">
            <div class="card-body movieImage" :style="{ backgroundImage: `url(${movie.image_url})` }">
                <h4>{{ movie.name }}</h4>
                <div class="tickContainer">
                    <i v-if="hasWatched" class="fa fa-check tick"></i>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            movie: Object,
        },
        methods: {
            toggleWatched(){
                
                axios.post('/saveMovieUser',{
                    movie_id:this.movie.id,
                    watched:!this.hasWatched                    
                })
                .then((response) => {
                    this.hasWatched = !this.hasWatched;
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            }
        },
        data() {
            return {
                hasWatched: (this.movie.watched == 1)
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>

<style scoped>
    .movieCard{
        margin-bottom:20px;
        margin: 1px solid #C0C0C0;
        cursor:pointer;
    }
    .movieImage h4{
        color:transparent;
    }
    .movieCard .card-header{
        min-height:50px;
    }
    .movieImage:hover h4 {
        color:black!important;
        background-color:white;
        opacity:0.5;
    }
    .tick{
        color:green;
        font-size:100px;
    }
    .tickContainer{
        position:relative;
        text-align:center;
        margin-top:70px;
        width:100%;
    }
    .movieCard .movieImage {
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        position: relative;
        height: 300px;
    }
    .movieCard .movieImage:before{
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
    }
    .movieCard.watched .movieImage:before{
        background: rgba(0,0,0,0.7);
    }

</style>
