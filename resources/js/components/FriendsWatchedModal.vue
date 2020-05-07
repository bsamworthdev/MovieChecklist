<template>
    <modal @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">My Friends</h5>
        </div>

        <div slot="body">
            <div class="container">
                <h5>{{ movie.friendsWatched }} friends also watched {{ movie.name }}</h5>
                <br>
                <div class="col-12">
                    <div id="friendsContainer" class="container">
                        <div class="row" v-for="friend in friendsStats" :key="friend.id">
                            <div class="col-6">
                                {{ friend.name }}
                            </div>
                            <div class="col-2">
                                <i v-if="friend.hasWatched" class="fa fa-check greenTick" :title="friend.name + ' has watched this movie'"></i>
                                <i v-else class="fa fa-times redCross" :title="friend.name + ' has not watched this movie'"></i>
                            </div>
                            <div class="col-2">
                                <i v-if="friend.isFavourite" class="fa fa-heart redHeart" :title="friend.name + ' loves this movie'"></i>
                            </div>
                            <div class="col-2">
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
            movie: Object,
            friendsStats: Array
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
                modalId:'friendsWatchedModal'
            }
        },
    }
</script>
<style scoped>
    #friendsContainer{
        overflow:auto;
    }
    .redCross{
        color:red;
    }
    .greenTick{
        color:green;
    }
    .redHeart{
        color:red;
    }
</style>