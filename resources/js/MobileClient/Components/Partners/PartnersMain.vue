<script setup>
import PartnerList from "@/MobileClient/Components/Partners/PartnerList.vue";
import PartnerTagList from "@/MobileClient/Components/Partners/PartnerTagList.vue";
</script>
<template>
    <PartnerList
        v-on:select="selectPartner"></PartnerList>

</template>
<script>
export default {
    methods:{
        selectPartner(partner){
            this.$emit("select", partner)
        }
    }
}
</script>
