<template>
  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>จัดการตารางเวรเบิกเครม</h2>

      <div class="d-flex align-items-center gap-2">
        <label class="fw-bold">เลือกเดือน:</label>
        <select class="form-select w-auto" v-model="selectedMonth" @change="fetchDuties">
          <option v-for="(mName, index) in monthNames" :key="index" :value="index + 1">
            {{ mName }}
          </option>
        </select>
        <button class="btn btn-info text-white fw-bold ms-2" @click="openEmployeesModal">
          <i class="bi bi-person-lines-fill me-1"></i> จัดการพนักงาน
        </button>
        <button
          class="btn btn-outline-primary fw-bold ms-2"
          @click="$router.push('/ot-report-claim')"
        >
          <i class="bi bi-printer me-1"></i> พิมพ์รายงานเบิกจ่าย OT
        </button>
      </div>
    </div>

    <table class="table table-bordered table-hover mt-3">
      <thead class="table-dark">
        <tr>
          <th>วันที่</th>
          <th>ชื่อพนักงาน</th>
          <th width="150">จัดการ</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="duty in duties" :key="duty.id">
          <td>{{ formatDate(duty.date) }}</td>
          <td>{{ duty.name }}</td>
          <td>
            <button class="btn btn-warning btn-sm me-2" @click="openEditModal(duty)">แก้ไข</button>
            <button class="btn btn-danger btn-sm" @click="deleteDuty(duty.id)">ลบ</button>
          </td>
        </tr>
        <tr v-if="duties.length === 0">
          <td colspan="3" class="text-center p-4 text-muted">- ไม่พบข้อมูลในเดือนนี้ -</td>
        </tr>
      </tbody>
    </table>

    <div v-if="showModal" class="modal d-block" style="background: rgba(0, 0, 0, 0.5)">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">แก้ไขข้อมูลเวร</h5>
            <button type="button" class="btn-close" @click="showModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateDuty">
              <div class="mb-3">
                <label class="form-label">วันที่</label>
                <input type="date" class="form-control" v-model="currentDuty.date" required />
              </div>
              <div class="mb-3">
                <label class="form-label">ประเภทเรทราคา</label>
                <select class="form-select" v-model="currentDuty.is_special">
                  <option :value="0">เรทปกติ</option>
                  <option :value="1">เรทวันหยุดพิเศษหรือปกติ</option>
                  <option :value="2">เรทวันหยุด 2 เท่า</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">พนักงาน</label>
                <select class="form-select" v-model="currentDuty.employee_id" required>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                    {{ emp.name }}
                  </option>
                </select>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" @click="showModal = false">
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal จัดการพนักงาน (เรท OT) -->
    <div v-if="showEmployeesModal" class="modal d-block" style="background: rgba(0, 0, 0, 0.5)">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-person-lines-fill me-2"></i>จัดการพนักงานและเรท OT
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              @click="showEmployeesModal = false"
            ></button>
          </div>
          <div class="modal-body p-0">
            <div class="table-responsive" style="max-height: 500px">
              <table class="table table-hover table-bordered mb-0 align-middle">
                <thead class="table-light position-sticky top-0 shadow-sm" style="z-index: 1">
                  <tr>
                    <th>ชื่อ</th>
                    <th>ตำแหน่ง</th>
                    <th class="text-center">เรทวันหยุด</th>
                    <th class="text-center">เรททำการ</th>
                    <th class="text-center">เรทวันหยุด(พิเศษ)</th>
                    <th class="text-center">เรทวันหยุด 2 เท่า</th>
                    <th class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emp in employees" :key="emp.id">
                    <td class="text-nowrap">{{ emp.name }}</td>
                    <td>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="emp.position"
                        placeholder="เจ้าหน้าที่เบิกเครม"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        class="form-control form-control-sm text-center"
                        v-model="emp.rate_holiday"
                        placeholder="690"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        class="form-control form-control-sm text-center"
                        v-model="emp.rate_weekday"
                        placeholder="650"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        class="form-control form-control-sm text-center"
                        v-model="emp.rate_holiday_special"
                        placeholder=""
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        class="form-control form-control-sm text-center"
                        v-model="emp.rate_weekday_special"
                        placeholder=""
                      />
                    </td>
                    <td class="text-center">
                      <button
                        class="btn btn-sm btn-success"
                        @click="saveEmployee(emp, $event)"
                        title="บันทึกข้อมูลส่วนบุคคล"
                      >
                        <i class="bi bi-floppy"></i> บันทึก
                      </button>
                    </td>
                  </tr>
                  <tr v-if="employees.length === 0">
                    <td colspan="7" class="text-center text-muted py-3">ไม่พบข้อมูลพนักงาน</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showEmployeesModal = false">
              ปิด
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DutyManagerClaim',
  data() {
    const today = new Date();

    return {
      duties: [],
      employees: [],
      showModal: false,
      showEmployeesModal: false,
      currentDuty: {},

      selectedMonth: today.getMonth() + 1,
      selectedYear: today.getFullYear(),

      monthNames: [
        'มกราคม',
        'กุมภาพันธ์',
        'มีนาคม',
        'เมษายน',
        'พฤษภาคม',
        'มิถุนายน',
        'กรกฎาคม',
        'สิงหาคม',
        'กันยายน',
        'ตุลาคม',
        'พฤศจิกายน',
        'ธันวาคม'
      ],

      apiUrl: '/api-digital/duties_claim/crud_duties_claim.php'
    };
  },
  mounted() {
    this.fetchDuties();
    this.fetchEmployees();
  },
  methods: {
    fetchDuties() {
      axios
        .get(
          `${this.apiUrl}?action=getDuties&month=${this.selectedMonth}&year=${this.selectedYear}`
        )
        .then((response) => {
          this.duties = response.data;
        })
        .catch((error) => console.error('Error fetching duties:', error));
    },
    fetchEmployees() {
      axios
        .get(`${this.apiUrl}?action=getEmployees`)
        .then((response) => {
          this.employees = response.data;
        })
        .catch((error) => console.error(error));
    },
    openEditModal(duty) {
      this.currentDuty = JSON.parse(JSON.stringify(duty));
      this.showModal = true;
    },
    openEmployeesModal() {
      this.fetchEmployees();
      this.showEmployeesModal = true;
    },
    saveEmployee(emp, event) {
      axios
        .post(`${this.apiUrl}?action=updateEmployee`, emp)
        .then((response) => {
          if (response.data.message === 'Update employee successful') {
            const btn = event.target.closest('button');
            const oldIcon = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> บันทึกแล้ว';
            btn.classList.replace('btn-success', 'btn-outline-success');
            setTimeout(() => {
              btn.innerHTML = oldIcon;
              btn.classList.replace('btn-outline-success', 'btn-success');
            }, 2000);
          } else {
            alert('เกิดข้อผิดพลาด: ' + response.data.message);
          }
        })
        .catch((error) => console.error(error));
    },
    updateDuty() {
      axios
        .post(`${this.apiUrl}?action=updateDuty`, this.currentDuty)
        .then((response) => {
          if (response.data.message === 'Update successful') {
            alert('บันทึกข้อมูลเรียบร้อย');
            this.showModal = false;
            this.fetchDuties();
          } else {
            alert('เกิดข้อผิดพลาด: ' + response.data.message);
          }
        })
        .catch((error) => console.error(error));
    },
    deleteDuty(id) {
      if (confirm('คุณต้องการลบข้อมูลนี้ใช่หรือไม่?')) {
        axios
          .post(`${this.apiUrl}?action=deleteDuty`, { id: id })
          .then((response) => {
            if (response.data.message === 'Delete successful') {
              alert('ลบข้อมูลเรียบร้อย');
              this.fetchDuties();
            } else {
              alert('เกิดข้อผิดพลาด: ' + response.data.message);
            }
          })
          .catch((error) => console.error(error));
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
    }
  }
};
</script>

<style scoped>
.modal.d-block {
  z-index: 1050;
}
</style>
