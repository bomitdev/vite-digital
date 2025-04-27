<template>
  <div class="dashboard-container">
    <div class="container mt-4">
      <!-- Date Range Selector -->
      <div class="date-range-card card shadow-sm mb-4">
        <!-- Header Section -->
        <div class="dashboard-header mt-4">
          <h1 class="dashboard-title">Dashboard ข้อมูลผู้มารับบริการ</h1>
        </div>
        <div class="card-body">
          <h5 class="card-title">เลือกช่วงวันที่ ที่ต้องการดูข้อมูล</h5>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">วันที่เริ่มต้น:</label>
              <input type="date" v-model="startDate" class="form-control" />
            </div>
            <div class="col-md-5">
              <label class="form-label">วันที่สิ้นสุด:</label>
              <input type="date" v-model="endDate" class="form-control" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button @click="fetchData" class="btn btn-primary w-100 py-2">
                <i class="bi bi-search me-2"></i>ดูข้อมูล
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div v-if="loading" class="text-center my-5 py-5">
        <div
          class="spinner-border text-primary"
          style="width: 3rem; height: 3rem"
          role="status"
        >
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Data Cards -->
      <div class="row g-4" v-if="!loading">
        <!-- OPD Card -->
        <div class="col-md-4">
          <div
            class="card text-white bg-primary mb-3 h-100 hover-effect"
            @click="goToOPDReport"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ผู้รับบริการผู้ป่วยนอก (OPD)</h5>
                <i class="bi bi-people-fill fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.opd_all) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- IPD Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div
            class="card text-white bg-red-300 mb-3 h-100 hover-effect"
            @click="goToIPDReport()"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ผู้รับบริการผู้ป่วยใน (IPD)</h5>
                <i class="bi bi-people-fill fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.ipd_all) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>
        <!-- income Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-red-300 mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ค่าใช้จ่ายทั้งหมด (OPD)</h5>
                <i class="bi bi-people-fill fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.sum_income) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยนอก</small>
              </div>
            </div>
          </div>
        </div>
        <hr />

        <!-- ER Card -->
        <h2 class="dashboard-title">ข้อมูลผู้มารับบริการแยกตามแผนก</h2>
        <div class="col-12 col-sm-6 col-md-4">
          <div
            class="card text-white bg-danger mb-3 h-100 hover-effect"
            @click="goToERReport"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ER (อุบัติเหตุฉุกเฉิน)</h5>
                <i class="bi bi-ambulance fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.er) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Dent Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div
            class="card text-white bg-pink mb-3 h-100 hover-effect"
            @click="goToDentalReport()"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ทันตกรรม</h5>
                <i class="bi bi-tooth fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.dent) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- PCU Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-purple mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">ปฐมภูมิและองค์รวม</h5>
                <i class="bi bi-heart fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.pcu) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">บริการปฐมภูมิ</small>
              </div>
            </div>
          </div>
        </div>

        <!-- TTM Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div
            class="card text-white bg-green mb-3 h-100 hover-effect"
            @click="goToTTMReport()"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">แพทย์แผนไทยฯ</h5>
                <i class="bi bi-heart fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.health_med) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- กายภาพ Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div
            class="card text-white bg-queen mb-3 h-100 hover-effect"
            @click="goToPhysicReport()"
            style="cursor: pointer"
          >
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">กายภาพ</h5>
                <i class="bi bi-heart fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.physic) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">คลิกเพื่อดูรายงาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- จิตเวช Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-grayscale mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">จิตเวชและยาเสพติด</h5>
                <i class="bi bi-heart fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.pd) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">จิตเวชและยาเสพติด</small>
              </div>
            </div>
          </div>
        </div>
        <hr />

        <!-- DM Card -->
        <h2 class="dashboard-title">คลินิกพิเศษ</h2>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-success mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">DM (โรคเบาหวาน)</h5>
                <i class="bi bi-droplet-half fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.dm) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยโรคเบาหวาน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- HT Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-warning mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">HT (โรคความดันโลหิตสูง)</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.ht) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยโรคความดัน</small>
              </div>
            </div>
          </div>
        </div>

        <!-- CKD Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-teal mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">CKD (โรคไต)</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.ckd) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยโรคไต</small>
              </div>
            </div>
          </div>
        </div>

        <!-- TB Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-burlywood mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">TB (โรควัณโรค)</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.tb) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยโรคไต</small>
              </div>
            </div>
          </div>
        </div>

        <!-- COPD Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-dark-orchid mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">COPD (โรคปอดอุดกั้นเรื้อรัง)</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.copd) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50"
                  >ผู้ป่วยโรคปอดอุดกั้นเรื้อรัง</small
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Thyroid Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-dark-orange mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">Thyroid (โรคไทรอยด์)</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.thyroid) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ผู้ป่วยโรคไทรอยด์</small>
              </div>
            </div>
          </div>
        </div>
        <hr />

        <!-- Refer Out Card -->
        <h2 class="dashboard-title">Refer</h2>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-teal mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">Refer Out</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.refer_out) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">Refer Out</small>
              </div>
            </div>
          </div>
        </div>
        <!-- Refer In Card -->
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-cyan mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">Refer In</h5>
                <i class="bi bi-heart-pulse fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.refer_in) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">Refer In</small>
              </div>
            </div>
          </div>
        </div>
        <hr />

        <!-- survail Card -->
        <h2 class="dashboard-title">โรคระบาดวิทยา</h2>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="card text-white bg-max-red mb-3 h-100 hover-effect">
            <div class="card-body d-flex flex-column">
              <div
                class="d-flex justify-content-between align-items-center mb-3"
              >
                <h5 class="card-title mb-0">โรคระบาดวิทยา</h5>
                <i class="bi bi-virus2 fs-4"></i>
              </div>
              <h2 class="card-value">{{ formatNumber(data.surveil) }}</h2>
              <div class="mt-auto pt-2">
                <small class="text-white-50">ระบาดวิทยา</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      startDate: "",
      endDate: "",
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
        sum_income: 0,
      },
      loading: false,
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
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(
          `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/get_dashboard.php`,
          {
            params: { start_date: this.startDate, end_date: this.endDate },
          }
        );
        this.data = response.data;
      } catch (error) {
        console.error("Error fetching data:", error);
        // You might want to add user notification here
      } finally {
        this.loading = false;
      }
    },
    goToOPDReport() {
      this.$router.push({
        path: "/opddashboardchart",
      });
    },
    goToIPDReport() {
      this.$router.push({
        path: "/ipd-report",
      });
    },
    goToERReport() {
      this.$router.push({
        path: "/er-report",
      });
    },
    goToDentalReport() {
      this.$router.push({
        path: "/dental-report",
      });
    },
    goToTTMReport() {
      this.$router.push({
        path: "/ttm-report",
      });
    },
    goToPhysicReport() {
      this.$router.push({
        path: "/physic-report",
      });
    },
    formatNumber(num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
  },
  mounted() {
    const fiscalYear = this.computedFiscalYear;
    this.startDate = `${fiscalYear - 1}-10-01`;
    this.endDate = `${fiscalYear}-09-30`;
    this.fetchData();
  },
};
</script>

