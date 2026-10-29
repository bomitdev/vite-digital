<template>
  <div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary">
      <div>
        <h2 class="text-primary fw-bold mb-0">
          <i class="bi bi-kanban me-2"></i>ระบบติดตามงานโครงการ (Project Management)
        </h2>
        <p class="text-secondary mt-1 mb-0 fs-5">จัดการและติดตามความคืบหน้าของโครงการต่างๆ</p>
      </div>
      <!-- Quick Fiscal Year Selector Pills & Action Buttons -->
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="bg-light p-1 rounded-pill border shadow-sm d-flex align-items-center">
          <span class="text-secondary small fw-bold px-2 d-none d-sm-inline"><i class="bi bi-calendar-check me-1"></i>ปีงบฯ:</span>
          <button 
            v-for="yr in availableFiscalYears" 
            :key="'header_pill_' + yr"
            type="button" 
            class="btn btn-sm rounded-pill fw-bold px-3 py-1 transition-hover" 
            :class="filterFiscalYear === yr ? 'btn-primary shadow-sm text-white' : 'btn-light text-secondary border-0'" 
            @click="filterFiscalYear = yr"
          >
            ปี {{ yr }}
            <span class="badge ms-1" :class="filterFiscalYear === yr ? 'bg-white text-primary' : 'bg-primary bg-opacity-10 text-primary'" v-if="yr === currentFiscalYear">ปัจจุบัน</span>
            <span class="badge bg-white text-dark border ms-1" v-if="getProjectCountByYear(yr) > 0">{{ getProjectCountByYear(yr) }}</span>
          </button>
          <button 
            type="button" 
            class="btn btn-sm rounded-pill fw-bold px-3 py-1 transition-hover" 
            :class="filterFiscalYear === '' ? 'btn-primary shadow-sm text-white' : 'btn-light text-secondary border-0'" 
            @click="filterFiscalYear = ''"
          >
            ทั้งหมด
          </button>
        </div>

        <button 
          class="btn rounded-pill px-3 fw-bold shadow-sm transition-hover" 
          :class="showCharts ? 'btn-outline-primary bg-white' : 'btn-primary'" 
          @click="toggleCharts"
          title="คลิกเพื่อเปิด/ปิดส่วนแสดงกราฟงบประมาณ"
        >
          <i class="bi me-2" :class="showCharts ? 'bi-graph-up' : 'bi-bar-chart-fill'"></i>
          {{ showCharts ? 'ซ่อนกราฟงบประมาณ' : 'โชว์กราฟงบประมาณ' }}
        </button>
        <button class="btn btn-primary shadow-sm rounded-pill px-4 fw-bold" @click="openModal()">
          <i class="bi bi-plus-circle-fill me-2"></i>เพิ่มโครงการใหม่
        </button>
      </div>
    </div>

    <!-- Current Year Empty State Alert Banner -->
    <div v-if="filterFiscalYear === currentFiscalYear && getProjectCountByYear(currentFiscalYear) === 0 && !loading" class="alert alert-info border-0 rounded-4 shadow-sm mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3" style="border-left: 5px solid #0dcaf0 !important;">
      <div class="d-flex align-items-center">
        <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-info-circle-fill fs-5"></i>
        </div>
        <div>
          <div class="fw-bold text-dark fs-6">
            กำลังแสดงผลของ <span class="badge bg-primary px-2 py-1 rounded-pill">ปีงบประมาณ {{ currentFiscalYear }} (ปีงบปัจจุบัน)</span>
          </div>
          <small class="text-secondary">
            ยังไม่มีข้อมูลโครงการที่บันทึกในปีนี้ (มีข้อมูลเดิม {{ getProjectCountByYear('2569') }} โครงการในปี 2569) คุณสามารถคัดลอกมาเริ่มต้นปีใหม่ หรือสลับดูปีเดิมได้
          </small>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" @click="filterFiscalYear = '2569'">
          <i class="bi bi-clock-history me-1"></i> ดูปี 2569 ({{ getProjectCountByYear('2569') }})
        </button>
        <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" @click="copyProjectsTo2570">
          <i class="bi bi-copy me-1"></i> คัดลอกโครงการจากปี 2569 สู่ปี {{ currentFiscalYear }}
        </button>
        <button class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" @click="updateProjectsYearTo2570">
          <i class="bi bi-arrow-repeat me-1"></i> ปรับเป็นปี {{ currentFiscalYear }} ทั้งหมด
        </button>
      </div>
    </div>

    <!-- Summary Dashboard -->
    <div class="row g-3 mb-4" v-if="!loading">
      <div class="col-md-3">
        <div class="card bg-primary text-white border-0 shadow-sm rounded-4 h-100 hover-card" @click="filterStatus = ''" style="cursor: pointer;">
          <div class="card-body d-flex flex-column justify-content-center p-4">
            <h6 class="text-white-50 fw-bold mb-1">
              โครงการทั้งหมด <span v-if="filterFiscalYear" class="badge bg-white text-primary rounded-pill ms-1">ปี {{ filterFiscalYear }}</span>
            </h6>
            <h2 class="fw-bold mb-0">{{ totalProjects }} <span class="fs-6 fw-normal">โครงการ</span></h2>
            <div class="mt-2 text-white-50 small"><i class="bi bi-folder2-open me-1"></i> แสดงโครงการทั้งหมด</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white border-0 shadow-sm rounded-4 h-100 hover-card" @click="showCharts = true" style="cursor: pointer;" title="คลิกเพื่อดูกราฟงบประมาณ">
          <div class="card-body d-flex flex-column justify-content-center p-4">
            <h6 class="text-white-50 fw-bold mb-1">
              งบประมาณรวม <span v-if="filterFiscalYear" class="badge bg-white text-success rounded-pill ms-1">ปี {{ filterFiscalYear }}</span>
            </h6>
            <h2 class="fw-bold mb-0">{{ totalBudget.toLocaleString('th-TH', { minimumFractionDigits: 2 }) }} <span class="fs-6 fw-normal">บาท</span></h2>
            <div class="mt-2 text-white-50 small"><i class="bi bi-bar-chart-fill me-1"></i> คลิกเพื่อดูกราฟวิเคราะห์</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white border-0 shadow-sm rounded-4 h-100 hover-card" @click="filterStatus = 'in_progress'" style="cursor: pointer;">
          <div class="card-body d-flex flex-column justify-content-center p-4">
            <h6 class="text-white-50 fw-bold mb-1">กำลังดำเนินการ</h6>
            <h2 class="fw-bold mb-0">{{ inProgressProjects }} <span class="fs-6 fw-normal">โครงการ</span></h2>
            <div class="mt-2 text-white-50 small"><i class="bi bi-arrow-repeat me-1"></i> คลิกเพื่อกรองข้อมูล</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-dark border-0 shadow-sm rounded-4 h-100 hover-card" @click="filterStatus = 'completed'" style="cursor: pointer;">
          <div class="card-body d-flex flex-column justify-content-center p-4">
            <h6 class="text-dark-50 fw-bold mb-1">เสร็จสิ้นแล้ว</h6>
            <h2 class="fw-bold mb-0">{{ completedProjects }} <span class="fs-6 fw-normal">โครงการ</span></h2>
            <div class="mt-2 text-dark-50 small"><i class="bi bi-check-circle me-1"></i> คลิกเพื่อกรองข้อมูล</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Budget Analytics & Charts Section -->
    <div class="mb-4" v-show="!loading && showCharts">
      <!-- Section Banner -->
      <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-white" style="border-left: 5px solid #0d6efd !important;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
              <i class="bi bi-pie-chart-fill fs-5"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">วิเคราะห์งบประมาณโครงการ (Budget Analytics)</h5>
              <small class="text-secondary">สรุปแผนผังการจัดสรรงบประมาณ แยกตามหมวดหมู่ กลุ่มงาน สถานะ และไตรมาส</small>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-light text-success border border-success-subtle shadow-sm px-3 py-2 rounded-pill fw-medium">
              <i class="bi bi-check-circle-fill me-1"></i>งบเสร็จสิ้นแล้ว: <strong>{{ completedBudget.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong> ฿ ({{ totalBudget > 0 ? ((completedBudget / totalBudget) * 100).toFixed(1) : 0 }}%)
            </span>
            <span class="badge bg-light text-info border border-info-subtle shadow-sm px-3 py-2 rounded-pill fw-medium">
              <i class="bi bi-arrow-repeat me-1"></i>งบกำลังดำเนินการ: <strong>{{ inProgressBudget.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong> ฿ ({{ totalBudget > 0 ? ((inProgressBudget / totalBudget) * 100).toFixed(1) : 0 }}%)
            </span>
            <span class="badge bg-light text-warning text-dark border border-warning-subtle shadow-sm px-3 py-2 rounded-pill fw-medium">
              <i class="bi bi-hourglass-split me-1"></i>งบรอดำเนินการ: <strong>{{ pendingBudget.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong> ฿ ({{ totalBudget > 0 ? ((pendingBudget / totalBudget) * 100).toFixed(1) : 0 }}%)
            </span>
            <span class="badge bg-light text-secondary border shadow-sm px-3 py-2 rounded-pill fw-medium">
              <i class="bi bi-calculator me-1"></i>เฉลี่ยต่อโครงการ: <strong>{{ averageBudget.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</strong> ฿
            </span>
          </div>
        </div>
      </div>

      <!-- Charts 2x2 Grid -->
      <div class="row g-3 mb-3">
        <!-- Chart 1: Budget by Category -->
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
              <div class="fw-bold text-dark fs-6">
                <i class="bi bi-pie-chart text-primary me-2"></i>สัดส่วนงบประมาณตามหมวดโครงการ
              </div>
              <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1">หมวดหมู่</span>
            </div>
            <div class="card-body px-4 pb-4">
              <div style="position: relative; height: 280px;">
                <canvas id="categoryChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart 2: Budget by Department -->
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
              <div class="fw-bold text-dark fs-6">
                <i class="bi bi-bar-chart-steps text-info me-2"></i>งบประมาณตามกลุ่มงาน/ผู้รับผิดชอบ (Top 8)
              </div>
              <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1">ผู้รับผิดชอบ</span>
            </div>
            <div class="card-body px-4 pb-4">
              <div style="position: relative; height: 280px;">
                <canvas id="deptChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart 3: Budget by Status -->
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
              <div class="fw-bold text-dark fs-6">
                <i class="bi bi-pie-chart-fill text-success me-2"></i>สัดส่วนงบประมาณตามสถานะโครงการ
              </div>
              <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">สถานะ</span>
            </div>
            <div class="card-body px-4 pb-4">
              <div style="position: relative; height: 280px;">
                <canvas id="statusChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart 4: Budget by Quarter -->
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
              <div class="fw-bold text-dark fs-6">
                <i class="bi bi-calendar3-range text-warning me-2"></i>แผนการจัดสรรงบประมาณตามไตรมาส (Q1 - Q4)
              </div>
              <span class="badge bg-warning bg-opacity-10 text-dark rounded-pill px-3 py-1">ไตรมาส</span>
            </div>
            <div class="card-body px-4 pb-4">
              <div style="position: relative; height: 280px;">
                <canvas id="quarterChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top 5 Highest Budget Projects -->
      <div class="card border-0 shadow-sm rounded-4 bg-white" v-if="topBudgetProjects.length > 0">
        <div class="card-header bg-transparent border-0 pt-3 px-4 d-flex justify-content-between align-items-center">
          <div class="fw-bold text-dark fs-6">
            <i class="bi bi-trophy-fill text-warning me-2"></i>5 อันดับโครงการที่มีงบประมาณสูงสุด
          </div>
          <span class="badge bg-light text-secondary border rounded-pill">Top Spending Projects</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="px-4 py-2 text-secondary text-center" style="width: 70px;">อันดับ</th>
                  <th class="py-2 text-secondary">ชื่อโครงการ</th>
                  <th class="py-2 text-secondary">หมวดหมู่</th>
                  <th class="py-2 text-secondary">กลุ่มงานที่รับผิดชอบ</th>
                  <th class="py-2 text-end text-secondary">งบประมาณรวม</th>
                  <th class="py-2 text-center text-secondary">สถานะ</th>
                  <th class="px-4 py-2 text-center text-secondary" style="width: 170px;">สัดส่วนต่องบรวม</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, idx) in topBudgetProjects" :key="'top_' + p.id">
                  <td class="px-4 text-center">
                    <span 
                      class="badge rounded-circle d-inline-flex align-items-center justify-content-center"
                      :class="idx === 0 ? 'bg-warning text-dark shadow-sm' : idx === 1 ? 'bg-secondary text-white' : idx === 2 ? 'bg-danger text-white' : 'bg-light text-muted border'"
                      style="width: 26px; height: 26px; font-weight: bold;"
                    >
                      {{ idx + 1 }}
                    </span>
                  </td>
                  <td class="fw-bold text-dark">
                    {{ p.project_name }}
                    <span v-if="p.fiscal_year" class="badge bg-light text-muted ms-1 small">ปี {{ p.fiscal_year }}</span>
                  </td>
                  <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill">
                      {{ p.category_name || '-' }}
                    </span>
                  </td>
                  <td class="text-secondary small">{{ p.manager_name || '-' }}</td>
                  <td class="text-end fw-bold text-success fs-6">
                    {{ p.total_budget.toLocaleString('th-TH', { minimumFractionDigits: 2 }) }} ฿
                  </td>
                  <td class="text-center text-nowrap">
                    <span :class="getStatusBadge(p.status)" class="d-inline-flex align-items-center px-2 py-1 rounded-pill small fw-bold">
                      <i :class="getStatusIcon(p.status)" class="me-1"></i>{{ getStatusText(p.status) }}
                    </span>
                  </td>
                  <td class="px-4">
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 6px; border-radius: 6px;">
                        <div class="progress-bar bg-primary" :style="{ width: ((p.total_budget / (totalBudget || 1)) * 100).toFixed(1) + '%' }"></div>
                      </div>
                      <span class="small text-muted fw-bold" style="min-width: 45px;">
                        {{ ((p.total_budget / (totalBudget || 1)) * 100).toFixed(1) }}%
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter and Search -->
    <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white">
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label text-secondary fw-bold small">ค้นหาชื่อโครงการ</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control bg-light border-0 rounded-end" placeholder="พิมพ์เพื่อค้นหา..." v-model="searchQuery" />
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label text-secondary fw-bold small">ปีงบประมาณ</label>
            <select class="form-select bg-light border-0" v-model="filterFiscalYear">
              <option v-for="yr in availableFiscalYears" :key="yr" :value="yr">
                ปี {{ yr }} {{ yr === currentFiscalYear ? '(ปีปัจจุบัน)' : '' }}
              </option>
              <option value="">ทุกปีงบประมาณ</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label text-secondary fw-bold small">หมวดโครงการ</label>
            <select class="form-select bg-light border-0" v-model="filterCategory">
              <option value="">ทั้งหมด</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label text-secondary fw-bold small">สถานะโครงการ</label>
            <select class="form-select bg-light border-0" v-model="filterStatus">
              <option value="">ทั้งหมด</option>
              <option value="pending">รอดำเนินการ</option>
              <option value="in_progress">กำลังดำเนินการ</option>
              <option value="completed">เสร็จสิ้น</option>
              <option value="cancelled">ยกเลิก</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-success w-100 rounded-3 fw-bold shadow-sm" @click="exportToExcel">
              <i class="bi bi-file-earmark-excel me-1"></i> นำออก Excel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3 fw-bold">กำลังโหลดข้อมูลโครงการ...</p>
    </div>

    <!-- Data Table -->
    <div v-else class="card shadow-sm border-0 rounded-4 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="py-3 px-4 text-secondary">รหัส</th>
              <th class="py-3 text-secondary">ชื่อโครงการ</th>
              <th class="py-3 text-secondary">สถานะ</th>
              <th class="py-3 text-secondary" style="min-width: 200px;">ความคืบหน้า</th>
              <th class="py-3 text-secondary">ระยะเวลา</th>
              <th class="py-3 text-secondary">ผู้รับผิดชอบ</th>
              <th class="py-3 px-4 text-secondary text-center">จัดการ</th>
            </tr>
          </thead>
          <tbody class="border-top-0">
            <tr v-for="(item, index) in filteredProjects" :key="item.id" class="transition-hover">
              <td class="px-4 text-muted fw-bold">#{{ item.id }}</td>
              <td>
                <div class="fw-bold text-dark">{{ item.project_name }}</div>
                <div class="small mt-1" v-if="item.category_name">
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill">{{ item.category_name }}</span>
                </div>
                <div class="small text-muted mt-1" v-if="item.quantity > 0 || item.unit_price > 0">
                  <i class="bi bi-cash-stack text-success"></i> งบประมาณรวม: 
                  <span class="fw-bold text-dark">{{ (item.quantity * item.unit_price).toLocaleString('th-TH', { minimumFractionDigits: 2 }) }}</span> ฿
                  <span class="ms-1 text-secondary">(ปีงบฯ {{ item.fiscal_year || '-' }})</span>
                </div>
                <div class="small mt-1" v-if="item.quarters">
                  <span v-for="q in item.quarters.split(',')" :key="q" 
                        class="badge me-1"
                        :class="(item.completed_quarters && item.completed_quarters.split(',').includes(q)) ? 'bg-success' : 'bg-secondary'">
                    {{ q }}
                  </span>
                </div>
              </td>
              <td class="text-nowrap">
                <span :class="getStatusBadge(item.status)" class="d-inline-flex align-items-center px-3 py-1 rounded-pill fw-bold" style="font-size: 0.85rem;">
                  <i :class="getStatusIcon(item.status)" class="me-1"></i>{{ getStatusText(item.status) }}
                </span>
              </td>
              <td>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small fw-bold" :class="item.progress === 100 ? 'text-success' : 'text-primary'">
                    <span v-if="item.quantity > 0">{{ item.completed_quantity || 0 }} / {{ item.quantity }} ({{ item.progress }}%)</span>
                    <span v-else>{{ item.progress }}%</span>
                  </span>
                </div>
                <div class="progress shadow-sm" style="height: 10px; border-radius: 10px;">
                  <div 
                    class="progress-bar progress-bar-striped progress-bar-animated" 
                    :class="getProgressColor(item.progress)"
                    role="progressbar" 
                    :style="{ width: item.progress + '%' }" 
                    :aria-valuenow="item.progress" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                  </div>
                </div>
              </td>
              <td>
                <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i>เริ่ม: <span class="text-dark fw-bold">{{ formatDate(item.start_date) }}</span></div>
                <div class="small text-muted" v-if="item.status !== 'completed'"><i class="bi bi-bullseye me-1"></i>เป้าหมาย: <span class="text-dark fw-bold">{{ formatDate(item.target_date) }}</span></div>
                <div class="small text-success" v-else><i class="bi bi-check-circle-fill me-1"></i>เสร็จเมื่อ: <span class="fw-bold">{{ formatDate(item.completed_date) }}</span></div>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                    {{ getInitials(item.manager_name) }}
                  </div>
                  <span class="text-dark fw-medium">{{ item.manager_name || 'ไม่ระบุ' }}</span>
                </div>
              </td>
              <td class="px-4 text-center">
                <button v-if="item.quantity > 0" class="btn btn-sm btn-outline-success rounded-pill me-2 px-3" @click="openProgressModal(item)" title="อัปเดตความคืบหน้า">
                  <i class="bi bi-check2-square me-1"></i>อัปเดต
                </button>
                <button class="btn btn-sm btn-outline-primary rounded-pill me-2 px-3" @click="openModal(item)" title="แก้ไข">
                  <i class="bi bi-pencil-square me-1"></i>แก้ไข
                </button>
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" @click="deleteProject(item.id)" title="ลบ">
                  <i class="bi bi-trash3 me-1"></i>ลบ
                </button>
              </td>
            </tr>
            <tr v-if="filteredProjects.length === 0">
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 d-block mb-2 text-primary opacity-50"></i>
                <h5 class="fw-bold">
                  {{ filterFiscalYear ? `ยังไม่มีข้อมูลโครงการของปีงบประมาณ ${filterFiscalYear}` : 'ไม่พบข้อมูลโครงการ' }}
                </h5>
                <p class="mb-3">
                  {{ filterFiscalYear === '2570' ? 'ยังไม่ได้เริ่มบันทึกโครงการสำหรับปีงบประมาณ 2570 หรือสามารถนำเข้า/คัดลอกจากปี 2569 ได้' : 'ลองปรับเปลี่ยนเงื่อนไขการค้นหาใหม่' }}
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                  <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" @click="openModal()">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มโครงการใหม่ปี {{ filterFiscalYear || '2570' }}
                  </button>
                  <button 
                    v-if="filterFiscalYear === '2570' && getProjectCountByYear('2569') > 0" 
                    class="btn btn-outline-success rounded-pill px-4 fw-bold shadow-sm"
                    @click="copyProjectsTo2570"
                  >
                    <i class="bi bi-copy me-1"></i> คัดลอกโครงการทั้งหมดจากปี 2569 มาเริ่มปี 2570
                  </button>
                  <button 
                    v-if="filterFiscalYear === '2570' && getProjectCountByYear('2569') > 0" 
                    class="btn btn-outline-warning text-dark rounded-pill px-4 fw-bold shadow-sm"
                    @click="updateProjectsYearTo2570"
                  >
                    <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนปีโครงการเดิมทั้งหมดเป็นปี 2570
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-success text-white">
            <h6 class="modal-title fw-bold"><i class="bi bi-check2-square me-2"></i>อัปเดตความคืบหน้า</h6>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <h6 class="text-dark fw-bold mb-3">{{ progressForm.project_name }}</h6>
            <label class="form-label text-secondary small mb-3">เลือกไตรมาสที่ทำเสร็จแล้ว (จากทั้งหมด {{ progressForm.available_quarters.length }} ไตรมาสที่เลือกไว้)</label>
            <div class="d-flex flex-column align-items-start w-75 mx-auto text-start">
              <div v-for="q in progressForm.available_quarters" :key="q" class="form-check mb-2">
                <input class="form-check-input" type="checkbox" :value="q" :id="'prog_q_' + q" v-model="progressForm.completed_quarters">
                <label class="form-check-label fw-bold" :for="'prog_q_' + q">
                  ไตรมาสที่ {{ q.replace('Q', '') }}
                </label>
              </div>
              <div v-if="!progressForm.available_quarters || progressForm.available_quarters.length === 0" class="text-muted small text-center w-100">
                <i class="bi bi-info-circle me-1"></i> ไม่มีข้อมูลไตรมาสสำหรับโครงการนี้
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light border-0 py-2 justify-content-center">
            <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" @click="saveProgress" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              <i class="bi bi-save me-1" v-else></i> บันทึก
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header text-white" :class="isEditMode ? 'bg-warning' : 'bg-primary'">
            <h5 class="modal-title fw-bold">
              <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle'"></i> 
              {{ isEditMode ? 'แก้ไขข้อมูลโครงการ' : 'เพิ่มโครงการใหม่' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <form @submit.prevent="saveProject">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label fw-bold text-dark">ชื่อแผนงาน/โครงการ/กิจกรรม <span class="text-danger">*</span></label>
                  <input type="text" class="form-control rounded-3" v-model="form.project_name" required placeholder="ระบุชื่อแผนงาน/โครงการ/กิจกรรม" />
                </div>
                
                <div class="col-md-3">
                  <label class="form-label fw-bold text-dark">จำนวน</label>
                  <input type="number" class="form-control rounded-3" v-model="form.quantity" min="0" />
                </div>
                
                <div class="col-md-3">
                  <label class="form-label fw-bold text-dark">ราคาต่อหน่วย (บาท)</label>
                  <input type="number" step="0.01" class="form-control rounded-3" v-model="form.unit_price" min="0" />
                </div>
                
                <div class="col-md-3">
                  <label class="form-label fw-bold text-success">ยอดรวม (บาท)</label>
                  <input type="text" class="form-control rounded-3 border-success text-success fw-bold bg-white" :value="(form.quantity * form.unit_price).toLocaleString('th-TH', { minimumFractionDigits: 2 })" readonly disabled />
                </div>
                
                <div class="col-md-3">
                  <label class="form-label fw-bold text-dark">ปีงบประมาณ</label>
                  <input type="text" class="form-control rounded-3" list="fiscalYearDatalist" v-model="form.fiscal_year" placeholder="เช่น 2570" />
                  <datalist id="fiscalYearDatalist">
                    <option v-for="yr in availableFiscalYears" :key="'dl_' + yr" :value="yr">ปี {{ yr }}</option>
                  </datalist>
                </div>

                <div class="col-md-12">
                  <label class="form-label fw-bold text-dark">หมวดโครงการ <span class="text-danger">*</span></label>
                  <select class="form-select rounded-3" v-model="form.category_id" required>
                    <option value="" disabled selected>เลือกหมวดโครงการ</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.category_name }}</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-bold text-dark border border-dark px-4 py-2 text-center" style="min-width: 150px;">ระยะที่ทำ</label>
                  <div class="row mt-2 px-2">
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="form-check d-flex align-items-start">
                        <input class="form-check-input flex-shrink-0 mt-1 shadow-none border-dark" style="width: 1.5em; height: 1.5em;" type="checkbox" value="Q1" id="q1" v-model="form.quarters" :disabled="isQuarterDisabled('Q1')">
                        <label class="form-check-label lh-sm ms-2" for="q1">
                          <span class="fw-bold text-dark">ไตรมาสที่ 1</span><br><small class="text-muted">ตุลาคม ถึง ธันวาคม</small>
                        </label>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="form-check d-flex align-items-start">
                        <input class="form-check-input flex-shrink-0 mt-1 shadow-none border-dark" style="width: 1.5em; height: 1.5em;" type="checkbox" value="Q2" id="q2" v-model="form.quarters" :disabled="isQuarterDisabled('Q2')">
                        <label class="form-check-label lh-sm ms-2" for="q2">
                          <span class="fw-bold text-dark">ไตรมาสที่ 2</span><br><small class="text-muted">มกราคม ถึง มีนาคม</small>
                        </label>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="form-check d-flex align-items-start">
                        <input class="form-check-input flex-shrink-0 mt-1 shadow-none border-dark" style="width: 1.5em; height: 1.5em;" type="checkbox" value="Q3" id="q3" v-model="form.quarters" :disabled="isQuarterDisabled('Q3')">
                        <label class="form-check-label lh-sm ms-2" for="q3">
                          <span class="fw-bold text-dark">ไตรมาสที่ 3</span><br><small class="text-muted">เมษายน ถึง มิถุนายน</small>
                        </label>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="form-check d-flex align-items-start">
                        <input class="form-check-input flex-shrink-0 mt-1 shadow-none border-dark" style="width: 1.5em; height: 1.5em;" type="checkbox" value="Q4" id="q4" v-model="form.quarters" :disabled="isQuarterDisabled('Q4')">
                        <label class="form-check-label lh-sm ms-2" for="q4">
                          <span class="fw-bold text-dark">ไตรมาสที่ 4</span><br><small class="text-muted">กรกฎาคม ถึง กันยายน</small>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 mt-4">
                  <hr class="text-muted m-0 mb-3">
                  <h6 class="fw-bold text-primary mb-3"><i class="bi bi-clock-history me-1"></i> ส่วนติดตามความคืบหน้า</h6>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold text-dark">สถานะ</label>
                  <select class="form-select rounded-3" v-model="form.status">
                    <option value="pending">รอดำเนินการ</option>
                    <option value="in_progress">กำลังดำเนินการ</option>
                    <option value="completed">เสร็จสิ้น</option>
                    <option value="cancelled">ยกเลิก</option>
                  </select>
                </div>


                
                <div class="col-md-6">
                  <label class="form-label fw-bold text-dark">ผู้รับผิดชอบ (กลุ่มงาน)</label>
                  <input class="form-control rounded-3" list="deptOptions" v-model="form.manager_name" placeholder="เลือกหรือพิมพ์ชื่อกลุ่มงาน">
                  <datalist id="deptOptions">
                    <option v-for="dept in departments" :key="dept.HR_DEPARTMENT_SUB_ID" :value="dept.HR_DEPARTMENT_SUB_NAME"></option>
                  </datalist>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer bg-white border-0 py-3">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold custom-shadow" @click="saveProject" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              <i class="bi bi-save me-1" v-else></i> บันทึกข้อมูล
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';
import * as XLSX from 'xlsx';
import Chart from 'chart.js/auto';

const router = useRouter();

const projects = ref([]);
const categories = ref([]);
const departments = ref([]);
const loading = ref(true);
const saving = ref(false);
const searchQuery = ref('');
const filterStatus = ref('');
const filterCategory = ref('');

const getCurrentThaiFiscalYear = () => {
  const now = new Date();
  const year = now.getFullYear() + 543;
  return now.getMonth() >= 9 ? year + 1 : year; // Oct (month index 9) starts new fiscal year in Thailand
};

const currentFiscalYear = String(getCurrentThaiFiscalYear());
const filterFiscalYear = ref(currentFiscalYear);
const showCharts = ref(true);

let modalInstance = null;
let progressModalInstance = null;
let categoryChartInstance = null;
let deptChartInstance = null;
let statusChartInstance = null;
let quarterChartInstance = null;
let chartRenderTimeout = null;

const progressForm = ref({
  id: null,
  project_name: '',
  quantity: 0,
  available_quarters: [],
  completed_quarters: []
});

const form = ref({
  id: null,
  project_name: '',
  description: '',
  quantity: 0,
  unit_price: 0,
  fiscal_year: '',
  category_id: '',
  quarters: [],
  status: 'pending',
  progress: 0,
  start_date: '',
  target_date: '',
  completed_date: '',
  manager_name: ''
});

const isEditMode = computed(() => !!form.value.id);

const availableFiscalYears = computed(() => {
  const currentFY = getCurrentThaiFiscalYear(); // 2570
  const years = new Set(['2570', '2569']);
  projects.value.forEach(p => {
    if (p.fiscal_year && String(p.fiscal_year).trim()) {
      years.add(String(p.fiscal_year).trim());
    }
  });
  return Array.from(years).sort((a, b) => b.localeCompare(a));
});

const getProjectCountByYear = (yr) => {
  return projects.value.filter(p => {
    const py = String(p.fiscal_year || '').trim();
    if (!py && yr === '2569') return true;
    return py === String(yr);
  }).length;
};

const baseFilteredProjects = computed(() => {
  return projects.value.filter(p => {
    const matchName = p.project_name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                      (p.description && p.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
    const matchCategory = filterCategory.value === '' || p.category_id == filterCategory.value;
    const pYear = String(p.fiscal_year || '').trim();
    const matchYear = filterFiscalYear.value === '' || 
                      pYear === filterFiscalYear.value || 
                      (filterFiscalYear.value === '2569' && !pYear);
    return matchName && matchCategory && matchYear;
  });
});

const totalProjects = computed(() => baseFilteredProjects.value.length);
const totalBudget = computed(() => {
  return baseFilteredProjects.value.reduce((sum, p) => sum + ((Number(p.quantity) || 0) * (Number(p.unit_price) || 0)), 0);
});
const completedProjects = computed(() => baseFilteredProjects.value.filter(p => p.status === 'completed').length);
const inProgressProjects = computed(() => baseFilteredProjects.value.filter(p => p.status === 'in_progress').length);

const completedBudget = computed(() => {
  return baseFilteredProjects.value
    .filter(p => p.status === 'completed')
    .reduce((sum, p) => sum + ((Number(p.quantity) || 0) * (Number(p.unit_price) || 0)), 0);
});

const inProgressBudget = computed(() => {
  return baseFilteredProjects.value
    .filter(p => p.status === 'in_progress')
    .reduce((sum, p) => sum + ((Number(p.quantity) || 0) * (Number(p.unit_price) || 0)), 0);
});

const pendingBudget = computed(() => {
  return baseFilteredProjects.value
    .filter(p => p.status === 'pending')
    .reduce((sum, p) => sum + ((Number(p.quantity) || 0) * (Number(p.unit_price) || 0)), 0);
});

const averageBudget = computed(() => {
  if (baseFilteredProjects.value.length === 0) return 0;
  return totalBudget.value / baseFilteredProjects.value.length;
});

const topBudgetProjects = computed(() => {
  return [...baseFilteredProjects.value]
    .map(p => ({
      ...p,
      total_budget: (Number(p.quantity) || 0) * (Number(p.unit_price) || 0)
    }))
    .filter(p => p.total_budget > 0)
    .sort((a, b) => b.total_budget - a.total_budget)
    .slice(0, 5);
});

const filteredProjects = computed(() => {
  return baseFilteredProjects.value.filter(p => {
    return filterStatus.value === '' || p.status === filterStatus.value;
  });
});

// Chart Render Functions
const renderCategoryChart = () => {
  const canvas = document.getElementById('categoryChartCanvas');
  if (!canvas) return;
  if (categoryChartInstance) {
    categoryChartInstance.destroy();
    categoryChartInstance = null;
  }

  const catMap = {};
  baseFilteredProjects.value.forEach(p => {
    const catName = p.category_name || 'ไม่ระบุหมวดหมู่';
    const budget = (Number(p.quantity) || 0) * (Number(p.unit_price) || 0);
    catMap[catName] = (catMap[catName] || 0) + budget;
  });

  const entries = Object.entries(catMap).sort((a, b) => b[1] - a[1]);
  const labels = entries.length > 0 ? entries.map(e => e[0]) : ['ไม่มีข้อมูล'];
  const data = entries.length > 0 ? entries.map(e => e[1]) : [0];
  const colors = [
    '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899',
    '#8b5cf6', '#3b82f6', '#14b8a6', '#f97316', '#64748b',
    '#84cc16', '#a855f7'
  ];

  categoryChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors.slice(0, Math.max(labels.length, 1)),
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            padding: 10,
            font: { size: 12 }
          }
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const val = ctx.raw || 0;
              const sum = data.reduce((a, b) => a + b, 0) || 1;
              const pct = ((val / sum) * 100).toFixed(1);
              return ` ${ctx.label}: ${val.toLocaleString('th-TH')} บาท (${pct}%)`;
            }
          }
        }
      }
    }
  });
};

