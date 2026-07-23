<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4">
    <div class="col-12 text-center my-4">
      <h3 class="fw-bold text-primary mb-3">แบบฟอร์มขอยืมอุปกรณ์คอมพิวเตอร์</h3>
      <p class="text-secondary">โปรดระบุอุปกรณ์ที่ต้องการยืมและรายละเอียดการใช้งานให้ครบถ้วน</p>
    </div>

    <div class="col-md-8 mx-auto">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
          <form @submit.prevent="submitRequest">
            <div class="row g-3">
              <!-- Requester Info -->
              <div class="col-md-6">
                <label class="form-label fw-bold"
                  >ชื่อผู้ยืม <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  v-model="form.borrower_name"
                  list="staffList"
                  class="form-control"
                  required
                  placeholder="ระบุชื่อ-นามสกุล"
                  @change="onStaffSelected"
                />
                <datalist id="staffList">
                  <option
                    v-for="staff in staffList"
                    :key="staff.ID"
                    :value="staff.FULLNAME"
                  ></option>
                </datalist>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold"
                  >หน่วยงาน/แผนก <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  v-model="form.department"
                  list="deptList"
                  class="form-control"
                  required
                  placeholder="ระบุหน่วยงาน"
                />
                <datalist id="deptList">
                  <option
                    v-for="dept in departmentList"
                    :key="dept.HR_DEPARTMENT_SUB_ID"
                    :value="dept.HR_DEPARTMENT_SUB_NAME"
                  ></option>
                </datalist>
              </div>

              <!-- Asset Selection -->
              <div class="col-12">
                <label class="form-label fw-bold"
                  >อุปกรณ์ที่ต้องการยืม <span class="text-danger">*</span></label
                >
                <select v-model="form.asset_id" class="form-select" required>
                  <option value="" disabled>-- เลือกอุปกรณ์ (แสดงเฉพาะเครื่องสำรอง) --</option>
                  <option v-for="asset in availableAssets" :key="asset.id" :value="asset.id">
                    [{{ asset.asset_code }}] {{ asset.name }} ({{ asset.type }})
                  </option>
                </select>

                <!-- Selected Asset Preview -->
                <div v-if="selectedAsset" class="mt-3 p-3 bg-light rounded-4 border d-flex align-items-center gap-3">
                  <div class="flex-shrink-0">
                    <img v-if="selectedAsset.image_path" :src="getImageUrl(selectedAsset.image_path)" class="rounded shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                    <div v-else class="d-flex justify-content-center align-items-center bg-white rounded shadow-sm text-muted" style="width: 80px; height: 80px;">
                      <i class="bi bi-pc-display" style="font-size: 2rem;"></i>
                    </div>
                  </div>
                  <div>
                    <div class="fw-bold text-primary mb-1">[{{ selectedAsset.asset_code }}] {{ selectedAsset.name }}</div>
                    <div class="small text-muted mb-1"><i class="bi bi-tag me-1"></i>ยี่ห้อ: {{ selectedAsset.brand || '-' }} รุ่น: {{ selectedAsset.model || '-' }}</div>
                    <div class="small text-muted"><i class="bi bi-cpu me-1"></i>สเปค: {{ selectedAsset.spec_cpu || '-' }} / RAM {{ selectedAsset.spec_ram || '-' }} / {{ selectedAsset.spec_storage || '-' }}</div>
                  </div>
                </div>
              </div>

              <!-- Objective -->
              <div class="col-12">
                <label class="form-label fw-bold"
                  >วัตถุประสงค์ในการยืม <span class="text-danger">*</span></label
                >
                <textarea
                  v-model="form.objective"
                  class="form-control"
                  rows="3"
                  required
                  placeholder="ระบุเหตุผลในการยืมไปใช้งาน"
                ></textarea>
              </div>

              <!-- Expected Return Date -->
              <div class="col-md-6">
                <label class="form-label fw-bold"
                  >วันที่คาดว่าจะคืน <span class="text-danger">*</span></label
                >
                <input
                  type="date"
                  v-model="form.expected_return_date"
                  class="form-control"
                  required
                  :min="minDate"
                />
              </div>

              <!-- Submit -->
              <div class="col-12 mt-4 text-center">
                <button type="submit" class="btn btn-primary px-5 rounded-pill" :disabled="loading">
                  <i class="bi bi-send me-2"></i>
                  {{ loading ? 'กำลังส่ง...' : 'ส่งคำขอยืมอุปกรณ์' }}
                </button>
              </div>
            </div>
          </form>
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
  name: 'ComputerLoanForm',
  data() {
    return {
      availableAssets: [],
      staffList: [],
      departmentList: [],
      form: {
        borrower_name: '',
        department: '',
        asset_id: '',
        objective: '',
        expected_return_date: ''
      },
      loading: false
    };
  },
  computed: {
    minDate() {
      const today = new Date();
      return today.toISOString().split('T')[0];
    },
    selectedAsset() {
      if (!this.form.asset_id) return null;
      return this.availableAssets.find(a => a.id === this.form.asset_id);
    }
  },
  mounted() {
    this.fetchAssets();
    this.fetchStaff();
    this.fetchDepartments();
  },
  methods: {
    async fetchStaff() {
      try {
        const res = await axios.get('/api-hosoffice/get_all_staff.php');
        if (res.data.status === 'success') {
          this.staffList = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching staff:', error);
      }
    },
    async fetchDepartments() {
      try {
        const res = await axios.get('/api-hosoffice/get_departments.php');
        if (res.data.status === 'success') {
          this.departmentList = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching departments:', error);
      }
    },
    onStaffSelected() {
      const selectedStaff = this.staffList.find((s) => s.FULLNAME === this.form.borrower_name);
      if (selectedStaff && selectedStaff.HR_DEPARTMENT_SUB_NAME) {
        this.form.department = selectedStaff.HR_DEPARTMENT_SUB_NAME;
      }
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      return `http://localhost/vue-app/vite-digital/${path}`;
    },
    async fetchAssets() {
      try {
        // Fetch only assets with status 'Spare' (สำรอง) and available for loan
        const res = await axios.get('/api-digital/asset/get_assets.php?status=Spare&available_for_loan=1');
        if (res.data.status === 'success') {
          this.availableAssets = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching assets:', error);
      }
    },
    async submitRequest() {
      this.loading = true;
      try {
        const res = await axios.post('/api-digital/computer_loan/request_loan.php', this.form);
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'ส่งคำขอสำเร็จ',
            text: 'กรุณารอเจ้าหน้าที่ไอทีตรวจสอบและอนุมัติการยืม',
            confirmButtonText: 'ตกลง'
          });
          // Reset form
          this.form = {
            borrower_name: '',
            department: '',
            asset_id: '',
            objective: '',
            expected_return_date: ''
          };
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถส่งคำขอได้', 'error');
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.card {
  transition: transform 0.2s;
}
.card:hover {
  transform: translateY(-2px);
}
</style>
