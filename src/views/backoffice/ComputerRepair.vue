<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold m-0 text-danger">
              <i class="bi bi-tools me-2"></i>ระบบแจ้งซ่อมคอมพิวเตอร์
            </h5>
            <div class="d-flex gap-2">
              <button
                class="btn btn-outline-secondary rounded-pill px-3"
                @click="$router.push('/home-backoffice')"
              >
                <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
              </button>
              <button class="btn btn-danger rounded-pill px-4" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>แจ้งซ่อมใหม่
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-control rounded-pill"
                  placeholder="ค้นหา (เลขที่ใบแจ้ง, ชื่อผู้แจ้ง, อาการ...)"
                  @input="fetchRequests"
                />
              </div>
              <div class="col-md-3">
                <select
                  v-model="filters.status"
                  class="form-select rounded-pill"
                  @change="fetchRequests"
                >
                  <option value="">-- ทุกสถานะ --</option>
                  <option value="Pending">รอดำเนินการ (Pending)</option>
                  <option value="In Progress">กำลังดำเนินการ (In Progress)</option>
                  <option value="Wait for Part">รออะไหล่ (Wait for Part)</option>
                  <option value="Completed">เสร็จสิ้น (Completed)</option>
                  <option value="Cancelled">ยกเลิก (Cancelled)</option>
                </select>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="bg-light">
                  <tr>
                    <th width="12%">Ticket No / วันที่แจ้ง</th>
                    <th width="15%">ผู้แจ้ง / แผนก</th>
                    <th width="23%">ปัญหา / อาการ</th>
                    <th width="15%">สถานที่ / เบอร์โทร</th>
                    <th width="10%" class="text-center">สถานะ</th>
                    <th width="15%">ผู้ดำเนินการ</th>
                    <th width="10%">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="req in requests" :key="req.id">
                    <td>
                      <span class="badge bg-light text-dark border">{{ req.ticket_no }}</span>
                      <div class="small text-muted mt-1" v-if="req.created_at">
                        <i class="bi bi-calendar3 me-1"></i>{{ formatDate(req.created_at) }}
                      </div>
                    </td>
                    <td>
                      <div class="fw-bold">{{ req.requester_name }}</div>
                      <div class="small text-muted">{{ req.department }}</div>
                    </td>
                    <td>
                      <div class="fw-bold text-primary">{{ req.issue_title }}</div>
                      <div class="small text-muted text-truncate" style="max-width: 250px">
                        {{ req.issue_description }}
                      </div>
                      <div v-if="req.asset_code" class="small text-info">
                        <i class="bi bi-pc-display me-1"></i>{{ req.asset_code }}
                      </div>
                    </td>
                    <td>
                      <div><i class="bi bi-geo-alt me-1"></i>{{ req.location }}</div>
                      <div class="small text-muted">
                        <i class="bi bi-telephone me-1"></i>{{ req.contact_tel }}
                      </div>
                    </td>
                    <td class="text-center">
                      <span :class="['badge rounded-pill', getStatusClass(req.status)]">{{
                        req.status
                      }}</span>
                    </td>
                    <td>
                      <div v-if="req.technician_name">
                        <i class="bi bi-person-gear me-1"></i>{{ req.technician_name }}
                      </div>
                      <div v-else class="text-muted">-</div>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary" @click="openModal(req)">
                        <i class="bi bi-pencil"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="requests.length === 0">
                    <td colspan="7" class="text-center py-5 text-muted">ไม่พบรายการแจ้งซ่อม</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="repairModal" tabindex="-1" ref="repairModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-tools me-2"></i>{{ isEdit ? 'จัดการงานซ่อม' : 'แจ้งซ่อมคอมพิวเตอร์' }}
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
      // Remove leading slash if present to ensure relative path for both Vite (Proxy) and XAMPP
      return path.startsWith('/') ? path.substring(1) : path;
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
    getStatusClass(status) {
      switch (status) {
        case 'Completed':
          return 'bg-success';
        case 'In Progress':
          return 'bg-info text-dark';
        case 'Wait for Part':
          return 'bg-warning text-dark';
        case 'Cancelled':
          return 'bg-secondary';
        default:
          return 'bg-danger'; // Pending
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

<style scoped></style>
