<template>
    <div id="usersTable">
        <div class="container">
             <div class="row">
                <div class="col-12">
                    <h5>Users: {{ sortedUsers.length }}</h5>
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
                            <th @click="sort('name')">Name</th>
                            <th @click="sort('username')">Username</th>
                            <th @click="sort('email')">Email</th>
                            <th @click="sort('watchedCount')">Movies Watched</th>
                            <th @click="sort('created_at_tidy')">Date Added</th>
                        </tr>
                        <tr v-for="user in sortedUsers" :key="user.id">
                            <td>{{ user.name}}</td>
                            <td>{{ user.username}}</td>
                            <td>{{ user.email }}</td>
                            <td>{{ user.watchedCount}}</td>
                            <td>{{ user.created_at_tidy}}</td>
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
            },
             sort:function(s) {
                //if s == current sort, reverse
                if(s === this.currentSort) {
                    this.currentSortDir = this.currentSortDir==='asc'?'desc':'asc';
                }
                this.currentSort = s;
            }
        },
        computed: {
            sortedUsers: function() {

                var filter = new RegExp(this.userFilter, 'gi');
                return this.users.filter(i => (i.name.match(filter))).sort((a,b) => {
                    let modifier = 1;
                    if(this.currentSortDir === 'desc') modifier = -1;

                    var new_a = a[this.currentSort];
                    var new_b = b[this.currentSort];
                    if (isNaN(new_a) && isNaN('undefined')){
                            new_a = new_a.toLowerCase();
                            new_b = new_b.toLowerCase();
                    } 
                    if(new_a < new_b) return -1 * modifier;
                    if(new_a > new_b) return 1 * modifier;
                    return 0;
                });
            }
           
        },
        data() {
            return {
                userFilter:'',
                currentSort:'name',
                currentSortDir:'asc'
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
    }
</script>
<style lang="scss" scoped>
    .table {
        min-width:800px;
        overflow:auto; 
        table-layout:fixed;
    }
    .table tr:first-of-type{
        background-color:#C0C0C0;
    }
    .container-sm, .container{
        overflow: auto;
    }
</style>