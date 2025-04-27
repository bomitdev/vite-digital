<template>
  <div class="container py-4">
    <div class="card report-card mb-4">
      <div class="card-header report-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="report-title mb-1">รายงานหัตถการแพทย์แผนไทย</h1>
            <p class="report-subtitle">ดูรายงานการทำหัตถการ กรุณาเลือกวันที่</p>
          </div>
          <i class="fas fa-spa report-icon"></i>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="form-floating">
              <input
                type="date"
                id="startDate"
                v-model="startDate"
                class="form-control"
                @change="validateDates"
              />
              <label for="startDate">วันที่เริ่มต้น:</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating">
              <input
                type="date"
                id="endDate"
                v-model="endDate"
                class="form-control"
                @change="validateDates"
              />
              <label for="endDate">วันที่สิ้นสุด:</label>
            </div>
          </div>
        </div>

        <div class="text-center mb-3">
          <button
            class="btn btn-generate px-4 py-2"
            :disabled="loading || !datesValid"
            @click="fetchData"
          >
            <span v-if="!loading">
              <i class="fas fa-file-alt me-2"></i>ดูรายงาน
            </span>
            <span v-else>
              <span class="spinner-border spinner-border-sm me-2"></span>
              กำลังโหลด...
            </span>
          </button>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="!datesValid" class="alert alert-warning">
          กรุณาเลือกช่วงวันที่ให้ถูกต้อง
        </div>
      </div>
    </div>

    <!-- รายงานผล -->
    <div v-if="data.length" class="results-section">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h2><i class="fas fa-leaf me-2"></i>รายการหัตถการแพทย์แผนไทย</h2>
          <p class="text-muted">
            รายงานตั้งแต่ {{ formattedStartDate }} ถึง {{ formattedEndDate }}
          </p>
        </div>
        <span class="badge bg-primary rounded-pill">
          {{ data.length }} รายการ
        </span>
      </div>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div v-for="item in data" :key="item.id">
          <div class="card procedure-card h-100">
            <div class="card-header procedure-card-header">
              <h5 class="mb-0">
                <span class="badge bg-secondary me-2">#{{ item.id }}</span>
                {{ item.name }}
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-value">{{ formatNumber(item.C_hn) }}</div>
                    <div class="stat-label">จำนวนคน</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-value">{{ formatNumber(item.C_vn) }}</div>
                    <div class="stat-label">จำนวนครั้ง</div>
                  </div>
                </div>
              </div>
              <div class="text-end">
                <small class="text-muted">
                  <i class="fas fa-coins me-1"></i>รวมค่าบริการ:
                  {{
                    item.sum_price !== null
                      ? formatNumber(item.sum_price) + ' บาท'
                      : 'ไม่ระบุ'
                  }}
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="text-end mt-4">
        <small class="text-muted">
          <i class="fas fa-clock me-1"></i>อัปเดตล่าสุด:
          {{ currentTime }}
        </small>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-5">
      <i class="fas fa-leaf fa-3x text-muted mb-3"></i>
      <h4 class="text-muted">ไม่พบข้อมูลหัตถการในช่วงวันที่ที่เลือก</h4>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      startDate: "",
      endDate: "",
      data: [],
      loading: false,
      error: null,
      currentTime: null,
    };
  },
  computed: {
    formattedStartDate() {
      return this.formatDate(this.startDate);
    },
    formattedEndDate() {
      return this.formatDate(this.endDate);
    },
    datesValid() {
      if (!this.startDate || !this.endDate) return false;
      return new Date(this.endDate) >= new Date(this.startDate);
    },
  },
  methods: {
    async fetchData() {
      if (!this.datesValid) return;

      this.loading = true;
      this.error = null;
      this.data = [];

      try {
        const res = await fetch(
          `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/ttm/get_operation_ttm.php?start_date=${this.startDate}&end_date=${this.endDate}`
        );
        const result = await res.json();

        if (res.ok) {
          this.data = (result.data || []).map((item) => ({
            id: item.id,
            name: item.name,
            C_hn: item.C_hn,
            C_vn: item.C_vn,
            sum_price: item.sum_price,
          }));
          this.updateCurrentTime();
        } else {
          this.error = result.error || "เกิดข้อผิดพลาดในการดึงข้อมูล";
        }
      } catch (err) {
        this.error = "ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้";
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    validateDates() {
      if (new Date(this.endDate) < new Date(this.startDate)) {
        this.error = "วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มต้น";
      } else {
        this.error = null;
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return "";
      return new Date(dateStr).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },
    formatNumber(num) {
      return Number(num || 0).toLocaleString();
    },
    updateCurrentTime() {
      this.currentTime = new Date().toLocaleString("th-TH");
    },
    getCurrentFiscalYearRange() {
      const today = new Date();
      const year = today.getFullYear();
      const month = today.getMonth(); // 0 = Jan

      let startFiscalYear, endFiscalYear;
      if (month >= 9) {
        startFiscalYear = `${year}-10-01`;
        endFiscalYear = `${year + 1}-09-30`;
      } else {
        startFiscalYear = `${year - 1}-10-01`;
        endFiscalYear = `${year}-09-30`;
      }

      return { start: startFiscalYear, end: endFiscalYear };
    },
  },
  mounted() {
    const { start, end } = this.getCurrentFiscalYearRange();
    this.startDate = start;
    this.endDate = end;
    this.updateCurrentTime();
    this.fetchData();
  },
};
</script>

<style scoped>
.container {
  max-width: 1400px;
}
.report-card {
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
}
.report-header {
  background: linear-gradient(to right, #80ed99, #38a3a5);
  color: #fff;
}
.report-title {
  font-size: 1.8rem;
  font-weight: 700;
}
.btn-generate {
  background-color: #38a3a5;
  border: none;
  color: #fff;
  border-radius: 8px;
}
.btn-generate:hover {
  background-color: #22577a;
}
.procedure-card {
  border: none;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}
.procedure-card-header {
  background-color: #d8f3dc;
}
.stat-card {
  background-color: #f1faee;
  padding: 0.75rem;
  border-radius: 8px;
  text-align: center;
}
.stat-value {
  font-size: 1.4rem;
  font-weight: bold;
  color: #2a9d8f;
}
.stat-label {
  font-size: 0.75rem;
  color: #6c757d;
}
</style>
