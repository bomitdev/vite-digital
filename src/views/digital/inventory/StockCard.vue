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
              <li class="breadcrumb-item active" aria-current="page">ตรวจสอบสต๊อกการ์ด</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-primary mb-0">
            <i class="bi bi-card-list me-2"></i>ระบบตรวจสอบสต๊อกการ์ด (Stock Card)
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
          <!-- ค้นหา -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label fw-bold">1. เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                  <select class="form-select form-select-lg" v-model="filters.warehouse_id" @change="onWarehouseChange">
                    <option value="">-- กรุณาเลือกคลัง --</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                  </select>
                </div>
                <div class="col-md-5 position-relative" ref="searchContainer">
                  <label class="form-label fw-bold">2. ค้นหารหัส/ชื่อวัสดุ <span class="text-danger">*</span></label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white text-muted border-end-0">
                      <i class="bi bi-search"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      v-model="searchQuery" 
                      @focus="isDropdownOpen = true"
                      @input="isDropdownOpen = true"
                      @keydown.enter.prevent="fetchStockCard"
                      placeholder="พิมพ์ชื่อหรือรหัสวัสดุ..."
                      :disabled="!filters.warehouse_id"
                    />
                    <button 
                      class="btn btn-outline-secondary" 
                      type="button" 
                      v-if="searchQuery || filters.item_id" 
                      @click="clearSearch"
                      title="ล้างข้อมูลการค้นหา"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>

                  <!-- Dropdown ผลลัพธ์การค้นหา -->
                  <div 
                    v-if="isDropdownOpen && filters.warehouse_id" 
                    class="position-absolute w-100 bg-white border rounded-3 shadow-lg mt-1" 
                    style="max-height: 280px; overflow-y: auto; z-index: 1050; left: 0;"
                  >
                    <div class="d-flex justify-content-between align-items-center p-2 px-3 border-bottom bg-light">
                      <small class="text-muted fw-bold">
                        <span v-if="isLoadingItems"><span class="spinner-border spinner-border-sm me-1"></span>กำลังโหลดรายการ...</span>
                        <span v-else>พบ {{ filteredItems.length }} รายการ (คลิกเพื่อเลือก)</span>
                      </small>
                      <button type="button" class="btn btn-sm btn-link text-secondary p-0 text-decoration-none" @click="isDropdownOpen = false">
                        <i class="bi bi-x-circle me-1"></i>ปิด
                      </button>
                    </div>
                    <div v-if="isLoadingItems" class="p-3 text-center text-muted">
                      <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลพัสดุ...
                    </div>
                    <div v-else-if="filteredItems.length === 0" class="p-3 text-muted text-center">
                      <i class="bi bi-inbox me-1"></i> ไม่พบข้อมูลในคลังนี้
                    </div>
                    <div 
                      v-for="m in filteredItems" 
                      :key="m.id" 
                      class="p-2 px-3 border-bottom dropdown-item-custom d-flex justify-content-between align-items-center"
                      :class="{'bg-primary-subtle text-primary fw-bold': String(filters.item_id) === String(m.id)}"
                      @click="selectItem(m)"
                    >
                      <div>
                        <span class="badge bg-light text-primary border border-primary-subtle font-monospace me-2">{{ m.item_code }}</span>
                        <span class="fw-semibold text-dark">{{ m.item_name }}</span>
                        <small class="text-muted ms-2" v-if="m.item_name_en">({{ m.item_name_en }})</small>
                      </div>
                      <div class="text-end ps-2">
                        <span class="badge bg-light text-secondary border">
                          คงเหลือ: <strong class="text-primary">{{ formatQty(m.on_hand) }}</strong> {{ m.unit_name || '' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <button 
                    type="button" 
                    class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" 
                    @click="fetchStockCard" 
                    :disabled="isLoading"
                  >
                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-search me-1" v-else></i> ค้นหาสต๊อกการ์ด
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ผลลัพธ์ (Stock Card) -->
          <div class="card border-0 shadow-sm rounded-4" v-if="hasSearched">
            <div class="card-body p-4 p-md-5">
              <div class="row mb-4 align-items-center">
                <div class="col-md-8">
                  <h4 class="fw-bold mb-1 text-primary">[{{ selectedItem.item_code }}] {{ selectedItem.item_name }}</h4>
                  <p class="text-muted mb-0">
                    <i class="bi bi-building me-1"></i>คลัง: <strong>{{ warehouseName }}</strong>
                    <span class="mx-2">•</span>
                    หน่วยนับ: <strong>{{ selectedItem.unit_name }}</strong>
                  </p>
                </div>
                <div class="col-md-4 text-end">
                  <div class="p-3 bg-light rounded-3 border">
                    <span class="d-block text-muted small fw-bold">ยอดคงเหลือปัจจุบัน</span>
                    <span class="fs-2 fw-bold text-success">{{ formatQty(currentBalance) }}</span>
                    <span class="ms-2 text-muted">{{ selectedItem.unit_name }}</span>
                  </div>
                </div>
              </div>

              <!-- รายการ Lots คงเหลือ -->
              <div class="mb-4" v-if="lots.length > 0">
                <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-boxes me-1"></i> ยอดคงเหลือแยกล็อต (FEFO)</h6>
                <div class="d-flex flex-wrap gap-2">
                  <div class="badge bg-white text-dark border p-2 px-3 shadow-sm" v-for="l in lots" :key="l.lot_id">
                    <div class="text-muted small">Lot: {{ l.lot_number || '-' }} | Exp: <span :class="{'text-danger': isExpired(l.expiry_date)}">{{ l.expiry_date || '-' }}</span></div>
                    <div class="fw-bold text-primary mt-1 fs-6">{{ formatQty(l.balance_qty) }} {{ selectedItem.unit_name }}</div>
                  </div>
                </div>
              </div>

              <!-- Table ประวัติ -->
              <h5 class="fw-bold mb-3 border-bottom pb-2 mt-4"><i class="bi bi-clock-history me-1"></i> ประวัติการเคลื่อนไหว (Transaction Log 100 รายการล่าสุด)</h5>
              <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                  <thead class="table-light text-center">
                    <tr>
                      <th rowspan="2" class="align-middle">วันที่-เวลา</th>
                      <th rowspan="2" class="align-middle">เอกสาร</th>
                      <th rowspan="2" class="align-middle">Lot (Exp)</th>
                      <th colspan="3">รายการเคลื่อนไหว ({{ selectedItem.unit_name }})</th>
                      <th rowspan="2" class="align-middle">ผู้ทำรายการ</th>
                      <th rowspan="2" class="align-middle">หมายเหตุ</th>
                    </tr>
                    <tr>
                      <th class="text-success">รับเข้า (IN)</th>
                      <th class="text-danger">เบิกออก (OUT)</th>
                      <th class="text-primary">คงเหลือ (BAL)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="transactions.length === 0">
                      <td colspan="8" class="text-center text-muted py-4">ไม่พบประวัติการเคลื่อนไหว</td>
                    </tr>
                    <tr v-for="t in transactions" :key="t.id">
                      <td class="text-center">{{ formatDateTime(t.transaction_date) }}</td>
                      <td class="text-center">
                        <span class="badge" :class="t.transaction_type === 'IN' ? 'bg-success' : 'bg-danger'">
                          {{ t.document_type }}
                        </span>
                      </td>
                      <td class="text-center text-muted small">
                         {{ t.lot_number || '-' }}
                         <br/><span v-if="t.expiry_date">(Exp: {{ t.expiry_date }})</span>
                      </td>
                      <td class="text-center text-success fw-bold">{{ t.qty_in > 0 ? formatQty(t.qty_in) : '-' }}</td>
                      <td class="text-center text-danger fw-bold">{{ t.qty_out > 0 ? formatQty(t.qty_out) : '-' }}</td>
                      <td class="text-center text-primary fw-bold bg-light">{{ formatQty(t.balance_qty) }}</td>
                      <td class="text-center">{{ t.user_name }}</td>
                      <td class="small">{{ t.remark }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Empty State (ก่อนเลือกพัสดุ) -->
          <div class="card border-0 shadow-sm rounded-4 text-center py-5 text-muted bg-white" v-else>
            <div class="py-5">
              <i class="bi bi-card-checklist fs-1 d-block mb-3 text-secondary opacity-50"></i>
              <h5 class="fw-bold text-dark">กรุณาเลือกหรือค้นหารายการพัสดุ</h5>
              <p class="small text-muted mb-0">พิมพ์ชื่อหรือรหัสพัสดุด้านบน แล้วเลือกรายการเพื่อตรวจสอบยอดคงเหลือและสต๊อกการ์ด</p>
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
import { logInventoryEvent } from '../../../utils/inventoryLogger';

export default {
  name: 'InventoryStockCard',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      warehouses: [],
      items: [],
      searchQuery: '',
      isDropdownOpen: false,
      isLoading: false,
      isLoadingItems: false,
      hasSearched: false,
      
      filters: {
        warehouse_id: '',
        item_id: ''
      },
      
      selectedItem: {},
      warehouseName: '',
      currentBalance: 0,
      lots: [],
      transactions: []
    };
  },
  computed: {
    filteredItems() {
      if (!this.searchQuery) return this.items;
      const q = this.searchQuery.toLowerCase().trim();
      return this.items.filter(m => 
        (m.item_name && m.item_name.toLowerCase().includes(q)) || 
        (m.item_code && m.item_code.toLowerCase().includes(q)) ||
        (m.item_name_en && m.item_name_en.toLowerCase().includes(q))
      );
    }
  },
  watch: {
    warehouseId: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.filters.warehouse_id = parseInt(newVal) || newVal;
          if (this.warehouses.length > 0) {
            this.fetchItems();
          }
        }
      }
    }
  },
  mounted() {
    this.fetchWarehouses();
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    formatQty(val) {
      return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
    },
    formatDateTime(val) {
      if (!val) return '-';
      return moment(val).format('DD/MM/YYYY HH:mm');
    },
    isExpired(dateStr) {
      if (!dateStr) return false;
      return moment(dateStr).isBefore(moment(), 'day');
    },
    handleClickOutside(e) {
      if (this.$refs.searchContainer && !this.$refs.searchContainer.contains(e.target)) {
        this.isDropdownOpen = false;
      }
    },
    async fetchWarehouses() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const res = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`);
        if (res.data.status === 'success') {
          this.warehouses = res.data.data.filter(w => w.active === 1);
          const targetWh = this.warehouseId || localStorage.getItem('selected_warehouse_id') || (this.warehouses[0]?.id || '');
          if (targetWh) {
            this.filters.warehouse_id = parseInt(targetWh) || targetWh;
            await this.fetchItems();
          }
        }
      } catch (err) {
        console.error(err);
      }
    },
    onWarehouseChange() {
      this.fetchItems();
    },
    async fetchItems() {
      this.items = [];
      this.searchQuery = '';
      this.filters.item_id = '';
      this.hasSearched = false;
      if (!this.filters.warehouse_id) return;
      
      const wh = this.warehouses.find(w => String(w.id) === String(this.filters.warehouse_id));
      this.warehouseName = wh ? wh.name : '';
      
      this.isLoadingItems = true;
      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.filters.warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data || [];
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.isLoadingItems = false;
      }
    },
    selectItem(m, autoFetch = true) {
      this.filters.item_id = m.id;
      this.selectedItem = {
        id: m.id,
        item_code: m.item_code,
        item_name: m.item_name,
        unit_name: m.unit_name || 'หน่วย'
      };
      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
      if (autoFetch) {
        this.fetchStockCard();
      }
    },
    clearSearch() {
      this.searchQuery = '';
      this.filters.item_id = '';
      this.selectedItem = {};
      this.hasSearched = false;
      this.isDropdownOpen = true;
    },
    async fetchStockCard() {
      if (!this.filters.warehouse_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกคลังพัสดุ', 'warning');
        return;
      }

      // If user typed something in search box but hasn't explicitly clicked a dropdown item:
      if (!this.filters.item_id && this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase().trim();
        const match = this.items.find(m => 
          (m.item_code && m.item_code.toLowerCase() === q) || 
          (m.item_name && m.item_name.toLowerCase() === q) ||
          `[${m.item_code?.toLowerCase()}] ${m.item_name?.toLowerCase()}` === q
        ) || this.filteredItems[0];

        if (match) {
          this.selectItem(match, false);
        }
      }

      if (!this.filters.item_id) {
        this.isDropdownOpen = true;
        if (this.items.length === 0) {
          Swal.fire('แจ้งเตือน', 'ไม่พบรายการพัสดุในคลังนี้', 'info');
        } else {
          Swal.fire({
            title: 'กรุณาเลือกรายการพัสดุ',
            text: 'กรุณาพิมพ์หรือคลิกเลือกรายการพัสดุที่ต้องการตรวจสอบสต๊อกการ์ด',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false
          });
        }
        return;
      }
      
      this.isLoading = true;
      try {
        // Fetch Stock Card & Balance
        const res = await axios.get(`/api-digital/inventory/stock_card.php?item_id=${this.filters.item_id}&warehouse_id=${this.filters.warehouse_id}`);
        if (res.data.status === 'success') {
          this.currentBalance = res.data.current_balance;
          this.transactions = res.data.data || [];
        }

        // Fetch Lots
        const lotRes = await axios.get(`/api-digital/inventory/get_lots.php?item_id=${this.filters.item_id}&warehouse_id=${this.filters.warehouse_id}`);
        if (lotRes.data.status === 'success') {
          this.lots = lotRes.data.data || [];
        }

        this.hasSearched = true;
        logInventoryEvent({
          action: 'VIEW_STOCK_CARD',
          page_code: 'stock_card',
          warehouse_id: this.filters.warehouse_id ? Number(this.filters.warehouse_id) : null,
          target_type: 'item',
          target_id: String(this.filters.item_id),
          details: `เปิดดู Stock Card พัสดุ [${this.selectedItem.item_code || '-'}] ${this.selectedItem.item_name || ''}`,
          meta: {
            current_balance: this.currentBalance,
            transactions_count: this.transactions.length,
          },
          dedupeMs: 3000,
        });
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลสต๊อกการ์ดได้', 'error');
      } finally {
        this.isLoading = false;
      }
    }
  }
};
</script>

<style scoped>
.dropdown-item-custom {
  cursor: pointer;
  transition: background-color 0.15s ease-in-out;
}
.dropdown-item-custom:hover {
  background-color: #f1f5f9;
}
</style>
