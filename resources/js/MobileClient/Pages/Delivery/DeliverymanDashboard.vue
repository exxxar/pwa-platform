<template>
    <div v-if="isAdmin" class="deliveryman-dashboard">
        <!-- ========================================== -->
        <!-- HERO СЕКЦИЯ -->
        <!-- ========================================== -->
        <div class="deliveryman-hero" :class="{ 'is-offline': !isOnline }">
            <div class="hero-background"></div>
            <div class="hero-content">
                <div class="hero-header">
                    <div class="deliveryman-info">
                        <div class="deliveryman-details">
                            <h1 class="deliveryman-name">{{ deliveryman.name || 'Курьер' }}</h1>
                            <!-- 🆕 Только кнопка настроек -->
                            <button class="settings-btn-header" @click="showSettingsModal = true" title="Настройки автообновления">
                                <i class="fa-solid fa-gear"></i>
                                <span>Настройки</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Заработано</div>
                            <div class="metric-value">{{ formatPrice(deliveryman.earned) }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'available'">
                        <div class="metric-icon"><i class="fa-solid fa-list-ul"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Доступные</div>
                            <div class="metric-value">{{ deliveryman.available_orders_count }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'active'">
                        <div class="metric-icon"><i class="fa-solid fa-motorcycle"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">В доставке</div>
                            <div class="metric-value">{{ deliveryman.active_orders_count }}</div>
                        </div>
                    </div>
                    <div class="metric-card" @click="activeTab = 'completed'">
                        <div class="metric-icon"><i class="fa-solid fa-check-double"></i></div>
                        <div class="metric-info">
                            <div class="metric-label">Завершено</div>
                            <div class="metric-value">{{ deliveryman.completed_orders_count }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- НАВИГАЦИЯ -->
        <!-- ========================================== -->
        <div class="nav-wrapper">
            <div class="nav-tabs">
                <button v-for="tab in tabs" :key="tab.id" class="nav-tab" :class="{ 'is-active': activeTab === tab.id }" @click="activeTab = tab.id">
                    <i :class="tab.icon"></i>
                    <span>{{ tab.label }}</span>
                    <span v-if="tab.badge" class="nav-badge">{{ tab.badge }}</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- КОНТЕНТ -->
        <!-- ========================================== -->
        <div class="dashboard-content">

            <!-- Вкладка: Доступные заказы -->
            <div v-if="activeTab === 'available'" class="tab-content">
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ -->
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ (Заменить во всех 3 вкладках: available, active, completed) -->
                <div class="date-filter-bar">
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-day"></i> С:</label>
                        <input type="date" v-model="dateFrom" @change="reloadCurrentTab" class="date-input">
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-check"></i> По:</label>
                        <input type="date" v-model="dateTo" @change="reloadCurrentTab" class="date-input">
                    </div>

                    <!-- 🆕 Группа кнопок действий -->
                    <div class="filter-actions">
                        <button class="btn-reset-dates" @click="resetToToday" title="Показать только сегодняшние заказы">
                            <i class="fa-solid fa-rotate-left"></i> Сегодня
                        </button>
                        <button class="btn-load-orders" @click="reloadCurrentTab" :disabled="isLoadingTab">
                            <i v-if="isLoadingTab" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-rotate"></i>
                            <span>Загрузить</span>
                        </button>
                    </div>
                </div>

                <div v-if="availableOrders.length === 0" class="empty-state">
                    <i class="fa-solid fa-mug-hot"></i>
                    <p>Новых заказов пока нет. Ожидайте...</p>
                </div>
                <div v-else class="orders-grid">
                    <div v-for="order in availableOrders" :key="order.id" class="order-card available-order" @click="openOrderDetails(order)">
                        <div class="order-header">
                            <div class="order-number"><i class="fa-solid fa-receipt"></i><span>Заказ #{{ order.id }}</span></div>
                            <span class="order-distance"><i class="fa-solid fa-route"></i> {{ order.distance_km }} км</span>
                        </div>
                        <div class="order-meta">
                            <div class="meta-item"><i class="fa-solid fa-calendar"></i> {{ formatDate(order.created_at) }}</div>
                            <div class="meta-item"><i class="fa-solid fa-clock"></i> {{ formatTime(order.created_at) }}</div>
                        </div>
                        <div v-if="getOrderProducts(order).length > 0" class="order-products">
                            <div class="products-header"><i class="fa-solid fa-bag-shopping"></i><span>Товары ({{ getOrderProducts(order).length }})</span></div>
                            <ul class="products-list">
                                <li v-for="(product, idx) in getDisplayedProducts(order)" :key="idx">
                                    <span class="prod-qty">{{ product.count }}×</span> {{ product.title || product.name || 'Товар' }}
                                </li>
                            </ul>
                        </div>
                        <div class="customer-info">
                            <div class="info-row"><i class="fa-solid fa-store"></i> {{ order.tenant_name }}</div>
                            <div class="info-row address-row"><i class="fa-solid fa-location-dot"></i> <span>{{ order.address || 'Адрес не указан' }}</span></div>
                            <a v-if="order.lat && order.lng" :href="getYandexMapUrl(order)" target="_blank" class="btn-map-link">
                                <i class="fa-solid fa-map-location-dot"></i> Показать на Яндекс.Карте
                            </a>
                        </div>
                        <div v-if="order.info" class="order-info-parsed">
                            <div v-for="(item, index) in parseOrderInfo(order.info)" :key="index" :class="['info-item', item.type === 'note' ? 'info-note' : 'info-kv']">
                                <template v-if="item.type === 'kv'">
                                    <span class="info-key">{{ item.key }}:</span>
                                    <span class="info-value">{{ item.value }}</span>
                                </template>
                                <template v-else>
                                    <span class="info-note-text"><i class="fa-solid fa-circle-info me-1"></i> {{ item.text }}</span>
                                </template>
                            </div>
                        </div>
                        <div class="order-price-row">
                            <div class="price-block">
                                <span class="price-label">Сумма:</span>
                                <span class="price-value">{{ formatPrice(order.order_price) }}</span>
                            </div>
                            <div class="earning-block">
                                <i class="fa-solid fa-coins"></i>
                                <span>+{{ formatPrice(order.delivery_price) }} ₽</span>
                            </div>
                        </div>
                        <div class="order-actions" @click.stop>
                            <button class="btn-action btn-accept" @click="handleAcceptOrder(order.id)">
                                <i class="fa-solid fa-bolt"></i> Взять заказ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладка: Мои текущие -->
            <div v-if="activeTab === 'active'" class="tab-content">
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ -->
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ (Заменить во всех 3 вкладках: available, active, completed) -->
                <div class="date-filter-bar">
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-day"></i> С:</label>
                        <input type="date" v-model="dateFrom" @change="reloadCurrentTab" class="date-input">
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-check"></i> По:</label>
                        <input type="date" v-model="dateTo" @change="reloadCurrentTab" class="date-input">
                    </div>

                    <!-- 🆕 Группа кнопок действий -->
                    <div class="filter-actions">
                        <button class="btn-reset-dates" @click="resetToToday" title="Показать только сегодняшние заказы">
                            <i class="fa-solid fa-rotate-left"></i> Сегодня
                        </button>
                        <button class="btn-load-orders" @click="reloadCurrentTab" :disabled="isLoadingTab">
                            <i v-if="isLoadingTab" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-rotate"></i>
                            <span>Загрузить</span>
                        </button>
                    </div>
                </div>

                <div v-if="activeOrders.length === 0" class="empty-state">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <p>Нет активных доставок. Отличная работа!</p>
                </div>
                <div v-else class="orders-grid">
                    <div v-for="order in activeOrders" :key="order.id" class="order-card" @click="openOrderDetails(order)">
                        <div class="order-header">
                            <div class="order-number"><i class="fa-solid fa-receipt"></i><span>Заказ #{{ order.id }}</span></div>
                            <span class="status-badge" :class="getStatusInfo(order.status).class">{{ getStatusInfo(order.status).label }}</span>
                        </div>
                        <div class="order-meta">
                            <div class="meta-item"><i class="fa-solid fa-calendar"></i> {{ formatDate(order.created_at) }}</div>
                            <div class="meta-item"><i class="fa-solid fa-clock"></i> {{ formatTime(order.created_at) }}</div>
                        </div>
                        <div v-if="getOrderProducts(order).length > 0" class="order-products">
                            <div class="products-header"><i class="fa-solid fa-bag-shopping"></i><span>Товары ({{ getOrderProducts(order).length }})</span></div>
                            <ul class="products-list">
                                <li v-for="(product, idx) in getDisplayedProducts(order)" :key="idx">
                                    <span class="prod-qty">{{ product.count }}×</span> {{ product.title || product.name || 'Товар' }}
                                </li>
                            </ul>
                        </div>
                        <div class="customer-info">
                            <div class="info-row"><i class="fa-solid fa-user"></i> {{ order.receiver_name || 'Гость' }}</div>
                            <div class="info-row"><i class="fa-solid fa-phone"></i> {{ order.receiver_phone || 'Нет телефона' }}</div>
                            <div class="info-row address-row"><i class="fa-solid fa-location-dot"></i> <span>{{ order.address || 'Адрес не указан' }}</span></div>
                            <a v-if="order.lat && order.lng" :href="getYandexMapUrl(order)" target="_blank" class="btn-map-link">
                                <i class="fa-solid fa-map-location-dot"></i> Показать на Яндекс.Карте
                            </a>
                        </div>
                        <div v-if="order.info" class="order-info-html" v-html="order.info"></div>
                        <div class="order-price-row">
                            <div class="price-block">
                                <span class="price-label">Сумма заказа:</span>
                                <span class="price-value">{{ formatPrice(order.summary_price) }}</span>
                            </div>
                            <div class="earning-block">
                                <i class="fa-solid fa-coins"></i>
                                <span>+{{ formatPrice(order.delivery_price) }} ₽</span>
                            </div>
                        </div>
                        <div class="order-actions" @click.stop>
                            <a :href="'tel:' + order.receiver_phone" class="btn-action btn-call" v-if="order.receiver_phone">
                                <i class="fa-solid fa-phone"></i> Позвонить
                            </a>
                            <button class="btn-action btn-chat" @click="openChatModal(order)">
                                <i class="fa-solid fa-comments"></i> Чат
                            </button>
                            <button class="btn-action btn-status" @click="openStatusModal(order)">
                                <i class="fa-solid fa-rotate"></i> Статус
                            </button>
                            <button class="btn-action btn-complete" @click="handleConfirmDelivery(order.id)">
                                <i class="fa-solid fa-check"></i> Доставлен
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вкладка: Завершенные -->
            <div v-if="activeTab === 'completed'" class="tab-content">
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ -->
                <!-- 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ (Заменить во всех 3 вкладках: available, active, completed) -->
                <div class="date-filter-bar">
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-day"></i> С:</label>
                        <input type="date" v-model="dateFrom" @change="reloadCurrentTab" class="date-input">
                    </div>
                    <div class="filter-group">
                        <label><i class="fa-solid fa-calendar-check"></i> По:</label>
                        <input type="date" v-model="dateTo" @change="reloadCurrentTab" class="date-input">
                    </div>

                    <!-- 🆕 Группа кнопок действий -->
                    <div class="filter-actions">
                        <button class="btn-reset-dates" @click="resetToToday" title="Показать только сегодняшние заказы">
                            <i class="fa-solid fa-rotate-left"></i> Сегодня
                        </button>
                        <button class="btn-load-orders" @click="reloadCurrentTab" :disabled="isLoadingTab">
                            <i v-if="isLoadingTab" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-rotate"></i>
                            <span>Загрузить</span>
                        </button>
                    </div>
                </div>

                <div v-if="completedOrders.length === 0" class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>История доставок пуста</p>
                </div>
                <div v-else class="orders-grid">
                    <div v-for="order in completedOrders" :key="order.id" class="order-card completed-order" @click="openOrderDetails(order)">
                        <div class="order-header">
                            <div class="order-number"><i class="fa-solid fa-receipt"></i><span>Заказ #{{ order.id }}</span></div>
                            <span class="status-badge completed"><i class="fa-solid fa-check me-1"></i> Доставлен</span>
                        </div>
                        <div class="order-meta">
                            <div class="meta-item"><i class="fa-solid fa-calendar"></i> {{ formatDate(order.delivered_at || order.created_at) }}</div>
                            <div class="meta-item"><i class="fa-solid fa-clock"></i> {{ formatTime(order.delivered_at || order.created_at) }}</div>
                        </div>
                        <div class="customer-info">
                            <div class="info-row"><i class="fa-solid fa-store"></i> {{ order.tenant?.name }}</div>
                            <div class="info-row address-row"><i class="fa-solid fa-location-dot"></i> <span>{{ order.address || 'Адрес не указан' }}</span></div>
                        </div>
                        <div class="order-price-row">
                            <div class="price-block">
                                <span class="price-label">Заработано:</span>
                                <span class="price-value text-success">+{{ formatPrice(order.delivery_price) }} ₽</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 🆕 МОДАЛКА: НАСТРОЙКИ АВТООБНОВЛЕНИЯ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showSettingsModal" class="modal-overlay" @click.self="showSettingsModal = false">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3><i class="fa-solid fa-gear text-primary me-2"></i> Настройки дашборда</h3>
                        <button class="modal-close" @click="showSettingsModal = false"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <!-- Автообновление -->
                        <div class="setting-row">
                            <div class="setting-info">
                                <div class="setting-title">Автообновление заказов</div>
                                <div class="setting-desc">Периодически проверять наличие новых заказов</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="deliverySettings.auto_refresh" @change="saveSettings">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <!-- Интервал обновления -->
                        <div class="setting-row" v-if="deliverySettings.auto_refresh">
                            <div class="setting-info">
                                <div class="setting-title">Интервал обновления</div>
                                <div class="setting-desc">Минимум 30 секунд</div>
                            </div>
                            <div class="setting-input-group">
                                <input
                                    type="number"
                                    v-model.number="deliverySettings.refresh_interval"
                                    @change="saveSettings"
                                    min="30"
                                    class="setting-input"
                                >
                                <span class="input-suffix">сек</span>
                            </div>
                        </div>

                        <!-- Звуковые уведомления -->
                        <div class="setting-row">
                            <div class="setting-info">
                                <div class="setting-title">Звуковые уведомления</div>
                                <div class="setting-desc">Звук при появлении новых заказов</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="deliverySettings.sound_enabled" @change="saveSettings">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary-modern w-100" @click="showSettingsModal = false">Закрыть</button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: ПОЛНЫЕ ДЕТАЛИ ЗАКАЗА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showDetailsModal && currentOrder" class="modal-overlay" @click.self="closeOrderDetails">
                <div class="modal-container modal-lg">
                    <div class="modal-header">
                        <h3><i class="fa-solid fa-receipt text-primary me-2"></i> Детали заказа #{{ currentOrder.id }}</h3>
                        <button class="modal-close" @click="closeOrderDetails"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="detail-section">
                            <h4 class="section-title"><i class="fa-solid fa-user"></i> Клиент</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Имя:</span>
                                    <span class="detail-value">{{ currentOrder.receiver_name || currentOrder.tenant_user?.name || 'Гость' }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Телефон:</span>
                                    <a :href="'tel:' + currentOrder.receiver_phone" class="detail-value link-primary">
                                        {{ currentOrder.receiver_phone || 'Нет телефона' }} <i class="fa-solid fa-phone ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h4 class="section-title"><i class="fa-solid fa-location-dot"></i> Адрес доставки</h4>
                            <p class="detail-address">{{ currentOrder.address || 'Адрес не указан' }}</p>
                            <a v-if="currentOrder.lat && currentOrder.lng" :href="getYandexMapUrl(currentOrder)" target="_blank" class="btn-map-link modal-map-btn">
                                <i class="fa-solid fa-map-location-dot"></i> Открыть в Яндекс.Картах
                            </a>
                        </div>
                        <div v-if="currentOrder.info" class="detail-section">
                            <h4 class="section-title"><i class="fa-solid fa-circle-info"></i> Информация по заказу</h4>
                            <div class="info-html-box" v-html="currentOrder.info"></div>
                        </div>
                        <div class="detail-section">
                            <h4 class="section-title"><i class="fa-solid fa-bag-shopping"></i> Состав заказа</h4>
                            <div class="products-list-modal">
                                <div v-for="(item, idx) in getOrderProducts(currentOrder)" :key="idx" class="product-row-modal">
                                    <span class="prod-qty">{{ item.count }}×</span>
                                    <span class="prod-name">{{ item.title || item.name || 'Товар' }}</span>
                                    <span class="prod-price">{{ formatPrice(item.price) }} ₽</span>
                                </div>
                                <div v-if="getOrderProducts(currentOrder).length === 0" class="text-muted text-center py-3">Состав заказа не указан</div>
                            </div>
                        </div>

                        <div v-if="currentOrder.dialog_id" class="detail-section">
                            <div class="chat-actions-header">
                                <h4 class="section-title"><i class="fa-solid fa-comments"></i> Сообщение клиенту</h4>
                            </div>
                            <textarea v-model="quickMessage" rows="3" placeholder="Например: Я подъехал, жду у двери..." class="message-input-sm" :disabled="isSendingMessage"></textarea>
                            <div class="quick-templates-sm">
                                <button @click="quickMessage = 'Здравствуйте! Я ваш курьер, уже выехал к вам 🛵'" :disabled="isSendingMessage" class="template-chip">Выехал</button>
                                <button @click="quickMessage = 'Я на месте, жду вас у подъезда 📍'" :disabled="isSendingMessage" class="template-chip">На месте</button>
                                <button @click="quickMessage = 'Не могу найти подъезд, подскажите, как пройти? 🤔'" :disabled="isSendingMessage" class="template-chip">Не могу найти</button>
                                <button @click="quickMessage = 'Заказ доставлен! Оставьте, пожалуйста, отзыв ⭐'" :disabled="isSendingMessage" class="template-chip">Доставлен</button>
                            </div>
                            <button @click="sendQuickMessage" :disabled="isSendingMessage || !quickMessage.trim()" class="btn-send-sm">
                                <i v-if="isSendingMessage" class="fa-solid fa-spinner fa-spin"></i>
                                <span v-else><i class="fa-solid fa-paper-plane me-1"></i> Отправить сообщение</span>
                            </button>
                        </div>
                        <div v-else class="detail-section text-muted text-center py-3">
                            <i class="fa-solid fa-comment-slash me-1"></i> У этого заказа нет привязанного чата
                        </div>

                        <div class="modal-total">
                            <div class="total-row">
                                <span>Сумма заказа:</span>
                                <span>{{ formatPrice(currentOrder.summary_price || currentOrder.order_price) }} ₽</span>
                            </div>
                            <div class="total-row earning-row">
                                <span>Ваш заработок:</span>
                                <span>+{{ formatPrice(currentOrder.delivery_price) }} ₽</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary-modern" @click="closeOrderDetails">Закрыть</button>
                        <button class="btn-primary-modern" @click="handleConfirmDelivery(currentOrder.id)" v-if="activeTab === 'active'">
                            <i class="fa-solid fa-check me-1"></i> Подтвердить доставку
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- 🆕 МОДАЛКА: ПОЛНОЦЕННЫЙ ЧАТ С ПОДГРУЗКОЙ -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showChatModal && currentChatOrder" class="modal-overlay chat-modal-overlay" @click.self="closeChatModal">
                <div class="modal-container chat-modal-container">
                    <div class="chat-modal-header">
                        <div class="chat-header-info">
                            <div class="chat-avatar">
                                <span>{{ getInitials(currentChatOrder.receiver_name || 'Клиент') }}</span>
                            </div>
                            <div class="chat-header-text">
                                <div class="chat-header-name">{{ currentChatOrder.receiver_name || 'Клиент' }}</div>
                                <div class="chat-header-order">Заказ #{{ currentChatOrder.id }}</div>
                            </div>
                        </div>
                        <button class="modal-close" @click="closeChatModal"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <div ref="chatMessagesContainer" class="chat-messages-area">
                        <div v-if="isChatLoading" class="chat-loader">
                            <div class="loader-spinner"></div>
                            <span>Загрузка истории...</span>
                        </div>
                        <div v-else-if="chatMessages.length === 0" class="empty-chat-state">
                            <i class="fa-solid fa-comments"></i>
                            <p>История сообщений пуста</p>
                        </div>
                        <div v-else class="messages-list">
                            <div v-for="msg in chatMessages" :key="msg.id" class="message-bubble" :class="{ 'is-mine': isMyMessage(msg) }">
                                <div class="message-content">
                                    <div v-if="!isMyMessage(msg) && getSenderName(msg)" class="sender-name">
                                        {{ getSenderName(msg) }}
                                    </div>
                                    <div class="message-text" v-html="msg.message"></div>
                                    <div class="message-meta">
                                        <span class="message-time">{{ formatMessageTime(msg.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <textarea
                            v-model="newChatMessage"
                            rows="2"
                            placeholder="Напишите сообщение..."
                            class="chat-textarea"
                            :disabled="isSendingChatMessage"
                            @keydown.enter.exact.prevent="sendChatMessage"
                        ></textarea>
                        <button class="chat-send-btn" @click="sendChatMessage" :disabled="isSendingChatMessage || !newChatMessage.trim()">
                            <i v-if="isSendingChatMessage" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ========================================== -->
        <!-- МОДАЛКА: СМЕНА СТАТУСА -->
        <!-- ========================================== -->
        <transition name="modal-fade">
            <div v-if="showStatusModal && statusOrder" class="modal-overlay" @click.self="closeStatusModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Смена статуса заказа #{{ statusOrder.id }}</h3>
                        <button class="modal-close" @click="closeStatusModal"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="section-label">Выберите новый статус</label>
                            <select v-model="newStatus" class="modern-select">
                                <option :value="1">🛵 В доставке</option>
                                <option :value="2">✅ Доставлен</option>
                                <option :value="3">❌ Отменен / Проблема</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary-modern" @click="closeStatusModal">Отмена</button>
                        <button class="btn-primary-modern" @click="handleStatusChange" :disabled="isChangingStatus || !newStatus">
                            <span v-if="isChangingStatus"><i class="fa-solid fa-spinner fa-spin"></i></span>
                            <span v-else>Применить статус</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>

    <div v-else class="d-flex justify-content-center align-items-center" style="height: 100vh; background: #f9fafb;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <p class="text-muted">Проверка доступа...</p>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'pinia';
import { useDeliverymanStore } from '@/MobileClient/stores/deliveryman';
import { usePermissions } from '@/MobileClient/composables/usePermissions.js';
import axios from 'axios';

export default {
    name: 'DeliverymanDashboard',
    setup() {
        const { isAdmin } = usePermissions();
        return { isAdmin };
    },

    data() {
        // 🆕 Получаем сегодняшнюю дату в формате YYYY-MM-DD для input type="date"
        const today = new Date().toISOString().split('T')[0];
        // 🆕 Загружаем даты из localStorage или используем сегодня
        const savedDateFrom = localStorage.getItem('delivery_dateFrom');
        const savedDateTo = localStorage.getItem('delivery_dateTo');
        return {
            activeTab: 'available',

            // 🆕 Переменные для фильтра дат
            dateFrom: savedDateFrom || today,
            dateTo: savedDateTo || today,
            isLoadingTab: false,

            showSettingsModal: false,
            deliverySettings: {
                auto_refresh: false,
                refresh_interval: 30,
                sound_enabled: true
            },
            isSavingSettings: false,
            refreshTimer: null,
            previousAvailableCount: 0,

            showDetailsModal: false,
            showChatModal: false,
            showProfileModal: false,
            showStatusModal: false,
            currentOrder: null,
            currentChatOrder: null,

            chatMessages: [],
            isChatLoading: false,
            newChatMessage: '',
            isSendingChatMessage: false,

            statusOrder: null,
            newStatus: null,
            isChangingStatus: false,
            quickMessage: '',
            isSendingMessage: false,

            tabs: [
                { id: 'available', label: 'Доступные', icon: 'fa-solid fa-list' },
                { id: 'active', label: 'Мои текущие', icon: 'fa-solid fa-motorcycle' },
                { id: 'completed', label: 'Завершенные', icon: 'fa-solid fa-check-double' },
            ]
        };
    },

    computed: {
        ...mapState(useDeliverymanStore, [
            'deliveryman',
            'activeOrders',
            'availableOrders',
            'completedOrders',
            'isOnline'
        ]),

        deliverymanInitials() {
            return this.deliveryman.name ? this.deliveryman.name.substring(0, 2).toUpperCase() : 'КУ';
        },
    },

    watch: {
        chatMessages: {
            handler() {
                this.$nextTick(() => this.scrollToBottom());
            },
            deep: true
        },
        // 🆕 При смене вкладки автоматически применяем текущий фильтр дат
        activeTab() {
            this.reloadCurrentTab();
        },
        // 🆕 Сохраняем даты в localStorage при изменении
        dateFrom(newVal) { localStorage.setItem('delivery_dateFrom', newVal); },
        dateTo(newVal) { localStorage.setItem('delivery_dateTo', newVal); },

        // 🆕 Перезапускаем таймер при изменении настроек
        'deliverySettings.auto_refresh'() { this.manageAutoRefresh(); },
        'deliverySettings.refresh_interval'() { this.manageAutoRefresh(); },

    },

    created() {
        if (!this.isAdmin) {
            this.$router.push({ name: 'Auth' }).catch(() => {});
        }
    },

    methods: {
        ...mapActions(useDeliverymanStore, [
            'fetchDashboard',
            'toggleStatus',
            'acceptOrder',
            'confirmDelivery',
            'startLocationTracking',
            'stopLocationTracking'
        ]),

        // 🆕 Загрузка настроек с бэкенда
        async loadSettings() {
            try {
                const response = await axios.get('/deliveryman/settings');
                if (response.data.success) {
                    this.deliverySettings = { ...this.deliverySettings, ...response.data.data };
                }
            } catch (e) {
                console.error('Ошибка загрузки настроек:', e);
            }
        },

        // 🆕 Сохранение настроек на бэкенд
        async saveSettings() {
            // Принудительно ставим минимум 30 секунд
            if (this.deliverySettings.refresh_interval < 30) {
                this.deliverySettings.refresh_interval = 30;
            }

            this.isSavingSettings = true;
            try {
                await axios.post('/deliveryman/settings', this.deliverySettings);
                this.$notify?.({ title: 'Успех', text: 'Настройки сохранены', type: 'success' });
                this.manageAutoRefresh(); // Перезапускаем таймер с новыми значениями
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось сохранить настройки', type: 'error' });
            } finally {
                this.isSavingSettings = false;
            }
        },

        // 🆕 Управление автообновлением
        manageAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }

            if (this.deliverySettings.auto_refresh) {
                const intervalMs = Math.max(30, this.deliverySettings.refresh_interval) * 1000;
                this.refreshTimer = setInterval(() => {
                    this.checkForNewOrders();
                }, intervalMs);
            }
        },

        // 🆕 Проверка новых заказов и обновление
        async checkForNewOrders() {
            const store = useDeliverymanStore();
            const oldCount = this.previousAvailableCount || store.availableOrders.length;

            await this.reloadCurrentTab(); // Обновляем текущую вкладку

            // Если включен звук и появились новые заказы в "Доступных"
            if (this.deliverySettings.sound_enabled && this.activeTab === 'available') {
                const newCount = store.availableOrders.length;
                if (newCount > oldCount) {
                    this.playNotificationSound();
                }
            }
            this.previousAvailableCount = store.availableOrders.length;
        },

        // 🆕 Простой звуковой сигнал через Web Audio API (не требует внешних файлов)
        // 🆕 Воспроизведение вашего кастомного звука
        playNotificationSound() {
            if (!this.deliverySettings.sound_enabled) return;

            try {
                // 🎵 УКАЖИТЕ ЗДЕСЬ ПУТЬ К ВАШЕМУ ФАЙЛУ
                // Если файл лежит в public/sounds/new_order.mp3, путь будет '/sounds/new_order.mp3'
                const audio = new Audio('/sounds/new_order.mp3');

                // Можно настроить громкость (от 0.0 до 1.0)
                audio.volume = 0.6;

                // Запускаем воспроизведение
                audio.play().catch(e => {
                    // Браузеры иногда блокируют автовоспроизведение звука, если пользователь еще не взаимодействовал со страницей
                    console.warn('Не удалось воспроизвести звук (возможно, блокировка автовоспроизведения браузером):', e);
                });
            } catch (e) {
                console.error('Ошибка при попытке воспроизвести звук:', e);
            }
        },

        async reloadCurrentTab() {
            this.isLoadingTab = true;
            try {
                const params = {
                    date_from: this.dateFrom,
                    date_to: this.dateTo
                };

                const store = useDeliverymanStore();
                let response;

                if (this.activeTab === 'available') {
                    response = await axios.get('/deliveryman/orders/available', { params });
                    store.availableOrders = response.data.data;
                } else if (this.activeTab === 'active') {
                    response = await axios.get('/deliveryman/orders/active', { params });
                    store.activeOrders = response.data.data;
                } else if (this.activeTab === 'completed') {
                    response = await axios.get('/deliveryman/orders/completed', { params });
                    store.completedOrders = response.data.data;
                }
            } catch (error) {
                console.error('Ошибка загрузки заказов:', error);
            } finally {
                this.isLoadingTab = false;
            }
        },

        resetToToday() {
            const today = new Date().toISOString().split('T')[0];
            this.dateFrom = today;
            this.dateTo = today;
            this.reloadCurrentTab();
        },



        async openChatModal(order) {
            if (!order.dialog_id) {
                this.$notify?.({ title: 'Ошибка', text: 'У этого заказа нет привязанного чата', type: 'error' });
                return;
            }
            this.currentChatOrder = order;
            this.showChatModal = true;
            this.chatMessages = [];
            await this.loadChatMessages(order.dialog_id);
        },

        closeChatModal() {
            this.showChatModal = false;
            this.currentChatOrder = null;
            this.chatMessages = [];
            this.newChatMessage = '';
        },

        async loadChatMessages(dialogId) {
            this.isChatLoading = true;
            try {
                const response = await axios.get(`/deliveryman/dialogs/${dialogId}/messages`);
                if (response.data.success) {
                    this.chatMessages = response.data.data;
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {
                console.error('Ошибка загрузки чата:', e);
                this.$notify?.({ title: 'Ошибка', text: 'Не удалось загрузить историю сообщений', type: 'error' });
            } finally {
                this.isChatLoading = false;
            }
        },

        async sendChatMessage() {
            if (!this.newChatMessage.trim() || !this.currentChatOrder?.dialog_id) return;

            const textToSend = this.newChatMessage;
            this.newChatMessage = '';
            this.isSendingChatMessage = true;

            try {
                const response = await axios.post(`/deliveryman/dialogs/${this.currentChatOrder.dialog_id}/messages`, {
                    message: textToSend
                });

                if (response.data.success) {
                    this.chatMessages.push(response.data.data);
                    this.$notify?.({ title: 'Отправлено', text: 'Сообщение доставлено', type: 'success' });
                }
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.error || 'Не удалось отправить сообщение', type: 'error' });
                this.newChatMessage = textToSend;
            } finally {
                this.isSendingChatMessage = false;
            }
        },

        scrollToBottom() {
            const container = this.$refs.chatMessagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        isMyMessage(msg) {
            return msg.sender_type === 'deliveryman';
        },

        getSenderName(msg) {
            if (msg.sender_type === 'system') return 'Система';
            if (msg.sender_type === 'admin') return msg.meta?.sender_name || 'Администратор';
            if (msg.sender_type === 'user') return 'Клиент';
            return 'Неизвестно';
        },

        formatMessageTime(timestamp) {
            if (!timestamp) return '';
            return new Date(timestamp).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },

        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        },

        parseOrderInfo(infoString) {
            if (!infoString) return [];
            const lines = infoString.replace(/<br\s*\/?>/gi, '\n').split('\n').map(line => line.trim()).filter(line => line.length > 0);
            const parsed = [];
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i];
                const colonIndex = line.indexOf(':');
                if (colonIndex > 0 && colonIndex < 25) {
                    const key = line.substring(0, colonIndex).trim();
                    let value = line.substring(colonIndex + 1).trim();
                    if (!value && i + 1 < lines.length && lines[i + 1].indexOf(':') === -1) {
                        value = lines[i + 1].trim();
                        i++;
                    }
                    parsed.push({ type: 'kv', key, value });
                } else {
                    parsed.push({ type: 'note', text: line });
                }
            }
            return parsed;
        },

        getOrderProducts(order) {
            if (!order?.product_details) return [];
            const details = Array.isArray(order.product_details) ? order.product_details[0] : order.product_details;
            return Array.isArray(details?.products) ? details.products : [];
        },

        getDisplayedProducts(order) {
            return this.getOrderProducts(order).slice(0, 3);
        },

        getYandexMapUrl(order) {
            if (!order.lat || !order.lng) return '#';
            return `https://yandex.ru/maps/?pt=${order.lng},${order.lat}&z=16&l=map`;
        },

        openOrderDetails(order) {
            this.currentOrder = order;
            this.showDetailsModal = true;
        },

        closeOrderDetails() {
            this.showDetailsModal = false;
            this.currentOrder = null;
            this.quickMessage = '';
            this.isSendingMessage = false;
        },

        openStatusModal(order) {
            this.statusOrder = order;
            this.newStatus = order.status;
            this.showStatusModal = true;
        },

        closeStatusModal() {
            this.showStatusModal = false;
            this.statusOrder = null;
            this.newStatus = null;
        },

        async handleStatusChange() {
            if (!this.statusOrder || !this.newStatus) return;
            this.isChangingStatus = true;
            try {
                await axios.post(`/deliveryman/orders/${this.statusOrder.id}/status`, { status: this.newStatus });
                this.$notify?.({ title: 'Успех', text: 'Статус заказа обновлен', type: 'success' });
                this.closeStatusModal();
                await this.fetchDashboard();
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.error || 'Не удалось изменить статус', type: 'error' });
            } finally {
                this.isChangingStatus = false;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(price || 0);
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        formatTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        },

        getStatusInfo(status) {
            const map = {
                0: { label: 'Новый', class: 'new' },
                1: { label: 'В доставке', class: 'processing' },
                2: { label: 'Выполнен', class: 'completed' },
                3: { label: 'Отменен', class: 'cancelled' },
                4: { label: 'Готов к доставке', class: 'processing' },
                5: { label: 'Готовится', class: 'processing' },
            };
            return map[status] || { label: 'Неизвестно', class: 'new' };
        },

        async handleToggleOnline() {
            const newStatus = !this.isOnline;
            const success = await this.toggleStatus(newStatus);
            if (success) {
                if (newStatus) {
                    this.startLocationTracking();
                    this.$notify?.({ title: 'Вы на линии', text: 'Заказы начнут поступать', type: 'success' });
                } else {
                    this.stopLocationTracking();
                    this.$notify?.({ title: 'Вы офлайн', text: 'Новые заказы не поступают', type: 'info' });
                }
            }
        },

        async handleAcceptOrder(orderId) {
            try {
                await this.acceptOrder(orderId);
                this.$notify?.({ title: 'Успех', text: 'Заказ принят в работу', type: 'success' });
                this.activeTab = 'active';
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.error || 'Не удалось принять заказ', type: 'error' });
            }
        },

        async handleConfirmDelivery(orderId) {
            if (!confirm('Подтвердить доставку заказа?')) return;
            try {
                await this.confirmDelivery(orderId);
                this.closeOrderDetails();
                this.$notify?.({ title: 'Отлично!', text: 'Доставка подтверждена, средства начислены', type: 'success' });
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.error || 'Ошибка подтверждения', type: 'error' });
            }
        },

        async sendQuickMessage() {
            if (!this.currentOrder?.dialog_id || !this.quickMessage.trim()) return;
            this.isSendingMessage = true;
            try {
                await axios.post(`/deliveryman/orders/${this.currentOrder.id}/message`, { message: this.quickMessage });
                this.$notify?.({ title: 'Отправлено', text: 'Сообщение доставлено клиенту', type: 'success' });
                this.quickMessage = '';
            } catch (e) {
                this.$notify?.({ title: 'Ошибка', text: e.response?.data?.error || 'Не удалось отправить сообщение', type: 'error' });
            } finally {
                this.isSendingMessage = false;
            }
        }
    },

    mounted() {
        this.fetchDashboard();
        if (this.isOnline) this.startLocationTracking();
    },

    beforeUnmount() {
        this.stopLocationTracking();
    }
};
</script>