const renderDeptChart = () => {
  const canvas = document.getElementById('deptChartCanvas');
  if (!canvas) return;
  if (deptChartInstance) {
    deptChartInstance.destroy();
    deptChartInstance = null;
  }

  const deptMap = {};
  baseFilteredProjects.value.forEach(p => {
    const dept = p.manager_name || 'ไม่ระบุกลุ่มงาน';
    const budget = (Number(p.quantity) || 0) * (Number(p.unit_price) || 0);
    deptMap[dept] = (deptMap[dept] || 0) + budget;
  });

  const entries = Object.entries(deptMap).sort((a, b) => b[1] - a[1]).slice(0, 8);
  const labels = entries.length > 0 ? entries.map(e => e[0]) : ['ไม่มีข้อมูล'];
  const data = entries.length > 0 ? entries.map(e => e[1]) : [0];

  deptChartInstance = new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'งบประมาณรวม',
        data,
        backgroundColor: 'rgba(14, 165, 233, 0.85)',
        hoverBackgroundColor: '#0284c7',
        borderRadius: 6,
        barPercentage: 0.65
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` งบประมาณ: ${ctx.raw.toLocaleString('th-TH')} บาท`
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(0, 0, 0, 0.05)' },
          ticks: {
            callback: (val) => {
              if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M ฿';
              if (val >= 1000) return (val / 1000).toFixed(0) + 'k ฿';
              return val + ' ฿';
            }
          }
        },
        y: {
          grid: { display: false },
          ticks: {
            font: { size: 11 }
          }
        }
      }
    }
  });
};

