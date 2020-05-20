<template>
    <div id="usersTable">
        <div class="container">
             <div class="row">
                <div class="col-12">
                    <h5>Users: {{ filteredUsers.length }}</h5>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <input id="userFilter" v-model="userFilter" placeholder="Type here..." type="text" class="form-control">
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Movies Watched</th>
                        </tr>
                        <tr v-for="user in filteredUsers" :key="user.id">
                            <td>{{ user.name }}</td>
                            <td>{{ user.email }}</td>
                            <td>{{ user.stats['watched']}}</td>
                        </tr>
                    </table>
                </div>   
            </div>
        </div>
    </div>
</template>

<script>

    export default {
        name: 'usersTable',
        props: {
            users : Array
        }, 
        methods:{
            addButtonClicked(){
                this.activeModal=4; 
                this.activeFriend=friend;
            },
            editButtonClicked(user){
                this.activeModal=5; 
                this.activeFriend=friend; 
            },
            deleteButtonClicked(user){
                this.activeModal=6; 
                this.activeFriend=friend; 
            },
            statRating(genre) {
                var percent = (genre.watched/(genre.watched+genre.unwatched))*100;
                var rating = '';
                if (percent > 70){
                    rating = 'amazing';
                }
                else if (percent > 40){
                    rating = 'good';
                }
                else if (percent > 20){
                    rating = 'medium';
                }
                else if (percent > 2){
                    rating = 'bad';
                }
                else {
                    rating = 'terrible';
                }
                return rating;
            }
        },
        computed: {
            filteredUsers: function (){
                var filter = new RegExp(this.userFilter, 'gi');
                return this.users.filter(i => (i.name.match(filter)));
            }
        },
        watch: {
            filteredUsers: function(newVal, oldVal) {
                console.log('Prop changed: ', newVal, ' | was: ', oldVal)
            }
        },
        data() {
            return {
                userFilter:''
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
    }
</script>
<style lang="scss" scoped>
    .table {
        overflow:auto; 
        table-layout:fixed;
    }
    .table tr:first-of-type{
        background-color:#C0C0C0;
    }
</style>