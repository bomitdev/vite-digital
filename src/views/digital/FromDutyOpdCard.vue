<template>
  <div class="container mt-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h2 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>เพิ่มตารางเวรห้องบัตร</h2>
      </div>
      <div class="card-body">
        <form @submit.prevent="submitForm">
          <!-- เลือกพนักงาน -->
          <div class="mb-4">
            <label for="employeeId" class="form-label fw-bold">เลือกเจ้าหน้าที่ขึ้นเวร</label>
            <select
              v-model="form.employees_opdcard_id"
              class="form-select form-select-lg"
              id="employeeId"
              required
            >
              <option value="" disabled>-- กรุณาเลือกพนักงาน --</option>
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
            <button type="submit" class="btn btn-primary btn-lg" :disabled="loading">
              <i class="bi bi-save"></i> {{ loading ? 'กำลังบันทึก...' : 'บันทึกทั้งหมด' }}
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
        employees_opdcard_id: '',
        dates: [{ date: '', is_special: 0 }]
      },
      message: '',
      loading: false
    };
  },
  mounted() {
    this.fetchEmployees();
  },
  methods: {
    async fetchEmployees() {
      try {
        const response = await axios.get('/api-digital/duties_opdcard/get_employee_opdcard.php');
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
      if (!this.form.employees_opdcard_id || this.form.dates.some((date) => !date)) {
        this.message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        return;
      }

      this.loading = true;
      try {
        const response = await axios.post(
          '/api-digital/duties_opdcard/add_duties_opdcard.php',
          this.form
        );
        this.message = response.data.message || 'บันทึกข้อมูลสำเร็จ';
        this.form.employees_opdcard_id = '';
        this.form.dates = [{ date: '', is_special: 0 }];
      } catch (error) {
        console.error(error);
        this.message = error.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
      } finally {
        this.loading = false;
        setTimeout(() => {
          this.message = '';
        }, 5000);
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
