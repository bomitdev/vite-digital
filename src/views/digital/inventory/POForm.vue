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
              <li class="breadcrumb-item active" aria-current="page">ระบบสั่งซื้อพัสดุ (PO)</li>
            </ol>
          </nav>
          <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center">
              <span class="me-2">📑</span> ระบบสั่งซื้อพัสดุ <span v-if="warehouseName" class="text-primary ms-2 fs-5">({{ warehouseName }})</span>
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
        </ul>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: สร้างใบสั่งซื้อใหม่ (Exact Image 2) -->
      <!-- ========================================== -->
      <div v-show="activeTab === 'create'">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4 p-md-5">

            <!-- Edit Notice if applicable -->
            <div v-if="isEdit" class="alert d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4 rounded-3 shadow-xs" 
                 :class="form.status === 'CANCELLED' ? 'alert-danger' : (hasReceivedItems ? 'alert-warning' : 'alert-info')">
              <div class="d-flex align-items-center">
                <i class="bi fs-4 me-2" 
                   :class="form.status === 'CANCELLED' ? 'bi-x-octagon-fill text-danger' : (hasReceivedItems ? 'bi-lock-fill text-warning-emphasis' : 'bi-pencil-square text-primary')"></i>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <strong v-if="form.status === 'CANCELLED'">ใบสั่งซื้อนี้ถูกยกเลิกแล้ว: {{ form.po_number }}</strong>
                    <strong v-else-if="hasReceivedItems">ใบสั่งซื้อนี้มีการรับของเข้าคลังแล้ว (ล็อกการแก้ไข): {{ form.po_number }}</strong>
                    <strong v-else>กำลังแก้ไขใบสั่งซื้อ: {{ form.po_number }}</strong>
                    <span class="badge" :class="getStatusBadgeClass(form.status)">{{ getStatusText(form.status) }}</span>
                  </div>
                  <div v-if="form.status === 'CANCELLED'" class="small mt-1 text-danger fw-semibold">
                    <i class="bi bi-exclamation-triangle me-1"></i> เอกสารใบสั่งซื้อนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้
                  </div>
                  <div v-else-if="hasReceivedItems" class="small mt-1 text-secondary">
                    <i class="bi bi-shield-lock me-1 text-warning-emphasis"></i> มีการรับพัสดุเข้าคลังแล้ว ระบบจึงล็อกเอกสารไว้ไม่อนุญาตให้แก้ไข เพื่อป้องกันความคลาดเคลื่อนของยอดสต็อกและการตรวจรับ (สามารถดูและพิมพ์เอกสารได้)
                  </div>
                  <div v-else class="small mt-1 text-muted">
                    สถานะ: สั่งซื้อแล้ว (ยังไม่ได้รับของ) — คุณสามารถแก้ไขข้อมูลหัวเอกสาร, วันที่, ผู้จำหน่าย, รายการพัสดุ, จำนวน และราคา แล้วกด "บันทึกการแก้ไข" ได้ทันที
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm fw-medium" @click="openPrintPOModal">
                  <i class="bi bi-printer me-1"></i> พิมพ์เอกสาร
                </button>
                <button v-if="['ORDERED'].includes(form.status) && !hasReceivedItems" type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" @click="cancelCurrentPO">
                  <i class="bi bi-x-circle me-1"></i> ยกเลิกใบสั่งซื้อนี้
                </button>
              </div>
            </div>

            <form @submit.prevent="savePO">
              
              <!-- 1. ข้อมูลหัวใบสั่งซื้อ -->
              <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                  <div class="d-flex align-items-center">
                    <h5 class="fw-bold mb-0 text-dark me-2">1. ข้อมูลหัวใบสั่งซื้อ</h5>
                    <i class="bi bi-link-45deg text-secondary fs-5" title="เชื่อมโยงกับฐานข้อมูลจัดซื้อ"></i>
                  </div>
                  <button 
                    v-if="canEditPO" 
                    type="button" 
                    class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 shadow-sm fw-bold d-flex align-items-center gap-2"
                    @click="openQuotationModal"
                    title="นำเข้าจากใบเสนอราคาด้วย AI (รูปภาพหรือ PDF)"
                  >
                    <i class="bi bi-stars text-success fs-6"></i>
                    <span>นำเข้าจากใบเสนอราคา (AI Upload)</span>
                  </button>
                </div>

                <div class="row g-3">
                  <!-- วันที่ใบสั่งซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">วันที่ใบสั่งซื้อ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" v-model="form.po_date" required :disabled="!canEditPO" />
                  </div>

                  <!-- ผู้จำหน่าย -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">ผู้จำหน่าย <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="selectedVendorIndex" @change="onVendorChange" required :disabled="!canEditPO">
                      <option value="">-- เลือกผู้จำหน่าย --</option>
                      <option v-for="(v, idx) in vendors" :key="idx" :value="idx">
                        {{ v.vendor_name }} ({{ v.vendor_code || '-' }})
                      </option>
                    </select>
                  </div>

                  <!-- วิธีจัดซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">วิธีจัดซื้อ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.buy_method" required :disabled="!canEditPO">
                      <option v-for="m in buyMethods" :key="m.code" :value="m.code">{{ m.name }}</option>
                    </select>
                  </div>

                  <!-- ประเภทงบประมาณ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">ประเภทงบประมาณ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.budget_type" required :disabled="!canEditPO">
                      <option v-for="b in budgetTypes" :key="b.code" :value="b.code">{{ b.name }}</option>
                    </select>
                  </div>

                  <!-- รูปแบบจัดซื้อ -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">รูปแบบจัดซื้อ <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.buy_common" required :disabled="!canEditPO">
                      <option v-for="c in buyCommons" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </select>
                  </div>

                  <!-- เกณฑ์ราคา -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">เกณฑ์ราคา <span class="text-danger">*</span></label>
                    <select class="form-select" v-model="form.price_criteria" required :disabled="!canEditPO">
                      <option v-for="p in priceCriterias" :key="p" :value="p">{{ p }}</option>
                    </select>
                  </div>

                  <!-- การคิดภาษีมูลค่าเพิ่ม (VAT) -->
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">
                      การคิดภาษีมูลค่าเพิ่ม (VAT) <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.vat_mode" @change="printSettings.vatMode = form.vat_mode" required :disabled="!canEditPO">
                      <option value="included">รวมภาษี 7% ในราคาแล้ว (VAT Included)</option>
                      <option value="excluded">ยังไม่รวมภาษี / บวกภาษี 7% เพิ่ม (VAT Excluded)</option>
                      <option value="none">ไม่คิดภาษี / ยกเว้นภาษี (VAT 0%)</option>
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
                      <input type="text" class="form-control" v-model="form.memo_no" placeholder="อย 0033.301/132" :disabled="!canEditPO" />
                    </div>

                    <!-- วันที่รายงานขอซื้อ/ขอจ้าง -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">วันที่รายงานขอซื้อ/ขอจ้าง</label>
                      <input type="date" class="form-control" v-model="form.memo_date" :disabled="!canEditPO" />
                    </div>

                    <!-- กำหนดส่งมอบ (วัน) -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">กำหนดส่งมอบ (วัน)</label>
                      <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" @click="form.delivery_days = Math.max(1, form.delivery_days - 5)" :disabled="!canEditPO">-</button>
                        <input type="number" class="form-control text-center fw-bold" v-model.number="form.delivery_days" min="1" :disabled="!canEditPO" />
                        <button class="btn btn-outline-secondary" type="button" @click="form.delivery_days += 5" :disabled="!canEditPO">+</button>
                      </div>
                    </div>

                    <!-- เลขที่สัญญา / เลขโครงการ (ถ้ามี) -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">เลขที่สัญญา / เลขโครงการ (ถ้ามี)</label>
                      <input type="text" class="form-control" v-model="form.contract_no" placeholder="ระบุเลขที่สัญญาหรือเลขโครงการ e-GP" :disabled="!canEditPO" />
                    </div>

                    <!-- กรรมการตรวจรับพัสดุ -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">กรรมการตรวจรับพัสดุ</label>
                      <input type="text" class="form-control" v-model="form.committee_name" placeholder="ระบุชื่อกรรมการตรวจรับพัสดุ" :disabled="!canEditPO" />
                    </div>

                    <!-- บันทึกช่วยจำ / หมายเหตุ -->
                    <div class="col-md-4">
                      <label class="form-label small fw-semibold text-secondary">บันทึกช่วยจำ / หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.note" placeholder="รายละเอียดหรือเงื่อนไขเพิ่มเติม..." :disabled="!canEditPO" />
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
                            <td>
                              <div class="fw-bold">{{ item.item_name_en || item.item_name }}</div>
                              <div v-if="item.item_name_en && item.item_name && item.item_name !== item.item_name_en" class="text-muted small">{{ item.item_name }}</div>
                            </td>
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
                <div v-if="canEditPO" class="card border rounded-3 p-3 mb-3 bg-white">
                  <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                    <label class="form-label small fw-semibold text-secondary mb-0">เลือกพัสดุที่จะสั่งซื้อ</label>
                    <div class="d-flex gap-2">
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold d-flex align-items-center gap-1 shadow-sm"
                        @click="openSelectPRModal"
                        title="ดึงรายการพัสดุจากใบขอซื้อ (PR) ที่ได้รับการอนุมัติแล้ว"
                      >
                        <i class="bi bi-file-earmark-check"></i>
                        <span>เลือกจากใบขอซื้อ (PR)</span>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold d-flex align-items-center gap-1 shadow-sm"
                        @click="openQuotationModal"
                        title="อัปโหลดรูปภาพหรือ PDF ใบเสนอราคาให้ AI ช่วยอ่านและกรอกรายการพัสดุอัตโนมัติ"
                      >
                        <i class="bi bi-stars"></i>
                        <span>อ่านใบเสนอราคา (AI)</span>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-primary rounded-pill px-3"
                        @click="addBlankItem"
                        title="เพิ่มแถวพัสดุใหม่เพื่อพิมพ์ชื่อยี่ห้อ/รุ่น/สเปกเองโดยไม่ต้องสร้างรหัสในคลัง"
                      >
                        <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการพิมพ์เอง / สเปกใหม่
                      </button>
                    </div>
                  </div>
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
                            <span class="fw-bold text-dark">{{ mat.item_name_en || mat.item_name }}</span>
                            <span v-if="mat.item_name_en && mat.item_name && mat.item_name_en !== mat.item_name" class="text-muted small ms-2">({{ mat.item_name }})</span>
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
                        <th v-if="canEditPO" style="width: 80px;" class="text-center">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="form.items.length === 0">
                        <td :colspan="canEditPO ? 8 : 7" class="text-center py-5 text-muted">
                          <i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                          <span>ยังไม่มีรายการพัสดุที่จะสั่งซื้อ (เลือกพัสดุจากช่องด้านบน หรือเลือกจากจุดสั่งซื้อ)</span>
                        </td>
                      </tr>
                      <tr v-for="(item, index) in form.items" :key="index">
                        <td class="text-center text-muted small">{{ index + 1 }}</td>
                        <td class="fw-bold text-secondary small">
                          <input 
                            v-if="canEditPO && !item.item_id" 
                            type="text" 
                            class="form-control form-control-sm font-monospace text-secondary" 
                            style="width: 110px;"
                            v-model="item.item_code" 
                            placeholder="รหัส (ถ้ามี)" 
                          />
                          <span v-else>{{ item.item_code || '-' }}</span>
                        </td>
                        <td>
                          <div v-if="canEditPO">
                            <input 
                              type="text" 
                              class="form-control form-control-sm fw-bold text-dark mb-1" 
                              v-model="item.item_name_en" 
                              placeholder="ชื่อภาษาอังกฤษ / ยี่ห้อ / รุ่น / สเปก (เช่น Toner Cartridge MX-60AT BA)" 
                              title="ชื่อหรือสเปกภาษาอังกฤษที่จะแสดงในใบสั่งซื้อ (PO)"
                            />
                            <div class="d-flex align-items-center gap-1 small text-muted">
                              <span class="text-nowrap" style="font-size: 0.78rem;">ชื่อไทย:</span>
                              <input 
                                type="text" 
                                class="form-control form-control-sm py-0 px-2 text-secondary" 
                                style="font-size: 0.8rem;"
                                v-model="item.item_name" 
                                placeholder="ชื่อภาษาไทย" 
                              />
                            </div>
                          </div>
                          <div v-else>
                            <div class="fw-bold text-dark">{{ item.item_name_en || item.item_name }}</div>
                            <div v-if="item.item_name_en && item.item_name && item.item_name !== item.item_name_en" class="small text-muted">
                              {{ item.item_name }}
                            </div>
                          </div>
                        </td>
                        <td>
                          <input 
                            type="number" 
                            class="form-control form-control-sm text-center fw-bold" 
                            v-model.number="item.qty_ordered" 
                            min="1" 
                            required 
                            :disabled="!canEditPO"
                          />
                        </td>
                        <td class="text-center text-muted small">
                          <input 
                            v-if="canEditPO" 
                            type="text" 
                            class="form-control form-control-sm text-center" 
                            style="min-width: 75px;"
                            v-model="item.unit_name" 
                            placeholder="ตลับ/อัน"
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
                            :disabled="!canEditPO"
                          />
                        </td>
                        <td class="text-end fw-bold text-dark">
                          {{ (Number(item.qty_ordered || 0) * Number(item.unit_price || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿
                        </td>
                        <td v-if="canEditPO" class="text-center">
                          <div class="d-flex justify-content-center gap-1">
                            <button 
                              type="button" 
                              class="btn btn-sm btn-outline-secondary border-0 rounded-circle"
                              @click="duplicateItem(index)"
                              title="คัดลอกแถวนี้ (เพื่อแก้เป็นรุ่น/สีอื่นอย่างรวดเร็ว)"
                            >
                              <i class="bi bi-copy"></i>
                            </button>
                            <button 
                              type="button" 
                              class="btn btn-sm btn-outline-danger border-0 rounded-circle"
                              @click="removeItem(index)"
                              title="ลบรายการ"
                            >
                              <i class="bi bi-trash3-fill"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="table-light">
                      <tr v-if="effectiveVatMode !== 'none'">
                        <td :colspan="canEditPO ? 6 : 5" class="text-end fw-semibold text-secondary py-2">
                          รวมเป็นเงิน (ก่อนภาษี) :
                        </td>
                        <td class="text-end fw-semibold text-dark py-2" colspan="2">
                          {{ subtotalFormatted }} ฿
                        </td>
                      </tr>
                      <tr v-if="effectiveVatMode !== 'none'">
                        <td :colspan="canEditPO ? 6 : 5" class="text-end fw-semibold text-secondary py-2">
                          ภาษีมูลค่าเพิ่ม 7.00% {{ effectiveVatMode === 'included' ? '(รวมในราคาแล้ว)' : '(บวกเพิ่ม 7%)' }} :
                        </td>
                        <td class="text-end fw-semibold text-dark py-2" colspan="2">
                          {{ vatAmountFormatted }} ฿
                        </td>
                      </tr>
                      <tr v-else>
                        <td :colspan="canEditPO ? 6 : 5" class="text-end fw-semibold text-secondary py-2">
                          ภาษีมูลค่าเพิ่ม (VAT) :
                        </td>
                        <td class="text-end fw-semibold text-muted py-2" colspan="2">
                          ยกเว้นภาษี (0.00%)
                        </td>
                      </tr>
                      <tr class="table-warning border-top">
                        <td :colspan="canEditPO ? 6 : 5" class="text-end fw-bold py-2 text-dark fs-6">
                          จำนวนเงินรวมทั้งสิ้น :
                        </td>
                        <td class="text-end fw-bold py-2 text-danger fs-5" colspan="2">
                          {{ grandTotalFormatted }} ฿
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
                    @click="goBackToList"
                  >
                    <i class="bi bi-arrow-left me-1"></i> กลับหน้ารายการ
                  </button>

                  <button 
                    v-if="isEdit && ['ORDERED'].includes(form.status) && !hasReceivedItems" 
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

                  <!-- Save Changes Button (When Editing) -->
                  <button 
                    v-if="isEdit && canEditPO" 
                    type="submit" 
                    class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold text-dark"
                    :disabled="saving || form.items.length === 0"
                  >
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check2-circle me-1"></i> บันทึกการแก้ไขใบสั่งซื้อ
                  </button>

                  <!-- Save Button (When New) -->
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
                        <button 
                          v-if="po.status === 'ORDERED'"
                          class="btn btn-outline-warning text-dark" 
                          @click="viewPODetails(po)" 
                          title="แก้ไขใบสั่งซื้อ (ยังไม่รับของ)"
                        >
                          <i class="bi bi-pencil-square"></i>
                        </button>
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
      <!-- MODAL: พิมพ์เอกสาร (Print Preview Modal)                 -->
      <!-- ======================================================= -->
      <div v-if="showPrintModal" class="modal-backdrop fade show"></div>
      <div v-if="showPrintModal" class="modal fade show d-block" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-3 d-flex justify-content-between align-items-center">
              <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-printer text-primary"></i>
                <span>{{ printDocType === 'po' ? 'ใบสั่งซื้อ / ใบสั่งจ้าง (Purchase Order)' : 'รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง' }}</span>
                <span class="badge bg-secondary-subtle text-secondary fs-6 fw-normal border">ขนาด A4</span>
              </h5>
              <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-danger rounded-pill px-3 btn-sm fw-bold shadow-sm" @click="downloadPdf" title="ดาวน์โหลดไฟล์ PDF ขนาด A4">
                  <i class="bi bi-file-earmark-pdf-fill me-1"></i> ดาวน์โหลด PDF (A4)
                </button>
                <button type="button" class="btn-close ms-2" @click="showPrintModal = false"></button>
              </div>
            </div>
            <div class="modal-body p-3 p-md-4 bg-light">
              
              <!-- Interactive Print Settings Bar (Visible on screen only) -->
              <div class="no-print bg-white p-3 rounded-4 shadow-sm mb-4 border">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                      <i class="bi bi-file-earmark-text me-1"></i>แบบฟอร์มมาตรฐาน รพ.ชานุมาน
                    </span>
                    <span v-if="printDocType === 'po' && poItemPages.length > 1" class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">
                      <i class="bi bi-file-earmark-break me-1"></i>แบ่งพิมพ์ {{ poItemPages.length }} แผ่น (แผ่นละไม่เกิน 15 รายการ)
                    </span>
                    <span v-else-if="printDocType === 'memo' && memoItemPages.length > 1" class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">
                      <i class="bi bi-file-earmark-break me-1"></i>แบ่งพิมพ์ {{ memoItemPages.length }} แผ่น (แผ่นละไม่เกิน 15 รายการ)
                    </span>
                    <span v-else class="badge bg-success-subtle text-success border px-2 py-1">
                      <i class="bi bi-aspect-ratio me-1"></i>ขนาด A4 (1 แผ่นจบ ไม่เกิน 15 รายการ)
                    </span>
                    <div class="d-flex align-items-center gap-1 ms-md-2" v-if="printDocType === 'po'">
                      <label class="small fw-bold text-secondary mb-0">ภาษี:</label>
                      <select class="form-select form-select-sm" style="width: 250px;" v-model="printSettings.vatMode" @change="form.vat_mode = printSettings.vatMode">
                        <option value="included">รวมภาษี 7% ในราคาแล้ว (VAT Included)</option>
                        <option value="excluded">ยังไม่รวมภาษี / บวก 7% เพิ่ม (VAT Excluded)</option>
                        <option value="none">ไม่คิดภาษี / ยกเว้นภาษี (VAT 0%)</option>
                      </select>
                    </div>
                    <div class="d-flex align-items-center gap-1 ms-md-2">
                      <label class="small fw-bold text-secondary mb-0">ชื่อพัสดุ:</label>
                      <select class="form-select form-select-sm" style="width: 200px;" v-model="printSettings.itemNameLanguage">
                        <option value="en">ชื่อภาษาอังกฤษ (English)</option>
                        <option value="both">แสดงทั้งสองชื่อ (EN + TH)</option>
                        <option value="th">ชื่อภาษาไทย (Thai)</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="d-flex gap-2 flex-wrap">
                    <button 
                      v-if="printDocType === 'po'"
                      type="button" 
                      class="btn btn-sm btn-outline-secondary rounded-pill px-3" 
                      @click="printSettings.showConfig = !printSettings.showConfig"
                    >
                      <i class="bi bi-sliders me-1"></i> {{ printSettings.showConfig ? 'ซ่อนการตั้งค่า' : 'ปรับแต่งเลขที่ / ผู้ลงนาม' }}
                    </button>

                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold shadow-sm" @click="downloadPdf">
                      <i class="bi bi-file-earmark-pdf-fill me-1"></i> ดาวน์โหลด PDF (A4)
                    </button>
                  </div>
                </div>

                <!-- Collapsible Settings Panel -->
                <div v-if="printDocType === 'po' && printSettings.showConfig" class="mt-3 pt-3 border-top small">
                  <div class="row g-2">
                    <div class="col-6 col-md-2">
                      <label class="form-label mb-1 fw-bold text-secondary">เล่มที่</label>
                      <input type="text" class="form-control form-control-sm" v-model="printSettings.bookNo" placeholder="เช่น 190 หรือ 01" />
                    </div>
                    <div class="col-6 col-md-3">
                      <label class="form-label mb-1 fw-bold text-secondary">เลขที่ใบสั่งซื้อ</label>
                      <input type="text" class="form-control form-control-sm" v-model="printSettings.poNumber" placeholder="เช่น 1 หรือ 001/2569" />
                    </div>
                    <div class="col-6 col-md-3">
                      <label class="form-label mb-1 fw-bold text-secondary">อ้างอิงใบขอซื้อ (PR)</label>
                      <input type="text" class="form-control form-control-sm" v-model="printSettings.prNumber" placeholder="UCT6910378-5" />
                    </div>
                    <div class="col-6 col-md-4">
                      <label class="form-label mb-1 fw-bold text-secondary">สถานที่ส่งมอบ</label>
                      <input type="text" class="form-control form-control-sm" v-model="printSettings.deliveryLocation" />
                    </div>

                    <!-- Signers 1 & 2 -->
                    <div class="col-md-6 mt-2">
                      <div class="p-2 border rounded-3 bg-light">
                        <span class="fw-bold text-primary">1. เจ้าหน้าที่พัสดุ</span>
                        <div class="input-group input-group-sm mt-1">
                          <span class="input-group-text">ชื่อ</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.supplyStaff.name" />
                          <span class="input-group-text">ตำแหน่ง</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.supplyStaff.position" />
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6 mt-2">
                      <div class="p-2 border rounded-3 bg-light">
                        <span class="fw-bold text-primary">2. หัวหน้ากลุ่มงาน (ผู้ขอซื้อ)</span>
                        <div class="input-group input-group-sm mt-1">
                          <span class="input-group-text">ชื่อ</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.requesterHead.name" />
                          <span class="input-group-text">ตำแหน่ง</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.requesterHead.position" />
                        </div>
                      </div>
                    </div>

                    <!-- Signers 3 & 4 -->
                    <div class="col-md-6">
                      <div class="p-2 border rounded-3 bg-light">
                        <span class="fw-bold text-primary">3. หัวหน้าเจ้าหน้าที่พัสดุ</span>
                        <div class="input-group input-group-sm mt-1">
                          <span class="input-group-text">ชื่อ</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.supplyHead.name" />
                          <span class="input-group-text">ตำแหน่ง</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.supplyHead.position" />
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="p-2 border rounded-3 bg-light">
                        <span class="fw-bold text-primary">4. ผู้สั่งซื้อ / ผู้มีอำนาจอนุมัติ (ผู้อำนวยการ)</span>
                        <div class="input-group input-group-sm mt-1">
                          <span class="input-group-text">ชื่อ</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.director.name" />
                          <span class="input-group-text">ตำแหน่ง</span>
                          <input type="text" class="form-control" v-model="printSettings.officers.director.position" />
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" @click="resetDefaultOfficers">
                      <i class="bi bi-arrow-counterclockwise me-1"></i> คืนค่าเริ่มต้นตามฟอร์ม
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" @click="saveOfficerSettings">
                      <i class="bi bi-check-lg me-1"></i> จดจำค่านี้ไว้ใช้ครั้งถัดไป
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Printable Area (A4 layout - Paginated: max 15 items per page) -->
              <div id="printable-area">
                
                <!-- DOCUMENT TYPE 1: ใบสั่งซื้อ / ใบสั่งจ้าง (Exact Match PDF Form) -->
                <div v-if="printDocType === 'po'">
                  <div 
                    v-for="(pageItems, pageIdx) in poItemPages" 
                    :key="'po-page-' + pageIdx"
                    class="po-page po-print-sheet bg-white mx-auto shadow-sm"
                    :class="{ 'mb-4': pageIdx < poItemPages.length - 1 }"
                  >
                    <!-- First Page Header & Metadata & Vendor Box -->
                    <template v-if="pageIdx === 0">
                      <!-- Header matching Hospital Pre-printed Form -->
                      <div class="po-header-section mb-2">
                        <div class="po-header-grid">
                          <div class="po-header-left"></div>
                          <div class="po-header-center">
                            <div class="po-hospital-name">โรงพยาบาลชานุมาน</div>
                            <div class="po-doc-title">ใบสั่งซื้อ / สั่งจ้าง</div>
                            <div class="po-date-line">
                              วันที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ formatThaiDateFull(form.po_date) }}</span></span>
                            </div>
                          </div>
                          <div class="po-header-right">
                            <div class="po-book-row">
                              เล่มที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ printSettings.bookNo || '01' }}</span></span>
                            </div>
                            <div class="po-num-row mt-1">
                              เลขที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ printSettings.poNumber || form.po_number || '1' }}</span></span>
                            </div>
                            <div v-if="poItemPages.length > 1" class="po-page-row small text-dark mt-1">
                              หน้า 1 / {{ poItemPages.length }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Vendor Information Box -->
                      <div class="vendor-box mb-2">
                        <div><span class="fw-bold">เรียน:</span> {{ form.vendor_name || 'หจก.อุบลคอมพิวเตอร์ แอนด์ เทเลคอมเซอร์วิส' }}</div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                          <div style="flex: 1 1 auto; margin-right: 14px;"><span class="fw-bold">ที่อยู่:</span> {{ form.vendor_address || '42,44,46 ถนนพโลชัย ต.ในเมือง อ.เมือง จ.อุบลราชธานี 34000' }}</div>
                          <div style="white-space: nowrap; margin-right: 14px;"><span class="fw-bold">เลขประจำตัวผู้เสียภาษี:</span> {{ form.vendor_tax_id || '0343536000913' }}</div>
                          <div style="white-space: nowrap;"><span class="fw-bold">เบอร์โทรศัพท์:</span> {{ form.vendor_phone || '045-240835-8' }}</div>
                        </div>
                      </div>

                      <!-- Introductory Statement -->
                      <div class="po-lead-text mb-2">
                        ตามที่ท่านได้เสนอราคาและยินยอมส่งมอบพัสดุให้แก่ โรงพยาบาลชานุมาน ซึ่งได้รับราคาและตกลงสั่งซื้อพัสดุตามรายการและเงื่อนไขดังต่อไปนี้
                      </div>
                    </template>

                    <!-- Continuation Pages Header (Page 2+) -->
                    <template v-else>
                      <div class="po-header-section mb-2">
                        <div class="po-header-grid">
                          <div class="po-header-left"></div>
                          <div class="po-header-center">
                            <div class="po-hospital-name">โรงพยาบาลชานุมาน</div>
                            <div class="po-doc-title">ใบสั่งซื้อ / สั่งจ้าง (ต่อ)</div>
                            <div class="po-date-line">
                              วันที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ formatThaiDateFull(form.po_date) }}</span></span>
                            </div>
                          </div>
                          <div class="po-header-right">
                            <div class="po-book-row">
                              เล่มที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ printSettings.bookNo || '01' }}</span></span>
                            </div>
                            <div class="po-num-row mt-1">
                              เลขที่<span class="po-dotted-blank px-2"><span class="fw-bold">{{ printSettings.poNumber || form.po_number || '1' }}</span></span>
                            </div>
                            <div class="po-page-row small fw-bold text-dark mt-1">
                              หน้า {{ pageIdx + 1 }} / {{ poItemPages.length }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="mb-2 small text-dark">
                        <span class="fw-bold">ผู้ขาย:</span> {{ form.vendor_name || '-' }}
                      </div>
                    </template>

                    <!-- Items Table (Max 15 rows on this page) -->
                    <table class="po-table">
                      <thead>
                        <tr>
                          <th style="width: 6%;">ลำดับ</th>
                          <th style="width: 46%;">รายการ</th>
                          <th style="width: 10%;">จำนวน</th>
                          <th style="width: 10%;">หน่วย</th>
                          <th style="width: 14%;">ราคาต่อหน่วย<br>(บาท)</th>
                          <th style="width: 14%;">จำนวนเงิน<br>(บาท)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(it, i) in pageItems" :key="i">
                          <td class="text-center">{{ (pageIdx * 15) + i + 1 }}</td>
                          <td class="text-start ps-2">{{ getItemDisplayName(it) }}</td>
                          <td class="text-end pe-2">{{ Number(it.qty_ordered || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</td>
                          <td class="text-center">{{ it.unit_name || 'หน่วย' }}</td>
                          <td class="text-end pe-2">{{ Number(it.unit_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</td>
                          <td class="text-end pe-2">{{ (Number(it.qty_ordered || 0) * Number(it.unit_price || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</td>
                        </tr>
                      </tbody>

                      <!-- If not last page: Show continuation note -->
                      <tfoot v-if="pageIdx < poItemPages.length - 1">
                        <tr>
                          <td colspan="6" class="text-end pe-3 py-1 fw-bold text-dark" style="background: #fff; border: 0.5px solid #000;">
                            (มีต่อหน้าที่ {{ pageIdx + 2 }})
                          </td>
                        </tr>
                      </tfoot>

                      <!-- If last page: Show full grand total footer -->
                      <tfoot v-else>
                        <tr>
                          <td colspan="4" class="empty-summary-cell"></td>
                          <td class="text-end pe-2 fw-medium">รวมเป็นเงิน</td>
                          <td class="text-end pe-2 fw-medium">{{ subtotalFormatted }}</td>
                        </tr>
                        <tr>
                          <td colspan="4" rowspan="2" class="align-middle text-center p-2 baht-cell">
                            <div>(จำนวนเงินรวมทั้งสิ้นเป็นตัวหนังสือ)</div>
                            <div class="fw-bold mt-1">( {{ thaiBahtText(grandTotal) }} )</div>
                          </td>
                          <td class="text-end pe-2 fw-medium">
                            ภาษีมูลค่าเพิ่ม {{ printSettings.vatMode === 'none' ? '0.00%' : '7.00%' }}
                          </td>
                          <td class="text-end pe-2 fw-medium">{{ vatAmountFormatted }}</td>
                        </tr>
                        <tr>
                          <td class="text-end pe-2 fw-bold">จำนวนเงินทั้งสิ้น</td>
                          <td class="text-end pe-2 fw-bold">{{ grandTotalFormatted }}</td>
                        </tr>
                      </tfoot>
                    </table>

                    <!-- Conditions, Signatures, and Vendor Ack box: ONLY on the last page -->
                    <template v-if="pageIdx === poItemPages.length - 1">
                      <!-- Conditions and Terms -->
                      <div class="po-conditions mb-2">
                        <div class="fw-bold mb-1">เงื่อนไขและข้อตกลงการสั่งซื้อ:</div>
                        <div class="condition-item">1. กำหนดส่งมอบพัสดุภายใน {{ printSettings.deliveryDays || form.delivery_days || 30 }} วัน นับตั้งแต่วันถัดจากวันที่ผู้ขายได้รับใบสั่งซื้อนี้</div>
                        <div class="condition-item">2. สถานที่ส่งมอบ: {{ printSettings.deliveryLocation }}</div>
                        <div class="condition-item">3. กรณีส่งมอบเกินกำหนด จะต้องชำระค่าปรับรายวันในอัตราร้อยละ {{ printSettings.penaltyRate || '0.20' }} ของราคาพัสดุที่ยังไม่ได้รับมอบนับแต่วันถัดจากวันครบกำหนดส่งมอบจนถึงวันที่ส่งมอบถูกต้องครบถ้วน</div>
                        <div class="condition-item">4. การตรวจรับจะถือว่าสมบูรณ์เมื่อคณะกรรมการตรวจรับพัสดุของโรงพยาบาลชานุมานได้ทำการตรวจรับของถูกต้องเรียบร้อยแล้ว</div>
                        <div class="condition-item">5. ข้อกำหนดเพิ่มเติม: {{ printSettings.extraCondition }}</div>
                      </div>

                      <!-- Signatures (4 officers in 2x2 grid) -->
                      <div class="po-signatures mb-2">
                        <div class="row text-center g-2">
                          <!-- Row 1: Left: เจ้าหน้าที่พัสดุ, Right: หัวหน้ากลุ่มงาน (ผู้ขอซื้อ) -->
                          <div class="col-6 mb-2">
                            <div>ลงชื่อ...................................................</div>
                            <div>( {{ printSettings.officers.supplyStaff.name }} )</div>
                            <div>ตำแหน่ง {{ printSettings.officers.supplyStaff.position }}</div>
                            <div class="fw-bold">{{ printSettings.officers.supplyStaff.role }}</div>
                          </div>
                          <div class="col-6 mb-2">
                            <div>ลงชื่อ...................................................</div>
                            <div>( {{ printSettings.officers.requesterHead.name }} )</div>
                            <div>ตำแหน่ง {{ printSettings.officers.requesterHead.position }}</div>
                            <div class="fw-bold">{{ printSettings.officers.requesterHead.role }}</div>
                          </div>

                          <!-- Row 2: Left: หัวหน้าเจ้าหน้าที่พัสดุ, Right: ผู้สั่งซื้อ / ผู้มีอำนาจอนุมัติ -->
                          <div class="col-6">
                            <div>ลงชื่อ...................................................</div>
                            <div>( {{ printSettings.officers.supplyHead.name }} )</div>
                            <div>ตำแหน่ง {{ printSettings.officers.supplyHead.position }}</div>
                            <div class="fw-bold">{{ printSettings.officers.supplyHead.role }}</div>
                          </div>
                          <div class="col-6">
                            <div>ลงชื่อ...................................................</div>
                            <div>( {{ printSettings.officers.director.name }} )</div>
                            <div>ตำแหน่ง {{ printSettings.officers.director.position }}</div>
                            <div class="fw-bold">{{ printSettings.officers.director.role }}</div>
                          </div>
                        </div>
                      </div>

                      <!-- Vendor Acknowledgement Box -->
                      <div class="vendor-ack-box">
                        <div class="text-center fw-bold mb-1">ใบตอบรับใบสั่งซื้อ (สำหรับบริษัท / ผู้ขาย)</div>
                        <div class="ack-text">
                          ข้าพเจ้า <strong>{{ form.vendor_name || '...................................................' }}</strong> 
                          ได้รับใบสั่งซื้อ เลขที่ <strong>{{ printSettings.poNumber || form.po_number || '......../........' }}</strong> 
                          ฉบับนี้ไว้แล้วเมื่อวันที่ ......../......../........ และยินดีปฏิบัติตามเงื่อนไขและกำหนดส่งมอบพัสดุถูกต้องทุกประการ
                        </div>
                        <div class="text-end pe-4 mt-1 ack-sign">
                          <div>ลงชื่อ...................................................ผู้รับใบสั่งซื้อ</div>
                          <div class="pe-4">(...................................................)</div>
                          <div class="pe-3">วันที่ ......../......../........</div>
                        </div>
                      </div>
                    </template>

                  </div>
                </div>

                <!-- DOCUMENT TYPE 2: รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง -->
                <div v-else>
                  <div 
                    v-for="(pageItems, pageIdx) in memoItemPages" 
                    :key="'memo-page-' + pageIdx"
                    class="po-page po-print-sheet bg-white mx-auto shadow-sm"
                    :class="{ 'mb-4': pageIdx < memoItemPages.length - 1 }"
                  >
                    <div class="text-center mb-3">
                      <h2 class="fw-bold fs-5 mb-1">
                        รายละเอียดแนบท้ายรายงานขอซื้อ/ขอจ้าง {{ memoItemPages.length > 1 ? `(หน้า ${pageIdx + 1}/${memoItemPages.length})` : '' }}
                      </h2>
                      <div class="small">ตามหนังสือรายงานขอซื้อ/ขอจ้าง เลขที่ {{ form.memo_no || 'อย 0033.301/132' }} ลงวันที่ {{ formatThaiDateFull(form.memo_date) }}</div>
                    </div>

                    <div class="mb-2 small" v-if="pageIdx === 0">
                      โรงพยาบาลชานุมาน มีความประสงค์จะขอซื้อเวชภัณฑ์/พัสดุ โดยวิธี{{ getBuyMethodCleanName(form.buy_method) }} ตามรายการดังต่อไปนี้
                    </div>

                    <table class="table table-bordered table-sm align-middle small mb-3">
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
                        <tr v-for="(it, i) in pageItems" :key="i">
                          <td class="text-center">{{ (pageIdx * 15) + i + 1 }}</td>
                          <td>
                            <div>{{ getItemDisplayName(it) }}</div>
                            <div class="text-muted small" v-if="it.item_code">รหัส: {{ it.item_code }}</div>
                          </td>
                          <td class="text-center fw-bold">{{ it.qty_ordered }}</td>
                          <td class="text-center">{{ it.unit_name || 'หน่วย' }}</td>
                          <td class="text-end">{{ Number(it.unit_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                          <td class="text-end fw-bold">{{ (Number(it.qty_ordered) * Number(it.unit_price)).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                        </tr>
                        <tr v-if="pageIdx < memoItemPages.length - 1">
                          <td colspan="6" class="text-end fw-bold py-2 bg-light">
                            (มีต่อหน้าที่ {{ pageIdx + 2 }})
                          </td>
                        </tr>
                        <tr v-else class="fw-bold">
                          <td colspan="4" class="text-center align-middle">
                            ({{ thaiBahtText(totalAmount) }})
                          </td>
                          <td class="text-end">รวมวงเงินทั้งสิ้น</td>
                          <td class="text-end text-danger">{{ totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- Memo footer details & signatures on last page -->
                    <template v-if="pageIdx === memoItemPages.length - 1">
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
                    </template>
                  </div>
                </div></div>

            </div>
          </div>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- MODAL: อ่านใบเสนอราคาด้วย AI (AI Quotation Reader)       -->
      <!-- ======================================================= -->
      <div v-if="showQuotationModal" class="modal-backdrop fade show"></div>
      <div v-if="showQuotationModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.55);" @click.self="closeQuotationModal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            
            <!-- Modal Header -->
            <div class="modal-header border-bottom p-3 bg-white d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                  <i class="bi bi-stars fs-5"></i>
                </div>
                <div>
                  <h5 class="modal-title fw-bold text-dark mb-0">นำเข้าข้อมูลจากใบเสนอราคาด้วย AI</h5>
                  <small class="text-muted">อัปโหลดรูปภาพหรือ PDF ใบเสนอราคา ระบบจะอ่านและกรอกข้อมูลสร้างใบ PO ให้อัตโนมัติ</small>
                </div>
              </div>
              <button type="button" class="btn-close" @click="closeQuotationModal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-md-4 bg-light">
              
              <!-- Dropzone / File Upload Area -->
              <div 
                v-if="!quotationScanning && !parsedQuotation"
                class="border border-2 border-dashed rounded-4 p-4 text-center cursor-pointer mb-3 position-relative transition-all bg-white"
                :class="isDraggingQuotation ? 'border-primary bg-primary-subtle' : 'border-secondary-subtle'"
                @dragover.prevent="isDraggingQuotation = true"
                @dragleave.prevent="isDraggingQuotation = false"
                @drop.prevent="onQuotationDrop"
                @click="$refs.quotationFileInput.click()"
              >
                <input 
                  type="file" 
                  ref="quotationFileInput" 
                  class="d-none" 
                  accept="image/jpeg,image/png,image/webp,image/heic,application/pdf"
                  @change="onQuotationFileSelect"
                />

                <div v-if="!quotationFile">
                  <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center p-3 mb-2">
                    <i class="bi bi-cloud-arrow-up fs-1"></i>
                  </div>
                  <h6 class="fw-bold text-dark mb-1">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h6>
                  <p class="text-muted small mb-2">รองรับรูปถ่าย / สแกนใบเสนอราคา (JPG, PNG) หรือไฟล์ PDF</p>
                  <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill small">
                    <i class="bi bi-clipboard-check me-1 text-success"></i> สามารถกด <b>Ctrl+V</b> เพื่อวางรูปที่แคปเจอร์หน้าจอได้ทันที
                  </span>
                </div>

                <!-- File Selected Preview -->
                <div v-else class="d-flex flex-column align-items-center">
                  <img 
                    v-if="quotationPreviewUrl" 
                    :src="quotationPreviewUrl" 
                    class="img-thumbnail rounded-3 shadow-sm mb-2" 
                    style="max-height: 220px; max-width: 100%; object-fit: contain;" 
                  />
                  <div v-else class="p-4 bg-light rounded-3 shadow-sm mb-2 text-danger text-center">
                    <i class="bi bi-file-earmark-pdf fs-1 d-block mb-1"></i>
                    <span class="small fw-bold">เอกสาร PDF</span>
                  </div>
                  <div class="fw-bold text-dark fs-6">{{ quotationFile.name }}</div>
                  <small class="text-muted mb-3">{{ (quotationFile.size / 1024).toFixed(1) }} KB</small>
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm"
                    @click.stop="resetQuotationUpload"
                  >
                    <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนไฟล์ใหม่
                  </button>
                </div>
              </div>

              <!-- Gemini API Key Input (if needed or toggled) -->
              <div v-if="quotationNeedKey || showApiKeyInput" class="alert alert-warning border-0 rounded-4 p-3 mb-3 shadow-sm">
                <div class="d-flex align-items-start gap-2 mb-2">
                  <i class="bi bi-key-fill text-warning fs-5"></i>
                  <div class="flex-grow-1">
                    <div class="fw-bold text-dark">กำหนด Google Gemini API Key</div>
                    <small class="text-secondary">ระบบใช้ Google Gemini AI ในการวิเคราะห์ข้อความและตารางในใบเสนอราคา สามารถขอรับ API Key ฟรีได้ที่ <a href="https://aistudio.google.com/app/apikey" target="_blank" class="fw-bold text-decoration-none">Google AI Studio</a></small>
                  </div>
                </div>
                <div class="input-group">
                  <input 
                    type="password" 
                    class="form-control" 
                    v-model="geminiApiKeyInput" 
                    placeholder="วาง API Key เช่น AIzaSy..." 
                  />
                  <button 
                    class="btn btn-warning fw-bold px-3" 
                    type="button" 
                    @click="saveGeminiKeyOnly"
                    :disabled="!geminiApiKeyInput.trim()"
                  >
                    บันทึก Key
                  </button>
                </div>
              </div>

              <!-- Error Message -->
              <div v-if="quotationScanError && !quotationNeedKey" class="alert alert-danger border-0 rounded-3 p-3 mb-3 d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                <div class="small flex-grow-1">{{ quotationScanError }}</div>
              </div>

              <!-- Scanning Spinner State -->
              <div v-if="quotationScanning" class="text-center py-5 bg-white rounded-4 shadow-sm border">
                <div class="spinner-grow text-success mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                <h5 class="fw-bold text-dark mb-1">AI กำลังวิเคราะห์และอ่านใบเสนอราคา...</h5>
                <p class="text-muted small mb-0">ระบบกำลังถอดชื่อผู้จำหน่าย, รายการพัสดุ, สเปก/รุ่นตลับหมึก, จำนวน, ราคาต่อหน่วย และคำนวณภาษีมูลค่าเพิ่ม (กรุณารอสักครู่)</p>
              </div>

              <!-- Parsed Results Preview -->
              <div v-if="parsedQuotation && !quotationScanning">
                <div class="alert alert-success border-0 rounded-3 p-3 mb-3 d-flex justify-content-between align-items-center shadow-sm">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span class="fw-bold text-success-emphasis">AI อ่านข้อมูลเรียบร้อยแล้ว ตรวจสอบรายละเอียดด้านล่างก่อนนำเข้า</span>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" @click="resetQuotationUpload">
                    <i class="bi bi-arrow-repeat me-1"></i> สแกนใหม่
                  </button>
                </div>

                <!-- Vendor & Quotation Header Preview Card -->
                <div class="card border rounded-3 p-3 mb-3 bg-white shadow-sm">
                  <div class="row g-2 small">
                    <div class="col-md-7">
                      <span class="text-muted d-block">ชื่อผู้จำหน่าย / ร้านค้า:</span>
                      <span class="fw-bold text-dark fs-6">{{ parsedQuotation.vendor_name || '-' }}</span>
                      <span v-if="parsedQuotation.matched_vendor" class="badge bg-success-subtle text-success ms-2">พบในฐานข้อมูลร้านค้า</span>
                      <span v-else class="badge bg-warning-subtle text-warning-emphasis ms-2">ผู้จำหน่ายใหม่ (พิมพ์ระบุ)</span>
                    </div>
                    <div class="col-md-5">
                      <span class="text-muted d-block">เลขประจำตัวผู้เสียภาษี:</span>
                      <span class="fw-bold text-dark">{{ parsedQuotation.vendor_tax_id || '-' }}</span>
                    </div>
                    <div class="col-md-7" v-if="parsedQuotation.vendor_address">
                      <span class="text-muted d-block">ที่อยู่:</span>
                      <span>{{ parsedQuotation.vendor_address }}</span>
                    </div>
                    <div class="col-md-5" v-if="parsedQuotation.vendor_phone">
                      <span class="text-muted d-block">โทรศัพท์:</span>
                      <span>{{ parsedQuotation.vendor_phone }}</span>
                    </div>
                    <div class="col-md-4" v-if="parsedQuotation.quotation_no">
                      <span class="text-muted d-block">เลขที่ใบเสนอราคา:</span>
                      <span class="fw-bold text-primary">{{ parsedQuotation.quotation_no }}</span>
                    </div>
                    <div class="col-md-4" v-if="parsedQuotation.quotation_date">
                      <span class="text-muted d-block">วันที่ในเอกสาร:</span>
                      <span>{{ parsedQuotation.quotation_date }}</span>
                    </div>
                    <div class="col-md-4">
                      <span class="text-muted d-block">รูปแบบภาษีมูลค่าเพิ่ม:</span>
                      <span class="badge" :class="parsedQuotation.vat_mode === 'included' ? 'bg-primary' : (parsedQuotation.vat_mode === 'excluded' ? 'bg-info text-dark' : 'bg-secondary')">
                        {{ parsedQuotation.vat_mode === 'included' ? 'รวมภาษี 7% ในราคาแล้ว' : (parsedQuotation.vat_mode === 'excluded' ? 'แยกภาษี 7% เพิ่ม' : 'ไม่คิดภาษี 0%') }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Items Table Preview -->
                <div class="card border rounded-3 overflow-hidden mb-3 bg-white shadow-sm">
                  <div class="card-header bg-light py-2 px-3 fw-bold small text-dark d-flex justify-content-between align-items-center">
                    <span>รายการพัสดุที่ตรวจพบ ({{ (parsedQuotation.items || []).length }} รายการ)</span>
                    <span class="badge bg-primary-subtle text-primary fs-6">ยอดรวมสุทธิ: {{ Number(parsedQuotation.total_amount || 0).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</span>
                  </div>
                  <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                      <thead class="table-light sticky-top">
                        <tr>
                          <th class="text-center" style="width: 40px;">#</th>
                          <th>รายการ / สเปก / รุ่น</th>
                          <th class="text-center" style="width: 80px;">จำนวน</th>
                          <th class="text-center" style="width: 70px;">หน่วย</th>
                          <th class="text-end" style="width: 100px;">ราคา/หน่วย</th>
                          <th class="text-end" style="width: 110px;">จำนวนเงิน</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(it, idx) in parsedQuotation.items" :key="idx">
                          <td class="text-center text-muted">{{ idx + 1 }}</td>
                          <td>
                            <div class="fw-bold text-dark">{{ it.item_name_en || it.item_name }}</div>
                            <div v-if="it.item_name_en && it.item_name && it.item_name_en !== it.item_name" class="text-muted small">{{ it.item_name }}</div>
                          </td>
                          <td class="text-center">{{ it.qty_ordered }}</td>
                          <td class="text-center">{{ it.unit_name || 'อัน' }}</td>
                          <td class="text-end">{{ Number(it.unit_price || 0).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                          <td class="text-end fw-bold">{{ (Number(it.qty_ordered || 0) * Number(it.unit_price || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Insertion Options -->
                <div class="d-flex align-items-center gap-3 p-2 bg-white rounded-3 border small shadow-sm">
                  <span class="fw-bold text-secondary">วิธีนำเข้าตารางพัสดุ:</span>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" v-model="quotationAppendMode" value="replace" id="optReplace">
                    <label class="form-check-label cursor-pointer" for="optReplace">แทนที่รายการเดิมทั้งหมดใน PO</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" v-model="quotationAppendMode" value="append" id="optAppend">
                    <label class="form-check-label cursor-pointer" for="optAppend">เพิ่มต่อท้ายรายการเดิม</label>
                  </div>
                </div>

              </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top p-3 bg-white d-flex justify-content-between align-items-center">
              <div>
                <button 
                  type="button" 
                  class="btn btn-link text-muted p-0 text-decoration-none small"
                  @click="showApiKeyInput = !showApiKeyInput"
                >
                  <i class="bi bi-gear me-1"></i> ตั้งค่า API Key
                </button>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" @click="closeQuotationModal">
                  ยกเลิก
                </button>
                <button 
                  v-if="!parsedQuotation"
                  type="button" 
                  class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"
                  :disabled="!quotationFile || quotationScanning"
                  @click="submitQuotationAI"
                >
                  <i class="bi bi-stars me-1"></i> เริ่มสแกนด้วย AI
                </button>
                <button 
                  v-else
                  type="button" 
                  class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                  @click="applyQuotationData"
                >
                  <i class="bi bi-check2-circle me-1"></i> นำข้อมูลลงในใบสั่งซื้อ (Apply)
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- MODAL: เลือกจากใบขอซื้อ (PR) -->
      <div v-if="showSelectPRModal" class="modal-backdrop fade show"></div>
      <div v-if="showSelectPRModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" @click.self="showSelectPRModal = false">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom p-3 bg-white d-flex justify-content-between align-items-center">
              <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-file-earmark-check-fill text-info"></i> เลือกรายการจากใบขอซื้อ (PR)
              </h5>
              <button type="button" class="btn-close" @click="showSelectPRModal = false"></button>
            </div>
            <div class="modal-body p-4 bg-light">
              <div v-if="loadingApprovedPRs" class="text-center py-4">
                <div class="spinner-border text-info" role="status"></div>
                <div class="text-muted mt-2 small">กำลังโหลดรายการใบขอซื้อที่อนุมัติแล้ว...</div>
              </div>
              <div v-else-if="approvedPRs.length === 0" class="text-center py-4 bg-white rounded-3 border">
                <i class="bi bi-inbox text-muted fs-1 mb-2 d-block"></i>
                <div class="fw-bold text-dark mb-1">ไม่พบใบขอซื้อที่รอออก PO</div>
                <div class="text-muted small">ใบขอซื้อทั้งหมดอาจถูกเปิดเป็น PO แล้ว หรือยังไม่ได้รับอนุมัติจาก ผอ.</div>
              </div>
              <div v-else class="list-group shadow-xs">
                <div 
                  v-for="pr in approvedPRs" 
                  :key="pr.id"
                  class="list-group-item list-group-item-action p-3 rounded-3 mb-2 border d-flex justify-content-between align-items-center"
                >
                  <div>
                    <div class="fw-bold text-primary mb-1">
                      <i class="bi bi-file-text me-1"></i> {{ pr.pr_number }}
                    </div>
                    <div class="small text-muted mb-1">
                      <span class="me-3"><i class="bi bi-calendar3 me-1"></i>{{ formatDateTh(pr.request_date) }}</span>
                      <span class="me-3"><i class="bi bi-person me-1"></i>{{ pr.requester_name }}</span>
                      <span><i class="bi bi-box-seam me-1"></i>{{ pr.item_count }} รายการ</span>
                    </div>
                    <div class="small text-secondary" v-if="pr.reason">
                      <span class="fw-semibold">เหตุผล:</span> {{ pr.reason }}
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-success mb-2">
                      {{ Number(pr.total_estimated || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 }) }} ฿
                    </div>
                    <button 
                      type="button" 
                      class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm"
                      @click="selectPR(pr)"
                    >
                      <i class="bi bi-check-circle me-1"></i> เลือก PR นี้
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-top p-3 bg-white">
              <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="showSelectPRModal = false">
                ปิดหน้าต่าง
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
import Swal from 'sweetalert2';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'POForm',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      activeTab: 'create', // 'create', 'list'
      isEdit: false,
      saving: false,
      warehouseName: localStorage.getItem('selected_warehouse_name') || '',
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
        vat_mode: 'included',
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

      // Print Modal
      showPrintModal: false,
      printDocType: 'po', // 'po' or 'memo'

      // Select PR Modal
      showSelectPRModal: false,
      approvedPRs: [],
      loadingApprovedPRs: false,

      // Quotation AI Reader Modal
      showQuotationModal: false,
      quotationFile: null,
      quotationPreviewUrl: null,
      quotationScanning: false,
      quotationScanError: '',
      quotationNeedKey: false,
      showApiKeyInput: false,
      geminiApiKeyInput: '',
      hasGeminiApiKey: false,
      isDraggingQuotation: false,
      parsedQuotation: null,
      quotationAppendMode: 'replace',
      printSettings: {
        showConfig: false,
        bookNo: '01',
        poNumber: '',
        prNumber: '',
        vatMode: 'included', // 'included' (default 7% in total), 'excluded', 'none'
        itemNameLanguage: 'en', // 'en' (default English), 'both', 'th'
        deliveryDays: 30,
        deliveryLocation: 'กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน',
        extraCondition: 'ส่งมอบของพร้อมใบกำกับภาษี',
        penaltyRate: '0.20',
        officers: {
          supplyStaff: {
            name: 'น.ส.วารุณี ผวงพันธ์',
            position: 'เจ้าหน้าที่พัสดุ',
            role: 'เจ้าหน้าที่พัสดุ'
          },
          requesterHead: {
            name: 'นายศราวุฒิ แสนโท',
            position: 'หัวหน้ากลุ่มงานสุขภาพดิจิทัล',
            role: 'หัวหน้ากลุ่มงาน (ผู้ขอซื้อ)'
          },
          supplyHead: {
            name: 'นพ.ธนากร คนเพียร',
            position: 'หัวหน้าเจ้าหน้าที่พัสดุ',
            role: 'หัวหน้าเจ้าหน้าที่พัสดุ'
          },
          director: {
            name: 'นายแพทย์ธนากร คนเพียร',
            position: 'ผู้อำนวยการโรงพยาบาลชานุมาน',
            role: 'ผู้สั่งซื้อ / ผู้มีอำนาจอนุมัติ'
          }
        }
      }
    };
  },
  computed: {
    effectiveVatMode() {
      return this.printSettings.vatMode || this.form.vat_mode || 'included';
    },
    totalAmount() {
      return this.form.items.reduce((sum, item) => {
        return sum + (Number(item.qty_ordered || 0) * Number(item.unit_price || 0));
      }, 0);
    },
    subtotal() {
      const total = Number(this.totalAmount) || 0;
      if (this.effectiveVatMode === 'included') {
        return Math.round((total / 1.07) * 100) / 100;
      }
      return total;
    },
    vatAmount() {
      const total = Number(this.totalAmount) || 0;
      if (this.effectiveVatMode === 'included') {
        return Math.round((total - this.subtotal) * 100) / 100;
      } else if (this.effectiveVatMode === 'excluded') {
        return Math.round((total * 0.07) * 100) / 100;
      }
      return 0;
    },
    grandTotal() {
      if (this.effectiveVatMode === 'excluded') {
        return Math.round((this.subtotal + this.vatAmount) * 100) / 100;
      }
      return Number(this.totalAmount) || 0;
    },
    subtotalFormatted() {
      return this.subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    vatAmountFormatted() {
      return this.vatAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    grandTotalFormatted() {
      return this.grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    poItemPages() {
      const items = this.form.items || [];
      if (items.length <= 15) return [items];
      const pages = [];
      for (let i = 0; i < items.length; i += 15) {
        pages.push(items.slice(i, i + 15));
      }
      return pages;
    },
    memoItemPages() {
      const items = this.form.items || [];
      if (items.length <= 15) return [items];
      const pages = [];
      for (let i = 0; i < items.length; i += 15) {
        pages.push(items.slice(i, i + 15));
      }
      return pages;
    },
    hasReceivedItems() {
      if (['COMPLETED', 'PARTIAL_RECEIVED'].includes(this.form.status)) return true;
      return (this.form.items || []).some(it => Number(it.qty_received || 0) > 0);
    },
    canEditPO() {
      if (!this.isEdit) return true;
      if (this.form.status === 'CANCELLED') return false;
      if (this.hasReceivedItems) return false;
      return true;
    }
  },
  watch: {
    async warehouseId() {
      await this.fetchMeta();
      await this.fetchMaterials();
      if (this.activeTab === 'list') {
        await this.fetchPOs();
      }
    },
    async '$route.query.id'(newId) {
      if (newId) {
        this.isEdit = true;
        this.activeTab = 'create';
        await this.loadPO(newId);
      }
    }
  },
  async mounted() {
    await this.fetchMeta();
    await this.fetchUserProfile();
    await this.fetchMaterials();
    await this.checkGeminiKeyStatus();

    window.addEventListener('paste', this.handlePaste);

    // Check query params
    const id = this.$route.query.id;
    const prId = this.$route.query.pr_id;
    const tab = this.$route.query.tab;

    if (id) {
      this.isEdit = true;
      this.activeTab = 'create';
      await this.loadPO(id);
    } else if (prId) {
      this.activeTab = 'create';
      await this.loadPR(prId);
    } else if (tab === 'list') {
      this.activeTab = 'list';
      await this.fetchPOs();
    } else if (tab === 'invs') {
      this.activeTab = 'list';
      await this.fetchPOs();
    }
  },
  beforeUnmount() {
    window.removeEventListener('paste', this.handlePaste);
  },
  methods: {
    async fetchMeta() {
      try {
        const whId = this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id') || '';
        const params = new URLSearchParams();
        if (whId) params.append('warehouse_id', whId);
        const res = await axios.get(`/api-digital/inventory/po_meta.php?${params.toString()}`);
        if (res.data.status === 'success') {
          const d = res.data.data;
          if (d.warehouse_name) {
            this.warehouseName = d.warehouse_name;
          }
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
        const whId = this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id') || '';
        const params = new URLSearchParams();
        params.append('limit', '100');
        if (whId) params.append('warehouse_id', whId);
        const res = await axios.get(`/api-digital/inventory/po_items_search.php?${params.toString()}`);
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
        (m.item_name || '').toLowerCase().includes(q) ||
        (m.item_name_en || '').toLowerCase().includes(q)
      );
    },
    selectMaterial(mat) {
      // Check if already in items with exact same name and code
      const existing = this.form.items.find(i => i.item_code === mat.item_code && i.item_name_en === (mat.item_name_en || mat.item_name || ''));
      if (existing) {
        existing.qty_ordered = Number(existing.qty_ordered || 0) + 1;
      } else {
        this.form.items.push({
          item_id: mat.item_id || 0,
          item_code: mat.item_code,
          item_name: mat.item_name,
          item_name_en: mat.item_name_en || mat.item_name || '',
          unit_name: mat.unit_name || 'อัน',
          qty_ordered: 1,
          unit_price: Number(mat.price) || 0
        });
      }
      this.showMaterialDropdown = false;
      this.materialSearchQuery = '';
    },
    addBlankItem() {
      this.form.items.push({
        item_id: null,
        item_code: '',
        item_name: '',
        item_name_en: '',
        unit_name: 'ตลับ',
        qty_ordered: 1,
        unit_price: 0
      });
    },
    duplicateItem(index) {
      const item = this.form.items[index];
      if (!item) return;
      this.form.items.splice(index + 1, 0, {
        item_id: item.item_id || null,
        item_code: item.item_code || '',
        item_name: item.item_name || '',
        item_name_en: item.item_name_en || '',
        unit_name: item.unit_name || 'อัน',
        qty_ordered: item.qty_ordered || 1,
        unit_price: item.unit_price || 0
      });
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
          const whId = this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id') || '';
          const params = new URLSearchParams();
          if (whId) params.append('warehouse_id', whId);
          const res = await axios.get(`/api-digital/inventory/po_low_stock.php?${params.toString()}`);
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
          item_name_en: item.item_name_en || '',
          unit_name: item.unit_name,
          qty_ordered: addQty,
          unit_price: Number(item.price) || 0
        });
      }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `เพิ่ม ${item.item_name_en || item.item_name} แล้ว`,
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
        vat_mode: 'included',
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
      this.printSettings.poNumber = '';
      this.printSettings.prNumber = '';
      this.activeTab = 'create';
      if (this.$route.query.id) {
        this.$router.replace('/inventory-po-form');
      }
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
        const whId = this.warehouseId || this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id') || null;
        const poDate = this.form.po_date ? (this.form.po_date.includes(' ') ? this.form.po_date : this.form.po_date + ' 00:00:00') : '';
        const payload = {
          ...this.form,
          id: this.form.id || (this.isEdit ? parseInt(this.$route.query.id) : 0),
          warehouse_id: whId ? parseInt(whId) : null,
          po_date: poDate,
          memo_date: this.form.memo_date ? this.form.memo_date : this.form.po_date
        };

        const res = await axios.post('/api-digital/inventory/po_save.php', payload);
        if (res.data.status === 'success') {
          if (res.data.po_id) this.form.id = res.data.po_id;
          if (res.data.po_number) {
            this.form.po_number = res.data.po_number;
            this.printSettings.poNumber = res.data.po_number;
          }
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message || 'บันทึกข้อมูลเรียบร้อยแล้ว',
            timer: 1600,
            showConfirmButton: false
          });
          if (this.$route.query.id) {
            await this.loadPO(this.$route.query.id);
          } else {
            this.switchToListTab();
          }
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
    goBackToList() {
      if (this.$route.query.id) {
        this.$router.push('/inventory-po');
      } else {
        this.activeTab = 'list';
      }
    },
    async loadPO(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/po_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const d = res.data.data;
          d.po_date = d.po_date ? d.po_date.split(' ')[0] : '';
          d.memo_date = d.memo_date ? d.memo_date.split(' ')[0] : d.po_date;
          d.vat_mode = d.vat_mode || 'included';
          this.form = d;
          this.printSettings.poNumber = d.po_number || '';
          this.printSettings.prNumber = d.pr_number || d.contract_no || d.memo_no || '';
          this.printSettings.vatMode = d.vat_mode;
          
          // Match vendor in dropdown
          const vIdx = this.vendors.findIndex(v => v.vendor_name === d.vendor_name || v.id == d.vendor_id);
          if (vIdx >= 0) {
            this.selectedVendorIndex = vIdx;
          } else if (d.vendor_name) {
            this.vendors.push({
              id: d.vendor_id,
              vendor_code: d.vendor_code,
              vendor_name: d.vendor_name,
              address: d.vendor_address,
              phone: d.vendor_phone,
              tax_id: d.vendor_tax_id
            });
            this.selectedVendorIndex = this.vendors.length - 1;
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
        const whId = this.warehouseId || this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id') || '';
        if (whId) params.append('warehouse_id', whId);
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
      this.printSettings.poNumber = po.po_number || '';
      this.loadPO(po.id);
    },
    printExistingPO(po) {
      this.printSettings.poNumber = po.po_number || '';
      this.loadPO(po.id).then(() => {
        this.printSettings.poNumber = po.po_number || this.form.po_number || '';
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
    openPrintPOModal() {
      this.printDocType = 'po';
      
      // Determine default warehouse code / book number
      const whCode = localStorage.getItem('selected_warehouse_code') || '01';
      this.printSettings.bookNo = this.printSettings.bookNo || String(whCode).padStart(2, '0');
      
      // ALWAYS sync with the active PO's number
      if (this.form.po_number) {
        this.printSettings.poNumber = this.form.po_number;
      } else if (!this.printSettings.poNumber) {
        const thaiYear = new Date(this.form.po_date || new Date()).getFullYear() + 543;
        this.printSettings.poNumber = `001/${thaiYear}`;
      }

      // PR reference
      if (this.form.pr_number || this.form.contract_no || this.form.memo_no) {
        this.printSettings.prNumber = this.form.pr_number || this.form.contract_no || this.form.memo_no;
      } else if (!this.printSettings.prNumber) {
        this.printSettings.prNumber = '';
      }

      // Delivery days & notes
      this.printSettings.deliveryDays = this.form.delivery_days || 30;
      this.printSettings.extraCondition = this.form.note || 'ส่งมอบของพร้อมใบกำกับภาษี';
      this.printSettings.vatMode = this.form.vat_mode || 'included';

      // Location
      if (this.warehouseName && this.warehouseName.includes('คอมพิวเตอร์')) {
        this.printSettings.deliveryLocation = 'กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน';
      } else if (this.warehouseName) {
        this.printSettings.deliveryLocation = `${this.warehouseName} โรงพยาบาลชานุมาน`;
      } else {
        this.printSettings.deliveryLocation = 'กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน';
      }

      // Load saved officers from localStorage if available
      try {
        const saved = localStorage.getItem('inventory_po_print_officers');
        if (saved) {
          const parsed = JSON.parse(saved);
          if (parsed && parsed.supplyStaff) {
            this.printSettings.officers = { ...this.printSettings.officers, ...parsed };
          }
        }
      } catch (e) {}

      this.showPrintModal = true;
    },
    openPrintMemoModal() {
      this.printDocType = 'memo';
      this.showPrintModal = true;
    },
    async downloadPdf() {
      const el = document.getElementById('printable-area');
      if (!el) return;

      try {
        const pageEls = Array.from(el.querySelectorAll('.po-page'));
        const pages = pageEls.length > 0 ? pageEls : [el];

        Swal.fire({
          title: `กำลังสร้างไฟล์ PDF (${pages.length} หน้า)...`,
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const html2canvasModule = await import('html2canvas');
        const html2canvas = html2canvasModule.default || html2canvasModule;
        const { jsPDF } = await import('jspdf');

        const docTitle = this.printDocType === 'po' 
          ? `ใบสั่งซื้อ_${this.printSettings.poNumber || 'PO'}` 
          : `รายละเอียดแนบท้าย_${this.form.memo_no || 'MEMO'}`;

        const pdf = new jsPDF({
          orientation: 'portrait',
          unit: 'mm',
          format: 'a4'
        });

        const pdfPageWidth = 210;
        const pdfPageHeight = 297;
        const marginX = 8;
        const marginY = 8;
        const maxContentWidth = pdfPageWidth - (marginX * 2); // 194 mm
        const maxContentHeight = pdfPageHeight - (marginY * 2); // 281 mm

        for (let i = 0; i < pages.length; i++) {
          if (i > 0) {
            pdf.addPage();
          }
          const pageEl = pages[i];

          // Create an offscreen clone specifically formatted to fit on 1 sheet of A4
          const clone = pageEl.cloneNode(true);
          clone.style.width = '190mm';
          clone.style.maxWidth = '190mm';
          clone.style.minHeight = 'auto';
          clone.style.height = 'auto';
          clone.style.padding = '0';
          clone.style.margin = '0';
          clone.style.border = 'none';
          clone.style.boxShadow = 'none';
          clone.style.background = '#ffffff';

          // Compact table and elements to prevent overflow
          clone.querySelectorAll('.po-table th, .po-table td').forEach(td => {
            td.style.padding = '2.5px 4px';
            td.style.fontSize = '12.5pt';
          });
          const cond = clone.querySelector('.po-conditions');
          if (cond) {
            cond.style.marginTop = '4px';
            cond.style.fontSize = '11.5pt';
            cond.style.lineHeight = '1.2';
          }
          const sigs = clone.querySelector('.po-signatures');
          if (sigs) {
            sigs.style.marginTop = '6px';
            sigs.style.fontSize = '11.5pt';
            sigs.style.lineHeight = '1.2';
          }
          const ack = clone.querySelector('.vendor-ack-box');
          if (ack) {
            ack.style.marginTop = '6px';
            ack.style.padding = '4px 6px';
            ack.style.fontSize = '11pt';
            ack.style.lineHeight = '1.2';
          }

          const container = document.createElement('div');
          container.style.position = 'fixed';
          container.style.left = '-9999px';
          container.style.top = '0';
          container.style.width = '190mm';
          container.appendChild(clone);
          document.body.appendChild(container);

          const canvas = await html2canvas(clone, {
            scale: 2,
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff'
          });

          document.body.removeChild(container);

          const imgData = canvas.toDataURL('image/jpeg', 0.98);

          let renderWidth = maxContentWidth;
          let renderHeight = (canvas.height * renderWidth) / canvas.width;

          // Auto-scale down if height exceeds 1 page so it NEVER breaks onto a second page!
          if (renderHeight > maxContentHeight) {
            const ratio = maxContentHeight / renderHeight;
            renderHeight = maxContentHeight;
            renderWidth = renderWidth * ratio;
          }

          const posX = marginX + ((maxContentWidth - renderWidth) / 2);
          const posY = marginY;

          pdf.addImage(imgData, 'JPEG', posX, posY, renderWidth, renderHeight);
        }

        pdf.save(`${docTitle}.pdf`);
        Swal.close();
      } catch (err) {
        console.error('Error generating PDF:', err);
        Swal.close();
        this.executePrint();
      }
    },
    executePrint() {
      const el = document.getElementById('printable-area');
      if (!el) {
        window.print();
        return;
      }

      // Create a hidden print iframe to isolate ONLY the PO document
      const iframe = document.createElement('iframe');
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      iframe.style.visibility = 'hidden';
      document.body.appendChild(iframe);

      const doc = iframe.contentWindow.document;
      const docTitle = this.printDocType === 'po' 
        ? `ใบสั่งซื้อ_${this.printSettings.poNumber || 'PO'}` 
        : `รายละเอียดแนบท้าย_${this.form.memo_no || 'MEMO'}`;

      doc.open();
      doc.write(`
        <!DOCTYPE html>
        <html lang="th">
        <head>
          <meta charset="utf-8">
          <title>${docTitle}</title>
          <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap">
          <style>
            @font-face {
              font-family: 'TH Sarabun New';
              src: url('/fonts/THSarabun.ttf') format('truetype');
              font-weight: normal;
              font-style: normal;
            }
            @font-face {
              font-family: 'TH Sarabun New';
              src: url('/fonts/THSarabun Bold.ttf') format('truetype');
              font-weight: bold;
              font-style: normal;
            }

            @page {
              size: A4 portrait !important;
              margin: 5mm 10mm 5mm 10mm !important;
            }

            *, *::before, *::after {
              box-sizing: border-box;
            }

            html, body {
              width: 100%;
              margin: 0;
              padding: 0;
              background: #fff !important;
              color: #000 !important;
              font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
              font-size: 13.5pt;
              line-height: 1.25;
            }

            .po-print-sheet {
              width: 100%;
              max-width: 100%;
              margin: 0;
              padding: 0;
              background: #fff;
            }

            .po-page {
              width: 100% !important;
              max-width: 100% !important;
              margin: 0 !important;
              padding: 0 !important;
              background: #fff !important;
              border: none !important;
              box-shadow: none !important;
              page-break-after: always !important;
              break-after: page !important;
            }

            .po-page:last-child {
              page-break-after: auto !important;
              break-after: auto !important;
            }

            .po-page.mb-4 {
              margin-bottom: 0 !important;
              box-shadow: none !important;
            }

            .po-header-section { margin-bottom: 6px; }
            .po-header-grid { display: flex; justify-content: space-between; align-items: flex-start; }
            .po-header-left { width: 28%; flex-shrink: 0; }
            .po-header-center { width: 44%; flex-grow: 1; text-align: center; }
            .po-header-right { width: 28%; flex-shrink: 0; text-align: right; font-size: 13pt; line-height: 1.35; }
            .po-hospital-name { font-size: 19pt; font-weight: bold; line-height: 1.2; text-align: center; }
            .po-doc-title { font-size: 17pt; font-weight: bold; line-height: 1.25; text-align: center; margin-top: 2px; }
            .po-date-line { font-size: 13.5pt; margin-top: 3px; text-align: center; }
            .po-dotted-blank { display: inline-block; min-width: 90px; border-bottom: 1.5px dotted #000; text-align: center; padding: 0 4px; margin: 0 3px; line-height: 1.1; vertical-align: baseline; }
            .po-red-num { color: #c00000 !important; font-weight: bold; font-size: 13.5pt; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            .row { display: flex; flex-wrap: wrap; margin-left: -5px; margin-right: -5px; }
            .g-0 { margin-left: 0; margin-right: 0; }
            .g-0 > * { padding-left: 0; padding-right: 0; }
            .g-2 { margin-left: -4px; margin-right: -4px; }
            .g-2 > * { padding-left: 4px; padding-right: 4px; }
            .col-4 { width: 33.333333%; }
            .col-6 { width: 50%; }
            .col-12 { width: 100%; }

            .d-flex { display: flex; }
            .justify-content-between { justify-content: space-between; }
            .align-items-center { align-items: center; }
            .flex-wrap { flex-wrap: wrap; }
            .gap-1 { gap: 4px; }
            .gap-2 { gap: 8px; }
            .gap-3 { gap: 16px; }
            .text-center { text-align: center; }
            .text-start { text-align: left; }
            .text-end { text-align: right; }
            .fw-bold { font-weight: bold; }
            .fw-medium { font-weight: 600; }
            .align-middle { vertical-align: middle; }

            .ps-2 { padding-left: 8px; }
            .ps-3 { padding-left: 12px; }
            .pe-2 { padding-right: 8px; }
            .pe-3 { padding-right: 12px; }
            .pe-4 { padding-right: 24px; }
            .p-2 { padding: 6px; }
            .mt-1 { margin-top: 3px; }
            .mt-2 { margin-top: 6px; }
            .mt-3 { margin-top: 10px; }
            .mb-1 { margin-bottom: 3px; }
            .mb-2 { margin-bottom: 6px; }

            .meta-label { display: inline-block; min-width: 60px; }

            .po-print-sheet, .po-print-sheet * {
              color: #000000 !important;
            }

            .vendor-box {
              border: 0.5px solid #000 !important;
              padding: 3px 8px;
              margin: 4px 0 6px 0;
              font-size: 13pt;
              line-height: 1.35;
            }

            .po-lead-text {
              text-indent: 2em;
              font-size: 13pt;
              line-height: 1.35;
              margin: 4px 0 6px 0;
            }

            .po-table {
              width: 100%;
              border-collapse: collapse;
              margin: 4px 0;
              font-size: 12.5pt;
              color: #000;
              border: 0.5px solid #000 !important;
            }

            .po-table th, .po-table td {
              border: 0.5px solid #000 !important;
              padding: 2px 4px;
              vertical-align: middle;
            }

            .po-table th {
              font-weight: bold;
              text-align: center;
              background-color: transparent !important;
            }

            .empty-summary-cell {
              border-left: 0.5px solid transparent !important;
              border-bottom: 0.5px solid transparent !important;
            }

            .baht-cell {
              vertical-align: middle !important;
              text-align: center;
              line-height: 1.3;
            }

            .po-conditions {
              font-size: 11.5pt;
              line-height: 1.2;
              margin-top: 4px;
            }

            .condition-item { margin-bottom: 1px; }

            .po-signatures {
              margin-top: 6px;
              font-size: 11.5pt;
              line-height: 1.25;
              page-break-inside: avoid;
            }

            .vendor-ack-box {
              border: 0.5px dashed #000 !important;
              border-radius: 4px;
              padding: 3px 6px;
              margin-top: 5px;
              font-size: 11pt;
              line-height: 1.2;
              page-break-inside: avoid;
            }

            .ack-text { text-indent: 2em; }
            .ack-sign { line-height: 1.4; text-align: right; }

            table.table-bordered, table.table-bordered th, table.table-bordered td {
              width: 100%;
              border-collapse: collapse;
              border: 0.5px solid #000 !important;
            }
            }
            table.table-bordered th, table.table-bordered td {
              border: 1px solid #000;
              padding: 3px 5px;
            }
          </style>
        </head>
        <body>
          ${el.innerHTML}
        </body>
        </html>
      `);
      doc.close();

      setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => {
          if (document.body.contains(iframe)) {
            document.body.removeChild(iframe);
          }
        }, 2000);
      }, 300);
    },
    getBuyMethodName(code) {
      const m = this.buyMethods.find(x => x.code === code);
      return m ? m.name : code;
    },
    getBuyMethodCleanName(code) {
      const m = this.buyMethods.find(x => x.code === code);
      let name = m ? m.name : (code || 'เฉพาะเจาะจง');
      return name.replace(/^\d+\s*:\s*(วิธี)?/, '').trim();
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
      if (isNaN(dt.getTime())) return d;
      const thMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
      return `${dt.getDate()} ${thMonths[dt.getMonth()]} ${dt.getFullYear() + 543}`;
    },
    thaiBahtText(amount) {
      if (!amount || isNaN(amount)) return 'ศูนย์บาทถ้วน';
      try {
        return BAHTTEXT(Number(amount));
      } catch (e) {
        return 'ศูนย์บาทถ้วน';
      }
    },
    resetDefaultOfficers() {
      this.printSettings.officers = {
        supplyStaff: {
          name: 'น.ส.วารุณี ผวงพันธ์',
          position: 'เจ้าหน้าที่พัสดุ',
          role: 'เจ้าหน้าที่พัสดุ'
        },
        requesterHead: {
          name: 'นายศราวุฒิ แสนโท',
          position: 'หัวหน้ากลุ่มงานสุขภาพดิจิทัล',
          role: 'หัวหน้ากลุ่มงาน (ผู้ขอซื้อ)'
        },
        supplyHead: {
          name: 'นพ.ธนากร คนเพียร',
          position: 'หัวหน้าเจ้าหน้าที่พัสดุ',
          role: 'หัวหน้าเจ้าหน้าที่พัสดุ'
        },
        director: {
          name: 'นายแพทย์ธนากร คนเพียร',
          position: 'ผู้อำนวยการโรงพยาบาลชานุมาน',
          role: 'ผู้สั่งซื้อ / ผู้มีอำนาจอนุมัติ'
        }
      };
      localStorage.removeItem('inventory_po_print_officers');
      Swal.fire({
        icon: 'success',
        title: 'คืนค่าเริ่มต้นสำเร็จ',
        text: 'ตั้งค่าผู้ลงนามเป็นรายชื่อตามแบบฟอร์มมาตรฐานแล้ว',
        timer: 1500,
        showConfirmButton: false
      });
    },
    saveOfficerSettings() {
      try {
        localStorage.setItem('inventory_po_print_officers', JSON.stringify(this.printSettings.officers));
        Swal.fire({
          icon: 'success',
          title: 'บันทึกเรียบร้อย',
          text: 'ระบบได้บันทึกข้อมูลผู้ลงนามไว้ใช้งานในครั้งถัดไปแล้ว',
          timer: 1500,
          showConfirmButton: false
        });
      } catch (e) {}
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
    },
    getItemDisplayName(it) {
      if (!it) return '';
      const en = (it.item_name_en || '').trim();
      const th = (it.item_name || '').trim();
      if (this.printSettings.itemNameLanguage === 'th') {
        return th || en;
      }
      if (this.printSettings.itemNameLanguage === 'both') {
        if (en && th && en.toLowerCase() !== th.toLowerCase()) {
          return `${en} (${th})`;
        }
        return en || th;
      }
      // default: 'en'
      return en || th;
    },

    // ==========================================
    // Select PR (Purchase Request) Methods
    // ==========================================
    async openSelectPRModal() {
      this.showSelectPRModal = true;
      this.loadingApprovedPRs = true;
      try {
        const whId = this.warehouseId || localStorage.getItem('selected_warehouse_id') || '';
        const params = new URLSearchParams();
        if (whId) params.append('warehouse_id', whId);
        const res = await axios.get(`/api-digital/inventory/pr_approved_list.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.approvedPRs = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching approved PRs:', err);
      } finally {
        this.loadingApprovedPRs = false;
      }
    },
    async selectPR(pr) {
      this.showSelectPRModal = false;
      await this.loadPR(pr.id);
    },
    async loadPR(prId) {
      try {
        const res = await axios.get(`/api-digital/inventory/pr_get.php?id=${prId}`);
        if (res.data?.status === 'success') {
          const pr = res.data.data;
          this.form.pr_id = pr.id;
          this.form.pr_number = pr.pr_number;
          this.printSettings.prNumber = pr.pr_number;
          if (pr.warehouse_id) {
            this.warehouseId = pr.warehouse_id;
          }
          if (pr.reason) {
            this.form.note = (this.form.note ? this.form.note + '\n' : '') + `อ้างอิงใบขอซื้อเลขที่ ${pr.pr_number} (เหตุผล: ${pr.reason})`;
          }
          if (pr.items && pr.items.length > 0) {
            this.form.items = pr.items.map(it => ({
              item_id: it.item_id || null,
              item_code: it.item_code || '',
              item_name: it.item_name || '',
              item_name_en: it.item_name || '',
              unit_name: it.unit_name || 'อัน',
              qty_ordered: Number(it.qty_requested) || 1,
              unit_price: Number(it.estimated_price) || 0
            }));
          }
          Swal.fire({
            icon: 'success',
            title: 'ดึงข้อมูลจากใบขอซื้อสำเร็จ!',
            html: `นำเข้ารายการจาก <b>${pr.pr_number}</b> จำนวน <b>${(pr.items || []).length}</b> รายการ`,
            timer: 2000,
            showConfirmButton: false
          });
        }
      } catch (err) {
        console.error('Error loading PR in PO:', err);
        Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลจากใบขอซื้อได้', 'error');
      }
    },

    // ==========================================
    // Quotation AI Reader Methods
    // ==========================================
    openQuotationModal() {
      this.showQuotationModal = true;
      this.quotationScanError = '';
      this.checkGeminiKeyStatus();
    },
    closeQuotationModal() {
      this.showQuotationModal = false;
      this.resetQuotationUpload();
    },
    resetQuotationUpload() {
      this.quotationFile = null;
      if (this.quotationPreviewUrl) {
        URL.revokeObjectURL(this.quotationPreviewUrl);
        this.quotationPreviewUrl = null;
      }
      this.parsedQuotation = null;
      this.quotationScanError = '';
      this.quotationScanning = false;
    },
    onQuotationFileSelect(e) {
      const file = e.target.files?.[0];
      if (file) {
        this.setQuotationFile(file);
      }
      if (e.target) e.target.value = '';
    },
    onQuotationDrop(e) {
      this.isDraggingQuotation = false;
      const file = e.dataTransfer?.files?.[0];
      if (file) {
        this.setQuotationFile(file);
      }
    },
    handlePaste(event) {
      if (!this.showQuotationModal) return;
      const items = (event.clipboardData || event.originalEvent?.clipboardData)?.items;
      if (!items) return;
      for (const item of items) {
        if (item.type && item.type.indexOf('image') !== -1) {
          const blob = item.getAsFile();
          if (blob) {
            this.setQuotationFile(blob, 'pasted-quotation.png');
            break;
          }
        }
      }
    },
    setQuotationFile(file, customName) {
      this.quotationFile = file;
      if (customName && !file.name) {
        try {
          Object.defineProperty(file, 'name', { value: customName });
        } catch(e) {}
      }
      this.parsedQuotation = null;
      this.quotationScanError = '';
      if (file.type && file.type.startsWith('image/')) {
        this.quotationPreviewUrl = URL.createObjectURL(file);
      } else {
        this.quotationPreviewUrl = null;
      }
    },
    async checkGeminiKeyStatus() {
      try {
        const res = await axios.get('/api-digital/inventory/parse_quotation.php');
        if (res.data && res.data.status === 'success') {
          this.hasGeminiApiKey = !!res.data.has_key;
          if (!this.hasGeminiApiKey) {
            this.quotationNeedKey = true;
          }
        }
      } catch (e) {}
    },
    async saveGeminiKeyOnly() {
      if (!this.geminiApiKeyInput.trim()) return;
      try {
        const formData = new FormData();
        formData.append('gemini_api_key', this.geminiApiKeyInput.trim());
        formData.append('action', 'save_key');
        const res = await axios.post('/api-digital/inventory/parse_quotation.php', formData);
        if (res.data.status === 'success') {
          this.hasGeminiApiKey = true;
          this.quotationNeedKey = false;
          this.showApiKeyInput = false;
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: 'บันทึก Google Gemini API Key เรียบร้อยแล้ว พร้อมใช้งาน',
            timer: 1500,
            showConfirmButton: false
          });
        }
      } catch (err) {
        this.quotationScanError = 'บันทึกไม่สำเร็จ: ' + (err.response?.data?.message || err.message);
      }
    },
    async submitQuotationAI() {
      if (!this.quotationFile) {
        this.quotationScanError = 'กรุณาเลือกหรือวางรูปภาพ/PDF ใบเสนอราคา';
        return;
      }

      this.quotationScanning = true;
      this.quotationScanError = '';

      try {
        const formData = new FormData();
        formData.append('file', this.quotationFile);
        if (this.geminiApiKeyInput.trim()) {
          formData.append('gemini_api_key', this.geminiApiKeyInput.trim());
        }

        const res = await axios.post('/api-digital/inventory/parse_quotation.php', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (res.data.status === 'need_key') {
          this.quotationNeedKey = true;
          this.quotationScanError = res.data.message;
        } else if (res.data.status === 'success') {
          this.parsedQuotation = res.data.data;
          this.quotationScanError = '';
          this.quotationNeedKey = false;
          this.hasGeminiApiKey = true;
        } else {
          this.quotationScanError = res.data.message || 'เกิดข้อผิดพลาดในการอ่านเอกสาร';
        }
      } catch (err) {
        console.error('AI quotation error:', err);
        this.quotationScanError = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์เพื่ออ่านเอกสารได้: ' + (err.response?.data?.message || err.message);
      } finally {
        this.quotationScanning = false;
      }
    },
    applyQuotationData() {
      if (!this.parsedQuotation) return;
      const d = this.parsedQuotation;

      // 1. Vendor info
      if (d.vendor_name) this.form.vendor_name = d.vendor_name;
      if (d.vendor_tax_id) this.form.vendor_tax_id = d.vendor_tax_id;
      if (d.vendor_address) this.form.vendor_address = d.vendor_address;
      if (d.vendor_phone) this.form.vendor_phone = d.vendor_phone;

      // Try matching vendor in dropdown
      let foundVendorIdx = -1;
      if (d.vendor_id) {
        foundVendorIdx = this.vendors.findIndex(v => v.id == d.vendor_id);
      }
      if (foundVendorIdx === -1 && d.vendor_tax_id) {
        foundVendorIdx = this.vendors.findIndex(v => v.tax_id && v.tax_id === d.vendor_tax_id);
      }
      if (foundVendorIdx === -1 && d.vendor_name) {
        const qName = d.vendor_name.toLowerCase().replace(/^(บริษัท|ห้างหุ้นส่วนจำกัด|หจก\.|บจก\.)\s*/, '');
        foundVendorIdx = this.vendors.findIndex(v => {
          const vName = (v.vendor_name || '').toLowerCase();
          return vName.includes(qName) || (qName.length > 4 && qName.includes(vName));
        });
      }

      if (foundVendorIdx !== -1) {
        this.selectedVendorIndex = foundVendorIdx;
        const v = this.vendors[foundVendorIdx];
        this.form.vendor_id = v.id || null;
        this.form.vendor_code = v.vendor_code || '';
        if (!this.form.vendor_address) this.form.vendor_address = v.address || '';
        if (!this.form.vendor_phone) this.form.vendor_phone = v.phone || '';
        if (!this.form.vendor_tax_id) this.form.vendor_tax_id = v.tax_id || '';
      }

      // 2. VAT Mode
      if (d.vat_mode) {
        this.form.vat_mode = d.vat_mode;
        this.printSettings.vatMode = d.vat_mode;
      }

      // 3. Quotation Reference Note
      if (d.quotation_no) {
        const quoteNote = `อ้างอิงใบเสนอราคาเลขที่: ${d.quotation_no}` + (d.quotation_date ? ` ลงวันที่ ${d.quotation_date}` : '');
        if (!this.form.note) {
          this.form.note = quoteNote;
        } else if (!this.form.note.includes(d.quotation_no)) {
          this.form.note += ` (${quoteNote})`;
        }
      }

      // 4. Items
      const newItems = (d.items || []).map(it => {
        let matchedMat = null;
        if (this.materials && this.materials.length > 0) {
          const itEn = (it.item_name_en || '').toLowerCase().trim();
          const itTh = (it.item_name || '').toLowerCase().trim();
          matchedMat = this.materials.find(m => {
            const mEn = (m.item_name_en || '').toLowerCase().trim();
            const mTh = (m.item_name || '').toLowerCase().trim();
            return (itEn && (mEn === itEn || mTh === itEn)) || (itTh && (mTh === itTh || mEn === itTh));
          });
        }

        return {
          item_id: matchedMat ? (matchedMat.item_id || matchedMat.id || null) : null,
          item_code: matchedMat ? (matchedMat.item_code || '') : '',
          item_name: it.item_name || it.item_name_en || '',
          item_name_en: it.item_name_en || it.item_name || '',
          unit_name: it.unit_name || 'อัน',
          qty_ordered: Number(it.qty_ordered) || 1,
          unit_price: Number(it.unit_price) || 0
        };
      });

      if (this.quotationAppendMode === 'replace') {
        this.form.items = newItems;
      } else {
        this.form.items = [...this.form.items, ...newItems];
      }

      this.closeQuotationModal();

      Swal.fire({
        icon: 'success',
        title: 'นำเข้าข้อมูลจากใบเสนอราคาสำเร็จ!',
        html: `ระบบได้กรอกข้อมูลผู้จำหน่ายและสร้างรายการพัสดุจำนวน <b>${newItems.length}</b> รายการลงในใบสั่งซื้อเรียบร้อยแล้ว`,
        timer: 2500,
        showConfirmButton: false
      });
    }
  }
};
</script>

<style scoped>
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun.ttf') format('truetype');
  font-weight: normal;
  font-style: normal;
}
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun Bold.ttf') format('truetype');
  font-weight: bold;
  font-style: normal;
}

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

/* PO Document Styles (Exact Match with Government PDF Form - Standard A4 210 x 297 mm) */
.po-page {
  width: 210mm;
  min-height: 297mm;
  max-width: 100%;
  padding: 5mm 10mm 5mm 10mm;
  box-sizing: border-box;
  background: #fff;
  border: 1px solid #e2e8f0;
  position: relative;
}

.po-page.mb-4 {
  margin-bottom: 24px !important;
  box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
}

.po-print-sheet {
  font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
  color: #000;
  font-size: 13pt;
  line-height: 1.25;
}

.po-header-section {
  margin-bottom: 8px;
}

.po-header-grid {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
}

.po-header-left {
  width: 28%;
  flex-shrink: 0;
}

.po-header-center {
  width: 44%;
  flex-grow: 1;
  text-align: center;
}

.po-header-right {
  width: 28%;
  flex-shrink: 0;
  text-align: right;
  font-size: 13pt;
  line-height: 1.35;
}

.po-hospital-name {
  font-size: 19pt;
  font-weight: bold;
  line-height: 1.2;
}

.po-doc-title {
  font-size: 17pt;
  font-weight: bold;
  line-height: 1.25;
  margin-top: 2px;
}

.po-date-line {
  font-size: 13.5pt;
  margin-top: 4px;
  text-align: center;
}

.po-dotted-blank {
  display: inline-block;
  min-width: 90px;
  border-bottom: 1.5px dotted #000;
  text-align: center;
  padding: 0 4px;
  margin: 0 3px;
  line-height: 1.1;
  vertical-align: baseline;
}

.po-print-sheet, .po-print-sheet * {
  color: #000000 !important;
}

.po-red-num {
  color: #000000 !important;
  font-weight: bold;
  font-size: 13.5pt;
}

.meta-label {
  display: inline-block;
  min-width: 60px;
}

.vendor-box {
  border: 0.5px solid #000 !important;
  padding: 4px 8px;
  margin: 4px 0 6px 0;
  font-size: 13pt;
  line-height: 1.35;
}

.po-lead-text {
  text-indent: 2em;
  font-size: 13pt;
  line-height: 1.35;
  margin: 4px 0 6px 0;
}

.po-table {
  width: 100%;
  border-collapse: collapse;
  margin: 4px 0;
  font-size: 12.5pt;
  color: #000;
  border: 0.5px solid #000 !important;
}

.po-table th, .po-table td {
  border: 0.5px solid #000 !important;
  padding: 2px 4px;
  vertical-align: middle;
}

.po-table th {
  font-weight: bold;
  text-align: center;
  background-color: transparent !important;
}

.empty-summary-cell {
  border-left: 0.5px solid transparent !important;
  border-bottom: 0.5px solid transparent !important;
}

.baht-cell {
  vertical-align: middle !important;
  line-height: 1.3;
}

.po-conditions {
  font-size: 11.5pt;
  line-height: 1.2;
  margin-top: 4px;
}

.condition-item {
  margin-bottom: 1px;
}

.po-signatures {
  margin-top: 6px;
  font-size: 11.5pt;
  line-height: 1.25;
  page-break-inside: avoid;
}

.vendor-ack-box {
  border: 0.5px dashed #000 !important;
  border-radius: 4px;
  padding: 3px 6px;
  margin-top: 5px;
  font-size: 11pt;
  line-height: 1.2;
  page-break-inside: avoid;
}

.ack-text {
  text-indent: 2em;
}

.ack-sign {
  line-height: 1.4;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 8mm 12mm 8mm 12mm;
  }
  
  html, body {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
  }

  /* Completely remove all web UI header, tabs, and tables from the print flow */
  .page-container > div:not(.modal):not(.modal-backdrop),
  .breadcrumb,
  .nav-tabs,
  .card {
    display: none !important;
  }
  
  .no-print, .modal-backdrop, .modal-header, .btn {
    display: none !important;
  }

  .modal {
    position: static !important;
    display: block !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
  }

  .modal-dialog {
    margin: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    transform: none !important;
  }

  .modal-content, .modal-body {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    overflow: visible !important;
  }
  
  #printable-area {
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
  }

  .po-page {
    width: 100% !important;
    max-width: 100% !important;
    min-height: auto !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    box-shadow: none !important;
    page-break-after: always !important;
    break-after: page !important;
  }

  .po-page:last-child {
    page-break-after: auto !important;
    break-after: auto !important;
  }
}
</style>

<style>
/* Global print rules to ensure navbar and shell are not printed */
@media print {
  header, nav, .navbar, .top-nav, footer {
    display: none !important;
  }
}
</style>
