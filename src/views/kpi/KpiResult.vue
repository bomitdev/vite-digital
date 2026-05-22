<template>
  <div class="container-fluid min-vh-100 bg-light py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card shadow-lg rounded-4 border-0 overflow-hidden">
            <div class="card-header bg-gradient-primary text-white p-4">
              <h3 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i> บันทึกผล KPI</h3>
              <p class="mb-0 opacity-75">Monthly Key Performance Indicator Entry</p>
            </div>

            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitResults">
                <!-- Period Section -->
                <div class="row g-4 mb-4">
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input
                        type="number"
                        v-model="form.year_thai"
                        class="form-control bg-light border-0 fw-bold text-primary"
                        id="yearInput"
                        required
                      />
                      <label for="yearInput">ปี พ.ศ. (Thai Year)</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-floating">
                      <select
                        v-model="form.period_number"
                        class="form-select bg-light border-0 fw-bold text-primary"
                        id="periodSelect"
                        required
                      >
                        <option value="" disabled>-- เลือก{{ periodLabel }} --</option>
                        <option v-for="p in availablePeriods" :key="p.id" :value="p.id">
                          {{ p.name }}
                        </option>
                      </select>
                      <label for="periodSelect">{{ periodLabel }}</label>
                    </div>
                  </div>
                </div>

                <!-- KPI Selection -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-secondary"
                    >เลือก KPI ที่ต้องการบันทึก</label
                  >
                  <select
                    v-model="form.kpi_id"
                    class="form-select form-select-lg shadow-sm border-secondary-subtle"
                    required
                  >
                    <option value="">-- กรุณาเลือก KPI --</option>
                    <option v-for="kpi in kpis" :key="kpi.id" :value="kpi.id">
                      {{ kpi.name }} (Target: {{ kpi.target_operator }} {{ kpi.target_value }}
                      {{ kpi.unit }})
                    </option>
                  </select>
                  <div
                    v-if="selectedKpiDetail"
                    class="alert alert-info mt-3 border-0 bg-info-subtle text-info-emphasis rounded-3"
                  >
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>หมวดหมู่:</strong> {{ selectedKpiDetail.category_name }}
                    <span class="ms-3 badge bg-white text-info border border-info">{{
                      selectedKpiDetail.unit
                    }}</span>
                  </div>
                </div>

                <hr class="border-secondary-subtle my-5" />

                <!-- Results Section -->
                <h5 class="text-primary fw-bold mb-4">
                  <i class="bi bi-bar-chart-fill me-2"></i> ผลการดำเนินงาน
                </h5>

                <div class="card bg-light border-0 rounded-4 mb-4">
                  <div class="card-body p-4">
                    <div class="mb-3">
                      <label class="form-label fw-bold h5 text-dark"
                        >ค่าผลลัพธ์ (Actual Value)</label
                      >
                      <div class="input-group input-group-lg">
                        <input
                          type="number"
                          step="0.01"
                          v-model.number="form.actual_value"
                          class="form-control fw-bold text-success"
                          placeholder="0.00"
                          required
                        />
                        <span class="input-group-text bg-white text-muted" v-if="selectedKpiDetail">
                          {{ selectedKpiDetail.unit }}
                        </span>
                      </div>
                      <div class="form-text mt-2 text-muted">
                        <i class="bi bi-lightbulb"></i> ถ้ามีข้อมูลตัวตั้ง/ตัวหาร
                        ระบบจะคำนวณให้อัตโนมัติ หรือกรอกค่าผลลัพธ์โดยตรงได้เลย
                      </div>
                    </div>

                    <div class="row g-3 mt-2">
                      <!-- Percentage Inputs -->
                      <template
                        v-if="
                          selectedKpiDetail?.calculation_type === 'percentage' ||
                          !selectedKpiDetail?.calculation_type
                        "
                      >
                        <div class="col-md-6">
                          <label class="form-label small text-muted">ตัวตั้ง (Numerator)</label>
                          <input
                            type="number"
                            v-model.number="form.numerator"
                            class="form-control"
                            placeholder="ระบุตัวเลข"
                          />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small text-muted">ตัวหาร (Denominator)</label>
                          <input
                            type="number"
                            v-model.number="form.denominator"
                            class="form-control"
                            placeholder="ระบุตัวเลข"
                          />
                        </div>
                      </template>

                      <!-- Multiplication Inputs -->
                      <template
                        v-else-if="selectedKpiDetail?.calculation_type === 'multiplication'"
                      >
                        <div class="col-md-6">
                          <label class="form-label small text-muted">ค่า A (Value A)</label>
                          <input
                            type="number"
                            v-model.number="form.numerator"
                            class="form-control"
                            placeholder="ระบุตัวเลข"
                          />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small text-muted">ค่า B (Value B)</label>
                          <input
                            type="number"
                            v-model.number="form.denominator"
                            class="form-control"
                            placeholder="ระบุตัวเลข"
                          />
                        </div>
                      </template>
                    </div>
                  </div>
                </div>

                <div class="d-grid">
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg rounded-pill shadow-sm hover-elevate"
                  >
                    <i class="bi bi-save2-fill me-2"></i> บันทึกผลการดำเนินงาน
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
import Swal from 'sweetalert2';

