<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/med-admin">หน้าหลักวัสดุ</router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">เบิกจ่ายวัสดุ</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-warning mb-0">
            <i class="bi bi-box-arrow-up me-2"></i>บันทึกจ่ายออก (Stock Out)
          </h2>
        </div>
        <div>
          <button @click="openLogModal" class="btn btn-outline-info rounded-pill me-2">
            <i class="bi bi-clock-history me-1"></i>ดูประวัติ
          </button>
          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </router-link>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitTx">
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">
                    1. เลือกวัสดุชิ้นที่ต้องการเบิกจ่าย
                  </h5>
                  <div class="position-relative">
                    <label class="form-label"
                      >ค้นหาและเลือกวัสดุ <span class="text-danger">*</span></label
                    >
                    <select class="form-select form-select-lg" v-model="form.material_id" required>
                      <option value="" disabled selected>-- เลือกวัสดุ --</option>
                      <option
                        v-for="m in materials"
                        :key="m.id"
                        :value="m.id"
                        :disabled="m.balance <= 0"
                      >
                        [{{ m.code }}] {{ m.name }} (คงเหลือ: {{ m.balance }} {{ m.unit }})
                        {{ m.balance <= 0 ? '- สินค้าหมด' : '' }}
                      </option>
                    </select>
                  </div>
                </div>

                <div class="mb-4" v-if="form.material_id">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. รายละเอียดการเบิกจ่าย</h5>
                  
                  <!-- Show Lots -->
                  <div class="alert alert-info py-2 mb-4" v-if="availableLots.length > 0">
                    <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i>Lot ที่มีอยู่ในคลัง (จ่ายแบบ FIFO):</div>
                    <ul class="mb-0 ps-3 small">
                      <li v-for="lot in availableLots" :key="lot.id">
                        Lot: <span class="fw-bold">{{ lot.lot_number || 'ไม่ระบุ' }}</span> 
                        <span class="text-muted ms-2">(เหลือ {{ lot.remaining_qty }} ชิ้น, รับเข้าเมื่อ {{ formatDateShort(lot.receive_date) }})</span>
                      </li>
                    </ul>
                  </div>

                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label"
                        >จำนวนที่เบิกจ่าย <span class="text-danger">*</span></label
                      >
                      <div class="input-group">
                        <input
                          type="number"
                          class="form-control form-control-lg"
                          v-model.number="form.quantity"
                          required
                          min="1"
                          :max="selectedBalance"
                        />
                        <span class="input-group-text">{{ selectedUnit }}</span>
                      </div>
                      <small class="text-danger" v-if="form.quantity > selectedBalance"
                        >ระบุจำนวนเกินกว่าที่มีในคลัง!</small
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label"
                        >วันที่จ่าย <span class="text-danger">*</span></label
                      >
                      <input
                        type="datetime-local"
                        class="form-control form-control-lg"
                        v-model="form.action_date"
                        required
                      />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label"
                        >ชื่อผู้อนุมัติ/ผู้จ่าย (IT) <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.user_profile_name"
                        required
                        placeholder="-- พิมพ์เพื่อค้นหา --"
                        list="itUsersList"
                        autocomplete="off"
                      />
                      <datalist id="itUsersList">
                        <option
                          v-for="user in itUsers"
                          :key="user.id"
                          :value="user.fullname"
                        ></option>
                      </datalist>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label"
                        >ชื่อผู้รับ <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.receiver_name"
                        required
                        placeholder="-- พิมพ์เพื่อค้นหา --"
                        list="allStaffList"
                        autocomplete="off"
                      />
                      <datalist id="allStaffList">
                        <option
                          v-for="staff in allStaff"
                          :key="staff.ID"
                          :value="staff.FULLNAME"
                        ></option>
                      </datalist>
                    </div>

                    <div class="col-md-12">
                      <label class="form-label"
                        >ชื่อแผนกที่เบิก (หน่วยงาน) <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.reference_dest"
                        required
                        placeholder="-- พิมพ์เพื่อค้นหา --"
                        list="departments"
                        autocomplete="off"
                      />
                      <datalist id="departments">
                        <option
                          v-for="dept in allDepartments"
                          :key="dept.HR_DEPARTMENT_SUB_ID"
                          :value="dept.HR_DEPARTMENT_SUB_NAME"
                        ></option>
                      </datalist>
                    </div>

                    <div class="col-12">
                      <label class="form-label">หมายเหตุ (เหตุผลการเบิก)</label>
                      <textarea
                        class="form-control"
                        v-model="form.note"
                        rows="2"
                        placeholder="เช่น คอมพิวเตอร์เสีย, ขอเพิ่ม"
                      ></textarea>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-5" v-if="form.material_id">
                  <button
                    type="button"
                    class="btn btn-light rounded-pill px-4 me-2"
                    @click="resetForm"
                  >
                    ล้างข้อมูล
                  </button>
                  <button
                    type="submit"
                    class="btn btn-warning rounded-pill px-5 fw-bold"
                    :disabled="isSubmitting || form.quantity > selectedBalance"
                  >
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    บันทึกรายการจ่ายออก
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Log Modal -->
    <MtLogModal ref="logModal" logType="out" modalId="outLogModal" />
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';
import MtLogModal from './MtLogModal.vue';

