<!-- Vue component -->
<template>
  <div>
    <multiselect 
      v-model="value" 
      :multiple="true" 
      @search-change="searchChanged" 
      :options="options" 
      :close-on-select="false" 
      :clear-on-select="false" 
      placeholder="Type name..." 
      :preserve-search="true" 
      label="name" 
      track-by="id" 
      :preselect-first="true">
      <template slot="selection" slot-scope="{ values, search, isOpen }">
        <span class="multiselect__single" v-if="values.length && !isOpen">{{ values.length }} friend{{ values.length == 1 ? '' : 's' }}</span>
      </template>
    </multiselect>
    <select id="friendMultiSelect" name="options[]" class="d-none" multiple>
        <option v-for="friend in value" :value="friend.id" :key="friend.id" selected="selected">{{friend.name}}</option>
    </select>
  </div>
</template>

<script>
  import Multiselect from 'vue-multiselect'

  export default {
    props: {
      options: Array
    },
    components: {
      Multiselect
    },
    computed: {
      getFriendNames:function(){
        var arr = ['Select Friends'];
        for (var option of this.options) {
            arr.push(option.name);
        }
        return arr;
      }
    },
    methods:{
      searchChanged(){
        this.$emit('friendMultiSelectChanged');
        console.log('friends filter changed');
      }
    },
    data () {
      return {
        value: '42'
      }
    }
  }
</script>

<!-- New step!
     Add Multiselect CSS. Can be added as a static asset or inside a component. -->
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<style>
.multiselect__input, .multiselect__single{
  line-height:15px!important; /*was 20px*/
  min-height:15px!important; /*was 20px*/
}
.multiselect{
  min-height:35px!important; /*was 40px*/
}
.multiselect__select{
  min-height:35px!important; /*was 40px*/
}
.multiselect__tags{
  min-height:35px!important; /*was 40px*/
  color:#495057!important;
  border-color:#ced4da!important;
}
.multiselect--active{
  position:absolute!important;
}
</style>