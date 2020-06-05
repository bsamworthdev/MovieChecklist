<template>
    <div class="container">
        <div class="trophyContainer" :class="{ 'selected':selectedTrophyStyle=='gold' }" @click="trophyClicked($event,'gold')">
            <i class="fas fa-trophy trophy gold"></i>{{ trophyByColor['gold'].length }}
        </div>
        <div class="trophyContainer" :class="{ 'selected':selectedTrophyStyle=='silver' }" @click="trophyClicked($event,'silver')">
            <i class="fas fa-trophy trophy silver"></i>{{ trophyByColor['silver'].length }}
        </div>
        <div class="trophyContainer" :class="{ 'selected':selectedTrophyStyle=='bronze' }" @click="trophyClicked($event,'bronze')">
            <i class="fas fa-trophy trophy bronze"></i>{{ trophyByColor['bronze'].length }}
        </div>
        <div v-if="showTrophyInfo" class="trophyInfo" :class="selectedTrophyStyle">
            <trophy-info-box
                :trophyStyle="selectedTrophyStyle"
                :trophyInfo="selectedTrophyInfo"
            >
            </trophy-info-box>
        </div>
    </div>
</template>

<script>
    import trophyInfoBox from './TrophyInfoBox';
    export default {
        props: {
            trophies: Array
        },
        components: {
            trophyInfoBox
        },
        methods: {
            trophyClicked: function (e, style){
                this.showTrophyInfo = false;
                this.selectedTrophyStyle = '';

                if (this.trophyByColor[style].length > 0){
                    this.selectedTrophyInfo = this.trophyByColor[style]
                    this.selectedTrophyStyle = style;
                    this.showTrophyInfo = true;
                }
                e.stopPropagation();
            },
            onClick: function () {
                this.selectedTrophyStyle = '';
                this.showTrophyInfo = false;
            },
        },
        computed: {
             trophyByColor: function (){
                var trophyByColor = [];
                var trophies = this.trophies;
                var trophy;
                
                trophyByColor['gold'] = [];
                trophyByColor['silver'] = [];
                trophyByColor['bronze'] = [];

                for (var i = 0; i < trophies.length; i++){
                    trophy= trophies[i];
                    trophyByColor[trophy.color].push(trophy);
                }
                return trophyByColor;
            }
        },
        data(){
            return {
                showTrophyInfo:false,
                selectedTrophyInfo:[],
                selectedTrophyStyle:''
            }
        },
        mounted() {
            console.log('Component mounted.');
            document.addEventListener('click', this.onClick);
        },
        beforeDestroy() {
            document.removeEventListener('click', this.onClick);
        },
    }
</script>
<style scoped>
    .trophy.gold{ color:gold; }
    .trophy.silver{ color:silver; }
    .trophy.bronze{ color:#cd7f32; }
    .fa-trophy {margin-right:2px!important;}
    .container{
        width: 147px;
        margin-left:0px;
    }
    .trophyInfo{
        position:absolute;
        top:39px;
        background-color:#FFF;
        border: 1px solid rgba(0, 0, 0, 0.125);
        border-radius: 0.25rem;
        z-index:999;
        width:250px;
        min-height:100px;
    }
    .trophyInfo.silver{
        margin-left:42px;
    }
    .trophyInfo.bronze{
        margin-left:84px;
    }
    .trophyContainer.selected{
        background-color:rgba(0, 0, 0, 0.1);
    }
</style>
