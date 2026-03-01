<script setup lang="ts">
import { RouterLink } from 'vue-router'
// NotFoundPage — fallback для несуществующих маршрутов (/:pathMatch(.*)*).
</script>

<template>
  <div class="not-found-page">
    <span class="not-found-code">404</span>
    <h1>Страница не найдена</h1>
    <p>Запрошенная страница не существует или была перемещена.</p>
    <RouterLink to="/" class="back-link">
      ← Вернуться на главную
    </RouterLink>
  </div>
</template>

<style scoped lang="scss">
.not-found-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  padding: var(--spacing-lg) var(--spacing-md);
  text-align: center;
  gap: var(--spacing-md);

  h1 {
    font-size: var(--font-size-2xl);
    color: var(--color-text);
  }

  p {
    font-size: var(--font-size-md);
    color: var(--color-text-muted);
    max-width: 30rem;
  }
}

.not-found-code {
  font-size: 5rem;
  font-weight: 700;
  color: var(--color-border);
  line-height: 1;
}

.back-link {
  display: inline-block;
  margin-top: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  background-color: var(--color-primary);
  color: #ffffff;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-sm);
  transition: background-color var(--transition-fast);

  &:hover {
    background-color: var(--color-primary-hover);
  }
}
</style>
