<template>
    <div>
      <canvas id="barChart"></canvas>
    </div>
  </template>
  
  <script>
  import { Chart, registerables } from "chart.js";
  
  Chart.register(...registerables);
  
  export default {
    props: ["data"],
    mounted() {
      new Chart(document.getElementById("barChart"), {
        type: "bar",
        data: this.data,
      });
    },
  };
  </script>
  