<template>
  <div class="inventory-reports-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-3 px-md-4">
      <!-- Breadcrumb & Top Bar -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 print-hide">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><router-link to="/home-backoffice">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-dashboard">ระบบบริหารคลังวัสดุ</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">7. รายงานและสถิติ</li>
            </ol>
          </nav>
          <div class="d-flex align-items-center gap-3">
            <h2 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i>
              <span>ระบบรายงานคลังพัสดุและเวชภัณฑ์</span>
            </h2>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
              INVS Reports
            </span>
          </div>
          <p class="text-muted small mb-0 mt-1">
            รายงานสรุปยอดคงคลัง, ประวัติการรับเข้า-เบิกจ่าย, ความเคลื่อนไหว และรายงานราชการประจำเดือน/ปี (PDF บันทึกข้อความ)
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <template v-if="['monthly_official', 'yearly_official'].includes(currentReportTab)">
            <button class="btn btn-outline-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="exportOfficialToExcel" :disabled="isLoadingOfficial || officialReport.details.length === 0">
              <i class="bi bi-file-earmark-excel-fill"></i>
              <span>ส่งออก Excel</span>
            </button>
            <button class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" @click="generateOfficialPDF(currentReportTab)" :disabled="isLoadingOfficial || isGeneratingPDF">
              <span v-if="isGeneratingPDF" class="spinner-border spinner-border-sm"></span>
              <i class="bi bi-file-earmark-pdf-fill" v-else></i>
              <span>ดาวน์โหลด PDF บันทึกข้อความ</span>
            </button>
          </template>
          <template v-else>
            <button class="btn btn-outline-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="exportToExcel" :disabled="isLoading || reportData.length === 0">
              <i class="bi bi-file-earmark-excel-fill"></i>
              <span>ส่งออก Excel</span>
            </button>
            <button class="btn btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="printReport" :disabled="isLoading">
              <i class="bi bi-printer-fill"></i>
              <span>พิมพ์รายงาน</span>
            </button>
          </template>
          <button class="btn btn-primary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="$emit('back-to-dashboard')">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
          </button>
        </div>
      </div>

      <!-- Quick Shortcut Cards for Official Reports (PDF) -->
      <div class="row g-3 mb-4 print-hide">
        <div class="col-md-6">
          <div 
            class="card border-0 shadow-sm rounded-4 official-shortcut-card p-3 h-100 cursor-pointer transition-all"
            :class="currentReportTab === 'monthly_official' ? 'bg-danger text-white active-card' : 'bg-white hover-shadow'"
            @click="switchReportTab('monthly_official')"
          >
            <div class="d-flex align-items-center gap-3">
              <div 
                class="rounded-4 p-3 d-flex align-items-center justify-content-center fs-2"
                :class="currentReportTab === 'monthly_official' ? 'bg-white text-danger' : 'bg-danger bg-opacity-10 text-danger'"
                style="width: 58px; height: 58px;"
              >
                <i class="bi bi-file-earmark-pdf-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <h5 class="fw-bold mb-0" :class="currentReportTab === 'monthly_official' ? 'text-white' : 'text-dark'">รายงานประจำเดือน (PDF)</h5>
                  <span class="badge rounded-pill px-2" :class="currentReportTab === 'monthly_official' ? 'bg-white text-danger' : 'bg-danger-subtle text-danger border border-danger-subtle'">
                    บันทึกข้อความราชการ
                  </span>
                </div>
                <small :class="currentReportTab === 'monthly_official' ? 'text-white-50' : 'text-muted'">
                  พิมพ์สรุปยอดรับ-จ่าย ประจำเดือน พร้อมตารางแนบแจกแจงรายหน่วยงานและสถานะคงคลัง
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div 
            class="card border-0 shadow-sm rounded-4 official-shortcut-card p-3 h-100 cursor-pointer transition-all"
            :class="currentReportTab === 'yearly_official' ? 'bg-primary text-white active-card' : 'bg-white hover-shadow'"
            @click="switchReportTab('yearly_official')"
          >
            <div class="d-flex align-items-center gap-3">
              <div 
                class="rounded-4 p-3 d-flex align-items-center justify-content-center fs-2"
                :class="currentReportTab === 'yearly_official' ? 'bg-white text-primary' : 'bg-primary bg-opacity-10 text-primary'"
                style="width: 58px; height: 58px;"
              >
                <i class="bi bi-calendar-check-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <h5 class="fw-bold mb-0" :class="currentReportTab === 'yearly_official' ? 'text-white' : 'text-dark'">รายงานประจำปี (PDF)</h5>
                  <span class="badge rounded-pill px-2" :class="currentReportTab === 'yearly_official' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary border border-primary-subtle'">
                    พิมพ์สรุปยอดรวมประจำปีปฏิทิน
                  </span>
                </div>
                <small :class="currentReportTab === 'yearly_official' ? 'text-white-50' : 'text-muted'">
                  พิมพ์สรุปยอดรวมประจำปีหรือตามช่วงเวลา พร้อมตารางแนบแจกแจงรายหน่วยงานและสถานะคงคลัง
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards (Print Hide) -->
      <div class="row g-3 mb-4 print-hide" v-if="summaryKpi && !['monthly_official', 'yearly_official'].includes(currentReportTab)">
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-primary border-4 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-bold">ยอดคงคลังรวม</span>
                <h4 class="fw-bold text-primary mb-0 mt-1">{{ formatNumber(summaryKpi.total_balance_qty) }}</h4>
                <small class="text-secondary">{{ formatCurrency(summaryKpi.total_value) }}</small>
              </div>
              <div class="kpi-icon-badge bg-primary-subtle text-primary">
                <i class="bi bi-box-seam-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-bold">รับเข้าคลังเดือนนี้</span>
                <h4 class="fw-bold text-success mb-0 mt-1">+{{ formatNumber(summaryKpi.month_in_qty) }}</h4>
                <small class="text-secondary">{{ formatCurrency(summaryKpi.month_in_value) }}</small>
              </div>
              <div class="kpi-icon-badge bg-success-subtle text-success">
                <i class="bi bi-arrow-down-left-circle-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-warning border-4 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-bold">เบิกจ่ายเดือนนี้</span>
                <h4 class="fw-bold text-warning mb-0 mt-1">-{{ formatNumber(summaryKpi.month_out_qty) }}</h4>
                <small class="text-secondary">{{ formatCurrency(summaryKpi.month_out_value) }}</small>
              </div>
              <div class="kpi-icon-badge bg-warning-subtle text-warning">
                <i class="bi bi-arrow-up-right-circle-fill"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-4 p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <span class="text-muted small fw-bold">ต่ำกว่าเกณฑ์ / ใกล้หมดอายุ</span>
                <h4 class="fw-bold text-danger mb-0 mt-1">{{ summaryKpi.low_stock_count }} / {{ summaryKpi.expiring_count }}</h4>
                <small class="text-secondary">รายการที่ต้องติดตาม</small>
              </div>
              <div class="kpi-icon-badge bg-danger-subtle text-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Tabs Bar (Print Hide) -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 print-hide">
        <div class="card-body p-2">
          <div class="nav nav-pills report-nav-pills gap-1 flex-nowrap overflow-x-auto p-1">
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'balance' }"
              @click="switchReportTab('balance')"
            >
              <i class="bi bi-box-seam"></i>
              <span>1. สรุปยอดคงเหลือพัสดุ</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'receipts' }"
              @click="switchReportTab('receipts')"
            >
              <i class="bi bi-arrow-down-left-circle"></i>
              <span>2. รายงานการรับเข้าคลัง</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'issues' }"
              @click="switchReportTab('issues')"
            >
              <i class="bi bi-arrow-up-right-circle"></i>
              <span>3. รายงานการเบิกจ่ายคลัง</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'movement' }"
              @click="switchReportTab('movement')"
            >
              <i class="bi bi-arrow-left-right"></i>
              <span>4. สรุปความเคลื่อนไหว (ยกมา-รับ-จ่าย-ยกไป)</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'expiring' }"
              @click="switchReportTab('expiring')"
            >
              <i class="bi bi-hourglass-split"></i>
              <span>5. พัสดุใกล้หมดอายุ / ล็อตคงคลัง</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'monthly_official' }"
              @click="switchReportTab('monthly_official')"
            >
              <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
              <span>6. รายงานประจำเดือน (PDF)</span>
            </button>
            <button
              class="nav-link text-nowrap rounded-pill d-flex align-items-center gap-2 px-3 py-2"
              :class="{ active: currentReportTab === 'yearly_official' }"
              @click="switchReportTab('yearly_official')"
            >
              <i class="bi bi-calendar-check-fill text-primary"></i>
              <span>7. รายงานประจำปี (PDF)</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- SECTION A: OFFICIAL MEMO & BREAKDOWN REPORTS (Tabs 6 & 7)                -->
      <!-- ========================================================================= -->
      <div v-if="['monthly_official', 'yearly_official'].includes(currentReportTab)">
        <div class="row g-4">
          <!-- Left Controls & Summary -->
          <div class="col-lg-4 print-hide">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="fw-bold mb-0 text-primary">
                    <i class="bi bi-gear-wide-connected me-2"></i>
                    {{ currentReportTab === 'monthly_official' ? 'ตั้งค่ารายงานประจำเดือน' : 'ตั้งค่ารายงานประจำปี' }}
                  </h5>
                  <span class="badge bg-light text-dark border">
                    {{ currentWarehouseName }}
                  </span>
                </div>

                <!-- Monthly Mode Input -->
                <div class="mb-3" v-if="currentReportTab === 'monthly_official'">
                  <label class="form-label fw-bold small text-secondary">เลือกเดือนและปีที่ต้องการออกรายงาน</label>
                  <input
                    type="month"
                    class="form-control form-control-lg"
                    v-model="selectedOfficialMonth"
                    @change="fetchOfficialReport"
                  />
                  <div class="form-text small">ระบบจะประมวลผลสรุปยอดตามเดือนปฏิทินที่เลือก</div>
                </div>

                <!-- Yearly Mode Input -->
                <div class="mb-3" v-else>
                  <label class="form-label fw-bold small text-secondary">เลือกช่วงวันที่เริ่มต้น</label>
                  <input type="date" class="form-control form-control-lg mb-2" v-model="officialStartDate" />
                  <label class="form-label fw-bold small text-secondary">เลือกช่วงวันที่สิ้นสุด</label>
                  <input type="date" class="form-control form-control-lg mb-3" v-model="officialEndDate" />
                  <button class="btn btn-outline-primary w-100 rounded-pill fw-bold" @click="fetchOfficialReport" :disabled="isLoadingOfficial">
                    <i class="bi bi-search me-1"></i> ประมวลผลช่วงวันที่
                  </button>
                </div>

                <!-- Warehouse Selector (if all warehouses or switchable) -->
                <div class="mb-4">
                  <label class="form-label fw-bold small text-secondary">คลังพัสดุ</label>
                  <select class="form-select" v-model="filters.warehouse_id" @change="fetchOfficialReport">
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                  </select>
                </div>

                <hr class="my-3 text-secondary" />

                <!-- Financial Summary Box -->
                <div v-if="isLoadingOfficial" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                  <div class="mt-2 text-muted small">กำลังคำนวณยอดทางบัญชี...</div>
                </div>
                <div v-else class="summary-box bg-light border p-3 rounded-4 mb-4">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">ยอดยกมา:</span>
                    <span class="fw-bold">{{ formatCurrency(officialReport.summary.forward_baht) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">ซื้อ / รับเข้า:</span>
                    <span class="fw-bold text-success">+{{ formatCurrency(officialReport.summary.in_baht) }}</span>
                  </div>
                  <div class="d-flex justify-content-between ps-3 border-bottom pb-2 mb-2">
                    <span class="text-muted fw-semibold">รวมรับ:</span>
                    <span class="fw-bold">{{ formatCurrency(officialReport.summary.total_baht) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">จ่ายออก / เบิก:</span>
                    <span class="fw-bold text-danger">-{{ formatCurrency(officialReport.summary.out_baht) }}</span>
                  </div>
                  <div class="d-flex justify-content-between pt-2 border-top">
                    <span class="fw-bold fs-5 text-dark">คงเหลือยกไป:</span>
                    <span class="fw-bold fs-5 text-primary">{{ formatCurrency(officialReport.summary.balance_baht) }}</span>
                  </div>
                </div>

                <!-- Signer Config Toggle -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="small text-muted fw-bold">ผู้ลงนามเอกสาร (3 ตำแหน่ง)</span>
                  <button class="btn btn-sm btn-link text-decoration-none p-0" @click="showSignerModal = true">
                    <i class="bi bi-pencil-square me-1"></i>แก้ไขรายชื่อ
                  </button>
                </div>

                <button
                  class="btn btn-danger w-100 btn-lg rounded-pill fw-bold shadow-sm mb-2"
                  @click="generateOfficialPDF(currentReportTab)"
                  :disabled="isLoadingOfficial || isGeneratingPDF"
                >
                  <span v-if="isGeneratingPDF" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-file-earmark-pdf-fill me-2" v-else></i>
                  สร้างไฟล์ PDF (3 หน้าสมบูรณ์)
                </button>
                <small class="text-muted text-center d-block">
                  รูปแบบบันทึกข้อความราชการ พร้อมตารางแนบแจกแจงรายหน่วยงานตามระเบียบ
                </small>
              </div>
            </div>
          </div>

          <!-- Right Live Document Preview -->
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
              <div class="card-header bg-white border-bottom p-3">
                <ul class="nav nav-pills gap-2">
                  <li class="nav-item">
                    <button 
                      class="nav-link rounded-pill py-1 px-3 small fw-bold" 
                      :class="{ active: officialPreviewTab === 'memo' }"
                      @click="officialPreviewTab = 'memo'"
                    >
                      <i class="bi bi-file-text me-1"></i> หน้า 1: บันทึกข้อความ
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link rounded-pill py-1 px-3 small fw-bold" 
                      :class="{ active: officialPreviewTab === 'landscape' }"
                      @click="officialPreviewTab = 'landscape'"
                    >
                      <i class="bi bi-table me-1"></i> หน้า 2: ตารางแนบแจกแจงรายหน่วยงาน (Landscape)
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link rounded-pill py-1 px-3 small fw-bold" 
                      :class="{ active: officialPreviewTab === 'status' }"
                      @click="officialPreviewTab = 'status'"
                    >
                      <i class="bi bi-shield-check me-1"></i> หน้า 3: รายงานสถานะคงคลัง
                    </button>
                  </li>
                </ul>
              </div>

              <div class="card-body p-4 p-md-5">
                <!-- Preview 1: Official Memo (บันทึกข้อความ) -->
                <div v-if="officialPreviewTab === 'memo'" class="memo-preview p-4 border rounded-3 bg-white shadow-xs mx-auto" style="max-width: 720px;">
                  <div class="d-flex align-items-start mb-3">
                    <img :src="krutImg" alt="Krut" height="70" class="me-3" />
                    <div class="flex-grow-1 text-center pe-5">
                      <h2 class="fw-bold mb-0" style="font-family: 'Sarabun', serif; letter-spacing: 2px;">บันทึกข้อความ</h2>
                    </div>
                  </div>

                  <div class="mb-2">
                    <strong>ส่วนราชการ</strong> <span class="ms-2 border-bottom border-dotted">{{ officialReport.signers.dept_name }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <div><strong>ที่</strong> <span class="ms-2 border-bottom border-dotted">{{ officialReport.signers.doc_no_prefix }}</span></div>
                    <div>
                      <strong>วันที่</strong> 
                      <span class="ms-2 border-bottom border-dotted">
                        {{ currentThaiDateFormatted }}
                      </span>
                    </div>
                  </div>
                  <div class="mb-2">
                    <strong>เรื่อง</strong> 
                    <span class="ms-2 border-bottom border-dotted">
                      <template v-if="currentReportTab === 'monthly_official'">
                        ส่งรายงานการเงินประจำเดือน{{ officialReport.period.thai_month }} {{ officialReport.period.year_be }}
                      </template>
                      <template v-else>
                        ส่งรายงานการเงินช่วงวันที่ {{ officialReport.period.formatted_start }} ถึง {{ officialReport.period.formatted_end }}
                      </template>
                    </span>
                  </div>
                  <div class="mb-4">
                    <strong>เรียน</strong> <span class="ms-2 border-bottom border-dotted">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
                  </div>

                  <p class="text-indent mb-4 text-justify" style="line-height: 1.8;">
                    {{ officialReport.signers.dept_name }} ได้สรุปยอดการรับ-จ่าย {{ currentWarehouseName }} 
                    <template v-if="currentReportTab === 'monthly_official'">
                      ประจำเดือน{{ officialReport.period.thai_month }} {{ officialReport.period.year_be }}
                    </template>
                    <template v-else>
                      ระหว่างวันที่ {{ officialReport.period.formatted_start }} ถึง {{ officialReport.period.formatted_end }}
                    </template>
                    เพื่อส่งการเงินจัดทำรายงาน ดังรายการต่อไปนี้
                  </p>

                  <div class="table-responsive mb-4">
                    <table class="table table-borderless mx-auto" style="max-width: 480px;">
                      <tbody>
                        <tr>
                          <td class="fw-bold">{{ currentWarehouseName }}</td>
                          <td class="text-end">ยกมา</td>
                          <td class="text-end fw-bold">{{ formatCurrency(officialReport.summary.forward_baht) }}</td>
                          <td>บาท</td>
                        </tr>
                        <tr>
                          <td></td>
                          <td class="text-end">ซื้อ</td>
                          <td class="text-end fw-bold text-success">{{ formatCurrency(officialReport.summary.in_baht) }}</td>
                          <td>บาท</td>
                        </tr>
                        <tr class="border-top">
                          <td></td>
                          <td class="text-end">รวม</td>
                          <td class="text-end fw-bold">{{ formatCurrency(officialReport.summary.total_baht) }}</td>
                          <td>บาท</td>
                        </tr>
                        <tr>
                          <td></td>
                          <td class="text-end">จ่าย</td>
                          <td class="text-end fw-bold text-danger">{{ formatCurrency(officialReport.summary.out_baht) }}</td>
                          <td>บาท</td>
                        </tr>
                        <tr class="border-top border-bottom border-2">
                          <td></td>
                          <td class="text-end fw-bold">คงเหลือ</td>
                          <td class="text-end fw-bold text-primary">{{ formatCurrency(officialReport.summary.balance_baht) }}</td>
                          <td>บาท</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <p class="mb-5">จึงเรียนมาเพื่อโปรดทราบ</p>

                  <!-- Signatures Row -->
                  <div class="row text-center mt-4">
                    <div class="col-4">
                      <div class="mb-4">...................................................</div>
                      <div class="fw-bold">({{ officialReport.signers.signer1_name }})</div>
                      <small class="text-muted d-block">{{ officialReport.signers.signer1_pos }}</small>
                      <small class="text-secondary">{{ officialReport.signers.signer1_role }}</small>
                    </div>
                    <div class="col-4">
                      <small class="text-muted d-block mb-1">ทราบ / เห็นควรอนุมัติ</small>
                      <div class="mb-3">...................................................</div>
                      <div class="fw-bold">({{ officialReport.signers.signer2_name }})</div>
                      <small class="text-muted d-block">{{ officialReport.signers.signer2_pos }}</small>
                      <small class="text-secondary">{{ officialReport.signers.signer2_title }}</small>
                    </div>
                    <div class="col-4">
                      <small class="text-muted d-block mb-1">อนุมัติ</small>
                      <div class="mb-3">...................................................</div>
                      <div class="fw-bold">({{ officialReport.signers.signer3_name }})</div>
                      <small class="text-muted d-block">{{ officialReport.signers.signer3_pos }}</small>
                      <small class="text-secondary">{{ officialReport.signers.signer3_title }}</small>
                    </div>
                  </div>
                </div>

                <!-- Preview 2: Landscape Detailed Breakdown Table -->
                <div v-else-if="officialPreviewTab === 'landscape'">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                      <h5 class="fw-bold mb-0 text-dark">ตารางแจกแจงการใช้วัสดุแยกตามหน่วยงาน</h5>
                      <small class="text-muted">
                        โรงพยาบาลชานุมาน | พบ {{ officialReport.details.length }} รายการพัสดุ และ {{ officialReport.departments.length }} หน่วยงานที่เบิก
                      </small>
                    </div>
                    <span class="badge bg-light text-secondary border">A4 Landscape</span>
                  </div>

                  <div class="table-responsive border rounded-3 bg-white" style="max-height: 480px; overflow-x: auto;">
                    <table class="table table-bordered table-sm align-middle text-center mb-0 small">
                      <thead class="table-light align-middle">
                        <tr>
                          <th rowspan="2" style="width: 40px;">ลำดับ</th>
                          <th rowspan="2" style="min-width: 140px;">รายการ</th>
                          <th rowspan="2" style="width: 60px;">หน่วยนับ</th>
                          <th rowspan="2" style="width: 80px;">ราคา/หน่วย</th>
                          <th colspan="2" class="bg-light">ยอดยกมา</th>
                          <th colspan="2" class="bg-success-subtle">รับเข้า</th>
                          <th rowspan="2" style="width: 60px;">รวม</th>
                          <th :colspan="officialReport.departments.length > 0 ? officialReport.departments.length : 1" class="bg-warning-subtle text-dark">
                            จ่ายแยกหน่วยงาน (จำนวน)
                          </th>
                          <th colspan="2" class="bg-danger-subtle">รวมจ่าย</th>
                          <th colspan="2" class="bg-primary-subtle">คงเหลือ</th>
                        </tr>
                        <tr>
                          <th>จำนวน</th>
                          <th>มูลค่า</th>
                          <th>จำนวน</th>
                          <th>มูลค่า</th>
                          <th v-if="officialReport.departments.length === 0">-</th>
                          <th v-for="d in officialReport.departments" :key="d" style="min-width: 90px; font-size: 11px;">
                            {{ d }}
                          </th>
                          <th>จำนวน</th>
                          <th>มูลค่า</th>
                          <th>จำนวน</th>
                          <th>มูลค่า</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="officialReport.details.length === 0">
                          <td :colspan="12 + (officialReport.departments.length || 1)" class="py-4 text-muted text-center">
                            ไม่พบรายการเคลื่อนไหวในช่วงเวลานี้
                          </td>
                        </tr>
                        <tr v-for="(it, idx) in officialReport.details" :key="it.id">
                          <td>{{ idx + 1 }}</td>
                          <td class="text-start fw-semibold">{{ it.name }}</td>
                          <td>{{ it.unit }}</td>
                          <td class="text-end">{{ formatNumber(it.price_per_unit) }}</td>
                          <td>{{ it.forward_qty || '-' }}</td>
                          <td class="text-end">{{ it.forward_baht > 0 ? formatNumber(it.forward_baht) : '-' }}</td>
                          <td class="text-success fw-bold">{{ it.in_qty || '-' }}</td>
                          <td class="text-end text-success">{{ it.in_baht > 0 ? formatNumber(it.in_baht) : '-' }}</td>
                          <td class="fw-bold">{{ it.total_qty }}</td>
                          <template v-if="officialReport.departments.length === 0">
                            <td>-</td>
                          </template>
                          <template v-else>
                            <td v-for="d in officialReport.departments" :key="d" :class="{'fw-bold text-danger': it.out_departments[d]}">
                              {{ it.out_departments[d] || '-' }}
                            </td>
                          </template>
                          <td class="text-danger fw-bold">{{ it.out_qty || '-' }}</td>
                          <td class="text-end text-danger">{{ it.out_baht > 0 ? formatNumber(it.out_baht) : '-' }}</td>
                          <td class="text-primary fw-bold">{{ it.balance_qty }}</td>
                          <td class="text-end text-primary fw-bold">{{ formatNumber(it.balance_baht) }}</td>
                        </tr>
                      </tbody>
                      <tfoot class="table-light fw-bold text-end">
                        <tr>
                          <td colspan="5" class="text-center">รวมทั้งสิ้น</td>
                          <td>{{ formatNumber(officialReport.summary.forward_baht) }}</td>
                          <td>-</td>
                          <td class="text-success">{{ formatNumber(officialReport.summary.in_baht) }}</td>
                          <td>-</td>
                          <td :colspan="officialReport.departments.length || 1" class="text-center">รวมจ่ายทั้งหมด</td>
                          <td class="text-danger">-</td>
                          <td class="text-danger">{{ formatNumber(officialReport.summary.out_baht) }}</td>
                          <td>-</td>
                          <td class="text-primary">{{ formatNumber(officialReport.summary.balance_baht) }}</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Preview 3: Warehouse Stock Status (Portrait) -->
                <div v-else-if="officialPreviewTab === 'status'" class="p-4 border rounded-3 bg-white shadow-xs mx-auto" style="max-width: 640px;">
                  <div class="text-center mb-4">
                    <h4 class="fw-bold mb-1">รายงานสถานะคงคลัง {{ currentWarehouseName }}</h4>
                    <p class="text-muted mb-0">โรงพยาบาลชานุมาน จังหวัดอำนาจเจริญ</p>
                    <small class="text-secondary fw-semibold">
                      <template v-if="currentReportTab === 'monthly_official'">
                        ประจำเดือน{{ officialReport.period.thai_month }} {{ officialReport.period.year_be }}
                      </template>
                      <template v-else>
                        ช่วงวันที่ {{ officialReport.period.formatted_start }} ถึง {{ officialReport.period.formatted_end }}
                      </template>
                    </small>
                  </div>

                  <table class="table table-bordered align-middle mb-5">
                    <thead class="table-light text-center">
                      <tr>
                        <th>รายการสถานะ</th>
                        <th style="width: 180px;">มูลค่า (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="ps-3">มูลค่าคงคลังยกมาจากงวดก่อนหน้า</td>
                        <td class="text-end fw-bold pe-3">{{ formatCurrency(officialReport.summary.forward_baht) }}</td>
                      </tr>
                      <tr>
                        <td class="ps-3 text-success">มูลค่ารับเข้าในงวด (จัดซื้อ)</td>
                        <td class="text-end fw-bold text-success pe-3">+{{ formatCurrency(officialReport.summary.in_baht) }}</td>
                      </tr>
                      <tr>
                        <td class="ps-3 text-danger">มูลค่าการเบิกใช้ (โรงพยาบาล)</td>
                        <td class="text-end fw-bold text-danger pe-3">-{{ formatCurrency(officialReport.summary.out_baht) }}</td>
                      </tr>
                      <tr class="table-primary fw-bold">
                        <td class="ps-3">มูลค่าคงคลังยกไปงวดถัดไป</td>
                        <td class="text-end pe-3 fs-5">{{ formatCurrency(officialReport.summary.balance_baht) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <div class="text-center my-4">
                    <div class="mb-4">
                      ลงชื่อ..........................................................ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ
                      <div class="fw-bold mt-1">({{ officialReport.signers.signer1_name }})</div>
                      <small class="text-muted">{{ officialReport.signers.signer1_pos }}</small>
                    </div>

                    <div class="mb-4">
                      <div class="mb-1 text-muted small">ทราบ</div>
                      ลงชื่อ..........................................................หัวหน้าพัสดุ / หัวหน้ากลุ่มงาน
                      <div class="fw-bold mt-1">({{ officialReport.signers.signer2_name }})</div>
                      <small class="text-muted">{{ officialReport.signers.signer2_pos }} {{ officialReport.signers.signer2_title }}</small>
                    </div>

                    <div class="mb-2">
                      <div class="mb-1 text-muted small">อนุมัติ / ไม่อนุมัติ</div>
                      ลงชื่อ.........................................................................
                      <div class="fw-bold mt-1">({{ officialReport.signers.signer3_name }})</div>
                      <small class="text-muted">{{ officialReport.signers.signer3_pos }}</small>
                      <div class="small text-secondary">{{ officialReport.signers.signer3_title }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- SECTION B: GENERAL INVENTORY REPORTS (Tabs 1-5)                           -->
      <!-- ========================================================================= -->
      <div v-else>
        <!-- Filter Controls Card (Print Hide) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 print-hide">
          <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-end">
              <!-- Warehouse Filter -->
              <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">คลังพัสดุ</label>
                <select class="form-select" v-model="filters.warehouse_id" @change="fetchReportData">
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                </select>
              </div>

              <!-- Date Filters -->
              <template v-if="['receipts', 'issues', 'movement'].includes(currentReportTab)">
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-secondary">วันที่เริ่มต้น</label>
                  <input type="date" class="form-control" v-model="filters.start_date" @change="fetchReportData" />
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-secondary">วันที่สิ้นสุด</label>
                  <input type="date" class="form-control" v-model="filters.end_date" @change="fetchReportData" />
                </div>
              </template>

              <!-- Category Filter -->
              <template v-if="['balance', 'movement'].includes(currentReportTab)">
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-secondary">หมวดหมู่พัสดุ</label>
                  <select class="form-select" v-model="filters.category_id" @change="fetchReportData">
                    <option value="">ทั้งหมด (ทุกหมวดหมู่)</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">[{{ c.code }}] {{ c.name }}</option>
                  </select>
                </div>
              </template>

              <!-- Stock Balance Status Filter -->
              <template v-if="currentReportTab === 'balance'">
                <div class="col-md-2">
                  <label class="form-label small fw-bold text-secondary">สถานะสินค้า</label>
                  <select class="form-select" v-model="filters.status" @change="fetchReportData">
                    <option value="all">ทั้งหมด</option>
                    <option value="in_stock">มีสต๊อก (> 0)</option>
                    <option value="low_stock">ต่ำกว่าเกณฑ์สั่งซื้อ</option>
                    <option value="out_of_stock">สินค้าหมด (0)</option>
                  </select>
                </div>
              </template>

              <!-- Expiring Days Filter -->
              <template v-if="currentReportTab === 'expiring'">
                <div class="col-md-3">
                  <label class="form-label small fw-bold text-secondary">เงื่อนไขวันหมดอายุ</label>
                  <select class="form-select" v-model="filters.days" @change="fetchReportData">
                    <option :value="30">หมดอายุภายใน 30 วัน</option>
                    <option :value="60">หมดอายุภายใน 60 วัน (2 เดือน)</option>
                    <option :value="90">หมดอายุภายใน 90 วัน (3 เดือน)</option>
                    <option :value="180">หมดอายุภายใน 180 วัน (6 เดือน)</option>
                    <option :value="-1">หมดอายุแล้ว (Expired)</option>
                  </select>
                </div>
              </template>

              <!-- Search Bar -->
              <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">ค้นหา</label>
                <div class="input-group">
                  <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0" placeholder="พิมพ์ชื่อ, รหัส, เลขที่..." v-model="filters.search" @input="debounceSearch" />
                  <button class="btn btn-outline-secondary" v-if="filters.search" @click="filters.search = ''; fetchReportData();">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>

              <!-- Quick Date Presets -->
              <div class="col-12 d-flex gap-2 flex-wrap" v-if="['receipts', 'issues', 'movement'].includes(currentReportTab)">
                <span class="small text-muted align-self-center me-1">เลือกด่วน:</span>
                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="setDatePreset('today')">วันนี้</button>
                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="setDatePreset('7days')">7 วันล่าสุด</button>
                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="setDatePreset('thisMonth')">เดือนนี้</button>
                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="setDatePreset('lastMonth')">เดือนที่แล้ว</button>
                <button class="btn btn-sm btn-light border rounded-pill px-3" @click="setDatePreset('thisYear')">ปีงบประมาณนี้</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-0">
            <!-- Loading Indicator -->
            <div v-if="isLoading" class="text-center py-5">
              <div class="spinner-border text-primary me-2" role="status"></div>
              <span class="text-muted">กำลังโหลดข้อมูลรายงาน...</span>
            </div>

            <!-- Empty State -->
            <div v-else-if="reportData.length === 0" class="text-center py-5">
              <i class="bi bi-clipboard-x fs-1 text-muted d-block mb-2"></i>
              <h5 class="fw-bold text-secondary">ไม่พบข้อมูลรายงาน</h5>
              <p class="text-muted small">ลองเปลี่ยนเงื่อนไขการค้นหา หรือเลือกช่วงวันที่อื่น</p>
            </div>

            <!-- TAB 1: BALANCE REPORT -->
            <div class="table-responsive" v-else-if="currentReportTab === 'balance'">
              <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 60px;">#</th>
                    <th>รหัสพัสดุ</th>
                    <th>ชื่อพัสดุ</th>
                    <th>หมวดหมู่</th>
                    <th class="text-center">หน่วยนับ</th>
                    <th class="text-end">จุดสั่งซื้อ</th>
                    <th class="text-end">ยอดคงคลัง</th>
                    <th class="text-end">ราคา/หน่วย</th>
                    <th class="text-end">มูลค่ารวม (บาท)</th>
                    <th class="text-center">สถานะ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in reportData" :key="r.id">
                    <td class="text-center text-muted small">{{ idx + 1 }}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">{{ r.item_code }}</span></td>
                    <td class="fw-bold text-dark">{{ r.item_name }}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary">{{ r.category_name || 'ทั่วไป' }}</span></td>
                    <td class="text-center text-muted">{{ r.unit_name || 'ชิ้น' }}</td>
                    <td class="text-end text-muted">{{ formatNumber(r.minimum_stock) }}</td>
                    <td class="text-end fw-bold" :class="Number(r.balance_qty) <= 0 ? 'text-danger' : (Number(r.balance_qty) <= Number(r.minimum_stock) ? 'text-warning' : 'text-success')">
                      {{ formatNumber(r.balance_qty) }}
                    </td>
                    <td class="text-end text-muted">{{ formatCurrency(r.unit_price) }}</td>
                    <td class="text-end fw-bold text-primary">{{ formatCurrency(r.total_value) }}</td>
                    <td class="text-center">
                      <span class="badge" :class="r.status_label === 'OUT_OF_STOCK' ? 'bg-danger' : (r.status_label === 'LOW_STOCK' ? 'bg-warning text-dark' : 'bg-success')">
                        {{ r.status_label === 'OUT_OF_STOCK' ? 'สินค้าหมด' : (r.status_label === 'LOW_STOCK' ? 'ต่ำกว่าเกณฑ์' : 'ปกติ') }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB 2: RECEIPTS REPORT -->
            <div class="table-responsive" v-else-if="currentReportTab === 'receipts'">
              <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>วันที่ตรวจรับ</th>
                    <th>เลขที่ใบรับ / PO</th>
                    <th>ผู้จัดจำหน่าย / บริษัท</th>
                    <th>รายการพัสดุ</th>
                    <th>Lot (Exp)</th>
                    <th class="text-end">จำนวน</th>
                    <th class="text-end">ราคา/หน่วย</th>
                    <th class="text-end">รวมเป็นเงิน</th>
                    <th>ผู้ตรวจรับ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in reportData" :key="r.id">
                    <td class="text-center text-muted small">{{ idx + 1 }}</td>
                    <td>{{ formatDate(r.receipt_date) }}</td>
                    <td>
                      <span class="fw-bold text-primary font-monospace">{{ r.receipt_no }}</span>
                      <small class="text-muted d-block" v-if="r.po_number">PO: {{ r.po_number }}</small>
                    </td>
                    <td>{{ r.vendor_name || '-' }}</td>
                    <td>
                      <span class="fw-bold text-dark">{{ r.item_name }}</span>
                      <small class="text-muted d-block">รหัส: {{ r.item_code }}</small>
                    </td>
                    <td class="small">
                      <span class="font-monospace text-secondary">{{ r.lot_number || '-' }}</span>
                      <small class="text-danger d-block" v-if="r.expiry_date">Exp: {{ formatDate(r.expiry_date) }}</small>
                    </td>
                    <td class="text-end fw-bold text-success">+{{ formatNumber(r.qty) }} {{ r.unit_name }}</td>
                    <td class="text-end text-muted">{{ formatCurrency(r.unit_cost) }}</td>
                    <td class="text-end fw-bold text-dark">{{ formatCurrency(r.total_price) }}</td>
                    <td class="small text-muted">{{ r.receiver_name || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB 3: ISSUES REPORT -->
            <div class="table-responsive" v-else-if="currentReportTab === 'issues'">
              <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>วันที่เบิก</th>
                    <th>เลขที่ใบเบิก</th>
                    <th>แผนก / หน่วยงานที่เบิก</th>
                    <th>ผู้ขอเบิก</th>
                    <th>รายการพัสดุ</th>
                    <th class="text-end">จำนวนเบิก</th>
                    <th class="text-end">ราคา/หน่วย</th>
                    <th class="text-end">รวมเป็นเงิน</th>
                    <th>ผู้จ่ายพัสดุ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in reportData" :key="r.id">
                    <td class="text-center text-muted small">{{ idx + 1 }}</td>
                    <td>{{ formatDate(r.issue_date) }}</td>
                    <td><span class="fw-bold text-danger font-monospace">{{ r.issue_no }}</span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary">{{ r.department_name || '-' }}</span></td>
                    <td>{{ r.requester_name || '-' }}</td>
                    <td>
                      <span class="fw-bold text-dark">{{ r.item_name }}</span>
                      <small class="text-muted d-block">รหัส: {{ r.item_code }}</small>
                    </td>
                    <td class="text-end fw-bold text-danger">-{{ formatNumber(r.qty) }} {{ r.unit_name }}</td>
                    <td class="text-end text-muted">{{ formatCurrency(r.unit_cost) }}</td>
                    <td class="text-end fw-bold text-dark">{{ formatCurrency(r.total_price) }}</td>
                    <td class="small text-muted">{{ r.issuer_name || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB 4: MOVEMENT REPORT -->
            <div class="table-responsive" v-else-if="currentReportTab === 'movement'">
              <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>รหัสพัสดุ</th>
                    <th>ชื่อพัสดุ</th>
                    <th>หมวดหมู่</th>
                    <th class="text-end">ยอดยกมา</th>
                    <th class="text-end text-success">ยอดรับเข้า (+)</th>
                    <th class="text-end text-danger">ยอดจ่ายออก (-)</th>
                    <th class="text-end text-primary">คงเหลือยกไป</th>
                    <th class="text-end">ราคา/หน่วย</th>
                    <th class="text-end">มูลค่ายกไป</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in reportData" :key="r.id">
                    <td class="text-center text-muted small">{{ idx + 1 }}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">{{ r.item_code }}</span></td>
                    <td class="fw-bold text-dark">{{ r.item_name }}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary">{{ r.category_name || 'ทั่วไป' }}</span></td>
                    <td class="text-end text-muted">{{ formatNumber(r.beginning_qty) }}</td>
                    <td class="text-end text-success fw-semibold">{{ Number(r.in_qty) > 0 ? '+' + formatNumber(r.in_qty) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold">{{ Number(r.out_qty) > 0 ? '-' + formatNumber(r.out_qty) : '-' }}</td>
                    <td class="text-end fw-bold text-primary">{{ formatNumber(r.ending_qty) }} {{ r.unit_name }}</td>
                    <td class="text-end text-muted">{{ formatCurrency(r.unit_price) }}</td>
                    <td class="text-end fw-bold text-dark">{{ formatCurrency(r.ending_value) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TAB 5: EXPIRING & AGING REPORT -->
            <div class="table-responsive" v-else-if="currentReportTab === 'expiring'">
              <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>รหัสพัสดุ</th>
                    <th>ชื่อพัสดุ</th>
                    <th>Lot Number</th>
                    <th>วันหมดอายุ</th>
                    <th class="text-center">สถานะอายุ</th>
                    <th class="text-end">จำนวนวันคงเหลือ</th>
                    <th class="text-end">ยอดคงเหลือ</th>
                    <th class="text-end">ราคา/หน่วย</th>
                    <th class="text-end">มูลค่ารวม</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r, idx) in reportData" :key="r.lot_id || idx">
                    <td class="text-center text-muted small">{{ idx + 1 }}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">{{ r.item_code }}</span></td>
                    <td class="fw-bold text-dark">{{ r.item_name }}</td>
                    <td><span class="font-monospace text-secondary">{{ r.lot_number || '-' }}</span></td>
                    <td><span class="fw-bold" :class="Number(r.days_remaining) <= 0 ? 'text-danger' : 'text-dark'">{{ formatDate(r.expiry_date) }}</span></td>
                    <td class="text-center">
                      <span class="badge" :class="Number(r.days_remaining) <= 0 ? 'bg-danger' : (Number(r.days_remaining) <= 30 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle')">
                        {{ r.status_text }}
                      </span>
                    </td>
                    <td class="text-end fw-bold" :class="Number(r.days_remaining) <= 0 ? 'text-danger' : 'text-warning'">
                      {{ Number(r.days_remaining) <= 0 ? 'หมดอายุแล้ว' : r.days_remaining + ' วัน' }}
                    </td>
                    <td class="text-end fw-bold">{{ formatNumber(r.balance_qty) }} {{ r.unit_name }}</td>
                    <td class="text-end text-muted">{{ formatCurrency(r.unit_cost) }}</td>
                    <td class="text-end fw-bold text-dark">{{ formatCurrency(r.total_value) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Signer Modal (For Official Reports) -->
      <div class="modal fade" id="signerModal" tabindex="-1" v-if="showSignerModal" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
              <h5 class="modal-title fw-bold text-primary">
                <i class="bi bi-pen me-2"></i>ปรับแต่งชื่อและตำแหน่งผู้ลงนามในเอกสาร
              </h5>
              <button type="button" class="btn-close" @click="showSignerModal = false"></button>
            </div>
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label small fw-bold">ชื่อส่วนราชการ</label>
                <input type="text" class="form-control" v-model="officialReport.signers.dept_name" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">เลขที่หนังสือ (คำขึ้นต้น)</label>
                <input type="text" class="form-control" v-model="officialReport.signers.doc_no_prefix" />
              </div>
              <hr />
              <h6 class="fw-bold text-secondary mb-2">1. ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ</h6>
              <div class="row g-2 mb-3">
                <div class="col-7"><input type="text" class="form-control form-control-sm" placeholder="ชื่อ-นามสกุล" v-model="officialReport.signers.signer1_name" /></div>
                <div class="col-5"><input type="text" class="form-control form-control-sm" placeholder="ตำแหน่ง" v-model="officialReport.signers.signer1_pos" /></div>
              </div>

              <h6 class="fw-bold text-secondary mb-2">2. หัวหน้าพัสดุ / หัวหน้ากลุ่มงาน</h6>
              <div class="row g-2 mb-3">
                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="ชื่อ-นามสกุล" v-model="officialReport.signers.signer2_name" /></div>
                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="ตำแหน่ง" v-model="officialReport.signers.signer2_pos" /></div>
                <div class="col-12"><input type="text" class="form-control form-control-sm" placeholder="ตำแหน่งบริหาร (เช่น หัวหน้ากลุ่มงานสุขภาพดิจิทัล)" v-model="officialReport.signers.signer2_title" /></div>
              </div>

              <h6 class="fw-bold text-secondary mb-2">3. ผู้อำนวยการโรงพยาบาล</h6>
              <div class="row g-2 mb-3">
                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="ชื่อ-นามสกุล" v-model="officialReport.signers.signer3_name" /></div>
                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="ตำแหน่งทางการแพทย์" v-model="officialReport.signers.signer3_pos" /></div>
                <div class="col-12"><input type="text" class="form-control form-control-sm" placeholder="ตำแหน่งบริหาร" v-model="officialReport.signers.signer3_title" /></div>
              </div>
            </div>
            <div class="modal-footer border-top">
              <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" @click="showSignerModal = false">
                บันทึกการตั้งค่า
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
import moment from 'moment';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Swal from 'sweetalert2';
import krutImgUrl from '../../../assets/krut.png';

moment.locale('th');

export default {
  name: 'InventoryReports',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      krutImg: krutImgUrl,
      currentReportTab: 'balance', // 'balance', 'receipts', 'issues', 'movement', 'expiring', 'monthly_official', 'yearly_official'
      isLoading: false,
      isLoadingOfficial: false,
      isGeneratingPDF: false,
      showSignerModal: false,
      officialPreviewTab: 'memo', // 'memo', 'landscape', 'status'
      
      warehouses: [],
      categories: [],
      
      filters: {
        warehouse_id: '',
        category_id: '',
        start_date: moment().startOf('month').format('YYYY-MM-DD'),
        end_date: moment().format('YYYY-MM-DD'),
        search: '',
        status: 'all',
        days: 60
      },

      // Official Reports state
      selectedOfficialMonth: moment().format('YYYY-MM'),
      officialStartDate: moment().startOf('year').format('YYYY-MM-DD'),
      officialEndDate: moment().format('YYYY-MM-DD'),
      officialReport: {
        warehouse: {},
        summary: {
          forward_baht: 0,
          in_baht: 0,
          total_baht: 0,
          out_baht: 0,
          balance_baht: 0
        },
        departments: [],
        details: [],
        period: {
          thai_month: '',
          year_be: '',
          formatted_start: '',
          formatted_end: ''
        },
        signers: {
          dept_name: 'กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน',
          doc_no_prefix: 'อจ.0033.314/',
          signer1_name: 'นายสุริยา  จันทรา',
          signer1_pos: 'นักวิชาการคอมพิวเตอร์',
          signer1_role: 'ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ',
          signer2_name: 'นายศราวุฒิ  แสนโท',
          signer2_pos: 'นักวิชาการคอมพิวเตอร์ชำนาญการ',
          signer2_title: 'หัวหน้ากลุ่มงานสุขภาพดิจิทัล',
          signer2_role: 'หัวหน้ากลุ่มงาน / หัวหน้าพัสดุ',
          signer3_name: 'นายธนากร  คนเพียร',
          signer3_pos: 'นายแพทย์ชำนาญการ รักษาการในตำแหน่ง',
          signer3_title: 'ผู้อำนวยการโรงพยาบาลชานุมาน'
        }
      },
      
      summaryKpi: null,
      reportData: [],
      reportSummary: null,
      searchTimeout: null
    };
  },
  computed: {
    activeReportTitle() {
      switch (this.currentReportTab) {
        case 'balance': return 'รายงานสรุปยอดคงเหลือพัสดุและเวชภัณฑ์';
        case 'receipts': return 'รายงานการตรวจรับเข้าคลังพัสดุ (Stock In)';
        case 'issues': return 'รายงานการเบิกจ่ายพัสดุ (Stock Out)';
        case 'movement': return 'รายงานสรุปความเคลื่อนไหวพัสดุ (Inventory Movement)';
        case 'expiring': return 'รายงานพัสดุใกล้หมดอายุและล็อตคงคลัง (Aging & Expiry)';
        case 'monthly_official': return 'รายงานประจำเดือน (PDF บันทึกข้อความและตารางแนบ)';
        case 'yearly_official': return 'รายงานประจำปี (PDF บันทึกข้อความและตารางแนบ)';
        default: return 'รายงานคลังพัสดุ';
      }
    },
    currentWarehouseName() {
      const wh = this.warehouses.find(w => String(w.id) === String(this.filters.warehouse_id));
      return wh ? wh.name : 'ทุกคลัง';
    },
    currentDateTimeFormatted() {
      return moment().format('DD/MM/YYYY HH:mm น.');
    },
    currentThaiDateFormatted() {
      const today = moment();
      const thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
      ];
      return `${today.date()} ${thaiMonths[today.month()]} ${today.year() + 543}`;
    }
  },
  async mounted() {
    await this.fetchInitialData();
    if (this.currentReportTab === 'monthly_official' || this.currentReportTab === 'yearly_official') {
      this.fetchOfficialReport();
    } else {
      this.fetchReportData();
    }
  },
  methods: {
    async fetchInitialData() {
      try {
        const [whRes, catRes, sumRes] = await Promise.all([
          axios.get('/api-digital/inventory/warehouses.php'),
          axios.get('/api-digital/inventory/categories.php'),
          axios.get(`/api-digital/inventory/reports.php?type=summary${this.warehouseId ? '&warehouse_id=' + this.warehouseId : ''}`)
        ]);

        if (whRes.data?.status === 'success') {
          this.warehouses = whRes.data.data;
          const target = this.warehouseId || localStorage.getItem('selected_warehouse_id') || (this.warehouses[0]?.id || '');
          this.filters.warehouse_id = target;
        }

        if (catRes.data?.status === 'success') {
          this.categories = catRes.data.data;
        }

        if (sumRes.data?.status === 'success') {
          this.summaryKpi = sumRes.data.summary;
        }
      } catch (err) {
        console.error('Failed to load initial report masters:', err);
      }
    },
    async fetchReportData() {
      if (['monthly_official', 'yearly_official'].includes(this.currentReportTab)) {
        this.fetchOfficialReport();
        return;
      }

      this.isLoading = true;
      try {
        const params = new URLSearchParams();
        params.append('type', this.currentReportTab);
        if (this.filters.warehouse_id) params.append('warehouse_id', this.filters.warehouse_id);
        if (this.filters.category_id) params.append('category_id', this.filters.category_id);
        if (this.filters.start_date) params.append('start_date', this.filters.start_date);
        if (this.filters.end_date) params.append('end_date', this.filters.end_date);
        if (this.filters.search) params.append('search', this.filters.search);
        if (this.filters.status) params.append('status', this.filters.status);
        if (this.filters.days !== undefined) params.append('days', this.filters.days);

        const res = await axios.get(`/api-digital/inventory/reports.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.reportData = res.data.data || [];
          this.reportSummary = res.data.summary || null;
        } else {
          this.reportData = [];
          this.reportSummary = null;
        }
      } catch (err) {
        console.error('Failed to fetch report data:', err);
        this.reportData = [];
        this.reportSummary = null;
      } finally {
        this.isLoading = false;
      }
    },
    async fetchOfficialReport() {
      this.isLoadingOfficial = true;
      try {
        const params = new URLSearchParams();
        params.append('warehouse_id', this.filters.warehouse_id || 1);
        params.append('report_mode', this.currentReportTab === 'yearly_official' ? 'yearly' : 'monthly');

        if (this.currentReportTab === 'monthly_official') {
          const parts = (this.selectedOfficialMonth || moment().format('YYYY-MM')).split('-');
          params.append('year', parts[0]);
          params.append('month', parts[1]);
        } else {
          params.append('start_date', this.officialStartDate);
          params.append('end_date', this.officialEndDate);
        }

        const res = await axios.get(`/api-digital/inventory/official_report.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.officialReport = {
            warehouse: res.data.warehouse || {},
            summary: res.data.summary || { forward_baht: 0, in_baht: 0, total_baht: 0, out_baht: 0, balance_baht: 0 },
            departments: res.data.departments || [],
            details: res.data.details || [],
            period: res.data.period || {},
            signers: res.data.signers || this.officialReport.signers
          };
        }
      } catch (err) {
        console.error('Failed to fetch official report data:', err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถคำนวณยอดรายงานทางการเงินได้', 'error');
      } finally {
        this.isLoadingOfficial = false;
      }
    },
    switchReportTab(tab) {
      if (this.currentReportTab === tab) return;
      this.currentReportTab = tab;
      if (['monthly_official', 'yearly_official'].includes(tab)) {
        this.fetchOfficialReport();
      } else {
        this.fetchReportData();
      }
    },
    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchReportData();
      }, 400);
    },
    setDatePreset(preset) {
      const today = moment();
      if (preset === 'today') {
        this.filters.start_date = today.format('YYYY-MM-DD');
        this.filters.end_date = today.format('YYYY-MM-DD');
      } else if (preset === '7days') {
        this.filters.start_date = moment().subtract(7, 'days').format('YYYY-MM-DD');
        this.filters.end_date = today.format('YYYY-MM-DD');
      } else if (preset === 'thisMonth') {
        this.filters.start_date = moment().startOf('month').format('YYYY-MM-DD');
        this.filters.end_date = moment().endOf('month').format('YYYY-MM-DD');
      } else if (preset === 'lastMonth') {
        this.filters.start_date = moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
        this.filters.end_date = moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
      } else if (preset === 'thisYear') {
        const currentMonth = moment().month();
        const fiscalYear = currentMonth >= 9 ? moment().year() : moment().year() - 1;
        this.filters.start_date = `${fiscalYear}-10-01`;
        this.filters.end_date = `${fiscalYear + 1}-09-30`;
      }
      this.fetchReportData();
    },
    formatNumber(num) {
      if (num === null || num === undefined || isNaN(num)) return '0';
      return Number(num).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    },
    formatCurrency(num) {
      if (num === null || num === undefined || isNaN(num)) return '0.00';
      return Number(num).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY');
    },
    printReport() {
      window.print();
    },
    async fetchFont(url) {
      return fetch(url)
        .then((res) => res.arrayBuffer())
        .then((buffer) => {
          let binary = '';
          const bytes = new Uint8Array(buffer);
          const len = bytes.byteLength;
          for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
          }
          return window.btoa(binary);
        });
    },
    async getBase64ImageFromURL(url) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = () => {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          const dataURL = canvas.toDataURL('image/png');
          resolve(dataURL);
        };
        img.onerror = (error) => reject(error);
        img.src = url;
      });
    },
    async generateOfficialPDF(mode = 'monthly_official') {
      this.isGeneratingPDF = true;
      Swal.fire({
        title: 'กำลังสร้างไฟล์ PDF...',
        text: 'กรุณารอสักครู่ ระบบกำลังจัดเตรียมหน้ากระดาษและแบบฟอร์มราชการ',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const doc = new jsPDF();
        const baseUrl = import.meta.env.BASE_URL.endsWith('/')
          ? import.meta.env.BASE_URL
          : import.meta.env.BASE_URL + '/';

        const fontNormal = await this.fetchFont(`${baseUrl}fonts/THSarabun.ttf`);
        const fontBold = await this.fetchFont(`${baseUrl}fonts/THSarabun Bold.ttf`);

        doc.addFileToVFS('THSarabun.ttf', fontNormal);
        doc.addFont('THSarabun.ttf', 'Sarabun', 'normal');
        doc.addFileToVFS('THSarabunBold.ttf', fontBold);
        doc.addFont('THSarabunBold.ttf', 'Sarabun', 'bold');
        doc.setFont('Sarabun', 'normal');

        const thaiMonths = [
          'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
          'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];

        // 1. Add Garuda Logo
        try {
          const logoData = await this.getBase64ImageFromURL(this.krutImg);
          doc.addImage(logoData, 'PNG', 20, 15, 16, 17);
        } catch (e) {
          console.warn('Could not load logo image for PDF:', e);
        }

        // ==========================================
        // PAGE 1: บันทึกข้อความ (Portrait)
        // ==========================================
        doc.setFontSize(30);
        doc.setFont('Sarabun', 'bold');
        doc.text('บันทึกข้อความ', 105, 25, { align: 'center' });

        doc.setFontSize(16);
        doc.setFont('Sarabun', 'bold');
        doc.text('ส่วนราชการ', 20, 40);
        doc.setFont('Sarabun', 'normal');
        doc.text(this.officialReport.signers.dept_name, 42, 40);

        doc.setFont('Sarabun', 'bold');
        doc.text('ที่', 20, 48);
        doc.setFont('Sarabun', 'normal');
        doc.text(this.officialReport.signers.doc_no_prefix, 26, 48);

        const reportDate = moment();
        doc.setFont('Sarabun', 'bold');
        doc.text('วันที่', 100, 48);
        doc.setFont('Sarabun', 'normal');
        doc.text(`${reportDate.date()} ${thaiMonths[reportDate.month()]} ${reportDate.year() + 543}`, 110, 48);

        doc.setFont('Sarabun', 'bold');
        doc.text('เรื่อง', 20, 56);
        doc.setFont('Sarabun', 'normal');
        const subjectText = mode === 'monthly_official'
          ? `ส่งรายงานการเงินประจำเดือน${this.officialReport.period.thai_month} ${this.officialReport.period.year_be}`
          : `ส่งรายงานการเงินช่วงวันที่ ${this.officialReport.period.formatted_start} ถึง ${this.officialReport.period.formatted_end}`;
        doc.text(subjectText, 32, 56);

        doc.setFont('Sarabun', 'bold');
        doc.text('เรียน', 20, 64);
        doc.setFont('Sarabun', 'normal');
        doc.text('ผู้อำนวยการโรงพยาบาลชานุมาน', 32, 64);

        // Paragraph 1
        const p1Opts = { maxWidth: 160, align: 'justify', lineHeightFactor: 1.5 };
        const indentStr = '          ';
        const p1Text = mode === 'monthly_official'
          ? `${indentStr}${this.officialReport.signers.dept_name} ได้สรุปยอดการรับ-จ่าย ${this.currentWarehouseName} ประจำเดือน${this.officialReport.period.thai_month} ${this.officialReport.period.year_be} เพื่อส่งการเงินจัดทำรายงาน ดังรายการต่อไปนี้`
          : `${indentStr}${this.officialReport.signers.dept_name} ได้สรุปยอดการรับ-จ่าย ${this.currentWarehouseName} ระหว่างวันที่ ${this.officialReport.period.formatted_start} ถึง ${this.officialReport.period.formatted_end} เพื่อส่งการเงินจัดทำรายงาน ดังรายการต่อไปนี้`;
        doc.text(p1Text, 20, 74, p1Opts);

        // List
        const listY = 95;
        const col1 = 50;
        const col2 = 110;
        const col3 = 155;
        const col4 = 175;

        doc.text(this.currentWarehouseName, col1, listY);
        doc.text('ยกมา', col2, listY);
        doc.text(this.formatCurrency(this.officialReport.summary.forward_baht), col3, listY, { align: 'right' });
        doc.text('บาท', col4, listY);

        doc.text('ซื้อ', col2, listY + 8);
        doc.text(this.formatCurrency(this.officialReport.summary.in_baht), col3, listY + 8, { align: 'right' });
        doc.text('บาท', col4, listY + 8);

        doc.text('รวม', col2, listY + 16);
        doc.text(this.formatCurrency(this.officialReport.summary.total_baht), col3, listY + 16, { align: 'right' });
        doc.text('บาท', col4, listY + 16);

        doc.text('จ่าย', col2, listY + 24);
        doc.text(this.formatCurrency(this.officialReport.summary.out_baht), col3, listY + 24, { align: 'right' });
        doc.text('บาท', col4, listY + 24);

        doc.text('คงเหลือ', col2, listY + 32);
        doc.setLineWidth(0.3);
        const wBal = doc.getTextWidth(this.formatCurrency(this.officialReport.summary.balance_baht));
        doc.line(col3 - wBal, listY + 33, col3, listY + 33);
        doc.text(this.formatCurrency(this.officialReport.summary.balance_baht), col3, listY + 32, { align: 'right' });
        doc.text('บาท', col4, listY + 32);

        // Paragraph 2
        doc.text('จึงเรียนมาเพื่อทราบ', 20, listY + 45);

        // Signature 1
        let signY = listY + 65;
        doc.text(`(${this.officialReport.signers.signer1_name})`, 140, signY, { align: 'center' });
        doc.line(125, signY + 1, 155, signY + 1);
        doc.text(this.officialReport.signers.signer1_pos, 140, signY + 8, { align: 'center' });

        // Approval 1
        signY += 20;
        doc.text('เห็นควรอนุมัติ', 40, signY, { align: 'center' });
        doc.text('ทราบ', 75, signY + 10, { align: 'center' });
        doc.line(70, signY + 11, 80, signY + 11);

        // Signature 2
        signY += 30;
        doc.text(`(${this.officialReport.signers.signer2_name})`, 75, signY, { align: 'center' });
        doc.line(55, signY + 1, 95, signY + 1);
        doc.text(this.officialReport.signers.signer2_pos, 75, signY + 8, { align: 'center' });
        if (this.officialReport.signers.signer2_title) {
          doc.text(this.officialReport.signers.signer2_title, 75, signY + 16, { align: 'center' });
        }

        // Approval 2 (Director)
        signY += 5;
        doc.text('อนุมัติ / ไม่อนุมัติ', 140, signY + 16, { align: 'center' });

        // Signature 3 (Director)
        signY += 40;
        doc.text(`(${this.officialReport.signers.signer3_name})`, 140, signY, { align: 'center' });
        doc.line(125, signY + 1, 155, signY + 1);
        doc.text(this.officialReport.signers.signer3_pos, 140, signY + 8, { align: 'center' });
        doc.text(this.officialReport.signers.signer3_title, 140, signY + 16, { align: 'center' });

        // ==========================================
        // PAGE 2: Detailed Table (Landscape)
        // ==========================================
        doc.addPage('a4', 'landscape');
        const departments = this.officialReport.departments || [];

        // Build Table Headers
        const head1 = [
          { content: 'ลำดับ', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 10 } },
          { content: 'บริษัท/ร้าน', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 20 } },
          { content: 'รายการ', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 35 } },
          { content: 'หน่วยนับ', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 12 } },
          { content: 'ราคา/หน่วย', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 15 } },
          { content: 'ยอดยกมา', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 12 } },
          { content: 'จำนวนเงิน', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 16 } },
          { content: 'รับเข้า', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 10 } },
          { content: 'จำนวนเงิน', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 16 } },
          { content: 'รวม', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 12 } }
        ];

        if (departments.length > 0) {
          head1.push({
            content: 'จ่าย',
            colSpan: departments.length,
            styles: { halign: 'center' }
          });
        } else {
          head1.push({
            content: 'จ่าย',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 12 }
          });
        }

        head1.push(
          { content: 'รวมจ่าย', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 14 } },
          { content: 'จำนวนเงินใช้ไป', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 18 } },
          { content: 'คงเหลือ', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 14 } },
          { content: 'จำนวนเงิน', rowSpan: 2, styles: { halign: 'center', valign: 'middle', cellWidth: 18 } }
        );

        const head2 = [];
        departments.forEach((d) => {
          head2.push({ content: d, styles: { halign: 'center', fontSize: 6.5 } });
        });

        const tableBody = [];
        let rIndex = 1;
        let sumForwardBaht = 0;
        let sumInBaht = 0;
        let sumOutBaht = 0;
        let sumBalBaht = 0;

        this.officialReport.details.forEach((item) => {
          const row = [
            rIndex++,
            item.vendor || '-',
            item.name,
            item.unit || '-',
            this.formatCurrency(item.price_per_unit),
            item.forward_qty || '-',
            item.forward_qty > 0 ? this.formatCurrency(item.forward_baht) : '-',
            item.in_qty || '-',
            item.in_qty > 0 ? this.formatCurrency(item.in_baht) : '-',
            item.total_qty || '-'
          ];

          if (departments.length > 0) {
            departments.forEach((d) => {
              const q = item.out_departments[d] || 0;
              row.push(q > 0 ? q : '-');
            });
          } else {
            row.push('-');
          }

          row.push(
            item.out_qty || '-',
            item.out_qty > 0 ? this.formatCurrency(item.out_baht) : '-',
            item.balance_qty || '-',
            this.formatCurrency(item.balance_baht)
          );

          sumForwardBaht += Number(item.forward_baht || 0);
          sumInBaht += Number(item.in_baht || 0);
          sumOutBaht += Number(item.out_baht || 0);
          sumBalBaht += Number(item.balance_baht || 0);

          tableBody.push(row);
        });

        // Summary footer row
        const footRow = [
          { content: 'รวม', colSpan: 6, styles: { halign: 'center', fontStyle: 'bold' } },
          { content: this.formatCurrency(sumForwardBaht), styles: { halign: 'right', fontStyle: 'bold' } },
          { content: '-', styles: { halign: 'center' } },
          { content: this.formatCurrency(sumInBaht), styles: { halign: 'right', fontStyle: 'bold' } },
          { content: '-', styles: { halign: 'center' } }
        ];

        let deptSumSpan = departments.length > 0 ? departments.length : 1;
        footRow.push({
          content: 'รวมจ่ายทั้งหมด',
          colSpan: deptSumSpan + 1,
          styles: { halign: 'center', fontStyle: 'bold' }
        });

        footRow.push(
          { content: this.formatCurrency(sumOutBaht), styles: { halign: 'right', fontStyle: 'bold' } },
          { content: '-', styles: { halign: 'center' } },
          { content: this.formatCurrency(sumBalBaht), styles: { halign: 'right', fontStyle: 'bold' } }
        );
        tableBody.push(footRow);

        autoTable(doc, {
          startY: 28,
          head: [head1, head2],
          body: tableBody,
          theme: 'grid',
          styles: {
            font: 'Sarabun',
            fontSize: 7.5,
            lineWidth: 0.1,
            lineColor: [0, 0, 0],
            textColor: [0, 0, 0],
            cellPadding: 1
          },
          headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            fontStyle: 'bold',
            halign: 'center',
            fontSize: 7.5
          },
          columnStyles: {
            0: { halign: 'center', cellWidth: 8 },
            1: { cellWidth: 20 },
            2: { cellWidth: 32 },
            [4]: { halign: 'right' },
            [6]: { halign: 'right' },
            [8]: { halign: 'right' },
            [12 + departments.length]: { halign: 'right', cellWidth: 16 },
            [14 + departments.length]: { halign: 'right', cellWidth: 16 }
          },
          margin: { top: 30, left: 5, right: 5, bottom: 10 },
          didDrawPage: () => {
            doc.setFontSize(16);
            doc.setFont('Sarabun', 'bold');
            const page2Title = mode === 'monthly_official'
              ? `การใช้วัสดุ ${this.currentWarehouseName} ประจำเดือน ${this.officialReport.period.thai_month} ${this.officialReport.period.year_be}`
              : `การใช้วัสดุ ${this.currentWarehouseName} ช่วงวันที่ ${this.officialReport.period.formatted_start} ถึง ${this.officialReport.period.formatted_end}`;
            doc.text(page2Title, 148, 15, { align: 'center' });
            doc.text('โรงพยาบาลชานุมาน อ.ชานุมาน จ.อำนาจเจริญ', 148, 22, { align: 'center' });
          }
        });

        // Small bottom summary box
        let finalY = doc.lastAutoTable.finalY + 10;
        if (finalY > 175) {
          doc.addPage('a4', 'landscape');
          finalY = 30;
        }
        doc.setFontSize(11);
        doc.setFont('Sarabun', 'normal');
        doc.text('ยอดยกมา', 20, finalY);
        doc.text(this.formatCurrency(sumForwardBaht), 60, finalY, { align: 'right' });
        doc.text('บาท', 65, finalY);

        doc.text('รวม', 20, finalY + 6);
        doc.text(this.formatCurrency(sumForwardBaht + sumInBaht), 60, finalY + 6, { align: 'right' });
        doc.text('บาท', 65, finalY + 6);

        doc.text('จ่าย', 20, finalY + 12);
        doc.text(this.formatCurrency(sumOutBaht), 60, finalY + 12, { align: 'right' });
        doc.text('บาท', 65, finalY + 12);
        doc.line(30, finalY + 13, 60, finalY + 13);

        doc.text('คงเหลือ', 20, finalY + 18);
        doc.text(this.formatCurrency(sumBalBaht), 60, finalY + 18, { align: 'right' });
        doc.text('บาท', 65, finalY + 18);
        doc.line(30, finalY + 19, 60, finalY + 19);
        doc.line(30, finalY + 20, 60, finalY + 20);

        // ==========================================
        // PAGE 3: Final Summary & Signatures (Portrait)
        // ==========================================
        doc.addPage('a4', 'portrait');

        doc.setFontSize(16);
        doc.setFont('Sarabun', 'normal');
        doc.text(`รายงานสถานะคงคลัง ${this.currentWarehouseName} โรงพยาบาลชานุมาน`, 105, 20, { align: 'center' });
        const page3Date = mode === 'monthly_official'
          ? `ประจำเดือน ${this.officialReport.period.thai_month}  ${this.officialReport.period.year_be}`
          : `ช่วงวันที่ ${this.officialReport.period.formatted_start} ถึง ${this.officialReport.period.formatted_end}`;
        doc.text(page3Date, 105, 28, { align: 'center' });

        const summaryHead = [
          [
            { content: '', styles: { halign: 'center', fillColor: [255, 255, 255] } },
            { content: this.currentWarehouseName, styles: { halign: 'center', fontStyle: 'normal', fillColor: [255, 255, 255] } }
          ]
        ];

        const summaryBody = [
          ['มูลค่าคงคลังยกมาจากงวดที่แล้ว', { content: this.formatCurrency(sumForwardBaht), styles: { halign: 'right' } }],
          ['มูลค่ารับเข้าในงวด (จัดซื้อ)', { content: this.formatCurrency(sumInBaht), styles: { halign: 'right' } }],
          ['มูลค่าการเบิกใช้(รพ.)', { content: this.formatCurrency(sumOutBaht), styles: { halign: 'right' } }],
          ['มูลค่าคงคลังยกไปงวดถัดไป', { content: this.formatCurrency(sumBalBaht), styles: { halign: 'right' } }]
        ];

        autoTable(doc, {
          startY: 40,
          head: summaryHead,
          body: summaryBody,
          theme: 'grid',
          styles: {
            font: 'Sarabun',
            fontSize: 14,
            lineWidth: 0.1,
            lineColor: [0, 0, 0],
            textColor: [0, 0, 0],
            cellPadding: 3
          },
          headStyles: {
            fillColor: [255, 255, 255],
            textColor: [0, 0, 0]
          },
          columnStyles: {
            0: { cellWidth: 80 },
            1: { cellWidth: 60 }
          },
          margin: { left: 35 }
        });

        // Signatures Page 3
        let sumSignY = doc.lastAutoTable.finalY + 30;
        doc.setFontSize(14);

        // --- User ---
        doc.text('ลงชื่อ..........................................................ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ', 105, sumSignY, { align: 'center' });
        doc.text(`(${this.officialReport.signers.signer1_name})`, 105, sumSignY + 10, { align: 'center' });
        doc.text(this.officialReport.signers.signer1_pos, 105, sumSignY + 18, { align: 'center' });

        // --- Manager ---
        sumSignY += 50;
        doc.text('ทราบ', 105, sumSignY, { align: 'center' });
        doc.text('ลงชื่อ..........................................................หัวหน้าพัสดุ / หัวหน้ากลุ่มงาน', 105, sumSignY + 12, { align: 'center' });
        doc.text(`(${this.officialReport.signers.signer2_name})`, 105, sumSignY + 22, { align: 'center' });
        doc.text(this.officialReport.signers.signer2_pos, 105, sumSignY + 30, { align: 'center' });
        if (this.officialReport.signers.signer2_title) {
          doc.text(this.officialReport.signers.signer2_title, 105, sumSignY + 38, { align: 'center' });
        }

        // --- Director ---
        sumSignY += 60;
        doc.text('อนุมัติ / ไม่อนุมัติ', 105, sumSignY, { align: 'center' });
        doc.text('ลงชื่อ.........................................................................', 105, sumSignY + 12, { align: 'center' });
        doc.text(`(${this.officialReport.signers.signer3_name})`, 105, sumSignY + 22, { align: 'center' });
        doc.text(this.officialReport.signers.signer3_pos, 105, sumSignY + 30, { align: 'center' });
        doc.text(this.officialReport.signers.signer3_title, 105, sumSignY + 38, { align: 'center' });

        const fileName = mode === 'monthly_official'
          ? `monthly-inventory-report-${this.selectedOfficialMonth}.pdf`
          : `yearly-inventory-report-${this.officialStartDate}-to-${this.officialEndDate}.pdf`;
        doc.save(fileName);
        Swal.close();
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
      } finally {
        this.isGeneratingPDF = false;
      }
    },
    exportOfficialToExcel() {
      if (!this.officialReport.details || this.officialReport.details.length === 0) return;

      const departments = this.officialReport.departments || [];
      const exportRows = this.officialReport.details.map((it, idx) => {
        const row = {
          'ลำดับ': idx + 1,
          'บริษัท/ร้าน': it.vendor || '-',
          'รหัสพัสดุ': it.item_code,
          'ชื่อพัสดุ': it.name,
          'หน่วยนับ': it.unit,
          'ราคา/หน่วย': it.price_per_unit,
          'ยอดยกมา (จำนวน)': it.forward_qty,
          'ยอดยกมา (มูลค่า)': it.forward_baht,
          'รับเข้า (จำนวน)': it.in_qty,
          'รับเข้า (มูลค่า)': it.in_baht,
          'รวมรับ (จำนวน)': it.total_qty
        };

        departments.forEach((d) => {
          row[`จ่าย - ${d}`] = it.out_departments[d] || 0;
        });

        row['รวมจ่าย (จำนวน)'] = it.out_qty;
        row['รวมจ่าย (มูลค่า)'] = it.out_baht;
        row['คงเหลือยกไป (จำนวน)'] = it.balance_qty;
        row['คงเหลือยกไป (มูลค่า)'] = it.balance_baht;

        return row;
      });

      const worksheet = XLSX.utils.json_to_sheet(exportRows);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Detailed_Report');

      const fileName = `${this.activeReportTitle.replace(/[^a-zA-Z0-9ก-๙]/g, '_')}_${moment().format('YYYYMMDD_HHmm')}.xlsx`;
      XLSX.writeFile(workbook, fileName);
    },
    exportToExcel() {
      if (!this.reportData || this.reportData.length === 0) return;

      let exportRows = [];
      const title = this.activeReportTitle;

      if (this.currentReportTab === 'balance') {
        exportRows = this.reportData.map((r, i) => ({
          'ลำดับ': i + 1,
          'รหัสพัสดุ': r.item_code,
          'ชื่อพัสดุ': r.item_name,
          'หมวดหมู่': r.category_name || 'ทั่วไป',
          'หน่วยนับ': r.unit_name || 'ชิ้น',
          'จุดสั่งซื้อ': r.minimum_stock,
          'ยอดคงคลัง': r.balance_qty,
          'ราคาต่อหน่วย': r.unit_price,
          'มูลค่าคงคลัง (บาท)': r.total_value,
          'สถานะ': r.status_label === 'OUT_OF_STOCK' ? 'สินค้าหมด' : (r.status_label === 'LOW_STOCK' ? 'ต่ำกว่าเกณฑ์' : 'ปกติ'),
          'คลัง': r.warehouse_name || '-'
        }));
      } else if (this.currentReportTab === 'receipts') {
        exportRows = this.reportData.map((r, i) => ({
          'ลำดับ': i + 1,
          'วันที่ตรวจรับ': this.formatDate(r.receipt_date),
          'เลขที่ใบตรวจรับ': r.receipt_no,
          'เลขที่ PO': r.po_number || '-',
          'เลขที่ใบส่งของ': r.invoice_no || '-',
          'ผู้จัดจำหน่าย/บริษัท': r.vendor_name || '-',
          'รหัสพัสดุ': r.item_code,
          'ชื่อพัสดุ': r.item_name,
          'หน่วยนับ': r.unit_name || 'ชิ้น',
          'Lot Number': r.lot_number || '-',
          'วันหมดอายุ': this.formatDate(r.expiry_date),
          'จำนวนรับ': r.qty,
          'ราคาต่อหน่วย': r.unit_cost,
          'รวมเป็นเงิน (บาท)': r.total_price,
          'ผู้ตรวจรับ': r.receiver_name || '-',
          'หมายเหตุ': r.remark || '-'
        }));
      } else if (this.currentReportTab === 'issues') {
        exportRows = this.reportData.map((r, i) => ({
          'ลำดับ': i + 1,
          'วันที่เบิก': this.formatDate(r.issue_date),
          'เลขที่ใบเบิก': r.issue_no,
          'แผนก/หน่วยงาน': r.department_name || '-',
          'ผู้ขอเบิก': r.requester_name || '-',
          'รหัสพัสดุ': r.item_code,
          'ชื่อพัสดุ': r.item_name,
          'หน่วยนับ': r.unit_name || 'ชิ้น',
          'Lot Number': r.lot_number || '-',
          'จำนวนเบิก': r.qty,
          'ราคาต่อหน่วย': r.unit_cost,
          'รวมเป็นเงิน (บาท)': r.total_price,
          'ผู้จ่ายพัสดุ': r.issuer_name || '-',
          'หมายเหตุ': r.remark || '-'
        }));
      } else if (this.currentReportTab === 'movement') {
        exportRows = this.reportData.map((r, i) => ({
          'ลำดับ': i + 1,
          'รหัสพัสดุ': r.item_code,
          'ชื่อพัสดุ': r.item_name,
          'หมวดหมู่': r.category_name || 'ทั่วไป',
          'หน่วยนับ': r.unit_name || 'ชิ้น',
          'ยอดยกมา': r.beginning_qty,
          'ยอดรับเข้า (+)': r.in_qty,
          'ยอดจ่ายออก (-)': r.out_qty,
          'คงเหลือยกไป': r.ending_qty,
          'ราคาต่อหน่วย': r.unit_price,
          'มูลค่ายกไป (บาท)': r.ending_value
        }));
      } else if (this.currentReportTab === 'expiring') {
        exportRows = this.reportData.map((r, i) => ({
          'ลำดับ': i + 1,
          'รหัสพัสดุ': r.item_code,
          'ชื่อพัสดุ': r.item_name,
          'คลัง': r.warehouse_name || '-',
          'Lot Number': r.lot_number,
          'วันหมดอายุ': this.formatDate(r.expiry_date),
          'จำนวนวันคงเหลือ': r.days_remaining,
          'สถานะอายุ': r.status_text,
          'ยอดคงเหลือ': r.balance_qty,
          'ราคาต่อหน่วย': r.unit_cost,
          'มูลค่ารวม (บาท)': r.total_value
        }));
      }

      const worksheet = XLSX.utils.json_to_sheet(exportRows);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Report');

      const fileName = `${title.replace(/[^a-zA-Z0-9ก-๙]/g, '_')}_${moment().format('YYYYMMDD_HHmm')}.xlsx`;
      XLSX.writeFile(workbook, fileName);
    }
  }
};
</script>

<style scoped>
.report-nav-pills {
  background: #f8fafc;
  border-radius: 999px;
}

.report-nav-pills .nav-link {
  color: #475569;
  font-weight: 600;
  border-radius: 999px;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.report-nav-pills .nav-link:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.report-nav-pills .nav-link.active {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.official-shortcut-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.official-shortcut-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
}

.cursor-pointer {
  cursor: pointer;
}

.kpi-icon-badge {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}

.report-table th {
  font-weight: 700;
  color: #334155;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  padding: 12px 14px;
}

.report-table td {
  padding: 11px 14px;
  font-size: 0.9rem;
}

.text-indent {
  text-indent: 40px;
}

.border-dotted {
  border-bottom-style: dotted !important;
}

.print-only {
  display: none;
}

@media print {
  .print-hide {
    display: none !important;
  }
  .print-only {
    display: block !important;
  }
  .inventory-reports-container {
    background: transparent !important;
    padding: 0 !important;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
  }
  .table {
    width: 100% !important;
    font-size: 11px !important;
  }
  .table th, .table td {
    padding: 6px !important;
  }
}
</style>
