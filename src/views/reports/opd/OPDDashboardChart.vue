<template>
  <div class="report-container">
    <div class="report-header">
      <h2 class="report-title">รายงานจำนวนผู้มารับบริการรายเดือน</h2>
      <p class="report-subtitle">
        แสดงข้อมูลจำนวนผู้มารับบริการแผนกผู้ป่วยนอก (OPD)
      </p>
    </div>

    <div class="report-controls card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="startDate" class="form-label">วันที่เริ่มต้น</label>
            <input
              type="date"
              id="startDate"
              v-model="startDate"
              class="form-control form-control-lg"
            />
          </div>
          <div class="col-md-4">
            <label for="endDate" class="form-label">วันที่สิ้นสุด</label>
            <input
              type="date"
              id="endDate"
              v-model="endDate"
              class="form-control form-control-lg"
            />
          </div>
          <div class="col-md-4 d-grid">
            <button class="btn btn-primary btn-lg" @click="fetchData">
              <i class="bi bi-search me-2"></i>ค้นหาข้อมูล
            </button>
          </div>
        </div>
      </div>
    </div>
    <OPDLineChart/>
    <div class="column-gap">
      <div class="chart-container card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">กราฟแสดงจำนวนผู้มารับบริการ</h5>
        </div>
        <div class="card-body">
          <canvas ref="chartCanvas" height="350"></canvas>
        </div>
      </div>
    </div>

    <div class="table-container card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">ตารางสรุปจำนวนผู้มารับบริการ</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover table-striped">
            <thead class="table-primary">
              <tr>
                <th class="text-center" style="width: 50%">เดือน</th>
                <th class="text-center" style="width: 50%">
                  จำนวนผู้มารับบริการ (OPD)
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in opdData" :key="index">
                <td class="text-center">{{ item.month }}</td>
                <td class="text-center">{{ formatNumber(item.opd_all) }}</td>
              </tr>
              <tr v-if="opdData.length > 0" class="table-secondary fw-bold">
                <td class="text-center">รวมทั้งหมด</td>
                <td class="text-center">{{ formatNumber(totalPatients) }}</td>
              </tr>
              <tr v-if="opdData.length === 0">
                <td colspan="2" class="text-center text-muted py-4">
                  ไม่พบข้อมูล
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import Chart from "chart.js/auto";


const startDate = ref("2024-10-01");
const endDate = ref("2025-09-30");
const opdData = ref([]);
const chartInstance = ref(null);
const chartCanvas = ref(null);

const totalPatients = computed(() => {
  return opdData.value.reduce(
    (sum, item) => sum + parseInt(item.opd_all || 0),
    0
  );
});

const formatNumber = (num) => {
  return parseInt(num || 0).toLocaleString();
};

const fetchData = async () => {
  try {
    const url = `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/opd/get_opd_month.php?start_date=${startDate.value}&end_date=${endDate.value}`;
    const res = await fetch(url);
    const data = await res.json();
    opdData.value = data;
    renderChart();
  } catch (error) {
    console.error("Error fetching data:", error);
    opdData.value = [];
    if (chartInstance.value) {
      chartInstance.value.destroy();
    }
  }
};

const renderChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  if (opdData.value.length === 0) return;

  const ctx = chartCanvas.value.getContext("2d");

  // Create gradient
  const gradient = ctx.createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, "rgba(155, 194, 187, 0.8)");
  gradient.addColorStop(1, "rgba(155, 194, 187, 0.1)");

  chartInstance.value = new Chart(ctx, {
    type: "line",
    data: {
      labels: opdData.value.map((item) => item.month),
      datasets: [
        {
          label: "จำนวนผู้รับบริการ (คน)",
          data: opdData.value.map((item) => item.opd_all),
          borderColor: "#4e8c87",
          backgroundColor: gradient,
          fill: true,
          tension: 0.3,
          pointBackgroundColor: "#fff",
          pointBorderColor: "#4e8c87",
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            font: {
              size: 14,
            },
          },
        },
        tooltip: {
          backgroundColor: "rgba(0,0,0,0.8)",
          titleFont: {
            size: 16,
          },
          bodyFont: {
            size: 14,
          },
          padding: 12,
          usePointStyle: true,
          callbacks: {
            label: function (context) {
              return ` ${context.parsed.y.toLocaleString()} คน`;
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            callback: function (value) {
              return value.toLocaleString();
            },
          },
          grid: {
            color: "rgba(0,0,0,0.05)",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
      interaction: {
        intersect: false,
        mode: "index",
      },
    },
  });
};

onMounted(fetchData);
</script>

<style scoped>
.report-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.report-header {
  text-align: center;
  margin-bottom: 30px;
}

.report-title {
  color: #2c3e50;
  font-weight: 700;
  margin-bottom: 10px;
}

.report-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.chart-container {
  border-radius: 10px;
  overflow: hidden;
}

.table-container {
  border-radius: 10px;
  overflow: hidden;
}

.card-header {
  font-size: 1.2rem;
  padding: 15px 20px;
}

.table {
  margin-bottom: 0;
}

.table th {
  font-weight: 600;
}

.table-hover tbody tr:hover {
  background-color: rgba(155, 194, 187, 0.1);
}

.form-control-lg {
  padding: 10px 15px;
  font-size: 1rem;
}

.btn-lg {
  padding: 10px 20px;
  font-size: 1.1rem;
}

@media (max-width: 768px) {
  .report-controls .col-md-4 {
    margin-bottom: 15px;
  }

  .report-controls .col-md-4:last-child {
    margin-bottom: 0;
  }
}
</style>
