<template>
    <div class="col-lg-3 col-md-4 col-6">
        <div class="card movieCard" :class="{ watched: hasWatched }" @click="toggleWatched">
            <div class="card-body movieImage" :style="{ backgroundImage: `url(${movie.image_url})` }">
                <h4>{{ movie.name }}</h4>
                <div class="rank">
                    <span>{{ movie.rank}}</span>
                </div>
                <div class="rating">
                    <i class="fa fa-star star"></i>
                    <span>{{ ratingShort }}</span>
                </div>
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
                    this.movieStatusChanged();
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            movieStatusChanged() {
                this.$emit('movieStatusChanged', this.hasWatched);
            }
        },
        computed: {
            ratingShort: function (){
                if (this.movie.rating) {
                    return parseFloat(this.movie.rating).toFixed(1);
                } else {
                    return 'n/a';
                }
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
        border: 4px solid #C0C0C0;
        cursor:pointer;
        height:90%;
    }
    .movieImage h4{
        color:black!important;
        margin-top:40px;
        position:absolute;
        color:transparent;
        background-color:white;
        opacity:0;
        width:100%;
        left:0;
        bottom:-8px;
        padding:4px 8px 4px 8px;
    }
    .movieCard .card-header{
        min-height:50px;
    }

    .movieImage:hover h4 {
        opacity:0.5;
    }
    .tick{
        color:green;
        font-size:100px;
    }
    .tickContainer{
        position:relative;
        text-align:center;
        margin-top:100px;
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

    .movieCard .rank{
        background-color:white;
        border-radius:22px;
        position:absolute;
        text-align:center;
        left:10px;
        top:9px;
        min-width:40px;
        border:1px solid black;
    }

    .movieCard .rank span{
        color:black;
        font-size:25px;
    }

    .movieCard .rating{
        position:absolute;
        text-align:center;
        right:22px;
        top:9px;
        min-width:40px;
    }

    .movieCard .rating span{
        position:relative;
        top:11px;
        left:12px;
        color:black;
        font-size:15px;
        width:40px;
        text-align:center;
    }
    .star{
        position:absolute;
        color:yellow;
        font-size:41px;
    }

</style>
