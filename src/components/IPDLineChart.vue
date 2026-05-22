<template>
  <ChartLine :data="chartData" :options="chartOptions" />
</template>

<script>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement
} from 'chart.js';
import { Line as ChartLine } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement);

export default {
  name: 'IPDLineChart',
  props: {
    labels: Array,
    dataPoints: Array
  },
  computed: {
    chartData() {
      return {
        labels: this.labels,
        datasets: [
          {
            label: 'ผู้รับบริการ IPD รายเดือน',
            backgroundColor: '#0d6efd88',
            borderColor: '#0d6efd',
            data: this.dataPoints,
            fill: true,
            tension: 0.4
          }
        ]
      };
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top'
          }
        }
      };
    }
  },
  components: { ChartLine }
};
</script>

<style scoped></style>