<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.deliveryman-dashboard {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 40px;
}

// ... (Здесь оставьте все ваши существующие стили без изменений) ...

// 🆕 ИСПРАВЛЕННЫЙ СЕЛЕКТОР (был пропущен точку в вашем коде)
.chat-modal-overlay {
    align-items: center;
}

.chat-modal-container {
    max-width: 500px;
    height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;
    background: $card-bg;
    flex-shrink: 0;

    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .chat-header-text {
        .chat-header-name {
            font-weight: 700;
            font-size: 1rem;
            color: $text;
        }
        .chat-header-order {
            font-size: 0.8rem;
            color: $text-muted;
        }
    }
}

.chat-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: $bg;
    display: flex;
    flex-direction: column;
    gap: 12px;

    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-thumb {
        background: $border;
        border-radius: 3px;
    }
}

.chat-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: $text-muted;
    font-size: 0.9rem;
    height: 100%;

    .loader-spinner {
        width: 24px;
        height: 24px;
        border: 2px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
}

.empty-chat-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: $text-muted;
    gap: 8px;

    i {
        font-size: 2rem;
        opacity: 0.3;
    }
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-bubble {
    display: flex;
    max-width: 85%;
    animation: fadeIn 0.2s ease;

    &.is-mine {
        align-self: flex-end;

        .message-content {
            background: $primary;
            color: white;
            border-bottom-right-radius: 4px;

            .message-time {
                color: rgba(255, 255, 255, 0.7);
            }
        }
    }

    &:not(.is-mine) {
        align-self: flex-start;

        .message-content {
            background: $card-bg;
            border: 1px solid $border;
            border-bottom-left-radius: 4px;
        }
    }
}

