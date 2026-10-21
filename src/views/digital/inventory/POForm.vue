<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-3 px-md-5">

      <!-- Breadcrumb & Top Bar -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
              <li class="breadcrumb-item"><router-link to="/home-backoffice" class="text-decoration-none">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-dashboard" class="text-decoration-none">Inventory Dashboard</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">ระบบสั่งซื้อเวชภัณฑ์ (PO)</li>
            </ol>
          </nav>
          <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center">
              <span class="me-2">📑</span> ระบบสั่งซื้อเวชภัณฑ์ (Purchase Order)
            </h3>
          </div>
          <p class="text-secondary small mb-0 mt-1">
            จัดทำใบสั่งซื้อ, พิมพ์รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง, ติดตามการค้างรับ และค้นหาประวัติเดิม
          </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-outline-secondary rounded-pill fw-medium btn-sm px-3 shadow-sm bg-white" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
          </button>
        </div>
      </div>

      <!-- Navigation Tabs (Image 2 style) -->
      <div class="border-bottom mb-4">
        <ul class="nav nav-tabs border-0 gap-1">
          <li class="nav-item">
            <button 
              class="nav-link custom-tab-btn" 
              :class="{ 'active fw-bold text-primary': activeTab === 'create' }"
              type="button" 
              @click="activeTab = 'create'"
            >
              <i class="bi bi-file-earmark-plus me-1 text-danger"></i> สร้างใบสั่งซื้อใหม่
            </button>
          </li>
          <li class="nav-item">
            <button 
              class="nav-link custom-tab-btn" 
              :class="{ 'active fw-bold text-primary': activeTab === 'list' }"
              type="button" 
              @click="switchToListTab"
            >
              <i class="bi bi-card-checklist me-1 text-primary"></i> ทะเบียนใบสั่งซื้อและติดตามสถานะ
              <span v-if="pos.length > 0" class="badge bg-primary-subtle text-primary ms-1 rounded-pill">{{ pos.length }}</span>
            </button>
          </li>
          <li class="nav-item">
            <button 
              class="nav-link custom-tab-btn" 
              :class="{ 'active fw-bold text-primary': activeTab === 'invs' }"
              type="button" 
              @click="switchToInvsTab"
            >
              <i class="bi bi-bank me-1 text-warning"></i> ประวัติใบสั่งซื้อ (INVS)
            </button>
          </li>
        </ul>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: สร้างใบสั่งซื้อใหม่ (Exact Image 2) -->
      <!-- ========================================== -->
      <div v-show="activeTab === 'create'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4 p-md-5">

            <!-- Edit Notice if applicable -->
            <div v-if="isEdit" class="alert d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4 rounded-3 shadow-xs" :class="form.status === 'CANCELLED' ? 'alert-danger' : 'alert-info'">
              <div class="d-flex align-items-center">
                <i class="bi fs-4 me-2" :class="form.status === 'CANCELLED' ? 'bi-x-octagon-fill text-danger' : 'bi-info-circle-fill text-primary'"></i>
                <div>
                  <strong>กำลังดูใบสั่งซื้อ: {{ form.po_number }}</strong>
                  <span class="badge ms-2" :class="getStatusBadgeClass(form.status)">{{ getStatusText(form.status) }}</span>
                  <div v-if="form.status === 'CANCELLED'" class="small mt-1 text-danger fw-semibold">
                    <i class="bi bi-exclamation-triangle me-1"></i> เอกสารใบสั่งซื้อนี้ถูกยกเลิกแล้ว
                  </div>
                </div>
              </div>
              <div v-if="form.status !== 'CANCELLED'">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" @click="cancelCurrentPO">
                  <i class="bi bi-x-circle me-1"></i> ยกเลิกใบสั่งซื้อนี้
                </button>
              </div>
            </div>

            <form @submit.prevent="savePO">
              
              <!-- 1. ข้อมูลหัวใบสั่งซื้อ -->
              <div class="mb-4">
                <div class="d-flex align-items-center mb-3">
                  <h5 class="fw-bold mb-0 text-dark me-2">1. ข้อมูลหัวใบสั่งซื้อ</h5>
                  <i class="bi bi-link-45deg text-secondary fs-5" title="เชื่อมโยงกับฐานข้อมูลจัดซื้อ"></i>
                </div>

                <div class="row g-3">
                  <!-- วันที่ใบสั่งซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">วันที่ใบสั่งซื้อ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" v-model="form.po_date" required :disabled="isEdit" />
                  </div>

                  <!-- ผู้จำหน่าย -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">ผู้จำหน่าย <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="selectedVendorIndex" @change="onVendorChange" required :disabled="isEdit">
                      <option value="">-- เลือกผู้จำหน่าย --</option>
                      <option v-for="(v, idx) in vendors" :key="idx" :value="idx">
                        {{ v.vendor_name }} ({{ v.vendor_code || '-' }})
                      </option>
                    </select>
                  </div>

                  <!-- วิธีจัดซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">วิธีจัดซื้อ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.buy_method" required :disabled="isEdit">
                      <option v-for="m in buyMethods" :key="m.code" :value="m.code">{{ m.name }}</option>
                    </select>
                  </div>

                  <!-- ประเภทงบประมาณ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">ประเภทงบประมาณ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.budget_type" required :disabled="isEdit">
                      <option v-for="b in budgetTypes" :key="b.code" :value="b.code">{{ b.name }}</option>
                    </select>
                  </div>

                  <!-- รูปแบบจัดซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">รูปแบบจัดซื้อ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.buy_common" required :disabled="isEdit">
                      <option v-for="c in buyCommons" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </select>
                  </div>

                  <!-- เกณฑ์ราคา -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">เกณฑ์ราคา <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.price_criteria" required :disabled="isEdit">
                      <option v-for="p in priceCriterias" :key="p" :value="p">{{ p }}</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Collapsible: ข้อมูลหนังสือรายงานขอซื้อและสัญญาเพิ่มเติม (ถ้ามี) -->
              <div class="card border rounded-3 mb-4 bg-light-subtle">
                <div 
                  class="card-header bg-transparent py-2 px-3 d-flex align-items-center justify-content-between cursor-pointer"
                  @click="showMoreInfo = !showMoreInfo"
                >
                  <div class="d-flex align-items-center gap-2 small fw-semibold text-secondary">
                    <i class="bi" :class="showMoreInfo ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                    <span>ข้อมูลหนังสือรายงานขอซื้อและสัญญาเพิ่มเติม (ถ้ามี)</span>
                  </div>
                  <span class="badge bg-light text-muted border small">{{ showMoreInfo ? 'ย่อข้อมูล' : 'ขยายข้อมูล' }}</span>
                </div>

                <div v-show="showMoreInfo" class="card-body p-3 border-top bg-white">
                  <div class="row g-3">
                    <!-- เลขที่รายงานขอซื้อ/ขอจ้าง -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary d-flex align-items-center">
                        เลขที่รายงานขอซื้อ/ขอจ้าง
                        <i class="bi bi-question-circle ms-1 text-muted" title="เลขที่หนังสือรายงานขอซื้อขอจ้าง เช่น อย 0033.301/132"></i>
                      </label>
                      <input type="text" class="form-control" v-model="form.memo_no" placeholder="อย 0033.301/132" :disabled="isEdit" />
                    </div>

                    <!-- วันที่รายงานขอซื้อ/ขอจ้าง -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">วันที่รายงานขอซื้อ/ขอจ้าง</label>
                      <input type="date" class="form-control" v-model="form.memo_date" :disabled="isEdit" />
                    </div>

                    <!-- กำหนดส่งมอบ (วัน) -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">กำหนดส่งมอบ (วัน)</label>
                      <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" @click="form.delivery_days = Math.max(1, form.delivery_days - 5)" :disabled="isEdit">-</button>
                        <input type="number" class="form-control text-center fw-bold" v-model.number="form.delivery_days" min="1" :disabled="isEdit" />
                        <button class="btn btn-outline-secondary" type="button" @click="form.delivery_days += 5" :disabled="isEdit">+</button>
                      </div>
                    </div>

                    <!-- เลขที่สัญญา / เลขโครงการ (ถ้ามี) -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">เลขที่สัญญา / เลขโครงการ (ถ้ามี)</label>
                      <input type="text" class="form-control" v-model="form.contract_no" placeholder="ระบุเลขที่สัญญาหรือเลขโครงการ e-GP" :disabled="isEdit" />
                    </div>

                    <!-- กรรมการตรวจรับพัสดุ -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">กรรมการตรวจรับพัสดุ</label>
                      <input type="text" class="form-control" v-model="form.committee_name" placeholder="ระบุชื่อกรรมการตรวจรับพัสดุ" :disabled="isEdit" />
                    </div>

                    <!-- บันทึกช่วยจำ / หมายเหตุ -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">บันทึกช่วยจำ / หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.note" placeholder="รายละเอียดหรือเงื่อนไขเพิ่มเติม..." :disabled="isEdit" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- 2. เพิ่มรายการพัสดุที่จะสั่งซื้อ -->
              <div class="mb-4">
                <h5 class="fw-bold mb-3 text-dark">2. เพิ่มรายการพัสดุที่จะสั่งซื้อ</h5>

                <!-- Collapsible: เพิ่มจากรายการที่ต่ำกว่าจุดสั่งซื้อ / ขาดคลัง -->
                <div class="card border rounded-3 mb-3 bg-light-subtle overflow-hidden">
                  <div 
                    class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between cursor-pointer border-bottom-0"
                    @click="toggleLowStock"
                  >
                    <div class="d-flex align-items-center gap-2 small fw-bold text-danger">
                      <i class="bi" :class="showLowStock ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                      <span>💊 เพิ่มจากรายการที่ต่ำกว่าจุดสั่งซื้อ / ขาดคลัง</span>
                      <span v-if="lowStockItems.length > 0" class="badge bg-danger rounded-pill ms-1">{{ lowStockItems.length }} รายการ</span>
                    </div>
                    <span class="badge bg-light text-secondary border small">{{ showLowStock ? 'ซ่อน' : 'เปิดดู' }}</span>
                  </div>

                  <!-- Low stock panel content -->
                  <div v-show="showLowStock" class="card-body p-3 bg-white border-top">
                    <div v-if="loadingLowStock" class="text-center py-3 text-muted">
                      <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> กำลังดึงรายการพัสดุที่ต่ำกว่าเกณฑ์...
                    </div>
                    <div v-else-if="lowStockItems.length === 0" class="text-center py-3 text-muted small">
                      <i class="bi bi-check-circle-fill text-success me-1"></i> ไม่มีรายการพัสดุที่ต่ำกว่าจุดสั่งซื้อในขณะนี้
                    </div>
                    <div v-else class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                      <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                          <tr>
                            <th>รหัส</th>
                            <th>รายการเวชภัณฑ์/พัสดุ</th>
                            <th class="text-end">คงเหลือ</th>
                            <th class="text-end">จุดสั่งซื้อ</th>
                            <th class="text-end">ราคามาตรฐาน</th>
                            <th class="text-center">สั่งซื้อ</th>
                            <th class="text-center">จัดการ</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, idx) in lowStockItems" :key="idx">
                            <td class="fw-bold">{{ item.item_code }}</td>
                            <td>{{ item.item_name }}</td>
                            <td class="text-end text-danger fw-bold">{{ item.on_hand }} {{ item.unit_name }}</td>
                            <td class="text-end text-muted">{{ item.min_stock }}</td>
                            <td class="text-end">{{ item.price.toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                            <td style="width: 110px;">
                              <input type="number" class="form-control form-control-sm text-center" v-model.number="item.suggested_qty" min="1" />
                            </td>
                            <td class="text-center">
                              <button 
                                type="button" 
                                class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2 fw-medium"
                                @click="addLowStockItem(item)"
                              >
                                <i class="bi bi-plus"></i> เพิ่ม
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Dropdown เลือกพัสดุที่จะสั่งซื้อ -->
                <div v-if="!isEdit" class="card border rounded-3 p-3 mb-3 bg-white">
                  <label class="form-label small fw-semibold text-secondary mb-1">เลือกพัสดุ</label>
                  <div class="d-flex gap-2">
                    <div class="flex-grow-1 position-relative">
                      <input 
                        type="text" 
                        class="form-control" 
                        placeholder="พิมพ์ค้นหารหัส หรือชื่อเวชภัณฑ์/พัสดุเพื่อเลือก..."
                        v-model="materialSearchQuery"
                        @input="filterMaterialList"
                        @focus="showMaterialDropdown = true"
                      />
                      <!-- Auto-complete dropdown -->
                      <div 
                        v-if="showMaterialDropdown && filteredMaterials.length > 0" 
                        class="position-absolute bg-white shadow-lg rounded-3 border w-100 mt-1 overflow-auto"
                        style="max-height: 250px; z-index: 1050;"
                      >
                        <div 
                          v-for="mat in filteredMaterials" 
                          :key="mat.item_code" 
                          class="p-2 border-bottom cursor-pointer hover-bg d-flex justify-content-between align-items-center"
                          @click="selectMaterial(mat)"
                        >
                          <div>
                            <span class="badge bg-secondary-subtle text-secondary me-2">{{ mat.item_code }}</span>
                            <span class="fw-medium text-dark">{{ mat.item_name }}</span>
                            <span class="text-muted small ms-2">({{ mat.unit_name }})</span>
                          </div>
                          <div class="text-end small">
                            <span class="text-success fw-bold">{{ Number(mat.price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</span>
                            <span class="text-muted ms-2">(คงเหลือ: {{ mat.on_hand }})</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" @click="fetchMaterials" title="รีเฟรชรายการ">
                      <i class="bi bi-arrow-repeat"></i>
                    </button>
                  </div>
                </div>

                <!-- Table of items in PO -->
                <div class="table-responsive border rounded-3 bg-white">
                  <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light small text-secondary">
                      <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th style="width: 140px;">รหัส</th>
                        <th>รายการพัสดุ</th>
                        <th style="width: 130px;" class="text-center">จำนวนที่สั่งซื้อ <span class="text-danger">*</span></th>
                        <th style="width: 110px;" class="text-center">หน่วยนับ</th>
                        <th style="width: 150px;" class="text-end">ราคาประเมิน/หน่วย <span class="text-danger">*</span></th>
                        <th style="width: 160px;" class="text-end">รวมเงิน (โดยประมาณ)</th>
                        <th v-if="!isEdit" style="width: 60px;" class="text-center">ลบ</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="form.items.length === 0">
                        <td :colspan="isEdit ? 7 : 8" class="text-center py-5 text-muted">
                          <i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                          <span>ยังไม่มีรายการพัสดุที่จะสั่งซื้อ (เลือกพัสดุจากช่องด้านบน หรือเลือกจากจุดสั่งซื้อ)</span>
                        </td>
                      </tr>
                      <tr v-for="(item, index) in form.items" :key="index">
                        <td class="text-center text-muted small">{{ index + 1 }}</td>
                        <td class="fw-bold text-secondary small">{{ item.item_code || '-' }}</td>
                        <td>
                          <div class="fw-medium text-dark">{{ item.item_name }}</div>
                        </td>
                        <td>
                          <input 
                            type="number" 
                            class="form-control form-control-sm text-center fw-bold" 
                            v-model.number="item.qty_ordered" 
                            min="1" 
                            required 
                            :disabled="isEdit"
                          />
                        </td>
                        <td class="text-center text-muted small">
                          <input 
                            v-if="!isEdit && !item.unit_name" 
                            type="text" 
                            class="form-control form-control-sm text-center" 
                            v-model="item.unit_name" 
                            placeholder="หน่วย"
                          />
                          <span v-else>{{ item.unit_name || 'หน่วย' }}</span>
                        </td>
                        <td>
                          <input 
                            type="number" 
                            class="form-control form-control-sm text-end" 
                            v-model.number="item.unit_price" 
                            min="0" 
                            step="0.01" 
                            required 
                            :disabled="isEdit"
                          />
                        </td>
                        <td class="text-end fw-bold text-dark">
                          {{ (Number(item.qty_ordered || 0) * Number(item.unit_price || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                        </td>
                        <td v-if="!isEdit" class="text-center">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-danger border-0 rounded-circle"
                            @click="removeItem(index)"
                            title="ลบรายการ"
                          >
                            <i class="bi bi-trash3-fill"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="table-light">
                      <tr>
                        <td :colspan="isEdit ? 5 : 6" class="text-end fw-bold py-3 text-secondary">
                          ยอดรวมประเมินทั้งสิ้น :
                        </td>
                        <td class="text-end fw-bold py-3 text-danger fs-5" colspan="2">
                          {{ totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <!-- Action Buttons (Bottom) -->
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 pt-3 border-top">
                <div class="d-flex gap-2">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-medium"
                    @click="activeTab = 'list'"
                  >
                    <i class="bi bi-arrow-left me-1"></i> กลับหน้ารายการ
                  </button>

                  <button 
                    v-if="isEdit && form.status !== 'CANCELLED'" 
                    type="button" 
                    class="btn btn-outline-danger rounded-pill px-4 shadow-sm fw-medium"
                    @click="cancelCurrentPO"
                  >
                    <i class="bi bi-x-circle me-1"></i> ยกเลิกใบสั่งซื้อนี้
                  </button>
                </div>

                <div class="d-flex gap-2 flex-wrap justify-content-end">
                  <!-- Print Attachment (รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง) -->
                  <button 
                    type="button" 
                    class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-medium"
                    @click="openPrintMemoModal"
                    :disabled="form.items.length === 0"
                  >
                    <i class="bi bi-printer me-1"></i> พิมพ์รายละเอียดแนบท้ายรายงาน
                  </button>

                  <!-- Print PO Document -->
                  <button 
                    type="button" 
                    class="btn btn-outline-success rounded-pill px-3 shadow-sm fw-medium"
                    @click="openPrintPOModal"
                    :disabled="form.items.length === 0"
                  >
                    <i class="bi bi-file-earmark-pdf me-1"></i> พิมพ์ใบสั่งซื้อ (PO)
                  </button>

                  <!-- Save Button -->
                  <button 
                    v-if="!isEdit" 
                    type="submit" 
                    class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold"
                    :disabled="saving || form.items.length === 0"
                  >
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-download me-1"></i> บันทึกใบขอซื้อ/สั่งซื้อ
                  </button>
                </div>
              </div>

            </form>

          </div>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- TAB 2: ทะเบียนใบสั่งซื้อและติดตามสถานะ (List & Track)     -->
      <!-- ======================================================= -->
      <div v-show="activeTab === 'list'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white p-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
              <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-card-checklist text-primary me-2"></i>ทะเบียนใบสั่งซื้อและติดตามสถานะ
              </h5>
              <p class="text-muted small mb-0 mt-1">ติดตามรายการใบสั่งซื้อ ค้างรับ ตรวจรับเข้าคลัง และพิมพ์เอกสารราชการ</p>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-danger rounded-pill shadow-sm fw-medium px-3" @click="newPO">
                <i class="bi bi-plus-lg me-1"></i> เปิดใบสั่งซื้อใหม่
              </button>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="ค้นหาเลขที่ PO, ชื่อผู้จำหน่าย, เลขที่รายงานขอซื้อ..." 
                    v-model="listFilter.search"
                    @input="fetchPOs"
                  />
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="listFilter.status" @change="fetchPOs">
                  <option value="">-- ทุกสถานะ --</option>
                  <option value="ORDERED">สั่งซื้อแล้ว (รอรับของ)</option>
                  <option value="PARTIAL_RECEIVED">รับของบางส่วน</option>
                  <option value="COMPLETED">รับของครบถ้วน</option>
                  <option value="CANCELLED">ยกเลิก</option>
                </select>
              </div>
              <div class="col-md-4 text-end">
                <button class="btn btn-outline-secondary rounded-pill px-3" @click="fetchPOs">
                  <i class="bi bi-arrow-repeat me-1"></i> รีเฟรช
                </button>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive border rounded-3 bg-white">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                  <tr>
                    <th class="ps-3">เลขที่เอกสาร PO</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>ผู้จำหน่าย</th>
                    <th>วิธีจัดซื้อ / งบ</th>
                    <th class="text-end">ยอดเงินรวม</th>
                    <th class="text-center">จำนวนรายการ</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end pe-3">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingPOs">
                    <td colspan="8" class="text-center py-5">
                      <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลทะเบียน...
                    </td>
                  </tr>
                  <tr v-else-if="pos.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                      <span>ยังไม่มีรายการใบสั่งซื้อในระบบ</span>
                    </td>
                  </tr>
                  <tr v-for="po in pos" :key="po.id">
                    <td class="ps-3 fw-bold text-primary">{{ po.po_number }}</td>
                    <td class="small">{{ formatDate(po.po_date) }}</td>
                    <td>
                      <div class="fw-semibold text-dark">{{ po.display_vendor_name || po.vendor_name || '-' }}</div>
                      <div class="small text-muted" v-if="po.memo_no">เลขที่รายงาน: {{ po.memo_no }}</div>
                    </td>
                    <td class="small text-muted">
                      <div>{{ po.buy_method === '13' ? 'เฉพาะเจาะจง' : po.buy_method }}</div>
                      <div class="badge bg-light text-secondary border">{{ po.budget_type === '30' ? 'เงินบำรุง' : po.budget_type }}</div>
                    </td>
                    <td class="text-end fw-bold text-dark">
                      {{ Number(po.total_cost || po.total_amount || 0).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿
                    </td>
                    <td class="text-center small">{{ po.item_count || 0 }} รายการ</td>
                    <td class="text-center">
                      <span class="badge" :class="getStatusBadgeClass(po.status)">
                        {{ getStatusText(po.status) }}
                      </span>
                    </td>
                    <td class="text-end pe-3">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" @click="viewPODetails(po)" title="ดูรายละเอียด">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" @click="printExistingPO(po)" title="พิมพ์เอกสาร">
                          <i class="bi bi-printer"></i>
                        </button>
                        <button 
                          v-if="['ORDERED', 'PARTIAL_RECEIVED'].includes(po.status)"
                          class="btn btn-outline-success" 
                          @click="$router.push(`/inventory-receive?po_id=${po.id}`)"
                          title="ไปหน้ารับเข้าคลัง"
                        >
                          <i class="bi bi-box-arrow-in-down"></i>
                        </button>
                        <button 
                          v-if="['ORDERED', 'PARTIAL_RECEIVED'].includes(po.status)"
                          class="btn btn-outline-danger" 
                          @click="cancelPO(po)"
                          title="ยกเลิกใบสั่งซื้อ"
                        >
                          <i class="bi bi-x-circle"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- TAB 3: ประวัติใบสั่งซื้อ (INVS) - SQL Server History   -->
      <!-- ======================================================= -->
      <div v-show="activeTab === 'invs'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white p-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
              <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-bank text-warning me-2"></i>ประวัติใบสั่งซื้อจากระบบโรงพยาบาลเดิม (INVS MS_PO)
              </h5>
              <p class="text-muted small mb-0 mt-1">สืบค้นประวัติการสั่งซื้อเวชภัณฑ์, ร้านค้า, ราคาครั้งก่อน จากฐานข้อมูล INVS</p>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-secondary rounded-pill px-3" @click="fetchInvsPOs">
                <i class="bi bi-arrow-repeat me-1"></i> โหลดข้อมูลใหม่
              </button>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <div class="input-group">
                  <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="ค้นหาเลขที่ PO, ร้านค้า, เลขที่รายงาน..." 
                    v-model="invsFilter.search"
                    @keyup.enter="fetchInvsPOs"
                  />
                  <button class="btn btn-primary" type="button" @click="fetchInvsPOs">ค้นหา</button>
                </div>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive border rounded-3 bg-white">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                  <tr>
                    <th class="ps-3">เลขที่ PO (INVS)</th>
                    <th>วันที่สั่งซื้อ</th>
                    <th>บริษัทผู้จำหน่าย</th>
                    <th>วิธีจัดซื้อ / งบประมาณ</th>
                    <th class="text-end">วงเงินจัดซื้อ (บาท)</th>
                    <th class="text-center">รายการ</th>
                    <th>กรรมการตรวจรับ</th>
                    <th class="text-end pe-3">รายการพัสดุ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingInvs">
                    <td colspan="8" class="text-center py-5">
                      <div class="spinner-border spinner-border-sm text-warning me-2"></div> กำลังดึงประวัติใบสั่งซื้อจาก INVS SQL Server...
                    </td>
                  </tr>
                  <tr v-else-if="invsPOs.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                      <span>ไม่พบข้อมูลประวัติใบสั่งซื้อใน INVS</span>
                    </td>
                  </tr>
                  <tr v-for="invs in invsPOs" :key="invs.po_no">
                    <td class="ps-3 fw-bold text-dark">{{ invs.po_no }}</td>
                    <td class="small">{{ invs.po_date }}</td>
                    <td>
                      <div class="fw-semibold text-dark">{{ invs.vendor_name }}</div>
                      <div class="small text-muted" v-if="invs.req_app_no">รายงานขอซื้อ: {{ invs.req_app_no }}</div>
                    </td>
                    <td class="small text-muted">
                      <div>{{ invs.buy_method }}</div>
                      <div class="badge bg-light text-secondary border">{{ invs.budget_type }}</div>
                    </td>
                    <td class="text-end fw-bold text-dark">
                      {{ Number(invs.total_cost).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿
                    </td>
                    <td class="text-center small">{{ invs.total_item }} รายการ</td>
                    <td class="small text-muted">{{ invs.aic_name || '-' }}</td>
                    <td class="text-end pe-3">
                      <button class="btn btn-sm btn-outline-warning rounded-pill px-3" @click="viewInvsItems(invs)">
                        <i class="bi bi-list-ul me-1"></i> ดูรายการ
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- MODAL: รายการพัสดุในใบสั่งซื้อ INVS                     -->
      <!-- ======================================================= -->
      <div v-if="showInvsModal" class="modal-backdrop fade show"></div>
      <div v-if="showInvsModal" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-3">
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-receipt text-warning me-2"></i>รายการพัสดุในใบสั่งซื้อ {{ selectedInvsPO?.po_no }}
              </h5>
              <button type="button" class="btn-close" @click="showInvsModal = false"></button>
            </div>
            <div class="modal-body p-4">
              <div class="mb-3 p-3 bg-light rounded-3 small">
                <div><strong>ผู้จำหน่าย:</strong> {{ selectedInvsPO?.vendor_name }}</div>
                <div><strong>วันที่สั่งซื้อ:</strong> {{ selectedInvsPO?.po_date }} | <strong>วงเงิน:</strong> {{ Number(selectedInvsPO?.total_cost).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} บาท</div>
              </div>

              <div v-if="loadingInvsItems" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-warning me-2"></div> กำลังโหลดรายการเวชภัณฑ์...
              </div>
              <div v-else class="table-responsive">
                <table class="table table-bordered table-sm align-middle small mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center" style="width: 50px;">#</th>
                      <th>รหัส</th>
                      <th>รายการเวชภัณฑ์</th>
                      <th class="text-end">จำนวนสั่ง</th>
                      <th class="text-center">หน่วย</th>
                      <th class="text-end">ราคา/หน่วย</th>
                      <th class="text-end">รวมเงิน</th>
                      <th class="text-center">สถานะรับ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in invsItems" :key="idx">
                      <td class="text-center">{{ idx + 1 }}</td>
                      <td class="fw-bold">{{ it.item_code }}</td>
                      <td>{{ it.item_name }}</td>
                      <td class="text-end fw-bold">{{ it.qty_ordered }}</td>
                      <td class="text-center">{{ it.unit_name }}</td>
                      <td class="text-end">{{ Number(it.unit_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      <td class="text-end fw-bold">{{ Number(it.total_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      <td class="text-center">
                        <span class="badge" :class="it.rcv_flag === 'รับครบแล้ว' ? 'bg-success' : 'bg-warning text-dark'">
                          {{ it.rcv_flag }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer border-top p-3">
              <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showInvsModal = false">ปิด</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- MODAL: พิมพ์เอกสาร (Print Preview Modal)                 -->
      <!-- ======================================================= -->
      <div v-if="showPrintModal" class="modal-backdrop fade show"></div>
      <div v-if="showPrintModal" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-3 d-flex justify-content-between align-items-center">
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-printer me-2"></i>{{ printDocType === 'po' ? 'พิมพ์ใบสั่งซื้อ (Purchase Order)' : 'พิมพ์รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง' }}
              </h5>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary rounded-pill px-3 fw-bold" @click="executePrint">
                  <i class="bi bi-printer-fill me-1"></i> สั่งพิมพ์
                </button>
                <button type="button" class="btn-close" @click="showPrintModal = false"></button>
              </div>
            </div>
            <div class="modal-body p-4 bg-light">
              
              <!-- Printable Area (A4 layout) -->
              <div id="printable-area" class="bg-white p-5 mx-auto rounded-3 shadow-sm" style="max-width: 210mm; min-height: 297mm; font-family: 'Sarabun', sans-serif;">
                
                <!-- DOCUMENT TYPE 1: ใบสั่งซื้อ (PO) -->
                <div v-if="printDocType === 'po'">
                  <div class="text-center mb-4">
                    <h2 class="fw-bold fs-4">ใบสั่งซื้อ (Purchase Order)</h2>
                    <div class="text-muted small">โรงพยาบาลชานุมาน อำเภอชานุมาน จังหวัดอำนาจเจริญ</div>
                  </div>

                  <div class="row mb-4 small">
                    <div class="col-6">
                      <div><strong>ผู้ขาย / ผู้จำหน่าย:</strong> {{ form.vendor_name }}</div>
                      <div><strong>ที่อยู่:</strong> {{ form.vendor_address || 'ตามทะเบียนการค้า' }}</div>
                      <div><strong>โทรศัพท์:</strong> {{ form.vendor_phone || '-' }}</div>
                      <div><strong>เลขประจำตัวผู้เสียภาษี:</strong> {{ form.vendor_tax_id || '-' }}</div>
                    </div>
                    <div class="col-6">
                      <div><strong>ใบสั่งซื้อเลขที่:</strong> {{ form.po_number || 'กำลังจัดทำ' }}</div>
                      <div><strong>วันที่:</strong> {{ formatThaiDateFull(form.po_date) }}</div>
                      <div><strong>วิธีจัดซื้อ:</strong> {{ getBuyMethodName(form.buy_method) }}</div>
                      <div><strong>งบประมาณ:</strong> {{ getBudgetName(form.budget_type) }}</div>
                    </div>
                  </div>

                  <div class="mb-3 small">
                    ตามที่ <strong>{{ form.vendor_name }}</strong> ได้เสนอราคาไว้ต่อโรงพยาบาลชานุมาน ซึ่งได้รับราคาและตกลงซื้อตามรายการดังต่อไปนี้
                  </div>

                  <table class="table table-bordered table-sm align-middle small mb-4">
                    <thead class="table-light text-center">
                      <tr>
                        <th style="width: 8%;">ลำดับ</th>
                        <th style="width: 15%;">รหัสพัสดุ</th>
                        <th>รายการเวชภัณฑ์ / พัสดุ</th>
                        <th style="width: 12%;">จำนวน</th>
                        <th style="width: 10%;">หน่วย</th>
                        <th style="width: 15%;">ราคาต่อหน่วย</th>
                        <th style="width: 18%;">จำนวนเงิน (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(it, i) in form.items" :key="i">
                        <td class="text-center">{{ i + 1 }}</td>
                        <td class="text-center">{{ it.item_code || '-' }}</td>
                        <td>{{ it.item_name }}</td>
                        <td class="text-center fw-bold">{{ it.qty_ordered }}</td>
                        <td class="text-center">{{ it.unit_name || 'หน่วย' }}</td>
                        <td class="text-end">{{ Number(it.unit_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                        <td class="text-end fw-bold">{{ (Number(it.qty_ordered) * Number(it.unit_price)).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      </tr>
                      <tr class="fw-bold">
                        <td colspan="5" class="text-center align-middle">
                          ({{ thaiBahtText(totalAmount) }})
                        </td>
                        <td class="text-end">รวมเป็นเงินทั้งสิ้น</td>
                        <td class="text-end text-danger fs-6">{{ totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <div class="small mb-4 lh-lg">
                    <div><strong>การซื้ออยู่ภายใต้เงื่อนไขต่อไปนี้:</strong></div>
                    <div>๑. กำหนดส่งมอบภายใน {{ form.delivery_days }} วัน นับถัดจากวันที่ได้รับใบสั่งซื้อ</div>
                    <div>๒. สถานที่ส่งมอบ: กลุ่มงานเภสัชกรรมและคุ้มครองผู้บริโภค / คลังพัสดุ โรงพยาบาลชานุมาน</div>
                    <div>๓. สงวนสิทธิ์ค่าปรับกรณีส่งมอบเกินกำหนด คิดเป็นรายวันในอัตราร้อยละ ๐.๒๐ ของราคาสิ่งของที่ยังไม่ได้รับมอบ</div>
                  </div>

                  <div class="row text-center mt-5 small">
                    <div class="col-6">
                      <div class="mb-4">ลงชื่อ..........................................................ผู้ขาย</div>
                      <div>(..........................................................)</div>
                      <div class="text-muted mt-1">วันที่......../......../........</div>
                    </div>
                    <div class="col-6">
                      <div class="mb-4">ลงชื่อ..........................................................ผู้สั่งซื้อ</div>
                      <div>(นายชาญชัย ศรีแก้ว)</div>
                      <div class="text-muted mt-1">ผู้อำนวยการโรงพยาบาลชานุมาน</div>
                    </div>
                  </div>
                </div>

                <!-- DOCUMENT TYPE 2: รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง -->
                <div v-else>
                  <div class="text-center mb-4">
                    <h2 class="fw-bold fs-5">รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง</h2>
                    <div class="small">ตามหนังสือรายงานขอซื้อ/ขอจ้าง เลขที่ {{ form.memo_no || 'อย 0033.301/132' }} ลงวันที่ {{ formatThaiDateFull(form.memo_date) }}</div>
                  </div>

                  <div class="mb-3 small">
                    โรงพยาบาลชานุมาน มีความประสงค์จะขอซื้อเวชภัณฑ์/พัสดุ โดยวิธีเฉพาะเจาะจง ตามรายการดังต่อไปนี้
                  </div>

                  <table class="table table-bordered table-sm align-middle small mb-4">
                    <thead class="table-light text-center">
                      <tr>
                        <th style="width: 8%;">ลำดับ</th>
                        <th>รายการพัสดุที่จะซื้อ</th>
                        <th style="width: 12%;">จำนวน</th>
                        <th style="width: 10%;">หน่วยนับ</th>
                        <th style="width: 18%;">ราคาประเมิน/หน่วย</th>
                        <th style="width: 20%;">วงเงินที่จะซื้อ (บาท)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(it, i) in form.items" :key="i">
                        <td class="text-center">{{ i + 1 }}</td>
                        <td>
                          <div>{{ it.item_name }}</div>
                          <div class="text-muted small">รหัส: {{ it.item_code }}</div>
                        </td>
                        <td class="text-center fw-bold">{{ it.qty_ordered }}</td>
                        <td class="text-center">{{ it.unit_name || 'หน่วย' }}</td>
                        <td class="text-end">{{ Number(it.unit_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                        <td class="text-end fw-bold">{{ (Number(it.qty_ordered) * Number(it.unit_price)).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      </tr>
                      <tr class="fw-bold">
                        <td colspan="4" class="text-center align-middle">
                          ({{ thaiBahtText(totalAmount) }})
                        </td>
                        <td class="text-end">รวมวงเงินทั้งสิ้น</td>
                        <td class="text-end text-danger">{{ totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <div class="small mb-4">
                    <div><strong>เกณฑ์ราคาที่ใช้:</strong> {{ form.price_criteria || 'ราคาครั้งก่อน' }}</div>
                    <div><strong>กำหนดเวลาส่งมอบ:</strong> ภายใน {{ form.delivery_days }} วัน นับถัดจากวันที่ลงนามในสัญญา/ใบสั่งซื้อ</div>
                    <div><strong>กรรมการตรวจรับพัสดุ:</strong> {{ form.committee_name || 'คณะกรรมการตรวจรับพัสดุที่ได้รับแต่งตั้ง' }}</div>
                  </div>

                  <div class="row text-center mt-5 small">
                    <div class="col-6">
                      <div class="mb-4">ลงชื่อ..........................................................เจ้าหน้าที่</div>
                      <div>({{ form.purchaser_name || 'เจ้าหน้าที่พัสดุ' }})</div>
                    </div>
                    <div class="col-6">
                      <div class="mb-4">ลงชื่อ..........................................................หัวหน้าเจ้าหน้าที่</div>
                      <div>(นายสุริยา จันทรา)</div>
                    </div>
                  </div>
                </div>

              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'POForm',
  data() {
    return {
      activeTab: 'create', // 'create', 'list', 'invs'
      isEdit: false,
      saving: false,
      showMoreInfo: true,
      showLowStock: false,
      loadingLowStock: false,
      showMaterialDropdown: false,
      materialSearchQuery: '',
      
      // Meta options
      vendors: [],
      selectedVendorIndex: '',
      buyMethods: [],
      budgetTypes: [],
      buyCommons: [],
      priceCriterias: [],
      
      // Materials Catalog
      materials: [],
      filteredMaterials: [],
      lowStockItems: [],

      // Main PO Form Data (matching Image 2)
      form: {
        id: 0,
        pr_id: null,
        po_number: '',
        po_date: new Date().toISOString().split('T')[0],
        vendor_id: null,
        vendor_code: '',
        vendor_name: '',
        vendor_address: '',
        vendor_phone: '',
        vendor_tax_id: '',
        buy_method: '13',
        budget_type: '30',
        buy_common: '0',
        price_criteria: 'ราคาครั้งก่อน',
        memo_no: 'อย 0033.301/132',
        memo_date: new Date().toISOString().split('T')[0],
        delivery_days: 30,
        contract_no: '',
        committee_name: '',
        purchaser_name: '',
        note: '',
        status: 'ORDERED',
        items: []
      },

      // Tab 2: PO List Data
      pos: [],
      loadingPOs: false,
      listFilter: {
        search: '',
        status: ''
      },

      // Tab 3: INVS Data
      invsPOs: [],
      loadingInvs: false,
      invsFilter: {
        search: ''
      },
      showInvsModal: false,
      selectedInvsPO: null,
      invsItems: [],
      loadingInvsItems: false,

      // Print Modal
      showPrintModal: false,
      printDocType: 'po' // 'po' or 'memo'
    };
  },
  computed: {
    totalAmount() {
      return this.form.items.reduce((sum, item) => {
        return sum + (Number(item.qty_ordered || 0) * Number(item.unit_price || 0));
      }, 0);
    }
  },
  async mounted() {
    await this.fetchMeta();
    await this.fetchUserProfile();
    await this.fetchMaterials();

    // Check query params
    const id = this.$route.query.id;
    const tab = this.$route.query.tab;

    if (id) {
      this.isEdit = true;
      this.activeTab = 'create';
      await this.loadPO(id);
    } else if (tab === 'list') {
      this.activeTab = 'list';
      await this.fetchPOs();
    } else if (tab === 'invs') {
      this.activeTab = 'invs';
      await this.fetchInvsPOs();
    }
  },
  methods: {
    async fetchMeta() {
      try {
        const res = await axios.get('/api-digital/inventory/po_meta.php');
        if (res.data.status === 'success') {
          const d = res.data.data;
          this.vendors = d.vendors || [];
          this.buyMethods = d.buy_methods || [];
          this.budgetTypes = d.budget_types || [];
          this.buyCommons = d.buy_commons || [];
          this.priceCriterias = d.price_criterias || [];
          if (!this.isEdit) {
            this.form.memo_no = d.default_memo_no || 'อย 0033.301/132';
            this.form.delivery_days = d.default_delivery_days || 30;
          }
        }
      } catch (err) {
        console.error('Error fetching PO meta:', err);
      }
    },
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data.status === 'success' && res.data.fullname) {
          this.form.purchaser_name = res.data.fullname;
        }
      } catch (err) {}
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/inventory/po_items_search.php?limit=100');
        if (res.data.status === 'success') {
          this.materials = res.data.data || [];
          this.filteredMaterials = [...this.materials];
        }
      } catch (err) {
        console.error('Error fetching materials:', err);
      }
    },
    filterMaterialList() {
      this.showMaterialDropdown = true;
      const q = (this.materialSearchQuery || '').toLowerCase().trim();
      if (!q) {
        this.filteredMaterials = [...this.materials];
        return;
      }
      this.filteredMaterials = this.materials.filter(m => 
        (m.item_code || '').toLowerCase().includes(q) || 
        (m.item_name || '').toLowerCase().includes(q)
      );
    },
    selectMaterial(mat) {
      // Check if already in items
      const existing = this.form.items.find(i => i.item_code === mat.item_code);
      if (existing) {
        existing.qty_ordered = Number(existing.qty_ordered || 0) + 1;
      } else {
        this.form.items.push({
          item_id: mat.item_id || 0,
          item_code: mat.item_code,
          item_name: mat.item_name,
          unit_name: mat.unit_name,
          qty_ordered: 1,
          unit_price: Number(mat.price) || 0
        });
      }
      this.showMaterialDropdown = false;
      this.materialSearchQuery = '';
    },
    removeItem(index) {
      this.form.items.splice(index, 1);
    },
    onVendorChange() {
      if (this.selectedVendorIndex === '') {
        this.form.vendor_id = null;
        this.form.vendor_code = '';
        this.form.vendor_name = '';
        this.form.vendor_address = '';
        this.form.vendor_phone = '';
        this.form.vendor_tax_id = '';
        return;
      }
      const v = this.vendors[this.selectedVendorIndex];
      if (v) {
        this.form.vendor_id = v.id || null;
        this.form.vendor_code = v.vendor_code || '';
        this.form.vendor_name = v.vendor_name || '';
        this.form.vendor_address = v.address || '';
        this.form.vendor_phone = v.phone || '';
        this.form.vendor_tax_id = v.tax_id || '';
      }
    },
    async toggleLowStock() {
      this.showLowStock = !this.showLowStock;
      if (this.showLowStock && this.lowStockItems.length === 0) {
        this.loadingLowStock = true;
        try {
          const res = await axios.get('/api-digital/inventory/po_low_stock.php');
          if (res.data.status === 'success') {
            this.lowStockItems = res.data.data || [];
          }
        } catch (err) {
          console.error('Error fetching low stock:', err);
        } finally {
          this.loadingLowStock = false;
        }
      }
    },
    addLowStockItem(item) {
      const existing = this.form.items.find(i => i.item_code === item.item_code);
      const addQty = Number(item.suggested_qty) || 1;
      if (existing) {
        existing.qty_ordered = Number(existing.qty_ordered || 0) + addQty;
      } else {
        this.form.items.push({
          item_id: item.item_id || 0,
          item_code: item.item_code,
          item_name: item.item_name,
          unit_name: item.unit_name,
          qty_ordered: addQty,
          unit_price: Number(item.price) || 0
        });
      }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `เพิ่ม ${item.item_name} แล้ว`,
        showConfirmButton: false,
        timer: 1200
      });
    },
    newPO() {
      this.isEdit = false;
      this.form = {
        id: 0,
        pr_id: null,
        po_number: '',
        po_date: new Date().toISOString().split('T')[0],
        vendor_id: null,
        vendor_code: '',
        vendor_name: '',
        vendor_address: '',
        vendor_phone: '',
        vendor_tax_id: '',
        buy_method: '13',
        budget_type: '30',
        buy_common: '0',
        price_criteria: 'ราคาครั้งก่อน',
        memo_no: 'อย 0033.301/132',
        memo_date: new Date().toISOString().split('T')[0],
        delivery_days: 30,
        contract_no: '',
        committee_name: '',
        purchaser_name: this.form.purchaser_name || 'เจ้าหน้าที่พัสดุ',
        note: '',
        status: 'ORDERED',
        items: []
      };
      this.selectedVendorIndex = '';
      this.activeTab = 'create';
    },
    async savePO() {
      if (!this.form.vendor_name) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกผู้จำหน่าย', 'warning');
        return;
      }
      if (this.form.items.length === 0) {
        Swal.fire('แจ้งเตือน', 'กรุณาเพิ่มรายการพัสดุที่ต้องการสั่งซื้อ', 'warning');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          ...this.form,
          po_date: this.form.po_date + ' 00:00:00',
          memo_date: this.form.memo_date ? this.form.memo_date : this.form.po_date
        };

        const res = await axios.post('/api-digital/inventory/po_save.php', payload);
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message || 'บันทึกใบสั่งซื้อเรียบร้อยแล้ว',
            timer: 1600,
            showConfirmButton: false
          });
          this.switchToListTab();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถบันทึกได้', 'error');
        }
      } catch (err) {
        console.error('Error saving PO:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
      } finally {
        this.saving = false;
      }
    },
    async loadPO(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/po_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const d = res.data.data;
          d.po_date = d.po_date ? d.po_date.split(' ')[0] : '';
          d.memo_date = d.memo_date ? d.memo_date.split(' ')[0] : d.po_date;
          this.form = d;
          
          // Match vendor in dropdown
          const vIdx = this.vendors.findIndex(v => v.vendor_name === d.vendor_name || v.id == d.vendor_id);
          if (vIdx >= 0) {
            this.selectedVendorIndex = vIdx;
          }
        }
      } catch (err) {
        console.error('Error loading PO:', err);
      }
    },
    switchToListTab() {
      this.activeTab = 'list';
      this.fetchPOs();
    },
    async fetchPOs() {
      this.loadingPOs = true;
      try {
        const params = new URLSearchParams();
        if (this.listFilter.search) params.append('search', this.listFilter.search);
        if (this.listFilter.status) params.append('status', this.listFilter.status);

        const res = await axios.get(`/api-digital/inventory/po_list.php?${params.toString()}`);
        if (res.data.status === 'success') {
          this.pos = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching POs:', err);
      } finally {
        this.loadingPOs = false;
      }
    },
    viewPODetails(po) {
      this.isEdit = true;
      this.activeTab = 'create';
      this.loadPO(po.id);
    },
    printExistingPO(po) {
      this.loadPO(po.id).then(() => {
        this.openPrintPOModal();
      });
    },
    async cancelCurrentPO() {
      if (!this.form.id) return;
      const { value: reason, isConfirmed } = await Swal.fire({
        title: 'ยืนยันการยกเลิกใบสั่งซื้อ?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการยกเลิกใบสั่งซื้อเลขที่ <strong class="text-danger">${this.form.po_number}</strong> ?<br><small class="text-muted">*หากใบสั่งซื้อนี้มาจากใบขอซื้อ (PR) ใบขอซื้อจะถูกปลดล็อกกลับมาให้ใช้งานใหม่ได้</small>`,
        input: 'textarea',
        inputPlaceholder: 'ระบุเหตุผลการยกเลิก (ถ้ามี)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> ยืนยันยกเลิกใบสั่งซื้อ',
        cancelButtonText: 'ปิด'
      });

      if (!isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/po_cancel.php', {
          id: this.form.id,
          reason: reason || ''
        });

        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'ยกเลิกสำเร็จ!',
            text: res.data.message,
            timer: 1600,
            showConfirmButton: false
          });
          await this.loadPO(this.form.id);
        } else {
          Swal.fire('ไม่สามารถยกเลิกได้', res.data.message, 'error');
        }
      } catch (err) {
        console.error('Cancel PO error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการยกเลิกใบสั่งซื้อ', 'error');
      }
    },
    async cancelPO(po) {
      if (!po || !po.id) return;
      const { value: reason, isConfirmed } = await Swal.fire({
        title: 'ยืนยันการยกเลิกใบสั่งซื้อ?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการยกเลิกใบสั่งซื้อเลขที่ <strong class="text-danger">${po.po_number}</strong> ?<br><small class="text-muted">*หากใบสั่งซื้อนี้มาจากใบขอซื้อ (PR) ใบขอซื้อจะถูกปลดล็อกกลับมาให้ใช้งานใหม่ได้</small>`,
        input: 'textarea',
        inputPlaceholder: 'ระบุเหตุผลการยกเลิก (ถ้ามี)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> ยืนยันยกเลิกใบสั่งซื้อ',
        cancelButtonText: 'ปิด'
      });

      if (!isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/po_cancel.php', {
          id: po.id,
          reason: reason || ''
        });

        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'ยกเลิกสำเร็จ!',
            text: res.data.message,
            timer: 1600,
            showConfirmButton: false
          });
          this.fetchPOs();
        } else {
          Swal.fire('ไม่สามารถยกเลิกได้', res.data.message, 'error');
        }
      } catch (err) {
        console.error('Cancel PO error:', err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการยกเลิกใบสั่งซื้อ', 'error');
      }
    },
    switchToInvsTab() {
      this.activeTab = 'invs';
      if (this.invsPOs.length === 0) {
        this.fetchInvsPOs();
      }
    },
    async fetchInvsPOs() {
      this.loadingInvs = true;
      try {
        const q = encodeURIComponent(this.invsFilter.search || '');
        const res = await axios.get(`/api-digital/inventory/po_invs_history.php?search=${q}&limit=60`);
        if (res.data.status === 'success') {
          this.invsPOs = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching INVS POs:', err);
      } finally {
        this.loadingInvs = false;
      }
    },
    async viewInvsItems(po) {
      this.selectedInvsPO = po;
      this.showInvsModal = true;
      this.loadingInvsItems = true;
      this.invsItems = [];
      try {
        const res = await axios.get(`/api-digital/inventory/po_invs_items.php?po_no=${encodeURIComponent(po.po_no)}`);
        if (res.data.status === 'success') {
          this.invsItems = res.data.data || [];
        }
      } catch (err) {
        console.error('Error loading INVS items:', err);
      } finally {
        this.loadingInvsItems = false;
      }
    },
    openPrintPOModal() {
      this.printDocType = 'po';
      this.showPrintModal = true;
    },
    openPrintMemoModal() {
      this.printDocType = 'memo';
      this.showPrintModal = true;
    },
    executePrint() {
      window.print();
    },
    getBuyMethodName(code) {
      const m = this.buyMethods.find(x => x.code === code);
      return m ? m.name : code;
    },
    getBudgetName(code) {
      const b = this.budgetTypes.find(x => x.code === code);
      return b ? b.name : code;
    },
    formatDate(d) {
      if (!d) return '-';
      const dt = new Date(d);
      return dt.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    formatThaiDateFull(d) {
      if (!d) return '-';
      const dt = new Date(d);
      const thMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
      return `${dt.getDate()} ${thMonths[dt.getMonth()]} พ.ศ. ${dt.getFullYear() + 543}`;
    },
    thaiBahtText(amount) {
      // Basic fallback Baht text converter
      if (!amount || isNaN(amount)) return 'ศูนย์บาทถ้วน';
      const numbers = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
      const positions = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
      let [integerPart, decimalPart] = Number(amount).toFixed(2).split('.');
      let result = '';
      let len = integerPart.length;

      for (let i = 0; i < len; i++) {
        let digit = parseInt(integerPart[i]);
        let pos = len - i - 1;
        if (digit !== 0) {
          if (pos === 1 && digit === 1) result += '';
          else if (pos === 1 && digit === 2) result += 'ยี่';
          else if (pos === 0 && digit === 1 && len > 1 && integerPart[len - 2] !== '0') result += 'เอ็ด';
          else result += numbers[digit];
          result += positions[pos];
        }
      }
      result += 'บาท';
      if (decimalPart === '00') {
        result += 'ถ้วน';
      } else {
        let d1 = parseInt(decimalPart[0]);
        let d2 = parseInt(decimalPart[1]);
        if (d1 !== 0) {
          if (d1 === 1) result += 'สิบ';
          else if (d1 === 2) result += 'ยี่สิบ';
          else result += numbers[d1] + 'สิบ';
        }
        if (d2 !== 0) {
          if (d2 === 1 && d1 !== 0) result += 'เอ็ด';
          else result += numbers[d2];
        }
        result += 'สตางค์';
      }
      return result;
    },
    getStatusBadgeClass(status) {
      switch (status) {
        case 'ORDERED': return 'bg-primary-subtle text-primary border border-primary-subtle';
        case 'PARTIAL_RECEIVED': return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        case 'COMPLETED': return 'bg-success-subtle text-success border border-success-subtle';
        case 'CANCELLED': return 'bg-danger-subtle text-danger border border-danger-subtle';
        default: return 'bg-secondary-subtle text-secondary';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'ORDERED': return 'สั่งซื้อแล้ว (รอรับของ)';
        case 'PARTIAL_RECEIVED': return 'รับของบางส่วน';
        case 'COMPLETED': return 'รับของครบถ้วน';
        case 'CANCELLED': return 'ยกเลิก';
        default: return status || 'รอดำเนินการ';
      }
    }
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.hover-bg:hover {
  background-color: #f8f9fa;
}
.custom-tab-btn {
  color: #64748b;
  border: none !important;
  border-bottom: 2px solid transparent !important;
  padding: 0.75rem 1.25rem;
  font-size: 0.95rem;
  background: transparent;
  transition: all 0.2s ease;
}
.custom-tab-btn:hover {
  color: #1e293b;
  background-color: #f1f5f9;
}
.custom-tab-btn.active {
  color: #0f172a !important;
  border-bottom: 2px solid #0d6efd !important;
  background-color: transparent !important;
}

@media print {
  body * {
    visibility: hidden;
  }
  #printable-area, #printable-area * {
    visibility: visible;
  }
  #printable-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
  }
  .modal-backdrop, .modal-header, .btn, .no-print {
    display: none !important;
  }
}
</style>
