<template>
  <div class="repair-page container-fluid py-3 py-md-4 px-3 px-md-4 min-vh-100">
    <div class="card main-card border-0 rounded-4 overflow-hidden">
      <div class="card-top-accent"></div>

      <!-- Header -->
      <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-box shadow-sm">
            <i class="bi bi-tools"></i>
          </div>
          <div>
            <h5 class="fw-bold m-0 text-dark">ระบบแจ้งซ่อมคอมพิวเตอร์</h5>
            <small class="text-muted">แจ้งปัญหาอุปกรณ์ไอทีและติดตามสถานะการซ่อมแบบเรียลไทม์</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-light border rounded-pill px-3 fw-medium text-secondary d-inline-flex align-items-center gap-1"
            @click="$router.push('/home-backoffice')"
          >
            <i class="bi bi-house-fill"></i>
            <span>กลับหน้าหลัก</span>
          </button>
          <button
            class="btn btn-danger btn-add-repair rounded-pill px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-1"
            @click="openModal()"
          >
            <i class="bi bi-plus-lg"></i>
            <span>แจ้งซ่อมใหม่</span>
          </button>
        </div>
      </div>

      <div class="card-body p-3 p-md-4">
        <!-- Filter & Quick Status Bar -->
        <div class="filter-toolbar p-3 rounded-4 mb-3">
          <div class="row g-2 align-items-center">
            <div class="col-lg-5 col-md-6">
              <div class="input-group search-input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                  <i class="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-control border-start-0 shadow-none"
                  placeholder="ค้นหาเลขที่ใบแจ้ง, ชื่อผู้แจ้ง, อาการ, รหัสครุภัณฑ์..."
                  @input="fetchRequests"
                />
                <button
                  v-if="filters.search"
                  type="button"
                  class="btn btn-white border border-start-0 text-muted"
                  @click="filters.search = ''; fetchRequests()"
                  title="ล้างคำค้นหา"
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>

            <div class="col-lg-7 col-md-6 d-flex flex-wrap align-items-center justify-content-md-end gap-1">
              <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                class="btn btn-sm status-filter-pill rounded-pill px-3 py-1"
                :class="{ active: filters.status === tab.value }"
                @click="setStatusFilter(tab.value)"
              >
                <span v-if="tab.dotClass" class="status-dot me-1" :class="tab.dotClass"></span>
                {{ tab.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive rounded-3 border">
          <table class="table table-hover align-middle mb-0 repair-table">
            <thead>
              <tr>
                <th width="13%">Ticket No / วันที่แจ้ง</th>
                <th width="16%">ผู้แจ้ง / แผนก</th>
                <th width="25%">ปัญหา / อาการ</th>
                <th width="16%">สถานที่ / เบอร์โทร</th>
                <th width="11%" class="text-center">สถานะ</th>
                <th width="13%">ผู้ดำเนินการ</th>
                <th width="6%" class="text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in requests" :key="req.id">
                <td>
                  <span class="ticket-badge">{{ req.ticket_no }}</span>
                  <div class="small text-muted mt-1" v-if="req.created_at">
                    <i class="bi bi-clock me-1"></i>{{ formatDate(req.created_at) }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ req.requester_name }}</div>
                  <div class="small text-muted text-truncate" style="max-width: 190px" :title="req.department">
                    {{ req.department || '-' }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-primary mb-1">{{ req.issue_title }}</div>
                  <div
                    v-if="req.issue_description"
                    class="small text-muted text-truncate"
                    style="max-width: 280px"
                    :title="req.issue_description"
                  >
                    {{ req.issue_description }}
                  </div>
                  <div v-if="req.asset_code" class="mt-1">
                    <span class="asset-chip">
                      <i class="bi bi-pc-display me-1"></i>{{ req.asset_code }}
                    </span>
                  </div>
                </td>
                <td>
                  <div class="small text-dark fw-medium">
                    <i class="bi bi-geo-alt text-danger me-1"></i>{{ req.location || '-' }}
                  </div>
                  <div class="small text-muted mt-1">
                    <i class="bi bi-telephone me-1"></i>{{ req.contact_tel || '-' }}
                  </div>
                </td>
                <td class="text-center">
                  <span :class="['status-pill', getStatusClass(req.status)]">
                    <i :class="['bi me-1', getStatusIcon(req.status)]"></i>
                    {{ getStatusLabel(req.status) }}
                  </span>
                </td>
                <td>
                  <div v-if="req.technician_name" class="tech-chip">
                    <i class="bi bi-person-gear text-primary me-1"></i>
                    <span class="text-truncate">{{ req.technician_name }}</span>
                  </div>
                  <div v-else class="small text-muted fst-italic">รอมอบหมาย</div>
                </td>
                <td class="text-center">
                  <button
                    class="btn btn-sm btn-outline-primary rounded-pill px-2"
                    @click="openModal(req)"
                    title="ดู / จัดการรายการ"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="requests.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                  <div class="fw-medium">ไม่พบรายการแจ้งซ่อม</div>
                  <small>ลองเปลี่ยนคำค้นหาหรือตัวกรองสถานะ</small>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="repairModal" tabindex="-1" ref="repairModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <div class="modal-header modal-header-gradient text-white py-3 px-4">
            <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
              <i class="bi bi-tools"></i>
              <span>{{ isEdit ? 'จัดการงานซ่อม' : 'แจ้งซ่อมคอมพิวเตอร์' }}</span>
              <span v-if="isEdit && form.ticket_no" class="badge bg-white text-danger rounded-pill fs-6 ms-1">
                {{ form.ticket_no }}
              </span>
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveRequest">
              <div class="row g-3">
                <!-- User Info -->
                <div class="col-12">
                  <h6 class="border-bottom pb-2 text-secondary">ข้อมูลผู้แจ้ง</h6>
                </div>
                <div class="col-md-6">
                  <label class="form-label">ชื่อผู้แจ้ง <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    v-model="form.requester_name"
                    list="staffDatalist"
                    class="form-control"
                    required
                    :disabled="isEdit && !isAdmin"
                    @change="handleStaffChange"
                  />
                  <datalist id="staffDatalist">
                    <option v-for="staff in staffList" :key="staff.ID" :value="staff.FULLNAME">
                      {{ staff.HR_DEPARTMENT_SUB_NAME }}
                    </option>
                  </datalist>
                </div>
                <div class="col-md-6">
                  <label class="form-label">แผนก/หน่วยงาน</label>
                  <input
                    type="text"
                    v-model="form.department"
                    class="form-control"
                    :disabled="isEdit && !isAdmin"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label"
                    >เบอร์โทรศัพท์ติดต่อ <span class="text-danger">*</span></label
                  >
                  <input
                    type="text"
                    v-model="form.contact_tel"
                    class="form-control"
                    required
                    :disabled="isEdit && !isAdmin"
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label">สถานที่/ห้อง</label>
                  <input
                    type="text"
                    v-model="form.location"
                    class="form-control"
                    :disabled="isEdit && !isAdmin"
                  />
                </div>

                <!-- Issue Info -->
                <div class="col-12 mt-4">
                  <h6 class="border-bottom pb-2 text-secondary">รายละเอียดปัญหา</h6>
                </div>
                <div class="col-md-12">
                  <label class="form-label">หัวข้อปัญหา <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    v-model="form.issue_title"
                    class="form-control"
                    placeholder="เช่น เปิดไม่ติด, ปริ้นไม่ออก"
                    required
                    :disabled="isEdit && !isAdmin"
                  />
                </div>
                <div class="col-md-12">
                  <label class="form-label">รายละเอียดเพิ่มเติม</label>
                  <textarea
                    v-model="form.issue_description"
                    class="form-control"
                    rows="3"
                    :disabled="isEdit && !isAdmin"
                  ></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">รหัสครุภัณฑ์ (ถ้ามี)</label>
                  <input
                    type="text"
                    v-model="form.asset_code"
                    list="assetList"
                    class="form-control"
                    placeholder="ค้นหา หรือระบุรหัส"
                  />
                  <datalist id="assetList">
                    <option v-for="asset in assets" :key="asset.id" :value="asset.asset_code">
                      {{ asset.name }} - {{ asset.brand }}
                    </option>
                  </datalist>
                </div>
                <div class="col-md-6">
                  <label class="form-label">รูปภาพประกอบ</label>
                  <input
                    type="file"
                    class="form-control"
                    @change="handleFileUpload"
                    accept="image/*"
                    :disabled="isEdit && !isAdmin"
                  />
                  <div
                    v-if="previewImage || form.image_path"
                    class="mt-2 text-center p-2 border rounded bg-light"
                  >
                    <img
                      :src="previewImage || getImageUrl(form.image_path)"
                      class="img-fluid"
                      style="max-height: 200px"
                    />
                  </div>
                </div>

                <!-- Technician Section (Only for Edit/Admin) -->
                <div v-if="isEdit && isAdmin" class="col-12 mt-4 bg-light p-3 rounded border">
                  <h6 class="border-bottom pb-2 text-primary fw-bold">สำหรับเจ้าหน้าที่ IT</h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">สถานะงาน</label>
                      <select v-model="form.status" class="form-select">
                        <option value="Pending">รอดำเนินการ</option>
                        <option value="In Progress">กำลังดำเนินการ</option>
                        <option value="Wait for Part">รออะไหล่</option>
                        <option value="Completed">ดำเนินการเสร็จสิ้น</option>
                        <option value="Cancelled">ยกเลิก</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">ผู้ดำเนินการ</label>
                      <select v-model="form.technician_name" class="form-select">
                        <option value="">-- ระบุชื่อเจ้าหน้าที่ --</option>
                        <option v-for="tech in technicians" :key="tech.ID" :value="tech.FULLNAME">
                          {{ tech.FULLNAME }}
                        </option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">บันทึกการแก้ไข / หมายเหตุ</label>
                      <textarea
                        v-model="form.technician_comment"
                        class="form-control"
                        rows="2"
                      ></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">วันที่ซ่อมเสร็จ</label>
                      <input
                        type="datetime-local"
                        v-model="form.completed_at"
                        class="form-control"
                      />
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer border-0 px-0 pb-0 mt-3 d-flex justify-content-between">
                <div class="d-flex gap-2">
                  <button
                    v-if="isEdit"
                    type="button"
                    class="btn btn-outline-danger rounded-pill px-4"
                    @click="deleteRequest"
                  >
                    <i class="bi bi-trash me-2"></i>ลบรายการ
                  </button>
                  <button type="button" class="btn btn-light rounded-pill px-4" @click="closeModal">
                    ยกเลิก
                  </button>
                </div>

                <button
                  type="submit"
                  class="btn btn-danger rounded-pill px-4"
                  :disabled="submitting"
                >
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span
                  >บันทึกข้อมูล
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
import { Modal } from 'bootstrap';

export default {
  name: 'ComputerRepair',
  data() {
    return {
      requests: [],
      assets: [],
      technicians: [], // Add this
      staffList: [], // Add this
      statusTabs: [
        { value: '', label: 'ทั้งหมด', dotClass: '' },
        { value: 'Pending', label: 'รอดำเนินการ', dotClass: 'bg-danger' },
        { value: 'In Progress', label: 'กำลังดำเนินการ', dotClass: 'bg-info' },
        { value: 'Wait for Part', label: 'รออะไหล่', dotClass: 'bg-warning' },
        { value: 'Completed', label: 'เสร็จสิ้น', dotClass: 'bg-success' },
        { value: 'Cancelled', label: 'ยกเลิก', dotClass: 'bg-secondary' }
      ],
      filters: {
        search: '',
        status: ''
      },
      bsModal: null,
      isEdit: false,
      submitting: false,
      form: {
        id: null,
        requester_name: '',
        department: '',
        contact_tel: '',
        location: '',
        issue_title: '',
        issue_description: '',
        asset_code: '',
        image_path: '',
        status: 'Pending',
        technician_name: '',
        technician_comment: '',
        completed_at: ''
      },
      selectedFile: null,
      previewImage: null,
      userProfile: {}
    };
  },
  computed: {
    isAdmin() {
      return (
        this.userProfile &&
        this.userProfile.department &&
        this.userProfile.department.includes('กลุ่มงานสุขภาพดิจิทัล')
      );
    }
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.repairModal);
    this.fetchAssets();
    this.fetchTechnicians();
    this.fetchStaffList();
    this.fetchUserProfile().then(() => {
      this.fetchRequests();

      // Check for asset_code in query
      if (this.$route.query && this.$route.query.asset_code) {
        this.openModal();
        this.form.asset_code = this.$route.query.asset_code;
      }
    });
  },
  methods: {
    async fetchAssets() {
      try {
        const res = await axios.get('/api-digital/asset/get_assets.php');
        if (res.data.status === 'success') {
          this.assets = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchTechnicians() {
      try {
        const res = await axios.get('/api-digital/repair/get_technicians.php');
        if (res.data.status === 'success') {
          this.technicians = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchStaffList() {
      try {
        const res = await axios.get('/api-hosoffice/get_all_staff.php');
        if (res.data.status === 'success') {
          this.staffList = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    handleStaffChange() {
      const selected = this.staffList.find(s => s.FULLNAME === this.form.requester_name);
      if (selected && selected.HR_DEPARTMENT_SUB_NAME) {
        this.form.department = selected.HR_DEPARTMENT_SUB_NAME;
      }
    },
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data.status === 'success') {
          this.userProfile = res.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchRequests() {
      try {
        const params = new URLSearchParams(this.filters);
        if (this.userProfile && this.userProfile.fullname) {
          params.append('requester', this.userProfile.fullname);
        }
        params.append('is_admin', this.isAdmin);

        const res = await axios.get(
          `/api-digital/repair/get_repair_requests.php?${params.toString()}`
        );
        if (res.data.status === 'success') {
          this.requests = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    openModal(req = null) {
      this.isEdit = !!req;
      this.selectedFile = null;
      this.previewImage = null;

      if (req) {
        this.form = { ...req };
        if (this.form.completed_at) {
          this.form.completed_at = this.form.completed_at.replace(' ', 'T').substring(0, 16);
        }
      } else {
        // Prepare new form with auto-filled user data
        this.form = {
          id: null,
          requester_name: this.userProfile.fullname || '',
          department: this.userProfile.department || '',
          contact_tel: '', // Could be fetched if available
          location: '',
          issue_title: '',
          issue_description: '',
          asset_code: '',
          image_path: '',
          status: 'Pending',
          technician_name: '',
          technician_comment: '',
          completed_at: ''
        };
      }
      this.bsModal.show();
    },
    closeModal() {
      this.bsModal.hide();
    },
    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
      if (this.selectedFile) {
        this.previewImage = URL.createObjectURL(this.selectedFile);
      } else {
        this.previewImage = null;
      }
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    async saveRequest() {
      this.submitting = true;
      try {
        const formData = new FormData();
        for (const key in this.form) {
          let val = this.form[key] !== null ? this.form[key] : '';
          if (key === 'completed_at' && val) {
            val = val.replace('T', ' ');
          }
          formData.append(key, val);
        }
        if (this.selectedFile) {
          formData.append('image', this.selectedFile);
        }

        const res = await axios.post('/api-digital/repair/save_repair_request.php', formData);

        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            showConfirmButton: false,
            timer: 1500
          });
          this.closeModal();
          this.fetchRequests();
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Connection Error', 'error');
        console.error(err);
      } finally {
        this.submitting = false;
      }
    },
    async deleteRequest() {
      if (!this.form.id) return;

      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบรายการแจ้งซ่อมนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบรายการ',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        try {
          const formData = new FormData();
          formData.append('id', this.form.id);

          const res = await axios.post('/api-digital/repair/delete_repair_request.php', formData);

          if (res.data.status === 'success') {
            await Swal.fire('ลบสำเร็จ!', 'รายการแจ้งซ่อมถูกลบเรียบร้อยแล้ว.', 'success');
            this.closeModal();
            this.fetchRequests();
          } else {
            Swal.fire('Error', res.data.message || 'เกิดข้อผิดพลาดในการลบ', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
        }
      }
    },
    setStatusFilter(status) {
      this.filters.status = status;
      this.fetchRequests();
    },
    getStatusClass(status) {
      switch (status) {
        case 'Completed':
          return 'bg-success-subtle text-success border border-success-subtle';
        case 'In Progress':
          return 'bg-info-subtle text-info-emphasis border border-info-subtle';
        case 'Wait for Part':
          return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        case 'Cancelled':
          return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
        default:
          return 'bg-danger-subtle text-danger border border-danger-subtle'; // Pending
      }
    },
    getStatusIcon(status) {
      switch (status) {
        case 'Completed':
          return 'bi-check-circle-fill';
        case 'In Progress':
          return 'bi-arrow-repeat';
        case 'Wait for Part':
          return 'bi-hourglass-split';
        case 'Cancelled':
          return 'bi-x-circle-fill';
        default:
          return 'bi-clock-fill';
      }
    },
    getStatusLabel(status) {
      switch (status) {
        case 'Completed':
          return 'เสร็จสิ้น';
        case 'In Progress':
          return 'กำลังดำเนินการ';
        case 'Wait for Part':
          return 'รออะไหล่';
        case 'Cancelled':
          return 'ยกเลิก';
        default:
          return 'รอดำเนินการ';
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = d.getFullYear() + 543;
      const time = dateStr.split(' ')[1] ? dateStr.split(' ')[1].substring(0, 5) : '';
      
      return `${day}/${month}/${year} ${time}`;
    }
  }
};
</script>

<style scoped>
.repair-page {
  background: radial-gradient(circle at top left, #fff5f5 0%, #f8fafc 45%, #f1f5f9 100%);
}

.main-card {
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  border: 1px solid #e2e8f0 !important;
}

.card-top-accent {
  height: 4px;
  background: linear-gradient(90deg, #dc2626 0%, #ef4444 50%, #f97316 100%);
}

.header-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.btn-add-repair {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
  border: none;
}

.filter-toolbar {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
}

.search-input-group .input-group-text,
.search-input-group .form-control,
.search-input-group .btn {
  border-color: #cbd5e1;
}

.search-input-group .input-group-text {
  border-top-left-radius: 999px;
  border-bottom-left-radius: 999px;
}

.search-input-group .form-control:last-child,
.search-input-group .btn:last-child {
  border-top-right-radius: 999px;
  border-bottom-right-radius: 999px;
}

.status-filter-pill {
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  font-size: 0.8rem;
  transition: all 0.15s ease;
}

.status-filter-pill:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.status-filter-pill.active {
  background-color: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
  font-weight: 600;
}

.status-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.repair-table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.75rem 0.85rem;
  white-space: nowrap;
}

.repair-table tbody td {
  padding: 0.75rem 0.85rem;
  border-color: #f1f5f9;
}

.ticket-badge {
  display: inline-block;
  background-color: #f1f5f9;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0.15rem 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.asset-chip {
  display: inline-flex;
  align-items: center;
  background-color: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  padding: 0.1rem 0.55rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.65rem;
  font-size: 0.76rem;
  font-weight: 600;
  white-space: nowrap;
}

.tech-chip {
  display: inline-flex;
  align-items: center;
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
  font-size: 0.8rem;
  color: #1e293b;
  max-width: 100%;
}

.modal-header-gradient {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
}
</style>
