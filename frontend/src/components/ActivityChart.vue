<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  LineController,
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, LineController)

const props = defineProps<{
  activity: Array<{ date: string; count: number }>
}>()

const chartData = computed(() => ({
  labels: props.activity.map(p => p.date.slice(5)), // MM-DD
  datasets: [
    {
      label: 'Заметок создано',
      data: props.activity.map(p => p.count),
      borderColor: '#42b883',
      backgroundColor: 'rgba(66, 184, 131, 0.15)',
      tension: 0.3,
      fill: true,
      pointRadius: 3,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0 },
    },
  },
}
</script>

<template>
    <div class="activity-chart">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>

<style scoped>
.activity-chart {
    height: 220px;
    width: 100%;
}
</style>
