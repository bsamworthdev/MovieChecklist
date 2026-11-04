<template>
    <button v-show="shown" type="button" id="topButton" title="Go to top" @click="returnToTop">Back To Top</button>
</template>

<script>
export default {
    data() {return {shown: false};},
    mounted() {
        window.addEventListener('scroll', this.update, {passive: true});
        this.update();
    },
    beforeDestroy() {window.removeEventListener('scroll', this.update);},
    methods: {
        update() {this.shown = document.body.scrollTop > 200 || document.documentElement.scrollTop > 200;},
        returnToTop() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
            this.update();
        }
    }
};
</script>

<style scoped>
#topButton {
    display: block;
    position: fixed;
    bottom: 20px;
    right: 30px;
    z-index: 99;
    outline: none;
    background-color: black;
    color: white;
    cursor: pointer;
    padding: 10px;
    border-radius: 10px;
    font-size: 18px;
    opacity: 0.7;
}
#topButton:hover {background-color: #555;}
</style>