export default {
  name: 'KpiResult',
  data() {
    return {
      kpis: [],
      periods: {
        month: [
          { id: 1, name: 'มกราคม' },
          { id: 2, name: 'กุมภาพันธ์' },
          { id: 3, name: 'มีนาคม' },
          { id: 4, name: 'เมษายน' },
          { id: 5, name: 'พฤษภาคม' },
          { id: 6, name: 'มิถุนายน' },
          { id: 7, name: 'กรกฎาคม' },
          { id: 8, name: 'สิงหาคม' },
          { id: 9, name: 'กันยายน' },
          { id: 10, name: 'ตุลาคม' },
          { id: 11, name: 'พฤศจิกายน' },
          { id: 12, name: 'ธันวาคม' }
        ],
        quarter: [
          { id: 1, name: 'ไตรมาสที่ 1 (ตุลาคม ถึง ธันวาคม)' },
          { id: 2, name: 'ไตรมาสที่ 2 (มกราคม ถึง มีนาคม)' },
          { id: 3, name: 'ไตรมาสที่ 3 (เมษายน ถึง มิถุนายน)' },
          { id: 4, name: 'ไตรมาสที่ 4 (กรกฎาคม ถึง กันยายน)' }
        ],
        'Semiannual report': [
          { id: 1, name: 'ครึ่งปีแรก (ตุลาคม ถึง มีนาคม)' },
          { id: 2, name: 'ครึ่งปีหลัง (เมษายน ถึง กันยายน)' }
        ],
        year: [{ id: 1, name: 'ทั้งปีงบประมาณ' }]
      },
      form: {
        kpi_id: '',
        year_thai: (() => {
          const d = new Date();
          return d.getMonth() >= 9 ? d.getFullYear() + 543 + 1 : d.getFullYear() + 543;
        })(),
        period_number: '',
        actual_value: '',
        numerator: '',
        denominator: ''
      }
    };
  },
  computed: {
    selectedKpiDetail() {
      return this.kpis.find((k) => k.id === this.form.kpi_id) || null;
    },
    periodLabel() {
      const type = this.selectedKpiDetail?.kpi_periodicity || 'month';
      if (type === 'quarter') return 'การรายงานผล (รายไตรมาส)';
      if (type === 'Semiannual report') return 'การรายงานผล (รายครึ่งปี)';
      if (type === 'year') return 'การรายงานผล (รายปีงบประมาณ)';
      return 'การรายงานผล (รายเดือน)';
    },
    availablePeriods() {
      const type = this.selectedKpiDetail?.kpi_periodicity || 'month';
      const allPeriods = this.periods[type] || this.periods['month'];

      const now = new Date();
      const currentCalendarMonth = now.getMonth() + 1; // 1-12
      const currentCalendarYear = now.getFullYear();
      const currentFiscalYear = now.getMonth() >= 9 ? currentCalendarYear + 543 + 1 : currentCalendarYear + 543;
      
      const currentFiscalMonth = currentCalendarMonth >= 10 ? currentCalendarMonth - 9 : currentCalendarMonth + 3;
      const currentFiscalQuarter = Math.ceil(currentFiscalMonth / 3);
      const currentFiscalHalf = Math.ceil(currentFiscalQuarter / 2);

      const selectedYear = parseInt(this.form.year_thai) || currentFiscalYear;

      const isArrived = (fiscalIndex, periodType) => {
        if (selectedYear < currentFiscalYear) return true;
        if (selectedYear > currentFiscalYear) return false;
        
        if (periodType === 'month') return fiscalIndex <= currentFiscalMonth;
        if (periodType === 'quarter') return fiscalIndex <= currentFiscalQuarter;
        if (periodType === 'Semiannual report') return fiscalIndex <= currentFiscalHalf;
        if (periodType === 'year') return true; 
        return true;
      };

      if (type === 'month') {
        return allPeriods.filter(m => {
          const fiscalIndex = m.id >= 10 ? m.id - 9 : m.id + 3;
          return isArrived(fiscalIndex, 'month');
        });
      }
      
      return allPeriods.filter(p => isArrived(p.id, type));
    }
  },
  watch: {
    'form.kpi_id': function (newVal) {
      const detail = this.selectedKpiDetail;
      if (detail) {
        if (detail.kpi_periodicity === 'month' || !detail.kpi_periodicity) {
          // Find if the current calendar month is in the available periods
          const nowMonth = new Date().getMonth() + 1;
          const available = this.availablePeriods;
          const currentOpt = available.find(p => p.id === nowMonth);
          this.form.period_number = currentOpt ? currentOpt.id : (available.length > 0 ? available[available.length - 1].id : '');
        } else {
          this.form.period_number = ''; // Require explicit selection for non-monthly
        }
      }
    },
    // Auto-calculate if numerator/denominator changes
    'form.numerator': function (val) {
      this.calculateActual();
    },
    'form.denominator': function (val) {
      this.calculateActual();
    }
  },
  methods: {
    calculateActual() {
      if (this.form.numerator === '' || this.form.denominator === '') {
        return;
      }

      const type = this.selectedKpiDetail?.calculation_type || 'percentage';

      if (type === 'multiplication') {
        const valA = parseFloat(this.form.numerator);
        const valB = parseFloat(this.form.denominator);
        if (!isNaN(valA) && !isNaN(valB)) {
          this.form.actual_value = (valA * valB).toFixed(2);
        }
      } else {
        // Percentage
        const num = parseFloat(this.form.numerator);
        const den = parseFloat(this.form.denominator);
        if (!isNaN(num) && !isNaN(den) && den !== 0) {
          this.form.actual_value = ((num / den) * 100).toFixed(2);
        }
      }
    },
    async fetchKPIs() {
      try {
        const res = await axios.get('/api-digital/kpi/get_kpis.php');
        this.kpis = res.data;
      } catch (err) {
        console.error('Error fetching KPIs:', err);
      }
    },
    async submitResults() {
      // Validate
      const selectedKpi = this.kpis.find((k) => k.id === this.form.kpi_id);
      if (!selectedKpi) {
        Swal.fire('Error', 'กรุณาเลือก KPI', 'error');
        return;
      }

      // Prepare payload
      const payload = {
        ...this.form,
        target_value_snapshot: selectedKpi.target_value
      };

      try {
        const res = await axios.post('/api-digital/kpi/save_result_kpi.php', payload);
        if (res.data.status === 'success') {
          Swal.fire('Success', 'บันทึกผลงานเรียบร้อย', 'success');
          // Reset form but keep month/year maybe?
          this.form.actual_value = '';
          this.form.numerator = '';
          this.form.denominator = '';
        } else {
          Swal.fire('Error', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      }
    }
  },
  async mounted() {
    await this.fetchKPIs();

    // Check for query param
    const kpiId = this.$route.query.kpi_id;
    if (kpiId) {
      const id = parseInt(kpiId);
      const exists = this.kpis.find((k) => k.id === id);
      if (exists) {
        this.form.kpi_id = id;
      }
    }
  }
};
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
}
.hover-elevate {
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}
.hover-elevate:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.form-floating > .form-control:focus ~ label,
.form-floating > .form-control:not(:placeholder-shown) ~ label,
.form-floating > .form-select ~ label {
  color: #0d6efd;
  font-weight: bold;
}
</style>
