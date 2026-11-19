<template>
  <div class="container-fluid py-4 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-light btn-sm rounded-pill border px-3" @click="$router.push('/home-backoffice')">
            <i class="bi bi-arrow-left me-1"></i> กลับ
          </button>
          <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">
            <i class="bi bi-shield-check me-1"></i> พ.ร.บ.คอมฯ มาตรา 26 (เก็บข้อมูล ≥ 90 วัน)
          </span>
        </div>
        <h3 class="fw-bold text-dark mt-2 mb-1">
          <i class="bi bi-journal-text text-primary me-2"></i>Central Audit Logs (ประวัติการใช้งานระบบ)
        </h3>
        <p class="text-muted small mb-0">ตรวจสอบบันทึกการเข้า-ออกระบบ และกิจกรรมสำคัญทุกโมดูลแบบ Append-only</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary rounded-pill px-3 fw-semibold" @click="fetchLogs" :disabled="loading">
          <i class="bi bi-arrow-clockwise me-1"></i> รีเฟรช
        </button>
        <button class="btn btn-success rounded-pill px-3 fw-semibold" @click="exportCsv" :disabled="logs.length === 0">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i> ส่งออก CSV
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">รายการทั้งหมดใน DB</div>
            <div class="fs-4 fw-bold text-primary mt-1">{{ Number(stats.total_rows || 0).toLocaleString() }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">กิจกรรมวันนี้</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ Number(stats.today_count || 0).toLocaleString() }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">เข้าสู่ระบบไม่สำเร็จวันนี้</div>
            <div class="fs-4 fw-bold text-danger mt-1">{{ Number(stats.today_fail_count || 0).toLocaleString() }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">ข้อมูลเก่าสุดในระบบ</div>
            <div class="fs-6 fw-bold text-dark mt-2">{{ formatDateTime(stats.oldest_log_at) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">ค้นหา</label>
            <input
              type="text"
              class="form-control form-control-sm"
              v-model="filters.search"
              @keyup.enter="applyFilters"
              placeholder="ชื่อผู้ใช้, IP, รายละเอียด, Endpoint..."
            />
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">โมดูล</label>
            <select class="form-select form-select-sm" v-model="filters.module" @change="applyFilters">
              <option value="all">ทั้งหมด</option>
              <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">การกระทำ</label>
            <select class="form-select form-select-sm" v-model="filters.action" @change="applyFilters">
              <option value="all">ทั้งหมด</option>
              <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">ตั้งแต่วันที่</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.date_from" @change="applyFilters" />
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">ถึงวันที่</label>
            <input type="date" class="form-control form-control-sm" v-model="filters.date_to" @change="applyFilters" />
          </div>
          <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100 fw-bold" @click="applyFilters">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <div class="small text-muted mt-2">กำลังโหลดบันทึกการใช้งาน...</div>
        </div>
        <div v-else-if="errorMsg" class="alert alert-danger m-3 mb-0">
          <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMsg }}
        </div>
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light text-secondary">
              <tr>
                <th class="ps-3 text-nowrap">วัน-เวลา</th>
                <th>ผู้ใช้งาน</th>
                <th>โมดูล</th>
                <th>การกระทำ</th>
                <th>รายละเอียด</th>
                <th>IP Address</th>
                <th class="text-center">สถานะ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="logs.length === 0">
                <td colspan="7" class="text-center text-muted py-5">ไม่พบข้อมูลบันทึกการใช้งาน</td>
              </tr>
              <tr v-for="log in logs" :key="log.id">
                <td class="ps-3 text-nowrap font-monospace">{{ formatDateTime(log.created_at) }}</td>
                <td>
                  <div class="fw-bold text-dark">{{ log.fullname || log.username || 'System' }}</div>
                  <div class="text-muted" style="font-size: 0.72rem;" v-if="log.username">@{{ log.username }}</div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">{{ log.module }}</span>
                </td>
                <td>
                  <span class="badge" :class="actionBadgeClass(log.action)">{{ log.action }}</span>
                </td>
                <td style="max-width: 360px;">
                  <div class="text-dark">{{ log.details || '-' }}</div>
                  <div class="text-muted font-monospace text-truncate" style="font-size: 0.7rem;" v-if="log.endpoint">
                    {{ log.method }} {{ log.endpoint }}
                  </div>
                </td>
                <td class="font-monospace text-muted">{{ log.ip_address || '-' }}</td>
                <td class="text-center">
                  <span
                    v-if="log.status_code"
                    class="badge rounded-pill"
                    :class="Number(log.status_code) >= 400 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'"
                  >
                    {{ log.status_code }}
                  </span>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 px-3" v-if="total > 0">
        <span class="small text-muted">
          แสดง {{ offset + 1 }} - {{ Math.min(offset + limit, total) }} จากทั้งหมด {{ total.toLocaleString() }} รายการ
        </span>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" :disabled="offset === 0" @click="changePage(-1)">ก่อนหน้า</button>
          <button class="btn btn-outline-secondary" :disabled="offset + limit >= total" @click="changePage(1)">ถัดไป</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'AuditLogsView',
  data() {
    return {
      loading: false,
      errorMsg: '',
      logs: [],
      total: 0,
      stats: {},
      modules: [],
      actions: [],
      limit: 50,
      offset: 0,
      filters: {
        search: '',
        module: 'all',
        action: 'all',
        date_from: '',
        date_to: '',
      },
    };
  },
  mounted() {
    this.fetchLogs();
  },
  methods: {
    formatDateTime(val) {
      if (!val) return '-';
      return moment(val).format('DD/MM/YYYY HH:mm:ss');
    },
    actionBadgeClass(action) {
      const a = String(action || '').toUpperCase();
      if (a.includes('FAIL') || a.includes('DELETE') || a.includes('REJECT')) return 'bg-danger-subtle text-danger';
      if (a.includes('SUCCESS') || a.includes('APPROVE') || a.includes('CREATE')) return 'bg-success-subtle text-success';
      if (a.includes('UPDATE') || a.includes('EDIT')) return 'bg-warning-subtle text-warning-emphasis';
      return 'bg-primary-subtle text-primary';
    },
    applyFilters() {
      this.offset = 0;
      this.fetchLogs();
    },
    changePage(dir) {
      const next = this.offset + dir * this.limit;
      if (next < 0 || next >= this.total) return;
      this.offset = next;
      this.fetchLogs();
    },
    async fetchLogs() {
      this.loading = true;
      this.errorMsg = '';
      try {
        const params = new URLSearchParams({
          limit: this.limit,
          offset: this.offset,
          module: this.filters.module,
          action: this.filters.action,
        });
        if (this.filters.search.trim()) params.append('search', this.filters.search.trim());
        if (this.filters.date_from) params.append('date_from', this.filters.date_from);
        if (this.filters.date_to) params.append('date_to', this.filters.date_to);

        const res = await axios.get(`/api-digital/audit_logs.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.logs = res.data.data || [];
          this.total = Number(res.data.total || 0);
          this.stats = res.data.stats || {};
          this.modules = res.data.modules || [];
          this.actions = res.data.actions || [];
        } else {
          this.errorMsg = res.data?.message || 'ไม่สามารถโหลดข้อมูลได้';
        }
      } catch (err) {
        this.errorMsg = err?.response?.data?.message || 'ไม่มีสิทธิ์เข้าถึงหรือเกิดข้อผิดพลาดในการโหลดข้อมูล';
      } finally {
        this.loading = false;
      }
    },
    exportCsv() {
      const headers = ['ID', 'Created At', 'User ID', 'Username', 'Fullname', 'Module', 'Action', 'Method', 'Endpoint', 'IP', 'Status', 'Details'];
      const esc = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
      const rows = this.logs.map((r) => [
        r.id, r.created_at, r.user_id, r.username, r.fullname, r.module, r.action, r.method, r.endpoint, r.ip_address, r.status_code, r.details
      ].map(esc).join(','));
      const csv = '\uFEFF' + [headers.join(','), ...rows].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `audit_logs_${moment().format('YYYYMMDD_HHmmss')}.csv`;
      a.click();
      URL.revokeObjectURL(url);
    },
  },
};
</script>

