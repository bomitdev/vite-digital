<template>
  <div class="container mt-5">
    <div class="card shadow-lg rounded-0 overflow-hidden mb-5 border-0">
      <div
        class="card-header bg-purple text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2"
      >
        <h4 class="mb-0 fw-bold">กำหนดตัวชี้วัด KPI (Admin)</h4>
        <div>
          <button
            class="btn btn-warning text-dark rounded-pill px-3 fw-bold me-2"
            @click="$refs.fileInput.click()"
          >
            <i class="bi bi-file-earmark-excel-fill me-1"></i> นำเข้าไฟล์ KPI
          </button>
          <input
            type="file"
            ref="fileInput"
            @change="handleFileUpload"
            accept=".xlsx, .xls"
            class="d-none"
          />
          <button
            class="btn btn-light text-dark rounded-pill px-3 fw-bold"
            @click="$router.push('/home-backoffice')"
          >
            <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>
      <div class="card-body p-4 bg-white">
        <form @submit.prevent="submitForm">
          <!-- Row: KPI Code & Name -->
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">รหัสตัวชี้วัด</label>
              <input
                type="text"
                v-model="form.kpi_code"
                class="form-control border-dark rounded-0 px-3 py-2"
                placeholder="เช่น KPI-001"
              />
            </div>
            <div class="col-md-9">
              <label class="form-label fw-bold">ชื่อตัวชี้วัด</label>
              <input
                type="text"
                v-model="form.kpi_name"
                class="form-control border-dark rounded-0 px-3 py-2"
                required
              />
            </div>
          </div>

          <!-- Description -->
          <div class="mb-4">
            <label class="form-label text-muted">รายละเอียดตัวชี้วัด</label>
            <input
              type="text"
              v-model="form.description"
              class="form-control border-dark rounded-0 px-3 py-2"
            />
          </div>

          <!-- Row 1: Fiscal Year, Category, Level -->
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label fw-bold">ปีงบประมาณ</label>
              <select
                v-model="form.fiscal_year"
                class="form-select border-dark rounded-0 px-3 py-2"
                required
              >
                <option v-for="y in fiscalYearList" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-bold">หมวดหมู่</label>
              <select
                v-model="form.category_id"
                class="form-select border-dark rounded-0 px-3 py-2"
                required
              >
                <option value="">-- เลือกหมวดหมู่ --</option>
                <option v-for="cat in masterData.categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">ระดับตัวชี้วัด</label>
              <select v-model="form.kpi_level" class="form-select border-dark rounded-0 px-3 py-2">
                <option value="">-- เลือกระดับ --</option>
                <option v-for="l in masterData.levels" :key="l.id" :value="l.name">
                  {{ l.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Row 2: Target, Operator, Unit -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">เป้าหมาย</label>
              <input
                type="number"
                step="0.01"
                v-model="form.target_value"
                class="form-control border-dark rounded-0 px-3 py-2"
                required
              />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">เครื่องหมาย</label>
              <select
                v-model="form.target_operator"
                class="form-select border-dark rounded-0 px-3 py-2"
              >
                <option value=">=">≥</option>
                <option value="<=">≤</option>
                <option value=">">></option>
                <option value="<"><</option>
                <option value="=">=</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">หน่วยนับ</label>
              <select v-model="form.unit" class="form-select border-dark rounded-0 px-3 py-2">
                <option value="">-- เลือกหน่วยนับ --</option>
                <option v-for="u in masterData.units" :key="u.id" :value="u.name">
                  {{ u.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Row 3: Responsible Person & Unit -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-bold">ผู้รับผิดชอบ</label>
              <div class="position-relative">
                <input
                  type="text"
                  v-model="staffInput"
                  class="form-control border-dark rounded-0 px-3 py-2"
                  placeholder="พิมพ์ชื่อเพื่อค้นหา..."
                  @focus="showStaffDropdown = true"
                  @blur="hideStaffDropdown"
                  @input="showStaffDropdown = true"
                />
                <!-- Custom Dropdown -->
                <ul
                  v-if="showStaffDropdown && filteredStaff.length > 0"
                  class="list-group position-absolute w-100 shadow-lg"
                  style="z-index: 1050; max-height: 200px; overflow-y: auto; top: 100%"
                >
                  <li
                    v-for="staff in filteredStaff"
                    :key="staff.ID"
                    class="list-group-item list-group-item-action cursor-pointer"
                    @mousedown.prevent="selectStaff(staff)"
                  >
                    {{ staff.FULLNAME }}
                    <small class="text-muted d-block" v-if="staff.HR_DEPARTMENT_SUB_NAME">
                      {{ staff.HR_DEPARTMENT_SUB_NAME }}
                    </small>
                  </li>
                </ul>

                <div class="mt-2" v-if="responsiblePersonList.length > 0">
                  <span
                    v-for="(person, index) in responsiblePersonList"
                    :key="index"
                    class="badge bg-primary-custom text-white me-1 mb-1 rounded-pill"
                  >
                    {{ person }}
                    <i
                      class="bi bi-x-circle ms-1 cursor-pointer"
                      @click="removeStaff(index)"
                      style="cursor: pointer"
                    ></i>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">หน่วยงาน</label>
              <input
                type="text"
                v-model="form.responsible_unit"
                class="form-control border-dark rounded-0 px-3 py-2"
              />
            </div>
          </div>

          <!-- Row 4: Frequency -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-bold">การรายงานผล (Frequency)</label>
              <select
                v-model="form.kpi_periodicity"
                class="form-select border-dark rounded-0 px-3 py-2"
              >
                <option v-for="p in masterData.periodicities" :key="p.id" :value="p.code">
                  {{ p.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Custom Formula Settings (Collapsible or just standard) -->

          <!-- Submit Button -->
          <div class="d-flex justify-content-center gap-3">
            <button
              type="submit"
              class="btn btn-primary-custom px-5 py-2 fs-5 fw-bold rounded-1 shadow-sm"
              style="min-width: 200px"
            >
              {{ isEdit ? 'อัพเดทข้อมูล' : 'บันทึกตัวชี้วัด' }}
            </button>
            <button
              type="button"
              v-if="isEdit"
              @click="resetForm"
              class="btn btn-outline-secondary px-4 py-2 rounded-1"
            >
              ยกเลิก
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ตารางข้อมูล KPI -->
    <div class="card shadow-sm rounded-0 border-0" v-if="kpis.length > 0">
      <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark">รายการ KPI ทั้งหมด</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="py-3 ps-3">Category</th>
                <th class="py-3">ชื่อ KPI</th>
                <th class="py-3">Target</th>
                <th class="py-3">ผู้รับผิดชอบ</th>
                <th class="py-3 pe-3 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="kpi in kpis" :key="kpi.id">
                <td class="ps-3">
                  <span class="badge bg-light text-dark border">{{
                    kpi.category_name || 'Uncategorized'
                  }}</span>
                </td>
                <td>
                  <div class="fw-bold">
                    <span class="text-muted me-1" v-if="kpi.code">[{{ kpi.code }}]</span>
                    {{ kpi.name }}
                  </div>
                  <small class="text-muted d-block text-truncate" style="max-width: 300px">{{
                    kpi.description
                  }}</small>
                </td>
                <td>
                  <span class="fw-bold text-primary-custom"
                    >{{ kpi.target_operator }} {{ kpi.target_value }}</span
                  >
                  <span class="small text-muted ms-1">{{ kpi.unit }}</span>
                </td>
                <td>
                  <div class="small">{{ kpi.responsible_person }}</div>
                </td>
                <td class="pe-3 text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-light border me-1"
                    @click.stop="openTrendModal(kpi)"
                    title="Trend"
                  >
                    <i class="bi bi-graph-up text-info"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light border me-1"
                    @click.stop="openHistoryModal(kpi)"
                    title="History"
                  >
                    <i class="bi bi-clock-history text-secondary"></i>
                  </button>
                  <button class="btn btn-sm btn-light border me-1" @click="editKpi(kpi)">
                    <i class="bi bi-pencil text-warning"></i>
                  </button>
                  <button class="btn btn-sm btn-light border" @click="deleteKpi(kpi.id)">
                    <i class="bi bi-trash text-danger"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Trend Modal -->
    <div class="modal fade" id="trendModal" tabindex="-1" ref="trendModal" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">แนวโน้ม (Trend) - {{ selectedKpi?.name }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              @click="closeTrendModal"
            ></button>
          </div>
          <div class="modal-body">
            <div style="position: relative; height: 400px; width: 100%">
              <canvas id="trendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" ref="historyModal" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-secondary text-white">
            <h5 class="modal-title">ประวัติการรายงาน (History) - {{ selectedKpi?.name }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              @click="closeHistoryModal"
            ></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped text-center">
                <thead>
                  <tr>
                    <th>วันที่/รอบ</th>
                    <th>เป้าหมาย</th>
                    <th>ผลงาน (Actual)</th>
                    <th>ผลการประเมิน</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in historyData" :key="h.id">
                    <td>{{ formatDate(h.period_date) }}</td>
                    <td>{{ h.target_value_snapshot }}</td>
                    <td>{{ h.actual_value }}</td>
                    <td>
                      <span v-if="checkPass(h)" class="badge bg-success">ผ่าน</span>
                      <span v-else class="badge bg-danger">ไม่ผ่าน</span>
                    </td>
                  </tr>
                  <tr v-if="historyData.length === 0">
                    <td colspan="4" class="text-center">ไม่พบข้อมูล</td>
                  </tr>
                </tbody>
              </table>
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
import { Modal } from 'bootstrap';
import Chart from 'chart.js/auto';
import * as XLSX from 'xlsx';

export default {
  name: 'KpiSetup',
  data() {
    return {
      kpis: [],
      isEdit: false,
      selectedKpi: null,
      historyData: [],
      trendModalInstance: null,
      historyModalInstance: null,
      chartInstance: null,
      form: {
        id: null,
        kpi_code: '',
        category_id: '',
        kpi_name: '',
        calculation_type: 'percentage',
        kpi_level: 'โรงพยาบาล',
        kpi_periodicity: 'month',
        description: '',
        target_value: '',
        target_operator: '>=',
        unit: 'เปอร์เซนต์',
        responsible_person: '',
        responsible_unit: '',
        fiscal_year: new Date().getFullYear() + 543
      },
      staffList: [],
      responsiblePersonList: [],
      staffInput: '',
      showStaffDropdown: false,
      fiscalYearList: [],
      masterData: {
        levels: [],
        periodicities: [],
        units: [],
        calculation_types: [],
        categories: []
      }
    };
  },
  computed: {
    filteredStaff() {
      if (!this.staffInput) return [];
      const query = this.staffInput.toLowerCase();
      return this.staffList.filter((staff) => staff.FULLNAME.toLowerCase().includes(query));
    }
  },
  methods: {
    async handleFileUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const json = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

          if (!json || json.length === 0) {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลในไฟล์ Excel', 'error');
            return;
          }

          const kpisToImport = json.map((row) => {
            let category_id = null;
            if (row['category_id'] && !isNaN(row['category_id'])) {
              category_id = parseInt(row['category_id']);
            } else if (row['category_name']) {
              const cat = this.masterData.categories.find(
                (c) =>
                  c.name.toLowerCase() === row['category_name'].toLowerCase() ||
                  row['category_name'].includes(c.name)
              );
              if (cat) category_id = cat.id;
            }
            if (!category_id && this.masterData.categories.length > 0) {
              category_id = this.masterData.categories[0].id;
            }

            return {
              kpi_code: row['code'] || '',
              category_id: category_id,
              fiscal_year: row['fiscal_year'] || new Date().getFullYear() + 543,
              kpi_name: row['name'] || '',
              description: row['description'] || '',
              calculation_type: row['calculation_type'] || 'percentage',
              kpi_level: row['kpi_level'] || 'โรงพยาบาล',
              kpi_periodicity: row['kpi_periodicity'] || 'month',
              target_value: row['target_value'] || 0,
              target_operator: row['target_operator'] || '>=',
              unit: row['unit'] || 'เปอร์เซนต์',
              responsible_person: row['responsible_person'] || '',
              responsible_unit: row['responsible_unit'] || ''
            };
          });

          const confirm = await Swal.fire({
            title: 'ยืนยันการนำเข้า?',
            text: `พบข้อมูลทั้งหมด ${kpisToImport.length} รายการ คุณต้องการนำเข้าสู่ระบบหรือไม่? (หากมีรหัสหรือชื่อซ้ำในปีงบเดียวกันจะถูกอัปเดต)`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก'
          });

          if (confirm.isConfirmed) {
            Swal.fire({
              title: 'กำลังนำเข้าข้อมูล',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            const res = await axios.post('/api-digital/kpi/import_kpi.php', kpisToImport);
            if (res.data.status === 'success') {
              Swal.fire('นำเข้าสำเร็จ', res.data.message, 'success');
              this.fetchKPIs();
            } else {
              Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถนำเข้าได้', 'error');
            }
          }
        } catch (error) {
          console.error('Error importing Excel:', error);
          Swal.fire('ข้อผิดพลาด', 'รูปแบบไฟล์ไม่ถูกต้อง หรือเกิดปัญหาในการอ่านไฟล์', 'error');
        } finally {
          if (this.$refs.fileInput) {
            this.$refs.fileInput.value = null;
          }
        }
      };
      reader.readAsArrayBuffer(file);
    },
    async fetchMasterData() {
      try {
        const res = await axios.get('/api-digital/kpi/get_master_data.php');
        if (res.data.status === 'success') {
          this.masterData = res.data.data;
        }
      } catch (err) {
        console.error('Fetch Master Data Error:', err);
      }
    },
    generateFiscalYears() {
      const current = new Date().getFullYear() + 543;
      for (let i = current + 1; i >= current - 5; i--) {
        this.fiscalYearList.push(i);
      }
    },
    async fetchHistoryOnly(id) {
      try {
        const res = await axios.get(`/api-digital/kpi/get_kpi_history.php?kpi_id=${id}`);
        return res.data.data || [];
      } catch (err) {
        console.error(err);
        return [];
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      return d.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    checkPass(h) {
      // Simple pass logic (actual >= target)
      // Ideally should check operator from definition, but for history snapshot we might lack operator.
      // We can use the selectedKpi operator.
      const op = this.selectedKpi?.target_operator || '>=';
      const actual = parseFloat(h.actual_value);
      const target = parseFloat(h.target_value_snapshot);

      if (op === '>=') return actual >= target;
      if (op === '<=') return actual <= target;
      if (op === '>') return actual > target;
      if (op === '<') return actual < target;
      if (op === '=') return actual == target;
      return false;
    },
    openTrendModal(kpi) {
      console.log('Open Trend Modal', kpi);
      this.selectedKpi = kpi;
      let el = this.$refs.trendModal;
      if (!el) el = document.getElementById('trendModal');

      if (el) {
        if (!this.trendModalInstance) {
          this.trendModalInstance = new Modal(el);
        }
        this.trendModalInstance.show();
      } else {
        console.error('Trend Modal element not found');
      }

      this.fetchHistoryOnly(kpi.id).then((data) => {
        setTimeout(() => {
          this.renderChart(data);
        }, 200);
      });
    },
    closeTrendModal() {
      if (this.trendModalInstance) this.trendModalInstance.hide();
    },
    renderChart(data) {
      const ctx = document.getElementById('trendChart');
      if (!ctx) return;
      if (this.chartInstance) this.chartInstance.destroy();

      // Sort chronological
      const sorted = [...data].reverse();

      this.chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: sorted.map((d) => this.formatDate(d.period_date)),
          datasets: [
            {
              label: 'ผลงาน (Actual)',
              data: sorted.map((d) => d.actual_value),
              borderColor: '#304ffe',
              backgroundColor: 'rgba(48, 79, 254, 0.1)',
              tension: 0.3,
              fill: true
            },
            {
              label: 'เป้าหมาย (Target)',
              data: sorted.map((d) => d.target_value_snapshot),
              borderColor: '#f44336',
              borderDash: [5, 5],
              fill: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    },
    openHistoryModal(kpi) {
      console.log('Open History Modal', kpi);
      this.selectedKpi = kpi;
      let el = this.$refs.historyModal;
      if (!el) el = document.getElementById('historyModal');

      if (el) {
        if (!this.historyModalInstance) {
          this.historyModalInstance = new Modal(el);
        }
        this.historyModalInstance.show();
      } else {
        console.error('History Modal element not found');
      }

      this.fetchHistoryOnly(kpi.id).then((data) => {
        this.historyData = data;
      });
    },
    closeHistoryModal() {
      if (this.historyModalInstance) this.historyModalInstance.hide();
    },
    hideStaffDropdown() {
      // Small delay to allow click event to register
      setTimeout(() => {
        this.showStaffDropdown = false;
      }, 200);
    },
    selectStaff(staff) {
      const val = staff.FULLNAME;
      if (!this.responsiblePersonList.includes(val)) {
        this.responsiblePersonList.push(val);

        // Auto-fill responsible unit
        if (staff.HR_DEPARTMENT_SUB_NAME) {
          const currentUnit = this.form.responsible_unit;
          if (!currentUnit) {
            this.form.responsible_unit = staff.HR_DEPARTMENT_SUB_NAME;
          } else if (!currentUnit.includes(staff.HR_DEPARTMENT_SUB_NAME)) {
            this.form.responsible_unit += `, ${staff.HR_DEPARTMENT_SUB_NAME}`;
          }
        }
      }
      this.form.responsible_person = this.responsiblePersonList.join(', ');
      this.staffInput = '';
      this.showStaffDropdown = false;
    },
    removeStaff(index) {
      this.responsiblePersonList.splice(index, 1);
      this.form.responsible_person = this.responsiblePersonList.join(', ');
    },
    async fetchKPIs() {
      try {
        const res = await axios.get('/api-digital/kpi/get_kpis.php');
        this.kpis = res.data;
      } catch (err) {
        console.error('Fetch KPIs error:', err);
      }
    },
    async fetchStaff() {
      try {
        // Fetch from api-hosoffice
        // Use relative path but need to go up if we are in api-digital?
        // No, frontend is consistent. We can use /api-hosoffice via proxy if defined.
        // Let's check vite.config again?
        // Yes, /api-hosoffice is proxied.
        const res = await axios.get('/api-hosoffice/get_all_staff.php');
        if (res.data.status === 'success') {
          this.staffList = res.data.data;
        }
      } catch (err) {
        console.error('Fetch Staff error:', err);
      }
    },
    async submitForm() {
      // Ensure string is updated before submit
      this.form.responsible_person = this.responsiblePersonList.join(', ');
      try {
        const res = await axios.post('/api-digital/kpi/save_kpi.php', this.form);
        if (res.data.status === 'success') {
          Swal.fire('Saved!', 'บันทึกข้อมูลสำเร็จ', 'success');
          this.resetForm();
          this.fetchKPIs();
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        Swal.fire('Error', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      }
    },
    editKpi(kpi) {
      this.isEdit = true;
      this.form = {
        id: kpi.id,
        kpi_code: kpi.code || '',
        category_id: kpi.category_id,
        kpi_name: kpi.name,
        description: kpi.description,
        calculation_type: kpi.calculation_type || 'percentage',
        kpi_level: kpi.kpi_level || 'โรงพยาบาล',
        kpi_periodicity: kpi.kpi_periodicity || 'month',
        target_value: kpi.target_value,
        target_operator: kpi.target_operator,
        unit: kpi.unit,
        responsible_person: kpi.responsible_person,
        responsible_unit: kpi.responsible_unit,
        fiscal_year: kpi.fiscal_year || new Date().getFullYear() + 543
      };

      // Load tags
      if (kpi.responsible_person) {
        this.responsiblePersonList = kpi.responsible_person
          .split(',')
          .map((s) => s.trim())
          .filter((s) => s);
      } else {
        this.responsiblePersonList = [];
      }
      this.staffInput = '';

      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    async deleteKpi(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'ข้อมูลที่เกี่ยวข้องทั้งหมดจะถูกลบ!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบข้อมูล'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/kpi/delete_kpi.php', { id });
          if (res.data.status === 'success') {
            Swal.fire('Deleted!', 'ลบข้อมูลเรียบร้อย', 'success');
            this.fetchKPIs();
          } else {
            Swal.fire('Error', 'ลบไม่สำเร็จ', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error', 'เกิดข้อผิดพลาด', 'error');
        }
      }
    },
    resetForm() {
      this.isEdit = false;
      this.form = {
        id: null,
        kpi_code: '',
        category_id: '',
        kpi_name: '',
        calculation_type: 'percentage',
        kpi_level: 'โรงพยาบาล',
        kpi_periodicity: 'month',
        description: '',
        target_value: '',
        target_operator: '>=',
        unit: '%',
        responsible_person: '',
        responsible_unit: '',
        fiscal_year: new Date().getFullYear() + 543
      };
      this.responsiblePersonList = [];
      this.staffInput = '';
      this.showStaffDropdown = false;
    }
  },
  mounted() {
    this.generateFiscalYears();
    this.fetchMasterData();
    this.fetchKPIs();
    this.fetchStaff();
  }
};
</script>

<style scoped>
.bg-purple {
  background-color: #4a148c; /* Deep purple */
}
.btn-primary-custom {
  background-color: #304ffe; /* Bright blue */
  color: white;
  border: none;
  transition: all 0.2s;
}
.btn-primary-custom:hover {
  background-color: #1a237e;
  transform: translateY(-1px);
}
.text-primary-custom {
  color: #304ffe;
}
.badge.bg-primary-custom {
  background-color: #304ffe !important;
}
.cursor-pointer {
  cursor: pointer;
}
</style>
