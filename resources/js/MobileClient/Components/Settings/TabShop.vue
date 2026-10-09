<template>

    <form @submit.prevent="onSubmit" class="settings-form">

        <!-- 1. Внешний вид -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-palette"></i> Внешний вид</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Тема по умолчанию</label>
                    <select v-model="form.default_theme_scheme" @change="emitDirty">
                        <option v-for="scheme in availableSchemes" :key="scheme.id" :value="scheme.id">{{
                                scheme.name
                            }}
                        </option>
                    </select>
                    <span
                        class="field-hint">Эта тема будет применяться новым пользователям, пока они не выберут свою.</span>
                </div>
            </div>
        </div>

        <!-- 2. Основные параметры -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-store"></i> Основные параметры</h3>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Магазин выключен</h4>
                    <p>Пользователи увидят сообщение о том, что магазин не работает</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.is_disabled" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div v-if="form.is_disabled" class="form-field full-width">
                <label>Текст при выключении</label>
                <textarea v-model="form.disabled_text" rows="4" maxlength="4000" @input="emitDirty"></textarea>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Бронирование столиков</h4>
                    <p>У пользователей будет скрыт блок бронирования</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.has_booking" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label>Тип магазина</label>
                    <select v-model="form.shop_display_type" @change="emitDirty">
                        <option :value="0">Продовольственный</option>
                        <option :value="1">Бытовые товары</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Отображение товаров</label>
                    <select v-model="form.is_product_list" @change="emitDirty">
                        <option :value="false">Плитка</option>
                        <option :value="true">Список</option>
                    </select>
                </div>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Покупки после закрытия</h4>
                    <p>Разрешить оставлять заказы вне рабочего времени</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.can_buy_after_closing" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Скрывать товары без наличия</h4>
                    <p>Товары с нулевым остатком не будут видны в каталоге</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_hide_disabled_products" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- 3. Способы получения -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-cart-shopping"></i> Способы получения</h3>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Разрешить доставку</h4>
                    <p>Клиенты смогут выбирать доставку при оформлении заказа</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.allow_delivery" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Разрешить самовывоз</h4>
                    <p>Клиенты смогут забирать заказ самостоятельно</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.allow_pickup" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- 4. 🆕 Зоны доставки -->
        <div class="form-section" v-if="form.allow_delivery">
            <h3 class="section-title"><i class="fa-solid fa-map-location-dot"></i> Зоны доставки</h3>
            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Настройте градацию зон доставки. Укажите радиус, чтобы система могла автоматически проверять адрес клиента.
            </div>
            <div class="dynamic-list">
                <div v-for="(zone, index) in form.delivery_zones" :key="zone.id" class="list-item-card">
                    <div class="list-item-header">
                        <span class="list-item-badge">Зона {{ index + 1 }}</span>
                        <button type="button" class="btn-icon-danger" @click="removeZone(zone.id)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Название зоны</label>
                            <input type="text" v-model="zone.name" placeholder="Например: Центр" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Радиус зоны (км)</label>
                            <input type="number" v-model="zone.radius" min="0" step="0.1" placeholder="5" @input="emitDirty">
                            <span class="field-hint">Макс. расстояние от заведения</span>
                        </div>
                        <div class="form-field">
                            <label>Время доставки</label>
                            <input type="text" v-model="zone.time" placeholder="30-40 мин" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Стоимость</label>
                            <input type="text" v-model="zone.price" placeholder="Бесплатно или 150 ₽" @input="emitDirty">
                        </div>
                        <div class="form-field">
                            <label>Мин. сумма заказа (₽)</label>
                            <input type="number" v-model="zone.minOrder" min="0" placeholder="1000" @input="emitDirty">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-add-item" @click="addZone">
                <i class="fa-solid fa-plus"></i> Добавить зону
            </button>
        </div>

        <!-- 5. Теги заведения -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-tags"></i> Теги заведения</h3>
            <div class="alert-info">
                <i class="fa-solid fa-circle-info"></i>
                Укажите ключевые слова, описывающие ваше заведение (например: <em>Итальянская кухня, Пицца,
                Доставка</em>).
            </div>
            <div class="form-field full-width">
                <label>Список тегов (через запятую)</label>
                <textarea
                    v-model="form.venue_tags"
                    rows="3"
                    maxlength="500"
                    placeholder="Итальянская, Пицца, Доставка, Веганское меню, Wi-Fi..."
                    class="form-input"
                    @input="emitDirty"
                ></textarea>
                <span class="field-hint">
            Введено уникальных тегов: <strong>{{ countTags(form.venue_tags) }}</strong>
          </span>
            </div>
        </div>

        <!-- 6. Детали доставки -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-truck"></i> Доставка</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Минимальная сумма заказа, ₽</label>
                    <input type="number" v-model="form.min_price" min="0" @input="emitDirty">
                </div>
            </div>
            <div class="form-field full-width">
                <label>Текст о доставке</label>
                <textarea v-model="form.delivery_price_text" rows="4" maxlength="4000"
                          placeholder="Описание процесса доставки и оплаты" @input="emitDirty"></textarea>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Автоматический расчёт доставки</h4>
                    <p v-if="form.shop_display_type === 0">Расчёт по координатам заведения</p>
                    <p v-else>Расчёт через СДЭК</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_automatic_delivery_request" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <template v-if="form.need_automatic_delivery_request && form.shop_display_type === 0">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Базовая цена доставки, ₽</label>
                        <input type="number" v-model="form.min_base_delivery_price" min="0" @input="emitDirty">
                    </div>
                    <div class="form-field">
                        <label>Цена за 1 км, ₽</label>
                        <input type="number" v-model="form.price_per_km" min="0" @input="emitDirty">
                    </div>
                    <div class="form-field">
                        <label>Бесплатная доставка от, ₽</label>
                        <input type="number" v-model="form.free_shipping_starts_from" min="0" @input="emitDirty">
                    </div>
                    <div class="form-field full-width">
                        <label><i class="fa-solid fa-location-dot"></i> Адрес заведения (для доставки)</label>
                        <input type="text" v-model="form.address" maxlength="255"
                               placeholder="г. Москва, ул. Примерная, д. 1" @input="emitDirty">
                    </div>
                    <div class="form-field full-width">
                        <label>Координаты заведения (из Яндекс.Карт)</label>
                        <input type="text" v-model="form.shop_coords" placeholder="00.000000, 00.000000"
                               @input="emitDirty">
                    </div>
                    <div class="form-field full-width" style="margin-top: 8px;">
                        <label><i class="fa-solid fa-city"></i> Ближайшие города (для подсказок в корзине)</label>
                        <textarea
                            v-model="form.nearest_cities"
                            rows="3"
                            maxlength="500"
                            placeholder="Москва, Химки, Красногорск, Мытищи..."
                            @input="emitDirty"
                        ></textarea>
                        <span class="field-hint">Введите названия городов через запятую или с новой строки.</span>
                    </div>
                    <div class="form-field full-width">
                        <label>Токен MapTiler</label>
                        <input type="text" v-model="form.map_tiler" placeholder="Ваш токен" @input="emitDirty">
                    </div>
                </div>
            </template>
        </div>

        <!-- 7. 🆕 Сервисы и преимущества (Из версии 2) -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-concierge-bell"></i> Сервисы и преимущества</h3>
            <div class="dynamic-list">
                <div v-for="(service, index) in form.delivery_services" :key="service.id" class="list-item-card">
                    <div class="list-item-header">
                        <span class="list-item-badge">Преимущество {{ index + 1 }}</span>
                        <button type="button" class="btn-icon-danger" @click="removeService(service.id)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Название</label>
                            <input type="text" v-model="service.title" placeholder="Термосумки" @input="emitDirty">
                        </div>
                        <div class="form-field full-width">
                            <label>Описание</label>
                            <input type="text" v-model="service.description" placeholder="Сохраняем температуру"
                                   @input="emitDirty">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-add-item" @click="addService">
                <i class="fa-solid fa-plus"></i> Добавить сервис
            </button>
        </div>

        <!-- 8. Оплата -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-credit-card"></i> Оплата</h3>
            <div class="form-field full-width">
                <label>Информация об оплате</label>
                <textarea v-model="form.payment_info" rows="4" maxlength="4000" placeholder="Как оплатить и инструкции"
                          @input="emitDirty"></textarea>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Оплата после звонка</h4>
                    <p>Отключить оплату скриншотом — клиент ждёт звонка оператора</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_pay_after_call" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Наличные</h4>
                    <p>Разрешить оплату наличными</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.can_use_cash" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>Перевод</h4>
                    <p>Разрешить оплату переводом или qr</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.can_use_qr" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <!-- СБП -->
            <div class="sbp-banks-wrapper">
                <h4 style="margin: 20px 0 12px; font-size: 1rem; color: var(--text);">
                    <i class="fa-solid fa-qrcode" style="color: var(--primary); margin-right: 8px;"></i>
                    Система быстрых платежей (СБП)
                </h4>
                <p class="field-hint" style="margin-bottom: 16px;">Выберите и настройте банки, через которые вы
                    принимаете оплату по СБП.</p>

                <div v-for="(bankConfig, bankKey) in form.sbp_banks" :key="bankKey" class="sbp-bank-card"
                     :class="{ 'is-active': bankConfig.enabled }">
                    <div class="sbp-bank-header">
                        <div class="sbp-bank-name">{{ getBankName(bankKey) }}</div>
                        <label class="toggle-switch">
                            <input type="checkbox" v-model="bankConfig.enabled" @change="emitDirty">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div v-if="bankConfig.enabled" class="sbp-bank-fields">
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Ключ терминала</label>
                                <input type="text" v-model="bankConfig.terminal_key" @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Пароль терминала</label>
                                <input type="password" v-model="bankConfig.terminal_password" @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Схема налогообложения</label>
                                <select v-model="bankConfig.tax" @change="emitDirty">
                                    <option value="osn">Общая</option>
                                    <option value="usn_income">УСН (доходы)</option>
                                    <option value="usn_income_outcome">УСН (доходы-расходы)</option>
                                    <option value="patent">Патентная</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Ставка НДС</label>
                                <select v-model="bankConfig.vat" @change="emitDirty">
                                    <option value="none">Нет</option>
                                    <option value="vat0">0%</option>
                                    <option value="vat10">10%</option>
                                    <option value="vat20">20%</option>
                                </select>
                            </div>
                        </div>

                        <div class="test-payment-wrapper">
                            <button
                                type="button"
                                class="btn-test-payment"
                                @click="testSbpPayment(bankKey)"
                                :disabled="isTestingPayment"
                            >
                                <i v-if="isTestingPayment && testingBank === bankKey"
                                   class="fa-solid fa-spinner fa-spin"></i>
                                <i v-else class="fa-solid fa-credit-card"></i>
                                <span>Проверить оплату (100 ₽)</span>
                            </button>
                            <span class="field-hint">Откроет тестовую ссылку в новой вкладке. Создаст тестовый заказ на 100 ₽.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 9. Секции корзины -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Секции корзины</h3>
            <div class="toggle-row">
                <div class="toggle-info"><h4>Промокод</h4></div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_promo_code" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div class="toggle-info"><h4>Использование бонусов</h4></div>
                <label class="toggle-switch">
                    <input type="checkbox" v-model="form.need_bonuses_section" @change="emitDirty">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <template v-if="form.shop_display_type === 0">
                <div class="toggle-row">
                    <div class="toggle-info"><h4>Число персон</h4></div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="form.need_person_counter" @change="emitDirty">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="toggle-row">
                    <div class="toggle-info"><h4>Ограничения по здоровью</h4></div>
                    <label class="toggle-switch">
                        <input type="checkbox" v-model="form.need_health_restrictions" @change="emitDirty">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </template>
        </div>

        <!-- 10. Контактное лицо (Менеджер) -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-headset"></i> Контактное лицо (Менеджер)</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label>Имя менеджера</label>
                    <input type="text" v-model="form.manager.name" placeholder="Иван Иванов" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Телефон менеджера</label>
                    <input type="tel" v-model="form.manager.phone" placeholder="+7 (999) 000-00-00" @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Email менеджера</label>
                    <input type="email" v-model="form.manager.email" placeholder="manager@example.com"
                           @input="emitDirty">
                </div>
                <div class="form-field">
                    <label>Ссылка на соц. сеть / Мессенджер</label>
                    <input type="url" v-model="form.manager.social_link" @blur="normalizeTelegramLink"
                           placeholder="https://t.me/username" @input="emitDirty">
                </div>
            </div>
        </div>

        <!-- 11. 🎁 Анонимный бокс -->
        <div class="form-section">
            <h3 class="section-title"><i class="fa-solid fa-gift"></i> Анонимный бокс (Сюрприз-бокс)</h3>

            <div class="alert-info" style="margin-bottom: 16px;">
                <i class="fa-solid fa-circle-info"></i>
                Функция позволяет клиентам заказать "коробку-сюрприз" на выбранную сумму.
                Состав бокса автоматически подбирается из товаров разных категорий.
            </div>

            <!-- ✅ ГЛОБАЛЬНАЯ ЗАЩИТА: v-if гарантирует существование объекта -->
            <template v-if="form.anonymous_box">

                <!-- Toggle включения -->
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>Включить анонимные боксы</h4>
                        <p>Показывать баннер в каталоге и разрешать добавление в корзину</p>
                    </div>
                    <label class="toggle-switch">
                        <!-- ✅ Без ?. внутри v-if защиты -->
                        <input
                            type="checkbox"
                            v-model="form.anonymous_box.enabled"
                            @change="emitDirty"
                        >
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <template v-if="form.anonymous_box.enabled">
                    <!-- Суммы и подписи -->
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-coins" style="color: var(--primary); margin-right: 8px;"></i>
                            Доступные суммы
                        </h4>
                        <p class="field-hint" style="margin-bottom: 12px;">
                            Добавьте варианты сумм, на которые клиент может заказать бокс
                        </p>

                        <div class="dynamic-list">
                            <div v-for="(amount, index) in form.anonymous_box.amounts"
                                 :key="amount.id"
                                 class="list-item-card">
                                <div class="list-item-header">
                                    <span class="list-item-badge">Вариант {{ index + 1 }}</span>
                                    <button type="button" class="btn-icon-danger"
                                            @click="removeBoxAmount(amount.id)"
                                            :disabled="form.anonymous_box.amounts.length <= 1">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Сумма (₽)</label>
                                        <input type="number"
                                               v-model.number="amount.value"
                                               min="100"
                                               step="100"
                                               placeholder="1000"
                                               @input="emitDirty">
                                    </div>
                                    <div class="form-field">
                                        <label>Подпись (необязательно)</label>
                                        <input type="text"
                                               v-model="amount.label"
                                               placeholder="Базовый, Оптимальный..."
                                               @input="emitDirty">
                                        <span class="field-hint">Оставьте пустым — покажется "На {сумма} ₽"</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-add-item" @click="addBoxAmount">
                            <i class="fa-solid fa-plus"></i> Добавить сумму
                        </button>
                    </div>

                    <!-- Тексты -->
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-pen" style="color: var(--primary); margin-right: 8px;"></i>
                            Тексты
                        </h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Заголовок баннера</label>
                                <input type="text"
                                       v-model="form.anonymous_box.banner_title"
                                       placeholder="Анонимный бокс"
                                       @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Подзаголовок (плашка)</label>
                                <input type="text"
                                       v-model="form.anonymous_box.banner_subtitle"
                                       placeholder="Эксклюзив"
                                       @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Эмодзи</label>
                                <input type="text"
                                       v-model="form.anonymous_box.emoji"
                                       placeholder="🎁"
                                       maxlength="4"
                                       @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Позиция баннера</label>
                                <select v-model="form.anonymous_box.banner_position" @change="emitDirty">
                                    <option value="top">Вверху (перед товарами)</option>
                                    <option value="middle">В середине</option>
                                    <option value="bottom">Внизу</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-field full-width" style="margin-top: 12px;">
                            <label>Описание (в модалке)</label>
                            <textarea
                                v-model="form.anonymous_box.description"
                                rows="3"
                                maxlength="500"
                                placeholder="Хочешь сюрприз? Мы соберем для тебя..."
                                @input="emitDirty"></textarea>
                        </div>
                    </div>

                    <!-- Визуал -->
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-palette" style="color: var(--primary); margin-right: 8px;"></i>
                            Оформление
                        </h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Цвет градиента (начало)</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="color"
                                           v-model="form.anonymous_box.gradient_from"
                                           @input="emitDirty"
                                           style="width: 50px; height: 40px; border: none; cursor: pointer;">
                                    <input type="text"
                                           v-model="form.anonymous_box.gradient_from"
                                           placeholder="#667eea"
                                           @input="emitDirty"
                                           style="flex: 1;">
                                </div>
                            </div>
                            <div class="form-field">
                                <label>Цвет градиента (конец)</label>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <input type="color"
                                           v-model="form.anonymous_box.gradient_to"
                                           @input="emitDirty"
                                           style="width: 50px; height: 40px; border: none; cursor: pointer;">
                                    <input type="text"
                                           v-model="form.anonymous_box.gradient_to"
                                           placeholder="#f093fb"
                                           @input="emitDirty"
                                           style="flex: 1;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Алгоритм генерации -->
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-sliders" style="color: var(--primary); margin-right: 8px;"></i>
                            Алгоритм генерации
                        </h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>% из "богатых" категорий</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.rich_percent"
                                       min="0"
                                       max="100"
                                       @input="emitDirty">
                                <span class="field-hint">Категории с большим количеством товаров</span>
                            </div>
                            <div class="form-field">
                                <label>% из "бедных" категорий</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.poor_percent"
                                       min="0"
                                       max="100"
                                       @input="emitDirty">
                                <span class="field-hint">Категории с меньшим количеством товаров</span>
                            </div>
                            <div class="form-field">
                                <label>Мин. товаров в боксе</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.min_items"
                                       min="1"
                                       max="20"
                                       @input="emitDirty">
                            </div>
                            <div class="form-field">
                                <label>Макс. недобор (%)</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.max_tolerance"
                                       min="0"
                                       max="50"
                                       @input="emitDirty">
                                <span class="field-hint">Если нет подходящих товаров</span>
                            </div>
                        </div>
                        <div class="alert-info" style="margin-top: 12px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Сумма процентов должна быть 100%. Текущая:
                            <strong>{{ (form.anonymous_box.rich_percent || 0) + (form.anonymous_box.poor_percent || 0) }}%</strong>
                        </div>
                    </div>

                    <!-- Ограничения -->
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-shield-halved" style="color: var(--primary); margin-right: 8px;"></i>
                            Ограничения
                        </h4>
                        <div class="form-grid">
                            <div class="form-field">
                                <label>Макс. боксов в день на юзера</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.max_per_user_per_day"
                                       min="0"
                                       @input="emitDirty">
                                <span class="field-hint">0 — без ограничений</span>
                            </div>
                            <div class="form-field">
                                <label>Мин. сумма заказа для показа</label>
                                <input type="number"
                                       v-model.number="form.anonymous_box.min_order_amount"
                                       min="0"
                                       @input="emitDirty">
                                <span class="field-hint">0 — показывать всегда</span>
                            </div>
                        </div>

                        <div class="form-field full-width" style="margin-top: 12px;">
                            <label>ID исключенных категорий (через запятую)</label>
                            <textarea
                                v-model="form.anonymous_box.excluded_categories_text"
                                rows="2"
                                placeholder="15, 23, 42"
                                @input="onExcludedCategoriesChange"></textarea>
                            <span class="field-hint">
                        Товары из этих категорий не попадут в бокс (например: "Алкоголь", "Табак")
                    </span>
                        </div>
                    </div>

                    <!-- Превью -->
                    <div style="margin-top: 24px;">
                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text);">
                            <i class="fa-solid fa-eye" style="color: var(--primary); margin-right: 8px;"></i>
                            Предпросмотр
                        </h4>
                        <div class="mystery-box-preview"
                             :style="{
                         background: `linear-gradient(135deg, ${form.anonymous_box.gradient_from || '#667eea'} 0%, ${form.anonymous_box.gradient_to || '#f093fb'} 100%)`
                     }">
                            <div class="preview-emoji">{{ form.anonymous_box.emoji || '🎁' }}</div>
                            <div class="preview-content">
                                <div class="preview-subtitle">{{ form.anonymous_box.banner_subtitle || 'Эксклюзив' }}</div>
                                <div class="preview-title">{{ form.anonymous_box.banner_title || 'Анонимный бокс' }}</div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- Fallback если объект ещё не создан (показывает загрузку) -->
            <div v-else class="alert-info">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Настройки анонимных боксов загружаются...
            </div>
        </div>

        <!-- Кнопка сохранения -->
        <button type="submit" class="save-button" :disabled="isSaving">
            <i v-if="isSaving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ isSaving ? 'Сохранение...' : 'Сохранить' }}</span>
        </button>
    </form>

