<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item"><router-link to="/home-backoffice">หน้าหลัก</router-link></li>
              <li class="breadcrumb-item"><router-link to="/inventory-dashboard">Inventory Dashboard</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">รับเข้าคลัง</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-success mb-0">
            <i class="bi bi-box-arrow-in-down me-2"></i>บันทึกรับเข้าคลังวัสดุ (Stock In)
          </h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button v-if="isAdmin" type="button" class="btn btn-success fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" @click="openImportModal">
            <i class="bi bi-file-earmark-spreadsheet-fill fs-5"></i>
            <span>นำเข้า Excel / CSV</span>
          </button>
          <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" @click="openAIDeliveryModal">
            <i class="bi bi-stars text-primary fs-5"></i>
            <span>AI สแกนอ่านใบส่งของ</span>
          </button>
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
          <button class="btn btn-primary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-grid-fill me-2"></i>Dashboard
          </button>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              <form @submit.prevent="submitTx">
                
                <!-- Section 1: ข้อมูลบิล / ใบสั่งซื้อ -->
                <div class="mb-4">
                  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-2 gap-2">
                    <h5 class="fw-bold mb-0">1. ข้อมูลบิล / ใบสั่งซื้อ (เอกสารอ้างอิง)</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-2" @click="openAIDeliveryModal">
                      <i class="bi bi-camera-fill text-primary"></i>
                      <i class="bi bi-stars text-warning"></i>
                      <span>อัปโหลดใบส่งของให้ AI อ่านบิล</span>
                    </button>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select form-select-lg fw-bold text-primary" v-model="form.warehouse_id" @change="onWarehouseChange" required>
                        <option value="">-- กรุณาเลือกคลัง --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">วันที่รับเข้า <span class="text-danger">*</span></label>
                      <input type="datetime-local" class="form-control form-control-lg" v-model="form.receipt_date" required />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">เลขที่บิล / ใบเสร็จ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-lg" v-model="form.receipt_no" required placeholder="เช่น RC-690001" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">เลขที่ Invoice</label>
                      <input type="text" class="form-control form-control-lg" v-model="form.invoice_no" placeholder="ถ้ามี" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">แหล่งที่มา / ผู้จำหน่าย</label>
                      <select class="form-select" v-model="form.vendor_id">
                        <option value="">-- เลือกผู้จำหน่าย --</option>
                        <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.vendor_name }}</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">นำเข้าจากใบสั่งซื้อ (PO)</label>
                      <select class="form-select" v-model="form.po_id" @change="onPOSelect">
                        <option value="">-- ไม่ระบุ (รับอิสระ) --</option>
                        <option v-for="po in activePOs" :key="po.id" :value="po.id">
                          {{ po.po_number }} ({{ po.warehouse_name }})
                        </option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <label class="form-label">หมายเหตุ</label>
                      <input type="text" class="form-control" v-model="form.remark" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)" />
                    </div>
                  </div>
                </div>

                <!-- Section 2: เพิ่มรายการวัสดุ -->
                <div class="mb-4 bg-light p-3 rounded-3" :class="{'opacity-50 pointer-events-none': !form.warehouse_id}">
                  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-2 gap-2">
                    <h5 class="fw-bold mb-0">2. ค้นหาและระบุรายการวัสดุเข้าคลัง</h5>
                    <div class="d-flex flex-wrap gap-2">
                      <button v-if="isAdmin" type="button" class="btn btn-sm btn-success fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" @click="openImportModal" :disabled="!form.warehouse_id">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        <span>นำเข้า Excel / CSV ยกยอด</span>
                      </button>
                      <button type="button" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" @click="openAIDeliveryModal" :disabled="!form.warehouse_id">
                        <i class="bi bi-file-earmark-arrow-up-fill text-danger"></i>
                        <i class="bi bi-stars text-primary"></i>
                        <span>นำเข้าอัตโนมัติจากใบส่งของ (AI)</span>
                      </button>
                    </div>
                  </div>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-3 position-relative">
                      <label class="form-label mb-1">ค้นหารหัส/ชื่อวัสดุ</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="searchQuery" 
                        @focus="isDropdownOpen = true"
                        placeholder="พิมพ์เพื่อค้นหา..."
                        :disabled="!form.warehouse_id"
                      />
                      <div 
                        v-if="isDropdownOpen && form.warehouse_id" 
                        class="position-absolute w-100 bg-white border rounded shadow mt-1" 
                        style="max-height: 250px; overflow-y: auto; z-index: 1050;"
                      >
                        <div class="d-flex justify-content-between p-2 border-bottom bg-light">
                          <button type="button" class="btn btn-sm btn-outline-secondary" @click="isDropdownOpen = false">ปิดหน้าต่าง</button>
                        </div>
                        <div v-if="filteredItems.length === 0" class="p-2 text-muted text-center">ไม่พบข้อมูลในคลังนี้</div>
                        <div 
                          v-for="m in filteredItems" 
                          :key="m.id" 
                          class="p-2 border-bottom dropdown-item-custom"
                          @click="selectItem(m)"
                        >
                          [{{ m.item_code }}] {{ m.item_name }} <span class="badge bg-info text-dark" v-if="m.lot_control">Lot</span>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">เลข Lot <span class="text-danger" v-if="currentItem.lot_control">*</span></label>
                      <input type="text" class="form-control" v-model="currentItem.lot_number" placeholder="Lot Number" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">วันหมดอายุ (Expiry) <span class="text-danger" v-if="currentItem.expiry_control">*</span></label>
                      <input type="date" class="form-control" v-model="currentItem.expiry_date" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">ราคา/หน่วย (บ.)</label>
                      <input type="number" step="0.01" class="form-control" v-model.number="currentItem.unit_cost" min="0" placeholder="0.00" :disabled="!currentItem.item_id" />
                    </div>
                    <div class="col-md-2">
                      <label class="form-label mb-1">จำนวนที่รับเข้า</label>
                      <div class="input-group">
                        <input type="number" step="0.0001" class="form-control" v-model.number="currentItem.qty" min="0.0001" :disabled="!currentItem.item_id" />
                        <span class="input-group-text">{{ currentItem.unit_name || 'หน่วย' }}</span>
                      </div>
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-primary w-100" @click="addItem" :disabled="!currentItem.item_id || currentItem.qty <= 0">
                        เพิ่ม
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Section 3: รายการที่เลือก -->
                <div class="mb-4" v-if="selectedItems.length > 0">
                  <h5 class="fw-bold mb-3 border-bottom pb-2">3. รายการวัสดุที่เตรียมรับเข้า</h5>
                  <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>ลำดับ</th>
                          <th>รหัสวัสดุ</th>
                          <th>ชื่อวัสดุ</th>
                          <th>Lot / Expiry</th>
                          <th class="text-end">ราคา/หน่วย</th>
                          <th class="text-end">จำนวนรับ</th>
                          <th>หน่วยนับ</th>
                          <th class="text-end">รวมเป็นเงิน</th>
                          <th class="text-center">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in selectedItems" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.item_code }}</td>
                          <td class="fw-bold">{{ item.item_name }}</td>
                          <td>
                            <span v-if="item.lot_number" class="badge bg-secondary me-1">Lot: {{ item.lot_number }}</span>
                            <span v-if="item.expiry_date" class="badge bg-warning text-dark">Exp: {{ item.expiry_date }}</span>
                            <span v-if="!item.lot_number && !item.expiry_date" class="text-muted">-</span>
                          </td>
                          <td class="text-end text-primary">{{ formatCurrency(item.unit_cost) }}</td>
                          <td class="text-end fw-bold" :class="item.qty > 0 ? 'text-success' : 'text-secondary'">
                            {{ item.qty }}
                            <span v-if="item.qty <= 0" class="badge bg-secondary-subtle text-secondary ms-1">สร้างเฉพาะทะเบียน</span>
                          </td>
                          <td>{{ item.unit_name }}</td>
                          <td class="text-end fw-bold">{{ formatCurrency(item.unit_cost * item.qty) }}</td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr class="table-light fw-bold">
                          <td colspan="7" class="text-end">มูลค่ารวมทั้งสิ้น</td>
                          <td class="text-end text-danger fs-5">{{ formatCurrency(totalAmount) }} ฿</td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Submit -->
                <div class="d-flex justify-content-end mt-4 border-top pt-4">
                  <button type="button" class="btn btn-light rounded-pill px-4 me-2" @click="resetAll">ล้างข้อมูล</button>
                  <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold" :disabled="isSubmitting || selectedItems.length === 0">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span> ยืนยันบันทึกรับเข้าคลัง
                  </button>
                </div>

              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- AI Delivery Note Upload & Scan Modal -->
    <!-- ========================================== -->
    <div v-if="showAIModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1060;" @paste="handlePaste">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <!-- Header -->
          <div class="modal-header bg-gradient-ai text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-stars fs-4 text-warning"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0">AI สแกนอ่านใบส่งของ & เพิ่มรายการรับเข้า</h5>
                <small class="opacity-75">อัปโหลดรูปถ่ายใบส่งของ / ใบกำกับภาษี หรือ PDF แล้วให้ AI ตรวจสอบและดึงรายการลงตารางรับเข้าอัตโนมัติ</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="closeAIModal"></button>
          </div>

          <!-- Body -->
          <div class="modal-body p-4 bg-light">
            <!-- Warning if warehouse not picked -->
            <div v-if="!form.warehouse_id" class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-3">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <div>กรุณาเลือกคลังวัสดุที่ต้องการรับเข้าก่อน เพื่อให้ AI จับคู่รหัสพัสดุในคลังได้อย่างแม่นยำ</div>
            </div>

            <!-- Upload Area & File Preview -->
            <div class="row g-4 mb-3">
              <div :class="parsedData ? 'col-lg-4' : 'col-12'">
                <div 
                  class="upload-dropzone p-4 text-center rounded-4 border-2 border-dashed bg-white shadow-sm cursor-pointer position-relative h-100 d-flex flex-column justify-content-center align-items-center"
                  :class="{ 'border-primary bg-primary-subtle': isDragging }"
                  @dragover.prevent="isDragging = true"
                  @dragleave.prevent="isDragging = false"
                  @drop.prevent="onFileDrop"
                  @click="triggerFileInput"
                >
                  <input 
                    type="file" 
                    ref="fileInputRef" 
                    class="d-none" 
                    accept="image/*,application/pdf"
                    @change="onFileChange"
                  />

                  <!-- Image Preview -->
                  <div v-if="previewUrl" class="mb-2 w-100 text-center position-relative">
                    <img :src="previewUrl" alt="ใบส่งของ" class="img-fluid rounded-3 shadow-sm border" style="max-height: 220px; object-fit: contain;" />
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle" @click.stop="clearFile" title="ลบรูป">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>

                  <!-- PDF Preview Icon -->
                  <div v-else-if="deliveryFile && deliveryFile.type === 'application/pdf'" class="mb-2 text-center position-relative w-100">
                    <i class="bi bi-file-earmark-pdf-fill text-danger display-4"></i>
                    <div class="fw-bold mt-2 text-dark text-truncate px-2">{{ deliveryFile.name }}</div>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle" @click.stop="clearFile" title="ลบไฟล์">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>

                  <!-- Empty upload state -->
                  <div v-else class="py-4">
                    <div class="icon-circle bg-primary-subtle text-primary mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                      <i class="bi bi-cloud-arrow-up-fill fs-2"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">ลากไฟล์ใบส่งของมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h6>
                    <small class="text-muted d-block mb-2">รองรับรูปถ่าย JPG, PNG, WEBP หรือไฟล์ PDF</small>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                      <i class="bi bi-clipboard-check me-1"></i> สามารถกด Ctrl + V เพื่อวางรูปภาพที่คัดลอกมาได้เลย
                    </span>
                  </div>

                  <!-- File name badge if selected -->
                  <div v-if="deliveryFile" class="small text-muted text-truncate w-100 mt-2">
                    <i class="bi bi-file-earmark-check text-success me-1"></i>{{ deliveryFile.name }} ({{ formatFileSize(deliveryFile.size) }})
                  </div>
                </div>
              </div>

              <!-- Action button when file selected but not yet scanned -->
              <div v-if="deliveryFile && !parsedData" class="col-12 text-center mt-3">
                <button 
                  type="button" 
                  class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow"
                  :disabled="isScanning"
                  @click="scanDeliveryNote"
                >
                  <span v-if="isScanning" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  <i v-else class="bi bi-stars text-warning me-2 fs-5"></i>
                  {{ isScanning ? 'AI กำลังอ่านและวิเคราะห์ใบส่งของ...' : 'เริ่มให้ AI อ่านรายการในเอกสาร' }}
                </button>
              </div>

              <!-- Header Summary extracted by AI -->
              <div v-if="parsedData" class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                      <i class="bi bi-receipt text-primary"></i> ข้อมูลหัวบิลที่ AI ตรวจพบ
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" @click="scanDeliveryNote" :disabled="isScanning">
                      <i class="bi bi-arrow-clockwise me-1"></i> สแกนใหม่
                    </button>
                  </div>
                  <div class="card-body p-4">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">ผู้จำหน่าย / ร้านค้า</label>
                        <div class="d-flex align-items-center gap-2">
                          <input type="text" class="form-control form-control-sm fw-bold" v-model="parsedData.vendor_name" />
                          <span v-if="parsedData.matched_vendor" class="badge bg-success-subtle text-success border border-success-subtle text-nowrap">
                            <i class="bi bi-check-circle me-1"></i>จับคู่ร้านค้าสำเร็จ
                          </span>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">เลขที่ใบส่งของ / Invoice</label>
                        <input type="text" class="form-control form-control-sm fw-bold text-primary" v-model="parsedData.invoice_no" placeholder="เช่น IV-69001" />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">วันที่ในใบส่งของ</label>
                        <input type="date" class="form-control form-control-sm" v-model="parsedData.delivery_date" />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">เลขที่ PO อ้างอิง</label>
                        <input type="text" class="form-control form-control-sm" v-model="parsedData.po_number" placeholder="ถ้ามี" />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">ยอดรวมทั้งสิ้น (บิล)</label>
                        <input type="text" class="form-control form-control-sm fw-bold text-danger text-end" :value="formatCurrency(parsedData.total_amount) + ' ฿'" readonly />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Items Table extracted by AI -->
            <div v-if="parsedData && parsedData.items && parsedData.items.length > 0" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
              <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-boxes text-success me-1"></i> รายการวัสดุที่ AI อ่านได้
                  </h6>
                  <span class="badge bg-primary-subtle text-primary rounded-pill">
                    {{ parsedData.items.length }} รายการ (เลือกนำเข้า {{ selectedParsedCount }} รายการ)
                  </span>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary fw-bold" @click="toggleSelectAllItems(true)">เลือกทั้งหมด</button>
                  <span class="text-muted">|</span>
                  <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-secondary" @click="toggleSelectAllItems(false)">ยกเลิกทั้งหมด</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light small text-secondary">
                    <tr>
                      <th class="text-center" style="width: 45px;">
                        <input type="checkbox" class="form-check-input" :checked="isAllParsedSelected" @change="toggleSelectAllItems($event.target.checked)" />
                      </th>
                      <th style="width: 50px;" class="text-center">ลำดับ</th>
                      <th style="min-width: 200px;">ชื่อรายการตามใบส่งของ</th>
                      <th style="min-width: 240px;">จับคู่กับพัสดุในคลัง</th>
                      <th style="width: 130px;">Lot No.</th>
                      <th style="width: 140px;">วันหมดอายุ</th>
                      <th style="width: 110px;" class="text-end">จำนวน</th>
                      <th style="width: 100px;">หน่วยนับ</th>
                      <th style="width: 120px;" class="text-end">ราคา/หน่วย</th>
                      <th style="width: 120px;" class="text-end pe-4">รวม (บาท)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in parsedData.items" :key="idx" :class="{ 'table-light text-muted': !it.selected }">
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input" v-model="it.selected" />
                      </td>
                      <td class="text-center small">{{ idx + 1 }}</td>
                      <td>
                        <div class="fw-bold text-dark">{{ it.item_name }}</div>
                        <small class="text-muted" v-if="it.item_code">รหัส/รุ่น: {{ it.item_code }}</small>
                      </td>
                      <td>
                        <!-- Matching Dropdown -->
                        <select class="form-select form-select-sm" v-model="it.matched_item_id" @change="onItemMatchChange(it)">
                          <option :value="0">✨ [สร้างเป็นพัสดุใหม่ตามบิลนี้]</option>
                          <option v-for="m in items" :key="m.id" :value="m.id">
                            [{{ m.item_code }}] {{ m.item_name }}
                          </option>
                        </select>
                        <small v-if="it.matched_item_id > 0" class="text-success d-block mt-1">
                          <i class="bi bi-check-circle-fill me-1"></i>ตรงกับพัสดุเดิมในคลัง
                        </small>
                        <small v-else class="text-primary d-block mt-1">
                          <i class="bi bi-plus-circle-fill me-1"></i>ระบบจะสร้างรหัสวัสดุใหม่อัตโนมัติ
                        </small>
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm" v-model="it.lot_number" placeholder="Lot No." />
                      </td>
                      <td>
                        <input type="date" class="form-control form-control-sm" v-model="it.expiry_date" />
                      </td>
                      <td>
                        <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm text-end fw-bold text-success" v-model.number="it.qty" />
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm" v-model="it.unit_name" placeholder="หน่วย" />
                      </td>
                      <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end text-primary" v-model.number="it.unit_price" />
                      </td>
                      <td class="text-end fw-bold pe-4">
                        {{ formatCurrency(it.qty * it.unit_price) }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr>
                      <td colspan="9" class="text-end fw-bold">ยอดรวมรายการที่เลือกนำเข้า:</td>
                      <td class="text-end fw-bold text-danger pe-4 fs-6">{{ formatCurrency(selectedParsedTotal) }} ฿</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Error message if scan failed -->
            <div v-if="scanError" class="alert alert-danger border-0 shadow-sm rounded-3 mt-3 d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-octagon-fill fs-5"></i>
              <div>{{ scanError }}</div>
            </div>

          </div>

          <!-- Footer -->
          <div class="modal-footer bg-white px-4 py-3 border-top d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="closeAIModal">
              ยกเลิก
            </button>
            <button 
              type="button" 
              class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
              :disabled="!parsedData || selectedParsedCount === 0"
              @click="applyAIDataToReceiveForm"
            >
              <i class="bi bi-box-arrow-in-down-right fs-5"></i>
              <span>นำเข้ารายการวัสดุสู่แบบฟอร์มรับเข้า ({{ selectedParsedCount }} รายการ)</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- Excel / CSV Bulk Receive & Opening Stock Modal -->
    <!-- ========================================== -->
    <div v-if="showImportModal && isAdmin" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1060;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header bg-success text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <div class="p-2 bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-file-earmark-spreadsheet-fill fs-4"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0">นำเข้า Excel / CSV รับเข้าคลัง &amp; ยกยอดสต๊อก</h5>
                <small class="opacity-75">รองรับทั้งไฟล์ Excel ที่มีหัวตาราง และไฟล์/ข้อความ CSV 7 คอลัมน์ (รหัส, ชื่อ, Lot, Exp/ว่าง, ราคา, จำนวน, หน่วย)</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="closeImportModal"></button>
          </div>

          <div class="modal-body p-4 bg-light">
            <div v-if="!form.warehouse_id" class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-3">
              <i class="bi bi-exclamation-triangle-fill fs-5"></i>
              <div>กรุณาเลือกคลังวัสดุในแบบฟอร์มหลักก่อนนำเข้า</div>
            </div>

            <!-- Template Banner -->
            <div class="card border-0 bg-white shadow-xs rounded-4 mb-3">
              <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small">
                  <div class="fw-bold text-dark">
                    <i class="bi bi-info-circle-fill text-success me-1"></i>
                    คลังปลายทาง: <span class="text-success">{{ activeReceiveWarehouseLabel }}</span>
                  </div>
                  <div class="text-muted">
                    หากรหัส/ชื่อวัสดุยังไม่มีในคลัง ระบบจะสร้างทะเบียนวัสดุ (`inv_items`) และหน่วยนับ (`inv_units`) ให้อัตโนมัติเมื่อกดบันทึกรับเข้า
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" @click="downloadReceiveTemplate">
                  <i class="bi bi-download me-1"></i> ดาวน์โหลดแม่แบบ Excel (.xlsx)
                </button>
              </div>
            </div>

            <!-- Mode Tabs: File Upload or Copy-Paste CSV -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
              <div class="card-header bg-white border-bottom pt-3 px-3 pb-0">
                <ul class="nav nav-tabs border-bottom-0">
                  <li class="nav-item">
                    <button type="button" class="nav-link fw-bold" :class="{ active: importMode === 'file' }" @click="importMode = 'file'">
                      <i class="bi bi-upload me-1"></i> เลือกไฟล์ Excel / CSV
                    </button>
                  </li>
                  <li class="nav-item">
                    <button type="button" class="nav-link fw-bold" :class="{ active: importMode === 'paste' }" @click="importMode = 'paste'">
                      <i class="bi bi-clipboard-plus me-1"></i> วางข้อความ CSV โดยตรง
                    </button>
                  </li>
                </ul>
              </div>

              <div class="card-body p-3">
                <div v-if="importMode === 'file'" class="text-center py-3 border-2 border-dashed rounded-3 bg-light-subtle">
                  <input
                    type="file"
                    ref="excelFileInputRef"
                    class="d-none"
                    accept=".xlsx,.xls,.csv"
                    @change="onImportFileChange"
                  />
                  <div v-if="!importFileName">
                    <i class="bi bi-cloud-arrow-up text-success fs-1 d-block mb-2"></i>
                    <div class="fw-bold text-dark mb-1">เลือกไฟล์ .xlsx, .xls หรือ .csv จากเครื่อง</div>
                    <div class="small text-muted mb-3">รองรับทั้งแบบมีหัวตารางและแบบไม่มีหัวตาราง (รหัส, ชื่อ, Lot, วันหมดอายุ, ราคา, จำนวน, หน่วย)</div>
                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" @click="triggerExcelFileInput">
                      <i class="bi bi-folder2-open me-1"></i> เลือกไฟล์จากเครื่อง
                    </button>
                  </div>
                  <div v-else class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                      <i class="bi bi-file-earmark-check me-1"></i> {{ importFileName }}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="triggerExcelFileInput">เปลี่ยนไฟล์</button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" @click="clearImportData">ล้างรายการ</button>
                  </div>
                </div>

                <div v-else>
                  <label class="form-label small fw-bold mb-1">วางข้อมูล CSV ที่นี่ (1 บรรทัดต่อ 1 รายการ):</label>
                  <textarea
                    class="form-control font-monospace small mb-2"
                    rows="6"
                    v-model="importCsvText"
                    placeholder="ตัวอย่าง:&#10;HK0001,กระดาษทิชชู่ม้วน,6810,,115,98,ม้วน&#10;OS0001,กระดาษทำปกสี เอ4,6810,,100,15,รีม"
                  ></textarea>
                  <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="clearImportData">ล้างข้อความ</button>
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-bold" @click="parsePastedCsv" :disabled="!importCsvText.trim()">
                      <i class="bi bi-table me-1"></i> อ่านข้อมูลและตรวจสอบ (Preview)
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Preview Table -->
            <div v-if="importPreviewRows.length > 0" class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-list-check text-success me-1"></i> ตรวจสอบรายการก่อนนำเข้า
                  </h6>
                  <span class="badge bg-primary-subtle text-primary rounded-pill">
                    ทั้งหมด {{ importPreviewRows.length }} รายการ
                  </span>
                  <span class="badge bg-success-subtle text-success rounded-pill">
                    เลือกนำเข้า {{ importSelectedCount }} รายการ (ยอดรับ > 0: {{ importPositiveSelectedCount }})
                  </span>
                  <span v-if="importZeroQtyCount > 0" class="badge bg-warning-subtle text-warning-emphasis rounded-pill">
                    ยอด 0 จำนวน {{ importZeroQtyCount }} รายการ
                  </span>
                </div>
                <div class="d-flex align-items-center gap-3 small">
                  <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="markOpeningCheck" v-model="importMarkAsOpening" />
                    <label class="form-check-label fw-semibold" for="markOpeningCheck">ตั้งหัวบิลเป็นยอดยกมา (OPEN)</label>
                  </div>
                  <div v-if="importZeroQtyCount > 0" class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="includeZeroQtyCheck" v-model="importIncludeZeroQty" @change="onToggleIncludeZeroQty" />
                    <label class="form-check-label fw-semibold" for="includeZeroQtyCheck">สร้างทะเบียนให้รายการยอด 0 ด้วย</label>
                  </div>
                </div>
              </div>

              <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0 small">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th class="text-center" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" :checked="isAllImportSelected" @change="toggleSelectAllImport($event.target.checked)" />
                      </th>
                      <th>รหัสวัสดุ</th>
                      <th>ชื่อวัสดุ</th>
                      <th>สถานะการจับคู่</th>
                      <th>Lot</th>
                      <th class="text-end">ราคา/หน่วย</th>
                      <th class="text-end">จำนวน</th>
                      <th>หน่วยนับ</th>
                      <th class="text-end pe-3">รวม (บาท)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in importPreviewRows" :key="idx" :class="{ 'table-light text-muted': !row.selected }">
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input" v-model="row.selected" />
                      </td>
                      <td class="font-monospace fw-bold">{{ row.item_code || '(สร้างอัตโนมัติ)' }}</td>
                      <td class="fw-bold text-dark">{{ row.item_name }}</td>
                      <td>
                        <span v-if="row.matched_item_id > 0" class="badge bg-success-subtle text-success border border-success-subtle">
                          ตรงกับพัสดุเดิม
                        </span>
                        <span v-else class="badge bg-primary-subtle text-primary border border-primary-subtle">
                          สร้างพัสดุใหม่ในคลัง
                        </span>
                        <span v-if="row.qty <= 0" class="badge bg-warning-subtle text-warning-emphasis ms-1">ยอด 0</span>
                      </td>
                      <td>{{ row.lot_number || '-' }}</td>
                      <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                      <td class="text-end fw-bold" :class="row.qty > 0 ? 'text-success' : 'text-secondary'">{{ row.qty }}</td>
                      <td>{{ row.unit_name || '-' }}</td>
                      <td class="text-end fw-bold pe-3">{{ formatCurrency(row.qty * row.unit_cost) }}</td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr class="fw-bold">
                      <td colspan="6" class="text-end">รวมรายการที่เลือก:</td>
                      <td class="text-end text-success">{{ importTotalQty }}</td>
                      <td></td>
                      <td class="text-end text-danger pe-3">{{ formatCurrency(importTotalAmount) }} ฿</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white px-4 py-3 border-top d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="closeImportModal">ปิด</button>
            <button
              type="button"
              class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"
              :disabled="importSelectedCount === 0 || !form.warehouse_id"
              @click="applyImportRowsToReceiveForm"
            >
              <i class="bi bi-box-arrow-in-down-right me-1"></i>
              นำเข้ารายการสู่แบบฟอร์มรับเข้า ({{ importSelectedCount }} รายการ)
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
import * as XLSX from 'xlsx';

export default {
  name: 'InventoryReceive',
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      warehouses: [],
      vendors: [],
      items: [],
      activePOs: [],
      selectedItems: [],
      searchQuery: '',
      isDropdownOpen: false,
      isSubmitting: false,
      
      form: {
        warehouse_id: '',
        receipt_date: moment().format('YYYY-MM-DDTHH:mm'),
        receipt_no: `RC-${moment().format('YYMMDDHHmmss')}`,
        invoice_no: '',
        vendor_id: '',
        remark: '',
        po_id: '',
        user_name: localStorage.getItem('user_name') || 'Admin'
      },
      
      currentItem: {
        item_id: '',
        item_code: '',
        item_name: '',
        lot_number: '',
        expiry_date: '',
        qty: 1,
        unit_id: '',
        unit_name: '',
        unit_cost: 0,
        lot_control: 0,
        expiry_control: 0
      },

      // AI Delivery Note Scanning
      showAIModal: false,
      isDragging: false,
      isScanning: false,
      scanError: '',
      deliveryFile: null,
      previewUrl: null,
      parsedData: null,

      // Excel / CSV Bulk Receive & Opening Stock (Admin Only)
      isAdmin: false,
      showImportModal: false,
      importMode: 'file',
      importFileName: '',
      importCsvText: '',
      importPreviewRows: [],
      importMarkAsOpening: true,
      importIncludeZeroQty: true
    };
  },
  computed: {
    filteredItems() {
      if (!this.searchQuery) return this.items;
      const q = this.searchQuery.toLowerCase();
      return this.items.filter(m => 
        m.item_name.toLowerCase().includes(q) || 
        m.item_code.toLowerCase().includes(q)
      );
    },
    totalAmount() {
      return this.selectedItems.reduce((sum, item) => sum + (item.qty * item.unit_cost), 0);
    },
    selectedParsedCount() {
      if (!this.parsedData || !this.parsedData.items) return 0;
      return this.parsedData.items.filter(it => it.selected).length;
    },
    selectedParsedTotal() {
      if (!this.parsedData || !this.parsedData.items) return 0;
      return this.parsedData.items
        .filter(it => it.selected)
        .reduce((sum, it) => sum + ((Number(it.qty) || 0) * (Number(it.unit_price) || 0)), 0);
    },
    isAllParsedSelected() {
      if (!this.parsedData || !this.parsedData.items || this.parsedData.items.length === 0) return false;
      return this.parsedData.items.every(it => it.selected);
    },
    activeReceiveWarehouseLabel() {
      const w = this.warehouses.find(x => String(x.id) === String(this.form.warehouse_id));
      return w ? `[${w.code}] ${w.name}` : 'ยังไม่ได้เลือกคลัง';
    },
    importZeroQtyCount() {
      return this.importPreviewRows.filter(r => Number(r.qty) <= 0).length;
    },
    importSelectedCount() {
      return this.importPreviewRows.filter(r => r.selected).length;
    },
    importPositiveSelectedCount() {
      return this.importPreviewRows.filter(r => r.selected && Number(r.qty) > 0).length;
    },
    importTotalQty() {
      return this.importPreviewRows
        .filter(r => r.selected)
        .reduce((sum, r) => sum + (Number(r.qty) || 0), 0);
    },
    importTotalAmount() {
      return this.importPreviewRows
        .filter(r => r.selected)
        .reduce((sum, r) => sum + ((Number(r.qty) || 0) * (Number(r.unit_cost) || 0)), 0);
    },
    isAllImportSelected() {
      if (this.importPreviewRows.length === 0) return false;
      return this.importPreviewRows.every(r => r.selected);
    }
  },
  mounted() {
    this.checkAdminStatus();
    this.fetchMasters();
  },
  beforeUnmount() {
    if (this.previewUrl) {
      URL.revokeObjectURL(this.previewUrl);
    }
  },
  methods: {
    async checkAdminStatus() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data && response.data.status === 'success') {
          const accessUser = response.data.access_user ? String(response.data.access_user).split(':') : [];
          this.isAdmin = accessUser.includes('administrator');
        } else {
          this.isAdmin = false;
        }
      } catch (err) {
        console.error('Error checking admin status in Receive:', err);
        this.isAdmin = false;
      }
    },
    formatCurrency(val) {
      return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    async fetchMasters() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const wh = this.warehouseId || localStorage.getItem('selected_warehouse_id');
        const poUrl = wh ? `/api-digital/inventory/po_active_list.php?warehouse_id=${wh}` : '/api-digital/inventory/po_active_list.php';
        const [whRes, poRes] = await Promise.all([
          axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`),
          axios.get(poUrl)
        ]);
        if (whRes.data.status === 'success') {
          // Filter only active warehouses
          this.warehouses = whRes.data.data.filter(w => parseInt(w.active) === 1);
          if (wh) {
            this.form.warehouse_id = parseInt(wh);
            this.onWarehouseChange();
          }
        }
        if (poRes.data.status === 'success') {
          this.activePOs = poRes.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async onPOSelect() {
      if (!this.form.po_id) {
        // Clear if deselected (maybe keep warehouse, but clear items)
        this.selectedItems = [];
        return;
      }
      try {
        const res = await axios.get(`/api-digital/inventory/po_get.php?id=${this.form.po_id}`);
        if (res.data.status === 'success') {
          const poData = res.data.data;
          this.form.warehouse_id = poData.warehouse_id;
          
          // Clear current selected and populate from PO
          this.selectedItems = [];
          
          poData.items.forEach(i => {
            const qtyOrdered = Number(i.qty_ordered);
            const qtyReceived = Number(i.qty_received || 0);
            const qtyRemaining = qtyOrdered - qtyReceived;
            
            if (qtyRemaining > 0) {
              this.selectedItems.push({
                po_item_id: i.id,
                item_id: i.item_id,
                item_code: i.item_code,
                item_name: i.item_name_en && i.item_name && i.item_name !== i.item_name_en 
                  ? `${i.item_name} (${i.item_name_en})` 
                  : (i.item_name_en || i.item_name || 'ไม่ระบุชื่อ'),
                item_name_en: i.item_name_en || '',
                unit_id: i.unit_id,
                unit_name: i.unit_name,
                qty: qtyRemaining, // Suggest remaining
                unit_cost: Number(i.unit_price),
                lot_number: '',
                expiry_date: '',
                lot_control: 0, // In real scenario, we might need to fetch item control configs
                expiry_control: 0
              });
            }
          });
          
          if (this.selectedItems.length === 0) {
            Swal.fire('Info', 'PO นี้รับวัสดุครบหมดแล้ว', 'info');
          } else {
            Swal.fire('Success', 'ดึงข้อมูลรายการวัสดุจาก PO สำเร็จ กรุณาระบุ Lot/Expiry (ถ้ามี)', 'success');
            // Fetch items and vendors for this warehouse
            this.fetchItems();
            this.fetchVendors();
          }
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'ไม่สามารถดึงข้อมูล PO ได้', 'error');
      }
    },
    async onWarehouseChange() {
      await Promise.all([
        this.fetchItems(),
        this.fetchVendors()
      ]);
    },
    async fetchVendors() {
      this.vendors = [];
      if (!this.form.warehouse_id) return;
      try {
        const res = await axios.get(`/api-digital/inventory/vendors.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data?.status === 'success') {
          this.vendors = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching vendors in Receive:', err);
      }
    },
    async fetchItems() {
      // Fetch items only for the selected warehouse
      this.items = [];
      this.searchQuery = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      if (!this.form.warehouse_id) return;
      
      try {
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.form.warehouse_id}`);
        if (res.data.status === 'success') {
          this.items = res.data.data.filter(i => parseInt(i.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    selectItem(m) {
      this.currentItem.item_id = m.id;
      this.currentItem.item_code = m.item_code;
      this.currentItem.item_name = m.item_name;
      this.currentItem.unit_id = m.unit_id;
      this.currentItem.unit_name = m.unit_name;
      this.currentItem.unit_cost = parseFloat(m.standard_price) || 0;
      this.currentItem.lot_control = m.lot_control;
      this.currentItem.expiry_control = m.expiry_control;
      
      this.searchQuery = `[${m.item_code}] ${m.item_name}`;
      this.isDropdownOpen = false;
    },
    addItem() {
      if (!this.currentItem.item_id || this.currentItem.qty <= 0) return;
      
      // Control Checks
      if (this.currentItem.lot_control && !this.currentItem.lot_number) {
        return Swal.fire('ข้อมูลไม่ครบ', 'วัสดุนี้จำเป็นต้องระบุเลข Lot', 'warning');
      }
      if (this.currentItem.expiry_control && !this.currentItem.expiry_date) {
        return Swal.fire('ข้อมูลไม่ครบ', 'วัสดุนี้จำเป็นต้องระบุวันหมดอายุ', 'warning');
      }

      this.selectedItems.push({ ...this.currentItem });
      this.resetCurrentItem();
      this.searchQuery = '';
    },
    removeItem(index) {
      this.selectedItems.splice(index, 1);
    },
    resetCurrentItem() {
      this.currentItem = {
        item_id: '', item_code: '', item_name: '', lot_number: '', expiry_date: '',
        qty: 1, unit_id: '', unit_name: '', unit_cost: 0, lot_control: 0, expiry_control: 0
      };
    },
    resetAll() {
      this.form.receipt_no = `RC-${moment().format('YYMMDDHHmmss')}`;
      this.form.invoice_no = '';
      this.form.vendor_id = '';
      this.form.po_id = '';
      this.form.remark = '';
      this.selectedItems = [];
      this.resetCurrentItem();
      this.searchQuery = '';
    },
    async submitTx() {
      if (this.selectedItems.length === 0) return;
      
      this.isSubmitting = true;
      try {
        const payload = {
          warehouse_id: this.form.warehouse_id,
          receipt_date: this.form.receipt_date,
          receipt_no: this.form.receipt_no,
          invoice_no: this.form.invoice_no,
          vendor_id: this.form.vendor_id,
          remark: this.form.remark,
          po_id: this.form.po_id,
          user_name: this.form.user_name,
          items: this.selectedItems
        };

        const res = await axios.post('/api-digital/inventory/receive.php', payload);
        if (res.data.status === 'success') {
          Swal.fire('บันทึกสำเร็จ!', 'รับเข้าวัสดุเรียบร้อยแล้ว ยอดคงเหลือถูกอัปเดตอัตโนมัติ', 'success').then(() => {
            this.resetAll();
            this.fetchItems();
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบขัดข้องระหว่างบันทึก', 'error');
      } finally {
        this.isSubmitting = false;
      }
    },

    // ==========================================
    // AI Delivery Note Scanning Methods
    // ==========================================
    openAIDeliveryModal() {
      if (!this.form.warehouse_id && this.warehouses.length > 0) {
        // Auto-select first active warehouse if not yet chosen
        this.form.warehouse_id = this.warehouses[0].id;
        this.onWarehouseChange();
      }
      this.showAIModal = true;
    },
    closeAIModal() {
      this.showAIModal = false;
      this.isScanning = false;
      this.scanError = '';
    },
    triggerFileInput() {
      if (this.$refs.fileInputRef) {
        this.$refs.fileInputRef.click();
      }
    },
    onFileChange(e) {
      const file = e.target.files?.[0];
      if (file) {
        this.setDeliveryFile(file);
      }
    },
    onFileDrop(e) {
      this.isDragging = false;
      const file = e.dataTransfer?.files?.[0];
      if (file) {
        this.setDeliveryFile(file);
      }
    },
    handlePaste(e) {
      const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
      if (!items) return;
      for (const item of items) {
        if (item.type.indexOf('image') !== -1) {
          const blob = item.getAsFile();
          if (blob) {
            this.setDeliveryFile(blob, `pasted-delivery-note-${Date.now()}.png`);
            break;
          }
        }
      }
    },
    setDeliveryFile(file, customName) {
      this.deliveryFile = file;
      if (customName && !file.name) {
        try {
          Object.defineProperty(file, 'name', { value: customName });
        } catch (e) {}
      }
      this.parsedData = null;
      this.scanError = '';

      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = null;
      }

      if (file.type && file.type.startsWith('image/')) {
        this.previewUrl = URL.createObjectURL(file);
      }
    },
    clearFile() {
      this.deliveryFile = null;
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = null;
      }
      this.parsedData = null;
      this.scanError = '';
      if (this.$refs.fileInputRef) {
        this.$refs.fileInputRef.value = '';
      }
    },
    formatFileSize(bytes) {
      if (!bytes) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },
    async scanDeliveryNote() {
      if (!this.deliveryFile) {
        this.scanError = 'กรุณาเลือกไฟล์ใบส่งของก่อนดำเนินการ';
        return;
      }

      this.isScanning = true;
      this.scanError = '';

      try {
        const formData = new FormData();
        formData.append('file', this.deliveryFile);
        formData.append('warehouse_id', this.form.warehouse_id || '');

        const res = await axios.post('/api-digital/inventory/parse_delivery_note.php', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (res.data.status === 'success') {
          this.parsedData = res.data.data;
          this.scanError = '';

          // Sync matched items with current active catalog in this.items
          if (this.parsedData.items) {
            for (const it of this.parsedData.items) {
              if (it.matched_item_id > 0) {
                const found = this.items.find(m => m.id == it.matched_item_id);
                if (found) {
                  it.matched_item_code = found.item_code;
                  it.matched_item_name = found.item_name;
                  it.matched_unit_id = found.unit_id;
                  it.matched_unit_name = found.unit_name || it.unit_name;
                  it.lot_control = found.lot_control;
                  it.expiry_control = found.expiry_control;
                }
              }
            }
          }
        } else if (res.data.status === 'need_key') {
          this.scanError = res.data.message;
        } else {
          this.scanError = res.data.message || 'เกิดข้อผิดพลาดในการสแกนอ่านเอกสาร';
        }
      } catch (err) {
        console.error('Scan error:', err);
        this.scanError = 'ไม่สามารถติดต่อเซิร์ฟเวอร์ AI ได้: ' + (err.response?.data?.message || err.message);
      } finally {
        this.isScanning = false;
      }
    },
    onItemMatchChange(it) {
      if (it.matched_item_id > 0) {
        const found = this.items.find(m => m.id == it.matched_item_id);
        if (found) {
          it.matched_item_code = found.item_code;
          it.matched_item_name = found.item_name;
          it.matched_unit_id = found.unit_id;
          it.matched_unit_name = found.unit_name || it.unit_name;
          it.lot_control = found.lot_control;
          it.expiry_control = found.expiry_control;
          if (!it.unit_price || it.unit_price <= 0) {
            it.unit_price = parseFloat(found.standard_price) || 0;
          }
        }
      } else {
        it.matched_item_code = '';
        it.matched_item_name = '';
        it.matched_unit_id = 0;
        it.lot_control = 0;
        it.expiry_control = 0;
      }
    },
    toggleSelectAllItems(val) {
      if (this.parsedData?.items) {
        this.parsedData.items.forEach(it => {
          it.selected = val;
        });
      }
    },
    applyAIDataToReceiveForm() {
      if (!this.parsedData || !this.parsedData.items) return;

      // 1. Header Information
      if (this.parsedData.invoice_no && !this.form.invoice_no) {
        this.form.invoice_no = this.parsedData.invoice_no;
      }
      if (this.parsedData.delivery_date) {
        this.form.receipt_date = moment(this.parsedData.delivery_date).format('YYYY-MM-DDTHH:mm');
      }

      // Match Vendor
      if (this.parsedData.vendor_id && !this.form.vendor_id) {
        this.form.vendor_id = this.parsedData.vendor_id;
      } else if (!this.form.vendor_id && this.parsedData.vendor_name) {
        const vName = this.parsedData.vendor_name.toLowerCase();
        const foundV = this.vendors.find(v => 
          v.vendor_name.toLowerCase().includes(vName) || vName.includes(v.vendor_name.toLowerCase())
        );
        if (foundV) {
          this.form.vendor_id = foundV.id;
        }
      }

      // Match PO if PO number recognized
      if (this.parsedData.po_number && !this.form.po_id) {
        const pNum = this.parsedData.po_number.toLowerCase().replace(/[^a-z0-9]/g, '');
        const matchedPO = this.activePOs.find(p => 
          p.po_number.toLowerCase().replace(/[^a-z0-9]/g, '').includes(pNum)
        );
        if (matchedPO) {
          this.form.po_id = matchedPO.id;
        }
      }

      if (this.parsedData.remark && !this.form.remark) {
        this.form.remark = `[นำเข้าจากใบส่งของ AI] ${this.parsedData.remark}`;
      }

      // 2. Add Selected Items into Receive List
      let count = 0;
      for (const it of this.parsedData.items) {
        if (!it.selected) continue;

        let itemId = it.matched_item_id || 0;
        let itemCode = '';
        let itemName = it.item_name;
        let unitId = it.matched_unit_id || 0;
        let unitName = it.matched_unit_name || it.unit_name || 'หน่วย';
        let lotControl = it.lot_control || 0;
        let expiryControl = it.expiry_control || 0;

        if (itemId > 0) {
          const m = this.items.find(x => x.id == itemId);
          if (m) {
            itemCode = m.item_code;
            itemName = m.item_name;
            unitId = m.unit_id;
            unitName = m.unit_name || unitName;
            lotControl = m.lot_control;
            expiryControl = m.expiry_control;
          }
        } else {
          itemCode = it.item_code || '';
        }

        this.selectedItems.push({
          item_id: itemId,
          item_code: itemCode,
          item_name: itemName,
          item_name_en: it.item_code || '',
          lot_number: it.lot_number || '',
          expiry_date: it.expiry_date || '',
          qty: Number(it.qty) || 1,
          unit_id: unitId,
          unit_name: unitName,
          unit_cost: Number(it.unit_price) || 0,
          lot_control: lotControl,
          expiry_control: expiryControl
        });
        count++;
      }

      this.closeAIModal();

      Swal.fire({
        icon: 'success',
        title: 'นำเข้ารายการวัสดุสำเร็จ!',
        html: `AI ได้อ่านและนำเข้ารายการวัสดุจากใบส่งของจำนวน <strong>${count}</strong> รายการ ลงในตารางรับเข้าเรียบร้อยแล้ว`,
        timer: 2500,
        showConfirmButton: false
      });
    },

    // ==========================================
    // Excel / CSV Bulk Receive & Opening Stock
    // ==========================================
    openImportModal() {
      if (!this.isAdmin) return;
      if (!this.form.warehouse_id && this.warehouses.length > 0) {
        this.form.warehouse_id = this.warehouses[0].id;
        this.onWarehouseChange();
      }
      this.showImportModal = true;
    },
    closeImportModal() {
      this.showImportModal = false;
    },
    triggerExcelFileInput() {
      if (this.$refs.excelFileInputRef) {
        this.$refs.excelFileInputRef.value = '';
        this.$refs.excelFileInputRef.click();
      }
    },
    clearImportData() {
      this.importFileName = '';
      this.importCsvText = '';
      this.importPreviewRows = [];
    },
    downloadReceiveTemplate() {
      const sampleRows = [
        {
          'รหัสวัสดุ': 'HK0001',
          'ชื่อวัสดุ (จำเป็น)': 'กระดาษทิชชู่ม้วน',
          'เลข Lot': '6810',
          'วันหมดอายุ (YYYY-MM-DD)': '',
          'ราคา/หน่วย': 115,
          'จำนวนรับเข้า (จำเป็น)': 98,
          'หน่วยนับ': 'ม้วน',
          'หมวดหมู่': 'งานบ้านงานครัว',
          'ประเภทวัสดุ': 'วัสดุงานบ้านงานครัว'
        },
        {
          'รหัสวัสดุ': 'OS0001',
          'ชื่อวัสดุ (จำเป็น)': 'กระดาษทำปกสี เอ4',
          'เลข Lot': '6810',
          'วันหมดอายุ (YYYY-MM-DD)': '',
          'ราคา/หน่วย': 100,
          'จำนวนรับเข้า (จำเป็น)': 15,
          'หน่วยนับ': 'รีม',
          'หมวดหมู่': 'สำนักงาน',
          'ประเภทวัสดุ': 'วัสดุสำนักงาน'
        }
      ];
      const ws = XLSX.utils.json_to_sheet(sampleRows);
      ws['!cols'] = [
        { wch: 14 }, { wch: 32 }, { wch: 12 }, { wch: 22 },
        { wch: 14 }, { wch: 20 }, { wch: 12 }, { wch: 20 }, { wch: 22 }
      ];
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Receive_Import');
      XLSX.writeFile(wb, 'แบบฟอร์มรับเข้าคลัง_ยกยอดสต๊อก.xlsx');
    },
    parseCsvText(text) {
      const rows = [];
      let row = [];
      let field = '';
      let inQuotes = false;
      const src = String(text || '').replace(/^\uFEFF/, '');

      for (let i = 0; i < src.length; i++) {
        const ch = src[i];
        if (inQuotes) {
          if (ch === '"' && src[i + 1] === '"') {
            field += '"';
            i++;
          } else if (ch === '"') {
            inQuotes = false;
          } else {
            field += ch;
          }
        } else if (ch === '"') {
          inQuotes = true;
        } else if (ch === ',') {
          row.push(field.trim());
          field = '';
        } else if (ch === '\r' || ch === '\n') {
          if (ch === '\r' && src[i + 1] === '\n') i++;
          row.push(field.trim());
          if (row.some(cell => cell !== '')) rows.push(row);
          row = [];
          field = '';
        } else {
          field += ch;
        }
      }
      row.push(field.trim());
      if (row.some(cell => cell !== '')) rows.push(row);
      return rows;
    },
    onImportFileChange(event) {
      const file = event.target.files?.[0];
      if (!file) return;
      this.importFileName = file.name;

      const isCsv = file.name.toLowerCase().endsWith('.csv');
      const reader = new FileReader();

      reader.onload = (e) => {
        try {
          let matrix = [];
          if (isCsv) {
            matrix = this.parseCsvText(e.target.result);
          } else {
            const data = new Uint8Array(e.target.result);
            const wb = XLSX.read(data, { type: 'array' });
            const firstSheet = wb.Sheets[wb.SheetNames[0]];
            matrix = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' })
              .filter(r => Array.isArray(r) && r.some(c => String(c ?? '').trim() !== ''));
          }
          this.buildImportPreviewFromMatrix(matrix);
        } catch (err) {
          console.error('Import parse error:', err);
          Swal.fire('อ่านไฟล์ไม่สำเร็จ', 'กรุณาตรวจสอบรูปแบบไฟล์ Excel / CSV อีกครั้ง', 'error');
        }
      };

      if (isCsv) {
        reader.readAsText(file, 'UTF-8');
      } else {
        reader.readAsArrayBuffer(file);
      }
    },
    parsePastedCsv() {
      const matrix = this.parseCsvText(this.importCsvText);
      this.buildImportPreviewFromMatrix(matrix);
    },
    buildImportPreviewFromMatrix(matrix) {
      if (!matrix || matrix.length === 0) {
        Swal.fire('ไม่พบข้อมูล', 'ไม่พบรายการในไฟล์หรือข้อความที่วาง', 'warning');
        this.importPreviewRows = [];
        return;
      }

      const firstRowStr = matrix[0].map(c => String(c ?? '').trim().toLowerCase());
      const headerKeywords = ['รหัส', 'ชื่อ', 'รายการ', 'จำนวน', 'ราคา', 'หน่วย', 'lot', 'code', 'name', 'qty', 'price', 'unit'];
      const hasHeader = firstRowStr.some(cell => headerKeywords.some(kw => cell.includes(kw)));

      const findColIndex = (headers, aliases) => {
        for (let i = 0; i < headers.length; i++) {
          const h = headers[i].replace(/\s+/g, '');
          if (aliases.some(a => h.includes(a))) return i;
        }
        return -1;
      };

      let colIdx = {
        code: 0,
        name: 1,
        lot: 2,
        exp: 3,
        cost: 4,
        qty: 5,
        unit: 6,
        cat: 7,
        type: 8
      };

      let dataRows = matrix;
      if (hasHeader) {
        const normHeaders = firstRowStr;
        colIdx = {
          code: findColIndex(normHeaders, ['รหัส', 'item_code', 'code']),
          name: findColIndex(normHeaders, ['ชื่อวัสดุ', 'ชื่อยา', 'ชื่อสินค้า', 'รายการ', 'item_name', 'name']),
          lot: findColIndex(normHeaders, ['lot', 'ล็อต', 'lot_number']),
          exp: findColIndex(normHeaders, ['หมดอายุ', 'expiry', 'exp']),
          cost: findColIndex(normHeaders, ['ราคา', 'cost', 'price']),
          qty: findColIndex(normHeaders, ['จำนวน', 'qty', 'quantity']),
          unit: findColIndex(normHeaders, ['หน่วย', 'unit']),
          cat: findColIndex(normHeaders, ['หมวด', 'category']),
          type: findColIndex(normHeaders, ['ประเภท', 'item_type', 'type'])
        };
        dataRows = matrix.slice(1);
      }

      const parsed = [];
      for (const cols of dataRows) {
        const getVal = (idx) => (idx >= 0 && idx < cols.length ? String(cols[idx] ?? '').trim() : '');
        const itemCode = getVal(colIdx.code);
        const itemName = getVal(colIdx.name);
        if (!itemCode && !itemName) continue;

        const lotNumber = getVal(colIdx.lot);
        const expRaw = getVal(colIdx.exp);
        const expiryDate = /^\d{4}-\d{2}-\d{2}$/.test(expRaw) ? expRaw : '';
        const unitCost = parseFloat(getVal(colIdx.cost).replace(/,/g, '')) || 0;
        const qtyVal = parseFloat(getVal(colIdx.qty).replace(/,/g, ''));
        const qty = Number.isFinite(qtyVal) ? qtyVal : 0;
        const unitName = getVal(colIdx.unit) || 'หน่วย';
        const categoryName = getVal(colIdx.cat);
        const itemTypeName = getVal(colIdx.type);

        let matched = null;
        if (itemCode) {
          matched = this.items.find(m => String(m.item_code || '').toLowerCase() === itemCode.toLowerCase());
        } else if (itemName) {
          matched = this.items.find(m => String(m.item_name || '').trim().toLowerCase() === itemName.toLowerCase());
        }

        parsed.push({
          selected: qty > 0 || this.importIncludeZeroQty,
          item_id: matched ? matched.id : 0,
          matched_item_id: matched ? matched.id : 0,
          item_code: matched ? matched.item_code : itemCode,
          item_name: matched ? matched.item_name : (itemName || itemCode),
          item_name_en: '',
          lot_number: lotNumber,
          expiry_date: expiryDate,
          unit_cost: unitCost,
          qty,
          unit_id: matched ? matched.unit_id : 0,
          unit_name: matched ? (matched.unit_name || unitName) : unitName,
          category_name: categoryName,
          item_type_name: itemTypeName,
          lot_control: matched ? matched.lot_control : (lotNumber ? 1 : 0),
          expiry_control: matched ? matched.expiry_control : (expiryDate ? 1 : 0)
        });
      }

      if (parsed.length === 0) {
        Swal.fire('ไม่พบรายการที่ใช้งานได้', 'กรุณาตรวจสอบว่ามีชื่อวัสดุและจำนวนในคอลัมน์ที่ถูกต้อง', 'warning');
      }
      this.importPreviewRows = parsed;
    },
    onToggleIncludeZeroQty() {
      this.importPreviewRows.forEach(r => {
        if (Number(r.qty) <= 0) {
          r.selected = this.importIncludeZeroQty;
        }
      });
    },
    toggleSelectAllImport(val) {
      this.importPreviewRows.forEach(r => {
        r.selected = val;
      });
    },
    applyImportRowsToReceiveForm() {
      const chosen = this.importPreviewRows.filter(r => r.selected);
      if (chosen.length === 0) return;

      if (this.importMarkAsOpening) {
        if (String(this.form.receipt_no || '').startsWith('RC-')) {
          this.form.receipt_no = `OPEN-${moment().format('YYMMDDHHmm')}`;
        }
        if (!this.form.remark) {
          this.form.remark = 'ยกยอดคงเหลือเข้าคลัง (Import Excel/CSV)';
        }
      }

      chosen.forEach(r => {
        this.selectedItems.push({
          item_id: r.item_id,
          item_code: r.item_code,
          item_name: r.item_name,
          item_name_en: r.item_name_en,
          lot_number: r.lot_number,
          expiry_date: r.expiry_date,
          qty: r.qty,
          unit_id: r.unit_id,
          unit_name: r.unit_name,
          unit_cost: r.unit_cost,
          category_name: r.category_name,
          item_type_name: r.item_type_name,
          lot_control: r.lot_control,
          expiry_control: r.expiry_control
        });
      });

      this.closeImportModal();
      Swal.fire({
        icon: 'success',
        title: 'นำเข้ารายการเรียบร้อย',
        html: `เพิ่มรายการลงตารางเตรียมรับเข้าแล้ว <strong>${chosen.length}</strong> รายการ<br/><small class="text-muted">กรุณาตรวจสอบข้อมูลและกด "ยืนยันบันทึกรับเข้าคลัง" ด้านล่าง</small>`,
        timer: 2800,
        showConfirmButton: false
      });
    }
  }
};
</script>

<style scoped>
.dropdown-item-custom {
  cursor: pointer;
}
.dropdown-item-custom:hover {
  background-color: #f8f9fa;
}
.pointer-events-none {
  pointer-events: none;
}
.bg-gradient-ai {
  background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
}
.upload-dropzone {
  transition: all 0.25s ease;
  min-height: 220px;
}
.upload-dropzone:hover {
  border-color: #0d6efd !important;
  background-color: #f8faff !important;
}
.cursor-pointer {
  cursor: pointer;
}
.border-dashed {
  border-style: dashed !important;
}
</style>
