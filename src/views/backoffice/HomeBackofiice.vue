<template>
  <div class="page-container min-vh-100 bg-light py-5">
    <div class="container-lg">
      <!-- User Profile Header -->
      <div v-if="userProfile.fullname" class="row justify-content-center mb-5 fade-in-up">
        <div class="col-lg-10">
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
        <div class="col-lg-10">
          <div class="d-flex align-items-center mb-4 px-2">
            <div class="bg-primary rounded-pill me-3" style="width: 5px; height: 25px"></div>
            <h4 class="fw-bold text-dark m-0">เมนูหลัก</h4>
          </div>

          <!-- Category 1: บริการไอทีและทรัพย์สิน (IT Support & Assets) -->
          <h6 class="fw-bold text-secondary mb-3 mt-1 border-bottom pb-2">
            <i class="bi bi-pc-display me-2 text-primary"></i>สำหรับเจ้าหน้าที่
          </h6>
          <div class="row g-3 mb-4">
            <!-- Access Log -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-teal text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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
            <!-- Computer Repair -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-red text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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
            <!-- Material Request Form IT -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-pink text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                @click="goToMaterialRequestForm"
              >
                <div
                  class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                ></div>
                <div class="position-relative z-1 d-flex flex-column h-100">
                  <div
                    class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                  >
                    <i class="bi bi-cart-plus fs-4"></i>
                  </div>
                  <h6 class="fw-bold text-white mb-1 text-truncate" title="ฟอร์มขอเบิกวัสดุคอมฯ">
                    ขอเบิกวัสดุคอมฯ
                  </h6>
                  <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                    สำหรับขอเบิกอุปกรณ์ IT
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

            <!-- Material Request Form Admin -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-teal text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                @click="goToMaterialAdminRequestForm"
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
                  <h6 class="fw-bold text-white mb-1 text-truncate" title="ฟอร์มขอเบิกวัสดุบริหาร">
                    ขอเบิกวัสดุบริหารฯ
                  </h6>
                  <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                    สำหรับขอเบิกวัสดุทั่วไป
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

            <!-- Report Status -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-blue text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-green text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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

            <!-- Computer Loan Form -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-purple text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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

            <!-- Revenue Result Reporting (Staff) -->
            <div class="col-6 col-md-4 col-lg-3">
              <div
                class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
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
          </div>
          <template v-if="hasGMAccess">
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-graph-up me-2 text-primary"></i>งานบริหาร
            </h6>
            <div class="row g-3 mb-4">
              <!-- Material Management Admin -->
              <div class="col-6 col-md-4 col-lg-3">
                <div
                  class="nav-card h-100 bg-gradient-green text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToMaterialAdmin"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-archive-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="เบิก-จ่ายวัสดุบริหารฯ">
                      เบิก-จ่ายวัสดุบริหารฯ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      หน้า Stock วัสดุงานบริหาร
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
          <template v-if="isAdmin">
            <h6 class="fw-bold text-secondary mb-3 mt-4 border-bottom pb-2">
              <i class="bi bi-graph-up me-2 text-primary"></i>งานบริการ IT และทรัพย์สิน
            </h6>
            <div class="row g-3 mb-4">
              <!-- Asset Management -->
              <div class="col-6 col-md-4 col-lg-3">
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

              <!-- Software Registration -->
              <div class="col-6 col-md-4 col-lg-3">
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

              <!-- Material Management IT -->
              <div class="col-6 col-md-4 col-lg-3">
                <div
                  class="nav-card h-100 bg-gradient-orange text-white rounded-4 shadow-sm p-3 position-relative overflow-hidden cursor-pointer group"
                  @click="goToMaterialV2"
                >
                  <div
                    class="card-bg-decoration bg-white opacity-10 group-hover-opacity-20 transition-all"
                  ></div>
                  <div class="position-relative z-1 d-flex flex-column h-100">
                    <div
                      class="icon-box-small bg-white bg-opacity-25 text-white rounded-3 mb-2 d-inline-flex align-items-center justify-content-center backdrop-blur"
                    >
                      <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1 text-truncate" title="เบิก-จ่ายวัสดุคอมฯ">
                      เบิก-จ่ายวัสดุคอมฯ
                    </h6>
                    <p class="text-white-50 small mb-2 flex-grow-1" style="font-size: 0.75rem">
                      หน้า Stock In/Out วัสดุ IT
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
              <div class="col-6 col-md-4 col-lg-3">
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
              <div class="col-6 col-md-4 col-lg-3">
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
              <div class="col-6 col-md-4 col-lg-3">
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
              <!-- IT Manager Schedule -->
              <div class="col-6 col-md-4 col-lg-3">
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
              <div class="col-6 col-md-4 col-lg-3">
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
              <div class="col-6 col-md-4 col-lg-3">
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
              <div class="col-6 col-md-4 col-lg-3">
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
            </div>
          </template>
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
      }
    };
  },
  computed: {
    isAdmin() {
      const dept = (this.userProfile && this.userProfile.department) || '';
      return dept.includes('กลุ่มงานสุขภาพดิจิทัล');
    },
    hasGMAccess() {
      const dept = (this.userProfile && this.userProfile.department) || '';
      const allowedDepts = ['สุขภาพดิจิทัล', 'บริหาร'];
      return allowedDepts.some((key) => dept.includes(key));
    }
  },
  methods: {
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.userProfile = response.data;
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
    async checkITAccessAndGo(path) {
      const getDept = () => this.userProfile.department || '';
      let dept = getDept();
      const allowedDepts = ['สุขภาพดิจิทัล', 'ประกันสุขภาพ', 'ยุทธศาสตร์'];

      const checkAccess = (d) => allowedDepts.some((key) => d.includes(key));

      if (checkAccess(dept)) {
        this.$router.push({ path: path });
      } else {
        if (!dept) {
          await this.fetchUserProfile();
          dept = getDept();
          if (checkAccess(dept)) {
            this.$router.push({ path: path });
            return;
          }
        }
        alert(`คุณไม่มีสิทธิ์เข้าถึงเมนูนี้ (หน่วยงานของคุณ: ${dept || 'ไม่ระบุ'})`);
      }
    },
    async checkITAccessAndGoGM(path) {
      const getDept = () => this.userProfile.department || '';
      let dept = getDept();
      const allowedDepts = ['สุขภาพดิจิทัล', 'บริหาร'];

      const checkAccess = (d) => allowedDepts.some((key) => d.includes(key));

      if (checkAccess(dept)) {
        this.$router.push({ path: path });
      } else {
        if (!dept) {
          await this.fetchUserProfile();
          dept = getDept();
          if (checkAccess(dept)) {
            this.$router.push({ path: path });
            return;
          }
        }
        alert(`คุณไม่มีสิทธิ์เข้าถึงเมนูนี้ (หน่วยงานของคุณ: ${dept || 'ไม่ระบุ'})`);
      }
    },
    async goToManagerSchedule() {
      await this.checkITAccessAndGo('/manager-schedule');
    },
    async goToServerList() {
      await this.checkITAccessAndGo('/server-list');
    },
    goToReport() {
      this.$router.push({ path: '/report' });
    },
    goToFingerScan() {
      this.$router.push({ path: '/finger-scan' });
    },
    async goToUserManager() {
      await this.checkITAccessAndGo('/user-manager');
    },
    async goToAdminDocuments() {
      await this.checkITAccessAndGo('/admin-docs');
    },
    async goToKpiDashboard() {
      await this.checkITAccessAndGo('/kpi-setup');
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
    async goToIpAddress() {
      await this.checkITAccessAndGo('/ip-address');
    },
    async goToMaterialV2() {
      await this.checkITAccessAndGo('/material-v2');
    },
    async goToMaterialAdmin() {
      await this.checkITAccessAndGoGM('/material-admin');
    },
    async goToSoftwareManagement() {
      await this.checkITAccessAndGo('/software-dashboard');
    },
    async goToCommunicationManagement() {
      await this.checkITAccessAndGo('/communication-dashboard');
    },
    goToMaterialRequestForm() {
      this.$router.push({ path: '/material-request' });
    },
    goToMaterialAdminRequestForm() {
      this.$router.push({ path: '/material-admin-request' });
    },

    goToComputerLoanForm() {
      this.$router.push({ path: '/computer-loan' });
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
  background-color: #f8f9fa;
  background-image: radial-gradient(#e9ecef 1px, transparent 1px);
  background-size: 20px 20px;
}

/* Profile Section */
.profile-card {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  border: none;
  transition: transform 0.3s ease;
}

.profile-card:hover {
  transform: translateY(-2px);
}

.bg-decoration-circle-1,
.bg-decoration-circle-2 {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.bg-decoration-circle-1 {
  width: 300px;
  height: 300px;
  top: -100px;
  right: -50px;
}

.bg-decoration-circle-2 {
  width: 200px;
  height: 200px;
  bottom: -50px;
  left: -50px;
}

.avatar-ring {
  width: 130px;
  height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-circle {
  width: 120px;
  height: 120px;
  background-color: #e2e8f0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 4px solid #fff;
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
}

.backdrop-blur {
  backdrop-filter: blur(5px);
}

.letter-spacing-1 {
  letter-spacing: 1px;
}

/* Nav Cards */
.nav-card {
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.nav-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.1) !important;
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
</style>
