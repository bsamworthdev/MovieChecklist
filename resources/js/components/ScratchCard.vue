<template>
    <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
        <div class="card movieCard" :class="{ watched: hasWatched }" @click="toggleWatched">
            <div class="card-body movieImage" :style="{ backgroundImage: `url(${movie.image_url_small})` }">
                <h4>{{ movie.name }}</h4>
                <div class="rank" :class="{ rounded: (movie.index==movie.rank) }">
                    <span class="filtered_rank">{{ movie.index }}</span>
                    <span v-if="movie.index!=movie.rank" class="actual_rank">({{ movie.rank }})</span>
                </div>
                <div v-if="hasWatched" class="favourite"
                        :class="{ selected: isFavourite, hovering: favouriteHover }" 
                        @mouseover="favouriteHover = true"
                        @mouseleave="favouriteHover = false"
                        @click="toggleIsFavourite($event)">
                    <i class="fa fa-heart heart filled"></i>
                    <i class="far fa-heart heart outline"></i>
                </div>
                <div class="rating">
                    <i class="fa fa-star star"></i>
                    <span>{{ ratingShort }}</span>
                </div>
                 <div class="platforms">
                    <div v-if="movie.on_netflix == 1" class="platform netflix" title="On netflix"></div>
                    <div v-if="movie.amazon" class="platform amazon" title="On amazon video"></div>
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
            },
            toggleIsFavourite(e){
                e.stopPropagation();
                axios.post('/setMovieAsFavourite',{
                    movie_id:this.movie.id,
                    favourite:!this.isFavourite                    
                })
                .then((response) => {
                    this.isFavourite = !this.isFavourite;
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
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
                hasWatched: (this.movie.watched == 1),
                isFavourite: (this.movie.favourite == 1),
                favouriteHover: false
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
        box-shadow:7px 7px #343a40;
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
    }
    .tickContainer{
        position:relative;
        text-align:center;
        width:100%;
    }
    .movieCard .movieImage {
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
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
        position:absolute;
        border-radius:8px;
        text-align:center;
        left:10px;
        top:9px;
        min-width:40px;
        border:1px solid black;
        padding-left:5px;
        padding-right:5px;
    }
    .movieCard .rank.rounded{
        border-radius:22px!important;
    }

    .movieCard .rank .filtered_rank{
        color:black;
        font-size:25px;
    }

    .movieCard .rank .actual_rank{
        font-size:12px;
        color:grey;
        display:block;
        margin-top:-9px;
        padding-bottom:3px;
    }

    .movieCard .rating{
        position:absolute;
        text-align:center;
        right:22px;
        top:9px;
        min-width:40px;
    }

    .movieCard .favourite{
        opacity:0.4;
        position:absolute;
        left:15px;
        top:65px;
        min-width:40px;
    }

    .movieCard .favourite.selected{
        opacity:1!important;
    }

    .movieCard .favourite.hovering .heart.outline{
        display:none;
    }

    .movieCard .favourite.hovering .heart.filled{
        display:block;
    }

    .movieCard .favourite.selected .heart.outline{
        display:none;
    }

    .movieCard .favourite.selected .heart.filled{
        display:block;
    }

    .movieCard .favourite:not(.hovering):not(.selected) .heart.outline{
        display:block;
    }

    .movieCard .favourite:not(.hovering):not(.selected) .heart.filled{
        display:none;
    }

    .movieCard .platforms{
        position:absolute;
        text-align:center;
        right:6px;
        top:60px;
        min-width:40px;
    }

    .movieCard .platform {
        width:30px;
        height:30px;
        background-size: cover;
        margin-bottom:6px;
    }

    .movieCard .platform.netflix {
        background-image: url('/images/netflix.jpg');
    }

    .movieCard .platform.amazon {
        background-image: url('/images/amazon.jpeg');
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
    .heart{
        position:absolute;
        color:red;
        font-size:31px;
    }

    @media only screen and (max-device-width: 800px){
        .movieImage h4{
            opacity:0.5!important;
        }
    }

    /*x-x-small*/
    @media (max-width: 320px) {
        .movieCard .movieImage{
            height: 320px;
        }
        .tick{
            font-size:80px;
        }
        .tickContainer{
            margin-top:80px;
        }
    }

    /*x-x-small*/
    @media (min-width: 321px) {
        .movieCard .movieImage{
            height: 390px;
        }
        .tick{
            font-size:80px;
        }
        .tickContainer{
            margin-top:100px;
        }
    }

    /*x-x-small*/
    @media (min-width: 400px) {
        .movieCard .movieImage{
            height: 549px;
        }
        .tick{
            font-size:120px;
        }
        .tickContainer{
            margin-top:300px;
        }
    }
    /*Extra-Small devices (portrait phones, 576px and up)*/
    @media (min-width: 576px) {
        .movieCard .movieImage{
            height: 242px;
        }
        .tick{
            font-size:60px;
        }
        .tickContainer{
            margin-top:100px;
        }
    }

    /*Small devices (landscape phones, 576px and up)*/
    @media (min-width: 768px){
        .movieCard .movieImage{
            height: 225px;
        }
        .tick{
            font-size:60px;
        }
        .tickContainer{
            margin-top:100px;
        }
    }
    
    /* Medium devices (desktops, 992px and up)*/
    @media (min-width: 992px) {
        .movieCard .movieImage{
            height: 233px;
        }
        .tick{
            font-size:80px;
        }
        .tickContainer{
            margin-top:100px;
        }
    }
    /*Extra large devices (large desktops, 1200px and up)*/
    @media (min-width: 1200px) {
        .movieCard .movieImage{
            height: 287px;
        }
        .tick{
            font-size:100px;
        }
        .tickContainer{
            margin-top:120px;
        }
    }

</style>
