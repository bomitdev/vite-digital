<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
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
          <p class="text-muted small mb-0 mt-1">
            ผังขั้นตอนการดำเนินงานบริหารคลังพัสดุและเวชภัณฑ์ เรียงตามลำดับวงจรปฏิบัติงานจริง (INVS Standard Workflow)
          </p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <!-- Master Data Button (Admin Only) -->
          <button
            v-if="isAdmin"
            class="btn text-white rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2 hover-scale"
            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none;"
            @click="$router.push('/inventory-settings')"
            title="ตั้งค่าคลังวัสดุ, หมวดหมู่ และรายการวัสดุ"
          >
            <i class="bi bi-gear-fill"></i>
            <span>ตั้งค่าระบบ (Master Data)</span>
          </button>

          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm fw-medium">
            <i class="bi bi-house-door me-1"></i>หน้าหลัก
          </router-link>
        </div>
      </div>

      <!-- Workflow Pipeline Banner (Stepper according to INVS manual) -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden workflow-banner">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-bold">
                <i class="bi bi-diagram-3-fill me-1"></i> ผังขั้นตอนการปฏิบัติงานคลัง (Step-by-Step Workflow)
              </span>
              <span class="text-muted small d-none d-lg-inline">คลิกเลือกเมนูตามลำดับขั้นตอนเพื่อเข้าใช้งาน</span>
            </div>
            <span class="badge bg-light text-secondary border px-3 py-1 small rounded-pill">
              มาตรฐานโปรแกรมบริหารคลัง รพ.
            </span>
          </div>

          <!-- Horizontal Stepper -->
          <div class="workflow-stepper-container">
            <div class="workflow-steps">
              <!-- Step 1 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-pr')">
                <div class="step-num step-num-1">1</div>
                <div class="step-icon text-danger bg-danger-subtle">
                  <i class="bi bi-cart-plus-fill"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">ขอซื้อ / สั่งซื้อ</div>
                  <div class="step-sub">PR & PO</div>
                </div>
              </div>

              <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>

              <!-- Step 2 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-receive')">
                <div class="step-num step-num-2">2</div>
                <div class="step-icon text-success bg-success-subtle">
                  <i class="bi bi-box-arrow-in-down"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">รับเข้าคลัง</div>
                  <div class="step-sub">Receive (IN)</div>
                </div>
              </div>

              <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>

              <!-- Step 3 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-issue')">
                <div class="step-num step-num-3">3</div>
                <div class="step-icon text-warning bg-warning-subtle">
                  <i class="bi bi-box-arrow-up"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">เบิกจ่ายคลัง</div>
                  <div class="step-sub">Issue (OUT)</div>
                </div>
              </div>

              <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>

              <!-- Step 4 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-transfer')">
                <div class="step-num step-num-4">4</div>
                <div class="step-icon text-primary bg-primary-subtle">
                  <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">โอนย้ายวัสดุ</div>
                  <div class="step-sub">Transfer</div>
                </div>
              </div>

              <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>

              <!-- Step 5 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-return')">
                <div class="step-num step-num-5">5</div>
                <div class="step-icon text-indigo bg-indigo-subtle">
                  <i class="bi bi-arrow-return-left"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">คืนวัสดุเข้าคลัง</div>
                  <div class="step-sub">Return</div>
                </div>
              </div>

              <div class="step-arrow"><i class="bi bi-chevron-right"></i></div>

              <!-- Step 6 -->
              <div class="workflow-step-item" @click="$router.push('/inventory-stock-card')">
                <div class="step-num step-num-6">6</div>
                <div class="step-icon text-info bg-info-subtle">
                  <i class="bi bi-card-list"></i>
                </div>
                <div class="step-label">
                  <div class="step-title">สต็อกการ์ด</div>
                  <div class="step-sub">Stock Card</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Dashboard Grid (Ordered by Workflow Steps 1 to 6) -->
      <div class="row g-4">
        <!-- Step 1: PR/PO System -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-pr')">
            <div class="card-accent-line bg-danger"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 1 : สั่งซื้อ
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-file-earmark-text me-1"></i>PR & PO
                </span>
              </div>
              <div class="icon-circle bg-danger-light text-danger mb-3 mx-auto shadow-sm">
                <i class="bi bi-cart-plus-fill fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">1. ระบบขอซื้อ/สั่งซื้อ (PR & PO)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                วางแผนจัดซื้อ จัดทำใบขอซื้อ (PR) ออกใบสั่งซื้อ (PO) ส่งผู้จำหน่าย และตรวจสอบรายการค้างรับตามแผน
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-danger fw-bold small">
                <span>จัดการเอกสาร PR / PO</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Receive (IN) -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-receive')">
            <div class="card-accent-line bg-success"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 2 : ตรวจรับ
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-box-arrow-in-down me-1"></i>Inbound
                </span>
              </div>
              <div class="icon-circle bg-success-light text-success mb-3 mx-auto shadow-sm">
                <i class="bi bi-box-arrow-in-down fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">2. บันทึกรับเข้าคลัง (IN)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                ตรวจรับพัสดุจากผู้จำหน่าย/ขนส่ง ตรวจสอบตามใบสั่งซื้อ บันทึก Lot No., วันหมดอายุ, ราคา และรับเข้าสต็อก
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-success fw-bold small">
                <span>บันทึกรับเข้าคลังสินค้า</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Issue (OUT) -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-issue')">
            <div class="card-accent-line bg-warning"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 3 : เบิกจ่าย
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-box-arrow-up me-1"></i>Outbound
                </span>
              </div>
              <div class="icon-circle bg-warning-light text-warning mb-3 mx-auto shadow-sm">
                <i class="bi bi-box-arrow-up fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">3. เบิกจ่ายคลัง (OUT)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                ตรวจสอบคำขอเบิกจากหน่วยงาน ยืนยันการตัดจ่ายพัสดุออกจากคลังตามระบบ FEFO / FIFO คุมยอดสต็อกอัตโนมัติ
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-warning fw-bold small">
                <span>ตัดจ่ายพัสดุออกคลัง</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 4: Transfer -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-transfer')">
            <div class="card-accent-line bg-primary"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 4 : โอนย้าย
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-arrow-left-right me-1"></i>Transfer
                </span>
              </div>
              <div class="icon-circle bg-primary-light text-primary mb-3 mx-auto shadow-sm">
                <i class="bi bi-arrow-left-right fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">4. โอนย้ายวัสดุ (Transfer)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                โอนย้ายสต็อกระหว่างคลังหลัก (Stock) และคลังย่อย (Sub-Stock) หรือระหว่างหน่วยงานเพื่อจัดสมดุลสต็อก
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-primary fw-bold small">
                <span>บันทึกโอนย้ายระหว่างคลัง</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 5: Return -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-return')">
            <div class="card-accent-line bg-indigo"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 5 : รับคืน
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-arrow-return-left me-1"></i>Return
                </span>
              </div>
              <div class="icon-circle bg-indigo-light text-indigo mb-3 mx-auto shadow-sm">
                <i class="bi bi-arrow-return-left fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">5. คืนวัสดุเข้าคลัง (Return)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                รับคืนวัสดุเหลือใช้ ชำรุด เสื่อมสภาพ หรือส่งคืนคลังหลัก พร้อมบันทึกสาเหตุการคืนและปรับยอดคงคลัง
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-indigo fw-bold small">
                <span>บันทึกรับคืนของเข้าคลัง</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 6: Stock Card -->
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 h-100 cursor-pointer hover-scale position-relative overflow-hidden card-step" @click="$router.push('/inventory-stock-card')">
            <div class="card-accent-line bg-info"></div>
            <div class="card-body p-4 d-flex flex-column text-center">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1 fw-bold small">
                  ขั้นตอนที่ 6 : ตรวจสอบ
                </span>
                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                  <i class="bi bi-card-list me-1"></i>Audit & Ledger
                </span>
              </div>
              <div class="icon-circle bg-info-light text-info mb-3 mx-auto shadow-sm">
                <i class="bi bi-card-list fs-2"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">6. สต็อกการ์ด (Stock Card)</h5>
              <p class="text-muted small mb-4 flex-grow-1">
                บัญชีคุมยอดพัสดุ ตรวจสอบประวัติความเคลื่อนไหว รับ-จ่าย-คงเหลือ ยอดสต็อกแยกตาม Lot และรายงานสรุป
              </p>
              <div class="action-footer pt-3 border-top d-flex justify-content-between align-items-center text-info fw-bold small">
                <span>ตรวจสอบบัญชีสต็อกการ์ด</span>
                <i class="bi bi-arrow-right-circle-fill fs-5 transition-transform"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'InventoryDashboard',
  data() {
    return {
      isAdminUser: false
    };
  },
  computed: {
    isAdmin() {
      return this.isAdminUser;
    }
  },
  mounted() {
    this.checkAdminStatus();
  },
  methods: {
    async checkAdminStatus() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data && response.data.status === 'success') {
          const accessUser = response.data.access_user ? response.data.access_user.split(':') : [];
          this.isAdminUser =
            accessUser.includes('administrator') ||
            accessUser.includes('menu_inventory_manage') ||
            accessUser.includes('menu_inventory_admin') ||
            accessUser.includes('menu_it_material_manage') ||
            accessUser.includes('menu_gm_material_manage') ||
            localStorage.getItem('user_role') === 'admin' ||
            localStorage.getItem('is_admin') === 'true';
        } else {
          this.isAdminUser =
            localStorage.getItem('user_role') === 'admin' ||
            localStorage.getItem('is_admin') === 'true';
        }
      } catch (error) {
        console.error('Error checking admin status', error);
        this.isAdminUser =
          localStorage.getItem('user_role') === 'admin' ||
          localStorage.getItem('is_admin') === 'true';
      }
    }
  }
};
</script>

