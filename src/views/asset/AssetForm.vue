<template>
  <div class="modal fade" id="assetFormModal" ref="modal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">{{ form.id ? 'แก้ไขข้อมูล' : 'เพิ่มข้อมูลใหม่' }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="save">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link active"
                  id="general-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#general"
                  type="button"
                  role="tab"
                >
                  ข้อมูลทั่วไป
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link"
                  id="spec-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#spec"
                  type="button"
                  role="tab"
                >
                  สเปคเครื่อง
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link"
                  id="purchase-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#purchase"
                  type="button"
                  role="tab"
                >
                  การจัดซื้อ & สถานะ
                </button>
              </li>
            </ul>

            <div class="tab-content" id="myTabContent">
              <!-- General Tab -->
              <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="row g-3">
                  <!-- Code Generation Section -->
                  <div class="col-12 bg-light p-3 rounded mb-2 border">
                    <label class="form-label fw-bold text-primary mb-2">
                      <i class="bi bi-magic me-1"></i>สร้างรหัสครุภัณฑ์อัตโนมัติ
                    </label>
                    <div class="row g-2">
                      <!-- Category -->
                      <div class="col-md-4">
                        <label class="small text-muted">หมวด (4 หลัก)</label>
                        <div class="input-group input-group-sm" v-if="!isAddingCategory">
                          <select
                            v-model="genCode.categoryId"
                            class="form-select"
                            @change="onCategoryChange"
                          >
                            <option value="">-- เลือก --</option>
                            <option v-for="c in categories" :key="c.id" :value="c.code">
                              {{ c.code }} - {{ c.name }}
                            </option>
                          </select>
                          <button
                            class="btn btn-outline-secondary"
                            type="button"
                            @click="enableAddCategory"
                          >
                            <i class="bi bi-plus"></i>
                          </button>
                        </div>
                        <div class="input-group input-group-sm" v-else>
                          <input
                            type="text"
                            v-model="newCategoryCode"
                            class="form-control"
                            placeholder="รหัส"
                            style="max-width: 60px"
                            ref="newCatCodeRef"
                          />
                          <input
                            type="text"
                            v-model="newCategoryName"
                            class="form-control"
                            placeholder="ชื่อ"
                            @keyup.enter="saveNewCategory"
                          />
                          <button class="btn btn-success" type="button" @click="saveNewCategory">
                            <i class="bi bi-check"></i>
                          </button>
                          <button
                            class="btn btn-outline-danger"
                            type="button"
                            @click="isAddingCategory = false"
                          >
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>

                      <!-- Class -->
                      <div class="col-md-4">
                        <label class="small text-muted">ชนิด (3 หลัก)</label>
                        <div class="input-group input-group-sm" v-if="!isAddingClass">
                          <select
                            v-model="genCode.classCode"
                            class="form-select"
                            @change="onClassChange"
                          >
                            <option value="">-- เลือก --</option>
                            <option v-for="c in classes" :key="c.class_id" :value="c.code">
                              {{ c.code }} - {{ c.name }}
                            </option>
                          </select>
                          <button
                            class="btn btn-outline-secondary"
                            type="button"
                            @click="enableAddClass"
                          >
                            <i class="bi bi-plus"></i>
                          </button>
                        </div>
                        <div class="input-group input-group-sm" v-else>
                          <input
                            type="text"
                            v-model="newClassCode"
                            class="form-control"
                            placeholder="รหัส"
                            style="max-width: 60px"
                            ref="newClassCodeRef"
                          />
                          <input
                            type="text"
                            v-model="newClassName"
                            class="form-control"
                            placeholder="ชื่อ"
                            @keyup.enter="saveNewClass"
                          />
                          <button class="btn btn-success" type="button" @click="saveNewClass">
                            <i class="bi bi-check"></i>
                          </button>
                          <button
                            class="btn btn-outline-danger"
                            type="button"
                            @click="isAddingClass = false"
                          >
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>

                      <!-- Type -->
                      <div class="col-md-4">
                        <label class="small text-muted">คุณลักษณะ (4 หลัก)</label>
                        <div class="input-group input-group-sm" v-if="!isAddingType">
                          <select
                            v-model="genCode.typeCode"
                            class="form-select"
                            @change="onTypeChange"
                          >
                            <option value="">-- เลือก --</option>
                            <option v-for="t in types" :key="t.id" :value="t.code">
                              {{ t.code }} - {{ t.name }}
                            </option>
                          </select>
                          <button
                            class="btn btn-outline-secondary"
                            type="button"
                            @click="enableAddType"
                          >
                            <i class="bi bi-plus"></i>
                          </button>
                        </div>
                        <div class="input-group input-group-sm" v-else>
                          <input
                            type="text"
                            v-model="newTypeCode"
                            class="form-control"
                            placeholder="รหัส"
                            style="max-width: 60px"
                            ref="newTypeCodeRef"
                          />
                          <input
                            type="text"
                            v-model="newTypeInput"
                            class="form-control"
                            placeholder="ชื่อ"
                            @keyup.enter="saveNewType"
                          />
                          <button class="btn btn-success" type="button" @click="saveNewType">
                            <i class="bi bi-check"></i>
                          </button>
                          <button
                            class="btn btn-outline-danger"
                            type="button"
                            @click="isAddingType = false"
                          >
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>

                      <div class="col-md-2 mt-2">
                        <label class="small text-muted">ปี (2)</label>
                        <input
                          type="text"
                          v-model="genCode.year"
                          class="form-control form-control-sm text-center"
                          maxlength="2"
                          placeholder="69"
                          @input="updateCode"
                        />
                      </div>
                      <div class="col-md-2 mt-2">
                        <label class="small text-muted">ลำดับ (2)</label>
                        <input
                          type="number"
                          v-model="genCode.number"
                          class="form-control form-control-sm text-center"
                          placeholder="01"
                          @input="updateCode"
                        />
                      </div>
                      <div class="col-md-4 mt-2 pt-4 text-center">
                        <span class="fw-bold text-dark fs-5">{{
                          generatedCode || 'waiting...'
                        }}</span>
                      </div>
                      <div class="col-md-4 mt-2 pt-3">
                        <button
                          type="button"
                          class="btn btn-sm btn-primary w-100"
                          @click="applyCode"
                          :disabled="!generatedCode"
                        >
                          ใช้รหัสนี้
                        </button>
                      </div>
                    </div>
                    <div class="mt-2 small text-muted">
                      ตัวอย่าง:
                      <span class="fw-bold text-dark">{{ generatedCode || 'waiting...' }}</span>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label"
                      >รหัสครุภัณฑ์ <span class="text-danger">*</span></label
                    >
                    <input
                      type="text"
                      v-model="form.asset_code"
                      class="form-control fw-bold"
                      required
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label"
                      >ชื่อครุภัณฑ์ <span class="text-danger">*</span></label
                    >
                    <input type="text" v-model="form.name" class="form-control" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">ยี่ห้อ</label>
                    <div class="input-group" v-if="!isAddingBrand">
                      <select v-model="form.brand" class="form-select">
                        <option value="">-- เลือกยี่ห้อ --</option>
                        <option v-for="b in brands" :key="b.brand_id" :value="b.brand_name">
                          {{ b.brand_name }}
                        </option>
                      </select>
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        @click="enableAddBrand"
                        title="เพิ่มยี่ห้อ"
                      >
                        +
                      </button>
                    </div>
                    <div class="input-group" v-else>
                      <input
                        type="text"
                        v-model="newBrandInput"
                        class="form-control"
                        placeholder="ระบุยี่ห้อใหม่"
                        ref="newBrandRef"
                        @keyup.enter="saveNewBrand"
                      />
                      <button class="btn btn-success" type="button" @click="saveNewBrand">
                        <i class="bi bi-check"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        type="button"
                        @click="isAddingBrand = false"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">รุ่น (Model)</label>
                    <input type="text" v-model="form.model" class="form-control" />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">อัพโหลดรูปภาพ</label>
                    <input
                      type="file"
                      class="form-control"
                      accept="image/*"
                      ref="fileInput"
                      @change="handleFileUpload"
                    />
                    <div
                      v-if="previewImage || form.image_path"
                      class="mt-2 position-relative d-inline-block"
                    >
                      <img
                        :src="previewImage || getImageUrl(form.image_path)"
                        class="img-thumbnail"
                        style="max-height: 100px"
                      />
                      <button
                        type="button"
                        class="btn btn-sm btn-danger position-absolute top-0 end-0 py-0 px-1"
                        @click="removeImage"
                      >
                        x
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Serial Number</label>
                    <input type="text" v-model="form.serial_number" class="form-control" />
                  </div>
                </div>
              </div>

              <!-- Spec Tab -->
              <div class="tab-pane fade" id="spec" role="tabpanel">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">ขนาด & หน่วย</label>
                    <div class="input-group">
                      <input
                        type="text"
                        v-model="form.size"
                        class="form-control"
                        placeholder="ระบุขนาด"
                      />
                      <select v-model="form.unit" class="form-select" v-if="!isAddingUnit">
                        <option value="">-- หน่วย --</option>
                        <option v-for="u in units" :key="u.unit_id" :value="u.name">
                          {{ u.name }}
                        </option>
                      </select>
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        @click="enableAddUnit"
                        v-if="!isAddingUnit"
                      >
                        +
                      </button>

                      <input
                        type="text"
                        v-model="newUnitInput"
                        class="form-control"
                        placeholder="หน่วยใหม่"
                        v-if="isAddingUnit"
                        ref="newUnitRef"
                        @keyup.enter="saveNewUnit"
                      />
                      <button
                        class="btn btn-success"
                        type="button"
                        @click="saveNewUnit"
                        v-if="isAddingUnit"
                      >
                        <i class="bi bi-check"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        type="button"
                        @click="isAddingUnit = false"
                        v-if="isAddingUnit"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">CPU</label>
                    <input
                      type="text"
                      v-model="form.spec_cpu"
                      class="form-control"
                      placeholder="Ex: Intel Core i5-12400"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">RAM</label>
                    <input
                      type="text"
                      v-model="form.spec_ram"
                      class="form-control"
                      placeholder="Ex: 16 GB"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Storage (HDD/SSD)</label>
                    <input
                      type="text"
                      v-model="form.spec_storage"
                      class="form-control"
                      placeholder="Ex: SSD 512 GB"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">OS</label>
                    <div class="input-group" v-if="!isAddingOS">
                      <select v-model="form.os" class="form-select">
                        <option value="">-- เลือก --</option>
                        <option v-for="os in osList" :key="os.id" :value="os.name">
                          {{ os.name }}
                        </option>
                      </select>
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        @click="enableAddOS"
                        title="เพิ่ม OS ใหม่"
                      >
                        +
                      </button>
                    </div>
                    <div class="input-group" v-else>
                      <input
                        type="text"
                        v-model="newOSInput"
                        class="form-control"
                        placeholder="ระบุ OS ใหม่"
                        ref="newOSRef"
                        @keyup.enter="saveNewOS"
                      />
                      <button class="btn btn-success" type="button" @click="saveNewOS">
                        <i class="bi bi-check"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        type="button"
                        @click="isAddingOS = false"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Purchase Tab -->
              <div class="tab-pane fade" id="purchase" role="tabpanel">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">วันที่จัดซื้อ/รับเข้า</label>
                    <input type="date" v-model="form.purchase_date" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">วันที่หมดประกัน</label>
                    <input type="date" v-model="form.warranty_expire_date" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">ราคา (บาท)</label>
                    <input type="number" step="0.01" v-model="form.price" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">วิธีการได้รับ</label>
                    <div class="input-group">
                      <select v-model="form.acquisition_method" class="form-select" v-if="!isAddingAcquisitionMethod">
                        <option value="">-- เลือก --</option>
                        <option v-for="am in acquisitionMethods" :key="am.id" :value="am.name">
                          {{ am.name }}
                        </option>
                      </select>
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        @click="enableAddAcquisitionMethod"
                        v-if="!isAddingAcquisitionMethod"
                      >
                        +
                      </button>

                      <input
                        type="text"
                        v-model="newAcquisitionMethodInput"
                        class="form-control"
                        placeholder="ระบุวิธีการได้รับ"
                        v-if="isAddingAcquisitionMethod"
                        ref="newAcquisitionMethodRef"
                        @keyup.enter="saveNewAcquisitionMethod"
                      />
                      <button
                        class="btn btn-success"
                        type="button"
                        @click="saveNewAcquisitionMethod"
                        v-if="isAddingAcquisitionMethod"
                      >
                        <i class="bi bi-check"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        type="button"
                        @click="isAddingAcquisitionMethod = false"
                        v-if="isAddingAcquisitionMethod"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">แหล่งที่มา/บริษัท</label>
                    <div class="input-group">
                      <select v-model="form.source" class="form-select" v-if="!isAddingSource">
                        <option value="">-- เลือก --</option>
                        <option v-for="s in sources" :key="s.source_id" :value="s.name">
                          {{ s.name }}
                        </option>
                      </select>
                      <button
                        class="btn btn-outline-secondary"
                        type="button"
                        @click="enableAddSource"
                        v-if="!isAddingSource"
                      >
                        +
                      </button>

                      <input
                        type="text"
                        v-model="newSourceInput"
                        class="form-control"
                        placeholder="ระบุแหล่งที่มา"
                        v-if="isAddingSource"
                        ref="newSourceRef"
                        @keyup.enter="saveNewSource"
                      />
                      <button
                        class="btn btn-success"
                        type="button"
                        @click="saveNewSource"
                        v-if="isAddingSource"
                      >
                        <i class="bi bi-check"></i>
                      </button>
                      <button
                        class="btn btn-outline-danger"
                        type="button"
                        @click="isAddingSource = false"
                        v-if="isAddingSource"
                      >
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">ผู้รับผิดชอบ/ผู้ใช้</label>
                    <input
                      type="text"
                      v-model="form.responsible_person"
                      class="form-control"
                      list="hrPersonList"
                      placeholder="ค้นหาชื่อ..."
                    />
                    <datalist id="hrPersonList">
                      <option v-for="p in hrPeople" :key="p.ID" :value="p.FULLNAME"></option>
                    </datalist>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">สถานที่ตั้ง</label>
                    <input
                      type="text"
                      v-model="form.location"
                      class="form-control"
                      list="hrDeptList"
                      placeholder="ค้นหาสถานที่..."
                    />
                    <datalist id="hrDeptList">
                      <option
                        v-for="(d, i) in hrDepartments"
                        :key="i"
                        :value="d.HR_DEPARTMENT_SUB_NAME"
                      ></option>
                    </datalist>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">สถานะ</label>
                    <select v-model="form.status" class="form-select">
                      <option value="Active">ใช้งานปกติ</option>
                      <option value="Spare">เครื่องสำรอง</option>
                      <option value="Repair">ส่งซ่อม</option>
                      <option value="Write-off">แทงจำหน่าย</option>
                      <option value="Sold">ขาย/โอน</option>
                    </select>

                    <!-- Checkbox for allow_loan if status is Spare -->
                    <div class="form-check mt-2" v-if="form.status === 'Spare'">
                      <input class="form-check-input" type="checkbox" v-model="form.allow_loan" :true-value="0" :false-value="1" id="allowLoanCheck">
                      <label class="form-check-label text-danger" for="allowLoanCheck">
                        ไม่อนุญาตให้ยืม (ระงับการยืม)
                      </label>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="text-end mt-4">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                ยกเลิก
              </button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span> บันทึก
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
import { Modal } from 'bootstrap';

export default {
  name: 'AssetForm',
  data() {
    return {
      bsModal: null,
      submitting: false,
      selectedFile: null,
      previewImage: null,
      categories: [],
      classes: [],
      brands: [],
      units: [],
      isAddingUnit: false,
      newUnitInput: '',
      types: [],
      isAddingType: false,
      newTypeInput: '', // Name
      newTypeCode: '', // Code
      isAddingBrand: false,
      newBrandInput: '',
      osList: [],
      isAddingOS: false,
      newOSInput: '',
      isAddingCategory: false,
      newCategoryCode: '',
      newCategoryName: '',
      isAddingClass: false,
      newClassCode: '',
      newClassName: '',
      genCode: {
        categoryId: '',
        classCode: '',
        typeCode: '',
        year: '',
        number: ''
      },
      form: {
        id: null,
        asset_code: '',
        name: '',
        type: 'PC',
        brand: '',
        model: '',
        size: '',
        unit: '',
        serial_number: '',
        spec_cpu: '',
        spec_ram: '',
        spec_storage: '',
        os: '',
        purchase_date: '',
        warranty_expire_date: '',
        price: '',
        responsible_person: '',
        location: '',
        status: 'Active',
        allow_loan: 1,
        notes: '',
        image_path: '',
        acquisition_method: '',
        source: ''
      },
      hrPeople: [],
      hrDepartments: [],
      sources: [],
      isAddingSource: false,
      newSourceInput: '',
      acquisitionMethods: [],
      isAddingAcquisitionMethod: false,
      newAcquisitionMethodInput: ''
    };
  },
  computed: {
    generatedCode() {
      if (
        this.genCode.categoryId &&
        this.genCode.classCode &&
        this.genCode.typeCode &&
        this.genCode.year &&
        this.genCode.number
      ) {
        const num = String(this.genCode.number).padStart(2, '0');
        return `${this.genCode.categoryId}-${this.genCode.classCode}-${this.genCode.typeCode}/${this.genCode.year}${num}`;
      }
      return '';
    }
  },
  mounted() {
    this.bsModal = new Modal(this.$refs.modal);
    this.fetchCategories();
    this.fetchBrands();
    this.fetchTypes();
    this.fetchUnits();
    this.fetchOSList();
    this.fetchHRPeople();
    this.fetchHRDepartments();
    this.fetchSources();
    this.fetchAcquisitionMethods();
  },
  methods: {
    async fetchOSList() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_os.php');
        if (res.data.status === 'success') this.osList = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    enableAddOS() {
      this.isAddingOS = true;
      this.newOSInput = '';
      this.$nextTick(() => {
        if (this.$refs.newOSRef) this.$refs.newOSRef.focus();
      });
    },
    async saveNewOS() {
      if (!this.newOSInput.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_os.php', {
          name: this.newOSInput
        });
        if (res.data.status === 'success') {
          await this.fetchOSList();
          this.form.os = this.newOSInput;
          this.isAddingOS = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.response?.data?.message || 'Failed', 'error');
      }
    },
    async fetchSources() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_sources.php');
        if (res.data.status === 'success') this.sources = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchAcquisitionMethods() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_acquisition_methods.php');
        if (res.data.status === 'success') this.acquisitionMethods = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    enableAddAcquisitionMethod() {
      this.isAddingAcquisitionMethod = true;
      this.newAcquisitionMethodInput = '';
      this.$nextTick(() => {
        if (this.$refs.newAcquisitionMethodRef) this.$refs.newAcquisitionMethodRef.focus();
      });
    },
    async saveNewAcquisitionMethod() {
      if (!this.newAcquisitionMethodInput.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_acquisition_method.php', {
          name: this.newAcquisitionMethodInput
        });
        if (res.data.status === 'success') {
          await this.fetchAcquisitionMethods();
          this.form.acquisition_method = this.newAcquisitionMethodInput;
          this.isAddingAcquisitionMethod = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.response?.data?.message || 'Failed', 'error');
      }
    },
    enableAddSource() {
      this.isAddingSource = true;
      this.newSourceInput = '';
      this.$nextTick(() => {
        if (this.$refs.newSourceRef) this.$refs.newSourceRef.focus();
      });
    },
    async saveNewSource() {
      if (!this.newSourceInput.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_source.php', {
          name: this.newSourceInput
        });
        if (res.data.status === 'success') {
          await this.fetchSources();
          this.form.source = this.newSourceInput;
          this.isAddingSource = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.response?.data?.message || 'Failed', 'error');
      }
    },
    async fetchHRPeople() {
      try {
        const res = await axios.get('/api-digital/asset/get_hr_person.php');
        if (res.data.status === 'success') this.hrPeople = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchHRDepartments() {
      try {
        const res = await axios.get('/api-digital/asset/get_hr_department.php');
        if (res.data.status === 'success') this.hrDepartments = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchTypes() {
      if (!this.genCode.classCode) {
        this.types = [];
        return;
      }
      try {
        const res = await axios.get(
          `/api-digital/asset/get_asset_types.php?class_code=${this.genCode.classCode}`
        );
        if (res.data.status === 'success') this.types = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    async fetchClasses() {
      if (!this.genCode.categoryId) {
        this.classes = [];
        return;
      }
      try {
        const res = await axios.get(
          `/api-digital/asset/get_asset_classes.php?category_code=${this.genCode.categoryId}`
        );
        if (res.data.status === 'success') this.classes = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    enableAddClass() {
      this.isAddingClass = true;
      this.newClassCode = '';
      this.newClassName = '';
      this.$nextTick(() => {
        if (this.$refs.newClassCodeRef) this.$refs.newClassCodeRef.focus();
      });
    },
    async saveNewClass() {
      if (!this.newClassCode.trim() || !this.newClassName.trim()) return;
      try {
        // Need category_id, find it from code
        const cat = this.categories.find((c) => c.code == this.genCode.categoryId);
        if (!cat) return;

        const res = await axios.post('/api-digital/asset/save_asset_class.php', {
          category_id: cat.id,
          code: this.newClassCode,
          name: this.newClassName
        });
        if (res.data.status === 'success') {
          await this.fetchClasses();
          this.genCode.classCode = this.newClassCode;
          this.isAddingClass = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed', 'error');
      }
    },
    enableAddType() {
      this.isAddingType = true;
      this.newTypeInput = '';
      this.newTypeCode = '';
      this.$nextTick(() => {
        if (this.$refs.newTypeCodeRef) this.$refs.newTypeCodeRef.focus();
      });
    },
    async saveNewType() {
      if (!this.newTypeInput.trim() || !this.newTypeCode.trim()) return;
      try {
        const cls = this.classes.find((c) => c.code == this.genCode.classCode);
        if (!cls) return;

        const res = await axios.post('/api-digital/asset/save_asset_type.php', {
          class_id: cls.class_id,
          code: this.newTypeCode,
          name: this.newTypeInput
        });
        if (res.data.status === 'success') {
          await this.fetchTypes();
          this.genCode.typeCode = this.newTypeCode;
          this.form.type = this.newTypeInput;
          this.isAddingType = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed', 'error');
      }
    },
    enableAddBrand() {
      this.isAddingBrand = true;
      this.newBrandInput = '';
      this.$nextTick(() => {
        if (this.$refs.newBrandRef) this.$refs.newBrandRef.focus();
      });
    },
    async saveNewBrand() {
      if (!this.newBrandInput.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_brand.php', {
          name: this.newBrandInput
        });
        if (res.data.status === 'success') {
          await this.fetchBrands();
          this.form.brand = this.newBrandInput;
          this.isAddingBrand = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.response?.data?.message || 'Failed', 'error');
      }
    },
    async fetchUnits() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_units.php');
        if (res.data.status === 'success') this.units = res.data.data;
      } catch (e) {
        console.error(e);
      }
    },
    enableAddUnit() {
      this.isAddingUnit = true;
      this.newUnitInput = '';
      this.$nextTick(() => {
        if (this.$refs.newUnitRef) this.$refs.newUnitRef.focus();
      });
    },
    async saveNewUnit() {
      if (!this.newUnitInput.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_unit.php', {
          name: this.newUnitInput
        });
        if (res.data.status === 'success') {
          await this.fetchUnits();
          this.form.unit = this.newUnitInput;
          this.isAddingUnit = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        } else {
          Swal.fire('Error', res.data.message || 'Failed', 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', e.response?.data?.message || 'Failed', 'error');
      }
    },
    async fetchBrands() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_brands.php');
        if (res.data.status === 'success') {
          this.brands = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    async fetchCategories() {
      try {
        const res = await axios.get('/api-digital/asset/get_asset_categories.php');
        if (res.data.status === 'success') {
          this.categories = res.data.data;
        }
      } catch (e) {
        console.error(e);
      }
    },
    onCategoryChange() {
      this.genCode.classCode = '';
      this.genCode.typeCode = '';
      this.classes = [];
      this.types = [];
      this.fetchClasses();
    },
    onClassChange() {
      this.genCode.typeCode = '';
      this.types = [];
      this.fetchTypes();
    },
    onTypeChange() {
      if (this.genCode.typeCode) {
        const t = this.types.find((x) => x.code == this.genCode.typeCode);
        if (t) {
          this.form.type = t.name;
          this.form.name = t.name;
        }
        this.fetchNextSequence();
      }
    },
    updateCode() {
      if (this.genCode.year && this.genCode.year.length >= 2) {
        this.fetchNextSequence();
      }
    },
    async fetchNextSequence() {
      if (
        this.genCode.categoryId &&
        this.genCode.classCode &&
        this.genCode.typeCode &&
        this.genCode.year &&
        this.genCode.year.length >= 2
      ) {
        const prefix = `${this.genCode.categoryId}-${this.genCode.classCode}-${this.genCode.typeCode}/${this.genCode.year}`;
        try {
          const res = await axios.get(`/api-digital/asset/get_next_asset_sequence.php?prefix=${prefix}`);
          if (res.data.status === 'success') {
            this.genCode.number = res.data.data;
          }
        } catch (e) {
          console.error(e);
        }
      }
    },
    applyCode() {
      if (this.generatedCode) {
        this.form.asset_code = this.generatedCode;
        // Auto-fill name from type (คุณลักษณะ) when applying code
        const t = this.types.find((x) => x.code == this.genCode.typeCode);
        if (t) {
          this.form.name = t.name;
        }
      }
    },
    enableAddCategory() {
      this.isAddingCategory = true;
      this.newCategoryCode = '';
      this.newCategoryName = '';
      this.$nextTick(() => {
        if (this.$refs.newCatCodeRef) this.$refs.newCatCodeRef.focus();
      });
    },
    async saveNewCategory() {
      if (!this.newCategoryCode.trim() || !this.newCategoryName.trim()) return;
      try {
        const res = await axios.post('/api-digital/asset/save_asset_category.php', {
          code: this.newCategoryCode,
          name: this.newCategoryName
        });
        if (res.data.status === 'success') {
          await this.fetchCategories();
          this.genCode.categoryId = this.newCategoryCode;
          this.isAddingCategory = false;
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
          this.updateCode();
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Failed', 'error');
      }
    },
    open(asset = null, isClone = false) {
      this.resetForm(); // Reset first to clear any previous state
      if (asset) {
        this.form = { ...asset };

        if (isClone) {
          this.form.id = null; // Clear ID for new record
          this.form.asset_code = ''; // Clear code to force new one
          this.form.serial_number = ''; // Clear serial number
          this.form.image_path = ''; // Clear image path
          this.previewImage = null; // Clear preview
          this.selectedFile = null;

          // Reset genCode so it doesn't show previous asset's breakdown
          this.genCode = { categoryId: '', classCode: '', typeCode: '', year: '', number: '' };

          // Optionally, pre-fill genCode parts based on the asset being cloned if desired,
          // but user probably wants to generate a NEW code.
          // We can try to parse the category/class/type from the old code to help them start
          if (asset.asset_code) {
            const parts = asset.asset_code.split(/[-/]/);
            if (parts.length >= 3) {
              this.genCode.categoryId = parts[0];
              this.genCode.classCode = parts[1];
              this.genCode.typeCode = parts[2];
              // Don't set year/number, let them be empty
              this.fetchClasses();
              this.fetchTypes();
            }
          }
        } else {
          // Normal Edit Mode
          // Parse: 7440-001-0001/6901
          if (asset.asset_code) {
            const parts = asset.asset_code.split(/[-/]/);
            if (parts.length >= 4) {
              this.genCode.categoryId = parts[0];
              this.genCode.classCode = parts[1];
              this.genCode.typeCode = parts[2];
              // YearRun eg 6901
              if (parts[3] && parts[3].length === 4) {
                this.genCode.year = parts[3].substring(0, 2);
                this.genCode.number = parseInt(parts[3].substring(2));
              }
              this.fetchClasses();
              this.fetchTypes();
            } else if (parts.length === 3) {
              this.genCode.categoryId = parts[0];
              this.genCode.year = parts[1];
              this.genCode.number = parseInt(parts[2]);
            }
          }
        }
      }
      this.bsModal.show();
    },
    resetForm() {
      this.previewImage = null;
      this.genCode = { categoryId: '', classCode: '', typeCode: '', year: '', number: '' };
      this.form = {
        id: null,
        asset_code: '',
        name: '',
        type: '',
        brand: '',
        model: '',
        serial_number: '',
        spec_cpu: '',
        spec_ram: '',
        spec_storage: '',
        os: '',
        purchase_date: '',
        warranty_expire_date: '',
        price: '',
        responsible_person: '',
        location: '',
        status: 'Active',
        allow_loan: 1,
        notes: '',
        image_path: ''
      };
      if (this.$refs.fileInput) this.$refs.fileInput.value = '';
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
    getImageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      return `http://localhost/vue-app/vite-digital/${path}`;
    },
    async save() {
      this.submitting = true;
      try {
        const formData = new FormData();
        for (const key in this.form) {
          formData.append(key, this.form[key] !== null ? this.form[key] : '');
        }
        if (this.selectedFile) {
          formData.append('image', this.selectedFile);
        }

        const res = await axios.post('/api-digital/asset/save_asset.php', formData);

        if (res.data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            showConfirmButton: false,
            timer: 1500
          });
          this.bsModal.hide();
          this.$emit('saved');
        } else {
          Swal.fire('Error', res.data.message || 'Unknown error from server', 'error');
        }
      } catch (err) {
        let errorMsg = 'Connection Error: Unable to save.';
        if (err.response && err.response.data && err.response.data.message) {
          errorMsg = err.response.data.message;
        }
        Swal.fire('Error', errorMsg, 'error');
        console.error(err);
      } finally {
        this.submitting = false;
      }
    }
  }
};
</script>
