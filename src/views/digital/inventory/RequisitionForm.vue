<template>
  <div class="requisition-form-container">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="javascript:void(0)" @click="$emit('back-to-dashboard')">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="javascript:void(0)" @click="$emit('go-to-manage')">คำขอเบิกและอนุมัติจ่าย</a></li>
            <li class="breadcrumb-item active">สร้างใบขอเบิกใหม่</li>
          </ol>
        </nav>
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-cart-plus-fill text-warning"></i>
          สร้างใบขอเบิกพัสดุ (Requisition Form)
        </h3>
        <small class="text-muted">บันทึกส่งคำขอเบิกพัสดุจากหน่วยงาน เพื่อเสนอเจ้าหน้าที่คลังพิจารณาอนุมัติจ่าย</small>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" @click="$emit('go-to-manage')">
          <i class="bi bi-list-ul me-1"></i> รายการคำขอเบิกทั้งหมด
        </button>
      </div>
    </div>

    <!-- Main Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4 p-md-5">
        <form @submit.prevent="submitRequisition">
          
          <!-- Section 1: ข้อมูลผู้ขอเบิกและคลัง -->
          <div class="mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-warning-emphasis d-flex align-items-center gap-2">
              <span class="badge bg-warning text-dark rounded-circle px-2">1</span>
              ข้อมูลผู้ขอเบิกและหน่วยงาน
            </h5>
            
            <div class="row g-3">
              <!-- คลังพัสดุปลายทาง -->
              <div class="col-md-4">
                <label class="form-label fw-bold">คลังที่ต้องการเบิก <span class="text-danger">*</span></label>
                <select class="form-select form-select-lg fw-bold text-primary" v-model="form.warehouse_id" @change="onWarehouseChange" required>
                  <option value="">-- กรุณาเลือกคลัง --</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                </select>
              </div>

              <!-- หน่วยงานที่ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold">หน่วยงาน / แผนกที่ขอเบิก <span class="text-danger">*</span></label>
                <select class="form-select form-select-lg" v-model="form.department_id" @change="updateDepartmentName" required>
                  <option value="">-- เลือกหน่วยงาน --</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select>
              </div>

              <!-- วันที่ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold">วันที่ต้องการขอเบิก</label>
                <input type="datetime-local" class="form-control form-control-lg" v-model="form.request_date" required />
              </div>

              <!-- ชื่อผู้ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold">ชื่อ-นามสกุล ผู้ขอเบิก <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="form.requester_name" placeholder="เช่น นายสมชาย ใจดี" required />
              </div>

              <!-- เบอร์โทรศัพท์ -->
              <div class="col-md-4">
                <label class="form-label fw-bold">เบอร์โทรติดต่อ / ภายใน</label>
                <input type="text" class="form-control" v-model="form.requester_phone" placeholder="เช่น 102 หรือ 081-xxx-xxxx" />
              </div>

              <!-- วัตถุประสงค์ -->
              <div class="col-md-4">
                <label class="form-label fw-bold">วัตถุประสงค์การนำไปใช้งาน</label>
                <input type="text" class="form-control" v-model="form.purpose" placeholder="เช่น ใช้งานประจำห้องตรวจ / ทดแทนของชำรุด" />
              </div>
            </div>
          </div>

          <!-- Section 2: ค้นหาและระบุรายการพัสดุ -->
          <div class="mb-4 bg-light p-4 rounded-4 border" :class="{'opacity-50 pointer-events-none': !form.warehouse_id}">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark d-flex align-items-center gap-2">
              <span class="badge bg-secondary text-white rounded-circle px-2">2</span>
              ค้นหาและระบุรายการพัสดุ
            </h5>

            <div class="row g-2 align-items-end">
              <!-- ช่องค้นหาพัสดุ -->
              <div class="col-md-5 position-relative">
                <label class="form-label mb-1 fw-bold small">ค้นหารหัสหรือชื่อพัสดุ</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="searchQuery" 
                  @focus="isDropdownOpen = true"
                  placeholder="พิมพ์ชื่อหรือรหัสพัสดุเพื่อค้นหา..."
                  :disabled="!form.warehouse_id"
                />
                
                <!-- Autocomplete Dropdown List -->
                <div 
                  v-if="isDropdownOpen && form.warehouse_id" 
                  class="position-absolute w-100 bg-white border rounded-3 shadow mt-1" 
                  style="max-height: 260px; overflow-y: auto; z-index: 1050;"
                >
                  <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                    <span class="small fw-bold text-muted">พบ {{ filteredItems.length }} รายการ</span>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" @click="isDropdownOpen = false">ปิด</button>
                  </div>
                  <div v-if="filteredItems.length === 0" class="p-3 text-muted text-center small">
                    ไม่พบรายการพัสดุในคลังนี้
                  </div>
                  <div 
                    v-for="m in filteredItems" 
                    :key="m.id" 
                    class="p-2 px-3 border-bottom item-select-row d-flex justify-content-between align-items-center"
                    @click="selectItem(m)"
                  >
                    <div>
                      <span class="fw-bold text-primary font-monospace">[{{ m.item_code }}]</span> 
                      <span class="ms-1">{{ m.item_name }}</span>
                    </div>
                    <span class="badge rounded-pill" :class="m.on_hand > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'">
                      คงเหลือ: {{ m.on_hand }} {{ m.unit_name }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- จำนวนคงเหลือปัจจุบันในคลัง -->
              <div class="col-md-2">
                <label class="form-label mb-1 fw-bold small">คงเหลือในคลัง</label>
                <input 
                  type="text" 
                  class="form-control bg-white" 
                  :value="currentItem.item_id ? `${currentItem.on_hand} ${currentItem.unit_name}` : '-'" 
                  readonly 
                  disabled
                />
              </div>

              <!-- จำนวนที่ต้องการขอเบิก -->
              <div class="col-md-2">
                <label class="form-label mb-1 fw-bold small">จำนวนที่ขอเบิก <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    class="form-control fw-bold" 
                    v-model.number="currentItem.qty" 
                    :disabled="!currentItem.item_id"
                  />
                  <span class="input-group-text small">{{ currentItem.unit_name || 'หน่วย' }}</span>
                </div>
              </div>

              <!-- หมายเหตุรายการ -->
              <div class="col-md-2">
                <label class="form-label mb-1 fw-bold small">หมายเหตุ</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="currentItem.item_note" 
                  placeholder="ระบุเพิ่มเติม..." 
                  :disabled="!currentItem.item_id"
                />
              </div>

              <!-- ปุ่มเพิ่มรายการ -->
              <div class="col-md-1 d-flex align-items-end">
                <button 
                  type="button" 
                  class="btn btn-warning text-dark w-100 fw-bold" 
                  @click="addItem" 
                  :disabled="!currentItem.item_id || currentItem.qty <= 0"
                >
                  <i class="bi bi-plus-lg"></i> เพิ่ม
                </button>
              </div>
            </div>
          </div>

          <!-- Section 3: รายการพัสดุที่เลือกเตรียมส่งคำขอ -->
          <div class="mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-dark d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary text-white rounded-circle px-2">3</span>
                รายการพัสดุในใบขอเบิก
              </div>
              <span class="fs-6 text-muted fw-normal">ทั้งหมด {{ selectedItems.length }} รายการ</span>
            </h5>

            <div v-if="selectedItems.length === 0" class="text-center py-5 bg-light rounded-3 text-muted">
              <i class="bi bi-basket3 fs-1 d-block mb-2 text-secondary"></i>
              <div class="fw-bold">ยังไม่มีรายการพัสดุ</div>
              <small>กรุณาค้นหาและกดปุ่ม "+ เพิ่ม" รายการพัสดุด้านบน</small>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light small">
                  <tr>
                    <th style="width: 50px;" class="text-center">ลำดับ</th>
                    <th style="width: 140px;">รหัสพัสดุ</th>
                    <th>ชื่อรายการพัสดุ</th>
                    <th class="text-center" style="width: 130px;">คงเหลือในคลัง</th>
                    <th class="text-center" style="width: 140px;">จำนวนที่ขอเบิก</th>
                    <th style="width: 100px;">หน่วยนับ</th>
                    <th>หมายเหตุ</th>
                    <th class="text-center" style="width: 70px;">ลบ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in selectedItems" :key="index">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td class="fw-bold font-monospace text-primary">{{ item.item_code }}</td>
                    <td class="fw-bold">{{ item.item_name }}</td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border">{{ item.on_hand }}</span>
                    </td>
                    <td class="text-center">
                      <input 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        class="form-control form-control-sm text-center fw-bold text-primary mx-auto" 
                        style="max-width: 100px;" 
                        v-model.number="item.qty" 
                      />
                    </td>
                    <td>{{ item.unit_name }}</td>
                    <td>
                      <input type="text" class="form-control form-control-sm" v-model="item.item_note" placeholder="หมายเหตุ" />
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" @click="removeItem(index)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Submit Bar -->
          <div class="d-flex justify-content-between align-items-center mt-5 border-top pt-4">
            <button type="button" class="btn btn-light rounded-pill px-4" @click="resetForm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างข้อมูล
            </button>
            <button 
              type="submit" 
              class="btn btn-warning text-dark rounded-pill px-5 py-2 fw-bold shadow" 
              :disabled="submitting || selectedItems.length === 0"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-send-fill me-2"></i> ยืนยันส่งใบขอเบิกพัสดุ
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';

export default {
  name: 'RequisitionForm',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      warehouses: [],
      departments: [],
      items: [],
      selectedItems: [],
      searchQuery: '',
      isDropdownOpen: false,
      submitting: false,

      form: {
        warehouse_id: '',
        department_id: '',
        department_name: '',
        request_date: moment().format('YYYY-MM-DDTHH:mm'),
        requester_name: localStorage.getItem('user_name') || '',
        requester_phone: '',
        purpose: ''
      },

      currentItem: {
        item_id: '',
        item_code: '',
        item_name: '',
        unit_name: '',
        on_hand: 0,
        qty: 1,
        item_note: ''
      }
    };
  },
  computed: {
    filteredItems() {
      if (!this.searchQuery) return this.items;
      const q = this.searchQuery.toLowerCase();
      return this.items.filter(m => 
        (m.item_name && m.item_name.toLowerCase().includes(q)) || 
        (m.item_code && m.item_code.toLowerCase().includes(q))
      );
    }
  },
  watch: {
    warehouseId(newVal) {
      if (newVal) {
        this.form.warehouse_id = parseInt(newVal);
        this.fetchItems();
      }
    }
  },
  mounted() {
    this.fetchMasters();
  },
  methods: {
    async fetchMasters() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const whRes = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`);
        if (whRes.data.status === 'success') {
          this.warehouses = whRes.data.data.filter(w => w.active === 1);
          const targetWh = this.warehouseId || localStorage.getItem('selected_warehouse_id');
          if (targetWh) {
            this.form.warehouse_id = parseInt(targetWh);
            this.fetchItems();
          }
        }

        // Fetch departments
        const deptRes = await axios.get('/api-hosoffice/departments.php').catch(() => null);
        if (deptRes && deptRes.data.status === 'success') {
          this.departments = deptRes.data.data;
        } else {
          this.departments = [
            { id: 1, name: 'ห้องตรวจ 1 (OPD)' },
            { id: 2, name: 'ห้องตรวจ 2 (OPD)' },
            { id: 3, name: 'แผนกศัลยกรรม (OR)' },
            { id: 4, name: 'ฝ่ายบริหารงานทั่วไป' },
            { id: 5, name: 'กลุ่มงานประกันสุขภาพ' },
            { id: 6, name: 'กลุ่มงานเทคโนโลยีสารสนเทศ (IT)' },
            { id: 7, name: 'แผนกเภสัชกรรม' }
          ];
        }
      } catch (err) {
        console.error(err);
      }
    },
    updateDepartmentName() {
      const d = this.departments.find(x => x.id === this.form.department_id);
      this.form.department_name = d ? d.name : '';
    },
    onWarehouseChange() {
      this.selectedItems = [];
      this.resetCurrentItem();
      this.fetchItems();
    },
    async fetchItems() {
      this.items = [];
      this.searchQuery = '';
      if (!this.form.warehouse_id) return;

      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data.filter(i => i.active === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    selectItem(m) {
      this.currentItem.item_id = m.id;
      this.currentItem.item_code = m.item_code;
      this.currentItem.item_name = m.item_name;
      this.currentItem.unit_name = m.unit_name || 'หน่วย';
      this.currentItem.on_hand = m.on_hand || 0;
      this.currentItem.qty = 1;
      this.currentItem.item_note = '';

      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
    },
    addItem() {
      if (!this.currentItem.item_id || this.currentItem.qty <= 0) return;

      // Check if already in selected items
      const existing = this.selectedItems.find(x => x.item_id === this.currentItem.item_id);
      if (existing) {
        existing.qty += this.currentItem.qty;
      } else {
        this.selectedItems.push({ ...this.currentItem });
      }

      this.resetCurrentItem();
      this.searchQuery = '';
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '',
        item_code: '',
        item_name: '',
        unit_name: '',
        on_hand: 0,
        qty: 1,
        item_note: ''
      };
    },
    resetForm() {
      this.selectedItems = [];
      this.resetCurrentItem();
      this.searchQuery = '';
      this.form.purpose = '';
    },
    async submitRequisition() {
      if (this.selectedItems.length === 0) {
        return Swal.fire('เตือน', 'กรุณาระบุรายการพัสดุอย่างน้อย 1 รายการ', 'warning');
      }

      const confirm = await Swal.fire({
        title: 'ยืนยันการส่งใบขอเบิกพัสดุ?',
        html: `ต้องการส่งคำขอเบิกพัสดุจำนวน <strong>${this.selectedItems.length}</strong> รายการ ไปยังคลังวัสดุใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: '<span class="text-dark fw-bold">ยืนยันส่งคำขอ</span>',
        cancelButtonText: 'ยกเลิก'
      });

      if (!confirm.isConfirmed) return;

      this.submitting = true;
      try {
        const payload = {
          warehouse_id: this.form.warehouse_id,
          department_id: this.form.department_id,
          department_name: this.form.department_name,
          request_date: this.form.request_date,
          requester_name: this.form.requester_name,
          requester_phone: this.form.requester_phone,
          purpose: this.form.purpose,
          items: this.selectedItems.map(it => ({
            item_id: it.item_id,
            item_code: it.item_code,
            item_name: it.item_name,
            unit_name: it.unit_name,
            qty_requested: it.qty,
            item_note: it.item_note
          }))
        };

        const res = await axios.post('/api-digital/inventory/requisition_create.php', payload);
        if (res.data.status === 'success') {
          const result = await Swal.fire({
            icon: 'success',
            title: 'ส่งใบขอเบิกสำเร็จ!',
            html: `ระบบได้บันทึกคำขอเลขที่ <strong class="text-primary font-monospace">${res.data.req_no}</strong> เรียบร้อยแล้ว (สถานะ: รอพิจารณาอนุมัติ)`,
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<span class="text-dark fw-bold">ดูรายการคำขอเบิก</span>',
            cancelButtonText: 'สร้างใบขอเบิกใหม่'
          });

          if (result.isConfirmed) {
            this.$emit('go-to-manage');
          } else {
            this.resetForm();
          }
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง', 'error');
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>

<style scoped>
.item-select-row {
  cursor: pointer;
  transition: background-color 0.15s;
}
.item-select-row:hover {
  background-color: #f8f9fa;
}
.pointer-events-none {
  pointer-events: none;
}
</style>

