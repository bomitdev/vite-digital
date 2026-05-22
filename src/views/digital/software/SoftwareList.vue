<template>
  <div class="software-list-container">
    <!-- Header Area -->
    <header class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
      <div>
        <h2 class="text-primary fw-bold m-0"><i class="bi bi-box-seam me-2"></i>รายการซอฟต์แวร์</h2>
        <p class="text-muted small mb-0 mt-1">จัดการข้อมูลสิทธิ์และการติดตั้งซอฟต์แวร์</p>
      </div>
      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-info shadow-sm"
          @click="$router.push('/asset-management')"
          title="ไปที่ระบบจัดการครุภัณฑ์"
        >
          <i class="bi bi-display me-1"></i> ระบบครุภัณฑ์
        </button>
        <button
          class="btn btn-outline-secondary shadow-sm"
          @click="$router.push('/software-dashboard')"
        >
          <i class="bi bi-pie-chart-fill me-1"></i> แดชบอร์ด
        </button>
        <button class="btn btn-primary shadow-sm" @click="openModal('add')">
          <i class="bi bi-plus-lg me-1"></i> เพิ่มซอฟต์แวร์
        </button>
      </div>
    </header>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted"
                ><i class="bi bi-search"></i
              ></span>
              <input
                type="text"
                class="form-control border-start-0 ps-0 text-dark"
                v-model="filters.search"
                placeholder="ค้นหาด้วยชื่อ, ผู้พัฒนา, หรือคีย์..."
                @input="debouncedFetch"
              />
            </div>
          </div>
          <div class="col-md-3">
            <select
              class="form-select shadow-sm border-light-subtle"
              v-model="filters.status"
              @change="fetchSoftware"
            >
              <option value="all">สถานะทั้งหมด</option>
              <option value="active">ใช้งานปกติ (Valid)</option>
              <option value="expiring">ใกล้หมดอายุ (≤30 วัน)</option>
              <option value="expired">หมดอายุแล้ว</option>
            </select>
          </div>
          <div class="col-md-4 text-end">
            <!-- potential extra controls -->
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-secondary">
            <tr>
              <th scope="col" class="py-3 ps-4">ชื่อซอฟต์แวร์</th>
              <th scope="col" class="py-3">ประเภท / คีย์</th>
              <th scope="col" class="py-3">วันหมดอายุ</th>
              <th scope="col" class="py-3 text-center">จำนวนติดตั้ง</th>
              <th scope="col" class="py-3 text-center">สถานะ</th>
              <th scope="col" class="py-3 text-end pe-4">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="softwareList.length === 0">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                ไม่พบข้อมูลซอฟต์แวร์
              </td>
            </tr>
            <tr v-for="sw in softwareList" :key="sw.id">
              <td class="ps-4">
                <div class="fw-bold text-dark">
                  {{ sw.software_name }}
                  <span class="badge bg-light text-dark fw-normal border ms-1" v-if="sw.version">{{
                    sw.version
                  }}</span>
                </div>
                <div class="small text-muted">{{ sw.developer || 'ไม่ระบุผู้พัฒนา' }}</div>
              </td>
              <td>
                <div class="fw-medium text-dark">{{ sw.license_type || 'N/A' }}</div>
                <div
                  class="small text-muted text-truncate"
                  style="max-width: 150px"
                  :title="sw.license_key"
                >
                  {{ sw.license_key || 'ไม่มีคีย์' }}
                </div>
              </td>
              <td>
                <div v-if="sw.expiry_date">
                  <div class="text-dark">
                    {{ new Date(sw.expiry_date).toLocaleDateString('th-TH') }}
                  </div>
                  <div class="small" :class="getExpiryTextClass(sw.expiry_date)">
                    {{ getDaysRemainingText(sw.expiry_date) }}
                  </div>
                </div>
                <div v-else class="text-muted">-</div>
              </td>
              <td class="text-center">
                <span class="badge rounded-pill" :class="getUsageBadgeClass(sw)">
                  {{ sw.current_installations }} /
                  {{ sw.max_installations === null ? '∞' : sw.max_installations }}
                </span>
                <button
                  class="btn btn-sm btn-link text-decoration-none d-block w-100 p-0 mt-1"
                  style="font-size: 0.75rem"
                  @click="openInstallations(sw)"
                >
                  จัดการสิทธิ์
                </button>
              </td>
              <td class="text-center">
                <span class="badge" :class="getStatusBadge(sw.expiry_date).class">
                  {{ getStatusBadge(sw.expiry_date).text }}
                </span>
              </td>
              <td class="text-end pe-4">
                <button
                  class="btn btn-sm btn-light border shadow-sm me-2"
                  @click="openModal('edit', sw)"
                  title="แก้ไข"
                >
                  <i class="bi bi-pencil-square text-primary"></i>
                </button>
                <button
                  class="btn btn-sm btn-light border shadow-sm"
                  @click="deleteSoftware(sw.id)"
                  title="ลบ"
                >
                  <i class="bi bi-trash3 text-danger"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Software Edit/Add Modal -->
    <div class="modal fade" id="softwareModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-primary text-white pt-4 pb-3 px-4">
            <h5 class="modal-title fw-bold">
              <i
                class="bi"
                :class="modalMode === 'add' ? 'bi-plus-circle' : 'bi-pencil-square'"
              ></i>
              {{ modalMode === 'add' ? 'เพิ่มซอฟต์แวร์ใหม่' : 'แก้ไขข้อมูลซอฟต์แวร์' }}
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveSoftware" class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-bold"
                  >ชื่อซอฟต์แวร์ <span class="text-danger">*</span></label
                >
                <input type="text" class="form-control" v-model="form.name" required />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">เวอร์ชัน</label>
                <input type="text" class="form-control" v-model="form.version" />
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold">ผู้พัฒนา / ผู้จัดจำหน่าย</label>
                <input type="text" class="form-control" v-model="form.developer" />
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">ประเภทไลเซนส์</label>
                <select class="form-select" v-model="form.license_type">
                  <option value="Subscription">เช่าใช้ (Subscription)</option>
                  <option value="Perpetual">ซื้อขาด (Perpetual / One-time)</option>
                  <option value="Freeware">ฟรีแวร์ / โอเพนซอร์ส (Freeware / Open Source)</option>
                  <option value="OEM">OEM (ติดมากับเครื่อง)</option>
                  <option value="Other">อื่นๆ (Other)</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold">คีย์ซอฟต์แวร์</label>
                <input
                  type="text"
                  class="form-control font-monospace text-muted"
                  v-model="form.license_key"
                  placeholder="XXXX-XXXX-XXXX-XXXX"
                />
              </div>

              <div class="col-md-4">
                <label class="form-label fw-bold">วันที่เริ่มใช้งาน</label>
                <input type="date" class="form-control" v-model="form.start_date" />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">วันที่หมดอายุ</label>
                <input type="date" class="form-control" v-model="form.expiry_date" />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">จำนวนเครื่องที่ติดตั้งได้สูงสุด</label>
                <input
                  type="number"
                  class="form-control"
                  v-model="form.max_installations"
                  placeholder="เว้นว่างไว้หากไม่จำกัด"
                  min="1"
                />
              </div>

              <div class="col-12 mt-4 text-end">
                <button
                  type="button"
                  class="btn btn-secondary shadow-sm px-4 me-2"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary shadow-sm px-4">
                  <i class="bi bi-save me-1"></i> บันทึก
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Installations Modal Component -->
    <SoftwareInstallations
      :software="selectedSoftware"
      @installations-updated="handleInstallationsUpdated"
    />
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';
import SoftwareInstallations from './SoftwareInstallations.vue';

