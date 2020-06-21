<template>
    <modal :parentData="{modalId: parentData.modalId}"  @close="close">
        <div slot="header">
            <button type="button" class="close" @click="$emit('close')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h5 class="modal-title">Edit Friend</h5>
        </div>

        <div slot="body">
            <form action="/editfriend" method="POST" class="form-horizontal">
                <div class="form-group">
                    <div class="col-2">
                        <span class="label label-default">Tags:</span>
                    </div>
                    <div class="col-10">
                        <vue-tags-input
                            class="w-100"
                            v-model="tag"
                            :tags="tags"
                            @tags-changed="newTags => tags = newTags"
                        />
                        <input type="hidden" id="tags" name="tags" :value="getTagNames"/>
                    </div>
                </div>
                <div class="form-group">
                        <input type="hidden" id="id" name="id" :value="friend.id"/>
                        <button id="saveButton" type="submit" class="btn btn-success form-control">
                            Save
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
        name:'edit-friend',
        props: {
            parentData: Object,
            friend:{
                required:true,
                type: Object
            }
        },
        components: {
            modal
        },
        mounted() {
            for (var i = 0; i < this.friend.tags.length; i++) {
                this.tags.push({
                    'text' : this.friend.tags[i]
                });
            }
            console.log('Component mounted.')
        },
        computed: {
            getTagNames: function(){
                var tagNames = [];
                var tags = this.tags;
                for (var i = 0; i < tags.length; i++) {
                    tagNames.push(tags[i].text);
                }
                return JSON.stringify(tagNames);
            },
        },
        methods: {
            close: function() {
                this.$emit('close')
            }
        },
        data() {
            return {
                tag: '',
                tags: [],
            };
        },
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
    .label{
        line-height:32px;
    }
</style>