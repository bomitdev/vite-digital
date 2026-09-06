<template>
  <div class="container-fluid mt-4" id="dashboardContent">
    <div class="mb-4">
      <h2 class="mb-0 text-primary">
        <i class="bi bi-speedometer2 me-2"></i>Hospital KPI Dashboard
      </h2>
      <p class="text-muted mb-0">ติดตามตัวชี้วัดประสิทธิภาพโรงพยาบาล (5 Dimensions)</p>
    </div>

    <!-- Summary Widgets & Donut Chart -->
    <div class="row mb-4 align-items-stretch">
      <div class="col-md-8">
        <div class="row h-100">
          <div class="col-md-6 mb-3">
            <div class="card bg-success text-white h-100 shadow-sm border-0"
                 style="cursor: pointer"
                 :class="{'ring-active': statusFilter === 'pass'}"
                 @click="setStatusFilter('pass')">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-uppercase mb-1"><i class="bi bi-check-circle me-1"></i>KPIs Passed</h6>
                <h2 class="display-6 fw-bold mb-0">{{ summary.passed }}</h2>
                <small class="opacity-75">{{ summary.passedPercent }}% of Total KPIs</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="card bg-danger text-white h-100 shadow-sm border-0"
                 style="cursor: pointer"
                 :class="{'ring-active': statusFilter === 'fail'}"
                 @click="setStatusFilter('fail')">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-uppercase mb-1"><i class="bi bi-x-circle me-1"></i>KPIs Failed</h6>
                <h2 class="display-6 fw-bold mb-0">{{ summary.failed }}</h2>
                <small class="opacity-75">{{ summary.failedPercent }}% need attention</small>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3 mb-md-0">
            <div class="card bg-warning text-dark h-100 shadow-sm border-0"
                 style="cursor: pointer"
                 :class="{'ring-active': statusFilter === 'nodata'}"
                 @click="setStatusFilter('nodata')">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-uppercase mb-1"><i class="bi bi-hourglass-split me-1"></i>รอรายงานผล</h6>
                <h2 class="display-6 fw-bold mb-0">{{ summary.nodata }}</h2>
                <small class="opacity-75">ตัวชี้วัดที่ยังไม่มีผลงาน</small>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card bg-info text-white h-100 shadow-sm border-0"
                 style="cursor: pointer"
                 :class="{'ring-active': statusFilter === 'all'}"
                 @click="setStatusFilter('all')">
              <div class="card-body p-3">
                <h6 class="card-title fw-bold text-uppercase mb-1"><i class="bi bi-list-task me-1"></i>Total KPIs</h6>
                <h2 class="display-6 fw-bold mb-0">{{ summary.total }}</h2>
                <small class="opacity-75">Active Indicators</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Donut Chart Area -->
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex flex-column align-items-center justify-content-center pt-3 pb-3">
            <h6 class="card-title text-muted mb-0 fw-bold text-uppercase">Performance Overview</h6>
            <div style="height: 180px; width: 100%; position: relative" class="mt-2">
              <DoughnutChart
                v-if="!loading && summary.total > 0"
                :data="doughnutChartData"
                :options="doughnutChartOptions"
              />
              <div v-else-if="!loading" class="text-center text-muted mt-5">No KPI Data</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mb-4" data-html2canvas-ignore="true">
      <div class="input-group shadow-sm" style="width: 280px">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input
          type="text"
          class="form-control border-start-0 ps-0"
          placeholder="ค้นหา KPI หรือ ผู้รับผิดชอบ..."
          v-model="searchQuery"
        />
      </div>
      <select class="form-select w-auto shadow-sm" v-model="selectedLevel" v-if="availableLevels.length > 0">
        <option value="">ทุกระดับ (All Levels)</option>
        <option v-for="level in availableLevels" :key="level" :value="level">{{ level }}</option>
      </select>
      <select class="form-select w-auto shadow-sm" v-model="selectedFrequency" v-if="availableFrequencies.length > 0">
        <option value="">ความถี่ (All Freq)</option>
        <option v-for="freq in availableFrequencies" :key="freq" :value="freq">{{ getFrequencyLabel(freq) }}</option>
      </select>
      <select class="form-select w-auto shadow-sm" v-model="selectedYear" @change="fetchData">
        <option v-for="y in yearList" :key="y" :value="y">ปีงบประมาณ {{ y }}</option>
      </select>
      <button class="btn btn-outline-primary shadow-sm fw-bold" @click="fetchData">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
      </button>
      <button class="btn btn-danger shadow-sm fw-bold" @click="openBatchExportPreview" :disabled="isExportingAll">
        <span v-if="isExportingAll" class="spinner-border spinner-border-sm me-2"></span>
        <i class="bi bi-file-earmark-pdf-fill me-1" v-else></i> Export All (PDF)
      </button>
      <button class="btn btn-success shadow-sm fw-bold" @click="exportDashboardExcel">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
      </button>
      <router-link to="/kpi-setup" class="btn btn-dark shadow-sm fw-bold" v-if="isAdmin || hasResponsibleKpi">
        <i class="bi bi-gear-fill me-1"></i> ตั้งค่า KPI
      </router-link>
    </div>

    <!-- Dimension Sections -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else>
      <div
        v-for="category in filteredCategories"
        :key="category.id"
        class="card mb-4 border-0 shadow-sm"
      >
        <div class="card-header bg-white border-bottom-0 py-3">
          <h4 class="mb-0 text-dark fw-bold border-start border-4 border-primary ps-3">
            {{ category.name }}
            <span class="text-muted fs-6 fw-normal">({{ category.description }})</span>
          </h4>
        </div>
        <div class="card-body p-4" style="background-color: #eef2f6; border-bottom-left-radius: inherit; border-bottom-right-radius: inherit;">
          <div class="row g-4">
            <div class="col-12 col-md-6 col-xl-3" v-for="kpi in category.kpis" :key="kpi.id">
              <div class="card border border-top border-4 rounded-4 shadow h-100 kpi-card bg-white overflow-hidden"
                   :class="kpi.actual_value === null ? 'border-secondary' : (checkStatus(kpi) === 'pass' ? 'border-success' : 'border-danger')">
                <div class="card-body p-3 d-flex flex-column">
                  
                  <div class="mb-2 flex-grow-1">
                    <div class="mb-2 d-flex flex-wrap gap-1">
                      <span class="badge bg-primary bg-opacity-10 text-primary" v-if="kpi.code" style="font-size: 0.7rem;">{{ kpi.code }}</span>
                      <span class="badge bg-secondary text-white" v-if="kpi.kpi_level" style="font-size: 0.7rem;">
                        <i class="bi bi-diagram-3-fill me-1"></i>{{ kpi.kpi_level }}
                      </span>
                    </div>
                    
                    <h6 class="fw-bold mb-2 lh-base text-dark" style="font-size: 0.95rem; display: -webkit-box; -webkit-line-clamp: 3; line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">{{ kpi.name }}</h6>
                    
                    <div class="badge bg-light text-secondary border w-100 text-start text-truncate fw-normal py-2 mb-3" style="font-size: 0.8rem;">
                      <i class="bi bi-person-fill me-1"></i> {{ kpi.responsible_person || 'ยังไม่ระบุ' }}
                    </div>

                    <div class="row g-2 mb-3 text-center">
                      <div class="col-6">
                        <div class="bg-light p-2 rounded-3 border h-100 d-flex flex-column justify-content-center">
                          <div class="text-muted mb-1" style="font-size: 0.7rem;">เป้าหมาย</div>
                          <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ kpi.target_operator }} {{ kpi.target_value }}</div>
                          <div class="text-muted" style="font-size: 0.65rem;">{{ kpi.unit }}</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="bg-light p-2 rounded-3 border h-100 d-flex flex-column justify-content-center">
                          <div class="text-muted mb-1" style="font-size: 0.7rem;">ผลงานล่าสุด</div>
                          <div class="fw-bold" style="font-size: 0.9rem;" :class="kpi.actual_value !== null ? 'text-primary' : 'text-muted'">{{ kpi.actual_value !== null ? kpi.actual_value : '-' }}</div>
                        </div>
                      </div>
                    </div>

                    <div class="d-flex flex-column gap-1 mb-2">
                         <span v-if="kpi.actual_value === null" class="badge bg-secondary w-100 py-2">No Data</span>
                         <span v-else-if="checkStatus(kpi) === 'pass'" class="badge bg-success w-100 py-2">Pass</span>
                         <span v-else class="badge bg-danger w-100 py-2">Fail</span>
                         
                         <span v-if="getMissingPeriods(kpi).length > 0" 
                               class="badge bg-warning text-dark border border-warning w-100 py-2" 
                               style="cursor: pointer; transition: 0.2s;"
                               @click="showMissingPeriodsDetails(kpi)">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>ค้างรายงาน {{ getMissingPeriods(kpi).length }} รอบ
                         </span>
                    </div>
                  </div>
                  
                  <div class="d-flex align-items-center mb-3 mt-auto justify-content-between">
                    <button class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 me-2 fw-bold" style="font-size: 0.8rem;" @click.stop="openEntryModal(kpi)">
                      <i class="bi bi-pencil-square"></i> รายงาน
                    </button>
                    <div class="d-flex gap-1">
                      <button class="btn btn-sm btn-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" @click.stop="openTrendModal(kpi)" title="กราฟแนวโน้ม">
                        <i class="bi bi-bar-chart-fill text-info"></i>
                      </button>
                      <button class="btn btn-sm btn-light border rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" @click.stop="openHistoryModal(kpi)" title="ประวัติ">
                        <i class="bi bi-clock-history text-secondary"></i>
                      </button>
                    </div>
                  </div>
                  
                  <hr class="text-muted opacity-25 my-2">
                  
                  <div class="d-flex overflow-auto gap-2 pb-1" style="scrollbar-width: thin;">
                    <div v-for="(block, idx) in getFrequencyBlocks(kpi)" :key="idx" style="min-width: 60px; flex: 1;">
                      <div class="bg-warning bg-opacity-10 p-1 rounded-2 border border-warning border-opacity-25 h-100 text-center d-flex flex-column justify-content-center">
                        <div class="text-dark mb-0 text-truncate" style="font-size: 0.65rem; font-weight: 600;" :title="block.label">{{ block.label }}</div>
                        <div class="fw-bold" style="font-size: 0.75rem;" :class="block.value !== '-' ? 'text-primary' : 'text-muted'">{{ block.value }}</div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>
          
          <div v-if="!category.kpis || category.kpis.length === 0" class="text-center text-muted py-3">
             No KPIs defined for this dimension.
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for History -->
    <!-- Trend Modal (Chart) -->
    <div class="modal fade" id="trendModal" tabindex="-1" aria-hidden="true" ref="trendModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-graph-up me-2"></i> Trend: {{ selectedKpi?.name }}
            </h5>
            <button class="btn btn-sm btn-light text-success fw-bold ms-auto me-3 shadow-sm" @click="openExportPreview">
              <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
            </button>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="text-center py-5">
              <div class="spinner-border text-primary"></div>
            </div>
            <div v-else style="height: 400px; position: relative">
              <KpiTrendChart
                v-if="chartData.labels"
                :chartData="chartData"
                :chartOptions="chartOptions"
              />
              <p v-else class="text-center text-muted mt-5">No data available.</p>
            </div>
            
            <!-- Analysis Section -->
            <div class="mt-4 border-top pt-3">
              <h6 class="fw-bold text-dark"><i class="bi bi-chat-left-text me-2"></i>วิเคราะห์ตัวชี้วัด</h6>
              <textarea
                class="form-control mb-3 shadow-sm border-1 bg-light"
                rows="4"
                v-model="analysisText"
                placeholder="พิมพ์ผลการวิเคราะห์ตัวชี้วัด เพื่อใช้อ้างอิงและติดตามผล..."
                style="resize: none;"
                :readonly="!isAdmin && !hasResponsibleKpi"
              ></textarea>
              <div class="d-flex justify-content-end" v-if="isAdmin || hasResponsibleKpi">
                <button class="btn btn-primary fw-bold px-4 shadow-sm" @click="saveAnalysis" :disabled="savingAnalysis">
                  <span v-if="savingAnalysis" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-save me-1" v-else></i> บันทึกผลวิเคราะห์
                </button>
              </div>
            </div>
            
          </div>
        </div>
      </div>
    </div>

    <!-- History Modal (Table) -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true" ref="historyModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-secondary text-white">
            <h5 class="modal-title">
              <i class="bi bi-clock-history me-2"></i> History: {{ selectedKpi?.name }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="text-center py-5">
              <div class="spinner-border text-secondary"></div>
            </div>
            <div v-else class="table-responsive">
              <table class="table table-bordered table-striped text-center">
                <thead>
                  <tr>
                    <th>Date/Period</th>
                    <th>Target</th>
                    <th>Actual</th>
                    <th>Status</th>
                    <th>วันที่บันทึก</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="h in historyList" :key="h.id">
                    <td>{{ formatDate(h.period_date) }}</td>
                    <td>{{ h.target_value_snapshot }}</td>
                    <td>{{ h.actual_value }}</td>
                    <td>
                      <span v-if="checkPassHistory(h)" class="badge bg-success">Pass</span>
                      <span v-else class="badge bg-danger">Fail</span>
                    </td>
                    <td>{{ formatDateTime(h.created_at) }}</td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary me-2" @click="editHistory(h)" title="แก้ไข">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" @click="deleteHistory(h)" title="ลบ">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="historyList.length === 0">
                    <td colspan="6" class="text-muted">No history data found.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Export Preview Modal -->
    <div class="modal fade" id="exportPreviewModal" tabindex="-1" aria-hidden="true" ref="exportPreviewModal">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="bi bi-file-earmark-excel me-2"></i> ตัวอย่างรายงานก่อนดาวน์โหลด (Preview)
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body bg-light">
            
            <div class="card shadow-sm border-0 mb-3" v-if="exportPreviewData">
              <div class="card-body p-4 bg-white" style="font-family: 'Sarabun', 'Sarabun New', sans-serif;">
                <div class="text-center mb-4">
                  <h4 class="fw-bold">รายงานตัวชี้วัด</h4>
                  <h5 class="fw-bold">{{ exportPreviewData.kpiName }}</h5>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-bordered border-dark text-center align-middle" style="min-width: 800px;">
                    <thead class="table-light border-dark">
                      <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="30%">ข้อมูล/ตัวชี้วัด</th>
                        <th width="15%">เป้าหมาย<br>ปีปัจจุบัน</th>
                        <th v-for="(p, idx) in exportPreviewData.periods" :key="'h'+idx">{{ p }}</th>
                      </tr>
                    </thead>
                    <tbody class="border-dark">
                      <tr>
                        <td>1</td>
                        <td class="text-start">{{ exportPreviewData.kpiName }}</td>
                        <td>{{ exportPreviewData.targetStr }}</td>
                        <td v-for="(a, idx) in exportPreviewData.actuals" :key="'d'+idx">{{ a }}</td>
                      </tr>
                      <tr>
                        <td colspan="3" class="p-3">
                          <img :src="'data:image/png;base64,' + exportPreviewData.base64Data" class="img-fluid border" alt="Trend Chart" style="max-height: 250px;">
                        </td>
                        <td :colspan="exportPreviewData.periods.length" class="text-start p-3" style="vertical-align: top;">
                          <h6 class="fw-bold text-decoration-underline mb-2">ผลการวิเคราะห์:</h6>
                          <div v-for="(line, idx) in exportPreviewData.analysisLines" :key="'a'+idx" class="mb-1">
                            {{ line }}
                          </div>
                          <div v-if="!exportPreviewData.analysisLines.length" class="text-muted fst-italic">ไม่มีข้อมูลการวิเคราะห์</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
              </div>
            </div>
            
          </div>
          <div class="modal-footer bg-white d-flex justify-content-end">
            <button type="button" class="btn btn-light border fw-bold px-4 me-auto" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary fw-bold px-3 shadow-sm" @click="confirmExportTrendWord">
              <i class="bi bi-file-earmark-word me-1"></i> Word
            </button>
            <button type="button" class="btn btn-danger fw-bold px-3 shadow-sm" @click="confirmExportTrendPdf">
              <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
            <button type="button" class="btn btn-success fw-bold px-3 shadow-sm" @click="confirmExportTrendExcel">
              <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Entry Modal -->
    <KpiEntryModal ref="entryModal" @saved="fetchData" />
    
    <!-- Batch Export Preview Modal -->
    <div class="modal fade" id="batchExportPreviewModal" tabindex="-1" aria-labelledby="batchExportPreviewModalLabel" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
          <div class="modal-header bg-danger text-white border-0">
            <h5 class="modal-title fw-bold" id="batchExportPreviewModalLabel">
              <i class="bi bi-file-earmark-pdf me-2"></i> ตัวอย่างรายงาน PDF รวมตัวชี้วัด ({{ selectedLevel || 'ทุกระดับ' }})
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0 bg-light" id="batchExportModalBody" style="overflow-x: auto;">
            <div style="min-width: 1200px; padding: 20px; display: flex; justify-content: center;">
              <div id="batchExportContainer" class="bg-white shadow-sm" style="width: 1200px; font-family: 'Sarabun', sans-serif;" v-if="batchExportData.length > 0">
                <div v-for="(kpiItem, index) in batchExportData" :key="index" class="kpi-export-page p-4" style="background: white;">
                  <div class="text-center mb-4">
                    <h4 class="fw-bold">{{ kpiItem.categoryName }}</h4>
                    <h5 class="fw-bold">{{ kpiItem.kpiName }}</h5>
                  </div>
                  <table class="table table-bordered border-dark text-center align-middle" style="width: 100%; margin-bottom: 20px;">
                    <thead class="table-light border-dark">
                      <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="30%">ข้อมูล/ตัวชี้วัด</th>
                        <th width="15%">เป้าหมาย<br>ปีปัจจุบัน</th>
                        <th v-for="(p, idx) in kpiItem.periods" :key="'bh'+idx">{{ p }}</th>
                      </tr>
                    </thead>
                    <tbody class="border-dark">
                      <tr>
                        <td>{{ index + 1 }}</td>
                        <td class="text-start">{{ kpiItem.kpiName }}</td>
                        <td>{{ kpiItem.targetStr }}</td>
                        <td v-for="(a, idx) in kpiItem.actuals" :key="'bd'+idx">{{ a }}</td>
                      </tr>
                      <tr>
                        <td colspan="3" class="p-3 text-center bg-white">
                          <img v-if="kpiItem.base64Data" :src="'data:image/png;base64,' + kpiItem.base64Data" class="img-fluid border" style="max-height: 250px;">
                          <div v-else class="text-muted">No Chart Data</div>
                        </td>
                        <td :colspan="kpiItem.periods.length" class="text-start p-3 bg-white" style="vertical-align: top;">
                          <h6 class="fw-bold text-decoration-underline mb-2">ผลการวิเคราะห์:</h6>
                          <div v-for="(line, aIdx) in kpiItem.analysisLines" :key="'ba'+aIdx" class="mb-1">
                            {{ line }}
                          </div>
                          <div v-if="!kpiItem.analysisLines.length" class="text-muted fst-italic">ไม่มีข้อมูลการวิเคราะห์</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="html2pdf__page-break" v-if="index < batchExportData.length - 1"></div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-white d-flex justify-content-end">
            <button type="button" class="btn btn-light border fw-bold px-4 me-auto" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-danger fw-bold px-4 shadow-sm" @click="confirmBatchExportPdf" :disabled="isExportingPdfFile">
              <span v-if="isExportingPdfFile" class="spinner-border spinner-border-sm me-2"></span>
              <i class="bi bi-file-earmark-pdf-fill me-1" v-else></i> ยืนยันการ Export เป็น PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';
import { Doughnut } from 'vue-chartjs';
import { ArcElement, Tooltip, Legend } from 'chart.js';
import KpiTrendChart from '../../components/KpiTrendChart.vue';
import KpiEntryModal from '../../components/KpiEntryModal.vue';
import { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, BorderStyle, WidthType, ImageRun, AlignmentType, HeadingLevel, VerticalAlign } from 'docx';
import saveAs from 'file-saver';
import * as XLSX from 'xlsx';
import ExcelJS from 'exceljs';
import html2pdf from 'html2pdf.js';
import { Chart as ChartJS, registerables } from 'chart.js';

// Register specific compenents for Doughnut and Line charts
ChartJS.register(...registerables);

export default {
  name: 'KpiDashboardView',
  components: {
    KpiTrendChart,
    KpiEntryModal,
    DoughnutChart: Doughnut
  },
  data() {
    return {
      loading: true,
      categories: [],
      selectedKpi: null,
      historyLoading: false,
      historyList: [],
      trendModalInstance: null,
      historyModalInstance: null,
      exportPreviewModalInstance: null,
      exportPreviewData: null,
      isExportingAll: false,
      isExportingWord: false,
      batchExportData: [],
      batchExportPreviewModalInstance: null,
      selectedYear: null,
      analysisText: '',
      savingAnalysis: false,
      selectedLevel: '',
      selectedFrequency: '',
      userDepartment: '',
      userFullname: '',
      userAccess: [],
      yearList: [],
      searchQuery: '',
      statusFilter: 'all',
      chartData: {},
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Performance Trend' }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    };
  },
  computed: {
    isAdmin() {
      // Basic check for admin departments
      return (
        this.userAccess.includes('administrator') ||
        this.userAccess.includes('menu_kpi_admin') ||
        this.userDepartment.includes('กลุ่มงานสุขภาพดิจิทัล') ||
        this.userDepartment.includes('ประกัน') ||
        this.userDepartment === 'admin'
      );
    },
    hasResponsibleKpi() {
      if (!this.userFullname) return false;
      for (const cat of this.categories) {
        if (cat.kpis) {
          for (const kpi of cat.kpis) {
            if (kpi.responsible_person && kpi.responsible_person.includes(this.userFullname)) {
              return true;
            }
          }
        }
      }
      return false;
    },
    baseCategories() {
      let result = this.categories;
      if (!this.isAdmin && this.userFullname) {
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          return { ...cat, kpis: cat.kpis.filter(kpi => kpi.responsible_person && kpi.responsible_person.includes(this.userFullname)) };
        });
        result = result.filter(cat => cat.kpis && cat.kpis.length > 0);
      }
      return result;
    },
    filteredCategoriesForSummary() {
      let result = this.baseCategories;

      // Filter by selected level
      if (this.selectedLevel) {
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          return { ...cat, kpis: cat.kpis.filter(kpi => kpi.kpi_level === this.selectedLevel) };
        });
      }
      
      // Filter by selected frequency
      if (this.selectedFrequency) {
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          return { ...cat, kpis: cat.kpis.filter(kpi => kpi.kpi_periodicity === this.selectedFrequency) };
        });
      }

      // Filter by search query
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          const matchedKpis = cat.kpis.filter((kpi) => {
            const name = kpi.name ? kpi.name.toLowerCase() : '';
            const person = kpi.responsible_person ? kpi.responsible_person.toLowerCase() : '';
            const desc = kpi.description ? kpi.description.toLowerCase() : '';
            const level = kpi.kpi_level ? kpi.kpi_level.toLowerCase() : '';
            return name.includes(query) || person.includes(query) || desc.includes(query) || level.includes(query);
          });
          return { ...cat, kpis: matchedKpis };
        });
      }

      return result.filter(cat => cat.kpis && cat.kpis.length > 0);
    },
    summary() {
      let total = 0;
      let passed = 0;
      let failed = 0;
      let nodata = 0;

      this.filteredCategoriesForSummary.forEach((cat) => {
        if (cat.kpis) {
          cat.kpis.forEach((kpi) => {
            total++;
            const status = this.checkStatus(kpi);
            if (status === 'pass') passed++;
            else if (status === 'fail') failed++;
            else if (status === 'nodata') nodata++;
          });
        }
      });

      return {
        total,
        passed,
        failed,
        nodata,
        passedPercent: total > 0 ? Math.round((passed / total) * 100) : 0,
        failedPercent: total > 0 ? Math.round((failed / total) * 100) : 0
      };
    },
    filteredCategories() {
      let result = this.filteredCategoriesForSummary;

      // Filter by status filter
      if (this.statusFilter !== 'all') {
        result = result.map(cat => {
          if (!cat.kpis) return cat;
          return { ...cat, kpis: cat.kpis.filter(kpi => this.checkStatus(kpi) === this.statusFilter) };
        });
      }

      return result.filter(cat => cat.kpis && cat.kpis.length > 0);
    },
    availableLevels() {
      const levels = new Set();
      this.baseCategories.forEach(cat => {
        if (cat.kpis) {
          cat.kpis.forEach(kpi => {
            if (kpi.kpi_level) {
              levels.add(kpi.kpi_level);
            }
          });
        }
      });
      return Array.from(levels).sort();
    },
    availableFrequencies() {
      const freqs = new Set();
      this.baseCategories.forEach(cat => {
        if (cat.kpis) {
          cat.kpis.forEach(kpi => {
            if (kpi.kpi_periodicity) {
              freqs.add(kpi.kpi_periodicity);
            }
          });
        }
      });
      return Array.from(freqs);
    },
    doughnutChartData() {
      return {
        labels: ['Passed', 'Failed', 'No Data'],
        datasets: [
          {
            backgroundColor: ['#198754', '#dc3545', '#ffc107'],
            borderWidth: 0,
            data: [this.summary.passed || 0, this.summary.failed || 0, this.summary.nodata || 0]
          }
        ]
      };
    },
    doughnutChartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed !== null) {
                  label += context.parsed;
                }
                return label;
              }
            }
          }
        },
        cutout: '75%'
      };
    }
  },
  async mounted() {
    await this.fetchUserProfile();
    this.generateYearList();
    // Fiscal Year Logic: Oct (9) onwards is next year
    const d = new Date();
    if (d.getMonth() >= 9) {
      this.selectedYear = d.getFullYear() + 543 + 1;
    } else {
      this.selectedYear = d.getFullYear() + 543;
    }
    this.fetchData();
  },
  methods: {
    getFrequencyBlocks(kpi) {
      const freq = kpi.kpi_periodicity;
      let blocks = [];
      const dataMap = {};
      
      if (kpi.period_data) {
        kpi.period_data.split(',').forEach(item => {
          const parts = item.split('|');
          if (parts.length === 2) {
            dataMap[parts[0]] = parts[1];
          }
        });
      }

      const isAverage = kpi.unit && (kpi.unit.includes('%') || kpi.unit.includes('ร้อยละ'));
      const calcAgg = (values) => {
        const validVals = values.filter(v => v !== '-' && v !== null && v !== '').map(v => parseFloat(v)).filter(v => !isNaN(v));
        if (validVals.length === 0) return '-';
        const sum = validVals.reduce((a, b) => a + b, 0);
        if (isAverage) {
          const avg = sum / validVals.length;
          return Number.isInteger(avg) ? String(avg) : avg.toFixed(2);
        }
        return Number.isInteger(sum) ? String(sum) : sum.toFixed(2);
      };

      if (freq === 'month') {
        const months = ['ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.'];
        const mValues = [];
        months.forEach((m, idx) => {
          let monthNum = idx < 3 ? idx + 10 : idx - 2;
          let val = '-';
          for (let date in dataMap) {
            if (parseInt(date.split('-')[1]) === monthNum) {
              val = dataMap[date];
              break;
            }
          }
          mValues.push(val);
          blocks.push({ label: m, value: val });
        });
        
        blocks.push({ label: 'ครึ่งปีแรก', value: calcAgg(mValues.slice(0, 6)) });
        blocks.push({ label: 'ครึ่งปีหลัง', value: calcAgg(mValues.slice(6, 12)) });
        blocks.push({ label: 'ผลงานทั้งปี', value: calcAgg(mValues) });
        
      } else if (freq === 'quarter') {
        blocks = [
          { label: 'ไตรมาส 1', value: '-' },
          { label: 'ไตรมาส 2', value: '-' },
          { label: 'ไตรมาส 3', value: '-' },
          { label: 'ไตรมาส 4', value: '-' }
        ];
        for (let date in dataMap) {
          let m = parseInt(date.split('-')[1]);
          if (m >= 10 && m <= 12) blocks[0].value = dataMap[date];
          else if (m >= 1 && m <= 3) blocks[1].value = dataMap[date];
          else if (m >= 4 && m <= 6) blocks[2].value = dataMap[date];
          else if (m >= 7 && m <= 9) blocks[3].value = dataMap[date];
        }
        const qVals = blocks.map(b => b.value);
        blocks.push({ label: 'ครึ่งปีแรก', value: calcAgg(qVals.slice(0, 2)) });
        blocks.push({ label: 'ครึ่งปีหลัง', value: calcAgg(qVals.slice(2, 4)) });
        blocks.push({ label: 'ผลงานทั้งปี', value: calcAgg(qVals) });
      } else if (freq === '6month' || freq === 'halfyear' || freq === 'half_year') {
        blocks = [
          { label: 'ครึ่งปีแรก', value: '-' },
          { label: 'ครึ่งปีหลัง', value: '-' }
        ];
        for (let date in dataMap) {
          let m = parseInt(date.split('-')[1]);
          if (m >= 10 || (m >= 1 && m <= 3)) blocks[0].value = dataMap[date];
          else blocks[1].value = dataMap[date];
        }
        const hVals = blocks.map(b => b.value);
        blocks.push({ label: 'ผลงานทั้งปี', value: calcAgg(hVals) });
      } else if (freq === 'year') {
        blocks = [{ label: 'ผลงานทั้งปี', value: '-' }];
        for (let date in dataMap) {
           blocks[0].value = dataMap[date];
        }
      } else {
         blocks = [{ label: 'ผลงาน', value: '-' }];
      }
      return blocks;
    },
    async fetchUserProfile() {
      try {
        const token = localStorage.getItem('user_token');
        if (!token) return;
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data.status === 'success') {
          this.userDepartment = response.data.department || '';
          this.userFullname = response.data.fullname || '';
          this.userAccess = response.data.access_user ? response.data.access_user.split(':') : [];
        }
      } catch (e) {
        console.error('Failed to load user profile', e);
      }
    },
    setStatusFilter(status) {
      this.statusFilter = status;
    },
    getFrequencyLabel(type) {
      if (type === 'quarter') return 'รายไตรมาส';
      if (type === 'Semiannual report') return 'รายครึ่งปี';
      if (type === 'year') return 'รายปีงบประมาณ';
      return 'รายเดือน';
    },
    generateYearList() {
      const current = new Date().getFullYear() + 543;
      // Range: Next Year down to 5 years ago
      for (let i = current + 1; i >= current - 5; i--) {
        this.yearList.push(i);
      }
    },
    async fetchHistoryData(kpiId) {
      this.historyLoading = true;
      try {
        const res = await axios.get(`/api-digital/kpi/get_kpi_history.php?kpi_id=${kpiId}`);
        if (res.data.status === 'success') {
          return res.data.data;
        }
        return [];
      } catch (e) {
        console.error(e);
        return [];
      } finally {
        this.historyLoading = false;
      }
    },
    async openTrendModal(kpi) {
      this.selectedKpi = kpi;
      this.analysisText = kpi.analysis || '';
      let el = this.$refs.trendModal;
      if (!el) el = document.getElementById('trendModal');
      if (el) {
        if (!this.trendModalInstance) this.trendModalInstance = new Modal(el);
        this.trendModalInstance.show();
      }
      const data = await this.fetchHistoryData(kpi.id);
      this.historyList = data;
      this.prepareChart(data);
    },
    async openHistoryModal(kpi) {
      this.selectedKpi = kpi;
      let el = this.$refs.historyModal;
      if (!el) el = document.getElementById('historyModal');
      if (el) {
        if (!this.historyModalInstance) this.historyModalInstance = new Modal(el);
        this.historyModalInstance.show();
      }
      this.historyList = await this.fetchHistoryData(kpi.id);
    },
    editHistory(h) {
      if (this.historyModalInstance) {
        this.historyModalInstance.hide();
      }
      
      const dateParts = h.period_date.split('-');
      const y = parseInt(dateParts[0]);
      const m = parseInt(dateParts[1]);
      
      let yearThai = y + 543;
      let periodNum = m;
      
      const periodicity = this.selectedKpi?.kpi_periodicity || 'month';
      
      if (periodicity === 'quarter') {
        if (m === 10) { periodNum = 1; yearThai = y + 1 + 543; }
        else if (m === 1) { periodNum = 2; yearThai = y + 543; }
        else if (m === 4) { periodNum = 3; yearThai = y + 543; }
        else if (m === 7) { periodNum = 4; yearThai = y + 543; }
      } else if (periodicity === 'Semiannual report') {
        if (m === 10) { periodNum = 1; yearThai = y + 1 + 543; }
        else if (m === 4) { periodNum = 2; yearThai = y + 543; }
      } else if (periodicity === 'year') {
        yearThai = y + 1 + 543;
        periodNum = 1;
      } else {
        periodNum = m;
        if (m >= 10) {
          yearThai = y + 1 + 543;
        } else {
          yearThai = y + 543;
        }
      }
      
      this.$refs.entryModal.openForEdit(this.selectedKpi, {
        year_thai: yearThai,
        period_number: periodNum,
        actual_value: h.actual_value,
        numerator: h.numerator || '',
        denominator: h.denominator || ''
      });
    },
    async deleteHistory(h) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบข้อมูลผลการดำเนินงานนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/kpi/delete_kpi_result.php', { id: h.id });
          if (res.data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1500, showConfirmButton: false });
            this.historyList = await this.fetchHistoryData(this.selectedKpi.id);
            this.fetchData();
          } else {
            Swal.fire('Error', res.data.message || 'ลบไม่สำเร็จ', 'error');
          }
        } catch (e) {
          console.error(e);
          Swal.fire('Error', 'Connection Error', 'error');
        }
      }
    },
    async saveAnalysis() {
      if (!this.selectedKpi) return;
      this.savingAnalysis = true;
      try {
        const res = await axios.post('/api-digital/kpi/save_kpi_analysis.php', {
          kpi_id: this.selectedKpi.id,
          analysis: this.analysisText
        });
        if (res.data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false });
          this.selectedKpi.analysis = this.analysisText; // update local
        } else {
          Swal.fire('Error', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      } finally {
        this.savingAnalysis = false;
      }
    },
    async openExportPreview() {
      if (!this.selectedKpi) {
        console.error('No selected KPI');
        return;
      }
      
      const canvas = document.querySelector('#trendModal canvas');
      if (!canvas) {
        Swal.fire('Error', 'ไม่พบกราฟสำหรับ Export', 'error');
        return;
      }
      
      const dataUrl = canvas.toDataURL('image/png');
      const base64Data = dataUrl.replace(/^data:image\/png;base64,/, '');
      
      const currentYear = this.selectedYear || new Date().getFullYear() + 543;
      const years = [];
      const actuals = [];
      
      for(let i = 4; i >= 0; i--) {
        const targetFy = currentYear - i;
        years.push(targetFy);
        
        // Find latest entry for this fiscal year
        const entriesInFy = this.historyList.filter(h => {
           const parts = h.period_date.split('-');
           const y = parseInt(parts[0]);
           const m = parseInt(parts[1]);
           const fy = m >= 10 ? y + 1 + 543 : y + 543;
           return fy === targetFy;
        });
        
        if (entriesInFy.length > 0) {
           // sort descending by date
           entriesInFy.sort((a,b) => b.period_date.localeCompare(a.period_date));
           actuals.push(entriesInFy[0].actual_value !== null ? entriesInFy[0].actual_value : '');
        } else {
           actuals.push('');
        }
      }
      
      const targetStr = `${this.selectedKpi.target_operator || ''} ${this.selectedKpi.target_value || ''} ${this.selectedKpi.unit || ''}`.trim();
      
      const analysisLines = (this.analysisText || '').split('\n').filter(line => line.trim() !== '');

      this.exportPreviewData = {
        kpiName: this.selectedKpi.name || '',
        kpiCode: this.selectedKpi.code || 'Export',
        targetStr: targetStr,
        periods: years,
        actuals: actuals,
        base64Data: base64Data,
        analysisLines: analysisLines
      };

      let el = this.$refs.exportPreviewModal;
      if (!el) el = document.getElementById('exportPreviewModal');
      if (el) {
        if (!this.exportPreviewModalInstance) this.exportPreviewModalInstance = new Modal(el);
        this.exportPreviewModalInstance.show();
      }
    },
    async confirmExportTrendExcel() {
      if (!this.exportPreviewData) return;
      
      const data = this.exportPreviewData;
      
      const workbook = new ExcelJS.Workbook();
      const sheet = workbook.addWorksheet('Trend Analysis');

      // Set columns
      const columns = [
        { header: 'ลำดับ', key: 'no', width: 10 },
        { header: 'ข้อมูล/ตัวชี้วัด', key: 'name', width: 50 },
        { header: 'เป้าหมาย ปีปัจจุบัน', key: 'target', width: 25 }
      ];
      data.periods.forEach((p, i) => {
        columns.push({ header: String(p), key: 'p' + i, width: 15 });
      });
      sheet.columns = columns;

      // Data Row
      const rowData = {
        no: 1,
        name: data.kpiName,
        target: data.targetStr
      };
      data.periods.forEach((p, i) => {
        rowData['p' + i] = data.actuals[i] !== undefined && data.actuals[i] !== '' ? String(data.actuals[i]) : '';
      });
      sheet.addRow(rowData);
      
      // Empty row
      sheet.addRow([]);
      
      // Analysis Row
      sheet.addRow(['', '', 'ผลการวิเคราะห์:']);
      if (data.analysisLines.length > 0) {
        data.analysisLines.forEach(line => sheet.addRow(['', '', line]));
      } else {
        sheet.addRow(['', '', '- ไม่มีข้อมูลการวิเคราะห์ -']);
      }
      
      // Add Chart Image
      if (data.base64Data) {
        const imageId = workbook.addImage({
          base64: 'data:image/png;base64,' + data.base64Data,
          extension: 'png',
        });
        
        sheet.addImage(imageId, {
          tl: { col: 0, row: 3 },
          ext: { width: 500, height: 250 }
        });
      }

      // Style headers
      sheet.getRow(1).font = { bold: true };
      sheet.getRow(1).alignment = { horizontal: 'center' };
      
      try {
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        saveAs(blob, `KPI_Trend_${data.kpiCode}.xlsx`);
        
        if (this.exportPreviewModalInstance) {
          this.exportPreviewModalInstance.hide();
        }
        Swal.fire({ icon: 'success', title: 'Export สำเร็จ', timer: 1500, showConfirmButton: false });
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้างไฟล์ Excel', 'error');
      }
    },
    async generateOffscreenChartDataUrl(history) {
      if (!history || history.length === 0) return '';
      const sorted = [...history].reverse();
      const data = {
        labels: sorted.map((h) => this.formatDate(h.period_date)),
        datasets: [
          {
            label: 'Actual',
            data: sorted.map((h) => h.actual_value),
            borderColor: '#304ffe',
            backgroundColor: 'rgba(48, 79, 254, 0.1)',
            tension: 0.3,
            fill: true
          },
          {
            label: 'Target',
            data: sorted.map((h) => h.target_value_snapshot),
            borderColor: '#f44336',
            borderDash: [5, 5],
            fill: false
          }
        ]
      };
      
      const canvas = document.createElement('canvas');
      canvas.width = 600;
      canvas.height = 300;
      canvas.style.display = 'none';
      document.body.appendChild(canvas);
      
      const chartOptions = Object.assign({}, this.chartOptions, {
        animation: false,
        responsive: false,
        maintainAspectRatio: false
      });
      
      const chart = new ChartJS(canvas, {
         type: 'line',
         data: data,
         options: chartOptions
      });
      
      await new Promise(r => setTimeout(r, 50));
      const dataUrl = chart.toBase64Image();
      
      chart.destroy();
      document.body.removeChild(canvas);
      
      return dataUrl.replace(/^data:image\/png;base64,/, '');
    },
    async openBatchExportPreview() {
      if (!this.selectedLevel) {
        Swal.fire('ข้อควรระวัง', 'กรุณาเลือก "ระดับตัวชี้วัด" (Level) จากตัวกรองด้านบนก่อนทำการ Export All PDF', 'warning');
        return;
      }
      
      let allKpis = [];
      this.filteredCategories.forEach(cat => {
        if (cat.kpis && cat.kpis.length > 0) {
          const kpisWithCat = cat.kpis.map(k => ({ ...k, categoryName: cat.name }));
          allKpis = allKpis.concat(kpisWithCat);
        }
      });
      
      if (allKpis.length === 0) {
        Swal.fire('Warning', 'ไม่มีตัวชี้วัดในระดับที่เลือก', 'warning');
        return;
      }
      
      this.isExportingAll = true;
      this.batchExportData = [];
      
      Swal.fire({
        title: 'กำลังเตรียมตัวอย่าง PDF...',
        text: 'ระบบกำลังดึงข้อมูลและประมวลผลกราฟ โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      try {
        const currentYear = this.selectedYear || new Date().getFullYear() + 543;
        const years = [];
        for(let i = 4; i >= 0; i--) {
          years.push(currentYear - i);
        }
        
        for (const kpi of allKpis) {
          const history = await this.fetchHistoryData(kpi.id);
          const base64Data = await this.generateOffscreenChartDataUrl(history);
          
          const actuals = [];
          for (const targetFy of years) {
            const entriesInFy = history.filter(h => {
               const parts = h.period_date.split('-');
               const y = parseInt(parts[0]);
               const m = parseInt(parts[1]);
               const fy = m >= 10 ? y + 1 + 543 : y + 543;
               return fy === targetFy;
            });
            if (entriesInFy.length > 0) {
               entriesInFy.sort((a,b) => b.period_date.localeCompare(a.period_date));
               actuals.push(entriesInFy[0].actual_value !== null ? entriesInFy[0].actual_value : '');
            } else {
               actuals.push('');
            }
          }
          
          const targetStr = `${kpi.target_operator || ''} ${kpi.target_value || ''} ${kpi.unit || ''}`.trim();
          const analysisLines = (kpi.analysis || '').split('\n').filter(line => line.trim() !== '');
          
          const hasData = actuals.some(a => a !== '');
          if (!hasData) {
            continue;
          }
          
          this.batchExportData.push({
            kpiName: kpi.name || '',
            kpiCode: kpi.code || 'Export',
            categoryName: kpi.categoryName || '',
            targetStr: targetStr,
            periods: years,
            actuals: actuals,
            base64Data: base64Data,
            analysisLines: analysisLines
          });
        }
        
        Swal.close();
        
        if (this.batchExportData.length === 0) {
           Swal.fire('แจ้งเตือน', 'ไม่พบตัวชี้วัดที่มีผลงานในระยะเวลา 5 ปีงบประมาณนี้', 'info');
           return;
        }
        
        if (!this.batchExportPreviewModalInstance) {
           const el = document.getElementById('batchExportPreviewModal');
           if (el) {
              this.batchExportPreviewModalInstance = new Modal(el);
              el.addEventListener('hidden.bs.modal', () => {
                this.batchExportData = [];
              });
           }
        }
        
        if (this.batchExportPreviewModalInstance) {
           this.batchExportPreviewModalInstance.show();
        }
        
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการดึงข้อมูลตัวชี้วัด', 'error');
      } finally {
        this.isExportingAll = false;
      }
    },
    async confirmBatchExportPdf() {
      this.isExportingPdfFile = true;
      try {
        const element = document.getElementById('batchExportContainer');
        if (!element) {
          throw new Error('ไม่พบข้อมูลสำหรับ Export');
        }
        
        // Temporarily change modal body overflow to visible so html2canvas captures everything
        const modalBody = document.getElementById('batchExportModalBody');
        const originalOverflow = modalBody.style.overflowX;
        modalBody.style.overflow = 'visible';
        
        // Wait a tiny bit for DOM update
        await new Promise(r => setTimeout(r, 100));
        
        const opt = {
          margin:       10,
          filename:     `All_KPIs_${this.selectedLevel}_Trend.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  { scale: 2, useCORS: true, windowWidth: 1250 },
          jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' },
          pagebreak:    { mode: ['css', 'legacy'] }
        };
        
        await html2pdf().set(opt).from(element).save();
        
        // Restore overflow
        modalBody.style.overflowX = originalOverflow;
        modalBody.style.overflowY = 'auto';
        
        if (this.batchExportPreviewModalInstance) {
          this.batchExportPreviewModalInstance.hide();
        }
        Swal.fire({ icon: 'success', title: 'Export PDF สำเร็จ', timer: 1500, showConfirmButton: false });
        
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้างไฟล์ PDF', 'error');
      } finally {
        this.isExportingPdfFile = false;
      }
    },
    async confirmExportTrendPdf() {
      if (!this.exportPreviewData) return;
      
      const element = document.querySelector('#exportPreviewModal .card-body');
      if (!element) return;
      
      const opt = {
        margin:       10,
        filename:     `KPI_Trend_${this.exportPreviewData.kpiCode}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
      };
      
      html2pdf().set(opt).from(element).save();
    },
    async confirmExportTrendWord() {
      if (!this.exportPreviewData) return;
      
      const data = this.exportPreviewData;
      
      // Build headers
      const headers = ['ลำดับ', 'ข้อมูล/ตัวชี้วัด', 'เป้าหมาย ปีปัจจุบัน', ...data.periods.map(String)];
      const tableHeaders = headers.map(h => 
        new TableCell({
          children: [new Paragraph({ children: [new TextRun({ text: h, bold: true, font: 'Sarabun' })], alignment: AlignmentType.CENTER })],
          verticalAlign: VerticalAlign.CENTER,
        })
      );
      
      // Data Row
      const dataRow = ['1', data.kpiName, data.targetStr, ...data.actuals.map(String)];
      const tableCells = dataRow.map(d => 
        new TableCell({
          children: [new Paragraph({ children: [new TextRun({ text: d, font: 'Sarabun' })], alignment: d === data.kpiName ? AlignmentType.LEFT : AlignmentType.CENTER })],
          verticalAlign: VerticalAlign.CENTER,
        })
      );

      // Convert base64 image to Uint8Array
      const byteString = atob(data.base64Data);
      const ia = new Uint8Array(byteString.length);
      for (let i = 0; i < byteString.length; i++) {
          ia[i] = byteString.charCodeAt(i);
      }
      const imageBuffer = ia;
      
      const children = [
        new Paragraph({
          children: [new TextRun({ text: 'รายงานตัวชี้วัดสำคัญ', bold: true, size: 32, font: 'Sarabun' })],
          alignment: AlignmentType.CENTER,
        }),
        new Paragraph({
          children: [new TextRun({ text: data.kpiName, bold: true, size: 28, font: 'Sarabun' })],
          alignment: AlignmentType.CENTER,
        }),
        new Paragraph({ text: '' }),
        new Table({
          width: { size: 100, type: WidthType.PERCENTAGE },
          rows: [
            new TableRow({ children: tableHeaders }),
            new TableRow({ children: tableCells }),
          ],
        }),
        new Paragraph({ text: '' }),
        new Paragraph({
          children: [
            new ImageRun({
              data: imageBuffer,
              transformation: { width: 500, height: 250 }
            })
          ],
          alignment: AlignmentType.CENTER,
        }),
        new Paragraph({ text: '' }),
        new Paragraph({
          children: [new TextRun({ text: 'ผลการวิเคราะห์:', bold: true, font: 'Sarabun' })]
        })
      ];
      
      if (data.analysisLines.length > 0) {
        data.analysisLines.forEach(line => {
          children.push(new Paragraph({ children: [new TextRun({ text: line, font: 'Sarabun' })] }));
        });
      } else {
        children.push(new Paragraph({ children: [new TextRun({ text: '- ไม่มีข้อมูลการวิเคราะห์ -', font: 'Sarabun', italics: true })] }));
      }
      
      const doc = new Document({
        sections: [{
          properties: {},
          children: children,
        }]
      });
      
      Packer.toBlob(doc).then(blob => {
        saveAs(blob, `KPI_Trend_${data.kpiCode}.docx`);
        if (this.exportPreviewModalInstance) {
          this.exportPreviewModalInstance.hide();
        }
        Swal.fire({ icon: 'success', title: 'Export Word สำเร็จ', timer: 1500, showConfirmButton: false });
      });
    },
    prepareChart(history) {
      const sorted = [...history].reverse();
      this.chartData = {
        labels: sorted.map((h) => this.formatDate(h.period_date)),
        datasets: [
          {
            label: 'Actual',
            data: sorted.map((h) => h.actual_value),
            borderColor: '#304ffe',
            backgroundColor: 'rgba(48, 79, 254, 0.1)',
            tension: 0.3,
            fill: true
          },
          {
            label: 'Target',
            data: sorted.map((h) => h.target_value_snapshot),
            borderColor: '#f44336',
            borderDash: [5, 5],
            fill: false
          }
        ]
      };
    },
    formatDate(d) {
      if (!d) return '-';
      const dateObj = new Date(d);
      const lastDayOfMonth = new Date(dateObj.getFullYear(), dateObj.getMonth() + 1, 0);
      return lastDayOfMonth.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    formatDateTime(d) {
      if (!d) return '-';
      return new Date(d).toLocaleString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    checkPassHistory(h) {
      const op = this.selectedKpi?.target_operator || '>=';
      const actual = parseFloat(h.actual_value);
      const target = parseFloat(h.target_value_snapshot);
      if (op === '>=') return actual >= target;
      if (op === '<=') return actual <= target;
      if (op === '>') return actual > target;
      if (op === '<') return actual < target;
      if (op === '=') return actual == target;
      return false;
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(
          `/api-digital/kpi/get_kpi_data.php?action=getAll&year=${this.selectedYear}`
        );
        if (response.data.status === 'success') {
          this.categories = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'Failed to fetch data', 'error');
        }
      } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Network error or API issue', 'error');
      } finally {
        this.loading = false;
      }
    },
    async confirmBatchExportWord() {
      if (!this.batchExportData || this.batchExportData.length === 0) return;
      
      this.isExportingWord = true;
      try {
        Swal.fire({
          title: 'กำลังสร้างไฟล์ Word...',
          text: 'กรุณารอสักครู่ ระบบกำลังประมวลผลข้อมูล',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const children = [];
        
        // Document Title
        children.push(
          new Paragraph({
            children: [
              new TextRun({ text: `รายงานรวมตัวชี้วัด (${this.selectedLevel || 'ทุกระดับ'})`, bold: true, size: 36, font: 'Sarabun' })
            ],
            alignment: AlignmentType.CENTER,
            spacing: { after: 400 }
          })
        );

        // Loop through all KPIs in batchExportData
        this.batchExportData.forEach((kpiItem, index) => {
          // Add page break before every KPI except the first one
          if (index > 0) {
            children.push(
              new Paragraph({
                children: [new TextRun({ text: '' })],
                pageBreakBefore: true
              })
            );
          }

          // Category & KPI Name Headers
          children.push(
            new Paragraph({
              children: [new TextRun({ text: kpiItem.categoryName, bold: true, size: 28, font: 'Sarabun' })],
              alignment: AlignmentType.CENTER,
              spacing: { before: 200, after: 100 }
            })
          );
          children.push(
            new Paragraph({
              children: [new TextRun({ text: kpiItem.kpiName, bold: true, size: 24, font: 'Sarabun' })],
              alignment: AlignmentType.CENTER,
              spacing: { after: 300 }
            })
          );

          // Build History Table Headers
          const headers = ['ลำดับ', 'ข้อมูล/ตัวชี้วัด', 'เป้าหมาย ปีปัจจุบัน', ...kpiItem.periods.map(String)];
          const tableHeaders = headers.map(h => 
            new TableCell({
              children: [new Paragraph({ children: [new TextRun({ text: h, bold: true, font: 'Sarabun' })], alignment: AlignmentType.CENTER })],
              verticalAlign: VerticalAlign.CENTER,
              shading: { fill: 'E0E0E0' },
              margins: { top: 100, bottom: 100, left: 100, right: 100 }
            })
          );
          
          // Data Row
          const dataRow = [String(index + 1), kpiItem.kpiName, kpiItem.targetStr, ...kpiItem.actuals.map(String)];
          const tableCells = dataRow.map((d, i) => 
            new TableCell({
              children: [new Paragraph({ children: [new TextRun({ text: d, font: 'Sarabun' })], alignment: i === 1 ? AlignmentType.LEFT : AlignmentType.CENTER })],
              verticalAlign: VerticalAlign.CENTER,
              margins: { top: 100, bottom: 100, left: 100, right: 100 }
            })
          );

          // Chart Image Row
          let chartCell;
          if (kpiItem.base64Data) {
            // Convert base64 string back to uint8 array
            const byteString = atob(kpiItem.base64Data);
            const ia = new Uint8Array(byteString.length);
            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            
            chartCell = new TableCell({
              columnSpan: 3,
              children: [
                new Paragraph({
                  children: [
                    new ImageRun({
                      data: ia,
                      transformation: { width: 500, height: 250 }
                    })
                  ],
                  alignment: AlignmentType.CENTER
                })
              ],
              verticalAlign: VerticalAlign.CENTER,
              margins: { top: 100, bottom: 100, left: 100, right: 100 }
            });
          } else {
            chartCell = new TableCell({
              columnSpan: 3,
              children: [
                new Paragraph({
                  children: [new TextRun({ text: 'No Chart Data', font: 'Sarabun', color: '808080' })],
                  alignment: AlignmentType.CENTER
                })
              ],
              verticalAlign: VerticalAlign.CENTER,
              margins: { top: 100, bottom: 100, left: 100, right: 100 }
            });
          }

          // Analysis Cell
          const analysisParagraphs = [
            new Paragraph({
              children: [new TextRun({ text: 'ผลการวิเคราะห์:', bold: true, underline: { type: 'single' }, font: 'Sarabun' })],
              spacing: { after: 100 }
            })
          ];
          
          if (kpiItem.analysisLines && kpiItem.analysisLines.length > 0) {
            kpiItem.analysisLines.forEach(line => {
              analysisParagraphs.push(
                new Paragraph({
                  children: [new TextRun({ text: line, font: 'Sarabun' })],
                  spacing: { after: 50 }
                })
              );
            });
          } else {
            analysisParagraphs.push(
              new Paragraph({
                children: [new TextRun({ text: 'ไม่มีข้อมูลการวิเคราะห์', font: 'Sarabun', italics: true, color: '808080' })]
              })
            );
          }

          const analysisCell = new TableCell({
            columnSpan: kpiItem.periods.length,
            children: analysisParagraphs,
            verticalAlign: VerticalAlign.TOP,
            margins: { top: 100, bottom: 100, left: 100, right: 100 }
          });

          // Create the main KPI Table
          children.push(
            new Table({
              rows: [
                new TableRow({ children: tableHeaders }),
                new TableRow({ children: tableCells }),
                new TableRow({ children: [chartCell, analysisCell] })
              ],
              width: { size: 100, type: WidthType.PERCENTAGE }
            })
          );
        });

        const doc = new Document({
          sections: [{ properties: {}, children: children }]
        });

        const blob = await Packer.toBlob(doc);
        saveAs(blob, `All_KPIs_${this.selectedLevel || 'Trend'}.docx`);
        
        if (this.batchExportPreviewModalInstance) {
          this.batchExportPreviewModalInstance.hide();
        }
        Swal.fire({ icon: 'success', title: 'ดาวน์โหลด Word สำเร็จ', timer: 1500, showConfirmButton: false });
        
      } catch (err) {
        console.error('Error generating Word document:', err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้างไฟล์ Word', 'error');
      } finally {
        this.isExportingWord = false;
      }
    },
    checkStatus(kpi) {
      if (kpi.actual_value === null) return 'nodata';

      if (kpi.failed_periods_count && parseInt(kpi.failed_periods_count) > 0) return 'fail';

      const actual = parseFloat(kpi.actual_value);
      const target = parseFloat(kpi.target_value);
      const op = kpi.target_operator;

      switch (op) {
        case '>=':
          return actual >= target ? 'pass' : 'fail';
        case '<=':
          return actual <= target ? 'pass' : 'fail';
        case '>':
          return actual > target ? 'pass' : 'fail';
        case '<':
          return actual < target ? 'pass' : 'fail';
        case '=':
          return actual == target ? 'pass' : 'fail';
        default:
          return 'nodata';
      }
    },
    getExpectedPeriodDates(periodicity) {
      if (!this.selectedYear) return [];
      
      const gYear = this.selectedYear - 543;
      const prevYear = gYear - 1;

      const now = new Date();
      const currentCalendarMonth = now.getMonth() + 1;
      const currentCalendarYear = now.getFullYear();
      const currentFiscalYear = currentCalendarMonth >= 10 ? currentCalendarYear + 543 + 1 : currentCalendarYear + 543;

      const selectedYear = this.selectedYear;

      const currentFiscalMonth = currentCalendarMonth >= 10 ? currentCalendarMonth - 9 : currentCalendarMonth + 3;
      const currentFiscalQuarter = Math.ceil(currentFiscalMonth / 3);
      const currentFiscalHalf = Math.ceil(currentFiscalQuarter / 2);

      const isArrived = (fiscalIndex, periodType) => {
        if (selectedYear < currentFiscalYear) return true;
        if (selectedYear > currentFiscalYear) return false;
        if (periodType === 'month') return fiscalIndex <= currentFiscalMonth;
        if (periodType === 'quarter') return fiscalIndex <= currentFiscalQuarter;
        if (periodType === 'Semiannual report') return fiscalIndex <= currentFiscalHalf;
        if (periodType === 'year') return true; 
        return true;
      };

      let expected = [];
      if (periodicity === 'quarter') {
        if (isArrived(1, 'quarter')) expected.push(`${prevYear}-10-01`);
        if (isArrived(2, 'quarter')) expected.push(`${gYear}-01-01`);
        if (isArrived(3, 'quarter')) expected.push(`${gYear}-04-01`);
        if (isArrived(4, 'quarter')) expected.push(`${gYear}-07-01`);
      } else if (periodicity === 'Semiannual report') {
        if (isArrived(1, 'Semiannual report')) expected.push(`${prevYear}-10-01`);
        if (isArrived(2, 'Semiannual report')) expected.push(`${gYear}-04-01`);
      } else if (periodicity === 'year') {
        if (isArrived(1, 'year')) expected.push(`${prevYear}-10-01`);
      } else {
        // month
        for (let i = 1; i <= 12; i++) {
          const fiscalIndex = i >= 10 ? i - 9 : i + 3;
          if (isArrived(fiscalIndex, 'month')) {
            const y = i >= 10 ? prevYear : gYear;
            const m = String(i).padStart(2, '0');
            expected.push(`${y}-${m}-01`);
          }
        }
      }
      return expected;
    },
    getMissingPeriods(kpi) {
      if (!kpi) return [];
      const reported = kpi.reported_periods ? kpi.reported_periods.split(',') : [];
      const expected = this.getExpectedPeriodDates(kpi.kpi_periodicity || 'month');
      return expected.filter(d => !reported.includes(d));
    },
    showMissingPeriodsDetails(kpi) {
      const missingDates = this.getMissingPeriods(kpi);
      if (missingDates.length === 0) return;
      
      let htmlContent = '<div class="text-start mt-3"><ul class="list-group list-group-flush border rounded">';
      missingDates.forEach(d => {
        const type = kpi.kpi_periodicity || 'month';
        let label = this.formatDate(d);
        
        if (type === 'quarter') {
            const m = parseInt(d.split('-')[1]);
            let q = 1;
            if (m === 1) q = 2; else if (m === 4) q = 3; else if (m === 7) q = 4;
            label = `ไตรมาสที่ ${q} (${this.formatDate(d)})`;
        } else if (type === 'Semiannual report') {
            const m = parseInt(d.split('-')[1]);
            let h = m === 10 ? 1 : 2;
            label = `ครึ่งปีที่ ${h} (${this.formatDate(d)})`;
        } else if (type === 'year') {
            label = `ปีงบประมาณ ${parseInt(d.split('-')[0]) + 1 + 543}`;
        } else {
            // format just month and year for monthly
            const dateObj = new Date(d);
            label = dateObj.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
        }
        
        htmlContent += `<li class="list-group-item text-danger"><i class="bi bi-x-circle me-2"></i> ${label}</li>`;
      });
      htmlContent += '</ul></div>';

      Swal.fire({
        title: 'รอบที่ยังไม่ได้รายงาน',
        html: htmlContent,
        icon: 'warning',
        confirmButtonColor: '#304ffe',
        confirmButtonText: 'ปิดหน้าต่าง'
      });
    },
    openEntryModal(kpi) {
      this.$refs.entryModal.open(kpi);
    },
    // Removed goToEntry as it's replaced by modal
    viewDetails(kpi) {
      // Legacy method, replaced by openHistoryModal
      this.openHistoryModal(kpi);
    },
    exportDashboardExcel() {
      if (!this.filteredCategories || this.filteredCategories.length === 0) {
        Swal.fire('Warning', 'ไม่มีข้อมูลสำหรับ Export', 'warning');
        return;
      }

      const data = [];
      data.push([
        'หมวดหมู่ (Dimension)', 
        'รหัส KPI', 
        'ระดับ', 
        'ชื่อตัวชี้วัด', 
        'ผู้รับผิดชอบ', 
        'เป้าหมาย', 
        'ผลงานล่าสุด', 
        'สถานะ'
      ]);

      this.filteredCategories.forEach(cat => {
        if (cat.kpis) {
          cat.kpis.forEach(kpi => {
            const status = this.checkStatus(kpi);
            let statusText = 'No Data';
            if (status === 'pass') statusText = 'ผ่าน (Pass)';
            else if (status === 'fail') statusText = 'ไม่ผ่าน (Fail)';

            const targetStr = `${kpi.target_operator || ''} ${kpi.target_value || ''} ${kpi.unit || ''}`.trim();
            const actualStr = kpi.actual_value !== null ? kpi.actual_value : 'รอการบันทึก';

            data.push([
              cat.name || '',
              kpi.code || '-',
              kpi.kpi_level || '-',
              kpi.name || '',
              kpi.responsible_person || 'ยังไม่ระบุ',
              targetStr,
              actualStr,
              statusText
            ]);
          });
        }
      });

      const ws = XLSX.utils.aoa_to_sheet(data);
      
      const colWidths = [
        { wch: 30 },
        { wch: 15 },
        { wch: 20 },
        { wch: 60 },
        { wch: 25 },
        { wch: 20 },
        { wch: 15 },
        { wch: 15 }
      ];
      ws['!cols'] = colWidths;

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "KPI Dashboard");
      XLSX.writeFile(wb, `KPI_Dashboard_${this.selectedYear}.xlsx`);
    }
  }
};
</script>

<style scoped>
.ring-active {
  box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.15) !important;
  transform: scale(1.02);
}
.card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card {
  transition: transform 0.25s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.kpi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15) !important;
}
.card-header {
  background-color: transparent;
}
</style>
