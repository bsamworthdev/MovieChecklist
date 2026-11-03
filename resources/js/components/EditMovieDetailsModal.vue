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
        <br />
        <div v-if="movie.on_disney_plus == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON DISNEY PLUS</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'disney_plus', '1')"
          >
            Wait! This movie
            <u>IS</u> on Disney Plus
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON DISNEY PLUS</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'disney_plus', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Disney Plus
          </button>
        </div>
        <br />
        <div v-if="movie.on_hbo_max == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON HBO MAX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'hbo_max', '1')"
          >
            Wait! This movie
            <u>IS</u> on HBO Max
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON HBO MAX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'hbo_max', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On HBO Max
          </button>
        </div>
        <br />
        <div v-if="movie.on_apple_tv == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON APPLE TV</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'apple_tv', '1')"
          >
            Wait! This movie
            <u>IS</u> on Apple TV
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON APPLE TV</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'apple_tv', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Apple TV
          </button>
        </div>
        <br />
        <div v-if="movie.on_paramount_plus == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON PARAMOUNT+</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'paramount_plus', '1')"
          >
            Wait! This movie
            <u>IS</u> on Paramount+
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON PARAMOUNT+</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'paramount_plus', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Paramount+
          </button>
        </div>
        <br />
        <div v-if="movie.on_bbc_iplayer == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON BBC IPLAYER</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'bbc_iplayer', '1')"
          >
            Wait! This movie
            <u>IS</u> on BBC iPlayer
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON BBC IPLAYER</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'bbc_iplayer', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On BBC iPlayer
          </button>
        </div>
        <br />
        <div v-if="movie.on_itvx == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON ITVX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'itvx', '1')"
          >
            Wait! This movie
            <u>IS</u> on ITVX
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON ITVX</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'itvx', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On ITVX
          </button>
        </div>
        <br />
        <div v-if="movie.on_channel4 == 0" class="col-sm-12">
          <span class="col-sm-12 badge badge-danger">NOT ON CHANNEL 4</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'channel4', '1')"
          >
            Wait! This movie
            <u>IS</u> on Channel 4
          </button>
        </div>
        <div v-else class="col-sm-12">
          <span class="col-sm-12 badge badge-success">ON CHANNEL 4</span>
          <button
            type="button"
            class="col-sm-12 btn btn-link"
            @click="setMovieStreamStatus($event, 'channel4', '0')"
          >
            Wait! This Movie
            <u>IS NOT</u> On Channel 4
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