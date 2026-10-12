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
              <li class="breadcrumb-item active" aria-current="page">เบิกจ่ายคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-box-arrow-right me-2"></i>บันทึกเบิกจ่ายคลังวัสดุ (Stock Issue / FEFO)
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
          <button class="btn btn-primary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-grid-fill me-2"></i>Dashboard
          </button>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitTx">
                
                <!-- Section 1: ข้อมูลใบเบิก -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2 text-danger">1. ข้อมูลใบเบิก (เอกสารอ้างอิง)</h5>
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select form-select-lg fw-bold text-danger" v-model="form.warehouse_id" @change="fetchItems" required>
                        <option value="">-- กรุณาเลือกคลัง --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">วันที่เบิกจ่าย <span class="text-danger">*</span></label>
                      <input type="datetime-local" class="form-control form-control-lg" v-model="form.issue_date" required />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">เลขที่ใบเบิก <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-lg" v-model="form.issue_no" required placeholder="เช่น IS-690001" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">หน่วยงานที่เบิก</label>
                      <select class="form-select" v-model="form.department_id" @change="updateDeptName">
                        <option value="">-- เลือกหน่วยงาน --</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">ชื่อผู้ขอเบิก</label>
                      <input type="text" class="form-control" v-model="form.requester_name" placeholder="ชื่อผู้มารับของ" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.remark" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)" />
                    </div>
                  </div>
                </div>

                <!-- Section 2: ค้นหาและระบุรายการวัสดุ -->
                <div class="mb-4 bg-light p-3 rounded-3 border" :class="{'opacity-50 pointer-events-none': !form.warehouse_id}">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. ค้นหาและระบุรายการวัสดุ (เลือกตาม Lot)</h5>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-4 position-relative">
                      <label class="form-label mb-1">ค้นหารหัส/ชื่อวัสดุ</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="searchQuery" 
                        @focus="isDropdownOpen = true"
                        placeholder="พิมพ์เพื่อค้นหา..."
                        :disabled="!form.warehouse_id"
                      />
                      <div 
                        v-if="isDropdownOpen && form.warehouse_id" 
                        class="position-absolute w-100 bg-white border rounded shadow mt-1" 
                        style="max-height: 250px; overflow-y: auto; z-index: 1050;"
                      >
                        <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                          <button type="button" class="btn btn-sm btn-outline-secondary" @click="isDropdownOpen = false">ปิดหน้าต่าง</button>
                        </div>
                        <div v-if="filteredItems.length === 0" class="p-2 text-muted text-center">ไม่พบข้อมูลในคลังนี้</div>
                        <div 
                          v-for="m in filteredItems" 
                          :key="m.id" 
                          class="p-2 border-bottom dropdown-item-custom"
                          @click="selectItem(m)"
                        >
                          [{{ m.item_code }}] {{ m.item_name }} 
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3">
                      <label class="form-label mb-1">เลือกล็อต (เรียงตาม FEFO) <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="currentItem.lot_id" :disabled="!currentItem.item_id || isLoadingLots">
                        <option value="">-- เลือก Lot --</option>
                        <option v-for="l in availableLots" :key="l.lot_id" :value="l.lot_id">
                          Lot: {{ l.lot_number || 'N/A' }} 
                          (Exp: {{ l.expiry_date || 'N/A' }}) - มี: {{ l.balance_qty }}
                        </option>
                      </select>
                    </div>

                    <div class="col-md-2">
                      <label class="form-label mb-1">จำนวนจ่าย</label>
                      <div class="input-group">
                        <input type="number" step="0.0001" class="form-control" v-model.number="currentItem.qty" min="0.0001" :disabled="!currentItem.lot_id" />
                        <span class="input-group-text">{{ currentItem.unit_name || 'หน่วย' }}</span>
                      </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                      <button type="button" class="btn btn-danger w-100 fw-bold" @click="addItem" :disabled="!currentItem.lot_id || currentItem.qty <= 0">
                        <i class="bi bi-plus-lg"></i> ตัดจ่าย
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Section 3: รายการที่เลือก -->
                <div class="mb-4" v-if="selectedItems.length > 0">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">3. รายการวัสดุที่เตรียมเบิกจ่าย</h5>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>ลำดับ</th>
                          <th>รหัสวัสดุ</th>
                          <th>ชื่อวัสดุ</th>
                          <th>Lot / Expiry</th>
                          <th class="text-end">จำนวนเบิก</th>
                          <th>หน่วยนับ</th>
                          <th class="text-center">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in selectedItems" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.item_code }}</td>
                          <td class="fw-bold">{{ item.item_name }}</td>
                          <td>
                            <span class="badge bg-secondary me-1">Lot: {{ item.lot_number || '-' }}</span>
                            <span class="badge bg-warning text-dark">Exp: {{ item.expiry_date || '-' }}</span>
                          </td>
                          <td class="text-end fw-bold text-danger">{{ item.qty }}</td>
                          <td>{{ item.unit_name }}</td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Submit -->
                <div class="d-flex justify-content-end mt-4 border-top pt-4">
                  <button type="button" class="btn btn-light rounded-pill px-4 me-2" @click="resetAll">ล้างข้อมูล</button>
                  <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold" :disabled="isSubmitting || selectedItems.length === 0">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span> ยืนยันบันทึกใบเบิกจ่าย
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
import moment from 'moment';
import Swal from 'sweetalert2';

