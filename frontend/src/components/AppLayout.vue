<script setup lang="ts">
import { RouterView } from 'vue-router'
import FolderTree from './FolderTree.vue'
import ToastContainer from './ToastContainer.vue'
import SearchBar from './SearchBar.vue'
</script>

<template>
  <div class="app-layout">
    <header class="app-layout__header">
      <div class="app-layout__header-inner">
        <span class="app-layout__logo">Заметки</span>
        <SearchBar />
      </div>
    </header>

    <div class="app-layout__body">
      <aside class="app-layout__sidebar">
        <FolderTree />
      </aside>

      <main class="app-layout__main">
        <div class="app-layout__content">
          <RouterView />
        </div>
      </main>
    </div>

    <footer class="app-layout__footer">
      <div class="app-layout__footer-inner">
        <span>© 2026 Заметки</span>
      </div>
    </footer>

    <ToastContainer />
  </div>
</template>

<style scoped lang="scss">
@use '@/assets/styles/breakpoints' as *;

.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;

  &__header {
    height: var(--header-height);
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  &__header-inner {
    max-width: var(--content-max-width);
    margin: 0 auto;
    padding: 0 var(--content-padding-x);
    height: 100%;
    display: flex;
    align-items: center;
  }

  &__logo {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--color-primary);
  }

  &__body {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  &__sidebar {
    flex-shrink: 0;
    overflow-y: auto;
  }

  &__main {
    flex: 1;
    overflow: auto;
    min-width: 0;
  }

  &__content {
    max-width: var(--content-max-width);
    margin: 0 auto;
    padding: var(--spacing-lg) var(--content-padding-x);
    min-width: 0;

    @include respond-to('lg') {
      padding: var(--spacing-2xl) var(--spacing-xl);
    }
  }

  &__footer {
    height: var(--footer-height);
    background-color: var(--color-surface);
    border-top: 1px solid var(--color-border);
  }

  &__footer-inner {
    max-width: var(--content-max-width);
    margin: 0 auto;
    padding: 0 var(--content-padding-x);
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
  }
}
</style>
