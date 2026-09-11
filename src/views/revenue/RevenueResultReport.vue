<template>
  <div class="container-fluid min-vh-100 bg-light py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card shadow-lg rounded-4 border-0 overflow-hidden">
            <div class="card-header bg-gradient-orange text-white p-4">
              <h3 class="mb-0 fw-bold">
                <i class="bi bi-pencil-square me-2"></i> บันทึกผลจัดเก็บรายได้
              </h3>
              <p class="mb-0 opacity-75">Revenue Collection Result Entry</p>
            </div>
            <div class="card-body p-4 p-md-5">
              <div v-if="isLoaded && availableTargets.length === 0" class="alert alert-info border-0 rounded-4 p-4 text-center my-3">
                <div class="fs-1 text-info mb-2"><i class="bi bi-info-circle-fill"></i></div>
                <h5 class="fw-bold text-dark">ท่านสามารถดูข้อมูลได้อย่างเดียว</h5>
                <p class="text-muted mb-3">
                  ท่านไม่มีรายการเป้าหมายที่ต้องกรอกผลงานในฐานะผู้รับผิดชอบ 
                  (เฉพาะผู้รับผิดชอบหรือผู้ดูแลระบบเท่านั้นที่สามารถส่งยอดผลงานได้)
                </p>
                <div class="d-flex justify-content-center gap-2">
                  <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="$router.push('/revenue-dashboard')">
                    <i class="bi bi-graph-up-arrow me-2"></i> ไปที่แดชบอร์ดศูนย์จัดเก็บรายได้
                  </button>
                  <button class="btn btn-outline-secondary rounded-pill px-4" @click="$router.push('/home-backoffice')">
                    กลับหน้าหลัก
                  </button>
                </div>
              </div>

              <form v-else @submit.prevent="submitResult">
                <!-- Month & Year -->
                <div class="row g-4 mb-4">
                  <div class="col-md-6">
                    <div class="form-floating">
                      <input
                        type="number"
                        v-model="form.year_thai"
                        class="form-control bg-light border-0 fw-bold text-dark"
                        id="yearInput"
                        required
                        disabled
                      />
                      <label for="yearInput">ปี พ.ศ. (Thai Year)</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-floating">
                      <select
                        v-model="form.month"
                        class="form-select bg-light border-0 fw-bold text-dark"
                        id="monthSelect"
                        required
                      >
                        <option value="" disabled>-- เลือกเดือน --</option>
                        <option v-for="m in fiscalMonths" :key="m.value" :value="m.value">
                          {{ m.label }}
                        </option>
                      </select>
                      <label for="monthSelect">รายงานผลประจำเดือน</label>
                    </div>
                  </div>
                </div>

                <!-- Target Selection -->
                <div class="mb-4">
                  <label class="form-label fw-semibold text-secondary"
                    >เลือกรายการเป้าหมายจัดเก็บ</label
                  >
                    <select
                      v-model="form.target_id"
                      class="form-select form-select-lg shadow-sm border-secondary-subtle"
                      required
                    >
                      <option value="" disabled>-- กรุณาเลือกรายการ --</option>
                      <option v-for="t in availableTargets" :key="t.id" :value="t.id">
                        {{ t.revenue_name }}
                        (เป้าหมาย: {{ formatCurrency(t.target_amount) }} บาท)
                      </option>
                    </select>
                  <div
                    v-if="selectedTarget"
                    class="alert alert-warning mt-3 border-0 bg-warning-subtle text-dark rounded-3"
                  >
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>ผู้รับผิดชอบ:</strong>
                    {{ selectedTarget.responsible_person || 'ไม่ได้ระบุ' }}
                    <br />
                    <strong>หน่วยงาน:</strong> {{ selectedTarget.responsible_unit || 'ไม่ได้ระบุ' }}
                    <span v-if="selectedTarget.claim_program" class="badge bg-dark ms-2">{{
                      selectedTarget.claim_program
                    }}</span>
                    <br />
                    <strong>จำนวนเงิน/ครั้ง:</strong>
                    <strong class="text-danger">{{ selectedTarget.unit_price || '-' }}</strong>
                  </div>
                </div>

                <hr class="border-secondary-subtle my-5" />

                <!-- Result entry -->
                <h5 class="text-warning fw-bold mb-4">
                  <i class="bi bi-cash-coin me-2"></i> ยอดที่จัดเก็บได้จริง
                </h5>

                <div class="card bg-light border-0 rounded-4 mb-4">
                  <div class="card-body p-4">
                    <div class="row mb-3">
                      <div class="col-12 mb-3 mb-md-0">
                        <label class="form-label fw-bold h5 text-dark"
                          >จำนวนผลงาน (ครั้ง/คน/คะแนน)
                          <span
                            v-if="selectedTarget && selectedTarget.unit_price"
                            class="fs-6 fw-normal text-muted"
                            >(เรท: {{ selectedTarget.unit_price }})</span
                          ></label
                        >
                        <input
                          type="number"
                          step="0.01"
                          v-model="form.achieved_items"
                          class="form-control form-control-lg fw-bold text-primary"
                          placeholder="0"
                          @input="calculateAmount"
                        />
                      </div>
                    </div>
                    <div>
                      <label class="form-label text-muted">หมายเหตุ (ถ้ามี)</label>
                      <textarea
                        v-model="form.remark"
                        class="form-control"
                        rows="2"
                        placeholder="อธิบายเพิ่มเติม..."
                      ></textarea>
                    </div>
                  </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-lg rounded-pill px-4 hover-elevate"
                    @click="$router.push('/home-backoffice')"
                  >
                    ยกเลิก
                  </button>
                  <button
                    type="submit"
                    class="btn btn-warning btn-lg rounded-pill px-5 shadow-sm hover-elevate fw-bold text-dark"
                  >
                    <i class="bi bi-save-fill me-2"></i> บันทึกข้อมูล
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
  name: 'RevenueResultReport',
  data() {
    return {
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
      targets: [],
      userDepartment: '',
      userFullname: '',
      isLoaded: false,
      form: {
        target_id: '',
        year_thai: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0),
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
        this.userDepartment.includes('ประกัน') ||
        this.userDepartment === 'admin'
      );
    },
    availableTargets() {
      return this.targets.filter(t => this.canReport(t));
    },
    selectedTarget() {
      return this.availableTargets.find((t) => t.id === this.form.target_id) || null;
    }
  },
  methods: {
    canReport(target) {
      if (!target) return false;
      if (this.isAdmin) return true;
      if (!this.userFullname) return false;
      const resp = (target.responsible_person || '').trim().toLowerCase();
      const cleanUser = this.userFullname.replace(/^(นาย|นาง|นางสาว|ดร\.|นพ\.|พญ\.)\s*/, '').trim().toLowerCase();
      return resp.includes(this.userFullname.toLowerCase()) || (cleanUser && resp.includes(cleanUser));
    },
    async fetchUserProfile() {
      try {
        const token = localStorage.getItem('user_token');
        if (!token) return;
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data.status === 'success') {
          this.userDepartment = response.data.department || '';
          this.userFullname = response.data.fullname || '';
        }
      } catch (e) {
        console.error('Failed to load user profile', e);
      }
    },
    formatCurrency(value) {
      if (!value) return '0.00';
      return parseFloat(value).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    calculateAmount() {
      if (!this.selectedTarget || !this.form.achieved_items) return;

      // Try to parse unit_price as a number. Value could be "20", "50", "100-200", etc.
      const priceStr = String(this.selectedTarget.unit_price || '').trim();
      const match = priceStr.match(/^[\d.]+/); // Match first number in string

      if (match) {
        const price = parseFloat(match[0]);
        if (!isNaN(price)) {
          this.form.collected_amount = (parseFloat(this.form.achieved_items) * price).toFixed(2);
        }
      }
    },
    async fetchTargets() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const res = await axios.get('/api-digital/revenue/get_targets.php', config);
        this.targets = res.data.filter((t) => t.fiscal_year == this.form.year_thai);
      } catch (err) {
        console.error('Fetch error:', err);
      }
    },
    async submitResult() {
      if (!this.canReport(this.selectedTarget)) {
        Swal.fire('ไม่มีสิทธิ์', 'คุณสามารถดูข้อมูลได้อย่างเดียว เฉพาะผู้รับผิดชอบเท่านั้นที่กรอกข้อมูลได้', 'warning');
        return;
      }
      try {
        const token = localStorage.getItem('user_token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const res = await axios.post('/api-digital/revenue/save_result.php', this.form, config);

        if (res.data.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ!',
            text: 'บันทึกผลการจัดเก็บรายได้เรียบร้อย',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
          this.form.achieved_items = '';
          this.form.collected_amount = '';
          this.form.remark = '';
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่', 'error');
      }
    }
  },
  async mounted() {
    await this.fetchUserProfile();
    await this.fetchTargets();
    this.isLoaded = true;
  }
};
</script>

<style scoped>
.bg-gradient-orange {
  background: linear-gradient(135deg, #fd7e14 0%, #d83600 100%);
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
  color: #fd7e14;
  font-weight: bold;
}
</style>
