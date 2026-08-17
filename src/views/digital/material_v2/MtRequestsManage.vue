<template>
  <div class="row g-4 mt-3">
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4"
    >
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
              <router-link to="/material-v2" class="text-decoration-none"
                >หน้าหลักวัสดุคอม</router-link
              >
            </li>
            <li class="breadcrumb-item active" aria-current="page">รายการขอเบิกวัสดุ</li>
          </ol>
        </nav>
        <h4 class="fw-bold mb-0 text-primary">รายการขอเบิกวัสดุ (Material Requests)</h4>
      </div>
      <div class="d-flex gap-2">
        <select v-model="statusFilter" class="form-select w-auto" @change="fetchRequests">
          <option value="all">ทั้งหมด (All)</option>
          <option value="pending">รอดำเนินการ (Pending)</option>
          <option value="approved">อนุมัติแล้ว (Approved)</option>
          <option value="rejected">ปฏิเสธ (Rejected)</option>
        </select>
        <router-link
          to="/material-v2/settings"
          class="btn btn-outline-primary fw-bold"
          title="ตั้งค่าการพิมพ์"
        >
          <i class="bi bi-gear-fill me-1"></i> ตั้งค่าพิมพ์ใบเบิก
        </router-link>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="bg-primary bg-opacity-10">
                <tr>
                  <th class="ps-4 fw-semibold text-primary">
                    <i class="bi bi-calendar-date me-1"></i> วันที่ขอเบิก
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-person me-1"></i> ผู้ขอเบิก
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-building me-1"></i> หน่วยงาน
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-box-seam me-1"></i> รายการวัสดุ
                  </th>
                  <th class="text-center fw-semibold text-primary">
                    <i class="bi bi-123 me-1"></i> จำนวน
                  </th>
                  <th class="text-center fw-semibold text-primary">
                    <i class="bi bi-info-circle me-1"></i> สถานะ
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-chat-text me-1"></i> หมายเหตุ
                  </th>
                  <th class="text-end pe-4 fw-semibold text-primary">
                    <i class="bi bi-wrench me-1"></i> จัดการ
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="requests.length === 0">
                  <td colspan="8" class="text-center py-5">
                    <div class="empty-state">
                      <i class="bi bi-inbox fs-1 text-muted opacity-50 mb-3 d-block"></i>
                      <h5 class="text-muted mb-0">ไม่มีข้อมูลคำขอเบิก</h5>
                      <p class="text-secondary small mt-2">
                        ยังไม่มีรายการเบิกวัสดุในสถานะที่คุณเลือก
                      </p>
                    </div>
                  </td>
                </tr>
                <tr v-for="req in requests" :key="req.id">
                  <td class="ps-4">{{ req.request_date }}</td>
                  <td>{{ req.requester_name }}</td>
                  <td>{{ req.department }}</td>
                  <td>
                    <div v-for="item in req.items" :key="item.id" class="mb-1 pb-1 border-bottom border-light">
                      <span class="fw-bold">{{ item.material_name }}</span><br />
                      <small class="text-muted">{{ item.material_code }}</small>
                    </div>
                  </td>
                  <td class="text-center fw-bold text-dark fs-6">
                    <div v-for="item in req.items" :key="item.id" class="mb-1 pb-1 border-bottom border-light" style="min-height: 42px; display: flex; align-items: center; justify-content: center;">
                      {{ item.quantity }}
                    </div>
                  </td>
                  <td class="text-center">
                    <span
                      class="badge rounded-pill px-3 py-2 fw-normal"
                      :class="getStatusBadge(req.status)"
                      style="font-size: 0.85rem"
                    >
                      <i :class="getStatusIcon(req.status)" class="me-1"></i>
                      {{ getStatusText(req.status) }}
                    </span>
                  </td>
                  <td>{{ req.admin_note || '-' }}</td>
                  <td class="text-end pe-4">
                    <div class="d-flex gap-2 justify-content-end align-items-center h-100 flex-wrap" style="max-width: 150px">
                      <template v-if="req.status === 'pending'">
                        <button
                          class="btn btn-sm btn-success flex-fill"
                          @click="approveRequest(req)"
                          title="อนุมัติจ่ายของ"
                        >
                          <i class="bi bi-check-circle"></i> อนุมัติ
                        </button>
                        <button
                          class="btn btn-sm btn-warning flex-fill text-dark"
                          @click="openEditModal(req)"
                          title="แก้ไข"
                        >
                          <i class="bi bi-pencil-square"></i> แก้ไข
                        </button>
                        <button
                          class="btn btn-sm btn-danger flex-fill"
                          @click="rejectRequest(req)"
                          title="ปฏิเสธ"
                        >
                          <i class="bi bi-x-circle"></i> ปฏิเสธ
                        </button>
                      </template>
                      <template v-else-if="req.status === 'approved'">
                        <button
                          class="btn btn-sm btn-danger text-white flex-fill"
                          @click="exportPDF(req)"
                          title="Export PDF"
                        >
                          <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
                        </button>
                      </template>
                      <button
                        class="btn btn-sm btn-outline-danger flex-fill"
                        @click="deleteRequest(req)"
                        title="ลบข้อมูล"
                      >
                        <i class="bi bi-trash"></i> ลบ
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-warning bg-opacity-10 border-bottom-0 pb-0">
            <h5 class="modal-title text-warning-emphasis fw-bold">
              <i class="bi bi-pencil-square me-2"></i>แก้ไขรายการเบิกวัสดุ
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 pt-3">
            <form @submit.prevent="saveEdit">
              <!-- ข้อมูลผู้เบิก -->
              <div class="card border border-light shadow-sm mb-4">
                <div class="card-body">
                  <h6 class="card-title text-muted mb-3 fw-bold">
                    <i class="bi bi-person-badge me-1"></i> ข้อมูลผู้เบิก
                  </h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">ชื่อ-สกุลผู้เบิก <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control bg-light"
                        v-model="editData.requester_name"
                        required
                        list="requesterOptionsEdit"
                      />
                      <datalist id="requesterOptionsEdit">
                        <option v-for="(name, index) in pastRequesters" :key="index" :value="name"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">หน่วยงาน <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control bg-light"
                        v-model="editData.department"
                        required
                        list="deptOptionsEdit"
                      />
                      <datalist id="deptOptionsEdit">
                        <option v-for="(dept, index) in pastDepartments" :key="index" :value="dept"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">วันที่ขอเบิก <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        class="form-control bg-light"
                        v-model="editData.request_date"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- รายการวัสดุ -->
              <div class="card border border-light shadow-sm mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title text-muted mb-0 fw-bold">
                      <i class="bi bi-box-seam me-1"></i> รายการวัสดุที่ต้องการเบิก
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addEditItem">
                      <i class="bi bi-plus-lg"></i> เพิ่มรายการ
                    </button>
                  </div>

                  <div v-if="editData.items.length === 0" class="text-center p-3 bg-light rounded text-muted">
                    กรุณาเพิ่มรายการวัสดุ
                  </div>

                  <div 
                    v-for="(item, index) in editData.items" 
                    :key="index" 
                    class="row g-2 align-items-end mb-3 p-3 bg-light rounded position-relative"
                  >
                    <button 
                      type="button" 
                      class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-2 me-2 rounded-circle border-0" 
                      style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                      @click="removeEditItem(index)"
                      title="ลบรายการนี้"
                      v-if="editData.items.length > 1"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>

                    <div class="col-md-7">
                      <label class="form-label text-secondary small mb-1">เลือกวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="item.material_id" required>
                        <option value="" disabled>-- เลือกวัสดุ --</option>
                        <option
                          v-for="mat in materials"
                          :key="mat.id"
                          :value="mat.id"
                          :disabled="mat.balance <= 0"
                        >
                          {{ mat.name }} ({{ mat.code }}) - คงเหลือ: {{ mat.balance }} {{ mat.unit || 'ชิ้น' }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-secondary small mb-1">จำนวน <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" @click="item.quantity > 1 ? item.quantity-- : null">-</button>
                        <input
                          type="number"
                          class="form-control text-center px-1"
                          v-model.number="item.quantity"
                          min="1"
                          required
                        />
                        <button type="button" class="btn btn-outline-secondary" @click="item.quantity++">+</button>
                      </div>
                    </div>
                    <div class="col-md-2 d-none d-md-block text-end pb-1">
                      <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-2 py-1" v-if="item.material_id">
                        เหลือ {{ getMaterialBalance(item.material_id) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submit -->
              <div class="text-end">
                <button
                  type="button"
                  class="btn btn-light me-2 fw-bold"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm" :disabled="!isEditFormValid">
                  <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
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
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtRequestsManage',
  data() {
    return {
      requests: [],
      statusFilter: 'all', // Default to all
      materials: [],
      pastRequesters: [],
      pastDepartments: [],
      editData: {
        request_no: null,
        group_id: null,
        requester_name: '',
        department: '',
        request_date: '',
        items: []
      },
      editModalInstance: null,
      editData: {
        request_no: null,
        group_id: null,
        requester_name: '',
        department: '',
        request_date: '',
        items: []
      },
      editModalInstance: null
    };
  },
  mounted() {
    this.fetchRequests();
    this.fetchMaterials();
    this.fetchRequestersAndDepts();
  },
  methods: {
    async fetchRequests() {
      try {
        const res = await axios.get(
          `/api-digital/material_v2/get_requests.php?status=${this.statusFilter}`
        );
        if (res.data.success) {
          this.requests = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching requests', error);
      }
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_materials.php');
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching materials:', error);
      }
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
    getMaterialBalance(materialId) {
      if (!materialId) return 0;
      const mat = this.materials.find(m => m.id === materialId);
      return mat ? mat.balance : 0;
    },
    openEditModal(req) {
      // Clone data to avoid live binding edits
      this.editData = {
        request_no: req.request_no,
        group_id: req.group_id,
        requester_name: req.requester_name,
        department: req.department,
        request_date: req.request_date,
        items: req.items.map(i => ({
          material_id: i.material_id,
          quantity: i.quantity
        }))
      };
      
      // Initialize if empty
      if (this.editData.items.length === 0) {
        this.addEditItem();
      }

      if (!this.editModalInstance) {
        this.editModalInstance = new bootstrap.Modal(
          document.getElementById('editRequestModal')
        );
      }
      this.editModalInstance.show();
    },
    addEditItem() {
      this.editData.items.push({ material_id: '', quantity: 1 });
    },
    removeEditItem(index) {
      if (this.editData.items.length > 1) {
        this.editData.items.splice(index, 1);
      }
    },
    async saveEdit() {
      try {
        const payload = {
          request_no: this.editData.request_no,
          group_id: this.editData.group_id,
          requester_name: this.editData.requester_name,
          department: this.editData.department,
          request_date: this.editData.request_date,
          items: this.editData.items
        };

        const res = await axios.post('/api-digital/material_v2/edit_request.php', payload);
        
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: res.data.message || 'แก้ไขข้อมูลเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
          });
          this.editModalInstance.hide();
          this.fetchRequests();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถแก้ไขข้อมูลได้', 'error');
        }
      } catch (error) {
        console.error('Error saving edit', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      }
    },
    async approveRequest(req) {
      // Validate items
      let hasError = false;
      let errorMsg = '';
      for (const item of req.items) {
        if (item.quantity > item.current_balance) {
          hasError = true;
          errorMsg = `ยอดคงเหลือไม่พอสำหรับ ${item.material_name} (เหลือ ${item.current_balance}) เบิก ${item.quantity}`;
          break;
        }
      }

      if (hasError) {
        Swal.fire('ข้อผิดพลาด', errorMsg, 'error');
        return;
      }

      const confirm = await Swal.fire({
        title: 'ยืนยันการอนุมัติจ่ายวัสดุ?',
        text: `คุณต้องการจ่ายวัสดุจำนวน ${req.items.length} รายการ ให้แก่ ${req.requester_name} ใช่หรือไม่? (สต๊อกจะถูกตัดทันที)`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'ยืนยันอนุมัติจ่าย'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/material_v2/approve_request.php', {
            request_no: req.group_id,
            admin_note: 'อนุมัติผ่านระบบ'
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'อนุมัติและจ่ายของสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchRequests();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Failed to approve request', 'error');
        }
      }
    },
    async rejectRequest(req) {
      const { value: note } = await Swal.fire({
        title: 'เหตุผลที่ปฏิเสธ',
        input: 'textarea',
        inputLabel: 'ระบุเหตุผลการไม่อนุมัติให้เบิก',
        inputPlaceholder: 'เช่น ของหมดชั่วคราว...',
        showCancelButton: true
      });

      if (note !== undefined) {
        try {
          const res = await axios.post('/api-digital/material_v2/reject_request.php', {
            request_no: req.group_id,
            admin_note: note
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'บันทึกการปฏิเสธสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchRequests();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Failed to reject request', 'error');
        }
      }
    },
    async deleteRequest(req) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบข้อมูล?',
        text:
          req.status === 'approved'
            ? 'คำขอนี้อนุมัติไปแล้ว การลบจะคืนยอดเข้าสต๊อกด้วย ยืนยันหรือไม่คะ?'
            : 'คุณต้องการลบข้อมูลคำขอเบิกนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/material_v2/delete_request.php', {
            request_no: req.group_id
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'ลบข้อมูลสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchRequests();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Failed to delete request', 'error');
        }
      }
    },
    async fetchFont(url) {
      return fetch(url)
        .then((res) => res.arrayBuffer())
        .then((buffer) => {
          let binary = '';
          const bytes = new Uint8Array(buffer);
          const len = bytes.byteLength;
          for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
          }
          return window.btoa(binary);
        });
    },
    async exportPDF(req) {
      Swal.fire({
        title: 'กำลังสร้างไฟล์ PDF...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const [reqRes, setRes] = await Promise.all([
          axios.get(`/api-digital/material_v2/get_request_by_id.php?request_no=${req.group_id}`),
          axios.get('/api-digital/material_v2/get_print_settings.php')
        ]);

        if (!reqRes.data.success) throw new Error('ไม่สามารถโหลดข้อมูลคำขอเบิกได้');

        const requestData = reqRes.data.data;
        const globalSettings = setRes.data.success ? setRes.data.global || {} : {};
        const departmentsConfig = setRes.data.success ? setRes.data.departments || [] : [];
        const matchedConfig =
          departmentsConfig.find((d) => d.department_name === requestData.department) || {};

        const doc = new jsPDF();

        const baseUrl = import.meta.env.BASE_URL.endsWith('/')
          ? import.meta.env.BASE_URL
          : import.meta.env.BASE_URL + '/';
        const fontNormal = await this.fetchFont(`${baseUrl}fonts/THSarabun.ttf`);
        const fontBold = await this.fetchFont(`${baseUrl}fonts/THSarabun Bold.ttf`);

        doc.addFileToVFS('THSarabun.ttf', fontNormal);
        doc.addFont('THSarabun.ttf', 'Sarabun', 'normal');
        doc.addFileToVFS('THSarabunBold.ttf', fontBold);
        doc.addFont('THSarabunBold.ttf', 'Sarabun', 'bold');

        doc.setFont('Sarabun', 'normal');

        doc.setFont('Sarabun', 'bold');
        doc.setFontSize(22);
        doc.text('ใบเบิกวัสดุ', 105, 20, { align: 'center' });

        doc.setFontSize(16);
        doc.text(
          `ใบเบิกที่: ${new Date().getFullYear() + 543 + String(requestData.id).padStart(4, '0')}`,
          20,
          35
        );

        const dDate = new Date(requestData.request_date || new Date());
        const months = [
          'มกราคม',
          'กุมภาพันธ์',
          'มีนาคม',
          'เมษายน',
          'พฤษภาคม',
          'มิถุนายน',
          'กรกฎาคม',
          'สิงหาคม',
          'กันยายน',
          'ตุลาคม',
          'พฤศจิกายน',
          'ธันวาคม'
        ];
        doc.text(
          `วันที่ ${dDate.getDate()} เดือน ${months[dDate.getMonth()]} พ.ศ. ${dDate.getFullYear() + 543}`,
          190,
          35,
          { align: 'right' }
        );

        doc.text('เรื่อง', 20, 45);
        doc.setFont('Sarabun', 'normal');
        doc.text('ขอเบิกวัสดุสำนักงาน', 35, 45);

        doc.setFont('Sarabun', 'bold');
        doc.text('เรียน', 20, 53);
        doc.setFont('Sarabun', 'normal');
        doc.text('เจ้าหน้าที่พัสดุ', 35, 53);

        doc.text(
          `ข้าพเจ้า  .........................................................................  ตำแหน่ง  ...................................................................................`,
          30,
          65
        );
        doc.text(requestData.requester_name || '', 44, 64);
        doc.text(requestData.position_name2 || '', 128, 64);

        doc.text(
          `กลุ่มงาน  .....................................................................................................`,
          30,
          75
        );
        doc.text(requestData.department || '', 45, 74);

        doc.text('ขอเบิกวัสดุสำนักงานเพื่อใช้ใน สำนักงาน ดังต่อไปนี้', 30, 85);

        const tableBody = requestData.items.map((item, index) => [
          (index + 1).toString(),
          item.material_name + (item.material_code ? ` (${item.material_code})` : ''),
          item.material_unit || 'ชิ้น',
          item.quantity,
          item.status === 'approved' ? item.quantity : '-',
          item.current_balance
        ]);

        autoTable(doc, {
          startY: 92,
          head: [['ลำดับที่', 'รายการ', 'หน่วยนับ', 'จำนวนเบิก', 'อนุมัติ', 'คงเหลือในบัญชีคุม']],
          body: tableBody,
          styles: {
            font: 'Sarabun',
            fontStyle: 'normal',
            fontSize: 14,
            halign: 'center',
            valign: 'middle',
            lineColor: [0, 0, 0],
            lineWidth: 0.1
          },
          headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            font: 'Sarabun',
            fontStyle: 'bold'
          },
          columnStyles: {
            0: { cellWidth: 20 },
            1: { halign: 'left', cellWidth: 'auto' },
            2: { cellWidth: 25 },
            3: { cellWidth: 25 },
            4: { cellWidth: 25 },
            5: { cellWidth: 35 }
          },
          theme: 'grid',
          margin: { left: 20, right: 20 }
        });

        let finalY = (doc.lastAutoTable || doc.previousAutoTable).finalY + 20;

        const reqName =
          matchedConfig.requester_name ||
          requestData.requester_name ||
          '...........................................';
        const reqPos =
          matchedConfig.requester_position ||
          '.....................................................';

        const payerName =
          globalSettings.payer_name || '...........................................';
        const payerPos =
          globalSettings.payer_position || '.....................................................';

        const appName =
          globalSettings.approver_name || '...........................................';
        const appPos =
          globalSettings.approver_position ||
          '.....................................................';

        if (finalY > 230) {
          doc.addPage();
          finalY = 30;
        }

        doc.text('ผู้ขอใช้พัสดุ...........................................ลงชื่อ', 60, finalY, {
          align: 'center'
        });
        doc.text(`( ${reqName} )`, 60, finalY + 10, { align: 'center' });
        doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 18, { align: 'center' });

        doc.text('ผู้จ่าย...........................................ลงชื่อ', 150, finalY, {
          align: 'center'
        });
        doc.text(`( ${payerName} )`, 150, finalY + 10, { align: 'center' });
        doc.text(`ตำแหน่ง ${payerPos}`, 150, finalY + 18, { align: 'center' });

        doc.text('ผู้เบิก...........................................ลงชื่อ', 60, finalY + 35, {
          align: 'center'
        });
        doc.text(`( ${reqName} )`, 60, finalY + 45, { align: 'center' });
        doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 53, { align: 'center' });

        doc.text('ผู้สั่งจ่าย...........................................ลงชื่อ', 150, finalY + 35, {
          align: 'center'
        });
        doc.text(`( ${appName} )`, 150, finalY + 45, { align: 'center' });
        doc.text(`ตำแหน่ง ${appPos}`, 150, finalY + 53, { align: 'center' });

        doc.text('ผู้รับพัสดุ...........................................ลงชื่อ', 60, finalY + 70, {
          align: 'center'
        });
        doc.text(`( ${requestData.requester_name} )`, 60, finalY + 80, { align: 'center' });
        doc.text(
          `ตำแหน่ง ${requestData.position_name2 || '.....................................................'}`,
          60,
          finalY + 88,
          { align: 'center' }
        );

        doc.save(`material-request-${requestData.request_no || requestData.id}.pdf`);
        Swal.close();
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
      }
    }
  },
  computed: {
    isEditFormValid() {
      if (!this.editData.requester_name || !this.editData.department || !this.editData.request_date) {
        return false;
      }
      if (this.editData.items.length === 0) return false;
      for (const item of this.editData.items) {
        if (!item.material_id || !item.quantity || item.quantity < 1) {
          return false;
        }
      }
      return true;
    }
  },
  computed: {
    isEditFormValid() {
      if (!this.editData.requester_name || !this.editData.department || !this.editData.request_date) {
        return false;
      }
      if (this.editData.items.length === 0) return false;
      for (const item of this.editData.items) {
        if (!item.material_id || !item.quantity || item.quantity < 1) {
          return false;
        }
      }
      return true;
    }
  }
};
</script>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.5rem;
}
</style>
