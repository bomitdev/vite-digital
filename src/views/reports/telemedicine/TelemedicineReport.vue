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
                      +{{ formatNumber(todayCount - yesterdayCount) }} จากเมื่อวาน ({{ formatNumber(yesterdayCount) }})
                    </span>
                    <span v-else class="badge bg-danger rounded-pill px-2 py-1 ms-1">
                      -{{ formatNumber(yesterdayCount - todayCount) }} จากเมื่อวาน ({{ formatNumber(yesterdayCount) }})
                    </span>
                  </div>
                </div>
                <h1 class="fw-bold mb-0" style="font-size: 3rem;">{{ formatNumber(totalTelemedicine) }}</h1>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
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
          
          <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-success mb-3">
                  <i class="bi bi-calendar2-check me-2"></i>กราฟแสดงแนวโน้มผู้รับบริการทางไกลรายเดือน
                </h6>
                <div style="height: 250px; position: relative">
                  <LineChart v-if="monthlyTrend && monthlyTrend.length > 0" :data="monthlyChartData" :options="monthlyChartOptions" />
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
                      <th class="py-3 px-4 text-secondary small fw-bold" style="width: 30%">แผนก / คลินิก</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 15%">เป้าหมาย</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 25%">จำนวน (ช่วงเวลาที่เลือก)</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 15%">วันนี้</th>
                      <th class="py-3 px-4 text-secondary small fw-bold text-center" style="width: 15%">เมื่อวาน</th>
                    </tr>
                  </thead>
                  <tbody v-for="(dept, index) in groupedData" :key="index">
                    <tr @click="toggleDept(dept.department_name)" style="cursor: pointer;" class="hover-bg-light">
                      <td class="py-3 px-4">
                        <div class="fw-bold text-dark fs-6 d-flex align-items-center">
                          <i class="bi me-2 text-primary" :class="expandedDepts.includes(dept.department_name) ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                          {{ dept.department_name }}
                        </div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <div class="fw-bold text-dark fs-5">20 <span class="fs-6 text-muted fw-normal">/วัน</span></div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <div class="fw-bold text-primary fs-5">{{ formatNumber(dept.total) }}</div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <div class="fw-bold text-dark fs-5">
                          {{ formatNumber(dept.today_count) }}
                          <br v-if="dept.today_count >= 20" />
                          <span v-if="dept.today_count >= 20" class="badge bg-success mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-check-circle-fill me-1"></i>ผ่านเป้าหมาย
                          </span>
                        </div>
                      </td>
                      <td class="py-3 px-4 text-center text-muted">
                        <div class="fs-6">
                          {{ formatNumber(dept.yesterday_count) }}
                          <br v-if="dept.yesterday_count >= 20" />
                          <span v-if="dept.yesterday_count >= 20" class="badge bg-success mt-1" style="font-size: 0.7rem;">
                            <i class="bi bi-check-circle-fill me-1"></i>ผ่าน
                          </span>
                        </div>
                      </td>
                    </tr>
                    
                    <template v-if="expandedDepts.includes(dept.department_name) && dept.rooms && dept.rooms.length > 0">
                      <tr v-for="(room, rIndex) in dept.rooms" :key="'r' + rIndex" class="bg-light">
                        <td class="py-2 px-4 ps-5">
                          <div class="small text-muted fw-bold"><i class="bi bi-geo-alt me-1"></i>{{ room.room_name }}</div>
                        </td>
                        <td class="py-2 px-4 text-center text-muted">-</td>
                        <td class="py-2 px-4 text-center text-primary small fw-bold">{{ formatNumber(room.total) }}</td>
                        <td class="py-2 px-4 text-center text-dark small fw-bold">{{ formatNumber(room.today_count) }}</td>
                        <td class="py-2 px-4 text-center text-muted small">{{ formatNumber(room.yesterday_count) }}</td>
                      </tr>
                    </template>
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
      monthlyTrend: [],
      loading: false,
      todayCount: 0,
      yesterdayCount: 0,
      expandedDepts: [],
    };
  },
  computed: {
    groupedData() {
      if (!this.data || this.data.length === 0) return [];
      
      const map = {};
      this.data.forEach(item => {
        const dept = item.department_name;
        if (!map[dept]) {
          map[dept] = {
            department_name: dept,
            total: 0,
            today_count: 0,
            yesterday_count: 0,
            rooms: []
          };
        }
        map[dept].total += parseInt(item.total || 0);
        map[dept].today_count += parseInt(item.today_count || 0);
        map[dept].yesterday_count += parseInt(item.yesterday_count || 0);
        
        if (item.room) {
          map[dept].rooms.push({
            room_name: item.room,
            total: parseInt(item.total || 0),
            today_count: parseInt(item.today_count || 0),
            yesterday_count: parseInt(item.yesterday_count || 0)
          });
        } else {
          // If no room is specified, we can optionally push it as "ไม่ระบุห้อง"
          map[dept].rooms.push({
            room_name: 'ไม่ระบุห้อง',
            total: parseInt(item.total || 0),
            today_count: parseInt(item.today_count || 0),
            yesterday_count: parseInt(item.yesterday_count || 0)
          });
        }
      });
      
      return Object.values(map).sort((a, b) => b.total - a.total);
    },
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
      if (!this.groupedData || this.groupedData.length === 0) return { labels: [], datasets: [] };
      return {
        labels: this.groupedData.map(item => item.department_name),
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
            data: this.groupedData.map(item => parseInt(item.total || 0))
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
    },
    monthlyChartData() {
      if (!this.monthlyTrend || this.monthlyTrend.length === 0) return { labels: [], datasets: [] };
      
      const monthNames = {
        '01': 'ม.ค.', '02': 'ก.พ.', '03': 'มี.ค.', '04': 'เม.ย.', 
        '05': 'พ.ค.', '06': 'มิ.ย.', '07': 'ก.ค.', '08': 'ส.ค.', 
        '09': 'ก.ย.', '10': 'ต.ค.', '11': 'พ.ย.', '12': 'ธ.ค.'
      };

      const labels = this.monthlyTrend.map(item => {
        const [year, month] = item.month_year.split('-');
        const thaiYear = parseInt(year) + 543;
        return `${monthNames[month]} ${thaiYear.toString().substring(2)}`;
      });

      return {
        labels: labels,
        datasets: [
          {
            label: 'จำนวน (ครั้ง)',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            borderColor: '#198754',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#198754',
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            tension: 0.3,
            data: this.monthlyTrend.map(item => parseInt(item.total || 0))
          }
        ]
      };
    },
    monthlyChartOptions() {
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
    toggleDept(deptName) {
      const index = this.expandedDepts.indexOf(deptName);
      if (index > -1) {
        this.expandedDepts.splice(index, 1);
      } else {
        this.expandedDepts.push(deptName);
      }
    },
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
          this.monthlyTrend = response.data.monthly_trend || [];
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
