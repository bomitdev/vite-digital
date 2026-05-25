<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10">
        <!-- Header Section -->
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <h4 class="fw-bold mb-1 text-dark">
              <i class="bi bi-github me-2 text-primary"></i>Git Synchronization
            </h4>
            <p class="text-muted mb-0">ระบบจัดการอัพโหลดและดาวน์โหลดข้อมูลผ่าน GitHub</p>
          </div>
          <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/home-backoffice')">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </button>
        </div>

        <div class="row g-4 justify-content-center">
          <!-- Push Card -->
          <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                  <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                    <i class="bi bi-cloud-arrow-up text-primary fs-4"></i>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0">อัพโหลดขึ้นระบบ (Push)</h5>
                    <small class="text-muted">ส่งไฟล์จากเครื่องเซิร์ฟเวอร์ไปยัง GitHub</small>
                  </div>
                </div>
                
                <p class="mb-3 text-secondary">เลือกโฟลเดอร์ที่ต้องการบันทึกการเปลี่ยนแปลงและอัพโหลด:</p>
                
                <div class="border rounded p-3 mb-4 bg-light">
                  <div class="form-check mb-2" v-for="folder in availableFolders" :key="folder.path">
                    <input class="form-check-input" type="checkbox" :value="folder.path" :id="'folder-'+folder.path" v-model="selectedFolders">
                    <label class="form-check-label ms-2 cursor-pointer" :for="'folder-'+folder.path">
                      <i class="bi bi-folder-fill text-warning me-2"></i>{{ folder.name }} 
                      <small class="text-muted ms-1">({{ folder.path }})</small>
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-sm-8">
                    <button 
                      class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm" 
                      @click="confirmAction('push')"
                      :disabled="loading || selectedFolders.length === 0"
                    >
                      <span v-if="loadingAction === 'push'" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-upload me-2"></i> เริ่มการอัพโหลด
                    </button>
                  </div>
                  <div class="col-sm-4">
                    <button 
                      class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-bold" 
                      @click="performAction('status')"
                      :disabled="loading"
                    >
                      <span v-if="loadingAction === 'status'" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-info-circle me-2"></i> สถานะ (Status)
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Output Console -->
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-terminal me-2"></i>Terminal Output</h6>
                <button class="btn btn-sm btn-outline-light" @click="logs = ''" v-if="logs">Clear</button>
              </div>
              <div class="card-body bg-dark p-0">
                <div class="console-output p-4 font-monospace text-light" style="min-height: 250px; max-height: 500px; overflow-y: auto;">
                  <div v-if="!logs" class="text-secondary text-center my-5">
                    <i class="bi bi-activity fs-1 d-block mb-3"></i>
                    No output to display. Run an action to see logs.
                  </div>
                  <pre v-else class="mb-0 text-light" style="white-space: pre-wrap; font-size: 0.9rem;">{{ logs }}</pre>
                </div>
              </div>
            </div>
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
  name: 'GitSync',
  data() {
    return {
      availableFolders: [
        { name: 'Frontend (src)', path: 'src' },
        { name: 'Backend API', path: 'backend' }
      ],
      selectedFolders: ['src', 'backend'], // Default selected
      loading: false,
      loadingAction: null,
      logs: ''
    };
  },
  methods: {
    async confirmAction(action) {
      if (action !== 'push') return;
      
      const result = await Swal.fire({
        title: 'ยืนยันการอัพโหลด?',
        text: 'คุณกำลังจะอัพโหลดโฟลเดอร์ที่เลือกไปยัง GitHub',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'อัพโหลด',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        this.performAction(action);
      }
    },
    
    async performAction(action) {
      this.loading = true;
      this.loadingAction = action;
      
      try {
        const payload = { action };
        if (action === 'push') {
          payload.folders = this.selectedFolders;
        }

        const res = await axios.post('/backend/git_sync.php', payload);
        
        if (res.data.status === 'success') {
          this.logs = res.data.logs + "\n\n" + this.logs; // Append new logs to top
          Swal.fire({
            icon: 'success',
            title: 'ดำเนินการเสร็จสิ้น',
            text: 'กรุณาตรวจสอบผลลัพธ์ในกล่องข้อความด้านล่าง',
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          Swal.fire('เกิดข้อผิดพลาด', res.data.message || 'Unknown error', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาดการเชื่อมต่อ', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
      } finally {
        this.loading = false;
        this.loadingAction = null;
      }
    }
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.console-output {
  background-color: #1e1e1e;
  border-radius: 0 0 1rem 1rem;
}
pre {
  color: #a9b7c6; /* IDE-like text color */
}
</style>
