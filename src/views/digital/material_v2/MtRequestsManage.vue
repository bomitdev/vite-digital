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
                    <span class="fw-bold">{{ req.material_name }}</span
                    ><br />
                    <small class="text-muted">{{ req.material_code }}</small>
                  </td>
                  <td class="text-center fw-bold text-dark fs-5">{{ req.quantity }}</td>
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
                    <div class="d-flex gap-2 justify-content-end">
                      <template v-if="req.status === 'pending'">
                        <button
                          class="btn btn-sm btn-success"
                          @click="approveRequest(req)"
                          title="อนุมัติจ่ายของ"
                        >
                          <i class="bi bi-check-circle"></i> อนุมัติ
                        </button>
                        <button
                          class="btn btn-sm btn-secondary"
                          @click="openEditModal(req)"
                          title="แก้ไข"
                        >
                          <i class="bi bi-pencil-square"></i> แก้ไข
                        </button>
                        <button
                          class="btn btn-sm btn-danger"
                          @click="rejectRequest(req)"
                          title="ปฏิเสธ"
                        >
                          <i class="bi bi-x-circle"></i> ปฏิเสธ
                        </button>
                      </template>
                      <template v-else-if="req.status === 'rejected'">
                        <button
                          class="btn btn-sm btn-secondary"
                          @click="openEditModal(req)"
                          title="แก้ไข"
                        >
                          <i class="bi bi-pencil-square"></i> แก้ไข
                        </button>
                      </template>
                      <template v-else-if="req.status === 'approved'">
                        <button
                          class="btn btn-sm btn-danger text-white"
                          @click="exportPDF(req)"
                          title="Export PDF"
                        >
                          <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
                        </button>
                      </template>
                      <button
                        class="btn btn-sm btn-outline-danger"
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
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-light border-bottom-0 rounded-top-4">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-pencil-square me-2"></i>แก้ไขรายการขอเบิก
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitEditRequest">
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >ชื่อผู้เบิก <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  v-model="editForm.requester_name"
                  class="form-control"
                  required
                  placeholder="ระบุชื่อ-นามสกุล"
                  list="edit_requester_list"
                />
                <datalist id="edit_requester_list">
                  <option
                    v-for="(name, index) in pastRequesters"
                    :key="index"
                    :value="name"
                  ></option>
                </datalist>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >หน่วยงาน/แผนก <span class="text-danger">*</span></label
                >
                <input
                  type="text"
                  v-model="editForm.department"
                  class="form-control"
                  required
                  placeholder="ระบุหน่วยงาน"
                  list="edit_department_list"
                />
                <datalist id="edit_department_list">
                  <option
                    v-for="(dept, index) in pastDepartments"
                    :key="index"
                    :value="dept"
                  ></option>
                </datalist>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold"
                  >วัสดุที่ต้องการเบิก <span class="text-danger">*</span></label
                >
                <select v-model="editForm.material_id" class="form-select" required>
                  <option value="" disabled>-- เลือกวัสดุ --</option>
                  <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                    {{ mat.code }} - {{ mat.name }} (คงเหลือ: {{ mat.balance }} {{ mat.unit }})
                  </option>
                </select>
              </div>
              <div class="mb-4">
                <label class="form-label fw-bold">จำนวน <span class="text-danger">*</span></label>
                <input
                  type="number"
                  v-model.number="editForm.quantity"
                  class="form-control"
                  min="1"
                  required
                />
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="submittingEdit">
                  {{ submittingEdit ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
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
      editForm: {
        id: null,
        requester_name: '',
        department: '',
        material_id: '',
        quantity: 1
      },
      editModalInstance: null,
      submittingEdit: false
    };
  },
  mounted() {
    this.fetchRequests();
    this.fetchMaterials();
    this.fetchRequestersAndDepts();

    // Initialize standard modal instance
    const modalEl = document.getElementById('editRequestModal');
    if (modalEl) {
      this.editModalInstance = new bootstrap.Modal(modalEl);
    }
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
    async approveRequest(req) {
      if (req.quantity > req.current_balance) {
        Swal.fire(
          'ข้อผิดพลาด',
          `ยอดคงเหลือไม่พอ (เหลือ ${req.current_balance}) เบิก ${req.quantity}`,
          'error'
        );
        return;
      }

      const confirm = await Swal.fire({
        title: 'ยืนยันการอนุมัติจ่ายวัสดุ?',
        text: `คุณต้องการจ่าย ${req.material_name} จำนวน ${req.quantity} ชิ้น ให้แก่ ${req.requester_name} ใช่หรือไม่? (สต๊อกจะถูกตัดทันที)`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'ยืนยันอนุมัติจ่าย'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/material_v2/approve_request.php', {
            id: req.id,
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
            id: req.id,
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
    openEditModal(req) {
      this.editForm = {
        id: req.id,
        requester_name: req.requester_name,
        department: req.department,
        material_id: req.material_id, // make sure material_id exists in req... Wait, does req contain material_id?
        quantity: req.quantity
      };

      // If req doesn't have material_id (maybe it only has material_code?), we need to find it by code or name
      if (!req.material_id) {
        const mat =
          this.materials.find(
            (m) => m.code === req.material_code && m.name === req.material_name
          ) || this.materials.find((m) => m.name === req.material_name);
        if (mat) {
          this.editForm.material_id = mat.id;
        }
      }

      if (this.editModalInstance) {
        this.editModalInstance.show();
      }
    },
    async submitEditRequest() {
      this.submittingEdit = true;
      try {
        const res = await axios.post('/api-digital/material_v2/edit_request.php', this.editForm);
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'อัปเดตข้อมูลสำเร็จ',
            showConfirmButton: false,
            timer: 1500
          });
          if (this.editModalInstance) {
            this.editModalInstance.hide();
          }
          this.fetchRequests();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอัปเดตข้อมูลได้', 'error');
      } finally {
        this.submittingEdit = false;
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
            id: req.id
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
          axios.get(`/api-digital/material_v2/get_request_by_id.php?id=${req.id}`),
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

        autoTable(doc, {
          startY: 92,
          head: [['ลำดับที่', 'รายการ', 'หน่วยนับ', 'จำนวนเบิก', 'อนุมัติ', 'คงเหลือในบัญชีคุม']],
          body: [
            [
              '1',
              requestData.material_name +
                (requestData.material_code ? ` (${requestData.material_code})` : ''),
              requestData.material_unit || 'ชิ้น',
              requestData.quantity,
              requestData.status === 'approved' ? requestData.quantity : '-',
              requestData.current_balance
            ]
          ],
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

        doc.save(`material-request-${requestData.id}.pdf`);
        Swal.close();
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
      }
    }
  }
};
</script>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.5rem;
}
</style>
