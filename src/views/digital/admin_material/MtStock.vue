<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/material-admin">หน้าหลักวัสดุ</router-link>
              </li>
              <li class="breadcrumb-item active" aria-current="page">คลังสินค้า (Stock)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">รายการคลังวัสดุ</h2>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button @click="openLogModal" class="btn btn-outline-info rounded-pill px-3 shadow-sm">
            <i class="bi bi-clock-history me-1"></i> ดูประวัติ (Log)
          </button>
          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-1"></i>หน้าหลัก
          </router-link>
          <button @click="openCategoryModal" class="btn btn-outline-warning rounded-pill px-3 shadow-sm">
            <i class="bi bi-tags me-1"></i> จัดการประเภท
          </button>
          <button @click="openCompanyModal" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
            <i class="bi bi-building me-1"></i> จัดการผู้จำหน่าย
          </button>
          <button class="btn btn-outline-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" :disabled="isExporting" @click="exportToExcel">
            <span v-if="isExporting" class="spinner-border spinner-border-sm" role="status"></span>
            <i v-else class="bi bi-file-earmark-excel-fill text-success"></i>
            <span>ส่งออก Excel</span>
          </button>
          <!-- <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" @click="downloadTemplate">
            <i class="bi bi-download me-1"></i> โหลด Template
          </button>
          <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" @click="$refs.excelInput.click()">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> นำเข้าวัสดุ
          </button> -->
          <input type="file" ref="excelInput" class="d-none" accept=".xlsx, .xls" @change="importExcel" />
          <button class="btn btn-success rounded-pill px-4 shadow-sm" @click="openModal()">
            <i class="bi bi-plus-lg me-2"></i>เพิ่มวัสดุใหม่
          </button>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-5">
              <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"
                  ><i class="bi bi-search"></i
                ></span>
                <input
                  type="text"
                  class="form-control border-start-0"
                  v-model="search"
                  placeholder="ค้นหารหัส, ชื่อ, ประเภท..."
                  @keyup.enter="fetchMaterials"
                />
                <button class="btn btn-success" @click="fetchMaterials">ค้นหา</button>
              </div>
            </div>
            
            <div class="col-md-2">
              <select class="form-select border-0 shadow-sm" v-model="selectedCategory">
                <option value="all">ทุกประเภท</option>
                <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>

            <div class="col-md-5 d-flex align-items-center">
              <div class="form-check form-switch ms-md-4">
                <input
                  class="form-check-input"
                  type="checkbox"
                  role="switch"
                  id="lowStockSwitch"
                  v-model="lowStockOnly"
                  @change="fetchMaterials"
                />
                <label class="form-check-label" for="lowStockSwitch"
                  >แสดงเฉพาะของใกล้หมด (<i class="bi bi-circle-fill text-danger small"></i>)</label
                >
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">รหัส</th>
                  <th>ชื่ออุปกรณ์</th>
                  <th>ประเภท</th>
                  <th class="text-center">คงเหลือ</th>
                  <th>หน่วย</th>
                  <th class="text-center">แจ้งเตือน(ขั้นต่ำ)</th>
                  <th class="text-center">สถานะ</th>
                  <th class="text-end pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="filteredMaterials.length === 0">
                  <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลวัสดุ</td>
                </tr>
                <template
                  v-for="item in filteredMaterials"
                  :key="item.id"
                >
                <tr
                  :class="{ 'table-danger bg-opacity-10': item.balance <= item.min_alert, 'border-transparent': expandedRowId === item.id }"
                >
                  <td class="ps-4 fw-bold">{{ item.code }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="flex-shrink-0" style="width: 40px; height: 40px;">
                        <img
                          v-if="item.image_path"
                          :src="getImageUrl(item.image_path)"
                          class="img-fluid rounded border shadow-sm cursor-pointer"
                          style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;"
                          @click="viewImage(getImageUrl(item.image_path), item.name)"
                          title="คลิกเพื่อดูรูปขยาย"
                        />
                        <div v-else class="d-flex justify-content-center align-items-center bg-light rounded border text-muted shadow-sm h-100 w-100">
                          <i class="bi bi-box"></i>
                        </div>
                      </div>
                      <div>
                        <i
                          v-if="item.balance <= item.min_alert"
                          class="bi bi-exclamation-circle-fill text-danger me-1"
                          title="ของใกล้หมด"
                        ></i>
                        {{ item.name }}
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-secondary rounded-pill">{{ item.type }}</span>
                  </td>
                  <td class="text-center">
                    <span
                      class="fs-5 fw-bold"
                      :class="item.balance <= item.min_alert ? 'text-danger' : 'text-success'"
                      >{{ item.balance }}</span
                    >
                  </td>
                  <td>{{ item.unit }}</td>
                  <td class="text-center">{{ item.min_alert }}</td>
                  <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center m-0">
                      <input class="form-check-input cursor-pointer" type="checkbox" role="switch" :checked="item.is_active == 1" @change="toggleStatus(item, $event)" title="เปิด/ปิด การใช้งาน">
                    </div>
                  </td>
                  <td class="text-end pe-4">
                    <button
                      class="btn btn-sm rounded-circle me-2"
                      :class="expandedRowId === item.id ? 'btn-info text-white' : 'btn-outline-info'"
                      @click="toggleRow(item)"
                      title="ดู Lot"
                    >
                      <i class="bi" :class="expandedRowId === item.id ? 'bi-chevron-up' : 'bi-tags'"></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-success rounded-circle me-2"
                      @click="openModal(item)"
                      title="แก้ไข"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <!-- Allow delete for admins if no history, or just generally but confirm -->
                    <button
                      class="btn btn-sm btn-outline-danger rounded-circle"
                      @click="deleteItem(item.id, item.name)"
                      title="ลบ"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
                <!-- Expandable Row for Lots -->
                <tr v-if="expandedRowId === item.id" class="bg-light bg-opacity-50">
                  <td colspan="9" class="p-0 border-bottom">
                    <div class="p-3 px-4 border-start border-info border-4">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-info fw-bold"><i class="bi bi-box-seam me-2"></i>รายการ Lot ที่มีในคลัง</h6>
                        <span class="badge bg-info text-white rounded-pill">รวม {{ item.balance }} {{ item.unit }}</span>
                      </div>
                      
                      <div v-if="isLoadingLots" class="text-center py-3 text-muted">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div> กำลังโหลด...
                      </div>
                      <div v-else-if="expandedLots.length === 0" class="text-center py-3 text-muted bg-white rounded border">
                        ไม่พบข้อมูล Lot ในสต็อก (อาจเป็นสินค้ายกยอด)
                      </div>
                      <div v-else class="table-responsive bg-white rounded border shadow-sm">
                        <table class="table table-sm table-hover align-middle mb-0">
                          <thead class="table-light text-muted">
                            <tr>
                              <th class="ps-3 py-2">วันที่รับเข้า</th>
                              <th class="py-2">เลข Lot</th>
                              <th class="py-2">แหล่งที่มา / ผู้จำหน่าย</th>
                              <th class="text-end py-2">ราคา/หน่วย</th>
                              <th class="text-center py-2">รับมา</th>
                              <th class="text-center py-2">คงเหลือ</th>
                              <th class="text-end pe-3 py-2">มูลค่าคงเหลือ</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="lot in expandedLots" :key="lot.id" :class="{'text-danger': lot.remaining_qty <= 0}">
                              <td class="ps-3">{{ formatDateShort(lot.receive_date) }}</td>
                              <td class="fw-bold">{{ lot.lot_number || 'ไม่ระบุ (Auto)' }}</td>
                              <td>
                                <span v-if="lot.vendor" class="badge bg-light text-dark border">
                                  <i class="bi bi-building me-1 text-primary"></i>{{ lot.vendor }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                              </td>
                              <td class="text-end">{{ Number(lot.price_per_unit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿</td>
                              <td class="text-center text-muted">{{ lot.original_qty }}</td>
                              <td class="text-center fw-bold" :class="lot.remaining_qty > 0 ? 'text-success' : ''">{{ lot.remaining_qty }}</td>
                              <td class="text-end fw-bold text-primary pe-3">{{ (lot.remaining_qty * lot.price_per_unit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿</td>
                            </tr>
                          </tbody>
                          <tfoot class="table-light fw-bold text-dark border-top">
                            <tr>
                              <td colspan="4" class="text-end py-2">รวมทั้งหมด:</td>
                              <td class="text-center py-2 text-muted">{{ expandedLots.reduce((sum, lot) => sum + (Number(lot.original_qty) || 0), 0).toLocaleString() }}</td>
                              <td class="text-center py-2 text-success">{{ expandedLots.reduce((sum, lot) => sum + (Number(lot.remaining_qty) || 0), 0).toLocaleString() }}</td>
                              <td class="text-end pe-3 py-2 text-primary">{{ expandedLots.reduce((sum, lot) => sum + (Number(lot.remaining_qty) * Number(lot.price_per_unit)), 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿</td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Add/Edit Material -->
      <div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold">
                {{ form.id ? 'แก้ไขข้อมูลวัสดุ' : 'เพิ่มวัสดุใหม่' }}
              </h5>
              <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body p-4">
              <form @submit.prevent="saveMaterial">
                <div class="row g-3 mb-3">
                  <div class="col-md-6 position-relative">
                    <label class="form-label fw-bold">ประเภท / หมวดหมู่ <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input
                        type="text"
                        class="form-control"
                        v-model="form.type"
                        required
                        placeholder="ค้นหา หรือพิมพ์ประเภท..."
                        @focus="isTypeDropdownOpen = true"
                        @input="onTypeInput"
                        @blur="closeTypeDropdown"
                      />
                      <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="toggleTypeDropdown"
                        tabindex="-1"
                      >
                        <i class="bi" :class="isTypeDropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                      </button>
                    </div>

                    <!-- Searchable Dropdown Menu -->
                    <div
                      v-if="isTypeDropdownOpen"
                      class="position-absolute w-100 bg-white border rounded-3 shadow-lg mt-1 overflow-hidden"
                      style="z-index: 1055; max-height: 230px; overflow-y: auto; left: 0;"
                    >
                      <div
                        v-for="cat in filteredCategoryList"
                        :key="cat.name"
                        class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between category-dropdown-item cursor-pointer"
                        :class="{ 'bg-primary-subtle text-primary fw-bold': form.type === cat.name }"
                        @mousedown.prevent="selectCategory(cat.name)"
                      >
                        <span>
                          <i class="bi bi-tag-fill me-2 opacity-50"></i>{{ cat.name }}
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace">
                          {{ cat.prefix }}-
                        </span>
                      </div>

                      <div
                        v-if="filteredCategoryList.length === 0"
                        class="px-3 py-2 text-muted fst-italic small text-center"
                      >
                        ไม่พบประเภทที่ค้นหา (พิมพ์ใหม่ได้)
                      </div>

                      <div
                        v-if="form.type && !availableCategories.some(c => c.name.toLowerCase() === form.type.trim().toLowerCase())"
                        class="px-3 py-2 bg-light text-primary border-top small cursor-pointer d-flex align-items-center"
                        @mousedown.prevent="selectCategory(form.type)"
                      >
                        <i class="bi bi-plus-circle me-2"></i>
                        <span>ใช้ประเภทใหม่: <strong>"{{ form.type }}"</strong></span>
                      </div>
                    </div>

                    <small class="text-muted" style="font-size: 0.8rem;">
                      <i class="bi bi-magic me-1 text-primary"></i>ระบบจะรันรหัสอัตโนมัติตามประเภท
                    </small>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold">
                      รหัสสินค้า (SKU) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <input
                        type="text"
                        class="form-control fw-bold text-primary font-monospace"
                        v-model="form.code"
                        required
                        placeholder="เช่น OS-0001"
                      />
                      <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="regenerateCode"
                        title="สร้างรหัสใหม่อัตโนมัติ"
                      >
                        <i class="bi bi-arrow-repeat"></i> รันใหม่
                      </button>
                    </div>
                    <small class="text-muted" style="font-size: 0.8rem;">สามารถพิมพ์แก้ไขเองได้</small>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-bold">ชื่ออุปกรณ์ / รายการวัสดุ <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.name"
                    required
                    placeholder="เช่น กระดาษ A4 80 แกรม"
                  />
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-bold">หน่วยนับ <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.unit"
                      required
                      list="unitList"
                      placeholder="เลือกหรือพิมพ์ใหม่ (เช่น ชิ้น, รีม)"
                    />
                    <datalist id="unitList">
                      <option v-for="u in uniqueUnits" :key="u" :value="u"></option>
                    </datalist>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold">ราคาต่อหน่วย (บาท) <span class="text-danger">*</span></label>
                    <input
                      type="number"
                      step="0.01"
                      class="form-control"
                      v-model="form.price_per_unit"
                      required
                    />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">จำนวนขั้นต่ำแจ้งเตือน</label>
                    <input type="number" class="form-control" v-model="form.min_alert" min="0" />
                  </div>
                  <div class="col-md-6 mb-3" v-if="!form.id">
                    <label class="form-label">จำนวนยกยอดสต๊อกเริ่มต้น</label>
                    <input type="number" class="form-control" v-model="form.balance" min="0" />
                  </div>
                  <div class="col-12 mb-3">
                    <label class="form-label">รูปภาพประกอบ</label>
                    <input type="file" class="form-control" accept="image/*" @change="handleFileUpload" ref="fileInput" />
                    <div v-if="previewImage || form.image_path" class="mt-2 position-relative d-inline-block">
                      <img :src="previewImage || getImageUrl(form.image_path)" class="img-thumbnail" style="max-height: 120px;" />
                      <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 py-0 px-1" @click="removeImage">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button
                    type="button"
                    class="btn btn-light rounded-pill px-4 me-2"
                    data-bs-dismiss="modal"
                  >
                    ยกเลิก
                  </button>
                  <button type="submit" class="btn btn-success rounded-pill px-4">
                    บันทึกข้อมูล
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Management Modal -->
      <div
        class="modal fade"
        id="categoryManageModal"
        tabindex="-1"
        aria-hidden="true"
      >
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom-0 pb-0">
              <h5 class="modal-title fw-bold text-dark">
                <i class="bi bi-tags-fill text-warning me-2"></i>จัดการประเภท / หมวดหมู่วัสดุ
              </h5>
              <button
                type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close"
              ></button>
            </div>
            <div class="modal-body p-4">
              <!-- Category form (Add / Edit) -->
              <div
                class="card border-0 rounded-3 p-3 mb-4 transition-all"
                :class="editingCategory ? 'bg-primary-subtle border border-primary-subtle' : 'bg-light'"
              >
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6
                    class="fw-bold mb-0"
                    :class="editingCategory ? 'text-primary' : 'text-secondary'"
                  >
                    <i class="bi" :class="editingCategory ? 'bi-pencil-square me-1' : 'bi-plus-circle me-1'"></i>
                    {{ editingCategory ? `แก้ไขประเภท: "${editingCategory.name}"` : 'เพิ่มประเภท / หมวดหมู่ใหม่' }}
                  </h6>
                  <button
                    v-if="editingCategory"
                    type="button"
                    class="btn btn-sm btn-link text-muted p-0 text-decoration-none"
                    @click="cancelCategoryEdit"
                  >
                    <i class="bi bi-x-circle me-1"></i>ยกเลิกการแก้ไข
                  </button>
                </div>
                <form @submit.prevent="saveCategoryForm" class="row g-2 align-items-end">
                  <div class="col-md-6">
                    <label class="form-label small fw-bold">ชื่อประเภท / หมวดหมู่ <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      v-model="newCategoryForm.name"
                      placeholder="เช่น วัสดุกีฬา, เครื่องดื่ม"
                      required
                    />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-bold">คำนำหน้ารหัส (Prefix) <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control form-control-sm text-uppercase font-monospace"
                      v-model="newCategoryForm.prefix"
                      placeholder="เช่น SP, DR"
                      maxlength="10"
                      required
                    />
                  </div>
                  <div class="col-md-3 d-flex gap-1">
                    <button
                      type="submit"
                      class="btn btn-sm w-100 fw-bold"
                      :class="editingCategory ? 'btn-primary' : 'btn-warning'"
                      :disabled="isSavingCategory"
                    >
                      <i class="bi" :class="editingCategory ? 'bi-check-lg me-1' : 'bi-check2-circle me-1'"></i>
                      {{ editingCategory ? 'บันทึกแก้ไข' : 'บันทึกประเภท' }}
                    </button>
                    <button
                      v-if="editingCategory"
                      type="button"
                      class="btn btn-outline-secondary btn-sm"
                      @click="cancelCategoryEdit"
                      title="ยกเลิก"
                    >
                      ยกเลิก
                    </button>
                  </div>
                </form>
                <div class="form-text small mt-1 text-muted">
                  * คำนำหน้ารหัส (Prefix) จะถูกนำไปใช้รันรหัสสินค้าอัตโนมัติ เช่น ระบุ <code>SP</code> ระบบจะรันเป็น <code>SP-0001</code>
                </div>
              </div>

              <!-- Category table list -->
              <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th style="width: 50px;">#</th>
                      <th>ชื่อประเภท / หมวดหมู่</th>
                      <th class="text-center" style="width: 140px;">รหัสคำนำหน้า (Prefix)</th>
                      <th class="text-center" style="width: 120px;">จำนวนวัสดุ</th>
                      <th class="text-end" style="width: 110px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="categories.length === 0">
                      <td colspan="5" class="text-center py-4 text-muted">ไม่มีข้อมูลหมวดหมู่</td>
                    </tr>
                    <tr
                      v-for="(cat, idx) in categories"
                      :key="cat.id || idx"
                      :class="{ 'table-primary': editingCategory && editingCategory.id === cat.id }"
                    >
                      <td>{{ idx + 1 }}</td>
                      <td class="fw-bold">{{ cat.name }}</td>
                      <td class="text-center">
                        <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1">
                          {{ cat.prefix }}-
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                          {{ getCategoryMaterialCount(cat.name) }} รายการ
                        </span>
                      </td>
                      <td class="text-end text-nowrap">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary border-0 me-1"
                          title="แก้ไขประเภท"
                          @click="startEditCategory(cat)"
                        >
                          <i class="bi bi-pencil-square"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger border-0"
                          title="ลบประเภท"
                          @click="deleteCategory(cat)"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                ปิด
              </button>
            </div>
          </div>
        </div>
      </div>



    </div>

    <!-- Company/Vendor Management Modal -->
    <MtCompanyModal ref="companyModal" @updated="onCompanyUpdated" />

    <!-- Log Modal -->
    <MtLogModal ref="logModal" logType="stock" modalId="stockLogModal" />
  </div>
</template>

<script>
import axios from 'axios';
import * as bootstrap from 'bootstrap';
import * as XLSX from 'xlsx';
import Swal from 'sweetalert2';
import MtLogModal from './MtLogModal.vue';
import MtCompanyModal from './MtCompanyModal.vue';

export default {
  name: 'MtStock',
  components: {
    MtLogModal,
    MtCompanyModal
  },
  data() {
    return {
      search: '',
      selectedCategory: 'all',
      lowStockOnly: false,
      isExporting: false,
      materials: [],
      expandedRowId: null,
      expandedLots: [],
      isLoadingLots: false,
      isTypeDropdownOpen: false,
      categories: [],
      categoryModalInstance: null,
      editingCategory: null,
      newCategoryForm: {
        name: '',
        prefix: ''
      },
      isSavingCategory: false,
      form: {
        id: null,
        code: '',
        name: '',
        type: '',
        unit: '',
        price_per_unit: 0.0,
        min_alert: 5,
        balance: 0,
        image_path: ''
      },
      previewImage: null,
      selectedFile: null,
      modalInstance: null
    };
  },
  methods: {
    formatDateShort(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
    },
    async toggleRow(item) {
      if (this.expandedRowId === item.id) {
        this.expandedRowId = null;
        return;
      }
      this.expandedRowId = item.id;
      this.expandedLots = [];
      this.isLoadingLots = true;
      try {
        const res = await axios.get(`/api-digital/admin_material/admin_get_lots.php?material_id=${item.id}`);
        if (res.data.status === 'success') {
          this.expandedLots = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch lots', err);
      } finally {
        this.isLoadingLots = false;
      }
    },
    openCompanyModal() {
      if (this.$refs.companyModal) {
        this.$refs.companyModal.openModal();
      }
    },
    onCompanyUpdated() {
      this.reloadExpandedLots();
    },
    async reloadExpandedLots() {
      if (this.expandedRowId) {
        this.isLoadingLots = true;
        try {
          const res = await axios.get(`/api-digital/admin_material/admin_get_lots.php?material_id=${this.expandedRowId}`);
          if (res.data.status === 'success') {
            this.expandedLots = res.data.data || [];
          }
        } catch (err) {
          console.error('Failed to reload lots', err);
        } finally {
          this.isLoadingLots = false;
        }
      }
    },
    async fetchMaterials() {
      try {
        let url = `/api-digital/admin_material/admin_get_materials.php?search=${encodeURIComponent(this.search)}`;
        if (this.lowStockOnly) url += '&low_stock=1';

        const res = await axios.get(url);
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async exportToExcel() {
      if (!this.filteredMaterials || this.filteredMaterials.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'ไม่พบข้อมูล',
          text: 'ไม่มีรายการวัสดุสำหรับส่งออก Excel',
          confirmButtonColor: '#0d6efd'
        });
        return;
      }

      this.isExporting = true;
      try {
        const materialMap = new Map();
        const rows = this.filteredMaterials.map((item, index) => {
          materialMap.set(item.id, item);
          const balance = Number(item.balance) || 0;
          const minAlert = Number(item.min_alert) || 0;
          const pricePerUnit = Number(item.price_per_unit) || 0;
          const totalValue = Math.round(balance * pricePerUnit * 100) / 100;

          let stockStatus = 'ปกติ';
          if (balance <= 0) {
            stockStatus = 'หมดสต๊อก';
          } else if (balance <= minAlert) {
            stockStatus = 'ใกล้หมด';
          }

          return {
            'ลำดับ': index + 1,
            'รหัสวัสดุ': item.code || '',
            'ชื่อวัสดุ/อุปกรณ์': item.name || '',
            'หมวดหมู่/ประเภท': item.type || '-',
            'ยอดคงเหลือ': balance,
            'หน่วยนับ': item.unit || '-',
            'เกณฑ์เตือนขั้นต่ำ': minAlert,
            'ราคาต่อหน่วย (บาท)': pricePerUnit,
            'มูลค่าคงเหลือรวม (บาท)': totalValue,
            'สถานะสต๊อก': stockStatus,
            'สถานะการใช้งาน': item.is_active == 1 ? 'เปิดใช้งาน' : 'ปิดการใช้งาน'
          };
        });

        const totalBalance = rows.reduce((sum, r) => sum + (r['ยอดคงเหลือ'] || 0), 0);
        const totalValue = rows.reduce((sum, r) => sum + (r['มูลค่าคงเหลือรวม (บาท)'] || 0), 0);

        const finalSummaryRows = [
          ...rows,
          {
            'ลำดับ': 'รวมทั้งสิ้น',
            'รหัสวัสดุ': `${rows.length} รายการ`,
            'ชื่อวัสดุ/อุปกรณ์': '',
            'หมวดหมู่/ประเภท': '',
            'ยอดคงเหลือ': totalBalance,
            'หน่วยนับ': '',
            'เกณฑ์เตือนขั้นต่ำ': '',
            'ราคาต่อหน่วย (บาท)': '',
            'มูลค่าคงเหลือรวม (บาท)': Math.round(totalValue * 100) / 100,
            'สถานะสต๊อก': '',
            'สถานะการใช้งาน': ''
          }
        ];

        const workbook = XLSX.utils.book_new();

        // 1. Sheet สรุปรายการคลังวัสดุ
        const wsSummary = XLSX.utils.json_to_sheet(finalSummaryRows);
        wsSummary['!cols'] = [
          { wch: 10 }, // ลำดับ
          { wch: 15 }, // รหัสวัสดุ
          { wch: 38 }, // ชื่อวัสดุ/อุปกรณ์
          { wch: 22 }, // หมวดหมู่/ประเภท
          { wch: 14 }, // ยอดคงเหลือ
          { wch: 10 }, // หน่วยนับ
          { wch: 18 }, // เกณฑ์เตือนขั้นต่ำ
          { wch: 18 }, // ราคาต่อหน่วย (บาท)
          { wch: 22 }, // มูลค่าคงเหลือรวม (บาท)
          { wch: 16 }, // สถานะสต๊อก
          { wch: 16 }  // สถานะการใช้งาน
        ];
        XLSX.utils.book_append_sheet(workbook, wsSummary, 'รายการคลังวัสดุ');

        // 2. Fetch active lots for Sheet รายละเอียดล็อต (Lots)
        try {
          const lotsRes = await axios.get('/api-digital/admin_material/admin_get_lots.php?all=1');
          if (lotsRes.data && lotsRes.data.status === 'success' && Array.isArray(lotsRes.data.data) && lotsRes.data.data.length > 0) {
            const allowedIds = new Set(this.filteredMaterials.map(m => m.id));
            const relevantLots = lotsRes.data.data.filter(lot => allowedIds.has(Number(lot.material_id)));

            if (relevantLots.length > 0) {
              const lotRows = relevantLots.map((lot, idx) => {
                const price = Number(lot.price_per_unit) || 0;
                const remQty = Number(lot.remaining_qty) || 0;
                const origQty = Number(lot.original_qty) || 0;
                const lotValue = Math.round(price * remQty * 100) / 100;
                const mat = materialMap.get(Number(lot.material_id)) || {};

                return {
                  'ลำดับ': idx + 1,
                  'รหัสวัสดุ': lot.material_code || mat.code || '',
                  'ชื่อวัสดุ/อุปกรณ์': lot.material_name || mat.name || '',
                  'หมวดหมู่/ประเภท': lot.material_type || mat.type || '-',
                  'เลขล็อต (Lot No.)': lot.lot_number || '-',
                  'ผู้จัดจำหน่าย/บริษัท': lot.vendor || '-',
                  'วันที่รับเข้า': lot.receive_date ? lot.receive_date.slice(0, 10) : '-',
                  'ราคาต่อหน่วย (บาท)': price,
                  'จำนวนรับเข้า': origQty,
                  'ยอดคงเหลือในล็อต': remQty,
                  'หน่วยนับ': lot.material_unit || mat.unit || '-',
                  'มูลค่าคงเหลือในล็อต (บาท)': lotValue
                };
              });

              const totalLotRemQty = lotRows.reduce((sum, r) => sum + (r['ยอดคงเหลือในล็อต'] || 0), 0);
              const totalLotValue = lotRows.reduce((sum, r) => sum + (r['มูลค่าคงเหลือในล็อต (บาท)'] || 0), 0);

              const finalLotRows = [
                ...lotRows,
                {
                  'ลำดับ': 'รวมทั้งสิ้น',
                  'รหัสวัสดุ': `${lotRows.length} รายการล็อต`,
                  'ชื่อวัสดุ/อุปกรณ์': '',
                  'หมวดหมู่/ประเภท': '',
                  'เลขล็อต (Lot No.)': '',
                  'ผู้จัดจำหน่าย/บริษัท': '',
                  'วันที่รับเข้า': '',
                  'ราคาต่อหน่วย (บาท)': '',
                  'จำนวนรับเข้า': '',
                  'ยอดคงเหลือในล็อต': totalLotRemQty,
                  'หน่วยนับ': '',
                  'มูลค่าคงเหลือในล็อต (บาท)': Math.round(totalLotValue * 100) / 100
                }
              ];

              const wsLots = XLSX.utils.json_to_sheet(finalLotRows);
              wsLots['!cols'] = [
                { wch: 10 }, // ลำดับ
                { wch: 15 }, // รหัสวัสดุ
                { wch: 35 }, // ชื่อวัสดุ/อุปกรณ์
                { wch: 20 }, // หมวดหมู่/ประเภท
                { wch: 22 }, // เลขล็อต
                { wch: 25 }, // ผู้จัดจำหน่าย
                { wch: 15 }, // วันที่รับเข้า
                { wch: 18 }, // ราคาต่อหน่วย
                { wch: 14 }, // จำนวนรับเข้า
                { wch: 18 }, // ยอดคงเหลือในล็อต
                { wch: 12 }, // หน่วยนับ
                { wch: 22 }  // มูลค่าคงเหลือในล็อต
              ];
              XLSX.utils.book_append_sheet(workbook, wsLots, 'รายละเอียดล็อต (Lots)');
            }
          }
        } catch (lotErr) {
          console.warn('Could not fetch lots for export', lotErr);
        }

        const now = new Date();
        const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        XLSX.writeFile(workbook, `รายการคลังวัสดุ_${dateStr}.xlsx`);

        Swal.fire({
          icon: 'success',
          title: 'ส่งออก Excel สำเร็จ',
          text: `ส่งออกข้อมูลรายการวัสดุจำนวน ${rows.length} รายการ เรียบร้อยแล้ว`,
          timer: 2000,
          showConfirmButton: false
        });
      } catch (err) {
        console.error('Export error:', err);
        Swal.fire({
          icon: 'error',
          title: 'เกิดข้อผิดพลาด',
          text: 'ไม่สามารถส่งออกข้อมูล Excel ได้: ' + (err.message || ''),
          confirmButtonColor: '#dc3545'
        });
      } finally {
        this.isExporting = false;
      }
    },
    downloadTemplate() {
      const templateData = [
        {
          รหัสสินค้า: 'OS-0001',
          ชื่ออุปกรณ์: 'ปากกาลูกลื่น น้ำเงิน 0.5',
          ประเภท: 'วัสดุสำนักงาน',
          หน่วยนับ: 'ด้าม',
          ราคาต่อหน่วย: 5.50,
          เลขLot: 'LOT-OS-01',
          แจ้งเตือนขั้นต่ำ: 20,
          ยอดยกมา: 100
        },
        {
          รหัสสินค้า: 'BF-0001',
          ชื่ออุปกรณ์: 'น้ำยาล้างจาน 500 มล.',
          ประเภท: 'งานบ้านงานครัว',
          หน่วยนับ: 'ขวด',
          ราคาต่อหน่วย: 35.00,
          เลขLot: 'LOT-BF-01',
          แจ้งเตือนขั้นต่ำ: 10,
          ยอดยกมา: 50
        }
      ];

      const worksheet = XLSX.utils.json_to_sheet(templateData);
      
      const wscols = [
        { wch: 15 }, // รหัสสินค้า
        { wch: 30 }, // ชื่ออุปกรณ์
        { wch: 20 }, // ประเภท
        { wch: 15 }, // หน่วยนับ
        { wch: 15 }, // ราคาต่อหน่วย
        { wch: 20 }, // เลขLot
        { wch: 15 }, // แจ้งเตือนขั้นต่ำ
        { wch: 15 }  // ยอดยกมา
      ];
      worksheet['!cols'] = wscols;

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Materials');

      XLSX.writeFile(workbook, 'Material_Import_Template.xlsx');
    },
    async importExcel(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const json = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

          if (!json || json.length === 0) {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลในไฟล์ Excel', 'error');
            return;
          }

          const confirm = await Swal.fire({
            title: 'ยืนยันการนำเข้าข้อมูล?',
            text: `พบข้อมูลจำนวน ${json.length} รายการ ต้องการดำเนินการต่อหรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ตกลง',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d6efd'
          });

          if (confirm.isConfirmed) {
            Swal.fire({
              title: 'กำลังนำเข้าข้อมูล',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            const res = await axios.post('/api-digital/admin_material/admin_import_material.php', json);
            
            if (res.data.status === 'success') {
              let htmlContent = `<div class="text-start">${res.data.message}</div>`;
              if (res.data.errors && res.data.errors.length > 0) {
                const errorList = res.data.errors.map(err => `<li>${err}</li>`).join('');
                htmlContent += `<hr><div class="text-start text-danger" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;"><b>ข้อผิดพลาดที่พบ:</b><ul class="mb-0 ps-3 mt-1">${errorList}</ul></div>`;
              }
              Swal.fire({
                title: 'ผลการนำเข้าข้อมูล',
                html: htmlContent,
                icon: res.data.errors && res.data.errors.length > 0 ? 'warning' : 'success',
                confirmButtonText: 'ตกลง'
              });
              this.fetchMaterials();
            } else {
              Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถนำเข้าได้', 'error');
            }
          }
        } catch (error) {
          console.error('Error importing Excel:', error);
          const errorMsg = error.response?.data?.message || 'รูปแบบไฟล์ไม่ถูกต้อง หรือเกิดปัญหาในการอ่านไฟล์';
          Swal.fire('ข้อผิดพลาด', errorMsg, 'error');
        } finally {
          if (this.$refs.excelInput) {
            this.$refs.excelInput.value = null;
          }
        }
      };
      reader.readAsArrayBuffer(file);
    },
    openModal(item = null) {
      this.previewImage = null;
      this.selectedFile = null;
      if (this.$refs.fileInput) this.$refs.fileInput.value = '';

      if (item) {
        this.form = { ...item };
      } else {
        const defaultType = this.selectedCategory !== 'all' ? this.selectedCategory : (this.uniqueCategories[0] || 'วัสดุสำนักงาน');
        const nextCode = this.generateNextCodeForType(defaultType) || 'OS-0001';

        this.form = {
          id: null,
          code: nextCode,
          name: '',
          type: defaultType,
          unit: '',
          price_per_unit: 0.0,
          min_alert: 5,
          balance: 0,
          image_path: ''
        };
      }
      if (!this.modalInstance) {
        this.modalInstance = new bootstrap.Modal(document.getElementById('materialModal'));
      }
      this.modalInstance.show();
    },
    generateNextCodeForType(selectedType) {
      if (!selectedType) return '';
      const typeTrim = selectedType.trim();

      // 1. Check from mt_admin_categories in database
      let prefix = '';
      const matchedCat = (this.categories || []).find(
        c => c.name && c.name.trim().toLowerCase() === typeTrim.toLowerCase()
      );
      if (matchedCat && matchedCat.prefix) {
        prefix = matchedCat.prefix.toUpperCase();
      }

      // 2. Default mapping fallback
      if (!prefix) {
        const defaultPrefixMap = {
          'วัสดุสำนักงาน': 'OS',
          'สำนักงาน': 'OS',
          'งานบ้านงานครัว': 'BF',
          'วัสดุงานบ้านงานครัว': 'BF',
          'งานบ้าน': 'HK',
          'งานครัว': 'BF',
          'วัสดุคอมพิวเตอร์': 'IT',
          'คอมพิวเตอร์': 'IT',
          'วัสดุการแพทย์': 'MD',
          'การแพทย์': 'MD',
          'วัสดุวิทยาศาสตร์': 'SC',
          'วิทยาศาสตร์': 'SC',
          'วัสดุยานพาหนะ': 'VH',
          'ยานพาหนะ': 'VH',
          'วัสดุไฟฟ้าและวิทยุ': 'EL',
          'วัสดุไฟฟ้า': 'EL',
          'ไฟฟ้า': 'EL',
          'งานประปา': 'PB',
          'วัสดุก่อสร้าง': 'CN'
        };
        prefix = defaultPrefixMap[typeTrim] || '';
      }

      // 3. Fallback to existing items prefix
      if (!prefix) {
        const sameTypeMaterials = (this.materials || []).filter(
          m => m.type && m.type.trim() === typeTrim && m.code
        );
        if (sameTypeMaterials.length > 0) {
          const prefixCounts = {};
          sameTypeMaterials.forEach(m => {
            const match = m.code.match(/^([A-Za-z]+)-(\d+)$/);
            if (match) {
              const p = match[1].toUpperCase();
              prefixCounts[p] = (prefixCounts[p] || 0) + 1;
            }
          });
          const sorted = Object.keys(prefixCounts).sort((a, b) => prefixCounts[b] - prefixCounts[a]);
          if (sorted.length > 0) prefix = sorted[0];
        }
      }

      // 4. Fallback: English letters or 'MT'
      if (!prefix) {
        const enMatch = typeTrim.match(/[A-Za-z]{2,}/);
        prefix = enMatch ? enMatch[0].substring(0, 3).toUpperCase() : 'MT';
      }

      prefix = prefix.toUpperCase();

      // 5. Find max number across all materials starting with this prefix
      const matchingCodes = (this.materials || [])
        .map(m => m.code)
        .filter(code => code && code.toUpperCase().startsWith(`${prefix}-`))
        .map(code => {
          const match = code.match(new RegExp(`^${prefix}-(\\d+)`, 'i'));
          return match ? parseInt(match[1], 10) : 0;
        })
        .filter(num => !isNaN(num));

      const maxNum = matchingCodes.length > 0 ? Math.max(...matchingCodes) : 0;
      const nextNum = maxNum + 1;

      return `${prefix}-${String(nextNum).padStart(4, '0')}`;
    },
    onTypeInput() {
      this.isTypeDropdownOpen = true;
      this.onTypeChange();
    },
    toggleTypeDropdown() {
      this.isTypeDropdownOpen = !this.isTypeDropdownOpen;
    },
    closeTypeDropdown() {
      setTimeout(() => {
        this.isTypeDropdownOpen = false;
      }, 150);
    },
    async selectCategory(catName) {
      this.form.type = catName;
      this.isTypeDropdownOpen = false;
      this.onTypeChange();

      // If new category not in categories list, register it in mt_admin_categories
      const exists = (this.categories || []).some(
        c => c.name.toLowerCase() === catName.trim().toLowerCase()
      );
      if (!exists && catName.trim()) {
        try {
          const prefix = (this.form.code && this.form.code.includes('-')) 
            ? this.form.code.split('-')[0].trim().toUpperCase() 
            : '';
          const res = await axios.post('/api-digital/admin_material/admin_save_category.php', {
            name: catName.trim(),
            prefix: prefix
          });
          if (res.data.status === 'success') {
            this.fetchCategories();
          }
        } catch (e) {
          console.error('Error auto-saving new category:', e);
        }
      }
    },
    onTypeChange() {
      if (this.form.id) return;
      const nextCode = this.generateNextCodeForType(this.form.type);
      if (nextCode) {
        this.form.code = nextCode;
      }
    },
    regenerateCode() {
      let currentPrefix = '';
      if (this.form.code && this.form.code.includes('-')) {
        currentPrefix = this.form.code.split('-')[0].trim().toUpperCase();
      }
      if (currentPrefix) {
        const matchingCodes = (this.materials || [])
          .map(m => m.code)
          .filter(code => code && code.toUpperCase().startsWith(`${currentPrefix}-`))
          .map(code => {
            const match = code.match(new RegExp(`^${currentPrefix}-(\\d+)`, 'i'));
            return match ? parseInt(match[1], 10) : 0;
          })
          .filter(num => !isNaN(num));

        const maxNum = matchingCodes.length > 0 ? Math.max(...matchingCodes) : 0;
        this.form.code = `${currentPrefix}-${String(maxNum + 1).padStart(4, '0')}`;
        return;
      }

      const nextCode = this.generateNextCodeForType(this.form.type || 'วัสดุสำนักงาน');
      if (nextCode) {
        this.form.code = nextCode;
      }
    },
    async saveMaterial() {
      try {
        const formData = new FormData();
        for (const key in this.form) {
          if (this.form[key] !== null) {
            formData.append(key, this.form[key]);
          }
        }
        if (this.selectedFile) {
          formData.append('image', this.selectedFile);
        }

        const res = await axios.post('/api-digital/admin_material/admin_save_material.php', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (res.data.status === 'success') {
          // alert(res.data.message);
          this.modalInstance.hide();
          this.fetchMaterials();
          this.fetchCategories();
        } else {
          alert(res.data.message);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
      }
    },
    async toggleStatus(item, event) {
      const newStatus = item.is_active == 1 ? 0 : 1;
      const statusText = newStatus === 1 ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน';
      
      const confirm = await Swal.fire({
        title: `ยืนยันการ${statusText}?`,
        text: `คุณต้องการ${statusText} "${item.name}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: newStatus === 1 ? '#198754' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `ยืนยัน`,
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const response = await axios.post('/api-digital/admin_material/admin_toggle_material_status.php', {
            id: item.id,
            is_active: newStatus
          });
          
          if (response.data.status === 'success') {
            item.is_active = newStatus;
            Swal.fire({
              icon: 'success',
              title: 'สำเร็จ',
              text: response.data.message,
              timer: 1500,
              showConfirmButton: false
            });
            this.$refs.logModal?.fetchLogs();
          } else {
            Swal.fire('ข้อผิดพลาด', response.data.message || 'ไม่สามารถเปลี่ยนสถานะได้', 'error');
            // Revert UI toggle on error
            item.is_active = item.is_active == 1 ? 1 : 0; 
          }
        } catch (error) {
          console.error(error);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
          // Revert UI toggle on error
          item.is_active = item.is_active == 1 ? 1 : 0; 
        }
      } else {
        // Revert UI toggle if cancelled
        const checkbox = event.target;
        checkbox.checked = !checkbox.checked;
      }
    },
    async deleteItem(id, name) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบวัสดุ?',
        text: `คุณแน่ใจหรือไม่ว่าต้องการลบ: ${name} ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/admin_material/admin_delete_material.php', { id });
          if (res.data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'ลบสำเร็จ',
              showConfirmButton: false,
              timer: 1500
            });
            this.fetchMaterials();
          } else {
            Swal.fire('ข้อผิดพลาด', res.data.message, 'error');
          }
        } catch (err) {
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้ อาจมีการเชื่อมโยงอยู่', 'error');
        }
      }
    },
    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
      if (this.selectedFile) {
        this.previewImage = URL.createObjectURL(this.selectedFile);
      } else {
        this.previewImage = null;
      }
    },
    removeImage() {
      this.form.image_path = '';
      this.selectedFile = null;
      this.previewImage = null;
      if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    },
    viewImage(url, name) {
      if (!url) return;
      Swal.fire({
        title: name,
        imageUrl: url,
        imageAlt: name,
        imageStyle: 'max-height: 80vh; max-width: 100%; object-fit: contain;',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: {
          image: 'rounded shadow'
        }
      });
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    async fetchCategories() {
      try {
        const res = await axios.get('/api-digital/admin_material/admin_get_categories.php');
        if (res.data.status === 'success') {
          this.categories = res.data.data || [];
        }
      } catch (err) {
        console.error('Error fetching categories:', err);
      }
    },
    openCategoryModal() {
      if (!this.categoryModalInstance) {
        this.categoryModalInstance = new bootstrap.Modal(document.getElementById('categoryManageModal'));
      }
      this.categoryModalInstance.show();
    },
    startEditCategory(cat) {
      this.editingCategory = { ...cat };
      this.newCategoryForm = {
        name: cat.name,
        prefix: cat.prefix
      };
    },
    cancelCategoryEdit() {
      this.editingCategory = null;
      this.newCategoryForm = {
        name: '',
        prefix: ''
      };
    },
    async saveCategoryForm() {
      if (!this.newCategoryForm.name || !this.newCategoryForm.prefix) return;
      this.isSavingCategory = true;
      try {
        const payload = {
          name: this.newCategoryForm.name.trim(),
          prefix: this.newCategoryForm.prefix.trim().toUpperCase()
        };
        if (this.editingCategory && this.editingCategory.id) {
          payload.id = this.editingCategory.id;
        }

        const res = await axios.post('/api-digital/admin_material/admin_save_category.php', payload);
        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            text: res.data.message || (this.editingCategory ? 'อัปเดตประเภทเรียบร้อยแล้ว' : 'เพิ่มประเภทเรียบร้อยแล้ว'),
            timer: 1500,
            showConfirmButton: false
          });
          this.cancelCategoryEdit();
          await this.fetchCategories();
          await this.fetchMaterials();
        } else {
          Swal.fire('ผิดพลาด', res.data.message, 'error');
        }
      } catch (e) {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึกประเภท', 'error');
      } finally {
        this.isSavingCategory = false;
      }
    },
    getCategoryMaterialCount(catName) {
      if (!catName || !this.materials) return 0;
      return this.materials.filter(m => m.type && m.type.trim() === catName.trim()).length;
    },
    async deleteCategory(cat) {
      const count = this.getCategoryMaterialCount(cat.name);
      let forceDelete = false;

      if (count > 0) {
        const confirmWithItems = await Swal.fire({
          title: 'หมวดหมู่นี้มีการใช้งานอยู่',
          html: `
            <div class="text-start">
              <p class="mb-2">ประเภท <b>"${cat.name}"</b> กำลังถูกใช้งานโดยวัสดุจำนวน <span class="badge bg-danger fs-6">${count} รายการ</span></p>
              <div class="alert alert-warning border-0 small mb-0">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                หากยืนยันการลบ ระบบจะลบประเภทนี้ออก และปลดประเภทของวัสดุทั้ง <b>${count} รายการ</b> ให้เป็น <i>"ยังไม่ระบุหมวดหมู่"</i> (ข้อมูลสต๊อกและประวัติของวัสดุยังคงอยู่ครบถ้วน)
              </div>
            </div>
          `,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: `<i class="bi bi-trash me-1"></i> ยืนยันลบและปลดวัสดุ (${count} รายการ)`,
          cancelButtonText: 'ยกเลิก'
        });

        if (!confirmWithItems.isConfirmed) return;
        forceDelete = true;
      } else {
        const confirm = await Swal.fire({
          title: 'ยืนยันการลบประเภท?',
          text: `ต้องการลบประเภท "${cat.name}" (${cat.prefix}-) ออกจากระบบหรือไม่?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'ยืนยันลบข้อมูล',
          cancelButtonText: 'ยกเลิก'
        });

        if (!confirm.isConfirmed) return;
      }

      try {
        const res = await axios.post('/api-digital/admin_material/admin_delete_category.php', {
          id: cat.id,
          force: forceDelete
        });
        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'ลบสำเร็จ',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          await this.fetchCategories();
          await this.fetchMaterials();
        } else {
          Swal.fire('ผิดพลาด', res.data.message, 'error');
        }
      } catch (e) {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการลบประเภท', 'error');
      }
    },
    openLogModal() {
      // eslint-disable-next-line no-undef
      const modal = new bootstrap.Modal(document.getElementById('stockLogModal'));
      modal.show();
      this.$refs.logModal.fetchLogs();
    }
  },
  computed: {
    uniqueCategories() {
      const fromCats = (this.categories || []).map(c => c.name);
      const fromMats = (this.materials || []).map(m => m.type).filter(t => t);
      return [...new Set([...fromCats, ...fromMats])].sort();
    },
    availableCategories() {
      if (this.categories && this.categories.length > 0) {
        return this.categories;
      }
      return [
        { name: 'วัสดุสำนักงาน', prefix: 'OS' },
        { name: 'งานบ้านงานครัว', prefix: 'BF' },
        { name: 'วัสดุคอมพิวเตอร์', prefix: 'IT' },
        { name: 'วัสดุการแพทย์', prefix: 'MD' },
        { name: 'วัสดุวิทยาศาสตร์', prefix: 'SC' },
        { name: 'วัสดุยานพาหนะ', prefix: 'VH' },
        { name: 'วัสดุไฟฟ้าและวิทยุ', prefix: 'EL' },
        { name: 'งานประปา', prefix: 'PB' },
        { name: 'วัสดุก่อสร้าง', prefix: 'CN' }
      ];
    },
    filteredCategoryList() {
      const q = (this.form.type || '').trim().toLowerCase();
      if (!q) return this.availableCategories;
      return this.availableCategories.filter(
        c => (c.name && c.name.toLowerCase().includes(q)) || (c.prefix && c.prefix.toLowerCase().includes(q))
      );
    },
    uniqueUnits() {
      const units = this.materials.map(m => m.unit).filter(u => u);
      return [...new Set(units)].sort();
    },
    filteredMaterials() {
      if (this.selectedCategory === 'all') {
        return this.materials;
      }
      return this.materials.filter(m => m.type === this.selectedCategory);
    },
  },
  mounted() {
    this.fetchMaterials();
    this.fetchCategories();
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
.table-danger {
  --bs-table-bg: rgba(220, 53, 69, 0.05);
}
.category-dropdown-item {
  transition: background-color 0.15s ease-in-out;
  cursor: pointer;
}
.category-dropdown-item:hover {
  background-color: #f1f5f9;
}
.cursor-pointer {
  cursor: pointer;
}
</style>
