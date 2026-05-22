<template>
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
    async fetchAssets() {
      try {
        // Fetch only assets with status 'Spare' (สำรอง)
        const res = await axios.get('/api-digital/asset/get_assets.php?status=Spare');
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
