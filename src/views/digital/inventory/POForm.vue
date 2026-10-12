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
              <li class="breadcrumb-item"><router-link to="/inventory-po">สั่งซื้อ (PO)</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">{{ isEdit ? po.po_number : 'เปิดใบสั่งซื้อใหม่' }}</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-file-earmark-plus me-2"></i>{{ isEdit ? 'ใบสั่งซื้อ: ' + po.po_number : 'เปิดใบสั่งซื้อใหม่ (PO)' }}
          </h2>
        </div>
        <div class="d-flex gap-2">
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
                  <h6 class="fw-bold mb-0">สถานะ: {{ getStatusText(po.status) }}</h6>
                </div>
              </div>

              <form @submit.prevent="savePO">
                <!-- Section 1: ข้อมูลอ้างอิง PR -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2 text-danger">1. อ้างอิงใบขอซื้อ (PR)</h5>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">เลือกใบขอซื้อ (ที่ผ่านการอนุมัติ) <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="selectedPrId" required :disabled="isEdit" @change="onPrChange">
                        <option value="">-- เลือกใบ PR --</option>
                        <option v-for="pr in availablePrs" :key="pr.id" :value="pr.id">
                          {{ pr.pr_number }} (คลัง: {{ pr.warehouse_name }})
                        </option>
                        <!-- When editing, ensure the selected PR shows -->
                        <option v-if="isEdit" :value="po.pr_id" disabled>
                          {{ po.pr_number }}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Section 2: ข้อมูลใบสั่งซื้อ -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2 text-danger">2. ข้อมูลใบสั่งซื้อ</h5>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">วันที่สั่งซื้อ <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" v-model="po.po_date" required :disabled="isEdit">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">ผู้จัดซื้อ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="po.purchaser_name" required :disabled="isEdit" placeholder="ระบุชื่อ-สกุล">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">วันที่คาดว่าจะได้รับของ</label>
                      <input type="date" class="form-control" v-model="po.expected_date" :disabled="isEdit">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">หมายเหตุ</label>
                      <textarea class="form-control" v-model="po.note" rows="2" placeholder="รายละเอียดเพิ่มเติม..." :disabled="isEdit"></textarea>
                    </div>
                  </div>
                </div>

                <!-- Section 3: รายการวัสดุ -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2 text-danger">3. รายการวัสดุที่สั่งซื้อ</h5>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>รหัส</th>
                          <th>รายการวัสดุ</th>
                          <th>จำนวนที่ขอ (PR)</th>
                          <th>จำนวนสั่งซื้อจริง <span class="text-danger">*</span></th>
                          <th>หน่วยนับ</th>
                          <th>ราคาจริง/หน่วย <span class="text-danger">*</span></th>
                          <th>รวมเงิน</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="po.items.length === 0">
                          <td colspan="7" class="text-center py-4 text-muted">กรุณาเลือกใบขอซื้อ (PR) ก่อน</td>
                        </tr>
                        <tr v-for="(item, index) in po.items" :key="index">
                          <td>{{ item.item_code }}</td>
                          <td>{{ item.item_name }}</td>
                          <td class="text-secondary fw-bold">{{ item.pr_qty_requested || '-' }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.qty_ordered" min="1" required :disabled="isEdit">
                          </td>
                          <td>{{ item.unit_name }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.unit_price" min="0" step="0.01" required :disabled="isEdit">
                          </td>
                          <td class="text-end fw-bold">{{ (item.qty_ordered * item.unit_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td colspan="6" class="text-end fw-bold bg-light">ยอดรวมทั้งสิ้น :</td>
                          <td class="text-end fw-bold bg-light text-danger fs-5">{{ totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-5">
                  <button type="button" class="btn btn-light border px-4 rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-po')">
                    <i class="bi bi-arrow-left"></i> กลับหน้ารายการ
                  </button>
                  <button v-if="!isEdit" type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-save me-1" v-else></i> บันทึกใบสั่งซื้อ
                  </button>
                </div>
              </form>
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

export default {
  name: 'POForm',
  data() {
    return {
      po: {
        id: 0,
        pr_id: '',
        po_number: '',
        po_date: new Date().toISOString().split('T')[0],
        expected_date: '',
        purchaser_name: '',
        note: '',
        status: 'ORDERED',
        items: []
      },
      selectedPrId: '',
      availablePrs: [],
      saving: false,
      isEdit: false
    }
  },
  computed: {
    totalAmount() {
      return this.po.items.reduce((sum, item) => sum + (item.qty_ordered * item.unit_price), 0);
    },
    alertClass() {
      switch (this.po.status) {
        case 'ORDERED': return 'alert-primary';
        case 'PARTIAL_RECEIVED': return 'alert-warning';
        case 'COMPLETED': return 'alert-success';
        case 'CANCELLED': return 'alert-danger';
        default: return 'alert-secondary';
      }
    },
    alertIcon() {
      switch (this.po.status) {
        case 'ORDERED': return 'bi-truck';
        case 'PARTIAL_RECEIVED': return 'bi-box-seam';
        case 'COMPLETED': return 'bi-check-circle-fill';
        case 'CANCELLED': return 'bi-x-circle-fill';
        default: return 'bi-info-circle';
      }
    }
  },
  async mounted() {
    const poId = this.$route.query.id;
    if (poId) {
      this.isEdit = true;
      await this.fetchPO(poId);
    } else {
      await this.fetchApprovedPRs();
      await this.fetchUserProfile();
    }
  },
  methods: {
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data.status === 'success') {
          this.po.purchaser_name = res.data.fullname;
        }
      } catch (err) {}
    },
    async fetchApprovedPRs() {
      try {
        const res = await axios.get('/api-digital/inventory/pr_approved_list.php');
        if (res.data.status === 'success') {
          this.availablePrs = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async fetchPO(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/po_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const data = res.data.data;
          data.po_date = data.po_date ? data.po_date.split(' ')[0] : '';
          this.selectedPrId = data.pr_id; // Set drop down

          data.items.forEach(i => {
            i.qty_ordered = Number(i.qty_ordered);
            i.unit_price = Number(i.unit_price);
          });
          this.po = data;
        } else {
          Swal.fire('Error', 'ไม่พบใบสั่งซื้อนี้', 'error');
          this.$router.push('/inventory-po');
        }
      } catch (err) {
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
      }
    },
    async onPrChange() {
      if (!this.selectedPrId) {
        this.po.items = [];
        return;
      }
      try {
        const res = await axios.get(`/api-digital/inventory/pr_get.php?id=${this.selectedPrId}`);
        if (res.data.status === 'success') {
          const prData = res.data.data;
          this.po.pr_id = prData.id;
          
          // Map PR items to PO items
          this.po.items = prData.items.map(i => ({
            item_id: i.item_id,
            item_code: i.item_code,
            item_name: i.item_name,
            unit_name: i.unit_name,
            pr_qty_requested: Number(i.qty_requested),
            qty_ordered: Number(i.qty_requested), // Default to PR qty
            unit_price: Number(i.estimated_price) // Default to PR estimated price
          }));
        }
      } catch (err) {
        Swal.fire('Error', 'ไม่สามารถโหลดข้อมูล PR ได้', 'error');
      }
    },
    async savePO() {
      if (!this.po.pr_id || !this.po.purchaser_name) {
        Swal.fire('Warning', 'กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
      }
      if (this.po.items.length === 0) {
        Swal.fire('Warning', 'ไม่มีรายการวัสดุ', 'warning');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          pr_id: this.po.pr_id,
          po_date: this.po.po_date + ' 00:00:00',
          expected_date: this.po.expected_date ? this.po.expected_date : null,
          purchaser_name: this.po.purchaser_name,
          note: this.po.note,
          items: this.po.items
        };

        const res = await axios.post('/api-digital/inventory/po_save.php', payload);
        
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.$router.push('/inventory-po');
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
    getStatusText(status) {
      switch (status) {
        case 'ORDERED': return 'สั่งซื้อแล้ว (รอรับของ)';
        case 'PARTIAL_RECEIVED': return 'รับของบางส่วน';
        case 'COMPLETED': return 'รับของครบถ้วน';
        case 'CANCELLED': return 'ยกเลิก';
        default: return status;
      }
    }
  }
}
</script>
