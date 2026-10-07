<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/material-admin">หน้าหลักวัสดุ</router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">รับเข้าคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-success mb-0">
            <i class="bi bi-box-arrow-in-down me-2"></i>บันทึกรับเข้าคลัง (Stock In)
          </h2>
        </div>
        <div>
          <button @click="openLogModal" class="btn btn-outline-info rounded-pill me-2">
            <i class="bi bi-clock-history me-1"></i>ดูประวัติ
          </button>
          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </router-link>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-10">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitTx">
                
                <!-- Section 1: Invoice Details -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">1. ข้อมูลบิล / ใบสั่งซื้อ</h5>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                      <input type="datetime-local" class="form-control form-control-lg" v-model="form.action_date" required />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">เลขที่บิล / ใบเสร็จ</label>
                      <input type="text" class="form-control form-control-lg" v-model="form.bill_number" placeholder="เช่น INV-2023001" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">แหล่งที่มา / ผู้จำหน่าย <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-lg" v-model="form.reference_dest" required placeholder="เช่น บริษัท A, สินค้าบริจาค" list="vendors" />
                      <datalist id="vendors">
                        <option value="Advice"></option>
                        <option value="JIB"></option>
                        <option value="IT City"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">ชื่อผู้รับผิดชอบ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="form.user_profile_name" required readonly />
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.note" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)" />
                    </div>
                  </div>
                </div>

                <!-- Section 2: Add Items -->
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. เพิ่มรายการวัสดุ (ระบุ Lot ราคา)</h5>
                  <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                      <label class="form-label mb-1">ค้นหาและเลือกวัสดุ</label>
                      <div class="d-flex justify-content-end mb-1">
                        <router-link to="/material-admin/stock" class="text-decoration-none small text-primary fw-bold">
                          <i class="bi bi-plus-circle me-1"></i>ยังไม่มีวัสดุนี้ในระบบ? (เพิ่มวัสดุใหม่)
                        </router-link>
                      </div>
                      <div class="position-relative">
                        <input 
                          type="text" 
                          class="form-control" 
                          v-model="searchQuery" 
                          @focus="isDropdownOpen = true"
                          @blur="closeDropdown"
                          placeholder="พิมพ์รหัส หรือชื่อวัสดุเพื่อค้นหา..."
                        />
                        <div 
                          v-if="isDropdownOpen" 
                          class="position-absolute w-100 bg-white border rounded shadow mt-1 z-3" 
                          style="max-height: 250px; overflow-y: auto;"
                        >
                          <div v-if="filteredMaterials.length === 0" class="p-2 text-muted text-center">
                            ไม่พบข้อมูล
                          </div>
                          <div 
                            v-for="m in filteredMaterials" 
                            :key="m.id" 
                            class="p-2 border-bottom dropdown-item-custom"
                            @mousedown.prevent="selectMaterial(m)"
                          >
                            [{{ m.code }}] {{ m.name }} 
                            <span class="text-muted small">(คงเหลือ: {{ m.balance }} {{ m.unit }})</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label mb-1">เลข Lot (ถ้ามี/ใส่เอง)</label>
                      <input type="text" class="form-control" v-model="currentItem.lot_number" placeholder="เช่น L001" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">ราคาต่อหน่วย (บ.)</label>
                      <input type="number" step="0.01" class="form-control" v-model.number="currentItem.price_per_unit" min="0" placeholder="ราคา" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">จำนวนที่รับเข้า</label>
                      <div class="input-group">
                        <input type="number" class="form-control" v-model.number="currentItem.quantity" min="1" />
                        <span class="input-group-text">{{ currentSelectedUnit }}</span>
                      </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                      <button type="button" class="btn btn-primary w-100 fw-bold" @click="addItem" :disabled="!currentItem.material_id || currentItem.quantity <= 0">
                        <i class="bi bi-plus-lg"></i> เพิ่ม
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Section 3: Added Items Table -->
                <div class="mb-4" v-if="items.length > 0">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">3. รายการวัสดุที่เตรียมรับเข้า</h5>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>ลำดับ</th>
                          <th>รหัสวัสดุ</th>
                          <th>ชื่อวัสดุ</th>
                          <th class="text-end">ราคา/หน่วย</th>
                          <th class="text-end">จำนวน</th>
                          <th>หน่วยนับ</th>
                          <th class="text-end">รวมเป็นเงิน</th>
                          <th class="text-center">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in items" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.code }}</td>
                          <td>
                            {{ item.name }}
                            <div class="small text-muted" v-if="item.lot_number">
                              <i class="bi bi-tag-fill me-1"></i>Lot: {{ item.lot_number }}
                            </div>
                            <div class="small text-muted" v-else>
                              <i class="bi bi-tag-fill me-1"></i>Lot อัตโนมัติ
                            </div>
                          </td>
                          <td class="text-end text-primary">{{ formatCurrency(item.price_per_unit) }} ฿</td>
                          <td class="text-end fw-bold text-success">{{ item.quantity }}</td>
                          <td>{{ item.unit }}</td>
                          <td class="text-end fw-bold">{{ formatCurrency(item.price_per_unit * item.quantity) }} ฿</td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" @click="removeItem(index)" title="ลบรายการ">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end mt-5">
                  <button type="button" class="btn btn-light rounded-pill px-4 me-2" @click="resetAll">
                    ล้างข้อมูลทั้งหมด
                  </button>
                  <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold" :disabled="isSubmitting || items.length === 0">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    บันทึกรับเข้าทั้งหมด ({{ items.length }} รายการ)
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Log Modal -->
    <MtLogModal ref="logModal" logType="in" modalId="inLogModal" />
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';
import MtLogModal from './MtLogModal.vue';

