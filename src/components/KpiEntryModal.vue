<template>
  <div class="modal fade" id="kpiEntryModal" tabindex="-1" aria-hidden="true" ref="modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg rounded-0 overflow-hidden">
        <!-- Header -->
        <div class="modal-header bg-purple text-white border-0 py-3">
          <h5 class="modal-title fw-bold">บันทึกผล KPI</h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>

        <div class="modal-body p-4 bg-light-gray" style="min-height: 500px">
          <form @submit.prevent="submitResults">
            <!-- KPI Detail Box -->
            <div class="card border border-dark rounded-0 mb-4 shadow-sm">
              <div class="card-body">
                <h5 class="fw-bold mb-1">{{ selectedKpi?.name || 'ชื่อตัวชี้วัด' }}</h5>
                <p class="text-muted mb-1">
                  {{ selectedKpi?.description || 'รายละเอียดตัวชี้วัด' }}
                </p>
                <div class="text-dark">
                  <span class="fw-bold">เป้าหมาย:</span> {{ selectedKpi?.target_operator }}
                  {{ selectedKpi?.target_value }} {{ selectedKpi?.unit }}
                </div>
              </div>
            </div>

            <!-- Period Selection (Visual only, controls logic partially) -->
            <div class="row g-3 mb-4">
              <div class="col-6">
                <!-- Year -->
                <div
                  class="bg-white border border-dark p-2 text-center h-100 d-flex flex-column justify-content-center"
                >
                  <div class="fw-bold fs-5">ปีงบประมาณ {{ form.year_thai }}</div>
                  <input
                    type="number"
                    v-model="form.year_thai"
                    class="form-control text-center mt-1 border-0 p-0"
                    style="display: none"
                  />
                  <!-- Hidden logic, shown text -->
                </div>
              </div>
              <div class="col-6">
                <!-- Period (Month/Quarter) -->
                <div class="bg-white border border-dark p-2 h-100">
                  <div class="text-center fw-bold fs-5 mb-1">{{ periodLabel }}</div>
                  <select
                    v-model="form.period_number"
                    class="form-select border-0 text-center fw-bold py-0"
                    style="font-size: 1.1rem; cursor: pointer"
                    required
                  >
                    <option value="" disabled>-- เลือก --</option>
                    <option v-for="p in periodOptions" :key="p.id" :value="p.id">
                      {{ p.name }}
                    </option>
                  </select>
                  <div
                    v-if="form.period_number && !getPeriodStatus().open"
                    class="text-danger small mt-1 fw-bold"
                  >
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ getPeriodStatus().message }}
                  </div>
                </div>
              </div>
            </div>

            <!-- 3 Columns Inputs -->
            <div class="row g-3 mb-5">
              <!-- Numerator -->
              <div class="col-md-4">
                <div class="text-center">
                  <label class="fw-bold fs-5 mb-2 d-block">{{ numeratorLabel }}</label>
                  <input
                    type="number"
                    step="any"
                    v-model.number="form.numerator"
                    class="form-control border-dark rounded-0 text-center py-2"
                    placeholder="ระบุตัวเลข"
                  />
                </div>
              </div>

              <!-- Denominator -->
              <div class="col-md-4">
                <div class="text-center">
                  <label class="fw-bold fs-5 mb-2 d-block">{{ denominatorLabel }}</label>
                  <input
                    type="number"
                    step="any"
                    v-model.number="form.denominator"
                    class="form-control border-dark rounded-0 text-center py-2"
                    placeholder="ระบุตัวเลข"
                  />
                </div>
              </div>

              <!-- Multiplier (Input) -->
              <div class="col-md-4">
                <div class="text-center">
                  <label class="fw-bold fs-5 mb-2 d-block">ตัวคูณ</label>
                  <input
                    type="number"
                    step="any"
                    v-model.number="form.multiplier"
                    class="form-control border-dark rounded-0 text-center py-2"
                    placeholder="ระบุตัวเลข"
                  />
                </div>
              </div>
            </div>

            <!-- Result Preview (Optional, for user feedback) -->
            <div class="text-center mb-4" v-if="form.actual_value !== ''">
              <span class="text-muted me-2">ผลลัพธ์ที่คำนวณได้:</span>
              <span class="fw-bold fs-4 text-primary">{{ form.actual_value }}</span>
              <span class="small ms-1">{{ selectedKpi?.unit }}</span>
            </div>

            <!-- Submit Button -->
            <div class="d-grid w-50 mx-auto">
              <button
                type="submit"
                class="btn btn-primary-custom text-white py-3 fs-5 fw-bold rounded-1 shadow-sm"
                :disabled="submitting || !getPeriodStatus().open"
              >
                <span
                  v-if="submitting"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                บันทึกผลการดำเนินงาน
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
      return this.selectedKpi?.numerator_label || 'ตัวตั้ง';
    },
    denominatorLabel() {
      return this.selectedKpi?.denominator_label || 'ตัวหาร';
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
      // Multiplier reset handled by watch

      this.bsModal.show();
    },
    openForEdit(kpi, payload) {
      this.selectedKpi = kpi;
      this.form.kpi_id = kpi.id;
      
      this.form.year_thai = payload.year_thai;
      
      // Update options based on the periodicity and the selected year
      this.generatePeriodOptions(kpi.kpi_periodicity);
      
      this.form.period_number = payload.period_number;
      this.form.actual_value = payload.actual_value;
      this.form.numerator = payload.numerator;
      this.form.denominator = payload.denominator;
      
      this.bsModal.show();
    },
    hide() {
      this.bsModal.hide();
    },
    calculateActual() {
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
      return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
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

<style scoped>
.bg-purple {
  background-color: #512da8; /* Deep purple matching mockup */
}
.bg-light-gray {
  background-color: #f8f9fa;
}
.btn-primary-custom {
  background-color: #304ffe; /* Bright blue */
  border: none;
  transition: all 0.2s;
}
.btn-primary-custom:hover {
  background-color: #1a237e;
  transform: translateY(-1px);
}
.form-control:focus {
  box-shadow: none;
  border-color: #512da8;
}
.form-select:focus {
  box-shadow: none;
}
</style>
