<template>
  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-success mb-0 d-flex align-items-center">
        <i class="bi bi-graph-up-arrow me-2"></i> ศูนย์จัดเก็บรายได้โรงพยาบาลชานุมาน
      </h3>
      <div>
        <label class="me-2 fw-bold text-muted">ปีงบประมาณ:</label>
        <select
          v-model="selectedYear"
          class="form-select d-inline-block w-auto"
          @change="onYearChange"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>

        <label class="me-2 ms-3 fw-bold text-muted d-none d-md-inline">รายการ:</label>
        <select
          v-model="selectedTargetFilter"
          class="form-select d-inline-block w-auto text-truncate"
          style="max-width: 250px"
          @change="fetchDashboardData"
        >
          <option value="">ทั้งหมด</option>
          <option v-for="t in availableTargets" :key="t.target_id" :value="t.target_id">
            {{ t.revenue_name }}
          </option>
        </select>
        <button
          v-if="isAdmin || hasResponsibleTarget"
          class="btn btn-outline-primary ms-3 rounded-pill px-3 fw-bold"
          @click="$router.push('/revenue-setup')"
        >
          <i class="bi bi-gear-fill me-1"></i> ตั้งค่าจัดเก็บรายได้
        </button>
        <button
          class="btn btn-outline-dark ms-3 rounded-pill px-3 fw-bold"
          @click="$router.push('/home-backoffice')"
        >
          กลับหน้าหลัก
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row row-cols-1 row-cols-md-5 g-4 mb-5">
      <div class="col">
        <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase fw-bold mb-2">เป้าหมายทั้งหมด</h6>
            <h3 class="text-info fw-bold mb-0">
              {{ summaryData.length }} <span class="fs-6 text-muted fw-normal">รายการ</span>
            </h3>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase fw-bold mb-2">เป้าหมายรวม (บาท)</h6>
            <h3 class="text-dark fw-bold mb-0">{{ formatCurrency(totalTarget) }}</h3>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase fw-bold mb-2">เรียกเก็บได้ (บาท)</h6>
            <h3 class="text-success fw-bold mb-0">{{ formatCurrency(totalCollected) }}</h3>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card shadow-sm border-0 border-start border-4 border-purple h-100">
          <div class="card-body">
            <h6 class="text-muted text-uppercase fw-bold mb-2">จัดเก็บได้จริง(Statement) รวม (บาท)</h6>
            <h3 class="text-purple fw-bold mb-0" style="color: #6f42c1">
              {{ formatCurrency(totalStatement) }}
            </h3>
          </div>
        </div>
      </div>
      <div class="col">
        <div
          class="card shadow-sm border-0 border-start border-4 h-100"
          :class="percentage >= 100 ? 'border-success' : 'border-warning'"
        >
          <div class="card-body">
            <h6 class="text-muted text-uppercase fw-bold mb-2">คิดเป็นร้อยละ</h6>
            <h3 class="fw-bold mb-0" :class="percentage >= 100 ? 'text-success' : 'text-warning'">
              {{ formatPercent(percentage) }}%
            </h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-5">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">แนวโน้มการจัดเก็บรายได้รายเดือน ปี {{ selectedYear }}</h5>
          </div>
          <div class="card-body">
            <div style="position: relative; height: 300px; width: 100%">
              <canvas id="monthlyTrendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0">องค์ประกอบรายได้ตามหมวดหมู่</h5>
          </div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <div style="position: relative; height: 300px; width: 100%">
              <canvas id="categoryPieChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Comparison Chart -->
    <div class="row g-4 mb-5">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 border-0 d-flex align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 me-3">เปรียบเทียบการจัดเก็บรายได้</h5>
            <div class="d-flex align-items-center">
              <select
                class="form-select form-select-sm w-auto d-inline-block fw-bold text-secondary"
                v-model="compareYear1"
                @change="fetchComparisonData"
              >
                <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
              </select>
              <span class="mx-2 fw-bold text-muted">vs</span>
              <select
                class="form-select form-select-sm w-auto d-inline-block fw-bold text-primary"
                v-model="compareYear2"
                @change="fetchComparisonData"
              >
                <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div style="position: relative; height: 350px; width: 100%">
              <canvas id="yearlyComparisonChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Table -->
    <div class="card shadow-sm rounded-0 border-0 mb-5">
      <div
        class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center"
      >
        <h5 class="mb-0 fw-bold text-dark">รายละเอียดแต่ละเป้าหมาย</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="py-3 ps-3">รายการรายได้</th>
                <th class="py-3 text-end">เป้าหมาย (บาท)</th>
                <th class="py-3 text-end">เรียกเก็บได้ (บาท)</th>
                <th class="py-3 text-end">ยอดเรียกเก็บได้จริง(Statement)</th>
                <th class="py-3 text-end">% ความสำเร็จ</th>
                <th class="py-3">ผู้รับผิดชอบ</th>
                <th class="py-3 pe-3">อัปเดตล่าสุด</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in summaryData" :key="item.target_id">
                <td class="ps-3 fw-bold">{{ item.revenue_name }}</td>
                <td class="text-end text-muted">{{ formatCurrency(item.target_amount) }}</td>
                <td class="text-end text-success fw-bold">
                  {{ formatCurrency(item.total_collected) }}
                </td>
                <td class="text-end text-purple fw-bold" style="color: #6f42c1">
                  {{ formatCurrency(item.total_statement) }}
                </td>
                <td class="text-end">
                  <span
                    class="badge"
                    :class="getPercentColor(item.target_amount, item.total_collected)"
                  >
                    {{ calcPercent(item.target_amount, item.total_collected) }}%
                  </span>
                </td>
                <td>
                  <div class="small">{{ item.responsible_person || '-' }}</div>
                </td>
                <td class="pe-3">
                  <span class="badge bg-light text-dark border" v-if="item.latest_report_date">
                    {{ formatDateThai(item.latest_report_date) }}
                  </span>
                  <span class="text-muted small" v-else>-</span>
                  <button
                    class="btn btn-sm btn-outline-success ms-2 border-0"
                    @click="openHistoryModal(item)"
                    title="บันทึกผลจัดเก็บ"
                  >
                    <i class="bi bi-journal-plus"></i>
                  </button>
                  <button
                    v-if="isAdmin"
                    class="btn btn-sm btn-outline-purple ms-2 border-0"
                    @click="openStatementModal(item)"
                    title="บันทึก Statement"
                  >
                    <i class="bi bi-wallet2"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="summaryData.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  ไม่พบข้อมูลปีงบประมาณ {{ selectedYear }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal ประวัติผลงาน -->
    <div
      class="modal fade"
      id="historyModal"
      ref="historyModal"
      aria-hidden="true"
      data-bs-focus="false"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-journal-plus me-2"></i> บันทึกผลและประวัติ:
              {{ selectedTarget?.revenue_name }}
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              @click="closeHistoryModal"
            ></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <form
              @submit.prevent="submitResultForm"
              class="mb-4 bg-white p-3 rounded shadow-sm border"
            >
              <h6 class="fw-bold mb-3 text-success">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มผลงานจัดเก็บรายเดือน
              </h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label small">เดือนที่รายงาน</label>
                  <select v-model="resultForm.month" class="form-select form-select-sm" required>
                    <option v-for="m in fiscalMonths" :key="m.value" :value="m.value">
                      {{ m.label }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">จำนวนผลงาน</label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="resultForm.achieved_items"
                    class="form-control form-control-sm text-primary fw-bold"
                    @input="calculateResultAmount"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label small">ยอดจัดเก็บ (บาท)</label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="resultForm.collected_amount"
                    class="form-control form-control-sm text-success fw-bold"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label small">หมายเหตุ (ถ้ามี)</label>
                  <div class="d-flex">
                    <input
                      type="text"
                      v-model="resultForm.remark"
                      class="form-control form-control-sm me-2"
                    />
                    <button type="submit" class="btn btn-sm btn-success">บันทึก</button>
                  </div>
                </div>
              </div>
            </form>

            <h6 class="fw-bold mb-2">ประวัติการบันทึกผลงาน</h6>
            <div class="table-responsive bg-white rounded-3 shadow-sm border">
              <table class="table table-hover align-middle mb-0 text-center text-sm">
                <thead class="table-success">
                  <tr>
                    <th class="py-3">เดือนที่รายงาน</th>
                    <th class="py-3">จำนวนผลงาน</th>
                    <th class="py-3">ยอดจัดเก็บ</th>
                    <th class="py-3">หมายเหตุ</th>
                    <th class="py-3">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in resultsData" :key="r.id">
                    <td class="fw-bold">{{ getMonthName(r.month) }}</td>
                    <td class="text-primary fw-bold">
                      {{ r.achieved_items !== null ? formatCurrency(r.achieved_items) : '-' }}
                    </td>
                    <td class="text-success fw-bold">{{ formatCurrency(r.collected_amount) }}</td>
                    <td>{{ r.remark || '-' }}</td>
                    <td>
                      <button
                        class="btn btn-sm btn-outline-warning me-2 border-0"
                        @click="editResult(r)"
                        title="แก้ไข"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger border-0"
                        @click="deleteResult(r.id)"
                        title="ลบ"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="resultsData.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">ยังไม่มีข้อมูลการบันทึก</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal บันทึก Statement -->
    <div
      class="modal fade"
      id="statementModal"
      ref="statementModal"
      aria-hidden="true"
      data-bs-focus="false"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-wallet2 me-2"></i> บันทึก Statement:
              {{ selectedTarget?.revenue_name }}
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              @click="closeStatementModal"
            ></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div class="d-flex justify-content-end mb-3">
              <button class="btn btn-primary" @click="addStatementRecord">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มยอด Statement
              </button>
            </div>
            <div class="table-responsive bg-white rounded-3 shadow-sm border mb-4">
              <table class="table table-hover align-middle mb-0 text-center text-sm">
                <thead class="table-primary">
                  <tr>
                    <th class="py-3">งวดเดือน</th>
                    <th class="py-3">วันที่ได้รับ</th>
                    <th class="py-3">ยอดเรียกเก็บได้จริง(Statement) (บาท)</th>
                    <th class="py-3">หมายเหตุ</th>
                    <th class="py-3">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="st in statementData" :key="st.id">
                    <td class="fw-bold">{{ getMonthName(st.month) }}</td>
                    <td>{{ formatDateThai(st.statement_date) }}</td>
                    <td class="text-success fw-bold">{{ formatCurrency(st.statement_amount) }}</td>
                    <td>{{ st.remark || '-' }}</td>
                    <td>
                      <button
                        class="btn btn-sm btn-outline-warning me-2 border-0"
                        @click="editStatementRecord(st)"
                        title="แก้ไข"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger border-0"
                        @click="deleteStatementRecord(st.id)"
                        title="ลบ"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="statementData.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">
                      ยังไม่ได้บันทึกยอด Statement
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-end">
              <button
                type="button"
                class="btn btn-outline-secondary"
                data-bs-dismiss="modal"
                @click="closeStatementModal"
              >
                ปิด
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Chart from 'chart.js/auto';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

export default {
  name: 'RevenueDashboard',
  data() {
    return {
      selectedYear: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0),
      compareYear1: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0) - 1,
      compareYear2: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0),
      selectedTargetFilter: '',
      availableTargets: [],
      summaryData: [],
      monthlyData: [],
      compMonthlyData1: [],
      compMonthlyData2: [],
      trendChartInstance: null,
      pieChartInstance: null,
      yearlyComparisonChartInstance: null,
      resultsData: [],
      selectedTarget: null,
      historyModalInstance: null,
      userDepartment: '',
      statementData: [],
      statementModalInstance: null,
      userFullname: '',
      fiscalMonths: [
        { value: 10, label: 'ตุลาคม' },
        { value: 11, label: 'พฤศจิกายน' },
        { value: 12, label: 'ธันวาคม' },
        { value: 1, label: 'มกราคม' },
        { value: 2, label: 'กุมภาพันธ์' },
        { value: 3, label: 'มีนาคม' },
        { value: 4, label: 'เมษายน' },
        { value: 5, label: 'พฤษภาคม' },
        { value: 6, label: 'มิถุนายน' },
        { value: 7, label: 'กรกฎาคม' },
        { value: 8, label: 'สิงหาคม' },
        { value: 9, label: 'กันยายน' }
      ],
      resultForm: {
        id: null,
        target_id: null,
        month: new Date().getMonth() + 1,
        achieved_items: '',
        collected_amount: '',
        remark: ''
      }
    };
  },
  computed: {
    isAdmin() {
      return (
        this.userDepartment.includes('กลุ่มงานสุขภาพดิจิทัล') ||
        this.userDepartment.includes('บริหาร') ||
        this.userDepartment.includes('ประกัน')
      );
    },
    yearOptions() {
      const current = new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0);
      return Array.from({ length: 5 }, (_, i) => current - i);
    },
    totalTarget() {
      return this.summaryData.reduce((sum, item) => sum + parseFloat(item.target_amount || 0), 0);
    },
    totalCollected() {
      return this.summaryData.reduce((sum, item) => sum + parseFloat(item.total_collected || 0), 0);
    },
    totalStatement() {
      return this.summaryData.reduce((sum, item) => sum + parseFloat(item.total_statement || 0), 0);
    },
    percentage() {
      if (this.totalTarget === 0) return 0;
      return (this.totalCollected / this.totalTarget) * 100;
    },
    hasResponsibleTarget() {
      if (!this.userFullname) return false;
      return this.summaryData.some(item => item.responsible_person && item.responsible_person.includes(this.userFullname));
    }
  },
  methods: {
    formatCurrency(value) {
      if (!value) return '0.00';
      return parseFloat(value).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    formatPercent(value) {
      return parseFloat(value).toFixed(2);
    },
    calcPercent(target, collected) {
      if (!target || target == 0) return '0.00';
      return ((parseFloat(collected) / parseFloat(target)) * 100).toFixed(2);
    },
    formatDateThai(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      const months = [
        'ม.ค.',
        'ก.พ.',
        'มี.ค.',
        'เม.ย.',
        'พ.ค.',
        'มิ.ย.',
        'ก.ค.',
        'ส.ค.',
        'ก.ย.',
        'ต.ค.',
        'พ.ย.',
        'ธ.ค.'
      ];
      return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear() + 543}`;
    },
    getPercentColor(target, collected) {
      const pct = (parseFloat(collected) / parseFloat(target)) * 100;
      if (pct >= 100) return 'bg-success';
      if (pct >= 80) return 'bg-info';
      if (pct >= 50) return 'bg-warning text-dark';
      return 'bg-danger';
    },
    onYearChange() {
      this.selectedTargetFilter = '';
      this.fetchDashboardData();
    },
    async fetchDashboardData() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const res = await axios.get(
          `/api-digital/revenue/get_dashboard_summary.php?fiscal_year=${this.selectedYear}&target_id=${this.selectedTargetFilter}`,
          config
        );
        const resPrev = await axios.get(
          `/api-digital/revenue/get_dashboard_summary.php?fiscal_year=${this.selectedYear - 1}&target_id=${this.selectedTargetFilter}`,
          config
        );

        if (resPrev.data.status === 'success') {
          this.prevMonthlyData = resPrev.data.data.monthly;
        } else {
          this.prevMonthlyData = [];
        }

        if (res.data.status === 'success') {
          this.summaryData = res.data.data.summary;
          this.monthlyData = res.data.data.monthly;
          if (!this.selectedTargetFilter) {
            this.availableTargets = [...this.summaryData];
          }
          this.renderCharts();
          this.fetchComparisonData();
        }
      } catch (err) {
        console.error('Fetch error:', err);
      }
    },
    async fetchComparisonData() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};

        const [res1, res2] = await Promise.all([
          axios.get(
            `/api-digital/revenue/get_dashboard_summary.php?fiscal_year=${this.compareYear1}&target_id=${this.selectedTargetFilter}`,
            config
          ),
          axios.get(
            `/api-digital/revenue/get_dashboard_summary.php?fiscal_year=${this.compareYear2}&target_id=${this.selectedTargetFilter}`,
            config
          )
        ]);

        this.compMonthlyData1 = res1.data.status === 'success' ? res1.data.data.monthly : [];
        this.compMonthlyData2 = res2.data.status === 'success' ? res2.data.data.monthly : [];

        this.renderComparisonChart();
      } catch (err) {
        console.error('Fetch comparison error:', err);
      }
    },
    async fetchUserProfile() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data.status === 'success') {
          this.userDepartment = response.data.department || '';
          this.userFullname = response.data.fullname || '';
        }
      } catch (e) {
        console.error('Failed to load user profile', e);
      }
    },
    calculateResultAmount() {
      if (!this.selectedTarget || !this.resultForm.achieved_items) return;

      const priceStr = String(this.selectedTarget.unit_price || '').trim();
      const match = priceStr.match(/^[\d.]+/);

      if (match) {
        const price = parseFloat(match[0]);
        if (!isNaN(price)) {
          this.resultForm.collected_amount = (
            parseFloat(this.resultForm.achieved_items) * price
          ).toFixed(2);
        }
      }
    },
    async submitResultForm() {
      try {
        const token = localStorage.getItem('user_token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const res = await axios.post(
          '/api-digital/revenue/save_result.php',
          this.resultForm,
          config
        );

        if (res.data.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ',
            text: 'บันทึกผลงานสำเร็จ',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          });
          this.resultForm.achieved_items = '';
          this.resultForm.collected_amount = '';
          this.resultForm.remark = '';
          this.fetchResultsAfterSave(this.selectedTarget.target_id);
          this.fetchDashboardData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      }
    },
    async fetchResultsAfterSave(target_id) {
      try {
        const res = await axios.get(`/api-digital/revenue/get_results.php?target_id=${target_id}`);
        this.resultsData = res.data;
      } catch (err) {
        console.error(err);
      }
    },
    async openHistoryModal(item) {
      this.selectedTarget = item;
      this.resultForm.target_id = item.target_id;
      this.resultForm.achieved_items = '';
      this.resultForm.collected_amount = '';
      this.resultForm.remark = '';

      let el = this.$refs.historyModal;
      if (!el) el = document.getElementById('historyModal');

      if (el) {
        if (!this.historyModalInstance) {
          this.historyModalInstance = new Modal(el);
        }
        this.historyModalInstance.show();
      }

      this.resultsData = [];
      try {
        const res = await axios.get(
          `/api-digital/revenue/get_results.php?target_id=${item.target_id}`
        );
        this.resultsData = res.data;
      } catch (err) {
        console.error('Fetch history error:', err);
      }
    },
    async editResult(r) {
      const { value: formValues } = await Swal.fire({
        target: document.getElementById('historyModal'),
        title: 'แก้ไขผลงาน',
        html:
          `<div class="text-start mb-3"><label class="form-label">จำนวนผลงาน</label><input id="swal-achieved" type="number" step="0.01" class="form-control" value="${r.achieved_items !== null ? r.achieved_items : ''}"></div>` +
          `<div class="text-start mb-3"><label class="form-label">ยอดเรียกเก็บได้(บาท)</label><input id="swal-collected" type="number" step="0.01" class="form-control" value="${r.collected_amount}"></div>` +
          `<div class="text-start mb-3"><label class="form-label">หมายเหตุ</label><textarea id="swal-remark" class="form-control">${r.remark || ''}</textarea></div>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
          return {
            id: r.id,
            achieved_items: document.getElementById('swal-achieved').value,
            collected_amount: document.getElementById('swal-collected').value,
            remark: document.getElementById('swal-remark').value
          };
        }
      });

      if (formValues) {
        try {
          const token = localStorage.getItem('user_token');
          const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
          const res = await axios.post(
            '/api-digital/revenue/update_result.php',
            formValues,
            config
          );
          if (res.data.status === 'success') {
            Swal.fire({
              title: 'สำเร็จ!',
              text: 'แก้ไขข้อมูลเรียบร้อย',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            });
            this.openHistoryModal(this.selectedTarget);
            this.fetchDashboardData();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถแก้ไขได้', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'ตรวจพบปัญหาการเชื่อมต่อ', 'error');
        }
      }
    },
    async deleteResult(id) {
      const confirm = await Swal.fire({
        target: document.getElementById('historyModal'),
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบประวัติการจัดเก็บนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const token = localStorage.getItem('user_token');
          const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
          const res = await axios.get(`/api-digital/revenue/delete_result.php?id=${id}`, config);
          if (res.data.status === 'success') {
            Swal.fire({
              title: 'ลบแล้ว!',
              text: 'ข้อมูลถูกลบออกจากระบบ',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            });
            this.openHistoryModal(this.selectedTarget);
            this.fetchDashboardData();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถลบได้', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'ตรวจพบปัญหาการเชื่อมต่อ', 'error');
        }
      }
    },
    closeHistoryModal() {
      if (this.historyModalInstance) {
        this.historyModalInstance.hide();
      }
    },
    async openStatementModal(item) {
      this.selectedTarget = item;
      this.statementData = [];

      let el = this.$refs.statementModal;
      if (!el) el = document.getElementById('statementModal');
      if (el) {
        if (!this.statementModalInstance) {
          this.statementModalInstance = new Modal(el);
        }
        this.statementModalInstance.show();
      }
      this.fetchStatementData();
    },
    async fetchStatementData() {
      if (!this.selectedTarget) return;
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const res = await axios.get(
          `/api-digital/revenue/get_statements.php?target_id=${this.selectedTarget.target_id}`,
          config
        );
        this.statementData = res.data || [];
      } catch (e) {
        console.error('Fetch statements error:', e);
      }
    },
    async addStatementRecord() {
      this.showStatementForm();
    },
    async editStatementRecord(st) {
      this.showStatementForm(st);
    },
    async showStatementForm(st = null) {
      const monthOptions = [
        { value: 10, label: 'ตุลาคม' },
        { value: 11, label: 'พฤศจิกายน' },
        { value: 12, label: 'ธันวาคม' },
        { value: 1, label: 'มกราคม' },
        { value: 2, label: 'กุมภาพันธ์' },
        { value: 3, label: 'มีนาคม' },
        { value: 4, label: 'เมษายน' },
        { value: 5, label: 'พฤษภาคม' },
        { value: 6, label: 'มิถุนายน' },
        { value: 7, label: 'กรกฎาคม' },
        { value: 8, label: 'สิงหาคม' },
        { value: 9, label: 'กันยายน' }
      ];

      let optionsHtml = '';
      monthOptions.forEach((m) => {
        const selected = st && st.month == m.value ? 'selected' : '';
        optionsHtml += `<option value="${m.value}" ${selected}>${m.label}</option>`;
      });

      const today = new Date().toISOString().split('T')[0];
      const defaultDate = st ? st.statement_date : today;

      const { value: formValues } = await Swal.fire({
        target: document.getElementById('statementModal'),
        title: st ? 'แก้ไขข้อมูล Statement' : 'เพิ่มยอด Statement',
        html:
          `<div class="text-start mb-3"><label class="form-label">งวดเดือน</label><select id="stmt-month" class="form-select">${optionsHtml}</select></div>` +
          `<div class="text-start mb-3"><label class="form-label">วันที่ได้รับ</label><input id="stmt-date" type="date" class="form-control" value="${defaultDate}"></div>` +
          `<div class="text-start mb-3"><label class="form-label">ยอดจัดเก็บได้จริง(Statement) (บาท)</label><input id="stmt-amount" type="number" step="0.01" class="form-control" value="${st ? st.statement_amount : ''}"></div>` +
          `<div class="text-start mb-3"><label class="form-label">หมายเหตุ (ถ้ามี)</label><textarea id="stmt-remark" class="form-control">${st ? st.remark || '' : ''}</textarea></div>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
          const m = document.getElementById('stmt-month').value;
          const a = document.getElementById('stmt-amount').value;
          if (!m || !a) {
            Swal.showValidationMessage('กรุณากรอกงวดเดือนและยอด Statement');
            return false;
          }
          return {
            id: st ? st.id : null,
            target_id: this.selectedTarget.target_id,
            month: m,
            date: document.getElementById('stmt-date').value,
            amount: a,
            remark: document.getElementById('stmt-remark').value
          };
        }
      });

      if (formValues) {
        try {
          const token = localStorage.getItem('user_token');
          const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
          const res = await axios.post(
            '/api-digital/revenue/save_statement_record.php',
            formValues,
            config
          );

          if (res.data.status === 'success') {
            Swal.fire({
              title: 'สำเร็จ!',
              text: 'บันทึกยอด Statement เรียบร้อย',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            });
            this.fetchStatementData();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถบันทึกได้', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'ตรวจพบปัญหาการเชื่อมต่อ', 'error');
        }
      }
    },
    async deleteStatementRecord(id) {
      const confirm = await Swal.fire({
        target: document.getElementById('statementModal'),
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบรายการ Statement นี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const token = localStorage.getItem('user_token');
          const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
          const res = await axios.get(
            `/api-digital/revenue/delete_statement_record.php?id=${id}`,
            config
          );
          if (res.data.status === 'success') {
            Swal.fire({
              title: 'ลบแล้ว!',
              text: 'ลบรายการสำเร็จ',
              icon: 'success',
              timer: 1500,
              showConfirmButton: false
            });
            this.fetchStatementData();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถลบลายการได้', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'ตรวจพบปัญหา', 'error');
        }
      }
    },
    closeStatementModal() {
      if (this.statementModalInstance) {
        this.statementModalInstance.hide();
      }
    },
    getMonthName(monthNum) {
      if (!monthNum) return '';
      const fiscalMonths = [
        { value: 10, label: 'ตุลาคม' },
        { value: 11, label: 'พฤศจิกายน' },
        { value: 12, label: 'ธันวาคม' },
        { value: 1, label: 'มกราคม' },
        { value: 2, label: 'กุมภาพันธ์' },
        { value: 3, label: 'มีนาคม' },
        { value: 4, label: 'เมษายน' },
        { value: 5, label: 'พฤษภาคม' },
        { value: 6, label: 'มิถุนายน' },
        { value: 7, label: 'กรกฎาคม' },
        { value: 8, label: 'สิงหาคม' },
        { value: 9, label: 'กันยายน' }
      ];
      const m = fiscalMonths.find((x) => x.value == monthNum);
      return m ? m.label : monthNum;
    },
    renderCharts() {
      const labels = [
        'ต.ค.',
        'พ.ย.',
        'ธ.ค.',
        'ม.ค.',
        'ก.พ.',
        'มี.ค.',
        'เม.ย.',
        'พ.ค.',
        'มิ.ย.',
        'ก.ค.',
        'ส.ค.',
        'ก.ย.'
      ];
      const fyMonths = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];

      const dataMap = new Map();
      this.monthlyData.forEach((m) =>
        dataMap.set(parseInt(m.month), {
          collected: parseFloat(m.month_collected || 0),
          statement: parseFloat(m.month_statement || 0)
        })
      );
      const plotCollected = fyMonths.map((m) => (dataMap.has(m) ? dataMap.get(m).collected : 0));
      const plotStatement = fyMonths.map((m) => (dataMap.has(m) ? dataMap.get(m).statement : 0));

      // 1. Monthly Trend Chart
      const ctxTrend = document.getElementById('monthlyTrendChart');
      if (ctxTrend) {
        if (this.trendChartInstance) this.trendChartInstance.destroy();

        this.trendChartInstance = new Chart(ctxTrend, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'ยอดเรียกเก็บได้ (บาท)',
                data: plotCollected,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: '#28a745',
                borderWidth: 1,
                borderRadius: 4
              },
              {
                label: 'ยอดจัดเก็บได้จริง(Statement) (บาท)',
                data: plotStatement,
                backgroundColor: 'rgba(111, 66, 193, 0.7)',
                borderColor: '#6f42c1',
                borderWidth: 1,
                borderRadius: 4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // 2. Pie Chart
      const ctxPie = document.getElementById('categoryPieChart');
      if (ctxPie) {
        if (this.pieChartInstance) this.pieChartInstance.destroy();

        const pLabels = this.summaryData.map((s) => s.revenue_name);
        const pData = this.summaryData.map((s) => parseFloat(s.total_collected));

        this.pieChartInstance = new Chart(ctxPie, {
          type: 'doughnut',
          data: {
            labels: pLabels.length ? pLabels : ['No Data'],
            datasets: [
              {
                data: pData.length ? pData : [1],
                backgroundColor: [
                  '#0d6efd',
                  '#198754',
                  '#ffc107',
                  '#dc3545',
                  '#0dcaf0',
                  '#6610f2',
                  '#fd7e14',
                  '#20c997',
                  '#e83e8c'
                ],
                borderWidth: 0
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
            }
          }
        });
      }

      // 3. Yearly Comparison Line Chart
      // Moved to separate function renderComparisonChart
    },
    renderComparisonChart() {
      const labels = [
        'ต.ค.',
        'พ.ย.',
        'ธ.ค.',
        'ม.ค.',
        'ก.พ.',
        'มี.ค.',
        'เม.ย.',
        'พ.ค.',
        'มิ.ย.',
        'ก.ค.',
        'ส.ค.',
        'ก.ย.'
      ];
      const fyMonths = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];

      const ctxComp = document.getElementById('yearlyComparisonChart');
      if (ctxComp) {
        if (this.yearlyComparisonChartInstance) this.yearlyComparisonChartInstance.destroy();

        const dataMap1 = new Map();
        this.compMonthlyData1.forEach((m) =>
          dataMap1.set(parseInt(m.month), parseFloat(m.month_collected || 0))
        );

        const dataMap2 = new Map();
        this.compMonthlyData2.forEach((m) =>
          dataMap2.set(parseInt(m.month), parseFloat(m.month_collected || 0))
        );

        const plot1 = fyMonths.map((m) => (dataMap1.has(m) ? dataMap1.get(m) : 0));
        const plot2 = fyMonths.map((m) => (dataMap2.has(m) ? dataMap2.get(m) : 0));

        this.yearlyComparisonChartInstance = new Chart(ctxComp, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: `ปีงบฯ ${this.compareYear1}`,
                data: plot1,
                borderColor: '#6c757d',
                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
              },
              {
                label: `ปีงบฯ ${this.compareYear2}`,
                data: plot2,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: {
              legend: { position: 'top' },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    let label = context.dataset.label || '';
                    if (label) {
                      label += ': ';
                    }
                    if (context.parsed.y !== null) {
                      label +=
                        parseFloat(context.parsed.y).toLocaleString('th-TH', {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2
                        }) + ' บาท';
                    }
                    return label;
                  }
                }
              }
            }
          }
        });
      }
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchDashboardData();
  }
};
</script>

<style scoped>
.btn-outline-purple {
  color: #6f42c1;
  border-color: #6f42c1;
}
.btn-outline-purple:hover {
  color: #fff;
  background-color: #6f42c1;
  border-color: #6f42c1;
}
.border-purple {
  border-color: #6f42c1 !important;
}
.text-purple {
  color: #6f42c1 !important;
}
</style>
