<template>
    <modal :parentData="{modalId: parentData.modalId}" @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Delete Friend</h5>
        </div>

        <div slot="body">
            <form action="/deletefriend" method="POST" class="form-horizontal">
                <label>Delete <span id="friendName" >{{ friend.name }}</span>?</label>
                <input type="hidden" id="id" name="id" :value="friend.id"/>
                <button type="submit" class="btn btn-danger form-control">
                    Delete
                </button>
            </form>
        </div>

        <div slot="footer">
            <button type="button" class="btn btn-default" @click="$emit('close')">Close</button>
        </div>
    </modal>
</template>

<script>
    import modal from './Modal';
    export default {
        name:'delete-friend',
        props: {
            parentData: {
                type: Object
            },
            friend:{
                required:true,
                type: Object
            }
        },
        components: {
            modal
        },
        methods: {
            close: function() {
                this.$emit('close')
            }
        },
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
<style scoped>
    .form-group label {
        width: 150px;
        vertical-align: top;
    }
</style>