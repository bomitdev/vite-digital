<template>
  <div class="requisition-manage-container container-fluid py-3 px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <nav aria-label="breadcrumb" class="mb-2">
          <div class="d-inline-flex flex-wrap align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border">
            <button
              type="button"
              class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold text-secondary d-inline-flex align-items-center gap-1 border-0 nav-pill-btn"
              @click="handleBackToDashboard"
            >
              <i class="bi bi-house-door-fill text-primary"></i>
              <span>หน้าหลัก</span>
            </button>
            <i class="bi bi-chevron-right text-muted small"></i>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fw-bold">
              <i class="bi bi-clipboard2-check-fill me-1"></i>
              คำขอเบิกและอนุมัติจ่าย
            </span>
          </div>
        </nav>
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-clipboard2-check-fill text-warning"></i>
          รายการขอเบิกและพิจารณาอนุมัติจ่าย
        </h3>
        <small class="text-muted">ตรวจสอบคำขอเบิกพัสดุจากหน่วยงาน และพิจารณาอนุมัติตัดจ่ายสต๊อก</small>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button
          v-if="isInventoryAdmin"
          class="btn btn-outline-dark rounded-pill px-3 shadow-sm fw-semibold"
          @click="openGlobalSigModal"
          title="ตั้งค่าชื่อ ตำแหน่ง และลายเซ็น ผู้จ่าย / ผู้สั่งจ่าย สำหรับ PDF"
        >
          <i class="bi bi-pen me-1"></i> ตั้งค่าลายเซ็น (PDF)
        </button>
        <button
          v-if="isSystemAdmin"
          class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold"
          @click="recalculateBalances"
          :disabled="recalculating"
          title="คำนวณยอดคงเหลือคลังและยอดคงเหลือในบัญชีคุมใหม่"
        >
          <span v-if="recalculating" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="bi bi-calculator me-1"></i> คำนวณยอดคงเหลือใหม่
        </button>
        <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" @click="fetchRequisitions">
          <i class="bi bi-arrow-clockwise me-1" :class="{ 'spin-anim': loading }"></i> รีเฟรช
        </button>
        <button class="btn btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm" @click="handleGoToForm">
          <i class="bi bi-plus-circle-fill me-1"></i> สร้างใบขอเบิกใหม่
        </button>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 stat-mini cursor-pointer" :class="{ 'active-card': currentStatus === 'PENDING' }" @click="setStatusFilter('PENDING')">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-bold">รอพิจารณาอนุมัติ</div>
              <div class="fs-4 fw-bold text-warning">{{ stats.pending_count || 0 }}</div>
            </div>
            <div class="icon-circle bg-warning-subtle text-warning">
              <i class="bi bi-hourglass-split fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 stat-mini cursor-pointer" :class="{ 'active-card': currentStatus === 'APPROVED' }" @click="setStatusFilter('APPROVED')">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-bold">อนุมัติจ่ายแล้ว</div>
              <div class="fs-4 fw-bold text-success">{{ stats.approved_count || 0 }}</div>
            </div>
            <div class="icon-circle bg-success-subtle text-success">
              <i class="bi bi-check-circle-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 stat-mini cursor-pointer" :class="{ 'active-card': currentStatus === 'REJECTED' }" @click="setStatusFilter('REJECTED')">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-bold">ไม่อนุมัติ / ปฏิเสธ</div>
              <div class="fs-4 fw-bold text-danger">{{ stats.rejected_count || 0 }}</div>
            </div>
            <div class="icon-circle bg-danger-subtle text-danger">
              <i class="bi bi-x-circle-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 stat-mini cursor-pointer" :class="{ 'active-card': currentStatus === 'all' }" @click="setStatusFilter('all')">
          <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small fw-bold">คำขอทั้งหมด</div>
              <div class="fs-4 fw-bold text-primary">{{ stats.total_count || 0 }}</div>
            </div>
            <div class="icon-circle bg-primary-subtle text-primary">
              <i class="bi bi-collection-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-3">
        <div class="row g-2 align-items-center">
          <!-- Status Pill Tabs -->
          <div class="col-lg-6">
            <div class="btn-group p-1 bg-light rounded-pill w-100" role="group">
              <button 
                type="button" 
                class="btn rounded-pill fw-bold text-nowrap" 
                :class="currentStatus === 'PENDING' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-muted'"
                @click="setStatusFilter('PENDING')"
              >
                รออนุมัติ ({{ stats.pending_count || 0 }})
              </button>
              <button 
                type="button" 
                class="btn rounded-pill fw-bold text-nowrap" 
                :class="currentStatus === 'APPROVED' ? 'btn-success text-white shadow-sm' : 'btn-light text-muted'"
                @click="setStatusFilter('APPROVED')"
              >
                จ่ายแล้ว ({{ stats.approved_count || 0 }})
              </button>
              <button 
                type="button" 
                class="btn rounded-pill fw-bold text-nowrap" 
                :class="currentStatus === 'REJECTED' ? 'btn-danger text-white shadow-sm' : 'btn-light text-muted'"
                @click="setStatusFilter('REJECTED')"
              >
                ไม่อนุมัติ ({{ stats.rejected_count || 0 }})
              </button>
              <button 
                type="button" 
                class="btn rounded-pill fw-bold text-nowrap" 
                :class="currentStatus === 'all' ? 'btn-primary text-white shadow-sm' : 'btn-light text-muted'"
                @click="setStatusFilter('all')"
              >
                ทั้งหมด ({{ stats.total_count || 0 }})
              </button>
            </div>
          </div>

          <!-- Search Input -->
          <div class="col-lg-6">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input 
                type="text" 
                class="form-control border-start-0 rounded-end-pill" 
                placeholder="ค้นหาเลขที่คำขอ, ชื่อผู้ขอเบิก, หน่วยงาน, หรือวัตถุประสงค์..."
                v-model="searchQuery"
                @input="debounceSearch"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Requisitions List Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-body p-0">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-warning" role="status"></div>
          <div class="mt-2 text-muted fw-bold">กำลังโหลดรายการคำขอเบิก...</div>
        </div>

        <!-- Empty State -->
        <div v-else-if="requisitions.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
          <h5 class="fw-bold">ไม่พบรายการคำขอเบิก</h5>
          <p class="small text-muted mb-3" v-if="currentStatus === 'PENDING'">ยังไม่มีคำขอเบิกที่รอการพิจารณาในคลังนี้</p>
          <p class="small text-muted mb-3" v-else>ไม่มีข้อมูลตรงกับเงื่อนไขการค้นหา</p>
          <button class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 fw-bold" @click="handleGoToForm">
            <i class="bi bi-plus-lg me-1"></i> สร้างใบขอเบิกใหม่
          </button>
        </div>

        <!-- Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary small">
              <tr>
                <th class="ps-4" style="width: 140px;">เลขที่ใบขอเบิก</th>
                <th style="width: 130px;">วันที่ขอ</th>
                <th>หน่วยงานที่ขอเบิก</th>
                <th>ผู้ขอเบิก</th>
                <th>วัตถุประสงค์</th>
                <th class="text-center" style="width: 110px;">จำนวนรายการ</th>
                <th class="text-end" style="width: 130px;">ยอดเงิน</th>
                <th class="text-center" style="width: 130px;">สถานะ</th>
                <th class="text-end pe-4 text-nowrap" style="min-width: 290px;">การจัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in requisitions" :key="req.id">
                <td class="ps-4">
                  <div class="fw-bold text-primary font-monospace">{{ req.req_no }}</div>
                  <small class="badge bg-light text-dark border" v-if="!warehouseId">{{ req.warehouse_name }}</small>
                </td>
                <td class="small text-muted">
                  {{ formatDate(req.request_date) }}
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ req.department_name || '-' }}</div>
                </td>
                <td>
                  <div class="fw-medium">{{ req.requester_name }}</div>
                  <small class="text-muted" v-if="req.requester_phone"><i class="bi bi-telephone me-1"></i>{{ req.requester_phone }}</small>
                </td>
                <td class="small text-muted text-truncate" style="max-width: 200px;" :title="req.purpose">
                  {{ req.purpose || '-' }}
                </td>
                <td class="text-center">
                  <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                    {{ req.item_count || 0 }} รายการ
                  </span>
                </td>
                <td class="text-end text-nowrap">
                  <span class="fw-bold text-dark">{{ formatCurrency(req.total_amount) }}</span>
                  <small class="text-muted ms-1">บาท</small>
                </td>
                <td class="text-center">
                  <span v-if="req.status === 'PENDING'" class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">
                    <i class="bi bi-hourglass-split me-1"></i>รออนุมัติ
                  </span>
                  <span v-else-if="req.status === 'APPROVED'" class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                    <i class="bi bi-check-circle-fill me-1"></i>อนุมัติแล้ว
                  </span>
                  <span v-else-if="req.status === 'REJECTED'" class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1">
                    <i class="bi bi-x-circle-fill me-1"></i>ไม่อนุมัติ
                  </span>
                  <span v-else-if="req.status === 'CANCELLED'" class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>ยกเลิกแล้ว
                  </span>
                  <span v-else class="badge bg-light text-dark rounded-pill px-3 py-1">
                    {{ req.status }}
                  </span>
                </td>
                <td class="text-end pe-4 text-nowrap">
                  <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">
                    <!-- Pending: Approve Button (Warehouse Manager / Admin only) -->
                    <button 
                      v-if="req.status === 'PENDING' && canApproveRequisition(req)" 
                      class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-1 action-pill-btn"
                      @click="openApproveModal(req)"
                      title="ตรวจสอบและพิจารณาอนุมัติจ่าย"
                    >
                      <i class="bi bi-check2-circle"></i>
                      <span>พิจารณา</span>
                    </button>

                    <!-- Edit Button (Own pending request or Warehouse Manager) -->
                    <button
                      v-if="canEditRequisition(req)"
                      class="btn btn-sm bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 fw-semibold d-inline-flex align-items-center gap-1 action-pill-btn"
                      @click="editRequisition(req)"
                      title="แก้ไขรายการขอเบิก"
                    >
                      <i class="bi bi-pencil-square"></i>
                      <span>แก้ไข</span>
                    </button>

                    <!-- PDF Preview & Download Button (Allowed only after approval) -->
                    <button 
                      class="btn btn-sm rounded-pill px-2 fw-semibold d-inline-flex align-items-center gap-1 action-pill-btn"
                      :class="req.status === 'APPROVED' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'btn-light text-muted border opacity-50'"
                      :disabled="req.status !== 'APPROVED'"
                      @click="openPdfPreview(req)"
                      :title="req.status === 'APPROVED' ? 'แสดงตัวอย่างและดาวน์โหลด PDF' : 'พิมพ์ PDF ได้เมื่ออนุมัติจ่ายแล้วเท่านั้น'"
                    >
                      <i class="bi bi-file-earmark-pdf-fill"></i>
                      <span>PDF</span>
                    </button>

                    <!-- Cancel Approved & Restore Stock Button (System Admin only) -->
                    <button
                      v-if="req.status === 'APPROVED' && isSystemAdmin"
                      class="btn btn-sm bg-secondary-subtle text-dark border border-secondary-subtle rounded-pill px-2 fw-semibold d-inline-flex align-items-center gap-1 action-pill-btn"
                      @click="confirmCancelApproved(req)"
                      title="ยกเลิกการจ่ายและคืนยอดพัสดุเข้าสต๊อก"
                    >
                      <i class="bi bi-arrow-counterclockwise"></i>
                      <span>ยกเลิกการจ่าย</span>
                    </button>

                    <!-- Cancel / Delete Button -->
                    <button 
                      v-if="canCancelRequisition(req)"
                      class="btn btn-sm btn-light text-danger border rounded-pill px-2 fw-semibold d-inline-flex align-items-center gap-1 action-pill-btn"
                      @click="confirmDelete(req)"
                      :title="canApproveRequisition(req) ? 'ลบใบขอเบิกนี้' : 'ยกเลิกใบขอเบิกของตนเอง'"
                    >
                      <i :class="canApproveRequisition(req) ? 'bi bi-trash3-fill' : 'bi bi-x-circle-fill'"></i>
                      <span v-if="!canApproveRequisition(req)">ยกเลิก</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. MODAL: พิจารณาอนุมัติคำขอเบิก (Approve Modal) -->
    <!-- ========================================== -->
    <div v-if="showApproveModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1060;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header bg-warning-subtle py-3 px-4 border-0">
            <div>
              <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-clipboard2-check text-warning-emphasis fs-4"></i>
                พิจารณาอนุมัติคำขอเบิกพัสดุ: <span class="text-primary font-monospace">{{ activeReq?.req_no }}</span>
              </h5>
              <small class="text-muted">คลัง: {{ activeReq?.warehouse_name }} | วันที่ขอ: {{ formatDate(activeReq?.request_date) }}</small>
            </div>
            <button type="button" class="btn-close" @click="showApproveModal = false"></button>
          </div>

          <div class="modal-body p-4">
            <!-- Requester Info Summary Banner -->
            <div class="p-3 bg-light rounded-3 border mb-4">
              <div class="row g-2">
                <div class="col-md-4">
                  <small class="text-muted d-block">หน่วยงานที่ขอเบิก</small>
                  <span class="fw-bold text-dark">{{ activeReq?.department_name || '-' }}</span>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block">ชื่อผู้ขอเบิก / เบอร์โทร</small>
                  <span class="fw-bold text-dark">{{ activeReq?.requester_name }} {{ activeReq?.requester_phone ? `(${activeReq.requester_phone})` : '' }}</span>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block">วัตถุประสงค์การใช้งาน</small>
                  <span class="text-dark">{{ activeReq?.purpose || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Items Table for Review -->
            <h6 class="fw-bold text-dark mb-3">
              <i class="bi bi-boxes me-1 text-primary"></i> รายการพัสดุที่ขอเบิก และการตัดจ่ายจากสต๊อก
            </h6>

            <div v-if="loadingReqDetails" class="text-center py-4">
              <div class="spinner-border text-primary spinner-border-sm me-2"></div> กำลังดึงข้อมูลสต๊อกและล็อตคงเหลือ...
            </div>

            <div v-else class="table-responsive mb-4">
              <table class="table table-bordered align-middle">
                <thead class="table-light small text-secondary">
                  <tr>
                    <th style="width: 50px;" class="text-center">ลำดับ</th>
                    <th>รายการพัสดุ</th>
                    <th class="text-center" style="width: 120px;">คงเหลือในคลัง</th>
                    <th class="text-center" style="width: 110px;">จำนวนที่ขอ</th>
                    <th style="width: 240px;">เลือกล็อตตัดจ่าย (FEFO)</th>
                    <th class="text-center" style="width: 130px;">จำนวนอนุมัติ</th>
                    <th style="width: 140px;">หมายเหตุ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in activeReqItems" :key="item.id">
                    <td class="text-center">{{ idx + 1 }}</td>
                    <td>
                      <div class="fw-bold text-dark">[{{ item.item_code }}] {{ item.item_name }}</div>
                      <small class="text-muted">หน่วยนับ: {{ item.unit_name || 'หน่วย' }}</small>
                    </td>
                    <td class="text-center">
                      <span 
                        class="badge rounded-pill px-3 py-1"
                        :class="item.current_stock > 0 ? 'bg-info-subtle text-info-emphasis' : 'bg-danger-subtle text-danger'"
                      >
                        {{ formatNumber(item.current_stock) }} {{ item.unit_name }}
                      </span>
                    </td>
                    <td class="text-center fw-bold fs-6 text-primary">
                      {{ formatNumber(item.qty_requested) }}
                    </td>
                    <td>
                      <!-- Lot Selection Dropdown (FEFO) -->
                      <select 
                        class="form-select form-select-sm" 
                        v-model="item.selected_lot_id"
                        :disabled="!item.available_lots || item.available_lots.length === 0"
                      >
                        <option v-if="!item.available_lots || item.available_lots.length === 0" value="">-- สินค้าหมด (ไม่มี Lot) --</option>
                        <option v-else-if="item.available_lots.length > 1" value="">-- ตัดจ่ายอัตโนมัติ (FEFO) --</option>
                        <option v-for="lot in item.available_lots" :key="lot.lot_id" :value="lot.lot_id">
                          Lot: {{ lot.lot_number || '-' }} | Exp: {{ lot.expiry_date || '-' }} (มี {{ formatNumber(lot.balance_qty) }})
                        </option>
                      </select>
                      <small
                        v-if="item.selected_lot_id && getSelectedLotBalance(item) < item.suggested_qty && item.suggested_qty <= item.current_stock"
                        class="text-info d-block mt-1"
                        style="font-size: 0.72rem;"
                      >
                        <i class="bi bi-info-circle me-1"></i>Lot นี้มี {{ formatNumber(getSelectedLotBalance(item)) }} ส่วนที่เหลือจะตัดจาก Lot ถัดไปอัตโนมัติ
                      </small>
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <input 
                          type="number" 
                          step="1" 
                          min="0" 
                          :max="Math.floor(Number(item.current_stock) || 0)"
                          class="form-control text-center fw-bold"
                          :class="item.suggested_qty > 0 ? 'text-success border-success' : 'text-danger border-danger'"
                          v-model.number="item.suggested_qty"
                          @change="item.suggested_qty = Math.max(0, Math.round(Number(item.suggested_qty) || 0))"
                        />
                        <span class="input-group-text small">{{ item.unit_name }}</span>
                      </div>
                      <small v-if="item.suggested_qty > item.current_stock" class="text-danger d-block mt-1 small">
                        *เกินยอดคงเหลือ
                      </small>
                    </td>
                    <td>
                      <small class="text-muted">{{ item.item_note || '-' }}</small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Approver Note & Reason -->
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">ชื่อผู้อนุมัติ</label>
                <input type="text" class="form-control" v-model="approverName" placeholder="ระบุชื่อผู้อนุมัติ" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">บันทึกข้อความ / หมายเหตุคำสั่ง</label>
                <input type="text" class="form-control" v-model="approvalNote" placeholder="เช่น อนุมัติจ่ายตามจำนวนที่มีจริงในคลัง" />
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light px-4 py-3 border-0 d-flex justify-content-between">
            <button 
              type="button" 
              class="btn btn-outline-danger rounded-pill px-4" 
              :disabled="submittingAction"
              @click="rejectRequest"
            >
              <i class="bi bi-x-circle me-1"></i> ปฏิเสธคำขอเบิก
            </button>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showApproveModal = false">
                ปิด
              </button>
              <button 
                type="button" 
                class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"
                :disabled="submittingAction || isApproveDisabled"
                @click="approveRequest"
              >
                <span v-if="submittingAction" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-check-circle-fill me-1"></i> อนุมัติและตัดจ่ายสต๊อกทันที
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- ========================================== -->
    <!-- 2. MODAL: แสดงตัวอย่าง & ดาวน์โหลด PDF (PDF Preview Modal) -->
    <!-- ========================================== -->
    <div v-if="showPreviewModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1060;">
      <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1050px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <div class="modal-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="p-2 bg-danger bg-opacity-10 text-danger rounded-3">
                <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                  ตัวอย่างใบเบิกพัสดุ
                  <span class="badge bg-primary-subtle text-primary border font-monospace fs-6">{{ previewDocId }}</span>
                </h5>
                <small class="text-muted">ตรวจสอบความถูกต้องของแบบฟอร์ม (ฟอนต์ Sarabun) ก่อนดาวน์โหลดใช้งาน</small>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closePreviewModal"></button>
          </div>

          <div class="modal-body p-0 bg-secondary bg-opacity-10" style="height: 75vh; position: relative;">
            <!-- Loading Indicator -->
            <div v-if="loadingPdf" class="d-flex flex-column align-items-center justify-content-center h-100">
              <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
              <h6 class="fw-bold text-dark">กำลังจัดหน้าเอกสารและสร้างตัวอย่าง PDF...</h6>
              <small class="text-muted">ระบบกำลังประมวลผลฟอนต์ Sarabun และลายเซ็นดิจิทัล</small>
            </div>

            <!-- Native Browser PDF Viewer (Fit to Page) -->
            <iframe
              v-else-if="pdfPreviewUrl"
              :src="pdfPreviewUrl"
              class="w-100 h-100 border-0"
              title="PDF Preview"
            ></iframe>

            <!-- Error fallback -->
            <div v-else class="text-center py-5 text-muted">
              <i class="bi bi-exclamation-triangle fs-1 text-warning mb-2 d-block"></i>
              <h6>ไม่สามารถแสดงตัวอย่างเอกสารได้</h6>
            </div>
          </div>

          <div class="modal-footer bg-white px-4 py-3 border-top d-flex justify-content-between align-items-center">
            <button 
              type="button" 
              class="btn btn-outline-secondary rounded-pill px-3 shadow-sm"
              @click="openInNewTab"
              :disabled="!pdfPreviewUrl"
              title="เปิดดูในแท็บใหม่ของเบราว์เซอร์"
            >
              <i class="bi bi-box-arrow-up-right me-1"></i> เปิดในแท็บใหม่
            </button>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" @click="closePreviewModal">
                ปิด
              </button>
              <button 
                type="button" 
                class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm"
                @click="downloadPdf"
                :disabled="!currentPdfDoc"
              >
                <i class="bi bi-download me-1"></i> ดาวน์โหลด PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Signers & Signatures Modal (PDF) -->
    <div v-if="showGlobalSigModal" class="modal-backdrop fade show"></div>
    <div v-if="showGlobalSigModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-pen text-primary me-2"></i>ตั้งค่าผู้ลงนามและลายเซ็น (สำหรับ PDF)
            </h5>
            <button type="button" class="btn-close" @click="showGlobalSigModal = false"></button>
          </div>
          <div class="px-4 pt-3 border-bottom bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-tabs border-0">
              <li class="nav-item">
                <button
                  type="button"
                  class="nav-link fw-semibold"
                  :class="sigModalTab === 'global' ? 'active bg-white text-primary' : 'text-secondary'"
                  @click="sigModalTab = 'global'"
                >
                  <i class="bi bi-gear me-1"></i>ผู้จ่าย / ผู้สั่งจ่าย (ส่วนกลาง)
                </button>
              </li>
              <li class="nav-item">
                <button
                  type="button"
                  class="nav-link fw-semibold"
                  :class="sigModalTab === 'dept' ? 'active bg-white text-primary' : 'text-secondary'"
                  @click="sigModalTab = 'dept'"
                >
                  <i class="bi bi-people-fill me-1"></i>หัวหน้ากลุ่มงาน ({{ deptSignersList.length }})
                </button>
              </li>
            </ul>
          </div>
          <div class="modal-body p-4">
            <div v-if="loadingGlobalSig" class="text-center py-4 text-muted">
              <div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดการตั้งค่า...
            </div>
            <!-- Tab 1: Global Signers -->
            <div v-else-if="sigModalTab === 'global'" class="row g-4">
              <!-- ผู้จ่าย -->
              <div class="col-md-6">
                <div class="p-3 rounded-4 bg-light border h-100">
                  <h6 class="fw-bold text-primary mb-3">
                    <i class="bi bi-person-check me-1"></i>ผู้จ่ายพัสดุ
                  </h6>
                  <div class="mb-2">
                    <label class="form-label small fw-bold">ชื่อผู้จ่าย</label>
                    <input type="text" class="form-control form-control-sm" v-model="globalSigForm.payer_name" placeholder="ระบุชื่อผู้จ่าย" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label small fw-bold">ตำแหน่ง</label>
                    <input type="text" class="form-control form-control-sm" v-model="globalSigForm.payer_position" placeholder="ระบุตำแหน่งผู้จ่าย" />
                  </div>
                  <div>
                    <label class="form-label small fw-bold">ลายเซ็นผู้จ่าย (PNG/JPG)</label>
                    <input
                      type="file"
                      accept="image/png,image/jpeg,image/jpg"
                      class="form-control form-control-sm"
                      @change="handleGlobalSigUpload($event, 'payer_signature')"
                    />
                    <div v-if="globalSigForm.payer_signature" class="mt-2 p-2 bg-white border rounded text-center">
                      <img :src="globalSigForm.payer_signature" alt="Payer Signature" style="max-height: 50px;" />
                      <div class="mt-1">
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" @click="globalSigForm.payer_signature = ''">
                          ลบลายเซ็น
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ผู้สั่งจ่าย -->
              <div class="col-md-6">
                <div class="p-3 rounded-4 bg-light border h-100">
                  <h6 class="fw-bold text-success mb-3">
                    <i class="bi bi-award me-1"></i>ผู้สั่งจ่าย (ผู้อนุมัติใบเบิก)
                  </h6>
                  <div class="mb-2">
                    <label class="form-label small fw-bold">ชื่อผู้สั่งจ่าย</label>
                    <input type="text" class="form-control form-control-sm" v-model="globalSigForm.approver_name" placeholder="ระบุชื่อผู้สั่งจ่าย" />
                  </div>
                  <div class="mb-3">
                    <label class="form-label small fw-bold">ตำแหน่ง</label>
                    <input type="text" class="form-control form-control-sm" v-model="globalSigForm.approver_position" placeholder="ระบุตำแหน่งผู้สั่งจ่าย" />
                  </div>
                  <div>
                    <label class="form-label small fw-bold">ลายเซ็นผู้สั่งจ่าย (PNG/JPG)</label>
                    <input
                      type="file"
                      accept="image/png,image/jpeg,image/jpg"
                      class="form-control form-control-sm"
                      @change="handleGlobalSigUpload($event, 'approver_signature')"
                    />
                    <div v-if="globalSigForm.approver_signature" class="mt-2 p-2 bg-white border rounded text-center">
                      <img :src="globalSigForm.approver_signature" alt="Approver Signature" style="max-height: 50px;" />
                      <div class="mt-1">
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" @click="globalSigForm.approver_signature = ''">
                          ลบลายเซ็น
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 2: Department Heads (ผู้ขอใช้พัสดุ / ผู้เบิก) -->
            <div v-else>
              <div class="p-3 rounded-4 bg-light border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-person-plus-fill text-primary me-1"></i>
                    {{ editingDeptSigner ? 'แก้ไขหัวหน้ากลุ่มงาน' : 'เพิ่ม / แก้ไขหัวหน้ากลุ่มงาน (ผู้ขอใช้พัสดุ / ผู้เบิก)' }}
                  </h6>
                  <button
                    v-if="editingDeptSigner"
                    type="button"
                    class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0"
                    style="font-size: 0.75rem;"
                    @click="resetDeptSignerForm"
                  >
                    ยกเลิกการแก้ไข
                  </button>
                </div>
                <div class="row g-2 align-items-end">
                  <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">ชื่อกลุ่มงาน / แผนก *</label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      v-model="deptSignerForm.department_name"
                      :readonly="editingDeptSigner"
                      placeholder="เช่น กลุ่มงานสุขภาพดิจิทัล"
                    />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">ชื่อหัวหน้ากลุ่มงาน *</label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      v-model="deptSignerForm.requester_name"
                      placeholder="ระบุชื่อ-นามสกุล"
                    />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">ตำแหน่ง</label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      v-model="deptSignerForm.requester_position"
                      placeholder="ระบุตำแหน่ง"
                    />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">รูปลายเซ็น (PNG/JPG)</label>
                    <div class="d-flex gap-1">
                      <input
                        type="file"
                        accept="image/png,image/jpeg,image/jpg"
                        class="form-control form-control-sm"
                        @change="handleDeptSigUpload"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-success text-nowrap px-3 fw-bold"
                        @click="saveDeptSigner"
                        :disabled="savingDeptSigner"
                      >
                        <span v-if="savingDeptSigner" class="spinner-border spinner-border-sm"></span>
                        <span v-else>บันทึก</span>
                      </button>
                    </div>
                  </div>
                  <div v-if="deptSignerForm.signature_image" class="col-12 mt-2 d-flex align-items-center gap-2">
                    <span class="small text-muted">ตัวอย่างลายเซ็น:</span>
                    <img :src="deptSignerForm.signature_image" alt="Dept Sig" class="bg-white border rounded p-1" style="max-height: 40px;" />
                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" @click="deptSignerForm.signature_image = null">
                      ลบลายเซ็น
                    </button>
                  </div>
                </div>
              </div>

              <div class="table-responsive border rounded-3">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3">กลุ่มงาน / แผนก</th>
                      <th>ชื่อหัวหน้า (ผู้ขอใช้พัสดุ / ผู้เบิก)</th>
                      <th>ตำแหน่ง</th>
                      <th class="text-center">ลายเซ็น</th>
                      <th class="text-center pe-3" style="width: 130px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="deptSignersList.length === 0">
                      <td colspan="5" class="text-center py-4 text-muted">ยังไม่มีข้อมูลหัวหน้ากลุ่มงาน</td>
                    </tr>
                    <tr v-for="d in deptSignersList" :key="'ds-' + d.department_name">
                      <td class="ps-3 fw-semibold text-primary small">{{ d.department_name }}</td>
                      <td class="small fw-semibold">{{ d.requester_name || '-' }}</td>
                      <td class="small text-muted">{{ d.requester_position || '-' }}</td>
                      <td class="text-center">
                        <img v-if="d.signature_image" :src="d.signature_image" alt="Sig" style="max-height: 28px;" />
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center pe-3">
                        <div class="btn-group btn-group-sm">
                          <button type="button" class="btn btn-outline-primary" @click="editDeptSigner(d)" title="แก้ไข">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button type="button" class="btn btn-outline-danger" @click="deleteDeptSigner(d.department_name)" title="ลบ">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3">
            <button type="button" class="btn btn-light rounded-pill px-4" @click="showGlobalSigModal = false">ปิด</button>
            <button
              v-if="sigModalTab === 'global'"
              type="button"
              class="btn btn-primary rounded-pill px-4 fw-bold"
              @click="saveGlobalSigSettings"
              :disabled="savingGlobalSig"
            >
              <span v-if="savingGlobalSig" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-save me-1"></i> บันทึกการตั้งค่าส่วนกลาง
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export default {
  name: 'RequisitionManage',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      requisitions: [],
      loading: false,
      searchQuery: '',
      currentStatus: 'PENDING',
      searchTimeout: null,

      stats: {
        total_count: 0,
        pending_count: 0,
        approved_count: 0,
        rejected_count: 0
      },

      // Approval Modal
      showApproveModal: false,
      loadingReqDetails: false,
      submittingAction: false,
      recalculating: false,
      activeReq: null,
      activeReqItems: [],
      approverName: localStorage.getItem('user_name') || 'ผู้ดูแลระบบ',
      approvalNote: '',

      // PDF Preview Modal
      showPreviewModal: false,
      loadingPdf: false,
      pdfPreviewUrl: null,
      previewDocId: '',
      currentPdfDoc: null,
      currentPdfFileName: '',

      // Cached Fonts in Base64
      cachedFontNormal: null,
      cachedFontBold: null,

      // User permissions
      userProfile: null,
      isSystemAdmin: false,
      isInventoryAdmin: false,
      managedWarehouseIds: [],
      externalDepartments: [],

      // Global Signer & Signature Modal
      showGlobalSigModal: false,
      sigModalTab: 'global',
      loadingGlobalSig: false,
      savingGlobalSig: false,
      globalSigForm: {
        payer_name: '',
        payer_position: '',
        payer_signature: '',
        approver_name: '',
        approver_position: '',
        approver_signature: ''
      },
      deptSignersList: [],
      editingDeptSigner: false,
      savingDeptSigner: false,
      deptSignerForm: {
        department_name: '',
        requester_name: '',
        requester_position: '',
        signature_image: null
      }
    };
  },
  computed: {
    isApproveDisabled() {
      if (!this.activeReqItems || this.activeReqItems.length === 0) return true;
      let totalQty = 0;
      for (const item of this.activeReqItems) {
        if (item.suggested_qty > item.current_stock) return true;
        totalQty += (Number(item.suggested_qty) || 0);
      }
      return totalQty <= 0;
    }
  },
  watch: {
    warehouseId() {
      this.fetchRequisitions();
    }
  },
  mounted() {
    this.fetchCurrentUserPermissions();
    this.fetchRequisitions();
  },
  beforeUnmount() {
    if (this.pdfPreviewUrl) {
      URL.revokeObjectURL(this.pdfPreviewUrl);
    }
  },
  methods: {
    handleBackToDashboard() {
      if (this.$route?.path === '/inventory-requisition') {
        this.$router.push('/home-backoffice');
      } else {
        this.$emit('back-to-dashboard');
      }
    },
    handleGoToForm() {
      if (this.$route?.path === '/inventory-requisition') {
        const wh = this.$route?.query?.warehouse_id || this.warehouseId || localStorage.getItem('selected_warehouse_id') || '';
        this.$router.push({
          path: '/inventory-requisition-form',
          query: wh ? { warehouse_id: wh } : {}
        });
      } else {
        this.$emit('go-to-form');
      }
    },
    async fetchCurrentUserPermissions() {
      try {
        const extRes = await axios.get('/api-digital/inventory/external_departments.php?all=1').catch(() => null);
        if (extRes?.data?.status === 'success' && Array.isArray(extRes.data.data)) {
          this.externalDepartments = extRes.data.data;
        }
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data && res.data.status === 'success') {
          this.userProfile = res.data;
          if (this.userProfile.fullname) {
            this.approverName = this.userProfile.fullname;
          }
          const perms = this.userProfile.access_user ? String(this.userProfile.access_user).split(':') : [];
          this.isSystemAdmin = perms.includes('administrator');
          this.isInventoryAdmin = perms.some(p => ['administrator', 'menu_inventory_admin', 'menu_inventory_manage'].includes(p));

          const uid = this.userProfile.id || this.userProfile.user_id || localStorage.getItem('user_id') || '';
          if (uid && !this.isInventoryAdmin) {
            const wRes = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${uid}&is_admin=0`).catch(() => null);
            if (wRes?.data?.status === 'success' && Array.isArray(wRes.data.data)) {
              this.managedWarehouseIds = wRes.data.data.map(w => Number(w.id));
            }
          }
        }
      } catch (err) {
        console.error('Error loading permissions:', err);
      }
    },
    normalizePersonName(name) {
      return String(name || '')
        .replace(/^(ว่าที่\s*(?:ร้อยตรี|ร้อยโท|ร้อยเอก|ร\.ต\.|ร\.ท\.|ร\.อ\.)|นาย|นางสาว|นาง|น\.ส\.|ดร\.|พญ\.|นพ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.)\s*/u, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
    },
    canApproveRequisition(req) {
      if (this.isInventoryAdmin) return true;
      const whId = Number(req?.warehouse_id || this.$route?.query?.warehouse_id || this.warehouseId || 0);
      return whId > 0 && this.managedWarehouseIds.includes(whId);
    },
    isOwnRequisition(req) {
      const myUid = Number(this.userProfile?.id || this.userProfile?.user_id || localStorage.getItem('user_id') || 0);
      const ownerUid = Number(req?.created_by_user_id || 0);
      if (myUid > 0 && ownerUid > 0) {
        return myUid === ownerUid;
      }
      const myName = this.userProfile?.fullname || localStorage.getItem('user_name') || '';
      if (!myName || !req?.requester_name) return false;
      const n1 = this.normalizePersonName(myName);
      const n2 = this.normalizePersonName(req.requester_name);
      return n1 !== '' && n1 === n2;
    },
    canEditRequisition(req) {
      if (!req || req.status !== 'PENDING') return false;
      return this.canApproveRequisition(req) || this.isOwnRequisition(req);
    },
    canCancelRequisition(req) {
      if (!req || req.status === 'APPROVED') return false;
      if (this.canApproveRequisition(req)) return true;
      return req.status === 'PENDING' && this.isOwnRequisition(req);
    },
    editRequisition(req) {
      this.$router.push({
        path: '/inventory-requisition-form',
        query: {
          edit_id: req.id,
          warehouse_id: req.warehouse_id
        }
      });
    },
    async fetchRequisitions() {
      this.loading = true;
      try {
        const queryWh = this.$route?.query?.warehouse_id;
        const wh = queryWh || this.warehouseId || localStorage.getItem('selected_warehouse_id') || '';
        const params = new URLSearchParams();
        if (wh) params.append('warehouse_id', wh);
        if (this.currentStatus) params.append('status', this.currentStatus);
        if (this.searchQuery) params.append('search', this.searchQuery);

        const res = await axios.get(`/api-digital/inventory/requisition_list.php?${params.toString()}`);
        if (res.data.status === 'success') {
          this.requisitions = res.data.data || [];
          if (res.data.stats) {
            this.stats = res.data.stats;
          }
          this.$emit('update-pending-count', this.stats.pending_count || 0);
        }
      } catch (err) {
        console.error('Fetch requisitions error:', err);
      } finally {
        this.loading = false;
      }
    },
    setStatusFilter(st) {
      this.currentStatus = st;
      this.fetchRequisitions();
    },
    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchRequisitions();
      }, 350);
    },
    formatDate(d) {
      if (!d) return '-';
      return moment(d).format('DD/MM/YYYY HH:mm');
    },
    formatDateThai(d) {
      if (!d) return '-';
      const m = moment(d);
      const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
      return `${m.date()} ${months[m.month()]} ${m.year() + 543}`;
    },
    formatDocId(req) {
      if (!req) return '';
      if (req.formatted_id) return req.formatted_id;
      const y = moment(req.request_date || new Date()).year() + 543;
      return `${y}${String(req.id || 0).padStart(4, '0')}`;
    },
    formatNumber(val) {
      if (val === null || val === undefined || val === '') return '-';
      const num = Number(val);
      if (isNaN(num)) return val;
      return Number.isInteger(num) ? num.toString() : num.toFixed(2);
    },
    formatCurrency(val) {
      const num = Number(val || 0);
      if (isNaN(num)) return '0.00';
      return num.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    async openApproveModal(req) {
      this.activeReq = req;
      this.activeReqItems = [];
      this.approvalNote = '';
      this.showApproveModal = true;
      this.loadingReqDetails = true;

      try {
        const res = await axios.get(`/api-digital/inventory/requisition_get.php?id=${req.id}`);
        if (res.data.status === 'success') {
          this.activeReq = res.data.data;
          this.activeReqItems = res.data.data.items || [];
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดรายการพัสดุได้', 'error');
      } finally {
        this.loadingReqDetails = false;
      }
    },
    getSelectedLotBalance(item) {
      if (!item || !item.selected_lot_id || !item.available_lots) return 0;
      const found = item.available_lots.find((l) => String(l.lot_id) === String(item.selected_lot_id));
      return found ? Number(found.balance_qty) || 0 : 0;
    },
    async approveRequest() {
      if (this.isApproveDisabled) return;

      const confirm = await Swal.fire({
        title: 'ยืนยันการอนุมัติและตัดจ่ายสต๊อก?',
        html: `ต้องการอนุมัติคำขอเลขที่ <strong class="text-primary">${this.activeReq.req_no}</strong> และตัดยอดพัสดุออกจากคลังทันทีใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> ยืนยันอนุมัติ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!confirm.isConfirmed) return;

      this.submittingAction = true;
      try {
        const payload = {
          requisition_id: this.activeReq.id,
          approver_name: this.approverName,
          admin_note: this.approvalNote,
          items: this.activeReqItems.map(it => ({
            id: it.id,
            item_id: it.item_id,
            lot_id: it.selected_lot_id,
            qty_approved: Number(it.suggested_qty) || 0
          }))
        };

        const res = await axios.post('/api-digital/inventory/requisition_approve.php', payload);
        if (res.data.status === 'success') {
          this.showApproveModal = false;
          await Swal.fire({
            icon: 'success',
            title: 'อนุมัติและตัดสต๊อกสำเร็จ!',
            text: `ระบบได้สร้างเอกสารใบเบิกเลขที่ ${res.data.issue_no} และตัดสต๊อกเรียบร้อยแล้ว`,
            timer: 2000,
            showConfirmButton: false
          });
          this.fetchRequisitions();
        } else {
          Swal.fire('ไม่สามารถอนุมัติได้', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง', 'error');
      } finally {
        this.submittingAction = false;
      }
    },
    async rejectRequest() {
      const { value: reason } = await Swal.fire({
        title: 'ระบุเหตุผลที่ไม่อนุมัติ',
        input: 'textarea',
        inputPlaceholder: 'เช่น สินค้าในคลังหมด หรือจำนวนที่ขอเกินโควตาประจำเดือน...',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ยืนยันปฏิเสธคำขอ',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
          if (!value) {
            return 'กรุณาระบุเหตุผลในการไม่อนุมัติ';
          }
        }
      });

      if (!reason) return;

      this.submittingAction = true;
      try {
        const res = await axios.post('/api-digital/inventory/requisition_reject.php', {
          requisition_id: this.activeReq.id,
          approver_name: this.approverName,
          reject_reason: reason
        });
        if (res.data.status === 'success') {
          this.showApproveModal = false;
          Swal.fire('ปฏิเสธคำขอแล้ว', res.data.message, 'info');
          this.fetchRequisitions();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
      } finally {
        this.submittingAction = false;
      }
    },
    async recalculateBalances() {
      const whId = Number(this.warehouseId || this.$route?.query?.warehouse_id || localStorage.getItem('selected_warehouse_id') || 0);
      const cf = await Swal.fire({
        title: 'คำนวณยอดคงเหลือใหม่?',
        text: 'ระบบจะซิงค์ยอดคงเหลือคลังจากยอด Lot จริง และคำนวณยอดคงเหลือในบัญชีคุม (Stock Card / PDF) ตามลำดับเวลาใหม่ทั้งหมด',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'คำนวณใหม่ทันที',
        cancelButtonText: 'ยกเลิก'
      });
      if (!cf.isConfirmed) return;

      this.recalculating = true;
      try {
        const res = await axios.post('/api-digital/inventory/recalculate_balances.php', {
          warehouse_id: whId
        });
        if (res.data?.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'คำนวณยอดใหม่สำเร็จ',
            text: res.data.message,
            timer: 2200,
            showConfirmButton: false
          });
          this.fetchRequisitions();
        } else {
          Swal.fire('ไม่สำเร็จ', res.data?.message || 'ไม่สามารถคำนวณยอดใหม่ได้', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err?.response?.data?.message || 'ไม่สามารถดำเนินการได้', 'error');
      } finally {
        this.recalculating = false;
      }
    },
    async confirmCancelApproved(req) {
      const { value: reason, isConfirmed } = await Swal.fire({
        title: `ยกเลิกการจ่าย ${req.req_no}?`,
        text: 'ระบบจะคืนยอดพัสดุกลับเข้าสต๊อกเดิมและยกเลิกใบตัดจ่ายที่เกี่ยวข้อง',
        input: 'text',
        inputPlaceholder: 'ระบุเหตุผลในการยกเลิกการจ่าย (ถ้ามี)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันยกเลิกการจ่าย (คืนสต๊อก)',
        cancelButtonText: 'ปิด'
      });

      if (!isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/requisition_cancel_approved.php', {
          requisition_id: req.id,
          cancel_reason: reason || '',
          actor_name: this.approverName || this.userProfile?.fullname || ''
        });
        if (res.data?.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'ยกเลิกการจ่ายสำเร็จ',
            text: res.data.message || 'คืนยอดสต๊อกเรียบร้อยแล้ว',
            timer: 1800,
            showConfirmButton: false
          });
          this.fetchRequisitions();
        } else {
          Swal.fire('ไม่สามารถยกเลิกได้', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err?.response?.data?.message || 'ไม่สามารถดำเนินการได้', 'error');
      }
    },
    async confirmDelete(req) {
      const isManager = this.canApproveRequisition(req);
      const confirm = await Swal.fire({
        title: isManager ? 'ยืนยันการลบใบขอเบิก?' : 'ยืนยันการยกเลิกใบขอเบิก?',
        text: isManager
          ? `คุณแน่ใจหรือไม่ว่าต้องการลบใบขอเบิกเลขที่ ${req.req_no}`
          : `คุณแน่ใจหรือไม่ว่าต้องการยกเลิกใบขอเบิกเลขที่ ${req.req_no}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: isManager ? 'ยืนยันลบ' : 'ยืนยันยกเลิกคำขอ',
        cancelButtonText: 'ปิด'
      });

      if (!confirm.isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/requisition_delete.php', {
          requisition_id: req.id
        });
        if (res.data.status === 'success') {
          Swal.fire('ลบสำเร็จ', res.data.message, 'success');
          this.fetchRequisitions();
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
      }
    },

    // ==========================================
    // PDF Generation & Preview Engine (jsPDF + THSarabun)
    // ==========================================
    async fetchFont(url) {
      return fetch(url)
        .then((res) => {
          if (!res.ok) throw new Error(`Font fetch error from ${url}`);
          return res.arrayBuffer();
        })
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
    async ensureFontsLoaded() {
      if (this.cachedFontNormal && this.cachedFontBold) return;
      const baseUrl = import.meta.env.BASE_URL.endsWith('/')
        ? import.meta.env.BASE_URL
        : import.meta.env.BASE_URL + '/';
      const [normal, bold] = await Promise.all([
        this.fetchFont(`${baseUrl}fonts/THSarabun.ttf`),
        this.fetchFont(`${baseUrl}fonts/THSarabun Bold.ttf`)
      ]);
      this.cachedFontNormal = normal;
      this.cachedFontBold = bold;
    },
    async generatePdfDoc(requestData) {
      await this.ensureFontsLoaded();

      const doc = new jsPDF();
      doc.addFileToVFS('THSarabun.ttf', this.cachedFontNormal);
      doc.addFont('THSarabun.ttf', 'Sarabun', 'normal');
      doc.addFileToVFS('THSarabunBold.ttf', this.cachedFontBold);
      doc.addFont('THSarabunBold.ttf', 'Sarabun', 'bold');

      const isComputer = (Number(requestData.warehouse_id) === 1);
      const title = isComputer ? 'ใบเบิกวัสดุคอมพิวเตอร์' : 'ใบเบิกวัสดุสำนักงาน';
      const subject = isComputer ? 'ขอเบิกวัสดุคอมพิวเตอร์' : 'ขอเบิกวัสดุสำนักงาน';
      const recipient = isComputer ? 'หัวหน้ากลุ่มงานเทคโนโลยีสารสนเทศ' : 'เจ้าหน้าที่พัสดุ';
      const subText = isComputer
        ? 'ขอเบิกวัสดุคอมพิวเตอร์เพื่อใช้ในหน่วยงาน ดังต่อไปนี้'
        : 'ขอเบิกวัสดุสำนักงานเพื่อใช้ในหน่วยงาน ดังต่อไปนี้';

      // 1. Title
      doc.setFont('Sarabun', 'bold');
      doc.setFontSize(22);
      doc.text(title, 105, 20, { align: 'center' });

      // 2. Doc Number & Date
      doc.setFontSize(16);
      const docId = this.formatDocId(requestData);
      doc.text(`ใบเบิกที่: ${docId}`, 20, 35);

      const dDate = new Date(requestData.request_date || new Date());
      const months = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
      ];
      doc.text(
        `วันที่ ${dDate.getDate()} เดือน ${months[dDate.getMonth()]} พ.ศ. ${dDate.getFullYear() + 543}`,
        190,
        35,
        { align: 'right' }
      );

      // 3. Subject & Recipient
      doc.text('เรื่อง', 20, 45);
      doc.setFont('Sarabun', 'normal');
      doc.text(subject, 35, 45);

      doc.setFont('Sarabun', 'bold');
      doc.text('เรียน', 20, 53);
      doc.setFont('Sarabun', 'normal');
      doc.text(recipient, 35, 53);

      // 4. Requester info with dotted lines matching hospital standard layout
      doc.text(
        `ข้าพเจ้า  .........................................................................  ตำแหน่ง  ...................................................................................`,
        30,
        65
      );
      doc.text(requestData.requester_name || '', 44, 64);
      doc.text(requestData.requester_position || '', 128, 64);

      doc.text(
        `กลุ่มงาน  .....................................................................................................`,
        30,
        75
      );
      const printDept = requestData.dept_signer?.department_name || requestData.department_name || '';
      doc.text(printDept, 45, 74);

      doc.text(subText, 30, 85);

      // 5. Items Table (6 columns)
      const items = requestData.items || [];
      const tableBody = items.map((item, index) => [
        (index + 1).toString(),
        item.item_name || '',
        item.unit_name || 'ชิ้น',
        this.formatNumber(item.qty_requested),
        item.status === 'APPROVED'
          ? this.formatNumber(item.qty_approved || item.qty_requested)
          : (item.status === 'REJECTED' ? 'ไม่อนุมัติ' : this.formatNumber(item.qty_approved || item.qty_requested || '-')),
        this.formatNumber(
          item.balance_after_issue !== null && item.balance_after_issue !== undefined
            ? item.balance_after_issue
            : item.current_stock
        )
      ]);

      autoTable(doc, {
        startY: 92,
        head: [['ลำดับ\nที่', 'รายการ', 'หน่วย\nนับ', 'จำนวน\nเบิก', 'อนุมัติ', 'คงเหลือ\nในบัญชีคุม']],
        body: tableBody,
        styles: {
          font: 'Sarabun',
          fontStyle: 'normal',
          fontSize: 14,
          cellPadding: { top: 1.5, right: 2, bottom: 1.5, left: 2 },
          halign: 'center',
          valign: 'middle',
          lineColor: [0, 0, 0],
          lineWidth: 0.1
        },
        headStyles: {
          fillColor: [255, 255, 255],
          textColor: [0, 0, 0],
          font: 'Sarabun',
          fontStyle: 'bold',
          halign: 'center',
          valign: 'middle'
        },
        columnStyles: {
          0: { cellWidth: 15 },
          1: { halign: 'left', cellWidth: 'auto' },
          2: { cellWidth: 18 },
          3: { cellWidth: 18 },
          4: { cellWidth: 18 },
          5: { cellWidth: 26 }
        },
        theme: 'grid',
        margin: { left: 20, right: 20 }
      });

      // 6. 5 Signers Layout
      let finalY = (doc.lastAutoTable || doc.previousAutoTable).finalY + 10;

      const deptSigner = requestData.dept_signer || {};
      const globalSettings = requestData.global_settings || {};
      const deptNameNorm = String(requestData.department_name || '').replace(/\s+/g, '').trim();
      const matchedExt = !requestData.department_id && deptNameNorm
        ? this.externalDepartments.find(
            e => String(e.name || '').replace(/\s+/g, '').trim() === deptNameNorm
          )
        : null;

      const reqName =
        (matchedExt && matchedExt.head_name) ||
        deptSigner.requester_name ||
        requestData.requester_name ||
        '...........................................';
      const reqPos =
        (matchedExt && matchedExt.head_position) ||
        deptSigner.requester_position ||
        requestData.requester_position ||
        '.....................................................';

      const payerName =
        globalSettings.payer_name || 'นางภรภัค อรอินทร์';
      const payerPos =
        globalSettings.payer_position || 'เจ้าพนักงานพัสดุ';

      const appName =
        globalSettings.approver_name || 'นางสาวศิริลักษณ์ พรหมสาขา ณ สกลนคร';
      const appPos =
        globalSettings.approver_position || 'นักวิชาการพัสดุ';

      if (finalY + 56 > 286) {
        doc.addPage();
        finalY = 24;
      }

      // Row 1: ผู้ขอใช้พัสดุ (Left), ผู้จ่าย (Right)
      doc.text('ผู้ขอใช้พัสดุ...........................................ลงชื่อ', 60, finalY, {
        align: 'center'
      });
      doc.text(`( ${reqName} )`, 60, finalY + 6, { align: 'center' });
      doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 11.5, { align: 'center' });

      doc.text('ผู้จ่าย...........................................ลงชื่อ', 150, finalY, {
        align: 'center'
      });
      doc.text(`( ${payerName} )`, 150, finalY + 6, { align: 'center' });
      doc.text(`ตำแหน่ง ${payerPos}`, 150, finalY + 11.5, { align: 'center' });

      // Row 2: ผู้เบิก (Left), ผู้สั่งจ่าย (Right)
      doc.text('ผู้เบิก...........................................ลงชื่อ', 60, finalY + 21, {
        align: 'center'
      });
      doc.text(`( ${reqName} )`, 60, finalY + 27, { align: 'center' });
      doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 32.5, { align: 'center' });

      doc.text('ผู้สั่งจ่าย...........................................ลงชื่อ', 150, finalY + 21, {
        align: 'center'
      });
      doc.text(`( ${appName} )`, 150, finalY + 27, { align: 'center' });
      doc.text(`ตำแหน่ง ${appPos}`, 150, finalY + 32.5, { align: 'center' });

      // Row 3: ผู้รับพัสดุ (Left)
      doc.text('ผู้รับพัสดุ...........................................ลงชื่อ', 60, finalY + 42, {
        align: 'center'
      });
      doc.text(`( ${requestData.requester_name || '...........................................'} )`, 60, finalY + 48, { align: 'center' });
      doc.text(
        `ตำแหน่ง ${requestData.requester_position || '.....................................................'}`,
        60,
        finalY + 53.5,
        { align: 'center' }
      );

      // Digital Signature Overlay for ผู้ขอใช้พัสดุ and ผู้เบิก
      if (deptSigner && deptSigner.signature_image) {
        try {
          const sigFormat =
            deptSigner.signature_image.includes('image/jpeg') ||
            deptSigner.signature_image.includes('image/jpg')
              ? 'JPEG'
              : 'PNG';
          doc.addImage(deptSigner.signature_image, sigFormat, 47, finalY - 7, 26, 9);
          doc.addImage(deptSigner.signature_image, sigFormat, 47, finalY + 21 - 7, 26, 9);
        } catch (sigErr) {
          console.warn('Could not add signature image to PDF:', sigErr);
        }
      }

      // Digital Signature Overlay for ผู้จ่าย (Row 1 Right)
      if (globalSettings && globalSettings.payer_signature) {
        try {
          const payerSigFmt =
            globalSettings.payer_signature.includes('image/jpeg') ||
            globalSettings.payer_signature.includes('image/jpg')
              ? 'JPEG'
              : 'PNG';
          doc.addImage(globalSettings.payer_signature, payerSigFmt, 137, finalY - 7, 26, 9);
        } catch (sigErr) {
          console.warn('Could not add payer signature to PDF:', sigErr);
        }
      }

      // Digital Signature Overlay for ผู้สั่งจ่าย (Row 2 Right)
      if (globalSettings && globalSettings.approver_signature) {
        try {
          const appSigFmt =
            globalSettings.approver_signature.includes('image/jpeg') ||
            globalSettings.approver_signature.includes('image/jpg')
              ? 'JPEG'
              : 'PNG';
          doc.addImage(globalSettings.approver_signature, appSigFmt, 137, finalY + 21 - 7, 26, 9);
        } catch (sigErr) {
          console.warn('Could not add approver signature to PDF:', sigErr);
        }
      }

      return doc;
    },
    async openGlobalSigModal() {
      this.showGlobalSigModal = true;
      this.loadingGlobalSig = true;
      try {
        const res = await axios.get('/api-digital/admin_material/admin_get_print_settings.php');
        if (res.data?.success) {
          if (res.data.global) {
            this.globalSigForm = {
              payer_name: res.data.global.payer_name || 'นางภรภัค อรอินทร์',
              payer_position: res.data.global.payer_position || 'เจ้าพนักงานพัสดุ',
              payer_signature: res.data.global.payer_signature || '',
              approver_name: res.data.global.approver_name || 'นางสาวศิริลักษณ์ พรหมสาขา ณ สกลนคร',
              approver_position: res.data.global.approver_position || 'นักวิชาการพัสดุ',
              approver_signature: res.data.global.approver_signature || ''
            };
          }
          this.deptSignersList = res.data.departments || [];
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.loadingGlobalSig = false;
      }
    },
    resetDeptSignerForm() {
      this.editingDeptSigner = false;
      this.deptSignerForm = {
        department_name: '',
        requester_name: '',
        requester_position: '',
        signature_image: null
      };
    },
    editDeptSigner(d) {
      this.editingDeptSigner = true;
      this.deptSignerForm = {
        department_name: d.department_name,
        requester_name: d.requester_name || '',
        requester_position: d.requester_position || '',
        signature_image: d.signature_image || null
      };
    },
    handleDeptSigUpload(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!file.type.match(/image\/(png|jpeg|jpg)/)) {
        Swal.fire('ข้อผิดพลาด', 'กรุณาอัปโหลดไฟล์รูปภาพ (PNG หรือ JPG) เท่านั้น', 'warning');
        return;
      }
      if (file.size > 3 * 1024 * 1024) {
        Swal.fire('ข้อผิดพลาด', 'ขนาดไฟล์รูปภาพต้องไม่เกิน 3MB', 'warning');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.deptSignerForm.signature_image = ev.target.result;
      };
      reader.readAsDataURL(file);
      e.target.value = '';
    },
    async saveDeptSigner() {
      if (!this.deptSignerForm.department_name.trim() || !this.deptSignerForm.requester_name.trim()) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาระบุชื่อกลุ่มงานและชื่อหัวหน้ากลุ่มงาน', 'warning');
        return;
      }
      this.savingDeptSigner = true;
      try {
        const res = await axios.post('/api-digital/admin_material/admin_save_dept_signer.php', this.deptSignerForm);
        if (res.data?.success) {
          Swal.fire({ icon: 'success', title: 'บันทึกหัวหน้ากลุ่มงานเรียบร้อย', timer: 1300, showConfirmButton: false });
          this.resetDeptSignerForm();
          await this.openGlobalSigModal();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.savingDeptSigner = false;
      }
    },
    async deleteDeptSigner(deptName) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `ต้องการลบข้อมูลผู้ลงนาม "${deptName}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบ'
      });
      if (!confirm.isConfirmed) return;
      try {
        const res = await axios.post('/api-digital/admin_material/admin_delete_dept_signer.php', {
          department_name: deptName
        });
        if (res.data?.success) {
          await this.openGlobalSigModal();
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบ', 'error');
      }
    },
    handleGlobalSigUpload(e, field) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (!file.type.match(/image\/(png|jpeg|jpg)/)) {
        Swal.fire('ข้อผิดพลาด', 'กรุณาอัปโหลดไฟล์รูปภาพ (PNG หรือ JPG) เท่านั้น', 'warning');
        return;
      }
      if (file.size > 3 * 1024 * 1024) {
        Swal.fire('ข้อผิดพลาด', 'ขนาดไฟล์รูปภาพต้องไม่เกิน 3MB', 'warning');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        this.globalSigForm[field] = ev.target.result;
      };
      reader.readAsDataURL(file);
      e.target.value = '';
    },
    async saveGlobalSigSettings() {
      this.savingGlobalSig = true;
      try {
        const res = await axios.post('/api-digital/admin_material/admin_save_global_settings.php', {
          settings: this.globalSigForm
        });
        if (res.data?.success) {
          this.showGlobalSigModal = false;
          Swal.fire({
            icon: 'success',
            title: 'บันทึกลายเซ็นเรียบร้อย',
            timer: 1400,
            showConfirmButton: false
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.savingGlobalSig = false;
      }
    },
    async openPdfPreview(req) {
      if (!req || req.status !== 'APPROVED') {
        Swal.fire('ไม่สามารถพิมพ์ PDF ได้', 'สามารถพิมพ์ PDF ได้เฉพาะใบขอเบิกที่อนุมัติจ่ายแล้วเท่านั้น', 'info');
        return;
      }
      this.loadingPdf = true;
      this.showPreviewModal = true;
      this.previewDocId = req.req_no || '';

      if (this.pdfPreviewUrl) {
        URL.revokeObjectURL(this.pdfPreviewUrl);
        this.pdfPreviewUrl = null;
      }
      this.currentPdfDoc = null;

      try {
        const res = await axios.get(`/api-digital/inventory/requisition_get.php?id=${req.id}`);
        if (!res.data || res.data.status !== 'success') {
          throw new Error(res.data?.message || 'ไม่สามารถโหลดข้อมูลใบขอเบิกได้');
        }

        const requestData = res.data.data;
        this.previewDocId = this.formatDocId(requestData);

        const doc = await this.generatePdfDoc(requestData);
        this.currentPdfDoc = doc;
        this.currentPdfFileName = `ใบเบิกพัสดุ_${this.previewDocId}.pdf`;

        const blob = doc.output('blob');
        this.pdfPreviewUrl = URL.createObjectURL(blob) + '#toolbar=1&view=Fit';
      } catch (err) {
        console.error('PDF generation error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการสร้างตัวอย่างเอกสาร PDF', 'error');
        this.showPreviewModal = false;
      } finally {
        this.loadingPdf = false;
      }
    },
    downloadPdf() {
      if (this.currentPdfDoc) {
        this.currentPdfDoc.save(this.currentPdfFileName || 'ใบเบิกพัสดุ.pdf');
      }
    },
    openInNewTab() {
      if (this.pdfPreviewUrl) {
        window.open(this.pdfPreviewUrl, '_blank');
      }
    },
    closePreviewModal() {
      this.showPreviewModal = false;
      if (this.pdfPreviewUrl) {
        URL.revokeObjectURL(this.pdfPreviewUrl);
        this.pdfPreviewUrl = null;
      }
      this.currentPdfDoc = null;
    }
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.stat-mini {
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-mini:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.active-card {
  border: 2px solid #ffc107 !important;
}
.icon-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.spin-anim {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  100% { transform: rotate(360deg); }
}
.nav-pill-btn {
  transition: all 0.2s ease;
}
.nav-pill-btn:hover {
  background-color: #e2e8f0 !important;
  color: #0f172a !important;
  transform: translateY(-1px);
}
.action-pill-btn {
  height: 30px;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}
.action-pill-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08) !important;
}
</style>