.message-content {
    padding: 10px 14px;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    max-width: 100%;
}

.sender-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: $primary;
    margin-bottom: 4px;
}

.message-text {
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-meta {
    display: flex;
    justify-content: flex-end;
    margin-top: 4px;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.chat-input-area {
    padding: 12px 16px;
    background: $card-bg;
    border-top: 1px solid $border;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    flex-shrink: 0;
}

.chat-textarea {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid $border;
    border-radius: 20px;
    resize: none;
    font-family: inherit;
    font-size: 0.9rem;
    background: $bg;
    max-height: 100px;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 2px rgba($primary, 0.1);
    }
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $primary;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: scale(1.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
    .chat-modal-overlay {
        padding: 0;
        align-items: flex-end;
    }
    .chat-modal-container {
        max-width: 100%;
        height: 95vh;
        border-radius: 20px 20px 0 0;
    }
}

// ==========================================
// 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ
// ==========================================
.date-filter-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);

    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: $text-muted;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid $border;
            border-radius: 8px;
            font-size: 0.9rem;
            color: $text;
            background: $bg;
            cursor: pointer;
            transition: all 0.2s;

            &:focus {
                outline: none;
                border-color: $primary;
                box-shadow: 0 0 0 3px rgba($primary, 0.1);
            }
        }
    }

    .btn-reset-dates {
        margin-left: auto;
        padding: 8px 16px;
        background: rgba($primary, 0.08);
        color: $primary;
        border: 1px solid rgba($primary, 0.2);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;

        &:hover {
            background: rgba($primary, 0.15);
            border-color: $primary;
        }

        &:active {
            transform: scale(0.98);
        }
    }
}

