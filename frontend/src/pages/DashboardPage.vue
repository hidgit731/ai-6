<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboard } from '@/composables/useDashboard'
import { useDashboardStore } from '@/stores/dashboard'
import DashboardStatCard from '@/components/DashboardStatCard.vue'
import ActivityChart from '@/components/ActivityChart.vue'

const { fetchStats } = useDashboard()
const dashboardStore = useDashboardStore()

onMounted(() => {
  fetchStats()
})
</script>

<template>
    <div class="dashboard-page">
        <h1 class="dashboard-page__title">Дашборд</h1>

        <div v-if="dashboardStore.loading" class="dashboard-page__loading">
            Загрузка статистики...
        </div>

        <div v-else-if="dashboardStore.error" class="dashboard-page__error">
            {{ dashboardStore.error }}
        </div>

        <template v-else-if="dashboardStore.stats">
            <div class="dashboard-page__stats">
                <DashboardStatCard
                    label="Заметки"
                    :value="dashboardStore.stats.notes_count"
                />
                <DashboardStatCard
                    label="Теги"
                    :value="dashboardStore.stats.tags_count"
                />
                <DashboardStatCard
                    label="Папки"
                    :value="dashboardStore.stats.folders_count"
                />
            </div>

            <div class="dashboard-page__chart-section">
                <h2 class="dashboard-page__chart-title">Активность за 30 дней</h2>
                <ActivityChart :activity="dashboardStore.stats.activity" />
            </div>
        </template>
    </div>
</template>

<style scoped>
.dashboard-page {
    padding: 24px;
    max-width: 900px;
    margin: 0 auto;
}

.dashboard-page__title {
    font-size: 24px;
    margin-bottom: 24px;
}

.dashboard-page__stats {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.dashboard-page__loading,
.dashboard-page__error {
    padding: 16px;
    border-radius: 4px;
    text-align: center;
}

.dashboard-page__error {
    color: #dc3545;
    background: #fff5f5;
    border: 1px solid #f5c6cb;
}

.dashboard-page__chart-section {
    background: var(--color-background-soft, #f8f9fa);
    border: 1px solid var(--color-border, #dee2e6);
    border-radius: 8px;
    padding: 20px;
}

.dashboard-page__chart-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-heading, #2c3e50);
}
</style>
