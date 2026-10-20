<template>
  <div class="had-dashboard bg-light min-vh-100 py-4 px-3 px-md-4">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><router-link to="/">หน้าหลัก</router-link></li>
            <li class="breadcrumb-item">IPDPaperless</li>
            <li class="breadcrumb-item active" aria-current="page">ติดตามการใช้ยาความเสี่ยงสูง (HAD)</li>
          </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
          <div class="brand-icon-square bg-danger text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm">
            <i class="bi bi-shield-exclamation fs-4"></i>
          </div>
          <div>
            <h3 class="fw-bold text-dark mb-0">
              ระบบติดตามการใช้ยาความเสี่ยงสูง (High Alert Drug - HAD)
            </h3>
            <small class="text-muted">
              ตรวจสอบและติดตามการประเมินการใช้ยาความเสี่ยงสูงของผู้ป่วยใน (IPD) ตามมาตรฐานความปลอดภัยทางยา
            </small>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button
          class="btn btn-outline-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium"
          @click="exportToExcel"
          :disabled="loading || filteredRecords.length === 0"
        >
          <i class="bi bi-file-earmark-excel-fill"></i>
          <span>ส่งออก Excel</span>
        </button>

        <button
          class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium"
          @click="fetchData"
          :disabled="loading"
        >
          <i class="bi bi-arrow-clockwise" :class="{ 'spin-animation': loading }"></i>
          <span>รีเฟรชข้อมูล</span>
        </button>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-end">
          <!-- Period / Fiscal Year Quick Select -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-calendar-event me-1"></i>ช่วงเวลา
            </label>
            <select
              class="form-select form-select-sm rounded-3 shadow-none border"
              v-model="selectedFiscalYear"
              @change="onFiscalYearChange"
            >
              <option value="today">⚡ วันนี้ (Today)</option>
              <option value="yesterday">เมื่อวานนี้</option>
              <option value="this_month">เดือนนี้</option>
              <option value="2569">ปีงบฯ 2569 (2025-2026)</option>
              <option value="2568">ปีงบฯ 2568 (2024-2025)</option>
              <option value="2567">ปีงบฯ 2567 (2023-2024)</option>
              <option value="custom">กำหนดเอง (Custom)</option>
            </select>
          </div>

          <!-- Date Range: Start -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-calendar3 me-1"></i>ตั้งแต่วันที่
            </label>
            <input
              type="date"
              class="form-control form-control-sm rounded-3 shadow-none border"
              v-model="startDate"
              :disabled="selectedFiscalYear !== 'custom'"
              @change="fetchData"
            />
          </div>

          <!-- Date Range: End -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-calendar3 me-1"></i>ถึงวันที่
            </label>
            <input
              type="date"
              class="form-control form-control-sm rounded-3 shadow-none border"
              v-model="endDate"
              :disabled="selectedFiscalYear !== 'custom'"
              @change="fetchData"
            />
          </div>

          <!-- Drug Selector Filter -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-capsule me-1"></i>รายการยา (HAD Drug)
            </label>
            <select
              class="form-select form-select-sm rounded-3 shadow-none border"
              v-model="selectedDrug"
              @change="fetchData"
            >
              <option value="">-- ยาทั้งหมด (All) --</option>
              <option v-for="d in drugOptions" :key="d.name" :value="d.name">
                {{ d.name }} ({{ d.count }})
              </option>
            </select>
          </div>

          <!-- Assessment Status Filter -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-check2-circle me-1"></i>สถานะประเมิน
            </label>
            <select
              class="form-select form-select-sm rounded-3 shadow-none border"
              v-model="selectedStatus"
              @change="fetchData"
            >
              <option value="all">ทั้งหมด (All Cases)</option>
              <option value="assessed">🟢 ประเมินแล้ว (Assessed)</option>
              <option value="unassessed">🔴 ยังไม่ประเมิน (Missing)</option>
            </select>
          </div>

          <!-- View Mode Toggle -->
          <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label small fw-bold text-secondary mb-1">
              <i class="bi bi-view-list me-1"></i>มุมมองข้อมูล
            </label>
            <div class="btn-group btn-group-sm w-100 rounded-3 shadow-none" role="group">
              <button
                type="button"
                class="btn"
                :class="viewMode === 'detail' ? 'btn-danger' : 'btn-outline-secondary'"
                @click="setViewMode('detail')"
                title="แสดงแยกตามรายการยา"
              >
                รายยา
              </button>
              <button
                type="button"
                class="btn"
                :class="viewMode === 'admission' ? 'btn-danger' : 'btn-outline-secondary'"
                @click="setViewMode('admission')"
                title="แสดงยุบรวมตามเลข AN"
              >
                รายเคส (AN)
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Metric Cards Row -->
    <div class="row g-3 mb-4">
      <!-- 1. Total Admissions with HAD -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card kpi-purple overflow-hidden">
          <div class="card-body p-4 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-purple-subtle text-purple rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-people-fill me-1"></i> ผู้ป่วยใช้ยา HAD
              </span>
              <div class="kpi-icon-circle bg-purple text-white">
                <i class="bi bi-person-lines-fill fs-5"></i>
              </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">
              {{ formatNumber(metrics.total_admissions) }}
              <span class="fs-6 fw-normal text-muted">Admit</span>
            </h2>
            <div class="text-muted small">
              <i class="bi bi-person-check-fill text-purple me-1"></i>
              จำนวนผู้ป่วย {{ formatNumber(metrics.total_patients) }} คน ({{ formatNumber(metrics.total_records) }} รายการสั่งใช้)
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Assessed Rate & Count -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card kpi-green overflow-hidden cursor-pointer" @click="quickFilterStatus('assessed')">
          <div class="card-body p-4 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i> ประเมินแล้ว (Assessed)
              </span>
              <div class="kpi-icon-circle bg-success text-white">
                <i class="bi bi-clipboard2-check-fill fs-5"></i>
              </div>
            </div>
            <h2 class="fw-bold text-success mb-1">
              {{ formatNumber(metrics.assessed_admissions) }}
              <span class="fs-6 fw-normal text-muted">เคส</span>
            </h2>
            <div class="text-muted small d-flex justify-content-between align-items-center">
              <span>
                <i class="bi bi-pie-chart-fill text-success me-1"></i>
                คิดเป็น <strong>{{ metrics.assessment_rate }}%</strong> ของเคสทั้งหมด
              </span>
              <i class="bi bi-chevron-right text-muted small"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Missing / Unassessed Rate & Count -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card kpi-red overflow-hidden cursor-pointer" @click="quickFilterStatus('unassessed')">
          <div class="card-body p-4 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> ยังไม่ได้รับการประเมิน
              </span>
              <div class="kpi-icon-circle bg-danger text-white">
                <i class="bi bi-clipboard2-x-fill fs-5"></i>
              </div>
            </div>
            <h2 class="fw-bold text-danger mb-1">
              {{ formatNumber(metrics.unassessed_admissions) }}
              <span class="fs-6 fw-normal text-muted">เคส</span>
            </h2>
            <div class="text-muted small d-flex justify-content-between align-items-center">
              <span>
                <i class="bi bi-bell-fill text-danger me-1"></i>
                ขาดการประเมิน <strong>{{ unassessedRate }}%</strong> (ต้องติดตาม)
              </span>
              <i class="bi bi-chevron-right text-muted small"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Distinct High Alert Drugs -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 kpi-card kpi-amber overflow-hidden">
          <div class="card-body p-4 position-relative">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-capsule-pill me-1"></i> ชนิดยา HAD ที่ใช้
              </span>
              <div class="kpi-icon-circle bg-warning text-white">
                <i class="bi bi-capsule fs-5"></i>
              </div>
            </div>
            <h2 class="fw-bold text-dark mb-1">
              {{ formatNumber(metrics.distinct_drugs_count) }}
              <span class="fs-6 fw-normal text-muted">รายการ</span>
            </h2>
            <div class="text-muted small">
              <i class="bi bi-info-circle-fill text-warning me-1"></i>
              ยอดนิยม: {{ topDrugs.length > 0 ? topDrugs[0].drug_name : '-' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
      <!-- Monthly Trend Bar Chart -->
      <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-bar-chart-fill text-danger me-2"></i>แนวโน้มการประเมินยาความเสี่ยงสูงรายเดือน
              </h5>
              <small class="text-muted">เปรียบเทียบผู้ป่วยที่ได้รับยาความเสี่ยงสูง กับ จำนวนที่ได้รับการประเมิน [HAD]</small>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
              <i class="bi bi-clock-history me-1"></i> รายเดือน
            </span>
          </div>
          <div class="card-body p-4">
            <div style="height: 310px; position: relative;">
              <canvas ref="monthlyChartRef"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Compliance Doughnut Chart & Summary -->
      <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-pie-chart-fill text-primary me-2"></i>สัดส่วนความครอบคลุม (Compliance)
            </h5>
            <small class="text-muted">อัตราการบันทึกแบบประเมิน [HAD]</small>
          </div>
          <div class="card-body p-4 d-flex flex-column justify-content-center">
            <div style="height: 230px; position: relative;">
              <canvas ref="complianceChartRef"></canvas>
            </div>
            <div class="d-flex justify-content-around mt-3 text-center border-top pt-3">
              <div>
                <small class="text-muted d-block">ประเมินแล้ว</small>
                <span class="fw-bold text-success fs-5">{{ metrics.assessment_rate }}%</span>
              </div>
              <div class="border-start"></div>
              <div>
                <small class="text-muted d-block">ขาดการประเมิน</small>
                <span class="fw-bold text-danger fs-5">{{ unassessedRate }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Drugs Horizontal Bar Chart -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clipboard-pulse text-danger me-2"></i>อันดับยาความเสี่ยงสูงที่มีการสั่งใช้มากที่สุด (Top HAD Prescriptions)
          </h5>
          <small class="text-muted">จำนวนผู้ป่วยในที่ได้รับยาแต่ละรายการ และสัดส่วนการประเมิน</small>
        </div>
        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-bold">
          Top {{ Math.min(topDrugs.length, 10) }} รายการ
        </span>
      </div>
      <div class="card-body p-4">
        <div style="height: 280px; position: relative;">
          <canvas ref="topDrugsChartRef"></canvas>
        </div>
      </div>
    </div>

    <!-- Detailed Patients & Assessment Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
      <div class="card-header bg-white py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom">
        <div>
          <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-table me-2 text-primary"></i>รายการผู้ป่วยที่ได้รับยาความเสี่ยงสูง
            </h5>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 small">
              {{ filteredRecords.length }} รายการ
            </span>
          </div>
          <small class="text-muted">
            แสดงข้อมูลตามช่วงวันที่และเงื่อนไขที่เลือก (คลิกเลือกดูรายละเอียดฟอร์มการประเมินได้)
          </small>
        </div>

        <!-- Search Box & Filter Badges -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="input-group input-group-sm" style="max-width: 280px;">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input
              type="text"
              class="form-control border-start-0 bg-light"
              placeholder="ค้นหา AN, HN, ชื่อผู้ป่วย, ชื่อยา..."
              v-model="searchQuery"
            />
            <button
              v-if="searchQuery"
              class="btn btn-outline-secondary border-start-0 bg-light"
              type="button"
              @click="searchQuery = ''"
            >
              <i class="bi bi-x"></i>
            </button>
          </div>

          <button
            class="btn btn-sm btn-outline-secondary rounded-pill px-3"
            @click="quickFilterStatus('all')"
            :class="{ active: selectedStatus === 'all' }"
          >
            ทั้งหมด
          </button>
          <button
            class="btn btn-sm btn-outline-success rounded-pill px-3"
            @click="quickFilterStatus('assessed')"
            :class="{ active: selectedStatus === 'assessed' }"
          >
            🟢 ประเมินแล้ว
          </button>
          <button
            class="btn btn-sm btn-outline-danger rounded-pill px-3"
            @click="quickFilterStatus('unassessed')"
            :class="{ active: selectedStatus === 'unassessed' }"
          >
            🔴 ยังไม่ประเมิน
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="mt-3 text-muted">กำลังดึงข้อมูลยาความเสี่ยงสูงจาก HOSxP...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="alert alert-danger m-4 rounded-3 text-center">
        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
        <div>{{ error }}</div>
        <button class="btn btn-sm btn-outline-danger rounded-pill mt-3" @click="fetchData">
          ลองใหม่อีกครั้ง
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="paginatedRecords.length === 0" class="text-center py-5 text-muted">
        <i class="bi bi-clipboard2-check fs-1 text-secondary opacity-50 d-block mb-2"></i>
        <h6>ไม่พบข้อมูลตามเงื่อนไขที่เลือก</h6>
        <p class="small text-muted mb-0">ลองปรับเปลี่ยนตัวกรอง วันที่ หรือรายการยาเพื่อค้นหาข้อมูล</p>
      </div>

      <!-- Table View -->
      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-muted small border-bottom">
            <tr>
              <th class="ps-4" width="4%">#</th>
              <th width="10%">AN</th>
              <th width="9%">HN</th>
              <th width="16%">ชื่อ-นามสกุล ผู้ป่วย</th>
              <th width="11%">วันรับไว้ / จำหน่าย</th>
              <th width="11%">หอผู้ป่วย</th>
              <th width="18%">รายการยาความเสี่ยงสูง (HAD)</th>
              <th class="text-center" width="11%">สถานะการประเมิน</th>
              <th width="10%">วันที่ประเมิน</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in paginatedRecords"
              :key="idx"
              class="table-row-item"
              :class="{ 'row-unassessed': !item.is_assessed }"
            >
              <td class="ps-4 text-muted small fw-bold">
                {{ (currentPage - 1) * pageSize + idx + 1 }}
              </td>
              <td>
                <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                  {{ item.an }}
                </span>
              </td>
              <td>
                <span class="font-monospace text-secondary fw-semibold">{{ item.hn }}</span>
              </td>
              <td>
                <div class="fw-bold text-dark">{{ item.pt_name }}</div>
              </td>
              <td class="small">
                <div class="text-dark"><i class="bi bi-box-arrow-in-right text-success me-1"></i>{{ formatThaiDate(item.regdate) }}</div>
                <div v-if="item.dchdate" class="text-muted" title="วันที่ Discharge">
                  <i class="bi bi-box-arrow-right text-danger me-1"></i>{{ formatThaiDate(item.dchdate) }}
                </div>
                <div v-else class="text-warning-emphasis fw-semibold small" title="ยังคงพักรักษาตัวอยู่ในหอผู้ป่วย">
                  <i class="bi bi-hospital me-1 text-warning"></i>กำลังรักษา (ยังไม่ D/C)
                </div>
              </td>
              <td>
                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                  {{ item.ward_name || '-' }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center gap-1 flex-wrap">
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 fw-bold">
                    <i class="bi bi-capsule me-1"></i>{{ item.drug_name }}
                  </span>
                  <span v-if="item.drug_count > 1" class="badge bg-secondary rounded-pill small">
                    +{{ item.drug_count - 1 }}
                  </span>
                </div>
              </td>
              <td class="text-center">
                <button
                  v-if="item.is_assessed"
                  type="button"
                  class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1 shadow-xs hover-scale"
                  @click="openAssessmentModal(item)"
                  title="คลิกเพื่อดูรายละเอียดแบบประเมินข้างใน"
                >
                  <i class="bi bi-check-circle-fill"></i>
                  <span>ประเมินแล้ว</span>
                  <i class="bi bi-eye ms-1 small opacity-75"></i>
                </button>
                <span
                  v-else
                  class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold pulse-badge"
                  title="ยังไม่พบแบบประเมิน [HAD] ในระบบ"
                >
                  <i class="bi bi-x-circle-fill me-1"></i>ยังไม่ประเมิน
                </span>
              </td>
              <td class="small">
                <template v-if="item.is_assessed && item.assess_date">
                  <div class="fw-bold text-dark">
                    <i class="bi bi-calendar-check text-success me-1"></i>{{ formatThaiDate(item.assess_date) }}
                  </div>
                  <small class="text-muted text-truncate d-block" style="max-width: 140px;" :title="item.assessment_head_staff">
                    <i class="bi bi-person me-1"></i>{{ item.assessment_head_staff || '-' }}
                  </small>
                  <a
                    href="javascript:void(0)"
                    class="text-primary text-truncate d-block text-decoration-none fw-medium hover-underline"
                    style="max-width: 180px;"
                    :title="item.assessment_form_name"
                    @click="openAssessmentModal(item)"
                  >
                    <i class="bi bi-file-earmark-medical me-1"></i>{{ item.assessment_form_name }}
                  </a>
                </template>
                <template v-else>
                  <span class="text-muted">-</span>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="card-footer bg-white py-3 px-4 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2 text-muted small">
          <span>แสดง</span>
          <select
            class="form-select form-select-sm w-auto shadow-none border"
            v-model.number="pageSize"
            @change="currentPage = 1"
          >
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span>จากทั้งหมด {{ filteredRecords.length }} รายการ</span>
        </div>

        <nav aria-label="Page navigation" v-if="totalPages > 1">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="currentPage--" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
              </button>
            </li>
            <li
              v-for="p in visiblePages"
              :key="p"
              class="page-item"
              :class="{ active: currentPage === p }"
            >
              <button class="page-link" @click="currentPage = p">{{ p }}</button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="currentPage++" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Assessment Detail Modal -->
    <transition name="modal-fade">
      <div v-if="showModal" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3" @click.self="closeModal">
        <div class="modal-dialog-custom bg-white rounded-4 shadow-2xl overflow-hidden d-flex flex-column border">
          <!-- Modal Header -->
          <div class="modal-header-custom p-3 p-md-4 bg-light border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="modal-icon-badge bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-sm">
                <i class="bi bi-file-earmark-medical-fill fs-4"></i>
              </div>
              <div>
                <h5 class="fw-bold text-dark mb-0">รายละเอียดการประเมินยาความเสี่ยงสูง (HAD Assessment)</h5>
                <small class="text-muted">ข้อมูลการประเมินจากระบบแบบประเมินอิเล็กทรอนิกส์ HOSxP</small>
              </div>
            </div>
            <button type="button" class="btn-close shadow-none" @click="closeModal" aria-label="Close"></button>
          </div>

          <!-- Modal Body (Scrollable) -->
          <div class="modal-body-custom p-3 p-md-4 overflow-y-auto" style="max-height: 72vh;">
            <!-- Loading State -->
            <div v-if="loadingModal" class="py-5 text-center">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <div class="text-secondary fw-medium">กำลังดึงข้อมูลแบบประเมินจากระบบ...</div>
            </div>

            <!-- Error State -->
            <div v-else-if="modalError" class="alert alert-danger rounded-3 d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <span>{{ modalError }}</span>
            </div>

            <!-- Data State -->
            <div v-else-if="assessmentData">
              <!-- Patient Info Header Card -->
              <div class="card border-0 bg-primary-subtle rounded-3 p-3 mb-3">
                <div class="row g-2 align-items-center">
                  <div class="col-12 col-md-5">
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                        <i class="bi bi-person-fill"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-dark fs-6">{{ assessmentData.patient?.pt_name }}</div>
                        <small class="text-secondary font-monospace">HN: {{ assessmentData.patient?.hn }} | AN: {{ assessmentData.patient?.an }}</small>
                      </div>
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-muted small">หอผู้ป่วย</div>
                    <div class="fw-bold text-dark small">{{ assessmentData.patient?.ward_name }}</div>
                  </div>
                  <div class="col-6 col-md-2">
                    <div class="text-muted small">อายุ / เพศ</div>
                    <div class="fw-bold text-dark small">{{ assessmentData.patient?.age_y ? assessmentData.patient?.age_y + ' ปี' : '-' }} ({{ assessmentData.patient?.sex === '1' ? 'ชาย' : 'หญิง' }})</div>
                  </div>
                  <div class="col-12 col-md-2 text-md-end">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-bold">
                      <i class="bi bi-check2-circle me-1"></i>ประเมินแล้ว {{ assessmentData.total_assessments }} ครั้ง
                    </span>
                  </div>
                </div>
              </div>

              <!-- Multiple Rounds Tabs (if > 1 assessment) -->
              <div v-if="assessmentData.assessments && assessmentData.assessments.length > 1" class="mb-3">
                <label class="form-label small fw-bold text-secondary mb-2">เลือกรอบการประเมิน:</label>
                <div class="d-flex gap-2 flex-wrap">
                  <button
                    v-for="(asm, idx) in assessmentData.assessments"
                    :key="idx"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-medium"
                    :class="activeAssessmentIndex === idx ? 'btn-primary shadow-sm' : 'btn-outline-secondary'"
                    @click="activeAssessmentIndex = idx"
                  >
                    <i class="bi bi-clock-history me-1"></i>
                    รอบที่ {{ idx + 1 }} ({{ formatThaiDateTime(asm.datetime) }})
                  </button>
                </div>
              </div>

              <!-- Assessment Content -->
              <div v-if="currentAssessment" class="assessment-content">
                <!-- Form Name Banner -->
                <div class="p-3 bg-light rounded-3 border mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                  <div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 small fw-bold me-2">
                      แบบประเมิน HAD
                    </span>
                    <strong class="text-dark">{{ currentAssessment.form_name }}</strong>
                  </div>
                  <div class="text-muted small d-flex align-items-center gap-3">
                    <div><i class="bi bi-calendar-event me-1"></i>{{ formatThaiDateTime(currentAssessment.datetime) }}</div>
                    <div><i class="bi bi-person-badge me-1"></i>ผู้ประเมิน: <span class="fw-semibold text-dark">{{ currentAssessment.staff || '-' }}</span></div>
                  </div>
                </div>

                <!-- Section 1: Doctor Order & Staff Roles -->
                <div v-if="currentAssessment.data?.order" class="card border rounded-3 mb-3">
                  <div class="card-header bg-white py-2 px-3 fw-bold small text-secondary d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-prescription2 text-primary"></i>
                    <span>คำสั่งการรักษา & การบริหารยา (Medical Order & Verification)</span>
                  </div>
                  <div class="card-body p-3">
                    <div class="row g-3">
                      <!-- Order Text -->
                      <div class="col-12" v-if="currentAssessment.data.order.order_text">
                        <label class="small text-muted mb-1">คำสั่งใช้ยา / ขนาดยา:</label>
                        <div class="p-2 bg-light rounded-2 font-monospace fw-bold text-primary border">
                          {{ currentAssessment.data.order.order_text }}
                        </div>
                      </div>
                      <!-- Doctor & Date -->
                      <div class="col-12 col-sm-6 col-md-3">
                        <small class="text-muted d-block">แพทย์ผู้สั่ง:</small>
                        <strong class="text-dark small">{{ currentAssessment.data.order.doctor || '-' }}</strong>
                      </div>
                      <div class="col-12 col-sm-6 col-md-3">
                        <small class="text-muted d-block">พยาบาลผู้รับคำสั่ง:</small>
                        <strong class="text-dark small">{{ currentAssessment.data.order.order_receiver || '-' }}</strong>
                      </div>
                      <div class="col-12 col-sm-6 col-md-3">
                        <small class="text-muted d-block">ผู้เตรียมยา (Check 1):</small>
                        <strong class="text-dark small">{{ currentAssessment.data.order.drug_preparer || '-' }}</strong>
                      </div>
                      <div class="col-12 col-sm-6 col-md-3">
                        <small class="text-muted d-block">ผู้บริหารยา (Check 2):</small>
                        <strong class="text-dark small">{{ currentAssessment.data.order.drug_administrator || '-' }}</strong>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Section 2: Vital Signs & Monitoring Log -->
                <div v-if="currentAssessment.data?.monitoring && currentAssessment.data.monitoring.length > 0" class="card border rounded-3 mb-3">
                  <div class="card-header bg-white py-2 px-3 fw-bold small text-secondary d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-activity text-danger"></i>
                    <span>ตารางบันทึกการติดตามสัญญาณชีพ & การเฝ้าระวังอาการ (Vital Signs & Monitoring Log)</span>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0 small">
                      <thead class="bg-light text-muted">
                        <tr>
                          <th class="ps-3" width="8%">ครั้งที่</th>
                          <th width="15%">วันที่ / เวลา</th>
                          <th width="18%">ความดัน (BP)</th>
                          <th width="15%">ชีพจร (HR)</th>
                          <th width="22%">พยาบาลผู้ประเมิน</th>
                          <th width="22%">ผลติดตาม / หมายเหตุ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="m in currentAssessment.data.monitoring" :key="m.round">
                          <td class="ps-3 fw-bold text-secondary">{{ m.round }}</td>
                          <td>
                            <span v-if="m.time" class="badge bg-light text-dark border font-monospace">{{ m.time }}</span>
                            <span v-if="m.date" class="ms-1 text-muted small">({{ formatThaiDate(m.date) }})</span>
                          </td>
                          <td>
                            <strong class="text-primary font-monospace">{{ m.bp }}</strong>
                            <small v-if="m.bp !== '-'" class="text-muted ms-1">mmHg</small>
                          </td>
                          <td>
                            <strong class="text-danger font-monospace">{{ m.hr }}</strong>
                            <small v-if="m.hr !== '-'" class="text-muted ms-1">bpm</small>
                          </td>
                          <td>
                            <i class="bi bi-person me-1 text-muted"></i>{{ m.staff }}
                          </td>
                          <td>
                            <span v-if="m.remark && m.remark !== '-'" class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                              {{ m.remark }}
                            </span>
                            <span v-else class="text-muted">-</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Section 3: Safety Checklist -->
                <div v-if="currentAssessment.data?.checklists && currentAssessment.data.checklists.length > 0" class="card border rounded-3 mb-3">
                  <div class="card-header bg-white py-2 px-3 fw-bold small text-secondary d-flex align-items-center gap-2 border-bottom">
                    <i class="bi bi-shield-check text-success"></i>
                    <span>รายการตรวจสอบความปลอดภัยและการบริหารยา (HAD Safety Checklist)</span>
                  </div>
                  <div class="card-body p-3">
                    <div class="row g-2">
                      <div
                        v-for="chk in currentAssessment.data.checklists"
                        :key="chk.field"
                        class="col-12 col-md-6"
                      >
                        <div
                          class="p-2 rounded-2 border d-flex align-items-center gap-2 small"
                          :class="chk.checked ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-light text-muted'"
                        >
                          <i :class="chk.checked ? 'bi bi-check-circle-fill text-success fs-6' : 'bi bi-dash-circle text-muted fs-6'"></i>
                          <span :class="{ 'fw-semibold': chk.checked }">{{ chk.label }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Fallback / Notice if no structured data -->
                <div v-if="!currentAssessment.data" class="alert alert-warning rounded-3 small">
                  <i class="bi bi-info-circle me-1"></i>
                  แบบประเมินนี้ถูกบันทึกในระบบ HOSxP แล้ว แต่ยังไม่มีรายละเอียดปลีกย่อยในฟิลด์มาตรฐาน
                </div>
              </div>
            </div>

            <div v-else class="py-5 text-center text-muted">
              <i class="bi bi-folder2-open fs-1 opacity-50 mb-2 d-block"></i>
              <div>ไม่พบข้อมูลแบบประเมินสำหรับผู้ป่วยรายนี้</div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer-custom p-3 bg-light border-top d-flex justify-content-between align-items-center">
            <small class="text-muted">
              <i class="bi bi-info-circle me-1"></i>ระบบเชื่อมโยงฐานข้อมูลการประเมิน HOSxP อัตโนมัติ
            </small>
            <button type="button" class="btn btn-secondary rounded-pill px-4" @click="closeModal">
              ปิดหน้าต่าง
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios';
import Chart from 'chart.js/auto';

export default {
  name: 'HadDashboard',
  data() {
    return {
      loading: false,
      error: null,
      selectedFiscalYear: 'today',
      startDate: '',
      endDate: '',
      selectedStatus: 'all',
      selectedDrug: '',
      viewMode: 'detail',
      searchQuery: '',

      metrics: {
        total_records: 0,
        total_admissions: 0,
        total_patients: 0,
        assessed_admissions: 0,
        unassessed_admissions: 0,
        assessment_rate: 0,
        distinct_drugs_count: 0
      },
      monthlyTrends: [],
      topDrugs: [],
      drugOptions: [],
      records: [],

      // Pagination
      currentPage: 1,
      pageSize: 25,

      // Assessment Detail Modal
      showModal: false,
      loadingModal: false,
      modalError: null,
      selectedItem: null,
      assessmentData: null,
      activeAssessmentIndex: 0
    };
  },
  created() {
    this.monthlyChartInstance = null;
    this.complianceChartInstance = null;
    this.topDrugsChartInstance = null;
  },
  computed: {
    currentAssessment() {
      if (!this.assessmentData || !this.assessmentData.assessments) return null;
      return this.assessmentData.assessments[this.activeAssessmentIndex] || null;
    },
    unassessedRate() {
      const rate = 100 - (this.metrics.assessment_rate || 0);
      return Math.round(rate * 10) / 10;
    },
    filteredRecords() {
      if (!this.searchQuery) return this.records;
      const q = this.searchQuery.trim().toLowerCase();
      return this.records.filter(r => {
        return (
          (r.an && r.an.toLowerCase().includes(q)) ||
          (r.hn && r.hn.toLowerCase().includes(q)) ||
          (r.pt_name && r.pt_name.toLowerCase().includes(q)) ||
          (r.drug_name && r.drug_name.toLowerCase().includes(q)) ||
          (r.ward_name && r.ward_name.toLowerCase().includes(q)) ||
          (r.assessment_form_name && r.assessment_form_name.toLowerCase().includes(q))
        );
      });
    },
    totalPages() {
      return Math.ceil(this.filteredRecords.length / this.pageSize) || 1;
    },
    paginatedRecords() {
      const start = (this.currentPage - 1) * this.pageSize;
      return this.filteredRecords.slice(start, start + this.pageSize);
    },
    visiblePages() {
      const pages = [];
      const total = this.totalPages;
      const cur = this.currentPage;
      const start = Math.max(1, cur - 2);
      const end = Math.min(total, cur + 2);

      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    }
  },
  mounted() {
    this.onFiscalYearChange();
  },
  beforeUnmount() {
    this.destroyCharts();
  },
  methods: {
    getTodayStr() {
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    },
    getYesterdayStr() {
      const d = new Date();
      d.setDate(d.getDate() - 1);
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    },
    getMonthStartStr() {
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      return `${yyyy}-${mm}-01`;
    },
    onFiscalYearChange() {
      const todayStr = this.getTodayStr();
      if (this.selectedFiscalYear === 'today') {
        this.startDate = todayStr;
        this.endDate = todayStr;
      } else if (this.selectedFiscalYear === 'yesterday') {
        const yestStr = this.getYesterdayStr();
        this.startDate = yestStr;
        this.endDate = yestStr;
      } else if (this.selectedFiscalYear === 'this_month') {
        this.startDate = this.getMonthStartStr();
        this.endDate = todayStr;
      } else if (this.selectedFiscalYear === '2569') {
        this.startDate = '2025-10-01';
        this.endDate = '2026-09-30';
      } else if (this.selectedFiscalYear === '2568') {
        this.startDate = '2024-10-01';
        this.endDate = '2025-09-30';
      } else if (this.selectedFiscalYear === '2567') {
        this.startDate = '2023-10-01';
        this.endDate = '2024-09-30';
      }
      this.fetchData();
    },
    quickFilterStatus(status) {
      this.selectedStatus = status;
      this.fetchData();
    },
    setViewMode(mode) {
      if (this.viewMode !== mode) {
        this.viewMode = mode;
        this.fetchData();
      }
    },
    async fetchData() {
      this.loading = true;
      this.error = null;
      try {
        const params = {
          start_date: this.startDate,
          end_date: this.endDate,
          status: this.selectedStatus,
          drug_name: this.selectedDrug,
          view_mode: this.viewMode
        };

        const res = await axios.get('/api-hosxe/ipd/get_had_dashboard.php', { params });
        if (res.data && res.data.status === 'success') {
          const d = res.data.data;
          this.metrics = d.metrics || this.metrics;
          this.monthlyTrends = d.monthly_trends || [];
          this.topDrugs = d.top_drugs || [];
          this.drugOptions = d.drug_options || [];
          this.records = d.records || [];
          this.currentPage = 1;

          this.$nextTick(() => {
            this.renderCharts();
          });
        } else {
          this.error = res.data.message || 'ไม่สามารถโหลดข้อมูลได้';
        }
      } catch (err) {
        console.error('HAD fetch error:', err);
        this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล HOSxP';
      } finally {
        this.loading = false;
      }
    },

    renderCharts() {
      this.renderMonthlyChart();
      this.renderComplianceChart();
      this.renderTopDrugsChart();
    },

    destroyCharts() {
      if (this.$refs.monthlyChartRef) {
        const c = Chart.getChart(this.$refs.monthlyChartRef);
        if (c) c.destroy();
      }
      if (this.$refs.complianceChartRef) {
        const c = Chart.getChart(this.$refs.complianceChartRef);
        if (c) c.destroy();
      }
      if (this.$refs.topDrugsChartRef) {
        const c = Chart.getChart(this.$refs.topDrugsChartRef);
        if (c) c.destroy();
      }
      this.monthlyChartInstance = null;
      this.complianceChartInstance = null;
      this.topDrugsChartInstance = null;
    },

    renderMonthlyChart() {
      const canvas = this.$refs.monthlyChartRef;
      if (!canvas) return;
      const existing = Chart.getChart(canvas);
      if (existing) existing.destroy();
      this.monthlyChartInstance = null;

      const labels = this.monthlyTrends.map(m => m.label);
      const totalData = this.monthlyTrends.map(m => m.total_admissions);
      const assessedData = this.monthlyTrends.map(m => m.assessed_admissions);
      const unassessedData = this.monthlyTrends.map(m => m.unassessed_admissions);

      this.monthlyChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'ผู้ป่วยใช้ยา HAD ทั้งหมด',
              data: totalData,
              backgroundColor: 'rgba(99, 102, 241, 0.4)',
              borderColor: '#6366f1',
              borderWidth: 1.5,
              borderRadius: 6
            },
            {
              label: 'ประเมินแล้ว (Assessed)',
              data: assessedData,
              backgroundColor: '#10b981',
              borderRadius: 6
            },
            {
              label: 'ยังไม่ประเมิน (Missing)',
              data: unassessedData,
              backgroundColor: '#ef4444',
              borderRadius: 6
            }
          ]
        },
        options: {
          animation: false,
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { boxWidth: 14, font: { family: 'inherit', size: 12 } }
            },
            tooltip: {
              callbacks: {
                footer: (items) => {
                  const total = items.find(i => i.datasetIndex === 0)?.raw || 0;
                  const assessed = items.find(i => i.datasetIndex === 1)?.raw || 0;
                  if (total > 0) {
                    const rate = Math.round((assessed / total) * 100);
                    return `อัตราการประเมิน: ${rate}%`;
                  }
                  return '';
                }
              }
            }
          },
          scales: {
            x: { grid: { display: false } },
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    },

    renderComplianceChart() {
      const canvas = this.$refs.complianceChartRef;
      if (!canvas) return;
      const existing = Chart.getChart(canvas);
      if (existing) existing.destroy();
      this.complianceChartInstance = null;

      const assessed = this.metrics.assessed_admissions || 0;
      const unassessed = this.metrics.unassessed_admissions || 0;

      this.complianceChartInstance = new Chart(canvas, {
        type: 'doughnut',
        data: {
          labels: ['ประเมินแล้ว', 'ขาดการประเมิน'],
          datasets: [
            {
              data: [assessed, unassessed],
              backgroundColor: ['#10b981', '#ef4444'],
              hoverOffset: 6,
              borderWidth: 2,
              borderColor: '#ffffff'
            }
          ]
        },
        options: {
          animation: false,
          responsive: true,
          maintainAspectRatio: false,
          cutout: '72%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: { boxWidth: 14, font: { family: 'inherit', size: 12 } }
            }
          }
        }
      });
    },

    renderTopDrugsChart() {
      const canvas = this.$refs.topDrugsChartRef;
      if (!canvas) return;
      const existing = Chart.getChart(canvas);
      if (existing) existing.destroy();
      this.topDrugsChartInstance = null;

      const top10 = this.topDrugs.slice(0, 10);
      const labels = top10.map(d => d.drug_name);
      const assessedData = top10.map(d => parseInt(d.assessed_count || 0));
      const unassessedData = top10.map(d => parseInt(d.unassessed_count || 0));

      this.topDrugsChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'ประเมินแล้ว (Assessed)',
              data: assessedData,
              backgroundColor: '#10b981',
              borderRadius: 6
            },
            {
              label: 'ยังไม่ประเมิน (Missing)',
              data: unassessedData,
              backgroundColor: '#ef4444',
              borderRadius: 6
            }
          ]
        },
        options: {
          animation: false,
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { boxWidth: 14, font: { family: 'inherit', size: 12 } }
            }
          },
          scales: {
            x: {
              stacked: true,
              beginAtZero: true,
              ticks: { precision: 0 }
            },
            y: {
              stacked: true,
              grid: { display: false },
              ticks: { font: { family: 'inherit', size: 11 } }
            }
          }
        }
      });
    },

    formatNumber(num) {
      if (num === null || num === undefined) return '0';
      return Number(num).toLocaleString('th-TH');
    },

    formatThaiDate(dateStr) {
      if (!dateStr) return '-';
      const parts = dateStr.split('-');
      if (parts.length === 3) {
        const thaiMonths = [
          '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
          'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
        ];
        const day = parseInt(parts[2], 10);
        const month = thaiMonths[parseInt(parts[1], 10)] || parts[1];
        const year = (parseInt(parts[0], 10) + 543).toString().slice(-2);
        return `${day} ${month} ${year}`;
      }
      return dateStr;
    },

    exportToExcel() {
      try {
        const rows = this.filteredRecords.map((r, idx) => ({
          'ลำดับ': idx + 1,
          'AN': r.an,
          'HN': r.hn,
          'ชื่อ-นามสกุล': r.pt_name,
          'วันที่ Admit': r.regdate,
          'วันที่ Discharge': r.dchdate,
          'หอผู้ป่วย': r.ward_name,
          'รายการยาความเสี่ยงสูง': r.drug_name,
          'สถานะการประเมิน': r.is_assessed ? 'ประเมินแล้ว' : 'ยังไม่ได้รับการประเมิน',
          'วันที่ประเมิน': r.assess_date || '-',
          'ชื่อแบบประเมิน [HAD]': r.assessment_form_name || '-',
          'ผู้ประเมิน': r.assessment_head_staff || '-'
        }));

        const csvContent = "\uFEFF" + [
          Object.keys(rows[0]).join(","),
          ...rows.map(row => Object.values(row).map(val => `"${val}"`).join(","))
        ].join("\n");

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", `รายงานการใช้ยาความเสี่ยงสูง_HAD_${this.startDate}_${this.endDate}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (err) {
        console.error('Export error:', err);
        alert('เกิดข้อผิดพลาดในการส่งออกไฟล์');
      }
    },

    async openAssessmentModal(item) {
      if (!item || !item.is_assessed) return;
      this.selectedItem = item;
      this.showModal = true;
      this.loadingModal = true;
      this.modalError = null;
      this.assessmentData = null;
      this.activeAssessmentIndex = 0;
      try {
        const res = await axios.get('/api-hosxe/ipd/get_had_assessment_detail.php', {
          params: { an: item.an }
        });
        if (res.data && res.data.status === 'success') {
          this.assessmentData = res.data.data;
        } else {
          this.modalError = res.data.message || 'ไม่สามารถโหลดข้อมูลแบบประเมินได้';
        }
      } catch (err) {
        console.error('Error fetching assessment detail:', err);
        this.modalError = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล HOSxP';
      } finally {
        this.loadingModal = false;
      }
    },

    closeModal() {
      this.showModal = false;
      this.selectedItem = null;
      this.assessmentData = null;
      this.modalError = null;
    },

    formatThaiDateTime(dtStr) {
      if (!dtStr) return '-';
      const parts = dtStr.split(' ');
      const datePart = this.formatThaiDate(parts[0]);
      const timePart = parts[1] ? parts[1].slice(0, 5) + ' น.' : '';
      return `${datePart} ${timePart}`;
    }
  }
};
</script>

<style scoped>
.had-dashboard {
  font-family: 'Sarabun', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.kpi-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
}

.kpi-icon-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-purple {
  background-color: #8b5cf6 !important;
}

.text-purple {
  color: #7c3aed !important;
}

.bg-purple-subtle {
  background-color: #ede9fe !important;
}

.pulse-badge {
  animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
  0% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
  }
}

.spin-animation {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}

.table-row-item {
  transition: background-color 0.15s ease;
}

.row-unassessed {
  background-color: rgba(254, 242, 242, 0.5);
}

.row-unassessed:hover {
  background-color: rgba(254, 226, 226, 0.7) !important;
}

/* Assessment Detail Modal Styles */
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(5px);
  z-index: 1055;
}

.modal-dialog-custom {
  width: 100%;
  max-width: 960px;
  max-height: 90vh;
  animation: modalSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.hover-scale {
  transition: transform 0.15s ease;
}

.hover-scale:hover {
  transform: scale(1.04);
}

.hover-underline:hover {
  text-decoration: underline !important;
}

.shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>