const renderStatusChart = () => {
  const canvas = document.getElementById('statusChartCanvas');
  if (!canvas) return;
  if (statusChartInstance) {
    statusChartInstance.destroy();
    statusChartInstance = null;
  }

  const statusMap = {
    completed: { label: 'เสร็จสิ้นแล้ว', color: '#10b981', amount: 0 },
    in_progress: { label: 'กำลังดำเนินการ', color: '#0ea5e9', amount: 0 },
    pending: { label: 'รอดำเนินการ', color: '#f59e0b', amount: 0 },
    cancelled: { label: 'ยกเลิก', color: '#ef4444', amount: 0 }
  };

  baseFilteredProjects.value.forEach(p => {
    const budget = (Number(p.quantity) || 0) * (Number(p.unit_price) || 0);
    const st = p.status || 'pending';
    if (statusMap[st]) {
      statusMap[st].amount += budget;
    }
  });

  const activeList = Object.values(statusMap).filter(s => s.amount > 0);
  const labels = activeList.length > 0 ? activeList.map(s => s.label) : ['ไม่มีข้อมูล'];
  const data = activeList.length > 0 ? activeList.map(s => s.amount) : [0];
  const colors = activeList.length > 0 ? activeList.map(s => s.color) : ['#e2e8f0'];

  statusChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: colors,
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            padding: 10,
            font: { size: 12 }
          }
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const val = ctx.raw || 0;
              const sum = data.reduce((a, b) => a + b, 0) || 1;
              const pct = ((val / sum) * 100).toFixed(1);
              return ` ${ctx.label}: ${val.toLocaleString('th-TH')} บาท (${pct}%)`;
            }
          }
        }
      }
    }
  });
};

