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
              <li class="breadcrumb-item active" aria-current="page">ประวัติและรายงาน</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-file-earmark-text me-2"></i>รายงานสรุปความเคลื่อนไหว
          </h2>
        </div>
      </div>

      <!-- Filters -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <form class="row g-3" @submit.prevent="fetchTransactions">
            <div class="col-md-2">
              <label class="form-label">วัสดุ</label>
              <select class="form-select" v-model="filters.material_id">
                <option value="">-- ทั้งหมด --</option>
                <option v-for="m in materials" :key="m.id" :value="m.id">
                  [{{ m.code }}] {{ m.name }}
                </option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">ประเภทรายการ</label>
              <select class="form-select" v-model="filters.action_type">
                <option value="">-- ทั้งหมด --</option>
                <option value="IN">รับเข้า (IN)</option>
                <option value="OUT">จ่ายออก (OUT)</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">รูปแบบเวลา</label>
              <select class="form-select" v-model="timeFormat">
                <option value="date">ระบุวันที่</option>
                <option value="month">ระบุเดือน</option>
                <option value="year">ระบุปี</option>
              </select>
            </div>

            <template v-if="timeFormat === 'date'">
              <div class="col-md-2">
                <label class="form-label">ตั้งแต่วันที่</label>
                <input type="date" class="form-control" v-model="filters.start_date" />
              </div>
              <div class="col-md-2">
                <label class="form-label">ถึงวันที่</label>
                <input type="date" class="form-control" v-model="filters.end_date" />
              </div>
            </template>

            <template v-if="timeFormat === 'month'">
              <div class="col-md-4">
                <label class="form-label">เดือน</label>
                <input type="month" class="form-control" v-model="filterMonth" />
              </div>
            </template>

            <template v-if="timeFormat === 'year'">
              <div class="col-md-4">
                <label class="form-label">ปี</label>
                <select class="form-select" v-model="filterYear">
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
            </template>

            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-1"></i>ค้นหา
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Report Table -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">วันที่ทำรายการ</th>
                  <th>รหัสสินค้า</th>
                  <th>ชื่อวัสดุ</th>
                  <th>ประเภท</th>
                  <th class="text-center">จำนวน</th>
                  <th>หน่วยงาน/ผู้รับ</th>
                  <th>แหล่งที่มา/ผู้จำหน่าย</th>
                  <th>ผู้บันทึก/อนุมัติ</th>
                  <th>หมายเหตุ</th>
                  <th class="text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="transactions.length === 0">
                  <td colspan="8" class="text-center py-5 text-muted">
                    ไม่พบข้อมูลการทำรายการในช่วงเวลาที่เลือก
                  </td>
                </tr>
                <tr v-for="t in transactions" :key="t.id">
                  <td class="ps-4">{{ formatDate(t.action_date) }}</td>
                  <td>{{ t.material_code }}</td>
                  <td class="fw-bold">{{ t.material_name }}</td>
                  <td>
                    <span v-if="t.action_type === 'IN'" class="badge bg-success rounded-pill"
                      >รับเข้า</span
                    >
                    <span v-else class="badge bg-warning text-dark rounded-pill">จ่ายออก</span>
                  </td>
                  <td
                    class="text-center fw-bold"
                    :class="t.action_type === 'IN' ? 'text-success' : 'text-warning'"
                  >
                    {{ t.action_type === 'IN' ? '+' : '-' }}{{ t.quantity }}
                    <span class="fw-normal text-muted fs-6">{{ t.unit }}</span>
                  </td>
                  <td>
                    <span v-if="t.action_type === 'OUT'">
                      <div class="fw-bold">{{ t.receiver_name }}</div>
                      <div class="small text-muted">{{ t.reference_dest }}</div>
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>
                    <span v-if="t.action_type === 'IN'">{{ t.reference_dest }}</span>
                    <span v-else class="text-muted">-</span>
                  </td>
                  <td>{{ t.user_profile_name }}</td>
                  <td class="text-muted small">{{ t.note }}</td>
                  <td class="text-center">
                    <button
                      class="btn btn-sm btn-outline-primary border-0 me-1"
                      title="แก้ไขรายการ"
                      @click="openEditModal(t)"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-danger border-0"
                      title="ยกเลิกรายการ"
                      @click="cancelTransaction(t.id)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editTxModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">แก้ไขรายการ</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveEditTx">
              <div class="mb-3">
                <label class="form-label">วันที่ทำรายการ</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  v-model="editForm.action_date"
                  required
                />
              </div>
              <div class="mb-3" v-if="editForm.action_type === 'OUT'">
                <label class="form-label">ชื่อผู้รับ</label>
                <input type="text" class="form-control" v-model="editForm.receiver_name" required />
              </div>
              <div class="mb-3">
                <label class="form-label">{{
                  editForm.action_type === 'IN' ? 'แหล่งที่มา/ผู้จำหน่าย' : 'หน่วยงาน'
                }}</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="editForm.reference_dest"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">จำนวน</label>
                <input
                  type="number"
                  class="form-control"
                  v-model.number="editForm.quantity"
                  required
                  min="1"
                />
              </div>
              <div class="mb-3">
                <label class="form-label">หมายเหตุ</label>
                <textarea class="form-control" v-model="editForm.note" rows="2"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" @click="saveEditTx">
              บันทึกการแก้ไข
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtReport',
  data() {
    return {
      materials: [],
      transactions: [],
      filters: {
        material_id: '',
        action_type: '',
        start_date: moment().startOf('month').format('YYYY-MM-DD'),
        end_date: moment().endOf('month').format('YYYY-MM-DD')
      },
      timeFormat: 'date',
      filterMonth: moment().format('YYYY-MM'),
      filterYear: moment().format('YYYY'),
      editForm: {
        id: null,
        action_type: '',
        action_date: '',
        receiver_name: '',
        reference_dest: '',
        quantity: 1,
        note: ''
      },
      editModalInstance: null
    };
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
    async fetchTransactions() {
      try {
        let sd = this.filters.start_date;
        let ed = this.filters.end_date;

        if (this.timeFormat === 'month') {
          sd = moment(this.filterMonth, 'YYYY-MM').startOf('month').format('YYYY-MM-DD');
          ed = moment(this.filterMonth, 'YYYY-MM').endOf('month').format('YYYY-MM-DD');
        } else if (this.timeFormat === 'year') {
          sd = moment(this.filterYear, 'YYYY').startOf('year').format('YYYY-MM-DD');
          ed = moment(this.filterYear, 'YYYY').endOf('year').format('YYYY-MM-DD');
        }

        let qs = `?material_id=${this.filters.material_id}&action_type=${this.filters.action_type}&start_date=${sd}&end_date=${ed}`;
        const res = await axios.get('/api-digital/material_v2/get_transactions.php' + qs);
        if (res.data.status === 'success') {
          this.transactions = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY HH:mm');
    },
    openEditModal(item) {
      this.editForm = {
        id: item.id,
        action_type: item.action_type,
        action_date: moment(item.action_date).format('YYYY-MM-DDTHH:mm'),
        receiver_name: item.receiver_name || '',
        reference_dest: item.reference_dest || '',
        quantity: item.quantity,
        note: item.note || ''
      };
      // eslint-disable-next-line no-undef
      if (!this.editModalInstance) {
        // eslint-disable-next-line no-undef
        this.editModalInstance = new bootstrap.Modal(document.getElementById('editTxModal'));
      }
      this.editModalInstance.show();
    },
    async saveEditTx() {
      try {
        const res = await axios.post(
          '/api-digital/material_v2/update_transaction.php',
          this.editForm
        );
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message,
            icon: 'success',
            confirmButtonText: 'ตกลง'
          });
          this.editModalInstance.hide();
          this.fetchTransactions();
        } else {
          Swal.fire('ข้อผิดพลาด!', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด!', 'ระบบขัดข้อง', 'error');
      }
    },
    cancelTransaction(id) {
      Swal.fire({
        title: 'ยืนยันการยกเลิกรายการ?',
        text: 'หากยกเลิกรายการนี้ ยอดวัสดุจะถูกคำนวณย้อนกลับอัตโนมัติ คุณต้องการดำเนินการต่อหรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันยกเลิกรายการ',
        cancelButtonText: 'ปิด'
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const res = await axios.post('/api-digital/material_v2/delete_transaction.php', {
              id: id
            });
            if (res.data.status === 'success') {
              Swal.fire({
                title: 'ยกเลิกสำเร็จ!',
                text: res.data.message,
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'ตกลง'
              });
              this.fetchTransactions();
            } else {
              Swal.fire('ข้อผิดพลาด!', res.data.message, 'error');
            }
          } catch (err) {
            Swal.fire('ข้อผิดพลาด!', 'ระบบขัดข้อง', 'error');
          }
        }
      });
    }
  },
  mounted() {
    this.fetchMaterials();
    this.fetchTransactions();
  },
  computed: {
    yearOptions() {
      const currentYear = parseInt(moment().format('YYYY'));
      const years = [];
      for (let i = 0; i < 5; i++) {
        years.push((currentYear - i).toString());
      }
      return years;
    }
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
</style>
