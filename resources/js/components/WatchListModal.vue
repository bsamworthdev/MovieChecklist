<template>
    <modal @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Your Watch List</h5>
        </div>

        <div slot="body">
            <div class="container">
                <div class="col-sm-12">
                    <div v-for="movie in watchList" :key="movie.id">
                        <div class="container">
                            <div class="row">
                                <div class="col-sm-9">
                                    {{ movie.top250_rank }} - {{ movie.name }}
                                    <img v-if="movie.on_netflix == '1'" src="/images/netflix.jpg" class="netflix" >
                                    <img v-if="movie.on_amazon == '1'" src="/images/amazon.jpeg" class="amazon" >
                                    <img v-if="movie.on_nowtv == '1'" src="/images/nowtv.jpg" class="nowtv" >
                                    <img v-if="movie.on_disney_plus == '1'" src="/images/disney_plus.jpg" class="disney_plus" >
                                </div>
                                <div class="col-sm-3">
                                    <i class="fa fa-trash text-danger" @click="remove(movie)" title="Remove from list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div slot="footer">
            <button type="button" class="btn btn-default" @click="close">Close</button>
        </div>
    </modal>
</template>

<script>
    import modal from './Modal' ;
    export default {
        props: {
            parentData: Object,
            watch_list: Array
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
            remove: function (movie) {
                axios.post('/toggleMovieInWatchList',{
                    movie_id:movie.id,
                    onWatchList:false                 
                })
                .then((response) => {
                    var index = this.watchList.map(x => {
                        return x.id;
                    }).indexOf(movie.id);
                    this.watchList.splice(index, 1);
                    
                    movie.on_watch_list = '0';
                    this.$emit('removeMovieFromWatchList', movie);
                    console.log(response);
                })
                .catch((error) => {
                    console.log(error);
                });
            },
            close: function() {
                this.$emit('close')
            }
        },
        data() {
            return {
                modalId:'randomMovieModal',
                watchList: this.watch_list
            }
        },
    }
</script>
<style scoped>
    #chosenMovieImage{
        background-position: center;
        background-repeat: no-repeat;
        background-size: contain;
        height:60vh;
    }
    .netflix, .amazon, .nowtv, .disney_plus{
        width: 17px;
    }
</style>