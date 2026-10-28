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
              <li class="breadcrumb-item active" aria-current="page">รับเข้าคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-success mb-0">
            <i class="bi bi-box-arrow-in-down me-2"></i>บันทึกรับเข้าคลังวัสดุ (Stock In)
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
                
                <!-- Section 1: ข้อมูลบิล / ใบสั่งซื้อ -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">1. ข้อมูลบิล / ใบสั่งซื้อ (เอกสารอ้างอิง)</h5>
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select form-select-lg fw-bold text-primary" v-model="form.warehouse_id" @change="onWarehouseChange" required>
                        <option value="">-- กรุณาเลือกคลัง --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                      <input type="datetime-local" class="form-control form-control-lg" v-model="form.receipt_date" required />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">เลขที่บิล / ใบเสร็จ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-lg" v-model="form.receipt_no" required placeholder="เช่น RC-690001" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">เลขที่ Invoice</label>
                      <input type="text" class="form-control form-control-lg" v-model="form.invoice_no" placeholder="ถ้ามี" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">แหล่งที่มา / ผู้จำหน่าย</label>
                      <select class="form-select" v-model="form.vendor_id">
                        <option value="">-- เลือกผู้จำหน่าย --</option>
                        <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.vendor_name }}</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">นำเข้าจากใบสั่งซื้อ (PO)</label>
                      <select class="form-select" v-model="form.po_id" @change="onPOSelect">
                        <option value="">-- ไม่ระบุ (รับอิสระ) --</option>
                        <option v-for="po in activePOs" :key="po.id" :value="po.id">
                          {{ po.po_number }} ({{ po.warehouse_name }})
                        </option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <label class="form-label">หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.remark" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)" />
                    </div>
                  </div>
                </div>

                <!-- Section 2: เพิ่มรายการวัสดุ -->
                <div class="mb-4 bg-light p-3 rounded-3" :class="{'opacity-50 pointer-events-none': !form.warehouse_id}">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. ค้นหาและระบุรายการวัสดุเข้าคลัง</h5>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-3 position-relative">
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
                          [{{ m.item_code }}] {{ m.item_name }} <span class="badge bg-info text-dark" v-if="m.lot_control">Lot</span>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">เลข Lot <span class="text-danger" v-if="currentItem.lot_control">*</span></label>
                      <input type="text" class="form-control" v-model="currentItem.lot_number" placeholder="Lot Number" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">วันหมดอายุ (Expiry) <span class="text-danger" v-if="currentItem.expiry_control">*</span></label>
                      <input type="date" class="form-control" v-model="currentItem.expiry_date" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">ราคา/หน่วย (บ.)</label>
                      <input type="number" step="0.01" class="form-control" v-model.number="currentItem.unit_cost" min="0" placeholder="0.00" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">จำนวนที่รับเข้า</label>
                      <div class="input-group">
                        <input type="number" step="0.0001" class="form-control" v-model.number="currentItem.qty" min="0.0001" :disabled="!currentItem.item_id" />
                        <span class="input-group-text">{{ currentItem.unit_name || 'หน่วย' }}</span>
                      </div>
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-primary w-100" @click="addItem" :disabled="!currentItem.item_id || currentItem.qty <= 0">
                        เพิ่ม
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Section 3: รายการที่เลือก -->
                <div class="mb-4" v-if="selectedItems.length > 0">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">3. รายการวัสดุที่เตรียมรับเข้า</h5>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>ลำดับ</th>
                          <th>รหัสวัสดุ</th>
                          <th>ชื่อวัสดุ</th>
                          <th>Lot / Expiry</th>
                          <th class="text-end">ราคา/หน่วย</th>
                          <th class="text-end">จำนวนรับ</th>
                          <th>หน่วยนับ</th>
                          <th class="text-end">รวมเป็นเงิน</th>
                          <th class="text-center">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in selectedItems" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.item_code }}</td>
                          <td class="fw-bold">{{ item.item_name }}</td>
                          <td>
                            <span v-if="item.lot_number" class="badge bg-secondary me-1">Lot: {{ item.lot_number }}</span>
                            <span v-if="item.expiry_date" class="badge bg-warning text-dark">Exp: {{ item.expiry_date }}</span>
                            <span v-if="!item.lot_number && !item.expiry_date" class="text-muted">-</span>
                          </td>
                          <td class="text-end text-primary">{{ formatCurrency(item.unit_cost) }}</td>
                          <td class="text-end fw-bold text-success">{{ item.qty }}</td>
                          <td>{{ item.unit_name }}</td>
                          <td class="text-end fw-bold">{{ formatCurrency(item.unit_cost * item.qty) }}</td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr class="table-light fw-bold">
                          <td colspan="7" class="text-end">มูลค่ารวมทั้งสิ้น</td>
                          <td class="text-end text-danger fs-5">{{ formatCurrency(totalAmount) }} ฿</td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Submit -->
                <div class="d-flex justify-content-end mt-4 border-top pt-4">
                  <button type="button" class="btn btn-light rounded-pill px-4 me-2" @click="resetAll">ล้างข้อมูล</button>
                  <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold" :disabled="isSubmitting || selectedItems.length === 0">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span> ยืนยันบันทึกรับเข้าคลัง
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
  name: 'InventoryReceive',
  data() {
    return {
      warehouses: [],
      vendors: [],
      items: [],
      activePOs: [],
      selectedItems: [],
      searchQuery: '',
      isDropdownOpen: false,
      isSubmitting: false,
      
      form: {
        warehouse_id: '',
        receipt_date: moment().format('YYYY-MM-DDTHH:mm'),
        receipt_no: `RC-${moment().format('YYMMDDHHmmss')}`,
        invoice_no: '',
        vendor_id: '',
        remark: '',
        po_id: '',
        user_name: localStorage.getItem('user_name') || 'Admin'
      },
      
      currentItem: {
        item_id: '',
        item_code: '',
        item_name: '',
        lot_number: '',
        expiry_date: '',
        qty: 1,
        unit_id: '',
        unit_name: '',
        unit_cost: 0,
        lot_control: 0,
        expiry_control: 0
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
    },
    totalAmount() {
      return this.selectedItems.reduce((sum, item) => sum + (item.qty * item.unit_cost), 0);
    }
  },
  mounted() {
    this.fetchMasters();
  },
  methods: {
    formatCurrency(val) {
      return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    async fetchMasters() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const [whRes, poRes] = await Promise.all([
          axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`),
          axios.get('/api-digital/inventory/po_active_list.php')
        ]);
        if (whRes.data.status === 'success') {
          // Filter only active warehouses
          this.warehouses = whRes.data.data.filter(w => parseInt(w.active) === 1);
        }
        if (poRes.data.status === 'success') {
          this.activePOs = poRes.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async onPOSelect() {
      if (!this.form.po_id) {
        // Clear if deselected (maybe keep warehouse, but clear items)
        this.selectedItems = [];
        return;
      }
      try {
        const res = await axios.get(`/api-digital/inventory/po_get.php?id=${this.form.po_id}`);
        if (res.data.status === 'success') {
          const poData = res.data.data;
          this.form.warehouse_id = poData.warehouse_id;
          
          // Clear current selected and populate from PO
          this.selectedItems = [];
          
          poData.items.forEach(i => {
            const qtyOrdered = Number(i.qty_ordered);
            const qtyReceived = Number(i.qty_received || 0);
            const qtyRemaining = qtyOrdered - qtyReceived;
            
            if (qtyRemaining > 0) {
              this.selectedItems.push({
                item_id: i.item_id,
                item_code: i.item_code,
                item_name: i.item_name,
                unit_id: i.unit_id,
                unit_name: i.unit_name,
                qty: qtyRemaining, // Suggest remaining
                unit_cost: Number(i.unit_price),
                lot_number: '',
                expiry_date: '',
                lot_control: 0, // In real scenario, we might need to fetch item control configs
                expiry_control: 0
              });
            }
          });
          
          if (this.selectedItems.length === 0) {
            Swal.fire('Info', 'PO นี้รับวัสดุครบหมดแล้ว', 'info');
          } else {
            Swal.fire('Success', 'ดึงข้อมูลรายการวัสดุจาก PO สำเร็จ กรุณาระบุ Lot/Expiry (ถ้ามี)', 'success');
            // Fetch items and vendors for this warehouse
            this.fetchItems();
            this.fetchVendors();
          }
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'ไม่สามารถดึงข้อมูล PO ได้', 'error');
      }
    },
    async onWarehouseChange() {
      await Promise.all([
        this.fetchItems(),
        this.fetchVendors()
      ]);
    },
    async fetchVendors() {
      this.vendors = [];
      if (!this.form.warehouse_id) return;
      try {
        const res = await axios.get(`/api-digital/inventory/vendors.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data?.status === 'success') {
          this.vendors = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching vendors in Receive:', err);
      }
    },
    async fetchItems() {
      // Fetch items only for the selected warehouse
      this.items = [];
      this.searchQuery = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      if (!this.form.warehouse_id) return;
      
      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data.filter(i => parseInt(i.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    selectItem(m) {
      this.currentItem.item_id = m.id;
      this.currentItem.item_code = m.item_code;
      this.currentItem.item_name = m.item_name;
      this.currentItem.unit_id = m.unit_id;
      this.currentItem.unit_name = m.unit_name;
      this.currentItem.unit_cost = parseFloat(m.standard_price) || 0;
      this.currentItem.lot_control = m.lot_control;
      this.currentItem.expiry_control = m.expiry_control;
      
      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
    },
    addItem() {
      if (!this.currentItem.item_id || this.currentItem.qty <= 0) return;
      
      // Control Checks
      if (this.currentItem.lot_control && !this.currentItem.lot_number) {
        return Swal.fire('ข้อมูลไม่ครบ', 'วัสดุนี้จำเป็นต้องระบุเลข Lot', 'warning');
      }
      if (this.currentItem.expiry_control && !this.currentItem.expiry_date) {
        return Swal.fire('ข้อมูลไม่ครบ', 'วัสดุนี้จำเป็นต้องระบุวันหมดอายุ', 'warning');
      }

      this.selectedItems.push({ ...this.currentItem });
      this.resetCurrentItem();
      this.searchQuery = '';
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '', item_code: '', item_name: '', lot_number: '', expiry_date: '',
        qty: 1, unit_id: '', unit_name: '', unit_cost: 0, lot_control: 0, expiry_control: 0
      };
    },
    resetAll() {
      this.form.receipt_no = `RC-${moment().format('YYMMDDHHmmss')}`;
      this.form.invoice_no = '';
      this.form.vendor_id = '';
      this.form.po_id = '';
      this.form.remark = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      this.searchQuery = '';
    },
    async submitTx() {
      if (this.selectedItems.length === 0) return;
      
      this.isSubmitting = true;
      try {
        const payload = {
          warehouse_id: this.form.warehouse_id,
          receipt_date: this.form.receipt_date,
          receipt_no: this.form.receipt_no,
          invoice_no: this.form.invoice_no,
          vendor_id: this.form.vendor_id,
          remark: this.form.remark,
          po_id: this.form.po_id,
          user_name: this.form.user_name,
          items: this.selectedItems
        };

        const res = await axios.post('/api-digital/inventory/receive.php', payload);
        if (res.data.status === 'success') {
          Swal.fire('บันทึกสำเร็จ!', 'รับเข้าวัสดุเรียบร้อยแล้ว ยอดคงเหลือถูกอัปเดตอัตโนมัติ', 'success').then(() => {
            this.resetAll();
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบขัดข้องระหว่างบันทึก', 'error');
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
