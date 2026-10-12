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
              <li class="breadcrumb-item active" aria-current="page">ขอซื้อ (PR)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-cart-plus-fill me-2"></i>ระบบขอซื้อ/สั่งซื้อ (PR & PO)
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
          <button class="btn btn-primary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-grid-fill me-2"></i>Dashboard
          </button>
        </div>
      </div>

      <!-- Navigation Tabs PR / PO -->
      <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
          <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" type="button">
            <i class="bi bi-file-earmark-text me-1"></i> ใบขอซื้อ (PR)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-pill px-4 text-dark bg-white shadow-sm border" type="button" @click="$router.push('/inventory-po')">
            <i class="bi bi-bag-check me-1"></i> ใบสั่งซื้อ (PO)
          </button>
        </li>
      </ul>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">รายการใบขอซื้อ (Purchase Requests)</h5>
          <button class="btn btn-danger rounded-pill shadow-sm" @click="$router.push('/inventory-pr-form')">
            <i class="bi bi-plus-lg me-1"></i> สร้างใบขอซื้อใหม่
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted">
                <tr>
                  <th class="ps-4">เลขที่เอกสาร</th>
                  <th>วันที่ขอซื้อ</th>
                  <th>คลังที่ร้องขอ</th>
                  <th>ผู้ขอซื้อ</th>
                  <th>จำนวนรายการ</th>
                  <th>สถานะ (Status)</th>
                  <th class="text-end pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                  </td>
                </tr>
                <tr v-else-if="prs.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    ยังไม่มีรายการใบขอซื้อ
                  </td>
                </tr>
                <tr v-for="pr in prs" :key="pr.id" class="cursor-pointer group">
                  <td class="ps-4 fw-bold text-primary">{{ pr.pr_number }}</td>
                  <td>{{ formatDate(pr.request_date) }}</td>
                  <td>{{ pr.warehouse_name }}</td>
                  <td>{{ pr.requester_name }}</td>
                  <td>{{ pr.item_count }} รายการ</td>
                  <td>
                    <span class="badge" :class="getStatusBadgeClass(pr.status)">
                      {{ getStatusText(pr.status) }}
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" @click="viewPR(pr)">
                      <i class="bi bi-search"></i> ตรวจสอบ
                    </button>
                  </td>
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
  name: 'PRDashboard',
  data() {
    return {
      prs: [],
      loading: true
    }
  },
  mounted() {
    this.fetchPRs();
  },
  methods: {
    async fetchPRs() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/inventory/pr_list.php');
        if (response.data.status === 'success') {
          this.prs = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'ไม่สามารถโหลดข้อมูล PR ได้', 'error');
        }
      } catch (error) {
        console.error('Error fetching PRs:', error);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      } finally {
        this.loading = false;
      }
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('th-TH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
      });
    },
    getStatusBadgeClass(status) {
      switch (status) {
        case 'PENDING': return 'bg-warning text-dark';
        case 'HEAD_APPROVED': return 'bg-info text-dark';
        case 'DIR_APPROVED': return 'bg-success';
        case 'HEAD_REJECTED': 
        case 'DIR_REJECTED': return 'bg-danger';
        default: return 'bg-secondary';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'PENDING': return 'รอหัวหน้าอนุมัติ';
        case 'HEAD_APPROVED': return 'รอ ผอ. อนุมัติ';
        case 'DIR_APPROVED': return 'อนุมัติแล้ว (รอเปิด PO)';
        case 'HEAD_REJECTED': return 'หัวหน้าไม่อนุมัติ';
        case 'DIR_REJECTED': return 'ผอ. ไม่อนุมัติ';
        default: return status;
      }
    },
    viewPR(pr) {
      this.$router.push(`/inventory-pr-form?id=${pr.id}`);
    }
  }
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.group:hover td {
  background-color: #f8f9fa;
}
</style>