<style scoped>
.hover-scale {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.hover-scale:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.12), 0 4px 8px -2px rgba(0, 0, 0, 0.05) !important;
}
.hover-scale:hover .action-footer i {
  transform: translateX(4px);
}
.transition-transform {
  transition: transform 0.2s ease;
}
.cursor-pointer {
  cursor: pointer;
}

/* Accent top border on cards */
.card-step {
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
  background: #ffffff;
}
.card-accent-line {
  height: 4px;
  width: 100%;
}

/* Icon circles */
.icon-circle {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s ease;
}
.card-step:hover .icon-circle {
  transform: scale(1.08);
}

/* Colors */
.bg-purple-light { background-color: #f3e8ff; }
.text-purple { color: #8b5cf6; }
.bg-success-light { background-color: #dcfce7; }
.bg-warning-light { background-color: #fef3c7; }
.text-warning { color: #d97706; }
.bg-info-light { background-color: #e0f2fe; }
.text-info { color: #0284c7; }
.bg-danger-light { background-color: #ffe4e6; }
.text-danger { color: #e11d48; }
.bg-primary-light { background-color: #e0e7ff; }
.bg-indigo-light { background-color: #ede9fe; }
.text-indigo { color: #6366f1; }
.bg-indigo { background-color: #6366f1; }
.bg-indigo-subtle { background-color: #e0e7ff; color: #4338ca; }
.border-indigo-subtle { border-color: #c7d2fe !important; }

/* Workflow Stepper Banner */
.workflow-banner {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0 !important;
}
.workflow-stepper-container {
  overflow-x: auto;
  padding: 0.5rem 0;
}
.workflow-steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-width: 780px;
}
.workflow-step-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 14px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  flex: 1;
}
.workflow-step-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
  border-color: #cbd5e1;
}
.step-num {
  position: absolute;
  top: -8px;
  left: -6px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}
.step-num-1 { background-color: #e11d48; }
.step-num-2 { background-color: #10b981; }
.step-num-3 { background-color: #f59e0b; }
.step-num-4 { background-color: #3b82f6; }
.step-num-5 { background-color: #6366f1; }
.step-num-6 { background-color: #06b6d4; }

.step-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.step-label {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.step-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  white-space: nowrap;
}
.step-sub {
  font-size: 0.72rem;
  color: #64748b;
  white-space: nowrap;
}
.step-arrow {
  color: #cbd5e1;
  font-size: 1.1rem;
  padding: 0 6px;
  display: flex;
  align-items: center;
  flex-shrink: 0;
}
</style>
