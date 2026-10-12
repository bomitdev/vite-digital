<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><router-link to="/home-backoffice">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">ระบบบริหารคลังวัสดุ (Inventory System)</li>
            </ol>
          </nav>
          <div class="d-flex align-items-center gap-3">
            <h2 class="fw-bold text-primary mb-0">
              <i class="bi bi-box-seam me-2"></i>ระบบบริหารคลังวัสดุรวมศูนย์
            </h2>
          </div>
        </div>
      </div>

      <!-- Main Dashboard Content -->
      <div class="row g-4 mt-2">
        <!-- Master Data -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-settings')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-purple-light text-purple mb-3 mx-auto">
                <i class="bi bi-gear-fill fs-2"></i>
              </div>
              <h5 class="fw-bold">ตั้งค่าระบบ (Master Data)</h5>
              <p class="text-muted small mb-0">จัดการคลังวัสดุ, หมวดหมู่ และรายการวัสดุ</p>
            </div>
          </div>
        </div>

        <!-- Receive -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-receive')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-success-light text-success mb-3 mx-auto">
                <i class="bi bi-box-arrow-in-down fs-2"></i>
              </div>
              <h5 class="fw-bold">บันทึกรับเข้าคลัง (IN)</h5>
              <p class="text-muted small mb-0">บันทึกการรับเข้าวัสดุเพื่ออัปเดตสต๊อก</p>
            </div>
          </div>
        </div>

        <!-- Issue -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-issue')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-warning-light text-warning mb-3 mx-auto">
                <i class="bi bi-box-arrow-up fs-2"></i>
              </div>
              <h5 class="fw-bold">เบิกจ่ายคลัง (OUT)</h5>
              <p class="text-muted small mb-0">ระบบตัดจ่ายออกแบบ FEFO</p>
            </div>
          </div>
        </div>

        <!-- Stock Card -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-stock-card')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-info-light text-info mb-3 mx-auto">
                <i class="bi bi-card-list fs-2"></i>
              </div>
              <h5 class="fw-bold">สต๊อกการ์ด (Stock Card)</h5>
              <p class="text-muted small mb-0">ตรวจสอบยอดคงเหลือและประวัติ</p>
            </div>
          </div>
        </div>

        <!-- PR/PO System -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-pr')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-danger-light text-danger mb-3 mx-auto">
                <i class="bi bi-cart-plus-fill fs-2"></i>
              </div>
              <h5 class="fw-bold">ระบบขอซื้อ/สั่งซื้อ</h5>
              <p class="text-muted small mb-0">จัดการเอกสาร PR และ PO</p>
            </div>
          </div>
        </div>

        <!-- Transfer -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-transfer')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-secondary text-white mb-3 mx-auto">
                <i class="bi bi-arrow-left-right fs-2"></i>
              </div>
              <h5 class="fw-bold">โอนย้ายวัสดุ</h5>
              <p class="text-muted small mb-0">ย้ายสต็อกระหว่างคลัง</p>
            </div>
          </div>
        </div>

        <!-- Return -->
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale" @click="$router.push('/inventory-return')">
            <div class="card-body p-4 text-center">
              <div class="icon-circle bg-dark text-white mb-3 mx-auto">
                <i class="bi bi-arrow-return-left fs-2"></i>
              </div>
              <h5 class="fw-bold">คืนวัสดุเข้าคลัง</h5>
              <p class="text-muted small mb-0">รับคืนของชำรุด/ใช้ไม่หมด</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'InventoryDashboard'
}
</script>

<style scoped>
.hover-scale {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-scale:hover {
  transform: translateY(-5px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.cursor-pointer {
  cursor: pointer;
}
.icon-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-purple-light { background-color: #f3e8ff; }
.text-purple { color: #8b5cf6; }
.bg-success-light { background-color: #dcfce7; }
.bg-warning-light { background-color: #fef3c7; }
.text-warning { color: #f59e0b; }
.bg-info-light { background-color: #e0f2fe; }
.text-info { color: #0ea5e9; }
.bg-danger-light { background-color: #ffe4e6; }
.text-danger { color: #e11d48; }


</style>
