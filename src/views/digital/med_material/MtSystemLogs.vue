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
              <li class="breadcrumb-item active" aria-current="page">ประวัติการใช้งานระบบ (Log)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-clock-history me-2"></i>ประวัติการใช้งานระบบ (System Logs)
          </h2>
        </div>
        <div>
          <button v-if="$route.query.type" @click="clearFilter" class="btn btn-outline-danger rounded-pill me-2">
            <i class="bi bi-x-circle me-1"></i>ล้างตัวกรอง
          </button>
          <router-link to="/med-admin" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </router-link>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="15%">วันที่-เวลา</th>
                  <th width="20%">ผู้ทำรายการ</th>
                  <th width="15%">ประเภท</th>
                  <th width="50%">รายละเอียด</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="logs.length === 0">
                  <td colspan="4" class="text-center py-4 text-muted">ยังไม่มีประวัติการทำรายการ</td>
                </tr>
                <tr v-for="log in logs" :key="log.id">
                  <td class="text-muted">{{ formatDate(log.created_at) }}</td>
                  <td class="fw-bold text-primary"><i class="bi bi-person-circle me-1"></i>{{ log.user_profile_name }}</td>
                  <td><span class="badge bg-secondary">{{ log.action }}</span></td>
                  <td>{{ log.details }}</td>
                </tr>
              </tbody>
            </table>
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
  name: 'MtSystemLogs',
  data() {
    return {
      logs: []
    };
  },
  watch: {
    '$route.query.type'() {
      this.fetchLogs();
    }
  },
  mounted() {
    this.fetchLogs();
  },
  methods: {
    async fetchLogs() {
      try {
        const type = this.$route.query.type || '';
        const res = await axios.get('/api-digital/med_material/med_get_logs.php?type=' + type);
        if (res.data.status === 'success') {
          this.logs = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    clearFilter() {
      this.$router.push('/med-admin/logs');
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY HH:mm:ss');
    }
  }
};
</script>

<style scoped>
.page-container {
  font-family: 'Prompt', sans-serif;
}
</style>
