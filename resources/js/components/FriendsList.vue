<template>
    <div id="friendsList" :class="{ 'modal-open': activeModal > 0 }">
        <div class="container">
            <div class="row">
                <div class="btn-group col-12">
                    <button type="button" class="btn btn-success" @click="addButtonClicked">+ Add Friend</button>
                </div>
            </div>
            <br>
            <div class="row">
                <div v-for="friend in filteredFriends" :key="friend.id" class="col-12 col-md-6 col-lg-4">
                    <div class="friend card" :class="statRating(friend.stats.overall)">
                        <div class="card-header">
                            <h4>
                                <div class="row">
                                    <div class="col-8 title">
                                        {{ friend.name }} 
                                    </div>
                                    <div class="col-4 starContainer">
                                        <i class="fa fa-star star"></i>
                                        <i class="fa fa-star star"></i>
                                        <i class="fa fa-star star"></i>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 tags">
                                        <div class="tag" v-for="tag in friend.tags" :key="tag">
                                            {{ tag }}
                                        </div>
                                    </div>
                                </div>
                            </h4>
                        </div>
                        <div class="card-body">
                            <h5>Top 100 Movies: <span class="stat" :class="statRating(friend.stats.overall)">{{ friend.stats.overall.watched }} of {{ friend.stats.overall.watched + friend.stats.overall.unwatched }}</span></h5>
                            <button class="btn btn-primary col-12" type="button" data-toggle="collapse" :data-target="'#genresContainer' + friend.id" aria-expanded="false" :aria-controls="'genresContainer' + friend.id">
                            Stats By Genre
                            </button>
                            <div class="collapse" :id="'genresContainer' + friend.id">
                                <div class="card card-body">
                                    <div class="row" v-for="(genre, key) in friend.stats.genre" :key="key">
                                        <div class="col-7">
                                            <span class="genre">{{key}}:</span>
                                        </div>
                                        <div class="col-5">
                                            <span class="stat" :class="statRating(genre)">
                                                {{ genre.watched }} / {{ genre.watched + genre.unwatched }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-primary showYearStats col-12" 
                            type="button" 
                            data-toggle="collapse" 
                            :data-target="'#yearsContainer' + friend.id" 
                            aria-expanded="false" 
                            aria-controls="'yearsContainer' + friend.id">
                            Stats By Year
                            </button>
                            <div class="collapse" :id="'yearsContainer' + friend.id">
                                <div class="card card-body">
                                    <div class="row" v-for="(time_period, key) in friend.stats.time_period" :key="key">
                                        <div class="col-7">
                                            <span class="time_period">{{key}}:</span>
                                        </div>
                                        <div class="col-5">
                                            <span class="stat" :class="statRating(time_period)">
                                                {{ time_period.watched }} / {{ time_period.watched + time_period.unwatched }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary col-4" @click="editButtonClicked(friend)">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger col-7" @click="deleteButtonClicked(friend)">
                                Remove Friend
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
            :friend = "activeFriend" 
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
        computed: {
            filteredFriends: function (){
                return this.friends.filter(i => i.matchesTagFilter === true)
            }
        },
        methods:{
            addButtonClicked(){
                this.activeModal=4; 
                this.activeFriend=friend;
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

    .friend.good, .friend.amazing{
        background-color:#d4f8d4;
    }

    .friend.medium{
        background-color:#fff1d8;
    }
    .friend.bad, .friend.terrible{
        background-color:#ffb2b2;
    }
    
    .stat.good, .stat.amazing{
        color:green;
    }
    .stat.medium{
        color:#e59400;
    }
    .stat.bad, .stat.terrible{
        color:red;
    }
    span.genre, span.time_period{
        min-width:100px;
    }
    .showYearStats{
        margin-top:5px;
    }
    .star{
        color:#C0C0C0;
        text-shadow: 0 0 3px #000;
        font-size:16px;
    }
    .starContainer{
        line-height:16px;
        white-space:nowrap;
    }
    .friend.bad .star:nth-of-type(1),
    .friend.medium .star:nth-of-type(1),
    .friend.good .star:nth-of-type(1){
        color:yellow;
    }
    .friend.medium .star:nth-of-type(2),
    .friend.good .star:nth-of-type(2){
        color:yellow;
    }
    .friend.good .star:nth-of-type(3){
        color:yellow;
    }
    .friend .card-header{
        padding-bottom:1px;
    }
    .tag{
        font-size: 12px;
        color: #212529;
        background-color: #C0C0C0;
        border: 1px solid rgba(0, 0, 0, 0.125);
        float: left;
        border-radius: 3px;
        margin-right: 3px;
        padding: 3px;
    }

    @media (max-width: 1200px) {
        .star {
            font-size:12px;
        }
        .starContainer{
            line-height:12px;
        }
        .title{
            font-size:18px;
        }
    }
    @media (max-width: 767px) {
        .star {
            font-size:16px;
        }
        .starContainer{
            line-height:16px;
        }
        .title{
            font-size:22px;
        }
    }
</style>