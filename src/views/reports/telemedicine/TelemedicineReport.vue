<template>
  <div class="telemedicine-dashboard">
    <div class="container mt-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="dashboard-title"><i class="bi bi-telephone text-primary me-2"></i>รายงานผู้รับบริการทางไกล (Telemedicine)</h2>
          <p class="text-muted">แยกตามแผนก / คลินิก</p>
        </div>
        <button
          @click="$router.push('/dashboard')"
          class="btn btn-outline-secondary rounded-pill fw-bold"
        >
          <i class="bi bi-arrow-left me-1"></i> กลับหน้า Dashboard
        </button>
      </div>

      <!-- Date Range Selector -->
      <div class="date-range-card card shadow-sm mb-3 border-0">
        <div class="card-body p-3">
          <h6 class="card-title text-primary mb-2">
            <i class="bi bi-calendar-range me-2"></i>เลือกช่วงวันที่ ที่ต้องการดูข้อมูล
          </h6>
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label for="startDate" class="form-label fw-bold">วันที่เริ่มต้น:</label>
              <input type="date" v-model="startDate" class="form-control" id="startDate" />
            </div>
            <div class="col-md-5">
              <label for="endDate" class="form-label fw-bold">วันที่สิ้นสุด:</label>
              <input type="date" v-model="endDate" class="form-control" id="endDate" />
            </div>
            <div class="col-md-2">
              <button class="btn btn-primary w-100 fw-bold" @click="fetchData" :disabled="loading">
                <i class="bi bi-search me-2"></i>
                <span v-if="!loading">ค้นหา</span>
                <span v-else>กำลังค้นหา...</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center my-5 py-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted fw-bold">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Data Display -->
      <div v-else-if="data && data.length > 0">
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card bg-primary text-white border-0 shadow rounded-4">
              <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                  <h5 class="mb-1 fw-bold">รวมผู้รับบริการทั้งหมด</h5>
                  <p class="mb-2 text-white-50" style="font-size: 0.85rem;">ในช่วงเวลาที่เลือก</p>
                  
                  <div class="d-inline-flex align-items-center bg-white bg-opacity-25 rounded-pill px-3 py-1">
                    <span class="fs-6 me-2">วันนี้: <strong>{{ formatNumber(todayCount) }}</strong></span>
                    <span v-if="todayCount >= yesterdayCount" class="badge bg-success rounded-pill px-2 py-1 ms-1">
                      <i class="bi bi-arrow-up-short fs-6 align-middle"></i> {{ formatNumber(todayCount - yesterdayCount) }} จากเมื่อวาน ({{ formatNumber(yesterdayCount) }})
                    </span>
                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 ms-1">
                      <i class="bi bi-arrow-down-short fs-6 align-middle"></i> {{ formatNumber(yesterdayCount - todayCount) }} จากเมื่อวาน ({{ formatNumber(yesterdayCount) }})
                    </span>
                  </div>
                </div>
                <h1 class="fw-bold mb-0" style="font-size: 3rem;">{{ formatNumber(totalTelemedicine) }}</h1>
              </div>
            </div>
          </div>
          
          <div class="col-12 mb-3">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-primary mb-3">
                  <i class="bi bi-graph-up me-2"></i>กราฟเปรียบเทียบผู้รับบริการทางไกลแยกตามแผนก
                </h6>
                <div style="height: 250px; position: relative">
                  <LineChart v-if="data && data.length > 0" :data="chartData" :options="chartOptions" />
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light border-bottom">
                    <tr>
                      <th class="py-3 px-4 text-secondary small fw-bold" style="width: 40%">แผนก / คลินิก</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 20%">จำนวน (ช่วงเวลาที่เลือก)</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 20%">วันนี้</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 20%">เมื่อวาน</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in data" :key="index">
                      <td class="py-3 px-4">
                        <div class="fw-bold text-dark fs-6">{{ item.department_name }}</div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <div class="fw-bold text-primary fs-5">{{ formatNumber(item.total) }}</div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <div class="fw-bold text-dark fs-5">{{ formatNumber(item.today_count) }}</div>
                        <div v-if="item.today_count > 0 || item.yesterday_count > 0" class="mt-1">
                          <span v-if="parseInt(item.today_count) >= parseInt(item.yesterday_count)" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.7rem;">
                            <i class="bi bi-arrow-up-short"></i> {{ formatNumber(item.today_count - item.yesterday_count) }}
                          </span>
                          <span v-else class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 0.7rem;">
                            <i class="bi bi-arrow-down-short"></i> {{ formatNumber(item.yesterday_count - item.today_count) }}
                          </span>
                        </div>
                      </td>
                      <td class="py-3 px-4 text-center text-muted">
                        <div class="fs-6">{{ formatNumber(item.yesterday_count) }}</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error/No Data State -->
      <div v-else class="no-data-card card text-center py-5 border-0 shadow-sm rounded-4">
        <div class="card-body">
          <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
          <h5 class="mt-3 fw-bold text-muted">ไม่พบข้อมูลผู้รับบริการทางไกล</h5>
          <p class="text-muted">โปรดตรวจสอบช่วงวันที่หรือลองใหม่อีกครั้ง</p>
          <button class="btn btn-outline-primary mt-2 rounded-pill px-4" @click="fetchData">
            <i class="bi bi-arrow-repeat me-2"></i>ลองอีกครั้ง
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export default {
  name: 'TelemedicineReport',
  components: {
    LineChart: Line
  },
  data() {
    return {
      startDate: '',
      endDate: '',
      data: [],
      loading: false,
      todayCount: 0,
      yesterdayCount: 0,
    };
  },
  computed: {
    computedFiscalYear() {
      const today = new Date();
      let fiscalYear = today.getFullYear();
      if (today.getMonth() < 9) {
        fiscalYear--;
      }
      return fiscalYear + 1;
    },
    totalTelemedicine() {
      if (!this.data || this.data.length === 0) return 0;
      return this.data.reduce((sum, item) => sum + parseInt(item.total || 0), 0);
    },
    chartData() {
      if (!this.data || this.data.length === 0) return { labels: [], datasets: [] };
      return {
        labels: this.data.map(item => item.department_name),
        datasets: [
          {
            label: 'จำนวน (ครั้ง)',
            backgroundColor: '#0d6efd',
            borderColor: '#0d6efd',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#0d6efd',
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: false,
            tension: 0.3,
            data: this.data.map(item => parseInt(item.total || 0))
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
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            bodyFont: { size: 14, family: "'Sarabun', sans-serif" },
            titleFont: { size: 14, weight: 'bold', family: "'Sarabun', sans-serif" }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return value.toLocaleString();
              },
              font: { family: "'Sarabun', sans-serif" }
            }
          },
          x: {
            ticks: {
              font: { family: "'Sarabun', sans-serif" }
            }
          }
        }
      };
    }
  },
  methods: {
    formatNumber(num) {
      return Number(num || 0).toLocaleString();
    },
    async fetchData() {
      this.loading = true;
      this.data = [];
      try {
        const response = await axios.get('/api-hosxe/telemedicine/get_telemedicine_report.php', {
          params: {
            start_date: this.startDate,
            end_date: this.endDate
          }
        });
        if (response.data.status === 'success') {
          this.data = response.data.data;
          this.todayCount = response.data.today || 0;
          this.yesterdayCount = response.data.yesterday || 0;
        } else {
          console.error('API Error:', response.data.message);
        }
      } catch (error) {
        console.error('Network Error:', error);
      } finally {
        this.loading = false;
      }
    }
  },
  mounted() {
    const fiscalYear = this.computedFiscalYear;
    this.startDate = `${fiscalYear - 1}-10-01`;
    this.endDate = `${fiscalYear}-09-30`;
    this.fetchData();
  }
};
</script>

<style scoped>
.telemedicine-dashboard {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 2rem 0;
  font-family: 'Sarabun', sans-serif;
}

.dashboard-title {
  color: #6f42c1;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.03);
}

.bg-primary {
  background-color: #0d6efd !important;
}

.text-primary {
  color: #0d6efd !important;
}
</style>
