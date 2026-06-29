<template>
  <div class="request-form-container mt-2 fade-in">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-2 custom-breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/material-v2" class="text-decoration-none">
                <i class="bi bi-house-fill me-1"></i>หน้าหลักวัสดุคอม
              </router-link>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page">
              แบบฟอร์มขอเบิกวัสดุ
            </li>
          </ol>
        </nav>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-3 title-animate">
          <div class="icon-square bg-gradient-primary text-white shadow-sm">
            <i class="bi bi-pc-display"></i>
          </div>
          แบบฟอร์มขอเบิกวัสดุคอมพิวเตอร์
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
                      v-for="(name, index) in pastRequesters"
                      :key="index"
                      :value="name"
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
                  <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm"
                      ><i class="bi bi-2-circle fs-6"></i
                    ></span>
                    <h5 class="fw-bold text-dark m-0">รายการวัสดุที่ต้องการเบิก</h5>
                  </div>

                  <div
                    class="row g-3 align-items-center p-3 rounded-4 bg-light border border-white shadow-sm position-relative fade-in item-row"
                  >
                    <!-- Material Selection -->
                    <div class="col-md-8">
                      <label class="form-label fw-bold text-secondary small"
                        >วัสดุที่ต้องการเบิก <span class="text-danger">*</span></label
                      >
                      <select
                        v-model="form.material_id"
                        class="form-select form-select-lg fs-6 shadow-none border-light-subtle"
                        required
                      >
                        <option value="" disabled>-- เลือกรายการวัสดุ --</option>
                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                          {{ mat.code }} - {{ mat.name }} (คงเหลือ: {{ mat.balance }}
                          {{ mat.unit }})
                        </option>
                      </select>
                    </div>

                    <!-- Quantity -->
                    <div class="col-md-4">
                      <label class="form-label fw-bold text-secondary small"
                        >จำนวน <span class="text-danger">*</span></label
                      >
                      <div class="input-group">
                        <input
                          type="number"
                          v-model.number="form.quantity"
                          class="form-control form-control-lg fs-6 shadow-none border-light-subtle"
                          min="1"
                          required
                        />
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
                      <span>ส่งคำขอเบิกวัสดุคอมพิวเตอร์</span>
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

        <!-- History Section -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden glass-card mb-5 fade-in" style="animation-delay: 0.2s;">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-2 mb-4">
              <span class="badge bg-info text-white rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-clock-history fs-6"></i></span>
              <h5 class="fw-bold text-dark m-0">ประวัติการขอเบิกวัสดุ</h5>
            </div>
            
            <div class="mb-3">
              <input type="text" v-model="searchQuery" class="form-control" placeholder="ค้นหาชื่อผู้เบิก, หน่วยงาน, หรือรายการวัสดุ...">
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>วันที่ขอเบิก</th>
                    <th>ผู้เบิก</th>
                    <th>หน่วยงาน</th>
                    <th>รายการวัสดุ</th>
                    <th class="text-center">จำนวน</th>
                    <th class="text-center">สถานะ</th>
                    <th>หมายเหตุ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredRequests.length === 0">
                    <td colspan="7" class="text-center py-4 text-muted">
                      <span v-if="!isAdmin && !form.requester_name">กรุณาระบุชื่อผู้เบิกด้านบนเพื่อดูประวัติของท่าน</span>
                      <span v-else>ไม่พบประวัติการขอเบิก</span>
                    </td>
                  </tr>
                  <tr v-for="req in filteredRequests" :key="req.id">
                    <td>{{ req.request_date }}</td>
                    <td>{{ req.requester_name }}</td>
                    <td>{{ req.department }}</td>
                    <td>{{ req.material_name }}</td>
                    <td class="text-center fw-bold">{{ req.quantity }}</td>
                    <td class="text-center">
                      <span class="badge rounded-pill px-3 py-2 fw-normal" :class="getStatusBadge(req.status)">
                        <i :class="getStatusIcon(req.status)" class="me-1"></i>
                        {{ getStatusText(req.status) }}
                      </span>
                    </td>
                    <td>{{ req.admin_note || '-' }}</td>
                  </tr>
                </tbody>
              </table>
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
  name: 'MtRequestForm',
  data() {
    return {
      materials: [],
      pastRequesters: [],
      pastDepartments: [],
      form: {
        requester_name: localStorage.getItem('last_requester_name') || '',
        department: localStorage.getItem('last_department') || '',
        material_id: '',
        quantity: 1
      },
      loading: false,
      requests: [],
      searchQuery: ''
    };
  },
  mounted() {
    this.fetchMaterials();
    this.fetchRequestersAndDepts();
    this.fetchRequests();
  },
  computed: {
    isAdmin() {
      return !!localStorage.getItem('user_token');
    },
    filteredRequests() {
      let reqs = this.requests;

      if (!this.isAdmin) {
        if (!this.form.requester_name) {
          return [];
        }
        const requester = this.form.requester_name.toLowerCase().trim();
        reqs = reqs.filter(req => req.requester_name && req.requester_name.toLowerCase().trim() === requester);
      }

      if (!this.searchQuery) return reqs;
      const q = this.searchQuery.toLowerCase();
      return reqs.filter(req => 
        (req.requester_name && req.requester_name.toLowerCase().includes(q)) ||
        (req.department && req.department.toLowerCase().includes(q)) ||
        (req.material_name && req.material_name.toLowerCase().includes(q))
      );
    }
  },
  methods: {
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_materials.php');
        if (res.data.status === 'success') {
          // ดึงเฉพาะวัสดุที่มีของเหลือ (balance > 0)
          this.materials = res.data.data.filter((mat) => mat.balance > 0);
        }
      } catch (error) {
        console.error('Error fetching materials:', error);
      }
    },
    async fetchRequests() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_requests.php?status=all');
        if (res.data.success) {
          this.requests = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching requests', error);
      }
    },
    getStatusBadge(status) {
      const map = {
        pending: 'bg-warning text-dark bg-opacity-75',
        approved: 'bg-success bg-opacity-75',
        rejected: 'bg-danger bg-opacity-75'
      };
      return map[status] || 'bg-secondary';
    },
    getStatusIcon(status) {
      const map = {
        pending: 'bi bi-hourglass-split',
        approved: 'bi bi-check2-circle',
        rejected: 'bi bi-x-circle'
      };
      return map[status] || 'bi-info-circle';
    },
    getStatusText(status) {
      const map = {
        pending: 'รออนุมัติ',
        approved: 'จ่ายของแล้ว',
        rejected: 'ปฏิเสธ'
      };
      return map[status] || status;
    },
    async fetchRequestersAndDepts() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_requesters_depts.php');
        if (res.data.success) {
          this.pastRequesters = res.data.requesters || [];
          this.pastDepartments = res.data.departments || [];
        }
      } catch (error) {
        console.error('Error fetching requesters and departments:', error);
      }
    },
    async submitRequest() {
      // Input Validation
      const selectedMat = this.materials.find((m) => m.id === this.form.material_id);
      if (selectedMat && this.form.quantity > selectedMat.balance) {
        Swal.fire({
          icon: 'warning',
          title: 'ของไม่พอเบิก',
          text: `คุณขอเบิก ${this.form.quantity} แต่มีของในสต๊อกเพียง ${selectedMat.balance}`
        });
        return;
      }

      this.loading = true;
      try {
        const res = await axios.post('/api-digital/material_v2/request_material.php', this.form);
        if (res.data.success) {
          // Save last requester name and dept
          localStorage.setItem('last_requester_name', this.form.requester_name);
          localStorage.setItem('last_department', this.form.department);

          Swal.fire({
            icon: 'success',
            title: 'ส่งคำขอสำเร็จ',
            text: 'กรุณารอเจ้าหน้าที่ไอทีอนุมัติและจ่ายของ',
            confirmButtonText: 'ตกลง'
          });
          // Reset form but keep name and department
          this.form = {
            requester_name: localStorage.getItem('last_requester_name') || '',
            department: localStorage.getItem('last_department') || '',
            material_id: '',
            quantity: 1
          };
          this.fetchRequests(); // Refresh the history list
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
