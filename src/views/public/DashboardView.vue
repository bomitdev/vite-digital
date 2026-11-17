<template>
  <div class="dashboard-container">
    <div class="container py-4">
      <!-- Header & Date Range Selector -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <div class="header-icon-box">
                <i class="bi bi-bar-chart-line-fill"></i>
              </div>
              <div>
                <h3 class="fw-bold text-dark mb-0">Dashboard ข้อมูลผู้มารับบริการ</h3>
                <small class="text-muted">สรุปภาพรวมสถิติผู้ป่วยนอก ผู้ป่วยใน ค่าใช้จ่าย แยกตามแผนกและคลินิกพิเศษ</small>
              </div>
            </div>

            <!-- Quick Date Presets -->
            <div class="d-flex flex-wrap gap-1">
              <button
                v-for="preset in datePresets"
                :key="preset.key"
                type="button"
                class="btn btn-sm rounded-pill px-3 fw-semibold"
                :class="activePreset === preset.key ? 'btn-primary' : 'btn-light border text-secondary'"
                @click="applyPreset(preset.key)"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>

          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
              <label class="form-label small fw-bold text-secondary mb-1">วันที่เริ่มต้น</label>
              <input type="date" v-model="startDate" @change="activePreset = ''" class="form-control" />
            </div>
            <div class="col-12 col-md-5">
              <label class="form-label small fw-bold text-secondary mb-1">วันที่สิ้นสุด</label>
              <input type="date" v-model="endDate" @change="activePreset = ''" class="form-control" />
            </div>
            <div class="col-12 col-md-2">
              <button @click="fetchData" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold" :disabled="loading">
                <i class="bi bi-search me-2"></i>ดูข้อมูล
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div v-if="loading" class="text-center my-5 py-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">กำลังโหลดข้อมูล...</p>
      </div>

      <template v-else>
        <!-- Top 3 KPI Cards -->
        <div class="row g-3 mb-4">
          <!-- OPD -->
          <div class="col-12 col-md-4">
            <div class="card kpi-card kpi-blue border-0 shadow-sm rounded-4 h-100 cursor-pointer" @click="goToOPDReport">
              <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="kpi-pill bg-primary-subtle text-primary">ผู้ป่วยนอก (OPD)</span>
                  <div class="kpi-icon bg-primary text-white">
                    <i class="bi bi-people-fill"></i>
                  </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                  <h2 class="fw-bold text-dark mb-0">{{ formatNumber(data.opd_all) }}</h2>
                  <span class="text-muted small">ครั้ง</span>
                </div>
                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center small text-primary fw-semibold">
                  <span>คลิกเพื่อดูรายงาน</span>
                  <i class="bi bi-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- IPD -->
          <div class="col-12 col-md-4">
            <div class="card kpi-card kpi-purple border-0 shadow-sm rounded-4 h-100 cursor-pointer" @click="goToIPDReport">
              <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="kpi-pill bg-purple-subtle text-purple">ผู้ป่วยใน (IPD)</span>
                  <div class="kpi-icon bg-purple text-white">
                    <i class="bi bi-hospital-fill"></i>
                  </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                  <h2 class="fw-bold text-dark mb-0">{{ formatNumber(data.ipd_all) }}</h2>
                  <span class="text-muted small">ราย</span>
                </div>
                <div class="mt-auto pt-2 d-flex justify-content-between align-items-center small text-purple fw-semibold">
                  <span>คลิกเพื่อดูรายงาน</span>
                  <i class="bi bi-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Total OPD Expense -->
          <div class="col-12 col-md-4">
            <div class="card kpi-card kpi-green border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="kpi-pill bg-success-subtle text-success">ค่าใช้จ่ายทั้งหมด (OPD)</span>
                  <div class="kpi-icon bg-success text-white">
                    <i class="bi bi-cash-stack"></i>
                  </div>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                  <h2 class="fw-bold text-dark mb-0">{{ formatNumber(data.sum_income) }}</h2>
                  <span class="text-muted small">บาท</span>
                </div>
                <div class="mt-auto pt-2 small text-muted">
                  เฉลี่ยประมาณ <strong class="text-success">{{ avgCostPerVisit }}</strong> บาท / ครั้ง
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Department Comparison Chart -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>กราฟเปรียบเทียบผู้มารับบริการแต่ละแผนก
              </h5>
              <small class="text-muted">เปรียบเทียบจำนวนผู้รับบริการแยกตามแผนกและคลินิกพิเศษ</small>
            </div>
            <div class="btn-group btn-group-sm">
              <button
                type="button"
                class="btn"
                :class="chartType === 'bar' ? 'btn-primary' : 'btn-outline-secondary'"
                @click="chartType = 'bar'"
              >
                <i class="bi bi-bar-chart me-1"></i>กราฟแท่ง
              </button>
              <button
                type="button"
                class="btn"
                :class="chartType === 'line' ? 'btn-primary' : 'btn-outline-secondary'"
                @click="chartType = 'line'"
              >
                <i class="bi bi-graph-up me-1"></i>กราฟเส้น
              </button>
            </div>
          </div>
          <div class="card-body p-4" style="height: 360px; position: relative">
            <BarChart v-if="chartType === 'bar'" :data="barChartData" :options="chartOptions" />
            <LineChart v-else :data="lineChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Section: Departments -->
        <div class="mb-4">
          <h5 class="section-heading mb-3">
            <i class="bi bi-building text-primary me-2"></i>ข้อมูลผู้มารับบริการแยกตามแผนก
          </h5>
          <div class="row g-3">
            <div v-for="card in departmentCards" :key="card.key" class="col-12 col-sm-6 col-lg-4">
              <div
                class="card stat-mini-card border-0 shadow-sm rounded-4 h-100"
                :class="{ 'cursor-pointer': Boolean(card.action) }"
                :style="{ borderLeft: `4px solid ${card.color}` }"
                @click="card.action && card.action()"
              >
                <div class="card-body p-3 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-semibold text-dark">{{ card.title }}</span>
                    <span class="mini-icon" :style="{ backgroundColor: card.bg, color: card.color }">
                      <i class="bi" :class="card.icon"></i>
                    </span>
                  </div>
                  <div class="d-flex align-items-baseline justify-content-between mt-auto">
                    <h3 class="fw-bold mb-0 text-dark">{{ formatNumber(data[card.key] || 0) }}</h3>
                    <span v-if="data.opd_all > 0" class="badge bg-light text-secondary border rounded-pill">
                      {{ (((data[card.key] || 0) / data.opd_all) * 100).toFixed(1) }}% ของ OPD
                    </span>
                  </div>
                  <small class="mt-2" :class="card.action ? 'text-primary fw-semibold' : 'text-muted'">
                    {{ card.action ? 'คลิกเพื่อดูรายงาน →' : card.subtitle }}
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Special Clinics -->
        <div class="mb-4">
          <h5 class="section-heading mb-3">
            <i class="bi bi-heart-pulse-fill text-danger me-2"></i>คลินิกพิเศษ
          </h5>
          <div class="row g-3">
            <div v-for="card in clinicCards" :key="card.key" class="col-12 col-sm-6 col-lg-4">
              <div
                class="card stat-mini-card border-0 shadow-sm rounded-4 h-100"
                :style="{ borderLeft: `4px solid ${card.color}` }"
              >
                <div class="card-body p-3 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-semibold text-dark">{{ card.title }}</span>
                    <span class="mini-icon" :style="{ backgroundColor: card.bg, color: card.color }">
                      <i class="bi" :class="card.icon"></i>
                    </span>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatNumber(data[card.key] || 0) }}</h3>
                  <small class="text-muted mt-auto">{{ card.subtitle }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Refer, Surveillance & Telemedicine -->
        <div class="mb-4">
          <h5 class="section-heading mb-3">
            <i class="bi bi-activity text-success me-2"></i>Refer, ระบาดวิทยา และบริการทางไกล
          </h5>
          <div class="row g-3">
            <div v-for="card in otherCards" :key="card.key" class="col-12 col-sm-6 col-lg-3">
              <div
                class="card stat-mini-card border-0 shadow-sm rounded-4 h-100"
                :style="{ borderLeft: `4px solid ${card.color}` }"
              >
                <div class="card-body p-3 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-semibold text-dark">{{ card.title }}</span>
                    <span class="mini-icon" :style="{ backgroundColor: card.bg, color: card.color }">
                      <i class="bi" :class="card.icon"></i>
                    </span>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatNumber(data[card.key] || 0) }}</h3>
                  <small class="text-muted mt-auto">{{ card.subtitle }}</small>
                </div>
              </div>
            </div>

            <!-- Telemedicine -->
            <div class="col-12 col-sm-6 col-lg-3">
              <div
                class="card stat-mini-card border-0 shadow-sm rounded-4 h-100 cursor-pointer"
                style="border-left: 4px solid #2563eb"
                @click="goToTelemedicineReport"
              >
                <div class="card-body p-3 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-semibold text-dark">บริการทางไกล (Telemedicine)</span>
                    <span class="mini-icon" style="background-color: #eff6ff; color: #2563eb">
                      <i class="bi bi-telephone-fill"></i>
                    </span>
                  </div>
                  <h3 class="fw-bold mb-2 text-dark">{{ formatNumber(data.telemedicine || 0) }}</h3>
                  <div class="mt-auto d-flex flex-wrap align-items-center gap-1 small">
                    <span class="badge bg-light text-dark border rounded-pill">
                      วันนี้: {{ formatNumber(data.telemedicine_today || 0) }}
                    </span>
                    <span
                      v-if="(data.telemedicine_today || 0) >= (data.telemedicine_yesterday || 0)"
                      class="badge bg-success-subtle text-success rounded-pill"
                    >
                      +{{ formatNumber((data.telemedicine_today || 0) - (data.telemedicine_yesterday || 0)) }}
                    </span>
                    <span v-else class="badge bg-danger-subtle text-danger rounded-pill">
                      -{{ formatNumber((data.telemedicine_yesterday || 0) - (data.telemedicine_today || 0)) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Line, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend);

export default {
  components: {
    LineChart: Line,
    BarChart: Bar
  },
  data() {
    return {
      startDate: '',
      endDate: '',
      activePreset: 'fiscal_current',
      chartType: 'bar',
      datePresets: [
        { key: 'today', label: 'วันนี้' },
        { key: 'month', label: 'เดือนนี้' },
        { key: 'fiscal_current', label: 'ปีงบปัจจุบัน' },
        { key: 'fiscal_prev', label: 'ปีงบก่อน' }
      ],
      data: {
        opd_all: 0,
        ipd_all: 0,
        dm: 0,
        ht: 0,
        ckd: 0,
        tb: 0,
        copd: 0,
        thyroid: 0,
        physic: 0,
        physic_ipd: 0,
        health_med: 0,
        dent: 0,
        surveil: 0,
        er: 0,
        pcu: 0,
        pd: 0,
        refer_out: 0,
        refer_in: 0,
        telemedicine: 0,
        telemedicine_today: 0,
        telemedicine_yesterday: 0,
        sum_income: 0
      },
      loading: false
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
    avgCostPerVisit() {
      const visits = Number(this.data.opd_all) || 0;
      const income = Number(this.data.sum_income) || 0;
      if (visits <= 0) return '0.00';
      return (income / visits).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    departmentCards() {
      return [
        { key: 'er', title: 'ER (อุบัติเหตุฉุกเฉิน)', icon: 'bi-ambulance', color: '#dc2626', bg: '#fef2f2', action: this.goToERReport },
        { key: 'dent', title: 'ทันตกรรม', icon: 'bi-tooth', color: '#db2777', bg: '#fdf2f8', action: this.goToDentalReport },
        { key: 'pcu', title: 'ปฐมภูมิและองค์รวม', icon: 'bi-heart-fill', color: '#7c3aed', bg: '#f5f3ff', subtitle: 'บริการปฐมภูมิ' },
        { key: 'health_med', title: 'แพทย์แผนไทยฯ', icon: 'bi-flower1', color: '#16a34a', bg: '#f0fdf4', action: this.goToTTMReport },
        { key: 'physic', title: 'กายภาพบำบัด', icon: 'bi-person-walking', color: '#0284c7', bg: '#f0f9ff', action: this.goToPhysicReport },
        { key: 'pd', title: 'จิตเวชและยาเสพติด', icon: 'bi-chat-heart-fill', color: '#475569', bg: '#f8fafc', subtitle: 'จิตเวชและยาเสพติด' }
      ];
    },
    clinicCards() {
      return [
        { key: 'dm', title: 'DM (โรคเบาหวาน)', icon: 'bi-droplet-half', color: '#16a34a', bg: '#f0fdf4', subtitle: 'ผู้ป่วยโรคเบาหวาน' },
        { key: 'ht', title: 'HT (โรคความดันโลหิตสูง)', icon: 'bi-heart-pulse', color: '#d97706', bg: '#fffbeb', subtitle: 'ผู้ป่วยโรคความดัน' },
        { key: 'ckd', title: 'CKD (โรคไต)', icon: 'bi-activity', color: '#0d9488', bg: '#f0fdfa', subtitle: 'ผู้ป่วยโรคไต' },
        { key: 'tb', title: 'TB (โรควัณโรค)', icon: 'bi-lungs-fill', color: '#b45309', bg: '#fef3c7', subtitle: 'ผู้ป่วยโรควัณโรค' },
        { key: 'copd', title: 'COPD (โรคปอดอุดกั้นเรื้อรัง)', icon: 'bi-wind', color: '#9333ea', bg: '#faf5ff', subtitle: 'ผู้ป่วยโรคปอดอุดกั้นเรื้อรัง' },
        { key: 'thyroid', title: 'Thyroid (โรคไทรอยด์)', icon: 'bi-capsule', color: '#ea580c', bg: '#fff7ed', subtitle: 'ผู้ป่วยโรคไทรอยด์' }
      ];
    },
    otherCards() {
      return [
        { key: 'refer_out', title: 'Refer Out', icon: 'bi-box-arrow-up-right', color: '#0d9488', bg: '#f0fdfa', subtitle: 'ส่งต่อผู้ป่วยออก' },
        { key: 'refer_in', title: 'Refer In', icon: 'bi-box-arrow-in-down-left', color: '#0891b2', bg: '#ecfeff', subtitle: 'รับส่งต่อผู้ป่วยเข้า' },
        { key: 'surveil', title: 'โรคระบาดวิทยา', icon: 'bi-virus2', color: '#dc2626', bg: '#fef2f2', subtitle: 'เฝ้าระวังระบาดวิทยา' }
      ];
    },
    chartItems() {
      const items = [
        { label: 'ER', value: Number(this.data.er) || 0, color: '#dc2626' },
        { label: 'ทันตกรรม', value: Number(this.data.dent) || 0, color: '#db2777' },
        { label: 'ปฐมภูมิฯ', value: Number(this.data.pcu) || 0, color: '#7c3aed' },
        { label: 'แพทย์แผนไทยฯ', value: Number(this.data.health_med) || 0, color: '#16a34a' },
        { label: 'กายภาพ', value: Number(this.data.physic) || 0, color: '#0284c7' },
        { label: 'จิตเวชฯ', value: Number(this.data.pd) || 0, color: '#64748b' },
        { label: 'DM', value: Number(this.data.dm) || 0, color: '#22c55e' },
        { label: 'HT', value: Number(this.data.ht) || 0, color: '#f59e0b' },
        { label: 'CKD', value: Number(this.data.ckd) || 0, color: '#14b8a6' },
        { label: 'TB', value: Number(this.data.tb) || 0, color: '#d97706' },
        { label: 'COPD', value: Number(this.data.copd) || 0, color: '#a855f7' },
        { label: 'Thyroid', value: Number(this.data.thyroid) || 0, color: '#f97316' }
      ];
      return this.chartType === 'bar' ? [...items].sort((a, b) => b.value - a.value) : items;
    },
    barChartData() {
      return {
        labels: this.chartItems.map(i => i.label),
        datasets: [
          {
            label: 'ผู้มารับบริการ',
            backgroundColor: this.chartItems.map(i => i.color),
            borderRadius: 8,
            data: this.chartItems.map(i => i.value)
          }
        ]
      };
    },
    lineChartData() {
      return {
        labels: this.chartItems.map(i => i.label),
        datasets: [
          {
            label: 'ผู้มารับบริการ',
            backgroundColor: '#7c3aed',
            borderColor: '#7c3aed',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#7c3aed',
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: false,
            tension: 0.3,
            data: this.chartItems.map(i => i.value)
          }
        ]
      };
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            padding: 10,
            callbacks: {
              label: (ctx) => ` ${Number(ctx.raw || 0).toLocaleString()} ครั้ง`
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (value) => value.toLocaleString()
            }
          }
        }
      };
    }
  },
  methods: {
    formatIsoDate(d) {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    },
    applyPreset(key) {
      this.activePreset = key;
      const now = new Date();
      if (key === 'today') {
        const s = this.formatIsoDate(now);
        this.startDate = s;
        this.endDate = s;
      } else if (key === 'month') {
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        this.startDate = this.formatIsoDate(first);
        this.endDate = this.formatIsoDate(last);
      } else if (key === 'fiscal_current') {
        const fy = this.computedFiscalYear;
        this.startDate = `${fy - 1}-10-01`;
        this.endDate = `${fy}-09-30`;
      } else if (key === 'fiscal_prev') {
        const fy = this.computedFiscalYear - 1;
        this.startDate = `${fy - 1}-10-01`;
        this.endDate = `${fy}-09-30`;
      }
      this.fetchData();
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(`/api-hosxe/get_dashboard.php`, {
          params: { start_date: this.startDate, end_date: this.endDate }
        });
        this.data = response.data;
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        this.loading = false;
      }
    },
    goToOPDReport() {
      this.$router.push({ path: '/opddashboardchart' });
    },
    goToIPDReport() {
      this.$router.push({ path: '/ipd-report' });
    },
    goToERReport() {
      this.$router.push({ path: '/er-report' });
    },
    goToDentalReport() {
      this.$router.push({ path: '/dental-report' });
    },
    goToTTMReport() {
      this.$router.push({ path: '/ttm-report' });
    },
    goToPhysicReport() {
      this.$router.push({ path: '/physic-report' });
    },
    goToTelemedicineReport() {
      this.$router.push({ path: '/telemedicine-report' });
    },
    formatNumber(num) {
      return (num ?? 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
  },
  mounted() {
    this.applyPreset('fiscal_current');
  }
};
</script>

<style scoped>
.dashboard-container {
  background-color: #f8fafc;
  min-height: 100vh;
}

.header-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
}

.kpi-card,
.stat-mini-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover,
.stat-mini-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 24px -8px rgba(15, 23, 42, 0.12) !important;
}

.kpi-blue {
  border-top: 4px solid #2563eb !important;
}
.kpi-purple {
  border-top: 4px solid #7c3aed !important;
}
.kpi-green {
  border-top: 4px solid #16a34a !important;
}

.kpi-pill {
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
}

.kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.bg-purple {
  background-color: #7c3aed !important;
}
.bg-purple-subtle {
  background-color: #ede9fe !important;
}
.text-purple {
  color: #7c3aed !important;
}

.section-heading {
  font-weight: 700;
  color: #1e293b;
}

.mini-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
</style>
