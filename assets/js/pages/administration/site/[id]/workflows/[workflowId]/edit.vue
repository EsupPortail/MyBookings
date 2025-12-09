<template>
  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <h1 class="text-h6">{{ $t('workflows.editWorkflow') }}</h1>
        </div>
        <div class="col-auto">
          <q-btn round color="primary" icon="arrow_back" :to="{ name: 'workflowListAdmin' }">
            <q-tooltip>
              {{ $t('workflows.back') }}
            </q-tooltip>
          </q-btn>
        </div>
      </div>
    </q-card-section>
    <q-separator inset />
    <q-card-section>
      <q-form
          ref="workflowForm"
          class="q-gutter-md"
          @submit="onSubmit"
      >
        <q-card-section class="no-margin">
          <q-input
              v-model="title"
              :label="$t('title') + '*'"
              lazy-rules
              :rules="[ val => val && val.length > 0 || $t('externalUsers.requiredField')]"/>
        </q-card-section>
        <q-card-section class="no-margin">
          <div style="height: 500px">
            <VueFlow v-model="elements" :zoomOnDoubleClick="false" fit-view-on-init :style="{ background: '#006D82' }" default-marker-color="#fff" class="customnodeflow">
              <template #node-custom="{ data }">
                <toggleNode :dataNode="data" @change="changeMode" />
              </template>
              <template #node-toggle-mail="{ data }">
                <toggle-node-notification :dataNode="data" @change="changeNotifMode" />
              </template>
              <template #node-input-mail="{ data }">
                <div class="text-caption">
                  <q-icon size="sm" text-color="primary" name="help">
                    <q-tooltip>
                      {{ $t('workflows.emailSeparatorHelp') }}
                    </q-tooltip>
                  </q-icon>
                  {{data.label}}</div>
                <q-input dense outlined v-model="data.mail" type="text" :rules="[val => testMail(val) || $t('workflows.invalidEmail') ]"  />
              </template>
              <Background variant="dots" patternColor="#fff" size="0.8"/>
              <Controls />
            </VueFlow>
          </div>
        </q-card-section>
        <q-card-actions class="no-margin" align="right">
          <q-btn :label="$t('workflows.send')" type="submit" color="primary"/>
        </q-card-actions>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<route lang="json">
{
  "name": "editWorkflow",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import {ref} from 'vue';
import { VueFlow, MarkerType, Position } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import '@vue-flow/controls/dist/style.css'
import toggleNode from '../../../../../../components/customNode/toggleNode.vue'
import { useRoute } from 'vue-router/auto'

/* these are necessary styles for vue flow */
import '@vue-flow/core/dist/style.css';
/* this contains the default theme, these are optional styles */
import '@vue-flow/core/dist/theme-default.css';
import axios from "axios";
import {getWorkflowById, patchWorkFlow, postWorkFlow} from "../../../../../../api/Workflow";
import ToggleNodeNotification from "../../../../../../components/customNode/toggleNodeNotification.vue";
export default {
  components: {ToggleNodeNotification, VueFlow, Background, Controls, toggleNode},
  setup() {
    const route = useRoute();
    const workflowId = route.params.workflowId;
    return {
      workflowId
    }
  },
  data () {
    return {
      title:'',
      elements: ref([])
    }
  },
  mounted() {
    this.initializeWorkflowElements();
    this.getWorkflow();
  },
  methods: {
    initializeWorkflowElements() {
      this.elements = [
        // Nodes
        { id: '1', type: 'input', label: this.$t('workflows.booking'), position: { x: 0, y: 29 }, sourcePosition: Position.Right },
        { id: '2', type: 'custom', data: { id: '2', type: 'input', label: this.$t('workflows.bookingConfirmation'), auto: true }, position: { x: 250, y: 5 }, sourcePosition: Position.Right, targetPosition: Position.Left},
        { id: '5', type: 'toggle-mail', data: { id: '5', type: 'input', label: this.$t('workflows.moderationNotification'), mode: 0 }, position: { x: 260, y: 130 } },
        { id: '8', type: 'input-mail', data: { id: '8', type: 'output', label: this.$t('workflows.moderationMail'), mail: null }, hidden: true, position: { x: 240, y: 220 }, sourcePosition: Position.Top },
        { id: '3', type: 'custom', data: { id: '3', type: 'input', label: this.$t('workflows.resourceAccess'), auto: true }, position: { x: 660, y: 5 } },
        { id: '6', type: 'toggle-mail', data: { id: '6', type: 'input', label: this.$t('workflows.moderationNotification'), mode: 0 }, position: { x: 650, y: 130 }},
        { id: '9', type: 'input-mail', data: { id: '9', type: 'output', label: this.$t('workflows.moderationMail'), mail: null }, hidden: true, position: { x: 630, y: 220 }, sourcePosition: Position.Top },
        { id: '4',type: 'custom', data: { id: '4', type: 'input', label: this.$t('workflows.resourceReturn'), auto: true }, position: { x: 980, y: 5 } },
        { id: '7', type: 'toggle-mail', data: { id: '7', type: 'input', label: this.$t('workflows.moderationNotification'), mode: 0 }, position: { x: 970, y: 130 }},
        { id: '10', type: 'input-mail', data: { id: '10', type: 'output', label: this.$t('workflows.moderationMail'), mail: null }, hidden: true, position: { x: 950, y: 220 }, sourcePosition: Position.Top },

        // Edges
        { id: 'e1-2',type: 'smoothstep', source: '1', target: '2', animated: true, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e2-3',type: 'smoothstep', source: '2', target: '3', animated: true, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e3-4',type: 'smoothstep', source: '3', target: '4', animated: true, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e2-5',type: 'smoothstep', source: '2', target: '5', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e3-6',type: 'smoothstep', source: '3', target: '6', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e3-6',type: 'smoothstep', source: '4', target: '7', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e5-8',type: 'smoothstep', source: '5', target: '8', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e6-9',type: 'smoothstep', source: '6', target: '9', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
        { id: 'e7-10',type: 'smoothstep', source: '7', target: '10', animated: false, style: { stroke: '#fff', strokeWidth: '2'}, markerEnd: MarkerType.ArrowClosed },
      ];
    },
    getWorkflow() {
      let self = this;
      getWorkflowById(this.workflowId).then(function (response) {
        self.title = response.data.title;
        self.setUpWorkflowWithData(response.data.configuration);
      });
    },
    changeMode(data) {
      let self = this;
      this.elements.forEach(function(element, index) {
        if(element.target === data.id) {
          self.elements[index].animated = data.mode;
        }
        if(element.id === data.id) {
          self.elements[index].data.auto = data.mode;
        }
      });
    },
    changeNotifMode(data) {
      let self = this;
      this.elements.forEach(function(element, index) {
        if(element.id === data.id) {
          self.elements[index].data.mode = data.mode;
        }
        if(element.source === data.id) {
          self.elements[index].hidden = !data.mode;
          self.elements.forEach(function(mailElement, index) {
            if(mailElement.id === element.target) {
              self.elements[index].hidden = !data.mode;
              self.elements[index].data.mail = null;
            }
          })
        }
      });
    },
    onSubmit() {
      let router = this.$router;
      let autoValidation = true;
      let autoStart = true;
      let autoEnd = true;
      let notifValidation = null;
      let notifStart = null;
      let notifEnd = null;
      this.elements.forEach(function(element) {
        if(element.id === '2') {
          autoValidation = element.data.auto;
        }
        if (element.id === '3') {
          autoStart = element.data.auto;
        }
        if(element.id === '4') {
          autoEnd = element.data.auto;
        }
        if(element.id === '8') {
          notifValidation = element.data.mail;
        }
        if(element.id === '9') {
          notifStart = element.data.mail;
        }
        if(element.id === '10') {
          notifEnd = element.data.mail;
        }
      })
      let configuration = {
        'autoValidation': autoValidation,
        'autoStart': autoStart,
        'autoEnd': autoEnd,
        'notifications': {
          'accepted': notifValidation,
          'start': notifStart,
          'end': notifEnd,
        }
      }
      patchWorkFlow(this.title, autoValidation, autoStart, autoEnd, configuration, this.$route.params.workflowId).then(function () {
        router.push({ name: 'workflowListAdmin' });
      })
    },
    setUpWorkflowWithData(data) {
      this.changeMode({id: '2', mode: data.autoValidation});
      this.changeMode({id: '3', mode: data.autoStart});
      this.changeMode({id: '4', mode: data.autoEnd});
      if(data.notifications.accepted !== null) {
        this.changeNotifMode({id: '5', mode: true});
        this.elements[8].data.mail = data.notifications.accepted;
        this.elements[this.getIndexElementById('8')].data.mail = data.notifications.accepted;
      }
      if(data.notifications.start !== null) {
        this.changeNotifMode({id: '6', mode: true});
        this.elements[this.getIndexElementById('9')].data.mail = data.notifications.start;

      }
      if(data.notifications.end !== null) {
        this.changeNotifMode({id: '7', mode: true});
        this.elements[this.getIndexElementById('10')].data.mail = data.notifications.end;
      }
    },
    getIndexElementById(id) {
      let rsltElement = null;
      this.elements.forEach(function(element, index) {
        if(element.id === id) {
          rsltElement = index;
        }
      })
      return rsltElement;
    },
    testMail(mail) {
      const regex = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))(;(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,})))*$/;
      return regex.test(mail);
    }
  }
}
</script>

