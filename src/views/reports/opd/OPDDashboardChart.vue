<template>
  <div class="report-container">
    <div class="report-header">
      <h2 class="report-title">รายงานจำนวนผู้มารับบริการรายเดือน</h2>
      <p class="report-subtitle">แสดงข้อมูลเปรียบเทียบจำนวนผู้มารับบริการแผนกผู้ป่วยนอก (OPD)</p>
    </div>

    <div class="report-controls card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end justify-content-center">
          <div class="col-md-4">
            <label class="form-label fw-bold">เปรียบเทียบปีงบประมาณที่ 1</label>
            <select v-model="compareYear1" class="form-select form-select-lg">
              <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
            </select>
          </div>
          <div class="col-md-auto d-flex align-items-center justify-content-center pb-2">
            <span class="fs-4 fw-bold text-muted">VS</span>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">เปรียบเทียบปีงบประมาณที่ 2</label>
            <select v-model="compareYear2" class="form-select form-select-lg">
              <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
            </select>
          </div>
          <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-lg" @click="fetchData">
              <i class="bi bi-search me-2"></i>ค้นหา
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="column-gap">
      <div class="chart-container card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">กราฟเปรียบเทียบจำนวนผู้มารับบริการ</h5>
        </div>
        <div class="card-body">
          <div style="position: relative; height: 350px; width: 100%">
            <canvas ref="chartCanvas"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="table-container card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">ตารางสรุปจำนวนผู้มารับบริการ</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0">
            <thead class="table-primary">
              <tr>
                <th class="text-center py-3" style="width: 30%">เดือน</th>
                <th class="text-center py-3" style="width: 35%">ปีงบฯ {{ compareYear1 }} (คน)</th>
                <th class="text-center py-3" style="width: 35%">ปีงบฯ {{ compareYear2 }} (คน)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(label, index) in monthLabels" :key="index">
                <td class="text-center fw-bold">{{ label }}</td>
                <td class="text-center text-secondary">{{ formatNumber(plotData1[index]) }}</td>
                <td class="text-center text-primary">{{ formatNumber(plotData2[index]) }}</td>
              </tr>
              <tr class="table-secondary fw-bold fs-5">
                <td class="text-center py-3">รวมทั้งหมด</td>
                <td class="text-center py-3 text-secondary">{{ formatNumber(totalPatients1) }}</td>
                <td class="text-center py-3 text-primary">{{ formatNumber(totalPatients2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import Chart from 'chart.js/auto';

const currentYear = new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0);
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);

const compareYear1 = ref(currentYear - 1);
const compareYear2 = ref(currentYear);

const plotData1 = ref(Array(12).fill(0));
const plotData2 = ref(Array(12).fill(0));

const chartInstance = ref(null);
const chartCanvas = ref(null);

const monthLabels = [
  'ต.ค.',
  'พ.ย.',
  'ธ.ค.',
  'ม.ค.',
  'ก.พ.',
  'มี.ค.',
  'เม.ย.',
  'พ.ค.',
  'มิ.ย.',
  'ก.ค.',
  'ส.ค.',
  'ก.ย.'
];
const fyMonths = ['10', '11', '12', '01', '02', '03', '04', '05', '06', '07', '08', '09'];

const totalPatients1 = computed(() => {
  return plotData1.value.reduce((sum, val) => sum + val, 0);
});

const totalPatients2 = computed(() => {
  return plotData2.value.reduce((sum, val) => sum + val, 0);
});

const formatNumber = (num) => {
  if (!num) return '0';
  return parseInt(num).toLocaleString();
};

const getFiscalYearDateRange = (fy) => {
  const adYear = fy - 543;
  return {
    start: `${adYear - 1}-10-01`,
    end: `${adYear}-09-30`
  };
};

const processData = (dataArray) => {
  const mapData = new Map();
  if (Array.isArray(dataArray)) {
    dataArray.forEach((item) => {
      const parts = item.month.split('-');
      if (parts.length === 2) {
        mapData.set(parts[1], parseInt(item.opd_all || 0));
      }
    });
  }
  return fyMonths.map((m) => mapData.get(m) || 0);
};

const fetchData = async () => {
  try {
    const range1 = getFiscalYearDateRange(compareYear1.value);
    const range2 = getFiscalYearDateRange(compareYear2.value);

    // Fetch data concurrently for both years
    const [res1, res2] = await Promise.all([
      fetch(
        `/api-hosxe/opd/get_opd_month.php?start_date=${range1.start}&end_date=${range1.end}`
      ).then((r) => r.json()),
      fetch(
        `/api-hosxe/opd/get_opd_month.php?start_date=${range2.start}&end_date=${range2.end}`
      ).then((r) => r.json())
    ]);

    plotData1.value = processData(res1);
    plotData2.value = processData(res2);

    renderChart();
  } catch (error) {
    console.error('Error fetching data:', error);
    plotData1.value = Array(12).fill(0);
    plotData2.value = Array(12).fill(0);
    if (chartInstance.value) {
      chartInstance.value.destroy();
    }
  }
};

const renderChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  const ctx = chartCanvas.value.getContext('2d');

  chartInstance.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [
        {
          label: `ปีงบประมาณ ${compareYear1.value}`,
          data: plotData1.value,
          borderColor: '#6c757d',
          backgroundColor: 'rgba(108, 117, 125, 0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#6c757d',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        },
        {
          label: `ปีงบประมาณ ${compareYear2.value}`,
          data: plotData2.value,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#0d6efd',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            font: {
              size: 14
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          titleFont: {
            size: 16
          },
          bodyFont: {
            size: 14
          },
          padding: 12,
          usePointStyle: true,
          callbacks: {
            label: function (context) {
              return ` ${context.parsed.y.toLocaleString()} คน`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            callback: function (value) {
              return value.toLocaleString();
            }
          },
          grid: {
            color: 'rgba(0,0,0,0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
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

.table th {
  font-weight: 600;
}

.form-select-lg {
  padding: 10px 15px;
  font-size: 1rem;
}

.btn-lg {
  padding: 10px 20px;
  font-size: 1.1rem;
}

@media (max-width: 768px) {
  .report-controls .col-md-4,
  .report-controls .col-md-auto {
    margin-bottom: 15px;
  }
}
</style>
