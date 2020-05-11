<template>
    <modal :parentData="{modalId: parentData.modalId}"  @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Add Friend</h5>
        </div>

        <div slot="body">
            <form action="/createFriendRequest" method="POST" class="form-horizontal">
                <div class="form-group">
                    <input type="text" name="email" class="form-control" @keydown="emailChanged($event)" placeholder="Friend Email"/>
                    <button id="sendButton" type="submit" class="btn btn-success form-control">
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
            people: Array,
            selectedPersonId: {
                type: Number,
                default() { 
                    return 0; 
                }
            }
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
            emailChanged: function(e) {
               // e.stopPropagation();
            //    if (isValidEmail){
            //        enable the button
            //    }
            },
            close: function() {
                this.$emit('close')
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