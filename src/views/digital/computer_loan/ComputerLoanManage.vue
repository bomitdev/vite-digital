<template>
  <div class="container-fluid py-4 min-vh-100" style="background-color: #f8f9fa">
    <div class="row g-4">
    <!-- Header Page -->
    <div class="col-12 text-center my-4">
      <h3 class="fw-bold text-primary mb-3">
        <i class="bi bi-clock-history me-2"></i>ประวัติการยืม-คืน อุปกรณ์คอมพิวเตอร์
      </h3>
      <p class="text-secondary">จัดการตรวจสอบ อนุมัติการยืม และบันทึกการรับคืนอุปกรณ์</p>
    </div>

    <!-- Stats summary -->
    <div class="col-12">
      <div class="row g-4 text-start">
        <div class="col-md-4">
          <div class="card text-dark border-0 shadow-sm rounded-4 h-100 p-4 position-relative overflow-hidden" style="background-color: #ffd60a;">
            <div class="position-absolute top-50 translate-middle-y end-0 pe-4 opacity-25">
              <i class="bi bi-hourglass-split" style="font-size: 4.5rem;"></i>
            </div>
            <div class="position-relative" style="z-index: 1;">
              <h2 class="fw-bolder display-5 mb-0">{{ pendingCount }}</h2>
              <span class="fw-semibold">รอตรวจสอบ</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white border-0 shadow-sm rounded-4 h-100 p-4 position-relative overflow-hidden" style="background-color: #00b4d8;">
            <div class="position-absolute top-50 translate-middle-y end-0 pe-4 opacity-25">
              <i class="bi bi-pc-display" style="font-size: 4.5rem;"></i>
            </div>
            <div class="position-relative" style="z-index: 1;">
              <h2 class="fw-bolder display-5 mb-0">{{ borrowedCount }}</h2>
              <span class="fw-semibold">กำลังยืม (ยังไม่คืน)</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white border-0 shadow-sm rounded-4 h-100 p-4 position-relative overflow-hidden" style="background-color: #2b9348;">
            <div class="position-absolute top-50 translate-middle-y end-0 pe-4 opacity-25">
              <i class="bi bi-check-circle" style="font-size: 4.5rem;"></i>
            </div>
            <div class="position-relative" style="z-index: 1;">
              <h2 class="fw-bolder display-5 mb-0">{{ returnedCount }}</h2>
              <span class="fw-semibold">คืนแล้ว</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="col-12 mt-2">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0 table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">วันที่ขอ</th>
                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">ผู้ยืม / หน่วยงาน</th>
                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">ครุภัณฑ์ที่ยืม</th>
                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">วัตถุประสงค์</th>
                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">กำหนดคืน</th>
                <th class="py-3 px-4 text-center text-secondary fw-semibold border-bottom-0">สถานะ</th>
                <th class="py-3 px-4 text-center text-secondary fw-semibold border-bottom-0">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status"></div>
                </td>
              </tr>
              <tr v-else-if="loans.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลการยืม</td>
              </tr>
              <tr v-for="loan in loans" :key="loan.id" class="border-bottom">
                <td class="px-4 py-3">{{ formatDate(loan.created_at) }}</td>
                <td class="px-4 py-3">
                  <div class="fw-bold text-dark">{{ loan.borrower_name }}</div>
                  <div class="small text-muted">{{ loan.department }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="d-flex align-items-center gap-3">
                    <div v-if="loan.asset_image_path" class="flex-shrink-0">
                      <img :src="getImageUrl(loan.asset_image_path)" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                    </div>
                    <div v-else class="flex-shrink-0 d-flex justify-content-center align-items-center bg-light rounded text-muted shadow-sm" style="width: 48px; height: 48px;">
                      <i class="bi bi-pc-display" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                      <div class="fw-bold text-primary">[{{ loan.asset_code }}]</div>
                      <div class="small text-muted">{{ loan.asset_name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-truncate" style="max-width: 250px" :title="loan.objective">
                  {{ loan.objective }}
                </td>
                <td class="px-4 py-3">
                  <div :class="{ 'text-danger fw-bold': isOverdue(loan) }">
                    {{ formatDate(loan.expected_return_date, false) }}
                  </div>
                  <div v-if="loan.status === 'returned'" class="small text-success mt-1">
                    (คืนเมื่อ: {{ formatDate(loan.actual_return_date) }})
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="getStatusBadgeClass(loan.status)">
                    {{ getStatusLabel(loan.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <!-- Action Buttons -->
                  <div class="d-flex justify-content-center gap-2">
                    <button
                      v-if="loan.status === 'pending'"
                      @click="updateStatus(loan, 'borrowed')"
                      class="btn btn-sm btn-outline-success rounded-circle"
                      title="อนุมัติให้ยืม"
                    >
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button
                      v-if="loan.status === 'pending'"
                      @click="openRejectModal(loan)"
                      class="btn btn-sm btn-outline-danger rounded-circle"
                      title="ปฏิเสธ"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>
                    <button
                      v-if="loan.status === 'borrowed'"
                      @click="updateStatus(loan, 'returned')"
                      class="btn btn-sm btn-outline-primary"
                      title="บันทึกรับคืน"
                    >
                      รับคืน
                    </button>
                    <button
                      class="btn btn-sm btn-light rounded-circle"
                      title="ดูรายละเอียด/จดบันทึก"
                      @click="openDetailModal(loan)"
                    >
                      <i class="bi bi-info-circle text-muted"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Detail/Reject Modal -->
    <div class="modal fade" id="manageLoanModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold">จัดการคำขอยืม</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body pt-3 pb-4 px-4" v-if="selectedLoan">
            <div class="mb-3">
              <strong>อุปกณ์:</strong> [{{ selectedLoan.asset_code }}] {{ selectedLoan.asset_name }}
            </div>
            <div class="mb-3">
              <strong>ผู้ยืม:</strong> {{ selectedLoan.borrower_name }} ({{
                selectedLoan.department
              }})
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold"
                >บันทึกของ Admin
                <span v-if="modalAction === 'rejected'" class="text-danger">*</span></label
              >
              <textarea
                v-model="adminNote"
                class="form-control"
                rows="3"
                placeholder="เพิ่มหมายเหตุ (ถ้ามี)"
              ></textarea>
            </div>
          </div>
          <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
            <button
              type="button"
              class="btn text-white"
              :class="modalAction === 'rejected' ? 'btn-danger' : 'btn-primary'"
              @click="submitModalAction"
            >
              บันทึกข้อมูล
            </button>
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
import * as bootstrap from 'bootstrap';

export default {
  name: 'ComputerLoanManage',
  data() {
    return {
      loans: [],
      loading: true,
      selectedLoan: null,
      adminNote: '',
      modalAction: '', // 'note' or 'rejected'
      myModal: null
    };
  },
  computed: {
    pendingCount() {
      return this.loans.filter((l) => l.status === 'pending').length;
    },
    borrowedCount() {
      return this.loans.filter((l) => l.status === 'borrowed').length;
    },
    returnedCount() {
      return this.loans.filter((l) => l.status === 'returned').length;
    }
  },
  mounted() {
    this.fetchLoans();
  },
  methods: {
    async fetchLoans() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/computer_loan/get_loans.php');
        if (res.data.success) {
          this.loans = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching loans:', error);
      } finally {
        this.loading = false;
      }
    },
    formatDate(dateString, includeTime = true) {
      if (!dateString) return '-';
      const d = new Date(dateString);
      const day = d.getDate().toString().padStart(2, '0');
      const month = (d.getMonth() + 1).toString().padStart(2, '0');
      const year = d.getFullYear() + 543;
      if (!includeTime) return `${day}/${month}/${year}`;

      const hours = d.getHours().toString().padStart(2, '0');
      const minutes = d.getMinutes().toString().padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${minutes}`;
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    isOverdue(loan) {
      if (loan.status !== 'borrowed') return false;
      const expected = new Date(loan.expected_return_date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return expected < today;
    },
    getStatusLabel(status) {
      const labels = {
        pending: 'รอตรวจสอบ',
        borrowed: 'กำลังยืม',
        returned: 'คืนแล้ว',
        rejected: 'ไม่อนุมัติ'
      };
      return labels[status] || status;
    },
    getStatusBadgeClass(status) {
      const classes = {
        pending: 'badge bg-warning text-dark px-3 py-2 rounded-pill',
        borrowed: 'badge bg-info text-dark px-3 py-2 rounded-pill',
        returned: 'badge bg-success px-3 py-2 rounded-pill',
        rejected: 'badge bg-danger px-3 py-2 rounded-pill'
      };
      return classes[status] || 'badge bg-secondary';
    },
    async updateStatus(loan, newStatus) {
      let confirmMessage = '';
      if (newStatus === 'borrowed') {
        confirmMessage = 'ยืนยันการอนุมัติให้ยืม? (จะเปลี่ยนสถานะเครื่องเป็น "ถูกยืม")';
      } else if (newStatus === 'returned') {
        confirmMessage = 'ยืนยันการรับคืนอุปกรณ์? (จะเปลี่ยนสถานะเครื่องกลับเป็น "สำรอง")';
      }

      const result = await Swal.fire({
        title: 'ยืนยันการดำเนินการ',
        text: confirmMessage,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
      });

      if (result.isConfirmed) {
        this.processUpdateStatus(loan.id, newStatus, loan.admin_note);
      }
    },
    openRejectModal(loan) {
      this.selectedLoan = loan;
      this.modalAction = 'rejected';
      this.adminNote = loan.admin_note || '';
      if (!this.myModal) {
        this.myModal = new bootstrap.Modal(document.getElementById('manageLoanModal'));
      }
      this.myModal.show();
    },
    openDetailModal(loan) {
      this.selectedLoan = loan;
      this.modalAction = 'note';
      this.adminNote = loan.admin_note || '';
      if (!this.myModal) {
        this.myModal = new bootstrap.Modal(document.getElementById('manageLoanModal'));
      }
      this.myModal.show();
    },
    async submitModalAction() {
      if (this.modalAction === 'rejected' && !this.adminNote.trim()) {
        Swal.fire('ข้อผิดพลาด', 'กรุณาระบุเหตุผลการไม่อนุมัติ', 'warning');
        return;
      }

      const statusToUpdate =
        this.modalAction === 'rejected' ? 'rejected' : this.selectedLoan.status;
      await this.processUpdateStatus(this.selectedLoan.id, statusToUpdate, this.adminNote);
      if (this.myModal) this.myModal.hide();
    },
    async processUpdateStatus(id, newStatus, adminNote) {
      try {
        const res = await axios.post('/api-digital/computer_loan/update_loan_status.php', {
          id: id,
          status: newStatus,
          admin_note: adminNote
        });
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            toast: true,
            position: 'top-end',
            timer: 2000,
            showConfirmButton: false
          });
          this.fetchLoans();
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถทำรายการได้', 'error');
      }
    }
  }
};
</script>

<style scoped>
.table th {
  font-weight: 600;
  color: #495057;
}
</style>
