<template>
    <q-dialog v-model="dialog" @hide="sendClose">
        <q-card style="width: 700px; max-width: 80vw;">
            <q-card-section>
                <div class="text-h6">{{ this.resourceInformationAsked.title }}</div>
            </q-card-section>
            <q-separator/>
            <q-card-section horizontal>
                <q-card-section style="width: 300px">
                    <q-img
                            :src="'/uploads/'+this.resourceInformationAsked.image"
                            style="height: 150px;"
                            fit="contain"
                    >
                        <template v-slot:error>
                            <div class="absolute-full flex flex-center bg-white text-dark">
                                Aucune illustration disponible
                            </div>
                        </template>
                    </q-img>
                </q-card-section>
                <q-separator vertical/>
                <q-card-section>
                    <ul>
                        <li>
                            Type : {{this.resourceInformationAsked.type.title}} / {{this.resourceInformationAsked.subType.title}}
                        </li>
                        <li>
                            Service : {{this.resourceInformationAsked.service.title}}
                        </li>
                        <li>
                            Nombre : {{this.resourceInformationAsked.childs.length}}
                        </li>
                        <li>
                          Description : <div v-html="this.resourceInformationAsked.description"></div>
                        </li>
                    </ul>
                </q-card-section>
            </q-card-section>
            <q-card-actions align="right">
                <q-btn flat label="OK" color="primary" @click="sendClose"/>
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script>
export default {
    name: "showResourceInformations",
    data () {
        return {
            dialog: false,
        }
    },
    props: {
        showResourceInformation:Boolean,
        resourceInformationAsked:Object,
    },
    mounted() {
        this.dialog = this.showResourceInformation;
    },
    methods: {
        sendClose() {
            this.dialog = false;
            this.$emit('close', this.dialog);
        },
    },
    watch: {
        showResourceInformation: function () {
            this.dialog = this.showResourceInformation === true;
        }
    }
}
</script>

<style scoped>

</style>