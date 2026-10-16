<template>
  <div class="row g-4 mt-3 fade-in">
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4"
    >
      <div class="mb-3 mb-md-0">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
              <router-link to="/material-it" class="text-decoration-none"
                >หน้าหลักวัสดุคอม</router-link
              >
            </li>
            <li class="breadcrumb-item active" aria-current="page">รายการขอเบิกวัสดุคอมพิวเตอร์</li>
          </ol>
        </nav>
        <h4 class="fw-bold mb-0 text-gradient-primary">รายการขอเบิกวัสดุคอมพิวเตอร์ (IT Material Requests)</h4>
      </div>
      <div class="d-flex gap-2">
        <select v-model="statusFilter" class="form-select w-auto rounded-pill shadow-sm border-0 bg-light fw-medium px-4" @change="fetchRequests">
          <option value="all">ทั้งหมด (All)</option>
          <option value="pending">รอดำเนินการ (Pending)</option>
          <option value="approved">อนุมัติแล้ว (Approved)</option>
          <option value="rejected">ปฏิเสธ (Rejected)</option>
        </select>
        <router-link
          to="/material-it/settings"
          class="btn btn-primary rounded-pill shadow-sm hover-lift fw-bold px-4"
          title="ตั้งค่าการพิมพ์"
        >
          <i class="bi bi-gear-fill me-1"></i> ตั้งค่าพิมพ์ใบเบิก
        </router-link>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="col-12">
      <div class="card border-0 shadow-lg rounded-4 glass-card overflow-hidden">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="custom-table mb-0">
              <thead class="bg-primary bg-opacity-10">
                <tr>
                  <th class="ps-4 fw-semibold text-primary">
                    <i class="bi bi-calendar-date me-1"></i> วันที่ขอเบิก
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-person me-1"></i> ผู้ขอเบิก
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-building me-1"></i> หน่วยงาน
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-box-seam me-1"></i> รายการวัสดุ
                  </th>
                  <th class="text-center fw-semibold text-primary">
                    <i class="bi bi-123 me-1"></i> จำนวน
                  </th>
                  <th class="text-center fw-semibold text-primary">
                    <i class="bi bi-info-circle me-1"></i> สถานะ
                  </th>
                  <th class="fw-semibold text-primary">
                    <i class="bi bi-chat-text me-1"></i> หมายเหตุ
                  </th>
                  <th class="text-end pe-4 fw-semibold text-primary">
                    <i class="bi bi-wrench me-1"></i> จัดการ
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="requests.length === 0">
                  <td colspan="8" class="text-center py-5">
                    <div class="empty-state-wrapper fade-in">
                      <div class="icon-square bg-light text-muted mb-3" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-inbox fs-1 opacity-50"></i>
                      </div>
                      <h5 class="fw-bold text-dark mb-1">ไม่มีข้อมูลคำขอเบิก</h5>
                      <p class="text-muted small mb-0">
                        ยังไม่มีรายการเบิกวัสดุในสถานะที่คุณเลือก
                      </p>
                    </div>
                  </td>
                </tr>
                <tr v-for="req in requests" :key="req.group_id || req.request_no">
                  <td class="ps-4">{{ req.request_date }}</td>
                  <td>{{ req.requester_name }}</td>
                  <td>{{ req.department }}</td>
                  <td>
                    <div v-for="item in req.items" :key="item.id" class="mb-1 pb-1 border-bottom border-light">
                      <div class="d-flex align-items-center flex-wrap gap-1">
                        <span class="fw-bold">{{ item.material_name }}</span>
                        <template v-if="req.status === 'pending'">
                          <span v-if="getMaterialBalance(item.material_id) <= 0" class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0" style="font-size: 0.72rem;">
                            <i class="bi bi-x-circle me-1"></i>ของหมด (0)
                          </span>
                          <span v-else-if="getMaterialBalance(item.material_id) < item.quantity" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0" style="font-size: 0.72rem;">
                            <i class="bi bi-exclamation-triangle me-1"></i>เหลือ {{ getMaterialBalance(item.material_id) }} {{ getMaterialUnit(item.material_id) }}
                          </span>
                          <span v-else class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size: 0.72rem;">
                            <i class="bi bi-check2 me-1"></i>เหลือ {{ getMaterialBalance(item.material_id) }} {{ getMaterialUnit(item.material_id) }}
                          </span>
                        </template>
                      </div>
                      <small class="text-muted">{{ item.material_code }}</small>
                    </div>
                  </td>
                  <td class="text-center fw-bold text-dark fs-6">
                    <div v-for="item in req.items" :key="item.id" class="mb-1 pb-1 border-bottom border-light" style="min-height: 42px; display: flex; align-items: center; justify-content: center;">
                      <span>{{ item.quantity }}</span>
                      <span class="text-muted small fw-normal ms-1">{{ getMaterialUnit(item.material_id) }}</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span
                      class="badge-status"
                      :class="getStatusBadge(req.status)"
                      style="font-size: 0.85rem"
                    >
                      <i :class="getStatusIcon(req.status)" class="me-1"></i>
                      {{ getStatusText(req.status) }}
                    </span>
                  </td>
                  <td>{{ req.admin_note || '-' }}</td>
                  <td class="text-end pe-4">
                    <div class="d-flex gap-2 justify-content-end align-items-center h-100 flex-wrap" style="max-width: 150px">
                      <template v-if="req.status === 'pending'">
                        <div v-if="isRequestOutOfStock(req)" class="w-100 text-center mb-1">
                          <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2 w-100" style="font-size: 0.72rem;">
                            <i class="bi bi-exclamation-octagon me-1"></i>ของในคลังหมด
                          </span>
                        </div>
                        <button
                          class="btn btn-sm btn-primary flex-fill fw-bold shadow-sm"
                          @click="openReviewModal(req)"
                          title="ตรวจสอบและพิจารณาอนุมัติ"
                        >
                          <i class="bi bi-search"></i> พิจารณาอนุมัติ
                        </button>
                      </template>
                      <template v-else-if="req.status === 'approved'">
                        <button
                          class="btn btn-sm btn-danger text-white flex-fill"
                          @click="exportPDF(req)"
                          title="Export PDF"
                        >
                          <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
                        </button>
                        <button
                          class="btn btn-sm btn-outline-danger flex-fill"
                          @click="deleteRequest(req)"
                          title="ลบข้อมูล"
                        >
                          <i class="bi bi-trash"></i> ลบ
                        </button>
                      </template>
                      <template v-else-if="req.status === 'rejected'">
                        <button
                          class="btn btn-sm btn-outline-danger flex-fill"
                          @click="deleteRequest(req)"
                          title="ลบข้อมูล"
                        >
                          <i class="bi bi-trash"></i> ลบ
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Request Modal -->
    <teleport to="body">
    <div class="modal fade" id="reviewRequestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-primary bg-opacity-10 border-bottom-0 pb-0">
            <h5 class="modal-title text-primary-emphasis fw-bold">
              <i class="bi bi-search me-2"></i>รายละเอียดคำขอเบิกวัสดุคอมพิวเตอร์
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 pt-3" v-if="reviewData">
            <div class="card border border-light shadow-sm mb-4">
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <span class="text-muted small">ชื่อ-สกุลผู้เบิก:</span>
                    <div class="fw-bold fs-6">{{ reviewData.requester_name }}</div>
                  </div>
                  <div class="col-md-6">
                    <span class="text-muted small">หน่วยงาน:</span>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <select v-if="isEditingDepartment" v-model="editDepartmentVal" class="form-select form-select-sm">
                        <option v-for="dept in pastDepartments" :key="dept" :value="dept">{{ dept }}</option>
                      </select>
                      <div v-else class="fw-bold fs-6">{{ reviewData.department }}</div>
                      <button v-if="!isEditingDepartment" type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill" @click="startEditDepartment" title="เปลี่ยนหน่วยงาน">
                        <i class="bi bi-pencil-fill small me-1"></i>แก้ไข
                      </button>
                      <button v-if="isEditingDepartment" type="button" class="btn btn-sm btn-success py-0 px-2 rounded-pill" @click="saveEditDepartment">
                        บันทึก
                      </button>
                      <button v-if="isEditingDepartment" type="button" class="btn btn-sm btn-secondary py-0 px-2 rounded-pill" @click="isEditingDepartment = false">
                        ยกเลิก
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <span class="text-muted small">วันที่ขอเบิก:</span>
                    <div class="fw-bold fs-6">{{ reviewData.request_date }}</div>
                  </div>
                  <div class="col-md-6">
                    <span class="text-muted small">สถานะ:</span>
                    <div>
                      <span class="badge bg-warning text-dark fs-6 px-3 rounded-pill shadow-sm">
                        <i class="bi bi-hourglass-split"></i> รออนุมัติ
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-1"></i> รายการวัสดุที่ขอเบิก</h6>
            <div class="table-responsive border rounded bg-white shadow-sm">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-center">
                  <tr>
                    <th width="5%">ลำดับ</th>
                    <th width="35%" class="text-start">รายการวัสดุ</th>
                    <th width="15%">ขอเบิก</th>
                    <th width="22%">จำนวนจ่าย</th>
                    <th width="23%">คงเหลือในคลัง</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in reviewData.items" :key="index">
                    <td class="text-center text-muted">{{ index + 1 }}</td>
                    <td>
                      <div class="fw-bold">{{ item.material_name }}</div>
                      <div class="small text-muted">{{ item.material_code }}</div>
                    </td>
                    <td class="text-center text-primary fs-5 fw-bold">
                      {{ item.quantity }}
                      <span class="fs-6 text-muted fw-normal ms-1">{{ getMaterialUnit(item.material_id) }}</span>
                    </td>
                    <td class="text-center">
                      <div v-if="getMaterialBalance(item.material_id) <= 0">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-2 fw-bold d-inline-block">
                          <i class="bi bi-slash-circle me-1"></i> ไม่สามารถจ่ายได้
                        </span>
                        <div class="text-muted small mt-1">(ของหมดในคลัง)</div>
                      </div>
                      <div v-else>
                        <div class="input-group input-group-sm w-100 mx-auto" style="max-width: 130px;">
                          <button 
                            type="button" 
                            class="btn btn-outline-secondary px-2" 
                            :disabled="item.dispense_qty <= 0"
                            @click="item.dispense_qty > 0 ? item.dispense_qty-- : null"
                          >-</button>
                          <input 
                            type="number" 
                            class="form-control text-center fw-bold" 
                            v-model.number="item.dispense_qty" 
                            min="0" 
                            :max="Math.min(item.quantity, getMaterialBalance(item.material_id))"
                            @input="validateItemDispenseQty(item)"
                          />
                          <button 
                            type="button" 
                            class="btn btn-outline-secondary px-2" 
                            :disabled="item.dispense_qty >= Math.min(item.quantity, getMaterialBalance(item.material_id))"
                            @click="item.dispense_qty < Math.min(item.quantity, getMaterialBalance(item.material_id)) ? item.dispense_qty++ : null"
                          >+</button>
                        </div>
                        <div v-if="item.dispense_qty < item.quantity" class="small text-warning-emphasis mt-1">
                          จ่ายบางส่วน (ขอ {{ item.quantity }})
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <template v-if="getMaterialBalance(item.material_id) <= 0">
                        <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fw-bold">
                          0 {{ getMaterialUnit(item.material_id) }}
                        </span>
                        <div class="small text-danger mt-1 fw-bold">
                          <i class="bi bi-x-circle-fill me-1"></i> สินค้าหมดในคลัง
                        </div>
                      </template>
                      <template v-else-if="getMaterialBalance(item.material_id) < item.quantity">
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-2 fw-bold">
                          {{ getMaterialBalance(item.material_id) }} {{ getMaterialUnit(item.material_id) }}
                        </span>
                        <div class="small text-danger mt-1 fw-bold" v-if="item.dispense_qty > getMaterialBalance(item.material_id)">
                          <i class="bi bi-exclamation-circle-fill me-1"></i> ของไม่พอจ่าย!
                        </div>
                        <div class="small text-warning-emphasis mt-1" v-else>
                          <i class="bi bi-info-circle me-1"></i> มีน้อยกว่าที่ขอเบิก
                        </div>
                      </template>
                      <template v-else>
                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-bold">
                          {{ getMaterialBalance(item.material_id) }} {{ getMaterialUnit(item.material_id) }}
                        </span>
                        <div class="small text-success mt-1">
                          <i class="bi bi-check-circle me-1"></i> ของพร้อมจ่าย
                        </div>
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light p-3 px-4 justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-danger fw-bold" @click="deleteFromReview(reviewData)">
              <i class="bi bi-trash"></i> ลบคำขอ
            </button>
            <div class="d-flex gap-2 align-items-center">
              <button type="button" class="btn btn-outline-warning text-dark fw-bold px-3" @click="editFromReview(reviewData)">
                <i class="bi bi-pencil-square"></i> แก้ไขคำขอ
              </button>
              <button type="button" class="btn btn-danger fw-bold px-3" @click="rejectFromReview(reviewData)">
                <i class="bi bi-x-circle"></i> ปฏิเสธ
              </button>
              <button 
                type="button" 
                class="btn btn-success fw-bold px-4" 
                :disabled="isApproveDisabled(reviewData)"
                @click="approveFromReview(reviewData)"
                :title="isApproveDisabled(reviewData) ? 'ไม่สามารถอนุมัติได้เนื่องจากไม่มีของพร้อมจ่าย' : 'ยืนยันอนุมัติจ่าย'"
              >
                <i class="bi bi-check-circle fs-5 me-1"></i> อนุมัติ
              </button>
            </div>
          </div>
          <div v-if="reviewData && isApproveDisabled(reviewData)" class="bg-danger-subtle text-danger px-4 py-2 border-top border-danger-subtle small fw-bold text-center">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <span v-if="isAllItemsOutOfStock(reviewData)">ไม่สามารถอนุมัติจ่ายได้ เนื่องจากของในคลังหมดทั้งหมด (กรุณากดปฏิเสธคำขอ)</span>
            <span v-else>มีรายการที่ระบุจำนวนจ่ายมากกว่าของในคลัง หรือจำนวนจ่ายรวมเป็น 0</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-warning bg-opacity-10 border-bottom-0 pb-0">
            <h5 class="modal-title text-warning-emphasis fw-bold">
              <i class="bi bi-pencil-square me-2"></i>แก้ไขรายการเบิกวัสดุคอมพิวเตอร์
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 pt-3">
            <form @submit.prevent="saveEdit">
              <!-- ข้อมูลผู้เบิก -->
              <div class="card border border-light shadow-sm mb-4">
                <div class="card-body">
                  <h6 class="card-title text-muted mb-3 fw-bold">
                    <i class="bi bi-person-badge me-1"></i> ข้อมูลผู้เบิก
                  </h6>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">ชื่อ-สกุลผู้เบิก <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control bg-light"
                        v-model="editData.requester_name"
                        required
                        list="requesterOptionsEdit"
                      />
                      <datalist id="requesterOptionsEdit">
                        <option v-for="(name, index) in pastRequesters" :key="index" :value="name"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">หน่วยงาน <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        class="form-control bg-light"
                        v-model="editData.department"
                        required
                        list="deptOptionsEdit"
                      />
                      <datalist id="deptOptionsEdit">
                        <option v-for="(dept, index) in pastDepartments" :key="index" :value="dept"></option>
                      </datalist>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label text-secondary small mb-1">วันที่ขอเบิก <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        class="form-control bg-light"
                        v-model="editData.request_date"
                        required
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- รายการวัสดุ -->
              <div class="card border border-light shadow-sm mb-4">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title text-muted mb-0 fw-bold">
                      <i class="bi bi-box-seam me-1"></i> รายการวัสดุที่ต้องการเบิก
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addEditItem">
                      <i class="bi bi-plus-lg"></i> เพิ่มรายการ
                    </button>
                  </div>

                  <div v-if="editData.items.length === 0" class="text-center p-3 bg-light rounded text-muted">
                    กรุณาเพิ่มรายการวัสดุ
                  </div>

                  <div 
                    v-for="(item, index) in editData.items" 
                    :key="index" 
                    class="row g-2 align-items-end mb-3 p-3 bg-light rounded position-relative"
                  >
                    <button 
                      type="button" 
                      class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 mt-2 me-2 rounded-circle border-0" 
                      style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                      @click="removeEditItem(index)"
                      title="ลบรายการนี้"
                      v-if="editData.items.length > 1"
                    >
                      <i class="bi bi-x-lg"></i>
                    </button>

                    <div class="col-md-7">
                      <label class="form-label text-secondary small mb-1">เลือกวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="item.material_id" required>
                        <option value="" disabled>-- เลือกวัสดุ --</option>
                        <option
                          v-for="mat in materials"
                          :key="mat.id"
                          :value="mat.id"
                          :disabled="mat.balance <= 0"
                        >
                          {{ mat.name }} ({{ mat.code }}) - คงเหลือ: {{ mat.balance }} {{ mat.unit || 'ชิ้น' }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label text-secondary small mb-1">จำนวน <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" @click="item.quantity > 1 ? item.quantity-- : null">-</button>
                        <input
                          type="number"
                          class="form-control text-center px-1"
                          v-model.number="item.quantity"
                          min="1"
                          required
                        />
                        <button type="button" class="btn btn-outline-secondary" @click="item.quantity++">+</button>
                      </div>
                    </div>
                    <div class="col-md-2 d-none d-md-block text-end pb-1">
                      <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-2 py-1" v-if="item.material_id">
                        เหลือ {{ getMaterialBalance(item.material_id) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submit -->
              <div class="text-end">
                <button
                  type="button"
                  class="btn btn-light me-2 fw-bold"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm" :disabled="!isEditFormValid">
                  <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    </teleport>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as bootstrap from 'bootstrap';

export default {
  name: 'MtRequestsManage',
  data() {
    return {
      requests: [],
      statusFilter: 'all',
      materials: [],
      pastRequesters: [],
      pastDepartments: [],
      editData: {
        request_no: null,
        group_id: null,
        requester_name: '',
        department: '',
        request_date: '',
        items: []
      },
      editModalInstance: null,
      reviewData: null,
      isEditingDepartment: false,
      editDepartmentVal: ''
    };
  },
  computed: {
    isEditFormValid() {
      if (!this.editData.requester_name || !this.editData.department || !this.editData.request_date) {
        return false;
      }
      if (this.editData.items.length === 0) return false;
      for (const item of this.editData.items) {
        if (!item.material_id || !item.quantity || item.quantity < 1) {
          return false;
        }
      }
      return true;
    }
  },
  mounted() {
    this.fetchRequests();
    this.fetchMaterials();
    this.fetchRequestersAndDepts();
  },
  methods: {
    async fetchRequests() {
      try {
        const res = await axios.get(
          `/api-digital/it_material/it_get_requests.php?status=${this.statusFilter}`
        );
        if (res.data.success) {
          this.requests = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching requests', error);
      }
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/it_material/it_get_materials.php');
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching materials:', error);
      }
    },
    async fetchRequestersAndDepts() {
      try {
        const res = await axios.get('/api-digital/it_material/it_get_requesters_depts.php');
        if (res.data.success) {
          this.pastRequesters = res.data.requesters || [];
          this.pastDepartments = res.data.departments || [];
        }
      } catch (error) {
        console.error('Error fetching requesters and departments:', error);
      }
    },
    getStatusBadge(status) {
      const map = {
        pending: 'bg-warning text-dark bg-opacity-75',
        approved: 'bg-success bg-opacity-75',
        rejected: 'bg-danger bg-opacity-75'
      };
      return map[status] || 'bg-secondary';
    },
    getStatusIcon(status) {
      const map = {
        pending: 'bi bi-hourglass-split',
        approved: 'bi bi-check2-circle',
        rejected: 'bi bi-x-circle'
      };
      return map[status] || 'bi-info-circle';
    },
    getStatusText(status) {
      const map = {
        pending: 'รออนุมัติ',
        approved: 'จ่ายของแล้ว',
        rejected: 'ปฏิเสธ'
      };
      return map[status] || status;
    },
    getMaterialBalance(materialId) {
      if (!materialId) return 0;
      const mat = this.materials.find(m => Number(m.id) === Number(materialId));
      return mat ? Number(mat.balance) : 0;
    },
    getMaterialUnit(materialId) {
      if (!materialId) return '';
      const mat = this.materials.find(m => Number(m.id) === Number(materialId));
      return mat ? (mat.unit || '') : '';
    },
    isRequestOutOfStock(req) {
      if (!req || !req.items || req.items.length === 0) return false;
      return req.items.every(item => this.getMaterialBalance(item.material_id) <= 0);
    },
    isAllItemsOutOfStock(data) {
      if (!data || !data.items || data.items.length === 0) return true;
      return data.items.every(item => this.getMaterialBalance(item.material_id) <= 0);
    },
    isApproveDisabled(data) {
      if (!data || !data.items || data.items.length === 0) return true;
      const totalDispensing = data.items.reduce((sum, item) => sum + (Number(item.dispense_qty) || 0), 0);
      if (totalDispensing <= 0) return true;
      const hasInvalid = data.items.some(item => {
        const bal = this.getMaterialBalance(item.material_id);
        const qty = Number(item.dispense_qty) || 0;
        return qty > bal || qty < 0;
      });
      return hasInvalid;
    },
    validateItemDispenseQty(item) {
      const bal = this.getMaterialBalance(item.material_id);
      const maxAllowed = Math.min(Number(item.quantity), bal);
      if (item.dispense_qty > maxAllowed) {
        item.dispense_qty = maxAllowed;
      }
      if (item.dispense_qty < 0 || isNaN(item.dispense_qty)) {
        item.dispense_qty = 0;
      }
    },
    async openReviewModal(req) {
      await this.fetchMaterials();
      this.isEditingDepartment = false;
      this.reviewData = JSON.parse(JSON.stringify(req));
      this.reviewData.items.forEach(item => {
        const bal = this.getMaterialBalance(item.material_id);
        if (bal <= 0) {
          item.dispense_qty = 0; // ไม่มีของในคลัง -> ไม่สามารถให้จ่ายได้
        } else {
          item.dispense_qty = Math.min(Number(item.quantity), bal);
        }
      });
      const modal = new bootstrap.Modal(document.getElementById('reviewRequestModal'));
      modal.show();
    },
    closeReviewModal() {
      const modalEl = document.getElementById('reviewRequestModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
    },
        startEditDepartment() {
      this.editDepartmentVal = this.reviewData.department || '';
      this.isEditingDepartment = true;
    },
    async saveEditDepartment() {
      if (!this.editDepartmentVal || !this.editDepartmentVal.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุหน่วยงาน', 'warning');
        return;
      }
      try {
        const res = await axios.post('/api-digital/it_material/it_update_request_department.php', {
          request_no: this.reviewData.request_no || this.reviewData.group_id,
          id: this.reviewData.id,
          department: this.editDepartmentVal.trim()
        });
        if (res.data.success) {
          this.reviewData.department = this.editDepartmentVal.trim();
          this.isEditingDepartment = false;
          Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'แก้ไขหน่วยงานเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
          });
          this.fetchRequests();
        } else {
          Swal.fire('ผิดพลาด', res.data.message || 'ไม่สามารถแก้ไขหน่วยงานได้', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      }
    },
    editFromReview(req) {
      this.closeReviewModal();
      setTimeout(() => {
        this.openEditModal(req);
      }, 400);
    },
    rejectFromReview(req) {
      this.closeReviewModal();
      setTimeout(() => {
        this.rejectRequest(req);
      }, 400);
    },
    approveFromReview(req) {
      this.closeReviewModal();
      setTimeout(() => {
        this.approveRequest(req);
      }, 400);
    },
    deleteFromReview(req) {
      this.closeReviewModal();
      setTimeout(() => {
        this.deleteRequest(req);
      }, 400);
    },
    openEditModal(req) {
      this.editData = {
        request_no: req.request_no || req.group_id,
        group_id: req.request_no || req.group_id,
        requester_name: req.requester_name,
        department: req.department,
        request_date: req.request_date,
        items: req.items.map(i => ({
          material_id: i.material_id,
          quantity: i.quantity
        }))
      };
      
      if (this.editData.items.length === 0) {
        this.addEditItem();
      }

      if (!this.editModalInstance) {
        this.editModalInstance = new bootstrap.Modal(
          document.getElementById('editRequestModal')
        );
      }
      this.editModalInstance.show();
    },
    addEditItem() {
      this.editData.items.push({ material_id: '', quantity: 1 });
    },
    removeEditItem(index) {
      if (this.editData.items.length > 1) {
        this.editData.items.splice(index, 1);
      }
    },
    async saveEdit() {
      try {
        const payload = {
          request_no: this.editData.request_no,
          group_id: this.editData.group_id,
          requester_name: this.editData.requester_name,
          department: this.editData.department,
          request_date: this.editData.request_date,
          items: this.editData.items
        };

        const res = await axios.post('/api-digital/it_material/it_update_request.php', payload);
        
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: res.data.message || 'แก้ไขข้อมูลเรียบร้อย',
            timer: 1500,
            showConfirmButton: false
          });
          this.editModalInstance.hide();
          this.fetchRequests();
          this.fetchMaterials();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถแก้ไขข้อมูลได้', 'error');
        }
      } catch (error) {
        console.error('Error saving edit', error);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      }
    },
    async approveRequest(req) {
      let hasError = false;
      let errorMsg = '';
      let dispenseCount = 0;
      let zeroCount = 0;

      for (const item of req.items) {
        const bal = this.getMaterialBalance(item.material_id);
        const checkQty = Number(item.dispense_qty !== undefined ? item.dispense_qty : item.quantity);

        if (checkQty > bal) {
          hasError = true;
          errorMsg = `ไม่สามารถจ่าย "${item.material_name}" ได้ เนื่องจากของในคลังไม่พอ (ในคลังคงเหลือ ${bal}, สั่งจ่าย ${checkQty})`;
          break;
        }

        if (checkQty > 0) {
          dispenseCount++;
        } else {
          zeroCount++;
        }
      }

      if (hasError) {
        Swal.fire('ข้อผิดพลาด', errorMsg, 'error');
        return;
      }

      if (dispenseCount === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'ไม่สามารถจ่ายได้',
          text: 'ไม่มีรายการที่พร้อมจ่ายของในคลัง (ของในคลังหมด หรือจำนวนจ่ายเป็น 0)'
        });
        return;
      }

      let confirmText = `คุณต้องการจ่ายวัสดุจำนวน ${dispenseCount} รายการ ให้แก่ ${req.requester_name} ใช่หรือไม่? (สต๊อกจะถูกตัดทันที)`;
      if (zeroCount > 0) {
        confirmText += `\n*หมายเหตุ: มี ${zeroCount} รายการที่ของในคลังหมด จะไม่ถูกจ่าย`;
      }

      const confirm = await Swal.fire({
        title: 'ยืนยันการอนุมัติจ่ายวัสดุคอมฯ?',
        text: confirmText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'ยืนยันอนุมัติจ่าย',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const approvals = req.items.map(item => {
            const qty = Number(item.dispense_qty !== undefined ? item.dispense_qty : item.quantity);
            return {
              id: item.id,
              approved_quantity: qty,
              admin_note: qty > 0 ? 'อนุมัติจ่ายวัสดุผ่านระบบ' : 'สินค้าในคลังหมด ไม่สามารถจ่ายได้'
            };
          });

          const res = await axios.post('/api-digital/it_material/it_approve_request.php', {
            request_no: req.request_no || req.group_id,
            approvals: approvals,
            admin_note: 'อนุมัติผ่านระบบ'
          });
          if (res.data.success) {
            await Promise.all([this.fetchRequests(), this.fetchMaterials()]);
            Swal.fire({
              icon: 'success',
              title: 'อนุมัติและจ่ายของสำเร็จ',
              text: res.data.message || 'ระบบได้ตัดสต๊อกและแจ้งเตือนผู้เบิกเรียบร้อย',
              showConfirmButton: false,
              timer: 1500
            });
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          const msg = err.response?.data?.message || err.message || 'Failed to approve request';
          Swal.fire('ข้อผิดพลาด', msg, 'error');
        }
      }
    },
    async rejectRequest(req) {
      const { value: note } = await Swal.fire({
        title: 'เหตุผลที่ปฏิเสธ',
        input: 'textarea',
        inputLabel: 'ระบุเหตุผลการไม่อนุมัติให้เบิก',
        inputPlaceholder: 'เช่น ของหมดชั่วคราว...',
        showCancelButton: true
      });

      if (note !== undefined) {
        try {
          const res = await axios.post('/api-digital/it_material/it_reject_request.php', {
            request_no: req.request_no || req.group_id,
            admin_note: note
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'บันทึกการปฏิเสธสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchRequests();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Failed to reject request', 'error');
        }
      }
    },
    async deleteRequest(req) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบข้อมูล?',
        text:
          req.status === 'approved'
            ? 'คำขอนี้อนุมัติไปแล้ว การลบจะคืนยอดเข้าสต๊อกด้วย ยืนยันหรือไม่คะ?'
            : 'คุณต้องการลบข้อมูลคำขอเบิกนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/it_material/it_delete_request.php', {
            request_no: req.request_no || req.group_id
          });
          if (res.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'ลบข้อมูลสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchRequests();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('Error', 'Failed to delete request', 'error');
        }
      }
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
    async exportPDF(req) {
      Swal.fire({
        title: 'กำลังสร้างไฟล์ PDF...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const reqIdentifier = req.request_no || req.group_id;
        const [reqRes, setRes] = await Promise.all([
          axios.get(`/api-digital/it_material/it_get_request_by_id.php?request_no=${reqIdentifier}`),
          axios.get('/api-digital/it_material/it_get_print_settings.php')
        ]);

        if (!reqRes.data.success) throw new Error('ไม่สามารถโหลดข้อมูลคำขอเบิกได้');

        const requestData = reqRes.data.data;
        const globalSettings = setRes.data.success ? setRes.data.global || {} : {};
        const departmentsConfig = setRes.data.success ? setRes.data.departments || [] : [];
        const targetDepts = [
          (requestData.department || '').trim(),
          (requestData.hr_department_sub || '').trim(),
          (requestData.hr_department || '').trim()
        ].filter(Boolean);

        let matchedConfig = {};
        for (const deptName of targetDepts) {
          const found = departmentsConfig.find(
            (d) => (d.department_name || '').trim().toLowerCase() === deptName.toLowerCase()
          );
          if (found) {
            matchedConfig = found;
            break;
          }
        }

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

        doc.setFont('Sarabun', 'bold');
        doc.setFontSize(22);
        doc.text('ใบเบิกวัสดุคอมพิวเตอร์', 105, 20, { align: 'center' });

        doc.setFontSize(16);
        doc.text(
          `ใบเบิกที่: ${new Date().getFullYear() + 543 + String(requestData.id).padStart(4, '0')}`,
          20,
          35
        );

        const dDate = new Date(requestData.request_date || new Date());
        const months = [
          'มกราคม',
          'กุมภาพันธ์',
          'มีนาคม',
          'เมษายน',
          'พฤษภาคม',
          'มิถุนายน',
          'กรกฎาคม',
          'สิงหาคม',
          'กันยายน',
          'ตุลาคม',
          'พฤศจิกายน',
          'ธันวาคม'
        ];
        doc.text(
          `วันที่ ${dDate.getDate()} เดือน ${months[dDate.getMonth()]} พ.ศ. ${dDate.getFullYear() + 543}`,
          190,
          35,
          { align: 'right' }
        );

        doc.text('เรื่อง', 20, 45);
        doc.setFont('Sarabun', 'normal');
        doc.text('ขอเบิกวัสดุคอมพิวเตอร์', 35, 45);

        doc.setFont('Sarabun', 'bold');
        doc.text('เรียน', 20, 53);
        doc.setFont('Sarabun', 'normal');
        doc.text('หัวหน้ากลุ่มงานเทคโนโลยีสารสนเทศ', 35, 53);

        doc.text(
          `ข้าพเจ้า  .........................................................................  ตำแหน่ง  ...................................................................................`,
          30,
          65
        );
        doc.text(requestData.requester_name || '', 44, 64);
        doc.text(requestData.position_name2 || '', 128, 64);

        doc.text(
          `กลุ่มงาน  .....................................................................................................`,
          30,
          75
        );
        const printDept = matchedConfig.department_name || requestData.department || requestData.hr_department_sub || '';
        doc.text(printDept, 45, 74);

        doc.text('ขอเบิกวัสดุคอมพิวเตอร์เพื่อใช้ในหน่วยงาน ดังต่อไปนี้', 30, 85);

        const tableBody = requestData.items.map((item, index) => [
          (index + 1).toString(),
          item.material_name + (item.material_code ? ` (${item.material_code})` : ''),
          item.material_unit || 'ชิ้น',
          item.quantity,
          item.status === 'approved' ? item.quantity : '-',
          item.current_balance
        ]);

        autoTable(doc, {
          startY: 92,
          head: [['ลำดับที่', 'รายการ', 'หน่วยนับ', 'จำนวนเบิก', 'อนุมัติ', 'คงเหลือในบัญชีคุม']],
          body: tableBody,
          styles: {
            font: 'Sarabun',
            fontStyle: 'normal',
            fontSize: 14,
            halign: 'center',
            valign: 'middle',
            lineColor: [0, 0, 0],
            lineWidth: 0.1
          },
          headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            font: 'Sarabun',
            fontStyle: 'bold'
          },
          columnStyles: {
            0: { cellWidth: 20 },
            1: { halign: 'left', cellWidth: 'auto' },
            2: { cellWidth: 25 },
            3: { cellWidth: 25 },
            4: { cellWidth: 25 },
            5: { cellWidth: 35 }
          },
          theme: 'grid',
          margin: { left: 20, right: 20 }
        });

        let finalY = (doc.lastAutoTable || doc.previousAutoTable).finalY + 20;

        const reqName =
          matchedConfig.requester_name ||
          requestData.requester_name ||
          '...........................................';
        const reqPos =
          (matchedConfig.requester_name ? matchedConfig.requester_position : (requestData.position_name2 || requestData.requester_position)) || matchedConfig.requester_position || requestData.position_name2 || requestData.requester_position ||
          '.....................................................';

        const payerName =
          globalSettings.payer_name || '...........................................';
        const payerPos =
          globalSettings.payer_position || '.....................................................';

        const appName =
          globalSettings.approver_name || '...........................................';
        const appPos =
          globalSettings.approver_position ||
          '.....................................................';

        if (finalY > 230) {
          doc.addPage();
          finalY = 30;
        }

        doc.text('ผู้ขอใช้พัสดุ...........................................ลงชื่อ', 60, finalY, {
          align: 'center'
        });
        doc.text(`( ${reqName} )`, 60, finalY + 10, { align: 'center' });
        doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 18, { align: 'center' });

        doc.text('ผู้จ่าย...........................................ลงชื่อ', 150, finalY, {
          align: 'center'
        });
        doc.text(`( ${payerName} )`, 150, finalY + 10, { align: 'center' });
        doc.text(`ตำแหน่ง ${payerPos}`, 150, finalY + 18, { align: 'center' });

        doc.text('ผู้เบิก...........................................ลงชื่อ', 60, finalY + 35, {
          align: 'center'
        });
        doc.text(`( ${reqName} )`, 60, finalY + 45, { align: 'center' });
        doc.text(`ตำแหน่ง ${reqPos}`, 60, finalY + 53, { align: 'center' });

        doc.text('ผู้สั่งจ่าย...........................................ลงชื่อ', 150, finalY + 35, {
          align: 'center'
        });
        doc.text(`( ${appName} )`, 150, finalY + 45, { align: 'center' });
        doc.text(`ตำแหน่ง ${appPos}`, 150, finalY + 53, { align: 'center' });

        doc.text('ผู้รับพัสดุ...........................................ลงชื่อ', 60, finalY + 70, {
          align: 'center'
        });
        doc.text(`( ${requestData.requester_name} )`, 60, finalY + 80, { align: 'center' });
        doc.text(
          `ตำแหน่ง ${requestData.position_name2 || '.....................................................'}`,
          60,
          finalY + 88,
          { align: 'center' }
        );

        doc.save(`it-material-request-${requestData.request_no || requestData.id}.pdf`);
        Swal.close();
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
      }
    }
  }
};
</script>

