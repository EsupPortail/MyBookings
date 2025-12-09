<template>
  <div class="text-caption">{{ dataNode.label }}</div>

  <div
      style="display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; max-width: 90%; margin: auto; gap: 3px"
  >
      <q-toggle dense size="md" v-model="auto" val="md" :label="mode" @click="onSelect" />
  </div>
  <Handle id="a" type="target" :position="Position.top" />

  <Handle v-if="dataNode.type === 'input'" id="b" type="source" :position="Position.Bottom"/>
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
      auto: false,
      mode: 'Désactivé'
    }
  },
  computed: {
    Position() {
      return Position
    },
    actualMode() {
      return this.dataNode.mode;
    }
  },
  methods: {
    onSelect() {
      this.$emit('change', {'mode':this.auto, 'id': this.dataNode.id })
    }
  },
  watch:{
    actualMode(value) {
      this.auto = value;
      if(value === true) {
        this.mode = 'Activé'
      } else {
        this.mode = 'Désactivé'
      }
    }
  }
}

</script>