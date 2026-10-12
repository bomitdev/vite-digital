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
              <li class="breadcrumb-item active" aria-current="page">คืนวัสดุเข้าคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-arrow-return-left me-2"></i>ระบบคืนวัสดุเข้าคลัง (Return)
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
          <h5 class="fw-bold mb-0">รายการรับคืนวัสดุ (Return In)</h5>
          <button class="btn btn-primary rounded-pill shadow-sm" @click="createNew">
            <i class="bi bi-plus-lg me-1"></i> บันทึกรับคืนวัสดุ
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted">
                <tr>
                  <th class="ps-4">เลขที่รับคืน</th>
                  <th>วันที่รับคืน</th>
                  <th>คืนเข้าคลัง (To)</th>
                  <th>หน่วยงานที่คืน (From)</th>
                  <th>สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="returns.length === 0">
                  <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    ยังไม่มีรายการรับคืน
                  </td>
                </tr>
                <tr v-for="r in returns" :key="r.id">
                  <td class="ps-4 fw-bold">{{ r.return_no }}</td>
                  <td>{{ r.return_date }}</td>
                  <td>{{ r.warehouse_name }}</td>
                  <td>{{ r.return_from }}</td>
                  <td><span class="badge bg-success">{{ r.status }}</span></td>
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
import Swal from 'sweetalert2';

export default {
  name: 'ReturnDashboard',
  data() {
    return {
      returns: []
    }
  },
  methods: {
    createNew() {
      Swal.fire('Coming Soon', 'หน้าจอบันทึกรับคืนกำลังพัฒนา...', 'info');
    }
  }
}
</script>
