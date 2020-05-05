<template>
    <div id="friendsList" :class="{ 'modal-open': activeModal > 0 }">
        <div class="form-group">
            <div class="col-lg-4 pl-0">
                <select class="form-control" v-model="selectedPersonId" @change="filter($event)" id="add_person">
                    <option value="0">My Friends</option>
                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.firstname }} {{ person.surname }}</option>
                </select>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <button type="button" class="btn btn-primary" @click="addButtonClicked">+ Add Friend</button>
            </div>
            <br>
            <div class="row">
                <div v-for="friend in friends" :key="friend.id" class="friend card col-12 col-md-4 col-lg-3">
                    <h3>{{ friend.name }}</h3>
                    <div class="btn-group"  role="group">
                        <button type="button" class="btn btn-primary" @click="editButtonClicked(friend)">
                            Edit
                        </button>
                        <button type="button" class="btn btn-danger" @click="deleteButtonClicked(friend)">
                            Delete
                        </button>
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
        padding:20px;
        background-color: #C0C0C0;
    }
</style>