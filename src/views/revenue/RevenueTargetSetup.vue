<template>
  <div class="container mt-5">
    <!-- Premium Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border-0">
      <div class="d-flex align-items-center mb-3 mb-md-0">
        <div class="bg-success bg-gradient text-white rounded-circle d-flex justify-content-center align-items-center me-3 shadow-sm" style="width: 50px; height: 50px;">
          <i class="bi bi-sliders fs-4"></i>
        </div>
        <div>
          <h4 class="fw-bolder text-dark mb-0">จัดการเป้าหมายรายได้</h4>
          <span class="text-muted small fw-semibold">Revenue Target Setup</span>
        </div>
      </div>
      <button
        class="btn btn-light border px-4 fw-bold shadow-sm rounded-pill d-flex align-items-center text-secondary"
        @click="$router.push('/revenue-dashboard')"
      >
        <i class="bi bi-arrow-left me-2"></i> กลับหน้าแดชบอร์ด
      </button>
    </div>

    <!-- Modal เพิ่ม/แก้ไขเป้าหมาย -->
    <div
      class="modal fade"
      id="targetSetupModal"
      ref="targetSetupModal"
      aria-hidden="true"
      data-bs-focus="false"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-light border-bottom">
            <h5 class="modal-title fw-bolder text-dark d-flex align-items-center">
              <i class="bi bi-gear-fill text-success me-2 fs-4"></i>
              {{ isEdit ? 'แก้ไขเป้าหมายรายได้' : 'กำหนดเป้าหมายจัดเก็บรายได้ใหม่' }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              @click="closeSetupModal"
            ></button>
          </div>
          <div class="modal-body p-5 bg-white">
            <form @submit.prevent="submitForm">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <label class="form-label fw-bold">ปีงบประมาณ</label>
                  <input
                    type="number"
                    v-model="form.fiscal_year"
                    class="form-control border-dark rounded-0 px-3 py-2"
                    required
                  />
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">ชื่อรายการจัดเก็บรายได้</label>
                  <input
                    type="text"
                    v-model="form.revenue_name"
                    class="form-control border-dark rounded-0 px-3 py-2"
                    required
                    placeholder="เช่น รายได้ผู้ป่วยนอกทั่วไป"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">จำนวนเงินหรือคะแนน/ครั้ง</label>
                  <input
                    type="text"
                    v-model="form.unit_price"
                    class="form-control border-dark rounded-0 px-3 py-2"
                    placeholder="เช่น 20, 100-200, คะแนน"
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">เป้าหมายจัดเก็บรวม (บาท)</label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="form.target_amount"
                    class="form-control border-dark rounded-0 px-3 py-2"
                    required
                  />
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">เป้าหมายจำนวน/เดือน</label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="form.target_per_month"
                    class="form-control border-dark rounded-0 px-3 py-2"
                  />
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-12">
                  <label class="form-label fw-bold">ผู้รับผิดชอบ</label>
                  <div class="d-flex mb-2">
                    <input
                      type="text"
                      list="hrPersonList"
                      v-model="currentPersonInput"
                      class="form-control border-dark rounded-0 px-3 py-2"
                      placeholder="ค้นหาชื่อเจ้าหน้าที่และกดเพิ่ม..."
                      @keyup.enter.prevent="addPerson"
                    />
                    <button type="button" class="btn btn-dark rounded-0 px-3" @click="addPerson">
                      เพิ่ม
                    </button>
                  </div>
                  <datalist id="hrPersonList">
                    <option
                      v-for="person in hrPersons"
                      :key="person.ID"
                      :value="person.FULLNAME"
                    ></option>
                  </datalist>
                  <div class="d-flex flex-wrap gap-2 mt-2">
                    <span
                      v-for="(name, index) in selectedPersons"
                      :key="index"
                      class="badge bg-primary d-flex align-items-center p-2 fs-6"
                    >
                      {{ name }}
                      <i
                        class="bi bi-x-circle ms-2"
                        style="cursor: pointer"
                        @click="removePerson(index)"
                      ></i>
                    </span>
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label fw-bold">โปรแกรม Claim</label>
                  <select
                    v-model="form.claim_program"
                    class="form-select border-dark rounded-0 px-3 py-2"
                  >
                    <option value="">-- เลือก / ไม่ระบุ --</option>
                    <option v-for="cp in claimPrograms" :key="cp.id" :value="cp.program_name">
                      {{ cp.program_name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-3 mt-4 border-top pt-3">
                <button
                  type="button"
                  @click="closeSetupModal"
                  class="btn btn-light border px-4 py-2 fw-bold"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  class="btn btn-primary px-5 py-2 fw-bold shadow-sm"
                >
                  <i class="bi bi-save me-1"></i> {{ isEdit ? 'อัพเดทข้อมูล' : 'บันทึกเป้าหมาย' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ตารางเป้าหมายรายได้ -->
    <div class="card shadow-sm rounded-4 border-0 mb-5 overflow-hidden">
      <div
        class="card-header bg-white py-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
      >
        <h5 class="mb-0 fw-bolder text-dark d-flex align-items-center">
          <i class="bi bi-table text-primary me-2"></i> รายการเป้าหมายรายได้ทั้งหมด
        </h5>
        <div class="d-flex flex-wrap gap-2">
          <div class="input-group shadow-sm rounded-pill overflow-hidden border bg-white" style="max-width: 300px">
            <span class="input-group-text bg-transparent border-0 text-muted ps-3"><i class="bi bi-search"></i></span>
            <input
              type="text"
              class="form-control border-0 ps-1 bg-transparent shadow-none"
              placeholder="ค้นหารายการ..."
              v-model="searchQuery"
            />
          </div>
          <button v-if="isAdmin" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center" @click="openSetupModal">
            <i class="bi bi-plus-lg me-2"></i> เพิ่มเป้าหมายใหม่
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 border-white">
            <thead class="bg-light text-secondary">
              <tr>
                <th class="py-3 ps-4 border-0 fw-bold">ปีงบฯ</th>
                <th class="py-3 border-0 fw-bold">รายการรายได้ (ต่อหน่วย)</th>
                <th class="py-3 text-end border-0 fw-bold">เป้าหมายรวม (บาท)</th>
                <th class="py-3 border-0 fw-bold">ผู้รับผิดชอบ / Claim</th>
                <th class="py-3 pe-4 text-end border-0 fw-bold">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredTargets.length === 0">
                <td colspan="5" class="text-center text-muted py-4">ไม่พบรายการที่ค้นหา</td>
              </tr>
              <tr v-for="target in filteredTargets" :key="target.id" class="border-bottom">
                <td class="ps-4">
                  <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-bold shadow-sm">{{ target.fiscal_year }}</span>
                </td>
                <td>
                  <div class="fw-bolder text-dark fs-6">{{ target.revenue_name }}</div>
                  <div class="small text-muted mt-1" v-if="target.unit_price">
                    เงิน/ครั้ง: <span class="badge bg-info bg-opacity-10 text-info border-info border rounded-pill">{{ target.unit_price }}</span>
                  </div>
                </td>
                <td class="text-end">
                  <div class="text-success fw-bolder fs-6">{{ formatCurrency(target.target_amount) }}</div>
                  <div class="small text-muted fw-semibold mt-1" v-if="target.target_per_month">
                    เป้า: {{ Number(target.target_per_month).toLocaleString() }} หน่วย/ด.
                  </div>
                </td>
                <td>
                  <div class="small fw-semibold text-secondary">
                    <i class="bi bi-person-circle me-1"></i> {{ target.responsible_person || 'ไม่ระบุ' }}
                  </div>
                  <div class="small mt-1" v-if="target.claim_program">
                    <span class="badge bg-light text-primary border rounded-pill"><i class="bi bi-tag-fill me-1"></i>{{ target.claim_program }}</span>
                  </div>
                </td>
                <td class="pe-4 text-end">
                  <div class="d-inline-flex gap-1">
                    <button
                      type="button"
                      class="btn btn-sm btn-light border text-primary fw-bold rounded-3 shadow-sm"
                      @click="openResultModal(target)"
                      title="บันทึกผลงาน"
                    >
                      <i class="bi bi-journal-plus me-1"></i> บันทึกผล
                    </button>
                    <button class="btn btn-sm btn-light border text-warning fw-bold rounded-3 shadow-sm" @click="editTarget(target)" title="แก้ไข">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-light border text-danger fw-bold rounded-3 shadow-sm" @click="deleteTarget(target.id)" v-if="isAdmin" title="ลบ">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal บันทึกผลงานรายเดือน -->
    <div
      class="modal fade"
      id="resultModal"
      ref="resultModal"
      aria-hidden="true"
      data-bs-focus="false"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              บันทึกผลการจัดเก็บรายได้: {{ selectedTarget?.revenue_name }}
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              data-bs-dismiss="modal"
              @click="closeResultModal"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitResultForm" class="mb-4 bg-light p-3 rounded border">
              <h6 class="fw-bold mb-3">เพิ่ม/แก้ไขผลงานรายเดือน</h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-3">
                  <label class="form-label small">เดือนที่รายงาน</label>
                  <select v-model="resultForm.month" class="form-select form-select-sm" required>
                    <option v-for="m in fiscalMonths" :key="m.value" :value="m.value">
                      {{ m.label }}
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
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
                  <label class="form-label small">หมายเหตุ</label>
                  <input
                    type="text"
                    v-model="resultForm.remark"
                    class="form-control form-control-sm"
                  />
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-sm btn-success w-100">บันทึกผล</button>
                </div>
              </div>
            </form>

            <h6 class="fw-bold mb-2">ประวัติการบันทึกผลงาน</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-striped text-center text-sm">
                <thead>
                  <tr>
                    <th>เดือน</th>
                    <th>จำนวนผลงาน</th>
                    <th>ยอดจัดเก็บได้จริง</th>
                    <th>หมายเหตุ</th>
                    <th>จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in resultsData" :key="r.id">
                    <td>{{ getMonthName(r.month) }}</td>
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
                    <td colspan="5" class="text-center text-muted">ยังไม่มีข้อมูล</td>
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

export default {
  name: 'RevenueTargetSetup',
  data() {
    return {
      searchQuery: '',
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
      claimPrograms: [],
      hrPersons: [],
      selectedPersons: [],
      currentPersonInput: '',
      isEdit: false,
      selectedTarget: null,
      resultsData: [],
      resultModalInstance: null,
      setupModalInstance: null,
      form: {
        id: null,
        revenue_name: '',
        fiscal_year: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0),
        target_amount: '',
        target_per_month: '',
        unit_price: '',
        responsible_person: '',
        claim_program: ''
      },
      resultForm: {
        id: null,
        target_id: null,
        month: new Date().getMonth() + 1,
        achieved_items: '',
        collected_amount: '',
        remark: ''
      },
      userDepartment: '',
      userFullname: ''
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
    filteredTargets() {
      let baseList = this.isAdmin 
        ? this.targets 
        : (this.userFullname ? this.targets.filter(t => t.responsible_person && t.responsible_person.includes(this.userFullname)) : []);
      
      if (!this.searchQuery) return baseList;
      const q = this.searchQuery.toLowerCase();
      return baseList.filter((target) => {
        return (
          (target.revenue_name && target.revenue_name.toLowerCase().includes(q)) ||
          (target.responsible_person && target.responsible_person.toLowerCase().includes(q)) ||
          (target.fiscal_year && String(target.fiscal_year).includes(q)) ||
          (target.claim_program && target.claim_program.toLowerCase().includes(q))
        );
      });
    }
  },
  methods: {
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
    async fetchHrPersons() {
      try {
        const res = await axios.get('/api-digital/asset/get_hr_person.php');
        if (res.data && res.data.status === 'success') {
          this.hrPersons = res.data.data;
        }
      } catch (err) {
        console.error('Fetch HR error:', err);
      }
    },
    async fetchClaimPrograms() {
      try {
        const res = await axios.get('/api-digital/revenue/get_claim_programs.php');
        if (res.data) {
          this.claimPrograms = res.data;
        }
      } catch (err) {
        console.error('Fetch claim programs error:', err);
      }
    },
    addPerson() {
      const name = this.currentPersonInput.trim();
      if (name && !this.selectedPersons.includes(name)) {
        this.selectedPersons.push(name);
      }
      this.currentPersonInput = '';
    },
    removePerson(index) {
      this.selectedPersons.splice(index, 1);
    },
    getMonthName(monthNum) {
      if (!monthNum) return '';
      const m = this.fiscalMonths.find((x) => x.value == monthNum);
      return m ? m.label : monthNum;
    },
    formatCurrency(value) {
      if (value === null || value === undefined) return '0.00';
      return parseFloat(value).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
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
    async fetchTargets() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const res = await axios.get('/api-digital/revenue/get_targets.php', config);
        this.targets = res.data;
      } catch (err) {
        console.error('Fetch error:', err);
      }
    },
    async submitForm() {
      try {
        this.form.responsible_person = this.selectedPersons.join(', ');
        const token = localStorage.getItem('user_token');
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const res = await axios.post('/api-digital/revenue/save_target.php', this.form, config);

        if (res.data.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ',
            text: 'บันทึกข้อมูลเป้าหมายสำเร็จ',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          });
          this.closeSetupModal();
          this.fetchTargets();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      }
    },
    editTarget(t) {
      this.isEdit = true;
      this.form = { ...t };
      this.selectedPersons = t.responsible_person
        ? t.responsible_person
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s)
        : [];
      
      let el = this.$refs.targetSetupModal;
      if (!el) el = document.getElementById('targetSetupModal');
      
      if (el) {
        if (!this.setupModalInstance) {
          this.setupModalInstance = new Modal(el);
        }
        this.setupModalInstance.show();
      }
    },
    async deleteTarget(id) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'ข้อมูลเป้าหมายและผลงานทั้งหมดจะถูกลบ!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบข้อมูล'
      });

      if (result.isConfirmed) {
        try {
          const token = localStorage.getItem('user_token');
          const config = {
            headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' }
          };
          const res = await axios.post('/api-digital/revenue/delete_target.php', { id }, config);
          if (res.data.status === 'success') {
            Swal.fire('ลบสำเร็จ', 'ลบข้อมูลเรียบร้อย', 'success');
            this.fetchTargets();
          } else {
            Swal.fire('ข้อผิดพลาด', 'ลบไม่สำเร็จ: ' + res.data.message, 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบ', 'error');
        }
      }
    },
    resetForm() {
      this.isEdit = false;
      this.form = {
        id: null,
        revenue_name: '',
        fiscal_year: new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0),
        target_amount: '',
        target_per_month: '',
        unit_price: '',
        responsible_person: '',
        claim_program: ''
      };
      this.selectedPersons = [];
      this.currentPersonInput = '';
    },
    openSetupModal() {
      this.resetForm();
      let el = this.$refs.targetSetupModal;
      if (!el) el = document.getElementById('targetSetupModal');
      if (el) {
        if (!this.setupModalInstance) {
          this.setupModalInstance = new Modal(el);
        }
        this.setupModalInstance.show();
      }
    },
    closeSetupModal() {
      if (this.setupModalInstance) {
        this.setupModalInstance.hide();
      }
      this.resetForm();
    },
    async openResultModal(t) {
      this.selectedTarget = t;
      this.resultForm.target_id = t.id;
      this.resultForm.achieved_items = '';
      this.resultForm.collected_amount = '';
      this.resultForm.remark = '';

      let el = this.$refs.resultModal;
      if (!el) el = document.getElementById('resultModal');

      if (el) {
        if (!this.resultModalInstance) {
          this.resultModalInstance = new Modal(el);
        }
        this.resultModalInstance.show();
      }

      await this.fetchResults(t.id);
    },
    closeResultModal() {
      if (this.resultModalInstance) this.resultModalInstance.hide();
    },
    async fetchResults(target_id) {
      try {
        const res = await axios.get(`/api-digital/revenue/get_results.php?target_id=${target_id}`);
        this.resultsData = res.data;
      } catch (err) {
        console.error(err);
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
          this.fetchResults(this.selectedTarget.id);
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        console.error('Save error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      }
    },
    async editResult(r) {
      const { value: formValues } = await Swal.fire({
        title: 'แก้ไขผลงาน',
        html:
          `<div class="text-start mb-3"><label class="form-label">จำนวนผลงาน</label><input id="swal-achieved" type="number" step="0.01" class="form-control" value="${r.achieved_items !== null ? r.achieved_items : ''}"></div>` +
          `<div class="text-start mb-3"><label class="form-label">ยอดจัดเก็บได้จริง (บาท)</label><input id="swal-collected" type="number" step="0.01" class="form-control" value="${r.collected_amount}"></div>` +
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
            this.fetchResults(this.selectedTarget.id);
            this.fetchTargets();
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
            this.fetchResults(this.selectedTarget.id);
            this.fetchTargets();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถลบได้', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('ข้อผิดพลาด', 'ตรวจพบปัญหาการเชื่อมต่อ', 'error');
        }
      }
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchTargets();
    this.fetchHrPersons();
    this.fetchClaimPrograms();
  }
};
</script>

<style scoped>
.text-sm {
  font-size: 0.9rem;
}
</style>
