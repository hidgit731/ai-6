<script setup lang="ts">
import { watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DOMPurify from 'dompurify'
import { useSearchStore } from '@/stores/search'
import Pagination from '@/components/Pagination.vue'
import SkeletonList from '@/components/SkeletonList.vue'

const route = useRoute()
const router = useRouter()
const searchStore = useSearchStore()

function sanitize(html: string): string {
    return DOMPurify.sanitize(html, { ALLOWED_TAGS: ['b'], ALLOWED_ATTR: [] })
}

function getQuery(): string {
    return typeof route.query.q === 'string' ? route.query.q : ''
}

function getPage(): number {
    const p = parseInt(String(route.query.page ?? '1'), 10)
    return isNaN(p) || p < 1 ? 1 : p
}

async function onPageChange(newPage: number): Promise<void> {
    await router.push({ name: 'search', query: { q: searchStore.query, page: newPage } })
    await searchStore.fetchResults(searchStore.query, newPage)
}

onMounted(async () => {
    const q = getQuery()
    const p = getPage()
    if (q) {
        await searchStore.fetchResults(q, p)
    }
})

watch(
    () => route.query.q,
    async (newQ) => {
        const q = typeof newQ === 'string' ? newQ : ''
        const p = getPage()
        if (q) {
            await searchStore.fetchResults(q, p)
        } else {
            searchStore.clearResults()
        }
    }
)
</script>

<template>
    <div class="search-results-page">
        <h1 class="search-results-page__title">
            Результаты поиска<span v-if="searchStore.query">: «{{ searchStore.query }}»</span>
        </h1>

        <SkeletonList v-if="searchStore.loading" />

        <p v-else-if="searchStore.error" class="search-results-page__error">
            {{ searchStore.error }}
        </p>

        <div
            v-else-if="searchStore.results.length === 0 && !searchStore.loading"
            class="search-results-page__empty"
        >
            <p v-if="searchStore.query">По запросу «{{ searchStore.query }}» ничего не найдено.</p>
            <p v-else>Введите поисковый запрос в строку поиска.</p>
        </div>

        <ul v-else class="search-results-page__list">
            <li
                v-for="item in searchStore.results"
                :key="item.id"
                class="search-result"
            >
                <router-link :to="{ name: 'note-view', params: { id: item.id } }" class="search-result__title">
                    {{ item.title }}
                </router-link>
                <p
                    class="search-result__headline"
                    v-html="sanitize(item.headline)"
                />
            </li>
        </ul>

        <Pagination
            v-if="searchStore.totalPages > 1"
            :page="searchStore.page"
            :total-pages="searchStore.totalPages"
            @page-change="onPageChange"
        />
    </div>
</template>

<style scoped lang="scss">
.search-results-page {
    padding: 2rem;

    &__title {
        margin: 0 0 2rem;
        font-size: 1.5rem;
        color: #222;
    }

    &__error {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }

    &__empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
        font-size: 1.1rem;
    }

    &__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
}

.search-result {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 1rem 1.25rem;

    &__title {
        display: block;
        font-size: 1.05rem;
        font-weight: 600;
        color: #4a90d9;
        text-decoration: none;
        margin-bottom: 0.4rem;

        &:hover {
            text-decoration: underline;
        }
    }

    &__headline {
        margin: 0;
        font-size: 0.9rem;
        color: #555;
        line-height: 1.5;

        :deep(b) {
            font-weight: 600;
            color: var(--color-primary, #4a90d9);
            background-color: color-mix(in srgb, var(--color-primary, #4a90d9) 12%, transparent);
            border-radius: 2px;
            padding: 0 2px;
        }
    }
}
</style>
