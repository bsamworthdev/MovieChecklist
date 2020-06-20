<template>
    <modal @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Your Chosen Movie</h5>
        </div>

        <div slot="body">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-xs-12">
                        <div id="chosenMovieImage" :style="{ backgroundImage: `url(${movie.image_url_small})`}" >
                        </div>
                    </div>
                    <div class="col-md-6 col-xs-12">
                        <div class="container">
                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <h3>{{ movie.name }}</h3>
                                    <h5>{{ movie.year }}</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    {{ movie.genre }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 text-center mt-3">
                                    <div v-if="movie.on_netflix == 1" class="platform netflix" title="On netflix"></div>
                                    <div v-if="movie.on_amazon  == 1" class="platform amazon" title="On amazon video"></div>
                                    <div v-if="movie.on_nowtv  == 1" class="platform nowtv" title="On Now TV"></div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-12 text-center">
                                    <button v-if="isOnWatchList" type="button" class="btn btn-danger" @click="toggleIsOnWatchlist($event)"><i class="fa fa-thumbsup"></i> Remove From List</button>
                                    <button v-else type="button" class="btn btn-success" @click="toggleIsOnWatchlist($event)"><i class="fa fa-thumbsup"></i> Add it to my list!</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 text-center mt-2">
                                    <button type="button" class="btn btn-primary" @click="retry"><i class="fa fa-thumbsup"></i>Pick me another</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div slot="footer">
            <button type="button" class="btn btn-default" @click="$emit('close')">Close</button>
        </div>
    </modal>
</template>

<script>
    import modal from './Modal' ;
    export default {
        props: {
            parentData: Object,
            selectedPersonId: {
                type: Number,
                default() { 
                    return 0; 
                }
            },
            movie: Object
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.');
        },
        methods: {
            addToList: function() {
                this.$emit('addToList');
            },
            retry: function() {
                this.$emit('retry');
            },
            close: function() {
                this.$emit('close');
            },
            toggleIsOnWatchlist(e){
                e.stopPropagation();
                axios.post('/toggleMovieInWatchList',{
                    movie_id:this.movie.id,
                    onWatchList:!this.isOnWatchList                 
                })
                .then((response) => {
                    this.isOnWatchList = !this.isOnWatchList;                 
                    if (this.isOnWatchList){
                        this.movie.on_watch_list = '1';
                        this.$emit('addMovieToWatchList', this.movie);
                    } else {
                        this.movie.on_watch_list = '0';
                        this.$emit('removeMovieFromWatchList', this.movie);
                    }
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
        },
        data() {
            return {
                isOnWatchList: (this.movie.on_watch_list == 1),
                modalId:'randomMovieModal'
            }
        },
    }
</script>
<style scoped>
    #chosenMovieImage{
        background-position: top;
        background-repeat: no-repeat;
        background-size: contain;
        height: 100%;
        min-height:200px;
    }

    .platform {
        width:40px;
        height:40px;
        background-size: cover;
        margin-bottom:6px;
        margin-left:auto;
        margin-right:auto;
    }

    .platform.netflix {
        background-image: url('/images/netflix.jpg');
    }

    .platform.amazon {
        background-image: url('/images/amazon.jpeg');
    }

    .platform.nowtv {
        background-image: url('/images/nowtv.jpg');
    }
</style>