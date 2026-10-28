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
            รายงานสรุปยอดคงคลัง, ประวัติการรับเข้า-เบิกจ่าย, ความเคลื่อนไหว และสินค้าใกล้หมดอายุ
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 align-items-center flex-wrap">
          <button class="btn btn-outline-success rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="exportToExcel" :disabled="isLoading || reportData.length === 0">
            <i class="bi bi-file-earmark-excel-fill"></i>
            <span>ส่งออก Excel</span>
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="printReport" :disabled="isLoading">
            <i class="bi bi-printer-fill"></i>
            <span>พิมพ์รายงาน</span>
          </button>
          <button class="btn btn-primary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="$emit('back-to-dashboard')">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
          </button>
        </div>
      </div>

      <!-- Printable Header for Print Only -->
      <div class="print-only mb-4 border-bottom pb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h3 class="fw-bold mb-1">โรงพยาบาล / หน่วยงานบริการสาธารณสุข</h3>
            <h5 class="fw-bold text-secondary mb-0">{{ activeReportTitle }}</h5>
            <small class="text-muted">คลัง: {{ currentWarehouseName }} | ออกรายงานเมื่อ: {{ currentDateTimeFormatted }}</small>
          </div>
          <div class="text-end">
            <span class="badge bg-light text-dark border px-3 py-2">INVS Inventory Management</span>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards (Print Hide) -->
      <div class="row g-3 mb-4 print-hide" v-if="summaryKpi">
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
          </div>
        </div>
      </div>

      <!-- Filter Controls Card (Print Hide) -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 print-hide">
        <div class="card-body p-3 p-md-4">
          <div class="row g-3 align-items-end">
            <!-- Warehouse Filter -->
            <div class="col-md-3">
              <label class="form-label fw-bold small text-secondary">
                <i class="bi bi-building me-1"></i>คลังวัสดุ
              </label>
              <select class="form-select" v-model="filters.warehouse_id" @change="fetchReportData">
                <option value="">-- คลังทั้งหมด --</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                  [{{ w.code }}] {{ w.name }}
                </option>
              </select>
            </div>

            <!-- Date Range Filters for Receipts, Issues, Movement -->
            <template v-if="['receipts', 'issues', 'movement'].includes(currentReportTab)">
              <div class="col-md-2 col-6">
                <label class="form-label fw-bold small text-secondary">
                  <i class="bi bi-calendar-event me-1"></i>ตั้งแต่วันที่
                </label>
                <input type="date" class="form-control" v-model="filters.start_date" @change="fetchReportData" />
              </div>
              <div class="col-md-2 col-6">
                <label class="form-label fw-bold small text-secondary">
                  <i class="bi bi-calendar-event me-1"></i>ถึงวันที่
                </label>
                <input type="date" class="form-control" v-model="filters.end_date" @change="fetchReportData" />
              </div>
            </template>

            <!-- Category Filter for Balance & Movement -->
            <template v-if="['balance', 'movement'].includes(currentReportTab)">
              <div class="col-md-2">
                <label class="form-label fw-bold small text-secondary">
                  <i class="bi bi-tags me-1"></i>หมวดหมู่วัสดุ
                </label>
                <select class="form-select" v-model="filters.category_id" @change="fetchReportData">
                  <option value="">-- ทุกหมวดหมู่ --</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </template>

            <!-- Status Filter for Balance -->
            <template v-if="currentReportTab === 'balance'">
              <div class="col-md-2">
                <label class="form-label fw-bold small text-secondary">
                  <i class="bi bi-funnel me-1"></i>สถานะสต็อก
                </label>
                <select class="form-select" v-model="filters.status" @change="fetchReportData">
                  <option value="all">ทั้งหมด</option>
                  <option value="in_stock">มีสินค้าในคลัง (>0)</option>
                  <option value="low">ต่ำกว่าเกณฑ์สั่งซื้อ (Low)</option>
                  <option value="out_of_stock">สินค้าหมดสต็อก (=0)</option>
                </select>
              </div>
            </template>

            <!-- Days Filter for Expiring -->
            <template v-if="currentReportTab === 'expiring'">
              <div class="col-md-3">
                <label class="form-label fw-bold small text-secondary">
                  <i class="bi bi-clock-history me-1"></i>เงื่อนไขวันหมดอายุ
                </label>
                <select class="form-select" v-model="filters.days" @change="fetchReportData">
                  <option :value="30">ใกล้หมดอายุภายใน 30 วัน</option>
                  <option :value="60">ใกล้หมดอายุภายใน 60 วัน (2 เดือน)</option>
                  <option :value="90">ใกล้หมดอายุภายใน 90 วัน (3 เดือน)</option>
                  <option :value="180">ใกล้หมดอายุภายใน 180 วัน (6 เดือน)</option>
                  <option :value="-1">หมดอายุแล้ว (Expired)</option>
                </select>
              </div>
            </template>

            <!-- Search Keyword -->
            <div class="col-md-3">
              <label class="form-label fw-bold small text-secondary">
                <i class="bi bi-search me-1"></i>ค้นหาคำสำคัญ
              </label>
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  placeholder="รหัส/ชื่อวัสดุ, เลขที่เอกสาร..."
                  v-model="filters.search"
                  @keyup.enter="fetchReportData"
                />
                <button class="btn btn-outline-secondary" type="button" @click="fetchReportData">
                  <i class="bi bi-search"></i>
                </button>
              </div>
            </div>

            <!-- Quick Preset Buttons for Dates -->
            <div class="col-12 mt-2 pt-2 border-top d-flex align-items-center gap-2 flex-wrap" v-if="['receipts', 'issues', 'movement'].includes(currentReportTab)">
              <span class="small text-muted fw-bold me-1">ช่วงเวลาด่วน:</span>
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 small" @click="setDatePreset('today')">วันนี้</button>
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 small" @click="setDatePreset('7days')">7 วันล่าสุด</button>
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 small" @click="setDatePreset('thisMonth')">เดือนนี้</button>
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 small" @click="setDatePreset('lastMonth')">เดือนก่อน</button>
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 small" @click="setDatePreset('thisYear')">ปีงบประมาณนี้</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Content Card -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <!-- Card Header Info -->
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-3 py-1">{{ activeReportBadge }}</span>
            <h5 class="fw-bold mb-0 text-dark">{{ activeReportTitle }}</h5>
            <span class="badge bg-light text-muted border ms-2">พบ {{ formatNumber(reportData.length) }} รายการ</span>
          </div>

          <!-- Total Value & Qty Pill -->
          <div class="d-flex gap-3 align-items-center small" v-if="reportSummary">
            <div class="bg-light px-3 py-1 rounded-pill border">
              <span class="text-muted">จำนวนรวม: </span>
              <span class="fw-bold text-primary">{{ formatNumber(reportSummary.total_qty || reportSummary.total_ending || 0) }}</span>
            </div>
            <div class="bg-light px-3 py-1 rounded-pill border" v-if="reportSummary.total_value !== undefined || reportSummary.total_amount !== undefined || reportSummary.total_ending_value !== undefined">
              <span class="text-muted">มูลค่ารวม: </span>
              <span class="fw-bold text-success">{{ formatCurrency(reportSummary.total_value ?? reportSummary.total_amount ?? reportSummary.total_ending_value ?? 0) }}</span>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <!-- Loading State -->
          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mb-0">กำลังประมวลผลข้อมูลรายงาน...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="reportData.length === 0" class="text-center py-5">
            <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
            <h5 class="text-muted fw-bold mt-3">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</h5>
            <p class="text-muted small">โปรดลองเปลี่ยนช่วงเวลา คลัง หรือคำค้นหาใหม่อีกครั้ง</p>
          </div>

          <!-- Table Views -->
          <div v-else class="table-responsive">
            <!-- 1. Stock Balance Table -->
            <table v-if="currentReportTab === 'balance'" class="table table-hover table-striped align-middle mb-0 report-table">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width: 50px;">ลำดับ</th>
                  <th style="width: 120px;">รหัสพัสดุ</th>
                  <th>ชื่อรายการพัสดุ / เวชภัณฑ์</th>
                  <th style="width: 140px;">หมวดหมู่</th>
                  <th class="text-center" style="width: 90px;">หน่วยนับ</th>
                  <th class="text-end" style="width: 100px;">จุดสั่งซื้อ</th>
                  <th class="text-end" style="width: 120px;">ยอดคงคลัง</th>
                  <th class="text-end" style="width: 110px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 130px;">มูลค่ารวม (฿)</th>
                  <th class="text-center" style="width: 110px;">สถานะ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in reportData" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td class="fw-bold text-primary font-monospace">{{ row.item_code }}</td>
                  <td>
                    <div class="fw-bold text-dark">{{ row.item_name }}</div>
                    <small class="text-muted d-block" v-if="!filters.warehouse_id">คลัง: {{ row.warehouse_name || '-' }}</small>
                  </td>
                  <td><span class="badge bg-light text-secondary border">{{ row.category_name || 'ทั่วไป' }}</span></td>
                  <td class="text-center">{{ row.unit_name || 'ชิ้น' }}</td>
                  <td class="text-end text-muted">{{ formatNumber(row.minimum_stock) }}</td>
                  <td class="text-end fw-bold" :class="row.balance_qty <= 0 ? 'text-danger' : (row.balance_qty <= row.minimum_stock ? 'text-warning' : 'text-success')">
                    {{ formatNumber(row.balance_qty) }}
                  </td>
                  <td class="text-end">{{ formatCurrency(row.unit_price) }}</td>
                  <td class="text-end fw-bold text-dark">{{ formatCurrency(row.total_value) }}</td>
                  <td class="text-center">
                    <span v-if="row.status_label === 'OUT_OF_STOCK'" class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">สินค้าหมด</span>
                    <span v-else-if="row.status_label === 'LOW_STOCK'" class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1">ต่ำกว่าเกณฑ์</span>
                    <span v-else class="badge bg-success-subtle text-success rounded-pill px-2 py-1">ปกติ</span>
                  </td>
                </tr>
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="6" class="text-end">รวมยอดทั้งหมด ({{ reportData.length }} รายการ):</td>
                  <td class="text-end text-primary">{{ formatNumber(reportSummary?.total_qty) }}</td>
                  <td></td>
                  <td class="text-end text-success">{{ formatCurrency(reportSummary?.total_value) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>

            <!-- 2. Receiving Report Table -->
            <table v-else-if="currentReportTab === 'receipts'" class="table table-hover table-striped align-middle mb-0 report-table">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width: 50px;">ลำดับ</th>
                  <th style="width: 110px;">วันที่รับ</th>
                  <th style="width: 120px;">เลขที่ใบรับ</th>
                  <th style="width: 110px;">เลขที่ PO</th>
                  <th style="width: 160px;">ผู้จัดจำหน่าย / บริษัท</th>
                  <th>รหัส / ชื่อพัสดุ</th>
                  <th style="width: 100px;">Lot No.</th>
                  <th style="width: 100px;">วันหมดอายุ</th>
                  <th class="text-end" style="width: 90px;">จำนวนรับ</th>
                  <th class="text-end" style="width: 100px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 120px;">รวมเงิน (฿)</th>
                  <th style="width: 110px;">ผู้ตรวจรับ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in reportData" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td class="small">{{ formatDate(row.receipt_date) }}</td>
                  <td class="fw-bold text-primary">{{ row.receipt_no }}</td>
                  <td class="text-muted small">{{ row.po_number || '-' }}</td>
                  <td class="small">{{ row.vendor_name || '-' }}</td>
                  <td>
                    <div class="fw-bold text-dark">{{ row.item_name }}</div>
                    <small class="text-muted font-monospace">{{ row.item_code }} ({{ row.unit_name || 'ชิ้น' }})</small>
                  </td>
                  <td class="font-monospace small">{{ row.lot_number || '-' }}</td>
                  <td class="small">{{ formatDate(row.expiry_date) }}</td>
                  <td class="text-end fw-bold text-success">+{{ formatNumber(row.qty) }}</td>
                  <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                  <td class="text-end fw-bold text-dark">{{ formatCurrency(row.total_price) }}</td>
                  <td class="small text-muted">{{ row.receiver_name || '-' }}</td>
                </tr>
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="8" class="text-end">รวมยอดรับเข้า ({{ reportData.length }} รายการ):</td>
                  <td class="text-end text-success">+{{ formatNumber(reportSummary?.total_qty) }}</td>
                  <td></td>
                  <td class="text-end text-primary">{{ formatCurrency(reportSummary?.total_amount) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>

            <!-- 3. Issue Report Table -->
            <table v-else-if="currentReportTab === 'issues'" class="table table-hover table-striped align-middle mb-0 report-table">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width: 50px;">ลำดับ</th>
                  <th style="width: 110px;">วันที่เบิก</th>
                  <th style="width: 120px;">เลขที่ใบเบิก</th>
                  <th style="width: 160px;">แผนก / หน่วยงาน</th>
                  <th style="width: 120px;">ผู้ขอเบิก</th>
                  <th>รหัส / ชื่อพัสดุ</th>
                  <th style="width: 100px;">Lot No.</th>
                  <th class="text-end" style="width: 90px;">จำนวนเบิก</th>
                  <th class="text-end" style="width: 100px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 120px;">รวมเงิน (฿)</th>
                  <th style="width: 110px;">ผู้จ่าย</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in reportData" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td class="small">{{ formatDate(row.issue_date) }}</td>
                  <td class="fw-bold text-warning-emphasis">{{ row.issue_no }}</td>
                  <td><span class="badge bg-light text-dark border">{{ row.department_name || '-' }}</span></td>
                  <td class="small text-muted">{{ row.requester_name || '-' }}</td>
                  <td>
                    <div class="fw-bold text-dark">{{ row.item_name }}</div>
                    <small class="text-muted font-monospace">{{ row.item_code }} ({{ row.unit_name || 'ชิ้น' }})</small>
                  </td>
                  <td class="font-monospace small">{{ row.lot_number || '-' }}</td>
                  <td class="text-end fw-bold text-danger">-{{ formatNumber(row.qty) }}</td>
                  <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                  <td class="text-end fw-bold text-dark">{{ formatCurrency(row.total_price) }}</td>
                  <td class="small text-muted">{{ row.issuer_name || '-' }}</td>
                </tr>
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="7" class="text-end">รวมยอดเบิกจ่าย ({{ reportData.length }} รายการ):</td>
                  <td class="text-end text-danger">-{{ formatNumber(reportSummary?.total_qty) }}</td>
                  <td></td>
                  <td class="text-end text-primary">{{ formatCurrency(reportSummary?.total_amount) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>

            <!-- 4. Movement Summary Table (Beginning -> In -> Out -> Ending) -->
            <table v-else-if="currentReportTab === 'movement'" class="table table-hover table-striped align-middle mb-0 report-table">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width: 50px;">ลำดับ</th>
                  <th style="width: 120px;">รหัสพัสดุ</th>
                  <th>ชื่อรายการพัสดุ</th>
                  <th style="width: 130px;">หมวดหมู่</th>
                  <th class="text-center" style="width: 80px;">หน่วย</th>
                  <th class="text-end" style="width: 100px;">ยอดยกมา</th>
                  <th class="text-end text-success" style="width: 100px;">รับเข้า (+)</th>
                  <th class="text-end text-danger" style="width: 100px;">จ่ายออก (-)</th>
                  <th class="text-end fw-bold text-primary" style="width: 110px;">คงเหลือยกไป</th>
                  <th class="text-end" style="width: 100px;">ราคา/หน่วย</th>
                  <th class="text-end fw-bold" style="width: 120px;">มูลค่ายกไป (฿)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in reportData" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td class="fw-bold text-primary font-monospace">{{ row.item_code }}</td>
                  <td class="fw-bold text-dark">{{ row.item_name }}</td>
                  <td><span class="badge bg-light text-secondary border">{{ row.category_name || 'ทั่วไป' }}</span></td>
                  <td class="text-center">{{ row.unit_name || 'ชิ้น' }}</td>
                  <td class="text-end text-secondary">{{ formatNumber(row.beginning_qty) }}</td>
                  <td class="text-end text-success fw-bold">+{{ formatNumber(row.in_qty) }}</td>
                  <td class="text-end text-danger fw-bold">-{{ formatNumber(row.out_qty) }}</td>
                  <td class="text-end fw-bold text-primary bg-primary-subtle">{{ formatNumber(row.ending_qty) }}</td>
                  <td class="text-end">{{ formatCurrency(row.unit_price) }}</td>
                  <td class="text-end fw-bold text-dark">{{ formatCurrency(row.ending_value) }}</td>
                </tr>
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="5" class="text-end">รวมทุกรายการ ({{ reportData.length }} รายการ):</td>
                  <td class="text-end">{{ formatNumber(reportSummary?.total_beginning) }}</td>
                  <td class="text-end text-success">+{{ formatNumber(reportSummary?.total_in) }}</td>
                  <td class="text-end text-danger">-{{ formatNumber(reportSummary?.total_out) }}</td>
                  <td class="text-end text-primary">{{ formatNumber(reportSummary?.total_ending) }}</td>
                  <td></td>
                  <td class="text-end text-success">{{ formatCurrency(reportSummary?.total_ending_value) }}</td>
                </tr>
              </tfoot>
            </table>

            <!-- 5. Expiring & Aging Table -->
            <table v-else-if="currentReportTab === 'expiring'" class="table table-hover table-striped align-middle mb-0 report-table">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width: 50px;">ลำดับ</th>
                  <th style="width: 120px;">รหัสพัสดุ</th>
                  <th>ชื่อรายการพัสดุ</th>
                  <th style="width: 130px;">คลัง</th>
                  <th style="width: 110px;">Lot Number</th>
                  <th style="width: 110px;">วันหมดอายุ</th>
                  <th class="text-center" style="width: 140px;">สถานะอายุ</th>
                  <th class="text-end" style="width: 100px;">ยอดคงเหลือ</th>
                  <th class="text-end" style="width: 100px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 120px;">มูลค่ารวม (฿)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in reportData" :key="idx">
                  <td class="text-center text-muted">{{ idx + 1 }}</td>
                  <td class="fw-bold text-primary font-monospace">{{ row.item_code }}</td>
                  <td>
                    <div class="fw-bold text-dark">{{ row.item_name }}</div>
                    <small class="text-muted">{{ row.category_name || 'ทั่วไป' }} ({{ row.unit_name || 'ชิ้น' }})</small>
                  </td>
                  <td class="small">{{ row.warehouse_name || '-' }}</td>
                  <td class="font-monospace fw-bold">{{ row.lot_number }}</td>
                  <td class="fw-bold" :class="row.days_remaining < 0 ? 'text-danger' : (row.days_remaining <= 30 ? 'text-warning-emphasis' : 'text-dark')">
                    {{ formatDate(row.expiry_date) }}
                  </td>
                  <td class="text-center">
                    <span v-if="row.status_badge === 'EXPIRED'" class="badge bg-danger text-white rounded-pill px-2 py-1">
                      <i class="bi bi-x-circle-fill me-1"></i>{{ row.status_text }}
                    </span>
                    <span v-else-if="row.status_badge === 'URGENT'" class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">
                      <i class="bi bi-exclamation-circle-fill me-1"></i>{{ row.status_text }}
                    </span>
                    <span v-else class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1">
                      <i class="bi bi-hourglass-split me-1"></i>{{ row.status_text }}
                    </span>
                  </td>
                  <td class="text-end fw-bold text-primary">{{ formatNumber(row.balance_qty) }}</td>
                  <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                  <td class="text-end fw-bold text-dark">{{ formatCurrency(row.total_value) }}</td>
                </tr>
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="7" class="text-end">รวมยอดล็อตใกล้หมดอายุ ({{ reportData.length }} ล็อต):</td>
                  <td class="text-end text-primary">{{ formatNumber(reportSummary?.total_qty) }}</td>
                  <td></td>
                  <td class="text-end text-danger">{{ formatCurrency(reportSummary?.total_value) }}</td>
                </tr>
              </tfoot>
            </table>
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

export default {
  name: 'InventoryReports',
  props: {
    warehouseId: {
      type: [Number, String],
      default: ''
    }
  },
  data() {
    const today = moment().format('YYYY-MM-DD');
    const startOfMonth = moment().startOf('month').format('YYYY-MM-DD');
    return {
      currentReportTab: 'balance',
      isLoading: false,
      warehouses: [],
      categories: [],
      vendors: [],
      summaryKpi: null,
      reportData: [],
      reportSummary: null,
      filters: {
        warehouse_id: this.warehouseId || localStorage.getItem('selected_warehouse_id') || '',
        start_date: startOfMonth,
        end_date: today,
        category_id: '',
        vendor_id: '',
        status: 'all',
        days: 60,
        search: ''
      }
    };
  },
  computed: {
    activeReportTitle() {
      switch (this.currentReportTab) {
        case 'balance': return 'รายงานสรุปยอดคงเหลือพัสดุ (Stock Balance)';
        case 'receipts': return 'รายงานการตรวจรับพัสดุเข้าคลัง (Receiving / Inward)';
        case 'issues': return 'รายงานการเบิกจ่ายพัสดุออกจากคลัง (Issue / Outward)';
        case 'movement': return 'รายงานสรุปความเคลื่อนไหวพัสดุ (Stock Movement Summary)';
        case 'expiring': return 'รายงานพัสดุใกล้หมดอายุและล็อตคงคลัง (Expiry & Aging)';
        default: return 'รายงานคลังพัสดุ';
      }
    },
    activeReportBadge() {
      switch (this.currentReportTab) {
        case 'balance': return 'Report 1';
        case 'receipts': return 'Report 2';
        case 'issues': return 'Report 3';
        case 'movement': return 'Report 4';
        case 'expiring': return 'Report 5';
        default: return 'Report';
      }
    },
    currentWarehouseName() {
      if (!this.filters.warehouse_id) return 'คลังทั้งหมด';
      const w = this.warehouses.find(item => item.id == this.filters.warehouse_id);
      return w ? `[${w.code}] ${w.name}` : 'คลังที่เลือก';
    },
    currentDateTimeFormatted() {
      return moment().format('DD/MM/YYYY HH:mm:ss');
    }
  },
  watch: {
    warehouseId(newVal) {
      if (newVal !== undefined && newVal !== null) {
        this.filters.warehouse_id = newVal;
        this.fetchReportData();
        this.fetchSummaryKpi();
      }
    }
  },
  async mounted() {
    await this.fetchInitialData();
    await this.fetchSummaryKpi();
    await this.fetchReportData();
  },
  methods: {
    async fetchInitialData() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const [resWh, resCat, resVen] = await Promise.all([
          axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`),
          axios.get('/api-digital/inventory/categories.php'),
          axios.get('/api-digital/inventory/vendors.php')
        ]);
        if (resWh.data?.status === 'success') {
          this.warehouses = resWh.data.data.filter(w => w.active == 1);
        }
        if (resCat.data?.status === 'success') {
          this.categories = resCat.data.data;
        }
        if (resVen.data?.status === 'success') {
          this.vendors = resVen.data.data;
        }
      } catch (err) {
        console.error('Failed to load initial metadata for reports:', err);
      }
    },
    async fetchSummaryKpi() {
      try {
        const params = new URLSearchParams();
        params.append('type', 'summary');
        if (this.filters.warehouse_id) {
          params.append('warehouse_id', this.filters.warehouse_id);
        }
        const res = await axios.get(`/api-digital/inventory/reports.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.summaryKpi = res.data.data;
        }
      } catch (err) {
        console.error('Failed to load summary KPI:', err);
      }
    },
    async fetchReportData() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams();
        params.append('type', this.currentReportTab);

        if (this.filters.warehouse_id) params.append('warehouse_id', this.filters.warehouse_id);
        if (this.filters.category_id) params.append('category_id', this.filters.category_id);
        if (this.filters.vendor_id) params.append('vendor_id', this.filters.vendor_id);
        if (this.filters.start_date) params.append('start_date', this.filters.start_date);
        if (this.filters.end_date) params.append('end_date', this.filters.end_date);
        if (this.filters.search) params.append('search', this.filters.search.trim());
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
    switchReportTab(tab) {
      if (this.currentReportTab === tab) return;
      this.currentReportTab = tab;
      this.fetchReportData();
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
        // Thai Fiscal Year: Oct 1 to Sep 30
        const currentMonth = moment().month(); // 0-indexed (Oct is 9)
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
      if (num === null || num === undefined || isNaN(num)) return '฿0.00';
      return '฿' + Number(num).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY');
    },
    printReport() {
      window.print();
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