<style scoped>
/* Typography & Layout */
.text-gradient-primary {
  background: linear-gradient(135deg, #0d6efd, #0dcaf0);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(255, 255, 255, 0.4);
}
.hover-lift {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Animations */
.fade-in {
  animation: fadeIn 0.5s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Custom Table Styles */
.custom-table {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
}
.custom-table th {
  background: rgba(13, 110, 253, 0.05);
  padding: 1.25rem 1rem;
  border-bottom: 2px solid rgba(13, 110, 253, 0.1);
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}
.custom-table td {
  padding: 1.25rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  transition: background-color 0.2s ease;
}
.custom-table tr:hover td {
  background-color: rgba(13, 110, 253, 0.02);
}
.custom-table tr:last-child td {
  border-bottom: none;
}
.custom-table th:first-child {
  border-top-left-radius: 12px;
}
.custom-table th:last-child {
  border-top-right-radius: 12px;
}

/* Badges */
.badge-status {
  padding: 0.6em 1.2em;
  border-radius: 50rem;
  font-weight: 500;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* Buttons */
.btn-action {
  border-radius: 50rem;
  padding: 0.4rem 1rem;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: 500;
  border: none;
}
.btn-action-pdf {
  background: linear-gradient(135deg, #ff416c, #ff4b2b);
  color: white;
}
.btn-action-del {
  background: white;
  color: #dc3545;
  border: 1px solid #dc3545;
}
.btn-action-del:hover {
  background: #dc3545;
  color: white;
}
.btn-action-approve {
  background: linear-gradient(135deg, #11998e, #38ef7d);
  color: white;
}
.btn-action-edit {
  background: linear-gradient(135deg, #f2c94c, #f2994a);
  color: white;
}
.btn-action-reject {
  background: linear-gradient(135deg, #eb5757, #000000);
  color: white;
}

/* Empty State */
.empty-state-wrapper {
  padding: 4rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(248, 250, 252, 0.6);
  border-radius: 16px;
  border: 1px dashed #cbd5e1;
}
</style>