</template>

<script>
import {themeSchemes} from '@/MobileClient/constants/themeSchemes.js';

export default {
    name: 'TabShop',

    props: {
        activeTab: {type: [Number, String], default: null},
        form: {type: Object, required: true},
        isSaving: {type: Boolean, default: false},
        extraProps: {type: Object, default: () => ({})},
    },

    emits: ['save', 'mark-dirty', 'notify', 'test-payment'],

    data() {
        return {
            availableSchemes: themeSchemes,
            isTestingPayment: false,
            testingBank: null,
        };
    },

    watch: {
        'form': {
            handler() {
                this.initAnonymousBoxConfig();
            },
            deep: false,
            immediate: false,
        },
    },

    mounted() {
        this.initAnonymousBoxConfig();
    },

    methods: {
        /**
         * 🎁 Инициализация конфига анонимного бокса
         * Гарантирует, что form.anonymous_box всегда существует
         */
        initAnonymousBoxConfig() {
            if (!this.form) return;

            // Создаем объект, если его нет
            if (!this.form.anonymous_box || typeof this.form.anonymous_box !== 'object') {
                // ✅ Прямое присваивание БЕЗ опциональной цепочки
                this.form.anonymous_box = this.getDefaultAnonymousBoxConfig();
                return;
            }

            // Дополняем недостающие поля дефолтами
            const defaults = this.getDefaultAnonymousBoxConfig();
            for (const key in defaults) {
                if (this.form.anonymous_box[key] === undefined) {
                    this.form.anonymous_box[key] = defaults[key];
                }
            }

            // Гарантируем что amounts - массив
            if (!Array.isArray(this.form.anonymous_box.amounts)) {
                this.form.anonymous_box.amounts = [...defaults.amounts];
            }

            // Конвертация excluded_categories в текст (для textarea)
            if (Array.isArray(this.form.anonymous_box.excluded_categories)) {
                this.form.anonymous_box.excluded_categories_text =
                    this.form.anonymous_box.excluded_categories.join(', ');
            } else {
                this.form.anonymous_box.excluded_categories = [];
                this.form.anonymous_box.excluded_categories_text = '';
            }

            // Конвертация amounts в объекты с id
            this.form.anonymous_box.amounts = this.form.anonymous_box.amounts.map((a, idx) => {
                if (typeof a === 'object' && a !== null && a.id) return a;
                return {
                    id: Date.now() + idx + Math.random(),
                    value: typeof a === 'number' ? a : (a?.value || 1000),
                    label: typeof a === 'object' ? (a?.label || '') : '',
                };
            });
        },

        getDefaultAnonymousBoxConfig() {
            return {
                enabled: false,
                amounts: [
                    { id: 1, value: 1000, label: 'Базовый' },
                    { id: 2, value: 2000, label: 'Оптимальный' },
                    { id: 3, value: 3000, label: 'Премиум' },
                    { id: 4, value: 5000, label: 'Максимальный' },
                ],
                description: 'Хочешь сюрприз? Мы соберем для тебя случайный пакет из вкусных товаров ровно на выбранную сумму. Состав бокса ты узнаешь только в момент получения!',
                banner_title: 'Анонимный бокс',
                banner_subtitle: 'Эксклюзив',
                banner_position: 'top',
                gradient_from: '#667eea',
                gradient_to: '#f093fb',
                emoji: '🎁',
                rich_percent: 80,
                poor_percent: 20,
                min_items: 3,
                max_tolerance: 10,
                max_per_user_per_day: 0,
                min_order_amount: 0,
                excluded_categories: [],
                excluded_categories_text: '',
            };
        },

        // --- Методы для Анонимного бокса ---
        addBoxAmount() {
            // ✅ Гарантируем наличие массива
            if (!this.form.anonymous_box) {
                this.form.anonymous_box = this.getDefaultAnonymousBoxConfig();
            }
            if (!Array.isArray(this.form.anonymous_box.amounts)) {
                this.form.anonymous_box.amounts = [];
            }

            // ✅ Прямое обращение без опциональной цепочки
            this.form.anonymous_box.amounts.push({
                id: Date.now() + Math.random(),
                value: 1000,
                label: '',
            });
            this.emitDirty();
        },

        removeBoxAmount(id) {
            if (!this.form.anonymous_box?.amounts) return;
            if (this.form.anonymous_box.amounts.length <= 1) return;

            // ✅ Прямое присваивание
            this.form.anonymous_box.amounts = this.form.anonymous_box.amounts.filter(a => a.id !== id);
            this.emitDirty();
        },

        onExcludedCategoriesChange() {
            if (!this.form.anonymous_box) return;

            const text = this.form.anonymous_box.excluded_categories_text || '';
            // ✅ Прямое присваивание
            this.form.anonymous_box.excluded_categories = text
                .split(',')
                .map(s => parseInt(s.trim()))
                .filter(n => !isNaN(n));
            this.emitDirty();
        },

        emitDirty() {
            this.$emit('mark-dirty', 'shop');
        },

        onSubmit() {
            this.$emit('save', this.form);
        },

        // --- Методы для Зон доставки и Сервисов ---
        addZone() {
            if (!this.form.delivery_zones) this.form.delivery_zones = [];
            this.form.delivery_zones.push({
                id: Date.now(),
                name: '',
                radius: 5,
                time: '',
                price: '',
                minOrder: 0
            });
            this.emitDirty();
        },

        removeZone(id) {
            this.form.delivery_zones = this.form.delivery_zones.filter(z => z.id !== id);
            this.emitDirty();
        },

        addService() {
            if (!this.form.delivery_services) this.form.delivery_services = [];
            this.form.delivery_services.push({
                id: Date.now(), title: '', description: ''
            });
            this.emitDirty();
        },

        removeService(id) {
            this.form.delivery_services = this.form.delivery_services.filter(s => s.id !== id);
            this.emitDirty();
        },

        countTags(tagsString) {
            if (!tagsString) return 0;
            const tags = tagsString.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
            return new Set(tags).size;
        },

        normalizeTelegramLink() {
            if (this.form.manager && this.form.manager.social_link) {
                let link = this.form.manager.social_link.trim();
                if (link && !link.startsWith('http://') && !link.startsWith('https://')) {
                    this.form.manager.social_link = 'https://' + link;
                }
            }
        },

        getBankName(bankKey) {
            const bankNames = {
                'tinkoff': 'Тинькофф',
                'sber': 'Сбербанк',
                'alfa': 'Альфа-Банк',
                'vtb': 'ВТБ',
                'psb': 'Промсвязьбанк',
                'yandex': 'Яндекс Pay',
            };
            return bankNames[bankKey] || bankKey.toUpperCase();
        },

        testSbpPayment(bankKey) {
            this.isTestingPayment = true;
            this.testingBank = bankKey;
            this.$emit('test-payment', {bankKey, amount: 100});
            setTimeout(() => {
                this.isTestingPayment = false;
                this.testingBank = null;
            }, 3000);
        }
    },
};
</script>