const renderQuarterChart = () => {
  const canvas = document.getElementById('quarterChartCanvas');
  if (!canvas) return;
  if (quarterChartInstance) {
    quarterChartInstance.destroy();
    quarterChartInstance = null;
  }

  const quarterBudgets = { Q1: 0, Q2: 0, Q3: 0, Q4: 0 };
  baseFilteredProjects.value.forEach(p => {
    const budget = (Number(p.quantity) || 0) * (Number(p.unit_price) || 0);
    if (!budget) return;
    if (p.quarters && typeof p.quarters === 'string') {
      const qList = p.quarters.split(',').map(q => q.trim()).filter(Boolean);
      if (qList.length > 0) {
        const splitVal = budget / qList.length;
        qList.forEach(q => {
          if (quarterBudgets[q] !== undefined) {
            quarterBudgets[q] += splitVal;
          }
        });
      }
    }
  });

  const labels = ['ไตรมาส 1 (ต.ค.-ธ.ค.)', 'ไตรมาส 2 (ม.ค.-มี.ค.)', 'ไตรมาส 3 (เม.ย.-มิ.ย.)', 'ไตรมาส 4 (ก.ค.-ก.ย.)'];
  const data = [quarterBudgets.Q1, quarterBudgets.Q2, quarterBudgets.Q3, quarterBudgets.Q4];
  const colors = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b'];

  quarterChartInstance = new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'งบประมาณตามไตรมาส',
        data,
        backgroundColor: colors,
        borderRadius: 8,
        barPercentage: 0.55
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => ` งบประมาณ: ${ctx.raw.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`
          }
        }
      },
      scales: {
        y: {
          grid: { color: 'rgba(0, 0, 0, 0.05)' },
          ticks: {
            callback: (val) => {
              if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M ฿';
              if (val >= 1000) return (val / 1000).toFixed(0) + 'k ฿';
              return val + ' ฿';
            }
          }
        },
        x: {
          grid: { display: false }
        }
      }
    }
  });
};

