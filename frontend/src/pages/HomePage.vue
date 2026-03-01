<script setup lang="ts">
// HomePage — главная страница, маршрут: /
// Один компонент = одна ответственность (Конституция).
</script>

<template>
  <div class="home-page">
    <h1>Добро пожаловать в Заметки</h1>
    <p>Здесь будут ваши заметки. Функциональность появится в следующих фичах.</p>
  </div>
</template>

<style scoped>
.home-page {
  padding: var(--spacing-lg) var(--spacing-md);

  h1 {
    font-size: var(--font-size-2xl);
    margin-bottom: var(--spacing-md);
    color: var(--color-text);
  }

  p {
    font-size: var(--font-size-md);
    color: var(--color-text-muted);
    line-height: var(--line-height-base);
  }
}
</style>