<style scoped>
.dashboard-container {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 2rem 0;
}

.dashboard-header {
  text-align: center;
  padding: 1rem 0;
}

.dashboard-title {
  color: purple;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.date-range-card {
  background-color: #fff;
  border-radius: 10px;
}

.card {
  border: none;
  border-radius: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-title {
  font-weight: 600;
  font-size: 1rem;
}

.card-value {
  font-weight: 700;
  font-size: 2.2rem;
  margin: 0.5rem 0;
}

.hover-effect:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.bg-pink {
  background-color: #d63384 !important;
}
.bg-purple {
  background-color: #6f42c1 !important;
}
.bg-green {
  background-color: #198754 !important;
}
.bg-queen {
  background-color: #436b95 !important;
}
.bg-max-red {
  background-color: #d92121 !important;
}
.bg-grayscale {
  background-color: #7a7a7a !important;
}
.bg-teal {
  background-color: #20c997 !important;
}
.bg-cyan {
  background-color: #0dcaf0 !important;
}
.bg-red-100 {
  background-color: #f8bbd0 !important;
}
.bg-red-300 {
  background-color: #f36c60 !important;
}
.bg-burlywood {
  background-color: #deb887 !important;
}
.bg-dark-cyan {
  background-color: #008b8b !important;
}
.bg-dark-magenta {
  background-color: #8b008b !important;
}
.bg-dark-orchid {
  background-color: #9932cc !important;
}
.bg-dark-orange {
  background-color: #ff8c00 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-title {
    font-size: 1.5rem;
  }

  .card-value {
    font-size: 1.8rem;
  }

  .date-range-card .col-md-5,
  .date-range-card .col-md-2 {
    width: 100%;
    margin-bottom: 1rem;
  }
}
</style>