export default {
  name: 'InventoryIssue',
  data() {
    return {
      warehouses: [],
      departments: [],
      items: [],
      availableLots: [],
      selectedItems: [],
      searchQuery: '',
      isDropdownOpen: false,
      isSubmitting: false,
      isLoadingLots: false,
      
      form: {
        warehouse_id: '',
        issue_date: moment().format('YYYY-MM-DDTHH:mm'),
        issue_no: `IS-${moment().format('YYMMDDHHmmss')}`,
        department_id: '',
        department_name: '',
        requester_name: '',
        remark: '',
        user_name: localStorage.getItem('user_name') || 'Admin'
      },
      
      currentItem: {
        item_id: '', item_code: '', item_name: '',
        lot_id: '', lot_number: '', expiry_date: '', max_qty: 0,
        qty: 1, unit_id: '', unit_name: ''
      }
    };
  },
  computed: {
    filteredItems() {
      if (!this.searchQuery) return this.items;
      const q = this.searchQuery.toLowerCase();
      return this.items.filter(m => 
        m.item_name.toLowerCase().includes(q) || 
        m.item_code.toLowerCase().includes(q)
      );
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
        }
        
        // Mock departments or fetch from actual API if exists
        const deptRes = await axios.get('/api-hosoffice/departments.php').catch(() => null);
        if (deptRes && deptRes.data.status === 'success') {
           this.departments = deptRes.data.data;
        } else {
           this.departments = [
             { id: 1, name: 'ห้องตรวจ 1' },
             { id: 2, name: 'ห้องตรวจ 2' },
             { id: 3, name: 'แผนกศัลยกรรม' },
             { id: 4, name: 'ฝ่ายบริหาร' }
           ];
        }
      } catch (err) {
        console.error(err);
      }
    },
    updateDeptName() {
      const d = this.departments.find(x => x.id === this.form.department_id);
      this.form.department_name = d ? d.name : '';
    },
    async fetchItems() {
      this.items = [];
      this.searchQuery = '';
      this.selectedItems = [];
      this.resetCurrentItem();
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
    async selectItem(m) {
      this.resetCurrentItem();
      this.currentItem.item_id = m.id;
      this.currentItem.item_code = m.item_code;
      this.currentItem.item_name = m.item_name;
      this.currentItem.unit_id = m.unit_id;
      this.currentItem.unit_name = m.unit_name;
      
      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
      this.isLoadingLots = true;

      // Fetch lots based on item FEFO
      try {
        const res = await axios.get(`/api-digital/inventory/get_lots.php?item_id=${m.id}&warehouse_id=${this.form.warehouse_id}`);
        if (res.data.status === 'success') {
          this.availableLots = res.data.data;
          if (this.availableLots.length > 0) {
             // Auto-select the first lot (which is the soonest to expire FEFO)
             this.currentItem.lot_id = this.availableLots[0].lot_id;
          } else {
             Swal.fire('สินค้าหมด', 'ไม่มีสินค้านี้ในสต๊อก (ยอดคงเหลือ 0)', 'warning');
          }
        }
      } catch(err) {
        console.error(err);
      } finally {
        this.isLoadingLots = false;
      }
    },
    addItem() {
      if (!this.currentItem.lot_id || this.currentItem.qty <= 0) return;
      
      const selectedLot = this.availableLots.find(l => l.lot_id === this.currentItem.lot_id);
      if (!selectedLot) return;

      if (this.currentItem.qty > selectedLot.balance_qty) {
        return Swal.fire('ยอดไม่พอ', `Lot นี้มีของเหลือแค่ ${selectedLot.balance_qty} เท่านั้น`, 'warning');
      }

      this.selectedItems.push({ 
        ...this.currentItem,
        lot_number: selectedLot.lot_number,
        expiry_date: selectedLot.expiry_date
      });
      
      this.resetCurrentItem();
      this.searchQuery = '';
      this.availableLots = [];
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '', item_code: '', item_name: '', lot_id: '', lot_number: '', expiry_date: '',
        max_qty: 0, qty: 1, unit_id: '', unit_name: ''
      };
    },
    resetAll() {
      this.form.issue_no = `IS-${moment().format('YYMMDDHHmmss')}`;
      this.form.department_id = '';
      this.form.department_name = '';
      this.form.requester_name = '';
      this.form.remark = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      this.searchQuery = '';
      this.availableLots = [];
    },
    async submitTx() {
      if (this.selectedItems.length === 0) return;
      
      this.isSubmitting = true;
      try {
        const payload = {
          warehouse_id: this.form.warehouse_id,
          issue_date: this.form.issue_date,
          issue_no: this.form.issue_no,
          department_id: this.form.department_id,
          department_name: this.form.department_name,
          requester_name: this.form.requester_name,
          remark: this.form.remark,
          user_name: this.form.user_name,
          items: this.selectedItems
        };

        const res = await axios.post('/api-digital/inventory/issue.php', payload);
        if (res.data.status === 'success') {
          Swal.fire('บันทึกเบิกจ่ายสำเร็จ!', 'ระบบตัดสต๊อกเรียบร้อยแล้ว', 'success').then(() => {
            this.resetAll();
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบขัดข้อง หรือมีบางรายการสต๊อกไม่เพียงพอ', 'error');
      } finally {
        this.isSubmitting = false;
      }
    }
  }
};
</script>

<style scoped>
.dropdown-item-custom {
  cursor: pointer;
}
.dropdown-item-custom:hover {
  background-color: #f8f9fa;
}
.pointer-events-none {
  pointer-events: none;
}
</style>