const renderCharts = () => {
  if (!showCharts.value) return;
  renderCategoryChart();
  renderDeptChart();
  renderStatusChart();
  renderQuarterChart();
};

const scheduleRenderCharts = () => {
  if (!showCharts.value) return;
  if (chartRenderTimeout) clearTimeout(chartRenderTimeout);
  chartRenderTimeout = setTimeout(() => {
    nextTick(() => {
      renderCharts();
    });
  }, 100);
};

const toggleCharts = () => {
  showCharts.value = !showCharts.value;
  if (showCharts.value) {
    scheduleRenderCharts();
  }
};

watch([baseFilteredProjects, showCharts], () => {
  scheduleRenderCharts();
}, { deep: true });

onBeforeUnmount(() => {
  if (categoryChartInstance) categoryChartInstance.destroy();
  if (deptChartInstance) deptChartInstance.destroy();
  if (statusChartInstance) statusChartInstance.destroy();
  if (quarterChartInstance) quarterChartInstance.destroy();
  if (chartRenderTimeout) clearTimeout(chartRenderTimeout);
});

const exportToExcel = () => {
  if (filteredProjects.value.length === 0) {
    Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลสำหรับนำออก', 'warning');
    return;
  }
  
  const exportData = filteredProjects.value.map(p => ({
    'รหัสโครงการ': p.id,
    'ชื่อโครงการ/กิจกรรม': p.project_name,
    'หมวดโครงการ': p.category_name || '-',
    'งบประมาณรวม (บาท)': (p.quantity || 0) * (p.unit_price || 0),
    'สถานะ': getStatusText(p.status),
    'ความคืบหน้า (%)': p.progress,
    'จำนวนที่เสร็จ': p.completed_quantity || 0,
    'เป้าหมาย (จำนวน)': p.quantity || 0,
    'ไตรมาสที่เลือก': p.quarters || '-',
    'ไตรมาสที่เสร็จ': p.completed_quarters || '-',
    'วันที่เริ่ม': formatDate(p.start_date),
    'วันที่เป้าหมาย': formatDate(p.target_date),
    'วันที่เสร็จสิ้น': formatDate(p.completed_date),
    'ผู้รับผิดชอบ': p.manager_name || '-'
  }));

  const worksheet = XLSX.utils.json_to_sheet(exportData);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Projects");
  
  const dateStr = new Date().toISOString().slice(0, 10);
  XLSX.writeFile(workbook, `projects_report_${dateStr}.xlsx`);
};



