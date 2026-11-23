<template>
  <div class="report-container">
    <div class="report-header">
      <h2 class="report-title">รายงานจำนวนผู้มารับบริการรายเดือน</h2>
      <p class="report-subtitle">แสดงข้อมูลเปรียบเทียบจำนวนผู้มารับบริการแผนกผู้ป่วยนอก (OPD)</p>
    </div>

    <div class="report-controls card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end justify-content-center">
          <div class="col-md-4">
            <label class="form-label fw-bold">เปรียบเทียบปีงบประมาณที่ 1</label>
            <select v-model="compareYear1" class="form-select form-select-lg">
              <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
            </select>
          </div>
          <div class="col-md-auto d-flex align-items-center justify-content-center pb-2">
            <span class="fs-4 fw-bold text-muted">VS</span>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">เปรียบเทียบปีงบประมาณที่ 2</label>
            <select v-model="compareYear2" class="form-select form-select-lg">
              <option v-for="y in yearOptions" :key="y" :value="y">ปีงบฯ {{ y }}</option>
            </select>
          </div>
          <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-lg" @click="fetchData">
              <i class="bi bi-search me-2"></i>ค้นหา
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="column-gap">
      <div class="chart-container card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">กราฟเปรียบเทียบจำนวนผู้มารับบริการ</h5>
        </div>
        <div class="card-body">
          <div style="position: relative; height: 350px; width: 100%">
            <canvas ref="chartCanvas"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 align-items-stretch">
      <!-- Left: Monthly Summary Table -->
      <div class="col-12 col-xl-5">
        <div class="table-container card shadow-sm h-100">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">ตารางสรุปจำนวนผู้มารับบริการ</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-primary">
                  <tr>
                    <th class="text-center py-3" style="width: 30%">เดือน</th>
                    <th class="text-center py-3" style="width: 35%">ปีงบฯ {{ compareYear1 }} (คน)</th>
                    <th class="text-center py-3" style="width: 35%">ปีงบฯ {{ compareYear2 }} (คน)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(label, index) in monthLabels" :key="index">
                    <td class="text-center fw-bold">{{ label }}</td>
                    <td class="text-center text-secondary">{{ formatNumber(plotData1[index]) }}</td>
                    <td class="text-center text-primary">{{ formatNumber(plotData2[index]) }}</td>
                  </tr>
                  <tr class="table-secondary fw-bold fs-6">
                    <td class="text-center py-3">รวมทั้งหมด</td>
                    <td class="text-center py-3 text-secondary">{{ formatNumber(totalPatients1) }}</td>
                    <td class="text-center py-3 text-primary">{{ formatNumber(totalPatients2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Pttype Summary Section -->
      <div class="col-12 col-xl-7">
        <div class="table-container card shadow-sm h-100">
      <div
        class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2"
      >
        <h5 class="mb-0">
          <i class="bi bi-shield-check me-2"></i>สรุปจำนวนผู้มารับบริการและค่ารักษาแยกตามสิทธิ
        </h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="btn-group btn-group-sm bg-white rounded-pill p-1" role="group">
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="pttypeViewMode === 'year1' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="pttypeViewMode = 'year1'"
            >
              ปีงบฯ {{ compareYear1 }}
            </button>
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="pttypeViewMode === 'year2' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="pttypeViewMode = 'year2'"
            >
              ปีงบฯ {{ compareYear2 }}
            </button>
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="pttypeViewMode === 'compare' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="pttypeViewMode = 'compare'"
            >
              เปรียบเทียบ 2 ปี
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Summary KPI Strip -->
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="p-3 rounded-3 bg-light border h-100">
              <div class="small text-muted mb-1">
                จำนวนสิทธิที่มีผู้รับบริการ ({{ activePttypeYearLabel }})
              </div>
              <div class="fs-4 fw-bold text-dark">{{ formatNumber(filteredPttypeRows.length) }} สิทธิ</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded-3 bg-primary-subtle border border-primary-subtle h-100">
              <div class="small text-primary-emphasis mb-1">
                รวมผู้รับบริการ ({{ activePttypeYearLabel }})
              </div>
              <div class="fs-4 fw-bold text-primary">
                {{ formatNumber(activePttypeTotals.visits) }} ครั้ง
                <span class="fs-6 fw-normal text-secondary">
                  ({{ formatNumber(activePttypeTotals.persons) }} คน/HN)
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle h-100">
              <div class="small text-success-emphasis mb-1">
                รวมค่ารักษาพยาบาลทั้งหมด ({{ activePttypeYearLabel }})
              </div>
              <div class="fs-4 fw-bold text-success">{{ formatMoney(activePttypeTotals.income) }} บาท</div>
            </div>
          </div>
        </div>

        <!-- Search Filter -->
        <div class="row g-2 align-items-center mb-3">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input
                type="text"
                class="form-control"
                v-model="pttypeSearch"
                placeholder="ค้นหารหัสสิทธิ หรือ ชื่อสิทธิการรักษา..."
              />
              <button
                v-if="pttypeSearch"
                type="button"
                class="btn btn-outline-secondary"
                @click="pttypeSearch = ''"
              >
                ล้าง
              </button>
            </div>
          </div>
          <div class="col-md-7 text-md-end small text-muted">
            * จำนวนคน (HN) นับแบบไม่ซ้ำรายบุคคลในแต่ละสิทธิ • ยอดเงินคำนวณจากค่ารักษา (income) ในระบบ HOSxP
          </div>
        </div>

        <div v-if="loadingPttype" class="text-center py-4 text-muted">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดข้อมูลแยกตามสิทธิ...
        </div>

        <!-- Single Year Table -->
        <div v-else-if="pttypeViewMode !== 'compare'" class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th class="text-center py-3" style="width: 60px">#</th>
                <th class="text-center py-3" style="width: 90px">รหัส</th>
                <th class="py-3">ชื่อสิทธิการรักษา</th>
                <th class="text-end py-3">จำนวนคน (HN)</th>
                <th class="text-end py-3">จำนวนครั้ง (Visit)</th>
                <th class="text-end py-3">เป็นเงินทั้งหมด (บาท)</th>
                <th class="text-end py-3" style="width: 110px">สัดส่วนเงิน</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredPttypeRows.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูลสิทธิการรักษา</td>
              </tr>
              <tr v-for="(row, idx) in paginatedPttypeRows" :key="row.pttype + '-' + idx">
                <td class="text-center text-muted">{{ (pttypePage - 1) * pttypePageSize + idx + 1 }}</td>
                <td class="text-center">
                  <span class="badge bg-secondary-subtle text-dark border">{{ row.pttype || '-' }}</span>
                </td>
                <td class="fw-semibold text-dark">{{ row.pttype_name }}</td>
                <td class="text-end">{{ formatNumber(row.person_count) }}</td>
                <td class="text-end text-primary fw-semibold">{{ formatNumber(row.visit_count) }}</td>
                <td class="text-end text-success fw-bold">{{ formatMoney(row.total_income) }}</td>
                <td class="text-end text-muted small">
                  {{ activePttypeTotals.income > 0 ? ((row.total_income / activePttypeTotals.income) * 100).toFixed(2) : '0.00' }}%
                </td>
              </tr>
              <tr v-if="filteredPttypeRows.length > 0" class="table-secondary fw-bold">
                <td colspan="3" class="text-center py-3">รวมทั้งหมด ({{ filteredPttypeRows.length }} สิทธิ)</td>
                <td class="text-end py-3">{{ formatNumber(activePttypeTotals.persons) }}</td>
                <td class="text-end py-3 text-primary">{{ formatNumber(activePttypeTotals.visits) }}</td>
                <td class="text-end py-3 text-success">{{ formatMoney(activePttypeTotals.income) }}</td>
                <td class="text-end py-3">100.00%</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Compare 2 Years Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th class="text-center py-2" rowspan="2" style="width: 55px">#</th>
                <th class="text-center py-2" rowspan="2" style="width: 80px">รหัส</th>
                <th class="py-2" rowspan="2">ชื่อสิทธิการรักษา</th>
                <th class="text-center py-2 border-start" colspan="3">ปีงบฯ {{ compareYear1 }}</th>
                <th class="text-center py-2 border-start" colspan="3">ปีงบฯ {{ compareYear2 }}</th>
              </tr>
              <tr>
                <th class="text-end py-2 border-start small">คน (HN)</th>
                <th class="text-end py-2 small">ครั้ง</th>
                <th class="text-end py-2 small">เป็นเงิน (บาท)</th>
                <th class="text-end py-2 border-start small">คน (HN)</th>
                <th class="text-end py-2 small">ครั้ง</th>
                <th class="text-end py-2 small">เป็นเงิน (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredCompareRows.length === 0">
                <td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูลสิทธิการรักษา</td>
              </tr>
              <tr v-for="(row, idx) in paginatedCompareRows" :key="'cmp-' + row.pttype + '-' + idx">
                <td class="text-center text-muted">{{ (pttypePage - 1) * pttypePageSize + idx + 1 }}</td>
                <td class="text-center">
                  <span class="badge bg-secondary-subtle text-dark border">{{ row.pttype || '-' }}</span>
                </td>
                <td class="fw-semibold text-dark">{{ row.pttype_name }}</td>
                <td class="text-end border-start text-secondary">{{ formatNumber(row.y1_persons) }}</td>
                <td class="text-end text-secondary">{{ formatNumber(row.y1_visits) }}</td>
                <td class="text-end text-secondary">{{ formatMoney(row.y1_income) }}</td>
                <td class="text-end border-start text-primary">{{ formatNumber(row.y2_persons) }}</td>
                <td class="text-end text-primary fw-semibold">{{ formatNumber(row.y2_visits) }}</td>
                <td class="text-end text-success fw-bold">{{ formatMoney(row.y2_income) }}</td>
              </tr>
              <tr v-if="filteredCompareRows.length > 0" class="table-secondary fw-bold">
                <td colspan="3" class="text-center py-3">รวมทั้งหมด</td>
                <td class="text-end py-3 border-start">{{ formatNumber(compareTotals.y1_persons) }}</td>
                <td class="text-end py-3">{{ formatNumber(compareTotals.y1_visits) }}</td>
                <td class="text-end py-3">{{ formatMoney(compareTotals.y1_income) }}</td>
                <td class="text-end py-3 border-start text-primary">{{ formatNumber(compareTotals.y2_persons) }}</td>
                <td class="text-end py-3 text-primary">{{ formatNumber(compareTotals.y2_visits) }}</td>
                <td class="text-end py-3 text-success">{{ formatMoney(compareTotals.y2_income) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div
          v-if="!loadingPttype && pttypeTotalRows > pttypePageSize"
          class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top"
        >
          <div class="small text-muted">
            แสดง {{ pttypeStartIndex }}–{{ pttypeEndIndex }} จากทั้งหมด {{ formatNumber(pttypeTotalRows) }} รายการ
          </div>
          <nav aria-label="Pttype pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: pttypePage <= 1 }">
                <button
                  type="button"
                  class="page-link"
                  @click="pttypePage = Math.max(1, pttypePage - 1)"
                  :disabled="pttypePage <= 1"
                >
                  <i class="bi bi-chevron-left"></i> ก่อนหน้า
                </button>
              </li>
              <li
                v-for="p in pttypePageNumbers"
                :key="'pg-' + p"
                class="page-item"
                :class="{ active: p === pttypePage, disabled: p === '...' }"
              >
                <button
                  type="button"
                  class="page-link"
                  @click="p !== '...' && (pttypePage = p)"
                  :disabled="p === '...'"
                >
                  {{ p }}
                </button>
              </li>
              <li class="page-item" :class="{ disabled: pttypePage >= pttypeTotalPages }">
                <button
                  type="button"
                  class="page-link"
                  @click="pttypePage = Math.min(pttypeTotalPages, pttypePage + 1)"
                  :disabled="pttypePage >= pttypeTotalPages"
                >
                  ถัดไป <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
      </div>
    </div>

    <!-- Top OPD Diagnosis Section -->
    <div class="table-container card shadow-sm mt-4">
      <div
        class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2"
      >
        <h5 class="mb-0">
          <i class="bi bi-heart-pulse-fill me-2"></i>อันดับโรคผู้ป่วยนอก (Top OPD Diagnosis)
        </h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <div class="btn-group btn-group-sm bg-white rounded-pill p-1" role="group">
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="diagViewMode === 'year1' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="diagViewMode = 'year1'"
            >
              ปีงบฯ {{ compareYear1 }}
            </button>
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="diagViewMode === 'year2' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="diagViewMode = 'year2'"
            >
              ปีงบฯ {{ compareYear2 }}
            </button>
            <button
              type="button"
              class="btn rounded-pill px-3 fw-semibold"
              :class="diagViewMode === 'compare' ? 'btn-primary text-white' : 'btn-light text-dark'"
              @click="diagViewMode = 'compare'"
            >
              เปรียบเทียบ 2 ปี
            </button>
          </div>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-2 align-items-center mb-3">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input
                type="text"
                class="form-control"
                v-model="diagSearch"
                placeholder="ค้นหารหัส ICD-10 หรือชื่อโรค..."
              />
              <button
                v-if="diagSearch"
                type="button"
                class="btn btn-outline-secondary"
                @click="diagSearch = ''"
              >
                ล้าง
              </button>
            </div>
          </div>
          <div class="col-md-7 d-flex flex-wrap align-items-center justify-content-md-end gap-3">
            <div class="form-check form-switch mb-0">
              <input
                class="form-check-input"
                type="checkbox"
                id="excludeZCodesSwitch"
                v-model="excludeZCodes"
              />
              <label class="form-check-label small fw-semibold text-dark" for="excludeZCodesSwitch">
                ตัดรหัส Z (ตรวจคัดกรอง/ล้างแผล/รับบริการ)
              </label>
            </div>
            <span class="small text-muted">
              แสดงสูงสุด 200 อันดับแรก ({{ activeDiagYearLabel }})
            </span>
          </div>
        </div>

        <div v-if="loadingDiag" class="text-center py-4 text-muted">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดข้อมูลอันดับโรคผู้ป่วยนอก...
        </div>

        <!-- Single Year Diag Table -->
        <div v-else-if="diagViewMode !== 'compare'" class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th class="text-center py-3" style="width: 70px">อันดับ</th>
                <th class="text-center py-3" style="width: 110px">รหัส ICD-10</th>
                <th class="py-3">ชื่อโรค / การวินิจฉัยหลัก (PDX)</th>
                <th class="text-end py-3">จำนวนคน (HN)</th>
                <th class="text-end py-3">จำนวนครั้ง (Visit)</th>
                <th class="text-end py-3">ค่ารักษา (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedDiagRows.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูลอันดับโรค</td>
              </tr>
              <tr v-for="(row, idx) in paginatedDiagRows" :key="'dg-' + row.pdx + '-' + idx">
                <td class="text-center">
                  <span
                    class="badge rounded-pill"
                    :class="(diagPage - 1) * diagPageSize + idx < 3 ? 'bg-warning text-dark fw-bold' : 'bg-light text-secondary border'"
                  >
                    {{ (diagPage - 1) * diagPageSize + idx + 1 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge bg-primary-subtle text-primary border fw-semibold">{{ row.pdx }}</span>
                </td>
                <td>
                  <div class="fw-semibold text-dark">
                    {{ row.icd_tname && row.icd_tname !== '-' ? row.icd_tname : row.icd_name }}
                  </div>
                  <div
                    v-if="row.icd_tname && row.icd_tname !== '-' && row.icd_name !== row.icd_tname"
                    class="small text-muted"
                  >
                    {{ row.icd_name }}
                  </div>
                </td>
                <td class="text-end">{{ formatNumber(row.person_count) }}</td>
                <td class="text-end text-primary fw-bold">{{ formatNumber(row.visit_count) }}</td>
                <td class="text-end text-success fw-semibold">{{ formatMoney(row.total_income) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Compare 2 Years Diag Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover table-striped align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th class="text-center py-2" rowspan="2" style="width: 65px">อันดับ</th>
                <th class="text-center py-2" rowspan="2" style="width: 100px">ICD-10</th>
                <th class="py-2" rowspan="2">ชื่อโรค / การวินิจฉัยหลัก (PDX)</th>
                <th class="text-center py-2 border-start" colspan="3">ปีงบฯ {{ compareYear1 }}</th>
                <th class="text-center py-2 border-start" colspan="3">ปีงบฯ {{ compareYear2 }}</th>
              </tr>
              <tr>
                <th class="text-end py-2 border-start small">คน</th>
                <th class="text-end py-2 small">ครั้ง</th>
                <th class="text-end py-2 small">เป็นเงิน (บาท)</th>
                <th class="text-end py-2 border-start small">คน</th>
                <th class="text-end py-2 small">ครั้ง</th>
                <th class="text-end py-2 small">เป็นเงิน (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="paginatedDiagCompareRows.length === 0">
                <td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูลอันดับโรค</td>
              </tr>
              <tr v-for="(row, idx) in paginatedDiagCompareRows" :key="'dgc-' + row.pdx + '-' + idx">
                <td class="text-center">
                  <span
                    class="badge rounded-pill"
                    :class="(diagPage - 1) * diagPageSize + idx < 3 ? 'bg-warning text-dark fw-bold' : 'bg-light text-secondary border'"
                  >
                    {{ (diagPage - 1) * diagPageSize + idx + 1 }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge bg-primary-subtle text-primary border fw-semibold">{{ row.pdx }}</span>
                </td>
                <td>
                  <div class="fw-semibold text-dark">
                    {{ row.icd_tname && row.icd_tname !== '-' ? row.icd_tname : row.icd_name }}
                  </div>
                  <div
                    v-if="row.icd_tname && row.icd_tname !== '-' && row.icd_name !== row.icd_tname"
                    class="small text-muted"
                  >
                    {{ row.icd_name }}
                  </div>
                </td>
                <td class="text-end border-start text-secondary">{{ formatNumber(row.y1_persons) }}</td>
                <td class="text-end text-secondary">{{ formatNumber(row.y1_visits) }}</td>
                <td class="text-end text-secondary">{{ formatMoney(row.y1_income) }}</td>
                <td class="text-end border-start text-primary">{{ formatNumber(row.y2_persons) }}</td>
                <td class="text-end text-primary fw-bold">{{ formatNumber(row.y2_visits) }}</td>
                <td class="text-end text-success fw-semibold">{{ formatMoney(row.y2_income) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Diag Pagination Bar -->
        <div
          v-if="!loadingDiag && diagTotalRows > diagPageSize"
          class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top"
        >
          <div class="small text-muted">
            แสดงอันดับ {{ diagStartIndex }}–{{ diagEndIndex }} จากทั้งหมด {{ formatNumber(diagTotalRows) }} โรค
          </div>
          <nav aria-label="Diagnosis pagination">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: diagPage <= 1 }">
                <button
                  type="button"
                  class="page-link"
                  @click="diagPage = Math.max(1, diagPage - 1)"
                  :disabled="diagPage <= 1"
                >
                  <i class="bi bi-chevron-left"></i> ก่อนหน้า
                </button>
              </li>
              <li
                v-for="p in diagPageNumbers"
                :key="'dpg-' + p"
                class="page-item"
                :class="{ active: p === diagPage, disabled: p === '...' }"
              >
                <button
                  type="button"
                  class="page-link"
                  @click="p !== '...' && (diagPage = p)"
                  :disabled="p === '...'"
                >
                  {{ p }}
                </button>
              </li>
              <li class="page-item" :class="{ disabled: diagPage >= diagTotalPages }">
                <button
                  type="button"
                  class="page-link"
                  @click="diagPage = Math.min(diagTotalPages, diagPage + 1)"
                  :disabled="diagPage >= diagTotalPages"
                >
                  ถัดไป <i class="bi bi-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import Chart from 'chart.js/auto';

const currentYear = new Date().getFullYear() + 543 + (new Date().getMonth() >= 9 ? 1 : 0);
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i);

const compareYear1 = ref(currentYear - 1);
const compareYear2 = ref(currentYear);

const plotData1 = ref(Array(12).fill(0));
const plotData2 = ref(Array(12).fill(0));
const pttypeData1 = ref([]);
const pttypeData2 = ref([]);
const loadingPttype = ref(false);
const pttypeViewMode = ref('year2');
const pttypeSearch = ref('');
const pttypePage = ref(1);
const pttypePageSize = 10;

const diagData1 = ref([]);
const diagData2 = ref([]);
const loadingDiag = ref(false);
const diagViewMode = ref('year2');
const diagSearch = ref('');
const excludeZCodes = ref(false);
const diagPage = ref(1);
const diagPageSize = 10;

const chartInstance = ref(null);
const chartCanvas = ref(null);

const monthLabels = [
  'ต.ค.',
  'พ.ย.',
  'ธ.ค.',
  'ม.ค.',
  'ก.พ.',
  'มี.ค.',
  'เม.ย.',
  'พ.ค.',
  'มิ.ย.',
  'ก.ค.',
  'ส.ค.',
  'ก.ย.'
];
const fyMonths = ['10', '11', '12', '01', '02', '03', '04', '05', '06', '07', '08', '09'];

const totalPatients1 = computed(() => {
  return plotData1.value.reduce((sum, val) => sum + val, 0);
});

const totalPatients2 = computed(() => {
  return plotData2.value.reduce((sum, val) => sum + val, 0);
});

const formatNumber = (num) => {
  if (!num) return '0';
  return parseInt(num).toLocaleString();
};

const formatMoney = (num) => {
  const val = Number(num || 0);
  return val.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const activePttypeYearLabel = computed(() => {
  return pttypeViewMode.value === 'year1'
    ? `ปีงบฯ ${compareYear1.value}`
    : `ปีงบฯ ${compareYear2.value}`;
});

const filteredPttypeRows = computed(() => {
  const rows = pttypeViewMode.value === 'year1' ? pttypeData1.value : pttypeData2.value;
  const q = pttypeSearch.value.trim().toLowerCase();
  if (!q) return rows;
  return rows.filter(
    (r) =>
      String(r.pttype || '').toLowerCase().includes(q) ||
      String(r.pttype_name || '').toLowerCase().includes(q)
  );
});

const activePttypeTotals = computed(() => {
  return filteredPttypeRows.value.reduce(
    (acc, r) => {
      acc.persons += Number(r.person_count || 0);
      acc.visits += Number(r.visit_count || 0);
      acc.income += Number(r.total_income || 0);
      return acc;
    },
    { persons: 0, visits: 0, income: 0 }
  );
});

const filteredCompareRows = computed(() => {
  const map = new Map();
  (pttypeData1.value || []).forEach((r) => {
    const key = String(r.pttype || r.pttype_name);
    map.set(key, {
      pttype: r.pttype,
      pttype_name: r.pttype_name,
      y1_persons: Number(r.person_count || 0),
      y1_visits: Number(r.visit_count || 0),
      y1_income: Number(r.total_income || 0),
      y2_persons: 0,
      y2_visits: 0,
      y2_income: 0
    });
  });
  (pttypeData2.value || []).forEach((r) => {
    const key = String(r.pttype || r.pttype_name);
    const existing = map.get(key) || {
      pttype: r.pttype,
      pttype_name: r.pttype_name,
      y1_persons: 0,
      y1_visits: 0,
      y1_income: 0,
      y2_persons: 0,
      y2_visits: 0,
      y2_income: 0
    };
    existing.y2_persons = Number(r.person_count || 0);
    existing.y2_visits = Number(r.visit_count || 0);
    existing.y2_income = Number(r.total_income || 0);
    map.set(key, existing);
  });

  let merged = Array.from(map.values()).sort(
    (a, b) => b.y2_visits + b.y1_visits - (a.y2_visits + a.y1_visits)
  );
  const q = pttypeSearch.value.trim().toLowerCase();
  if (q) {
    merged = merged.filter(
      (r) =>
        String(r.pttype || '').toLowerCase().includes(q) ||
        String(r.pttype_name || '').toLowerCase().includes(q)
    );
  }
  return merged;
});

const compareTotals = computed(() => {
  return filteredCompareRows.value.reduce(
    (acc, r) => {
      acc.y1_persons += r.y1_persons;
      acc.y1_visits += r.y1_visits;
      acc.y1_income += r.y1_income;
      acc.y2_persons += r.y2_persons;
      acc.y2_visits += r.y2_visits;
      acc.y2_income += r.y2_income;
      return acc;
    },
    { y1_persons: 0, y1_visits: 0, y1_income: 0, y2_persons: 0, y2_visits: 0, y2_income: 0 }
  );
});

const pttypeTotalRows = computed(() => {
  return pttypeViewMode.value === 'compare'
    ? filteredCompareRows.value.length
    : filteredPttypeRows.value.length;
});

const pttypeTotalPages = computed(() => {
  return Math.max(1, Math.ceil(pttypeTotalRows.value / pttypePageSize));
});

const paginatedPttypeRows = computed(() => {
  const start = (pttypePage.value - 1) * pttypePageSize;
  return filteredPttypeRows.value.slice(start, start + pttypePageSize);
});

const paginatedCompareRows = computed(() => {
  const start = (pttypePage.value - 1) * pttypePageSize;
  return filteredCompareRows.value.slice(start, start + pttypePageSize);
});

const pttypeStartIndex = computed(() => {
  if (pttypeTotalRows.value === 0) return 0;
  return (pttypePage.value - 1) * pttypePageSize + 1;
});

const pttypeEndIndex = computed(() => {
  return Math.min(pttypeTotalRows.value, pttypePage.value * pttypePageSize);
});

const pttypePageNumbers = computed(() => {
  const total = pttypeTotalPages.value;
  const current = pttypePage.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [1];
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

watch([pttypeSearch, pttypeViewMode], () => {
  pttypePage.value = 1;
});

const activeDiagYearLabel = computed(() => {
  if (diagViewMode.value === 'compare') {
    return `ปีงบฯ ${compareYear1.value} vs ${compareYear2.value}`;
  }
  return diagViewMode.value === 'year1'
    ? `ปีงบฯ ${compareYear1.value}`
    : `ปีงบฯ ${compareYear2.value}`;
});

const filteredDiagRows = computed(() => {
  let rows = diagViewMode.value === 'year1' ? diagData1.value : diagData2.value;
  if (excludeZCodes.value) {
    rows = rows.filter((r) => !String(r.pdx || '').toUpperCase().startsWith('Z'));
  }
  const q = diagSearch.value.trim().toLowerCase();
  if (!q) return rows;
  return rows.filter(
    (r) =>
      String(r.pdx || '').toLowerCase().includes(q) ||
      String(r.icd_name || '').toLowerCase().includes(q) ||
      String(r.icd_tname || '').toLowerCase().includes(q)
  );
});

const filteredDiagCompareRows = computed(() => {
  const map = new Map();
  (diagData1.value || []).forEach((r) => {
    const key = String(r.pdx || '');
    map.set(key, {
      pdx: r.pdx,
      icd_name: r.icd_name,
      icd_tname: r.icd_tname,
      y1_persons: Number(r.person_count || 0),
      y1_visits: Number(r.visit_count || 0),
      y1_income: Number(r.total_income || 0),
      y2_persons: 0,
      y2_visits: 0,
      y2_income: 0
    });
  });
  (diagData2.value || []).forEach((r) => {
    const key = String(r.pdx || '');
    const existing = map.get(key) || {
      pdx: r.pdx,
      icd_name: r.icd_name,
      icd_tname: r.icd_tname,
      y1_persons: 0,
      y1_visits: 0,
      y1_income: 0,
      y2_persons: 0,
      y2_visits: 0,
      y2_income: 0
    };
    existing.y2_persons = Number(r.person_count || 0);
    existing.y2_visits = Number(r.visit_count || 0);
    existing.y2_income = Number(r.total_income || 0);
    map.set(key, existing);
  });

  let merged = Array.from(map.values()).sort(
    (a, b) => b.y2_visits + b.y1_visits - (a.y2_visits + a.y1_visits)
  );
  if (excludeZCodes.value) {
    merged = merged.filter((r) => !String(r.pdx || '').toUpperCase().startsWith('Z'));
  }
  const q = diagSearch.value.trim().toLowerCase();
  if (q) {
    merged = merged.filter(
      (r) =>
        String(r.pdx || '').toLowerCase().includes(q) ||
        String(r.icd_name || '').toLowerCase().includes(q) ||
        String(r.icd_tname || '').toLowerCase().includes(q)
    );
  }
  return merged;
});

const diagTotalRows = computed(() => {
  return diagViewMode.value === 'compare'
    ? filteredDiagCompareRows.value.length
    : filteredDiagRows.value.length;
});

const diagTotalPages = computed(() => {
  return Math.max(1, Math.ceil(diagTotalRows.value / diagPageSize));
});

const paginatedDiagRows = computed(() => {
  const start = (diagPage.value - 1) * diagPageSize;
  return filteredDiagRows.value.slice(start, start + diagPageSize);
});

const paginatedDiagCompareRows = computed(() => {
  const start = (diagPage.value - 1) * diagPageSize;
  return filteredDiagCompareRows.value.slice(start, start + diagPageSize);
});

const diagStartIndex = computed(() => {
  if (diagTotalRows.value === 0) return 0;
  return (diagPage.value - 1) * diagPageSize + 1;
});

const diagEndIndex = computed(() => {
  return Math.min(diagTotalRows.value, diagPage.value * diagPageSize);
});

const diagPageNumbers = computed(() => {
  const total = diagTotalPages.value;
  const current = diagPage.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [1];
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

watch([diagSearch, diagViewMode, excludeZCodes], () => {
  diagPage.value = 1;
});

const getFiscalYearDateRange = (fy) => {
  const adYear = fy - 543;
  return {
    start: `${adYear - 1}-10-01`,
    end: `${adYear}-09-30`
  };
};

const processData = (dataArray) => {
  const mapData = new Map();
  if (Array.isArray(dataArray)) {
    dataArray.forEach((item) => {
      const parts = item.month.split('-');
      if (parts.length === 2) {
        mapData.set(parts[1], parseInt(item.opd_all || 0));
      }
    });
  }
  return fyMonths.map((m) => mapData.get(m) || 0);
};

const fetchData = async () => {
  loadingPttype.value = true;
  loadingDiag.value = true;
  pttypePage.value = 1;
  diagPage.value = 1;
  try {
    const range1 = getFiscalYearDateRange(compareYear1.value);
    const range2 = getFiscalYearDateRange(compareYear2.value);

    // Fetch data concurrently for both years (monthly + pttype + top diag)
    const [res1, res2, pt1, pt2, dg1, dg2] = await Promise.all([
      fetch(
        `/api-hosxe/opd/get_opd_month.php?start_date=${range1.start}&end_date=${range1.end}`
      ).then((r) => r.json()),
      fetch(
        `/api-hosxe/opd/get_opd_month.php?start_date=${range2.start}&end_date=${range2.end}`
      ).then((r) => r.json()),
      fetch(
        `/api-hosxe/opd/get_opd_pttype_summary.php?start_date=${range1.start}&end_date=${range1.end}`
      )
        .then((r) => r.json())
        .catch(() => []),
      fetch(
        `/api-hosxe/opd/get_opd_pttype_summary.php?start_date=${range2.start}&end_date=${range2.end}`
      )
        .then((r) => r.json())
        .catch(() => []),
      fetch(
        `/api-hosxe/opd/get_opd_top_diag.php?start_date=${range1.start}&end_date=${range1.end}`
      )
        .then((r) => r.json())
        .catch(() => []),
      fetch(
        `/api-hosxe/opd/get_opd_top_diag.php?start_date=${range2.start}&end_date=${range2.end}`
      )
        .then((r) => r.json())
        .catch(() => [])
    ]);

    plotData1.value = processData(res1);
    plotData2.value = processData(res2);
    pttypeData1.value = Array.isArray(pt1) ? pt1 : [];
    pttypeData2.value = Array.isArray(pt2) ? pt2 : [];
    diagData1.value = Array.isArray(dg1) ? dg1 : [];
    diagData2.value = Array.isArray(dg2) ? dg2 : [];

    renderChart();
  } catch (error) {
    console.error('Error fetching data:', error);
    plotData1.value = Array(12).fill(0);
    plotData2.value = Array(12).fill(0);
    pttypeData1.value = [];
    pttypeData2.value = [];
    diagData1.value = [];
    diagData2.value = [];
    if (chartInstance.value) {
      chartInstance.value.destroy();
    }
  } finally {
    loadingPttype.value = false;
    loadingDiag.value = false;
  }
};

const renderChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  const ctx = chartCanvas.value.getContext('2d');

  chartInstance.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [
        {
          label: `ปีงบประมาณ ${compareYear1.value}`,
          data: plotData1.value,
          borderColor: '#6c757d',
          backgroundColor: 'rgba(108, 117, 125, 0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#6c757d',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        },
        {
          label: `ปีงบประมาณ ${compareYear2.value}`,
          data: plotData2.value,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13, 110, 253, 0.1)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#0d6efd',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
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
            font: {
              size: 14
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          titleFont: {
            size: 16
          },
          bodyFont: {
            size: 14
          },
          padding: 12,
          usePointStyle: true,
          callbacks: {
            label: function (context) {
              return ` ${context.parsed.y.toLocaleString()} คน`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            callback: function (value) {
              return value.toLocaleString();
            }
          },
          grid: {
            color: 'rgba(0,0,0,0.05)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  });
};

onMounted(fetchData);
</script>

<style scoped>
.report-container {
  max-width: 1520px;
  margin: 0 auto;
  padding: 20px;
}

.report-header {
  text-align: center;
  margin-bottom: 30px;
}

.report-title {
  color: #2c3e50;
  font-weight: 700;
  margin-bottom: 10px;
}

.report-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.chart-container {
  border-radius: 10px;
  overflow: hidden;
}

.table-container {
  border-radius: 10px;
  overflow: hidden;
}

.card-header {
  font-size: 1.2rem;
  padding: 15px 20px;
}

.table th {
  font-weight: 600;
}

.form-select-lg {
  padding: 10px 15px;
  font-size: 1rem;
}

.btn-lg {
  padding: 10px 20px;
  font-size: 1.1rem;
}

@media (max-width: 768px) {
  .report-controls .col-md-4,
  .report-controls .col-md-auto {
    margin-bottom: 15px;
  }
}
</style>
