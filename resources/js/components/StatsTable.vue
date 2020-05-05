<template>
    <div id="statsTable" :class="{ 'modal-open': activeModal > 0 }">
        <div class="form-group">
            <div class="col-lg-4 pl-0">
                <select class="form-control" v-model="selectedPersonId" @change="filter($event)" id="add_person">
                    <option value="0">My Friends</option>
                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.firstname }} {{ person.surname }}</option>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-primary" @click="addButtonClicked">+ Add New</button>
        <table class="table table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th class="d-none"> id</th>
                    <th> Person</th>
                    <th> Friend Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="friend in friends" :key="friend.id">
                    <td class="d-none">{{ friend.id }}</td>
                    <td>{{ friend.name }}</td>
                    <td>
                        <div class="btn-group"  role="group">
                            <button type="button" class="btn btn-primary" @click="editButtonClicked(friend)">
                                Edit
                            </button>
                            <button type="button" class="btn btn-danger" @click="deleteButtonClicked(friend)">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="friends.length == 0">
                    <td colspan="8" id="emptyRecord">no records found</td>
                </tr>
            </tbody>
        </table>

        <div v-if="activeModal > 0" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
    export default {
        name: 'statsTable',
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
</style>