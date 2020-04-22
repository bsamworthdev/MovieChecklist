<template>
    <modal @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Your Chosen Movie</h5>
        </div>

        <div slot="body">
            <div class="container">
                <div class="col-sm-12">
                    <h3>{{ movie.name }}</h3>
                </div>
                <div class="col-sm-12">
                    <div id="chosenMovieImage" :style="{ backgroundImage: `url(${movie.image_url})`}" >
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
        name:'add-call-component',
        props: {
            parentData: Object,
            people: Array,
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
</style>