<template>
  <div class="row g-4 mt-3">
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4"
    >
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
              <router-link to="/material-admin" class="text-decoration-none"
                >หน้าหลักวัสดุ</router-link
              >
            </li>
            <li class="breadcrumb-item active" aria-current="page">รายการขอเบิกวัสดุ</li>
          </ol>
        </nav>
        <h4 class="fw-bold mb-0 text-primary">รายการขอเบิกวัสดุ (Material Requests)</h4>
      </div>
      <div class="d-flex gap-2">
        <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
          <i class="bi bi-house-door me-1"></i> กลับหน้าหลัก
        </router-link>
        <select v-model="statusFilter" class="form-select w-auto" @change="fetchRequests">
          <option value="all">ทั้งหมด (All)</option>
          <option value="pending">รอดำเนินการ (Pending)</option>
          <option value="approved">อนุมัติแล้ว (Approved)</option>
          <option value="rejected">ปฏิเสธ (Rejected)</option>
        </select>
        <router-link
          to="/material-admin/settings"
          class="btn btn-outline-success fw-bold"
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
              <thead class="bg-success bg-opacity-10">
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
                <tr v-for="req in requests" :key="req.request_no || req.id">
                  <td class="ps-4">{{ req.request_date }}</td>
                  <td>{{ req.requester_name }}</td>
                  <td>{{ req.department }}</td>
                  <td>
                    <div
                      v-for="(item, i) in req.items"
                      :key="i"
                      class="mb-2 pb-2 border-bottom last-border-0"
                    >
                      <span class="fw-bold">{{ item.material_name }}</span
                      ><br />
                      <small class="text-muted">{{ item.material_code }}</small>
                    </div>
                  </td>
                  <td class="text-center">
                    <div
                      v-for="(item, i) in req.items"
                      :key="i"
                      class="mb-2 pb-2 fw-bold text-dark fs-5 last-border-0"
                    >
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
                  <td>{{ req.items[0]?.admin_note || '-' }}</td>
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
                          class="btn btn-sm btn-danger"
                          @click="rejectRequest(req)"
                          title="ปฏิเสธ"
                        >
                          <i class="bi bi-x-circle"></i> ปฏิเสธ
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
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Modal -->
    <div
      class="modal fade"
      id="approveModal"
      tabindex="-1"
      aria-labelledby="approveModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success bg-opacity-10">
            <h5
              class="modal-title font-weight-bold tracking-tight text-success"
              id="approveModalLabel"
            >
              <i class="bi bi-check-circle me-2"></i>ยืนยันการอนุมัติจ่ายวัสดุ
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
              @click="closeApproveModal"
            ></button>
          </div>
          <div class="modal-body" v-if="selectedRequest">
            <div class="mb-3 p-3 bg-light rounded-3">
              <strong>ผู้ขอเบิก:</strong> {{ selectedRequest.requester_name }}<br />
              <strong>หน่วยงาน:</strong> {{ selectedRequest.department }}<br />
              <strong>เลขที่คำร้อง:</strong>
              <span class="badge bg-secondary">{{ selectedRequest.request_no || '-' }}</span>
            </div>

            <div
              v-for="(item, index) in selectedRequest.items"
              :key="index"
              class="card mb-3 border border-success border-opacity-25 shadow-none pb-0"
            >
              <div class="card-body py-2">
                <div class="row align-items-center">
                  <div class="col-12">
                    <p class="mb-1 fw-bold text-success">
                      {{ index + 1 }}. {{ item.material_name }}
                      <small class="text-muted">({{ item.material_code }})</small>
                    </p>
                  </div>
                  <div class="col-4">
                    <label class="form-label text-muted x-small mb-0">ขอเบิก</label>
                    <div class="fw-bold">{{ item.quantity }}</div>
                  </div>
                  <div class="col-4 border-start">
                    <label class="form-label text-muted x-small mb-0">คงเหลือ</label>
                    <div :class="item.current_balance < 1 ? 'text-danger' : ''">
                      {{ item.current_balance }}
                    </div>
                  </div>
                  <div class="col-4 border-start">
                    <label class="form-label text-success-emphasis small mb-1 fw-bold"
                      >ระบุจำนวนที่อนุมัติ</label
                    >
                    <input
                      type="number"
                      class="form-control form-control-sm"
                      v-model.number="item.approved_quantity"
                      min="1"
                      :max="Math.min(item.quantity, item.current_balance)"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-0 mt-3 pt-3 border-top">
              <label class="form-label font-weight-bold">หมายเหตุการอนุมัติ (รวม)</label>
              <textarea
                class="form-control form-control-sm"
                v-model="adminNote"
                rows="2"
                placeholder="เช่น อนุมัติผ่านระบบ..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
              @click="closeApproveModal"
            >
              ยกเลิก
            </button>
            <button
              type="button"
              class="btn btn-success"
              @click="confirmApproval"
              :disabled="!isApprovalValid"
            >
              <i class="bi bi-check2-all me-1"></i> ยืนยันอนุมัติจ่าย
            </button>
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
import { Modal } from 'bootstrap';