@media (max-width: 640px) {
    .date-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;

        .filter-group {
            justify-content: space-between;
        }

        .btn-reset-dates {
            margin-left: 0;
            justify-content: center;
            width: 100%;
        }
    }
}
</style>



<style lang="scss" scoped>
$primary: #3b82f6;
$primary-dark: #2563eb;
$success: #10b981;
$danger: #ef4444;
$warning: #f59e0b;
$text: #1f2937;
$text-muted: #6b7280;
$border: #e5e7eb;
$bg: #f9fafb;
$card-bg: #ffffff;

.deliveryman-dashboard {
    min-height: 100vh;
    background: $bg;
    padding-bottom: 40px;
}

.deliveryman-hero {
    position: relative;
    padding: 32px 24px 40px;
    background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
    color: white;
    transition: background 0.3s ease;

    &.is-offline {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    }
}

.hero-background {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
}

.deliveryman-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.deliveryman-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
}

.deliveryman-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.deliveryman-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    opacity: 0.95;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 12px;
    transition: background 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.1);
    }
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-online .status-dot {
    background: #4ade80;
    box-shadow: 0 0 8px #4ade80;
}

.status-offline .status-dot {
    background: #f87171;
    box-shadow: 0 0 8px #f87171;
}

// 🆕 Контейнер для рейтинга и настроек в одну строку
.deliveryman-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    flex-wrap: wrap;
}