export default {
  name: 'MtTransactionOut',
  components: {
    MtLogModal
  },
  data() {
    return {
      materials: [],
      itUsers: [],
      allStaff: [],
      allDepartments: [],
      availableLots: [],
      isSubmitting: false,
      form: {
        material_id: '',
        quantity: 1,
        action_date: moment().format('YYYY-MM-DDTHH:mm'),
        user_profile_name: '',
        receiver_name: '',
        reference_dest: '',
        note: ''
      }
    };
  },
  computed: {
    selectedMaterial() {
      if (!this.form.material_id) return null;
      return this.materials.find((x) => x.id === this.form.material_id);
    },
    selectedUnit() {
      return this.selectedMaterial ? this.selectedMaterial.unit : '';
    },
    selectedBalance() {
      return this.selectedMaterial ? parseInt(this.selectedMaterial.balance) : 0;
    }
  },
  watch: {
    'form.material_id'(newVal) {
      if (newVal) {
        this.fetchLots(newVal);
      } else {
        this.availableLots = [];
      }
    }
  },
  methods: {
    formatDateShort(dateStr) {
      if (!dateStr) return '';
      return moment(dateStr).format('DD/MM/YYYY');
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/med_material/med_get_materials.php');
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async fetchLots(materialId) {
      try {
        const res = await axios.get(`/api-digital/med_material/med_get_lots.php?material_id=${materialId}`);
        if (res.data.status === 'success') {
          this.availableLots = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch lots', err);
      }
    },
    async fetchItUsers() {
      try {
        const res = await axios.get('/api-digital/med_material/med_get_it_users.php');
        if (res.data.status === 'success') {
          this.itUsers = res.data.data;

          // Set default user if matched
          const currentName = localStorage.getItem('user_name');
          if (currentName && this.itUsers.find((u) => u.fullname === currentName)) {
            this.form.user_profile_name = currentName;
          }
        }
      } catch (err) {
        console.error('Failed to fetch IT users', err);
      }
    },
    async fetchAllStaff() {
      try {
        const res = await axios.get('/api-hosoffice/get_all_staff.php');
        if (res.data.status === 'success') {
          this.allStaff = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch all staff', err);
      }
    },
    async fetchDepartments() {
      try {
        const res = await axios.get('/api-hosoffice/get_departments.php');
        if (res.data.status === 'success') {
          this.allDepartments = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch departments', err);
      }
    },
    resetForm() {
      this.form.material_id = '';
      this.form.quantity = 1;
      this.form.receiver_name = '';
      this.form.reference_dest = '';
      this.form.note = '';
      this.form.action_date = moment().format('YYYY-MM-DDTHH:mm');
    },
    async submitTx() {
      if (this.form.quantity <= 0) {
        alert('จำนวนต้องมากกว่า 0');
        return;
      }
      if (this.form.quantity > this.selectedBalance) {
        alert('ยอดคงคลังไม่เพียงพอ');
        return;
      }
      this.isSubmitting = true;
      try {
        const res = await axios.post('/api-digital/med_material/med_transaction_out.php', this.form);
        if (res.data.status === 'success') {
          alert('บันทึกการเบิกจ่ายสำเร็จ ยอดคงเหลือใหม่: ' + res.data.new_balance);
          this.$router.push('/med-admin');
        } else {
          alert(res.data.message);
        }
      } catch (err) {
        alert('ระบบขัดข้อง');
      } finally {
        this.isSubmitting = false;
      }
    },
    openLogModal() {
      // eslint-disable-next-line no-undef
      const modal = new bootstrap.Modal(document.getElementById('outLogModal'));
      modal.show();
      this.$refs.logModal.fetchLogs();
    }
  },
  mounted() {
    this.fetchItUsers();
    this.fetchAllStaff();
    this.fetchDepartments();
    this.fetchMaterials();
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
</style>
