<template>

  <div :id="modalId" class="modal fade show" role="dialog" data-backdrop="true">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <slot name="header">
            default header
          </slot>
        </div>
        <div class="modal-body">
          <slot name="body">
            default body
          </slot>
        </div>
        <div class="modal-footer">
          <slot name="footer">
            default footer 
            <button type="button" class="btn btn-default" @click="$emit('close')">Ok</button>
          </slot>
        </div>
      </div>

    </div>
  </div>

</template>
<script>
  export default {
    name: 'modal-component',
    props: {
      parentData: Object,
      modalId: String
    },
    mounted: function () {
      document.addEventListener("keydown", (e) => {
          if (this.show && e.keyCode == 27) {
              this.close()
          }
      })
    },
    methods: {
      close: function() {
          this.$emit('close')
      }
    }
  }
</script>
<style scoped>
  .modal.show {
    display:block;
  }
  .modal-header > div{
    width:100%;
  }
  .modal-body {
    max-height:72vh;
    min-height:140px;
    overflow:auto;
  }
  .modal,.modal-dialog{
    height:100%;
  }
</style>