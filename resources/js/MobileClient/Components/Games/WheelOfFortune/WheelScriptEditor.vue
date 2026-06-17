<script setup>

import ParametrizedTextArea from "@/MobileClient/Components/Games/WheelOfFortune/ParametrizedTextArea.vue";
import SimpleProductList from "@/MobileClient/Components/Admin/Shop/SimpleProductList.vue";
</script>
<template>
    <form v-on:submit.prevent="submit" v-if="loaded_params">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   v-model="form.can_play"
                   role="switch" id="script-settings-wheel-of-fortune-can_play">
            <label class="form-check-label" for="script-settings-wheel-of-fortune-can_play">Состояние колеса
                фортуны: <span v-bind:class="{'text-primary fw-bold':form.can_play}">вкл</span> \
                <span v-bind:class="{'text-primary fw-bold':!form.can_play}">выкл</span></label>
        </div>

        <div class="form-check form-switch mb-2">
            <input class="form-check-input"
                   type="checkbox"
                   v-model="form.use_in_shop"
                   role="switch" id="script-settings-wheel-of-fortune-use_in_shop">
            <label class="form-check-label" for="script-settings-wheel-of-fortune-use_in_shop">Использовать выигрыши
                колеса фортуны в корзине товаров
                : <span v-bind:class="{'text-primary fw-bold':form.use_in_shop}">вкл</span> \
                <span v-bind:class="{'text-primary fw-bold':!form.use_in_shop}">выкл</span></label>
        </div>

        <div class="form-floating mb-2">
                <textarea class="form-control"
                          v-model="form.rules_text"
                          maxlength="4000"
                          style="min-height:150px;"
                          placeholder="Leave a comment here"
                          id="script-settings-wheel-of-fortune-can_play"></textarea>
            <label for="script-settings-disabled_text">Правила колеса фортуны
                <span
                    v-if="(form.rules_text||'').length>0">{{ (form.rules_text || '').length }}/4000</span>
            </label>
        </div>

        <div class="form-floating mb-2">
                <textarea class="form-control"
                          v-model="form.main_text"
                          maxlength="4000"
                          style="min-height:150px;"
                          placeholder="Leave a comment here"
                          id="script-settings-wheel-of-fortune-can_play" required></textarea>
            <label for="script-settings-disabled_text">Текст в боте
                <span
                    v-if="(form.main_text||'').length>0">{{ (form.main_text || '').length }}/4000</span>
            </label>
        </div>

        <div class="form-floating mb-2">
            <input type="text"
                   maxlength="100"
                   v-model="form.btn_text"
                   class="form-control" id="floatingInput" placeholder="name@example.com" required>
            <label for="floatingInput">Текст кнопки
                <span
                    v-if="(form.btn_text||'').length>0">{{ (form.btn_text || '').length }}/100</span>
            </label>
        </div>

        <ParametrizedTextArea
            v-model="form.win_message"
            :maxlength="4000"
            :required="true"
            class="mb-2">
            <template #title>
                Текст при выигрыше
            </template>
        </ParametrizedTextArea>

        <div class="form-floating mb-2">
                <textarea class="form-control"
                          v-model="form.callback_message"
                          maxlength="4000"
                          style="min-height:150px;"
                          placeholder="Leave a comment here"
                          id="script-settings-wheel-of-fortune-can_play" required></textarea>
            <label for="script-settings-disabled_text"> Детали получения приза
                <span
                    v-if="(form.callback_message||'').length>0">{{ (form.callback_message || '').length }}/4000</span>
            </label>
        </div>

        <div class="form-floating mb-2">
            <input type="number"
                   min="0"
                   v-model="form.max_attempts"
                   class="form-control" id="floatingInput" placeholder="name@example.com" required>
            <label for="floatingInput">Максимальное число попыток</label>
        </div>
        <div class="form-floating mb-2">
            <select class="form-select"
                    v-model="form.before_script"
                    id="select-custom-script" aria-label="Floating label select example">
                <option :value="null" selected>Не выбрано</option>
                <option :value="script.id" v-for="script in scripts">{{ script.title || '-' }}</option>

            </select>
            <label for="select-custom-script">Персональный скрипт</label>
        </div>

        <div class="alert alert-light mb-2">
            <p class="mb-0">
                <i class="fa-solid fa-trophy text-primary"></i>
                Призы, которые может выиграть пользователь, максимум <strong class="fw-bold text-primary">20</strong>
            </p>
            <p class="mb-0"
               v-if="form.wheels.length===0"><strong class="fw-bold text-primary">Внимание!</strong> Вы еще не добавили
                ни одного приза в колесо!</p>
        </div>

        <template v-if="form.wheels.length>0"
                  v-for="(item, index) in form.wheels">

            <div class="mb-2 align-items-start d-flex">
                <div class="form-floating w-100" style="padding-right:5px;">
                    <textarea class="form-control border-light"
                              v-model="form.wheels[index].value"
                              maxlength="4000"
                              style="min-height:100px;"
                              placeholder="Leave a comment here"
                              id="script-settings-wheel-of-fortune-can_play" required>

                    </textarea>
                    <label for="script-settings-disabled_text">#{{ index + 1 }} - описание приза
                        <span
                            v-if="(form.wheels[index].value||'').length>0">{{
                                (form.wheels[index].value || '').length
                            }}/4000</span>
                    </label>
                </div>

                <div class="dropdown">
                    <button
                        v-bind:style="{'background-color':form.wheels[index].bg_color}"
                        class="btn btn-outline-light" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa-solid fa-gear text-secondary"></i>
                    </button>

                    <ul class="dropdown-menu w-100">
                        <li><a class="dropdown-item"
                               @click="selectPrize(index)"
                               href="javascript:void(0)">
                            <i class="fa-solid fa-cog"></i>
                            Настроить</a></li>
                        <li>
                            <a class="dropdown-item"
                               @click="removeWheel(index)"
                               href="javascript:void(0)">
                                <i class="fa-solid fa-trash text-danger"></i>
                                Удалить
                            </a>
                        </li>
                    </ul>
                </div>


            </div>


        </template>

        <button
            type="button"
            v-if="form.wheels.length<20"
            @click="addWheel"
            class="btn btn-outline-primary p-3 w-100 mb-5">Добавить приз
        </button>

        <button
            style="z-index: 100;"
            type="submit" class="btn btn-primary w-100 p-3 mb-3 position-sticky tenanttom-0">Сохранить изменения
        </button>
    </form>

    <!-- Modal -->
    <div class="modal fade" id="prize-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Редактор приз</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <template v-if="selected_prize_index!=null">

                        <template v-if="tab==='main'">
                            <div class="form-floating mb-2">
                                <select class="form-select"
                                        id="floatingSelect"
                                        @change="selectProduct(null, selected_prize_index)"
                                        v-model="form.wheels[selected_prize_index].type"
                                        required
                                        aria-label="Floating label select example">
                                    <option
                                        :value="prizeType.key"
                                        v-for="prizeType in wheel_types">{{ prizeType.title || '-' }}
                                    </option>
                                </select>
                                <label for="floatingSelect">Тип приза</label>
                            </div>


                            <button
                                v-if="form.wheels[selected_prize_index].type==='effect_product'"
                                @click="tab='products'"
                                class="w-100 btn mb-2 btn-outline-light text-primary p-3" type="button"
                                aria-expanded="false">
                                <span v-if="!form.wheels[selected_prize_index].effect_product">Выбрать товар</span>
                                <span
                                    v-else>{{
                                        form.wheels[selected_prize_index].effect_product?.title || 'Без названия'
                                    }}</span>
                            </button>

                            <template v-if="form.wheels[selected_prize_index].type!=='text'">
                                <div class="form-floating mb-2">
                                    <input type="number"
                                           min="0"
                                           v-model="form.wheels[selected_prize_index].effect_value"
                                           class="form-control" id="floatingInput" placeholder="name@example.com">
                                    <label for="floatingInput">Значение эффекта</label>
                                </div>
                            </template>
                            <input type="color"
                                   @change="changeWheelColor(selected_prize_index)"
                                   v-model="form.wheels[selected_prize_index].bg_color"
                                   style="min-height:30px;"
                                   class="form-control p-0 mb-2" id="floatingInput" placeholder="name@example.com">

                            <div
                                style="max-height: 200px;overflow-y: auto;"
                                class="row row-cols-5 w-100 p-2 mb-2">
                                <div
                                    class="col mb-2" v-for="smile in smiles">
                                    <a
                                        v-bind:class="{'bg-primary':smile===(form.wheels[selected_prize_index]||{smile:null}).smile}"
                                        @click="addSmile(selected_prize_index, smile)"
                                        href="javascript:void(0)"
                                        class="btn btn-outline-light"
                                    >{{ smile }}</a>
                                </div
                                >
                            </div>
                        </template>
                        <template v-if="tab==='products'">

                            <button
                                @click="tab='main'"
                                class="btn btn-light text-secondary mb-3">Назад
                            </button>

                            <SimpleProductList
                                :selected="[form.wheels[selected_prize_index].effect_product?.id]"
                                v-on:select="selectProduct($event, selected_prize_index)"
                            />
                        </template>
                    </template>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success w-100 p-3" data-bs-dismiss="modal">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
