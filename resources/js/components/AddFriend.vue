<template>
    <modal :parentData="{modalId: parentData.modalId}"  @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Add Call</h5>
        </div>

        <div slot="body">
            <form action="/addfriend" method="POST" class="form-horizontal">
                <div class="form-group">
                    <input type="text" class="input-form" placeholder="Enter Pairing Code"/>
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
</style>