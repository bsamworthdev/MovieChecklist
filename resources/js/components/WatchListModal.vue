<template>
    <modal @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Your Watch List</h5>
        </div>

        <div slot="body">
            <div class="container">
                <div class="col-sm-12">
                    <div v-for="movie in watch_list" :key="movie.id">
                        {{ movie.rank }} - {{ movie.name }}
                        <img v-if="movie.on_netflix == '1'" src="/images/netflix.jpg" class="netflix" >
                        <img v-if="movie.on_amazon == '1'" src="/images/amazon.jpeg" class="amazon" >
                        <img v-if="movie.on_nowtv == '1'" src="/images/nowtv.jpg" class="nowtv" >
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
            watch_list: Array
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
            close: function() {
                this.$emit('close')
            }
        },
        data() {
            return {
                modalId:'randomMovieModal'
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
    .netflix, .amazon, .nowtv{
        width: 17px;
    }
</style>