export default {
    props: ["modelValue"],
    data() {
        return {
            tab: 'main',
            prize_config_modal: null,
            selected_prize_index: null,
            smiles: ["🥤", "🥗", "🍔", "🍗", "🍟", "🥓", "🌯", "🍱", "🍜", "🍲", "🍧", "🍨", "🧁", "🥞",
                "🤖", "🎲", "🎯", "😊", "😎", "🌻", "👽", "💌", "📚", "🐶", "👻", "🏀", "👓", "🎓",
                "1️⃣", "2️⃣", '3️⃣', "4️⃣", "5️⃣", "6️⃣", "7️⃣", "8️⃣", "9️⃣", "🔟", "💡", "🚀", "⭐", "💎", "☘", "🏆", "🎁"],
            loaded_params: false,
            wheel_types: [
                {
                    key: "text",
                    title: "Приз выдается во время заказа"
                },

                {
                    key: "product_discount",
                    title: "Скидка на товары, %"
                },
                /*   {
                       key: "delivery_discount",
                       title: "Скидка на доставку, %"
                   },*/
                {
                    key: "cashback",
                    title: "Начисление кэшбэка, руб"
                },
                {
                    key: "effect_product",
                    title: "Скидка на конкретный товар, %"
                }
            ],
            scripts: [
                {
                    id: 'marketplace_1',
                    title: 'Под маркетплейс (вариант 1)',
                },
                {
                    id: 'auth_1',
                    title: 'Форма регистрации',
                },
                {
                    id: 'review_1',
                    title: 'Форма отзыва',
                }
            ],
            form: {
                use_in_shop: false,
                can_play: true,
                rules_text: null,
                max_attempts: 1,
                main_text: null,
                callback_message: null,
                win_message: null,
                btn_text: null,
                before_script: null,
                after_script: null,
                wheels: []
            }
        }
    },
    computed: {
        tenant() {
            return window.Tenant
        },
    },
    watch: {
        'form': {
            handler: function (newValue) {
                this.$emit("updated:modelValue", this.form)
            },
            deep: true
        },

    },
    mounted() {

        this.prize_config_modal = new bootstrap.Modal('#prize-modal', {})
        this.loaded_params = false
        this.$nextTick(() => {
            this.form = this.modelValue
            this.loaded_params = true

        })

    },
    methods: {
        selectPrize(index) {
            this.selected_prize_index = null
            this.tab = 'main'
            this.$nextTick(() => {
                this.selected_prize_index = index
                this.prize_config_modal.show()
            })
        },
        selectProduct(item, index) {
            this.form.wheels[index].effect_product = item
            this.tab = 'main'
        },
        changeWheelColor(index) {

        },
        removeWheel(index) {
            this.form.wheels.splice(index, 1)

            this.$notify({
                title: 'Колесо фортуны',
                text: 'Приз успешно удален',
                type: 'success'
            })
        },
        addWheel() {
            if (!this.form.wheels)
                this.form.wheels = []

            if (this.form.wheels.length < 20) {

                this.form.wheels.push({
                    key: "wheel_text",
                    type: "text",
                    value: null,
                    effect_value: 0,
                    effect_product: null, //объект скидки: товар
                })

                this.$notify({
                    title: 'Колесо фортуны',
                    text: 'Новый приз успешно добавлен',
                    type: 'success'
                })
            } else {
                this.$notify({
                    title: 'Колесо фортуны',
                    text: 'Вы уже добавили максимальное число призов!',
                    type: 'error'
                })
            }
        },

        addSmile(index, smile) {
            this.form.wheels[index].smile = smile
        },
        submit() {
            let data = new FormData();
            Object.keys(this.form)
                .forEach(key => {
                    const item = this.form[key] || ''
                    if (typeof item === 'object')
                        data.append(key, JSON.stringify(item))
                    else
                        data.append(key, item)
                });


            this.$store.dispatch("updateWheelCustomScriptParams", {
                slugForm: data
            }).then((response) => {
                this.$notify({
                    title: "Информация о скрипте",
                    text: "Информация о скрипте успешно обновлена!",
                    type: "success"
                })
                this.$emit("callback", response.data)

                window.location.reload()
            }).catch(err => {
                this.$notify({
                    title: "Информация о скрипте",
                    text: "Ошибка обновления информации о скрипте",
                    type: "error"
                })
            })
        },

    }
}
</script>
