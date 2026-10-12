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
              <li class="breadcrumb-item active" aria-current="page">สั่งซื้อ (PO)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-cart-plus-fill me-2"></i>ระบบขอซื้อ/สั่งซื้อ (PR & PO)
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
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
          <button class="nav-link rounded-pill px-4 text-dark bg-white shadow-sm border" type="button" @click="$router.push('/inventory-pr')">
            <i class="bi bi-file-earmark-text me-1"></i> ใบขอซื้อ (PR)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" type="button">
            <i class="bi bi-bag-check me-1"></i> ใบสั่งซื้อ (PO)
          </button>
        </li>
      </ul>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">รายการใบสั่งซื้อ (Purchase Orders)</h5>
          <button class="btn btn-primary rounded-pill shadow-sm" @click="$router.push('/inventory-po-form')">
            <i class="bi bi-plus-lg me-1"></i> เปิดใบสั่งซื้อใหม่
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted">
                <tr>
                  <th class="ps-4">เลขที่เอกสาร PO</th>
                  <th>อ้างอิง PR</th>
                  <th>วันที่สั่งซื้อ</th>
                  <th>ผู้จัดซื้อ</th>
                  <th>จำนวนรายการ</th>
                  <th>สถานะ (Status)</th>
                  <th class="text-end pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                  </td>
                </tr>
                <tr v-else-if="pos.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    ยังไม่มีรายการใบสั่งซื้อ
                  </td>
                </tr>
                <tr v-for="po in pos" :key="po.id" class="cursor-pointer group">
                  <td class="ps-4 fw-bold text-primary">{{ po.po_number }}</td>
                  <td>{{ po.pr_number }}</td>
                  <td>{{ formatDate(po.po_date) }}</td>
                  <td>{{ po.purchaser_name }}</td>
                  <td>{{ po.item_count }} รายการ</td>
                  <td>
                    <span class="badge" :class="getStatusBadgeClass(po.status)">
                      {{ getStatusText(po.status) }}
                    </span>
                  </td>
                  <td class="text-end pe-4">
                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" @click="viewPO(po)">
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
  name: 'PODashboard',
  data() {
    return {
      pos: [],
      loading: true
    }
  },
  mounted() {
    this.fetchPOs();
  },
  methods: {
    async fetchPOs() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/inventory/po_list.php');
        if (response.data.status === 'success') {
          this.pos = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'ไม่สามารถโหลดข้อมูล PO ได้', 'error');
        }
      } catch (error) {
        console.error('Error fetching POs:', error);
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
        case 'ORDERED': return 'bg-primary';
        case 'PARTIAL_RECEIVED': return 'bg-warning text-dark';
        case 'COMPLETED': return 'bg-success';
        case 'CANCELLED': return 'bg-danger';
        default: return 'bg-secondary';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'ORDERED': return 'สั่งซื้อแล้ว (รอรับของ)';
        case 'PARTIAL_RECEIVED': return 'รับของบางส่วน';
        case 'COMPLETED': return 'รับของครบถ้วน';
        case 'CANCELLED': return 'ยกเลิก';
        default: return status;
      }
    },
    viewPO(po) {
      this.$router.push(`/inventory-po-form?id=${po.id}`);
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
