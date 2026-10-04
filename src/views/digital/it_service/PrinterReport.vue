<template>
  <div class="printer-report-container mt-4 fade-in">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
        <div class="icon-square bg-success text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
          <i class="bi bi-bar-chart-fill fs-5"></i>
        </div>
        รายงานการใช้งานเครื่องปริ้น
      </h3>
      <div class="d-flex gap-2">
        <router-link to="/printer-dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm hover-lift">
          <i class="bi bi-arrow-left me-1"></i> กลับหน้าระบบติดตาม
        </router-link>
      </div>
    </div>

    <!-- Alert / Status -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">กำลังประมวลผลรายงาน...</p>
    </div>

    <!-- Data Table -->
    <div v-else class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <h5 class="m-0 fw-bold text-dark">สรุปยอดการพิมพ์รายเครื่อง</h5>
        <span class="badge bg-light text-secondary border">อัปเดตล่าสุด: {{ currentDate }}</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="px-4 py-3 text-secondary fw-bold" style="width: 60px;">#</th>
                <th class="py-3 text-secondary fw-bold">ชื่อเครื่องปริ้น</th>
                <th class="py-3 text-secondary fw-bold text-end">ใช้วันนี้ (แผ่น)</th>
                <th class="py-3 text-secondary fw-bold text-end">ใช้เดือนนี้ (แผ่น)</th>
                <th class="py-3 text-secondary fw-bold text-end">ใช้ปีงบฯ นี้ (แผ่น)</th>
                <th class="px-4 py-3 text-secondary fw-bold text-end">ยอดสะสมรวม (แผ่น)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in reports" :key="item.id">
                <td class="px-4 text-muted">{{ index + 1 }}</td>
                <td>
                  <div class="fw-bold text-dark">{{ item.name }}</div>
                  <div class="small text-muted">IP: {{ item.ip }}</div>
                </td>
                <td class="text-end fw-bold text-info fs-5">
                  {{ formatNumber(item.today_usage) }}
                </td>
                <td class="text-end fw-bold text-primary fs-5">
                  {{ formatNumber(item.this_month_usage) }}
                </td>
                <td class="text-end fw-bold text-success fs-5">
                  {{ formatNumber(item.this_fy_usage) }}
                </td>
                <td class="px-4 text-end text-muted">
                  {{ formatNumber(item.current_total) }}
                </td>
              </tr>
              <tr v-if="reports.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                  ยังไม่มีข้อมูลรายงาน
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Instructions Alert -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-center mb-5">
      <i class="bi bi-info-circle-fill fs-3 text-info me-3"></i>
      <div>
        <h6 class="fw-bold mb-1">เกี่ยวกับรายงานนี้</h6>
        <p class="mb-0 small">ระบบจะทำการจดบันทึกยอดกระดาษจากเครื่องปริ้นอัตโนมัติในทุกๆ วัน เพื่อนำมาคำนวณหักลบหาปริมาณการพิมพ์ในเดือนปัจจุบัน และปีงบประมาณปัจจุบัน (เริ่ม 1 ต.ค.)</p>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'PrinterReport',
  data() {
    return {
      loading: true,
      reports: [],
      currentDate: new Date().toLocaleDateString('th-TH')
    };
  },
  mounted() {
    this.fetchReports();
  },
  methods: {
    async fetchReports() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/it_service/printer_report.php?action=summary');
        if (response.data.success) {
          this.reports = response.data.data;
        } else {
          throw new Error(response.data.message || 'ไม่สามารถดึงข้อมูลได้');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อ API ได้: ' + error.message, 'error');
      } finally {
        this.loading = false;
      }
    },
    formatNumber(num) {
      if (num === null || num === undefined) return '0';
      return new Intl.NumberFormat().format(num);
    }
  }
};
</script>

<style scoped>
.hover-lift {
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
}
.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
}
.fade-in {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