.deliveryman-rating-small {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.15);
    padding: 4px 10px;
    border-radius: 12px;
    width: fit-content;

    i {
        color: #fbbf24;
        font-size: 0.75rem;
    }
}

// 🆕 Маленькая заблокированная кнопка настроек
.settings-btn-small {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 4px 12px;
    border-radius: 12px;
    cursor: not-allowed;
    transition: all 0.2s;

    i {
        font-size: 0.7rem;
        opacity: 0.7;
    }

    // Визуальный индикатор "заблокировано"
    &:disabled {
        opacity: 0.5;
        pointer-events: none; // Полностью блокируем клики и hover
    }
}



.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    cursor: pointer;
    transition: 0.2s;

    &:hover {
        background: rgba(255, 255, 255, 0.18);
        transform: translateY(-2px);
    }
}

.metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.metric-info {
    flex: 1;
}

.metric-label {
    font-size: 0.7rem;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.metric-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1;
}

.nav-wrapper {
    background: white;
    border-bottom: 1px solid $border;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.nav-tabs {
    display: flex;
    gap: 4px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 8px 24px;
    overflow-x: auto;

    &::-webkit-scrollbar {
        display: none;
    }
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: $text-muted;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;

    &:hover {
        background: rgba($primary, 0.05);
        color: $primary;
    }

    &.is-active {
        background: rgba($primary, 0.1);
        color: $primary;
    }
}

.nav-badge {
    padding: 2px 8px;
    background: $primary;
    color: white;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 700;
}

.dashboard-content {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 24px;
}

.tab-content {
    animation: fadeIn 0.3s ease;
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.order-card {
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
        border-color: $primary;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    &.available-order {
        border-left: 4px solid $warning;
    }
}

.completed-order {
    opacity: 0.85;
    border-left: 4px solid $success !important;

    &:hover {
        opacity: 1;
        transform: translateY(-2px);
    }
}

.order-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.order-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $text;

    i {
        color: $primary;
        font-size: 1rem;
    }
}

.order-distance {
    font-size: 0.8rem;
    font-weight: 600;
    color: $text-muted;
    background: $bg;
    padding: 4px 8px;
    border-radius: 6px;
}

.order-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid $border;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: $text-muted;

    i {
        font-size: 0.75rem;
    }
}

