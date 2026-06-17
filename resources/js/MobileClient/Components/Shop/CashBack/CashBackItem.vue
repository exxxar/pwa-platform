<template>
    <div
        @click="open(item)"
        class="list-group-item d-flex flex-column">

        <div class="d-flex justify-content-between w-100">
            <div>
                <i class="fa fa-circle-up text-success mr-2" style="font-size:24px;" v-if="item.amount > 0"></i>
                <i class="fa-regular fa-circle-down text-danger mr-2" style="font-size:24px;" v-else></i>

                <span class="fw-bold">{{ item.amount || 0 }} руб. </span>
            </div>

            <span class="text-muted fw-bold" v-if="item.sub_title">{{ item.sub_title }}</span>
        </div>

        <div class="w-100 mt-2" v-if="item.is_open">
            <table class="table table-borderless rounded-sm shadow-l m-0 p-0" style="overflow: hidden;">
                <thead>
                <tr class="bg-gray1-dark">
                    <th scope="col" class="color-theme">Параметр</th>
                    <th scope="col" class="color-theme">Значение</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th scope="row">Сумма кэшбэка, руб</th>
                    <td class="fw-bold text-primary">{{ item.amount || 0 }}</td>
                </tr>

                <tr v-if="item.sub_title">
                    <th scope="row">Подзаголовок</th>
                    <td class="fw-bold text-primary">{{ item.sub_title }}</td>
                </tr>

                <tr>
                    <th scope="row">Описание</th>
                    <td class="fw-bold text-primary">{{ item.description || 'Нет описания' }}</td>
                </tr>

                <tr>
                    <th scope="row">Дата начисления</th>
                    <td class="fw-bold text-primary">{{ item.fired_at ? $filters.current(item.fired_at) : 'Не указано' }}</td>
                </tr>

                <tr>
                    <th scope="row">Дата создания</th>
                    <td class="fw-bold text-primary">{{ $filters.current(item.created_at) }}</td>
                </tr>

                <tr v-if="item.user">
                    <th scope="row">TG id пользователя</th>
                    <td class="fw-bold text-primary">{{ item.user.telegram_chat_id || 'Не указано' }}</td>
                </tr>

                <tr v-if="item.user">
                    <th scope="row">Имя пользователя</th>
                    <td class="fw-bold text-primary">{{ item.user.fio_from_telegram || 'Не указано' }}</td>
                </tr>

                <tr v-if="item.user">
                    <th scope="row">Телефон пользователя</th>
                    <td class="fw-bold text-primary">{{ item.user.phone || 'Не указано' }}</td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: ["item"],
    methods: {
        open(item) {
            item.is_open = !(item.is_open || false)
        }
    }
}
</script>
