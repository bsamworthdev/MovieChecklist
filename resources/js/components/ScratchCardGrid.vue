<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    
                    <scratch-card></scratch-card>
                    <scratch-card></scratch-card>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import scratchCard from './ScratchCard';
    export default {
        components : {
            scratchCard,
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
