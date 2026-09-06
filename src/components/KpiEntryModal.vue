<template>
  <div class="modal fade" id="kpiEntryModal" tabindex="-1" aria-hidden="true" ref="modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
        <!-- Header -->
        <div class="modal-header bg-white border-bottom py-3">
          <h5 class="modal-title fw-bold text-primary">
            <i class="bi bi-pencil-fill me-2"></i>บันทึกผลตัวชี้วัด
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>

        <div class="modal-body p-4 bg-white" style="min-height: 500px">
          <form @submit.prevent="submitResults">
            <!-- KPI Detail Box -->
            <div class="bg-light p-4 rounded-3 mb-4 border">
              <div class="small text-muted mb-1 fw-bold">ชื่อตัวชี้วัด</div>
              <h6 class="fw-bold lh-base">{{ selectedKpi?.name || 'ชื่อตัวชี้วัด' }}</h6>
              <div class="small text-muted mt-3 fw-bold">เป้าหมาย</div>
              <div class="text-primary fw-bold">{{ selectedKpi?.target_operator }} {{ selectedKpi?.target_value }} {{ selectedKpi?.unit }}</div>
            </div>

            <!-- Row 1: Year, Frequency, Period -->
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label fw-bold text-muted small">ปีงบประมาณ</label>
                <input type="number" v-model="form.year_thai" class="form-control border rounded-2" />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold text-muted small">การรายงานผล (Frequency)</label>
                <select class="form-select border rounded-2 bg-light" disabled>
                  <option>{{ periodLabel }}</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-bold text-muted small">เลือก</label>
                <select v-model="form.period_number" class="form-select border rounded-2" required>
                  <option value="" disabled>-- เลือก --</option>
                  <option v-for="p in periodOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <div v-if="form.period_number && !getPeriodStatus().open" class="text-danger small mt-1 fw-bold">
                  <i class="bi bi-exclamation-circle me-1"></i>{{ getPeriodStatus().message }}
                </div>
              </div>
            </div>

            <!-- Row 2: Responsible Person -->
            <div class="mb-4">
              <label class="form-label fw-bold text-muted small">ผู้รับผิดชอบ</label>
              <input type="text" class="form-control bg-light border rounded-2" :value="selectedKpi?.responsible_person || 'ไม่ระบุ'" readonly />
            </div>

            <!-- Row 3: Inputs (Table style) -->
            <div class="row g-0 mb-4 border rounded-2 overflow-hidden shadow-sm" v-if="!isCriteria">
              <div class="col-4 border-end">
                <div class="p-2 text-center bg-white text-muted small fw-bold">{{ numeratorLabel }}</div>
                <div class="p-2 bg-white"><input type="number" step="any" v-model.number="form.numerator" class="form-control border-0 text-center fw-bold fs-5" placeholder="-" /></div>
              </div>
              <div class="col-4 border-end">
                <div class="p-2 text-center bg-white text-muted small fw-bold">{{ denominatorLabel }}</div>
                <div class="p-2 bg-white"><input type="number" step="any" v-model.number="form.denominator" class="form-control border-0 text-center fw-bold fs-5" placeholder="-" /></div>
              </div>
              <div class="col-4">
                <div class="p-2 text-center bg-white text-muted small fw-bold">ตัวคูณ</div>
                <div class="p-2 bg-white"><input type="number" step="any" v-model.number="form.multiplier" class="form-control border-0 text-center fw-bold fs-5" placeholder="-" /></div>
              </div>
            </div>

            <!-- Criteria Input (Pass/Fail) -->
            <div class="row g-3 mb-4" v-else>
              <div class="col-12">
                <label class="form-label fw-bold text-muted small mb-3">ผลการประเมิน (เกณฑ์ประเมิน)</label>
                <div class="btn-group w-100 shadow-sm rounded-3" role="group">
                  <input type="radio" class="btn-check" name="criteriaResult" id="criteriaPass" autocomplete="off" :value="1" v-model="form.actual_value">
                  <label class="btn btn-outline-success p-3 fw-bold fs-5 d-flex align-items-center justify-content-center" for="criteriaPass">
                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>ผ่าน
                  </label>

                  <input type="radio" class="btn-check" name="criteriaResult" id="criteriaFail" autocomplete="off" :value="0" v-model="form.actual_value">
                  <label class="btn btn-outline-danger p-3 fw-bold fs-5 d-flex align-items-center justify-content-center" for="criteriaFail">
                    <i class="bi bi-x-circle-fill me-2 fs-4"></i>ไม่ผ่าน
                  </label>
                </div>
              </div>
            </div>

            <!-- Result Box -->
            <div class="bg-primary bg-opacity-10 p-4 rounded-3 mb-4 d-flex align-items-center justify-content-between border border-primary border-opacity-25 shadow-sm">
              <span class="fw-bold text-primary fs-5">ผลลัพธ์คำนวณ (Actual Value)</span>
              <div class="d-flex align-items-center">
                <div class="bg-white border border-primary border-opacity-25 rounded-2 d-flex align-items-center px-3 py-2 me-3 shadow-sm" style="min-width: 150px">
                  <input v-if="isCriteria" type="text" class="form-control border-0 p-0 text-center fw-bold text-dark fs-5 bg-transparent" :value="displayActualValue" readonly />
                  <input v-else type="number" step="any" class="form-control border-0 p-0 text-center fw-bold text-dark fs-5 bg-transparent" v-model="form.actual_value" placeholder="-" />
                </div>
                <span class="text-primary fw-bold" v-if="!isCriteria">{{ selectedKpi?.unit }}</span>
              </div>
            </div>

            <!-- History Table -->
            <div class="border rounded-3 p-0 mb-4 bg-white shadow-sm" v-if="historyList.length > 0">
              <div class="p-3 border-bottom bg-light d-flex align-items-center rounded-top-3">
                <i class="bi bi-clock-history me-2 text-muted"></i><span class="fw-bold text-muted small">ประวัติการรายงานผลย้อนหลัง</span>
              </div>
              <div class="table-responsive">
                <table class="table table-hover text-center mb-0 align-middle">
                  <thead class="text-muted small">
                    <tr>
                      <th class="py-3">รอบการรายงาน</th>
                      <th class="py-3">ตัวตั้ง(ผลงานที่ทำได้)</th>
                      <th class="py-3">ตัวหาร(จำนวนทั้งหมด)</th>
                      <th class="py-3">ตัวคูณ</th>
                      <th class="py-3">ผลลัพธ์</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="h in historyList" :key="h.id">
                      <td class="text-start ps-4">{{ formatDate(h.period_date) }}</td>
                      <td>{{ h.numerator || '-' }}</td>
                      <td>{{ h.denominator || '-' }}</td>
                      <td>{{ h.multiplier || '-' }}</td>
                      <td class="fw-bold text-dark" :class="getDisplayValueClass(h.actual_value)">{{ getDisplayValue(h.actual_value) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-outline-secondary px-4 py-2 bg-white fw-bold shadow-sm" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" :disabled="submitting || !getPeriodStatus().open">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <i class="bi bi-save me-1" v-else></i> บันทึกผลการดำเนินงาน
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

export default {
  name: 'KpiEntryModal',
  data() {
    return {
      bsModal: null,
      selectedKpi: null,
      submitting: false,
      periodOptions: [],
      schedule: [],
      historyList: [],
      form: {
        kpi_id: '',
        year_thai: (() => {
          const d = new Date();
          return d.getMonth() >= 9 ? d.getFullYear() + 543 + 1 : d.getFullYear() + 543;
        })(),
        period_number: '',
        actual_value: '',
        numerator: '',
        denominator: '',
        multiplier: '' // Now an input
      }
    };
  },
  computed: {
    periodLabel() {
      const type = this.selectedKpi?.kpi_periodicity || 'month';
      if (type === 'quarter') return 'ไตรมาส';
      if (type === 'Semiannual report') return 'ครึ่งปี';
      if (type === 'year') return 'ปีงบประมาณ';
      return 'เดือน';
    },
    numeratorLabel() {
      return this.selectedKpi?.numerator_label || 'ตัวตั้ง(ผลงานที่ทำได้)';
    },
    denominatorLabel() {
      return this.selectedKpi?.denominator_label || 'ตัวหาร(จำนวนทั้งหมด)';
    },
    isCriteria() {
      const unit = this.selectedKpi?.unit || '';
      return unit === 'เกณฑ์ประเมิน' || unit === 'เกณประเมิน';
    },
    displayActualValue() {
      if (this.isCriteria) {
        if (this.form.actual_value === 1 || this.form.actual_value === '1') return 'ผ่าน';
        if (this.form.actual_value === 0 || this.form.actual_value === '0') return 'ไม่ผ่าน';
        return '-';
      }
      return this.form.actual_value !== '' && this.form.actual_value !== null ? this.form.actual_value : '-';
    }
  },
  watch: {
    'form.numerator': 'calculateActual',
    'form.denominator': 'calculateActual',
    'form.multiplier': 'calculateActual',
    'form.year_thai': 'fetchSchedule',
    selectedKpi(newVal) {
      if (newVal) {
        this.generatePeriodOptions(newVal.kpi_periodicity);
        // Pre-fill multiplier if available
        if (newVal.multiplier && parseFloat(newVal.multiplier) > 0) {
          this.form.multiplier = parseFloat(newVal.multiplier);
        } else {
          // Defaults if not set
          if (newVal.calculation_type === 'rate_100k') this.form.multiplier = 100000;
          else if (newVal.calculation_type === 'percentage') this.form.multiplier = 100;
          else this.form.multiplier = 1;
        }
      }
    }
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.modal);
    this.fetchSchedule();
  },
  methods: {
    generatePeriodOptions(type) {
      this.form.period_number = ''; // Reset selection

      const now = new Date();
      const currentCalendarMonth = now.getMonth() + 1; // 1-12
      const currentCalendarYear = now.getFullYear();
      const currentFiscalYear = now.getMonth() >= 9 ? currentCalendarYear + 543 + 1 : currentCalendarYear + 543;
      
      const currentFiscalMonth = currentCalendarMonth >= 10 ? currentCalendarMonth - 9 : currentCalendarMonth + 3;
      const currentFiscalQuarter = Math.ceil(currentFiscalMonth / 3);
      const currentFiscalHalf = Math.ceil(currentFiscalQuarter / 2);

      const selectedYear = parseInt(this.form.year_thai) || currentFiscalYear;

      // Check if a period index (fiscal-based) has arrived
      const isArrived = (fiscalIndex, periodType) => {
        if (selectedYear < currentFiscalYear) return true;
        if (selectedYear > currentFiscalYear) return false;
        
        if (periodType === 'month') return fiscalIndex <= currentFiscalMonth;
        if (periodType === 'quarter') return fiscalIndex <= currentFiscalQuarter;
        if (periodType === 'half') return fiscalIndex <= currentFiscalHalf;
        if (periodType === 'year') return true; // Year is always available for the current fiscal year
        return true;
      };

      if (type === 'quarter') {
        const allQuarters = [
          { id: 1, name: 'ไตรมาสที่ 1 (ต.ค. - ธ.ค.)' },
          { id: 2, name: 'ไตรมาสที่ 2 (ม.ค. - มี.ค.)' },
          { id: 3, name: 'ไตรมาสที่ 3 (เม.ย. - มิ.ย.)' },
          { id: 4, name: 'ไตรมาสที่ 4 (ก.ค. - ก.ย.)' }
        ];
        this.periodOptions = allQuarters.filter(q => isArrived(q.id, 'quarter'));
        if (this.periodOptions.length > 0) {
          this.form.period_number = this.periodOptions[this.periodOptions.length - 1].id;
        }
      } else if (type === 'Semiannual report') {
        const allHalves = [
          { id: 1, name: 'ครึ่งปีแรก (ต.ค. - มี.ค.)' },
          { id: 2, name: 'ครึ่งปีหลัง (เม.ย. - ก.ย.)' }
        ];
        this.periodOptions = allHalves.filter(h => isArrived(h.id, 'half'));
        if (this.periodOptions.length > 0) {
          this.form.period_number = this.periodOptions[this.periodOptions.length - 1].id;
        }
      } else if (type === 'year') {
        this.periodOptions = [{ id: 1, name: 'ปีงบประมาณ' }];
        if (isArrived(1, 'year')) {
          this.form.period_number = 1;
        } else {
          this.periodOptions = [];
        }
      } else {
        // Month
        const thaiMonths = [
          'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
          'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        
        // Map calendar months to check fiscal arrival
        const allMonths = thaiMonths.map((m, i) => {
          const id = i + 1;
          const fiscalIndex = id >= 10 ? id - 9 : id + 3;
          return { id, name: m, fiscalIndex };
        });
        
        this.periodOptions = allMonths
          .filter(m => isArrived(m.fiscalIndex, 'month'))
          .map(m => ({ id: m.id, name: m.name }));

        if (this.periodOptions.length > 0) {
          if (selectedYear === currentFiscalYear) {
            // Find current calendar month in the options, or fallback to the last valid one
            const currentOpt = this.periodOptions.find(p => p.id === currentCalendarMonth);
            this.form.period_number = currentOpt ? currentOpt.id : this.periodOptions[this.periodOptions.length - 1].id;
          } else {
            // For past years, usually September (id 9) is the last fiscal month
            this.form.period_number = 9;
          }
        }
      }
    },
    open(kpi) {
      this.selectedKpi = kpi;
      this.form.kpi_id = kpi.id;
      this.form.actual_value = '';
      this.form.numerator = '';
      this.form.denominator = '';
      
      this.fetchHistoryData(kpi.id);
      this.bsModal.show();
    },
    openForEdit(kpi, payload) {
      this.selectedKpi = kpi;
      this.form.kpi_id = kpi.id;
      this.form.year_thai = payload.year_thai;
      
      this.generatePeriodOptions(kpi.kpi_periodicity);
      
      this.form.period_number = payload.period_number;
      this.form.actual_value = payload.actual_value;
      this.form.numerator = payload.numerator;
      this.form.denominator = payload.denominator;
      
      this.fetchHistoryData(kpi.id);
      this.bsModal.show();
    },
    async fetchHistoryData(kpiId) {
      this.historyList = [];
      try {
        const res = await axios.get(`/api-digital/kpi/get_kpi_history.php?kpi_id=${kpiId}`);
        if (res.data.status === 'success') {
          this.historyList = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    hide() {
      this.bsModal.hide();
    },
    setCriteriaValue(val) {
      this.form.actual_value = val;
    },
    calculateActual() {
      if (this.isCriteria) return; // Safeguard: don't auto-calculate for criteria inputs
      
      const num = parseFloat(this.form.numerator);
      const den = parseFloat(this.form.denominator);
      const mult = parseFloat(this.form.multiplier);

      if (!isNaN(num) && !isNaN(den) && den !== 0 && !isNaN(mult)) {
        this.form.actual_value = ((num / den) * mult).toFixed(2);
      } else {
        this.form.actual_value = '';
      }
    },
    async fetchSchedule() {
      try {
        const res = await axios.get(
          `/api-digital/kpi/get_kpi_schedule.php?year=${this.form.year_thai}`
        );
        if (res.data.status === 'success') {
          this.schedule = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    getPeriodStatus() {
      // If period type is not month, skip check for now (as we only seeded months)
      if (this.selectedKpi?.kpi_periodicity !== 'month') return { open: true };
      if (!this.form.period_number || this.schedule.length === 0) return { open: true };

      const period = this.schedule.find((s) => s.period_number == this.form.period_number);
      if (!period) return { open: true };

      const now = new Date();
      const start = new Date(period.input_start_date + 'T00:00:00');
      const end = new Date(period.input_end_date + 'T23:59:59');

      const isOpen = now >= start && now <= end;
      return {
        open: isOpen,
        message: isOpen
          ? ''
          : `ไม่อยู่ในช่วงเวลาบันทึก (${this.formatDate(start)} - ${this.formatDate(end)})`
      };
    },
    formatDate(d) {
      if (!d) return '-';
      const dateObj = typeof d === 'string' ? new Date(d) : d;
      return dateObj.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    },
    async submitResults() {
      if (!this.selectedKpi) return;
      this.submitting = true;

      const payload = {
        ...this.form,
        target_value_snapshot: this.selectedKpi.target_value
      };

      try {
        const res = await axios.post('/api-digital/kpi/save_result_kpi.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            timer: 1500,
            showConfirmButton: false
          });
          this.hide();
          this.$emit('saved');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Connection Error', 'error');
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>
