<template>
  <div class="container mt-4">
    <h2>รายงานผู้ป่วยเบาหวาน (DM)</h2>

    <!-- ตัวเลือกช่วงวันที่ -->
    <div class="row mb-3">
      <div class="col-md-5">
        <label>จากวันที่:</label>
        <input type="date" v-model="startDate" class="form-control" />
      </div>
      <div class="col-md-5">
        <label>ถึงวันที่:</label>
        <input type="date" v-model="endDate" class="form-control" />
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button @click="fetchDmPatients" class="btn btn-primary w-100">
          ดูข้อมูล
        </button>
      </div>
    </div>

    <!-- แสดงผลรายชื่อผู้ป่วย -->
    <div v-if="patients.length > 0" class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>วันที่</th>
            <th>หมายเลขผู้ป่วย (HN)</th>
            <th>ชื่อ-นามสกุล</th>
            <th>อายุ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(patient, index) in patients" :key="index">
            <td>{{ patient.vstdate }}</td>
            <td>{{ patient.hn }}</td>
            <td>{{ patient.pt_name }}</td>
            <td>{{ patient.age_y }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ถ้าไม่มีข้อมูล -->
    <div v-else>
      <p>ไม่พบข้อมูลสำหรับช่วงวันที่ที่เลือก</p>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      startDate: "2025-03-28", // วันที่เริ่มต้น
      endDate: "2025-03-28",   // วันที่สิ้นสุด
      patients: [],            // รายชื่อผู้ป่วยที่ดึงจาก API
    };
  },
  methods: {
    async fetchDmPatients() {
      try {
        const response = await axios.get(
          "http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/get_dm_report.php", 
          {
            params: {
              start_date: this.startDate,  // ส่งพารามิเตอร์ start_date
              end_date: this.endDate,      // ส่งพารามิเตอร์ end_date
            }
          }
        );
        // อัพเดทข้อมูลผู้ป่วย
        if (response.data && response.data.error) {
          this.patients = [];
          alert(response.data.error);
        } else {
          this.patients = response.data;
        }
      } catch (error) {
        console.error("Error fetching DM patients:", error);
      }
    },
  },
  mounted() {
    // เรียก fetch ข้อมูลทันทีเมื่อหน้าถูกโหลด
    this.fetchDmPatients();
  },
};
</script>

<style scoped>
.table-responsive {
  margin-top: 20px;
}
</style>
