<template>
  <div class="row g-4 mt-1">
    <!-- Header Section -->
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-2"
    >
      <div class="mb-3 mb-md-0 fade-in-up">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-2 custom-breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/home-backoffice" class="text-decoration-none">
                <i class="bi bi-house-door-fill me-1"></i>หน้าหลัก
              </router-link>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page">ทะเบียน IP Address</li>
          </ol>
        </nav>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-3">
          <div class="icon-square bg-gradient-primary text-white shadow-sm">
            <i class="bi bi-router-fill"></i>
          </div>
          ทะเบียน IP Address องค์กร
        </h3>
        <p class="text-muted mb-0 ms-5 ps-3 fs-6">
          ระบบจัดการและตรวจสอบสถานะ IP Address สำหรับเครื่องคอมพิวเตอร์และอุปกรณ์บนเครือข่าย
        </p>
      </div>
      <div class="d-flex gap-2 fade-in-up delay-100">
        <button
          class="btn btn-primary rounded-pill px-4 shadow-sm hover-lift fw-bold d-flex align-items-center gap-2"
          @click="openAddModal"
        >
          <i class="bi bi-plus-circle-fill"></i>เพิ่มทะเบียน IP
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-12 mt-0">
      <div class="row g-3">
        <div class="col-md-3 col-sm-6 fade-in-up delay-100">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-primary border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-hdd-network fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">IP ทั้งหมด</p>
                <h4 class="mb-0 fw-black text-dark">{{ totalIPs }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in-up delay-200">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-success border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-check-circle-fill fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">ใช้งานอยู่</p>
                <h4 class="mb-0 fw-black text-dark">{{ activeIPs }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in-up delay-300">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-warning border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-bookmark-star-fill fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">จอง/สงวนไว้</p>
                <h4 class="mb-0 fw-black text-dark">{{ reservedIPs }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 fade-in-up delay-400">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-secondary border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-secondary bg-opacity-10 text-secondary rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-dash-circle-fill fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">ว่าง/ไม่ใช้งาน</p>
                <h4 class="mb-0 fw-black text-dark">{{ inactiveIPs }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-12 mt-4 fade-in-up delay-500">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden glass-card">
        <div class="card-body p-4">
          <!-- Filters & Search -->
          <div class="row mb-4 g-3">
            <div class="col-md-6 col-lg-4">
              <div class="input-group input-group-custom shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-primary px-3">
                  <i class="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  class="form-control border-start-0 ps-0"
                  v-model="searchQuery"
                  @input="debounceFetch"
                  placeholder="ค้นหา IP, อุปกรณ์, แผนก, คนใช้งาน..."
                />
              </div>
            </div>
            <div class="col-md-6 col-lg-3">
              <select
                class="form-select form-select-custom shadow-sm"
                v-model="statusFilter"
                @change="fetchIPs"
              >
                <option value="all">ทุกสถานะ</option>
                <option value="active">🟢 ใช้งานอยู่</option>
                <option value="inactive">⚪ ว่าง/ไม่ใช้งาน</option>
                <option value="reserved">🟡 จอง/สงวนไว้</option>
              </select>
            </div>
          </div>

          <!-- Table -->
          <div class="table-responsive rounded-3 border">
            <table class="table table-hover table-custom mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th scope="col" class="text-secondary fw-bold py-3 px-3">IP Address</th>
                  <th scope="col" class="text-secondary fw-bold py-3">ชื่ออุปกรณ์ / MAC Address</th>
                  <th scope="col" class="text-secondary fw-bold py-3">แผนก & ผู้ใช้งาน</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center">ประเภท</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center">สถานะ</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center" width="120">
                    จัดการ
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="6" class="text-center py-5 text-muted">
                    <div
                      class="spinner-border spinner-border-sm text-primary me-2"
                      role="status"
                    ></div>
                    กำลังโหลดข้อมูล...
                  </td>
                </tr>
                <tr v-else-if="ips.length === 0">
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> ไม่พบข้อมูล IP Address
                  </td>
                </tr>
                <tr v-for="ip in ips" :key="ip.id" class="item-row">
                  <td class="px-3 fw-bold text-dark">
                    <i class="bi bi-hdd-network text-primary me-2"></i>{{ ip.ip_address }}
                  </td>
                  <td>
                    <div class="fw-bold text-dark">{{ ip.device_name || '-' }}</div>
                    <div class="small text-muted font-monospace">
                      <i class="bi bi-cpu me-1"></i>{{ ip.mac_address || 'ไม่มี MAC' }}
                    </div>
                  </td>
                  <td>
                    <div class="fw-bold">
                      <i class="bi bi-building me-1 text-secondary"></i>{{ ip.department || '-' }}
                    </div>
                    <div class="small text-muted">
                      <i class="bi bi-person me-1 text-secondary"></i>{{ ip.user_name || '-' }}
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border p-2">{{ ip.device_type }}</span>
                  </td>
                  <td class="text-center">
                    <span
                      class="badge rounded-pill p-2 px-3 fw-bold border"
                      :class="getStatusClass(ip.status)"
                    >
                      {{ getStatusLabel(ip.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <button
                        class="btn btn-sm btn-light text-primary hover-lift rounded-circle"
                        style="width: 35px; height: 35px"
                        @click="editIP(ip)"
                        title="แก้ไข"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-light text-danger hover-lift rounded-circle"
                        style="width: 35px; height: 35px"
                        @click="deleteIP(ip)"
                        title="ลบ"
                      >
                        <i class="bi bi-trash3-fill"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-3 text-muted small text-end">
            แสดงทั้งหมด <strong>{{ ips.length }}</strong> รายการ
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="ipModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 bg-white rounded-top-4">
            <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
              <div
                class="icon-square bg-gradient-primary text-white shadow-sm"
                style="width: 35px; height: 35px; font-size: 1rem"
              >
                <i :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-lg'"></i>
              </div>
              {{ isEditMode ? 'แก้ไขข้อมูล IP Address' : 'ลงทะเบียน IP Address ใหม่' }}
            </h5>
            <button
              type="button"
              class="btn-close shadow-none"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4 pt-3">
            <form @submit.prevent="saveIP">
              <div class="row g-3">
                <div class="col-md-6 border-end-md pb-3 pb-md-0 d-md-flex flex-column">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >IP Address <span class="text-danger">*</span></label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0 text-primary"
                        ><i class="bi bi-hdd-network"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0 fw-bold fs-5 text-dark"
                        v-model="form.ip_address"
                        required
                        placeholder="192.168.1.x"
                        :disabled="isEditMode && false"
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ชื่ออุปกรณ์ (Device Name)</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-laptop"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.device_name"
                        placeholder="เช่น COM-ADMIN-01"
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >MAC Address</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-cpu"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0 font-monospace"
                        v-model="form.mac_address"
                        placeholder="00:1A:2B:3C:4D:5E"
                      />
                    </div>
                  </div>

                  <div class="mb-0 flex-grow-1">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ประเภทอุปกรณ์</label
                    >
                    <select class="form-select form-select-custom" v-model="form.device_type">
                      <option value="PC">PC (เครื่องคอมพิวเตอร์)</option>
                      <option value="Notebook">Notebook (แล็ปท็อป)</option>
                      <option value="Printer">Printer (เครื่องพิมพ์)</option>
                      <option value="Scanner">Scanner</option>
                      <option value="Server">Server</option>
                      <option value="Network">Network Device (Switch/Router)</option>
                      <option value="CCTV">CCTV Camera</option>
                      <option value="Mobile">Mobile/Tablet</option>
                      <option value="Other">อื่นๆ (Other)</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-6 ps-md-4">
                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >แผนก / หน่วยงาน</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-building"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.department"
                        placeholder="ระบุแผนก"
                        list="dept_list"
                      />
                      <datalist id="dept_list">
                        <option v-for="d in uniqueDepts" :key="d" :value="d"></option>
                      </datalist>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ผู้รับผิดชอบ / คนใช้งาน</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-person"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.user_name"
                        placeholder="ระบุชื่อพนักงาน"
                        list="staff_list"
                      />
                      <datalist id="staff_list">
                        <option
                          v-for="staff in staffList"
                          :key="staff.id"
                          :value="staff.name"
                        ></option>
                      </datalist>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >สถานะ <span class="text-danger">*</span></label
                    >
                    <select
                      class="form-select form-select-custom fw-bold"
                      v-model="form.status"
                      required
                      :class="{
                        'text-success': form.status === 'active',
                        'text-warning': form.status === 'reserved',
                        'text-secondary': form.status === 'inactive'
                      }"
                    >
                      <option value="active">🟢 ใช้งานอยู่ (Active)</option>
                      <option value="inactive">⚪ ว่าง/ไม่ได้ใช้งาน (Inactive)</option>
                      <option value="reserved">🟡 จอง/สงวนสิทธิ์ไว้ (Reserved)</option>
                    </select>
                  </div>

                  <div class="mb-0">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >หมายเหตุ</label
                    >
                    <textarea
                      class="form-control"
                      v-model="form.notes"
                      rows="3"
                      placeholder="ระบุรายละเอียดเพิ่มเติม..."
                    ></textarea>
                  </div>
                </div>
              </div>
              <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <button
                  type="button"
                  class="btn btn-light rounded-pill px-4 fw-bold shadow-sm"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
                  :disabled="saving"
                >
                  <div class="spinner-border spinner-border-sm" role="status" v-if="saving"></div>
                  <i class="bi bi-save" v-else></i> บันทึกข้อมูล
                </button>
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
import * as bootstrap from 'bootstrap';

export default {
  name: 'IpAddressList',
  data() {
    return {
      ips: [],
      loading: true,
      saving: false,
      searchQuery: '',
      statusFilter: 'all',
      debounceTimer: null,
      modalInstance: null,
      isEditMode: false,
      staffList: [],
      form: {
        id: null,
        ip_address: '',
        device_name: '',
        mac_address: '',
        device_type: 'PC',
        department: '',
        user_name: '',
        status: 'active',
        notes: ''
      }
    };
  },
  computed: {
    totalIPs() {
      return this.ips.length;
    },
    activeIPs() {
      return this.ips.filter((ip) => ip.status === 'active').length;
    },
    reservedIPs() {
      return this.ips.filter((ip) => ip.status === 'reserved').length;
    },
    inactiveIPs() {
      return this.ips.filter((ip) => ip.status === 'inactive').length;
    },
    uniqueDepts() {
      const depts = this.ips.map((ip) => ip.department).filter(Boolean);
      return [...new Set(depts)].sort();
    }
  },
  mounted() {
    this.fetchIPs();
    this.fetchStaff();
    // Initialize Modal
    const modalEl = document.getElementById('ipModal');
    if (modalEl) {
      this.modalInstance = new bootstrap.Modal(modalEl);
      modalEl.addEventListener('hidden.bs.modal', () => {
        this.resetForm();
      });
    }
  },
  beforeUnmount() {
    if (this.modalInstance) {
      this.modalInstance.dispose();
    }
  },
  methods: {
    debounceFetch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.fetchIPs();
      }, 400);
    },
    async fetchIPs() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/ip_address/get_ips.php', {
          params: { search: this.searchQuery, status: this.statusFilter }
        });
        if (res.data.success) {
          this.ips = res.data.data || [];
        }
      } catch (error) {
        console.error('Error fetching IPs:', error);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'ดึงข้อมูลล้มเหลว',
          showConfirmButton: false,
          timer: 3000
        });
      } finally {
        this.loading = false;
      }
    },
    async fetchStaff() {
      try {
        const res = await axios.get('/api-digital/duties/get_employee.php');
        if (res.data && res.data.data) {
          this.staffList = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching staff info:', error);
      }
    },
    getStatusLabel(status) {
      if (status === 'active') return '🟢 ใช้งานอยู่';
      if (status === 'inactive') return '⚪ ว่าง/ไม่ได้ใช้งาน';
      if (status === 'reserved') return '🟡 จอง/สงวนไว้';
      return parseInt(status) || status;
    },
    getStatusClass(status) {
      if (status === 'active') return 'text-success bg-success bg-opacity-10 border-success';
      if (status === 'inactive')
        return 'text-secondary bg-secondary bg-opacity-10 border-secondary';
      if (status === 'reserved') return 'text-warning bg-warning bg-opacity-10 border-warning';
      return '';
    },
    openAddModal() {
      this.isEditMode = false;
      this.resetForm();
      this.modalInstance.show();
    },
    editIP(item) {
      this.isEditMode = true;
      this.form = { ...item };
      this.modalInstance.show();
    },
    resetForm() {
      this.form = {
        id: null,
        ip_address: '',
        device_name: '',
        mac_address: '',
        device_type: 'PC',
        department: '',
        user_name: '',
        status: 'active',
        notes: ''
      };
    },
    async saveIP() {
      this.saving = true;
      const url = this.isEditMode
        ? '/api-digital/ip_address/update_ip.php'
        : '/api-digital/ip_address/add_ip.php';

      try {
        const res = await axios.post(url, this.form);
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: res.data.message,
            timer: 2000,
            showConfirmButton: false
          });
          this.modalInstance.hide();
          this.fetchIPs();
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error('Save error', error);
        Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      } finally {
        this.saving = false;
      }
    },
    deleteIP(item) {
      Swal.fire({
        title: 'ยืนยันการลบ',
        text: `คุณต้องการลบรายการ IP ${item.ip_address} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#secondary',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const res = await axios.post('/api-digital/ip_address/delete_ip.php', { id: item.id });
            if (res.data.success) {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'ลบข้อมูลสำเร็จ',
                showConfirmButton: false,
                timer: 3000
              });
              this.fetchIPs();
            } else {
              throw new Error(res.data.message);
            }
          } catch (error) {
            Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถลบข้อมูลได้', 'error');
          }
        }
      });
    }
  }
};
</script>

<style scoped>
/* Animations */
.fade-in {
  animation: fadeIn 0.4s ease-out;
}
.fade-in-up {
  animation: fadeInUp 0.5s ease-out forwards;
  opacity: 0;
}
.delay-100 {
  animation-delay: 0.1s;
}
.delay-200 {
  animation-delay: 0.2s;
}
.delay-300 {
  animation-delay: 0.3s;
}
.delay-400 {
  animation-delay: 0.4s;
}
.delay-500 {
  animation-delay: 0.5s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Custom UI Items */
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

/* Input Groups */
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
.form-select-custom {
  background-color: #f8f9fa;
  border-color: #e9ecef;
  border-radius: 0.5rem;
}
.form-select-custom:focus {
  background-color: #fff;
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Data tables */
.table-custom th {
  border-bottom-width: 2px !important;
  letter-spacing: 0.5px;
}
.item-row {
  transition: all 0.2s ease;
}
.item-row:hover {
  background-color: #f8f9fa !important;
}

/* Stats Card */
.stat-card {
  transition:
    transform 0.2s,
    box-shadow 0.2s;
  cursor: default;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}

.custom-breadcrumb .breadcrumb-item a {
  color: #6c757d;
  transition: color 0.2s;
}
.custom-breadcrumb .breadcrumb-item a:hover {
  color: var(--bs-primary);
}

.border-end-md {
  border-right: 1px solid #dee2e6;
}
@media (max-width: 767.98px) {
  .border-end-md {
    border-right: none;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
  }
}
</style>
