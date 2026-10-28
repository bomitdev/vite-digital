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
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="unit-tab" data-bs-toggle="tab" data-bs-target="#unit" type="button" role="tab" @click="activeTab='unit'">
                <i class="bi bi-rulers me-1"></i> หน่วยนับ ({{ filteredUnits.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="buymethod-tab" data-bs-toggle="tab" data-bs-target="#buymethod" type="button" role="tab" @click="activeTab='buymethod'">
                <i class="bi bi-cart-check me-1"></i> วิธีจัดซื้อ ({{ filteredBuyMethods.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="item-tab" data-bs-toggle="tab" data-bs-target="#item" type="button" role="tab" @click="activeTab='item'">
                <i class="bi bi-box-seam me-1"></i> รายการวัสดุ (Items)
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="vendor-tab" data-bs-toggle="tab" data-bs-target="#vendor" type="button" role="tab" @click="activeTab='vendor'">
                <i class="bi bi-shop me-1"></i> บริษัท / ผู้จำหน่าย ({{ filteredVendors.length }})
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
                <button class="btn btn-primary rounded-pill" @click="openCategoryModal()"><i class="bi bi-plus-lg"></i> เพิ่มหมวดหมู่</button>
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
                      <td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลหมวดหมู่</td>
                    </tr>
                    <tr v-for="c in categories" :key="c.id">
                      <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                          <i class="bi bi-building me-1"></i>{{ c.warehouse_name }}
                        </span>
                      </td>
                      <td><code>{{ c.code }}</code></td>
                      <td class="fw-semibold">{{ c.name }}</td>
                      <td>
                        <span class="badge" :class="Number(c.active) ? 'bg-success' : 'bg-danger'">
                          {{ Number(c.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openCategoryModal(c)" title="แก้ไขหมวดหมู่"><i class="bi bi-pencil"></i></button>
                          <button class="btn btn-outline-danger" @click="deleteCategory(c)" title="ลบหมวดหมู่"><i class="bi bi-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Units Tab -->
            <div class="tab-pane fade" id="unit" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-rulers me-2 text-primary"></i>รายชื่อหน่วยนับพัสดุและเวชภัณฑ์ (Unit Master)</h5>
                  <p class="text-muted small mb-0">จัดการหน่วยนับพื้นฐาน สำหรับกำหนดในรายการวัสดุ การสั่งซื้อ การรับเข้า และการเบิกจ่ายพัสดุ</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button class="btn btn-outline-info rounded-pill px-3 shadow-xs" @click="importUnitsFromInvs" :disabled="isImportingUnits" title="นำเข้าหน่วยนับจากระบบ INVS เดิม">
                    <span v-if="isImportingUnits" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-cloud-arrow-down me-1"></i> ดึงข้อมูลจาก INVS
                  </button>
                  <button class="btn btn-outline-secondary rounded-pill px-3 shadow-xs" @click="seedDefaultUnits" :disabled="isSeedingUnits" title="เพิ่มหน่วยนับมาตรฐานทั่วไป (ชิ้น, กล่อง, แผง, ขวด ฯลฯ)">
                    <span v-if="isSeedingUnits" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-magic me-1"></i> หน่วยมาตรฐาน
                  </button>
                  <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openUnitModal()">
                    <i class="bi bi-plus-lg me-1"></i> เพิ่มหน่วยนับใหม่
                  </button>
                </div>
              </div>

              <!-- Search Bar & Filters -->
              <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-5">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="ค้นหาชื่อหน่วยนับ, รหัส..." 
                      v-model="unitSearchQuery"
                    />
                    <button class="btn btn-outline-secondary" v-if="unitSearchQuery" @click="unitSearchQuery = ''">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-3 col-lg-3">
                  <select class="form-select" v-model="unitFilterStatus">
                    <option value="">ทั้งหมด (All Status)</option>
                    <option value="1">เปิดใช้งาน (Active)</option>
                    <option value="0">ปิดใช้งาน (Inactive)</option>
                  </select>
                </div>
                <div class="col-12 col-lg-4 d-flex align-items-center justify-content-lg-end">
                  <span class="badge bg-light text-secondary border px-3 py-2">
                    รวม {{ filteredUnits.length }} หน่วยนับ
                  </span>
                </div>
              </div>

              <!-- Units Table -->
              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 80px;" class="text-center ps-3">ลำดับ</th>
                      <th style="width: 140px;">รหัสหน่วย</th>
                      <th>ชื่อหน่วยนับ</th>
                      <th class="text-center" style="width: 160px;">รายการวัสดุที่ใช้</th>
                      <th class="text-center" style="width: 120px;">สถานะ</th>
                      <th class="text-center" style="width: 120px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loadingUnits">
                      <td colspan="6" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลหน่วยนับ...
                      </td>
                    </tr>
                    <tr v-else-if="filteredUnits.length === 0">
                      <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-rulers fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ไม่พบข้อมูลหน่วยนับ</span>
                        <div class="mt-2">
                          <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openUnitModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มหน่วยแรก
                          </button>
                          <button class="btn btn-sm btn-outline-info rounded-pill px-3 ms-2" @click="importUnitsFromInvs">
                            <i class="bi bi-cloud-arrow-down me-1"></i> ดึงจาก INVS
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="(u, idx) in filteredUnits" :key="u.id">
                      <td class="text-center text-muted small ps-3">{{ idx + 1 }}</td>
                      <td>
                        <span class="badge bg-light text-dark border font-monospace">{{ u.code || '-' }}</span>
                      </td>
                      <td class="fw-bold text-dark">
                        {{ u.name }}
                      </td>
                      <td class="text-center">
                        <span class="badge rounded-pill" :class="Number(u.item_count) > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border'">
                          <i class="bi bi-box-seam me-1"></i>{{ u.item_count || 0 }} รายการ
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(u.active) ? 'bg-success' : 'bg-secondary'">
                          {{ Number(u.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openUnitModal(u)" title="แก้ไขหน่วยนับ">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-outline-danger" @click="deleteUnit(u)" title="ลบหน่วยนับ">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Buy Methods Tab -->
            <div class="tab-pane fade" id="buymethod" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-cart-check me-2 text-primary"></i>วิธีจัดซื้อพัสดุ / เวชภัณฑ์ (Procurement Methods)</h5>
                  <p class="text-muted small mb-0">กำหนดวิธีจัดซื้อตามระเบียบพัสดุ สำหรับใช้ในการออกใบขอซื้อ (PR), ใบสั่งซื้อ (PO), และรายงานการจัดซื้อ</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button class="btn btn-outline-info rounded-pill px-3 shadow-xs" @click="importBuyMethodsFromInvs" :disabled="isImportingBuyMethods" title="นำเข้าวิธีจัดซื้อจากระบบ INVS เดิม">
                    <span v-if="isImportingBuyMethods" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-cloud-arrow-down me-1"></i> ดึงข้อมูลจาก INVS
                  </button>
                  <button class="btn btn-outline-secondary rounded-pill px-3 shadow-xs" @click="seedDefaultBuyMethods" :disabled="isSeedingBuyMethods" title="เพิ่มวิธีจัดซื้อมาตรฐานตามระเบียบพัสดุ">
                    <span v-if="isSeedingBuyMethods" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-magic me-1"></i> วิธีจัดซื้อมาตรฐาน
                  </button>
                  <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openBuyMethodModal()">
                    <i class="bi bi-plus-lg me-1"></i> เพิ่มวิธีจัดซื้อใหม่
                  </button>
                </div>
              </div>

              <!-- Search Bar & Filters -->
              <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-5">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="ค้นหารหัส, ชื่อวิธีจัดซื้อ..." 
                      v-model="buyMethodSearchQuery"
                    />
                    <button class="btn btn-outline-secondary" v-if="buyMethodSearchQuery" @click="buyMethodSearchQuery = ''">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-3 col-lg-3">
                  <select class="form-select" v-model="buyMethodFilterStatus">
                    <option value="">ทั้งหมด (All Status)</option>
                    <option value="1">เปิดใช้งาน (Active)</option>
                    <option value="0">ปิดใช้งาน (Inactive)</option>
                  </select>
                </div>
                <div class="col-12 col-lg-4 d-flex align-items-center justify-content-lg-end">
                  <span class="badge bg-light text-secondary border px-3 py-2">
                    รวม {{ filteredBuyMethods.length }} วิธีจัดซื้อ
                  </span>
                </div>
              </div>

              <!-- Buy Methods Table -->
              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 80px;" class="text-center ps-3">รหัส</th>
                      <th>ชื่อวิธีจัดซื้อ</th>
                      <th class="text-center" style="width: 140px;">ค่าเริ่มต้น PO</th>
                      <th class="text-center" style="width: 160px;">กำหนดยืนราคา/ส่งมอบ</th>
                      <th class="text-center" style="width: 140px;">ใบสั่งซื้อที่ใช้</th>
                      <th class="text-center" style="width: 110px;">สถานะ</th>
                      <th class="text-center" style="width: 110px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loadingBuyMethods">
                      <td colspan="7" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลวิธีจัดซื้อ...
                      </td>
                    </tr>
                    <tr v-else-if="filteredBuyMethods.length === 0">
                      <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ไม่พบข้อมูลวิธีจัดซื้อ</span>
                        <div class="mt-2">
                          <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openBuyMethodModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มวิธีจัดซื้อแรก
                          </button>
                          <button class="btn btn-sm btn-outline-info rounded-pill px-3 ms-2" @click="importBuyMethodsFromInvs">
                            <i class="bi bi-cloud-arrow-down me-1"></i> ดึงจาก INVS
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="m in filteredBuyMethods" :key="m.id">
                      <td class="text-center ps-3">
                        <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ m.code }}</span>
                      </td>
                      <td>
                        <div class="fw-bold text-dark">{{ m.name }}</div>
                      </td>
                      <td class="text-center">
                        <span v-if="Number(m.is_default)" class="badge bg-warning text-dark border border-warning px-2 py-1">
                          <i class="bi bi-star-fill me-1"></i>ค่าเริ่มต้น
                        </span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-light text-secondary border">
                          <i class="bi bi-clock me-1"></i>{{ m.deal_days || 0 }} วัน
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge rounded-pill" :class="Number(m.po_count) > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border'">
                          <i class="bi bi-file-earmark-text me-1"></i>{{ m.po_count || 0 }} ใบ
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(m.active) ? 'bg-success' : 'bg-secondary'">
                          {{ Number(m.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openBuyMethodModal(m)" title="แก้ไขวิธีจัดซื้อ">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-outline-danger" @click="deleteBuyMethod(m)" title="ลบวิธีจัดซื้อ">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
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
                <button class="btn btn-success rounded-pill" @click="openItemModal()"><i class="bi bi-plus-lg"></i> ลงทะเบียนวัสดุใหม่</button>
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

            <!-- Vendors / Companies Tab -->
            <div class="tab-pane fade" id="vendor" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-shop me-2 text-primary"></i>รายชื่อบริษัท / ร้านค้าผู้จำหน่าย</h5>
                  <p class="text-muted small mb-0">จัดการข้อมูลคู่ค้า ผู้จัดจำหน่ายเวชภัณฑ์และพัสดุ สำหรับใช้ในใบขอซื้อ (PR) ใบสั่งซื้อ (PO) และการตรวจรับ</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button class="btn btn-outline-info rounded-pill px-3 shadow-xs" @click="importVendorsFromInvs" :disabled="isImportingVendors" title="นำเข้าข้อมูลบริษัทจากฐานข้อมูลเดิม INVS">
                    <span v-if="isImportingVendors" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-cloud-arrow-down me-1"></i> ดึงข้อมูลจาก INVS
                  </button>
                  <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openVendorModal()">
                    <i class="bi bi-plus-lg me-1"></i> เพิ่มบริษัทใหม่
                  </button>
                </div>
              </div>

              <!-- Search Bar & Warehouse Filter -->
              <div class="row g-3 mb-4">
                <div class="col-md-5 col-lg-4">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="ค้นหาชื่อบริษัท, รหัส, เลขผู้เสียภาษี, เบอร์โทร..." 
                      v-model="vendorSearchQuery"
                    />
                    <button class="btn btn-outline-secondary" v-if="vendorSearchQuery" @click="vendorSearchQuery = ''">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-4 col-lg-3">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-primary border-end-0"><i class="bi bi-building"></i></span>
                    <select class="form-select border-start-0 fw-bold text-dark" v-model="selectedVendorWarehouse" @change="fetchVendors">
                      <option value="">-- คลังทั้งหมด (All) --</option>
                      <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        [{{ w.code }}] {{ w.name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3 col-lg-2">
                  <select class="form-select" v-model="vendorFilterStatus">
                    <option value="">ทั้งหมด (All Status)</option>
                    <option value="1">เปิดใช้งาน</option>
                    <option value="0">ปิดใช้งาน</option>
                  </select>
                </div>
                <div class="col-12 col-lg-3 d-flex align-items-center justify-content-lg-end">
                  <span class="badge bg-light text-secondary border px-3 py-2">
                    รวม {{ filteredVendors.length }} บริษัท
                    <span v-if="selectedVendorWarehouseName" class="text-primary fw-bold ms-1">({{ selectedVendorWarehouseName }})</span>
                  </span>
                </div>
              </div>

              <!-- Vendors Table -->
              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 120px;" class="ps-3">รหัสบริษัท</th>
                      <th>ชื่อบริษัท / ร้านค้า</th>
                      <th style="width: 180px;">คลังที่สังกัด</th>
                      <th>เลขผู้เสียภาษี</th>
                      <th>เบอร์โทรศัพท์ / ผู้ติดต่อ</th>
                      <th>ที่อยู่</th>
                      <th class="text-center" style="width: 100px;">สถานะ</th>
                      <th class="text-center" style="width: 100px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loadingVendors">
                      <td colspan="8" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลบริษัท...
                      </td>
                    </tr>
                    <tr v-else-if="filteredVendors.length === 0">
                      <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-shop fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ไม่พบข้อมูลบริษัท / ผู้จำหน่าย</span>
                        <div class="mt-2" v-if="vendors.length === 0">
                          <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openVendorModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มบริษัทแรก
                          </button>
                          <button class="btn btn-sm btn-outline-info rounded-pill px-3 ms-2" @click="importVendorsFromInvs">
                            <i class="bi bi-cloud-arrow-down me-1"></i> ดึงจาก INVS
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="v in filteredVendors" :key="v.id">
                      <td class="ps-3">
                        <span class="badge bg-light text-dark border font-monospace">{{ v.vendor_code || '-' }}</span>
                      </td>
                      <td>
                        <div class="fw-bold text-dark">{{ v.vendor_name }}</div>
                        <small class="text-muted" v-if="v.bank_info"><i class="bi bi-bank me-1"></i>{{ v.bank_info }}</small>
                      </td>
                      <td>
                        <span v-if="v.warehouse_name" class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                          <i class="bi bi-building me-1"></i>{{ v.warehouse_name }}
                        </span>
                        <span v-else class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                          <i class="bi bi-globe me-1"></i>ทุกคลัง
                        </span>
                      </td>
                      <td>
                        <span class="small font-monospace text-secondary">{{ v.tax_id || '-' }}</span>
                      </td>
                      <td>
                        <div class="small fw-semibold text-dark" v-if="v.phone"><i class="bi bi-telephone me-1 text-muted"></i>{{ v.phone }}</div>
                        <div class="small text-muted" v-if="v.contact_person"><i class="bi bi-person me-1"></i>{{ v.contact_person }}</div>
                        <span v-if="!v.phone && !v.contact_person" class="text-muted small">-</span>
                      </td>
                      <td class="small text-muted text-truncate" style="max-width: 200px;" :title="v.address">
                        {{ v.address || '-' }}
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(v.active) ? 'bg-success' : 'bg-secondary'">
                          {{ Number(v.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openVendorModal(v)" title="แก้ไขข้อมูลบริษัท">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-outline-danger" @click="deleteVendor(v)" title="ลบหรือปิดการใช้งาน">
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

    <!-- Vendor / Company Modal -->
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-shop me-2"></i>{{ isEditVendor ? 'แก้ไขข้อมูลบริษัท / ผู้จำหน่าย' : 'เพิ่มบริษัท / ผู้จำหน่ายใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveVendor">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">คลังพัสดุที่สังกัด <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="vendorForm.warehouse_id" required>
                    <option value="">-- เลือกคลังพัสดุ --</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                      [{{ w.code }}] {{ w.name }}
                    </option>
                  </select>
                  <div class="form-text text-muted small">บริษัทจะสังกัดและแสดงในคลังที่ระบุนี้</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">รหัสบริษัท / ร้านค้า</label>
                  <input type="text" class="form-control" v-model="vendorForm.vendor_code" placeholder="เช่น VND-0001 (เว้นว่างเพื่อสร้างอัตโนมัติ)" />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">ชื่อบริษัท / ผู้จำหน่าย <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="vendorForm.vendor_name" required placeholder="เช่น บริษัท เอเชีย เมดิไซเอนซ์ จำกัด" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">เลขประจำตัวผู้เสียภาษี (Tax ID)</label>
                  <input type="text" class="form-control" v-model="vendorForm.tax_id" placeholder="เลข 13 หลัก" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">เบอร์โทรศัพท์ติดต่อ</label>
                  <input type="text" class="form-control" v-model="vendorForm.phone" placeholder="เช่น 02-xxx-xxxx, 08x-xxx-xxxx" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">อีเมล (Email)</label>
                  <input type="email" class="form-control" v-model="vendorForm.email" placeholder="sales@company.com" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">ผู้ประสานงาน / ผู้แทนขาย (เซลล์)</label>
                  <input type="text" class="form-control" v-model="vendorForm.contact_person" placeholder="ชื่อ-สกุล หรือเบอร์โทรเซลล์" />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">ที่อยู่บริษัท</label>
                  <textarea class="form-control" rows="2" v-model="vendorForm.address" placeholder="เลขที่ ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด รหัสไปรษณีย์"></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">ข้อมูลบัญชีธนาคาร</label>
                  <input type="text" class="form-control" v-model="vendorForm.bank_info" placeholder="เช่น ธนาคารกสิกรไทย เลขที่ 123-4-56789-0 ชื่อบัญชี บจก. ..." />
                </div>
                <div class="col-12">
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="vendorActive" v-model="vendorForm.active" :true-value="1" :false-value="0">
                    <label class="form-check-label fw-semibold" for="vendorActive">เปิดใช้งานบริษัทนี้</label>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingVendor">
                  <span v-if="isSavingVendor" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Unit Modal -->
    <div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-rulers me-2"></i>{{ isEditUnit ? 'แก้ไขหน่วยนับ' : 'เพิ่มหน่วยนับใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveUnit">
              <div class="mb-3">
                <label class="form-label small fw-semibold">รหัสหน่วยนับ (Unit Code)</label>
                <input type="text" class="form-control" v-model="unitForm.code" placeholder="เช่น U001, PIECE, BOX (เว้นว่างได้)" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อหน่วยนับ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="unitForm.name" required placeholder="เช่น ชิ้น, กล่อง, แผง, ขวด, เม็ด" />
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="unitActive" v-model="unitForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="unitActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingUnit">
                  <span v-if="isSavingUnit" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Buy Method Modal -->
    <div class="modal fade" id="buyMethodModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-cart-check me-2"></i>{{ isEditBuyMethod ? 'แก้ไขวิธีจัดซื้อ' : 'เพิ่มวิธีจัดซื้อใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveBuyMethod">
              <div class="mb-3">
                <label class="form-label small fw-semibold">รหัสวิธีจัดซื้อ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="buyMethodForm.code" required placeholder="เช่น 13, 14, 8, 12, 1" />
                <div class="form-text small text-muted">รหัสตามมาตรฐานระบบจัดซื้อพัสดุภาครัฐ (e-GP / INVS)</div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อวิธีจัดซื้อ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="buyMethodForm.name" required placeholder="เช่น วิธีเฉพาะเจาะจง, ตกลงราคา" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">กำหนดยืนราคา / ส่งมอบเริ่มต้น (วัน)</label>
                <input type="number" class="form-control" v-model.number="buyMethodForm.deal_days" placeholder="30" min="0" />
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="buyMethodDefault" v-model="buyMethodForm.is_default" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="buyMethodDefault">ตั้งเป็นค่าเริ่มต้นสำหรับการสร้างใบสั่งซื้อ (PO Default)</label>
                </div>
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="buyMethodActive" v-model="buyMethodForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="buyMethodActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingBuyMethod">
                  <span v-if="isSavingBuyMethod" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
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
  props: {
    warehouseId: {
      type: [String, Number],
      default: ''
    }
  },
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
      },
      // Vendor / Company state
      vendors: [],
      loadingVendors: false,
      isSavingVendor: false,
      isEditVendor: false,
      isImportingVendors: false,
      selectedVendorWarehouse: '',
      vendorSearchQuery: '',
      vendorFilterStatus: '',
      vendorForm: {
        id: null,
        warehouse_id: '',
        vendor_code: '',
        vendor_name: '',
        tax_id: '',
        phone: '',
        email: '',
        contact_person: '',
        address: '',
        bank_info: '',
        active: 1
      },
      // Units state
      unitSearchQuery: '',
      unitFilterStatus: '',
      loadingUnits: false,
      isSavingUnit: false,
      isEditUnit: false,
      isImportingUnits: false,
      isSeedingUnits: false,
      unitForm: {
        id: null,
        code: '',
        name: '',
        active: 1
      },
      // Buy Methods state
      buyMethods: [],
      loadingBuyMethods: false,
      isSavingBuyMethod: false,
      isEditBuyMethod: false,
      isImportingBuyMethods: false,
      isSeedingBuyMethods: false,
      buyMethodSearchQuery: '',
      buyMethodFilterStatus: '',
      buyMethodForm: {
        id: null,
        code: '',
        name: '',
        deal_days: 30,
        is_default: 0,
        active: 1
      }
    };
  },
  computed: {
    selectedVendorWarehouseName() {
      if (!this.selectedVendorWarehouse) return '';
      const wh = this.warehouses.find(w => String(w.id) === String(this.selectedVendorWarehouse));
      return wh ? wh.name : '';
    },
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
    },
    filteredVendors() {
      let list = this.vendors;
      if (this.vendorFilterStatus !== '') {
        list = list.filter(v => Number(v.active) === Number(this.vendorFilterStatus));
      }
      if (this.vendorSearchQuery) {
        const q = this.vendorSearchQuery.toLowerCase().trim();
        list = list.filter(v => 
          (v.vendor_name && v.vendor_name.toLowerCase().includes(q)) || 
          (v.vendor_code && v.vendor_code.toLowerCase().includes(q)) || 
          (v.tax_id && v.tax_id.toLowerCase().includes(q)) || 
          (v.phone && v.phone.toLowerCase().includes(q)) || 
          (v.contact_person && v.contact_person.toLowerCase().includes(q)) ||
          (v.warehouse_name && v.warehouse_name.toLowerCase().includes(q))
        );
      }
      return list;
    },
    filteredUnits() {
      let list = this.units;
      if (this.unitFilterStatus !== '') {
        list = list.filter(u => Number(u.active) === Number(this.unitFilterStatus));
      }
      if (this.unitSearchQuery) {
        const q = this.unitSearchQuery.toLowerCase().trim();
        list = list.filter(u => 
          (u.name && u.name.toLowerCase().includes(q)) || 
          (u.code && u.code.toLowerCase().includes(q))
        );
      }
      return list;
    },
    filteredBuyMethods() {
      let list = this.buyMethods;
      if (this.buyMethodFilterStatus !== '') {
        list = list.filter(m => Number(m.active) === Number(this.buyMethodFilterStatus));
      }
      if (this.buyMethodSearchQuery) {
        const q = this.buyMethodSearchQuery.toLowerCase().trim();
        list = list.filter(m => 
          (m.name && m.name.toLowerCase().includes(q)) || 
          (m.code && m.code.toLowerCase().includes(q))
        );
      }
      return list;
    }
  },
  watch: {
    warehouseId(newVal) {
      if (newVal) {
        this.selectedVendorWarehouse = String(newVal);
        this.fetchVendors();
      }
    }
  },
  mounted() {
    const qWh = this.warehouseId || this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id');
    if (qWh) {
      this.selectedVendorWarehouse = String(qWh);
      this.categoryForm.warehouse_id = qWh;
      this.itemForm.warehouse_id = qWh;
      this.vendorForm.warehouse_id = qWh;
    }
    const subtab = this.$route.query.subtab;
    if (subtab && ['wh', 'cat', 'unit', 'buymethod', 'item', 'vendor'].includes(subtab)) {
      this.activeTab = subtab;
      this.$nextTick(() => {
        const tabBtn = document.getElementById(`${subtab}-tab`);
        if (tabBtn) {
          const trigger = new bootstrap.Tab(tabBtn);
          trigger.show();
        }
      });
    }
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const vendorParams = new URLSearchParams();
        if (this.selectedVendorWarehouse) {
          vendorParams.append('warehouse_id', this.selectedVendorWarehouse);
        }
        const [whRes, catRes, itemRes, unitRes, staffRes, vendorRes, buyMethodRes] = await Promise.all([
          axios.get('/api-digital/inventory/warehouses.php?is_admin=1'),
          axios.get('/api-digital/inventory/categories.php'),
          axios.get('/api-digital/inventory/items.php'),
          axios.get('/api-digital/inventory/units.php'),
          axios.get('/api-hosoffice/get_all_staff.php').catch(() => null),
          axios.get(`/api-digital/inventory/vendors.php?${vendorParams.toString()}`).catch(() => null),
          axios.get('/api-digital/inventory/buy_methods.php').catch(() => null)
        ]);
        
        if (whRes?.data?.status === 'success') this.warehouses = whRes.data.data;
        if (catRes?.data?.status === 'success') this.categories = catRes.data.data;
        if (itemRes?.data?.status === 'success') this.items = itemRes.data.data;
        if (unitRes?.data?.status === 'success') this.units = unitRes.data.data;
        if (staffRes?.data?.status === 'success') this.allStaff = staffRes.data.data;
        if (vendorRes?.data?.status === 'success') this.vendors = vendorRes.data.data;
        if (buyMethodRes?.data?.status === 'success') this.buyMethods = buyMethodRes.data.data;
      } catch (err) {
        console.error("Error fetching master data", err);
      }
    },
    openWarehouseModal(wh = null) {
      if (wh && wh.id) {
        this.isEditWarehouse = true;
        this.warehouseForm = { id: wh.id, code: wh.code, name: wh.name, active: wh.active !== undefined ? Number(wh.active) : 1 };
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
      if (cat && cat.id) {
        this.isEditCategory = true;
        this.categoryForm = {
          id: cat.id,
          warehouse_id: cat.warehouse_id,
          code: cat.code,
          name: cat.name,
          active: cat.active !== undefined ? Number(cat.active) : 1
        };
      } else {
        this.isEditCategory = false;
        const defaultWh = this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : '');
        this.categoryForm = {
          id: null,
          warehouse_id: defaultWh,
          code: '',
          name: '',
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      modal.show();
    },
    async saveCategory() {
      if (!this.categoryForm.warehouse_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกคลังวัสดุ', 'warning');
        return;
      }
      if (!this.categoryForm.code || !this.categoryForm.code.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสหมวดหมู่', 'warning');
        return;
      }
      if (!this.categoryForm.name || !this.categoryForm.name.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกชื่อหมวดหมู่', 'warning');
        return;
      }

      this.isSavingCategory = true;
      try {
        const method = (this.isEditCategory && this.categoryForm.id) ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/categories.php', this.categoryForm);
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message || (this.isEditCategory ? 'แก้ไขหมวดหมู่เรียบร้อยแล้ว' : 'เพิ่มหมวดหมู่เรียบร้อยแล้ว'),
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
        const msg = err.response?.data?.message || 'ระบบเกิดข้อขัดข้องระหว่างบันทึก';
        Swal.fire('ข้อผิดพลาด', msg, 'error');
        console.error(err);
      } finally {
        this.isSavingCategory = false;
      }
    },
    async deleteCategory(cat) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบหมวดหมู่?',
        text: `คุณต้องการลบหมวดหมู่ "${cat.name}" (${cat.code}) ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ต้องการลบ',
        cancelButtonText: 'ยกเลิก'
      });
      if (confirm.isConfirmed) {
        try {
          const res = await axios.delete(`/api-digital/inventory/categories.php?id=${cat.id}`);
          if (res.data.status === 'success') {
            Swal.fire('ลบสำเร็จ!', res.data.message || 'ลบหมวดหมู่เรียบร้อยแล้ว', 'success');
            this.fetchData();
          } else {
            Swal.fire('ไม่สามารถลบได้', res.data.message || 'ลบไม่สำเร็จ', 'error');
          }
        } catch (err) {
          const msg = err.response?.data?.message || 'เกิดข้อผิดพลาดในการลบ';
          Swal.fire('ไม่สามารถลบได้', msg, 'error');
        }
      }
    },
    openItemModal(item = null) {
      if (item && item.id) {
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
        const defaultWh = this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : '');
        this.itemForm = {
          id: null, item_code: '', old_code: '', item_name: '', item_name_en: '',
          warehouse_id: defaultWh, category_id: '',
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
        const method = (this.isEditItem && this.itemForm.id) ? 'put' : 'post';
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
    },
    // Vendor / Company Methods
    async fetchVendors() {
      this.loadingVendors = true;
      try {
        const params = new URLSearchParams();
        if (this.selectedVendorWarehouse) {
          params.append('warehouse_id', this.selectedVendorWarehouse);
        }
        const res = await axios.get(`/api-digital/inventory/vendors.php?${params.toString()}`);
        if (res.data?.status === 'success') {
          this.vendors = res.data.data || [];
        }
      } catch (err) {
        console.error("Error fetching vendors:", err);
      } finally {
        this.loadingVendors = false;
      }
    },
    openVendorModal(vendor = null) {
      if (vendor && vendor.id) {
        this.isEditVendor = true;
        this.vendorForm = {
          id: vendor.id,
          warehouse_id: vendor.warehouse_id || '',
          vendor_code: vendor.vendor_code || '',
          vendor_name: vendor.vendor_name || '',
          tax_id: vendor.tax_id || '',
          phone: vendor.phone || '',
          email: vendor.email || '',
          contact_person: vendor.contact_person || '',
          address: vendor.address || '',
          bank_info: vendor.bank_info || '',
          active: vendor.active !== undefined ? Number(vendor.active) : 1
        };
      } else {
        this.isEditVendor = false;
        this.vendorForm = {
          id: null,
          warehouse_id: this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : ''),
          vendor_code: '',
          vendor_name: '',
          tax_id: '',
          phone: '',
          email: '',
          contact_person: '',
          address: '',
          bank_info: '',
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('vendorModal'));
      modal.show();
    },
    async saveVendor() {
      if (!this.vendorForm.warehouse_id) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกคลังพัสดุที่สังกัด', 'warning');
        return;
      }
      this.isSavingVendor = true;
      try {
        const method = this.isEditVendor ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/vendors.php', this.vendorForm);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message || 'บันทึกข้อมูลบริษัทเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });

          const modalEl = document.getElementById('vendorModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();

          this.fetchVendors();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Save vendor error:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
      } finally {
        this.isSavingVendor = false;
      }
    },
    async deleteVendor(vendor) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบ / ปิดการใช้งาน?',
        html: `คุณต้องการลบหรือปิดการใช้งานบริษัท <strong class="text-danger">${vendor.vendor_name}</strong> หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.delete(`/api-digital/inventory/vendors.php?id=${vendor.id}`);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ!',
            text: res.data.message || 'ลบข้อมูลบริษัทเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });
          this.fetchVendors();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Delete vendor error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
      }
    },
    async importVendorsFromInvs() {
      const targetWhId = this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : '');
      const whOptions = {};
      this.warehouses.forEach(w => {
        whOptions[w.id] = `[${w.code}] ${w.name}`;
      });

      const { value: chosenWh, isConfirmed } = await Swal.fire({
        title: 'ดึงข้อมูลบริษัทจาก INVS',
        html: 'เลือกคลังพัสดุที่ต้องการผูกกับรายชื่อบริษัทที่จะนำเข้าจาก INVS:',
        icon: 'question',
        input: 'select',
        inputOptions: whOptions,
        inputValue: targetWhId,
        inputPlaceholder: '-- เลือกคลังพัสดุ --',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-cloud-arrow-down me-1"></i> ยืนยันดึงข้อมูล',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (value) => {
          if (!value) {
            return 'กรุณาเลือกคลังพัสดุที่จะผูกข้อมูล';
          }
        }
      });

      if (!isConfirmed || !chosenWh) return;

      this.isImportingVendors = true;
      try {
        const res = await axios.post('/api-digital/inventory/vendors.php', { 
          action: 'import_invs',
          warehouse_id: chosenWh
        });
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.selectedVendorWarehouse = chosenWh;
          this.fetchVendors();
        } else {
          Swal.fire('ไม่สามารถดึงข้อมูลได้', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Import INVS vendors error:", err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อฐานข้อมูล INVS ได้', 'error');
      } finally {
        this.isImportingVendors = false;
      }
    },
    // Units Methods
    async fetchUnits() {
      this.loadingUnits = true;
      try {
        const res = await axios.get('/api-digital/inventory/units.php');
        if (res.data?.status === 'success') {
          this.units = res.data.data;
        }
      } catch (err) {
        console.error("Error fetching units", err);
      } finally {
        this.loadingUnits = false;
      }
    },
    openUnitModal(unit = null) {
      if (unit && unit.id) {
        this.isEditUnit = true;
        this.unitForm = {
          id: unit.id,
          code: unit.code || '',
          name: unit.name || '',
          active: unit.active !== undefined ? Number(unit.active) : 1
        };
      } else {
        this.isEditUnit = false;
        this.unitForm = {
          id: null,
          code: '',
          name: '',
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('unitModal'));
      modal.show();
    },
    async saveUnit() {
      if (!this.unitForm.name.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่อหน่วยนับ', 'warning');
        return;
      }
      this.isSavingUnit = true;
      try {
        const method = this.isEditUnit ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/units.php', this.unitForm);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message || 'บันทึกข้อมูลหน่วยนับเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });

          const modalEl = document.getElementById('unitModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();

          this.fetchUnits();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Save unit error:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
      } finally {
        this.isSavingUnit = false;
      }
    },
    async deleteUnit(unit) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบหน่วยนับ?',
        html: `คุณต้องการลบหน่วยนับ <strong class="text-danger">${unit.name}</strong> หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.delete(`/api-digital/inventory/units.php?id=${unit.id}`);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ!',
            text: res.data.message || 'ลบข้อมูลหน่วยนับเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });
          this.fetchUnits();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Delete unit error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
      }
    },
    async importUnitsFromInvs() {
      const result = await Swal.fire({
        title: 'ดึงข้อมูลหน่วยนับจาก INVS',
        text: 'ต้องการนำเข้ารายชื่อหน่วยนับจากตาราง SALE_UNIT ของระบบ INVS ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-cloud-arrow-down me-1"></i> ยืนยันดึงข้อมูล',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      this.isImportingUnits = true;
      try {
        const res = await axios.post('/api-digital/inventory/units.php', { action: 'import_invs' });
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.fetchUnits();
        } else {
          Swal.fire('ไม่สามารถดึงข้อมูลได้', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Import INVS units error:", err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อฐานข้อมูล INVS ได้', 'error');
      } finally {
        this.isImportingUnits = false;
      }
    },
    async seedDefaultUnits() {
      const result = await Swal.fire({
        title: 'เพิ่มหน่วยนับมาตรฐาน',
        text: 'ต้องการเพิ่มหน่วยนับมาตรฐานทั่วไป (ชิ้น, กล่อง, แผง, ขวด, เม็ด, หลอด, ซอง, ชุด ฯลฯ) ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#adb5bd',
        confirmButtonText: '<i class="bi bi-magic me-1"></i> ยืนยันเพิ่ม',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      this.isSeedingUnits = true;
      try {
        const res = await axios.post('/api-digital/inventory/units.php', { action: 'seed_defaults' });
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'เพิ่มสำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.fetchUnits();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Seed default units error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มหน่วยนับมาตรฐาน', 'error');
      } finally {
        this.isSeedingUnits = false;
      }
    },
    // Buy Methods
    async fetchBuyMethods() {
      this.loadingBuyMethods = true;
      try {
        const res = await axios.get('/api-digital/inventory/buy_methods.php');
        if (res.data?.status === 'success') {
          this.buyMethods = res.data.data;
        }
      } catch (err) {
        console.error("Error fetching buy methods", err);
      } finally {
        this.loadingBuyMethods = false;
      }
    },
    openBuyMethodModal(item = null) {
      if (item && item.id) {
        this.isEditBuyMethod = true;
        this.buyMethodForm = {
          id: item.id,
          code: item.code || '',
          name: item.name || '',
          deal_days: item.deal_days !== undefined ? Number(item.deal_days) : 30,
          is_default: item.is_default !== undefined ? Number(item.is_default) : 0,
          active: item.active !== undefined ? Number(item.active) : 1
        };
      } else {
        this.isEditBuyMethod = false;
        this.buyMethodForm = {
          id: null,
          code: '',
          name: '',
          deal_days: 30,
          is_default: 0,
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('buyMethodModal'));
      modal.show();
    },
    async saveBuyMethod() {
      if (!this.buyMethodForm.code.trim() || !this.buyMethodForm.name.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุรหัสและชื่อวิธีจัดซื้อ', 'warning');
        return;
      }
      this.isSavingBuyMethod = true;
      try {
        const method = this.isEditBuyMethod ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/buy_methods.php', this.buyMethodForm);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message || 'บันทึกข้อมูลวิธีจัดซื้อเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });

          const modalEl = document.getElementById('buyMethodModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();

          this.fetchBuyMethods();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Save buy method error:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
      } finally {
        this.isSavingBuyMethod = false;
      }
    },
    async deleteBuyMethod(item) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบวิธีจัดซื้อ?',
        html: `คุณต้องการลบวิธีจัดซื้อ <strong class="text-danger">[${item.code}] ${item.name}</strong> หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.delete(`/api-digital/inventory/buy_methods.php?id=${item.id}`);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ!',
            text: res.data.message || 'ลบข้อมูลวิธีจัดซื้อเรียบร้อยแล้ว',
            icon: 'success',
            timer: 1600,
            showConfirmButton: false
          });
          this.fetchBuyMethods();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        console.error("Delete buy method error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
      }
    },
    async importBuyMethodsFromInvs() {
      const result = await Swal.fire({
        title: 'ดึงข้อมูลวิธีจัดซื้อจาก INVS',
        text: 'ต้องการนำเข้าวิธีจัดซื้อจากตาราง BUYMETHOD ของระบบ INVS ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0dcaf0',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-cloud-arrow-down me-1"></i> ยืนยันดึงข้อมูล',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      this.isImportingBuyMethods = true;
      try {
        const res = await axios.post('/api-digital/inventory/buy_methods.php', { action: 'import_invs' });
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.fetchBuyMethods();
        } else {
          Swal.fire('ไม่สามารถดึงข้อมูลได้', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Import INVS buy methods error:", err);
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อฐานข้อมูล INVS ได้', 'error');
      } finally {
        this.isImportingBuyMethods = false;
      }
    },
    async seedDefaultBuyMethods() {
      const result = await Swal.fire({
        title: 'เพิ่มวิธีจัดซื้อมาตรฐาน',
        text: 'ต้องการเพิ่มวิธีจัดซื้อมาตรฐานตามระเบียบพัสดุ (วิธีเฉพาะเจาะจง, e-Bidding, คัดเลือก, ตกลงราคา ฯลฯ) ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#adb5bd',
        confirmButtonText: '<i class="bi bi-magic me-1"></i> ยืนยันเพิ่ม',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      this.isSeedingBuyMethods = true;
      try {
        const res = await axios.post('/api-digital/inventory/buy_methods.php', { action: 'seed_defaults' });
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'เพิ่มสำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.fetchBuyMethods();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Seed default buy methods error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเพิ่มวิธีจัดซื้อมาตรฐาน', 'error');
      } finally {
        this.isSeedingBuyMethods = false;
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