export default {
  name: 'MtRequestsManage',
  data() {
    return {
      requests: [],
      statusFilter: 'all', // Default to all
      selectedRequest: null,
      approvedQuantity: null,
      adminNote: '',
      approveModalInstance: null
    };
  },
  computed: {
    isApprovalValid() {
      if (!this.selectedRequest || !this.selectedRequest.items) return false;
      return this.selectedRequest.items.every((item) => {
        const qty = item.approved_quantity;
        const maxVal = Math.min(item.quantity, item.current_balance);
        return qty > 0 && qty <= maxVal;
      });
    }
  },
  mounted() {
    this.fetchRequests();
    // Initialize Bootstrap modal instance
    const modalEl = document.getElementById('approveModal');
    if (modalEl) {
      this.approveModalInstance = new Modal(modalEl);
    }
  },
  methods: {
    async fetchRequests() {
      try {
        const res = await axios.get(
          `/api-digital/material_admin/get_requests.php?status=${this.statusFilter}`
        );
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
    async approveRequest(req) {
      const hasStockOut = req.items.some((it) => it.current_balance <= 0);
      if (hasStockOut) {
        Swal.fire('ข้อควรระวัง', 'มีพัสดุบางรายการหมดสต๊อก', 'warning');
      }

      this.selectedRequest = { ...req };
      // Pre-fill approved quantities
      this.selectedRequest.items = this.selectedRequest.items.map((it) => ({
        ...it,
        approved_quantity: Math.min(it.quantity, it.current_balance) || 0
      }));

      this.adminNote = 'อนุมัติผ่านระบบ';

      if (!this.approveModalInstance) {
        const modalEl = document.getElementById('approveModal');
        if (modalEl) {
          this.approveModalInstance = new Modal(modalEl);
        }
      }

      if (this.approveModalInstance) {
        this.approveModalInstance.show();
      } else {
        // Fallback or error logging if Bootstrap isn't loaded
        console.error('Bootstrap modal initialization failed');
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเปิดหน้าต่างอนุมัติได้ (Bootstrap Modal Error)', 'error');
      }
    },
    closeApproveModal() {
      if (this.approveModalInstance) {
        this.approveModalInstance.hide();
      }
      setTimeout(() => {
        this.selectedRequest = null;
        this.approvedQuantity = null;
        this.adminNote = '';
      }, 300); // Wait for transition
    },
    async confirmApproval() {
      if (!this.isApprovalValid) return;

      const btn = document.activeElement;
      if (btn) btn.disabled = true;

      const payload = {
        approvals: this.selectedRequest.items.map((it) => ({
          id: it.id,
          approved_quantity: it.approved_quantity,
          admin_note: this.adminNote
        }))
      };

      console.log('Sending approval payload:', payload);

      try {
        const res = await axios.post('/api-digital/material_admin/approve_request.php', payload);

        if (res.data.success) {
          this.closeApproveModal();
          Swal.fire({
            icon: 'success',
            title: 'อนุมัติและจ่ายของสำเร็จ',
            showConfirmButton: false,
            timer: 1500
          });
          this.fetchRequests();
        } else {
          Swal.fire('ข้อผิดพลาดจากระบบ', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Failed to approve request', 'error');
      } finally {
        if (btn) btn.disabled = false;
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
          const res = await axios.post('/api-digital/material_admin/reject_request.php', {
            id: req.items[0]?.id, // Backend will use this to find request_no
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
        const reqId = req.items && req.items.length > 0 ? req.items[0].id : req.id;
        if (!reqId) throw new Error('ไม่พบรหัสคำขอเบิก');

        const [reqRes, setRes] = await Promise.all([
          axios.get(`/api-digital/material_admin/get_request_by_id.php?id=${reqId}`),
          axios.get('/api-digital/material_admin/get_print_settings.php')
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
          body: requestData.items.map((it, idx) => [
            idx + 1,
            it.material_name + (it.material_code ? ` (${it.material_code})` : ''),
            it.material_unit || 'ชิ้น',
            it.quantity,
            it.status === 'approved' ? it.quantity : '-',
            it.current_balance
          ]),
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
