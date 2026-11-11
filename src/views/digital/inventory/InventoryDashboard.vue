<template>
  <div class="inventory-layout min-vh-100 d-flex bg-light">
    <!-- Left Sidebar -->
    <aside
      class="inventory-sidebar bg-white border-end shadow-sm d-flex flex-column"
      :class="{ 'sidebar-collapsed': isCollapsed, 'sidebar-mobile-open': mobileOpen }"
    >
      <!-- Sidebar Header -->
      <div class="sidebar-header p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 overflow-hidden" v-show="!isCollapsed">
          <div class="sidebar-brand-icon">
            <i class="bi bi-box-seam-fill"></i>
          </div>
          <div class="sidebar-brand-text text-truncate">
            <div class="fw-bold text-dark lh-1">คลังพัสดุและเวชภัณฑ์</div>
            <small class="text-muted" style="font-size: 0.72rem;">Inventory Management System</small>
          </div>
        </div>
        <div v-show="isCollapsed" class="sidebar-brand-icon mx-auto" title="ระบบบริหารคลังพัสดุ">
          <i class="bi bi-box-seam-fill"></i>
        </div>

        <!-- Collapse toggle button (desktop) -->
        <button
          class="btn btn-sm btn-light text-secondary rounded-circle d-none d-lg-flex align-items-center justify-content-center toggle-btn"
          @click="toggleCollapse"
          :title="isCollapsed ? 'ขยายแถบเมนู' : 'ย่อแถบเมนู'"
        >
          <i class="bi" :class="isCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
        </button>

        <!-- Close button (mobile) -->
        <button
          class="btn btn-sm btn-light text-secondary rounded-circle d-lg-none"
          @click="mobileOpen = false"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- Sidebar Navigation Menu -->
      <div class="sidebar-menu flex-grow-1 overflow-y-auto py-3 px-2">
        <!-- Section: Overview -->
        <div class="menu-section-label" v-show="!isCollapsed">ภาพรวมระบบ</div>
        <div
          class="menu-item"
          :class="{ active: currentTab === 'overview' }"
          @click="selectTab('overview')"
          :title="isCollapsed ? 'ผังขั้นตอนและภาพรวม' : ''"
        >
          <div class="menu-icon bg-primary-subtle text-primary">
            <i class="bi bi-grid-1x2-fill"></i>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="fw-bold">Dashboard Inventory</div>
            <small class="text-muted">ระบบบริหารคลังพัสดุ</small>
          </div>
        </div>

        <!-- Section: 6 Steps Workflow -->
        <div class="menu-section-label mt-3" v-show="!isCollapsed">ขั้นตอนตามวงจรคลังพัสดุ</div>

        <!-- Step 1: ขอซื้อ / สั่งซื้อ (PR & PO) -->
        <div
          class="menu-item"
          :class="{ active: ['pr', 'pr_form', 'po', 'po_form'].includes(currentTab) }"
          @click="toggleGroup('pr_po')"
          :title="isCollapsed ? '1. ขอซื้อ / สั่งซื้อ (PR & PO)' : ''"
        >
          <div class="menu-icon bg-danger-subtle text-danger position-relative">
            <i class="bi bi-cart-plus-fill"></i>
            <span class="mini-step-badge bg-danger">1</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">1. ขอซื้อ / สั่งซื้อ</span>
              <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-0 small">PR/PO</span>
            </div>
            <small class="text-muted">วางแผนและสั่งซื้อ</small>
          </div>
          <i
            v-show="!isCollapsed"
            class="bi ms-auto text-muted small"
            :class="isGroupOpen('pr_po') ? 'bi-chevron-down' : 'bi-chevron-right'"
          ></i>
        </div>

        <!-- Submenu for Step 1 -->
        <div v-show="isGroupOpen('pr_po')" class="submenu-container ps-3 pe-1 py-1">
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'pr' }"
            @click.stop="selectTab('pr')"
          >
            <i class="bi bi-file-earmark-text me-2 text-danger"></i>
            <span>รายการขอซื้อ (PR)</span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'pr_form' }"
            @click.stop="selectTab('pr_form')"
          >
            <i class="bi bi-plus-circle me-2 text-danger"></i>
            <span>สร้างใบขอซื้อใหม่</span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'po' }"
            @click.stop="selectTab('po')"
          >
            <i class="bi bi-bag-check me-2 text-primary"></i>
            <span>รายการสั่งซื้อ (PO)</span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'po_form' }"
            @click.stop="selectTab('po_form')"
          >
            <i class="bi bi-cart-check me-2 text-primary"></i>
            <span>จัดทำใบสั่งซื้อ (PO Form)</span>
          </div>
        </div>

        <!-- Step 2: บันทึกรับเข้าคลัง (Receive IN) -->
        <div
          class="menu-item"
          :class="{ active: currentTab === 'receive' }"
          @click="selectTab('receive')"
          :title="isCollapsed ? '2. บันทึกรับเข้าคลัง (IN)' : ''"
        >
          <div class="menu-icon bg-success-subtle text-success position-relative">
            <i class="bi bi-box-arrow-in-down"></i>
            <span class="mini-step-badge bg-success">2</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">2. ตรวจรับเข้าคลัง</span>
              <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0 small">IN</span>
            </div>
            <small class="text-muted">บันทึกรับพัสดุเข้าสต็อก</small>
          </div>
        </div>

        <!-- Step 3: เบิกจ่ายคลัง (Requisitions & Issue) -->
        <div
          class="menu-item"
          :class="{ active: ['requisitions', 'requisition_form', 'issue'].includes(currentTab) }"
          @click="toggleGroup('issue_group')"
          :title="isCollapsed ? '3. เบิกจ่ายคลัง (OUT)' : ''"
        >
          <div class="menu-icon bg-warning-subtle text-warning-emphasis position-relative">
            <i class="bi bi-box-arrow-up"></i>
            <span class="mini-step-badge bg-warning text-dark">3</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">3. เบิกจ่ายคลัง</span>
              <div class="d-flex align-items-center gap-1">
                <span v-if="pendingRequisitionsCount > 0" class="badge bg-danger rounded-pill px-2 py-0 small animate__animated animate__pulse animate__infinite">
                  {{ pendingRequisitionsCount }}
                </span>
                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-0 small">OUT</span>
              </div>
            </div>
            <small class="text-muted">ขอเบิก, อนุมัติ, ตัดจ่ายคลัง</small>
          </div>
          <i
            v-show="!isCollapsed"
            class="bi ms-auto text-muted small"
            :class="isGroupOpen('issue_group') ? 'bi-chevron-down' : 'bi-chevron-right'"
          ></i>
        </div>

        <!-- Submenu for Step 3 -->
        <div v-show="isGroupOpen('issue_group')" class="submenu-container ps-3 pe-1 py-1">
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'requisitions' }"
            @click.stop="selectTab('requisitions')"
          >
            <i class="bi bi-clipboard2-check me-2 text-warning-emphasis"></i>
            <span class="d-flex align-items-center justify-content-between flex-grow-1">
              คำขอเบิก & อนุมัติ
              <span v-if="pendingRequisitionsCount > 0" class="badge bg-danger rounded-pill px-2 py-0 small ms-1">
                {{ pendingRequisitionsCount }}
              </span>
            </span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'requisition_form' }"
            @click.stop="selectTab('requisition_form')"
          >
            <i class="bi bi-plus-circle me-2 text-warning-emphasis"></i>
            <span>สร้างใบขอเบิกใหม่</span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'issue' }"
            @click.stop="selectTab('issue')"
          >
            <i class="bi bi-box-arrow-up me-2 text-danger"></i>
            <span>บันทึกตัดจ่ายตรง (FEFO)</span>
          </div>
        </div>

        <!-- Step 4: โอนย้ายวัสดุ (Transfer) -->
        <div
          class="menu-item"
          :class="{ active: ['transfer', 'transfer_form'].includes(currentTab) }"
          @click="toggleGroup('transfer')"
          :title="isCollapsed ? '4. โอนย้ายวัสดุ (Transfer)' : ''"
        >
          <div class="menu-icon bg-primary-subtle text-primary position-relative">
            <i class="bi bi-arrow-left-right"></i>
            <span class="mini-step-badge bg-primary">4</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">4. โอนย้ายวัสดุ</span>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-0 small">Transfer</span>
            </div>
            <small class="text-muted">โอนระหว่างคลังหลัก/ย่อย</small>
          </div>
          <i
            v-show="!isCollapsed"
            class="bi ms-auto text-muted small"
            :class="isGroupOpen('transfer') ? 'bi-chevron-down' : 'bi-chevron-right'"
          ></i>
        </div>

        <!-- Submenu for Step 4 -->
        <div v-show="isGroupOpen('transfer')" class="submenu-container ps-3 pe-1 py-1">
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'transfer' }"
            @click.stop="selectTab('transfer')"
          >
            <i class="bi bi-list-ul me-2 text-primary"></i>
            <span>รายการใบโอนย้าย</span>
          </div>
          <div
            class="submenu-item"
            :class="{ active: currentTab === 'transfer_form' }"
            @click.stop="selectTab('transfer_form')"
          >
            <i class="bi bi-plus-circle me-2 text-primary"></i>
            <span>บันทึกใบโอนย้ายใหม่</span>
          </div>
        </div>

        <!-- Step 5: คืนวัสดุเข้าคลัง (Return) -->
        <div
          class="menu-item"
          :class="{ active: currentTab === 'return' }"
          @click="selectTab('return')"
          :title="isCollapsed ? '5. คืนวัสดุเข้าคลัง (Return)' : ''"
        >
          <div class="menu-icon bg-indigo-subtle text-indigo position-relative">
            <i class="bi bi-arrow-return-left"></i>
            <span class="mini-step-badge bg-indigo">5</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">5. คืนวัสดุเข้าคลัง</span>
              <span class="badge bg-indigo-subtle text-indigo rounded-pill px-2 py-0 small">Return</span>
            </div>
            <small class="text-muted">รับคืนของเหลือใช้/ชำรุด</small>
          </div>
        </div>

        <!-- Step 6: สต็อกการ์ด (Stock Card) -->
        <div
          class="menu-item"
          :class="{ active: currentTab === 'stock_card' }"
          @click="selectTab('stock_card')"
          :title="isCollapsed ? '6. สต็อกการ์ด (Stock Card)' : ''"
        >
          <div class="menu-icon bg-info-subtle text-info position-relative">
            <i class="bi bi-card-list"></i>
            <span class="mini-step-badge bg-info">6</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">6. สต็อกการ์ด</span>
              <span class="badge bg-info-subtle text-info rounded-pill px-2 py-0 small">Stock Card</span>
            </div>
            <small class="text-muted">คุมยอดสต็อก / ความเคลื่อนไหว</small>
          </div>
        </div>

        <!-- Step 7: รายงาน (Reports) -->
        <div
          class="menu-item"
          :class="{ active: currentTab === 'reports' }"
          @click="selectTab('reports')"
          :title="isCollapsed ? '7. รายงาน (Reports)' : ''"
        >
          <div class="menu-icon bg-secondary-subtle text-secondary position-relative">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>
            <span class="mini-step-badge bg-secondary">7</span>
          </div>
          <div class="menu-text" v-show="!isCollapsed">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold">7. รายงาน</span>
              <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0 small">Reports</span>
            </div>
            <small class="text-muted">สรุปยอด / รับ-จ่าย / เคลื่อนไหว</small>
          </div>
        </div>

        <!-- Section: Settings (Master Data) -->
        <template v-if="canManageSettings">
          <div class="menu-section-label mt-3" v-show="!isCollapsed">การจัดการระบบ</div>
          <div
            class="menu-item"
            :class="{ active: currentTab === 'settings' }"
            @click="selectTab('settings')"
            :title="isCollapsed ? 'ตั้งค่าระบบ (Master Data)' : ''"
          >
            <div class="menu-icon bg-purple-light text-purple">
              <i class="bi bi-gear-fill"></i>
            </div>
            <div class="menu-text" v-show="!isCollapsed">
              <div class="d-flex align-items-center justify-content-between">
                <span class="fw-bold">ตั้งค่าระบบ</span>
                <span class="badge text-white rounded-pill px-2 py-0 small" style="background: #7c3aed;">Master</span>
              </div>
              <small class="text-muted">คลัง, หมวดหมู่, หน่วยนับ, วิธีจัดซื้อ, พัสดุ</small>
            </div>
          </div>
        </template>
      </div>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer p-2 border-top bg-light-subtle d-flex flex-column gap-1">
        <router-link
          to="/home-backoffice"
          class="btn btn-sm btn-outline-secondary rounded-pill d-flex align-items-center justify-content-center gap-2 py-2"
          :title="isCollapsed ? 'กลับหน้าหลัก Backoffice' : ''"
        >
          <i class="bi bi-house-door-fill"></i>
          <span v-show="!isCollapsed">หน้าหลัก Backoffice</span>
        </router-link>
      </div>
    </aside>

    <!-- Mobile Backdrop -->
    <div
      v-if="mobileOpen"
      class="sidebar-backdrop d-lg-none"
      @click="mobileOpen = false"
    ></div>

    <!-- Main Content Area -->
    <main class="inventory-main-content flex-grow-1 overflow-x-hidden d-flex flex-column">
      <!-- Mobile Top Navbar -->
      <div class="d-lg-none bg-white p-3 border-bottom d-flex align-items-center justify-content-between shadow-sm sticky-top z-3">
        <button class="btn btn-outline-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" @click="mobileOpen = true">
          <i class="bi bi-list fs-4"></i>
        </button>
        <div class="fw-bold text-primary d-flex align-items-center gap-2 overflow-hidden px-2">
          <i class="bi bi-box-seam-fill flex-shrink-0"></i>
          <span class="text-truncate" style="max-width: 180px;">{{ activeWarehouseDisplay }}</span>
        </div>
        <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 d-flex align-items-center gap-1" @click="openWarehouseModal" title="สลับคลัง">
          <i class="bi bi-arrow-left-right"></i>
          <span style="font-size: 0.75rem;">สลับ</span>
        </button>
      </div>

      <!-- Desktop Sticky Warehouse Context Banner / Header -->
      <div class="bg-white border-bottom px-4 py-2 d-none d-lg-flex align-items-center justify-content-between sticky-top z-2 shadow-xs">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 p-2 bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-xs" style="width: 42px; height: 42px;">
            <span v-if="activeWarehouseObj" class="fs-5">{{ activeWarehouseObj.code }}</span>
            <i v-else class="bi bi-grid-fill fs-5"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold text-dark fs-6">{{ activeWarehouseDisplay }}</span>
              <span v-if="activeWarehouseObj" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                <i class="bi bi-check2-circle me-1"></i>คลังที่กำลังทำงาน
              </span>
              <span v-else class="badge bg-primary text-white rounded-pill small">Admin View (ทุกคลัง)</span>
            </div>
            <small class="text-muted" style="font-size: 0.75rem;">
              {{ activeWarehouseObj ? 'ระบบแยกข้อมูลเฉพาะคลังที่เลือกเพื่อความถูกต้องในการทำงาน' : 'โหมดภาพรวมสต็อกและเอกสารของทุกคลังในระบบ' }}
            </small>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button
            class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold d-flex align-items-center gap-2 shadow-xs hover-scale"
            @click="openWarehouseModal"
            title="คลิกเพื่อสลับไปยังคลังอื่น"
          >
            <i class="bi bi-arrow-left-right"></i>
            <span>สลับคลัง (Switch Warehouse)</span>
          </button>
          <button
            v-if="canManageSettings"
            class="btn btn-sm text-white rounded-pill px-3 fw-bold d-flex align-items-center gap-1 shadow-xs"
            :class="{ 'opacity-75': currentTab === 'settings' }"
            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none;"
            @click="selectTab('settings')"
            title="ตั้งค่าคลัง, หมวดหมู่, พัสดุ, บริษัท"
          >
            <i class="bi bi-gear-fill"></i>
            <span>ตั้งค่าระบบ</span>
          </button>
          <router-link to="/home-backoffice" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium">
            <i class="bi bi-house-door me-1"></i>Backoffice
          </router-link>
        </div>
      </div>

      <!-- Content Container -->
      <div class="content-wrapper flex-grow-1">
        <!-- 1. Tab: Overview (Summary Cards, Charts, Low Stock Table & Workflow Steps) -->
        <div v-if="currentTab === 'overview'" class="tab-pane-content py-4 px-3 px-md-4">
          <!-- Page Header & Warehouse Filter -->
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
                  <i class="bi bi-box-seam me-2"></i>ระบบบริหารคลังพัสดุและเวชภัณฑ์รวมศูนย์
                </h2>
              </div>
              <p class="text-muted small mb-0 mt-1">
                ภาพรวมสถานะคงคลัง ยอดรับเข้า-จ่ายออก กราฟสถิติ และสินค้าถึงจุดสั่งซื้อ (Unified Inventory Dashboard)
              </p>
            </div>
            
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <!-- Warehouse Selector Button & Indicator -->
              <div class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border">
                <i class="bi bi-building text-primary"></i>
                <span class="fw-bold text-dark">{{ activeWarehouseDisplay }}</span>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 ms-1 fw-bold" @click="openWarehouseModal" style="font-size: 0.75rem;">
                  <i class="bi bi-arrow-left-right me-1"></i>สลับคลัง
                </button>
              </div>

              <!-- Master Data Button (Admin or Warehouse Manager) -->
              <button
                v-if="canManageSettings"
                class="btn text-white rounded-pill px-3 shadow-sm fw-bold d-flex align-items-center gap-2 hover-scale"
                style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none;"
                @click="selectTab('settings')"
                title="ตั้งค่าคลังวัสดุ, หมวดหมู่ และรายการวัสดุ (เฉพาะผู้ดูแลระบบและผู้ดูแลคลัง)"
              >
                <i class="bi bi-gear-fill"></i>
                <span>ตั้งค่าระบบ</span>
              </button>

              <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm fw-medium">
                <i class="bi bi-house-door me-1"></i>หน้าหลัก
              </router-link>
            </div>
          </div>

          <!-- Section 1: Top 4 KPI Metric Cards -->
          <div class="row g-3 mb-4">
            <!-- 1.1 ยอดรวมมูลค่า (Total Inventory Value) -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden stat-card stat-card-green">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="stat-badge text-success bg-success-subtle">
                      <i class="bi bi-cash-stack me-1"></i> ยอดรวมมูลค่า
                    </span>
                    <div class="stat-icon-circle bg-success text-white">
                      <i class="bi bi-currency-dollar fs-5"></i>
                    </div>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatCurrency(metrics.total_value) }}</h3>
                  <div class="text-muted small">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    มูลค่าสต็อกคงเหลือปัจจุบัน
                  </div>
                </div>
              </div>
            </div>

            <!-- 1.2 ยอดคงเหลือ (Total Balance Stock) -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden stat-card stat-card-blue">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="stat-badge text-primary bg-primary-subtle">
                      <i class="bi bi-box-seam me-1"></i> ยอดคงเหลือ
                    </span>
                    <div class="stat-icon-circle bg-primary text-white">
                      <i class="bi bi-boxes fs-5"></i>
                    </div>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatNumber(metrics.total_balance_qty) }} <span class="fs-6 fw-normal text-muted">ชิ้น</span></h3>
                  <div class="text-muted small">
                    <i class="bi bi-tag-fill text-primary me-1"></i>
                    มีสินค้าพร้อมเบิก {{ metrics.active_items_count || 0 }} / {{ metrics.total_items_count || 0 }} รายการ
                  </div>
                </div>
              </div>
            </div>

            <!-- 1.3 รับเข้า (Total Inbound) -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden stat-card stat-card-teal cursor-pointer" @click="selectTab('receive')">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="stat-badge text-teal bg-teal-subtle">
                      <i class="bi bi-box-arrow-in-down me-1"></i> รับเข้าคลัง (IN)
                    </span>
                    <div class="stat-icon-circle bg-teal text-white">
                      <i class="bi bi-box-arrow-in-down fs-5"></i>
                    </div>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatNumber(metrics.total_in_qty) }} <span class="fs-6 fw-normal text-muted">ชิ้น</span></h3>
                  <div class="text-muted small d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt text-teal me-1"></i> บันทึกรับเข้า {{ metrics.in_tx_count || 0 }} ครั้ง</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- 1.4 จ่ายออก (Total Outbound) -->
            <div class="col-12 col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden stat-card stat-card-amber cursor-pointer" @click="selectTab('requisitions')">
                <div class="card-body p-4 position-relative">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="stat-badge text-warning-emphasis bg-warning-subtle">
                      <i class="bi bi-box-arrow-up me-1"></i> จ่ายออกคลัง (OUT)
                    </span>
                    <div class="stat-icon-circle bg-warning text-white">
                      <i class="bi bi-box-arrow-up fs-5"></i>
                    </div>
                  </div>
                  <h3 class="fw-bold mb-1 text-dark">{{ formatNumber(metrics.total_out_qty) }} <span class="fs-6 fw-normal text-muted">ชิ้น</span></h3>
                  <div class="text-muted small d-flex justify-content-between align-items-center">
                    <span v-if="pendingRequisitionsCount > 0" class="text-danger fw-bold">
                      <i class="bi bi-hourglass-split me-1"></i> รออนุมัติ {{ pendingRequisitionsCount }} รายการ
                    </span>
                    <span v-else>
                      <i class="bi bi-send-check text-warning me-1"></i> ตัดจ่ายพัสดุ {{ metrics.out_tx_count || 0 }} ครั้ง
                    </span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Alert: Pending Requisitions Notification Banner -->
          <div v-if="pendingRequisitionsCount > 0" class="alert alert-warning border-0 shadow-sm rounded-4 d-flex flex-wrap align-items-center justify-content-between p-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-warning text-dark p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-bell-fill fs-5"></i>
              </div>
              <div>
                <strong class="fs-6 text-dark d-block">มีคำขอเบิกพัสดุรอการพิจารณาอนุมัติ {{ pendingRequisitionsCount }} รายการ</strong>
                <span class="small text-muted">หน่วยงานได้ส่งคำขอเบิกพัสดุเข้ามาในคลังนี้ กรุณาตรวจสอบสต๊อกและพิจารณาอนุมัติจ่าย</span>
              </div>
            </div>
            <button class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" @click="selectTab('requisitions')">
              <i class="bi bi-clipboard2-check me-1"></i> ตรวจสอบและอนุมัติจ่าย
            </button>
          </div>

          <!-- Section 2: Charts Row (กราฟแสดงสินค้า) -->
          <div class="row g-4 mb-4">
            <!-- 2.1 Monthly Movement Bar Chart -->
            <div class="col-12 col-xl-8">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div>
                    <h5 class="fw-bold mb-0 text-dark">
                      <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>กราฟสรุปการเคลื่อนไหว รับเข้า - จ่ายออก
                    </h5>
                    <small class="text-muted">เปรียบเทียบยอด รับเข้าคลัง (IN) และ ตัดจ่ายออก (OUT) รายเดือน</small>
                  </div>
                  <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
                    <i class="bi bi-calendar3 me-1"></i> ย้อนหลัง 12 เดือน
                  </span>
                </div>
                <div class="card-body p-4">
                  <div style="height: 300px; position: relative;">
                    <canvas ref="movementChartRef"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2.2 Category Distribution Doughnut Chart -->
            <div class="col-12 col-xl-4">
              <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                  <div>
                    <h5 class="fw-bold mb-0 text-dark">
                      <i class="bi bi-pie-chart-fill text-success me-2"></i>สัดส่วนสินค้าตามหมวดหมู่
                    </h5>
                    <small class="text-muted">จำนวนสินค้าคงเหลือแยกตามประเภท</small>
                  </div>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                  <div style="height: 270px; position: relative;">
                    <canvas ref="categoryChartRef"></canvas>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: ยอดสินค้าต่ำสุด (Low Stock Items / Safety Stock Alert Table) -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <div class="alert-icon-square bg-danger-subtle text-danger">
                  <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-dark">ยอดสินค้าต่ำสุด / ถึงจุดสั่งซื้อ (Low Stock Alert)</h5>
                    <span class="badge bg-danger rounded-pill px-2 py-1 small">
                      {{ lowStockItems.length }} รายการ
                    </span>
                  </div>
                  <small class="text-muted">รายการพัสดุที่คงเหลือต่ำกว่าเกณฑ์ขั้นต่ำหรือหมดสต็อก ควรรีบเปิดใบขอซื้อ (PR)</small>
                </div>
              </div>

              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" @click="selectTab('pr_form')">
                  <i class="bi bi-plus-lg me-1"></i>สร้างใบขอซื้อ (PR)
                </button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" @click="selectTab('po_form')">
                  <i class="bi bi-cart-check me-1"></i>สั่งซื้อเวชภัณฑ์ (PO)
                </button>
              </div>
            </div>

            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light text-muted small">
                    <tr>
                      <th class="ps-4" width="5%">#</th>
                      <th width="15%">รหัสพัสดุ</th>
                      <th width="25%">ชื่อรายการพัสดุ</th>
                      <th width="12%">หมวดหมู่</th>
                      <th class="text-center" width="10%">คงเหลือ (On-Hand)</th>
                      <th class="text-center" width="10%">เกณฑ์ขั้นต่ำ (Min)</th>
                      <th class="text-center" width="11%">จำนวนควรสั่ง</th>
                      <th class="text-center" width="12%">สถานะสต็อก</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="lowStockItems.length === 0">
                      <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                        ยอดสต็อกพัสดุทุกรายการอยู่ในเกณฑ์ปกติ ไม่มีสินค้าที่ต้องสั่งซื้อด่วน
                      </td>
                    </tr>
                    <tr v-for="(item, idx) in lowStockItems" :key="item.id">
                      <td class="ps-4 text-muted fw-bold">{{ idx + 1 }}</td>
                      <td class="fw-bold text-dark">{{ item.item_code }}</td>
                      <td>
                        <div class="fw-bold text-dark">{{ item.item_name }}</div>
                        <small class="text-muted" v-if="item.price > 0">ราคาประมาณ: {{ formatCurrency(item.price) }} / {{ item.unit_name }}</small>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                          {{ item.category_name }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="fw-bold fs-6" :class="item.on_hand === 0 ? 'text-danger' : 'text-warning-emphasis'">
                          {{ formatNumber(item.on_hand) }}
                        </span>
                        <small class="text-muted ms-1">{{ item.unit_name }}</small>
                      </td>
                      <td class="text-center">
                        <span class="text-secondary fw-semibold">{{ formatNumber(item.minimum_stock) }}</span>
                        <small class="text-muted ms-1">{{ item.unit_name }}</small>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-bold">
                          + {{ formatNumber(item.suggested_qty) }} {{ item.unit_name }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span
                          class="badge rounded-pill px-3 py-1 fw-bold"
                          :class="item.status_type === 'DANGER' ? 'bg-danger text-white' : 'bg-warning text-dark'"
                        >
                          {{ item.status_text }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Dynamic Views for Workflow Steps -->
        <div v-else-if="currentTab === 'pr'" class="tab-pane-content">
          <PRDashboard :warehouse-id="selectedWarehouse" :key="'pr-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'pr_form'" class="tab-pane-content">
          <PRForm :warehouse-id="selectedWarehouse" :key="'prform-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'po'" class="tab-pane-content">
          <PODashboard :warehouse-id="selectedWarehouse" :key="'po-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'po_form'" class="tab-pane-content">
          <POForm :warehouse-id="selectedWarehouse" :key="'poform-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'receive'" class="tab-pane-content">
          <Receive :warehouse-id="selectedWarehouse" :key="'rcv-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'requisitions'" class="tab-pane-content">
          <RequisitionManage 
            :warehouse-id="selectedWarehouse" 
            :key="'reqm-' + (selectedWarehouse || 'all')" 
            @back-to-dashboard="selectTab('overview')"
            @go-to-form="selectTab('requisition_form')"
            @update-pending-count="handlePendingCount"
          />
        </div>

        <div v-else-if="currentTab === 'requisition_form'" class="tab-pane-content">
          <RequisitionForm 
            :warehouse-id="selectedWarehouse" 
            :key="'reqf-' + (selectedWarehouse || 'all')" 
            @back-to-dashboard="selectTab('overview')"
            @go-to-manage="selectTab('requisitions')"
          />
        </div>

        <div v-else-if="currentTab === 'issue'" class="tab-pane-content">
          <Issue :warehouse-id="selectedWarehouse" :key="'iss-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'transfer'" class="tab-pane-content">
          <TransferDashboard :warehouse-id="selectedWarehouse" :key="'trf-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'transfer_form'" class="tab-pane-content">
          <TransferForm :warehouse-id="selectedWarehouse" :key="'trfform-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'return'" class="tab-pane-content">
          <ReturnDashboard :warehouse-id="selectedWarehouse" :key="'ret-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'stock_card'" class="tab-pane-content">
          <StockCard :warehouse-id="selectedWarehouse" :key="'sc-' + (selectedWarehouse || 'all')" />
        </div>

        <div v-else-if="currentTab === 'reports'" class="tab-pane-content">
          <InventoryReports :warehouse-id="selectedWarehouse" :key="'rep-' + (selectedWarehouse || 'all')" @back-to-dashboard="selectTab('overview')" />
        </div>

        <div v-else-if="currentTab === 'settings'" class="tab-pane-content">
          <InventorySettings :warehouse-id="selectedWarehouse" :key="'set-' + (selectedWarehouse || 'all')" @switch-warehouse="openWarehouseModal" />
        </div>
      </div>
    </main>

    <!-- Warehouse Selection Modal (Identical to HomeBackoffice) -->
    <div v-if="showWarehouseModal" class="modal-backdrop fade show" style="backdrop-filter: blur(8px); background-color: rgba(15, 23, 42, 0.65); z-index: 1055;"></div>
    <div v-if="showWarehouseModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1056;" @click.self="showWarehouseModal = false">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden animate-fade-in-up">
          
          <!-- Modal Header -->
          <div class="modal-header border-0 p-4 text-white position-relative" style="background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 p-3 bg-white bg-opacity-20 d-flex align-items-center justify-content-center shadow-sm backdrop-blur" style="width: 52px; height: 52px;">
                <i class="bi bi-box-seam-fill fs-3 text-white"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-white mb-1">เลือกคลังวัสดุที่ต้องการเข้าใช้งาน</h5>
                <p class="text-white-50 small mb-0">ระบบคลังวัสดุรวมศูนย์ • เลือกระบบคลังตามสิทธิ์ที่คุณได้รับ</p>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="showWarehouseModal = false"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-4 bg-light">
            
            <!-- User Info Badge & Admin Indicator -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 px-1">
              <div class="small text-secondary d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-5 text-primary"></i>
                <span>ผู้ใช้งาน: <strong class="text-dark">{{ userProfile?.fullname || 'ผู้ใช้งาน' }}</strong></span>
                <span class="text-muted" v-if="userProfile?.department">({{ userProfile.department }})</span>
              </div>
              <div class="d-flex gap-2">
                <span v-if="isAdminUser" class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
                  <i class="bi bi-shield-check me-1"></i> สิทธิ์ผู้ดูแลระบบ (เข้าถึงได้ทุกคลัง)
                </span>
                <span v-else-if="userWarehouses.some(w => w.access_level === 'MANAGER')" class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                  <i class="bi bi-person-badge-fill me-1"></i> สิทธิ์ผู้ดูแลคลัง (สร้าง/ตั้งค่าคลังได้)
                </span>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loadingWarehouses" class="text-center py-5">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <div class="fw-semibold text-secondary">กำลังตรวจสอบสิทธิ์การเข้าถึงคลังวัสดุ...</div>
            </div>

            <!-- Empty / No Access State -->
            <div v-else-if="userWarehouses.length === 0" class="text-center py-5 bg-white rounded-4 border p-4 shadow-xs">
              <div class="text-warning mb-3">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">ไม่พบคลังที่คุณมีสิทธิ์เข้าใช้งาน</h5>
              <p class="text-muted small mx-auto mb-4" style="max-width: 460px;">
                บัญชีผู้ใช้งานของคุณยังไม่ได้รับการกำหนดสิทธิ์ในการเข้าถึงคลังวัสดุใดๆ 
                กรุณาติดต่อผู้ดูแลระบบ (Admin) หรือหัวหน้างานเพื่อมอบหมายสิทธิ์การใช้งานคลังพัสดุ
              </p>
              <button class="btn btn-outline-secondary rounded-pill px-4" @click="showWarehouseModal = false">
                <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
              </button>
            </div>

            <!-- Warehouses List -->
            <div v-else class="row g-3">
              
              <!-- Option for Admin: All Warehouses -->
              <div class="col-12" v-if="isAdminUser">
                <div 
                  class="warehouse-card p-3 rounded-4 bg-white border border-2 shadow-sm cursor-pointer position-relative d-flex align-items-center justify-content-between transition-all"
                  :class="!selectedWarehouse ? 'border-primary' : 'border-primary-subtle'"
                  @click="selectWarehouseAndEnter(null)"
                >
                  <div class="d-flex align-items-center gap-3">
                    <div class="wh-icon-box bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-xs">
                      <i class="bi bi-grid-fill fs-4"></i>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-dark mb-0">ภาพรวมทุกคลัง (All Warehouses)</h6>
                        <span class="badge bg-primary text-white rounded-pill px-2">Admin View</span>
                        <span v-if="!selectedWarehouse" class="badge bg-success text-white rounded-pill px-2">กำลังเลือกอยู่</span>
                      </div>
                      <small class="text-muted">ดูยอดสต็อก การรับ-จ่าย และรายงานรวมของทุกคลังในระบบ</small>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2 text-primary fw-semibold small pe-2">
                    <span>{{ !selectedWarehouse ? 'เลือกอยู่' : 'เข้าสู่ภาพรวม' }}</span>
                    <i class="bi bi-arrow-right-circle-fill fs-5"></i>
                  </div>
                </div>
              </div>

              <!-- Available Warehouses -->
              <div 
                v-for="wh in userWarehouses" 
                :key="wh.id" 
                class="col-12 col-md-6"
              >
                <div 
                  class="warehouse-card p-3 rounded-4 bg-white border shadow-sm cursor-pointer position-relative h-100 d-flex flex-column justify-content-between transition-all"
                  :class="String(selectedWarehouse) === String(wh.id) ? 'border-primary border-2 shadow' : ''"
                  @click="selectWarehouseAndEnter(wh)"
                >
                  <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="wh-icon-box bg-primary text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs">
                      <span class="fw-bold fs-5">{{ wh.code }}</span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                      <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                        <span class="badge bg-light text-secondary border small">รหัส: {{ wh.code }}</span>
                        <span 
                          v-if="wh.access_level === 'MANAGER'" 
                          class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"
                        >
                          <i class="bi bi-person-badge-fill me-1"></i> ผู้ดูแลคลัง
                        </span>
                        <span 
                          v-else-if="wh.access_level === 'STAFF'" 
                          class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill"
                        >
                          <i class="bi bi-person-fill me-1"></i> เจ้าหน้าที่
                        </span>
                        <span 
                          v-else-if="wh.access_level === 'ADMIN'" 
                          class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"
                        >
                          <i class="bi bi-shield-check me-1"></i> ผู้ดูแลระบบ
                        </span>
                      </div>
                      <h6 class="fw-bold text-dark mb-1 text-truncate" :title="wh.name">{{ wh.name }}</h6>
                      <small class="text-muted d-block">คลิกเพื่อเข้าจัดการสต็อก รับ-จ่าย เฉพาะคลังนี้</small>
                    </div>
                  </div>

                  <div class="pt-2 border-top d-flex align-items-center justify-content-between text-primary fw-semibold small">
                    <span v-if="String(selectedWarehouse) === String(wh.id)" class="text-success fw-bold">
                      <i class="bi bi-check-circle-fill me-1"></i> กำลังใช้งานคลังนี้
                    </span>
                    <span v-else>เข้าสู่คลังนี้</span>
                    <i class="bi bi-arrow-right"></i>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- Modal Footer -->
          <div class="modal-footer border-0 bg-white px-4 py-3 d-flex justify-content-between flex-wrap gap-2">
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i> ระบบจะกรองข้อมูลเฉพาะคลังที่เลือกเพื่อความถูกต้องในการทำงาน
            </small>
            <div class="d-flex gap-2">
              <button 
                v-if="canManageSettings"
                type="button" 
                class="btn btn-outline-primary rounded-pill px-3 fw-bold small"
                @click="showWarehouseModal = false; selectTab('settings');"
              >
                <i class="bi bi-gear-fill me-1"></i> ตั้งค่าระบบคลัง / สร้างคลังใหม่
              </button>
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showWarehouseModal = false">
                ปิด
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

// Asynchronous Component Loading for Fast & Smooth Tab Switching
const PRDashboard = defineAsyncComponent(() => import('./PRDashboard.vue'));
const PRForm = defineAsyncComponent(() => import('./PRForm.vue'));
const PODashboard = defineAsyncComponent(() => import('./PODashboard.vue'));
const POForm = defineAsyncComponent(() => import('./POForm.vue'));
const Receive = defineAsyncComponent(() => import('./Receive.vue'));
const Issue = defineAsyncComponent(() => import('./Issue.vue'));
const RequisitionManage = defineAsyncComponent(() => import('./RequisitionManage.vue'));
const RequisitionForm = defineAsyncComponent(() => import('./RequisitionForm.vue'));
const TransferDashboard = defineAsyncComponent(() => import('./TransferDashboard.vue'));
const TransferForm = defineAsyncComponent(() => import('./TransferForm.vue'));
const ReturnDashboard = defineAsyncComponent(() => import('./ReturnDashboard.vue'));
const StockCard = defineAsyncComponent(() => import('./StockCard.vue'));
const InventoryReports = defineAsyncComponent(() => import('./InventoryReports.vue'));
const InventorySettings = defineAsyncComponent(() => import('./InventorySettings.vue'));

export default {
  name: 'InventoryDashboard',
  components: {
    PRDashboard,
    PRForm,
    PODashboard,
    POForm,
    Receive,
    Issue,
    RequisitionManage,
    RequisitionForm,
    TransferDashboard,
    TransferForm,
    ReturnDashboard,
    StockCard,
    InventoryReports,
    InventorySettings
  },
  data() {
    return {
      currentTab: this.$route.query.tab || 'overview',
      isCollapsed: false,
      mobileOpen: false,
      openGroups: {
        pr_po: true,
        issue_group: true,
        transfer: false
      },
      pendingRequisitionsCount: 0,
      isAdminUser: false,
      currentUserId: '',
      userProfile: {},
      selectedWarehouse: '',
      showWarehouseModal: false,
      loadingWarehouses: false,
      userWarehouses: [],
      warehouses: [],
      metrics: {
        total_value: 0,
        total_balance_qty: 0,
        active_items_count: 0,
        total_items_count: 0,
        total_in_qty: 0,
        total_out_qty: 0,
        in_tx_count: 0,
        out_tx_count: 0,
        low_stock_count: 0
      },
      lowStockItems: [],
      monthlyTrends: [],
      categoryDistribution: [],
      movementChart: null,
      categoryChart: null
    };
  },
  computed: {
    isAdmin() {
      return this.isAdminUser;
    },
    isManager() {
      return this.isAdminUser || this.warehouses.some(w => w.access_level === 'MANAGER');
    },
    canManageSettings() {
      return this.isAdminUser || this.isManager;
    },
    activeWarehouseObj() {
      if (!this.selectedWarehouse) return null;
      return this.warehouses.find(w => String(w.id) === String(this.selectedWarehouse)) || null;
    },
    activeWarehouseDisplay() {
      if (!this.selectedWarehouse) return 'ภาพรวมทุกคลัง (All Warehouses)';
      if (this.activeWarehouseObj) {
        return `[${this.activeWarehouseObj.code}] ${this.activeWarehouseObj.name}`;
      }
      const savedName = localStorage.getItem('selected_warehouse_name');
      const savedCode = localStorage.getItem('selected_warehouse_code');
      if (savedName && savedCode) return `[${savedCode}] ${savedName}`;
      return savedName || 'คลังวัสดุ';
    }
  },
  watch: {
    '$route.query.tab'(newTab) {
      this.currentTab = newTab || 'overview';
      if (['pr', 'pr_form', 'po', 'po_form'].includes(this.currentTab)) {
        this.openGroups.pr_po = true;
      } else if (['requisitions', 'requisition_form', 'issue'].includes(this.currentTab)) {
        this.openGroups.issue_group = true;
      } else if (['transfer', 'transfer_form'].includes(this.currentTab)) {
        this.openGroups.transfer = true;
      }
      if (this.currentTab === 'overview') {
        this.$nextTick(() => {
          this.renderCharts();
        });
      }
    }
  },
  async mounted() {
    if (['pr', 'pr_form', 'po', 'po_form'].includes(this.currentTab)) {
      this.openGroups.pr_po = true;
    } else if (['requisitions', 'requisition_form', 'issue'].includes(this.currentTab)) {
      this.openGroups.issue_group = true;
    } else if (['transfer', 'transfer_form'].includes(this.currentTab)) {
      this.openGroups.transfer = true;
    }
    await this.checkAdminStatus();

    // Read warehouse from route query or localStorage
    const qWh = this.$route.query.warehouse_id;
    if (qWh !== undefined && qWh !== null && qWh !== '') {
      this.selectedWarehouse = parseInt(qWh) || qWh;
      localStorage.setItem('selected_warehouse_id', this.selectedWarehouse);
    } else {
      const savedWh = localStorage.getItem('selected_warehouse_id');
      if (savedWh) {
        this.selectedWarehouse = parseInt(savedWh) || savedWh;
      }
    }

    await this.fetchDashboardSummary();
  },
  beforeUnmount() {
    if (this.movementChart) this.movementChart.destroy();
    if (this.categoryChart) this.categoryChart.destroy();
  },
  methods: {
    async fetchDashboardSummary() {
      try {
        const userId = this.currentUserId || localStorage.getItem('user_id') || '';
        const isAdmin = this.isAdminUser ? 1 : 0;
        const params = new URLSearchParams();
        if (this.selectedWarehouse) params.append('warehouse_id', this.selectedWarehouse);
        if (userId) params.append('user_id', userId);
        params.append('is_admin', isAdmin);

        const res = await axios.get(`/api-digital/inventory/dashboard_summary.php?${params.toString()}`);
        if (res.data && res.data.status === 'success') {
          const d = res.data.data;
          this.metrics = d.metrics || this.metrics;
          this.pendingRequisitionsCount = d.metrics?.pending_requisitions_count || 0;
          this.lowStockItems = d.low_stock_items || [];
          this.monthlyTrends = d.monthly_trends || [];
          this.categoryDistribution = d.category_distribution || [];
          this.warehouses = d.warehouses || [];

          // Sync warehouse name to localStorage
          if (this.warehouses.length > 0 && this.selectedWarehouse) {
            const currentObj = this.warehouses.find(w => w.id == this.selectedWarehouse);
            if (currentObj) {
              localStorage.setItem('selected_warehouse_name', currentObj.name);
            } else if (!this.isAdminUser) {
              this.selectedWarehouse = this.warehouses[0].id;
              localStorage.setItem('selected_warehouse_id', this.selectedWarehouse);
              localStorage.setItem('selected_warehouse_name', this.warehouses[0].name);
            }
          } else if (this.warehouses.length > 0 && !this.selectedWarehouse && !this.isAdminUser) {
            this.selectedWarehouse = this.warehouses[0].id;
            localStorage.setItem('selected_warehouse_id', this.selectedWarehouse);
            localStorage.setItem('selected_warehouse_name', this.warehouses[0].name);
          }

          if (this.currentTab === 'overview') {
            this.$nextTick(() => {
              this.renderCharts();
            });
          }
        }
      } catch (err) {
        console.error('Failed to load dashboard summary:', err);
      }
    },
    onWarehouseChange() {
      if (this.selectedWarehouse) {
        localStorage.setItem('selected_warehouse_id', this.selectedWarehouse);
        const whObj = this.warehouses.find(w => w.id == this.selectedWarehouse);
        if (whObj) localStorage.setItem('selected_warehouse_name', whObj.name);
        this.$router.replace({ query: { ...this.$route.query, warehouse_id: this.selectedWarehouse } });
      } else {
        localStorage.removeItem('selected_warehouse_id');
        localStorage.setItem('selected_warehouse_name', 'คลังทั้งหมด');
        const q = { ...this.$route.query };
        delete q.warehouse_id;
        this.$router.replace({ query: q });
      }
      this.fetchDashboardSummary();
    },
    async openWarehouseModal() {
      this.showWarehouseModal = true;
      this.loadingWarehouses = true;
      this.userWarehouses = [];
      try {
        const userId = this.currentUserId || localStorage.getItem('user_id') || '';
        const isAdmin = this.isAdminUser ? 1 : 0;
        const res = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}&is_admin=${isAdmin}`);
        if (res.data && res.data.status === 'success') {
          this.userWarehouses = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching user warehouses:', err);
      } finally {
        this.loadingWarehouses = false;
      }
    },
    selectWarehouseAndEnter(wh) {
      if (wh) {
        this.selectedWarehouse = wh.id;
        localStorage.setItem('selected_warehouse_id', wh.id);
        localStorage.setItem('selected_warehouse_code', wh.code);
        localStorage.setItem('selected_warehouse_name', wh.name);
        if (wh.access_level) {
          localStorage.setItem('selected_warehouse_access_level', wh.access_level);
        }
        this.$router.replace({ query: { ...this.$route.query, warehouse_id: wh.id } });
      } else {
        this.selectedWarehouse = '';
        localStorage.removeItem('selected_warehouse_id');
        localStorage.removeItem('selected_warehouse_code');
        localStorage.setItem('selected_warehouse_name', 'คลังทั้งหมด');
        const q = { ...this.$route.query };
        delete q.warehouse_id;
        this.$router.replace({ query: q });
      }
      this.showWarehouseModal = false;
      this.fetchDashboardSummary();
    },
    renderCharts() {
      this.renderMovementChart();
      this.renderCategoryChart();
    },
    renderMovementChart() {
      if (this.movementChart) {
        this.movementChart.destroy();
        this.movementChart = null;
      }
      const ctx = this.$refs.movementChartRef;
      if (!ctx) return;

      const labels = this.monthlyTrends.map(m => m.label);
      const dataIn = this.monthlyTrends.map(m => m.qty_in);
      const dataOut = this.monthlyTrends.map(m => m.qty_out);

      this.movementChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'รับเข้าคลัง (IN)',
              data: dataIn,
              backgroundColor: 'rgba(16, 185, 129, 0.85)',
              borderColor: '#10b981',
              borderWidth: 1,
              borderRadius: 6
            },
            {
              label: 'จ่ายออกคลัง (OUT)',
              data: dataOut,
              backgroundColor: 'rgba(245, 158, 11, 0.85)',
              borderColor: '#f59e0b',
              borderWidth: 1,
              borderRadius: 6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                boxWidth: 12,
                font: { family: 'Prompt, sans-serif', weight: 'bold' }
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return ` ${context.dataset.label}: ${Number(context.raw).toLocaleString()} ชิ้น`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { font: { family: 'Prompt, sans-serif' } },
              grid: { color: 'rgba(0, 0, 0, 0.05)' }
            },
            x: {
              ticks: { font: { family: 'Prompt, sans-serif' } },
              grid: { display: false }
            }
          }
        }
      });
    },
    renderCategoryChart() {
      if (this.categoryChart) {
        this.categoryChart.destroy();
        this.categoryChart = null;
      }
      const ctx = this.$refs.categoryChartRef;
      if (!ctx) return;

      const labels = this.categoryDistribution.map(c => c.name);
      const dataQty = this.categoryDistribution.map(c => c.total_qty);

      const colors = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#ec4899', '#14b8a6', '#6366f1', '#84cc16'
      ];

      this.categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [
            {
              data: dataQty,
              backgroundColor: colors.slice(0, labels.length),
              borderWidth: 2,
              borderColor: '#ffffff'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 10,
                font: { family: 'Prompt, sans-serif', size: 11 }
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return ` ${context.label}: ${Number(context.raw).toLocaleString()} ชิ้น`;
                }
              }
            }
          },
          cutout: '65%'
        }
      });
    },
    selectTab(tab) {
      this.currentTab = tab;
      if (this.mobileOpen) this.mobileOpen = false;
      if (tab === 'overview') {
        this.$router.replace({ query: {} }).catch(() => {});
        this.$nextTick(() => {
          this.renderCharts();
        });
      } else {
        this.$router.replace({ query: { ...this.$route.query, tab } }).catch(() => {});
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    handlePendingCount(cnt) {
      this.pendingRequisitionsCount = cnt;
    },
    toggleGroup(grp) {
      if (this.isCollapsed) {
        this.isCollapsed = false;
        this.openGroups[grp] = true;
      } else {
        this.openGroups[grp] = !this.openGroups[grp];
      }
    },
    isGroupOpen(grp) {
      return !this.isCollapsed && !!this.openGroups[grp];
    },
    toggleCollapse() {
      this.isCollapsed = !this.isCollapsed;
      if (this.currentTab === 'overview') {
        setTimeout(() => {
          this.renderCharts();
        }, 300);
      }
    },
    formatCurrency(num) {
      if (!num || isNaN(num)) return '฿0.00';
      return '฿' + Number(num).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatNumber(num) {
      if (!num || isNaN(num)) return '0';
      return Number(num).toLocaleString('th-TH');
    },
    async checkAdminStatus() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data && response.data.status === 'success') {
          this.userProfile = response.data;
          this.currentUserId = response.data.id || response.data.user_id || '';
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
/* Layout Architecture */
.inventory-layout {
  position: relative;
  width: 100%;
}

/* Sidebar Styling */
.inventory-sidebar {
  width: 270px;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 1020;
  transition: width 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s ease;
  user-select: none;
}

.inventory-sidebar.sidebar-collapsed {
  width: 76px;
}

.sidebar-header {
  height: 68px;
}

.sidebar-brand-icon {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  color: #ffffff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
}

.toggle-btn {
  width: 28px;
  height: 28px;
  padding: 0;
  border: 1px solid #e5e7eb;
}

.menu-section-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 0.4rem 0.75rem 0.2rem;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 0.6rem 0.75rem;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.18s ease;
  margin-bottom: 0.2rem;
  color: #374151;
  position: relative;
}

.menu-item:hover {
  background-color: #f3f4f6;
  color: #111827;
}

.menu-item.active {
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.08));
  color: #4f46e5;
}

.menu-item.active .menu-text .fw-bold {
  color: #4338ca !important;
}

.menu-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
  margin-right: 0.75rem;
}

.inventory-sidebar.sidebar-collapsed .menu-icon {
  margin-right: 0;
}

.inventory-sidebar.sidebar-collapsed .menu-item {
  justify-content: center;
  padding: 0.6rem 0;
}

.mini-step-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 17px;
  height: 17px;
  border-radius: 50%;
  color: #ffffff;
  font-size: 0.65rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
}

.menu-text {
  flex-grow: 1;
  min-width: 0;
  line-height: 1.25;
}

.menu-text .fw-bold {
  font-size: 0.88rem;
}

/* Submenu */
.submenu-container {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.submenu-item {
  display: flex;
  align-items: center;
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s ease;
}

.submenu-item:hover {
  background-color: #f3f4f6;
  color: #111827;
}

.submenu-item.active {
  background-color: #ede9fe;
  color: #6d28d9;
  font-weight: 700;
}

/* Metric / Stat Cards */
.stat-card {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
}

.stat-badge {
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
}

.stat-icon-circle {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
}

.text-teal { color: #0d9488 !important; }
.bg-teal { background-color: #0d9488 !important; }
.bg-teal-subtle { background-color: rgba(13, 148, 136, 0.12) !important; }

/* Alert Box */
.alert-icon-square {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

/* Content Area Overrides for Nested Child Components */
.inventory-main-content {
  min-width: 0;
}

.inventory-main-content :deep(.page-container) {
  padding-top: 1rem !important;
  padding-bottom: 2rem !important;
  background-color: transparent !important;
  min-height: auto !important;
}

.inventory-main-content :deep(.container-fluid) {
  padding-left: 1rem !important;
  padding-right: 1rem !important;
}

@media (min-width: 768px) {
  .inventory-main-content :deep(.container-fluid) {
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
  }
}

/* Mobile Responsiveness */
@media (max-width: 991.98px) {
  .inventory-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 280px !important;
    transform: translateX(-100%);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  }

  .inventory-sidebar.sidebar-mobile-open {
    transform: translateX(0);
  }

  .sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.45);
    z-index: 1019;
    backdrop-filter: blur(2px);
  }
}

/* Workflow Banner & Stepper */
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

/* Card Steps in Overview */
.card-step {
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
  background: #ffffff;
}

.card-accent-line {
  height: 4px;
  width: 100%;
}

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

.hover-underline:hover {
  text-decoration: underline;
}

/* Color Tokens */
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

/* Warehouse Selection Modal Styles */
.warehouse-card {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid #e2e8f0;
}
.warehouse-card:hover {
  transform: translateY(-3px);
  border-color: #0284c7 !important;
  box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.15), 0 8px 10px -6px rgba(2, 132, 199, 0.1) !important;
}
.wh-icon-box {
  width: 48px;
  height: 48px;
}
.animate-fade-in-up {
  animation: fadeInUpModal 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeInUpModal {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
