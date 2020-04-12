<template>
    <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card movieCard" :class="{ watched: hasWatched }" @click="toggleWatched">
            <div class="card-header">{{ movie.name }}</div>

            <div class="card-body">
                {{ movie.year }}
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            movie: Object,
        },
        data() {
            return {
                hasWatched: true,
            }
        },
        methods: {
            toggleWatched(){
                this.hasWatched = !this.hasWatched;
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>

<style scoped>
    .movieCard.watched .card-body{
        background-color:transparent;
    }
    .movieCard:not(.watched) .card-body{
        background-color:#C0C0C0;
    }
</style>