<style>

.vue-flow__node-input {
  font-size: 16px;
}
.customnodeflow .vue-flow__node-custom{border:1px solid #777;padding:10px;border-radius:3px;background:whitesmoke;display:flex;flex-direction:column;justify-content:space-between;align-items:center;gap:10px;max-width:250px}.customnodeflow button{padding:5px;width:25px;height:25px;border-radius:0px;-webkit-box-shadow:0px 5px 10px 0px rgba(0,0,0,.3);box-shadow:0 5px 10px #0000004d;cursor:pointer}.customnodeflow button:hover{opacity:.9;transform:scale(105%);transition:.25s all ease}.animated-bg-gradient{background:linear-gradient(122deg,#6f3381,#81c7d4,#fedfe1,#fffffb);background-size:800% 800%;-webkit-animation:gradient 4s ease infinite;-moz-animation:gradient 4s ease infinite;animation:gradient 4s ease infinite}@-webkit-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@-moz-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}
.customnodeflow .vue-flow__node-toggle-mail{border:1px solid #777;padding:10px;border-radius:3px;background:whitesmoke;display:flex;flex-direction:column;justify-content:space-between;align-items:center;gap:10px;max-width:250px}.customnodeflow button{padding:5px;width:25px;height:25px;border-radius:0px;-webkit-box-shadow:0px 5px 10px 0px rgba(0,0,0,.3);box-shadow:0 5px 10px #0000004d;cursor:pointer}.customnodeflow button:hover{opacity:.9;transform:scale(105%);transition:.25s all ease}.animated-bg-gradient{background:linear-gradient(122deg,#6f3381,#81c7d4,#fedfe1,#fffffb);background-size:800% 800%;-webkit-animation:gradient 4s ease infinite;-moz-animation:gradient 4s ease infinite;animation:gradient 4s ease infinite}@-webkit-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@-moz-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}
.customnodeflow .vue-flow__node-input-mail{border:1px solid #777;padding:10px;border-radius:3px;background:whitesmoke;display:flex;flex-direction:column;justify-content:space-between;align-items:center;gap:10px;max-width:250px}.customnodeflow button{padding:5px;width:25px;height:25px;border-radius:0px;-webkit-box-shadow:0px 5px 10px 0px rgba(0,0,0,.3);box-shadow:0 5px 10px #0000004d;cursor:pointer}.customnodeflow button:hover{opacity:.9;transform:scale(105%);transition:.25s all ease}.animated-bg-gradient{background:linear-gradient(122deg,#6f3381,#81c7d4,#fedfe1,#fffffb);background-size:800% 800%;-webkit-animation:gradient 4s ease infinite;-moz-animation:gradient 4s ease infinite;animation:gradient 4s ease infinite}@-webkit-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@-moz-keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}@keyframes gradient{0%{background-position:0% 22%}50%{background-position:100% 79%}to{background-position:0% 22%}}

</style>
