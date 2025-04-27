<template>
  <Bar :data="chartData" :options="chartOptions" />
</template>

<script setup>
import { defineProps } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'

// Register chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

// Define props for receiving chart data
const props = defineProps({
  chartData: {
    type: Object,
    required: true
  }
})

// Chart data example based on the provided data
const chartData = {
  labels: ['2024-10', '2024-11', '2024-12', '2025-01', '2025-02', '2025-03'],
  datasets: [
    {
      label: 'Health Med',
      data: [250, 230, 213, 198, 219, 232],
      backgroundColor: '#FF5733'
    },
    {
      label: 'Physic',
      data: [161, 140, 94, 177, 138, 108],
      backgroundColor: '#33FF57'
    },
    {
      label: 'PCU',
      data: [466, 2153, 884, 1148, 427, 383],
      backgroundColor: '#3357FF'
    },
    {
      label: 'PD',
      data: [41, 56, 50, 69, 57, 48],
      backgroundColor: '#FF33F6'
    },
    {
      label: 'Dent',
      data: [1312, 1940, 1469, 3131, 1774, 919],
      backgroundColor: '#FFC300'
    },
    {
      label: 'ER',
      data: [2587, 2109, 2076, 2483, 2214, 1675],
      backgroundColor: '#D633FF'
    }
  ]
}

// Chart options
const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'top'
    },
    title: {
      display: true,
      text: 'จำนวนเคสแยกตามประเภท'
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}
</script>

<style scoped>
.container {
  max-width: 1000px;
  margin: 0 auto;
}
</style>
