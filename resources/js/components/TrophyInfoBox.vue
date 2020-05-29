<template>
    <div class="container">
        <div class="card">
            <div class="card-header">
                {{ trophyStyleUcFirst }} Trophy Details
            </div>
            <div class="card-body">
                <ul>
                    <li v-for= "trophy in trophyInfo" :key="trophy.id">
                        {{ trophy.details }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            trophyInfo: Array,
            trophyStyle: String
        },
        methods: {
           
        },
        computed: {
            trophyStyleUcFirst: function()
            {
                var str = this.trophyStyle;
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        },
        mounted() {
            console.log('Component mounted.');
        }
    }
</script>
<style scoped>
    .container{
        width: 100%!important;
        padding: 0px;
    }
    .card-header{
        padding: 0.15rem 1.25rem;
        font-size: 14px;
    }
    .card-body{
        padding: 0.25rem;
        margin-left: -10px;
    }
</style>
