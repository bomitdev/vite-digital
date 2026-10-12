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
              <li class="breadcrumb-item active" aria-current="page">ตั้งค่าระบบคลังวัสดุ</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-primary mb-0">
            <i class="bi bi-gear-fill me-2"></i>ตั้งค่า Master Data คลังวัสดุ
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
          <button class="btn btn-primary rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-dashboard')">
            <i class="bi bi-grid-fill me-2"></i>Dashboard
          </button>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-0">
          <ul class="nav nav-tabs nav-justified mt-3 border-0 px-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-bold border-0 border-bottom border-3" id="wh-tab" data-bs-toggle="tab" data-bs-target="#wh" type="button" role="tab" @click="activeTab='wh'">
                <i class="bi bi-building me-1"></i> คลังวัสดุ ({{ warehouses.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="cat-tab" data-bs-toggle="tab" data-bs-target="#cat" type="button" role="tab" @click="activeTab='cat'">
                <i class="bi bi-tags me-1"></i> หมวดหมู่
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="item-tab" data-bs-toggle="tab" data-bs-target="#item" type="button" role="tab" @click="activeTab='item'">
                <i class="bi bi-box-seam me-1"></i> รายการวัสดุ (Items)
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body p-4 p-md-5">
          <div class="tab-content">
            
            <!-- Warehouses Tab -->
            <div class="tab-pane fade show active" id="wh" role="tabpanel">
              <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">รายชื่อคลังวัสดุทั้งหมด</h5>
                <button class="btn btn-primary rounded-pill" @click="openWarehouseModal()"><i class="bi bi-plus-lg"></i> เพิ่มคลังใหม่</button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>รหัสคลัง</th>
                      <th>ชื่อคลัง</th>
                      <th>สถานะ</th>
                      <th>จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="wh in warehouses" :key="wh.id">
                      <td><span class="badge bg-secondary">{{ wh.code }}</span></td>
                      <td class="fw-bold">{{ wh.name }}</td>
                      <td>
                        <span class="badge" :class="wh.active ? 'bg-success' : 'bg-danger'">
                          {{ wh.active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary me-2" @click="openWarehouseModal(wh)" title="แก้ไขคลัง"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-info" @click="openAccessModal(wh)" title="จัดการสิทธิ์เข้าถึง"><i class="bi bi-person-lines-fill"></i> สิทธิ์เข้าถึง</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade" id="cat" role="tabpanel">
              <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">หมวดหมู่วัสดุ (อ้างอิงตามคลัง)</h5>
                <button class="btn btn-primary rounded-pill" @click="openCategoryModal"><i class="bi bi-plus-lg"></i> เพิ่มหมวดหมู่</button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>คลังวัสดุ</th>
                      <th>รหัสหมวด</th>
                      <th>ชื่อหมวดหมู่</th>
                      <th>สถานะ</th>
                      <th>จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="categories.length === 0">
                      <td colspan="4" class="text-center text-muted">ยังไม่มีข้อมูลหมวดหมู่</td>
                    </tr>
                    <tr v-for="c in categories" :key="c.id">
                      <td>{{ c.warehouse_name }}</td>
                      <td>{{ c.code }}</td>
                      <td>{{ c.name }}</td>
                      <td>
                        <span class="badge" :class="c.active ? 'bg-success' : 'bg-danger'">
                          {{ c.active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary" @click="openCategoryModal(c)"><i class="bi bi-pencil"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Items Tab -->
            <div class="tab-pane fade" id="item" role="tabpanel">
              <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">รายการวัสดุ (Item Master)</h5>
                <button class="btn btn-success rounded-pill" @click="openItemModal"><i class="bi bi-plus-lg"></i> ลงทะเบียนวัสดุใหม่</button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>รหัสวัสดุ</th>
                      <th>ชื่อวัสดุ</th>
                      <th>คลัง</th>
                      <th>หน่วย</th>
                      <th>Lot</th>
                      <th>Expiry</th>
                      <th>จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="items.length === 0">
                      <td colspan="6" class="text-center text-muted py-4">ยังไม่มีข้อมูล Item</td>
                    </tr>
                    <tr v-for="i in items" :key="i.id">
                      <td>{{ i.item_code }}</td>
                      <td class="fw-bold">{{ i.item_name }}</td>
                      <td>{{ i.warehouse_name }}</td>
                      <td>{{ i.unit_name }}</td>
                      <td><i v-if="i.lot_control" class="bi bi-check-circle-fill text-success"></i></td>
                      <td><i v-if="i.expiry_control" class="bi bi-check-circle-fill text-success"></i></td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary" @click="openItemModal(i)"><i class="bi bi-pencil"></i></button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
    <!-- Warehouse Modal -->
    <div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-building me-2"></i>{{ isEditWarehouse ? 'แก้ไขคลังวัสดุ' : 'เพิ่มคลังวัสดุใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveWarehouse">
              <div class="mb-3">
                <label class="form-label">รหัสคลัง <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="warehouseForm.code" required placeholder="เช่น 01" />
              </div>
              <div class="mb-3">
                <label class="form-label">ชื่อคลังวัสดุ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="warehouseForm.name" required placeholder="เช่น คลังยา" />
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="whActive" v-model="warehouseForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label" for="whActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingWarehouse">
                  <span v-if="isSavingWarehouse" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-tags me-2"></i>{{ isEditCategory ? 'แก้ไขหมวดหมู่วัสดุ' : 'เพิ่มหมวดหมู่วัสดุ' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveCategory">
              <div class="mb-3">
                <label class="form-label">คลังวัสดุ <span class="text-danger">*</span></label>
                <select class="form-select" v-model="categoryForm.warehouse_id" required>
                  <option value="">-- เลือกคลัง --</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">รหัสหมวดหมู่ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="categoryForm.code" required placeholder="เช่น C01" />
              </div>
              <div class="mb-3">
                <label class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="categoryForm.name" required placeholder="เช่น ยาเม็ด" />
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="catActive" v-model="categoryForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label" for="catActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingCategory">
                  <span v-if="isSavingCategory" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Item Master Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-success">
              <i class="bi bi-box-seam me-2"></i>{{ isEditItem ? 'แก้ไขรายการวัสดุ' : 'ลงทะเบียนวัสดุใหม่ (Item Master)' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4 p-md-5">
            <form @submit.prevent="saveItem">
              <!-- Section 1: ข้อมูลพื้นฐาน -->
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">1. ข้อมูลพื้นฐานและการจัดหมวดหมู่</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label">รหัสวัสดุ (Item Code) <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="itemForm.item_code" required placeholder="เช่น M001" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">รหัสเดิม (ถ้ามี)</label>
                  <input type="text" class="form-control" v-model="itemForm.old_code" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">ชื่อวัสดุ <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="itemForm.item_name" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">ชื่อภาษาอังกฤษ / ชื่อพ้อง</label>
                  <input type="text" class="form-control" v-model="itemForm.item_name_en" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">คลังวัสดุ <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="itemForm.warehouse_id" required>
                    <option value="">-- เลือกคลัง --</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">หมวดหมู่</label>
                  <select class="form-select" v-model="itemForm.category_id">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
              </div>

              <!-- Section 2: หน่วยนับและรายละเอียด -->
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">2. หน่วยนับและรายละเอียดเพิ่มเติม</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label">หน่วยนับพื้นฐาน <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="itemForm.unit_id" required>
                    <option value="">-- เลือกหน่วย --</option>
                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">หน่วยจัดซื้อ (ใหญ่)</label>
                  <select class="form-select" v-model="itemForm.purchase_unit_id">
                    <option value="">-- เลือกหน่วย --</option>
                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">อัตราแปลงหน่วย (Conversion)</label>
                  <input type="number" step="0.01" class="form-control" v-model="itemForm.conversion_rate" placeholder="1.00" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">หน่วยเบิกจ่าย</label>
                  <select class="form-select" v-model="itemForm.issue_unit_id">
                    <option value="">-- เลือกหน่วย --</option>
                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Barcode</label>
                  <input type="text" class="form-control" v-model="itemForm.barcode" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">ยี่ห้อ (Brand)</label>
                  <input type="text" class="form-control" v-model="itemForm.brand" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">รุ่น (Model)</label>
                  <input type="text" class="form-control" v-model="itemForm.model" />
                </div>
              </div>

              <!-- Section 3: การควบคุมสต๊อกและราคา -->
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">3. การควบคุมสต๊อกและราคา</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label">Stock ขั้นต่ำ (Min)</label>
                  <input type="number" class="form-control" v-model="itemForm.minimum_stock" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Stock สูงสุด (Max)</label>
                  <input type="number" class="form-control" v-model="itemForm.maximum_stock" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">จุดสั่งซื้อ (Reorder Point)</label>
                  <input type="number" class="form-control" v-model="itemForm.reorder_point" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">ราคากลาง / มาตรฐาน</label>
                  <input type="number" step="0.01" class="form-control" v-model="itemForm.standard_price" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">ราคาซื้อล่าสุด</label>
                  <input type="number" step="0.01" class="form-control" v-model="itemForm.last_purchase_price" />
                </div>
              </div>

              <!-- Section 4: รูปแบบการควบคุม -->
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">4. กฎการควบคุม (Control Rules)</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="lotCtrl" v-model="itemForm.lot_control" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="lotCtrl">ควบคุม Lot Number</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="expCtrl" v-model="itemForm.expiry_control" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="expCtrl">ควบคุมวันหมดอายุ</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="serCtrl" v-model="itemForm.serial_control" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="serCtrl">ควบคุม Serial Number</label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="actCtrl" v-model="itemForm.active" :true-value="1" :false-value="0">
                    <label class="form-check-label" for="actCtrl">เปิดใช้งาน (Active)</label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

    <!-- Access Control Modal -->
    <div class="modal fade" id="accessModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header bg-info text-white rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>จัดการสิทธิ์คลัง: {{ currentAccessWarehouse?.name }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="addAccessUser" class="mb-4">
              <div class="row g-2 align-items-end">
                <div class="col-md-7">
                  <label class="form-label">เลือกพนักงาน (พิมพ์เพื่อค้นหา)</label>
                  <div class="position-relative">
                    <input type="text" class="form-control" v-model="searchStaffQuery" @focus="showStaffDropdown = true" @blur="hideStaffDropdown" placeholder="พิมพ์ชื่อพนักงาน..." autocomplete="off">
                    <ul class="dropdown-menu show w-100 position-absolute" v-if="showStaffDropdown" style="max-height: 250px; overflow-y: auto; z-index: 1050;">
                      <li v-for="user in filteredStaff" :key="user.ID">
                        <a class="dropdown-item py-2 border-bottom" href="#" @mousedown.prevent="selectStaff(user)">
                          <div class="fw-bold">{{ user.FULLNAME }}</div>
                          <div class="small text-muted">{{ user.HR_DEPARTMENT_SUB_NAME || 'ไม่ระบุสังกัด' }}</div>
                        </a>
                      </li>
                      <li v-if="filteredStaff.length === 0">
                        <span class="dropdown-item text-muted">ไม่พบรายชื่อ</span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="form-label">ระดับสิทธิ์</label>
                  <select class="form-select" v-model="accessForm.access_level">
                    <option value="STAFF">เจ้าหน้าที่ (Staff)</option>
                    <option value="MANAGER">หัวหน้าคลัง (Manager)</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-primary w-100" :disabled="isSavingAccess">
                    เพิ่มสิทธิ์
                  </button>
                </div>
              </div>
            </form>

            <h6 class="fw-bold mb-3">รายชื่อผู้มีสิทธิ์เข้าถึงคลังนี้</h6>
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th>ชื่อพนักงาน</th>
                    <th>ระดับสิทธิ์</th>
                    <th>ลบสิทธิ์</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="warehouseUsers.length === 0">
                    <td colspan="3" class="text-center text-muted py-3">ยังไม่มีผู้ได้รับสิทธิ์</td>
                  </tr>
                  <tr v-for="u in warehouseUsers" :key="u.id">
                    <td>{{ u.user_name }}</td>
                    <td>
                      <span class="badge" :class="u.access_level === 'MANAGER' ? 'bg-primary' : 'bg-secondary'">
                        {{ u.access_level }}
                      </span>
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger" @click="removeAccessUser(u.id)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

export default {
  name: 'InventorySettings',
  data() {
    return {
      activeTab: 'wh',
      isSaving: false,
      isSavingCategory: false,
      isSavingWarehouse: false,
      isEditWarehouse: false,
      isEditCategory: false,
      isEditItem: false,
      warehouses: [],
      categories: [],
      items: [],
      units: [],
      warehouseForm: {
        id: null, code: '', name: '', active: 1
      },
      categoryForm: {
        id: null, warehouse_id: '', code: '', name: '', active: 1
      },
      itemForm: {
        id: null, item_code: '', old_code: '', item_name: '', item_name_en: '',
        warehouse_id: '', category_id: '',
        unit_id: '', purchase_unit_id: '', issue_unit_id: '', conversion_rate: 1.00,
        barcode: '', brand: '', model: '',
        minimum_stock: 0, maximum_stock: 0, reorder_point: 0,
        standard_price: 0, last_purchase_price: 0,
        lot_control: 0, expiry_control: 0, serial_control: 0, active: 1
      },
      allStaff: [],
      warehouseUsers: [],
      currentAccessWarehouse: null,
      isSavingAccess: false,
      searchStaffQuery: '',
      showStaffDropdown: false,
      accessForm: {
        user_id: '', access_level: 'STAFF'
      }
    };
  },
  computed: {
    filteredCategories() {
      if (!this.itemForm.warehouse_id) return [];
      return this.categories.filter(c => c.warehouse_id === this.itemForm.warehouse_id);
    },
    filteredStaff() {
      if (!this.searchStaffQuery) return this.allStaff.slice(0, 50);
      const q = this.searchStaffQuery.toLowerCase();
      return this.allStaff.filter(u => 
        (u.FULLNAME && u.FULLNAME.toLowerCase().includes(q)) || 
        (u.HR_DEPARTMENT_SUB_NAME && u.HR_DEPARTMENT_SUB_NAME.toLowerCase().includes(q))
      ).slice(0, 50);
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const [whRes, catRes, itemRes, unitRes, staffRes] = await Promise.all([
          axios.get('/api-digital/inventory/warehouses.php?is_admin=1'),
          axios.get('/api-digital/inventory/categories.php'),
          axios.get('/api-digital/inventory/items.php'),
          axios.get('/api-digital/inventory/units.php'),
          axios.get('/api-hosoffice/get_all_staff.php').catch(() => null)
        ]);
        
        if (whRes?.data?.status === 'success') this.warehouses = whRes.data.data;
        if (catRes?.data?.status === 'success') this.categories = catRes.data.data;
        if (itemRes?.data?.status === 'success') this.items = itemRes.data.data;
        if (unitRes?.data?.status === 'success') this.units = unitRes.data.data;
        if (staffRes?.data?.status === 'success') this.allStaff = staffRes.data.data;
      } catch (err) {
        console.error("Error fetching master data", err);
      }
    },
    openWarehouseModal(wh = null) {
      if (wh) {
        this.isEditWarehouse = true;
        this.warehouseForm = { id: wh.id, code: wh.code, name: wh.name, active: wh.active };
      } else {
        this.isEditWarehouse = false;
        this.warehouseForm = { id: null, code: '', name: '', active: 1 };
      }
      const modal = new bootstrap.Modal(document.getElementById('warehouseModal'));
      modal.show();
    },
    async saveWarehouse() {
      this.isSavingWarehouse = true;
      try {
        const method = this.isEditWarehouse ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/warehouses.php', this.warehouseForm);
        
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: 'ข้อมูลคลังวัสดุถูกบันทึกเรียบร้อยแล้ว',
            icon: 'success',
            confirmButtonColor: '#0d6efd'
          });
          
          const modalEl = document.getElementById('warehouseModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
          
          this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบเกิดข้อขัดข้อง', 'error');
        console.error(err);
      } finally {
        this.isSavingWarehouse = false;
      }
    },
    async openAccessModal(wh) {
      this.currentAccessWarehouse = wh;
      this.accessForm = { user_id: '', access_level: 'STAFF' };
      this.searchStaffQuery = '';
      this.showStaffDropdown = false;
      await this.fetchWarehouseUsers(wh.id);
      const modal = new bootstrap.Modal(document.getElementById('accessModal'));
      modal.show();
    },
    hideStaffDropdown() {
      setTimeout(() => {
        this.showStaffDropdown = false;
        if (!this.accessForm.user_id) {
          this.searchStaffQuery = '';
        }
      }, 200);
    },
    selectStaff(user) {
      this.accessForm.user_id = user.ID;
      this.searchStaffQuery = user.FULLNAME;
      this.showStaffDropdown = false;
    },
    async fetchWarehouseUsers(warehouse_id) {
      try {
        const res = await axios.get(`/api-digital/inventory/warehouse_users_get.php?warehouse_id=${warehouse_id}`);
        if (res.data.status === 'success') {
          this.warehouseUsers = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    async addAccessUser() {
      if (!this.accessForm.user_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาพิมพ์และเลือกพนักงานจากในรายการ', 'warning');
        return;
      }
      this.isSavingAccess = true;
      try {
        const payload = {
          action: 'add',
          warehouse_id: this.currentAccessWarehouse.id,
          user_id: this.accessForm.user_id,
          access_level: this.accessForm.access_level
        };
        const res = await axios.post('/api-digital/inventory/warehouse_users_save.php', payload);
        if (res.data.status === 'success') {
          this.accessForm.user_id = '';
          await this.fetchWarehouseUsers(this.currentAccessWarehouse.id);
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.isSavingAccess = false;
      }
    },
    async removeAccessUser(id) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบสิทธิ์?',
        text: 'เจ้าหน้าที่รายนี้จะไม่สามารถเข้าถึงคลังนี้ได้อีก',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#secondary',
        confirmButtonText: 'ใช่, ลบเลย'
      });
      
      if (confirm.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/inventory/warehouse_users_save.php', { action: 'remove', id: id });
          if (res.data.status === 'success') {
            await this.fetchWarehouseUsers(this.currentAccessWarehouse.id);
          }
        } catch (err) {
          console.error(err);
        }
      }
    },
    openCategoryModal(cat = null) {
      if (cat) {
        this.isEditCategory = true;
        this.categoryForm = { id: cat.id, warehouse_id: cat.warehouse_id, code: cat.code, name: cat.name, active: cat.active };
      } else {
        this.isEditCategory = false;
        this.categoryForm = { id: null, warehouse_id: '', code: '', name: '', active: 1 };
      }
      const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      modal.show();
    },
    async saveCategory() {
      this.isSavingCategory = true;
      try {
        const method = this.isEditCategory ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/categories.php', this.categoryForm);
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: 'เพิ่มหมวดหมู่เรียบร้อยแล้ว',
            icon: 'success',
            confirmButtonColor: '#0d6efd'
          });
          
          const modalEl = document.getElementById('categoryModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
          
          this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบเกิดข้อขัดข้อง', 'error');
        console.error(err);
      } finally {
        this.isSavingCategory = false;
      }
    },
    openItemModal(item = null) {
      if (item) {
        this.isEditItem = true;
        this.itemForm = {
          id: item.id, item_code: item.item_code, old_code: item.old_code,
          item_name: item.item_name, item_name_en: item.item_name_en,
          warehouse_id: item.warehouse_id, category_id: item.category_id,
          unit_id: item.unit_id, purchase_unit_id: item.purchase_unit_id,
          issue_unit_id: item.issue_unit_id, conversion_rate: item.conversion_rate,
          barcode: item.barcode, brand: item.brand, model: item.model,
          minimum_stock: item.minimum_stock, maximum_stock: item.maximum_stock,
          reorder_point: item.reorder_point, standard_price: item.standard_price,
          last_purchase_price: item.last_purchase_price,
          lot_control: item.lot_control, expiry_control: item.expiry_control,
          serial_control: item.serial_control, active: item.active
        };
      } else {
        this.isEditItem = false;
        this.itemForm = {
          id: null, item_code: '', old_code: '', item_name: '', item_name_en: '',
          warehouse_id: '', category_id: '',
          unit_id: '', purchase_unit_id: '', issue_unit_id: '', conversion_rate: 1.00,
          barcode: '', brand: '', model: '',
          minimum_stock: 0, maximum_stock: 0, reorder_point: 0,
          standard_price: 0, last_purchase_price: 0,
          lot_control: 0, expiry_control: 0, serial_control: 0, active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('itemModal'));
      modal.show();
    },
    async saveItem() {
      this.isSaving = true;
      try {
        const method = this.isEditItem ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/items.php', this.itemForm);
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'ลงทะเบียนสำเร็จ!',
            text: 'ระบบได้บันทึกข้อมูล Item เรียบร้อยแล้ว',
            icon: 'success',
            confirmButtonColor: '#198754'
          });
          
          // Close Modal
          const modalEl = document.getElementById('itemModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
          
          // Refresh data
          this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ระบบเกิดข้อขัดข้องระหว่างบันทึก', 'error');
        console.error(err);
      } finally {
        this.isSaving = false;
      }
    }
  }
};
</script>

<style scoped>
.nav-tabs .nav-link {
  color: #6c757d;
  border-bottom: 3px solid transparent !important;
}
.nav-tabs .nav-link.active {
  color: #0d6efd;
  border-color: transparent transparent #0d6efd !important;
  background-color: transparent;
}
</style>
