<template>
  <div class="container py-4">
    <div class="card report-card mb-4">
      <div class="card-header report-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="report-title mb-1">รายงานจำแนกผู้ป่วยกายภาพตามรหัสโรค ICD10</h1>
            <p class="report-subtitle">กรุณาเลือกช่วงวันที่เพื่อแสดงข้อมูล</p>
          </div>
          <i class="fas fa-notes-medical report-icon"></i>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" v-model="startDate" class="form-control" id="startDate" />
              <label for="startDate">วันที่เริ่มต้น:</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" v-model="endDate" class="form-control" id="endDate" />
              <label for="endDate">วันที่สิ้นสุด:</label>
            </div>
          </div>
        </div>

        <div class="text-center mb-3">
          <button class="btn btn-generate px-4 py-2" :disabled="loading || !datesValid" @click="fetchData">
            <span v-if="!loading"><i class="fas fa-file-medical me-2"></i>ดูรายงาน</span>
            <span v-else><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</span>
          </button>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="!datesValid" class="alert alert-warning">กรุณาเลือกช่วงวันที่ให้ถูกต้อง</div>
      </div>
    </div>

    <div v-if="data.length" class="card mt-4">
      <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2 class="mb-0"><i class="fas fa-disease me-2"></i>รหัสโรคและจำนวน</h2>
            <p class="text-muted mb-0">ตั้งแต่ {{ formattedStartDate }} ถึง {{ formattedEndDate }}</p>
          </div>
          <span class="badge bg-primary rounded-pill">{{ data.length }} โรค</span>
        </div>
      </div>
      
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>ICD10</th>
                <th>จำนวนผู้ป่วย (HN)</th>
                <th>จำนวนครั้ง (Visit)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.pdx">
                <td>{{ item.pdx }}</td>
                <td>{{ formatNumber(item.C_hn) }}</td>
                <td>{{ formatNumber(item.C_vn) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="card-footer bg-white text-end">
        <small class="text-muted"><i class="fas fa-clock me-1"></i>อัปเดตล่าสุด: {{ currentTime }}</small>
      </div>
    </div>

    <div v-else-if="!loading" class="card mt-4">
      <div class="card-body text-center py-5">
        <i class="fas fa-disease fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">ไม่พบข้อมูลในช่วงวันที่ที่เลือก</h4>
      </div>
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
          `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/physic/get_icd10_physic.php?start_date=${this.startDate}&end_date=${this.endDate}&physic=16`
        );
        const result = await res.json();

        if (res.ok) {
          this.data = result;
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
  border: none;
}
.report-header {
  background: linear-gradient(to right, #43cea2, #185a9d);
  color: #fff;
  border-radius: 12px 12px 0 0 !important;
}
.report-title {
  font-size: 1.8rem;
  font-weight: 700;
}
.btn-generate {
  background-color: #185a9d;
  border: none;
  color: #fff;
  border-radius: 8px;
  transition: all 0.3s ease;
}
.btn-generate:hover {
  background-color: #0b3c5d;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.btn-generate:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}
.table {
  margin-bottom: 0;
}
.card {
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}
.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}
</style>