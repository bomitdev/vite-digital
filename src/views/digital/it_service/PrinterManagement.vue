<template>
  <div class="printer-management-container mt-4 fade-in">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <h3 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
        <div class="icon-square bg-dark text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
          <i class="bi bi-gear-fill fs-5"></i>
        </div>
        จัดการข้อมูลเครื่องปริ้น
      </h3>
      <div class="d-flex gap-2">
        <button class="btn btn-primary rounded-pill px-4 shadow-sm hover-lift" @click="openAddModal">
          <i class="bi bi-plus-circle me-1"></i> เพิ่มเครื่องปริ้น
        </button>
        <router-link to="/printer-dashboard" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm hover-lift">
          <i class="bi bi-arrow-left me-1"></i> กลับหน้าระบบติดตาม
        </router-link>
      </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        <div class="table-responsive" v-else>
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="px-4 py-3 text-secondary fw-bold" style="width: 80px;">ลำดับ</th>
                <th class="py-3 text-secondary fw-bold">ชื่อเครื่องปริ้น</th>
                <th class="py-3 text-secondary fw-bold">IP Address</th>
                <th class="py-3 text-secondary fw-bold">Community</th>
                <th class="px-4 py-3 text-secondary fw-bold text-end" style="width: 150px;">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(printer, index) in printers" :key="printer.id">
                <td class="px-4 py-3 text-muted" style="width: 5%">{{ index + 1 }}</td>
                <td class="py-3 fw-bold text-dark" style="width: 35%">{{ printer.name }}</td>
                <td class="py-3" style="width: 25%"><span class="badge bg-light text-primary border px-3 py-2 fs-6 rounded-pill"><i class="bi bi-hdd-network me-1"></i> {{ printer.ip_address }}</span></td>
                <td class="py-3 text-muted" style="width: 15%">{{ printer.community || 'public' }}</td>
                <td class="px-4 py-3 text-end" style="width: 20%">
                  <div class="d-flex gap-2 justify-content-end text-nowrap">
                    <button @click="resetMeter(printer)" class="btn btn-sm btn-outline-info rounded-3 hover-lift px-3" title="เปลี่ยนเครื่องปริ้นใหม่ (เก็บยอดสะสมเดิม)">
                      <i class="bi bi-arrow-repeat"></i> เปลี่ยนเครื่อง
                    </button>
                    <button @click="openEditModal(printer)" class="btn btn-sm btn-outline-primary rounded-3 hover-lift px-3" title="แก้ไข">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button @click="deletePrinter(printer.id)" class="btn btn-sm btn-outline-danger rounded-3 hover-lift px-3" title="ลบ">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="printers.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                  ยังไม่มีข้อมูลเครื่องปริ้น
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold">
              <i class="bi text-primary me-2" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
              {{ isEditMode ? 'แก้ไขเครื่องปริ้น' : 'เพิ่มเครื่องปริ้นใหม่' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal" :disabled="submitting"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fw-bold small">ชื่อเครื่องปริ้น</label>
              <input type="text" class="form-control form-control-lg" v-model="formData.name" placeholder="ระบุชื่อเครื่องปริ้น" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">IP Address</label>
              <input type="text" class="form-control form-control-lg" v-model="formData.ip" placeholder="เช่น 192.168.9.17" required>
            </div>
            <div class="alert alert-warning small mb-0 mt-3 border-0 rounded-3">
              <i class="bi bi-info-circle-fill me-1"></i> เครื่องปริ้นจะต้องเปิดใช้งาน <strong>SNMP v1/v2c</strong> ไว้เรียบร้อยแล้ว
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary rounded-pill px-4" @click="closeModal" :disabled="submitting">ยกเลิก</button>
            <button type="button" class="btn btn-primary rounded-pill px-4" @click="savePrinter" :disabled="submitting || !formData.name || !formData.ip">
              <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
              บันทึก
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'PrinterManagement',
  data() {
    return {
      loading: true,
      printers: [],
      showModal: false,
      isEditMode: false,
      editId: null,
      submitting: false,
      formData: {
        name: '',
        ip: ''
      }
    };
  },
  mounted() {
    this.fetchPrinters();
  },
  methods: {
    async fetchPrinters() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/it_service/printer_dashboard.php?action=fetch_db');
        if (response.data.success) {
          this.printers = response.data.data;
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
    openAddModal() {
      this.isEditMode = false;
      this.editId = null;
      this.formData = { name: '', ip: '' };
      this.showModal = true;
    },
    openEditModal(printer) {
      this.isEditMode = true;
      this.editId = printer.id;
      this.formData = { name: printer.name, ip: printer.ip_address };
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
    },
    async savePrinter() {
      this.submitting = true;
      try {
        const action = this.isEditMode ? 'edit' : 'add';
        const payload = this.isEditMode 
            ? { id: this.editId, ...this.formData } 
            : this.formData;
            
        const response = await axios.post(`/api-digital/it_service/printer_dashboard.php?action=${action}`, payload);
        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: response.data.message || 'บันทึกข้อมูลเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
          });
          this.closeModal();
          this.fetchPrinters();
        } else {
          throw new Error(response.data.message || 'เกิดข้อผิดพลาดในการบันทึก');
        }
      } catch (error) {
        Swal.fire('ผิดพลาด', error.message || 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
      } finally {
        this.submitting = false;
      }
    },
    async deletePrinter(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบเครื่องปริ้นนี้ออกจากระบบใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const response = await axios.post('/api-digital/it_service/printer_dashboard.php?action=delete', { id });
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'ลบสำเร็จ!',
              timer: 1500,
              showConfirmButton: false
            });
            this.fetchPrinters();
          } else {
            throw new Error(response.data.message || 'ลบไม่สำเร็จ');
          }
        } catch (error) {
          Swal.fire('ผิดพลาด', error.message || 'ไม่สามารถลบข้อมูลได้', 'error');
        }
      }
    },
    async resetMeter(printer) {
      const result = await Swal.fire({
        title: 'ยืนยันการเปลี่ยนเครื่องปริ้น?',
        html: `คุณกำลังเปลี่ยนเครื่องปริ้นใหม่สำหรับ <br><b>${printer.name} (IP: ${printer.ip_address})</b> ใช่หรือไม่?<br><br>
               <span class="text-danger small">กรุณาเสียบสาย LAN เข้าเครื่องปริ้นใหม่ก่อนกดยืนยัน</span><br>
               <span class="text-success small">ระบบจะทำการชดเชยเลขไมล์ให้ยอดสะสมของโรงพยาบาลไม่หายไป</span>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, เปลี่ยนเครื่องใหม่',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          Swal.fire({
            title: 'กำลังเชื่อมต่อเครื่องปริ้นใหม่...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
          });
          const response = await axios.post('/api-digital/it_service/printer_dashboard.php?action=reset_meter', { id: printer.id });
          if (response.data.success) {
            Swal.fire('สำเร็จ!', response.data.message, 'success');
            this.fetchPrinters();
          } else {
            throw new Error(response.data.message || 'ไม่สามารถชดเชยยอดได้');
          }
        } catch (error) {
          console.error(error);
          Swal.fire('ข้อผิดพลาด', error.message, 'error');
        }
      }
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
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
