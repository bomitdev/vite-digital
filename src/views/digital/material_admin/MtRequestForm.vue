<template>
  <div class="request-form-container mt-2 fade-in">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-2 custom-breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/material-admin" class="text-decoration-none">
                <i class="bi bi-house-fill me-1"></i>หน้าหลักวัสดุ
              </router-link>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page">
              แบบฟอร์มขอเบิกวัสดุ
            </li>
          </ol>
        </nav>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-3 title-animate">
          <div class="icon-square bg-gradient-primary text-white shadow-sm">
            <i class="bi bi-clipboard2-check-fill"></i>
          </div>
          แบบฟอร์มขอเบิกวัสดุงานบริหารทั่วไป
        </h3>
        <p class="text-muted mb-0 ms-5 ps-3 fs-6">
          โปรดระบุวัสดุที่ต้องการเบิกและข้อมูลของท่านให้ครบถ้วนเพื่อความรวดเร็วในการตรวจสอบ
        </p>
      </div>
      <div>
        <router-link
          to="/home-backoffice"
          class="btn btn-light shadow-sm border rounded-pill px-4 hover-lift"
        >
          <i class="bi bi-arrow-left me-2 text-primary"></i>กลับหน้าหลัก
        </router-link>
      </div>
    </div>

    <!-- Form Content -->
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden glass-card mb-5">
          <div class="card-body p-4 p-md-5">
            <form @submit.prevent="submitRequest">
              <div class="row g-4">
                <!-- Section Title: Requester Info -->
                <div class="col-12 mb-2">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm"
                      ><i class="bi bi-1-circle fs-6"></i
                    ></span>
                    <h5 class="fw-bold text-dark m-0">ข้อมูลผู้เบิก</h5>
                  </div>
                </div>

                <!-- Requester Info Fields -->
                <div class="col-md-6 mt-0">
                  <label
                    class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1"
                    >ชื่อผู้เบิก <span class="text-danger">*</span></label
                  >
                  <div class="input-group input-group-custom shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary px-3">
                      <i class="bi bi-person-fill"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.requester_name"
                      class="form-control border-start-0 ps-0 form-control-lg fs-6"
                      required
                      placeholder="ระบุชื่อ-นามสกุล"
                      list="requester_list"
                    />
                  </div>
                  <datalist id="requester_list">
                    <option
                      v-for="(req, index) in pastRequesters"
                      :key="index"
                      :value="req.name"
                    ></option>
                  </datalist>
                </div>

                <div class="col-md-6 mt-0">
                  <label
                    class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1"
                    >หน่วยงาน/แผนก <span class="text-danger">*</span></label
                  >
                  <div class="input-group input-group-custom shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary px-3">
                      <i class="bi bi-building"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.department"
                      class="form-control border-start-0 ps-0 form-control-lg fs-6"
                      required
                      placeholder="ระบุหน่วยงาน"
                      list="department_list"
                    />
                  </div>
                  <datalist id="department_list">
                    <option
                      v-for="(dept, index) in pastDepartments"
                      :key="index"
                      :value="dept"
                    ></option>
                  </datalist>
                </div>

                <div class="col-12 mt-4 pt-4 border-top border-light-subtle">
                  <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm"
                        ><i class="bi bi-2-circle fs-6"></i
                      ></span>
                      <h5 class="fw-bold text-dark m-0">รายการวัสดุที่ต้องการเบิก</h5>
                    </div>
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm hover-lift d-flex align-items-center gap-1"
                      @click="addItem"
                    >
                      <i class="bi bi-plus-lg"></i> เพิ่มรายการ
                    </button>
                  </div>

                  <!-- Material Items -->
                  <div class="items-container">
                    <div
                      v-for="(item, index) in form.items"
                      :key="index"
                      class="row g-3 align-items-center mb-3 item-row p-3 rounded-4 bg-light border border-white shadow-sm position-relative fade-in"
                    >
                      <!-- Item Number Badge -->
                      <div class="position-absolute top-0 start-0 translate-middle ms-4 mt-2 z-1">
                        <span class="badge rounded-circle bg-dark shadow-sm item-index-badge">{{
                          index + 1
                        }}</span>
                      </div>

                      <div class="col-md-8">
                        <label class="form-label fw-bold text-secondary small"
                          >วัสดุ <span class="text-danger">*</span></label
                        >
                        <select
                          v-model="item.material_id"
                          class="form-select form-select-lg fs-6 shadow-none border-light-subtle"
                          required
                        >
                          <option value="" disabled>-- เลือกรายการวัสดุ --</option>
                          <option
                            v-for="mat in getAvailableMaterials(index)"
                            :key="mat.id"
                            :value="mat.id"
                          >
                            {{ mat.code }} - {{ mat.name }} (คงเหลือ: {{ mat.balance }}
                            {{ mat.unit }})
                          </option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label fw-bold text-secondary small"
                          >จำนวน <span class="text-danger">*</span></label
                        >
                        <div class="input-group">
                          <input
                            type="number"
                            v-model.number="item.quantity"
                            class="form-control form-control-lg fs-6 shadow-none border-light-subtle"
                            min="1"
                            required
                          />
                        </div>
                      </div>
                      <div
                        class="col-md-1 d-flex justify-content-end align-items-end h-100 pb-1 mt-md-0 mt-3"
                      >
                        <button
                          type="button"
                          class="btn btn-danger btn-sm shadow-sm rounded-circle d-flex align-items-center justify-content-center hover-lift btn-delete w-100"
                          style="height: 48px; max-width: 48px"
                          @click="removeItem(index)"
                          :disabled="form.items.length === 1"
                          title="ลบรายการ"
                        >
                          <i class="bi bi-trash3-fill fs-5"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-5 mb-2 text-center">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg submit-btn fw-bold"
                    :disabled="loading"
                  >
                    <div class="d-flex align-items-center gap-2" v-if="!loading">
                      <i class="bi bi-send-fill fs-5"></i>
                      <span>ส่งคำขอเบิกวัสดุ</span>
                    </div>
                    <div class="d-flex align-items-center gap-2" v-else>
                      <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                      ></span>
                      <span>กำลังประมวลผล...</span>
                    </div>
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
  name: 'MtRequestForm',
  data() {
    return {
      materials: [],
      pastRequesters: [],
      pastDepartments: [],
      form: {
        requester_name: '',
        department: '',
        items: [{ material_id: '', quantity: 1 }]
      },
      loading: false
    };
  },
  mounted() {
    this.fetchMaterials();
    this.fetchRequestersAndDepts();
  },
  watch: {
    'form.requester_name'(newName) {
      if (newName) {
        const found = this.pastRequesters.find((req) => req.name === newName);
        if (found && found.department) {
          this.form.department = found.department;
        }
      }
    }
  },
  methods: {
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/material_admin/get_materials.php');
        if (res.data.status === 'success') {
          // ดึงเฉพาะวัสดุที่มีของเหลือ (balance > 0)
          this.materials = res.data.data.filter((mat) => mat.balance > 0);
        }
      } catch (error) {
        console.error('Error fetching materials:', error);
      }
    },
    async fetchRequestersAndDepts() {
      try {
        const res = await axios.get('/api-digital/material_admin/get_requesters_depts.php');
        if (res.data.success) {
          this.pastRequesters = res.data.requesters || [];
          this.pastDepartments = res.data.departments || [];
        }
      } catch (error) {
        console.error('Error fetching requesters and departments:', error);
      }
    },
    addItem() {
      this.form.items.push({ material_id: '', quantity: 1 });
    },
    removeItem(index) {
      if (this.form.items.length > 1) {
        this.form.items.splice(index, 1);
      }
    },
    getAvailableMaterials(currentIndex) {
      // Create a set of selected material IDs excluding the current row
      const selectedIds = new Set(
        this.form.items
          .map((item, index) => (index !== currentIndex ? item.material_id : null))
          .filter((id) => id !== null && id !== '')
      );
      // Return materials that are not selected in other rows
      return this.materials.filter((mat) => !selectedIds.has(mat.id));
    },
    async submitRequest() {
      // Validate items
      if (
        this.form.items.length === 0 ||
        this.form.items.some((item) => !item.material_id || item.quantity <= 0)
      ) {
        Swal.fire({
          icon: 'warning',
          title: 'ข้อมูลไม่ครบ',
          text: 'กรุณาเลือกวัสดุและระบุจำนวนให้ถูกต้องทุกรายการ'
        });
        return;
      }

      // Check stock balance for each item
      for (const item of this.form.items) {
        const selectedMat = this.materials.find((m) => m.id === item.material_id);
        if (selectedMat && item.quantity > selectedMat.balance) {
          Swal.fire({
            icon: 'warning',
            title: 'ของไม่พอเบิก',
            text: `คุณขอเบิก ${selectedMat.name} จำนวน ${item.quantity} สมบูรณ์ แต่มียอดคงเหลือเพียง ${selectedMat.balance} ${selectedMat.unit}`
          });
          return;
        }
      }

      this.loading = true;
      try {
        const res = await axios.post('/api-digital/material_admin/request_material.php', this.form);
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'ส่งคำขอสำเร็จ',
            text: 'ระบบได้บันทึกคำขอเบิกวัสดุเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่อนุมัติและจ่ายของ',
            confirmButtonText: 'ตกลง'
          });
          // Reset form
          this.form = {
            requester_name: '',
            department: '',
            items: [{ material_id: '', quantity: 1 }]
          };
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error(error);
        Swal.fire(
          'ข้อผิดพลาด',
          error.response?.data?.message || error.message || 'ไม่สามารถส่งคำขอได้',
          'error'
        );
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
/* Animations */
.fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.title-animate {
  animation: slideInRight 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Custom Components */
.icon-square {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.25rem;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, var(--bs-primary) 0%, #0d6efd 100%);
}

.hover-lift {
  transition:
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s ease;
}
.hover-lift:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.glass-card {
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

.input-group-custom .form-control:focus {
  border-color: #dee2e6;
  box-shadow: none;
}
.input-group-custom {
  transition: all 0.2s ease;
  border-radius: 0.5rem;
  overflow: hidden;
}
.input-group-custom:focus-within {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
  border-color: #86b7fe;
}
.input-group-custom .input-group-text,
.input-group-custom .form-control {
  background-color: #f8f9fa;
  border-color: #e9ecef;
}
.input-group-custom:focus-within .input-group-text,
.input-group-custom:focus-within .form-control {
  background-color: #fff;
  border-color: #86b7fe;
}

/* Item Rows */
.item-row {
  transition: all 0.3s ease;
}
.item-row:hover {
  background-color: #fff !important;
  border-color: #e9ecef !important;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
  transform: translateY(-2px);
}

.item-index-badge {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

/* Breadcrumb */
.custom-breadcrumb .breadcrumb-item a {
  color: #6c757d;
  transition: color 0.2s;
}
.custom-breadcrumb .breadcrumb-item a:hover {
  color: var(--bs-primary);
}

/* Submit Button */
.submit-btn {
  background: linear-gradient(135deg, var(--bs-primary) 0%, #0a58ca 100%);
  border: none;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.submit-btn:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 15px 25px rgba(13, 110, 253, 0.3) !important;
}
.submit-btn:active:not(:disabled) {
  transform: translateY(1px);
}
</style>
