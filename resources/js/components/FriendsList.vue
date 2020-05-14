<template>
    <div id="friendsList" :class="{ 'modal-open': activeModal > 0 }">
        <div class="container">
            <div class="row">
                <div class="btn-group col-6">
                    <button type="button" class="btn btn-primary" @click="addButtonClicked">+ Add Friend</button>
                </div>
            </div>
            <br>
            <div class="row">
                <div v-for="friend in friends" :key="friend.id" class="col-12 col-md-4 col-lg-3">
                    <div class="friend card">
                        <div class="card-header">
                            <h4>{{ friend.name }}</h4>
                        </div>
                        <div class="card-body">
                            <h5>Top 100: <span class="stat" :class="statRating(friend.stats.overall)">{{ friend.stats.overall.watched }} of {{ friend.stats.overall.watched + friend.stats.overall.unwatched }}</span></h5>
                            <div v-for="(genre, key) in friend.stats.genre" :key="key">
                                <span class="genre">{{key}}:</span>
                                <span class="stat" :class="statRating(genre)">
                                    {{ genre.watched }} of {{ genre.watched + genre.unwatched }}
                                </span>
                            </div>
                            <div v-for="(time_period, key) in friend.stats.time_period" :key="key">
                                <span class="time_period">{{key}}:</span>
                                <span class="stat" :class="statRating(time_period)">
                                    {{ time_period.watched }} of {{ time_period.watched + time_period.unwatched }}
                                </span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary d-none" @click="editButtonClicked(friend)">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger" @click="deleteButtonClicked(friend)">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <add-friend 
            v-if="activeModal==4" 
            @close="activeModal=0" 
            :people = "people"
            :parentData="addFriendData"
            :selectedPersonId="selectedPersonId">
        </add-friend>
        <edit-friend 
            v-if="activeModal==5" 
            @close="activeModal=0" 
            :activeFriend = "activeFriend" 
            :parentData="editFriendData">
        </edit-friend>
        <delete-friend 
            v-if="activeModal==6" 
            @close="activeModal=0" 
            :friend = "activeFriend" 
            :parentData="deleteFriendData">
        </delete-friend>
    

        <div v-if="activeModal > 0" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
    import addFriend from './AddFriend';
    import editFriend from './EditFriend';
    import deleteFriend from './DeleteFriend';

    export default {
        name: 'friendsList',
        props: {
            friends : Array, 
            modalId : Number,
            people: Array,
            passedPersonId: {
                type: Number,
                default() { 
                    return 0; 
                }
            }
        },
        components : {
            addFriend,
            editFriend,
            deleteFriend
        },
        methods:{
            addButtonClicked(){
                this.activeModal=4; 
            },
            editButtonClicked(friend){
                this.activeModal=5; 
                this.activeFriend=friend; 
            },
            deleteButtonClicked(friend){
                this.activeModal=6; 
                this.activeFriend=friend; 
            },
            filter(event) {
                this.selectedPersonId = parseInt(event.target.value);
            },
            statRating(genre) {
                var percent = (genre.watched/(genre.watched+genre.unwatched))*100;
                var rating = '';
                if (percent > 40){
                    rating = 'good';
                }
                else if (percent > 20){
                    rating = 'medium';
                }
                else {
                    rating = 'bad';
                }
                return rating;
            }
        },
        data() {
            return {
                addFriendData:{ 
                    modalId:'addFriendModal'
                },
                editFriendData:{ 
                    modalId:'editFriendData'
                },
                deleteFriendData:{ 
                    modalId:'deleteFriendData', 
                },
                activeModal: 0,
                activeFriend: {},
                selectedPersonId: this.passedPersonId
            }
        },
        mounted() {
            console.log('Component mounted.')
        },
    }
</script>
<style lang="scss" scoped>
    .table{
        margin-top:5px;
    }
    .form-group label {
        width: 150px;
        vertical-align: top;
    }
    #emptyRecord{
        font-style:italic;
        text-align:center;
    }
    .friend{
        margin-bottom:15px;
        padding:0px;
        background-color: #F7F7F7;
    }
    .card-footer{
        text-align:center;
    }
    .stat.good{
        color:green;
    }
    .stat.medium{
        color:orange;
    }
    .stat.bad{
        color:red;
    }
    span.genre, span.time_period{
        min-width:100px;
    }
</style>