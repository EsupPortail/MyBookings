<template>
  <div>{{ dataNode.label }}</div>

  <div
      style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; max-width: 90%; margin: auto; gap: 3px"
  >
      <q-toggle size="md" v-model="auto" val="md" :label="mode" @click="onSelect" />
  </div>
  <Handle id="a" type="target" :position="Position.Left" />

  <Handle v-if="dataNode.type === 'input'" id="b" type="source" :position="Position.Right"/>
</template>

<script>
import {Handle, Position} from "@vue-flow/core";

export default {
  emits: ['change'],
  components: {Handle},
  props: {
    dataNode: Object
  },
  data() {
    return {
      auto: true,
      mode: 'Automatique'
    }
  },
  computed: {
    Position() {
      return Position
    },
    actualNode() {
      return this.dataNode.auto;
    }
  },
  methods: {
    onSelect() {
      this.$emit('change', {'mode':this.auto, 'id': this.dataNode.id })
    }
  },
  watch:{
    actualNode: function (value) {
      this.auto = value;
      if(value === true) {
        this.mode = 'Automatique'
      } else {
        this.mode = 'Manuel'
      }
    }
  }
}

</script>