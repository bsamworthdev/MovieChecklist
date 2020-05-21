<template>
    <modal :parentData="{modalId: parentData.modalId}"  @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Add Friend</h5>
        </div>

        <div slot="body">
            <form action="/createFriendRequest" method="POST" class="form-horizontal">
                <div class="form-group">
                    <input type="text" name="email" class="form-control" @keyup="emailChanged($event)" placeholder="Friend Email" v-model="enteredEmail" value=""/>
                    <button id="sendButton" type="submit" :class="{'d-none':userExists}" class="btn btn-success form-control">
                        Invite a friend
                    </button>
                    <button id="sendButton" type="submit" :class="{'d-none':!userExists}" class="btn btn-success form-control">
                        Send Friend Request
                    </button>
                </div>    
            </form>
        </div>

        <div slot="footer">
            <button type="button" class="btn btn-default" @click="$emit('close')">Close</button>
        </div>
    </modal>
</template>

<script>
    import modal from './Modal' ;
    export default {
        name:'add-friend',
        props: {
            parentData: Object,
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
            emailChanged: function(e) {
                e.stopPropagation();
            //    if (isValidEmail){
            //        enable the button
            //    }

                axios.post('/findUserByEmail/',{
                    email:this.enteredEmail                  
                })
                .then((response) => {
                    this.userExists = response.data;
                    console.log(response);
                })
                .catch((error) => {
                    this.userExists = false;
                    console.log(error);
                });
            },
            close: function() {
                this.$emit('close')
            }
        },
        data() {
            return {
                userExists: false,
                enteredEmail: ''
            }
        }
    }
</script>
<style scoped>
    .form-group label {
        clear:both;
        float:left;
        vertical-align: top;
    }
    .form-group div {
        float:left;
        padding-bottom:5px;
    }
    #sendButton{
        margin-top:10px;
    }
</style>