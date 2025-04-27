<template>
    <Line :data="chartData"     _options: (__VLS_ctx.chartOptions) />
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
    PointElement,
  } from "chart.js";
  import { Line } from "vue-chartjs";
  
  ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement
  );
  
  export default {
    name: "LineChart",
    props: {
      labels: Array,
      dataPoints: Array,
    },
    computed: {
      chartData() {
        return {
          labels: this.labels,
          datasets: [
            {
              label: "ผู้รับบริการ OPD รายเดือน",
              backgroundColor: "#0d6efd88",
              borderColor: "#0d6efd",
              data: this.dataPoints,
              fill: true,
              tension: 0.4,
            },
          ],
        };
      },
      chartOptions() {
        return {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
          },
        };
      },
    },
    components: { Line },
  };
  </script>
  
  <style scoped>
  </style>
  