<template>
    <div class="auth-layout">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Админ-панель</h1>
                    <p class="auth-subtitle">Войдите в систему для продолжения</p>
                </div>
                <slot/>
            </div>
        </div>
    </div>
</template>

<script setup>
// AuthLayout - простая обертка для страниц авторизации
</script>

<style scoped>
.auth-layout {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.auth-container {
    width: 100%;
    max-width: 420px;
    padding: 20px;
}

.auth-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.auth-header {
    text-align: center;
    margin-bottom: 32px;
}

.auth-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.auth-subtitle {
    font-size: 14px;
    color: #718096;
    margin: 0;
}
</style>
