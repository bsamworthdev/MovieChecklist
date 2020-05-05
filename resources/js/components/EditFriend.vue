<template>
    <modal :parentData="{modalId: parentData.modalId}"  @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Edit Friend</h5>
        </div>

        <div slot="body">
            <form action="/editfriend" method="POST" class="form-horizontal">
                <div class="form-group">
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
        name:'edit-friend',
        props: {
            parentData: Object,
            activeFriend:{
                required:true,
                type: Object
            }
        },
        components: {
            modal
        },
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
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
    .header{
        width:100%;
    }
</style>