export default {
  name: 'SoftwareList',
  components: {
    SoftwareInstallations
  },
  data() {
    return {
      softwareList: [],
      filters: {
        search: '',
        status: 'all'
      },
      searchTimeout: null,
      modalMode: 'add',
      modalInstance: null,
      installationsModalInstance: null,
      selectedSoftware: null,
      form: {
        id: null,
        name: '',
        version: '',
        developer: '',
        license_key: '',
        license_type: 'Subscription',
        start_date: '',
        expiry_date: '',
        max_installations: ''
      }
    };
  },
  methods: {
    debouncedFetch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchSoftware();
      }, 500);
    },
    async fetchSoftware() {
      try {
        const res = await axios.get(`/api-digital/software/get_software.php`, {
          params: {
            search: this.filters.search,
            status: this.filters.status
          }
        });
        if (res.data.success) {
          this.softwareList = res.data.data;
        }
      } catch (error) {
        console.error('API Error', error);
      }
    },
    openModal(mode, sw = null) {
      this.modalMode = mode;
      if (mode === 'edit' && sw) {
        this.form = {
          id: sw.id,
          name: sw.software_name,
          version: sw.version || '',
          developer: sw.developer || '',
          license_key: sw.license_key || '',
          license_type: sw.license_type || 'Other',
          start_date: sw.start_date || '',
          expiry_date: sw.expiry_date || '',
          max_installations: sw.max_installations || ''
        };
      } else {
        this.form = {
          id: null,
          name: '',
          version: '',
          developer: '',
          license_key: '',
          license_type: 'Subscription',
          start_date: '',
          expiry_date: '',
          max_installations: ''
        };
      }

      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(document.getElementById('softwareModal'));
      }
      this.modalInstance.show();
    },
    hideModal() {
      if (this.modalInstance) {
        this.modalInstance.hide();
      }
    },
    async saveSoftware() {
      try {
        const url =
          this.modalMode === 'add'
            ? '/api-digital/software/add_software.php'
            : '/api-digital/software/update_software.php';

        const res = await axios.post(url, this.form);
        if (res.data.success) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.data.message,
            showConfirmButton: false,
            timer: 1500
          });
          this.hideModal();
          this.fetchSoftware();
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Server Error', 'error');
      }
    },
    async deleteSoftware(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'การดำเนินการนี้จะลบข้อมูลซอฟต์แวร์และประวัติการติดตั้งอย่างถาวร!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#858c93',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/software/delete_software.php', { id });
          if (res.data.success) {
            Swal.fire('ลบสำเร็จ!', 'ข้อมูลซอฟต์แวร์ถูกลบเรียบร้อยแล้ว.', 'success');
            this.fetchSoftware();
          } else {
            Swal.fire('เกิดข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (error) {
          console.error(error);
        }
      }
    },
    openInstallations(sw) {
      this.selectedSoftware = sw;
      if (!this.installationsModalInstance) {
        this.installationsModalInstance = new bootstrap.Modal(
          document.getElementById('installationsModal')
        );
      }
      // Delay opening slightly to let vue reactivity pass props
      this.$nextTick(() => {
        this.installationsModalInstance.show();
      });
    },
    handleInstallationsUpdated(newCount) {
      // Update count locally without refetching the whole list
      if (this.selectedSoftware) {
        const sw = this.softwareList.find((s) => s.id === this.selectedSoftware.id);
        if (sw) {
          sw.current_installations = newCount;
        }
      }
    },
    getDaysRemainingText(expiryDateStr) {
      if (!expiryDateStr) return '';
      const expiry = new Date(expiryDateStr);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const diffTime = expiry - today;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays < 0) return `ผ่านมาแล้ว ${Math.abs(diffDays)} วัน`;
      if (diffDays === 0) return 'หมดอายุวันนี้';
      if (diffDays <= 30) return `เหลืออีก ${diffDays} วัน`;
      return `อีก ${diffDays} วัน`;
    },
    getExpiryTextClass(expiryDateStr) {
      if (!expiryDateStr) return '';
      const expiry = new Date(expiryDateStr);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const diffTime = expiry - today;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays < 0) return 'text-danger fw-bold';
      if (diffDays <= 30) return 'text-warning fw-bold';
      return 'text-success';
    },
    getStatusBadge(expiryDateStr) {
      if (!expiryDateStr)
        return {
          text: 'พร้อมใช้งาน',
          class: 'bg-success-subtle text-success border border-success'
        };

      const expiry = new Date(expiryDateStr);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const diffTime = expiry - today;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays < 0)
        return { text: 'หมดอายุ', class: 'bg-danger-subtle text-danger border border-danger' };
      if (diffDays <= 30)
        return {
          text: 'ใกล้หมดอายุ',
          class: 'bg-warning-subtle text-warning border border-warning'
        };
      return { text: 'พร้อมใช้งาน', class: 'bg-success-subtle text-success border border-success' };
    },
    getUsageBadgeClass(sw) {
      if (sw.max_installations === null)
        return 'bg-success-subtle text-success border border-success';
      const pct = sw.current_installations / sw.max_installations;
      if (pct >= 1) return 'bg-danger-subtle text-danger border border-danger';
      if (pct >= 0.8) return 'bg-warning-subtle text-warning border border-warning';
      return 'bg-secondary-subtle text-secondary border border-secondary';
    }
  },
  mounted() {
    this.fetchSoftware();
  }
};
</script>

<style scoped>
.software-list-container {
  padding: 1.5rem 2rem;
  background-color: #f8f9fc;
  min-height: calc(100vh - 60px);
}

.table th {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
</style>
