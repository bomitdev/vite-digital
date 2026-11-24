<template>
  <div class="loan-page min-vh-100 py-3 px-3">
    <div class="container" style="max-width: 920px">
      <!-- Compact Top Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-3">
          <div class="hero-icon-wrap shadow-sm">
            <i class="bi bi-pc-display-horizontal"></i>
          </div>
          <div>
            <h4 class="fw-bold text-dark mb-0">แบบฟอร์มขอยืมอุปกรณ์คอมพิวเตอร์</h4>
            <small class="text-secondary">
              โปรดระบุข้อมูลผู้ยืม เลือกอุปกรณ์สำรองที่ต้องการ และกำหนดวันส่งคืน
            </small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 fw-semibold">
            <i class="bi bi-laptop me-1"></i>
            พร้อมยืม {{ availableAssets.length }} รายการ
          </span>
          <button
            type="button"
            class="btn btn-white btn-sm rounded-pill px-3 shadow-sm border text-secondary fw-medium d-inline-flex align-items-center gap-1"
            @click="$router.back()"
          >
            <i class="bi bi-arrow-left"></i>
            <span>ย้อนกลับ</span>
          </button>
        </div>
      </div>

      <!-- Main Form Card -->
      <div class="card main-form-card border-0 rounded-4 overflow-hidden">
        <div class="card-top-accent"></div>
        <div class="card-body p-3 p-md-4">
          <form @submit.prevent="submitRequest">
            <!-- Section 1: Borrower Info -->
            <div class="form-section mb-3">
              <div class="section-title mb-2">
                <span class="step-badge">1</span>
                <h6 class="fw-bold text-dark mb-0">ข้อมูลผู้ขอยืม</h6>
              </div>

              <div class="row g-2 g-md-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    ชื่อผู้ยืม <span class="text-danger">*</span>
                  </label>
                  <div class="input-group input-group-modern">
                    <span class="input-group-text">
                      <i class="bi bi-person"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.borrower_name"
                      list="staffList"
                      class="form-control"
                      required
                      placeholder="พิมพ์หรือเลือกชื่อ-นามสกุล"
                      @change="onStaffSelected"
                    />
                  </div>
                  <datalist id="staffList">
                    <option
                      v-for="staff in staffList"
                      :key="staff.ID"
                      :value="staff.FULLNAME"
                    ></option>
                  </datalist>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    หน่วยงาน/แผนก <span class="text-danger">*</span>
                  </label>
                  <div class="input-group input-group-modern">
                    <span class="input-group-text">
                      <i class="bi bi-building"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.department"
                      list="deptList"
                      class="form-control"
                      required
                      placeholder="ระบุหน่วยงาน/แผนก"
                    />
                  </div>
                  <datalist id="deptList">
                    <option
                      v-for="dept in departmentList"
                      :key="dept.HR_DEPARTMENT_SUB_ID"
                      :value="dept.HR_DEPARTMENT_SUB_NAME"
                    ></option>
                  </datalist>
                </div>
              </div>
            </div>

            <!-- Section 2: Asset Selection -->
            <div class="form-section mb-3 pt-2 border-top">
              <div class="section-title mb-2">
                <span class="step-badge">2</span>
                <h6 class="fw-bold text-dark mb-0">
                  อุปกรณ์ที่ต้องการยืม <span class="text-danger">*</span>
                </h6>
              </div>

              <div class="dropdown custom-select-dropdown w-100">
                <div
                  v-if="isDropdownOpen"
                  @click="isDropdownOpen = false"
                  style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 999"
                ></div>

                <button
                  class="btn asset-select-btn w-100 d-flex justify-content-between align-items-center text-start position-relative"
                  :class="{ 'is-open': isDropdownOpen, 'has-value': !!selectedAsset }"
                  type="button"
                  @click="toggleDropdown"
                  style="z-index: 1000"
                >
                  <div class="d-flex align-items-center text-truncate pe-2">
                    <template v-if="selectedAsset">
                      <img
                        v-if="selectedAsset.image_path"
                        :src="getImageUrl(selectedAsset.image_path)"
                        class="me-2 rounded-2 object-fit-cover border"
                        style="width: 28px; height: 28px"
                      />
                      <span
                        v-else
                        class="me-2 rounded-2 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px"
                      >
                        <i class="bi bi-pc-display"></i>
                      </span>
                      <span class="badge bg-primary-subtle text-primary me-2">
                        {{ selectedAsset.asset_code }}
                      </span>
                      <span class="text-truncate text-dark fw-medium">
                        {{ selectedAsset.name }} ({{ selectedAsset.type }})
                      </span>
                    </template>
                    <template v-else>
                      <i class="bi bi-search text-muted me-2"></i>
                      <span class="text-muted">-- เลือกอุปกรณ์ (แสดงเฉพาะเครื่องสำรอง) --</span>
                    </template>
                  </div>
                  <i
                    class="bi bi-chevron-down text-secondary transition-chevron"
                    :class="{ 'rotate-180': isDropdownOpen }"
                  ></i>
                </button>

                <div
                  class="dropdown-menu w-100 shadow-lg border rounded-4 p-2 mt-1"
                  :class="{ show: isDropdownOpen }"
                  style="z-index: 1001; position: absolute"
                >
                  <div class="px-1 pb-2 border-bottom mb-2">
                    <div class="input-group input-group-sm">
                      <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                      </span>
                      <input
                        ref="assetSearchInput"
                        type="text"
                        v-model="assetSearch"
                        class="form-control bg-light border-start-0 shadow-none"
                        placeholder="ค้นหารหัส, ชื่ออุปกรณ์, ยี่ห้อ..."
                        @click.stop
                      />
                    </div>
                  </div>

                  <div style="max-height: 320px; overflow-y: auto">
                    <div
                      v-if="filteredAssets.length === 0"
                      class="text-center text-muted py-4 small"
                    >
                      <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                      ไม่พบอุปกรณ์สำรองที่ตรงกับคำค้นหา
                    </div>
                    <a
                      v-for="asset in filteredAssets"
                      :key="asset.id"
                      class="dropdown-item d-flex align-items-center rounded-3 p-2 mb-1 custom-dropdown-item"
                      :class="{ active: String(form.asset_id) === String(asset.id) }"
                      href="#"
                      @click.prevent="selectAsset(asset)"
                    >
                      <img
                        v-if="asset.image_path"
                        :src="getImageUrl(asset.image_path)"
                        class="rounded-3 me-3 object-fit-cover border flex-shrink-0"
                        style="width: 52px; height: 52px"
                      />
                      <div
                        v-else
                        class="rounded-3 me-3 bg-light d-flex align-items-center justify-content-center text-primary border flex-shrink-0"
                        style="width: 52px; height: 52px"
                      >
                        <i class="bi bi-pc-display fs-5"></i>
                      </div>
                      <div class="overflow-hidden flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                          <span class="badge bg-primary-subtle text-primary">
                            {{ asset.asset_code }}
                          </span>
                          <span class="fw-bold text-dark text-truncate">{{ asset.name }}</span>
                        </div>
                        <div class="small text-muted text-truncate">
                          {{ asset.type || '-' }} · ยี่ห้อ: {{ asset.brand || '-' }}
                          <span v-if="asset.model"> ({{ asset.model }})</span>
                        </div>
                      </div>
                      <i
                        v-if="String(form.asset_id) === String(asset.id)"
                        class="bi bi-check-circle-fill text-primary ms-2"
                      ></i>
                    </a>
                  </div>
                </div>
              </div>

              <!-- Selected Asset Preview -->
              <div
                v-if="selectedAsset"
                class="selected-asset-card mt-2 p-2 px-3 rounded-3 d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3"
              >
                <div class="flex-shrink-0">
                  <img
                    v-if="selectedAsset.image_path"
                    :src="getImageUrl(selectedAsset.image_path)"
                    class="rounded-3 shadow-sm border bg-white"
                    style="width: 60px; height: 60px; object-fit: cover"
                  />
                  <div
                    v-else
                    class="d-flex justify-content-center align-items-center bg-white rounded-3 shadow-sm border text-primary"
                    style="width: 60px; height: 60px"
                  >
                    <i class="bi bi-pc-display fs-4"></i>
                  </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <div class="fw-bold text-primary text-truncate small">
                      [{{ selectedAsset.asset_code }}] {{ selectedAsset.name }}
                    </div>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0"
                      style="font-size: 0.72rem"
                      @click="clearAsset"
                    >
                      <i class="bi bi-x-lg me-1"></i>เปลี่ยนอุปกรณ์
                    </button>
                  </div>
                  <div class="d-flex flex-wrap gap-1">
                    <span class="spec-chip">
                      <i class="bi bi-tag me-1"></i>{{ selectedAsset.brand || '-' }} {{ selectedAsset.model || '' }}
                    </span>
                    <span class="spec-chip">
                      <i class="bi bi-cpu me-1"></i>{{ selectedAsset.spec_cpu || '-' }}
                    </span>
                    <span class="spec-chip">
                      <i class="bi bi-memory me-1"></i>RAM {{ selectedAsset.spec_ram || '-' }}
                    </span>
                    <span class="spec-chip">
                      <i class="bi bi-hdd me-1"></i>{{ selectedAsset.spec_storage || '-' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 3: Objective & Return Date -->
            <div class="form-section mb-3 pt-2 border-top">
              <div class="section-title mb-2">
                <span class="step-badge">3</span>
                <h6 class="fw-bold text-dark mb-0">รายละเอียดการใช้งานและกำหนดส่งคืน</h6>
              </div>

              <div class="row g-2 g-md-3">
                <div class="col-md-7">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    วัตถุประสงค์ในการยืม <span class="text-danger">*</span>
                  </label>
                  <textarea
                    v-model="form.objective"
                    class="form-control modern-textarea"
                    rows="2"
                    required
                    placeholder="เช่น ใช้สำหรับอบรม, ออกหน่วยแพทย์เคลื่อนที่, ทดแทนเครื่องเดิมที่ส่งซ่อม..."
                  ></textarea>
                </div>

                <div class="col-md-5">
                  <label class="form-label small fw-semibold text-dark mb-1">
                    วันที่คาดว่าจะคืน <span class="text-danger">*</span>
                  </label>
                  <div class="input-group input-group-modern mb-2">
                    <span class="input-group-text">
                      <i class="bi bi-calendar-event"></i>
                    </span>
                    <input
                      type="date"
                      v-model="form.expected_return_date"
                      class="form-control"
                      required
                      :min="minDate"
                    />
                  </div>
                  <div class="d-flex flex-wrap gap-1">
                    <button
                      v-for="preset in datePresets"
                      :key="preset.days"
                      type="button"
                      class="btn btn-sm preset-btn rounded-pill px-2 py-0"
                      style="font-size: 0.75rem"
                      :class="{ active: form.expected_return_date === getPresetDate(preset.days) }"
                      @click="form.expected_return_date = getPresetDate(preset.days)"
                    >
                      {{ preset.label }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit -->
            <div class="pt-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
              <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                เมื่อส่งคำขอแล้ว เจ้าหน้าที่ไอทีจะตรวจสอบและอนุมัติรายการให้ต่อไป
              </div>
              <button
                type="submit"
                class="btn btn-primary submit-btn px-4 py-2 rounded-pill fw-semibold shadow-sm"
                :disabled="loading"
              >
                <span
                  v-if="loading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                <i v-else class="bi bi-send-fill me-2"></i>
                {{ loading ? 'กำลังส่งคำขอ...' : 'ส่งคำขอยืมอุปกรณ์' }}
              </button>
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
      assetSearch: '',
      datePresets: [
        { label: '+3 วัน', days: 3 },
        { label: '+7 วัน', days: 7 },
        { label: '+14 วัน', days: 14 },
        { label: '+30 วัน', days: 30 }
      ],
      form: {
        borrower_name: '',
        department: '',
        asset_id: '',
        objective: '',
        expected_return_date: ''
      },
      loading: false,
      isDropdownOpen: false
    };
  },
  computed: {
    minDate() {
      return new Date().toLocaleDateString('en-CA');
    },
    selectedAsset() {
      if (!this.form.asset_id) return null;
      return this.availableAssets.find((a) => String(a.id) === String(this.form.asset_id));
    },
    filteredAssets() {
      const q = this.assetSearch.trim().toLowerCase();
      if (!q) return this.availableAssets;
      return this.availableAssets.filter((a) =>
        [a.asset_code, a.name, a.type, a.brand, a.model]
          .filter(Boolean)
          .some((field) => String(field).toLowerCase().includes(q))
      );
    }
  },
  mounted() {
    this.fetchAssets();
    this.fetchStaff();
    this.fetchDepartments();
  },
  methods: {
    getPresetDate(days) {
      const d = new Date();
      d.setDate(d.getDate() + days);
      return d.toLocaleDateString('en-CA');
    },
    toggleDropdown() {
      this.isDropdownOpen = !this.isDropdownOpen;
      if (this.isDropdownOpen) {
        this.$nextTick(() => {
          this.$refs.assetSearchInput?.focus();
        });
      }
    },
    clearAsset() {
      this.form.asset_id = '';
    },
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
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    selectAsset(asset) {
      this.form.asset_id = asset.id;
      this.isDropdownOpen = false;
      this.assetSearch = '';
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
      if (!this.form.asset_id) {
        Swal.fire({
          icon: 'warning',
          title: 'กรุณาเลือกอุปกรณ์',
          text: 'โปรดเลือกอุปกรณ์คอมพิวเตอร์สำรองที่ต้องการยืม',
          confirmButtonText: 'ตกลง'
        });
        return;
      }

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
          this.form = {
            borrower_name: '',
            department: '',
            asset_id: '',
            objective: '',
            expected_return_date: ''
          };
          this.fetchAssets();
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
.loan-page {
  background: radial-gradient(circle at top left, #eef4ff 0%, #f8fafc 45%, #f1f5f9 100%);
}

.btn-white {
  background-color: #ffffff;
}

.btn-white:hover {
  background-color: #f8fafc;
}

.hero-icon-wrap {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex-shrink: 0;
}

.main-form-card {
  box-shadow: 0 12px 36px rgba(15, 23, 42, 0.07);
  border: 1px solid #e2e8f0 !important;
}

.card-top-accent {
  height: 5px;
  background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #6366f1 100%);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.step-badge {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: #eff6ff;
  color: #2563eb;
  font-weight: 700;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid #bfdbfe;
}

.section-divider {
  border-color: #e2e8f0;
  opacity: 0.8;
}

.input-group-modern .input-group-text {
  background-color: #f8fafc;
  border-color: #cbd5e1;
  color: #64748b;
  border-top-left-radius: 12px;
  border-bottom-left-radius: 12px;
}

.input-group-modern .form-control {
  border-color: #cbd5e1;
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
  padding-top: 0.45rem;
  padding-bottom: 0.45rem;
}

.input-group-modern .form-control:focus,
.modern-textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

.modern-textarea {
  border-color: #cbd5e1;
  border-radius: 12px;
  padding: 0.75rem 0.9rem;
}

.asset-select-btn {
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 0.65rem 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.asset-select-btn:hover {
  border-color: #94a3b8;
}

.asset-select-btn.is-open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

.transition-chevron {
  transition: transform 0.2s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.custom-dropdown-item {
  transition: background-color 0.15s ease;
}

.custom-dropdown-item:hover {
  background-color: #f1f5f9;
}

.custom-dropdown-item.active {
  background-color: #eff6ff;
  color: inherit;
}

.selected-asset-card {
  background: linear-gradient(135deg, #f8faff 0%, #eff6ff 100%);
  border: 1px solid #bfdbfe;
}

.spec-chip {
  display: inline-flex;
  align-items: center;
  background-color: #ffffff;
  border: 1px solid #dbeafe;
  color: #334155;
  font-size: 0.75rem;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}

.preset-btn {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  transition: all 0.15s ease;
}

.preset-btn:hover {
  background-color: #e2e8f0;
  color: #1e293b;
}

.preset-btn.active {
  background-color: #eff6ff;
  color: #2563eb;
  border-color: #93c5fd;
  font-weight: 600;
}

.submit-btn {
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  border: none;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.28) !important;
}
</style>
