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
              <li class="breadcrumb-item"><router-link to="/inventory-transfer">โอนย้ายวัสดุ</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">{{ isEdit ? transfer.transfer_no : 'สร้างใบโอนย้าย' }}</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-secondary mb-0">
            <i class="bi bi-arrow-left-right me-2"></i>{{ isEdit ? 'ใบโอนย้าย: ' + transfer.transfer_no : 'สร้างใบโอนย้าย (Transfer)' }}
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-transfer')">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              
              <div v-if="isEdit" class="alert alert-success mb-4 d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                <div>
                  <h6 class="fw-bold mb-0">สถานะ: COMPLETED (โอนย้ายสำเร็จ)</h6>
                </div>
              </div>

              <form @submit.prevent="saveTransfer">
                <!-- Section 1: ข้อมูลคลัง -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">1. ระบุคลังต้นทางและปลายทาง</h5>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">โอนจากคลัง (Source) <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="transfer.from_warehouse_id" required :disabled="isEdit || selectedItems.length > 0" @change="fetchItems">
                        <option value="">-- เลือกคลังต้นทาง --</option>
                        <option v-for="wh in activeWarehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                      </select>
                      <small class="text-muted" v-if="selectedItems.length > 0">ไม่สามารถเปลี่ยนคลังต้นทางได้หากมีรายการแล้ว</small>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">โอนไปคลัง (Destination) <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="transfer.to_warehouse_id" required :disabled="isEdit">
                        <option value="">-- เลือกคลังปลายทาง --</option>
                        <option v-for="wh in activeWarehouses" :key="'to'+wh.id" :value="wh.id" :disabled="wh.id === transfer.from_warehouse_id">{{ wh.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">วันที่โอนย้าย <span class="text-danger">*</span></label>
                      <input type="datetime-local" class="form-control" v-model="transfer.transfer_date" required :disabled="isEdit">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">หมายเหตุ / เหตุผลการโอน</label>
                      <input type="text" class="form-control" v-model="transfer.remark" placeholder="ตัวอย่าง: กระจายของให้คลังย่อย" :disabled="isEdit">
                    </div>
                  </div>
                </div>

                <!-- Section 2: เลือกวัสดุ -->
                <div class="mb-4 bg-light p-3 rounded-3" :class="{'opacity-50 pointer-events-none': !transfer.from_warehouse_id || isEdit}">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. ค้นหาและระบุรายการวัสดุที่จะโอน</h5>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-3 position-relative">
                      <label class="form-label mb-1">ค้นหารหัส/ชื่อวัสดุ (จากคลังต้นทาง)</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="searchQuery" 
                        @focus="isDropdownOpen = true"
                        placeholder="พิมพ์เพื่อค้นหา..."
                        :disabled="!transfer.from_warehouse_id || isEdit"
                      />
                      <div 
                        v-if="isDropdownOpen && transfer.from_warehouse_id && !isEdit" 
                        class="position-absolute w-100 bg-white border rounded shadow mt-1" 
                        style="max-height: 250px; overflow-y: auto; z-index: 1050;"
                      >
                        <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                          <button type="button" class="btn btn-sm btn-outline-secondary" @click="isDropdownOpen = false">ปิดหน้าต่าง</button>
                        </div>
                        <div v-if="filteredItems.length === 0" class="p-2 text-muted text-center">ไม่พบข้อมูลในคลังต้นทาง</div>
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
                    <div class="col-md-4">
                      <label class="form-label mb-1">เลือก Lot (เรียงตาม FEFO)</label>
                      <select class="form-select" v-model="currentItem.lot_id" :disabled="!currentItem.item_id || isLoadingLots || isEdit">
                        <option value="">-- เลือก Lot --</option>
                        <option v-for="l in availableLots" :key="l.lot_id" :value="l.lot_id">
                          Lot: {{ l.lot_number || '-' }} | Exp: {{ l.expiry_date || '-' }} | คงเหลือ: {{ l.balance_qty }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">จำนวนโอน</label>
                      <div class="input-group">
                        <input type="number" class="form-control text-center fw-bold" v-model.number="currentItem.qty" min="0.01" step="0.01" :disabled="!currentItem.item_id || isEdit">
                        <span class="input-group-text">{{ currentItem.unit_name || 'หน่วย' }}</span>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <button type="button" class="btn btn-primary w-100" @click="addItem" :disabled="!currentItem.item_id || !currentItem.lot_id || currentItem.qty <= 0 || isEdit">
                        เพิ่มรายการ
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Section 3: รายการที่เลือก -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">3. รายการวัสดุที่จะโอนย้าย</h5>
                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>ลำดับ</th>
                          <th>รหัสวัสดุ</th>
                          <th>ชื่อวัสดุ</th>
                          <th>Lot / Expiry</th>
                          <th class="text-end">จำนวนโอน</th>
                          <th>หน่วยนับ</th>
                          <th class="text-center" v-if="!isEdit">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="selectedItems.length === 0">
                          <td :colspan="isEdit ? 6 : 7" class="text-center py-4 text-muted">ยังไม่มีรายการ</td>
                        </tr>
                        <tr v-for="(item, index) in selectedItems" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.item_code }}</td>
                          <td class="fw-bold">{{ item.item_name }}</td>
                          <td>
                            <span class="badge bg-secondary me-1">Lot: {{ item.lot_number || '-' }}</span>
                            <span v-if="item.expiry_date" class="badge bg-warning text-dark">Exp: {{ item.expiry_date }}</span>
                          </td>
                          <td class="text-end fw-bold text-primary">{{ item.qty }}</td>
                          <td>{{ item.unit_name }}</td>
                          <td class="text-center" v-if="!isEdit">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-5" v-if="!isEdit">
                  <button type="button" class="btn btn-light border px-4 rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-transfer')">
                    ยกเลิก
                  </button>
                  <button type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" :disabled="saving || selectedItems.length === 0">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-save me-1" v-else></i> ยืนยันการโอนย้าย
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
  name: 'TransferForm',
  data() {
    return {
      activeWarehouses: [],
      items: [],
      availableLots: [],
      selectedItems: [],
      searchQuery: '',
      isDropdownOpen: false,
      saving: false,
      isLoadingLots: false,
      isEdit: false,
      
      transfer: {
        id: 0,
        from_warehouse_id: '',
        to_warehouse_id: '',
        transfer_no: `TR-${moment().format('YYMMDDHHmmss')}`,
        transfer_date: moment().format('YYYY-MM-DDTHH:mm'),
        remark: '',
        user_name: localStorage.getItem('user_name') || 'Admin'
      },
      
      currentItem: {
        item_id: '', item_code: '', item_name: '', unit_id: '', unit_name: '',
        lot_id: '', lot_number: '', expiry_date: '',
        qty: 1
      }
    }
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
  async mounted() {
    await this.fetchWarehouses();
    const id = this.$route.query.id;
    if (id) {
      this.isEdit = true;
      await this.fetchTransfer(id);
    }
  },
  methods: {
    async fetchWarehouses() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const res = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`);
        if (res.data.status === 'success') {
          this.activeWarehouses = res.data.data.filter(w => parseInt(w.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    async fetchItems() {
      this.items = [];
      this.searchQuery = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      if (!this.transfer.from_warehouse_id) return;
      
      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.transfer.from_warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data.filter(i => parseInt(i.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '', item_code: '', item_name: '', unit_id: '', unit_name: '',
        lot_id: '', lot_number: '', expiry_date: '',
        qty: 1
      };
      this.availableLots = [];
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

      try {
        const res = await axios.get(`/api-digital/inventory/get_lots.php?item_id=${m.id}&warehouse_id=${this.transfer.from_warehouse_id}`);
        if (res.data.status === 'success') {
          this.availableLots = res.data.data;
          if (this.availableLots.length > 0) {
             this.currentItem.lot_id = this.availableLots[0].lot_id;
          } else {
             Swal.fire('สินค้าหมด', 'ไม่มีสินค้านี้ในคลังต้นทาง (ยอดคงเหลือ 0)', 'warning');
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
        item_id: this.currentItem.item_id,
        item_code: this.currentItem.item_code,
        item_name: this.currentItem.item_name,
        unit_name: this.currentItem.unit_name,
        lot_id: selectedLot.lot_id,
        lot_number: selectedLot.lot_number,
        expiry_date: selectedLot.expiry_date,
        qty: this.currentItem.qty
      });
      
      this.resetCurrentItem();
      this.searchQuery = '';
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    async saveTransfer() {
      if (!this.transfer.from_warehouse_id || !this.transfer.to_warehouse_id) {
        return Swal.fire('Warning', 'กรุณาระบุคลังต้นทางและปลายทาง', 'warning');
      }
      if (this.transfer.from_warehouse_id === this.transfer.to_warehouse_id) {
        return Swal.fire('Warning', 'ไม่สามารถโอนไปยังคลังเดียวกันได้', 'warning');
      }
      if (this.selectedItems.length === 0) {
        return Swal.fire('Warning', 'กรุณาเพิ่มรายการวัสดุที่จะโอน', 'warning');
      }

      this.saving = true;
      try {
        const payload = {
          from_warehouse_id: this.transfer.from_warehouse_id,
          to_warehouse_id: this.transfer.to_warehouse_id,
          transfer_no: this.transfer.transfer_no,
          transfer_date: this.transfer.transfer_date.replace('T', ' '),
          remark: this.transfer.remark,
          user_name: this.transfer.user_name,
          items: this.selectedItems
        };

        const res = await axios.post('/api-digital/inventory/transfer_save.php', payload);
        
        if (res.data.status === 'success') {
          await Swal.fire('สำเร็จ', 'โอนย้ายสต๊อกวัสดุเรียบร้อยแล้ว', 'success');
          this.$router.push('/inventory-transfer');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโอนย้าย', 'error');
      } finally {
        this.saving = false;
      }
    },
    async fetchTransfer(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/transfer_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const data = res.data.data;
          this.transfer.from_warehouse_id = data.from_warehouse_id;
          this.transfer.to_warehouse_id = data.to_warehouse_id;
          this.transfer.transfer_no = data.transfer_no;
          this.transfer.transfer_date = data.transfer_date.replace(' ', 'T').substring(0,16);
          this.transfer.remark = data.remark;
          
          this.selectedItems = data.items.map(i => ({
            item_code: i.item_code,
            item_name: i.item_name,
            unit_name: i.unit_name,
            lot_number: i.lot_number,
            expiry_date: i.expiry_date,
            qty: Number(i.qty)
          }));
        } else {
          Swal.fire('Error', 'ไม่พบใบโอนย้ายนี้', 'error');
          this.$router.push('/inventory-transfer');
        }
      } catch (err) {
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
      }
    }
  }
}
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
