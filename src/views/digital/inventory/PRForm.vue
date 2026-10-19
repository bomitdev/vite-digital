<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><router-link to="/home-backoffice">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-dashboard">Inventory Dashboard</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-pr">ขอซื้อ (PR)</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">{{ isEdit ? pr.pr_number : 'สร้างใบขอซื้อใหม่' }}</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-file-earmark-plus me-2"></i>{{ isEdit ? 'ใบขอซื้อ: ' + pr.pr_number : 'สร้างใบขอซื้อใหม่ (PR)' }}
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-danger rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-po-form')">
            <i class="bi bi-cart-check me-1"></i> ระบบสั่งซื้อเวชภัณฑ์ (PO)
          </button>
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              
              <!-- Status Badge (if edit) -->
              <div v-if="isEdit" class="alert mb-4 d-flex align-items-center" :class="alertClass">
                <i class="bi me-2 fs-4" :class="alertIcon"></i>
                <div>
                  <h6 class="fw-bold mb-0">สถานะ: {{ getStatusText(pr.status) }}</h6>
                  <p class="mb-0 small" v-if="pr.head_approver_name">หัวหน้าอนุมัติ: {{ pr.head_approver_name }} ({{ formatDate(pr.head_approve_date) }})</p>
                  <p class="mb-0 small text-danger" v-if="pr.head_reject_reason">เหตุผลไม่อนุมัติ: {{ pr.head_reject_reason }}</p>
                  <p class="mb-0 small" v-if="pr.dir_approver_name">ผอ.อนุมัติ: {{ pr.dir_approver_name }} ({{ formatDate(pr.dir_approve_date) }})</p>
                  <p class="mb-0 small text-danger" v-if="pr.dir_reject_reason">เหตุผลไม่อนุมัติ: {{ pr.dir_reject_reason }}</p>
                </div>
              </div>

              <form @submit.prevent="savePR">
                <!-- Section 1: ข้อมูลใบขอซื้อ -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2 text-danger">1. ข้อมูลใบขอซื้อ</h5>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="pr.warehouse_id" required :disabled="!canEdit">
                        <option value="">-- เลือกคลัง --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">วันที่ขอซื้อ <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" v-model="pr.request_date" required :disabled="!canEdit">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">ผู้ขอซื้อ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="pr.requester_name" required :disabled="!canEdit" placeholder="ระบุชื่อ-สกุล">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">เหตุผลการขอซื้อ</label>
                      <textarea class="form-control" v-model="pr.reason" rows="2" placeholder="เช่น เพื่อสำรองใช้ในคลัง..." :disabled="!canEdit"></textarea>
                    </div>
                  </div>
                </div>

                <!-- Section 2: รายการวัสดุ -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0 text-danger">2. รายการวัสดุที่ต้องการสั่งซื้อ</h5>
                    <button v-if="canEdit" type="button" class="btn btn-sm btn-primary rounded-pill shadow-sm" @click="openItemModal">
                      <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการ
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>รหัส</th>
                          <th>รายการวัสดุ</th>
                          <th>จำนวนที่ขอ <span class="text-danger">*</span></th>
                          <th>หน่วยนับ</th>
                          <th>ราคาประเมิน/หน่วย</th>
                          <th>รวมเงิน (โดยประมาณ)</th>
                          <th v-if="canEdit" width="80" class="text-center">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="pr.items.length === 0">
                          <td :colspan="canEdit ? 7 : 6" class="text-center py-4 text-muted">ยังไม่มีรายการวัสดุ</td>
                        </tr>
                        <tr v-for="(item, index) in pr.items" :key="index">
                          <td>{{ item.item_code }}</td>
                          <td>{{ item.item_name }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.qty_requested" min="1" required :disabled="!canEdit">
                          </td>
                          <td>{{ item.unit_name }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.estimated_price" min="0" step="0.01" :disabled="!canEdit">
                          </td>
                          <td class="text-end fw-bold">{{ (item.qty_requested * item.estimated_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                          <td v-if="canEdit" class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td :colspan="5" class="text-end fw-bold bg-light">ยอดรวมประเมินทั้งสิ้น :</td>
                          <td class="text-end fw-bold bg-light text-danger fs-5">{{ totalEstimatedPrice.toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                          <td v-if="canEdit" class="bg-light"></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-5">
                  <button type="button" class="btn btn-light border px-4 rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-pr')">
                    <i class="bi bi-arrow-left"></i> กลับหน้ารายการ
                  </button>
                  <button v-if="canEdit" type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-save me-1" v-else></i> บันทึกใบขอซื้อ
                  </button>
                </div>
              </form>

              <!-- Approval Section -->
              <div v-if="isEdit && pr.status === 'PENDING'" class="mt-4 p-4 bg-warning-light rounded-4 border border-warning">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-check-fill me-2 text-warning"></i>สำหรับหัวหน้าแผนก (Head Approval)</h5>
                <p class="text-muted small">โปรดตรวจสอบรายการและพิจารณาอนุมัติใบขอซื้อนี้</p>
                <div class="d-flex gap-2">
                   <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="handleApprove('HEAD', 'APPROVE')"><i class="bi bi-check-circle me-1"></i> อนุมัติ</button>
                   <button class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm" @click="handleApprove('HEAD', 'REJECT')"><i class="bi bi-x-circle me-1"></i> ไม่อนุมัติ</button>
                </div>
              </div>

              <div v-if="isEdit && pr.status === 'HEAD_APPROVED'" class="mt-4 p-4 bg-info-light rounded-4 border border-info">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-check-fill me-2 text-info"></i>สำหรับผู้อำนวยการ (Director Approval)</h5>
                <p class="text-muted small">โปรดตรวจสอบรายการและพิจารณาอนุมัติใบขอซื้อนี้ เพื่อดำเนินการจัดซื้อต่อไป</p>
                <div class="d-flex gap-2">
                   <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="handleApprove('DIR', 'APPROVE')"><i class="bi bi-check-circle me-1"></i> อนุมัติ</button>
                   <button class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm" @click="handleApprove('DIR', 'REJECT')"><i class="bi bi-x-circle me-1"></i> ไม่อนุมัติ</button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal เลือกวัสดุ -->
    <div class="modal fade" id="itemModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
          <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>เลือกรายการวัสดุ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <input type="text" class="form-control form-control-lg mb-3 rounded-pill bg-light border-0 px-4" v-model="searchItemQuery" placeholder="พิมพ์ชื่อหรือรหัสวัสดุเพื่อค้นหา...">
            <div class="table-responsive" style="max-height: 400px;">
              <table class="table table-hover align-middle">
                <thead class="table-light sticky-top">
                  <tr>
                    <th>รหัส</th>
                    <th>ชื่อรายการ</th>
                    <th>หน่วย</th>
                    <th width="100">เลือก</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredItems" :key="item.id">
                    <td>{{ item.item_code }}</td>
                    <td>{{ item.item_name }}</td>
                    <td>{{ item.unit_name }}</td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-bold" @click="selectItem(item)" :disabled="isItemAlreadyAdded(item.id)">
                        {{ isItemAlreadyAdded(item.id) ? 'เลือกแล้ว' : 'เพิ่ม' }}
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredItems.length === 0">
                    <td colspan="4" class="text-center py-4 text-muted">ไม่พบรายการวัสดุ</td>
                  </tr>
                </tbody>
              </table>
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
  name: 'PRForm',
  data() {
    return {
      pr: {
        id: 0,
        pr_number: '',
        warehouse_id: '',
        request_date: new Date().toISOString().split('T')[0],
        requester_name: '',
        reason: '',
        status: 'PENDING',
        items: []
      },
      warehouses: [],
      availableItems: [],
      searchItemQuery: '',
      loading: false,
      saving: false,
      itemModal: null,
      isEdit: false
    }
  },
  computed: {
    canEdit() {
      // สามารถแก้ได้ถ้าเป็นสร้างใหม่ หรือยังเป็นสถานะ PENDING
      return !this.isEdit || this.pr.status === 'PENDING';
    },
    filteredItems() {
      if (!this.searchItemQuery) return this.availableItems;
      const q = this.searchItemQuery.toLowerCase();
      return this.availableItems.filter(i => 
        i.item_name.toLowerCase().includes(q) || 
        i.item_code.toLowerCase().includes(q)
      );
    },
    totalEstimatedPrice() {
      return this.pr.items.reduce((sum, item) => sum + (item.qty_requested * item.estimated_price), 0);
    },
    alertClass() {
      switch (this.pr.status) {
        case 'PENDING': return 'alert-warning';
        case 'HEAD_APPROVED': return 'alert-info';
        case 'DIR_APPROVED': return 'alert-success';
        case 'HEAD_REJECTED':
        case 'DIR_REJECTED': return 'alert-danger';
        default: return 'alert-secondary';
      }
    },
    alertIcon() {
      switch (this.pr.status) {
        case 'PENDING': return 'bi-hourglass-split';
        case 'HEAD_APPROVED': return 'bi-check-circle';
        case 'DIR_APPROVED': return 'bi-check-circle-fill';
        case 'HEAD_REJECTED':
        case 'DIR_REJECTED': return 'bi-x-circle-fill';
        default: return 'bi-info-circle';
      }
    }
  },
  async mounted() {
    await this.fetchWarehouses();
    const prId = this.$route.query.id;
    if (prId) {
      this.isEdit = true;
      await this.fetchPR(prId);
    } else {
      await this.fetchUserProfile();
    }
  },
  methods: {
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data.status === 'success') {
          this.pr.requester_name = res.data.fullname;
        }
      } catch (err) {
        console.error('Failed to load user profile', err);
      }
    },
    async fetchWarehouses() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const res = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`);
        if (res.data.status === 'success') {
          this.warehouses = res.data.data.filter(w => parseInt(w.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    async fetchPR(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/pr_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const data = res.data.data;
          data.request_date = data.request_date.split(' ')[0]; // extract YYYY-MM-DD
          // numeric parsing
          data.items.forEach(i => {
            i.qty_requested = Number(i.qty_requested);
            i.estimated_price = Number(i.estimated_price);
          });
          this.pr = data;
        } else {
          Swal.fire('Error', 'ไม่พบใบขอซื้อนี้', 'error');
          this.$router.push('/inventory-pr');
        }
      } catch (err) {
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
      }
    },
    async fetchItemsForWarehouse() {
      if (!this.pr.warehouse_id) return;
      try {
        // Use the existing items.php
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.pr.warehouse_id}`);
        if (res.data.status === 'success') {
          // For now, load all items. In a strict system, maybe filter by warehouse's categories.
          this.availableItems = res.data.data.filter(i => parseInt(i.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    async openItemModal() {
      if (!this.pr.warehouse_id) {
        Swal.fire('Warning', 'กรุณาเลือกคลังวัสดุก่อนเพิ่มรายการ', 'warning');
        return;
      }
      if (this.availableItems.length === 0) {
        await this.fetchItemsForWarehouse();
      }
      if (!this.itemModal) {
        this.itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
      }
      this.searchItemQuery = '';
      this.itemModal.show();
    },
    isItemAlreadyAdded(itemId) {
      return this.pr.items.some(i => i.item_id === itemId);
    },
    selectItem(item) {
      this.pr.items.push({
        item_id: item.id,
        item_code: item.item_code,
        item_name: item.item_name,
        unit_name: item.unit_name,
        qty_requested: 1,
        estimated_price: 0
      });
      // Optionally hide modal if single select is desired
      if (this.itemModal) {
        this.itemModal.hide();
      }
    },
    removeItem(index) {
      this.pr.items.splice(index, 1);
    },
    async savePR() {
      if (!this.pr.warehouse_id || !this.pr.requester_name) {
        Swal.fire('Warning', 'กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
      }
      if (this.pr.items.length === 0) {
        Swal.fire('Warning', 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ', 'warning');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          id: this.pr.id,
          warehouse_id: this.pr.warehouse_id,
          request_date: this.pr.request_date + ' 00:00:00',
          requester_name: this.pr.requester_name,
          reason: this.pr.reason,
          items: this.pr.items
        };

        const res = await axios.post('/api-digital/inventory/pr_save.php', payload);
        
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.$router.push('/inventory-pr');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาด', 'error');
      } finally {
        this.saving = false;
      }
    },
    async handleApprove(level, action) {
      let reason = '';
      if (action === 'REJECT') {
        const { value: text } = await Swal.fire({
          title: 'ระบุเหตุผลที่ไม่อนุมัติ',
          input: 'textarea',
          inputPlaceholder: 'พิมพ์เหตุผลของคุณที่นี่...',
          showCancelButton: true,
          confirmButtonText: 'ยืนยันไม่อนุมัติ',
          cancelButtonText: 'ยกเลิก'
        });
        if (text === undefined) return; // user cancelled
        if (!text.trim()) {
          Swal.fire('Warning', 'กรุณาระบุเหตุผล', 'warning');
          return;
        }
        reason = text.trim();
      } else {
        const confirm = await Swal.fire({
          title: 'ยืนยันการอนุมัติ?',
          text: "คุณตรวจสอบข้อมูลครบถ้วนแล้วใช่หรือไม่",
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#28a745',
          confirmButtonText: 'ใช่, อนุมัติเลย',
          cancelButtonText: 'ยกเลิก'
        });
        if (!confirm.isConfirmed) return;
      }

      // Fetch user profile again to get approver name
      let approver_name = 'ผู้มีอำนาจอนุมัติ';
      try {
        const uRes = await axios.get('/api-hosoffice/get_user_profile.php');
        if (uRes.data.status === 'success') {
          approver_name = uRes.data.fullname;
        }
      } catch (e) {}

      try {
        const res = await axios.post('/api-digital/inventory/pr_approve.php', {
          id: this.pr.id,
          level: level,
          action: action,
          approver_name: approver_name,
          reason: reason
        });
        if (res.data.status === 'success') {
          Swal.fire('สำเร็จ', 'ดำเนินการอนุมัติเรียบร้อย', 'success');
          await this.fetchPR(this.pr.id); // reload data
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการอนุมัติ', 'error');
      }
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('th-TH', {
        year: 'numeric', month: 'short', day: 'numeric'
      });
    },
    getStatusText(status) {
      switch (status) {
        case 'PENDING': return 'รอหัวหน้าอนุมัติ';
        case 'HEAD_APPROVED': return 'รอ ผอ. อนุมัติ';
        case 'DIR_APPROVED': return 'อนุมัติแล้ว (รอเปิด PO)';
        case 'HEAD_REJECTED': return 'หัวหน้าไม่อนุมัติ';
        case 'DIR_REJECTED': return 'ผอ. ไม่อนุมัติ';
        default: return status;
      }
    }
  }
}
</script>
