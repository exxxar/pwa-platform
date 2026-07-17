<template>


    <button type="button"
            v-if="item?.is_visible || true"
            @click="goTo(route)"
            style="min-height:250px;"
            :disabled="disabled||false"
            class="btn shadow-sm border-0 btn-outline-primary w-100  mb-2 card p-0">
        <div class="card-body p-0 d-flex justify-content-center align-items-center flex-column w-100">
            <img v-lazy="item?.img" class="img-fluid menu-item-img w-100" alt="">

            <slot name="counter"/>

            <p
                style="line-height:100%;text-transform:uppercase;font-size:12px;"
                class="my-2 px-3">{{item?.title || defaultText}} <slot name="post-text"/></p>
            <span style="font-size:12px;" v-if="disabled||false"><i class="fa-solid fa-lock"></i> закрыто</span>
        </div>

    </button>
</template>
<script>
export default {
    props:["item",'defaultImage','defaultText', 'route', 'disabled'],
    methods:{
        goTo(name) {
            this.$router.push({name: name})
        },
    }
}
</script>
<style>
.menu-item-img {
    height: 160px;
    object-fit: cover;
}
</style>