.order-products {
    margin-bottom: 12px;
}

.products-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: $text;
    margin-bottom: 8px;

    i {
        color: $primary;
        font-size: 0.9rem;
    }
}

.products-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;

    li {
        font-size: 0.85rem;
        color: $text-muted;
        padding-left: 16px;
        position: relative;
        line-height: 1.4;

        &::before {
            content: '•';
            position: absolute;
            left: 4px;
            color: $primary;
        }
    }
}

.prod-qty {
    font-weight: 700;
    color: $primary;
    margin-right: 4px;
}

.customer-info {
    background: $bg;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 12px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: $text;
    margin-bottom: 4px;

    &:last-child {
        margin-bottom: 0;
    }

    i {
        color: $text-muted;
        width: 16px;
        text-align: center;
    }
}

.address-row {
    font-weight: 600;
    color: $text;
}

.btn-map-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: $primary;
    text-decoration: none;
    padding: 6px 10px;
    background: rgba($primary, 0.08);
    border-radius: 6px;
    margin-top: 6px;
    transition: all 0.2s;
    width: fit-content;

    &:hover {
        background: rgba($primary, 0.15);
        color: $primary-dark;
        transform: translateX(2px);
    }
}

.modal-map-btn {
    margin-top: 12px;
    font-size: 0.9rem;
    padding: 8px 14px;
}

