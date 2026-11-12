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

      <!-- Active Warehouse Context Banner -->
      <div v-if="activeWarehouse" class="alert bg-white border border-2 border-primary-subtle shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-3 p-2 bg-primary text-white fw-bold fs-4 d-flex align-items-center justify-content-center shadow-xs" style="width: 50px; height: 50px;">
            {{ activeWarehouse.code }}
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-primary text-white rounded-pill px-2">กำลังตั้งค่าเฉพาะคลังนี้</span>
              <span class="badge bg-light text-secondary border">รหัส: {{ activeWarehouse.code }}</span>
            </div>
            <h5 class="fw-bold text-dark mb-0">{{ activeWarehouse.name }}</h5>
            <small class="text-muted">ข้อมูลหมวดหมู่, พัสดุ, บริษัท และหน่วยนับ จะถูกแยกและแสดงเฉพาะคลังนี้ เพื่อป้องกันความสับสน</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary rounded-pill px-3 fw-bold d-flex align-items-center gap-2 shadow-xs" @click="$emit('switch-warehouse')">
            <i class="bi bi-arrow-left-right"></i>
            <span>สลับไปคลังอื่น</span>
          </button>
        </div>
      </div>
      <div v-else class="alert alert-secondary rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-grid-fill me-2 text-primary"></i>
          <strong>โหมดภาพรวมทุกคลัง (All Warehouses)</strong>
          <span class="text-muted ms-2 small">แสดงข้อมูลภาพรวมทั้งหมดในระบบ</span>
        </div>
        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" @click="$emit('switch-warehouse')">
          <i class="bi bi-arrow-left-right me-1"></i> เลือกคลังเพื่อจัดการเฉพาะคลัง
        </button>
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
                <i class="bi bi-tags me-1"></i> {{ isPharmacyWarehouse ? 'กลุ่มยา (Drug Groups)' : 'หมวดหมู่' }} ({{ displayCategories.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="itemtype-tab" data-bs-toggle="tab" data-bs-target="#itemtype" type="button" role="tab" @click="activeTab='itemtype'">
                <i class="bi bi-collection me-1"></i> {{ isPharmacyWarehouse ? 'ประเภทเวชภัณฑ์' : (isMedicalWarehouse ? 'ประเภทเวชภัณฑ์' : 'ประเภทพัสดุ') }} ({{ displayItemTypes.length }})
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
                <i class="bi bi-box-seam me-1"></i> รายการพัสดุ ({{ displayItems.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="vendor-tab" data-bs-toggle="tab" data-bs-target="#vendor" type="button" role="tab" @click="activeTab='vendor'">
                <i class="bi bi-shop me-1"></i> บริษัท / ผู้จำหน่าย ({{ filteredVendors.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation" v-if="isPharmacyWarehouse">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="dosageform-tab" data-bs-toggle="tab" data-bs-target="#dosageform" type="button" role="tab" @click="activeTab='dosageform'">
                <i class="bi bi-capsule me-1"></i> รูปแบบยา ({{ dosageForms.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation" v-if="isPharmacyWarehouse">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="edlist-tab" data-bs-toggle="tab" data-bs-target="#edlist" type="button" role="tab" @click="activeTab='edlist'">
                <i class="bi bi-journal-medical me-1"></i> บัญชียาหลัก ({{ edLists.length }})
              </button>
            </li>
            <li class="nav-item" role="presentation" v-if="isPharmacyWarehouse">
              <button class="nav-link fw-bold border-0 border-bottom border-3" id="productcat-tab" data-bs-toggle="tab" data-bs-target="#productcat" type="button" role="tab" @click="activeTab='productcat'">
                <i class="bi bi-patch-check me-1"></i> หมวดหมู่ อย. ({{ productCategories.length }})
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body p-4 p-md-5">
          <div class="tab-content">
            
            <!-- Warehouses Tab -->
            <div class="tab-pane fade show active" id="wh" role="tabpanel">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h5 class="fw-bold mb-1">รายชื่อคลังวัสดุทั้งหมด</h5>
                  <p class="text-muted small mb-0">เฉพาะผู้ดูแลระบบ (Admin) สามารถเพิ่มคลังใหม่ได้ และผู้ดูแลคลัง (Manager) สามารถตั้งค่าคลังของตนเองได้</p>
                </div>
                <button 
                  v-if="canCreateWarehouse" 
                  class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" 
                  @click="openWarehouseModal()"
                >
                  <i class="bi bi-plus-lg me-1"></i> เพิ่มคลังใหม่
                </button>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>รหัสคลัง</th>
                      <th>ชื่อคลัง</th>
                      <th>สถานะ</th>
                      <th>ระดับสิทธิ์ของคุณ</th>
                      <th>จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="warehouses.length === 0">
                      <td colspan="5" class="text-center text-muted py-4">ไม่พบคลังวัสดุที่คุณมีสิทธิ์เข้าถึง</td>
                    </tr>
                    <tr v-for="wh in warehouses" :key="wh.id">
                      <td><span class="badge bg-secondary font-monospace">{{ wh.code }}</span></td>
                      <td class="fw-bold text-dark">{{ wh.name }}</td>
                      <td>
                        <span class="badge" :class="Number(wh.active) ? 'bg-success' : 'bg-danger'">
                          {{ Number(wh.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td>
                        <span v-if="isAdminUser || wh.access_level === 'ADMIN'" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                          <i class="bi bi-shield-check me-1"></i> ผู้ดูแลระบบ
                        </span>
                        <span v-else-if="wh.access_level === 'MANAGER'" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                          <i class="bi bi-person-badge-fill me-1"></i> ผู้ดูแลคลัง
                        </span>
                        <span v-else class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1">
                          <i class="bi bi-person-fill me-1"></i> เจ้าหน้าที่
                        </span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-1">
                          <button 
                            class="btn btn-sm btn-outline-primary" 
                            @click="openWarehouseModal(wh)" 
                            :disabled="!canManageWarehouse(wh)"
                            :title="canManageWarehouse(wh) ? 'แก้ไขคลัง' : 'เฉพาะผู้ดูแลคลัง/Admin'"
                          >
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button 
                            class="btn btn-sm btn-outline-info" 
                            @click="openAccessModal(wh)" 
                            :disabled="!canManageWarehouse(wh)"
                            :title="canManageWarehouse(wh) ? 'จัดการสิทธิ์เข้าถึง' : 'เฉพาะผู้ดูแลคลัง/Admin'"
                          >
                            <i class="bi bi-person-lines-fill me-1"></i> สิทธิ์เข้าถึง
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade" id="cat" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <div>
                  <h5 class="fw-bold mb-1">
                    <i class="bi bi-tags me-2 text-primary"></i>{{ isPharmacyWarehouse ? 'ข้อมูลกลุ่มยา (Drug Groups Master)' : 'หมวดหมู่วัสดุ (Categories)' }}
                  </h5>
                  <small class="text-muted" v-if="activeWarehouse">
                    {{ isPharmacyWarehouse ? 'จัดการรายชื่อกลุ่มยาในคลังยา สำหรับจัดหมวดหมู่และเลือกในทะเบียนยา:' : 'แสดงเฉพาะหมวดหมู่ของคลัง:' }}
                    <strong class="text-primary">{{ activeWarehouse.name }}</strong>
                  </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button 
                    v-if="isPharmacyWarehouse" 
                    class="btn btn-outline-success rounded-pill px-3 shadow-xs fw-medium"
                    @click="seedDrugGroups" 
                    :disabled="isSeedingDrugGroups"
                    title="นำเข้า 24 กลุ่มยามาตรฐานของโรงพยาบาล"
                  >
                    <span v-if="isSeedingDrugGroups" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-magic me-1"></i> นำเข้า 24 กลุ่มยามาตรฐาน
                  </button>
                  <button class="btn btn-primary rounded-pill shadow-sm px-4" @click="openCategoryModal()">
                    <i class="bi bi-plus-lg me-1"></i> {{ isPharmacyWarehouse ? 'เพิ่มกลุ่มยาใหม่' : 'เพิ่มหมวดหมู่' }}
                  </button>
                </div>
              </div>

              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 70px;" class="text-center ps-3">ลำดับ</th>
                      <th style="width: 180px;">คลังวัสดุ</th>
                      <th style="width: 130px;">{{ isPharmacyWarehouse ? 'รหัสกลุ่มยา' : 'รหัสหมวด' }}</th>
                      <th>{{ isPharmacyWarehouse ? 'ชื่อกลุ่มยา' : 'ชื่อหมวดหมู่' }}</th>
                      <th style="width: 180px;">กลุ่ม GPSC</th>
                      <th style="width: 120px;" class="text-center">สถานะ</th>
                      <th style="width: 120px;" class="text-center">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="displayCategories.length === 0">
                      <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-tags fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ยังไม่มีข้อมูล{{ isPharmacyWarehouse ? 'กลุ่มยา' : 'หมวดหมู่' }}{{ activeWarehouse ? ' ในคลังนี้' : '' }}</span>
                        <div class="mt-2" v-if="isPharmacyWarehouse">
                          <button class="btn btn-sm btn-outline-success rounded-pill px-3 me-2" @click="seedDrugGroups">
                            <i class="bi bi-magic me-1"></i> นำเข้า 24 กลุ่มยามาตรฐาน
                          </button>
                          <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openCategoryModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มกลุ่มยาแรก
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="(c, idx) in displayCategories" :key="c.id">
                      <td class="text-center text-muted small ps-3">{{ idx + 1 }}</td>
                      <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                          <i class="bi bi-building me-1"></i>{{ c.warehouse_name }}
                        </span>
                      </td>
                      <td><code class="fw-bold">{{ c.code }}</code></td>
                      <td class="fw-semibold text-dark">{{ c.name }}</td>
                      <td class="small text-muted">
                        <span v-if="c.gpsc_code || c.gpsc_name">{{ c.gpsc_code }} {{ c.gpsc_name }}</span>
                        <span v-else class="text-muted opacity-50">-</span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(c.active) ? 'bg-success' : 'bg-danger'">
                          {{ Number(c.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openCategoryModal(c)" :title="isPharmacyWarehouse ? 'แก้ไขกลุ่มยา' : 'แก้ไขหมวดหมู่'"><i class="bi bi-pencil"></i></button>
                          <button class="btn btn-outline-danger" @click="deleteCategory(c)" :title="isPharmacyWarehouse ? 'ลบกลุ่มยา' : 'ลบหมวดหมู่'"><i class="bi bi-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Item Types Tab (ประเภทเวชภัณฑ์) -->
            <div class="tab-pane fade" id="itemtype" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1">
                    <i class="bi bi-collection me-2 text-primary"></i>{{ isPharmacyWarehouse ? 'ประเภทเวชภัณฑ์ / พัสดุ (Medical Supplies & Item Types)' : 'ประเภทพัสดุ (Item Types)' }}
                  </h5>
                  <p class="text-muted small mb-0">
                    {{ isPharmacyWarehouse ? 'กำหนดประเภทเวชภัณฑ์ รหัสบัญชี และกลุ่มรายงานกระทรวงสาธารณสุข (เช่น ยาในบัญชี E, ยานอกบัญชี N, ยาสมุนไพร ฯลฯ)' : 'กำหนดประเภทพัสดุและรหัสบัญชีสำหรับคลังนี้' }}
                  </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openItemTypeModal()">
                    <i class="bi bi-plus-lg me-1"></i> {{ isPharmacyWarehouse ? 'เพิ่มประเภทเวชภัณฑ์' : 'เพิ่มประเภทพัสดุ' }}
                  </button>
                </div>
              </div>

              <!-- Search Bar & Status Filter -->
              <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-5">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="ค้นหารหัส, ชื่อประเภทเวชภัณฑ์, รหัสบัญชี..." 
                      v-model="itemTypeSearchQuery"
                    />
                    <button class="btn btn-outline-secondary" v-if="itemTypeSearchQuery" @click="itemTypeSearchQuery = ''">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-3 col-lg-3">
                  <select class="form-select" v-model="itemTypeFilterStatus">
                    <option value="">ทั้งหมด (All Status)</option>
                    <option value="1">เปิดใช้งาน (Active)</option>
                    <option value="0">ปิดใช้งาน (Inactive)</option>
                  </select>
                </div>
                <div class="col-12 col-lg-4 d-flex align-items-center justify-content-lg-end">
                  <span class="badge bg-light text-secondary border px-3 py-2">
                    รวม {{ filteredItemTypes.length }} ประเภท
                  </span>
                </div>
              </div>

              <!-- Item Types Table -->
              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 70px;" class="text-center ps-3">รหัส</th>
                      <th>ประเภทเวชภัณฑ์</th>
                      <th style="width: 110px;" class="text-center">กลุ่มรายงาน</th>
                      <th style="width: 140px;" class="text-center">กลุ่มรายงานกระทรวง</th>
                      <th style="width: 130px;" class="text-center">รหัสบัญชีเจ้าหนี้</th>
                      <th style="width: 130px;" class="text-center">รหัสบัญชีเดบิต</th>
                      <th style="width: 130px;" class="text-center">รหัสบัญชีเครดิต</th>
                      <th style="width: 110px;" class="text-center">รายการยา/พัสดุ</th>
                      <th style="width: 100px;" class="text-center">สถานะ</th>
                      <th style="width: 100px;" class="text-center">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loadingItemTypes">
                      <td colspan="10" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังโหลดข้อมูลประเภทเวชภัณฑ์...
                      </td>
                    </tr>
                    <tr v-else-if="filteredItemTypes.length === 0">
                      <td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-collection fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ไม่พบข้อมูลประเภทเวชภัณฑ์</span>
                        <div class="mt-2">
                          <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openItemTypeModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มประเภทแรก
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="it in filteredItemTypes" :key="it.id">
                      <td class="text-center ps-3">
                        <span class="badge bg-light text-dark border font-monospace px-2 py-1">{{ it.code }}</span>
                      </td>
                      <td>
                        <div class="fw-bold text-dark">{{ it.name }}</div>
                      </td>
                      <td class="text-center">
                        <span v-if="it.report_group" class="badge bg-secondary-subtle text-secondary border px-2 py-1 font-monospace">
                          {{ it.report_group }}
                        </span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span v-if="it.moph_report_group === 'E'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace fw-bold">
                          E (ในบัญชี)
                        </span>
                        <span v-else-if="it.moph_report_group === 'N'" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 font-monospace fw-bold">
                          N (นอกบัญชี)
                        </span>
                        <span v-else-if="it.moph_report_group" class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 font-monospace fw-bold">
                          {{ it.moph_report_group }}
                        </span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span v-if="it.ap_account_code" class="font-monospace small text-dark">{{ it.ap_account_code }}</span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span v-if="it.debit_account_code" class="font-monospace small text-dark">{{ it.debit_account_code }}</span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span v-if="it.credit_account_code" class="font-monospace small text-dark">{{ it.credit_account_code }}</span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-center">
                        <span class="badge rounded-pill" :class="Number(it.item_count) > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border'">
                          <i class="bi bi-box-seam me-1"></i>{{ it.item_count || 0 }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(it.active) ? 'bg-success' : 'bg-secondary'">
                          {{ Number(it.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openItemTypeModal(it)" title="แก้ไขประเภทเวชภัณฑ์">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-outline-danger" @click="deleteItemType(it)" title="ลบประเภทเวชภัณฑ์">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="tab-pane fade" id="unit" role="tabpanel">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-rulers me-2 text-primary"></i>รายชื่อหน่วยนับพัสดุและเวชภัณฑ์ (Unit Master)</h5>
                  <p class="text-muted small mb-0">จัดการหน่วยนับพื้นฐาน สำหรับกำหนดในรายการวัสดุ การสั่งซื้อ การรับเข้า และการเบิกจ่ายพัสดุ</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
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
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <div>
                  <h5 class="fw-bold mb-1">รายการวัสดุ (Item Master)</h5>
                  <small class="text-muted" v-if="activeWarehouse">แสดงเฉพาะรายการพัสดุของคลัง: <strong class="text-primary">{{ activeWarehouse.name }}</strong></small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <button 
                    v-if="isPharmacyWarehouse"
                    type="button"
                    class="btn btn-outline-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1 fw-semibold"
                    @click="openTmtModal"
                    title="สืบค้นและนำเข้ายาจากคลังยามาตรฐาน TMT (35,419 รายการ)"
                  >
                    <i class="bi bi-capsule fs-6"></i>
                    <span>คลังยามาตรฐาน TMT</span>
                    <span class="badge bg-primary text-white rounded-pill ms-1" style="font-size: 0.72rem;">35,419</span>
                  </button>
                  <button 
                    type="button"
                    class="btn btn-outline-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-1"
                    @click="exportItemsToExcel" 
                    :disabled="filteredDisplayItems.length === 0"
                    title="ส่งออกรายการวัสดุพัสดุทั้งหมดเป็นไฟล์ Excel"
                  >
                    <i class="bi bi-file-earmark-excel fs-6"></i>
                    <span>ส่งออก Excel</span>
                  </button>
                  <button 
                    type="button"
                    class="btn btn-success rounded-pill px-3 shadow-sm text-white d-flex align-items-center gap-1"
                    @click="openItemImportModal"
                    title="นำเข้ารายการวัสดุ/ยาจากไฟล์ Excel"
                  >
                    <i class="bi bi-file-earmark-arrow-up fs-6"></i>
                    <span>นำเข้าด้วย Excel</span>
                  </button>
                  <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" @click="openItemModal()">
                    <i class="bi bi-plus-lg"></i>
                    <span>ลงทะเบียนวัสดุใหม่</span>
                  </button>
                </div>
              </div>

              <!-- Search and Filter Bar -->
              <div class="row g-2 mb-4 align-items-center">
                <div class="col-md-5 col-lg-3">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="ค้นหาชื่อวัสดุ, รหัส, GPU, 24 หลัก..." 
                      v-model="itemSearchQuery"
                    />
                    <button class="btn btn-outline-secondary" v-if="itemSearchQuery" @click="itemSearchQuery = ''">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-4 col-lg-2">
                  <select class="form-select" v-model="itemFilterCategory">
                    <option value="">-- {{ isPharmacyWarehouse ? 'ทุกกลุ่มยา' : 'ทุกหมวดหมู่' }} --</option>
                    <option v-for="c in displayCategories" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3 col-lg-2">
                  <select class="form-select" v-model="itemFilterType">
                    <option value="">-- ทุกประเภท --</option>
                    <option v-for="t in displayItemTypes" :key="t.id" :value="t.id">
                      {{ t.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3 col-lg-1" v-if="isPharmacyWarehouse">
                  <select class="form-select" v-model="itemFilterEdList" title="กรองตามบัญชียาหลัก">
                    <option value="">บัญชี</option>
                    <option v-for="ed in edLists" :key="ed.id" :value="ed.id">{{ ed.name }}</option>
                  </select>
                </div>
                <div class="col-md-3 col-lg-2" v-if="isPharmacyWarehouse">
                  <select class="form-select" v-model="itemFilterDosageForm" title="กรองตามรูปแบบยา">
                    <option value="">-- ทุกรูปแบบยา --</option>
                    <option v-for="df in dosageForms" :key="df.id" :value="df.id">[{{ df.code }}] {{ df.name }}</option>
                  </select>
                </div>
                <div class="col-md-3 col-lg-2" v-if="isPharmacyWarehouse">
                  <select class="form-select" v-model="itemFilterSendMoph" title="กรองตามสถานะส่งข้อมูลกระทรวง">
                    <option value="">-- ส่งกระทรวง (ทั้งหมด) --</option>
                    <option value="1">ส่งข้อมูลให้กระทรวง</option>
                    <option value="0">ไม่ส่งกระทรวง</option>
                    <option value="null">ยังไม่ระบุ</option>
                  </select>
                </div>
                <div class="col-12 d-flex align-items-center justify-content-lg-end" :class="isPharmacyWarehouse ? 'mt-2' : 'col-lg-auto ms-lg-auto'">
                  <span class="badge bg-light text-secondary border px-3 py-2">
                    รวม {{ filteredDisplayItems.length }} รายการ
                    <span v-if="activeWarehouse" class="text-primary fw-bold ms-1">({{ activeWarehouse.name }})</span>
                  </span>
                </div>
              </div>

              <div class="table-responsive border rounded-3 bg-white" style="max-height: 620px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small sticky-top" style="z-index: 2;">
                    <tr>
                      <th style="width: 130px;" class="ps-3">รหัสวัสดุ</th>
                      <th>ชื่อวัสดุ / ยา</th>
                      <th>ประเภทเวชภัณฑ์</th>
                      <th style="width: 140px;">คลัง</th>
                      <th style="width: 100px;">หน่วยนับ</th>
                      <th class="text-center" style="width: 80px;">Lot</th>
                      <th class="text-center" style="width: 80px;">Expiry</th>
                      <th class="text-center" style="width: 100px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="filteredDisplayItems.length === 0">
                      <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ยังไม่มีข้อมูลรายการวัสดุ/ยา{{ activeWarehouse ? ' ใน' + activeWarehouse.name : '' }}</span>
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                          <button 
                            v-if="isPharmacyWarehouse" 
                            type="button" 
                            class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold" 
                            @click="openTmtModal"
                          >
                            <i class="bi bi-capsule me-1"></i> เลือกยาจากคลัง TMT (35,419 รายการ)
                          </button>
                          <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3 shadow-sm" @click="openItemImportModal">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> นำเข้าด้วย Excel
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openItemModal()">
                            <i class="bi bi-plus-lg me-1"></i> ลงทะเบียนวัสดุชิ้นแรก
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-for="i in filteredDisplayItems" :key="i.id">
                      <td class="ps-3">
                        <code class="fw-bold text-dark">{{ i.item_code }}</code>
                      </td>
                      <td>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <span class="fw-bold text-dark">{{ i.item_name }}</span>
                          <span v-if="i.ed_sub_list" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.7rem;">บัญชี {{ i.ed_sub_list }}</span>
                          <span v-if="i.dosage_form" class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 0.7rem;">{{ i.dosage_form }}</span>
                          <span v-if="Number(i.is_gpo) === 1" class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">GPO</span>
                          <span v-if="Number(i.is_original) === 1" class="badge bg-dark-subtle text-dark border border-dark-subtle" style="font-size: 0.7rem;">Original</span>
                          <span v-if="Number(i.is_free_dispense) === 1" class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">จ่ายฟรี</span>
                          <span v-if="i.send_moph !== null && i.send_moph !== undefined && i.send_moph !== '' && Number(i.send_moph) === 1" class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;" title="ส่งข้อมูลให้กระทรวงสาธารณสุข"><i class="bi bi-send-check me-1"></i>ส่งกระทรวง</span>
                          <span v-else-if="i.send_moph !== null && i.send_moph !== undefined && i.send_moph !== '' && Number(i.send_moph) === 0" class="badge bg-light text-muted border" style="font-size: 0.7rem;">ไม่ส่งกระทรวง</span>
                        </div>
                        <div v-if="i.item_name_en && i.item_name_en !== i.item_name" class="small text-muted font-monospace">{{ i.item_name_en }}</div>
                        <div class="d-flex gap-3 flex-wrap mt-1">
                          <small v-if="i.category_name" class="text-secondary"><i class="bi bi-folder2 me-1"></i>{{ i.category_name }}</small>
                          <small v-if="i.tmt_gpu" class="text-primary font-monospace"><i class="bi bi-upc-scan me-1"></i>GPU: {{ i.tmt_gpu }}</small>
                          <small v-if="i.std_code_24 || i.old_code" class="text-muted font-monospace"><i class="bi bi-hash"></i>{{ i.std_code_24 || i.old_code }}</small>
                          <small v-if="i.std_unit_th || i.std_unit_en" class="text-secondary"><i class="bi bi-hospital me-1"></i>หน่วยกระทรวง: {{ i.std_unit_th || '-' }} <span v-if="i.std_unit_en" class="font-monospace">({{ i.std_unit_en }})</span></small>
                          <small v-if="Number(i.standard_price) > 0" class="text-success fw-semibold"><i class="bi bi-tag me-1"></i>{{ Number(i.standard_price).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }} ฿</small>
                        </div>
                      </td>
                      <td>
                        <span v-if="i.item_type_name" class="badge bg-light text-dark border">
                          <span v-if="i.item_type_moph" class="badge bg-primary me-1">{{ i.item_type_moph }}</span>
                          {{ i.item_type_name }}
                        </span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                          {{ i.warehouse_name }}
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-light text-dark border">{{ i.unit_name || '-' }}</span>
                      </td>
                      <td class="text-center"><i v-if="i.lot_control" class="bi bi-check-circle-fill text-success fs-6"></i><span v-else class="text-muted small">-</span></td>
                      <td class="text-center"><i v-if="i.expiry_control" class="bi bi-check-circle-fill text-success fs-6"></i><span v-else class="text-muted small">-</span></td>
                      <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" @click="openItemModal(i)" title="แก้ไขข้อมูลวัสดุ"><i class="bi bi-pencil"></i></button>
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
                  <!-- Excel Export -->
                  <button 
                    type="button"
                    class="btn btn-outline-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-1"
                    @click="exportVendorsToExcel" 
                    :disabled="filteredVendors.length === 0"
                    title="ส่งออกรายการบริษัททั้งหมดตามตัวกรองเป็นไฟล์ Excel"
                  >
                    <i class="bi bi-file-earmark-excel fs-6"></i>
                    <span>ส่งออก Excel</span>
                  </button>

                  <!-- Excel Import -->
                  <button 
                    type="button"
                    class="btn btn-success rounded-pill px-3 shadow-sm text-white d-flex align-items-center gap-1"
                    @click="openVendorImportModal"
                    title="นำเข้ารายชื่อบริษัทจากไฟล์ Excel"
                  >
                    <i class="bi bi-file-earmark-arrow-up fs-6"></i>
                    <span>นำเข้าด้วย Excel</span>
                  </button>

                  <!-- Add Manual -->
                  <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" @click="openVendorModal()">
                    <i class="bi bi-plus-lg"></i>
                    <span>เพิ่มบริษัทใหม่</span>
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
                <div class="col-md-4 col-lg-3" v-if="!activeWarehouse">
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
                <div class="col-md-4 col-lg-3" v-else>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-primary border-end-0"><i class="bi bi-building"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 fw-bold text-dark" :value="'[' + activeWarehouse.code + '] ' + activeWarehouse.name" readonly />
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
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                          <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3 shadow-sm" @click="openVendorImportModal">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> นำเข้าด้วย Excel
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openVendorModal()">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มบริษัทแรก
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

            <!-- Pharmacy: Dosage Forms Tab -->
            <div class="tab-pane fade" id="dosageform" role="tabpanel" v-if="isPharmacyWarehouse">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-capsule me-2 text-primary"></i>รูปแบบยา / ชนิดยา (Dosage Forms Master)</h5>
                  <p class="text-muted small mb-0">จัดการรายชื่อรูปแบบยามาตรฐาน เช่น ยาเม็ด (tab), ยาฉีด (inj), แคปซูล (cap), ยาน้ำ (syr) สำหรับใช้ระบุในทะเบียนยา</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openDosageFormModal()">
                  <i class="bi bi-plus-lg me-1"></i> เพิ่มรูปแบบยาใหม่
                </button>
              </div>

              <!-- Search Bar -->
              <div class="row g-3 mb-4">
                <div class="col-md-5 col-lg-4">
                  <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" placeholder="ค้นหารหัสย่อ, ชื่อรูปแบบยา..." v-model="dosageFormSearchQuery" />
                    <button class="btn btn-outline-secondary" v-if="dosageFormSearchQuery" @click="dosageFormSearchQuery = ''"><i class="bi bi-x-lg"></i></button>
                  </div>
                </div>
                <div class="col-md-7 col-lg-8 d-flex justify-content-md-end align-items-center">
                  <span class="badge bg-light text-secondary border px-3 py-2">รวม {{ filteredDosageForms.length }} รูปแบบยา</span>
                </div>
              </div>

              <div class="table-responsive border rounded-3 bg-white" style="max-height: 540px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small sticky-top">
                    <tr>
                      <th style="width: 120px;" class="ps-3">รหัสย่อ (Code)</th>
                      <th>ชื่อรูปแบบยา (ภาษาไทย)</th>
                      <th>ชื่อภาษาอังกฤษ (English)</th>
                      <th class="text-center" style="width: 140px;">รายการยาที่ใช้</th>
                      <th class="text-center" style="width: 100px;">สถานะ</th>
                      <th class="text-center" style="width: 100px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="filteredDosageForms.length === 0">
                      <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-capsule fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <span>ไม่พบข้อมูลรูปแบบยา</span>
                      </td>
                    </tr>
                    <tr v-for="df in filteredDosageForms" :key="df.id">
                      <td class="ps-3"><span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">{{ df.code }}</span></td>
                      <td class="fw-bold text-dark">{{ df.name }}</td>
                      <td class="text-muted small font-monospace">{{ df.name_en || '-' }}</td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark border"><i class="bi bi-capsule me-1 text-primary"></i>{{ df.item_count || 0 }} รายการ</span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(df.active) ? 'bg-success' : 'bg-secondary'">{{ Number(df.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openDosageFormModal(df)" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                          <button class="btn btn-outline-danger" @click="deleteDosageForm(df)" title="ลบ" :disabled="Number(df.item_count) > 0"><i class="bi bi-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Pharmacy: National Essential Drugs List (ED Sub-list) Tab -->
            <div class="tab-pane fade" id="edlist" role="tabpanel" v-if="isPharmacyWarehouse">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-journal-medical me-2 text-primary"></i>บัญชียาหลักแห่งชาติย่อย (National Essential Drugs - ED Lists)</h5>
                  <p class="text-muted small mb-0">จัดการบัญชียาหลักแห่งชาติ (ก, ข, ค, ง, จ) พร้อมคำอธิบายและเงื่อนไขการสั่งใช้ยาเพื่อการควบคุมการใช้ยาอย่างสมเหตุผล</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openEdListModal()">
                  <i class="bi bi-plus-lg me-1"></i> เพิ่มบัญชีย่อยใหม่
                </button>
              </div>

              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 100px;" class="ps-3 text-center">รหัสบัญชี</th>
                      <th style="width: 160px;">ชื่อบัญชียา</th>
                      <th>คำอธิบายและเงื่อนไขการสั่งใช้</th>
                      <th class="text-center" style="width: 100px;">ลำดับ</th>
                      <th class="text-center" style="width: 140px;">รายการยาที่ใช้</th>
                      <th class="text-center" style="width: 100px;">สถานะ</th>
                      <th class="text-center" style="width: 100px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="ed in edLists" :key="ed.id">
                      <td class="ps-3 text-center"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 fs-6 fw-bold">บัญชี {{ ed.code }}</span></td>
                      <td class="fw-bold text-dark">{{ ed.name }}</td>
                      <td class="small text-muted">{{ ed.description || '-' }}</td>
                      <td class="text-center font-monospace">{{ ed.sort_order }}</td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark border"><i class="bi bi-capsule me-1 text-primary"></i>{{ ed.item_count || 0 }} รายการ</span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(ed.active) ? 'bg-success' : 'bg-secondary'">{{ Number(ed.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openEdListModal(ed)" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                          <button class="btn btn-outline-danger" @click="deleteEdList(ed)" title="ลบ" :disabled="Number(ed.item_count) > 0"><i class="bi bi-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Pharmacy: FDA Product Categories Tab -->
            <div class="tab-pane fade" id="productcat" role="tabpanel" v-if="isPharmacyWarehouse">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                  <h5 class="fw-bold mb-1"><i class="bi bi-patch-check me-2 text-primary"></i>หมวดหมู่ผลิตภัณฑ์ตามทะเบียน อย. (FDA Product Categories)</h5>
                  <p class="text-muted small mb-0">จัดการหมวดหมู่มาตรฐาน อย. (รหัส 1: ยาแผนปัจจุบัน, 3: ยาสมุนไพร, 5: ยาอื่นๆ) สำหรับการออกรายงานส่งกระทรวงสาธารณสุข</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openProductCategoryModal()">
                  <i class="bi bi-plus-lg me-1"></i> เพิ่มหมวด อย. ใหม่
                </button>
              </div>

              <div class="table-responsive border rounded-3 bg-white">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-secondary small">
                    <tr>
                      <th style="width: 100px;" class="ps-3 text-center">รหัสหมวด</th>
                      <th>ชื่อหมวดหมู่ อย.</th>
                      <th>คำอธิบาย</th>
                      <th class="text-center" style="width: 140px;">รายการยาที่ใช้</th>
                      <th class="text-center" style="width: 100px;">สถานะ</th>
                      <th class="text-center" style="width: 100px;">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="pc in productCategories" :key="pc.id">
                      <td class="ps-3 text-center"><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-3 py-1 font-monospace fs-6">[{{ pc.code }}]</span></td>
                      <td class="fw-bold text-dark">{{ pc.name }}</td>
                      <td class="small text-muted">{{ pc.description || '-' }}</td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark border"><i class="bi bi-capsule me-1 text-primary"></i>{{ pc.item_count || 0 }} รายการ</span>
                      </td>
                      <td class="text-center">
                        <span class="badge" :class="Number(pc.active) ? 'bg-success' : 'bg-secondary'">{{ Number(pc.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                      </td>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" @click="openProductCategoryModal(pc)" title="แก้ไข"><i class="bi bi-pencil"></i></button>
                          <button class="btn btn-outline-danger" @click="deleteProductCategory(pc)" title="ลบ" :disabled="Number(pc.item_count) > 0"><i class="bi bi-trash"></i></button>
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
              <i class="bi bi-tags me-2"></i>{{ isEditCategory ? (isPharmacyWarehouse ? 'แก้ไขข้อมูลกลุ่มยา' : 'แก้ไขหมวดหมู่วัสดุ') : (isPharmacyWarehouse ? 'เพิ่มกลุ่มยาใหม่' : 'เพิ่มหมวดหมู่วัสดุ') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveCategory">
              <div class="mb-3">
                <label class="form-label">คลังวัสดุ <span class="text-danger">*</span></label>
                <div v-if="activeWarehouse" class="p-2 px-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                  <span class="fw-bold text-primary"><i class="bi bi-building me-2"></i>[{{ activeWarehouse.code }}] {{ activeWarehouse.name }}</span>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">คลังปัจจุบัน</span>
                </div>
                <select v-else class="form-select" v-model="categoryForm.warehouse_id" required>
                  <option value="">-- เลือกคลัง --</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">{{ isPharmacyWarehouse ? 'รหัสกลุ่มยา' : 'รหัสหมวดหมู่' }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="categoryForm.code" required :placeholder="isPharmacyWarehouse ? 'เช่น 01' : 'เช่น C01'" />
              </div>
              <div class="mb-3">
                <label class="form-label">{{ isPharmacyWarehouse ? 'ชื่อกลุ่มยา' : 'ชื่อหมวดหมู่' }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="categoryForm.name" required :placeholder="isPharmacyWarehouse ? 'เช่น Antidotes' : 'เช่น ยาเม็ด หรือ อุปกรณ์คอมพิวเตอร์'" />
              </div>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="form-label small text-secondary">รหัสกลุ่ม GPSC (ถ้ามี)</label>
                  <input type="text" class="form-control" v-model="categoryForm.gpsc_code" placeholder="เช่น รหัส GPSC" />
                </div>
                <div class="col-6">
                  <label class="form-label small text-secondary">ชื่อกลุ่ม GPSC (ถ้ามี)</label>
                  <input type="text" class="form-control" v-model="categoryForm.gpsc_name" placeholder="ชื่อกลุ่มตาม GPSC" />
                </div>
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

    <!-- Item Type Modal -->
    <div class="modal fade" id="itemTypeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-collection me-2"></i>{{ isEditItemType ? 'แก้ไขประเภทเวชภัณฑ์' : 'เพิ่มประเภทเวชภัณฑ์ใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveItemType">
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label">รหัสประเภท <span class="text-danger">*</span></label>
                  <input type="text" class="form-control font-monospace fw-bold" v-model="itemTypeForm.code" required placeholder="เช่น 1, 10, 2" />
                </div>
                <div class="col-md-8">
                  <label class="form-label">ชื่อประเภทเวชภัณฑ์ <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="itemTypeForm.name" required placeholder="เช่น ยาในบัญชียาหลักแห่งชาติ" />
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label">กลุ่มรายงาน</label>
                  <input type="text" class="form-control font-monospace" v-model="itemTypeForm.report_group" placeholder="เช่น 01, 02" />
                  <div class="form-text small text-muted">รหัสกลุ่มสำหรับการออกรายงานสรุปเวชภัณฑ์</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">กลุ่มรายงานกระทรวง (MOPH)</label>
                  <input type="text" class="form-control font-monospace" v-model="itemTypeForm.moph_report_group" placeholder="เช่น E (ในบัญชี), N (นอกบัญชี)" />
                  <div class="form-text small text-muted">E = ในบัญชียาหลัก, N = นอกบัญชียาหลัก</div>
                </div>
              </div>

              <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-journal-code me-2"></i>การผูกรหัสบัญชี (Account Codes)</h6>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">รหัสบัญชีเจ้าหนี้</label>
                    <input type="text" class="form-control font-monospace" v-model="itemTypeForm.ap_account_code" placeholder="เช่น รหัสบัญชี" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">รหัสบัญชีเดบิต</label>
                    <input type="text" class="form-control font-monospace" v-model="itemTypeForm.debit_account_code" placeholder="เช่น รหัสบัญชี" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">รหัสบัญชีเครดิต</label>
                    <input type="text" class="form-control font-monospace" v-model="itemTypeForm.credit_account_code" placeholder="เช่น รหัสบัญชี" />
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="itActive" v-model="itemTypeForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label" for="itActive">เปิดใช้งาน</label>
                </div>
              </div>

              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingItemType">
                  <span v-if="isSavingItemType" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
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
              <!-- TMT Search Box for Pharmacy Warehouse -->
              <div v-if="isPharmacyWarehouse && !isEditItem" class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-3 p-3 mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-capsule me-1"></i> TMT Drug Catalog</span>
                    <span class="fw-bold text-primary small">ค้นหายาจากคลังยามาตรฐานแห่งชาติ (35,419 รายการ)</span>
                  </div>
                  <div class="d-flex align-items-center gap-2" v-if="tmtSelectedDrug">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      <i class="bi bi-check-circle-fill me-1"></i> ผูกรหัส TMT: {{ tmtSelectedDrug.tpucode }}
                    </span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" @click="clearTmtSelection">ล้างการเลือก</button>
                  </div>
                </div>
                <div class="position-relative">
                  <div class="input-group shadow-xs">
                    <span class="input-group-text bg-white border-end-0 text-primary">
                      <i class="bi bi-search"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 ps-0" 
                      placeholder="พิมพ์ชื่อการค้า (เช่น Beramol, Sifrol), ตัวยาสำคัญ (เช่น Paracetamol), หรือรหัส TPU..."
                      v-model="tmtQuery"
                      @input="onTmtInput"
                      @focus="tmtShowDropdown = tmtResults.length > 0"
                    />
                    <button v-if="tmtQuery" type="button" class="btn btn-outline-secondary border-start-0" @click="clearTmtSelection">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>

                  <!-- Dropdown Suggestions -->
                  <div 
                    v-if="tmtShowDropdown && (tmtResults.length > 0 || isSearchingTmt)" 
                    class="position-absolute w-100 bg-white border rounded-3 shadow-lg mt-1 p-2" 
                    style="z-index: 1060; max-height: 320px; overflow-y: auto;"
                  >
                    <div v-if="isSearchingTmt" class="text-center py-3 text-muted">
                      <div class="spinner-border spinner-border-sm text-primary me-2"></div> กำลังค้นหาจากฐานข้อมูล 35,419 รายการ...
                    </div>
                    <div v-else-if="tmtResults.length === 0" class="text-center py-3 text-muted small">
                      ไม่พบรายการยาที่ตรงกับ "{{ tmtQuery }}"
                    </div>
                    <div v-else>
                      <div 
                        v-for="d in tmtResults" 
                        :key="d.id"
                        class="p-2 rounded-2 border-bottom cursor-pointer hover-bg-light"
                        style="cursor: pointer;"
                        @click="selectTmtDrug(d)"
                      >
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="fw-bold text-dark">{{ d.trade_name || d.fsn }}</span>
                          <span class="badge bg-secondary font-monospace" style="font-size: 0.75rem;">TPU: {{ d.tpucode }}</span>
                        </div>
                        <div class="small text-muted mt-1">
                          <span class="text-primary fw-semibold">{{ d.active_ingredient }}</span>
                          <span v-if="d.strength" class="badge bg-light text-dark border ms-1">{{ d.strength }}</span>
                          <span v-if="d.dosage_form" class="ms-1 text-secondary">| {{ d.dosage_form }}</span>
                          <span v-if="d.disp_unit_th" class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-1">หน่วย: {{ d.disp_unit_th }}</span>
                        </div>
                        <div v-if="d.manufacturer" class="small text-secondary mt-1" style="font-size: 0.75rem;">
                          <i class="bi bi-building me-1"></i> ผู้ผลิต: {{ d.manufacturer }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-text small text-muted mt-1">
                  <i class="bi bi-magic me-1 text-primary"></i> เลือกรหัสยาเพื่อดึงชื่อยา, ชื่อสามัญ, ความแรง, ผู้ผลิต และหน่วยนับ มาใส่ฟอร์มให้อัตโนมัติ
                </div>
              </div>

              <!-- Section 1: ข้อมูลพื้นฐาน -->
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">1. ข้อมูลพื้นฐานและการจัดหมวดหมู่</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">รหัสวัสดุ (Item Code) <span class="text-danger">*</span></label>
                    <button 
                      v-if="!isEditItem"
                      type="button" 
                      class="btn btn-link p-0 text-primary text-decoration-none small" 
                      style="font-size: 0.75rem;" 
                      @click="fetchNextItemCode" 
                      title="คำนวณรหัสถัดไปอัตโนมัติ"
                    >
                      <i class="bi bi-magic me-1"></i>รันเลขอัตโนมัติ
                    </button>
                  </div>
                  <div class="input-group">
                    <input 
                      type="text" 
                      class="form-control font-monospace fw-bold text-primary" 
                      v-model="itemForm.item_code" 
                      required 
                      placeholder="เช่น C002" 
                    />
                    <button 
                      v-if="!isEditItem"
                      type="button" 
                      class="btn btn-outline-secondary" 
                      @click="fetchNextItemCode" 
                      title="รันเลขต่อจากเดิมอัตโนมัติ"
                      :disabled="isLoadingNextCode"
                    >
                      <span v-if="isLoadingNextCode" class="spinner-border spinner-border-sm"></span>
                      <i v-else class="bi bi-arrow-repeat"></i>
                    </button>
                  </div>
                  <div class="form-text small text-muted" v-if="!isEditItem">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>รันเลขต่อจากรหัสเดิมในคลังนี้อัตโนมัติ
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="form-label">รหัสเดิม (ถ้ามี)</label>
                  <input type="text" class="form-control" v-model="itemForm.old_code" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ isPharmacyWarehouse ? 'ชื่อยา / เวชภัณฑ์' : 'ชื่อวัสดุ' }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="itemForm.item_name" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ isPharmacyWarehouse ? 'ชื่อภาษาอังกฤษ / ชื่อสามัญ (Generic Name)' : 'ชื่อภาษาอังกฤษ / ชื่อสามัญ' }}</label>
                  <input type="text" class="form-control" v-model="itemForm.item_name_en" :placeholder="isPharmacyWarehouse ? 'เช่น Paracetamol 500 mg tablet' : 'ชื่อภาษาอังกฤษ หรือชื่อสามัญ'" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">คลังวัสดุ <span class="text-danger">*</span></label>
                  <div v-if="activeWarehouse" class="p-2 px-3 bg-light rounded-3 border">
                    <span class="fw-bold text-primary small"><i class="bi bi-building me-1"></i>[{{ activeWarehouse.code }}] {{ activeWarehouse.name }}</span>
                  </div>
                  <select v-else class="form-select" v-model="itemForm.warehouse_id" required @change="onItemWarehouseChange">
                    <option value="">-- เลือกคลัง --</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">{{ isPharmacyWarehouse ? 'กลุ่มยา' : 'หมวดหมู่' }}</label>
                  <select class="form-select" v-model="itemForm.category_id" @change="onItemCategoryChange">
                    <option value="">{{ isPharmacyWarehouse ? '-- เลือกกลุ่มยา --' : '-- เลือกหมวดหมู่ --' }}</option>
                    <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.code ? c.code + ' : ' : '' }}{{ c.name }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ isPharmacyWarehouse ? 'ประเภทเวชภัณฑ์' : 'ประเภทพัสดุ' }}</label>
                  <select class="form-select" v-model="itemForm.item_type_id">
                    <option value="">-- ไม่ระบุประเภท --</option>
                    <option v-for="t in displayItemTypes" :key="t.id" :value="t.id">
                      {{ t.code ? t.code + ' : ' : '' }}{{ t.name }}{{ t.moph_report_group ? ' [กลุ่มกระทรวง: ' + t.moph_report_group + ']' : '' }}
                    </option>
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

              <!-- Pharmacy Master Fields: เฉพาะคลังยา -->
              <div v-if="isPharmacyWarehouse" class="card border border-primary-subtle bg-primary bg-opacity-10 rounded-3 p-3 mb-4 shadow-2xs">
                <h6 class="fw-bold mb-3 text-primary d-flex align-items-center">
                  <i class="bi bi-capsule me-2"></i> ข้อมูลเฉพาะทางเภสัชกรรมและบัญชียา
                </h6>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">รูปแบบยา / ชนิดยา (Dosage Form)</label>
                    <select class="form-select form-select-sm" v-model="itemForm.dosage_form_id">
                      <option :value="null">-- เลือกรูปแบบยา --</option>
                      <option v-for="df in dosageForms" :key="df.id" :value="df.id">
                        [{{ df.code }}] {{ df.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">บัญชียาหลักแห่งชาติ (ED List)</label>
                    <select class="form-select form-select-sm" v-model="itemForm.ed_list_id">
                      <option :value="null">-- ไม่ระบุ / ยานอกบัญชี --</option>
                      <option v-for="ed in edLists" :key="ed.id" :value="ed.id">
                        [{{ ed.code }}] {{ ed.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">หมวดหมู่ตามทะเบียน อย. (Product Category)</label>
                    <select class="form-select form-select-sm" v-model="itemForm.product_cat_id">
                      <option :value="null">-- เลือกหมวด อย. --</option>
                      <option v-for="pc in productCategories" :key="pc.id" :value="pc.id">
                        [{{ pc.code }}] {{ pc.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">รหัสมาตรฐาน 24 หลัก (สนย./สปสช.)</label>
                    <input type="text" class="form-control form-control-sm font-monospace" v-model="itemForm.std_code_24" placeholder="เช่น 1002220000042031203" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">รหัส TMT GPU</label>
                    <input type="text" class="form-control form-control-sm font-monospace" v-model="itemForm.tmt_gpu" placeholder="เช่น 226551" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label small fw-semibold">ความแรงยา (Strength)</label>
                    <input type="text" class="form-control form-control-sm" v-model="itemForm.strength" placeholder="เช่น 500 mg" />
                  </div>
                  <div class="col-md-2">
                    <label class="form-label small fw-semibold">ขนาดบรรจุ</label>
                    <input type="number" min="1" class="form-control form-control-sm" v-model="itemForm.packing_size" placeholder="1" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold text-primary">
                      <i class="bi bi-send-check me-1"></i>การส่งข้อมูลให้กระทรวง (DMSIC / สนย.)
                    </label>
                    <select class="form-select form-select-sm fw-semibold" v-model="itemForm.send_moph">
                      <option :value="null">-- ยังไม่ระบุ --</option>
                      <option :value="1">ส่งข้อมูลให้กระทรวง</option>
                      <option :value="0">ไม่ส่ง</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">หน่วยมาตรฐานกระทรวง (ไทย)</label>
                    <input type="text" class="form-control form-control-sm" v-model="itemForm.std_unit_th" placeholder="เช่น เม็ด, ไวอัล, ขวด" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small fw-semibold">หน่วยมาตรฐานกระทรวง (EN)</label>
                    <input type="text" class="form-control form-control-sm font-monospace" v-model="itemForm.std_unit_en" placeholder="เช่น TABLET, vial, bottle" />
                  </div>
                  <div class="col-12 d-flex flex-wrap align-items-center gap-4 mt-2 pt-2 border-top border-primary-subtle">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="gpoCtrl" v-model="itemForm.is_gpo" :true-value="1" :false-value="0">
                      <label class="form-check-label small fw-semibold" for="gpoCtrl">ยาองค์การเภสัชกรรม (GPO)</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="origCtrl" v-model="itemForm.is_original" :true-value="1" :false-value="0">
                      <label class="form-check-label small fw-semibold" for="origCtrl">ยาต้นแบบ (Original Drug)</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="freeDispenseCtrl" v-model="itemForm.is_free_dispense" :true-value="1" :false-value="0">
                      <label class="form-check-label small fw-semibold text-danger" for="freeDispenseCtrl">ยาจ่ายฟรี</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="sendDosCtrl" v-model="itemForm.send_dos" :true-value="1" :false-value="0">
                      <label class="form-check-label small fw-semibold" for="sendDosCtrl">ส่งข้อมูลให้ DOS</label>
                    </div>
                  </div>
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
                  <input type="number" step="0.0001" class="form-control" v-model="itemForm.standard_price" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">ราคาซื้อล่าสุด</label>
                  <input type="number" step="0.0001" class="form-control" v-model="itemForm.last_purchase_price" />
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

    <!-- Item Import Excel Modal -->
    <div class="modal fade" id="itemImportModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-success d-flex align-items-center">
              <i class="bi bi-file-earmark-spreadsheet-fill me-2 fs-4"></i>
              นำเข้าข้อมูลรายการวัสดุ / ยา / เวชภัณฑ์ ผ่าน Excel
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetItemImportState"></button>
          </div>
          <div class="modal-body p-4">
            
            <!-- Instructions and Template Download Card -->
            <div class="card border-0 bg-success-subtle rounded-3 mb-4 p-3">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="bg-success text-white rounded-circle p-2 px-3 fs-5 flex-shrink-0">
                    <i class="bi bi-file-earmark-excel"></i>
                  </div>
                  <div>
                    <h6 class="fw-bold text-success-emphasis mb-1">ขั้นตอนที่ 1: ดาวน์โหลดไฟล์แม่แบบ Excel</h6>
                    <p class="small text-muted mb-0">
                      ไฟล์แม่แบบบรรจุตัวอย่างข้อมูลสำหรับ <strong>{{ activeWarehouse ? activeWarehouse.name : 'คลังพัสดุ' }}</strong> (ชื่อวัสดุ/ยา*, รหัส, ชื่ออังกฤษ, หน่วยนับ, หมวดหมู่, ประเภท, ราคา, Lot, Exp)
                    </p>
                  </div>
                </div>
                <button type="button" class="btn btn-success rounded-pill px-3 py-2 fw-semibold shadow-sm flex-shrink-0" @click="downloadItemTemplate">
                  <i class="bi bi-download me-1"></i> ดาวน์โหลดแม่แบบ Excel (.xlsx)
                </button>
              </div>
            </div>

            <!-- Target Warehouse & Options Card -->
            <div class="card border rounded-3 mb-4 p-3 bg-light-subtle">
              <h6 class="fw-bold mb-3 text-secondary small text-uppercase">
                <i class="bi bi-gear-fill me-1 text-primary"></i> ตั้งค่าการนำเข้าและสังกัดคลัง
              </h6>
              <div class="row g-3 align-items-center">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">นำเข้าสังกัดคลังพัสดุ <span class="text-danger">*</span></label>
                  <div v-if="activeWarehouse" class="p-2 px-3 bg-white rounded-3 border d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-primary small"><i class="bi bi-building me-1"></i>[{{ activeWarehouse.code }}] {{ activeWarehouse.name }}</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">คลังปัจจุบัน</span>
                  </div>
                  <select v-else class="form-select fw-bold" v-model="itemImportTargetWarehouse">
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                      [{{ w.code }}] {{ w.name }}
                    </option>
                  </select>
                  <div class="form-text small text-muted">
                    <i class="bi bi-info-circle me-1"></i> ระบบจะผูกหน่วยนับและหมวดหมู่ให้อัตโนมัติ (หากหน่วยนับยังไม่มี ระบบจะสร้างหน่วยนับให้ทันที)
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">การจัดการรายการที่ซ้ำ</label>
                  <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" id="itemImportUpdateExisting" v-model="itemImportUpdateExisting">
                    <label class="form-check-label fw-semibold text-dark small" for="itemImportUpdateExisting">
                      อัปเดตข้อมูลเดิม หากพบรหัสหรือชื่อวัสดุซ้ำในคลังนี้
                    </label>
                  </div>
                  <div class="form-text small text-muted">หากเปิดไว้ ระบบจะอัปเดตราคา/หน่วยนับ/หมวดหมู่ให้เป็นปัจจุบันแทนการแจ้งเตือนซ้ำ</div>
                </div>
              </div>
            </div>

            <!-- Upload Area -->
            <div class="card border-2 border-dashed rounded-3 p-4 text-center mb-4 bg-white" style="border-style: dashed !important; border-color: #198754 !important;">
              <input 
                type="file" 
                ref="itemExcelInput" 
                class="d-none" 
                accept=".xlsx, .xls, .csv" 
                @change="onItemFileSelected" 
              />
              
              <div v-if="!itemImportFileName">
                <i class="bi bi-cloud-arrow-up text-success fs-1 mb-2 d-block"></i>
                <h6 class="fw-bold mb-1">เลือกไฟล์ Excel รายการวัสดุ/ยาที่ต้องการนำเข้า</h6>
                <p class="text-muted small mb-3">รองรับไฟล์นามสกุล .xlsx, .xls, .csv</p>
                <button type="button" class="btn btn-outline-success rounded-pill px-4 shadow-sm" @click="triggerItemFileInput">
                  <i class="bi bi-folder2-open me-1"></i> เลือกไฟล์จากเครื่อง
                </button>
              </div>

              <div v-else class="d-flex align-items-center justify-content-between p-2 px-3 bg-success-subtle rounded-3 border border-success-subtle">
                <div class="d-flex align-items-center gap-3 text-start">
                  <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-2"></i>
                  <div>
                    <div class="fw-bold text-success-emphasis">{{ itemImportFileName }}</div>
                    <small class="text-muted">ขนาดไฟล์: {{ itemImportFileSize }}</small>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-success rounded-pill" @click="triggerItemFileInput">
                    <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนไฟล์
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" @click="resetItemImportState">
                    <i class="bi bi-trash me-1"></i> ลบ
                  </button>
                </div>
              </div>
            </div>

            <!-- Preview Table -->
            <div v-if="itemImportPreview.length > 0">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                  <i class="bi bi-eye me-1 text-primary"></i> ตรวจสอบข้อมูลก่อนนำเข้า
                </h6>
                <div class="d-flex gap-2 flex-wrap">
                  <span class="badge bg-primary px-3 py-2">
                    ทั้งหมด {{ itemImportPreview.length }} แถว
                  </span>
                  <span class="badge bg-success px-3 py-2">
                    <i class="bi bi-check-circle me-1"></i> พร้อมนำเข้า {{ itemImportValidCount }} แถว
                  </span>
                  <span v-if="itemImportInvalidCount > 0" class="badge bg-danger px-3 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> ไม่สมบูรณ์ {{ itemImportInvalidCount }} แถว
                  </span>
                </div>
              </div>

              <div class="table-responsive border rounded-3 bg-white" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th style="width: 50px;" class="text-center">#</th>
                      <th style="width: 120px;">รหัสวัสดุ</th>
                      <th>ชื่อวัสดุ / ยา</th>
                      <th>หน่วยนับ</th>
                      <th>หมวดหมู่ / กลุ่มยา</th>
                      <th>ประเภท / บัญชี</th>
                      <th class="text-center">ส่งกระทรวง</th>
                      <th class="text-end">ราคามาตรฐาน</th>
                      <th class="text-center" style="width: 55px;">Lot</th>
                      <th class="text-center" style="width: 55px;">Exp</th>
                      <th style="width: 90px;" class="text-center">สถานะ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in itemImportPreview" :key="item.row_num" :class="item.is_valid ? '' : 'table-danger'">
                      <td class="text-center text-muted">{{ item.row_num }}</td>
                      <td>
                        <span v-if="item.item_code" class="badge bg-light text-dark border">{{ item.item_code }}</span>
                        <span v-else class="text-muted small fst-italic">(สร้างอัตโนมัติ)</span>
                      </td>
                      <td class="font-sans-serif" :class="item.is_valid ? 'text-dark' : 'text-danger'">
                        <div class="fw-bold">{{ item.item_name || '-' }}</div>
                        <div class="d-flex gap-1 flex-wrap mt-1">
                          <span v-if="item.dosage_form" class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size: 0.68rem;">{{ item.dosage_form }}</span>
                          <span v-if="item.strength" class="badge bg-light text-dark border" style="font-size: 0.68rem;">{{ item.strength }}</span>
                          <span v-if="item.is_gpo === 1" class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">GPO</span>
                          <span v-if="item.is_free_dispense === 1" class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">จ่ายฟรี</span>
                        </div>
                      </td>
                      <td><span class="badge bg-light text-dark border">{{ item.unit_name || '-' }}</span></td>
                      <td class="small font-sans-serif">{{ item.category_name || '-' }}</td>
                      <td class="small font-sans-serif">
                        <div>{{ item.item_type_name || '-' }}</div>
                        <span v-if="item.ed_sub_list" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;">บัญชี {{ item.ed_sub_list }}</span>
                      </td>
                      <td class="text-center font-sans-serif">
                        <span v-if="item.send_moph === 1" class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">ส่งกระทรวง</span>
                        <span v-else-if="item.send_moph === 0" class="badge bg-light text-muted border" style="font-size: 0.7rem;">ไม่ส่ง</span>
                        <span v-else class="text-muted small">-</span>
                      </td>
                      <td class="text-end font-monospace">{{ Number(item.standard_price || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}</td>
                      <td class="text-center">
                        <i v-if="item.lot_control === 1 || item.lot_control === '1' || item.lot_control === 'ใช่'" class="bi bi-check-lg text-success"></i>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="text-center">
                        <i v-if="item.expiry_control === 1 || item.expiry_control === '1' || item.expiry_control === 'ใช่'" class="bi bi-check-lg text-success"></i>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="text-center font-sans-serif">
                        <span v-if="item.is_valid" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                          <i class="bi bi-check-lg"></i> ผ่าน
                        </span>
                        <span v-else class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" :title="item.error_msg">
                          <i class="bi bi-x-lg"></i> {{ item.error_msg }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
          <div class="modal-footer border-top bg-light rounded-bottom-4 py-3 d-flex justify-content-between">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" @click="resetItemImportState">
              ยกเลิก
            </button>
            <button 
              type="button" 
              class="btn btn-success rounded-pill px-5 fw-bold shadow-sm"
              :disabled="itemImportValidCount === 0 || isImportingItems"
              @click="confirmItemImport"
            >
              <span v-if="isImportingItems" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-cloud-arrow-up-fill me-1"></i>
              ยืนยันการนำเข้า ({{ itemImportValidCount }} รายการ)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TMT Drug Catalog Browser Modal -->
    <div class="modal fade" id="tmtCatalogModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom bg-primary text-white rounded-top-4 py-3">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-capsule fs-4"></i>
              <div>
                <h5 class="modal-title fw-bold mb-0 text-white">คลังสืบค้นยามาตรฐานแห่งชาติ (TMT Drug Catalog)</h5>
                <small class="opacity-75">ฐานข้อมูลยาและเวชภัณฑ์มาตรฐานระดับประเทศ 35,419 รายการ (Thai Medicines Terminology)</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            
            <!-- Search and Quick Filter Box -->
            <div class="card border-0 bg-light rounded-3 p-3 mb-4 shadow-xs">
              <div class="row g-2 align-items-center">
                <div class="col-md-8">
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-primary">
                      <i class="bi bi-search"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      placeholder="พิมพ์ชื่อยาทางการค้า, ตัวยาสำคัญ (Generic), รหัส TPU, หรือชื่อผู้ผลิต..." 
                      v-model="tmtModalQuery" 
                      @input="onTmtModalSearchInput"
                    />
                    <button v-if="tmtModalQuery" type="button" class="btn btn-outline-secondary border-start-0" @click="tmtModalQuery = ''; searchTmtModal();">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="col-md-4 d-flex justify-content-md-end gap-2 align-items-center">
                  <span class="badge bg-primary px-3 py-2 fs-6">
                    พบ {{ tmtModalResults.length }} รายการ
                  </span>
                </div>
              </div>
              <!-- Quick Filter Pills -->
              <div class="d-flex gap-2 flex-wrap mt-3 align-items-center">
                <small class="text-muted fw-semibold">คำค้นยอดนิยม:</small>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('paracetamol')">
                  💊 Paracetamol
                </button>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('amoxicillin')">
                  💊 Amoxicillin
                </button>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('saline')">
                  💧 Normal Saline
                </button>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('omeprazole')">
                  💊 Omeprazole
                </button>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('metformin')">
                  💊 Metformin
                </button>
                <button type="button" class="btn btn-sm btn-white border rounded-pill px-3" @click="quickTmtSearch('losartan')">
                  💊 Losartan
                </button>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="isSearchingTmtModal" class="text-center py-5">
              <div class="spinner-border text-primary mb-2" role="status"></div>
              <div class="text-muted">กำลังค้นหาในฐานข้อมูล 35,419 รายการ...</div>
            </div>

            <!-- Empty State -->
            <div v-else-if="tmtModalResults.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-capsule fs-1 d-block mb-2 text-secondary opacity-50"></i>
              <h6>ไม่พบข้อมูลยาที่ตรงกับคำค้นหา</h6>
              <small>ลองพิมพ์ชื่อการค้า หรือชื่อสามัญทางยา (ภาษาอังกฤษ) เช่น "paracetamol", "amoxicillin", "ceftriaxone"</small>
            </div>

            <!-- Drug Results Table -->
            <div v-else class="table-responsive border rounded-3 bg-white" style="max-height: 480px; overflow-y: auto;">
              <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.88rem;">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th style="width: 110px;">รหัส TPU</th>
                    <th style="min-width: 220px;">ชื่อทางการค้า (Trade Name)</th>
                    <th style="min-width: 220px;">ตัวยาสำคัญ & ความแรง (Generic)</th>
                    <th style="width: 140px;">รูปแบบยา</th>
                    <th style="width: 110px;">หน่วยนับ</th>
                    <th style="min-width: 180px;">ผู้ผลิต</th>
                    <th style="width: 140px;" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(drug, index) in tmtModalResults" :key="drug.id">
                    <td class="text-center text-muted">{{ index + 1 }}</td>
                    <td>
                      <span class="badge bg-secondary-subtle text-secondary border font-monospace">{{ drug.tpucode }}</span>
                    </td>
                    <td class="font-sans-serif fw-bold text-dark">
                      {{ drug.trade_name || drug.fsn }}
                    </td>
                    <td class="font-sans-serif">
                      <div class="text-primary fw-semibold">{{ drug.active_ingredient }}</div>
                      <small class="badge bg-light text-dark border mt-1" v-if="drug.strength">{{ drug.strength }}</small>
                    </td>
                    <td class="font-sans-serif small text-muted">{{ drug.dosage_form || '-' }}</td>
                    <td>
                      <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                        {{ drug.disp_unit_th || drug.disp_unit || '-' }}
                      </span>
                    </td>
                    <td class="font-sans-serif small text-secondary">{{ drug.manufacturer || '-' }}</td>
                    <td class="text-center">
                      <span v-if="isTmtInInventory(drug.tpucode)" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i> มีในคลังแล้ว
                      </span>
                      <button 
                        v-else 
                        type="button" 
                        class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs font-sans-serif fw-semibold"
                        :disabled="isAddingTmtItem[drug.tpucode]"
                        @click="addTmtToInventory(drug)"
                        title="นำยานี้เข้าสู่รายการพัสดุของคลังยาทันที"
                      >
                        <span v-if="isAddingTmtItem[drug.tpucode]" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-plus-lg me-1"></i>
                        เพิ่มเข้าคลัง
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
          <div class="modal-footer border-top bg-light rounded-bottom-4 py-3 d-flex justify-content-between">
            <span class="small text-muted">
              <i class="bi bi-info-circle me-1 text-primary"></i> 
              เมื่อกด <strong>"เพิ่มเข้าคลัง"</strong> ระบบจะบันทึกยาพร้อมแมปหน่วยนับเข้าสู่ [03] คลังยา ทันที
            </span>
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
              ปิดหน้าต่าง
            </button>
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
                    <option value="MANAGER">ผู้ดูแลคลัง (Manager)</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-primary w-100 fw-bold" :disabled="isSavingAccess">
                    เพิ่มสิทธิ์
                  </button>
                </div>
              </div>
            </form>

            <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
              <i class="bi bi-info-circle-fill fs-5 text-info flex-shrink-0"></i>
              <div>
                <strong>คำอธิบายระดับสิทธิ์:</strong>
                <span class="ms-1"><strong>ผู้ดูแลคลัง (Manager)</strong> สามารถสร้างคลังใหม่ ตั้งค่าหมวดหมู่/วัสดุ/ผู้จำหน่าย และกำหนดสิทธิ์เจ้าหน้าที่ในคลังนี้ได้ | <strong>เจ้าหน้าที่ (Staff)</strong> มีสิทธิ์ทำรายการรับ-จ่าย-โอนในคลังนี้</span>
              </div>
            </div>

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
                    <td>
                      <div class="fw-bold">{{ u.user_name }}</div>
                      <small class="text-muted" v-if="u.created_at">บันทึกเมื่อ: {{ u.created_at }}</small>
                    </td>
                    <td>
                      <span class="badge px-3 py-2 rounded-pill" :class="u.access_level === 'MANAGER' ? 'bg-primary' : 'bg-secondary'">
                        <i :class="u.access_level === 'MANAGER' ? 'bi bi-person-badge-fill me-1' : 'bi bi-person-fill me-1'"></i>
                        {{ u.access_level === 'MANAGER' ? 'ผู้ดูแลคลัง (MANAGER)' : 'เจ้าหน้าที่ (STAFF)' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger" @click="removeAccessUser(u.id)" title="ลบสิทธิ์">
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
                  <div v-if="activeWarehouse" class="p-2 px-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-primary small"><i class="bi bi-building me-1"></i>[{{ activeWarehouse.code }}] {{ activeWarehouse.name }}</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">คลังปัจจุบัน</span>
                  </div>
                  <select v-else class="form-select" v-model="vendorForm.warehouse_id" required>
                    <option value="">-- เลือกคลังพัสดุ --</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                      [{{ w.code }}] {{ w.name }}
                    </option>
                  </select>
                  <div class="form-text text-muted small">บริษัทจะสังกัดและแสดงเฉพาะในคลังที่ระบุนี้</div>
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

    <!-- Vendor Import Excel Modal -->
    <div class="modal fade" id="vendorImportModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-success d-flex align-items-center">
              <i class="bi bi-file-earmark-spreadsheet-fill me-2 fs-4"></i>
              นำเข้าข้อมูลบริษัท / ร้านค้าผู้จำหน่ายผ่าน Excel
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetVendorImportState"></button>
          </div>
          <div class="modal-body p-4">
            
            <!-- Instructions and Template Download Card -->
            <div class="card border-0 bg-success-subtle rounded-3 mb-4 p-3">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="bg-success text-white rounded-circle p-2 px-3 fs-5 flex-shrink-0">
                    <i class="bi bi-file-earmark-excel"></i>
                  </div>
                  <div>
                    <h6 class="fw-bold text-success-emphasis mb-1">ขั้นตอนที่ 1: ดาวน์โหลดไฟล์แม่แบบ Excel</h6>
                    <p class="small text-muted mb-0">
                      กรอกข้อมูลตามคอลัมน์มาตรฐาน (ชื่อบริษัท*, รหัสบริษัท, เลขประจำตัวผู้เสียภาษี, เบอร์โทรศัพท์, ผู้ติดต่อ, อีเมล, ที่อยู่, ข้อมูลธนาคาร)
                    </p>
                  </div>
                </div>
                <button type="button" class="btn btn-success rounded-pill px-3 py-2 fw-semibold shadow-sm flex-shrink-0" @click="downloadVendorTemplate">
                  <i class="bi bi-download me-1"></i> ดาวน์โหลดแม่แบบ Excel (.xlsx)
                </button>
              </div>
            </div>

            <!-- Target Warehouse & Options Card -->
            <div class="card border rounded-3 mb-4 p-3 bg-light-subtle">
              <h6 class="fw-bold mb-3 text-secondary small text-uppercase">
                <i class="bi bi-gear-fill me-1 text-primary"></i> ตั้งค่าการนำเข้าและสังกัดคลัง
              </h6>
              <div class="row g-3 align-items-center">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">นำเข้าสังกัดคลังพัสดุ</label>
                  <select class="form-select fw-bold" v-model="vendorImportTargetWarehouse">
                    <option value="">ใช้งานร่วมกันทุกคลัง (Global / ไม่ระบุคลัง)</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                      [{{ w.code }}] {{ w.name }}
                    </option>
                  </select>
                  <div class="form-text small text-muted">
                    <span v-if="vendorImportTargetWarehouse">บริษัทที่นำเข้าจะสังกัดคลังที่เลือกนี้</span>
                    <span v-else>บริษัทที่นำเข้าจะสามารถใช้งานได้ในทุกคลังของโรงพยาบาล</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">การจัดการรายการที่ซ้ำ</label>
                  <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" id="vendorImportUpdateExisting" v-model="vendorImportUpdateExisting">
                    <label class="form-check-label fw-semibold text-dark small" for="vendorImportUpdateExisting">
                      อัปเดตข้อมูลเดิม หากพบรหัสหรือชื่อบริษัทซ้ำ
                    </label>
                  </div>
                  <div class="form-text small text-muted">หากเปิดไว้ ระบบจะอัปเดตข้อมูลที่อยู่/เบอร์โทรให้เป็นปัจจุบันแทนการสร้างซ้ำ</div>
                </div>
              </div>
            </div>

            <!-- Upload Area -->
            <div class="card border-2 border-dashed rounded-3 p-4 text-center mb-4 bg-white" style="border-style: dashed !important; border-color: #198754 !important;">
              <input 
                type="file" 
                ref="vendorExcelInput" 
                class="d-none" 
                accept=".xlsx, .xls, .csv" 
                @change="onVendorFileSelected" 
              />
              
              <div v-if="!vendorImportFileName">
                <i class="bi bi-cloud-arrow-up text-success fs-1 mb-2 d-block"></i>
                <h6 class="fw-bold mb-1">เลือกไฟล์ Excel ที่ต้องการนำเข้า</h6>
                <p class="text-muted small mb-3">รองรับไฟล์นามสกุล .xlsx, .xls, .csv</p>
                <button type="button" class="btn btn-outline-success rounded-pill px-4 shadow-sm" @click="triggerVendorFileInput">
                  <i class="bi bi-folder2-open me-1"></i> เลือกไฟล์จากเครื่อง
                </button>
              </div>

              <div v-else class="d-flex align-items-center justify-content-between p-2 px-3 bg-success-subtle rounded-3 border border-success-subtle">
                <div class="d-flex align-items-center gap-3 text-start">
                  <i class="bi bi-file-earmark-spreadsheet-fill text-success fs-2"></i>
                  <div>
                    <div class="fw-bold text-success-emphasis">{{ vendorImportFileName }}</div>
                    <small class="text-muted">ขนาดไฟล์: {{ vendorImportFileSize }}</small>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-success rounded-pill" @click="triggerVendorFileInput">
                    <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนไฟล์
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" @click="resetVendorImportState">
                    <i class="bi bi-trash me-1"></i> ลบ
                  </button>
                </div>
              </div>
            </div>

            <!-- Preview Table -->
            <div v-if="vendorImportPreview.length > 0">
              <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                  <i class="bi bi-eye me-1 text-primary"></i> ตรวจสอบข้อมูลก่อนนำเข้า
                </h6>
                <div class="d-flex gap-2 flex-wrap">
                  <span class="badge bg-primary px-3 py-2">
                    ทั้งหมด {{ vendorImportPreview.length }} แถว
                  </span>
                  <span class="badge bg-success px-3 py-2">
                    <i class="bi bi-check-circle me-1"></i> พร้อมนำเข้า {{ vendorImportValidCount }} แถว
                  </span>
                  <span v-if="vendorImportInvalidCount > 0" class="badge bg-danger px-3 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> ไม่สมบูรณ์ {{ vendorImportInvalidCount }} แถว
                  </span>
                </div>
              </div>

              <div class="table-responsive border rounded-3 bg-white" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th style="width: 50px;" class="text-center">#</th>
                      <th style="width: 120px;">รหัสบริษัท</th>
                      <th>ชื่อบริษัท / ร้านค้า</th>
                      <th>เลขผู้เสียภาษี</th>
                      <th>เบอร์โทร</th>
                      <th>ผู้ติดต่อ</th>
                      <th>ที่อยู่</th>
                      <th style="width: 90px;" class="text-center">สถานะ</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in vendorImportPreview" :key="item.row_num" :class="item.is_valid ? '' : 'table-danger'">
                      <td class="text-center text-muted">{{ item.row_num }}</td>
                      <td>
                        <span v-if="item.vendor_code" class="badge bg-light text-dark border">{{ item.vendor_code }}</span>
                        <span v-else class="text-muted small fst-italic">(สร้างอัตโนมัติ)</span>
                      </td>
                      <td class="fw-bold font-sans-serif" :class="item.is_valid ? 'text-dark' : 'text-danger'">
                        {{ item.vendor_name || '-' }}
                      </td>
                      <td>{{ item.tax_id || '-' }}</td>
                      <td>{{ item.phone || '-' }}</td>
                      <td>{{ item.contact_person || '-' }}</td>
                      <td class="text-truncate" style="max-width: 160px;" :title="item.address">{{ item.address || '-' }}</td>
                      <td class="text-center">
                        <span v-if="item.is_valid" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                          <i class="bi bi-check-lg"></i> ผ่าน
                        </span>
                        <span v-else class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" :title="item.error_msg">
                          <i class="bi bi-x-lg"></i> {{ item.error_msg }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
          <div class="modal-footer border-top bg-light rounded-bottom-4 py-3 d-flex justify-content-between">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" @click="resetVendorImportState">
              ยกเลิก
            </button>
            <button 
              type="button" 
              class="btn btn-success rounded-pill px-5 fw-bold shadow-sm"
              :disabled="vendorImportValidCount === 0 || isImportingVendors"
              @click="confirmVendorImport"
            >
              <span v-if="isImportingVendors" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-cloud-arrow-up-fill me-1"></i>
              ยืนยันการนำเข้า ({{ vendorImportValidCount }} บริษัท)
            </button>
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
              <div class="mb-3" v-if="activeWarehouse">
                <label class="form-label small fw-semibold">สังกัดคลังวัสดุ</label>
                <select class="form-select" v-model="unitForm.warehouse_id">
                  <option :value="activeWarehouse.id">ใช้เฉพาะ [{{ activeWarehouse.code }}] {{ activeWarehouse.name }}</option>
                  <option :value="null">ใช้งานร่วมกันทุกคลัง (Global)</option>
                </select>
                <div class="form-text small text-muted">หากเลือกใช้เฉพาะคลัง จะแสดงและใช้งานเฉพาะคลังนี้เท่านั้น</div>
              </div>
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

    <!-- Dosage Form Modal -->
    <div class="modal fade" id="dosageFormModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-capsule me-2"></i>{{ isEditDosageForm ? 'แก้ไขรูปแบบยา' : 'เพิ่มรูปแบบยาใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveDosageForm">
              <div class="mb-3">
                <label class="form-label small fw-semibold">รหัสย่อ (Code) <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" v-model="dosageFormForm.code" required placeholder="เช่น tab, inj, cap, sol" />
                <div class="form-text small text-muted">ตัวย่อภาษาอังกฤษมาตรฐาน 2-10 ตัวอักษร</div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อรูปแบบยา (ภาษาไทย) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="dosageFormForm.name" required placeholder="เช่น ยาเม็ด (Tablet), ยาฉีด (Injection)" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อภาษาอังกฤษ (English Name)</label>
                <input type="text" class="form-control" v-model="dosageFormForm.name_en" placeholder="เช่น Tablet, Injection, Capsule" />
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="dfActive" v-model="dosageFormForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="dfActive">เปิดใช้งานรูปแบบยานี้</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingDosageForm">
                  <span v-if="isSavingDosageForm" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ED List Modal -->
    <div class="modal fade" id="edListModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-journal-medical me-2"></i>{{ isEditEdList ? 'แก้ไขบัญชียาหลัก' : 'เพิ่มบัญชียาหลักย่อย' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveEdList">
              <div class="mb-3">
                <label class="form-label small fw-semibold">รหัสบัญชีย่อย <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" v-model="edListForm.code" required placeholder="เช่น ก, ข, ค, ง, จ" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อบัญชียา <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="edListForm.name" required placeholder="เช่น บัญชี ก" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">คำอธิบายและเงื่อนไขการสั่งใช้</label>
                <textarea class="form-control" rows="3" v-model="edListForm.description" placeholder="ระบุเงื่อนไขทางการแพทย์หรือข้อบ่งใช้"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ลำดับการแสดงผล</label>
                <input type="number" class="form-control" v-model.number="edListForm.sort_order" />
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="edActive" v-model="edListForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="edActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingEdList">
                  <span v-if="isSavingEdList" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Category Modal -->
    <div class="modal fade" id="productCategoryModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom-0 bg-light rounded-top-4 py-3">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi bi-patch-check me-2"></i>{{ isEditProductCategory ? 'แก้ไขหมวดหมู่ อย.' : 'เพิ่มหมวดหมู่ อย. ใหม่' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="saveProductCategory">
              <div class="mb-3">
                <label class="form-label small fw-semibold">รหัสมาตรฐาน อย. <span class="text-danger">*</span></label>
                <input type="text" class="form-control font-monospace" v-model="productCategoryForm.code" required placeholder="เช่น 1, 3, 5" />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อหมวดหมู่ อย. <span class="text-danger">*</span></label>
                <input type="text" class="form-control" v-model="productCategoryForm.name" required placeholder="เช่น ยาแผนปัจจุบันที่ขึ้นทะเบียนตำรับยากับ อย." />
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">คำอธิบาย</label>
                <textarea class="form-control" rows="2" v-model="productCategoryForm.description" placeholder="รายละเอียดเพิ่มเติม"></textarea>
              </div>
              <div class="mb-4">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="pcActive" v-model="productCategoryForm.active" :true-value="1" :false-value="0">
                  <label class="form-check-label fw-semibold" for="pcActive">เปิดใช้งาน</label>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" :disabled="isSavingProductCategory">
                  <span v-if="isSavingProductCategory" class="spinner-border spinner-border-sm me-2"></span> บันทึกข้อมูล
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
import * as XLSX from 'xlsx';

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
      currentUserId: '',
      isAdminUser: false,
      isSuperAdmin: false,
      isSaving: false,
      isSavingCategory: false,
      isSavingWarehouse: false,
      isEditWarehouse: false,
      isEditCategory: false,
      isEditItem: false,
      isLoadingNextCode: false,
      isSeedingDrugGroups: false,
      warehouses: [],
      categories: [],
      items: [],
      units: [],
      warehouseForm: {
        id: null, code: '', name: '', active: 1
      },
      categoryForm: {
        id: null, warehouse_id: '', code: '', name: '', gpsc_code: '', gpsc_name: '', active: 1
      },
      itemForm: {
        id: null, item_code: '', old_code: '', item_name: '', item_name_en: '',
        warehouse_id: '', category_id: '', item_type_id: '',
        dosage_form_id: null, ed_list_id: null, product_cat_id: null,
        std_code_24: '', tmt_gpu: '', strength: '', is_gpo: 0, is_original: 0, packing_size: 1,
        send_moph: null, send_dos: 0, is_free_dispense: 0, std_unit_en: '', std_unit_th: '',
        moph_status_note: '', moph_updated_text: '',
        unit_id: '', purchase_unit_id: '', issue_unit_id: '', conversion_rate: 1.00,
        barcode: '', brand: '', model: '',
        minimum_stock: 0, maximum_stock: 0, reorder_point: 0,
        standard_price: 0, last_purchase_price: 0,
        lot_control: 0, expiry_control: 0, serial_control: 0, active: 1
      },
      // Items Search and Import state
      itemSearchQuery: '',
      itemFilterCategory: '',
      itemFilterType: '',
      itemFilterDosageForm: '',
      itemFilterEdList: '',
      itemFilterSendMoph: '',
      isImportingItems: false,
      itemImportPreview: [],
      itemImportFileName: '',
      itemImportFileSize: '',
      itemImportTargetWarehouse: '',
      itemImportUpdateExisting: true,
      // Pharmacy Masters State
      dosageForms: [],
      loadingDosageForms: false,
      dosageFormSearchQuery: '',
      isEditDosageForm: false,
      isSavingDosageForm: false,
      dosageFormForm: { id: null, code: '', name: '', name_en: '', active: 1 },
      edLists: [],
      loadingEdLists: false,
      edListSearchQuery: '',
      isEditEdList: false,
      isSavingEdList: false,
      edListForm: { id: null, code: '', name: '', description: '', sort_order: 0, active: 1 },
      productCategories: [],
      loadingProductCategories: false,
      productCategorySearchQuery: '',
      isEditProductCategory: false,
      isSavingProductCategory: false,
      productCategoryForm: { id: null, code: '', name: '', description: '', active: 1 },
      // TMT Drug Catalog State
      tmtQuery: '',
      tmtResults: [],
      isSearchingTmt: false,
      tmtShowDropdown: false,
      tmtDebounceTimer: null,
      tmtSelectedDrug: null,
      tmtModalQuery: '',
      tmtModalResults: [],
      isSearchingTmtModal: false,
      tmtModalDebounceTimer: null,
      isAddingTmtItem: {},
      tmtTotalCount: 35419,
      // Item Types state
      itemTypes: [],
      loadingItemTypes: false,
      isSavingItemType: false,
      isEditItemType: false,
      itemTypeSearchQuery: '',
      itemTypeFilterStatus: '',
      itemTypeForm: {
        id: null,
        code: '',
        name: '',
        report_group: '',
        moph_report_group: '',
        ap_account_code: '',
        debit_account_code: '',
        credit_account_code: '',
        active: 1
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
      vendorImportPreview: [],
      vendorImportFileName: '',
      vendorImportFileSize: '',
      vendorImportTargetWarehouse: '',
      vendorImportUpdateExisting: true,
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
  emits: ['switch-warehouse'],
  computed: {
    effectiveWarehouseId() {
      const id = this.warehouseId || this.$route.query.warehouse_id || localStorage.getItem('selected_warehouse_id');
      return id ? String(id) : '';
    },
    activeWarehouse() {
      if (!this.effectiveWarehouseId) return null;
      return this.warehouses.find(w => String(w.id) === String(this.effectiveWarehouseId)) || null;
    },
    isPharmacyWarehouse() {
      if (!this.activeWarehouse) return false;
      return String(this.activeWarehouse.id) === '2' || (this.activeWarehouse.name || '').includes('ยา');
    },
    isMedicalWarehouse() {
      if (!this.activeWarehouse) return false;
      return String(this.activeWarehouse.code) === '04' || (this.activeWarehouse.name || '').includes('เวชภัณฑ์');
    },
    displayCategories() {
      if (this.effectiveWarehouseId) {
        return this.categories.filter(c => String(c.warehouse_id) === String(this.effectiveWarehouseId));
      }
      return this.categories;
    },
    displayItemTypes() {
      if (this.effectiveWarehouseId) {
        return this.itemTypes.filter(t => String(t.warehouse_id) === String(this.effectiveWarehouseId));
      }
      return this.itemTypes;
    },
    displayItems() {
      if (this.effectiveWarehouseId) {
        return this.items.filter(i => String(i.warehouse_id) === String(this.effectiveWarehouseId));
      }
      return this.items;
    },
    filteredDisplayItems() {
      let list = this.displayItems;
      if (this.itemFilterCategory) {
        list = list.filter(i => String(i.category_id) === String(this.itemFilterCategory));
      }
      if (this.itemFilterType) {
        list = list.filter(i => String(i.item_type_id) === String(this.itemFilterType));
      }
      if (this.itemFilterDosageForm) {
        list = list.filter(i => String(i.dosage_form_id) === String(this.itemFilterDosageForm) || String(i.dosage_form) === String(this.itemFilterDosageForm));
      }
      if (this.itemFilterEdList) {
        list = list.filter(i => String(i.ed_list_id) === String(this.itemFilterEdList) || String(i.ed_sub_list) === String(this.itemFilterEdList));
      }
      if (this.itemFilterSendMoph !== '') {
        if (this.itemFilterSendMoph === 'null') {
          list = list.filter(i => i.send_moph === null || i.send_moph === undefined || i.send_moph === '');
        } else {
          list = list.filter(i => i.send_moph !== null && i.send_moph !== undefined && i.send_moph !== '' && String(i.send_moph) === String(this.itemFilterSendMoph));
        }
      }
      if (this.itemSearchQuery) {
        const q = this.itemSearchQuery.toLowerCase().trim();
        list = list.filter(i => 
          (i.item_name && i.item_name.toLowerCase().includes(q)) ||
          (i.item_code && i.item_code.toLowerCase().includes(q)) ||
          (i.old_code && i.old_code.toLowerCase().includes(q)) ||
          (i.item_name_en && i.item_name_en.toLowerCase().includes(q)) ||
          (i.barcode && i.barcode.toLowerCase().includes(q)) ||
          (i.tmt_gpu && i.tmt_gpu.toLowerCase().includes(q)) ||
          (i.std_code_24 && i.std_code_24.toLowerCase().includes(q)) ||
          (i.brand && i.brand.toLowerCase().includes(q)) ||
          (i.dosage_form && i.dosage_form.toLowerCase().includes(q)) ||
          (i.category_name && i.category_name.toLowerCase().includes(q)) ||
          (i.unit_name && i.unit_name.toLowerCase().includes(q)) ||
          (i.item_type_name && i.item_type_name.toLowerCase().includes(q))
        );
      }
      return list;
    },
    filteredDosageForms() {
      if (!this.dosageFormSearchQuery) return this.dosageForms;
      const q = this.dosageFormSearchQuery.toLowerCase().trim();
      return this.dosageForms.filter(df => 
        (df.code && df.code.toLowerCase().includes(q)) ||
        (df.name && df.name.toLowerCase().includes(q)) ||
        (df.name_en && df.name_en.toLowerCase().includes(q))
      );
    },
    itemImportValidCount() {
      return this.itemImportPreview.filter(i => i.is_valid).length;
    },
    itemImportInvalidCount() {
      return this.itemImportPreview.filter(i => !i.is_valid).length;
    },
    selectedVendorWarehouseName() {
      const targetWh = this.effectiveWarehouseId || this.selectedVendorWarehouse;
      if (!targetWh) return '';
      const wh = this.warehouses.find(w => String(w.id) === String(targetWh));
      return wh ? wh.name : '';
    },
    filteredCategories() {
      const whId = this.itemForm.warehouse_id || this.effectiveWarehouseId;
      if (!whId) return [];
      return this.categories.filter(c => String(c.warehouse_id) === String(whId));
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
      const targetWh = this.effectiveWarehouseId || this.selectedVendorWarehouse;
      if (targetWh) {
        list = list.filter(v => String(v.warehouse_id) === String(targetWh) || !v.warehouse_id);
      }
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
    vendorImportValidCount() {
      return this.vendorImportPreview.filter(i => i.is_valid).length;
    },
    vendorImportInvalidCount() {
      return this.vendorImportPreview.filter(i => !i.is_valid).length;
    },
    filteredUnits() {
      let list = this.units;
      if (this.effectiveWarehouseId) {
        list = list.filter(u => String(u.warehouse_id) === String(this.effectiveWarehouseId));
      }
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
    },
    filteredItemTypes() {
      let list = this.displayItemTypes;
      if (this.itemTypeFilterStatus !== '') {
        list = list.filter(t => Number(t.active) === Number(this.itemTypeFilterStatus));
      }
      if (this.itemTypeSearchQuery) {
        const q = this.itemTypeSearchQuery.toLowerCase().trim();
        list = list.filter(t => 
          (t.name && t.name.toLowerCase().includes(q)) || 
          (t.code && t.code.toLowerCase().includes(q)) ||
          (t.report_group && t.report_group.toLowerCase().includes(q)) ||
          (t.moph_report_group && t.moph_report_group.toLowerCase().includes(q)) ||
          (t.ap_account_code && t.ap_account_code.toLowerCase().includes(q)) ||
          (t.debit_account_code && t.debit_account_code.toLowerCase().includes(q)) ||
          (t.credit_account_code && t.credit_account_code.toLowerCase().includes(q))
        );
      }
      return list;
    },
    isManagerUser() {
      return this.isAdminUser || this.warehouses.some(w => w.access_level === 'MANAGER');
    },
    canCreateWarehouse() {
      return this.isSuperAdmin;
    }
  },
  watch: {
    warehouseId(newVal) {
      if (newVal) {
        this.selectedVendorWarehouse = String(newVal);
        this.categoryForm.warehouse_id = newVal;
        this.itemForm.warehouse_id = newVal;
        this.vendorForm.warehouse_id = newVal;
        this.unitForm.warehouse_id = newVal;
      }
      this.fetchData();
    }
  },
  async mounted() {
    await this.checkUserPermissions();
    const qWh = this.effectiveWarehouseId;
    if (qWh) {
      this.selectedVendorWarehouse = String(qWh);
      this.categoryForm.warehouse_id = qWh;
      this.itemForm.warehouse_id = qWh;
      this.vendorForm.warehouse_id = qWh;
      this.unitForm.warehouse_id = parseInt(qWh) || qWh;
    }
    const subtab = this.$route.query.subtab;
    if (subtab && ['wh', 'cat', 'itemtype', 'unit', 'buymethod', 'item', 'vendor'].includes(subtab)) {
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
    async checkUserPermissions() {
      try {
        const token = localStorage.getItem('user_token');
        const config = token ? { headers: { Authorization: `Bearer ${token}` } } : {};
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data && response.data.status === 'success') {
          this.currentUserId = response.data.id || response.data.user_id || '';
          const accessUser = response.data.access_user ? response.data.access_user.split(':') : [];
          this.isSuperAdmin = accessUser.includes('administrator');
          this.isAdminUser =
            accessUser.includes('administrator') ||
            accessUser.includes('menu_inventory_manage') ||
            accessUser.includes('menu_inventory_admin') ||
            accessUser.includes('menu_it_material_manage') ||
            accessUser.includes('menu_gm_material_manage') ||
            localStorage.getItem('user_role') === 'admin' ||
            localStorage.getItem('is_admin') === 'true';
        } else {
          this.currentUserId = localStorage.getItem('user_id') || '';
          this.isSuperAdmin = false;
          this.isAdminUser =
            localStorage.getItem('user_role') === 'admin' ||
            localStorage.getItem('is_admin') === 'true';
        }
      } catch (err) {
        this.currentUserId = localStorage.getItem('user_id') || '';
        this.isSuperAdmin = false;
        this.isAdminUser =
          localStorage.getItem('user_role') === 'admin' ||
          localStorage.getItem('is_admin') === 'true';
      }
    },
    canManageWarehouse(wh) {
      if (this.isAdminUser) return true;
      if (!wh) return false;
      return wh.access_level === 'MANAGER' || wh.access_level === 'ADMIN';
    },
    async fetchData() {
      try {
        const whId = this.effectiveWarehouseId;
        const vendorParams = new URLSearchParams();
        if (whId) {
          vendorParams.append('warehouse_id', whId);
        } else if (this.selectedVendorWarehouse) {
          vendorParams.append('warehouse_id', this.selectedVendorWarehouse);
        }

        const catParams = whId ? `?warehouse_id=${whId}` : '';
        const itemParams = whId ? `?warehouse_id=${whId}` : '';
        const unitParams = whId ? `?warehouse_id=${whId}` : '';
        const itemTypeParams = whId ? `?warehouse_id=${whId}` : '';

        const whUrl = this.isAdminUser
          ? '/api-digital/inventory/warehouses.php?is_admin=1'
          : `/api-digital/inventory/warehouses.php?user_id=${this.currentUserId || localStorage.getItem('user_id') || ''}&include_inactive=1`;

        const [whRes, catRes, itemRes, unitRes, staffRes, vendorRes, buyMethodRes, itemTypeRes, pharmaRes] = await Promise.all([
          axios.get(whUrl),
          axios.get(`/api-digital/inventory/categories.php${catParams}`),
          axios.get(`/api-digital/inventory/items.php${itemParams}`),
          axios.get(`/api-digital/inventory/units.php${unitParams}`),
          axios.get('/api-hosoffice/get_all_staff.php').catch(() => null),
          axios.get(`/api-digital/inventory/vendors.php?${vendorParams.toString()}`).catch(() => null),
          axios.get('/api-digital/inventory/buy_methods.php').catch(() => null),
          axios.get(`/api-digital/inventory/item_types.php${itemTypeParams}`).catch(() => null),
          axios.get('/api-digital/inventory/pharmacy_masters.php').catch(() => null)
        ]);
        
        if (whRes?.data?.status === 'success') this.warehouses = whRes.data.data;
        if (catRes?.data?.status === 'success') this.categories = catRes.data.data;
        if (itemRes?.data?.status === 'success') this.items = itemRes.data.data;
        if (unitRes?.data?.status === 'success') this.units = unitRes.data.data;
        if (staffRes?.data?.status === 'success') this.allStaff = staffRes.data.data;
        if (vendorRes?.data?.status === 'success') this.vendors = vendorRes.data.data;
        if (buyMethodRes?.data?.status === 'success') this.buyMethods = buyMethodRes.data.data;
        if (itemTypeRes?.data?.status === 'success') this.itemTypes = itemTypeRes.data.data;
        if (pharmaRes?.data?.status === 'success') {
          this.dosageForms = pharmaRes.data.dosage_forms || [];
          this.edLists = pharmaRes.data.ed_lists || [];
          this.productCategories = pharmaRes.data.product_categories || [];
        }
      } catch (err) {
        console.error("Error fetching master data", err);
      }
    },
    openWarehouseModal(wh = null) {
      if (wh && wh.id) {
        this.isEditWarehouse = true;
        this.warehouseForm = { id: wh.id, code: wh.code, name: wh.name, active: wh.active !== undefined ? Number(wh.active) : 1 };
      } else {
        if (!this.isSuperAdmin) {
          Swal.fire('ไม่มีสิทธิ์', 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเพิ่มคลังใหม่ได้', 'warning');
          return;
        }
        this.isEditWarehouse = false;
        this.warehouseForm = { id: null, code: '', name: '', active: 1 };
      }
      const modal = new bootstrap.Modal(document.getElementById('warehouseModal'));
      modal.show();
    },
    async saveWarehouse() {
      if (!this.isEditWarehouse && !this.isSuperAdmin) {
        Swal.fire('ไม่มีสิทธิ์', 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเพิ่มคลังใหม่ได้', 'warning');
        return;
      }
      this.isSavingWarehouse = true;
      try {
        const method = this.isEditWarehouse ? 'put' : 'post';
        const payload = {
          ...this.warehouseForm,
          user_id: this.currentUserId || localStorage.getItem('user_id') || ''
        };
        const res = await axios[method]('/api-digital/inventory/warehouses.php', payload);
        
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: 'ข้อมูลคลังวัสดุถูกบันทึกเรียบร้อยแล้ว' + (!this.isEditWarehouse ? ' (คุณได้รับสิทธิ์ผู้ดูแลคลังนี้โดยอัตโนมัติ)' : ''),
            icon: 'success',
            confirmButtonColor: '#0d6efd'
          });
          
          const modalEl = document.getElementById('warehouseModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
          
          await this.fetchData();
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
          warehouse_id: this.effectiveWarehouseId || cat.warehouse_id,
          code: cat.code,
          name: cat.name,
          gpsc_code: cat.gpsc_code || '',
          gpsc_name: cat.gpsc_name || '',
          active: cat.active !== undefined ? Number(cat.active) : 1
        };
      } else {
        this.isEditCategory = false;
        const defaultWh = this.effectiveWarehouseId || this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : '');
        this.categoryForm = {
          id: null,
          warehouse_id: defaultWh,
          code: '',
          name: '',
          gpsc_code: '',
          gpsc_name: '',
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
      modal.show();
    },
    async seedDrugGroups() {
      const whId = this.effectiveWarehouseId || 2;
      this.isSeedingDrugGroups = true;
      try {
        const res = await axios.post('/api-digital/inventory/categories_seed_drugs.php', {
          warehouse_id: whId
        });
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message || 'นำเข้า 24 กลุ่มยามาตรฐานเรียบร้อยแล้ว',
            timer: 1600,
            showConfirmButton: false
          });
          await this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'ไม่สามารถนำเข้าได้', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการนำเข้ากลุ่มยา', 'error');
      } finally {
        this.isSeedingDrugGroups = false;
      }
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
    // Item Type Methods
    openItemTypeModal(it = null) {
      if (it && it.id) {
        this.isEditItemType = true;
        this.itemTypeForm = {
          id: it.id,
          code: it.code || '',
          name: it.name || '',
          report_group: it.report_group || '',
          moph_report_group: it.moph_report_group || '',
          ap_account_code: it.ap_account_code || '',
          debit_account_code: it.debit_account_code || '',
          credit_account_code: it.credit_account_code || '',
          warehouse_id: it.warehouse_id || this.effectiveWarehouseId || null,
          active: it.active !== undefined ? Number(it.active) : 1
        };
      } else {
        this.isEditItemType = false;
        const currentWhTypes = this.displayItemTypes;
        const codes = currentWhTypes.map(t => parseInt(t.code)).filter(n => !isNaN(n));
        const nextCode = codes.length > 0 ? (Math.max(...codes) + 1).toString().padStart(2, '0') : '01';
        this.itemTypeForm = {
          id: null,
          code: nextCode,
          name: '',
          report_group: '',
          moph_report_group: '',
          ap_account_code: '',
          debit_account_code: '',
          credit_account_code: '',
          warehouse_id: this.effectiveWarehouseId || null,
          active: 1
        };
      }
      const modal = new bootstrap.Modal(document.getElementById('itemTypeModal'));
      modal.show();
    },
    async saveItemType() {
      if (!this.itemTypeForm.name || !this.itemTypeForm.name.trim()) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกชื่อประเภทเวชภัณฑ์', 'warning');
        return;
      }
      this.isSavingItemType = true;
      try {
        const method = (this.isEditItemType && this.itemTypeForm.id) ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/item_types.php', this.itemTypeForm);
        if (res.data.status === 'success') {
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: res.data.message || 'บันทึกข้อมูลประเภทเวชภัณฑ์เรียบร้อยแล้ว',
            icon: 'success',
            confirmButtonColor: '#0d6efd'
          });
          const modalEl = document.getElementById('itemTypeModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();
          this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        const msg = err.response?.data?.message || 'ระบบเกิดข้อขัดข้องระหว่างบันทึก';
        Swal.fire('ข้อผิดพลาด', msg, 'error');
      } finally {
        this.isSavingItemType = false;
      }
    },
    async deleteItemType(it) {
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบประเภทเวชภัณฑ์?',
        text: `คุณต้องการลบ "${it.name}" (รหัส: ${it.code}) ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ต้องการลบ',
        cancelButtonText: 'ยกเลิก'
      });
      if (confirm.isConfirmed) {
        try {
          const res = await axios.delete(`/api-digital/inventory/item_types.php?id=${it.id}`);
          if (res.data.status === 'success') {
            Swal.fire('ลบสำเร็จ!', res.data.message || 'ลบประเภทเวชภัณฑ์เรียบร้อยแล้ว', 'success');
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
      this.clearTmtSelection();
      if (item && item.id) {
        this.isEditItem = true;
        this.itemForm = {
          id: item.id, item_code: item.item_code, old_code: item.old_code,
          item_name: item.item_name, item_name_en: item.item_name_en,
          warehouse_id: this.effectiveWarehouseId || item.warehouse_id, category_id: item.category_id,
          item_type_id: item.item_type_id || '',
          dosage_form_id: item.dosage_form_id || null,
          ed_list_id: item.ed_list_id || null,
          product_cat_id: item.product_cat_id || null,
          std_code_24: item.std_code_24 || item.old_code || '',
          tmt_gpu: item.tmt_gpu || item.barcode || '',
          strength: item.strength || '',
          is_gpo: item.is_gpo ? Number(item.is_gpo) : 0,
          is_original: item.is_original ? Number(item.is_original) : 0,
          packing_size: item.packing_size || 1,
          send_moph: (item.send_moph === null || item.send_moph === undefined || item.send_moph === '') ? null : Number(item.send_moph),
          send_dos: item.send_dos ? Number(item.send_dos) : 0,
          is_free_dispense: item.is_free_dispense ? Number(item.is_free_dispense) : 0,
          std_unit_en: item.std_unit_en || '',
          std_unit_th: item.std_unit_th || '',
          moph_status_note: item.moph_status_note || '',
          moph_updated_text: item.moph_updated_text || '',
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
        const defaultWh = this.effectiveWarehouseId || this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : '');
        const isPharma = this.isPharmacyWarehouse;
        this.itemForm = {
          id: null, item_code: '', old_code: '', item_name: '', item_name_en: '',
          warehouse_id: defaultWh, category_id: '', item_type_id: '',
          dosage_form_id: null, ed_list_id: null, product_cat_id: null,
          std_code_24: '', tmt_gpu: '', strength: '', is_gpo: 0, is_original: 0, packing_size: 1,
          send_moph: null, send_dos: 0, is_free_dispense: 0, std_unit_en: '', std_unit_th: '',
          moph_status_note: '', moph_updated_text: '',
          unit_id: '', purchase_unit_id: '', issue_unit_id: '', conversion_rate: 1.00,
          barcode: '', brand: '', model: '',
          minimum_stock: 0, maximum_stock: 0, reorder_point: 0,
          standard_price: 0, last_purchase_price: 0,
          lot_control: isPharma ? 1 : 0, expiry_control: isPharma ? 1 : 0, serial_control: 0, active: 1
        };
        // Auto-run next sequential item code continuing from previous item
        this.fetchNextItemCode();
      }
      const modalEl = document.getElementById('itemModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    async fetchNextItemCode() {
      this.isLoadingNextCode = true;
      try {
        const whId = this.itemForm.warehouse_id || this.effectiveWarehouseId || '';
        const catId = this.itemForm.category_id || '';
        if (whId) {
          const res = await axios.get(`/api-digital/inventory/items.php?action=next_code&warehouse_id=${whId}&category_id=${catId}`);
          if (res.data?.status === 'success' && res.data?.next_code) {
            this.itemForm.item_code = res.data.next_code;
          }
        }
      } catch (err) {
        console.error('Failed to get next item code', err);
      } finally {
        this.isLoadingNextCode = false;
      }
    },
    onItemCategoryChange() {
      if (!this.isEditItem && (!this.itemForm.item_code || this.itemForm.item_code.startsWith('C') || this.itemForm.item_code.startsWith('M'))) {
        this.fetchNextItemCode();
      }
    },
    onItemWarehouseChange() {
      if (!this.isEditItem) {
        this.fetchNextItemCode();
      }
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
          warehouse_id: this.effectiveWarehouseId || vendor.warehouse_id || '',
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
          warehouse_id: this.effectiveWarehouseId || this.selectedVendorWarehouse || (this.warehouses.length > 0 ? this.warehouses[0].id : ''),
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
    exportVendorsToExcel() {
      const vendorsToExport = this.filteredVendors;
      if (!vendorsToExport || vendorsToExport.length === 0) {
        Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลบริษัทสำหรับส่งออก', 'info');
        return;
      }

      const exportRows = vendorsToExport.map((v, index) => {
        return {
          'ลำดับ': index + 1,
          'รหัสบริษัท': v.vendor_code || '',
          'ชื่อบริษัท / ร้านค้า': v.vendor_name || '',
          'คลังที่สังกัด': v.warehouse_name || 'ทุกคลัง (Global)',
          'เลขผู้เสียภาษี': v.tax_id || '',
          'เบอร์โทรศัพท์': v.phone || '',
          'ชื่อผู้ติดต่อ': v.contact_person || '',
          'อีเมล': v.email || '',
          'ที่อยู่': v.address || '',
          'ข้อมูลบัญชีธนาคาร': v.bank_info || '',
          'สถานะ': Number(v.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน'
        };
      });

      const worksheet = XLSX.utils.json_to_sheet(exportRows);
      worksheet['!cols'] = [
        { wch: 8 },  // ลำดับ
        { wch: 16 }, // รหัสบริษัท
        { wch: 38 }, // ชื่อบริษัท
        { wch: 22 }, // คลังที่สังกัด
        { wch: 20 }, // เลขผู้เสียภาษี
        { wch: 18 }, // เบอร์โทร
        { wch: 20 }, // ผู้ติดต่อ
        { wch: 25 }, // อีเมล
        { wch: 45 }, // ที่อยู่
        { wch: 35 }, // ธนาคาร
        { wch: 14 }  // สถานะ
      ];

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Vendors');

      const whName = this.selectedVendorWarehouseName ? `_${this.selectedVendorWarehouseName}` : '';
      const nowStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const fileName = `รายชื่อบริษัทผู้จำหน่าย${whName}_${nowStr}.xlsx`;
      XLSX.writeFile(workbook, fileName);
    },
    openVendorImportModal() {
      this.vendorImportTargetWarehouse = this.effectiveWarehouseId || this.selectedVendorWarehouse || '';
      this.vendorImportUpdateExisting = true;
      this.resetVendorImportState();
      const modal = new bootstrap.Modal(document.getElementById('vendorImportModal'));
      modal.show();
    },
    resetVendorImportState() {
      this.vendorImportPreview = [];
      this.vendorImportFileName = '';
      this.vendorImportFileSize = '';
      if (this.$refs.vendorExcelInput) {
        this.$refs.vendorExcelInput.value = '';
      }
    },
    triggerVendorFileInput() {
      if (this.$refs.vendorExcelInput) {
        this.$refs.vendorExcelInput.click();
      }
    },
    downloadVendorTemplate() {
      const templateData = [
        {
          'ชื่อบริษัท / ร้านค้า (จำเป็น)': 'บริษัท ตัวอย่างเวชภัณฑ์ จำกัด',
          'รหัสบริษัท (เว้นว่างได้)': 'VND-0001',
          'เลขประจำตัวผู้เสียภาษี': '0105559001234',
          'เบอร์โทรศัพท์': '02-123-4567',
          'ชื่อผู้ติดต่อ': 'คุณสมชาย ใจดี',
          'อีเมล': 'contact@examplepharm.com',
          'ที่อยู่': '123/45 ถนนพหลโยธิน แขวงลาดยาว เขตจตุจักร กทม. 10900',
          'ข้อมูลบัญชีธนาคาร': 'ธ.กรุงไทย 123-0-12345-6 สาขาจตุจักร'
        },
        {
          'ชื่อบริษัท / ร้านค้า (จำเป็น)': 'ห้างหุ้นส่วนจำกัด สยามเมดิคอล ซัพพลาย',
          'รหัสบริษัท (เว้นว่างได้)': '',
          'เลขประจำตัวผู้เสียภาษี': '0103558004567',
          'เบอร์โทรศัพท์': '045-241234',
          'ชื่อผู้ติดต่อ': 'คุณวิภาวรรณ',
          'อีเมล': 'siam_med@yahoo.com',
          'ที่อยู่': '456 ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.อุบลราชธานี 34000',
          'ข้อมูลบัญชีธนาคาร': 'ธ.กสิกรไทย 456-1-78901-2'
        }
      ];

      const worksheet = XLSX.utils.json_to_sheet(templateData);
      worksheet['!cols'] = [
        { wch: 38 }, // ชื่อบริษัท
        { wch: 20 }, // รหัสบริษัท
        { wch: 22 }, // เลขผู้เสียภาษี
        { wch: 18 }, // เบอร์โทร
        { wch: 20 }, // ผู้ติดต่อ
        { wch: 28 }, // อีเมล
        { wch: 50 }, // ที่อยู่
        { wch: 35 }  // ธนาคาร
      ];

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Vendors_Template');
      XLSX.writeFile(workbook, 'แบบฟอร์มนำเข้าบริษัท_ผู้จำหน่าย.xlsx');
    },
    onVendorFileSelected(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.vendorImportFileName = file.name;
      this.vendorImportFileSize = (file.size / 1024).toFixed(1) + ' KB';

      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

          if (!rawJson || rawJson.length === 0) {
            Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลในไฟล์ Excel หรือแผ่นงานว่างเปล่า', 'warning');
            this.resetVendorImportState();
            return;
          }

          this.vendorImportPreview = rawJson.map((row, idx) => {
            const clean = {};
            for (const k in row) {
              clean[k.trim()] = String(row[k] ?? '').trim();
            }

            const name = clean['ชื่อบริษัท / ร้านค้า (จำเป็น)'] 
              || clean['ชื่อบริษัท / ร้านค้า'] 
              || clean['ชื่อบริษัท'] 
              || clean['ชื่อร้านค้า'] 
              || clean['ชื่อคู่ค้า'] 
              || clean['ชื่อสถานประกอบการ'] 
              || clean['ผู้จำหน่าย'] 
              || clean['vendor_name'] 
              || clean['company_name'] 
              || clean['name'] 
              || '';

            const code = clean['รหัสบริษัท (เว้นว่างได้)'] 
              || clean['รหัสบริษัท'] 
              || clean['รหัสผู้จำหน่าย'] 
              || clean['รหัสร้านค้า'] 
              || clean['vendor_code'] 
              || clean['code'] 
              || '';

            const tax_id = clean['เลขประจำตัวผู้เสียภาษี'] 
              || clean['เลขผู้เสียภาษี'] 
              || clean['เลขประจำตัวผู้เสียภาษีอากร'] 
              || clean['tax_id'] 
              || clean['taxid'] 
              || '';

            const phone = clean['เบอร์โทรศัพท์'] 
              || clean['โทรศัพท์'] 
              || clean['เบอร์โทร'] 
              || clean['โทร'] 
              || clean['phone'] 
              || clean['tel'] 
              || clean['telephone'] 
              || '';

            const contact = clean['ชื่อผู้ติดต่อ'] 
              || clean['ผู้ติดต่อ'] 
              || clean['ผู้ประสานงาน'] 
              || clean['เซลล์'] 
              || clean['contact_person'] 
              || clean['contact'] 
              || '';

            const email = clean['อีเมล'] 
              || clean['อีเมล์'] 
              || clean['email'] 
              || clean['e-mail'] 
              || '';

            const address = clean['ที่อยู่'] 
              || clean['ที่อยู่บริษัท'] 
              || clean['address'] 
              || '';

            const bank = clean['ข้อมูลบัญชีธนาคาร'] 
              || clean['ข้อมูลธนาคาร'] 
              || clean['ธนาคาร'] 
              || clean['bank_info'] 
              || clean['bank'] 
              || '';

            const isValid = name.length > 0;

            return {
              row_num: idx + 1,
              vendor_name: name,
              vendor_code: code,
              tax_id: tax_id,
              phone: phone,
              contact_person: contact,
              email: email,
              address: address,
              bank_info: bank,
              is_valid: isValid,
              error_msg: isValid ? '' : 'ไม่มีชื่อบริษัท'
            };
          });

        } catch (err) {
          console.error("Error parsing vendor excel:", err);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอ่านไฟล์ Excel ได้ กรุณาตรวจสอบรูปแบบไฟล์', 'error');
          this.resetVendorImportState();
        }
      };
      reader.readAsArrayBuffer(file);
    },
    async confirmVendorImport() {
      const validItems = this.vendorImportPreview.filter(item => item.is_valid);
      if (validItems.length === 0) {
        Swal.fire('แจ้งเตือน', 'ไม่มีรายการที่ถูกต้องพร้อมนำเข้า', 'warning');
        return;
      }

      this.isImportingVendors = true;
      try {
        const payload = {
          action: 'batch_import',
          warehouse_id: this.vendorImportTargetWarehouse || (this.activeWarehouse ? this.activeWarehouse.id : null),
          update_existing: this.vendorImportUpdateExisting,
          items: validItems
        };

        const res = await axios.post('/api-digital/inventory/vendors.php', payload);
        if (res.data?.status === 'success') {
          let html = `<div class="text-start mb-2">${res.data.message}</div>`;
          if (res.data.errors && res.data.errors.length > 0) {
            html += `<hr><div class="text-start text-danger small" style="max-height: 150px; overflow-y: auto;"><ul>` +
              res.data.errors.map(e => `<li>${e}</li>`).join('') +
              `</ul></div>`;
          }

          await Swal.fire({
            title: 'นำเข้าข้อมูลสำเร็จ!',
            html: html,
            icon: 'success',
            confirmButtonText: 'ตกลง'
          });

          const modalEl = document.getElementById('vendorImportModal');
          const modalInstance = bootstrap.Modal.getInstance(modalEl);
          if (modalInstance) modalInstance.hide();

          this.resetVendorImportState();
          this.fetchVendors();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถนำเข้าข้อมูลได้', 'error');
        }
      } catch (err) {
        console.error("Vendor batch import error:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล', 'error');
      } finally {
        this.isImportingVendors = false;
      }
    },
    exportItemsToExcel() {
      const itemsToExport = (this.filteredDisplayItems && this.filteredDisplayItems.length > 0) 
        ? this.filteredDisplayItems 
        : this.displayItems;
      if (!itemsToExport || itemsToExport.length === 0) {
        Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลรายการพัสดุสำหรับส่งออก', 'info');
        return;
      }

      const isPharma = this.isPharmacyWarehouse;
      const formatSendMoph = (val) => {
        if (val === null || val === undefined || val === '') return '';
        return Number(val) === 1 ? 'ส่งข้อมูลให้กระทรวง' : 'ไม่ส่ง';
      };

      const exportRows = itemsToExport.map((i, index) => {
        if (isPharma) {
          return {
            'ลำดับ': index + 1,
            'รหัสวัสดุ': i.item_code || '',
            'รหัสมาตรฐาน 24 หลัก': i.std_code_24 || '',
            'รหัสเดิม': i.old_code || '',
            'ชื่อวัสดุ': i.item_name || '',
            'ชื่อภาษาอังกฤษ': i.item_name_en || '',
            'คลัง': i.warehouse_name || '',
            'กลุ่มยา': i.category_name || '',
            'ประเภทเวชภัณฑ์': i.item_type_name || '',
            'บัญชี': i.ed_sub_list || '',
            'หมวดหมู่ อย.': i.product_cat_name || i.product_cat || '',
            'รูปแบบยา': i.dosage_form || '',
            'ความแรง': i.strength || '',
            'หน่วยนับ': i.unit_name || '',
            'ขนาดบรรจุ': Number(i.packing_size) || 1,
            'หน่วยมาตรฐาน (TH)': i.std_unit_th || '',
            'หน่วยมาตรฐาน (EN)': i.std_unit_en || '',
            'รหัส TMT GPU': i.tmt_gpu || '',
            'บาร์โค้ด': i.barcode || '',
            'ยา GPO': Number(i.is_gpo) === 1 ? 'ใช่' : 'ไม่ใช่',
            'ยาต้นแบบ (Original)': Number(i.is_original) === 1 ? 'ใช่' : 'ไม่ใช่',
            'ยาจ่ายฟรี': Number(i.is_free_dispense) === 1 ? 'จ่ายฟรี' : '',
            'ส่งข้อมูลให้กระทรวง': formatSendMoph(i.send_moph),
            'ส่งข้อมูลให้ DOS': Number(i.send_dos) === 1 ? 'ส่งข้อมูลให้DOS' : '',
            'ราคาต่อหน่วยมาตรฐาน': Number(i.standard_price) || 0,
            'ราคาซื้อล่าสุด': Number(i.last_purchase_price) || 0,
            'สต็อกขั้นต่ำ': Number(i.minimum_stock) || 0,
            'สต็อกสูงสุด': Number(i.maximum_stock) || 0,
            'จุดสั่งซื้อ': Number(i.reorder_point) || 0,
            'อัตราการใช้เฉลี่ย': Number(i.ave_rate) || 0,
            'คุม Lot': (i.lot_control == 1 || i.lot_control === true || i.lot_control === '1') ? 'ใช่' : 'ไม่ใช่',
            'คุมวันหมดอายุ': (i.expiry_control == 1 || i.expiry_control === true || i.expiry_control === '1') ? 'ใช่' : 'ไม่ใช่',
            'สถานะบัญชี': i.moph_status_note || '',
            'วันที่อัปเดตกระทรวง': i.moph_updated_text || '',
            'สถานะ': Number(i.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน'
          };
        }
        return {
          'ลำดับ': index + 1,
          'รหัสวัสดุ': i.item_code || '',
          'รหัสเดิม': i.old_code || '',
          'ชื่อวัสดุ': i.item_name || '',
          'ชื่อภาษาอังกฤษ': i.item_name_en || '',
          'คลัง': i.warehouse_name || '',
          'หมวดหมู่': i.category_name || '',
          'ประเภทพัสดุ': i.item_type_name || '',
          'หน่วยนับ': i.unit_name || '',
          'บาร์โค้ด': i.barcode || '',
          'ราคาต่อหน่วยมาตรฐาน': Number(i.standard_price) || 0,
          'ราคาซื้อล่าสุด': Number(i.last_purchase_price) || 0,
          'สต็อกขั้นต่ำ': Number(i.minimum_stock) || 0,
          'สต็อกสูงสุด': Number(i.maximum_stock) || 0,
          'จุดสั่งซื้อ': Number(i.reorder_point) || 0,
          'คุม Lot': (i.lot_control == 1 || i.lot_control === true || i.lot_control === '1') ? 'ใช่' : 'ไม่ใช่',
          'คุมวันหมดอายุ': (i.expiry_control == 1 || i.expiry_control === true || i.expiry_control === '1') ? 'ใช่' : 'ไม่ใช่',
          'สถานะ': Number(i.active) ? 'เปิดใช้งาน' : 'ปิดใช้งาน'
        };
      });

      const worksheet = XLSX.utils.json_to_sheet(exportRows);
      worksheet['!cols'] = isPharma
        ? [
            { wch: 8 },  // ลำดับ
            { wch: 14 }, // รหัสวัสดุ
            { wch: 24 }, // รหัสมาตรฐาน 24 หลัก
            { wch: 16 }, // รหัสเดิม
            { wch: 36 }, // ชื่อวัสดุ
            { wch: 26 }, // ชื่ออังกฤษ
            { wch: 16 }, // คลัง
            { wch: 24 }, // กลุ่มยา
            { wch: 24 }, // ประเภทเวชภัณฑ์
            { wch: 8 },  // บัญชี
            { wch: 32 }, // หมวดหมู่ อย.
            { wch: 12 }, // รูปแบบยา
            { wch: 16 }, // ความแรง
            { wch: 12 }, // หน่วยนับ
            { wch: 10 }, // ขนาดบรรจุ
            { wch: 16 }, // หน่วยมาตรฐาน TH
            { wch: 16 }, // หน่วยมาตรฐาน EN
            { wch: 14 }, // TMT GPU
            { wch: 16 }, // บาร์โค้ด
            { wch: 10 }, // GPO
            { wch: 12 }, // Original
            { wch: 12 }, // จ่ายฟรี
            { wch: 20 }, // ส่งข้อมูลให้กระทรวง
            { wch: 18 }, // ส่งข้อมูลให้ DOS
            { wch: 16 }, // ราคามาตรฐาน
            { wch: 16 }, // ราคาซื้อล่าสุด
            { wch: 12 }, // สต็อกต่ำสุด
            { wch: 12 }, // สต็อกสูงสุด
            { wch: 12 }, // จุดสั่งซื้อ
            { wch: 14 }, // อัตราใช้เฉลี่ย
            { wch: 10 }, // Lot
            { wch: 12 }, // Expiry
            { wch: 22 }, // สถานะบัญชี
            { wch: 18 }, // วันที่อัปเดตกระทรวง
            { wch: 12 }  // สถานะ
          ]
        : [
            { wch: 8 },  // ลำดับ
            { wch: 16 }, // รหัสวัสดุ
            { wch: 16 }, // รหัสเดิม
            { wch: 36 }, // ชื่อวัสดุ
            { wch: 25 }, // ชื่ออังกฤษ
            { wch: 20 }, // คลัง
            { wch: 24 }, // หมวดหมู่
            { wch: 24 }, // ประเภท
            { wch: 12 }, // หน่วยนับ
            { wch: 16 }, // บาร์โค้ด
            { wch: 16 }, // ราคามาตรฐาน
            { wch: 16 }, // ราคาซื้อล่าสุด
            { wch: 12 }, // สต็อกต่ำสุด
            { wch: 12 }, // สต็อกสูงสุด
            { wch: 12 }, // จุดสั่งซื้อ
            { wch: 10 }, // Lot
            { wch: 12 }, // Expiry
            { wch: 12 }  // สถานะ
          ];

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Items');

      const whName = this.activeWarehouse ? `_${this.activeWarehouse.name}` : '';
      const nowStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
      const fileName = `รายการวัสดุพัสดุ${whName}_${nowStr}.xlsx`;
      XLSX.writeFile(workbook, fileName);
    },
    openItemImportModal() {
      this.itemImportTargetWarehouse = this.effectiveWarehouseId || (this.activeWarehouse ? this.activeWarehouse.id : (this.warehouses[0]?.id || ''));
      this.itemImportUpdateExisting = true;
      this.resetItemImportState();
      const modalEl = document.getElementById('itemImportModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    resetItemImportState() {
      this.itemImportPreview = [];
      this.itemImportFileName = '';
      this.itemImportFileSize = '';
      if (this.$refs.itemExcelInput) {
        this.$refs.itemExcelInput.value = '';
      }
    },
    triggerItemFileInput() {
      if (this.$refs.itemExcelInput) {
        this.$refs.itemExcelInput.click();
      }
    },
    downloadItemTemplate() {
      const isPharmacy = this.isPharmacyWarehouse;

      let templateData = [];
      if (isPharmacy) {
        templateData = [
          {
            'ชื่อวัสดุ / ยา (จำเป็น)': 'Paracetamol 500 mg tab',
            'รหัสวัสดุ (เว้นว่างได้)': 'PARA001',
            'รหัสมาตรฐาน 24 หลัก': '1001450000044941102',
            'รหัส TMT GPU': '200142',
            'ชื่อภาษาอังกฤษ / Generic': 'Acetaminophen 500mg',
            'กลุ่มยา': 'Analgesics',
            'ประเภทเวชภัณฑ์': 'ยาในบัญชียาหลักแห่งชาติ',
            'บัญชี': 'ก',
            'หมวดหมู่ อย.': 'ยาแผนปัจจุบันที่ขึ้นทะเบียนตำรับยากับ อย.',
            'รูปแบบยา': 'tab',
            'ความแรง': '500 mg',
            'หน่วยนับ (จำเป็น)': 'เม็ด',
            'ขนาดบรรจุ': 1,
            'หน่วยมาตรฐาน (TH)': 'เม็ด',
            'หน่วยมาตรฐาน (EN)': 'TABLET',
            'ยา GPO': 'ใช่',
            'ยาต้นแบบ (Original)': 'ไม่ใช่',
            'ยาจ่ายฟรี': '',
            'ส่งข้อมูลให้กระทรวง': 'ส่งข้อมูลให้กระทรวง',
            'ส่งข้อมูลให้ DOS': 'ส่งข้อมูลให้DOS',
            'ราคาต่อหน่วยมาตรฐาน': 0.50,
            'ราคาซื้อล่าสุด': 0.45,
            'สต็อกขั้นต่ำ': 500,
            'สต็อกสูงสุด': 5000,
            'จุดสั่งซื้อ': 1000,
            'คุม Lot (ใช่/ไม่ใช่)': 'ใช่',
            'คุมวันหมดอายุ (ใช่/ไม่ใช่)': 'ใช่',
            'บาร์โค้ด': '8851234567890'
          },
          {
            'ชื่อวัสดุ / ยา (จำเป็น)': 'Amoxicillin 500 mg cap',
            'รหัสวัสดุ (เว้นว่างได้)': 'AMOX001',
            'รหัสมาตรฐาน 24 หลัก': '',
            'รหัส TMT GPU': '201143',
            'ชื่อภาษาอังกฤษ / Generic': 'Amoxicillin Trihydrate',
            'กลุ่มยา': 'Anti-Infectives',
            'ประเภทเวชภัณฑ์': 'ยาในบัญชียาหลักแห่งชาติ',
            'บัญชี': 'ก',
            'หมวดหมู่ อย.': 'ยาแผนปัจจุบันที่ขึ้นทะเบียนตำรับยากับ อย.',
            'รูปแบบยา': 'cap',
            'ความแรง': '500 mg',
            'หน่วยนับ (จำเป็น)': 'แคปซูล',
            'ขนาดบรรจุ': 1,
            'หน่วยมาตรฐาน (TH)': 'แคปซูล',
            'หน่วยมาตรฐาน (EN)': 'CAPSULE',
            'ยา GPO': 'ไม่ใช่',
            'ยาต้นแบบ (Original)': 'ไม่ใช่',
            'ยาจ่ายฟรี': '',
            'ส่งข้อมูลให้กระทรวง': 'ไม่ส่ง',
            'ส่งข้อมูลให้ DOS': 'ส่งข้อมูลให้DOS',
            'ราคาต่อหน่วยมาตรฐาน': 1.20,
            'ราคาซื้อล่าสุด': 1.10,
            'สต็อกขั้นต่ำ': 200,
            'สต็อกสูงสุด': 2000,
            'จุดสั่งซื้อ': 500,
            'คุม Lot (ใช่/ไม่ใช่)': 'ใช่',
            'คุมวันหมดอายุ (ใช่/ไม่ใช่)': 'ใช่',
            'บาร์โค้ด': ''
          }
        ];
      } else {
        templateData = [
          {
            'ชื่อวัสดุ / ยา (จำเป็น)': 'หมึกพิมพ์ HP LaserJet 85A (CE285A)',
            'รหัสวัสดุ (เว้นว่างได้)': 'IT-TONER-01',
            'รหัสเดิม (เว้นว่างได้)': '',
            'ชื่อภาษาอังกฤษ / Generic': 'HP Toner Cartridge 85A',
            'หน่วยนับ (จำเป็น)': 'ตลับ',
            'หมวดหมู่ / กลุ่มยา': 'วัสดุคอมพิวเตอร์',
            'ประเภทเวชภัณฑ์': 'วัสดุสิ้นเปลือง',
            'ราคาต่อหน่วยมาตรฐาน': 1250.00,
            'ราคาซื้อล่าสุด': 1200.00,
            'สต็อกขั้นต่ำ': 2,
            'สต็อกสูงสุด': 10,
            'จุดสั่งซื้อ': 3,
            'คุม Lot (ใช่/ไม่ใช่)': 'ไม่ใช่',
            'คุมวันหมดอายุ (ใช่/ไม่ใช่)': 'ไม่ใช่',
            'บาร์โค้ด': ''
          },
          {
            'ชื่อวัสดุ / ยา (จำเป็น)': 'กระดาษถ่ายเอกสาร A4 80 แกรม (Double A)',
            'รหัสวัสดุ (เว้นว่างได้)': 'OFF-001',
            'รหัสเดิม (เว้นว่างได้)': '',
            'ชื่อภาษาอังกฤษ / Generic': 'Double A Paper A4 80gsm',
            'หน่วยนับ (จำเป็น)': 'รีม',
            'หมวดหมู่ / กลุ่มยา': 'เครื่องเขียนและอุปกรณ์สำนักงาน',
            'ประเภทเวชภัณฑ์': 'วัสดุสำนักงาน',
            'ราคาต่อหน่วยมาตรฐาน': 135.00,
            'ราคาซื้อล่าสุด': 130.00,
            'สต็อกขั้นต่ำ': 20,
            'สต็อกสูงสุด': 100,
            'จุดสั่งซื้อ': 30,
            'คุม Lot (ใช่/ไม่ใช่)': 'ไม่ใช่',
            'คุมวันหมดอายุ (ใช่/ไม่ใช่)': 'ไม่ใช่',
            'บาร์โค้ด': ''
          }
        ];
      }

      const worksheet = XLSX.utils.json_to_sheet(templateData);
      worksheet['!cols'] = isPharmacy
        ? [
            { wch: 34 }, { wch: 16 }, { wch: 24 }, { wch: 14 }, { wch: 26 },
            { wch: 22 }, { wch: 24 }, { wch: 8 },  { wch: 30 }, { wch: 10 },
            { wch: 12 }, { wch: 12 }, { wch: 10 }, { wch: 14 }, { wch: 14 },
            { wch: 10 }, { wch: 12 }, { wch: 10 }, { wch: 20 }, { wch: 16 },
            { wch: 16 }, { wch: 16 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
            { wch: 16 }, { wch: 18 }, { wch: 18 }
          ]
        : [
            { wch: 38 }, // ชื่อวัสดุ / ยา
            { wch: 18 }, // รหัสวัสดุ
            { wch: 16 }, // รหัสเดิม
            { wch: 30 }, // ชื่อภาษาอังกฤษ
            { wch: 14 }, // หน่วยนับ
            { wch: 25 }, // หมวดหมู่ / กลุ่มยา
            { wch: 25 }, // ประเภทเวชภัณฑ์
            { wch: 18 }, // ราคามาตรฐาน
            { wch: 16 }, // ราคาซื้อล่าสุด
            { wch: 14 }, // สต็อกขั้นต่ำ
            { wch: 14 }, // สต็อกสูงสุด
            { wch: 14 }, // จุดสั่งซื้อ
            { wch: 18 }, // คุม Lot
            { wch: 20 }, // คุม Exp
            { wch: 18 }  // บาร์โค้ด
          ];

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Items_Template');
      const suffix = isPharmacy ? 'คลังยา' : (this.activeWarehouse ? this.activeWarehouse.name : 'ทั่วไป');
      XLSX.writeFile(workbook, `แบบฟอร์มนำเข้ารายการวัสดุ_${suffix}.xlsx`);
    },
    onItemFileSelected(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.itemImportFileName = file.name;
      this.itemImportFileSize = (file.size / 1024).toFixed(1) + ' KB';

      const parseYesNoNull = (raw) => {
        const s = String(raw ?? '').trim().toLowerCase();
        if (!s) return null;
        if (['1', 'true', 'yes', 'ใช่', 'ส่งข้อมูลให้กระทรวง', 'ส่งข้อมูลให้dos', 'ส่ง', 'จ่ายฟรี'].includes(s)) return 1;
        if (['0', 'false', 'no', 'ไม่ใช่', 'ไม่ส่ง'].includes(s)) return 0;
        return null;
      };

      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '' });

          if (!rawJson || rawJson.length === 0) {
            Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลในไฟล์ Excel หรือแผ่นงานว่างเปล่า', 'warning');
            this.resetItemImportState();
            return;
          }

          this.itemImportPreview = rawJson.map((row, idx) => {
            const clean = {};
            for (const k in row) {
              clean[k.trim()] = String(row[k] ?? '').trim();
            }

            const name = clean['ชื่อวัสดุ / ยา (จำเป็น)']
              || clean['ชื่อวัสดุ / ยา']
              || clean['ชื่อวัสดุ']
              || clean['ชื่อยา']
              || clean['รายการวัสดุ']
              || clean['รายการ']
              || clean['ชื่อพัสดุ']
              || clean['item_name']
              || clean['name']
              || '';

            const code = clean['รหัสวัสดุ (เว้นว่างได้)']
              || clean['รหัสวัสดุ']
              || clean['รหัสยา']
              || clean['รหัสสินค้า']
              || clean['รหัสพัสดุ']
              || clean['item_code']
              || clean['code']
              || '';

            const stdCode24 = clean['รหัสมาตรฐาน 24 หลัก']
              || clean['รหัสเดิม / มาตรฐาน 24 หลัก']
              || clean['รหัสมาตรฐาน']
              || clean['std_code_24']
              || '';

            const oldCode = clean['รหัสเดิม (เว้นว่างได้)']
              || clean['รหัสเดิม']
              || clean['old_code']
              || stdCode24
              || '';

            const nameEn = clean['ชื่อภาษาอังกฤษ / Generic']
              || clean['ชื่อภาษาอังกฤษ']
              || clean['ชื่อสากล / Generic']
              || clean['ชื่อสามัญทางยา']
              || clean['generic_name']
              || clean['item_name_en']
              || clean['english_name']
              || '';

            const unit = clean['หน่วยนับ (จำเป็น)']
              || clean['หน่วยนับ']
              || clean['หน่วย']
              || clean['unit_name']
              || clean['unit']
              || '';

            const category = clean['หมวดหมู่ / กลุ่มยา']
              || clean['หมวดหมู่']
              || clean['กลุ่มยา']
              || clean['GR_Code']
              || clean['category_name']
              || clean['category']
              || clean['group_name']
              || '';

            const itemType = clean['ประเภทเวชภัณฑ์']
              || clean['ประเภทวัสดุ']
              || clean['ประเภทพัสดุ']
              || clean['ประเภท']
              || clean['DEF']
              || clean['item_type_name']
              || clean['item_type']
              || clean['type']
              || '';

            const stdPrice = parseFloat(clean['ราคาต่อหน่วยมาตรฐาน']
              || clean['ราคามาตรฐาน']
              || clean['ราคาต่อหน่วย']
              || clean['ราคา/หน่วย']
              || clean['ราคา']
              || clean['PRICE']
              || clean['standard_price']
              || clean['price']
              || '0') || 0;

            const lastPrice = parseFloat(clean['ราคาซื้อล่าสุด']
              || clean['ราคาซื้อ']
              || clean['last_purchase_price']
              || clean['last_price']
              || clean['cost']
              || '') || stdPrice;

            const minStock = parseInt(clean['สต็อกขั้นต่ำ']
              || clean['ยอดคงเหลือขั้นต่ำ']
              || clean['min_stock']
              || clean['minimum_stock']
              || '0', 10) || 0;

            const maxStock = parseInt(clean['สต็อกสูงสุด']
              || clean['max_stock']
              || clean['maximum_stock']
              || '0', 10) || 0;

            const reorderPoint = parseInt(clean['จุดสั่งซื้อ']
              || clean['reorder_point']
              || '0', 10) || minStock;

            const aveRate = parseFloat(clean['อัตราการใช้เฉลี่ย'] || clean['AVE_RATE'] || clean['ave_rate'] || '0') || 0;

            const lotVal = clean['คุม Lot (ใช่/ไม่ใช่)']
              || clean['คุม Lot']
              || clean['lot_control']
              || clean['lot']
              || '';
            const lotControl = parseYesNoNull(lotVal) ?? 0;

            const expVal = clean['คุมวันหมดอายุ (ใช่/ไม่ใช่)']
              || clean['คุมวันหมดอายุ']
              || clean['expiry_control']
              || clean['exp_control']
              || clean['expiry']
              || '';
            const expControl = parseYesNoNull(expVal) ?? 0;

            const tmtGpu = clean['รหัส TMT GPU']
              || clean['GPU']
              || clean['tmt_gpu']
              || '';
            const barcode = clean['บาร์โค้ด']
              || clean['barcode']
              || (clean['รหัส GPU / บาร์โค้ด'] || tmtGpu)
              || '';
            const brand = clean['ยี่ห้อ'] || clean['brand'] || '';
            const model = clean['รุ่น'] || clean['model'] || '';

            const dosageForm = clean['รูปแบบยา'] || clean['ชนิด'] || clean['dosage_form'] || '';
            const strength = clean['ความแรง'] || clean['strength'] || '';
            const edSubList = clean['บัญชี'] || clean['ed_sub_list'] || '';
            const productCat = clean['หมวดหมู่ อย.'] || clean['PRODUCT_CAT'] || clean['Combo168'] || clean['product_cat'] || '';
            const packingSize = parseInt(clean['ขนาดบรรจุ'] || clean['PACKING'] || clean['packing_size'] || '1', 10) || 1;
            const stdUnitTh = clean['หน่วยมาตรฐาน (TH)'] || clean['Combo160'] || clean['std_unit_th'] || '';
            const stdUnitEn = clean['หน่วยมาตรฐาน (EN)'] || clean['หน่วยนับ_1'] || clean['std_unit_en'] || '';

            const isGpo = parseYesNoNull(clean['ยา GPO'] ?? clean['GPO'] ?? clean['Gpo'] ?? clean['is_gpo'] ?? '');
            const isOriginal = parseYesNoNull(clean['ยาต้นแบบ (Original)'] ?? clean['Original'] ?? clean['is_original'] ?? '');
            const isFreeDispense = parseYesNoNull(clean['ยาจ่ายฟรี'] ?? clean['จ่ายฟรี'] ?? clean['Combo162'] ?? clean['is_free_dispense'] ?? '');
            const sendMoph = parseYesNoNull(clean['ส่งข้อมูลให้กระทรวง'] ?? clean['Combo197'] ?? clean['send_moph'] ?? '');
            const sendDos = parseYesNoNull(clean['ส่งข้อมูลให้ DOS'] ?? clean['ส่งข้อมูลให้DOS'] ?? clean['Combo199'] ?? clean['send_dos'] ?? '');
            const mophStatusNote = clean['สถานะบัญชี'] || clean['สถานะ'] || clean['moph_status_note'] || '';
            const mophUpdatedText = clean['วันที่อัปเดตกระทรวง'] || clean['D_UPDATE'] || clean['DATE_STATUS'] || clean['moph_updated_text'] || '';

            const isValid = name.length > 0;

            return {
              row_num: idx + 1,
              item_name: name,
              item_code: code,
              old_code: oldCode,
              std_code_24: stdCode24,
              item_name_en: nameEn,
              unit_name: unit,
              category_name: category,
              item_type_name: itemType,
              dosage_form: dosageForm,
              strength: strength,
              ed_sub_list: edSubList,
              product_cat: productCat,
              packing_size: packingSize,
              std_unit_th: stdUnitTh,
              std_unit_en: stdUnitEn,
              tmt_gpu: tmtGpu,
              is_gpo: isGpo,
              is_original: isOriginal,
              is_free_dispense: isFreeDispense,
              send_moph: sendMoph,
              send_dos: sendDos,
              moph_status_note: mophStatusNote,
              moph_updated_text: mophUpdatedText,
              standard_price: stdPrice,
              last_purchase_price: lastPrice,
              minimum_stock: minStock,
              maximum_stock: maxStock,
              reorder_point: reorderPoint,
              ave_rate: aveRate,
              lot_control: lotControl,
              expiry_control: expControl,
              barcode: barcode,
              brand: brand,
              model: model,
              is_valid: isValid,
              error_msg: isValid ? '' : 'ไม่มีชื่อวัสดุ/ยา (จำเป็น)'
            };
          });

        } catch (err) {
          console.error("Error parsing item excel:", err);
          Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอ่านไฟล์ Excel ได้ กรุณาตรวจสอบรูปแบบไฟล์', 'error');
          this.resetItemImportState();
        }
      };
      reader.readAsArrayBuffer(file);
    },
    async confirmItemImport() {
      const validItems = this.itemImportPreview.filter(item => item.is_valid);
      if (validItems.length === 0) {
        Swal.fire('แจ้งเตือน', 'ไม่มีรายการที่ถูกต้องพร้อมนำเข้า', 'warning');
        return;
      }

      const targetWhId = this.itemImportTargetWarehouse || (this.activeWarehouse ? this.activeWarehouse.id : null);
      if (!targetWhId) {
        Swal.fire('แจ้งเตือน', 'กรุณาเลือกคลังเป้าหมายที่จะนำเข้าข้อมูล', 'warning');
        return;
      }

      this.isImportingItems = true;
      try {
        const payload = {
          action: 'batch_import',
          warehouse_id: targetWhId,
          update_existing: this.itemImportUpdateExisting,
          items: validItems
        };

        const res = await axios.post('/api-digital/inventory/items.php', payload);
        if (res.data?.status === 'success') {
          let html = `<div class="text-start mb-2">${res.data.message}</div>`;
          if (res.data.errors && res.data.errors.length > 0) {
            html += `<hr><div class="text-start text-danger small" style="max-height: 150px; overflow-y: auto;"><ul>` +
              res.data.errors.map(e => `<li>${e}</li>`).join('') +
              `</ul></div>`;
          }

          await Swal.fire({
            title: 'นำเข้าข้อมูลสำเร็จ!',
            html: html,
            icon: 'success',
            confirmButtonText: 'ตกลง'
          });

          const modalEl = document.getElementById('itemImportModal');
          if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          }

          this.resetItemImportState();
          await Promise.all([
            this.fetchData(),
            typeof this.fetchUnits === 'function' ? this.fetchUnits() : Promise.resolve()
          ]);
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถนำเข้าข้อมูลได้', 'error');
        }
      } catch (err) {
        console.error("Item batch import error:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล', 'error');
      } finally {
        this.isImportingItems = false;
      }
    },
    // TMT Catalog Methods
    openTmtModal() {
      this.tmtModalQuery = '';
      this.quickTmtSearch('paracetamol');
      const modalEl = document.getElementById('tmtCatalogModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    quickTmtSearch(keyword) {
      this.tmtModalQuery = keyword;
      this.searchTmtModal();
    },
    onTmtModalSearchInput() {
      if (this.tmtModalDebounceTimer) clearTimeout(this.tmtModalDebounceTimer);
      this.tmtModalDebounceTimer = setTimeout(() => {
        this.searchTmtModal();
      }, 350);
    },
    async searchTmtModal() {
      const q = (this.tmtModalQuery || '').trim();
      if (!q) {
        this.tmtModalResults = [];
        return;
      }
      this.isSearchingTmtModal = true;
      try {
        const res = await axios.get(`/api-digital/inventory/tmt_search.php?q=${encodeURIComponent(q)}&limit=50`);
        if (res.data?.status === 'success') {
          this.tmtModalResults = res.data.data || [];
        }
      } catch (err) {
        console.error("Error searching TMT modal:", err);
      } finally {
        this.isSearchingTmtModal = false;
      }
    },
    async addTmtToInventory(drug) {
      if (!drug || !drug.tpucode) return;
      const whId = this.effectiveWarehouseId || (this.activeWarehouse ? this.activeWarehouse.id : 2);
      
      this.isAddingTmtItem = { ...this.isAddingTmtItem, [drug.tpucode]: true };
      try {
        const payload = {
          action: 'add_to_inventory',
          warehouse_id: whId,
          tpucode: drug.tpucode
        };
        const res = await axios.post('/api-digital/inventory/tmt_search.php', payload);
        if (res.data?.status === 'success' || res.data?.status === 'exists') {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: res.data?.status === 'success' ? 'success' : 'info',
            title: res.data?.message || 'เพิ่มเข้าคลังเรียบร้อยแล้ว',
            showConfirmButton: false,
            timer: 2500
          });
          // Refresh item list and units
          await Promise.all([
            this.fetchData(),
            typeof this.fetchUnits === 'function' ? this.fetchUnits() : Promise.resolve()
          ]);
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถเพิ่มยาเข้าคลังได้', 'error');
        }
      } catch (err) {
        console.error("Error adding TMT item:", err);
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการเพิ่มยา', 'error');
      } finally {
        this.isAddingTmtItem = { ...this.isAddingTmtItem, [drug.tpucode]: false };
      }
    },
    isTmtInInventory(tpucode) {
      if (!tpucode || !this.items) return false;
      const codeStr = String(tpucode).trim();
      return this.items.some(i => String(i.item_code).trim() === codeStr);
    },
    onTmtInput() {
      if (this.tmtDebounceTimer) clearTimeout(this.tmtDebounceTimer);
      const q = (this.tmtQuery || '').trim();
      if (q.length === 0) {
        this.tmtResults = [];
        this.tmtShowDropdown = false;
        return;
      }
      this.tmtDebounceTimer = setTimeout(() => {
        this.searchTmtDropdown();
      }, 300);
    },
    async searchTmtDropdown() {
      const q = (this.tmtQuery || '').trim();
      if (!q) return;
      this.isSearchingTmt = true;
      this.tmtShowDropdown = true;
      try {
        const res = await axios.get(`/api-digital/inventory/tmt_search.php?q=${encodeURIComponent(q)}&limit=15`);
        if (res.data?.status === 'success') {
          this.tmtResults = res.data.data || [];
        }
      } catch (err) {
        console.error("Error searching TMT dropdown:", err);
      } finally {
        this.isSearchingTmt = false;
      }
    },
    selectTmtDrug(drug) {
      if (!drug) return;
      this.tmtSelectedDrug = drug;
      this.tmtShowDropdown = false;
      this.tmtQuery = drug.trade_name ? `${drug.trade_name} (TPU: ${drug.tpucode})` : drug.tpucode;

      // Populate itemForm
      this.itemForm.item_code = drug.tpucode;
      const tradeName = drug.trade_name ? drug.trade_name.trim() : '';
      const strength = drug.strength ? drug.strength.trim() : '';
      const activeIng = drug.active_ingredient ? drug.active_ingredient.trim() : '';

      this.itemForm.item_name = tradeName ? (tradeName + (strength ? ` ${strength}` : '')) : (drug.fsn || '');
      this.itemForm.item_name_en = activeIng ? (activeIng + (strength ? ` ${strength}` : '')) : '';
      this.itemForm.brand = drug.manufacturer || '';
      this.itemForm.lot_control = 1;
      this.itemForm.expiry_control = 1;

      // Match unit
      const targetUnitName = drug.disp_unit_th || drug.disp_unit;
      if (targetUnitName && this.units && this.units.length > 0) {
        const foundUnit = this.units.find(u => 
          u.name.toLowerCase() === targetUnitName.toLowerCase() || 
          u.name.toLowerCase() === (drug.disp_unit || '').toLowerCase()
        );
        if (foundUnit) {
          this.itemForm.unit_id = foundUnit.id;
        }
      }
    },
    clearTmtSelection() {
      this.tmtSelectedDrug = null;
      this.tmtQuery = '';
      this.tmtResults = [];
      this.tmtShowDropdown = false;
    },
    // Units Methods
    async fetchUnits() {
      this.loadingUnits = true;
      try {
        const params = new URLSearchParams();
        if (this.effectiveWarehouseId) {
          params.append('warehouse_id', this.effectiveWarehouseId);
        }
        const res = await axios.get(`/api-digital/inventory/units.php?${params.toString()}`);
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
          warehouse_id: unit.warehouse_id !== undefined ? unit.warehouse_id : (this.effectiveWarehouseId ? parseInt(this.effectiveWarehouseId) : null),
          code: unit.code || '',
          name: unit.name || '',
          active: unit.active !== undefined ? Number(unit.active) : 1
        };
      } else {
        this.isEditUnit = false;
        this.unitForm = {
          id: null,
          warehouse_id: this.effectiveWarehouseId ? parseInt(this.effectiveWarehouseId) : null,
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
        const payload = { 
          action: 'seed_defaults',
          warehouse_id: this.effectiveWarehouseId ? parseInt(this.effectiveWarehouseId) : null
        };
        const res = await axios.post('/api-digital/inventory/units.php', payload);
        if (res.data?.status === 'success') {
          Swal.fire({
            title: 'สำเร็จ!',
            text: res.data.message,
            icon: 'success'
          });
          this.fetchUnits();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
      } catch (err) {
        console.error("Seed units error:", err);
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
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
    },
    async fetchPharmacyMasters() {
      try {
        const res = await axios.get('/api-digital/inventory/pharmacy_masters.php');
        if (res.data?.status === 'success') {
          this.dosageForms = res.data.dosage_forms || [];
          this.edLists = res.data.ed_lists || [];
          this.productCategories = res.data.product_categories || [];
        }
      } catch (err) {
        console.error("Error fetching pharmacy masters:", err);
      }
    },
    // Dosage Form Methods
    openDosageFormModal(df = null) {
      if (df && df.id) {
        this.isEditDosageForm = true;
        this.dosageFormForm = { id: df.id, code: df.code, name: df.name, name_en: df.name_en || '', active: Number(df.active) };
      } else {
        this.isEditDosageForm = false;
        this.dosageFormForm = { id: null, code: '', name: '', name_en: '', active: 1 };
      }
      const modalEl = document.getElementById('dosageFormModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    async saveDosageForm() {
      if (!this.dosageFormForm.code || !this.dosageFormForm.name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสย่อและชื่อรูปแบบยา', 'warning');
        return;
      }
      this.isSavingDosageForm = true;
      try {
        const payload = { ...this.dosageFormForm, table: 'dosage_forms' };
        const method = this.isEditDosageForm ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/pharmacy_masters.php', payload);
        if (res.data?.status === 'success') {
          Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: res.data.message || 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1800
          });
          const modalEl = document.getElementById('dosageFormModal');
          if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          }
          await this.fetchPharmacyMasters();
          await this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.isSavingDosageForm = false;
      }
    },
    async deleteDosageForm(df) {
      if (!df || !df.id) return;
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบรูปแบบยา?',
        text: `คุณต้องการลบรูปแบบยา [${df.code}] ${df.name} หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย'
      });
      if (!confirm.isConfirmed) return;
      try {
        const res = await axios.delete(`/api-digital/inventory/pharmacy_masters.php?table=dosage_forms&id=${df.id}`);
        if (res.data?.status === 'success') {
          Swal.fire('ลบสำเร็จ!', res.data.message, 'success');
          await this.fetchPharmacyMasters();
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการลบ', 'error');
      }
    },
    // ED List Methods
    openEdListModal(ed = null) {
      if (ed && ed.id) {
        this.isEditEdList = true;
        this.edListForm = { id: ed.id, code: ed.code, name: ed.name, description: ed.description || '', sort_order: Number(ed.sort_order || 0), active: Number(ed.active) };
      } else {
        this.isEditEdList = false;
        this.edListForm = { id: null, code: '', name: '', description: '', sort_order: this.edLists.length + 1, active: 1 };
      }
      const modalEl = document.getElementById('edListModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    async saveEdList() {
      if (!this.edListForm.code || !this.edListForm.name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสและชื่อบัญชียาหลัก', 'warning');
        return;
      }
      this.isSavingEdList = true;
      try {
        const payload = { ...this.edListForm, table: 'ed_lists' };
        const method = this.isEditEdList ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/pharmacy_masters.php', payload);
        if (res.data?.status === 'success') {
          Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: res.data.message || 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1800
          });
          const modalEl = document.getElementById('edListModal');
          if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          }
          await this.fetchPharmacyMasters();
          await this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.isSavingEdList = false;
      }
    },
    async deleteEdList(ed) {
      if (!ed || !ed.id) return;
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบบัญชีย่อย?',
        text: `คุณต้องการลบ [${ed.code}] ${ed.name} หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย'
      });
      if (!confirm.isConfirmed) return;
      try {
        const res = await axios.delete(`/api-digital/inventory/pharmacy_masters.php?table=ed_lists&id=${ed.id}`);
        if (res.data?.status === 'success') {
          Swal.fire('ลบสำเร็จ!', res.data.message, 'success');
          await this.fetchPharmacyMasters();
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการลบ', 'error');
      }
    },
    // Product Category Methods
    openProductCategoryModal(pc = null) {
      if (pc && pc.id) {
        this.isEditProductCategory = true;
        this.productCategoryForm = { id: pc.id, code: pc.code, name: pc.name, description: pc.description || '', active: Number(pc.active) };
      } else {
        this.isEditProductCategory = false;
        this.productCategoryForm = { id: null, code: '', name: '', description: '', active: 1 };
      }
      const modalEl = document.getElementById('productCategoryModal');
      if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
      }
    },
    async saveProductCategory() {
      if (!this.productCategoryForm.code || !this.productCategoryForm.name) {
        Swal.fire('แจ้งเตือน', 'กรุณากรอกรหัสและชื่อหมวด อย.', 'warning');
        return;
      }
      this.isSavingProductCategory = true;
      try {
        const payload = { ...this.productCategoryForm, table: 'product_categories' };
        const method = this.isEditProductCategory ? 'put' : 'post';
        const res = await axios[method]('/api-digital/inventory/pharmacy_masters.php', payload);
        if (res.data?.status === 'success') {
          Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: res.data.message || 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1800
          });
          const modalEl = document.getElementById('productCategoryModal');
          if (modalEl) {
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
          }
          await this.fetchPharmacyMasters();
          await this.fetchData();
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
      } finally {
        this.isSavingProductCategory = false;
      }
    },
    async deleteProductCategory(pc) {
      if (!pc || !pc.id) return;
      const confirm = await Swal.fire({
        title: 'ยืนยันการลบหมวดหมู่ อย.?',
        text: `คุณต้องการลบ [${pc.code}] ${pc.name} หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย'
      });
      if (!confirm.isConfirmed) return;
      try {
        const res = await axios.delete(`/api-digital/inventory/pharmacy_masters.php?table=product_categories&id=${pc.id}`);
        if (res.data?.status === 'success') {
          Swal.fire('ลบสำเร็จ!', res.data.message, 'success');
          await this.fetchPharmacyMasters();
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data?.message || 'ลบไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', err.response?.data?.message || 'เกิดข้อผิดพลาดในการลบ', 'error');
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
