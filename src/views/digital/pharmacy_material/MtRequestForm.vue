<template>
  <div class="request-form-container container-fluid px-4 px-md-5 pt-3 fade-in">
    <!-- Top Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 custom-breadcrumb">
          <li class="breadcrumb-item">
            <router-link to="/pharmacy-admin" class="text-decoration-none d-flex align-items-center">
              <i class="bi bi-house-fill me-1"></i>หน้าหลักวัสดุ
            </router-link>
          </li>
          <li class="breadcrumb-item active fw-medium" aria-current="page">
            แบบฟอร์มขอเบิกวัสดุ
          </li>
        </ol>
      </nav>

      <!-- Action Buttons -->
      <div class="d-flex gap-2">
        <router-link
          v-if="isAdmin"
          to="/pharmacy-admin/requests"
          class="btn btn-primary shadow-sm border rounded-pill px-4 hover-lift fw-medium text-sm"
        >
          <i class="bi bi-ui-checks-grid me-2"></i>จัดการคำขอ (Admin)
        </router-link>
        <router-link
          to="/home-backoffice"
          class="btn btn-light shadow-sm border rounded-pill px-4 hover-lift fw-medium text-sm"
        >
          <i class="bi bi-arrow-left me-2 text-primary"></i>กลับหน้าหลัก
        </router-link>
      </div>
    </div>

    <!-- Centered Header Section -->
    <div class="text-center mb-5 fade-in">
      <div class="d-inline-flex align-items-center justify-content-center gap-3 mb-2 title-animate">
        <div class="icon-square bg-gradient-primary text-white shadow-sm">
          <i class="bi bi-clipboard2-check-fill fs-4"></i>
        </div>
        <h3 class="fw-black text-dark m-0">แบบฟอร์มขอเบิกยา</h3>
      </div>
      <p class="text-muted mb-0 fs-6">
        โปรดระบุวัสดุที่ต้องการเบิกและข้อมูลของท่านให้ครบถ้วนเพื่อความรวดเร็วในการตรวจสอบ
      </p>
    </div>

    <!-- Form Content -->
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        <div class="card border-0 shadow-lg rounded-4 glass-card mb-5">
          <div class="card-body p-4 p-md-5">
            <form @submit.prevent="submitRequest">
              <div class="row g-4">
                <!-- Section Title: Requester Info -->
                <div class="col-12 mb-2">
                  <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm"
                      ><i class="bi bi-1-circle fs-6"></i
                    ></span>
                    <h5 class="fw-bold text-dark m-0">ข้อมูลผู้เบิก</h5>
                  </div>
                </div>

                <!-- Requester Info Fields -->
                <div class="col-md-6 mt-0">
                  <label
                    class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1"
                    >ชื่อผู้เบิก <span class="text-danger">*</span></label
                  >
                  <div class="input-group input-group-custom shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary px-3">
                      <i class="bi bi-person-fill"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.requester_name"
                      class="form-control border-start-0 ps-0 form-control-lg fs-6"
                      required
                      placeholder="ระบุชื่อ-นามสกุล"
                      list="requester_list"
                    />
                  </div>
                  <datalist id="requester_list">
                    <option
                      v-for="(req, index) in pastRequesters"
                      :key="index"
                      :value="req.name"
                    ></option>
                  </datalist>
                </div>

                <div class="col-md-6 mt-0">
                  <label
                    class="form-label fw-bold text-secondary small text-uppercase letter-spacing-1"
                    >หน่วยงาน/แผนก <span class="text-danger">*</span></label
                  >
                  <div class="input-group input-group-custom shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary px-3">
                      <i class="bi bi-building"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.department"
                      class="form-control border-start-0 ps-0 form-control-lg fs-6"
                      required
                      placeholder="ระบุหน่วยงาน"
                      list="department_list"
                    />
                  </div>
                  <datalist id="department_list">
                    <option
                      v-for="(dept, index) in pastDepartments"
                      :key="index"
                      :value="dept"
                    ></option>
                  </datalist>
                </div>

                <div class="col-12 mt-4 pt-4 border-top border-light-subtle">
                  <div class="row g-4">
                    <!-- Left Pane: Materials List -->
                    <div class="col-lg-7">
                      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                         <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                           <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-2-circle fs-6"></i></span>
                           รายการวัสดุสำนักงาน
                         </h5>
                         <div class="d-flex gap-2 flex-wrap justify-content-end">
                           <select v-model="selectedType" class="form-select shadow-sm border-light-subtle" style="max-width: 160px; font-size: 0.9rem;">
                             <option value="">ทุกประเภท</option>
                             <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
                           </select>
                           <div class="input-group shadow-sm" style="max-width: 280px;">
                             <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                             <input type="text" v-model="materialSearchQuery" class="form-control border-start-0 ps-0" placeholder="ค้นหาชื่อ หรือรหัสวัสดุ...">
                           </div>
                         </div>
                      </div>
                      
                      <div class="table-responsive border border-light-subtle rounded-4 bg-white shadow-sm" style="max-height: 500px; overflow-y: auto;">
                         <table class="table table-hover align-middle mb-0">
                           <thead class="table-light sticky-top shadow-sm" style="z-index: 10;">
                             <tr>
                               <th width="12%" class="ps-4">รูปภาพ</th>
                               <th width="33%">รหัส / ชื่อวัสดุ</th>
                               <th width="20%" class="text-center">ประเภท</th>
                               <th width="15%" class="text-center">คงเหลือ</th>
                               <th width="20%" class="text-center pe-4">แอคชัน</th>
                             </tr>
                           </thead>
                           <tbody>
                             <tr v-if="filteredMaterials.length === 0">
                               <td colspan="5" class="text-center py-5 text-muted">
                                 <i class="bi bi-search fs-1 mb-2 d-block text-light"></i>
                                 ไม่พบรายการวัสดุที่ค้นหา
                               </td>
                             </tr>
                             <tr v-for="mat in filteredMaterials" :key="mat.id" class="fade-in">
                               <td class="ps-4">
                                 <img v-if="mat.image_path" :src="getImageUrl(mat.image_path)" class="rounded object-fit-cover shadow-sm border" style="width: 45px; height: 45px;" @error="e => e.target.style.display = 'none'">
                                 <div v-else class="rounded bg-light d-flex align-items-center justify-content-center text-muted shadow-sm border" style="width: 45px; height: 45px;">
                                   <i class="bi bi-image"></i>
                                 </div>
                               </td>
                               <td>
                                 <div class="fw-bold text-dark fs-6">{{ mat.name }}</div>
                                 <div class="small text-muted">{{ mat.code }}</div>
                               </td>
                               <td class="text-center">
                                 <span class="badge bg-secondary rounded-pill fw-normal shadow-sm">{{ mat.type || '-' }}</span>
                               </td>
                               <td class="text-center">
                                 <span class="badge bg-light text-dark border px-2 py-1 fs-6">{{ mat.balance }} <span class="fw-normal text-muted ms-1">{{ mat.unit }}</span></span>
                               </td>
                               <td class="text-center pe-4">
                                 <button type="button" class="btn btn-sm rounded-pill fw-bold w-100 shadow-sm d-flex justify-content-center align-items-center gap-1" 
                                         :class="isMaterialInCart(mat.id) ? 'btn-secondary' : 'btn-outline-primary hover-lift'"
                                         :disabled="isMaterialInCart(mat.id)"
                                         @click="addToCart(mat)">
                                   <i class="bi" :class="isMaterialInCart(mat.id) ? 'bi-check2' : 'bi-plus-lg'"></i> 
                                   {{ isMaterialInCart(mat.id) ? 'เลือกแล้ว' : 'เบิก' }}
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
                        <div class="d-flex align-items-center justify-content-between mb-4">
                          <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <span class="badge bg-success text-white rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-cart-check fs-6"></i></span>
                            ตะกร้าเบิกวัสดุ
                          </h5>
                          <span class="badge bg-primary rounded-pill shadow-sm fs-6 px-3">{{ form.items.length }} รายการ</span>
                        </div>

                        <!-- Empty State -->
                        <div v-if="form.items.length === 0" class="text-center py-5 my-auto fade-in">
                          <div class="icon-square bg-white text-muted shadow-sm mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-cart-x fs-1"></i>
                          </div>
                          <h6 class="fw-bold text-dark mb-1">ยังไม่ได้เลือกรายการวัสดุ</h6>
                          <p class="text-muted small mb-0">กรุณาคลิกปุ่ม "เบิก" จากรายการด้านซ้าย</p>
                        </div>

                        <!-- Cart Items -->
                        <div v-else class="cart-items-container flex-grow-1" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                          <div v-for="(item, index) in form.items" :key="item.material_id" class="card border-0 shadow-sm mb-3 rounded-4 fade-in overflow-hidden">
                            <div class="card-body p-3">
                              <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="fw-bold text-dark lh-sm pe-2">
                                  <span class="badge bg-dark rounded-circle me-1 shadow-sm" style="font-size: 0.7rem; padding: 0.35em 0.5em;">{{ index + 1 }}</span>
                                  {{ getSelectedMaterial(item.material_id)?.name || 'ไม่ทราบชื่อ' }}
                                </div>
                                <button type="button" class="btn btn-sm btn-light text-danger shadow-sm rounded-circle p-1 hover-lift flex-shrink-0" @click="removeItem(index)" style="width: 32px; height: 32px;">
                                  <i class="bi bi-trash-fill"></i>
                                </button>
                              </div>
                              <div class="d-flex justify-content-between align-items-center mt-3 bg-light rounded-3 p-2 border">
                                <span class="small fw-bold text-secondary">ระบุจำนวน:</span>
                                <div class="d-flex align-items-center gap-2">
                                  <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden" style="width: 120px;">
                                    <button type="button" class="btn btn-primary px-2" @click="item.quantity > 1 ? item.quantity-- : null"><i class="bi bi-dash"></i></button>
                                    <input type="number" v-model.number="item.quantity" class="form-control text-center fw-bold border-primary text-primary px-0 hide-arrows" min="1" required style="max-width: 50px;">
                                    <button type="button" class="btn btn-primary px-2" @click="item.quantity++"><i class="bi bi-plus"></i></button>
                                  </div>
                                  <span class="small fw-bold text-dark w-25 text-end pe-2">{{ getSelectedMaterial(item.material_id)?.unit || '' }}</span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-5 mb-2 text-center d-flex justify-content-center gap-3">
                  <button
                    v-if="editingRequestNo"
                    type="button"
                    class="btn btn-light btn-lg px-4 py-3 rounded-pill shadow-sm fw-bold border hover-lift"
                    @click="cancelEdit"
                    :disabled="loading"
                  >
                    <i class="bi bi-x-circle me-1"></i> ยกเลิกการแก้ไข
                  </button>
                  <button
                    type="submit"
                    class="btn btn-primary btn-lg px-5 py-3 rounded-pill shadow-lg submit-btn fw-bold"
                    :disabled="loading || materials.length === 0"
                  >
                    <div class="d-flex align-items-center gap-2" v-if="!loading">
                      <i class="bi" :class="editingRequestNo ? 'bi-save-fill' : 'bi-send-fill'" fs-5></i>
                      <span>{{ editingRequestNo ? 'บันทึกการแก้ไขคำขอ' : 'ส่งคำขอเบิกวัสดุ' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2" v-else>
                      <span
                        class="spinner-border spinner-border-sm"
                        role="status"
                        aria-hidden="true"
                      ></span>
                      <span>กำลังประมวลผล...</span>
                    </div>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- History Section -->
        <div class="card border-0 shadow-lg rounded-4 glass-card mb-5 fade-in" style="animation-delay: 0.2s;">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-2 mb-4">
              <span class="badge bg-info text-white rounded-pill px-2 py-1 shadow-sm"><i class="bi bi-clock-history fs-6"></i></span>
              <h5 class="fw-bold text-dark m-0">ประวัติการขอเบิกวัสดุ</h5>
            </div>
            
            <div class="mb-3">
              <input type="text" v-model="searchQuery" class="form-control" placeholder="ค้นหาชื่อผู้เบิก, หน่วยงาน, หรือรายการวัสดุ...">
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>วันที่ขอเบิก</th>
                    <th>ผู้เบิก</th>
                    <th>หน่วยงาน</th>
                    <th>รายการวัสดุ</th>
                    <th class="text-center">จำนวน</th>
                    <th class="text-center">สถานะ</th>
                    <th>หมายเหตุ</th>
                    <th class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredRequests.length === 0">
                    <td colspan="8" class="text-center py-4 text-muted">
                      <span v-if="!isAdmin && !form.requester_name">กรุณาระบุชื่อผู้เบิกด้านบนเพื่อดูประวัติของท่าน</span>
                      <span v-else>ไม่พบประวัติการขอเบิก</span>
                    </td>
                  </tr>
                  <tr v-for="req in filteredRequests" :key="req.id">
                    <td>{{ req.request_date }}</td>
                    <td>{{ req.requester_name }}</td>
                    <td>{{ req.department }}</td>
                    <td>
                      <div v-for="(item, i) in req.items" :key="i" class="mb-1">
                        {{ item.material_name }} <small class="text-muted">({{ item.material_code }})</small>
                      </div>
                    </td>
                    <td class="text-center fw-bold">
                      <div v-for="(item, i) in req.items" :key="i" class="mb-1">
                        {{ item.quantity }}
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="badge rounded-pill px-3 py-2 fw-normal" :class="getStatusBadge(req.status)">
                        <i :class="getStatusIcon(req.status)" class="me-1"></i>
                        {{ getStatusText(req.status) }}
                      </span>
                    </td>
                    <td>{{ req.items && req.items.length > 0 ? req.items[0].admin_note || '-' : '-' }}</td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2" v-if="req.status === 'pending'">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm hover-lift" @click="editRequest(req)" title="แก้ไขคำขอ">
                          <i class="bi bi-pencil-square"></i> แก้ไข
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm hover-lift" @click="cancelRequest(req)" title="ยกเลิกคำขอ">
                          <i class="bi bi-trash"></i> ยกเลิก
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
    <!-- Vue Preview Modal -->
    <div v-if="showPreviewModal" class="modal-backdrop fade show" style="z-index: 1050;"></div>
    
    <div v-if="showPreviewModal" class="modal fade show d-block" tabindex="-1" style="z-index: 1055;" aria-modal="true" role="dialog">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden fade-in">
          <div class="modal-header bg-gradient-primary text-white border-0 py-3 px-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>ตรวจสอบรายการขอเบิก</h5>
            <button type="button" class="btn-close btn-close-white" @click="showPreviewModal = false" :disabled="loading"></button>
          </div>
          <div class="modal-body p-4 p-md-5 bg-light">
            <!-- Receipt/Slip Design -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-0 position-relative">
              <!-- Receipt decorative top edge -->
              <div class="position-absolute top-0 start-0 w-100 border-top border-3 border-primary" style="border-radius: 16px 16px 0 0;"></div>
              
              <div class="text-center mb-4 pb-3 border-bottom border-dashed">
                <div class="icon-square bg-success-subtle text-success mx-auto mb-3" style="width: 60px; height: 60px; border-radius: 50%;">
                  <i class="bi bi-check2-circle fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark">ใบสรุปรายการขอเบิกวัสดุ</h4>
                <p class="text-muted mb-0">กรุณาตรวจสอบความถูกต้องก่อนกดยืนยัน</p>
              </div>
              
              <div class="row mb-4 g-3 bg-light rounded-3 p-3 mx-0 border">
                <div class="col-sm-6 border-sm-end">
                  <div class="small text-muted mb-1"><i class="bi bi-person me-1"></i>ชื่อผู้เบิก</div>
                  <div class="fw-bold text-dark fs-5">{{ form.requester_name }}</div>
                </div>
                <div class="col-sm-6 text-sm-end">
                  <div class="small text-muted mb-1"><i class="bi bi-building me-1"></i>หน่วยงาน/แผนก</div>
                  <div class="fw-bold text-dark fs-5">{{ form.department }}</div>
                </div>
              </div>
              
              <h6 class="fw-bold mb-3"><i class="bi bi-box-seam me-2 text-primary"></i>รายการวัสดุ ({{ form.items.length }} รายการ)</h6>
              <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="text-center" width="10%">ลำดับ</th>
                      <th width="60%">รายการ</th>
                      <th class="text-center" width="30%">จำนวนที่เบิก</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in form.items" :key="index">
                      <td class="text-center text-muted">{{ index + 1 }}</td>
                      <td>
                        <div class="fw-bold text-dark">{{ getSelectedMaterial(item.material_id)?.name }}</div>
                        <div class="small text-muted">{{ getSelectedMaterial(item.material_id)?.code }}</div>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                          {{ item.quantity }} {{ getSelectedMaterial(item.material_id)?.unit }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light pt-0 pb-4 px-4 px-md-5 justify-content-center gap-3">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 hover-lift fw-medium" @click="showPreviewModal = false" :disabled="loading">
              <i class="bi bi-arrow-left me-1"></i> กลับไปแก้ไข
            </button>
            <button type="button" class="btn btn-success shadow-sm rounded-pill px-5 py-2 fw-bold hover-lift" @click="confirmSubmit" :disabled="loading">
              <span v-if="!loading"><i class="bi" :class="editingRequestNo ? 'bi-save-fill' : 'bi-send-check-fill'"></i> <span class="ms-2">{{ editingRequestNo ? 'ยืนยันการแก้ไข' : 'ยืนยันการขอเบิก' }}</span></span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                กำลังประมวลผล...
              </span>
            </button>
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
  name: 'MtRequestForm',
  data() {
    return {
      materials: [],
      pastRequesters: [],
      pastDepartments: [],
      form: {
        requester_name: localStorage.getItem('user_name') || localStorage.getItem('last_requester_name_pharmacy') || '',
        department: localStorage.getItem('user_department') || localStorage.getItem('last_department_pharmacy') || '',
        items: []
      },
      loading: false,
      requests: [],
      searchQuery: '',
      materialSearchQuery: '',
      selectedType: '',
      showPreviewModal: false,
      editingRequestNo: null,
      isAdminUser: false
    };
  },
  computed: {
    isAdmin() {
      return this.isAdminUser;
    },
    uniqueCategories() {
      const categories = this.materials.map(m => m.type).filter(t => t);
      return [...new Set(categories)].sort();
    },
    filteredMaterials() {
      let mats = this.materials;
      
      if (this.selectedType) {
        mats = mats.filter(m => m.type === this.selectedType);
      }
      
      if (!this.materialSearchQuery) return mats;
      
      const q = this.materialSearchQuery.toLowerCase().trim();
      return mats.filter(m => 
        (m.name && m.name.toLowerCase().includes(q)) || 
        (m.code && m.code.toLowerCase().includes(q))
      );
    },
    filteredRequests() {
      let reqs = this.requests;

      if (!this.isAdmin) {
        if (!this.form.requester_name) {
          return [];
        }
        const requester = this.form.requester_name.toLowerCase().trim();
        reqs = reqs.filter(req => req.requester_name && req.requester_name.toLowerCase().trim() === requester);
      }

      if (!this.searchQuery) return reqs;
      const q = this.searchQuery.toLowerCase();
      return reqs.filter(req => {
        const matchName = req.requester_name && req.requester_name.toLowerCase().includes(q);
        const matchDept = req.department && req.department.toLowerCase().includes(q);
        const matchItems = req.items && req.items.some(it => it.material_name && it.material_name.toLowerCase().includes(q));
        return matchName || matchDept || matchItems;
      });
    }
  },
  mounted() {
    this.checkAdminStatus();
    this.fetchMaterials();
    this.fetchRequestersAndDepts();
    this.fetchRequests();
  },
  watch: {
    'form.requester_name'(newName) {
      if (newName) {
        const found = this.pastRequesters.find((req) => req.name === newName);
        if (found && found.department) {
          this.form.department = found.department;
        }
      }
    }
  },
  methods: {
    async checkAdminStatus() {
      try {
        const token = localStorage.getItem('user_token');
        if (!token) return;
        const config = { headers: { Authorization: `Bearer ${token}` } };
        const response = await axios.get('/api-hosoffice/get_user_profile.php', config);
        if (response.data && response.data.status === 'success') {
          const accessUser = response.data.access_user ? response.data.access_user.split(':') : [];
          this.isAdminUser = accessUser.includes('administrator') || accessUser.includes('menu_gm_material_manage');
          
          if (!this.form.department && response.data.department) {
            this.form.department = response.data.department;
          }
        }
      } catch (error) {
        console.error('Error checking admin status', error);
      }
    },
    async fetchMaterials() {
      try {
        const res = await axios.get('/api-digital/pharmacy_material/pharmacy_get_materials.php');
        if (res.data.status === 'success') {
          // ดึงเฉพาะวัสดุที่มีของเหลือ (balance > 0) และเปิดใช้งานอยู่ (is_active != 0)
          this.materials = res.data.data
            .filter((mat) => mat.balance > 0 && mat.is_active != 0)
            .sort((a, b) => b.balance - a.balance);
            
          if (this.materials.length === 0) {
            Swal.fire({
              icon: 'info',
              title: 'ไม่มีวัสดุพร้อมเบิก',
              text: 'ขณะนี้ไม่มีวัสดุใดๆ ในสต๊อกที่สามารถเบิกได้'
            });
          }
        }
      } catch (error) {
        console.error('Error fetching materials:', error);
      }
    },
    async fetchRequestersAndDepts() {
      try {
        const res = await axios.get('/api-digital/pharmacy_material/pharmacy_get_requesters_depts.php');
        if (res.data.success) {
          this.pastRequesters = res.data.requesters || [];
          this.pastDepartments = res.data.departments || [];
        }
      } catch (error) {
        console.error('Error fetching requesters and departments:', error);
      }
    },
    async fetchRequests() {
      try {
        const res = await axios.get('/api-digital/pharmacy_material/pharmacy_get_requests.php?status=all');
        if (res.data.success) {
          this.requests = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching requests', error);
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
    editRequest(req) {
      this.editingRequestNo = req.request_no;
      this.form.requester_name = req.requester_name;
      this.form.department = req.department;
      this.form.items = req.items.map(it => ({
        material_id: it.material_id,
        quantity: it.quantity
      }));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    cancelEdit() {
      this.editingRequestNo = null;
      this.form = {
        requester_name: localStorage.getItem('user_name') || localStorage.getItem('last_requester_name_pharmacy') || '',
        department: localStorage.getItem('user_department') || localStorage.getItem('last_department_pharmacy') || '',
        items: []
      };
    },
    async cancelRequest(req) {
      const result = await Swal.fire({
        title: 'ยืนยันการยกเลิก?',
        text: 'คุณต้องการยกเลิกคำขอเบิกวัสดุนี้ใช่หรือไม่? ข้อมูลจะถูกลบออกและไม่สามารถกู้คืนได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ยกเลิกเลย',
        cancelButtonText: 'กลับ'
      });

      if (result.isConfirmed) {
        try {
          const res = await axios.post('/api-digital/pharmacy_material/pharmacy_delete_request.php', { request_no: req.request_no });
          if (res.data.success) {
            Swal.fire('ยกเลิกสำเร็จ!', 'คำขอเบิกวัสดุถูกยกเลิกเรียบร้อยแล้ว', 'success');
            if (this.editingRequestNo === req.request_no) {
              this.cancelEdit();
            }
            this.fetchRequests();
          } else {
            throw new Error(res.data.message);
          }
        } catch (error) {
          console.error(error);
          Swal.fire('ข้อผิดพลาด', error.response?.data?.message || error.message || 'ไม่สามารถยกเลิกคำขอได้', 'error');
        }
      }
    },
    isMaterialInCart(id) {
      return this.form.items.some(item => item.material_id === id);
    },
    addToCart(mat) {
      if (!this.isMaterialInCart(mat.id)) {
        this.form.items.push({ material_id: mat.id, quantity: 1 });
      }
    },
    removeItem(index) {
      this.form.items.splice(index, 1);
    },
    getMaterialUnit(materialId) {
      if (!materialId) return '';
      const mat = this.materials.find((m) => m.id === materialId);
      return mat ? mat.unit : '';
    },
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const baseUrl = import.meta.env.VITE_BACKEND_URL || '';
      return `${baseUrl}/vue-app/vite-digital/${path}`;
    },
    getSelectedMaterial(id) {
      if (!id) return null;
      return this.materials.find(m => m.id == id) || null;
    },
    getAvailableMaterials(currentIndex) {
      const selectedIds = new Set(
        this.form.items
          .map((item, index) => (index !== currentIndex ? item.material_id : null))
          .filter((id) => id !== null && id !== '')
      );
      return this.materials.filter((mat) => !selectedIds.has(mat.id));
    },
    selectMaterial(item, mat) {
      item.material_id = mat.id;
      item.isDropdownOpen = false;
    },
    async submitRequest() {
      // Validate items
      if (
        this.form.items.length === 0 ||
        this.form.items.some((item) => !item.material_id || item.quantity <= 0)
      ) {
        Swal.fire({
          icon: 'warning',
          title: 'ข้อมูลไม่ครบ',
          text: 'กรุณาเลือกวัสดุและระบุจำนวนให้ถูกต้องทุกรายการ'
        });
        return;
      }

      // Check stock balance for each item
      for (const item of this.form.items) {
        const selectedMat = this.materials.find((m) => m.id === item.material_id);
        if (selectedMat && item.quantity > selectedMat.balance) {
          Swal.fire({
            icon: 'warning',
            title: 'ของไม่พอเบิก',
            text: `คุณขอเบิก ${selectedMat.name} จำนวน ${item.quantity} สมบูรณ์ แต่มียอดคงเหลือเพียง ${selectedMat.balance} ${selectedMat.unit}`
          });
          return;
        }
      }

      // All validations passed, show preview modal
      this.showPreviewModal = true;
    },
    async confirmSubmit() {
      this.loading = true;
      try {
        let endpoint = '/api-digital/pharmacy_material/pharmacy_request_material.php';
        let payload = this.form;
        if (this.editingRequestNo) {
           endpoint = '/api-digital/pharmacy_material/pharmacy_update_request.php';
           payload = { ...this.form, request_no: this.editingRequestNo };
        }

        const res = await axios.post(endpoint, payload);
        if (res.data.success) {
          // Save last requester name and dept
          localStorage.setItem('last_requester_name_pharmacy', this.form.requester_name);
          localStorage.setItem('last_department_pharmacy', this.form.department);

          this.showPreviewModal = false; // Hide modal

          Swal.fire({
            icon: 'success',
            title: this.editingRequestNo ? 'แก้ไขสำเร็จ' : 'ส่งคำขอสำเร็จ',
            text: this.editingRequestNo ? 'อัปเดตคำขอเบิกวัสดุเรียบร้อยแล้ว' : 'ระบบได้บันทึกคำขอเบิกวัสดุเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่อนุมัติและจ่ายของ',
            confirmButtonText: 'ตกลง'
          });
          
          this.editingRequestNo = null;

          // Reset form
          this.form = {
            requester_name: localStorage.getItem('user_name') || localStorage.getItem('last_requester_name_pharmacy') || '',
            department: localStorage.getItem('user_department') || localStorage.getItem('last_department_pharmacy') || '',
            items: []
          };
          this.fetchRequests(); // Refresh the history list
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error(error);
        Swal.fire(
          'ข้อผิดพลาด',
          error.response?.data?.message || error.message || 'ไม่สามารถส่งคำขอได้',
          'error'
        );
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.border-dashed {
  border-bottom-style: dashed !important;
  border-bottom-color: #dee2e6 !important;
  border-bottom-width: 2px !important;
}

/* Animations */
.fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.title-animate {
  animation: slideInRight 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Custom Components */
.icon-square {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.25rem;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, var(--bs-primary) 0%, #0d6efd 100%);
}

.hover-lift {
  transition:
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s ease;
}
.hover-lift:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.glass-card {
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

.input-group-custom .form-control:focus {
  border-color: #dee2e6;
  box-shadow: none;
}
.input-group-custom {
  transition: all 0.2s ease;
  border-radius: 0.5rem;
  overflow: hidden;
}
.input-group-custom:focus-within {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
  border-color: #86b7fe;
}
.input-group-custom .input-group-text,
.input-group-custom .form-control {
  background-color: #f8f9fa;
  border-color: #e9ecef;
}
.input-group-custom:focus-within .input-group-text,
.input-group-custom:focus-within .form-control {
  background-color: #fff;
  border-color: #86b7fe;
}

/* Item Rows */
.item-row {
  transition: all 0.3s ease;
}
.item-row:hover {
  background-color: #fff !important;
  border-color: #e9ecef !important;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
  transform: translateY(-2px);
}

.item-index-badge {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

/* Breadcrumb */
.custom-breadcrumb .breadcrumb-item a {
  color: #6c757d;
  transition: color 0.2s;
}
.custom-breadcrumb .breadcrumb-item a:hover {
  color: var(--bs-primary);
}

/* Submit Button */
.submit-btn {
  background: linear-gradient(135deg, var(--bs-primary) 0%, #0a58ca 100%);
  border: none;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.submit-btn:hover:not(:disabled) {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 15px 25px rgba(13, 110, 253, 0.3) !important;
}
.submit-btn:active:not(:disabled) {
  transform: translateY(1px);
}

/* Custom Dropdown for Select */
.custom-dropdown-item {
  transition: all 0.2s ease;
}
.custom-dropdown-item:hover {
  background-color: #f8f9fa;
  transform: translateX(4px);
}
.custom-select-dropdown .dropdown-toggle::after {
  margin-left: auto;
}

/* Chrome, Safari, Edge, Opera */
.hide-arrows::-webkit-outer-spin-button,
.hide-arrows::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

/* Firefox */
.hide-arrows {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
