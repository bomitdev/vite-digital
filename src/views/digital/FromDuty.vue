<template>
  <div class="container mt-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h2 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>เพิ่มตารางเวร IT</h2>
      </div>
      <div class="card-body">
        <!-- คัดลอกเวรจากห้องบัตร -->
        <div class="mb-4 p-3 border border-info rounded bg-info bg-opacity-10">
          <h5 class="fw-bold text-info-emphasis"><i class="bi bi-files"></i> คัดลอกเวรทั้งเดือนจากห้องบัตร</h5>
          <p class="small text-muted mb-2">ดึงข้อมูลเวรจากห้องบัตรมาใส่ในตาราง IT (คำเตือน: ข้อมูลเวร IT เดิมของเดือนที่เลือกจะถูกแทนที่)</p>
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <select class="form-select" v-model="copyMonth">
                <option v-for="(m, i) in months" :key="i" :value="i+1">{{ m }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <select class="form-select" v-model="copyYear">
                <option v-for="y in [currentYear-1, currentYear, currentYear+1]" :key="y" :value="y">{{ y }} ({{ y + 543 }})</option>
              </select>
            </div>
            <div class="col-md-4">
              <button type="button" class="btn btn-info text-dark w-100 fw-bold" @click="copyFromOpdCard">
                <i class="bi bi-box-arrow-in-down"></i> ดึงข้อมูลเวร
              </button>
            </div>
          </div>
        </div>
        
        <hr class="mb-4" />

        <form @submit.prevent="submitForm">
          <h5 class="fw-bold text-primary mb-3"><i class="bi bi-person-plus"></i> เพิ่มเวรรายบุคคล</h5>
          <!-- เลือกพนักงาน -->
          <div class="mb-4">
            <label for="employeeId" class="form-label fw-bold">เลือกเจ้าหน้าที่ขั้นเวร</label>
            <select
              v-model="form.employee_id"
              class="form-select form-select-lg"
              id="employeeId"
              required
            >
              <option value="" disabled selected>-- กรุณาเลือกพนักงาน --</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ employee.name }}
              </option>
            </select>
          </div>

          <!-- เพิ่มหลายวัน -->
          <div class="mb-4">
            <label class="form-label fw-bold">วันที่ปฏิบัติงาน</label>
            <div class="border rounded p-3 bg-light">
              <div
                v-for="(date, index) in form.dates"
                :key="index"
                class="row g-2 mb-2 align-items-center"
              >
                <div class="col-md-5">
                  <input type="date" v-model="form.dates[index].date" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <select v-model="form.dates[index].is_special" class="form-select">
                    <option :value="0">เรทปกติ</option>
                    <option :value="1">เรทวันหยุดพิเศษหรือปกติ</option>
                    <option :value="2">เรทวันหยุด 2 เท่า</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    @click="removeDate(index)"
                  >
                    <i class="bi bi-trash"></i> ลบ
                  </button>
                </div>
              </div>
              <button type="button" class="btn btn-outline-primary mt-2" @click="addDate">
                <i class="bi bi-plus-circle"></i> เพิ่มวัน
              </button>
            </div>
            <small class="text-muted">สามารถเพิ่มหลายวันได้โดยคลิกปุ่ม "เพิ่มวัน"</small>
          </div>

          <!-- ปุ่มบันทึก -->
          <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="bi bi-save"></i> บันทึกทั้งหมด
            </button>
          </div>
        </form>

        <!-- ข้อความตอบกลับ -->
        <div
          v-if="message"
          class="alert alert-success mt-4 alert-dismissible fade show"
          role="alert"
        >
          <i class="bi bi-check-circle-fill me-2"></i>{{ message }}
          <button type="button" class="btn-close" @click="message = ''" aria-label="Close"></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      employees: [],
      form: {
        employee_id: '',
        dates: [{ date: '', is_special: 0 }]
      },
      message: '',
      copyMonth: new Date().getMonth() + 1,
      copyYear: new Date().getFullYear(),
      currentYear: new Date().getFullYear(),
      months: [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
      ]
    };
  },
  mounted() {
    this.fetchEmployees();
  },
  methods: {
    async fetchEmployees() {
      try {
        const response = await axios.get('/api-digital/duties/get_employee.php');
        this.employees = response.data.data;
      } catch (error) {
        console.error('ไม่สามารถโหลดข้อมูลพนักงานได้', error);
        this.message = 'เกิดข้อผิดพลาดในการโหลดข้อมูลพนักงาน';
      }
    },
    addDate() {
      this.form.dates.push({ date: '', is_special: 0 });
    },
    removeDate(index) {
      if (this.form.dates.length > 1) {
        this.form.dates.splice(index, 1);
      } else {
        this.message = 'ต้องมีอย่างน้อย 1 วันที่';
      }
    },
    async submitForm() {
      try {
        const response = await axios.post('/api-digital/duties/add_duties.php', this.form);
        this.message = response.data.message || 'บันทึกข้อมูลสำเร็จ';
        this.form.employee_id = '';
        this.form.dates = [{ date: '', is_special: 0 }];

        // ลบข้อความหลังจาก 5 วินาที
        setTimeout(() => {
          this.message = '';
        }, 5000);
      } catch (error) {
        console.error(error);
        this.message = error.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
      }
    },
    async copyFromOpdCard() {
      if (
        !confirm(
          `คุณต้องการคัดลอกตารางเวรจาก "ห้องบัตร" ประจำเดือน ${this.months[this.copyMonth - 1]} ${this.copyYear} มาลงใน "ตารางเวร IT" ใช่หรือไม่?\n\n(คำเตือน: ข้อมูลเวร IT ของเดือนนี้จะถูกลบและแทนที่ด้วยข้อมูลจากห้องบัตรทั้งหมด!)`
        )
      ) {
        return;
      }
      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-digital/duties/copy-schedule-from-opd.php',
          {
            month: this.copyMonth,
            year: this.copyYear
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (response.data.status === 'success') {
          this.message = response.data.message;
          setTimeout(() => {
            this.message = '';
          }, 5000);
        } else {
          alert('เกิดข้อผิดพลาด: ' + response.data.message);
        }
      } catch (error) {
        console.error('Copy Error', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      }
    }
  }
};
</script>

<style scoped>
.container {
  max-width: 700px;
}
.card {
  border-radius: 10px;
  overflow: hidden;
}
.form-select-lg {
  padding: 0.75rem 1rem;
}
.btn-lg {
  padding: 0.5rem 1.5rem;
}
.alert {
  border-radius: 8px;
}
</style>
