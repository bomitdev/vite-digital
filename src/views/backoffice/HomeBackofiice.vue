<template>
  <div class="page-container min-vh-100 bg-light py-5">
    <div class="container-xxl">
      <!-- User Profile Header -->
      <div v-if="userProfile.fullname" class="row justify-content-center mb-5 fade-in-up">
        <div class="col-12">
          <div
            class="profile-card p-4 p-md-5 rounded-5 shadow text-white position-relative overflow-hidden"
          >
            <div class="bg-decoration-circle-1"></div>
            <div class="bg-decoration-circle-2"></div>

            <div class="d-flex flex-column flex-md-row align-items-center position-relative z-1">
              <div class="me-md-5 mb-4 mb-md-0 position-relative">
                <div class="avatar-ring p-1 rounded-circle bg-white bg-opacity-25 backdrop-blur">
                  <div class="avatar-circle">
                    <img
                      v-if="userProfile.image"
                      :src="userProfile.image"
                      alt="Profile"
                      class="profile-img"
                    />
                    <i v-else class="bi bi-person-fill text-white fs-1"></i>
                  </div>
                </div>
                <div class="status-badge">
                  <span class="visually-hidden">Online</span>
                </div>
              </div>
              <div class="text-center text-md-start">
                <h5 class="text-white-50 text-uppercase fw-bold letter-spacing-1 mb-2">
                  ยินดีต้อนรับกลับ,
                </h5>
                <h2 class="display-6 fw-bold mb-2 text-white">{{ userProfile.fullname }}</h2>
                <div
                  v-if="userProfile.department"
                  class="d-inline-flex align-items-center bg-white bg-opacity-20 backdrop-blur rounded-pill px-4 py-2 mt-2 border border-white border-opacity-25"
                >
                  <i class="bi bi-building-fill me-2 fs-5"></i>
                  <span class="fs-6 text-dark">{{ userProfile.department }}</span>
                </div>
              </div>
              <div class="ms-md-auto mt-4 mt-md-0">
                <button
                  @click="logout"
                  class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 hover-scale"
                >
                  <i class="bi bi-box-arrow-right text-danger"></i>
                  <span class="text-danger">ออกจากระบบ</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Menu Grid -->
      <div class="row justify-content-center g-4 fade-in-up delay-100">
        <div class="col-12">
          <div class="d-flex align-items-center mb-4 px-2">
            <div class="bg-primary rounded-pill me-3" style="width: 5px; height: 25px"></div>
            <h4 class="fw-bold text-dark m-0">เมนูหลัก</h4>
          </div>

          <!-- Category 1: บริการไอทีและทรัพย์สิน (IT Support & Assets) -->
          <template v-if="showStaffCategory">
            <h6 class="fw-bold text-secondary mb-3 mt-1 border-bottom pb-2">
              <i class="bi bi-pc-display me-2 text-primary"></i>สำหรับเจ้าหน้าที่
            </h6>
            <div class="row g-4 mb-4">
              <!-- ====== GROUP 1: HR & Time ====== -->
              <!-- Access Log -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_time_in_out', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-green text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToFingerScan"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="เวลาเข้า-ออกงาน">
                      เวลาเข้า-ออกงาน
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ประวัติลงเวลาและสรุปวันลา
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- ====== GROUP 2: Data & Reports ====== -->
              <!-- Report Status -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_data_report', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToReport"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-file-earmark-text-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ขอข้อมูล/รายงาน">
                      ขอข้อมูล/รายงาน
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ยื่นคำร้องและติดตามผล
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Report Center -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_report_center', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToReportCenter"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-table fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="Report Center">
                      Report Center
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      เรียกดูรายงานและ Export
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- General Maintenance Repair -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_general_repair', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-teal text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToGeneralRepair"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-wrench-adjustable fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="แจ้งซ่อมบำรุงทั่วไป">
                      แจ้งซ่อมบำรุงทั่วไป
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      อาคาร ครุภัณฑ์ ไฟฟ้า ประปา แอร์
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====== GROUP 3: IT ====== -->
              <!-- Computer Repair -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_computer_repair', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToComputerRepair"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-tools fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="แจ้งซ่อมคอมพิวเตอร์">
                      แจ้งซ่อมคอมพิวเตอร์
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ติดตามปัญหาอุปกรณ์
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              

              <!-- Computer Loan Form -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_borrow_it', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToComputerLoanForm"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-laptop fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ยืมอุปกรณ์คอมพิวเตอร์">
                      ยืมอุปกรณ์คอมฯ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ยื่นคำร้องขอยืมอุปกรณ์
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Central Inventory Requisition (เบิกวัสดุแบบรวมศูนย์) -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="
                  hasPermission([
                    'menu_it_material_req',
                    'menu_gm_material_req',
                    'menu_inventory_req',
                    'backoffice_staff_all'
                  ]) ||
                  isInventoryAdmin ||
                  hasWarehouseAccess
                "
              >
                <div
                  class="nav-card h-100 bg-gradient-teal text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="openRequisitionWarehouseModal"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div
                        class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 d-inline-flex align-items-center justify-content-center backdrop-blur"
                      >
                        <i class="bi bi-boxes fs-4"></i>
                      </div>
                      <span
                        class="badge bg-white bg-opacity-25 text-white rounded-pill px-2 py-1 fw-bold"
                        style="font-size: 0.65rem"
                      >
                        คลังรวมศูนย์
                      </span>
                    </div>
                    <h6
                      class="fw-bold text-white mb-1 text-truncate"
                      title="เบิกวัสดุแบบรวมศูนย์"
                    >
                      เบิกวัสดุแบบรวมศูนย์
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      เลือกคลังที่ต้องการขอเบิกวัสดุ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เลือกคลังเบิก
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Department Shift Schedule (จัดการตารางเวรกลุ่มงาน) -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission(['administrator', 'backoffice_staff_all']) || hasDeptScheduleAccess">
                <div
                  class="nav-card h-100 bg-gradient-purple text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="openDeptScheduleModal"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-calendar2-week-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="จัดการตารางเวรกลุ่มงาน">
                      จัดการตารางเวรกลุ่มงาน
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดตารางเวรแยกตามกลุ่มงาน
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- เบิกวัสดุการแพทย์ -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission(['backoffice_staff_all'])">
                <a
                  href="http://192.168.9.83:8602/"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-decoration-none d-block h-100"
                >
                  <div
                    class="nav-card h-100 bg-gradient-purple text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  >
                    <div
                      class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                    ></div>

                    <div class="position-relative z-1 d-flex flex-column h-100">
                      <div
                        class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                      >
                        <i class="bi bi-box-seam fs-4"></i>
                      </div>

                      <h6 class="fw-bold text-white mb-1 text-truncate" title="เบิกวัสดุการแพทย์">
                        เบิกวัสดุการแพทย์
                      </h6>

                      <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                        ระบบเบิกจ่ายวัสดุการแพทย์
                      </p>

                      <div
                        class="d-flex align-items-center text-white fw-bold small mt-auto"
                        style="font-size: 0.75rem"
                      >
                        เข้าใช้งาน
                        <i
                          class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                        ></i>
                      </div>
                    </div>
                  </div>
                </a>
              </div>

              <!-- Revenue Result Reporting (Staff) -->
              <div
                class="col-6 col-md-4 col-lg-3"
                v-if="hasPermission(['menu_revenue_report', 'backoffice_staff_all'])"
              >
                <div
                  class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="goToRevenueResult"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="รายงานผลจัดเก็บรายได้">
                      รายงานผลจัดเก็บ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ดูภาพรวมและส่งยอดรายได้
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Project Management -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_projects')">
                <div
                  class="nav-card h-100 bg-gradient-purple text-white rounded-4 shadow-sm p-4 position-relative overflow-hidden cursor-pointer group"
                  @click="$router.push('/projects')"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-kanban fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ระบบงานโครงการ">
                      โครงการ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ติดตามความคืบหน้าโครงการ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Category Inventory System (Phase 1-3) -->
          <template
            v-if="
              isInventoryAdmin ||
              hasPermission('menu_pharmacy_material_manage') ||
              hasPermission('menu_med_material_manage') ||
              hasPermission('menu_gm_material_manage') ||
              hasWarehouseAccess
            "
          >
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-box-seam me-2 text-primary"></i>ระบบคลังวัสดุรวมศูนย์ (Inventory
              System)
            </h6>
            <div class="row g-3 mb-4">
              <!-- Main Inventory Dashboard -->
              <div class="col-6 col-md-4 col-lg-3">
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="openWarehouseSelectModal"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ระบบคลังวัสดุ">
                      จัดการคลังวัสดุ (สำหรับผู้ดูแลคลัง)
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      รับ-จ่าย, เช็คสต๊อก, Master Data
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template v-if="hasGMAccess">
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-graph-up me-2 text-primary"></i>งานบริหาร
            </h6>
            <div class="row g-3 mb-4">
              <!-- Procurement Tracking -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_procurement')">
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToProcurement"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-cart-check fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ระบบจัดซื้อ-จัดจ้าง">
                      ระบบจัดซื้อ-จัดจ้าง
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ติดตามการส่งเอกสารเบิกจ่าย
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Category 2: รายงานและสถิติ (Reports & Analytics) -->
          <template v-if="isItAdmin">
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-graph-up me-2 text-primary"></i>งานบริการ IT และทรัพย์สิน
            </h6>
            <div class="row g-3 mb-4">
              <!-- Asset Management -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_it_asset')">
                <div
                  class="nav-card h-100 bg-gradient-cyan text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToAssetManagement"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-pc-display fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ระบบครุภัณฑ์คอมฯ">
                      ระบบครุภัณฑ์คอมฯ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ทะเบียนและประวัติซ่อม
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Printer Dashboard -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_it_asset')">
                <div
                  class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToPrinterDashboard"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-printer fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ประวัติเครื่องปริ้น">
                      ประวัติปริ้นเตอร์
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      สถานะและยอดการปริ้น
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Software Registration -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_software')">
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToSoftwareManagement"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-disc fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ทะเบียนซอฟต์แวร์">
                      ทะเบียนซอฟต์แวร์
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการ License, วันหมดอายุ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- IP Address Registry -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_ip_address')">
                <div
                  class="nav-card h-100 bg-gradient-indigo text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToIpAddress"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-router-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ทะเบียน IP Address">
                      ทะเบียน IP Address
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการ IP เครื่องคอมพิวเตอร์
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Server Registry -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_server')">
                <div
                  class="nav-card h-100 bg-gradient-indigo text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToServerList"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-server fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ทะเบียน Server">
                      ทะเบียน Server
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ดูและจัดการข้อมูลสเปคเครื่องเซิร์ฟเวอร์
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Category 3: การจัดการองค์กร (Management & Admin) -->
          <template v-if="isAdmin">
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-gear-fill me-2 text-primary"></i>สำหรับ Admin
            </h6>
            <div class="row g-3">
              <!-- KPI Dashboard -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_kpi_admin')">
                <div
                  class="nav-card h-100 bg-gradient-indigo text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToKpiDashboard"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ระบบ KPI (Admin)">
                      ระบบ KPI (Admin)
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการตัวชี้วัด, ลงผลงาน
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- QI Committees -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_ha') || isAdmin">
                <div
                  class="nav-card h-100 bg-gradient-teal text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToQiCommittees"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="คณะกรรมการ HA">
                      คณะกรรมการ HA
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการรายชื่อทีมพัฒนาคุณภาพ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- IT Manager Schedule -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_it_schedule')">
                <div
                  class="nav-card h-100 bg-gradient-purple text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToManagerSchedule"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-calendar-check-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="จัดตารางเวร IT">
                      จัดตารางเวร IT
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      สำหรับกลุ่มงานดิจิทัลฯ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Communication Channels Registry -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_communication')">
                <div
                  class="nav-card h-100 bg-gradient-pink text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToCommunicationManagement"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-broadcast fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ทะเบียนช่องทางสื่อสาร">
                      ช่องทางสื่อสาร
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ระบบติดตามและจัดการ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Admin Docs Center -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_doc_center')">
                <div
                  class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToAdminDocuments"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-folder-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ศูนย์จัดการเอกสาร">
                      ศูนย์จัดการเอกสาร
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการไฟล์เอกสารระบบ
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- User Manager -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('menu_user_manager')">
                <div
                  class="nav-card h-100 bg-gradient-indigo text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToUserManager"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-person-lines-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="จัดการสิทธิ์ผู้ใช้งาน">
                      สิทธิ์ผู้ใช้งาน
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      กำหนดสิทธิ์ระดับผู้ใช้
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- สถาปัตยกรรมระบบ (Archify) -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('administrator')">
                <div
                  class="nav-card h-100 bg-gradient-cyan text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="$router.push('/architecture')"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-diagram-3-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="สถาปัตยกรรมระบบ">
                      สถาปัตยกรรมระบบ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      แผนภาพโครงสร้างทั้งระบบ (Archify)
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- ตั้งค่าคีย์ MOPH Notify (Admin Only) -->
              <div class="col-6 col-md-4 col-lg-3" v-if="hasPermission('administrator')">
                <div
                  class="nav-card h-100 bg-gradient-green text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="$router.push('/moph-notify-settings')"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-key-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="ตั้งค่าคีย์ MOPH Notify">
                      ตั้งค่าคีย์ MOPH Notify
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการ Key แจ้งเตือน LINE ในฐานข้อมูล
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Git Sync -->
              <div class="col-6 col-md-4 col-lg-3" v-if="userProfile?.username === 'suriya'">
                <div
                  class="nav-card h-100 bg-gradient-dark text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="$router.push('/git-sync')"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-github fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="Git Sync">Git Sync</h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      จัดการอัพโหลดโค้ด
                    </p>
                    <div
                      class="d-flex align-items-center text-white fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i
                        class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"
                      ></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Central Audit Logs (Admin Only) -->
              <div class="col-6 col-md-4 col-lg-3" v-if="isAdmin">
                <div
                  class="nav-card h-100 bg-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group border border-primary-subtle"
                  @click="$router.push('/audit-logs')"
                >
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-primary-subtle text-primary rounded-3 mb-2 d-inline-flex align-items-center justify-content-center"
                    >
                      <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1 text-truncate" title="Audit Logs (90 วัน)">Audit Logs (90 วัน)</h6>
                    <p class="text-muted small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      ตรวจสอบประวัติการใช้งานระบบ
                    </p>
                    <div
                      class="d-flex align-items-center text-primary fw-bold small mt-auto"
                      style="font-size: 0.75rem"
                    >
                      เข้าใช้งาน
                      <i class="bi bi-arrow-right ms-1 transition-transform group-hover-translate-x"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- WAREHOUSE SELECTION MODAL (ตามสิทธิ์ผู้ใช้งาน)         -->
    <!-- ======================================================= -->
    <div v-if="showWarehouseModal" class="modal-backdrop fade show" style="backdrop-filter: blur(8px); background-color: rgba(15, 23, 42, 0.65); z-index: 1055;"></div>
    <div v-if="showWarehouseModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1056;" @click.self="showWarehouseModal = false">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden animate-fade-in-up">
          
          <!-- Modal Header -->
          <div
            class="modal-header border-0 p-4 text-white position-relative"
            :style="
              warehouseModalMode === 'requisition'
                ? 'background: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);'
                : 'background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);'
            "
          >
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 p-3 bg-white bg-opacity-20 d-flex align-items-center justify-content-center shadow-sm backdrop-blur" style="width: 52px; height: 52px;">
                <i :class="warehouseModalMode === 'requisition' ? 'bi bi-cart-plus-fill fs-3 text-white' : 'bi bi-box-seam-fill fs-3 text-white'"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-white mb-1">
                  {{ warehouseModalMode === 'requisition' ? 'เลือกคลังวัสดุที่ต้องการขอเบิก' : 'เลือกคลังวัสดุที่ต้องการเข้าใช้งาน' }}
                </h5>
                <p class="text-white-50 small mb-0">
                  {{
                    warehouseModalMode === 'requisition'
                      ? 'เบิกวัสดุแบบรวมศูนย์ • เลือกคลังพัสดุที่ต้องการสร้างใบขอเบิก'
                      : 'ระบบคลังวัสดุรวมศูนย์ • เลือกระบบคลังตามสิทธิ์ที่คุณได้รับ'
                  }}
                </p>
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
                <span v-if="isInventoryAdmin" class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">
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
              <div class="col-12" v-if="warehouseModalMode === 'manage' && isInventoryAdmin">
                <div 
                  class="warehouse-card p-3 rounded-4 bg-white border border-2 border-primary-subtle shadow-sm cursor-pointer position-relative d-flex align-items-center justify-content-between transition-all"
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
                      </div>
                      <small class="text-muted">ดูยอดสต็อก การรับ-จ่าย และรายงานรวมของทุกคลังในระบบ</small>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2 text-primary fw-semibold small pe-2">
                    <span>เข้าสู่ภาพรวม</span>
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
                  @click="selectWarehouseAndEnter(wh)"
                >
                  <div class="d-flex align-items-start gap-3 mb-3">
                    <div
                      class="wh-icon-box text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs"
                      :class="warehouseModalMode === 'requisition' ? 'bg-gradient-teal' : 'bg-gradient-blue'"
                    >
                      <span class="fw-bold fs-5">{{ wh.code }}</span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                      <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                        <span class="badge bg-light text-secondary border small">รหัส: {{ wh.code }}</span>
                        <span
                          v-if="warehouseModalMode === 'requisition'"
                          class="badge bg-teal-subtle text-success border border-success-subtle rounded-pill"
                        >
                          <i class="bi bi-cart-plus-fill me-1"></i> เปิดให้ขอเบิก
                        </span>
                        <span 
                          v-else-if="wh.access_level === 'MANAGER'" 
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
                      <small class="text-muted d-block">
                        {{ warehouseModalMode === 'requisition' ? 'คลิกเพื่อสร้างใบขอเบิกวัสดุจากคลังนี้' : 'คลิกเพื่อเข้าจัดการสต็อก รับ-จ่าย' }}
                      </small>
                    </div>
                  </div>

                  <div
                    class="pt-2 border-top d-flex align-items-center justify-content-between fw-semibold small"
                    :class="warehouseModalMode === 'requisition' ? 'text-success' : 'text-primary'"
                  >
                    <span>{{ warehouseModalMode === 'requisition' ? 'ขอเบิกวัสดุจากคลังนี้' : 'เข้าสู่คลังนี้' }}</span>
                    <i class="bi bi-arrow-right"></i>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- Modal Footer -->
          <div class="modal-footer border-0 bg-white px-4 py-3 d-flex justify-content-between flex-wrap gap-2">
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i> สามารถสลับคลังได้อีกครั้งภายในหน้าระบบคลัง
            </small>
            <div class="d-flex gap-2">
              <button 
                v-if="warehouseModalMode === 'manage' && (isInventoryAdmin || userWarehouses.some(w => w.access_level === 'MANAGER'))"
                type="button" 
                class="btn btn-outline-primary rounded-pill px-3 fw-bold small"
                @click="goToSettingsDirectly"
              >
                <i class="bi bi-gear-fill me-1"></i> ตั้งค่าระบบคลัง / สร้างคลังใหม่
              </button>
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showWarehouseModal = false">
                ยกเลิก
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Department Schedule Selection Modal -->
    <div v-if="showDeptScheduleModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl animate-fade-in-up" style="max-width: 1180px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header border-0 text-white p-4 bg-gradient-purple">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-calendar2-week-fill fs-4"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0">เลือกกลุ่มงานที่ต้องการดู / จัดการตารางเวร</h5>
                <small class="text-white-50">เลือกกลุ่มงานของคุณ หรือกลุ่มงานที่ได้รับสิทธิ์เข้าถึง</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="showDeptScheduleModal = false"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <div v-if="loadingDeptGroups" class="text-center py-5">
              <div class="spinner-border text-primary mb-2" role="status"></div>
              <p class="text-muted small mb-0">กำลังตรวจสอบตารางเวรกลุ่มงาน...</p>
            </div>

            <div v-else-if="deptScheduleGroups.length === 0" class="text-center py-5">
              <i class="bi bi-calendar-x fs-1 text-muted d-block mb-2"></i>
              <h6 class="fw-bold text-dark">ยังไม่มีตารางเวรกลุ่มงานที่คุณเข้าถึงได้</h6>
              <p class="text-muted small mb-0">
                {{ hasPermission(['administrator']) ? 'กดปุ่ม "สร้างตารางเวรกลุ่มงานใหม่" ด้านล่างเพื่อเริ่มต้น' : 'ยังไม่มีการสร้างตารางเวรสำหรับกลุ่มงานของคุณ' }}
              </p>
            </div>

            <div v-else>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted fw-semibold">
                  <i class="bi bi-check-circle-fill text-success me-1"></i>
                  กลุ่มงานที่สร้างตารางเวรแล้ว: <strong class="text-dark">{{ deptScheduleGroups.length }}</strong> กลุ่มงาน
                </small>
              </div>
              <div class="row g-3">
              <div v-for="g in deptScheduleGroups" :key="g.id" class="col-12 col-md-6">
                <div
                  class="warehouse-card p-3 rounded-4 bg-white border shadow-sm cursor-pointer position-relative h-100 d-flex flex-column justify-content-between transition-all"
                  @click="selectDeptScheduleAndEnter(g)"
                >
                  <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="wh-icon-box bg-gradient-purple text-white rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs">
                      <i class="bi bi-calendar2-week-fill fs-5"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                      <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                        <span class="badge bg-light text-secondary border small">{{ g.member_count || 0 }} คน</span>
                        <span v-if="g.access_level === 'ADMIN'" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                          <i class="bi bi-shield-check me-1"></i> ผู้ดูแลกลุ่มงาน
                        </span>
                        <span v-else-if="g.access_level === 'STAFF'" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                          <i class="bi bi-pencil-square me-1"></i> ผู้จัดเวร
                        </span>
                        <span v-else class="badge bg-light text-secondary border rounded-pill">
                          <i class="bi bi-eye me-1"></i> ดูอย่างเดียว
                        </span>
                      </div>
                      <h6 class="fw-bold text-dark mb-1" :title="g.title">{{ g.title }}</h6>
                      <small class="text-muted d-block">{{ g.department_name }}</small>
                    </div>
                  </div>
                  <div class="pt-2 border-top d-flex align-items-center justify-content-between fw-semibold small text-primary">
                    <span>{{ g.access_level === 'VIEWER' ? 'เปิดดูตารางเวร' : 'เข้าจัดการตารางเวร' }}</span>
                    <i class="bi bi-arrow-right"></i>
                  </div>
                </div>
              </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 bg-white px-4 py-3 d-flex justify-content-between flex-wrap gap-2">
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i> ผู้จัดเวรต้องได้รับสิทธิ์จากผู้ดูแลระบบก่อนจึงจะแก้ไขได้
            </small>
            <div class="d-flex gap-2">
              <button
                v-if="hasPermission(['administrator'])"
                type="button"
                class="btn btn-outline-primary rounded-pill px-3 fw-bold small"
                @click="goToCreateDeptSchedule"
              >
                <i class="bi bi-plus-circle me-1"></i> สร้างตารางเวรกลุ่มงานใหม่
              </button>
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showDeptScheduleModal = false">
                ยกเลิก
              </button>
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
  // ... (rest of script remains the same)
  name: 'HomeBackofiice',
  data() {
    return {
      userProfile: {
        fullname: '',
        department: '',
        image: ''
      },
      showWarehouseModal: false,
      warehouseModalMode: 'manage',
      loadingWarehouses: false,
      userWarehouses: [],
      hasWarehouseAccess: false,
      hasDeptScheduleAccess: false,
      showDeptScheduleModal: false,
      loadingDeptGroups: false,
      deptScheduleGroups: []
    };
  },
  computed: {
    showStaffCategory() {
      return this.hasPermission([
        'menu_time_in_out',
        'menu_computer_repair',
        'menu_general_repair',
        'menu_it_material_req',
        'menu_gm_material_req',
        'menu_data_report',
        'menu_report_center',
        'menu_borrow_it',
        'menu_revenue_report',
        'menu_projects',
        'backoffice_staff_all'
      ]);
    },
    hasGMAccess() {
      return this.hasPermission(['menu_procurement']);
    },

    isItAdmin() {
      return this.hasPermission([
        'menu_it_asset',
        'menu_software',
        'menu_it_material_manage',
        'menu_ip_address',
        'menu_server'
      ]);
    },
    isAdmin() {
      return this.hasPermission([
        'menu_kpi_admin',
        'menu_it_schedule',
        'menu_communication',
        'menu_doc_center',
        'menu_user_manager',
        'menu_git_sync',
        'menu_ha'
      ]);
    },
    isInventoryAdmin() {
      return this.hasPermission([
        'administrator',
        'menu_inventory_admin',
        'menu_inventory_manage'
      ]);
    }
  },
  methods: {
    hasPermission(keys) {
      if (!this.userProfile || !this.userProfile.access_user) return false;
      const userPerms = this.userProfile.access_user.split(':');
      if (userPerms.includes('administrator')) return true;
      if (Array.isArray(keys)) {
        return keys.some((k) => userPerms.includes(k));
      }
      return userPerms.includes(keys);
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.userProfile = response.data;
          const userId = response.data.id || response.data.user_id || '';
          if (userId) {
            axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}&is_admin=${this.isInventoryAdmin ? 1 : 0}`)
              .then(wRes => {
                if (wRes.data && wRes.data.status === 'success' && wRes.data.data?.length > 0) {
                  this.hasWarehouseAccess = true;
                }
              }).catch(() => {});
            axios.get(`/api-digital/dept_schedule/api.php?action=groups&user_id=${userId}&is_admin=${this.hasPermission(['administrator']) ? 1 : 0}`)
              .then(dRes => {
                if (dRes.data && dRes.data.status === 'success' && dRes.data.data?.length > 0) {
                  this.hasDeptScheduleAccess = true;
                }
              }).catch(() => {});
          }
        }
      } catch (error) {
        console.error('Failed to load profile', error);
        if (error.response && error.response.data) {
          console.log('Debug Auth Error:', error.response.data);
        }
      }
    },
    // isAdmin moved to computed
    async goToReportCenter() {
      // Check if Admin
      if (this.isAdmin) {
        if (
          confirm(
            'คุณต้องการไปหน้าจัดการรายงาน (Admin) หรือไม่? กด OK เพื่อไป Admin, Cancel เพื่อไปดูรายงาน'
          )
        ) {
          this.$router.push({ path: '/report-center/admin' });
        } else {
          this.$router.push({ path: '/report-center' });
        }
      } else {
        this.$router.push({ path: '/report-center' });
      }
    },
    async checkAdminAccessAndGo(path) {
      if (this.isAdmin) {
        this.$router.push({ path: path });
      } else {
        alert('คุณไม่มีสิทธิ์เข้าถึงเมนูนี้ (เฉพาะสิทธิ์ผู้ดูแลระบบ)');
      }
    },
    async checkITAccessAndGo(path) {
      if (this.isItAdmin || this.isAdmin) {
        this.$router.push({ path: path });
      } else {
        alert('คุณไม่มีสิทธิ์เข้าถึงเมนูนี้ (เฉพาะสิทธิ์งานบริการ IT)');
      }
    },
    async goToManagerSchedule() {
      await this.checkAdminAccessAndGo('/manager-schedule');
    },
    async openDeptScheduleModal() {
      this.showDeptScheduleModal = true;
      this.loadingDeptGroups = true;
      this.deptScheduleGroups = [];
      try {
        const userId = this.userProfile?.id || this.userProfile?.user_id || localStorage.getItem('user_id') || '';
        const isAdmin = this.hasPermission(['administrator']) ? 1 : 0;
        let deptId = this.userProfile?.department_sub_id || this.userProfile?.HR_DEPARTMENT_SUB_ID || '';
        if (!isAdmin && !deptId && userId) {
          const sRes = await axios.get('/api-hosoffice/get_all_staff.php').catch(() => null);
          const staffList = sRes?.data?.data || (Array.isArray(sRes?.data) ? sRes.data : []);
          const me = staffList.find(s => String(s.ID) === String(userId));
          if (me?.HR_DEPARTMENT_SUB_ID) deptId = me.HR_DEPARTMENT_SUB_ID;
        }
        const res = await axios.get('/api-digital/dept_schedule/api.php', {
          params: { action: 'groups', user_id: userId, user_department_id: deptId, is_admin: isAdmin }
        });
        if (res.data?.status === 'success') {
          this.deptScheduleGroups = res.data.data || [];
        }
      } catch (err) {
        console.error('Failed to load department schedules:', err);
      } finally {
        this.loadingDeptGroups = false;
      }
    },
    selectDeptScheduleAndEnter(group) {
      this.showDeptScheduleModal = false;
      this.$router.push({ path: '/department-schedule', query: { group_id: group.id } });
    },
    goToCreateDeptSchedule() {
      this.showDeptScheduleModal = false;
      this.$router.push({ path: '/department-schedule', query: { create: '1' } });
    },
    async goToServerList() {
      await this.checkITAccessAndGo('/server-list');
    },
    goToReport() {
      this.$router.push({ path: '/report' });
    },
    goToReportCenter() {
      this.$router.push({ path: '/report-center' });
    },
    goToFingerScan() {
      this.$router.push({ path: '/finger-scan' });
    },
    async goToUserManager() {
      await this.checkAdminAccessAndGo('/user-manager');
    },
    async goToPrinterDashboard() {
      await this.checkITAccessAndGo('/printer-dashboard');
    },
    async goToAdminDocuments() {
      await this.checkAdminAccessAndGo('/admin-docs');
    },
    async goToKpiDashboard() {
      await this.checkAdminAccessAndGo('/kpi-setup');
    },
    async goToQiCommittees() {
      this.$router.push({ path: '/qi-committees' });
    },

    goToRevenueResult() {
      this.$router.push({ path: '/revenue-dashboard' });
    },
    async goToAssetManagement() {
      await this.checkITAccessAndGo('/asset-management');
    },
    goToComputerRepair() {
      this.$router.push({ path: '/computer-repair' });
    },
    goToGeneralRepair() {
      this.$router.push({ path: '/general-repair' });
    },
    async goToIpAddress() {
      await this.checkITAccessAndGo('/ip-address');
    },
    goToProcurement() {
      this.$router.push({ path: '/procurement' });
    },
    async goToSoftwareManagement() {
      await this.checkITAccessAndGo('/software-dashboard');
    },
    async goToCommunicationManagement() {
      await this.checkAdminAccessAndGo('/communication-dashboard');
    },
    goToComputerLoanForm() {
      this.$router.push({ path: '/computer-loan' });
    },
    async openRequisitionWarehouseModal() {
      if (!this.userProfile || !this.userProfile.id) {
        await this.fetchUserProfile();
      }

      this.warehouseModalMode = 'requisition';
      this.showWarehouseModal = true;
      this.loadingWarehouses = true;
      this.userWarehouses = [];

      try {
        const res = await axios.get('/api-digital/inventory/warehouses.php');
        if (res.data && res.data.status === 'success') {
          this.userWarehouses = (res.data.data || []).filter((w) => Number(w.active) === 1);
        }
      } catch (err) {
        console.error('Error fetching warehouses for requisition:', err);
      } finally {
        this.loadingWarehouses = false;
      }
    },
    async openWarehouseSelectModal() {
      if (!this.userProfile || !this.userProfile.id) {
        await this.fetchUserProfile();
      }

      this.warehouseModalMode = 'manage';
      this.showWarehouseModal = true;
      this.loadingWarehouses = true;
      this.userWarehouses = [];

      try {
        const userId = this.userProfile?.id || this.userProfile?.user_id || '';
        const isAdmin = this.isInventoryAdmin ? 1 : 0;
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
      if (this.warehouseModalMode === 'requisition') {
        if (!wh) return;
        localStorage.setItem('selected_warehouse_id', wh.id);
        localStorage.setItem('selected_warehouse_code', wh.code);
        localStorage.setItem('selected_warehouse_name', wh.name);
        this.showWarehouseModal = false;
        this.$router.push({
          path: '/inventory-requisition-form',
          query: { warehouse_id: wh.id, from: 'central_req' }
        });
        return;
      }

      if (wh) {
        localStorage.setItem('selected_warehouse_id', wh.id);
        localStorage.setItem('selected_warehouse_code', wh.code);
        localStorage.setItem('selected_warehouse_name', wh.name);
        if (wh.access_level) {
          localStorage.setItem('selected_warehouse_access_level', wh.access_level);
        }
        this.showWarehouseModal = false;
        this.$router.push({ path: '/inventory-dashboard', query: { warehouse_id: wh.id } });
      } else {
        localStorage.removeItem('selected_warehouse_id');
        localStorage.removeItem('selected_warehouse_code');
        localStorage.setItem('selected_warehouse_name', 'คลังทั้งหมด');
        this.showWarehouseModal = false;
        this.$router.push({ path: '/inventory-dashboard' });
      }
    },
    goToSettingsDirectly() {
      this.showWarehouseModal = false;
      this.$router.push({ path: '/inventory-dashboard', query: { tab: 'settings' } });
    },
    logout() {
      if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
        localStorage.removeItem('user_token');
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    this.fetchUserProfile();
  }
};
</script>

<style scoped>
/* Page Background */
.page-container {
  background-color: #f1f3f5;
}

/* Profile Section */
.profile-card {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  border: none;
  transition: transform 0.3s ease;
  width: 1300px;
  height: 300px;
}

.profile-card:hover {
  transform: translateY(-2px);
}

.bg-decoration-circle-1,
.bg-decoration-circle-2 {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  pointer-events: none;
}

.bg-decoration-circle-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -50px;
  animation: floatCircle1 8s ease-in-out infinite alternate;
}

.bg-decoration-circle-2 {
  width: 200px;
  height: 200px;
  bottom: -50px;
  left: -50px;
  animation: floatCircle2 6s ease-in-out infinite alternate;
}

.avatar-ring {
  position: relative;
  width: 200px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: avatarGlow 3.2s ease-in-out infinite;
}

.avatar-ring::before {
  content: '';
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 2px dashed rgba(255, 255, 255, 0.45);
  animation: spinSlow 18s linear infinite;
  pointer-events: none;
}

.avatar-circle {
  width: 200px;
  height: 200px;
  background-color: #e2e8f0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 4px solid #fff;
  position: relative;
  z-index: 1;
}

.profile-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.status-badge {
  position: absolute;
  bottom: 10px;
  right: 15px;
  width: 20px;
  height: 20px;
  background-color: #078658;
  border: 3px solid #fff;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 2;
}

.status-badge::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid #22c55e;
  animation: statusPulse 2s ease-out infinite;
}

@keyframes floatCircle1 {
  0% {
    transform: translate(0, 0) scale(1);
  }
  100% {
    transform: translate(-25px, 20px) scale(1.08);
  }
}

@keyframes floatCircle2 {
  0% {
    transform: translate(0, 0) scale(1);
  }
  100% {
    transform: translate(22px, -18px) scale(1.1);
  }
}

@keyframes avatarGlow {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.35);
  }
  50% {
    box-shadow: 0 0 0 12px rgba(255, 255, 255, 0);
  }
}

@keyframes spinSlow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes statusPulse {
  0% {
    transform: scale(0.95);
    opacity: 0.9;
  }
  100% {
    transform: scale(1.9);
    opacity: 0;
  }
}

.backdrop-blur {
  backdrop-filter: blur(5px);
}

.letter-spacing-1 {
  letter-spacing: 1px;
}

/* Nav Cards - Minimal Center Icon Tile Style */
.nav-card {
  --card-accent: #3b82f6;
  --card-accent-bg: rgba(59, 130, 246, 0.1);
  background: #ffffff !important;
  color: #1f2937 !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 10px !important;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05) !important;
  padding: 1.75rem 1rem !important;
  min-height: 145px;
  text-align: center;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.nav-card:hover {
  transform: translateY(-4px);
  border-color: var(--card-accent) !important;
  box-shadow: 0 10px 24px -6px rgba(15, 23, 42, 0.12) !important;
}

.nav-card .card-bg-decoration {
  display: none !important;
}

.nav-card > .position-relative {
  align-items: center !important;
  justify-content: center !important;
  gap: 0.75rem;
}

.nav-card .d-flex.justify-content-between {
  justify-content: center !important;
  width: 100%;
  margin-bottom: 0 !important;
}

.nav-card .icon-box,
.nav-card .icon-box-small {
  background: transparent !important;
  border: none !important;
  backdrop-filter: none !important;
  width: auto !important;
  height: auto !important;
  margin: 0 !important;
  padding: 0 !important;
  color: var(--card-accent) !important;
  transition: transform 0.22s ease;
}

.nav-card .icon-box i,
.nav-card .icon-box-small i {
  font-size: 2.75rem !important;
  line-height: 1;
}

.nav-card:hover .icon-box,
.nav-card:hover .icon-box-small {
  transform: scale(1.08);
}

.nav-card h6,
.nav-card .fw-bold.text-white {
  color: #1f2937 !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
  margin: 0 !important;
  white-space: normal !important;
  line-height: 1.35;
}

.nav-card p.text-white-50,
.nav-card .text-white-50,
.nav-card .mt-auto.text-white,
.nav-card .mt-auto.fw-bold {
  display: none !important;
}

.nav-card .badge.bg-white {
  position: absolute;
  top: -8px;
  right: -2px;
  background-color: var(--card-accent-bg) !important;
  color: var(--card-accent) !important;
  font-size: 0.65rem !important;
}

.nav-card.bg-gradient-purple {
  --card-accent: #7c3aed;
  --card-accent-bg: rgba(124, 58, 237, 0.12);
}
.nav-card.bg-gradient-blue {
  --card-accent: #3b82f6;
  --card-accent-bg: rgba(59, 130, 246, 0.12);
}
.nav-card.bg-gradient-teal {
  --card-accent: #0d9488;
  --card-accent-bg: rgba(13, 148, 136, 0.12);
}
.nav-card.bg-gradient-orange {
  --card-accent: #ea580c;
  --card-accent-bg: rgba(234, 88, 12, 0.12);
}
.nav-card.bg-gradient-pink {
  --card-accent: #db2777;
  --card-accent-bg: rgba(219, 39, 119, 0.12);
}
.nav-card.bg-gradient-green {
  --card-accent: #65a30d;
  --card-accent-bg: rgba(101, 163, 13, 0.12);
}
.nav-card.bg-gradient-indigo {
  --card-accent: #f59e0b;
  --card-accent-bg: rgba(245, 158, 11, 0.12);
}
.nav-card.bg-gradient-cyan {
  --card-accent: #0891b2;
  --card-accent-bg: rgba(8, 145, 178, 0.12);
}
.nav-card.bg-gradient-red {
  --card-accent: #dc2626;
  --card-accent-bg: rgba(220, 38, 38, 0.12);
}
.nav-card.bg-gradient-dark {
  --card-accent: #44403c;
  --card-accent-bg: rgba(68, 64, 60, 0.12);
}

.icon-box {
  width: 64px;
  height: 64px;
}

.icon-box-small {
  width: 42px;
  height: 42px;
}

.bg-gradient-purple {
  background: linear-gradient(135deg, #a076f9 0%, #6f42c1 100%);
}
.bg-gradient-blue {
  background: linear-gradient(135deg, #4facfe 0%, #076d72 100%);
}
.bg-gradient-teal {
  background: linear-gradient(135deg, #20c997 0%, #05662c 100%);
}
.bg-gradient-orange {
  background: linear-gradient(135deg, #fd7e14 0%, #d63384 100%);
}
.bg-gradient-pink {
  background: linear-gradient(135deg, #e83e8c 0%, #6f42c1 100%);
}
.bg-gradient-green {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}
.bg-gradient-indigo {
  background: linear-gradient(135deg, #6610f2 0%, #0d6efd 100%);
}
.bg-gradient-cyan {
  background: linear-gradient(135deg, #17a2b8 0%, #0dcaf0 100%);
}
.bg-gradient-red {
  background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
}

.bg-purple-subtle {
  background-color: rgba(111, 66, 193, 0.1);
}
.text-purple {
  color: #6f42c1;
}

.bg-blue-subtle {
  background-color: rgba(13, 110, 253, 0.1);
}
.bg-teal-subtle {
  background-color: rgba(32, 201, 151, 0.1);
}
.text-teal {
  color: #198754;
}

.card-bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 6px;
}

.group:hover .group-hover-opacity-10 {
  opacity: 0.1 !important;
  height: 100%;
}

.group:hover .group-hover-translate-x {
  transform: translateX(5px);
}

.transition-all {
  transition: all 0.3s ease;
}
.transition-transform {
  transition: transform 0.3s ease;
}

.hover-scale {
  transition: transform 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.05);
}

/* Animations */
.fade-in-up {
  animation: fadeInUp 0.8s ease-out;
}

.delay-100 {
  animation-delay: 0.1s;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

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
