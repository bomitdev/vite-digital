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
              <li class="breadcrumb-item"><router-link to="/inventory-pr">ขอซื้อ (PR)</router-link></li>
              <li class="breadcrumb-item active" aria-current="page">{{ isEdit ? pr.pr_number : 'สร้างใบขอซื้อใหม่' }}</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-file-earmark-plus me-2"></i>{{ isEdit ? 'ใบขอซื้อ: ' + pr.pr_number : 'สร้างใบขอซื้อใหม่ (PR)' }}
          </h2>
        </div>
        <div class="d-flex gap-2">
          <button v-if="isEdit" type="button" class="btn btn-success rounded-pill fw-bold shadow-sm" @click="openPrintModal">
            <i class="bi bi-printer-fill me-1"></i> พิมพ์ใบขอซื้อเสนอ ผอ.
          </button>
          <button class="btn btn-outline-danger rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-po-form')">
            <i class="bi bi-cart-check me-1"></i> ระบบสั่งซื้อเวชภัณฑ์ (PO)
          </button>
          <button class="btn btn-outline-secondary rounded-pill fw-bold shadow-sm" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
          </button>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
              
              <!-- Status & Print Action Banner -->
              <div v-if="isEdit" class="card border border-success-subtle bg-success-subtle bg-opacity-10 rounded-4 p-3 mb-4 shadow-xs">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                  <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="badge bg-success text-white rounded-pill px-3 py-1">
                        <i class="bi bi-file-earmark-check me-1"></i>บันทึกใบขอซื้อเรียบร้อย
                      </span>
                      <span class="fw-bold text-dark fs-6">{{ pr.pr_number }}</span>
                    </div>
                    <div class="small text-muted">
                      <i class="bi bi-info-circle me-1"></i>ระบบไม่ต้องอนุมัติออนไลน์ สามารถกดปุ่ม <strong>"พิมพ์ใบขอซื้อเสนอ ผอ."</strong> เพื่อพิมพ์เอกสารเสนอ ผอ. ลงนามตัวจริงได้ทันที หรือแก้ไขข้อมูล/รายการ และกด <strong>"บันทึกการแก้ไข"</strong> ได้ตลอดเวลา
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" @click="openPrintModal">
                      <i class="bi bi-printer-fill"></i>
                      <span>พิมพ์ใบขอซื้อเสนอ ผอ.</span>
                    </button>
                  </div>
                </div>
              </div>

              <form @submit.prevent="savePR">
                <!-- Section 1: ข้อมูลใบขอซื้อ -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0 text-danger">
                      <i class="bi bi-info-circle me-1"></i> 1. ข้อมูลใบขอซื้อ
                    </h5>
                    <span v-if="isEdit" class="badge bg-warning text-dark rounded-pill px-3 py-1">
                      <i class="bi bi-pencil-square me-1"></i>กำลังแก้ไขข้อมูล PR
                    </span>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">เลือกคลังวัสดุ <span class="text-danger">*</span></label>
                      <select class="form-select" v-model="pr.warehouse_id" required>
                        <option value="">-- เลือกคลัง --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">[{{ w.code }}] {{ w.name }}</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">วันที่ขอซื้อ <span class="text-danger">*</span></label>
                      <input type="date" class="form-control" v-model="pr.request_date" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">ผู้ขอซื้อ <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" v-model="pr.requester_name" required placeholder="ระบุชื่อ-สกุล">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">เหตุผลการขอซื้อ</label>
                      <textarea class="form-control" v-model="pr.reason" rows="2" placeholder="เช่น เพื่อสำรองใช้ในคลัง..."></textarea>
                    </div>
                  </div>
                </div>

                <!-- Section 2: รายการวัสดุ -->
                <div class="mb-4">
                  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold mb-0 text-danger">
                      <i class="bi bi-cart-plus me-1"></i> 2. รายการวัสดุที่ต้องการสั่งซื้อ
                    </h5>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill shadow-sm" @click="openItemModal">
                      <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการ
                    </button>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>รหัส</th>
                          <th>รายการวัสดุ</th>
                          <th>จำนวนที่ขอ <span class="text-danger">*</span></th>
                          <th>หน่วยนับ</th>
                          <th>ราคาประเมิน/หน่วย</th>
                          <th>รวมเงิน (โดยประมาณ)</th>
                          <th width="80" class="text-center">ลบ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="pr.items.length === 0">
                          <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีรายการวัสดุ</td>
                        </tr>
                        <tr v-for="(item, index) in pr.items" :key="index">
                          <td>{{ item.item_code }}</td>
                          <td>{{ item.item_name }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.qty_requested" min="1" required>
                          </td>
                          <td>{{ item.unit_name }}</td>
                          <td style="width: 150px;">
                            <input type="number" class="form-control form-control-sm text-end" v-model.number="item.estimated_price" min="0" step="0.01">
                          </td>
                          <td class="text-end fw-bold">{{ (item.qty_requested * item.estimated_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                          <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeItem(index)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td :colspan="5" class="text-end fw-bold bg-light">ยอดรวมประเมินทั้งสิ้น :</td>
                          <td class="text-end fw-bold bg-light text-danger fs-5">{{ totalEstimatedPrice.toLocaleString('th-TH', {minimumFractionDigits: 2}) }} ฿</td>
                          <td class="bg-light"></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mt-5">
                  <div>
                    <button v-if="isEdit" type="button" class="btn btn-outline-danger px-4 rounded-pill fw-bold shadow-sm" @click="deleteThisPR">
                      <i class="bi bi-trash-fill me-1"></i> ลบใบขอซื้อนี้
                    </button>
                  </div>
                  <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="button" class="btn btn-light border px-4 rounded-pill fw-bold shadow-sm" @click="$router.push('/inventory-pr')">
                      <i class="bi bi-arrow-left"></i> กลับหน้ารายการ
                    </button>
                    <button v-if="isEdit" type="button" class="btn btn-outline-success px-4 rounded-pill fw-bold shadow-sm" @click="openPrintModal">
                      <i class="bi bi-printer-fill me-1"></i> พิมพ์ใบขอซื้อเสนอ ผอ.
                    </button>
                    <button type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" :disabled="saving">
                      <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                      <i class="bi bi-save me-1" v-else></i> {{ isEdit ? 'บันทึกการแก้ไข' : 'บันทึกใบขอซื้อ' }}
                    </button>
                  </div>
                </div>
              </form>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL: พิมพ์ใบขอซื้อ (Print PR Preview Modal)           -->
    <!-- ======================================================= -->
    <div v-if="showPrintModal" class="modal-backdrop fade show no-print"></div>
    <div v-if="showPrintModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
          <div class="modal-header border-bottom p-3 d-flex justify-content-between align-items-center no-print">
            <h5 class="modal-title fw-bold text-dark">
              <i class="bi bi-printer-fill me-2 text-danger"></i>พิมพ์ใบขอซื้อพัสดุ (PR) เสนอ ผอ. ลงนาม
            </h5>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="executePrint">
                <i class="bi bi-printer-fill me-1"></i> สั่งพิมพ์เอกสาร (Print)
              </button>
              <button type="button" class="btn-close" @click="showPrintModal = false"></button>
            </div>
          </div>

          <div class="modal-body p-4 bg-light">
            <!-- Printable Area (A4 Layout) -->
            <div id="printable-pr-area" class="print-page bg-white p-5 mx-auto rounded-3 shadow-sm" style="max-width: 210mm; min-height: 297mm; font-family: 'Sarabun', sans-serif; color: #000;">
              
              <!-- Memo Header -->
              <div class="position-relative text-center mb-4 pb-2">
                <img src="../../../assets/krut.png" alt="Garuda" class="position-absolute" style="left: 0; top: 0; height: 3cm;" />
                <h1 class="fw-bold mb-0" style="font-size: 30pt; letter-spacing: 2px;">บันทึกข้อความ</h1>
              </div>

              <!-- Memo Metadata -->
              <div class="mb-3" style="font-size: 15pt; line-height: 1.8;">
                <div class="d-flex align-items-baseline">
                  <strong style="min-width: 95px;">ส่วนราชการ</strong>
                  <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2">
                    โรงพยาบาลชานุมาน กลุ่มงาน/คลัง {{ pr.warehouse_name || currentWarehouseName }} โทร. ๐๔๕ ๔๖๖๐๐๙
                  </span>
                </div>

                <div class="row g-0 align-items-baseline">
                  <div class="col-6 d-flex align-items-baseline">
                    <strong style="min-width: 40px;">ที่</strong>
                    <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2 font-monospace">
                      อจ ๐๐๓๓.๓๐๑ / {{ pr.pr_number || 'กำลังจัดทำ' }}
                    </span>
                  </div>
                  <div class="col-6 d-flex align-items-baseline ps-3">
                    <strong style="min-width: 50px;">วันที่</strong>
                    <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2">
                      {{ formatThaiDateFull(pr.request_date) }}
                    </span>
                  </div>
                </div>

                <div class="d-flex align-items-baseline">
                  <strong style="min-width: 50px;">เรื่อง</strong>
                  <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2">
                    ขออนุมัติจัดซื้อพัสดุ/เวชภัณฑ์ จำนวน {{ pr.items ? pr.items.length : 0 }} รายการ
                  </span>
                </div>

                <div class="d-flex align-items-baseline mt-2">
                  <strong style="min-width: 50px;">เรียน</strong>
                  <span class="ps-2">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
                </div>
              </div>

              <!-- Memo Body -->
              <div class="mb-3 text-justify" style="font-size: 15pt; text-indent: 2.5cm; line-height: 1.8;">
                ด้วย กลุ่มงาน/คลัง {{ pr.warehouse_name || currentWarehouseName }} โรงพยาบาลชานุมาน มีความประสงค์จะขออนุมัติจัดซื้อพัสดุ/เวชภัณฑ์ เพื่อใช้ในการปฏิบัติราชการและการให้บริการผู้ป่วย โดยมีเหตุผลความจำเป็น ดังนี้:
                <strong>{{ pr.reason || 'เพื่อสำรองใช้ในคลังและสนับสนุนการให้บริการทางการแพทย์' }}</strong>
                โดยขออนุมัติจัดซื้อตามรายการและจำนวน ดังต่อไปนี้
              </div>

              <!-- Items Table -->
              <table class="table table-bordered border-dark table-sm align-middle mb-3" style="font-size: 13pt;">
                <thead class="text-center" style="background-color: #f2f2f2;">
                  <tr>
                    <th style="width: 7%;">ลำดับ</th>
                    <th style="width: 15%;">รหัสพัสดุ</th>
                    <th>รายการพัสดุ / เวชภัณฑ์</th>
                    <th style="width: 12%;">จำนวนขอ</th>
                    <th style="width: 10%;">หน่วยนับ</th>
                    <th style="width: 16%;">ราคาประเมิน/หน่วย</th>
                    <th style="width: 18%;">รวมเป็นเงิน (บาท)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(it, i) in pr.items" :key="i">
                    <td class="text-center">{{ i + 1 }}</td>
                    <td class="text-center font-monospace">{{ it.item_code || '-' }}</td>
                    <td>{{ it.item_name }}</td>
                    <td class="text-center fw-bold">{{ it.qty_requested }}</td>
                    <td class="text-center">{{ it.unit_name || 'หน่วย' }}</td>
                    <td class="text-end">{{ Number(it.estimated_price).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                    <td class="text-end fw-bold">{{ (Number(it.qty_requested) * Number(it.estimated_price)).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}</td>
                  </tr>
                  <tr class="fw-bold">
                    <td colspan="5" class="text-center align-middle" style="background-color: #fafafa;">
                      ({{ thaiBahtText(totalEstimatedPrice) }})
                    </td>
                    <td class="text-end">รวมวงเงินทั้งสิ้น</td>
                    <td class="text-end text-dark" style="background-color: #fafafa;">
                      {{ totalEstimatedPrice.toLocaleString('th-TH', {minimumFractionDigits: 2}) }}
                    </td>
                  </tr>
                </tbody>
              </table>

              <div class="mb-4" style="font-size: 15pt; text-indent: 2.5cm;">
                จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
              </div>

              <!-- Signatures Section: 3 Levels for Manual Signing -->
              <div class="row mt-4 pt-2" style="font-size: 14pt; line-height: 1.6;">
                <!-- Signer 1: Requester -->
                <div class="col-6 text-center mb-4">
                  <div class="mb-4">ลงชื่อ..........................................................ผู้ขอซื้อ</div>
                  <div>( {{ pr.requester_name }} )</div>
                  <div>ตำแหน่ง ..........................................................</div>
                  <div class="text-muted small mt-1">วันที่......../......../........</div>
                </div>

                <!-- Signer 2: Head of Department -->
                <div class="col-6 text-center mb-4">
                  <div class="text-start ps-4 mb-2 small">
                    <div>ความเห็น: [ &nbsp; ] เห็นควรจัดซื้อ &nbsp;&nbsp; [ &nbsp; ] อื่นๆ .............................</div>
                  </div>
                  <div class="mb-4">ลงชื่อ..........................................................หัวหน้ากลุ่มงาน</div>
                  <div>(..........................................................)</div>
                  <div>ตำแหน่ง ..........................................................</div>
                  <div class="text-muted small mt-1">วันที่......../......../........</div>
                </div>

                <!-- Signer 3: Director (ผอ. เซ็นอนุมัติ) -->
                <div class="col-12 mt-2">
                  <div class="border border-dark p-3 rounded-2 mx-auto" style="max-width: 480px;">
                    <div class="fw-bold mb-2 text-center" style="font-size: 15pt;">
                      คำสั่ง / การอนุมัติของผู้อำนวยการโรงพยาบาลชานุมาน
                    </div>
                    <div class="mb-3 d-flex justify-content-center gap-4">
                      <label><input type="checkbox" disabled /> อนุมัติให้จัดซื้อได้</label>
                      <label><input type="checkbox" disabled /> ไม่อนุมัติ</label>
                      <label><input type="checkbox" disabled /> อื่นๆ</label>
                    </div>
                    <div class="text-center">
                      <div class="mb-4">ลงชื่อ..........................................................ผู้อนุมัติ</div>
                      <div class="fw-bold">(นายชาญชัย ศรีแก้ว)</div>
                      <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
                      <div class="mt-1">วันที่......../......../........</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="modal-footer border-top p-3 no-print">
            <button type="button" class="btn btn-secondary rounded-pill px-4" @click="showPrintModal = false">ปิด</button>
            <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="executePrint">
              <i class="bi bi-printer-fill me-1"></i> สั่งพิมพ์เอกสาร (Print)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal เลือกวัสดุ -->
    <div class="modal fade" id="itemModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
          <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>เลือกรายการวัสดุ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <input type="text" class="form-control form-control-lg mb-3 rounded-pill bg-light border-0 px-4" v-model="searchItemQuery" placeholder="พิมพ์ชื่อหรือรหัสวัสดุเพื่อค้นหา...">
            <div class="table-responsive" style="max-height: 400px;">
              <table class="table table-hover align-middle">
                <thead class="table-light sticky-top">
                  <tr>
                    <th>รหัส</th>
                    <th>ชื่อรายการ</th>
                    <th>หน่วย</th>
                    <th width="100">เลือก</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredItems" :key="item.id">
                    <td>{{ item.item_code }}</td>
                    <td>{{ item.item_name }}</td>
                    <td>{{ item.unit_name }}</td>
                    <td>
                      <button class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-bold" @click="selectItem(item)" :disabled="isItemAlreadyAdded(item.id)">
                        {{ isItemAlreadyAdded(item.id) ? 'เลือกแล้ว' : 'เพิ่ม' }}
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredItems.length === 0">
                    <td colspan="4" class="text-center py-4 text-muted">ไม่พบรายการวัสดุ</td>
                  </tr>
                </tbody>
              </table>
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
import * as bootstrap from 'bootstrap';

export default {
  name: 'PRForm',
  data() {
    return {
      pr: {
        id: 0,
        pr_number: '',
        warehouse_id: '',
        request_date: new Date().toISOString().split('T')[0],
        requester_name: '',
        reason: '',
        status: 'DIR_APPROVED',
        items: []
      },
      warehouses: [],
      availableItems: [],
      searchItemQuery: '',
      loading: false,
      saving: false,
      itemModal: null,
      isEdit: false,

      // Print Modal
      showPrintModal: false
    };
  },
  computed: {
    canEdit() {
      // สามารถแก้ไขได้ตลอดสำหรับใบขอซื้อ
      return true;
    },
    currentWarehouseName() {
      if (this.pr.warehouse_name) return this.pr.warehouse_name;
      const w = this.warehouses.find(x => x.id == this.pr.warehouse_id);
      return w ? w.name : 'วัสดุและเวชภัณฑ์';
    },
    filteredItems() {
      if (!this.searchItemQuery) return this.availableItems;
      const q = this.searchItemQuery.toLowerCase();
      return this.availableItems.filter(i => 
        i.item_name.toLowerCase().includes(q) || 
        i.item_code.toLowerCase().includes(q)
      );
    },
    totalEstimatedPrice() {
      return this.pr.items.reduce((sum, item) => sum + (item.qty_requested * item.estimated_price), 0);
    },
    alertClass() {
      switch (this.pr.status) {
        case 'PENDING': return 'alert-warning';
        case 'HEAD_APPROVED': return 'alert-info';
        case 'DIR_APPROVED': return 'alert-success';
        case 'HEAD_REJECTED':
        case 'DIR_REJECTED': return 'alert-danger';
        default: return 'alert-secondary';
      }
    },
    alertIcon() {
      switch (this.pr.status) {
        case 'PENDING': return 'bi-hourglass-split';
        case 'HEAD_APPROVED': return 'bi-check-circle';
        case 'DIR_APPROVED': return 'bi-check-circle-fill';
        case 'HEAD_REJECTED':
        case 'DIR_REJECTED': return 'bi-x-circle-fill';
        default: return 'bi-info-circle';
      }
    }
  },
  async mounted() {
    await this.fetchWarehouses();
    const prId = this.$route.query.id;
    if (prId) {
      this.isEdit = true;
      await this.fetchPR(prId);
    } else {
      await this.fetchUserProfile();
    }
  },
  methods: {
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data.status === 'success') {
          this.pr.requester_name = res.data.fullname;
        }
      } catch (err) {
        console.error('Failed to load user profile', err);
      }
    },
    async fetchWarehouses() {
      try {
        const userId = localStorage.getItem('user_id') || '';
        const res = await axios.get(`/api-digital/inventory/warehouses.php?user_id=${userId}`);
        if (res.data.status === 'success') {
          this.warehouses = res.data.data.filter(w => parseInt(w.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    async fetchPR(id) {
      try {
        const res = await axios.get(`/api-digital/inventory/pr_get.php?id=${id}`);
        if (res.data.status === 'success') {
          const data = res.data.data;
          data.request_date = data.request_date.split(' ')[0]; // extract YYYY-MM-DD
          // numeric parsing
          data.items.forEach(i => {
            i.qty_requested = Number(i.qty_requested);
            i.estimated_price = Number(i.estimated_price);
          });
          this.pr = data;
        } else {
          Swal.fire('Error', 'ไม่พบใบขอซื้อนี้', 'error');
          this.$router.push('/inventory-pr');
        }
      } catch (err) {
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
      }
    },
    async fetchItemsForWarehouse() {
      if (!this.pr.warehouse_id) return;
      try {
        // Use the existing items.php
        const res = await axios.get(`/api-digital/inventory/items.php?warehouse_id=${this.pr.warehouse_id}`);
        if (res.data.status === 'success') {
          // For now, load all items. In a strict system, maybe filter by warehouse's categories.
          this.availableItems = res.data.data.filter(i => parseInt(i.active) === 1);
        }
      } catch (err) {
        console.error(err);
      }
    },
    async openItemModal() {
      if (!this.pr.warehouse_id) {
        Swal.fire('Warning', 'กรุณาเลือกคลังวัสดุก่อนเพิ่มรายการ', 'warning');
        return;
      }
      if (this.availableItems.length === 0) {
        await this.fetchItemsForWarehouse();
      }
      if (!this.itemModal) {
        this.itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
      }
      this.searchItemQuery = '';
      this.itemModal.show();
    },
    isItemAlreadyAdded(itemId) {
      return this.pr.items.some(i => i.item_id === itemId);
    },
    selectItem(item) {
      this.pr.items.push({
        item_id: item.id,
        item_code: item.item_code,
        item_name: item.item_name,
        unit_name: item.unit_name,
        qty_requested: 1,
        estimated_price: 0
      });
      // Optionally hide modal if single select is desired
      if (this.itemModal) {
        this.itemModal.hide();
      }
    },
    removeItem(index) {
      this.pr.items.splice(index, 1);
    },
    async savePR() {
      if (!this.pr.warehouse_id || !this.pr.requester_name) {
        Swal.fire('Warning', 'กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
        return;
      }
      if (this.pr.items.length === 0) {
        Swal.fire('Warning', 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ', 'warning');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          id: this.pr.id,
          warehouse_id: this.pr.warehouse_id,
          request_date: this.pr.request_date + ' 00:00:00',
          requester_name: this.pr.requester_name,
          reason: this.pr.reason,
          items: this.pr.items
        };

        const res = await axios.post('/api-digital/inventory/pr_save.php', payload);
        
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.$router.push('/inventory-pr');
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาด', 'error');
      } finally {
        this.saving = false;
      }
    },
    async deleteThisPR() {
      if (!this.pr.id) return;
      const result = await Swal.fire({
        title: 'ยืนยันการลบใบขอซื้อ?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการลบใบขอซื้อเลขที่ <strong class="text-danger">${this.pr.pr_number}</strong> ?<br><small class="text-muted">*รายการพัสดุในใบขอซื้อนี้จะถูกลบออกทั้งหมด</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/pr_delete.php', { id: this.pr.id });
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'ลบสำเร็จ!',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.$router.push('/inventory-pr');
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data.message, 'error');
        }
      } catch (err) {
        console.error('Delete PR error:', err);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบใบขอซื้อได้ กรุณาลองใหม่อีกครั้ง', 'error');
      }
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('th-TH', {
        year: 'numeric', month: 'short', day: 'numeric'
      });
    },
    formatThaiDateFull(dateString) {
      if (!dateString) return '-';
      const dt = new Date(dateString);
      const thMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
      return `${dt.getDate()} ${thMonths[dt.getMonth()]} พ.ศ. ${dt.getFullYear() + 543}`;
    },
    thaiBahtText(amount) {
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
    getStatusText(status) {
      switch (status) {
        case 'COMPLETED': return 'เปิด PO แล้ว';
        case 'REJECTED':
        case 'HEAD_REJECTED':
        case 'DIR_REJECTED': return 'ไม่อนุมัติ';
        default: return 'พร้อมพิมพ์ (เสนอ ผอ. เซ็น)';
      }
    },
    openPrintModal() {
      this.showPrintModal = true;
    },
    executePrint() {
      window.print();
    }
  }
};
</script>

<style scoped>
/* Print CSS */
@media print {
  body * {
    visibility: hidden;
  }
  #printable-pr-area, #printable-pr-area * {
    visibility: visible;
  }
  #printable-pr-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 1.5cm !important;
    box-shadow: none !important;
  }
  .no-print {
    display: none !important;
  }
}
</style>
