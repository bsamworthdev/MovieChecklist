<template>
  <modal @close="close">
    <div slot="header">
      <button type="button" class="close" @click="$emit('close')" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      <h5 class="modal-title">Edit Movie</h5>
    </div>

    <div slot="body">
      <div class="container">
        <div class="col-sm-12">
          <h3>{{ movie.name }}</h3>
        </div>
        <div v-if="movie.on_netflix == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON NETFLIX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'netflix', '1')"
          >
            Wait! This movie
            <u>IS</u> on Netflix
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON NETFLIX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'netflix', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Netflix
          </button>
        </div>
        <br />
        <div v-if="movie.on_amazon == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON AMAZON</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'amazon', '1')"
          >
            Wait! This movie
            <u>IS</u> on Amazon
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON AMAZON</span>
          <button
            type="col-sm-12 button"
            class="btn btn-link"
            @click="setMovieStreamStatus($event, 'amazon', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Amazon
          </button>
        </div>
        <br />
        <div v-if="movie.on_nowtv == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON NOW TV</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'nowtv', '1')"
          >
            Wait! This movie
            <u>IS</u> on Now TV
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON NOW TV</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'nowtv', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Now TV
          </button>
        </div>
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
    setMovieStreamStatus(e, platform, status) {
      e.stopPropagation();
      axios
        .post("/setMovieStreamStatus", {
          movie_id: this.movie.id,
          platform: platform,
          status: status
        })
        .then(response => {
          location.reload();
        })
        .catch(error => {
          console.log(error);
        });
    },
    close: function() {
      this.$emit("close");
    }
  },
  data() {
    return {
      modalId: "editMovieDetailsModal"
    };
  }
};
</script>
<style scoped>
#chosenMovieImage {
  background-position: center;
  background-repeat: no-repeat;
  background-size: contain;
  height: 60vh;
}
span.badge {
  padding: 10px;
  font-size: 20px;
}
</style>