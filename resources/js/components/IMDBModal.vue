<template>
  <modal @close="close">
    <div slot="header">
      <button type="button" class="close" @click="$emit('close')" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h5 class="modal-title">IMDB</h5>
    </div>

    <div slot="body">
      <div class="container">
        <iframe id="iframeMovie" :src="'https://imdb.com/title/' + movie.imdb_id"></iframe>
      </div>
    </div>

    <div slot="footer">
      <button type="button" class="btn btn-default" @click="$emit('close')">Close</button>
    </div>
  </modal>
</template>

<script>
import modal from "./Modal";
export default {
  props: {
    parentData: Object,
    movie: Object
  },
  components: {
    modal
  },
  mounted() {
    console.log("Component mounted.");
  },
  methods: {
    close: function() {
      this.$emit("close");
    }
  },
  data() {
    return {
      modalId: "IMDBModal"
    };
  }
};
</script>
<style scoped>
  #iframeMovie {
    width: 100%;
    height: 80vh;
    border:none;
  }
</style>