export default {
  name: 'MtTransactionIn',
  components: {
    MtLogModal
  },
  data() {
    return {
      materials: [],
      searchQuery: '',
      isDropdownOpen: false,
      isSubmitting: false,
      items: [], // Array to hold selected materials
      currentItem: {
        material_id: '',
        quantity: 1,
        price_per_unit: 0,
        lot_number: ''
      },
      form: {
        action_date: moment().format('YYYY-MM-DDTHH:mm'),
        bill_number: '',
        user_profile_name: localStorage.getItem('user_name') || 'Admin',
        reference_dest: '',
        note: ''
      }
    };
  },
  computed: {
    filteredMaterials() {
      if (!this.searchQuery) return this.materials;
      const lowerQuery = this.searchQuery.toLowerCase();
      return this.materials.filter(m => 
        m.name.toLowerCase().includes(lowerQuery) || 
        m.code.toLowerCase().includes(lowerQuery)
      );
    },
    currentSelectedUnit() {
      if (!this.currentItem.material_id) return '';
      const m = this.materials.find((x) => x.id === this.currentItem.material_id);
      return m ? m.unit : '';
    }
  },
  mounted() {
    if (!localStorage.getItem('user_name')) {
      axios.get('/api-hosoffice/get_user_profile.php').then((res) => {
        if (res.data.status === 'success') this.form.user_profile_name = res.data.fullname;
      });
    }
    this.fetchMaterials();
  },
  methods: {
    formatCurrency(value) {
      if (!value) return '0.00';
      return parseFloat(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/admin_material/admin_get_materials.php');
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    selectMaterial(m) {
      this.currentItem.material_id = m.id;
      this.currentItem.price_per_unit = parseFloat(m.price_per_unit) || 0;
      this.searchQuery = `[${m.code}] ${m.name}`;
      this.isDropdownOpen = false;
    },
    closeDropdown() {
      this.isDropdownOpen = false;
    },
    addItem() {
      if (!this.currentItem.material_id || this.currentItem.quantity <= 0) return;
      
      const material = this.materials.find((x) => x.id === this.currentItem.material_id);
      if (!material) return;

      // Check if already in list, if so add quantity
      const existingItem = this.items.find(i => i.material_id === this.currentItem.material_id && i.price_per_unit === this.currentItem.price_per_unit && i.lot_number === this.currentItem.lot_number);
      if (existingItem) {
        existingItem.quantity += this.currentItem.quantity;
      } else {
        this.items.push({
          material_id: material.id,
          code: material.code,
          name: material.name,
          unit: material.unit,
          quantity: this.currentItem.quantity,
          price_per_unit: this.currentItem.price_per_unit,
          lot_number: this.currentItem.lot_number
        });
      }

      // Reset current item input
      this.currentItem.material_id = '';
      this.currentItem.quantity = 1;
      this.currentItem.price_per_unit = 0;
      this.currentItem.lot_number = '';
      this.searchQuery = '';
    },
    removeItem(index) {
      this.items.splice(index, 1);
    },
    resetAll() {
      this.items = [];
      this.currentItem.material_id = '';
      this.currentItem.quantity = 1;
      this.currentItem.price_per_unit = 0;
      this.searchQuery = '';
      this.form.bill_number = '';
      this.form.reference_dest = '';
      this.form.note = '';
      this.form.action_date = moment().format('YYYY-MM-DDTHH:mm');
    },
    async submitTx() {
      if (this.items.length === 0) {
        Swal.fire('แจ้งเตือน', 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ', 'warning');
        return;
      }

      this.isSubmitting = true;
      try {
        // Prepare payload as array of items
        const payloadItems = this.items.map(item => ({
          material_id: item.material_id,
          quantity: item.quantity,
          price_per_unit: item.price_per_unit,
          lot_number: item.lot_number,
          action_date: this.form.action_date,
          bill_number: this.form.bill_number,
          user_profile_name: this.form.user_profile_name,
          reference_dest: this.form.reference_dest,
          note: this.form.note
        }));

        const payload = { items: payloadItems };

        const res = await axios.post('/api-digital/admin_material/admin_transaction_in.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ',
            text: `รับเข้าวัสดุทั้งหมด ${this.items.length} รายการเรียบร้อยแล้ว`,
            icon: 'success',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#198754'
          }).then(() => {
            this.$router.push('/material-admin');
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบขัดข้อง', 'error');
      } finally {
        this.isSubmitting = false;
      }
    },
    openLogModal() {
      // eslint-disable-next-line no-undef
      const modal = new bootstrap.Modal(document.getElementById('inLogModal'));
      modal.show();
      this.$refs.logModal.fetchLogs();
    }
  },
  mounted() {
    if (!localStorage.getItem('user_name')) {
      axios.get('/api-hosoffice/get_user_profile.php').then((res) => {
        if (res.data.status === 'success') this.form.user_profile_name = res.data.fullname;
      });
    }
    this.fetchMaterials();
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
.dropdown-item-custom {
  cursor: pointer;
  transition: background-color 0.15s;
}
.dropdown-item-custom:hover {
  background-color: #f8f9fa;
}
.z-3 {
  z-index: 1050 !important;
}
</style>
