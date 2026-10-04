<template>
  <div class="printer-dashboard-container mt-4 fade-in">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
        <div class="icon-square bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
          <i class="bi bi-printer-fill fs-5"></i>
        </div>
        ระบบติดตามสถานะเครื่องปริ้น
      </h3>
      <div class="d-flex gap-2">
        <router-link to="/printer-reports" class="btn btn-outline-success rounded-pill px-4 shadow-sm hover-lift">
          <i class="bi bi-bar-chart-fill me-1"></i> รายงาน
        </router-link>
        <router-link to="/printer-management" class="btn btn-outline-primary rounded-pill px-4 shadow-sm hover-lift">
          <i class="bi bi-gear-fill me-1"></i> จัดการ
        </router-link>
        <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm hover-lift">
          <i class="bi bi-arrow-left me-1"></i> กลับหน้าหลัก
        </router-link>
      </div>
    </div>

    <!-- Alert / Status -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">กำลังดึงข้อมูลจากเครื่องปริ้น...</p>
    </div>

    <div v-else class="row g-4">
      <div class="col-sm-6 col-md-4 col-lg-3" v-for="printer in printers" :key="printer.id">
        <div class="card shadow-sm rounded-4 h-100 position-relative overflow-hidden hover-lift" style="border: 1px solid rgba(0,0,0,0.05); background: #ffffff;">
          <div class="card-body p-3 position-relative z-1 d-flex flex-column">
            
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="pe-2">
                <h6 class="fw-bold text-dark mb-1 text-truncate" :title="printer.name">{{ printer.name }}</h6>
                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">IP: {{ printer.ip }}</span>
              </div>
              <div class="icon-square bg-primary text-white rounded-circle shadow-sm flex-shrink-0" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-printer fs-5"></i>
              </div>
            </div>
            
            <div class="mt-auto pt-2">
              <p class="text-secondary mb-0" style="font-size: 0.75rem;">จำนวนการใช้งานทั้งหมด</p>
              <div class="fw-bold text-primary d-flex align-items-baseline gap-1" style="font-size: 1.5rem;">
                {{ formatNumber(printer.page_count) }}
                <span class="text-secondary fw-normal" style="font-size: 0.75rem;">แผ่น</span>
              </div>
            </div>
            
            <div class="mt-3 pt-2 border-top border-light-subtle d-flex justify-content-between align-items-center">
              <span class="fw-bold" style="font-size: 0.75rem;" :class="printer.status === 'online' ? 'text-success' : 'text-danger'">
                <i class="bi" :class="printer.status === 'online' ? 'bi-circle-fill' : 'bi-exclamation-circle-fill'"></i>
                {{ printer.status === 'online' ? 'เชื่อมต่อปกติ' : 'ขาดการเชื่อมต่อ' }}
              </span>
              <span class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i>{{ formatTime(printer.last_update) }}</span>
            </div>
            
          </div>
        </div>
      </div>
      
      <div v-if="printers.length === 0" class="col-12 text-center py-5">
        <i class="bi bi-printer text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-3">ยังไม่มีข้อมูลเครื่องปริ้นในระบบ<br>กรุณากด "เพิ่มเครื่องปริ้น" ด้านบน</p>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'PrinterDashboard',
  data() {
    return {
      loading: true,
      printers: []
    };
  },
  mounted() {
    this.fetchPrinterStatus();
  },
  methods: {
    async fetchPrinterStatus() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/it_service/printer_dashboard.php?action=fetch_live');
        if (response.data.success) {
          this.printers = response.data.data;
        } else {
          throw new Error(response.data.message || 'ไม่สามารถดึงข้อมูลได้');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อกับ API หรือเครื่องปริ้นได้: ' + error.message, 'error');
      } finally {
        this.loading = false;
      }
    },
    formatNumber(num) {
      if (num === null || num === undefined) return '-';
      return new Intl.NumberFormat().format(num);
    },
    formatTime(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.glass-card {
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
}
.hover-lift {
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
}
.hover-lift:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
}
.fade-in {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