.order-info-html, .info-html-box {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-left: 4px solid $warning;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 0.9rem;
    color: $text;
    line-height: 1.5;
    margin-top: 8px;

    :deep(b), :deep(strong) {
        color: $text;
        font-weight: 700;
    }

    :deep(br) {
        margin-bottom: 4px;
    }
}

.info-html-box {
    background: $bg;
    border: 1px solid $border;
    border-left: 4px solid $primary;
}

.order-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-top: 1px solid $border;
    border-bottom: 1px solid $border;
    margin-bottom: 12px;
}

.price-block {
    display: flex;
    flex-direction: column;
}

.price-label {
    font-size: 0.8rem;
    color: $text-muted;
}

.price-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: $text;
}

.earning-block {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.95rem;
    font-weight: 700;
    color: $success;
    background: rgba($success, 0.1);
    padding: 6px 12px;
    border-radius: 8px;
}

.order-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.btn-action {
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: 0.2s;
    text-decoration: none;

    &.btn-call, &.btn-chat {
        background: $bg;
        color: $text;
        border: 1px solid $border;

        &:hover {
            background: $border;
        }
    }

    &.btn-status {
        background: rgba($warning, 0.1);
        color: $warning;
        border: 1px solid rgba($warning, 0.2);

        &:hover {
            background: $warning;
            color: white;
        }
    }

    &.btn-complete {
        background: $success;
        color: white;
        grid-column: span 2;

        &:hover {
            background: #059669;
        }
    }

    &.btn-accept {
        background: $primary;
        color: white;
        grid-column: span 2;

        &:hover {
            background: $primary-dark;
        }
    }
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;

    &.new {
        background: rgba($primary, 0.1);
        color: $primary;
    }

    &.processing {
        background: rgba($warning, 0.1);
        color: $warning;
    }

    &.completed {
        background: rgba($success, 0.1);
        color: $success;
    }

    &.cancelled {
        background: rgba($danger, 0.1);
        color: $danger;
    }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: $text-muted;

    i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(31, 41, 55, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-container {
    background: $card-bg;
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    animation: modalSlideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-container.modal-lg {
    max-width: 600px;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid $border;

    h3 {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        color: $text;
        display: flex;
        align-items: center;
    }
}

.modal-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: $bg;
    border: none;
    color: $text-muted;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: $danger;
        color: white;
        transform: rotate(90deg);
    }
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    gap: 12px;
    flex-direction: column;
    padding: 16px 24px;
    border-top: 1px solid $border;
    background: $bg;

    & > * {
        width: 100%;
    }
}

.detail-section {
    margin-bottom: 20px;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: $text-muted;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;

    i {
        color: $primary;
    }
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 0.8rem;
    color: $text-muted;
}

.detail-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: $text;
}

.link-primary {
    color: $primary;
    text-decoration: none;

    &:hover {
        text-decoration: underline;
    }
}

.detail-address {
    font-size: 1rem;
    font-weight: 600;
    color: $text;
    background: $bg;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid $primary;
    margin: 0;
}

.products-list-modal {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid $border;
    border-radius: 8px;
}

.product-row-modal {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid $border;
    font-size: 0.9rem;

    &:last-child {
        border-bottom: none;
    }
}

.prod-name {
    flex: 1;
    color: $text;
}

.prod-price {
    font-weight: 700;
    color: $text;
    white-space: nowrap;
}

.modal-total {
    background: $bg;
    border-radius: 8px;
    padding: 16px;
    margin-top: 8px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    margin-bottom: 8px;

    &:last-child {
        margin-bottom: 0;
    }
}

.earning-row {
    font-weight: 700;
    color: $success;
    font-size: 1.1rem;
    border-top: 1px dashed $border;
    padding-top: 8px;
    margin-top: 8px;
}

.chat-embed-placeholder {
    height: 350px;
    background: $bg;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px dashed $border;
}

