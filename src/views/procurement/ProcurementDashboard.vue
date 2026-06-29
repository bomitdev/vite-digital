<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold m-0 text-primary">
              <i class="bi bi-cart-check me-2"></i>ระบบจัดการการจัดซื้อคอมพิวเตอร์และอุปกรณ์
            </h5>
            <div class="d-flex gap-2">
              <button
                class="btn btn-outline-secondary rounded-pill px-3"
                @click="$router.push('/home-backoffice')"
              >
                <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
              </button>
              <button class="btn btn-primary rounded-pill px-4" @click="openForm()">
                <i class="bi bi-plus-lg me-2"></i>สร้างรายการ
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-3">
                <input
                  type="text"
                  v-model="filters.search"
                  class="form-control rounded-pill"
                  placeholder="ค้นหา (เลขที่บิล, ชื่อร้านค้า, หมายเหตุ...)"
                  @input="fetchBills"
                />
              </div>
              <div class="col-md-3">
                <select
                  v-model="filters.status"
                  class="form-select rounded-pill"
                  @change="fetchBills"
                >
                  <option value="">-- ทุกสถานะ --</option>
                  <option value="Draft">รอดำเนินการ (Draft)</option>
                  <option value="Forwarded">ส่งให้บริหารแล้ว (Forwarded)</option>
                  <option value="Received">บริหารรับเอกสารแล้ว (Received)</option>
                </select>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="bg-light">
                  <tr>
                    <th class="text-center" width="5%">#</th>
                    <th width="15%">เลขที่บิล</th>
                    <th width="20%">ชื่อร้านค้า/บริษัท</th>
                    <th width="10%">ยอดเงิน</th>
                    <th width="10%">วันที่บิล</th>
                    <th width="15%">สถานะ</th>
                    <th width="15%">ข้อมูลการส่งมอบ</th>
                    <th width="10%" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(bill, index) in bills" :key="bill.id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="fw-bold text-primary">{{ bill.bill_number }}</td>
                    <td>{{ bill.vendor_name }}</td>
                    <td>{{ formatCurrency(bill.amount) }}</td>
                    <td>{{ formatDate(bill.bill_date) }}</td>
                    <td>
                      <span :class="['badge rounded-pill', getStatusClass(bill.status)]">
                        {{ getStatusText(bill.status) }}
                      </span>
                    </td>
                    <td>
                      <div v-if="bill.status === 'Forwarded' || bill.status === 'Received'" class="small text-muted">
                        <i class="bi bi-send me-1"></i>ส่งโดย: {{ bill.forwarded_by }}<br/>
                        <span class="ms-3">{{ formatDateTime(bill.forwarded_at) }}</span>
                      </div>
                      <div v-if="bill.status === 'Received'" class="small text-success mt-1">
                        <i class="bi bi-check2-all me-1"></i>รับโดย: {{ bill.received_by }}<br/>
                        <span class="ms-3">{{ formatDateTime(bill.received_at) }}</span>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2">
                        <!-- Action Buttons based on status -->
                        <button
                          v-if="bill.status === 'Draft'"
                          class="btn btn-sm btn-success"
                          @click="forwardBill(bill)"
                          title="ส่งต่อให้บริหาร"
                        >
                          <i class="bi bi-send-check"></i>
                        </button>
                        
                        <button
                          v-if="bill.status === 'Forwarded'"
                          class="btn btn-sm btn-info text-white"
                          @click="receiveBill(bill)"
                          title="ยืนยันรับเอกสาร (บริหาร)"
                        >
                          <i class="bi bi-inbox"></i>
                        </button>

                        <button
                          v-if="bill.status === 'Draft'"
                          class="btn btn-sm btn-outline-warning"
                          @click="openForm(bill)"
                          title="แก้ไข"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        
                        <!-- One button for all attachments -->
                        <button
                          class="btn btn-sm btn-outline-primary"
                          @click="openFileManager(bill)"
                          title="จัดการไฟล์แนบ"
                        >
                          <i class="bi bi-paperclip"></i>
                        </button>

                        <button
                          v-if="bill.status === 'Draft' || isAdmin"
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteBill(bill)"
                          title="ลบ"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="bills.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลบิล</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProcurementForm ref="procurementForm" @saved="fetchBills" />

    <!-- File Manager Modal -->
    <div class="modal fade" id="fileManagerModal" tabindex="-1" ref="fileManagerModal">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header" style="background: #1e293b;">
            <div>
              <h6 class="modal-title text-white mb-0">
                <i class="bi bi-paperclip me-2"></i>ไฟล์แนบ
              </h6>
              <small class="text-white-50">{{ fileManagerBill && fileManagerBill.bill_number }}</small>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-3" style="background: #f8fafc;">
            <!-- Hidden file input -->
            <input
              type="file"
              ref="replacementFileInput"
              style="display:none"
              accept="image/*,application/pdf"
              @change="uploadReplacementFile"
            />

            <div class="d-flex flex-column gap-3">
              <!-- คำขออนุมัติซื้อ -->
              <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                      :style="fileManagerBill && fileManagerBill.approval_file_path ? 'background:#dbeafe;width:36px;height:36px' : 'background:#f1f5f9;width:36px;height:36px'">
                      <i class="bi bi-file-earmark-check"
                        :class="fileManagerBill && fileManagerBill.approval_file_path ? 'text-primary' : 'text-secondary'"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">คำขออนุมัติซื้อ</div>
                      <div class="small" :class="fileManagerBill && fileManagerBill.approval_file_path ? 'text-success' : 'text-muted'">
                        {{ fileManagerBill && fileManagerBill.approval_file_path ? 'มีไฟล์แนบ' : 'ยังไม่มีไฟล์' }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-1">
                    <button v-if="fileManagerBill && fileManagerBill.approval_file_path"
                      class="btn btn-sm btn-outline-primary rounded-pill px-3"
                      @click="openViewerFromManager('approval_file_path', 'approval_file', 'คำขออนุมัติซื้อ')"
                    >ดูไฟล์</button>
                    <button
                      class="btn btn-sm rounded-pill px-3"
                      :class="fileManagerBill && fileManagerBill.approval_file_path ? 'btn-outline-warning' : 'btn-primary'"
                      @click="triggerManagerUpload('approval_file', 'คำขออนุมัติซื้อ')"
                      :disabled="fileViewerUploading && currentFileField === 'approval_file'"
                    >
                      <span v-if="fileViewerUploading && currentFileField === 'approval_file'" class="spinner-border spinner-border-sm"></span>
                      <span v-else>{{ fileManagerBill && fileManagerBill.approval_file_path ? 'เปลี่ยน' : 'อัปโหลด' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- ใบสั่งซื้อ -->
              <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                      :style="fileManagerBill && fileManagerBill.po_file_path ? 'background:#cffafe;width:36px;height:36px' : 'background:#f1f5f9;width:36px;height:36px'">
                      <i class="bi bi-file-earmark-ruled"
                        :class="fileManagerBill && fileManagerBill.po_file_path ? 'text-info' : 'text-secondary'"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">ใบสั่งซื้อ (PO)</div>
                      <div class="small" :class="fileManagerBill && fileManagerBill.po_file_path ? 'text-success' : 'text-muted'">
                        {{ fileManagerBill && fileManagerBill.po_file_path ? 'มีไฟล์แนบ' : 'ยังไม่มีไฟล์' }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-1">
                    <button v-if="fileManagerBill && fileManagerBill.po_file_path"
                      class="btn btn-sm btn-outline-info rounded-pill px-3"
                      @click="openViewerFromManager('po_file_path', 'po_file', 'ใบสั่งซื้อ')"
                    >ดูไฟล์</button>
                    <button
                      class="btn btn-sm rounded-pill px-3"
                      :class="fileManagerBill && fileManagerBill.po_file_path ? 'btn-outline-warning' : 'btn-primary'"
                      @click="triggerManagerUpload('po_file', 'ใบสั่งซื้อ')"
                      :disabled="fileViewerUploading && currentFileField === 'po_file'"
                    >
                      <span v-if="fileViewerUploading && currentFileField === 'po_file'" class="spinner-border spinner-border-sm"></span>
                      <span v-else>{{ fileManagerBill && fileManagerBill.po_file_path ? 'เปลี่ยน' : 'อัปโหลด' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- ใบวางบิล -->
              <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body py-3 px-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                      :style="fileManagerBill && fileManagerBill.file_path ? 'background:#fef9c3;width:36px;height:36px' : 'background:#f1f5f9;width:36px;height:36px'">
                      <i class="bi bi-file-earmark-text"
                        :class="fileManagerBill && fileManagerBill.file_path ? 'text-warning' : 'text-secondary'"></i>
                    </div>
                    <div>
                      <div class="fw-semibold small">ใบวางบิล</div>
                      <div class="small" :class="fileManagerBill && fileManagerBill.file_path ? 'text-success' : 'text-muted'">
                        {{ fileManagerBill && fileManagerBill.file_path ? 'มีไฟล์แนบ' : 'ยังไม่มีไฟล์' }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-1">
                    <button v-if="fileManagerBill && fileManagerBill.file_path"
                      class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                      @click="openViewerFromManager('file_path', 'invoice_file', 'ใบวางบิล')"
                    >ดูไฟล์</button>
                    <button
                      class="btn btn-sm rounded-pill px-3"
                      :class="fileManagerBill && fileManagerBill.file_path ? 'btn-outline-warning' : 'btn-primary'"
                      @click="triggerManagerUpload('invoice_file', 'ใบวางบิล')"
                      :disabled="fileViewerUploading && currentFileField === 'invoice_file'"
                    >
                      <span v-if="fileViewerUploading && currentFileField === 'invoice_file'" class="spinner-border spinner-border-sm"></span>
                      <span v-else>{{ fileManagerBill && fileManagerBill.file_path ? 'เปลี่ยน' : 'อัปโหลด' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- File Viewer Modal -->
    <div class="modal fade" id="fileViewerModal" tabindex="-1" ref="fileViewerModal">
      <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90vw;">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-dark text-white py-2 px-4">
            <h6 class="modal-title mb-0">
              <i class="bi bi-file-earmark-text me-2"></i>{{ fileViewerTitle }}
            </h6>
            <div class="d-flex gap-2 align-items-center">
              <button
                v-if="currentBill"
                class="btn btn-sm btn-warning text-dark"
                @click="triggerFileUpload"
                title="เปลี่ยนไฟล์"
                :disabled="fileViewerUploading"
              >
                <span v-if="fileViewerUploading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-arrow-repeat me-1"></i>เปลี่ยนไฟล์
              </button>
              <a v-if="fileViewerUrl && !fileViewerError" :href="fileViewerUrl" target="_blank" class="btn btn-sm btn-outline-light" title="เปิดในแท็บใหม่">
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
          </div>
          <!-- Hidden file input for replacement upload -->
          <input
            type="file"
            ref="replacementFileInput"
            style="display:none"
            accept="image/*,application/pdf"
            @change="uploadReplacementFile"
          />
          <div class="modal-body p-0" style="height: 80vh; background: #525659;">
            <!-- Loading -->
            <div v-if="fileViewerLoading" class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
              <div class="spinner-border mb-3" role="status"></div>
              <p class="mb-0">กำลังโหลดไฟล์...</p>
            </div>
            <!-- Error -->
            <div v-else-if="fileViewerError" class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
              <i class="bi bi-file-earmark-x fs-1 mb-3 text-danger"></i>
              <p class="fw-bold fs-5 mb-1">ไม่พบไฟล์</p>
              <p class="text-white-50 small mb-3">{{ fileViewerError }}</p>
              <button
                v-if="currentBill"
                class="btn btn-primary"
                @click="triggerFileUpload"
                :disabled="fileViewerUploading"
              >
                <span v-if="fileViewerUploading" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-upload me-1"></i>อัปโหลดไฟล์ใหม่
              </button>
            </div>
            <!-- PDF Viewer -->
            <iframe
              v-else-if="fileViewerType === 'pdf'"
              :src="fileViewerUrl"
              width="100%"
              height="100%"
              style="border: none; display: block;"
            ></iframe>
            <!-- Image Viewer -->
            <div
              v-else-if="fileViewerType === 'image'"
              class="d-flex align-items-center justify-content-center h-100"
            >
              <img :src="fileViewerUrl" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
            </div>
            <!-- Unsupported -->
            <div v-else class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
              <i class="bi bi-file-earmark-x fs-1 mb-3"></i>
              <p>ไม่สามารถแสดงไฟล์ประเภทนี้ได้</p>
              <a :href="fileViewerUrl" target="_blank" class="btn btn-light">ดาวน์โหลดไฟล์</a>
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
import ProcurementForm from './ProcurementForm.vue';
import { Modal } from 'bootstrap';

export default {
  name: 'ProcurementDashboard',
  components: { ProcurementForm },
  data() {
    return {
      bills: [],
      userProfile: null,
      filters: {
        search: '',
        status: ''
      },
      fileViewerUrl: '',
      fileViewerTitle: '',
      fileViewerType: '',
      fileViewerLoading: false,
      fileViewerUploading: false,
      fileViewerError: '',
      currentBill: null,
      currentFileField: null,
      fileManagerBill: null,
      bsFileManagerModal: null,
      bsFileViewerModal: null
    };
  },
  computed: {
    isAdmin() {
      const dept = (this.userProfile && this.userProfile.department) || '';
      return dept.includes('กลุ่มงานสุขภาพดิจิทัล');
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchBills();
    this.bsFileManagerModal = new Modal(this.$refs.fileManagerModal);
    this.bsFileViewerModal = new Modal(this.$refs.fileViewerModal);
    this.$refs.fileManagerModal.addEventListener('hidden.bs.modal', () => {
      this.fileManagerBill = null;
    });
    this.$refs.fileViewerModal.addEventListener('hidden.bs.modal', () => {
      this.fileViewerUrl = '';
      this.fileViewerType = '';
      this.fileViewerError = '';
      this.fileViewerLoading = false;
      this.fileViewerUploading = false;
      this.currentBill = null;
      this.currentFileField = null;
      if (this.$refs.replacementFileInput) this.$refs.replacementFileInput.value = '';
    });
  },
  methods: {
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.userProfile = response.data;
        }
      } catch (error) {
        console.error('Failed to load profile', error);
      }
    },
    async fetchBills() {
      try {
        const params = new URLSearchParams(this.filters).toString();
        const res = await axios.get(`/api-digital/procurement/get_bills.php?${params}`);
        if (res.data.status === 'success') {
          this.bills = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    openForm(bill = null) {
      this.$refs.procurementForm.open(bill);
    },
    async forwardBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันการส่งเอกสาร?',
        text: `คุณต้องการส่งบิลเลขที่ ${bill.bill_number} ให้ฝ่ายบริหารใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ส่งเอกสาร',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#28a745'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/forward_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ส่งเอกสารสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot forward bill', 'error');
        }
      }
    },
    async receiveBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันรับเอกสาร?',
        text: `คุณได้รับบิลเลขที่ ${bill.bill_number} เรียบร้อยแล้วใช่หรือไม่?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'รับเอกสารแล้ว',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#17a2b8'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/receive_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'รับเอกสารสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot receive bill', 'error');
        }
      }
    },
    async deleteBill(bill) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบบิลเลขที่ ${bill.bill_number} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/procurement/delete_bill.php', { id: bill.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', showConfirmButton: false, timer: 1500 });
            this.fetchBills();
          } else {
            Swal.fire('Error', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', err.response?.data?.message || 'Cannot delete bill', 'error');
        }
      }
    },
    openFileManager(bill) {
      this.fileManagerBill = bill;
      this.bsFileManagerModal.show();
    },
    openViewerFromManager(pathField, fileField, label) {
      const path = this.fileManagerBill[pathField];
      const title = `${label} - ${this.fileManagerBill.bill_number}`;
      this.bsFileManagerModal.hide();
      this.$nextTick(() => {
        this.viewFile(path, title, this.fileManagerBill, fileField);
      });
    },
    triggerManagerUpload(fileField, label) {
      this.currentBill = this.fileManagerBill;
      this.currentFileField = fileField;
      this.fileViewerTitle = `${label} - ${this.fileManagerBill.bill_number}`;
      if (this.$refs.replacementFileInput) this.$refs.replacementFileInput.click();
    },
    async viewFile(path, title = 'ดูไฟล์แนบ', bill = null, fileField = null) {
      if (!path) return;
      this.currentBill = bill;
      this.currentFileField = fileField;
      let url = path;
      if (!path.startsWith('http')) {
        if (import.meta.env.DEV) {
          url = path.startsWith('/') ? path : `/${path}`;
        } else {
          const baseUrl = window.location.origin;
          url = `${baseUrl}/vue-app/vite-digital/${path}`;
        }
      }
      const ext = path.split('.').pop().toLowerCase();
      const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
      if (ext === 'pdf') {
        this.fileViewerType = 'pdf';
      } else if (imageExts.includes(ext)) {
        this.fileViewerType = 'image';
      } else {
        this.fileViewerType = 'other';
      }
      this.fileViewerUrl = '';
      this.fileViewerError = '';
      this.fileViewerLoading = true;
      this.fileViewerTitle = title;
      this.bsFileViewerModal.show();
      try {
        const resp = await fetch(url, { method: 'HEAD' });
        if (!resp.ok) {
          this.fileViewerError = `ไม่พบไฟล์นี้ในระบบ (อาจถูกลบหรือยังไม่ได้อัปโหลด) [สถานะ: ${resp.status}]`;
        } else {
          this.fileViewerUrl = url;
        }
      } catch (e) {
        this.fileViewerError = 'ไม่สามารถเชื่อมต่อไฟล์ได้: ' + e.message;
      } finally {
        this.fileViewerLoading = false;
      }
    },
    triggerFileUpload() {
      if (this.$refs.replacementFileInput) {
        this.$refs.replacementFileInput.click();
      }
    },
    async uploadReplacementFile(event) {
      const file = event.target.files[0];
      if (!file || !this.currentBill || !this.currentFileField) return;

      this.fileViewerUploading = true;
      try {
        const formData = new FormData();
        // ส่งข้อมูลบิลทั้งหมดเพื่อ update
        const b = this.currentBill;
        formData.append('id', b.id);
        formData.append('bill_number', b.bill_number);
        formData.append('vendor_name', b.vendor_name);
        formData.append('bill_date', b.bill_date);
        formData.append('amount', b.amount || 0);
        formData.append('notes', b.notes || '');
        formData.append('file_path', b.file_path || '');
        formData.append('approval_file_path', b.approval_file_path || '');
        formData.append('po_file_path', b.po_file_path || '');
        // แนบไฟล์ใหม่
        formData.append(this.currentFileField, file);

        const res = await axios.post('/api-digital/procurement/save_bill.php', formData);
        if (res.data.status === 'success') {
          await this.fetchBills();
          const updated = this.bills.find(b => b.id === this.currentBill.id);
          if (updated) {
            const fieldMap = {
              'approval_file': 'approval_file_path',
              'po_file': 'po_file_path',
              'invoice_file': 'file_path'
            };
            // อัปเดตโบอใน file manager ด้วย
            this.fileManagerBill = updated;
            this.currentBill = updated;
            // ถ้าไม่ได้เปิดผ่าน viewer ให้อัปเดต fileManagerBill แล้วสวย
            if (this.fileViewerTitle) {
              const newPath = updated[fieldMap[this.currentFileField]];
              await this.viewFile(newPath, this.fileViewerTitle, updated, this.currentFileField);
            }
          }
        } else {
          Swal.fire('เกิดข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('เกิดข้อผิดพลาด', err.response?.data?.message || err.message, 'error');
      } finally {
        this.fileViewerUploading = false;
        if (this.$refs.replacementFileInput) this.$refs.replacementFileInput.value = '';
      }
    },
    getStatusClass(status) {
      switch (status) {
        case 'Draft': return 'bg-secondary';
        case 'Forwarded': return 'bg-warning text-dark';
        case 'Received': return 'bg-success';
        default: return 'bg-light text-dark';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'Draft': return 'รอดำเนินการ';
        case 'Forwarded': return 'ส่งให้บริหารแล้ว';
        case 'Received': return 'บริหารรับเอกสารแล้ว';
        default: return status;
      }
    },
    formatCurrency(value) {
      if (!value) return '0.00';
      return parseFloat(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    formatDateTime(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + 
             date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>
