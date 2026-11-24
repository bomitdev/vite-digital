<template>
  <div class="requisition-form-container container-fluid py-3 px-3 px-md-4">
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
            <button
              type="button"
              class="btn btn-sm btn-light rounded-pill px-3 py-1 fw-semibold text-secondary d-inline-flex align-items-center gap-1 border-0 nav-pill-btn"
              @click="handleGoToManage"
            >
              <i class="bi bi-clipboard2-check-fill text-warning"></i>
              <span>คำขอเบิกและอนุมัติจ่าย</span>
            </button>
            <i class="bi bi-chevron-right text-muted small"></i>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fw-bold">
              <i class="bi bi-pencil-square me-1" v-if="editId"></i>
              <i class="bi bi-plus-circle-fill me-1" v-else></i>
              {{ editId ? 'แก้ไขใบขอเบิก' : 'สร้างใบขอเบิกใหม่' }}
            </span>
          </div>
        </nav>
        <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-cart-plus-fill text-warning"></i>
          {{ editId ? `แก้ไขใบขอเบิกพัสดุ (${editReqNo || '#' + editId})` : 'สร้างใบขอเบิกพัสดุ (Requisition Form)' }}
        </h3>
        <small class="text-muted">บันทึกส่งคำขอเบิกพัสดุจากหน่วยงาน เพื่อเสนอเจ้าหน้าที่คลังพิจารณาอนุมัติจ่าย</small>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" @click="handleGoToManage">
          <i class="bi bi-list-ul me-1"></i> รายการคำขอเบิกทั้งหมด
        </button>
        <button class="btn btn-light border rounded-pill px-3 shadow-sm" @click="handleBackToDashboard">
          <i class="bi bi-arrow-left me-1"></i> กลับหน้าหลัก
        </button>
      </div>
    </div>

    <!-- Main Form Card -->
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4 p-md-5">
        <form @submit.prevent="submitRequisition">
          
          <!-- Section 1: ข้อมูลผู้ขอเบิกและคลัง -->
          <div class="mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-warning-emphasis d-flex align-items-center gap-2">
              <span class="badge bg-warning text-dark rounded-circle px-2">1</span>
              ข้อมูลผู้ขอเบิกและหน่วยงาน
            </h5>
            
            <div class="row g-3">
              <!-- คลังพัสดุปลายทาง -->
              <div class="col-md-4">
                <label class="form-label fw-bold">คลังที่ต้องการเบิก <span class="text-danger">*</span></label>
                <select class="form-select form-select-lg fw-bold text-primary" v-model="form.warehouse_id" @change="onWarehouseChange" required>
                  <option value="">-- กรุณาเลือกคลัง --</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                </select>
              </div>

              <!-- หน่วยงานที่ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold">หน่วยงาน / แผนกที่ขอเบิก <span class="text-danger">*</span></label>
                <select class="form-select form-select-lg" v-model="selectedDeptKey" @change="onDeptSelectChange" required>
                  <option value="">-- เลือกหน่วยงาน --</option>
                  <optgroup label="หน่วยงานภายในโรงพยาบาล">
                    <option v-for="d in departments" :key="'int:' + d.id" :value="'int:' + d.id">{{ d.name }}</option>
                  </optgroup>
                  <optgroup v-if="externalDepartments.length" label="รพ.สต. / หน่วยงานภายนอก">
                    <option v-for="ext in externalDepartments" :key="'ext:' + ext.id" :value="'ext:' + ext.id">{{ ext.name }}</option>
                  </optgroup>
                  <option value="custom">+ ระบุหน่วยงานภายนอกอื่นๆ เอง...</option>
                </select>
                <input
                  v-if="selectedDeptKey === 'custom'"
                  type="text"
                  class="form-control mt-2"
                  v-model="form.department_name"
                  placeholder="พิมพ์ชื่อ รพ.สต. หรือหน่วยงานภายนอก"
                  required
                />
                <div v-if="selectedExternalDept" class="form-text text-primary small mt-1">
                  <i class="bi bi-person-badge me-1"></i>ผู้ขอใช้พัสดุ (PDF): {{ selectedExternalDept.head_name }} ({{ selectedExternalDept.head_position }})
                </div>
              </div>

              <!-- วันที่ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold">วันที่ต้องการขอเบิก</label>
                <input type="datetime-local" class="form-control form-control-lg" v-model="form.request_date" required />
              </div>

              <!-- ชื่อผู้ขอเบิก -->
              <div class="col-md-4">
                <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                  <span>ชื่อ-นามสกุล ผู้ขอเบิก <span class="text-danger">*</span></span>
                  <span v-if="staffList.length" class="badge bg-primary-subtle text-primary border rounded-pill fw-normal small px-2">
                    <i class="bi bi-people-fill me-1"></i>{{ staffList.length }} รายชื่อ
                  </span>
                </label>
                <div class="position-relative">
                  <input
                    type="text"
                    class="form-control form-control-lg"
                    v-model="form.requester_name"
                    list="staffListOptions"
                    placeholder="พิมพ์ค้นหาหรือคลิกเลือกรายชื่อเจ้าหน้าที่..."
                    @change="onStaffSelected"
                    required
                  />
                  <datalist id="staffListOptions">
                    <option
                      v-for="s in staffListForDatalist"
                      :key="s.ID"
                      :value="s.FULLNAME"
                    >
                      {{ s.HR_DEPARTMENT_SUB_NAME ? `[${s.HR_DEPARTMENT_SUB_NAME}]` : '' }} {{ s.HR_POSITION_NAME ? `(${s.HR_POSITION_NAME})` : '' }} {{ s.HR_PHONE ? `โทร: ${s.HR_PHONE}` : '' }}
                    </option>
                  </datalist>
                </div>
              </div>

              <!-- เบอร์โทรศัพท์ -->
              <div class="col-md-4">
                <label class="form-label fw-bold">เบอร์โทรติดต่อ / ภายใน</label>
                <input type="text" class="form-control" v-model="form.requester_phone" placeholder="เช่น 102 หรือ 081-xxx-xxxx" />
              </div>

              <!-- วัตถุประสงค์ -->
              <div class="col-md-4">
                <label class="form-label fw-bold">วัตถุประสงค์การนำไปใช้งาน</label>
                <input type="text" class="form-control" v-model="form.purpose" placeholder="เช่น ใช้งานประจำห้องตรวจ / ทดแทนของชำรุด" />
              </div>

              <!-- Quick Select Pills for department staff (Full Width) -->
              <div v-if="departmentStaffList.length > 0" class="col-12">
                <div class="p-2 px-3 bg-light rounded-3 border">
                  <div class="small text-muted mb-2 d-flex align-items-center justify-content-between gap-1">
                    <span class="d-flex align-items-center gap-1">
                      <i class="bi bi-person-check-fill text-success"></i>
                      <span>เจ้าหน้าที่ในแผนกนี้ (คลิกเพื่อเลือกทันที):</span>
                    </span>
                    <span class="badge bg-white text-secondary border rounded-pill">{{ departmentStaffList.length }} คน</span>
                  </div>
                  <div class="d-flex flex-wrap gap-1 pe-1" style="max-height: 112px; overflow-y: auto;">
                    <button
                      type="button"
                      v-for="s in departmentStaffList"
                      :key="s.ID"
                      class="btn btn-sm rounded-pill py-0 px-2 text-start transition-all"
                      style="font-size: 0.78rem; line-height: 1.65;"
                      :class="form.requester_name === s.FULLNAME ? 'btn-primary shadow-xs' : 'btn-outline-secondary bg-white'"
                      @click="selectStaffDirectly(s)"
                    >
                      <i class="bi bi-person me-1"></i>{{ s.FULLNAME }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2 & 3: รายการวัสดุในคลัง + ตะกร้าเบิกวัสดุ (Split Pane แบบเบิกวัสดุบริหาร) -->
          <div class="mt-4 pt-4 border-top" :class="{'opacity-50 pointer-events-none': !form.warehouse_id}">
            <div class="row g-4">
              <!-- Left Pane: Inventory Items List -->
              <div class="col-lg-7">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                  <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-circle px-2">2</span>
                    รายการวัสดุในคลัง
                  </h5>
                  <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <select v-model="selectedCategory" class="form-select shadow-sm border-light-subtle" style="max-width: 190px; font-size: 0.9rem;">
                      <option value="">ทุกประเภท</option>
                      <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                    <div class="input-group shadow-sm" style="max-width: 260px;">
                      <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                      <input type="text" v-model="searchQuery" class="form-control border-start-0 ps-0" placeholder="ค้นหาชื่อ หรือรหัสวัสดุ..." :disabled="!form.warehouse_id">
                    </div>
                  </div>
                </div>

                <div class="table-responsive border border-light-subtle rounded-4 bg-white shadow-sm" style="max-height: 520px; overflow-y: auto;">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top shadow-sm" style="z-index: 10;">
                      <tr class="small text-secondary">
                        <th width="42%" class="ps-3">รหัส / ชื่อวัสดุ</th>
                        <th width="22%" class="text-center">ประเภท</th>
                        <th width="16%" class="text-center">คงเหลือ</th>
                        <th width="20%" class="text-center pe-3">แอคชัน</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!form.warehouse_id">
                        <td colspan="4" class="text-center py-5 text-muted">
                          <i class="bi bi-building fs-1 mb-2 d-block text-secondary"></i>
                          กรุณาเลือกคลังวัสดุที่ต้องการเบิกด้านบนก่อน
                        </td>
                      </tr>
                      <tr v-else-if="filteredItems.length === 0">
                        <td colspan="4" class="text-center py-5 text-muted">
                          <i class="bi bi-search fs-1 mb-2 d-block text-secondary"></i>
                          ไม่พบรายการวัสดุที่ค้นหา
                        </td>
                      </tr>
                      <tr v-for="mat in filteredItems" :key="mat.id">
                        <td class="ps-3">
                          <div class="fw-bold text-dark">
                            {{ mat.item_name }}
                            <span v-if="Number(mat.admin_only_issue) === 1" class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 0.7rem;">
                              <i class="bi bi-shield-lock-fill me-1"></i>เฉพาะผู้ดูแลคลัง
                            </span>
                          </div>
                          <div class="small text-muted font-monospace">{{ mat.item_code || '-' }}</div>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-secondary-subtle text-secondary border rounded-pill fw-normal">
                            {{ mat.item_type_name || mat.category_name || '-' }}
                          </span>
                        </td>
                        <td class="text-center">
                          <span class="badge border px-2 py-1" :class="Number(mat.on_hand) > 0 ? 'bg-light text-dark' : 'bg-danger-subtle text-danger border-danger-subtle'">
                            <span class="fw-bold">{{ formatQty(mat.on_hand) }}</span>
                            <span class="fw-normal text-muted ms-1">{{ mat.unit_name || 'หน่วย' }}</span>
                          </span>
                        </td>
                        <td class="text-center pe-3">
                          <button
                            type="button"
                            class="btn btn-sm rounded-pill fw-bold w-100 shadow-sm d-flex justify-content-center align-items-center gap-1"
                            :class="isItemInCart(mat.id) ? 'btn-secondary' : 'btn-outline-primary'"
                            :disabled="isItemInCart(mat.id)"
                            @click="addToCart(mat)"
                          >
                            <i class="bi" :class="isItemInCart(mat.id) ? 'bi-check2' : 'bi-plus-lg'"></i>
                            {{ isItemInCart(mat.id) ? 'เลือกแล้ว' : 'เบิก' }}
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Right Pane: Cart -->
              <div class="col-lg-5 mt-4 mt-lg-0">
                <div class="bg-light rounded-4 p-4 h-100 border border-light-subtle shadow-sm d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                      <span class="badge bg-success text-white rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-cart-check fs-6"></i></span>
                      ตะกร้าเบิกวัสดุ
                    </h5>
                    <span class="badge bg-primary rounded-pill shadow-sm fs-6 px-3">{{ selectedItems.length }} รายการ</span>
                  </div>

                  <!-- Empty State -->
                  <div v-if="selectedItems.length === 0" class="text-center py-5 my-auto">
                    <div class="bg-white text-muted shadow-sm mx-auto mb-3 rounded-4 d-flex align-items-center justify-content-center" style="width: 76px; height: 76px;">
                      <i class="bi bi-cart-x fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">ยังไม่ได้เลือกรายการวัสดุ</h6>
                    <p class="text-muted small mb-0">กรุณาคลิกปุ่ม "เบิก" จากรายการด้านซ้าย</p>
                  </div>

                  <!-- Cart Items (Compact Scannable List) -->
                  <div v-else class="flex-grow-1 d-flex flex-column">
                    <div class="bg-white rounded-3 border shadow-xs overflow-auto" style="max-height: 460px;">
                      <div
                        v-for="(item, index) in selectedItems"
                        :key="item.item_id"
                        class="px-3 py-2 border-bottom cart-row"
                      >
                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                              <span class="badge bg-dark rounded-pill flex-shrink-0" style="font-size: 0.68rem;">{{ index + 1 }}</span>
                              <span class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;" :title="item.item_name">
                                {{ item.item_name }}
                              </span>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1 ps-4" style="font-size: 0.75rem;">
                              <span class="text-muted font-monospace">[{{ item.item_code }}]</span>
                              <span
                                class="badge rounded-pill fw-normal"
                                :class="item.qty > item.on_hand ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'"
                              >
                                คงเหลือ {{ formatQty(item.on_hand) }} {{ item.unit_name }}
                              </span>
                            </div>
                          </div>

                          <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <div class="input-group input-group-sm rounded-pill overflow-hidden border border-primary" style="width: 108px;">
                              <button type="button" class="btn btn-primary px-2 py-0" @click="item.qty > 1 ? item.qty-- : null"><i class="bi bi-dash"></i></button>
                              <input
                                type="number"
                                step="1"
                                min="1"
                                :max="Math.max(1, Math.floor(Number(item.on_hand) || 0))"
                                v-model.number="item.qty"
                                @change="onCartQtyChange(item)"
                                class="form-control text-center fw-bold border-0 text-primary px-0 hide-arrows"
                                required
                                style="max-width: 42px; font-size: 0.85rem;"
                              >
                              <button type="button" class="btn btn-primary px-2 py-0" @click="incrementCartQty(item)"><i class="bi bi-plus"></i></button>
                            </div>
                            <span class="small fw-semibold text-secondary text-nowrap" style="min-width: 36px;">{{ item.unit_name || 'หน่วย' }}</span>
                            <button
                              type="button"
                              class="btn btn-sm btn-link text-danger p-1"
                              @click="removeItem(index)"
                              title="นำออก"
                            >
                              <i class="bi bi-trash-fill"></i>
                            </button>
                          </div>
                        </div>

                        <div class="mt-1 ps-4">
                          <input
                            type="text"
                            class="form-control form-control-sm bg-light border-0 py-0 px-2 text-muted"
                            style="font-size: 0.76rem; height: 24px;"
                            v-model="item.item_note"
                            placeholder="หมายเหตุรายการนี้ (ถ้ามี)..."
                          />
                        </div>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2 px-2 small text-muted">
                      <span>รวมทั้งหมด <strong class="text-dark">{{ selectedItems.length }}</strong> รายการ</span>
                      <span>จำนวนรวม <strong class="text-primary">{{ selectedItems.reduce((s, it) => s + (Number(it.qty) || 0), 0).toLocaleString() }}</strong> ชิ้น/หน่วย</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Bar -->
          <div class="d-flex justify-content-between align-items-center mt-5 border-top pt-4">
            <button type="button" class="btn btn-light rounded-pill px-4" @click="resetForm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างข้อมูล
            </button>
            <button 
              type="submit" 
              class="btn btn-warning text-dark rounded-pill px-5 py-2 fw-bold shadow" 
              :disabled="submitting || selectedItems.length === 0"
            >
              <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-send-fill me-2"></i>
              {{ editId ? 'บันทึกการแก้ไขใบขอเบิก' : 'ยืนยันส่งใบขอเบิกพัสดุ' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';

export default {
  name: 'RequisitionForm',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      warehouses: [],
      departments: [],
      externalDepartments: [],
      selectedDeptKey: '',
      staffList: [],
      userProfile: null,
      isInventoryAdmin: false,
      managedWarehouseIds: [],
      items: [],
      selectedItems: [],
      searchQuery: '',
      selectedCategory: '',
      isDropdownOpen: false,
      submitting: false,
      editId: null,
      editReqNo: '',

      form: {
        warehouse_id: '',
        department_id: '',
        department_name: '',
        request_date: moment().format('YYYY-MM-DDTHH:mm'),
        requester_name: localStorage.getItem('user_name') || '',
        requester_phone: '',
        purpose: ''
      },

      currentItem: {
        item_id: '',
        item_code: '',
        item_name: '',
        unit_name: '',
        on_hand: 0,
        qty: 1,
        item_note: ''
      }
    };
  },
  computed: {
    selectedExternalDept() {
      if (!this.selectedDeptKey || !this.selectedDeptKey.startsWith('ext:')) return null;
      const extId = Number(this.selectedDeptKey.slice(4));
      return this.externalDepartments.find(e => Number(e.id) === extId) || null;
    },
    canRequestRestrictedItems() {
      if (this.isInventoryAdmin) return true;
      const whId = Number(this.form.warehouse_id);
      return whId > 0 && this.managedWarehouseIds.includes(whId);
    },
    uniqueCategories() {
      const cats = new Set();
      this.items.forEach(m => {
        if (Number(m.on_hand) <= 0) return;
        if (Number(m.admin_only_issue) === 1 && !this.canRequestRestrictedItems) return;
        const c = m.item_type_name || m.category_name;
        if (c) cats.add(c);
      });
      return Array.from(cats).sort((a, b) => a.localeCompare(b, 'th'));
    },
    filteredItems() {
      let available = this.items.filter(
        m => Number(m.on_hand) > 0 && (Number(m.admin_only_issue) !== 1 || this.canRequestRestrictedItems)
      );
      if (this.selectedCategory) {
        available = available.filter(
          m => (m.item_type_name || m.category_name) === this.selectedCategory
        );
      }
      if (!this.searchQuery) return available;
      const q = this.searchQuery.toLowerCase();
      return available.filter(m => 
        (m.item_name && m.item_name.toLowerCase().includes(q)) || 
        (m.item_code && m.item_code.toLowerCase().includes(q))
      );
    },
    // กรองรายชื่อเจ้าหน้าที่ที่สังกัดหน่วยงาน/แผนกที่เลือก
    departmentStaffList() {
      if (!this.form.department_id && !this.form.department_name) return [];
      const deptName = (this.form.department_name || '').toLowerCase();
      const deptId = Number(this.form.department_id);

      return this.staffList.filter(s => {
        if (s.HR_DEPARTMENT_SUB_ID && Number(s.HR_DEPARTMENT_SUB_ID) === deptId) {
          return true;
        }
        if (!s.HR_DEPARTMENT_SUB_NAME || !deptName) return false;
        const sDept = s.HR_DEPARTMENT_SUB_NAME.toLowerCase();
        if (sDept === deptName) return true;

        // คำค้นหาหลักของกลุ่มงานในโรงพยาบาล
        const keywords = [
          'ประกันสุขภาพ', 'สุขภาพดิจิทัล', 'สารสนเทศ', 'เภสัช', 'บริหารทั่วไป',
          'การพยาบาล', 'ผู้ป่วยนอก', 'ผู้ป่วยใน', 'อุบัติเหตุ', 'ทันตกรรม',
          'เทคนิคการแพทย์', 'รังสี', 'เวชกรรมฟื้นฟู', 'จิตเวช', 'โภชน'
        ];
        for (const kw of keywords) {
          if (deptName.includes(kw) && sDept.includes(kw)) return true;
        }
        return false;
      });
    },
    // เรียงรายชื่อใน datalist: เอาคนในแผนกที่เลือกขึ้นก่อน ตามด้วยทุกคน
    staffListForDatalist() {
      if (this.departmentStaffList.length > 0) {
        const deptIds = new Set(this.departmentStaffList.map(s => s.ID));
        const others = this.staffList.filter(s => !deptIds.has(s.ID));
        return [...this.departmentStaffList, ...others];
      }
      return this.staffList;
    }
  },
  watch: {
    warehouseId(newVal) {
      if (newVal) {
        this.form.warehouse_id = parseInt(newVal);
        this.fetchItems();
      }
    },
    '$route.query.warehouse_id'(newVal) {
      if (newVal) {
        this.form.warehouse_id = parseInt(newVal);
        this.fetchItems();
      }
    }
  },
  mounted() {
    this.fetchMasters();
  },
  methods: {
    formatQty(val) {
      return Math.round(Number(val) || 0).toLocaleString('en-US');
    },
    handleBackToDashboard() {
      if (this.$route.path === '/inventory-requisition-form') {
        this.$router.push('/home-backoffice');
      } else {
        this.$emit('back-to-dashboard');
      }
    },
    handleGoToManage() {
      if (this.$route.path === '/inventory-requisition-form') {
        this.$router.push({ path: '/inventory-requisition', query: { warehouse_id: this.form.warehouse_id || 1 } });
      } else {
        this.$emit('go-to-manage');
      }
    },
    async fetchMasters() {
      try {
        const whRes = await axios.get('/api-digital/inventory/warehouses.php');
        if (whRes.data.status === 'success') {
          this.warehouses = (whRes.data.data || []).filter(w => Number(w.active) === 1);
          const queryWh = this.$route?.query?.warehouse_id;
          const targetWh = queryWh || this.warehouseId || localStorage.getItem('selected_warehouse_id');
          if (targetWh) {
            this.form.warehouse_id = parseInt(targetWh);
            this.fetchItems();
          }
        }

        // Fetch departments
        const deptRes = await axios.get('/api-hosoffice/departments.php').catch(() => null);
        if (deptRes && deptRes.data && deptRes.data.status === 'success' && Array.isArray(deptRes.data.data)) {
          this.departments = deptRes.data.data.map(d => ({
            id: d.id || d.HR_DEPARTMENT_SUB_ID,
            name: d.name || d.HR_DEPARTMENT_SUB_NAME
          }));
        } else {
          this.departments = [
            { id: 1, name: 'ห้องตรวจ 1 (OPD)' },
            { id: 2, name: 'ห้องตรวจ 2 (OPD)' },
            { id: 3, name: 'แผนกศัลยกรรม (OR)' },
            { id: 4, name: 'ฝ่ายบริหารงานทั่วไป' },
            { id: 5, name: 'กลุ่มงานประกันสุขภาพ' },
            { id: 6, name: 'กลุ่มงานเทคโนโลยีสารสนเทศ (IT)' },
            { id: 7, name: 'แผนกเภสัชกรรม' }
          ];
        }

        // Fetch external departments (รพ.สต.)
        const extDeptRes = await axios.get('/api-digital/inventory/external_departments.php').catch(() => null);
        if (extDeptRes?.data?.status === 'success' && Array.isArray(extDeptRes.data.data)) {
          this.externalDepartments = extDeptRes.data.data;
        }

        // Fetch Staff List
        const staffRes = await axios.get('/api-hosoffice/get_all_staff.php').catch(() => null);
        if (staffRes && staffRes.data && staffRes.data.status === 'success' && Array.isArray(staffRes.data.data)) {
          this.staffList = staffRes.data.data;
        }

        // Fetch Logged-in User Profile to prefill if applicable
        const profileRes = await axios.get('/api-hosoffice/get_user_profile.php').catch(() => null);
        if (profileRes && profileRes.data && profileRes.data.status === 'success') {
          this.userProfile = profileRes.data;
          const perms = this.userProfile.access_user ? String(this.userProfile.access_user).split(':') : [];
          this.isInventoryAdmin = perms.some(p => ['administrator', 'menu_inventory_admin', 'menu_inventory_manage'].includes(p));

          const uid = this.userProfile.id || this.userProfile.user_id || localStorage.getItem('user_id') || '';
          if (uid && !this.isInventoryAdmin) {
            const myWhRes = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${uid}&is_admin=0`).catch(() => null);
            if (myWhRes?.data?.status === 'success' && Array.isArray(myWhRes.data.data)) {
              this.managedWarehouseIds = myWhRes.data.data.map(w => Number(w.id));
            }
          }
          if (!this.form.requester_name || this.form.requester_name === 'Admin') {
            this.form.requester_name = this.userProfile.fullname || '';
          }
          if (this.userProfile.phone && !this.form.requester_phone) {
            this.form.requester_phone = this.userProfile.phone;
          }
          if (!this.form.department_id && this.userProfile.department) {
            const myDept = this.departments.find(d => 
              (this.userProfile.department_id && d.id == this.userProfile.department_id) ||
              d.name.includes(this.userProfile.department) ||
              this.userProfile.department.includes(d.name)
            );
            if (myDept) {
              this.form.department_id = myDept.id;
              this.form.department_name = myDept.name;
              this.selectedDeptKey = `int:${myDept.id}`;
            } else if (this.userProfile.department_id) {
              this.form.department_id = this.userProfile.department_id;
              this.form.department_name = this.userProfile.department;
              this.selectedDeptKey = `int:${this.userProfile.department_id}`;
            }
          }
        }

        const editIdQuery = this.$route?.query?.edit_id;
        if (editIdQuery) {
          await this.loadRequisitionForEdit(parseInt(editIdQuery));
        }
      } catch (err) {
        console.error(err);
      }
    },
    async loadRequisitionForEdit(reqId) {
      try {
        const res = await axios.get(`/api-digital/inventory/requisition_get.php?id=${reqId}`);
        if (res.data?.status === 'success' && res.data.data) {
          const r = res.data.data;
          if (r.status !== 'PENDING') {
            Swal.fire('ไม่สามารถแก้ไขได้', 'ใบขอเบิกนี้ถูกพิจารณาไปแล้ว', 'warning').then(() => {
              this.handleGoToManage();
            });
            return;
          }
          this.editId = r.id;
          this.editReqNo = r.req_no || '';
          this.form.warehouse_id = parseInt(r.warehouse_id);
          this.form.department_id = r.department_id ? parseInt(r.department_id) : null;
          this.form.department_name = r.department_name || '';
          if (r.department_id) {
            this.selectedDeptKey = `int:${parseInt(r.department_id)}`;
          } else if (r.department_name) {
            const matchedExt = this.externalDepartments.find(
              e => e.name.trim() === String(r.department_name).trim()
            );
            this.selectedDeptKey = matchedExt ? `ext:${matchedExt.id}` : 'custom';
          } else {
            this.selectedDeptKey = '';
          }
          this.form.request_date = r.request_date ? moment(r.request_date).format('YYYY-MM-DDTHH:mm') : this.form.request_date;
          this.form.requester_name = r.requester_name || '';
          this.form.requester_phone = r.requester_phone || '';
          this.form.purpose = r.purpose || '';

          await this.fetchItems();
          this.selectedItems = (r.items || []).map(it => ({
            item_id: Number(it.item_id),
            item_code: it.item_code || '',
            item_name: it.item_name || '',
            unit_name: it.unit_name || 'หน่วย',
            on_hand: Number(it.current_stock || 0),
            qty: Math.max(1, Math.round(Number(it.qty_requested) || 1)),
            item_note: it.item_note || ''
          }));
        }
      } catch (err) {
        console.error('Load edit requisition failed:', err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลใบขอเบิกสำหรับแก้ไขได้', 'error');
      }
    },
    onDeptSelectChange() {
      const key = this.selectedDeptKey || '';
      if (key.startsWith('int:')) {
        const id = parseInt(key.slice(4));
        const d = this.departments.find(x => Number(x.id) === id);
        this.form.department_id = id;
        this.form.department_name = d ? d.name : '';
      } else if (key.startsWith('ext:')) {
        const extId = parseInt(key.slice(4));
        const ext = this.externalDepartments.find(x => Number(x.id) === extId);
        this.form.department_id = null;
        this.form.department_name = ext ? ext.name : '';
        if (!this.editId && this.userProfile?.fullname && this.form.requester_name === this.userProfile.fullname) {
          this.form.requester_name = '';
          this.form.requester_phone = '';
        }
      } else if (key === 'custom') {
        this.form.department_id = null;
        const isInternalName = this.departments.some(d => d.name === this.form.department_name);
        if (isInternalName || this.externalDepartments.some(e => e.name === this.form.department_name)) {
          this.form.department_name = '';
        }
        if (!this.editId && this.userProfile?.fullname && this.form.requester_name === this.userProfile.fullname) {
          this.form.requester_name = '';
          this.form.requester_phone = '';
        }
      } else {
        this.form.department_id = '';
        this.form.department_name = '';
      }
    },
    updateDepartmentName() {
      const d = this.departments.find(x => x.id === this.form.department_id);
      this.form.department_name = d ? d.name : '';
    },
    selectStaffDirectly(staff) {
      this.form.requester_name = staff.FULLNAME;
      if (staff.HR_PHONE && (!this.form.requester_phone || this.form.requester_phone.trim() === '')) {
        this.form.requester_phone = staff.HR_PHONE;
      }
      this.matchDepartmentFromStaff(staff);
    },
    onStaffSelected() {
      const name = (this.form.requester_name || '').trim();
      if (!name) return;
      const staff = this.staffList.find(s => s.FULLNAME.trim() === name);
      if (staff) {
        if (staff.HR_PHONE && (!this.form.requester_phone || this.form.requester_phone.trim() === '')) {
          this.form.requester_phone = staff.HR_PHONE;
        }
        this.matchDepartmentFromStaff(staff);
      }
    },
    matchDepartmentFromStaff(staff) {
      if (this.selectedDeptKey || this.form.department_id) return; // ถ้าเลือกแผนกไว้แล้ว ไม่เปลี่ยนทับ
      const dept = this.departments.find(d => {
        if (staff.HR_DEPARTMENT_SUB_ID && Number(d.id) === Number(staff.HR_DEPARTMENT_SUB_ID)) return true;
        if (staff.HR_DEPARTMENT_SUB_NAME && (d.name.includes(staff.HR_DEPARTMENT_SUB_NAME) || staff.HR_DEPARTMENT_SUB_NAME.includes(d.name))) return true;
        return false;
      });
      if (dept) {
        this.form.department_id = dept.id;
        this.form.department_name = dept.name;
        this.selectedDeptKey = `int:${dept.id}`;
      } else if (staff.HR_DEPARTMENT_SUB_NAME) {
        this.form.department_name = staff.HR_DEPARTMENT_SUB_NAME;
      }
    },
    onWarehouseChange() {
      this.selectedItems = [];
      this.resetCurrentItem();
      this.fetchItems();
    },
    async fetchItems() {
      this.items = [];
      this.searchQuery = '';
      if (!this.form.warehouse_id) return;

      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data.filter(i => i.active === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    isItemInCart(itemId) {
      return this.selectedItems.some(x => Number(x.item_id) === Number(itemId));
    },
    addToCart(mat) {
      if (this.isItemInCart(mat.id)) return;
      if (Number(mat.on_hand) <= 0) {
        Swal.fire('ยอดคงเหลือไม่พอ', `${mat.item_name} ไม่มีสินค้าคงเหลือในคลัง`, 'warning');
        return;
      }
      this.selectedItems.push({
        item_id: mat.id,
        item_code: mat.item_code,
        item_name: mat.item_name,
        unit_name: mat.unit_name || 'หน่วย',
        on_hand: mat.on_hand || 0,
        qty: 1,
        item_note: ''
      });
    },
    incrementCartQty(item) {
      const maxQty = Math.floor(Number(item.on_hand) || 0);
      const nextQty = (Math.round(Number(item.qty) || 0)) + 1;
      if (nextQty > maxQty) {
        item.qty = Math.max(1, maxQty);
        Swal.fire({
          icon: 'warning',
          title: 'ยอดคงเหลือไม่พอ',
          text: `${item.item_name} คงเหลือ ${this.formatQty(maxQty)} ${item.unit_name || 'หน่วย'}`,
          confirmButtonText: 'ตกลง'
        });
        return;
      }
      item.qty = nextQty;
    },
    onCartQtyChange(item) {
      const maxQty = Math.floor(Number(item.on_hand) || 0);
      let val = Math.max(1, Math.round(Number(item.qty) || 1));
      if (val > maxQty) {
        val = Math.max(1, maxQty);
        Swal.fire({
          icon: 'warning',
          title: 'ยอดคงเหลือไม่พอ',
          text: `${item.item_name} คงเหลือ ${this.formatQty(maxQty)} ${item.unit_name || 'หน่วย'}`,
          confirmButtonText: 'ตกลง'
        });
      }
      item.qty = val;
    },
    selectItem(m) {
      this.currentItem.item_id = m.id;
      this.currentItem.item_code = m.item_code;
      this.currentItem.item_name = m.item_name;
      this.currentItem.unit_name = m.unit_name || 'หน่วย';
      this.currentItem.on_hand = m.on_hand || 0;
      this.currentItem.qty = 1;
      this.currentItem.item_note = '';

      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
    },
    addItem() {
      if (!this.currentItem.item_id || this.currentItem.qty <= 0) return;

      // Check if already in selected items
      const existing = this.selectedItems.find(x => x.item_id === this.currentItem.item_id);
      if (existing) {
        existing.qty += this.currentItem.qty;
      } else {
        this.selectedItems.push({ ...this.currentItem });
      }

      this.resetCurrentItem();
      this.searchQuery = '';
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '',
        item_code: '',
        item_name: '',
        unit_name: '',
        on_hand: 0,
        qty: 1,
        item_note: ''
      };
    },
    resetForm() {
      this.selectedItems = [];
      this.resetCurrentItem();
      this.searchQuery = '';
      this.form.purpose = '';
    },
    async submitRequisition() {
      if (this.selectedItems.length === 0) {
        return Swal.fire('เตือน', 'กรุณาระบุรายการพัสดุอย่างน้อย 1 รายการ', 'warning');
      }

      const esc = (s) => String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
      const whObj = this.warehouses.find(w => Number(w.id) === Number(this.form.warehouse_id));
      const whLabel = whObj ? `[${whObj.code}] ${whObj.name}` : '-';
      const rowsHtml = this.selectedItems.map((it, idx) => `
        <tr>
          <td class="text-center text-muted">${idx + 1}</td>
          <td class="text-start">
            <div class="fw-bold text-dark">${esc(it.item_name)}</div>
            <small class="text-muted font-monospace">${esc(it.item_code || '-')}</small>
            ${it.item_note ? `<div class="small text-secondary fst-italic">หมายเหตุ: ${esc(it.item_note)}</div>` : ''}
          </td>
          <td class="text-end fw-bold text-primary text-nowrap">${this.formatQty(it.qty)} ${esc(it.unit_name || 'หน่วย')}</td>
        </tr>
      `).join('');

      const summaryHtml = `
        <div class="text-start small mb-3 p-3 bg-light rounded-3 border">
          <div class="mb-1"><span class="text-muted">คลังที่เบิก:</span> <strong class="text-primary">${esc(whLabel)}</strong></div>
          <div class="mb-1"><span class="text-muted">ผู้ขอเบิก:</span> <strong>${esc(this.form.requester_name)}</strong></div>
          <div><span class="text-muted">หน่วยงาน:</span> <strong>${esc(this.form.department_name || '-')}</strong></div>
        </div>
        <div class="table-responsive border rounded-3" style="max-height: 260px; overflow-y: auto;">
          <table class="table table-sm table-bordered align-middle mb-0 small">
            <thead class="table-light sticky-top">
              <tr>
                <th style="width: 45px;" class="text-center">#</th>
                <th class="text-start">รายการพัสดุ</th>
                <th style="width: 110px;" class="text-end">จำนวน</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
        <div class="mt-2 small text-muted">รวมทั้งหมด <strong>${this.selectedItems.length}</strong> รายการ</div>
      `;

      const confirm = await Swal.fire({
        title: this.editId ? `ยืนยันการบันทึกแก้ไข (${esc(this.editReqNo)})?` : 'ตรวจสอบรายการก่อนส่งใบขอเบิก',
        html: summaryHtml,
        width: '640px',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: this.editId ? '<span class="text-dark fw-bold">ยืนยันบันทึกการแก้ไข</span>' : '<span class="text-dark fw-bold">ยืนยันส่งคำขอ</span>',
        cancelButtonText: 'กลับไปแก้ไข'
      });

      if (!confirm.isConfirmed) return;

      this.submitting = true;
      try {
        const payload = {
          requisition_id: this.editId || undefined,
          warehouse_id: this.form.warehouse_id,
          department_id: this.form.department_id ? parseInt(this.form.department_id) : null,
          department_name: this.form.department_name,
          request_date: this.form.request_date,
          requester_name: this.form.requester_name,
          requester_phone: this.form.requester_phone,
          purpose: this.form.purpose,
          items: this.selectedItems.map(it => ({
            item_id: it.item_id,
            item_code: it.item_code,
            item_name: it.item_name,
            unit_name: it.unit_name,
            qty_requested: it.qty,
            item_note: it.item_note
          }))
        };

        const endpoint = this.editId
          ? '/api-digital/inventory/requisition_update.php'
          : '/api-digital/inventory/requisition_create.php';
        const res = await axios.post(endpoint, payload);
        if (res.data.status === 'success') {
          if (this.editId) {
            await Swal.fire({
              icon: 'success',
              title: 'แก้ไขใบขอเบิกสำเร็จ!',
              html: `บันทึกการแก้ไขคำขอเลขที่ <strong class="text-primary font-monospace">${res.data.req_no}</strong> เรียบร้อยแล้ว`,
              confirmButtonColor: '#ffc107',
              confirmButtonText: '<span class="text-dark fw-bold">กลับหน้ารายการคำขอเบิก</span>'
            });
            this.handleGoToManage();
          } else {
            const result = await Swal.fire({
              icon: 'success',
              title: 'ส่งใบขอเบิกสำเร็จ!',
              html: `ระบบได้บันทึกคำขอเลขที่ <strong class="text-primary font-monospace">${res.data.req_no}</strong> เรียบร้อยแล้ว (สถานะ: รอพิจารณาอนุมัติ)`,
              showCancelButton: true,
              confirmButtonColor: '#ffc107',
              cancelButtonColor: '#6c757d',
              confirmButtonText: '<span class="text-dark fw-bold">ดูรายการคำขอเบิก</span>',
              cancelButtonText: 'สร้างใบขอเบิกใหม่'
            });

            if (result.isConfirmed) {
              this.handleGoToManage();
            } else {
              this.resetForm();
            }
          }
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        const msg = err?.response?.data?.message || 'ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง';
        Swal.fire('ข้อผิดพลาด', msg, 'error');
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>

<style scoped>
.item-select-row {
  cursor: pointer;
  transition: background-color 0.15s;
}
.item-select-row:hover {
  background-color: #f8f9fa;
}
.pointer-events-none {
  pointer-events: none;
}
.hide-arrows::-webkit-outer-spin-button,
.hide-arrows::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.hide-arrows {
  -moz-appearance: textfield;
}
.nav-pill-btn {
  transition: all 0.2s ease;
}
.nav-pill-btn:hover {
  background-color: #e2e8f0 !important;
  color: #0f172a !important;
  transform: translateY(-1px);
}
</style>