.mock-chat {
    width: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mock-message {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 0.9rem;
    max-width: 80%;

    &.system {
        background: $warning;
        color: white;
        align-self: center;
        font-size: 0.8rem;
    }

    &.client {
        background: white;
        border: 1px solid $border;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
}

.modern-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid $border;
    border-radius: 10px;
    font-size: 0.95rem;
    background: $card-bg;
    color: $text;
    cursor: pointer;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.btn-primary-modern, .btn-secondary-modern {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    flex: 1;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-primary-modern {
    background: $primary;
    color: white;

    &:hover:not(:disabled) {
        background: $primary-dark;
    }
}

.btn-secondary-modern {
    background: $card-bg;
    color: $text;
    border: 1px solid $border;

    &:hover:not(:disabled) {
        background: $bg;
        border-color: $primary;
        color: $primary;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .modal-overlay {
        padding: 0;
        align-items: flex-end;
    }
    .modal-container {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 20px 20px 0 0;
    }
    .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .detail-grid {
        grid-template-columns: 1fr;
    }
    .order-actions {
        grid-template-columns: 1fr;
    }
    .btn-action.btn-complete, .btn-action.btn-accept {
        grid-column: span 1;
    }
}

// --- Красивый парсинг информации о заказе ---
.order-info-parsed {
    background: $bg;
    border: 1px solid $border;
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 8px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    font-size: 0.9rem;
    line-height: 1.4;
}

// Стиль для пар "Ключ: Значение"
.info-kv {
    justify-content: space-between;
    gap: 12px;

    .info-key {
        color: $text-muted;
        font-weight: 500;
        white-space: nowrap;
        flex-shrink: 0; // Ключ не будет переноситься на новую строку
    }

    .info-value {
        color: $text;
        font-weight: 600;
        text-align: right;
        word-break: break-word; // Значение может переноситься, если длинное
    }
}

// Стиль для общих заметок (например, про подъезд или сдачу)
.info-note {
    background: rgba($warning, 0.08);
    border-left: 3px solid $warning;
    padding: 8px 12px;
    border-radius: 6px;

    .info-note-text {
        color: #92400e; // Темно-оранжевый для читаемости
        font-weight: 600;
        display: flex;
        align-items: center;
        width: 100%;
    }
}

// --- Стили для блока быстрых сообщений ---
.chat-actions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;

    .section-title {
        margin-bottom: 0;
    }
}

.btn-open-full-chat {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba($primary, 0.1);
    color: $primary;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;

    &:hover {
        background: $primary;
        color: white;
        transform: translateY(-1px);
    }
}

.message-input-sm {
    width: 100%;
    padding: 12px;
    border: 1px solid $border;
    border-radius: 10px;
    resize: none;
    font-family: inherit;
    font-size: 0.95rem;
    margin-bottom: 12px;
    transition: all 0.2s;
    background: $card-bg;
    box-sizing: border-box;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.quick-templates-sm {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.template-chip {
    padding: 6px 12px;
    background: $bg;
    border: 1px solid $border;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: $text;
    cursor: pointer;
    transition: all 0.2s;

    &:hover:not(:disabled) {
        background: rgba($primary, 0.1);
        border-color: $primary;
        color: $primary;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.btn-send-sm {
    width: 100%;
    padding: 12px;
    background: $primary;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
}

chat-modal-overlay {
    align-items: center; // Центрируем по вертикали
}

.chat-modal-container {
    max-width: 500px;
    height: 80vh; // Фиксированная высота для модалки
    display: flex;
    flex-direction: column;
    overflow: hidden; // Чтобы скроллился только список сообщений
}

.chat-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid $border;
    background: $card-bg;
    flex-shrink: 0;

    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, $primary 0%, $primary-dark 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .chat-header-text {
        .chat-header-name {
            font-weight: 700;
            font-size: 1rem;
            color: $text;
        }
        .chat-header-order {
            font-size: 0.8rem;
            color: $text-muted;
        }
    }
}

.chat-messages-area {
    flex: 1; // Занимает всё доступное пространство
    overflow-y: auto;
    padding: 16px;
    background: $bg;
    display: flex;
    flex-direction: column;
    gap: 12px;

    &::-webkit-scrollbar {
        width: 6px;
    }
    &::-webkit-scrollbar-thumb {
        background: $border;
        border-radius: 3px;
    }
}

.chat-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: $text-muted;
    font-size: 0.9rem;
    height: 100%;

    .loader-spinner {
        width: 24px;
        height: 24px;
        border: 2px solid $border;
        border-top-color: $primary;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
}

.empty-chat-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: $text-muted;
    gap: 8px;

    i {
        font-size: 2rem;
        opacity: 0.3;
    }
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-bubble {
    display: flex;
    max-width: 85%;
    animation: fadeIn 0.2s ease;

    &.is-mine {
        align-self: flex-end;

        .message-content {
            background: $primary;
            color: white;
            border-bottom-right-radius: 4px;

            .message-time {
                color: rgba(255, 255, 255, 0.7);
            }
        }
    }

    &:not(.is-mine) {
        align-self: flex-start;

        .message-content {
            background: $card-bg;
            border: 1px solid $border;
            border-bottom-left-radius: 4px;
        }
    }
}

.message-content {
    padding: 10px 14px;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    max-width: 100%;
}

.sender-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: $primary;
    margin-bottom: 4px;
}

.message-text {
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-meta {
    display: flex;
    justify-content: flex-end;
    margin-top: 4px;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.chat-input-area {
    padding: 12px 16px;
    background: $card-bg;
    border-top: 1px solid $border;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    flex-shrink: 0;
}

.chat-textarea {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid $border;
    border-radius: 20px;
    resize: none;
    font-family: inherit;
    font-size: 0.9rem;
    background: $bg;
    max-height: 100px;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 2px rgba($primary, 0.1);
    }
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $primary;
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;

    &:hover:not(:disabled) {
        background: $primary-dark;
        transform: scale(1.05);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

// Адаптив для модалки чата на мобильных
@media (max-width: 640px) {
    .chat-modal-overlay {
        padding: 0;
        align-items: flex-end;
    }
    .chat-modal-container {
        max-width: 100%;
        height: 95vh;
        border-radius: 20px 20px 0 0;
    }
}

// ==========================================
// 🆕 ПАНЕЛЬ ФИЛЬТРА ПО ДАТАМ
// ==========================================
.date-filter-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);

    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: $text-muted;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid $border;
            border-radius: 8px;
            font-size: 0.9rem;
            color: $text;
            background: $bg;
            cursor: pointer;
            transition: all 0.2s;

            &:focus {
                outline: none;
                border-color: $primary;
                box-shadow: 0 0 0 3px rgba($primary, 0.1);
            }
        }
    }

    .btn-reset-dates {
        margin-left: auto; // Прижимает кнопку вправо
        padding: 8px 16px;
        background: rgba($primary, 0.08);
        color: $primary;
        border: 1px solid rgba($primary, 0.2);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;

        &:hover {
            background: rgba($primary, 0.15);
            border-color: $primary;
        }

        &:active {
            transform: scale(0.98);
        }
    }
}

// Адаптив для мобильных
@media (max-width: 640px) {
    .date-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;

        .filter-group {
            justify-content: space-between;
        }

        .btn-reset-dates {
            margin-left: 0;
            justify-content: center;
            width: 100%;
        }
    }
}

// 🆕 Стили для кнопки настроек в шапке
.settings-btn-header {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;

    &:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
    }
}

// 🆕 Стили для модалки настроек
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: 1px solid $border;

    &:last-child {
        border-bottom: none;
    }
}

.setting-info {
    flex: 1;
    padding-right: 16px;
}

.setting-title {
    font-weight: 600;
    color: $text;
    margin-bottom: 4px;
}

.setting-desc {
    font-size: 0.8rem;
    color: $text-muted;
}

// Переключатель (Toggle Switch)
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;

    input {
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .toggle-slider {
            background-color: $primary;
        }

        &:checked + .toggle-slider:before {
            transform: translateX(22px);
        }
    }
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: $border;
    transition: .3s;
    border-radius: 34px;

    &:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
}

// Поле ввода интервала
.setting-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.setting-input {
    width: 80px;
    padding: 8px 12px;
    border: 1px solid $border;
    border-radius: 8px;
    font-size: 0.95rem;
    text-align: center;
    background: $bg;

    &:focus {
        outline: none;
        border-color: $primary;
        box-shadow: 0 0 0 3px rgba($primary, 0.1);
    }
}

.input-suffix {
    font-size: 0.9rem;
    color: $text-muted;
    font-weight: 500;
}

// ==========================================
// 🆕 ОБНОВЛЕННЫЕ СТИЛИ ПАНЕЛИ ФИЛЬТРА
// ==========================================
.date-filter-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: $card-bg;
    border: 1px solid $border;
    border-radius: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);

    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: $text-muted;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid $border;
            border-radius: 8px;
            font-size: 0.9rem;
            color: $text;
            background: $bg;
            cursor: pointer;
            transition: all 0.2s;

            &:focus {
                outline: none;
                border-color: $primary;
                box-shadow: 0 0 0 3px rgba($primary, 0.1);
            }
        }
    }

    // 🆕 Группа для кнопок, чтобы они держались вместе
    .filter-actions {
        display: flex;
        gap: 8px;
        margin-left: auto; // Прижимает кнопки вправо на десктопе
    }

    .btn-reset-dates {
        padding: 8px 16px;
        background: rgba($primary, 0.08);
        color: $primary;
        border: 1px solid rgba($primary, 0.2);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;

        &:hover {
            background: rgba($primary, 0.15);
            border-color: $primary;
        }

        &:active {
            transform: scale(0.98);
        }
    }

    // 🆕 Стили для новой кнопки "Загрузить"
    .btn-load-orders {
        padding: 8px 16px;
        background: $primary;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;

        &:hover:not(:disabled) {
            background: $primary-dark;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba($primary, 0.2);
        }

        &:active:not(:disabled) {
            transform: scale(0.98);
        }

        &:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
    }
}

// 🆕 Адаптив для мобильных устройств
@media (max-width: 640px) {
    .date-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;

        .filter-group {
            justify-content: space-between;
        }

        .filter-actions {
            margin-left: 0;
            flex-direction: column; // Кнопки друг под другом на телефоне
            width: 100%;
        }

        .btn-reset-dates,
        .btn-load-orders {
            width: 100%;
            justify-content: center;
        }
    }
}
</style>