const isQuarterDisabled = (q) => {
  if (!form.value.quarters) return false;
  if (!form.value.quarters.includes(q) && form.value.quarters.length >= (form.value.quantity || 0)) {
    return true;
  }
  return false;
};

// Reset quarters when quantity changes
watch(() => form.value.quantity, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    form.value.quarters = [];
  }
});

// Auto 100% when status completed
watch(() => form.value.status, (newStatus) => {
  if (newStatus === 'completed') {
    form.value.progress = 100;
    if (!form.value.completed_date) {
      form.value.completed_date = new Date().toISOString().slice(0, 10);
    }
  }
});

const fetchProjects = async () => {
  loading.value = true;
  try {
    const token = localStorage.getItem('user_token');
    const res = await axios.get(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/get_projects.php`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (res.data.status === 'success') {
      projects.value = res.data.data;
      scheduleRenderCharts();
    } else {
      Swal.fire('Error', res.data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
    }
  } catch (error) {
    console.error('Fetch Projects Error:', error);
    Swal.fire('Error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
  } finally {
    loading.value = false;
    scheduleRenderCharts();
  }
};

const fetchCategories = async () => {
  try {
    const token = localStorage.getItem('user_token');
    const res = await axios.get(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/get_project_categories.php`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (res.data.status === 'success') {
      categories.value = res.data.data;
    }
  } catch (error) {
    console.error('Fetch Categories Error:', error);
  }
};

const fetchDepartments = async () => {
  try {
    const res = await axios.get('/api-hosoffice/get_departments.php');
    if (res.data.status === 'success') {
      departments.value = res.data.data;
    }
  } catch (error) {
    console.error('Fetch Departments Error:', error);
  }
};

const openModal = (item = null) => {
  if (item) {
    form.value = { ...item };
    if (item.quarters) {
      form.value.quarters = typeof item.quarters === 'string' ? item.quarters.split(',') : item.quarters;
    } else {
      form.value.quarters = [];
    }
  } else {
    form.value = {
      id: null,
      project_name: '',
      description: '',
      quantity: 0,
      unit_price: 0,
      fiscal_year: filterFiscalYear.value || '2570',
      category_id: '',
      quarters: [],
      status: 'pending',
      progress: 0,
      start_date: new Date().toISOString().slice(0, 10),
      target_date: '',
      completed_date: '',
      manager_name: ''
    };
  }
  
  if (!modalInstance) {
    modalInstance = new bootstrap.Modal(document.getElementById('projectModal'));
  }
  modalInstance.show();
};

const copyProjectsTo2570 = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันคัดลอกโครงการสู่ปี 2570?',
    html: 'ระบบจะนำเข้าและสร้างโครงการใหม่สำหรับปีงบประมาณ 2570 โดยใช้ข้อมูลตั้งต้นจากปี 2569<br><br><span class="text-danger small">* สถานะจะถูกตั้งเป็น "รอดำเนินการ" และความคืบหน้า 0%</span>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#198754',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-copy me-1"></i> ยืนยันคัดลอก',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    try {
      const token = localStorage.getItem('user_token');
      const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/copy_projects_to_year.php`, {
        from_year: '2569',
        to_year: '2570',
        action: 'copy'
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (res.data.status === 'success') {
        await Swal.fire('สำเร็จ!', res.data.message, 'success');
        await fetchProjects();
        filterFiscalYear.value = '2570';
      } else {
        Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถคัดลอกได้', 'error');
      }
    } catch (err) {
      console.error(err);
      Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
  }
};

const updateProjectsYearTo2570 = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันปรับเปลี่ยนปีงบประมาณ?',
    html: 'คุณต้องการเปลี่ยนปีงบประมาณของโครงการทั้งหมดจากปี 2569 เป็นปี 2570 ใช่หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ffc107',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'ใช่, เปลี่ยนเป็นปี 2570',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    try {
      const token = localStorage.getItem('user_token');
      const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/copy_projects_to_year.php`, {
        from_year: '2569',
        to_year: '2570',
        action: 'update'
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (res.data.status === 'success') {
        await Swal.fire('สำเร็จ!', res.data.message, 'success');
        await fetchProjects();
        filterFiscalYear.value = '2570';
      } else {
        Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถปรับเปลี่ยนได้', 'error');
      }
    } catch (err) {
      console.error(err);
      Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
  }
};

