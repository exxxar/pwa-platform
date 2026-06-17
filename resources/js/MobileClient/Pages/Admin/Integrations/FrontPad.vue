<script setup>
import FrontPadForm from "@/MobileClient/Components/Admin/Integrations/FrontPad/FrontPadForm.vue";


</script>
<template>

    <div class="container my-3">
        <div class="row">
            <div class="col-12">
                <FrontPadForm/>
            </div>
        </div>
    </div>




</template>
<script>


export default {
    data() {
        return {

        }
    },

    mounted() {


    },
    methods: {
        /* loadBotAdminConfig() {
             this.$store.dispatch("loadBotAdminConfig").then((resp) => {
                 this.bot = resp.data

                 this.amo = this.bot.amo


             })
         },*/
    }
}
</script>
