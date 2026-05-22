<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/material-v2">หน้าหลักวัสดุ</router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">รับเข้าคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-success mb-0">
            <i class="bi bi-box-arrow-in-down me-2"></i>บันทึกรับเข้าคลัง (Stock In)
          </h2>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitTx">
                <div class="mb-4">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">
                    1. เลือกวัสดุชิ้นที่ต้องการรับเข้า
                  </h5>
                  <div class="position-relative">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                      <label class="form-label mb-0"
                        >ค้นหาและเลือกวัสดุ <span class="text-danger">*</span></label
                      >
                      <router-link
                        to="/material-v2/stock"
                        class="text-decoration-none small text-primary fw-bold"
                      >
                        <i class="bi bi-plus-circle me-1"></i>ยังไม่มีวัสดุนี้ในระบบ?
                        (เพิ่มวัสดุใหม่)
                      </router-link>
                    </div>
                    <select class="form-select form-select-lg" v-model="form.material_id" required>
                      <option value="" disabled selected>-- เลือกวัสดุ --</option>
                      <option v-for="m in materials" :key="m.id" :value="m.id">
                        [{{ m.code }}] {{ m.name }} (คงเหลือปัจจุบัน: {{ m.balance }} {{ m.unit }})
                      </option>
                    </select>
                  </div>
                </div>

                <div class="mb-4" v-if="form.material_id">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">2. รายละเอียดการรับเข้า</h5>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label"
                        >จำนวนที่รับเข้า <span class="text-danger">*</span></label
                      >
                      <div class="input-group">
                        <input
                          type="number"
                          class="form-control form-control-lg"
                          v-model.number="form.quantity"
                          required
                          min="1"
                        />
                        <span class="input-group-text">{{ selectedUnit }}</span>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label"
                        >วันที่รับเข้า <span class="text-danger">*</span></label
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
                        >ชื่อผู้รับผิดชอบ <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.user_profile_name"
                        required
                        readonly
                      />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label"
                        >แหล่งที่มา / ผู้จำหน่าย <span class="text-danger">*</span></label
                      >
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.reference_dest"
                        required
                        placeholder="เช่น บริษัท A, สินค้าบริจาค"
                        list="vendors"
                      />
                      <datalist id="vendors">
                        <option value="Advice"></option>
                        <option value="JIB"></option>
                        <option value="IT City"></option>
                      </datalist>
                    </div>

                    <div class="col-12">
                      <label class="form-label">หมายเหตุ</label>
                      <textarea
                        class="form-control"
                        v-model="form.note"
                        rows="2"
                        placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)"
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
                    class="btn btn-success rounded-pill px-5 fw-bold"
                    :disabled="isSubmitting"
                  >
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    บันทึกรายการรับเข้า
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
import moment from 'moment';

export default {
  name: 'MtTransactionIn',
  data() {
    return {
      materials: [],
      isSubmitting: false,
      form: {
        material_id: '',
        quantity: 1,
        action_date: moment().format('YYYY-MM-DDTHH:mm'),
        user_profile_name: localStorage.getItem('user_name') || 'Admin',
        reference_dest: '',
        note: ''
      }
    };
  },
  computed: {
    selectedUnit() {
      if (!this.form.material_id) return '';
      const m = this.materials.find((x) => x.id === this.form.material_id);
      return m ? m.unit : '';
    }
  },
  methods: {
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/material_v2/get_materials.php');
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    resetForm() {
      this.form.material_id = '';
      this.form.quantity = 1;
      this.form.reference_dest = '';
      this.form.note = '';
      this.form.action_date = moment().format('YYYY-MM-DDTHH:mm');
    },
    async submitTx() {
      if (this.form.quantity <= 0) {
        alert('จำนวนต้องมากกว่า 0');
        return;
      }
      this.isSubmitting = true;
      try {
        const res = await axios.post('/api-digital/material_v2/transaction_in.php', this.form);
        if (res.data.status === 'success') {
          alert('บันทึกสำเร็จ ยอดคงเหลือใหม่: ' + res.data.new_balance);
          this.$router.push('/material-v2');
        } else {
          alert(res.data.message);
        }
      } catch (err) {
        alert('ระบบขัดข้อง');
      } finally {
        this.isSubmitting = false;
      }
    }
  },
  mounted() {
    // If not local storage, fetch user profile
    if (!localStorage.getItem('user_name')) {
      axios.get('/api-hosoffice/get_user_profile.php').then((res) => {
        if (res.data.status === 'success') this.form.user_profile_name = res.data.fullname;
      });
    }
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