const openProgressModal = (item) => {
  progressForm.value = {
    id: item.id,
    project_name: item.project_name,
    quantity: item.quantity,
    available_quarters: item.quarters ? (typeof item.quarters === 'string' ? item.quarters.split(',') : item.quarters) : [],
    completed_quarters: item.completed_quarters ? (typeof item.completed_quarters === 'string' ? item.completed_quarters.split(',') : item.completed_quarters) : []
  };
  if (!progressModalInstance) {
    progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
  }
  progressModalInstance.show();
};

const saveProgress = async () => {
  saving.value = true;
  try {
    const token = localStorage.getItem('user_token');
    const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/update_project_progress.php`, progressForm.value, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (res.data.status === 'success') {
      progressModalInstance.hide();
      fetchProjects();
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500
      });
    } else {
      Swal.fire('Error', res.data.message, 'error');
    }
  } catch (error) {
    console.error('Update Progress Error:', error);
  } finally {
    saving.value = false;
  }
};

const saveProject = async () => {
  if (!form.value.project_name) {
    Swal.fire('แจ้งเตือน', 'กรุณากรอกชื่อโครงการ', 'warning');
    return;
  }

  saving.value = true;
  try {
    const token = localStorage.getItem('user_token');
    const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/save_project.php`, form.value, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (res.data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: res.data.message,
        timer: 1500,
        showConfirmButton: false
      });
      modalInstance.hide();
      fetchProjects();
    } else {
      Swal.fire('Error', res.data.message || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    }
  } catch (error) {
    console.error('Save Project Error:', error);
    Swal.fire('Error', 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์', 'error');
  } finally {
    saving.value = false;
  }
};

