<template>
  <div class="date-selector-wrapper mb-4">
    <div class="text-center mb-4">
      <h2 class="dashboard-title">Dashboard ข้อมูลผู้มารับบริการ</h2>
      <p class="text-muted small">สรุปสถิติการรับบริการแยกตามแผนกและกลุ่มโรค</p>
    </div>

    <div class="card border-0 shadow-sm custom-card">
      <div class="card-body p-4">
        <div class="row align-items-center g-3">
          <div class="col-lg-3">
            <div class="d-flex align-items-center">
              <div class="icon-box me-3">
                <i class="bi bi-calendar3"></i>
              </div>
              <div>
                <h6 class="mb-0 fw-bold">ช่วงเวลาข้อมูล</h6>
                <small class="text-muted">ระบุวันที่ต้องการดู</small>
              </div>
            </div>
          </div>

          <div class="col-md-4 col-lg-3">
            <div class="input-group input-group-custom">
              <span class="input-group-text bg-white border-end-0">
                <small class="text-primary fw-bold">เริ่ม</small>
              </span>
              <input type="date" v-model="startDate" class="form-control border-start-0 ps-0" />
            </div>
          </div>

          <div class="col-md-4 col-lg-3">
            <div class="input-group input-group-custom">
              <span class="input-group-text bg-white border-end-0">
                <small class="text-danger fw-bold">ถึง</small>
              </span>
              <input type="date" v-model="endDate" class="form-control border-start-0 ps-0" />
            </div>
          </div>

          <div class="col-md-4 col-lg-3 col-xl-2 ms-auto">
            <button @click="emitDate" class="btn btn-primary w-100 shadow-sm btn-search">
              <i class="bi bi-search me-2"></i>ดึงข้อมูล
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      startDate: '',
      endDate: ''
    };
  },
  methods: {
    emitDate() {
      this.$emit('date-changed', {
        start: this.startDate,
        end: this.endDate
      });
    }
  },
  mounted() {
    const today = new Date();
    let fiscalYear = today.getFullYear() + (today.getMonth() < 9 ? 0 : 1);
    this.startDate = `${fiscalYear - 1}-10-01`;
    this.endDate = `${fiscalYear}-09-30`;
    this.emitDate();
  }
};
</script>

<style>
/* หัวข้อ Dashboard */
.dashboard-title {
  color: #5a2d82; /* สีม่วงเข้มดูทางการ */
  font-weight: 800;
  letter-spacing: -0.5px;
  margin-bottom: 5px;
}

/* ปรับแต่ง Card */
.custom-card {
  border-radius: 16px;
  background: #ffffff;
}

/* กล่องไอคอนซ้ายมือ */
.icon-box {
  width: 45px;
  height: 45px;
  background-color: #f3e8ff;
  color: #7c3aed;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.2rem;
}

/* ปรับแต่งช่อง Input */
.input-group-custom .form-control {
  border-color: #e2e8f0;
  padding: 0.6rem 0.75rem;
  font-weight: 500;
  color: #475569;
}

.input-group-custom .form-control:focus {
  border-color: #cbd5e1;
  box-shadow: none;
  background-color: #f8fafc;
}

.input-group-custom .input-group-text {
  border-color: #e2e8f0;
  padding-left: 1rem;
}

/* ปุ่มค้นหา */
.btn-search {
  padding: 0.7rem;
  border-radius: 12px;
  font-weight: 600;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  border: none;
  transition: all 0.3s ease;
}

.btn-search:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
}

/* Responsive */
@media (max-width: 991px) {
  .icon-box {
    display: none;
  }
  .dashboard-title {
    font-size: 1.5rem;
  }
}
</style>
