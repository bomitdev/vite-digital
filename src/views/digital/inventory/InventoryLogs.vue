<template>
  <div class="inventory-logs-page">
    <!-- Top Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div>
        <div class="d-flex align-items-center gap-2">
          <div class="header-badge bg-indigo-soft text-indigo rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
            <i class="bi bi-journal-check fs-5"></i>
          </div>
          <div>
            <h4 class="fw-bold text-slate-900 mb-0">ประวัติการใช้งานระบบคลัง (Audit Log)</h4>
            <p class="text-muted small mb-0">ติดตามการเข้าชมทุกหน้าและการบันทึก/แก้ไขข้อมูลในระบบจัดการคลังวัสดุ</p>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2">
        <button
          class="btn btn-outline-success rounded-pill px-3 fw-semibold btn-sm d-flex align-items-center gap-2"
          @click="exportExcel"
          :disabled="loading || logs.length === 0"
        >
          <i class="bi bi-file-earmark-excel"></i>
          ส่งออก Excel
        </button>
        <button
          class="btn btn-primary rounded-pill px-3 fw-semibold btn-sm d-flex align-items-center gap-2"
          @click="fetchLogs"
          :disabled="loading"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm"></span>
          <i v-else class="bi bi-arrow-clockwise"></i>
          รีเฟรชข้อมูล
        </button>
      </div>
    </div>

    <!-- KPI Summary Cards (Today) -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted small fw-semibold">กิจกรรมทั้งหมดวันนี้</span>
              <span class="badge bg-primary-subtle text-primary rounded-pill"><i class="bi bi-activity"></i></span>
            </div>
            <div class="fs-3 fw-bold text-slate-900">{{ summary.total_today }}</div>
            <div class="text-muted" style="font-size: 0.72rem;">รายการในวันที่ {{ todayLabel }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted small fw-semibold">เข้าเปิดดูหน้า / คลัง</span>
              <span class="badge bg-info-subtle text-info rounded-pill"><i class="bi bi-eye"></i></span>
            </div>
            <div class="fs-3 fw-bold text-info">{{ summary.views_today }}</div>
            <div class="text-muted" style="font-size: 0.72rem;">VIEW_PAGE / SWITCH_WAREHOUSE</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted small fw-semibold">บันทึก / แก้ไขข้อมูล</span>
              <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-pencil-square"></i></span>
            </div>
            <div class="fs-3 fw-bold text-success">{{ summary.writes_today }}</div>
            <div class="text-muted" style="font-size: 0.72rem;">รับ-จ่าย-เบิก-โอน-ตั้งค่า</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="text-muted small fw-semibold">ผู้ใช้งานวันนี้</span>
              <span class="badge bg-warning-subtle text-warning rounded-pill"><i class="bi bi-people"></i></span>
            </div>
            <div class="fs-3 fw-bold text-warning-emphasis">{{ summary.distinct_users_today }}</div>
            <div class="text-muted" style="font-size: 0.72rem;">จำนวนบัญชีที่มีการใช้งาน</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">ค้นหา</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input
                v-model.trim="filters.search"
                type="text"
                class="form-control border-start-0"
                placeholder="ชื่อผู้ใช้, เลขที่เอกสาร, รายละเอียด, IP..."
                @keyup.enter="fetchLogs"
              />
            </div>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">คลังวัสดุ</label>
            <select v-model="filters.warehouse_id" class="form-select form-select-sm" @change="fetchLogs">
              <option value="">ทุกคลังที่มีสิทธิ์</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="String(wh.id)">
                {{ wh.name }}
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">หน้าจอ / โมดูล</label>
            <select v-model="filters.page_code" class="form-select form-select-sm" @change="fetchLogs">
              <option value="">ทุกหน้าจอ</option>
              <option v-for="pg in pageOptions" :key="pg.page_code" :value="pg.page_code">
                {{ pg.page_name }}
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">ประเภทกิจกรรม</label>
            <select v-model="filters.category" class="form-select form-select-sm" @change="fetchLogs">
              <option value="all">ทั้งหมด</option>
              <option value="write">เฉพาะ บันทึก/แก้ไข/ลบ</option>
              <option value="view">เฉพาะ เข้าเปิดดูหน้า</option>
            </select>
          </div>

          <div class="col-6 col-md-1">
            <label class="form-label small fw-semibold text-muted mb-1">ตั้งแต่</label>
            <input v-model="filters.start_date" type="date" class="form-control form-control-sm" @change="fetchLogs" />
          </div>

          <div class="col-6 col-md-1">
            <label class="form-label small fw-semibold text-muted mb-1">ถึงวันที่</label>
            <input v-model="filters.end_date" type="date" class="form-control form-control-sm" @change="fetchLogs" />
          </div>

          <div class="col-6 col-md-1 d-grid">
            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters" title="ล้างตัวกรอง">
              <i class="bi bi-x-circle me-1"></i>ล้าง
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div v-if="errorMsg" class="alert alert-danger border-0 rounded-4 shadow-sm mb-3 d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span>{{ errorMsg }}</span>
    </div>

    <!-- Logs Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fw-bold text-slate-800">
          <i class="bi bi-list-ul me-2 text-primary"></i>รายการบันทึกกิจกรรม ({{ logs.length }} รายการล่าสุด)
        </span>
        <div class="d-flex align-items-center gap-2 small text-muted">
          <span>แสดงสูงสุด</span>
          <select v-model.number="filters.limit" class="form-select form-select-sm" style="width: auto;" @change="fetchLogs">
            <option :value="100">100</option>
            <option :value="150">150</option>
            <option :value="300">300</option>
            <option :value="500">500</option>
          </select>
          <span>รายการ</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 log-table">
          <thead class="table-light text-muted small">
            <tr>
              <th style="width: 155px;">วัน-เวลา</th>
              <th style="width: 170px;">ผู้ใช้งาน</th>
              <th style="width: 155px;">คลัง</th>
              <th style="width: 170px;">หน้าจอ</th>
              <th style="width: 145px;">การกระทำ</th>
              <th>รายละเอียด</th>
              <th style="width: 120px;">IP Address</th>
              <th class="text-center" style="width: 70px;">ข้อมูล</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                กำลังโหลดประวัติการใช้งาน...
              </td>
            </tr>
            <tr v-else-if="logs.length === 0">
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                ไม่พบประวัติการใช้งานตามเงื่อนไขที่เลือก
              </td>
            </tr>
            <tr v-else v-for="log in logs" :key="log.id">
              <td class="small text-nowrap text-slate-700 font-monospace">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td>
                <div class="fw-semibold text-slate-900 small">{{ log.user_fullname || log.username || 'ระบบ / ไม่ระบุ' }}</div>
                <div v-if="log.username && log.username !== log.user_fullname" class="text-muted" style="font-size: 0.72rem;">
                  @{{ log.username }}
                </div>
              </td>
              <td>
                <span v-if="log.warehouse_name" class="badge bg-light text-dark border fw-normal">
                  {{ log.warehouse_name }}
                </span>
                <span v-else class="text-muted small">ทั้งหมด / ระบบ</span>
              </td>
              <td>
                <div class="small fw-semibold text-slate-800">{{ log.page_name || pageLabel(log.page_code) }}</div>
                <div class="text-muted font-monospace" style="font-size: 0.7rem;">{{ log.page_code }}</div>
              </td>
              <td>
                <span class="badge rounded-pill px-2 py-1" :class="actionBadgeClass(log.action)">
                  {{ log.action }}
                </span>
              </td>
              <td class="small text-slate-800">
                <div>{{ log.details || '-' }}</div>
                <div v-if="log.target_id" class="text-muted" style="font-size: 0.72rem;">
                  Target: {{ log.target_type || '-' }} #{{ log.target_id }}
                </div>
              </td>
              <td class="small font-monospace text-muted">{{ log.ip_address || '-' }}</td>
              <td class="text-center">
                <button
                  class="btn btn-sm btn-light border rounded-pill px-2"
                  @click="selectedLog = log"
                  title="ดูรายละเอียดเพิ่มเติม"
                >
                  <i class="bi bi-code-square"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="selectedLog" class="modal-backdrop fade show" style="z-index: 1060;"></div>
    <div v-if="selectedLog" class="modal fade show d-block" tabindex="-1" style="z-index: 1061;" @click.self="selectedLog = null">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
          <div class="modal-header border-bottom">
            <h6 class="modal-title fw-bold">
              <i class="bi bi-journal-text me-2 text-primary"></i>รายละเอียด Log #{{ selectedLog.id }}
            </h6>
            <button type="button" class="btn-close" @click="selectedLog = null"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2 small mb-3">
              <div class="col-md-6"><strong>เวลา:</strong> {{ formatDateTime(selectedLog.created_at) }}</div>
              <div class="col-md-6"><strong>ผู้ใช้งาน:</strong> {{ selectedLog.user_fullname || selectedLog.username || '-' }}</div>
              <div class="col-md-6"><strong>หน้าจอ:</strong> {{ selectedLog.page_name }} ({{ selectedLog.page_code }})</div>
              <div class="col-md-6"><strong>การกระทำ:</strong> {{ selectedLog.action }}</div>
              <div class="col-md-6"><strong>คลัง:</strong> {{ selectedLog.warehouse_name || 'ทั้งหมด / ระบบ' }}</div>
              <div class="col-md-6"><strong>IP:</strong> {{ selectedLog.ip_address || '-' }}</div>
              <div class="col-12"><strong>รายละเอียด:</strong> {{ selectedLog.details || '-' }}</div>
              <div class="col-12 text-muted"><strong>User-Agent:</strong> {{ selectedLog.user_agent || '-' }}</div>
            </div>
            <div class="fw-semibold small mb-1">Metadata JSON</div>
            <pre class="bg-light border rounded-3 p-3 small mb-0" style="max-height: 260px; overflow: auto;">{{ prettyMeta(selectedLog.meta_json) }}</pre>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" @click="selectedLog = null">ปิด</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import * as XLSX from 'xlsx';
import { INVENTORY_PAGE_NAMES } from '../../../utils/inventoryLogger';

export default {
  name: 'InventoryLogs',
  props: {
    warehouseId: {
      type: [String, Number],
      default: '',
    },
  },
  data() {
    const today = new Date().toISOString().slice(0, 10);
    return {
      loading: false,
      errorMsg: '',
      logs: [],
      warehouses: [],
      availablePages: [],
      selectedLog: null,
      summary: {
        total_today: 0,
        views_today: 0,
        writes_today: 0,
        distinct_users_today: 0,
      },
      filters: {
        warehouse_id: this.warehouseId ? String(this.warehouseId) : '',
        page_code: '',
        category: 'all',
        search: '',
        start_date: today,
        end_date: today,
        limit: 150,
      },
    };
  },
  computed: {
    todayLabel() {
      return new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    pageOptions() {
      const merged = new Map();
      Object.entries(INVENTORY_PAGE_NAMES).forEach(([code, name]) => {
        merged.set(code, { page_code: code, page_name: name });
      });
      this.availablePages.forEach((p) => {
        if (p.page_code && !merged.has(p.page_code)) {
          merged.set(p.page_code, p);
        }
      });
      return Array.from(merged.values());
    },
  },
  watch: {
    warehouseId(newVal) {
      this.filters.warehouse_id = newVal ? String(newVal) : '';
      this.fetchLogs();
    },
  },
  async mounted() {
    await this.fetchWarehouses();
    await this.fetchLogs();
  },
  methods: {
    async fetchWarehouses() {
      try {
        const res = await axios.get('/api-digital/inventory/warehouses.php');
        if (res.data?.status === 'success' && Array.isArray(res.data.data)) {
          this.warehouses = res.data.data;
        }
      } catch (e) {
        // ignore
      }
    },
    async fetchLogs() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const params = new URLSearchParams();
        if (this.filters.warehouse_id) params.append('warehouse_id', this.filters.warehouse_id);
        if (this.filters.page_code) params.append('page_code', this.filters.page_code);
        if (this.filters.category) params.append('category', this.filters.category);
        if (this.filters.search) params.append('search', this.filters.search);
        if (this.filters.start_date) params.append('start_date', this.filters.start_date);
        if (this.filters.end_date) params.append('end_date', this.filters.end_date);
        params.append('limit', String(this.filters.limit || 150));

        const res = await axios.get(`/api-digital/inventory/activity_logs.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.logs = Array.isArray(res.data.data) ? res.data.data : [];
          if (res.data.summary) this.summary = res.data.summary;
          if (Array.isArray(res.data.filters?.pages)) {
            this.availablePages = res.data.filters.pages;
          }
        } else {
          this.errorMsg = res.data?.message || 'ไม่สามารถโหลดข้อมูล Log ได้';
        }
      } catch (err) {
        this.errorMsg = err?.response?.data?.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล Log';
      } finally {
        this.loading = false;
      }
    },
    resetFilters() {
      this.filters = {
        warehouse_id: this.warehouseId ? String(this.warehouseId) : '',
        page_code: '',
        category: 'all',
        search: '',
        start_date: '',
        end_date: '',
        limit: 150,
      };
      this.fetchLogs();
    },
    pageLabel(code) {
      return INVENTORY_PAGE_NAMES[code] || code || '-';
    },
    actionBadgeClass(action) {
      const a = String(action || '').toUpperCase();
      if (a.includes('DELETE') || a.includes('REJECT') || a.includes('CANCEL')) {
        return 'bg-danger-subtle text-danger';
      }
      if (a.includes('APPROVE') || a.includes('RECEIVE') || a.includes('CREATE') || a.includes('IMPORT') || a.includes('SAVE') || a.includes('ISSUE') || a.includes('TRANSFER')) {
        return 'bg-success-subtle text-success';
      }
      if (a.includes('UPDATE') || a.includes('EXPORT') || a.includes('PRINT') || a.includes('PARSE')) {
        return 'bg-warning-subtle text-warning-emphasis';
      }
      return 'bg-info-subtle text-info-emphasis';
    },
    formatDateTime(dt) {
      if (!dt) return '-';
      const d = new Date(String(dt).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return dt;
      return d.toLocaleString('th-TH', {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    },
    prettyMeta(meta) {
      if (!meta) return '-';
      if (typeof meta === 'object') return JSON.stringify(meta, null, 2);
      try {
        return JSON.stringify(JSON.parse(meta), null, 2);
      } catch (e) {
        return String(meta);
      }
    },
    exportExcel() {
      if (!this.logs.length) return;
      const rows = this.logs.map((l, idx) => ({
        'ลำดับ': idx + 1,
        'วัน-เวลา': l.created_at,
        'ผู้ใช้งาน': l.user_fullname || l.username || '-',
        'Username': l.username || '-',
        'คลัง': l.warehouse_name || 'ทั้งหมด',
        'Page Code': l.page_code,
        'หน้าจอ': l.page_name || this.pageLabel(l.page_code),
        'การกระทำ': l.action,
        'Target Type': l.target_type || '-',
        'Target ID': l.target_id || '-',
        'รายละเอียด': l.details || '-',
        'IP Address': l.ip_address || '-',
      }));
      const ws = XLSX.utils.json_to_sheet(rows);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Inventory_Logs');
      const stamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '-');
      XLSX.writeFile(wb, `Inventory_Audit_Logs_${stamp}.xlsx`);
    },
  },
};
</script>

<style scoped>
.bg-indigo-soft {
  background-color: rgba(99, 102, 241, 0.12);
}
.text-indigo {
  color: #4f46e5;
}
.text-slate-900 {
  color: #0f172a;
}
.text-slate-800 {
  color: #1e293b;
}
.text-slate-700 {
  color: #334155;
}
.kpi-card {
  transition: transform 0.15s ease;
}
.kpi-card:hover {
  transform: translateY(-2px);
}
.log-table td,
.log-table th {
  vertical-align: middle;
}
</style>