const deleteProject = async (id) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: "คุณต้องการลบข้อมูลโครงการนี้ใช่หรือไม่? ข้อมูลจะไม่สามารถกู้คืนได้!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'ใช่, ลบเลย!',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    try {
      const token = localStorage.getItem('user_token');
      const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/projects/delete_project.php`, { id }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (res.data.status === 'success') {
        Swal.fire('ลบสำเร็จ!', res.data.message, 'success');
        fetchProjects();
      } else {
        Swal.fire('Error', res.data.message || 'ไม่สามารถลบข้อมูลได้', 'error');
      }
    } catch (error) {
      console.error('Delete Error:', error);
      Swal.fire('Error', 'ระบบขัดข้อง', 'error');
    }
  }
};

// Utilities
const getStatusBadge = (status) => {
  const badges = {
    pending: 'bg-secondary bg-opacity-10 text-secondary border border-secondary',
    in_progress: 'bg-primary bg-opacity-10 text-primary border border-primary',
    completed: 'bg-success bg-opacity-10 text-success border border-success',
    cancelled: 'bg-danger bg-opacity-10 text-danger border border-danger'
  };
  return badges[status] || badges['pending'];
};

const getStatusIcon = (status) => {
  const icons = {
    pending: 'bi-hourglass-split',
    in_progress: 'bi-play-circle-fill',
    completed: 'bi-check-circle-fill',
    cancelled: 'bi-x-circle-fill'
  };
  return icons[status] || icons['pending'];
};

const getStatusText = (status) => {
  const texts = {
    pending: 'รอดำเนินการ',
    in_progress: 'กำลังดำเนินการ',
    completed: 'เสร็จสิ้น',
    cancelled: 'ยกเลิก'
  };
  return texts[status] || status;
};

const getProgressColor = (progress) => {
  if (progress === 100) return 'bg-success';
  if (progress >= 50) return 'bg-primary';
  return 'bg-warning';
};

const formatDate = (dateStr) => {
  if (!dateStr || dateStr === '0000-00-00') return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString('th-TH', options);
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.substring(0, 2).toUpperCase();
};

const checkPermission = async () => {
  try {
    const token = localStorage.getItem('user_token');
    const res = await axios.get('/api-hosoffice/get_user_profile.php', {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (res.data && res.data.status === 'success') {
      const accessUser = res.data.access_user || '';
      const userPerms = accessUser.split(':');
      if (!userPerms.includes('administrator') && !userPerms.includes('menu_projects')) {
        Swal.fire({
          icon: 'error',
          title: 'ไม่มีสิทธิ์เข้าถึง',
          text: 'คุณไม่มีสิทธิ์เข้าถึงระบบงานโครงการ',
          confirmButtonText: 'กลับหน้าหลัก',
          allowOutsideClick: false
        }).then(() => {
          router.push('/home-backoffice');
        });
        return false;
      }
      return true;
    }
    return false;
  } catch (error) {
    console.error('Check Permission Error:', error);
    router.push('/home-backoffice');
    return false;
  }
};

onMounted(async () => {
  const hasAccess = await checkPermission();
  if (!hasAccess) return;
  fetchCategories();
  fetchDepartments();
  await fetchProjects();
  scheduleRenderCharts();
});
</script>

<style scoped>
.hover-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}

.transition-hover {
  transition: all 0.2s ease;
}
.transition-hover:hover {
  background-color: #f8f9fa !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.custom-range {
  height: 1.5rem;
  padding: 0;
}
.custom-range::-webkit-slider-thumb {
  background: #0d6efd;
}
.custom-range::-webkit-slider-runnable-track {
  background: #e9ecef;
  border-radius: 1rem;
}
.custom-shadow {
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2) !important;
}
</style>
