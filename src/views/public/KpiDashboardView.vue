<template>
  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0 text-primary">
          <i class="bi bi-speedometer2 me-2"></i>Hospital KPI Dashboard
        </h2>
        <p class="text-muted mb-0">ติดตามตัวชี้วัดประสิทธิภาพโรงพยาบาล (5 Dimensions)</p>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-2">
        <div class="input-group" style="width: 250px">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input
            type="text"
            class="form-control"
            placeholder="ค้นหา KPI หรือ ผู้รับผิดชอบ..."
            v-model="searchQuery"
          />
        </div>
        <select class="form-select w-auto" v-model="selectedLevel" v-if="availableLevels.length > 0">
          <option value="">ทุกระดับ (All Levels)</option>
          <option v-for="level in availableLevels" :key="level" :value="level">{{ level }}</option>
        </select>
        <select class="form-select w-auto" v-model="selectedYear" @change="fetchData">
          <option v-for="y in yearList" :key="y" :value="y">ปีงบประมาณ {{ y }}</option>
        </select>
        <button class="btn btn-outline-primary" @click="fetchData">
          <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i> Export</button>
        <router-link to="/kpi-setup" class="btn btn-dark" v-if="isAdmin">
          <i class="bi bi-gear-fill me-1"></i> ตั้งค่า KPI
        </router-link>
      </div>
    </div>

    <!-- Summary Widgets & Donut Chart -->
    <div class="row mb-4 align-items-stretch">
      <div class="col-md-8">
        <div class="row h-100">
          <div class="col-md-6 mb-3">
            <div class="card bg-success text-white h-100 shadow-sm border-0">
              <div class="card-body">
                <h5 class="card-title"><i class="bi bi-check-circle me-2"></i>KPIs Passed</h5>
                <h2 class="display-4 fw-bold">{{ summary.passed }}</h2>
                <small>{{ summary.passedPercent }}% of Total KPIs</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="card bg-danger text-white h-100 shadow-sm border-0">
              <div class="card-body">
                <h5 class="card-title"><i class="bi bi-x-circle me-2"></i>KPIs Failed</h5>
                <h2 class="display-4 fw-bold">{{ summary.failed }}</h2>
                <small>{{ summary.failedPercent }}% need attention</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3 mb-md-0">
            <div class="card bg-warning text-dark h-100 shadow-sm border-0">
              <div class="card-body">
                <h5 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Warning</h5>
                <h2 class="display-4 fw-bold">{{ summary.warning }}</h2>
                <small>Close to target</small>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card bg-info text-white h-100 shadow-sm border-0">
              <div class="card-body">
                <h5 class="card-title"><i class="bi bi-list-task me-2"></i>Total KPIs</h5>
                <h2 class="display-4 fw-bold">{{ summary.total }}</h2>
                <small>Active Indicators</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Donut Chart Area -->
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex flex-column align-items-center justify-content-center pt-4">
            <h5 class="card-title text-muted mb-0 fw-bold">Performance Overview</h5>
            <div style="height: 220px; width: 100%; position: relative">
              <DoughnutChart
                v-if="!loading && summary.total > 0"
                :data="doughnutChartData"
                :options="doughnutChartOptions"
              />
              <div v-else-if="!loading" class="text-center text-muted mt-5">No KPI Data</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dimension Sections -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else>
      <div
        v-for="category in filteredCategories"
        :key="category.id"
        class="card mb-4 border-0 shadow-sm"
      >
        <div class="card-header bg-white border-bottom-0 py-3">
          <h4 class="mb-0 text-dark fw-bold border-start border-4 border-primary ps-3">
            {{ category.name }}
            <span class="text-muted fs-6 fw-normal">({{ category.description }})</span>
          </h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width: 30%">KPI Name</th>
                  <th class="text-center">Frequency</th>
                  <th class="text-center">Target</th>
                  <th class="text-center">Actual</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Trend</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="kpi in category.kpis" :key="kpi.id">
                  <td>
                    <div class="fw-bold text-dark">{{ kpi.name }}</div>
                    <small class="text-muted">{{ kpi.description }}</small>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                      <span class="badge bg-primary text-white" v-if="kpi.kpi_level">
                        <i class="bi bi-diagram-3-fill me-1"></i>{{ kpi.kpi_level }}
                      </span>
                      <span class="badge bg-info text-dark">
                        <i class="bi bi-person-fill me-1"></i>{{ kpi.responsible_person || 'ไม่ระบุ' }}
                      </span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-secondary border">
                      {{ getFrequencyLabel(kpi.kpi_periodicity) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border">
                      {{ kpi.target_operator }} {{ kpi.target_value }} {{ kpi.unit }}
                    </span>
                  </td>
                  <td class="text-center fw-bold fs-5">
                    {{ kpi.actual_value !== null ? kpi.actual_value : '-' }}
                  </td>
                  <td class="text-center">
                    <span v-if="kpi.actual_value === null" class="badge bg-secondary">No Data</span>
                    <span
                      v-else-if="checkStatus(kpi) === 'pass'"
                      class="badge bg-success rounded-pill px-3"
                      >Pass</span
                    >
                    <span v-else class="badge bg-danger rounded-pill px-3">Fail</span>
                    
                    <div v-if="getMissingPeriods(kpi).length > 0" class="mt-2">
                      <span class="badge bg-warning text-dark border border-warning" 
                            style="cursor: pointer; transition: 0.2s;"
                            @mouseover="$event.target.classList.add('shadow-sm')"
                            @mouseleave="$event.target.classList.remove('shadow-sm')"
                            @click="showMissingPeriodsDetails(kpi)"
                            title="คลิกเพื่อดูรายละเอียด">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>ค้างรายงาน {{ getMissingPeriods(kpi).length }} รอบ
                      </span>
                    </div>
                  </td>
                  <td class="text-center">
                    <button
                      type="button"
                      class="btn btn-link p-0 text-decoration-none"
                      @click.stop="openTrendModal(kpi)"
                      title="View Trend"
                    >
                      <i
                        v-if="checkStatus(kpi) === 'pass'"
                        class="bi bi-graph-up-arrow text-success fs-5"
                      ></i>
                      <i v-else class="bi bi-graph-down-arrow text-danger fs-5"></i>
                    </button>
                  </td>
                  <td class="text-end">
                    <button
                      type="button"
                      class="btn btn-sm btn-light border me-1"
                      @click.stop="openEntryModal(kpi)"
                      title="รายงานผล"
                    >
                      <i class="bi bi-pencil-square text-primary"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-light border"
                      @click.stop="openHistoryModal(kpi)"
                      title="ประวัติ"
                    >
                      <i class="bi bi-clock-history text-secondary"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="!category.kpis || category.kpis.length === 0">
                  <td colspan="7" class="text-center text-muted py-3">
                    No KPIs defined for this dimension.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for History -->
    <!-- Trend Modal (Chart) -->
    <div class="modal fade" id="trendModal" tabindex="-1" aria-hidden="true" ref="trendModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-graph-up me-2"></i> Trend: {{ selectedKpi?.name }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="text-center py-5">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else style="height: 400px; position: relative">
              <KpiTrendChart
                v-if="chartData.labels"
                :chartData="chartData"
                :chartOptions="chartOptions"
              />
              <p v-else class="text-center text-muted mt-5">No data available.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal (Table) -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true" ref="historyModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-secondary text-white">
            <h5 class="modal-title">
              <i class="bi bi-clock-history me-2"></i> History: {{ selectedKpi?.name }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="text-center py-5">
              <div class="spinner-border text-secondary"></div>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-bordered table-striped text-center">
                <thead>
                  <tr>
                    <th>Date/Period</th>
                    <th>Target</th>
                    <th>Actual</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in historyList" :key="h.id">
                    <td>{{ formatDate(h.period_date) }}</td>
                    <td>{{ h.target_value_snapshot }}</td>
                    <td>{{ h.actual_value }}</td>
                    <td>
                      <span v-if="checkPassHistory(h)" class="badge bg-success">Pass</span>
                      <span v-else class="badge bg-danger">Fail</span>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary me-2" @click="editHistory(h)" title="แก้ไข">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" @click="deleteHistory(h)" title="ลบ">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="historyList.length === 0">
                    <td colspan="4" class="text-muted">No history data found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- KPI Entry Modal -->
    <KpiEntryModal ref="entryModal" @saved="fetchData" />
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import KpiTrendChart from '../../components/KpiTrendChart.vue';
import KpiEntryModal from '../../components/KpiEntryModal.vue';

// Register specific compenents for Doughnut
ChartJS.register(ArcElement, Tooltip, Legend);

export default {
  name: 'KpiDashboardView',
  components: {
    KpiTrendChart,
    KpiEntryModal,
    DoughnutChart: Doughnut
  },
  data() {
    return {
      loading: true,
      categories: [],
      summary: {
        total: 0,
        passed: 0,
        failed: 0,
        warning: 0,
        passedPercent: 0,
        failedPercent: 0
      },
      selectedKpi: null,
      historyLoading: false,
      historyList: [],
      trendModalInstance: null,
      historyModalInstance: null,
      selectedYear: null,
      selectedLevel: '',
      userDepartment: '',
      yearList: [],
      searchQuery: '',
      chartData: {},
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Performance Trend' }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    };
  },
  computed: {
    isAdmin() {
      // Basic check for admin departments
      return (
        this.userDepartment.includes('กลุ่มงานสุขภาพดิจิทัล') ||
        this.userDepartment.includes('ประกัน') ||
        this.userDepartment === 'admin'
      );
    },
    filteredCategories() {
      let result = this.categories;

      // Filter by selected level
      if (this.selectedLevel) {
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          return { ...cat, kpis: cat.kpis.filter(kpi => kpi.kpi_level === this.selectedLevel) };
        });
      }

      // Filter by search query
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          const matchedKpis = cat.kpis.filter((kpi) => {
            const name = kpi.name ? kpi.name.toLowerCase() : '';
            const person = kpi.responsible_person ? kpi.responsible_person.toLowerCase() : '';
            const desc = kpi.description ? kpi.description.toLowerCase() : '';
            const level = kpi.kpi_level ? kpi.kpi_level.toLowerCase() : '';
            return name.includes(query) || person.includes(query) || desc.includes(query) || level.includes(query);
          });
          return { ...cat, kpis: matchedKpis };
        });
      }

      return result.filter(cat => cat.kpis && cat.kpis.length > 0);
    },
    availableLevels() {
      const levels = new Set();
      this.categories.forEach(cat => {
        if (cat.kpis) {
          cat.kpis.forEach(kpi => {
            if (kpi.kpi_level) {
              levels.add(kpi.kpi_level);
            }
          });
        }
      });
      return Array.from(levels).sort();
    },
    doughnutChartData() {
      return {
        labels: ['Passed', 'Failed', 'Warning'],
        datasets: [
          {
            backgroundColor: ['#198754', '#dc3545', '#ffc107'],
            borderWidth: 0,
            data: [this.summary.passed || 0, this.summary.failed || 0, this.summary.warning || 0]
          }
        ]
      };
    },
    doughnutChartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed !== null) {
                  label += context.parsed;
                }
                return label;
              }
            }
          }
        },
        cutout: '75%'
      };
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.generateYearList();
    // Fiscal Year Logic: Oct (9) onwards is next year
    const d = new Date();
    if (d.getMonth() >= 9) {
      this.selectedYear = d.getFullYear() + 543 + 1;
    } else {
      this.selectedYear = d.getFullYear() + 543;
    }
    this.fetchData();
  },
  methods: {
    async fetchUserProfile() {
      try {
        const token = localStorage.getItem('user_token');
        if (!token) return;
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data.status === 'success') {
          this.userDepartment = response.data.department || '';
        }
      } catch (e) {
        console.error('Failed to load user profile', e);
      }
    },
    getFrequencyLabel(type) {
      if (type === 'quarter') return 'รายไตรมาส';
      if (type === 'Semiannual report') return 'รายครึ่งปี';
      if (type === 'year') return 'รายปีงบประมาณ';
      return 'รายเดือน';
    },
    generateYearList() {
      const current = new Date().getFullYear() + 543;
      // Range: Next Year down to 5 years ago
      for (let i = current + 1; i >= current - 5; i--) {
        this.yearList.push(i);
      }
    },
    async fetchHistoryData(kpiId) {
      this.historyLoading = true;
      try {
        const res = await axios.get(`/api-digital/kpi/get_kpi_history.php?kpi_id=${kpiId}`);
        if (res.data.status === 'success') {
          return res.data.data;
        }
        return [];
      } catch (e) {
        console.error(e);
        return [];
      } finally {
        this.historyLoading = false;
      }
    },
    async openTrendModal(kpi) {
      this.selectedKpi = kpi;
      let el = this.$refs.trendModal;
      if (!el) el = document.getElementById('trendModal');
      if (el) {
        if (!this.trendModalInstance) this.trendModalInstance = new Modal(el);
        this.trendModalInstance.show();
      }
      const data = await this.fetchHistoryData(kpi.id);
      this.prepareChart(data);
    },
    async openHistoryModal(kpi) {
      this.selectedKpi = kpi;
      let el = this.$refs.historyModal;
      if (!el) el = document.getElementById('historyModal');
      if (el) {
        if (!this.historyModalInstance) this.historyModalInstance = new Modal(el);
        this.historyModalInstance.show();
      }
      this.historyList = await this.fetchHistoryData(kpi.id);
    },
    editHistory(h) {
      if (this.historyModalInstance) {
        this.historyModalInstance.hide();
      }
      
      const dateParts = h.period_date.split('-');
      const y = parseInt(dateParts[0]);
      const m = parseInt(dateParts[1]);
      
      let yearThai = y + 543;
      let periodNum = m;
      
      const periodicity = this.selectedKpi?.kpi_periodicity || 'month';
      
      if (periodicity === 'quarter') {
        if (m === 10) { periodNum = 1; yearThai = y + 1 + 543; }
        else if (m === 1) { periodNum = 2; yearThai = y + 543; }
        else if (m === 4) { periodNum = 3; yearThai = y + 543; }
        else if (m === 7) { periodNum = 4; yearThai = y + 543; }
      } else if (periodicity === 'Semiannual report') {
        if (m === 10) { periodNum = 1; yearThai = y + 1 + 543; }
        else if (m === 4) { periodNum = 2; yearThai = y + 543; }
      } else if (periodicity === 'year') {
        yearThai = y + 1 + 543;
        periodNum = 1;
      } else {
        periodNum = m;
        if (m >= 10) {
          yearThai = y + 1 + 543;
        } else {
          yearThai = y + 543;
        }
      }
      
      this.$refs.entryModal.openForEdit(this.selectedKpi, {
        year_thai: yearThai,
        period_number: periodNum,
        actual_value: h.actual_value,
        numerator: h.numerator || '',
        denominator: h.denominator || ''
      });
    },
    async deleteHistory(h) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบข้อมูลผลการดำเนินงานนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/kpi/delete_kpi_result.php', { id: h.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1500, showConfirmButton: false });
            this.historyList = await this.fetchHistoryData(this.selectedKpi.id);
            this.fetchData();
          } else {
            Swal.fire('Error', res.data.message || 'ลบไม่สำเร็จ', 'error');
          }
        } catch (e) {
          console.error(e);
          Swal.fire('Error', 'Connection Error', 'error');
        }
      }
    },
    prepareChart(history) {
      const sorted = [...history].reverse();
      this.chartData = {
        labels: sorted.map((h) => this.formatDate(h.period_date)),
        datasets: [
          {
            label: 'Actual',
            data: sorted.map((h) => h.actual_value),
            borderColor: '#304ffe',
            backgroundColor: 'rgba(48, 79, 254, 0.1)',
            tension: 0.3,
            fill: true
          },
          {
            label: 'Target',
            data: sorted.map((h) => h.target_value_snapshot),
            borderColor: '#f44336',
            borderDash: [5, 5],
            fill: false
          }
        ]
      };
    },
    formatDate(d) {
      if (!d) return '-';
      return new Date(d).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    checkPassHistory(h) {
      const op = this.selectedKpi?.target_operator || '>=';
      const actual = parseFloat(h.actual_value);
      const target = parseFloat(h.target_value_snapshot);
      if (op === '>=') return actual >= target;
      if (op === '<=') return actual <= target;
      if (op === '>') return actual > target;
      if (op === '<') return actual < target;
      if (op === '=') return actual == target;
      return false;
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(
          `/api-digital/kpi/get_kpi_data.php?action=getAll&year=${this.selectedYear}`
        );
        if (response.data.status === 'success') {
          this.categories = response.data.data;
          this.calculateSummary();
        } else {
          Swal.fire('Error', response.data.message || 'Failed to fetch data', 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Network error or API issue', 'error');
      } finally {
        this.loading = false;
      }
    },
    checkStatus(kpi) {
      if (kpi.actual_value === null) return 'nodata';

      const actual = parseFloat(kpi.actual_value);
      const target = parseFloat(kpi.target_value);
      const op = kpi.target_operator;

      switch (op) {
        case '>=':
          return actual >= target ? 'pass' : 'fail';
        case '<=':
          return actual <= target ? 'pass' : 'fail';
        case '>':
          return actual > target ? 'pass' : 'fail';
        case '<':
          return actual < target ? 'pass' : 'fail';
        case '=':
          return actual == target ? 'pass' : 'fail';
        default:
          return 'nodata';
      }
    },
    getExpectedPeriodDates(periodicity) {
      if (!this.selectedYear) return [];
      
      const gYear = this.selectedYear - 543;
      const prevYear = gYear - 1;

      const now = new Date();
      const currentCalendarMonth = now.getMonth() + 1;
      const currentCalendarYear = now.getFullYear();
      const currentFiscalYear = currentCalendarMonth >= 10 ? currentCalendarYear + 543 + 1 : currentCalendarYear + 543;

      const selectedYear = this.selectedYear;

      const currentFiscalMonth = currentCalendarMonth >= 10 ? currentCalendarMonth - 9 : currentCalendarMonth + 3;
      const currentFiscalQuarter = Math.ceil(currentFiscalMonth / 3);
      const currentFiscalHalf = Math.ceil(currentFiscalQuarter / 2);

      const isArrived = (fiscalIndex, periodType) => {
        if (selectedYear < currentFiscalYear) return true;
        if (selectedYear > currentFiscalYear) return false;
        if (periodType === 'month') return fiscalIndex <= currentFiscalMonth;
        if (periodType === 'quarter') return fiscalIndex <= currentFiscalQuarter;
        if (periodType === 'Semiannual report') return fiscalIndex <= currentFiscalHalf;
        if (periodType === 'year') return true; 
        return true;
      };

      let expected = [];
      if (periodicity === 'quarter') {
        if (isArrived(1, 'quarter')) expected.push(`${prevYear}-10-01`);
        if (isArrived(2, 'quarter')) expected.push(`${gYear}-01-01`);
        if (isArrived(3, 'quarter')) expected.push(`${gYear}-04-01`);
        if (isArrived(4, 'quarter')) expected.push(`${gYear}-07-01`);
      } else if (periodicity === 'Semiannual report') {
        if (isArrived(1, 'Semiannual report')) expected.push(`${prevYear}-10-01`);
        if (isArrived(2, 'Semiannual report')) expected.push(`${gYear}-04-01`);
      } else if (periodicity === 'year') {
        if (isArrived(1, 'year')) expected.push(`${prevYear}-10-01`);
      } else {
        // month
        for (let i = 1; i <= 12; i++) {
          const fiscalIndex = i >= 10 ? i - 9 : i + 3;
          if (isArrived(fiscalIndex, 'month')) {
            const y = i >= 10 ? prevYear : gYear;
            const m = String(i).padStart(2, '0');
            expected.push(`${y}-${m}-01`);
          }
        }
      }
      return expected;
    },
    getMissingPeriods(kpi) {
      if (!kpi) return [];
      const reported = kpi.reported_periods ? kpi.reported_periods.split(',') : [];
      const expected = this.getExpectedPeriodDates(kpi.kpi_periodicity || 'month');
      return expected.filter(d => !reported.includes(d));
    },
    showMissingPeriodsDetails(kpi) {
      const missingDates = this.getMissingPeriods(kpi);
      if (missingDates.length === 0) return;
      
      let htmlContent = '<div class="text-start mt-3"><ul class="list-group list-group-flush border rounded">';
      missingDates.forEach(d => {
        const type = kpi.kpi_periodicity || 'month';
        let label = this.formatDate(d);
        
        if (type === 'quarter') {
            const m = parseInt(d.split('-')[1]);
            let q = 1;
            if (m === 1) q = 2; else if (m === 4) q = 3; else if (m === 7) q = 4;
            label = `ไตรมาสที่ ${q} (${this.formatDate(d)})`;
        } else if (type === 'Semiannual report') {
            const m = parseInt(d.split('-')[1]);
            let h = m === 10 ? 1 : 2;
            label = `ครึ่งปีที่ ${h} (${this.formatDate(d)})`;
        } else if (type === 'year') {
            label = `ปีงบประมาณ ${parseInt(d.split('-')[0]) + 1 + 543}`;
        } else {
            // format just month and year for monthly
            const dateObj = new Date(d);
            label = dateObj.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
        }
        
        htmlContent += `<li class="list-group-item text-danger"><i class="bi bi-x-circle me-2"></i> ${label}</li>`;
      });
      htmlContent += '</ul></div>';

      Swal.fire({
        title: 'รอบที่ยังไม่ได้รายงาน',
        html: htmlContent,
        icon: 'warning',
        confirmButtonColor: '#304ffe',
        confirmButtonText: 'ปิดหน้าต่าง'
      });
    },
    calculateSummary() {
      let total = 0;
      let passed = 0;
      let failed = 0;

      this.categories.forEach((cat) => {
        if (cat.kpis) {
          cat.kpis.forEach((kpi) => {
            total++;
            const status = this.checkStatus(kpi);
            if (status === 'pass') passed++;
            else if (status === 'fail') failed++;
          });
        }
      });

      this.summary.total = total;
      this.summary.passed = passed;
      this.summary.failed = failed;
      this.summary.warning = 0; // Logic for warning can be added (e.g. within 5% of target)

      this.summary.passedPercent = total > 0 ? Math.round((passed / total) * 100) : 0;
      this.summary.failedPercent = total > 0 ? Math.round((failed / total) * 100) : 0;
    },
    openEntryModal(kpi) {
      this.$refs.entryModal.open(kpi);
    },
    // Removed goToEntry as it's replaced by modal
    viewDetails(kpi) {
      // Legacy method, replaced by openHistoryModal
      this.openHistoryModal(kpi);
    }
  }
};
</script>

<style scoped>
.card {
  transition: transform 0.2s;
}
.card-header {
  background-color: transparent;
}
</style>
