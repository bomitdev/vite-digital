<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><router-link to="/home-backoffice">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-dashboard">Inventory Dashboard</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">โอนย้ายวัสดุ</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-secondary mb-0">
            <i class="bi bi-arrow-left-right me-2"></i>ระบบโอนย้ายวัสดุ (Transfer)
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">รายการใบโอนย้าย (Transfer Out)</h5>
          <button class="btn btn-primary rounded-pill shadow-sm" @click="createNew">
            <i class="bi bi-plus-lg me-1"></i> โอนย้ายคลังใหม่
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted">
                <tr>
                  <th class="ps-4">เลขที่เอกสาร</th>
                  <th>วันที่โอน</th>
                  <th>ต้นทาง (From)</th>
                  <th>ปลายทาง (To)</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="5" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                  </td>
                </tr>
                <tr v-else-if="transfers.length === 0">
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    ยังไม่มีรายการโอนย้าย
                  </td>
                </tr>
                <tr v-for="t in transfers" :key="t.id" class="cursor-pointer group" @click="viewTransfer(t)">
                  <td class="ps-4 fw-bold text-primary">{{ t.transfer_no }}</td>
                  <td>{{ formatDate(t.transfer_date) }}</td>
                  <td>{{ t.from_warehouse }}</td>
                  <td>{{ t.to_warehouse }}</td>
                  <td><span class="badge bg-success">{{ t.status }}</span></td>
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
import Swal from 'sweetalert2';

export default {
  name: 'TransferDashboard',
  data() {
    return {
      transfers: [],
      loading: true
    }
  },
  mounted() {
    this.fetchTransfers();
  },
  methods: {
    async fetchTransfers() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/inventory/transfer_list.php');
        if (res.data.status === 'success') {
          this.transfers = res.data.data;
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    createNew() {
      this.$router.push('/inventory-transfer-form');
    },
    viewTransfer(t) {
      this.$router.push(`/inventory-transfer-form?id=${t.id}`);
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('th-TH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
      });
    }
  }
}
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.group:hover td { background-color: #f8f9fa; }
</style>
