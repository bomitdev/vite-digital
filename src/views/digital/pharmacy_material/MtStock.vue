<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/pharmacy-admin">หน้าหลักวัสดุ</router-link>
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
          <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" @click="downloadTemplate">
            <i class="bi bi-download me-1"></i> โหลด Template
          </button>
          <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" @click="$refs.excelInput.click()">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> นำเข้าวัสดุ
          </button>
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
                  <th class="text-end">มูลค่ารวม (฿)</th>
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
                  <td class="text-end fw-semibold text-primary">
                    {{ Number(item.total_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                  </td>
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
                  <td colspan="8" class="p-0 border-bottom">
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
                              <td class="text-end">{{ Number(lot.price_per_unit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿</td>
                              <td class="text-center text-muted">{{ lot.original_qty }}</td>
                              <td class="text-center fw-bold" :class="lot.remaining_qty > 0 ? 'text-success' : ''">{{ lot.remaining_qty }}</td>
                              <td class="text-end fw-bold text-primary pe-3">{{ (lot.remaining_qty * lot.price_per_unit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }} ฿</td>
                            </tr>
                          </tbody>
                          <tfoot class="table-light fw-bold text-dark border-top">
                            <tr>
                              <td colspan="3" class="text-end py-2">รวมทั้งหมด:</td>
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
                <div class="mb-3">
                  <label class="form-label"
                    >รหัสสินค้า (SKU) <span class="text-danger">*</span></label
                  >
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.code"
                    required
                    placeholder="เช่น ADM-RAM-001"
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label">ชื่ออุปกรณ์ <span class="text-danger">*</span></label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.name"
                    required
                    placeholder="เช่น RAM DDR4 8GB"
                  />
                </div>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.type"
                      required
                      list="categoryList"
                      placeholder="เลือกหรือพิมพ์ใหม่ (เช่น RAM)"
                    />
                    <datalist id="categoryList">
                      <option v-for="cat in uniqueCategories" :key="cat" :value="cat"></option>
                    </datalist>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">หน่วยนับ <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="form.unit"
                      required
                      list="unitList"
                      placeholder="เลือกหรือพิมพ์ใหม่ (เช่น ชิ้น)"
                    />
                    <datalist id="unitList">
                      <option v-for="u in uniqueUnits" :key="u" :value="u"></option>
                    </datalist>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label"
                      >ราคาต่อหน่วย <span class="text-danger">*</span></label
                    >
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



    </div>

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

export default {
  name: 'MtStock',
  components: {
    MtLogModal
  },
  data() {
    return {
      search: '',
      selectedCategory: 'all',
      lowStockOnly: false,
      materials: [],
      expandedRowId: null,
      expandedLots: [],
      isLoadingLots: false,
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
        const res = await axios.get(`/api-digital/pharmacy_material/pharmacy_get_lots.php?material_id=${item.id}`);
        if (res.data.status === 'success') {
          this.expandedLots = res.data.data;
        }
      } catch (err) {
        console.error('Failed to fetch lots', err);
      } finally {
        this.isLoadingLots = false;
      }
    },
    async fetchMaterials() {
      try {
        let url = `/api-digital/pharmacy_material/pharmacy_get_materials.php?search=${encodeURIComponent(this.search)}`;
        if (this.lowStockOnly) url += '&low_stock=1';

        const res = await axios.get(url);
        if (res.data.status === 'success') {
          this.materials = res.data.data;
        }
      } catch (err) {
        console.error(err);
      }
    },
    downloadTemplate() {
      const templateData = [
        {
          รหัสสินค้า: 'MT-001',
          ชื่ออุปกรณ์: 'ปากกาน้ำเงิน',
          ประเภท: 'เครื่องเขียน',
          หน่วยนับ: 'ด้าม',
          ราคาต่อหน่วย: 5.50,
          เลขLot: 'L-2026-01',
          แจ้งเตือนขั้นต่ำ: 20,
          ยอดยกมา: 100
        },
        {
          รหัสสินค้า: 'MT-002',
          ชื่ออุปกรณ์: 'กระดาษ A4',
          ประเภท: 'กระดาษ',
          หน่วยนับ: 'รีม',
          ราคาต่อหน่วย: 95.00,
          เลขLot: 'L-2026-02',
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

            const res = await axios.post('/api-digital/pharmacy_material/pharmacy_import_material.php', json);
            
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
          Swal.fire('ข้อผิดพลาด', 'รูปแบบไฟล์ไม่ถูกต้อง หรือเกิดปัญหาในการอ่านไฟล์', 'error');
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
        let nextCode = 'MT-001';
        if (this.materials && this.materials.length > 0) {
          const mtCodes = this.materials
            .map(m => m.code)
            .filter(code => code && code.startsWith('MT-'))
            .map(code => parseInt(code.replace('MT-', ''), 10))
            .filter(num => !isNaN(num));

          if (mtCodes.length > 0) {
            const maxNum = Math.max(...mtCodes);
            nextCode = `MT-${String(maxNum + 1).padStart(3, '0')}`;
          }
        }

        this.form = {
          id: null,
          code: nextCode,
          name: '',
          type: '',
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

        const res = await axios.post('/api-digital/pharmacy_material/pharmacy_save_material.php', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        if (res.data.status === 'success') {
          // alert(res.data.message);
          this.modalInstance.hide();
          this.fetchMaterials();
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
          const response = await axios.post('/api-digital/pharmacy_material/pharmacy_toggle_material_status.php', {
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
          const res = await axios.post('/api-digital/pharmacy_material/pharmacy_delete_material.php', { id });
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
    openLogModal() {
      // eslint-disable-next-line no-undef
      const modal = new bootstrap.Modal(document.getElementById('stockLogModal'));
      modal.show();
      this.$refs.logModal.fetchLogs();
    }
  },
  computed: {
    uniqueCategories() {
      const categories = this.materials.map(m => m.type).filter(t => t);
      return [...new Set(categories)].sort();
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
</style>