<style scoped>
/* Добавьте сюда стили для .dynamic-list, .list-item-card, .sbp-banks-wrapper,
   .test-payment-wrapper и .btn-test-payment, если они еще не определены глобально */
.list-item-card {
    background: var(--bg-secondary, #f8f9fa);
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
}

.list-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.list-item-badge {
    background: var(--primary, #007bff);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: bold;
}

.btn-add-item {
    width: 100%;
    padding: 10px;
    border: 2px dashed var(--border-color, #ced4da);
    background: transparent;
    color: var(--text-secondary, #6c757d);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-item:hover {
    border-color: var(--primary, #007bff);
    color: var(--primary, #007bff);
}

.sbp-bank-card {
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.sbp-bank-card.is-active {
    border-color: var(--primary, #007bff);
    background: rgba(0, 123, 255, 0.03);
}

.sbp-bank-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.sbp-bank-name {
    font-weight: 600;
    font-size: 1rem;
}

.sbp-bank-fields {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color, #e9ecef);
}

.btn-test-payment {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--success, #28a745);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    margin-top: 8px;
}

.btn-test-payment:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.mystery-box-preview {
    padding: 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.preview-emoji {
    font-size: 2rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.preview-content {
    flex: 1;
}

.preview-subtitle {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    margin-bottom: 2px;
}

.preview-title {
    font-size: 1.1rem;
    font-weight: 700;
}
</style>
