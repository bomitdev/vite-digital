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
              <li class="breadcrumb-item active" aria-current="page">ขอซื้อ (PR)</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-danger mb-0">
            <i class="bi bi-cart-plus-fill me-2"></i>ระบบขอซื้อ/สั่งซื้อ (PR & PO)
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

      <!-- Navigation Tabs PR / PO -->
      <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
          <button class="nav-link active rounded-pill px-4 fw-bold shadow-sm" type="button">
            <i class="bi bi-file-earmark-text me-1"></i> ใบขอซื้อ (PR)
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link rounded-pill px-4 text-dark bg-white shadow-sm border" type="button" @click="$router.push('/inventory-po')">
            <i class="bi bi-bag-check me-1"></i> ใบสั่งซื้อ (PO)
          </button>
        </li>
      </ul>

      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">รายการใบขอซื้อ (Purchase Requests)</h5>
          <button class="btn btn-danger rounded-pill shadow-sm" @click="$router.push('/inventory-pr-form')">
            <i class="bi bi-plus-lg me-1"></i> สร้างใบขอซื้อใหม่
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted">
                <tr>
                  <th class="ps-4">เลขที่เอกสาร</th>
                  <th>วันที่ขอซื้อ</th>
                  <th>คลังที่ร้องขอ</th>
                  <th>ผู้ขอซื้อ</th>
                  <th>จำนวนรายการ</th>
                  <th>สถานะ (Status)</th>
                  <th class="text-end pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                  </td>
                </tr>
                <tr v-else-if="prs.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    ยังไม่มีรายการใบขอซื้อ
                  </td>
                </tr>
                <tr v-for="pr in prs" :key="pr.id" class="cursor-pointer group">
                  <td class="ps-4 fw-bold text-primary">{{ pr.pr_number }}</td>
                  <td>{{ formatDate(pr.request_date) }}</td>
                  <td>{{ pr.warehouse_name }}</td>
                  <td>{{ pr.requester_name }}</td>
                  <td>{{ pr.item_count }} รายการ</td>
                  <td>
                    <span class="badge" :class="getStatusBadgeClass(pr.status)">
                      {{ getStatusText(pr.status) }}
                    </span>
                  </td>
                  <td class="text-end pe-4 text-nowrap">
                    <button class="btn btn-sm btn-outline-success rounded-pill me-1 shadow-xs" @click.stop="printPR(pr)" title="พิมพ์ใบขอซื้อเสนอ ผอ. ลงนาม">
                      <i class="bi bi-printer-fill me-1"></i> พิมพ์
                    </button>
                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" @click.stop="viewPR(pr)">
                      <i class="bi bi-pencil-square me-1"></i> แก้ไข / ดู
                    </button>
                    <button class="btn btn-sm btn-outline-danger rounded-pill" @click.stop="deletePR(pr)" title="ลบใบขอซื้อ">
                      <i class="bi bi-trash-fill me-1"></i> ลบ
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
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
            <div v-if="loadingPrintPR" class="text-center py-5">
              <div class="spinner-border text-danger" role="status"></div>
              <div class="text-muted mt-2">กำลังเตรียมเอกสารใบขอซื้อ...</div>
            </div>

            <!-- Printable Area (A4 Layout) -->
            <div v-else-if="selectedPR" id="printable-pr-area" class="print-page bg-white p-5 mx-auto rounded-3 shadow-sm" style="max-width: 210mm; min-height: 297mm; font-family: 'Sarabun', sans-serif; color: #000;">
              
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
                    โรงพยาบาลชานุมาน กลุ่มงาน/คลัง {{ selectedPR.warehouse_name || 'วัสดุและเวชภัณฑ์' }} โทร. ๐๔๕ ๔๖๖๐๐๙
                  </span>
                </div>

                <div class="row g-0 align-items-baseline">
                  <div class="col-6 d-flex align-items-baseline">
                    <strong style="min-width: 40px;">ที่</strong>
                    <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2 font-monospace">
                      อจ ๐๐๓๓.๓๐๑ / {{ selectedPR.pr_number }}
                    </span>
                  </div>
                  <div class="col-6 d-flex align-items-baseline ps-3">
                    <strong style="min-width: 50px;">วันที่</strong>
                    <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2">
                      {{ formatThaiDateFull(selectedPR.request_date) }}
                    </span>
                  </div>
                </div>

                <div class="d-flex align-items-baseline">
                  <strong style="min-width: 50px;">เรื่อง</strong>
                  <span class="border-bottom border-dark border-dotted flex-grow-1 ps-2">
                    ขออนุมัติจัดซื้อพัสดุ/เวชภัณฑ์ จำนวน {{ selectedPR.items ? selectedPR.items.length : 0 }} รายการ
                  </span>
                </div>

                <div class="d-flex align-items-baseline mt-2">
                  <strong style="min-width: 50px;">เรียน</strong>
                  <span class="ps-2">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
                </div>
              </div>

              <!-- Memo Body -->
              <div class="mb-3 text-justify" style="font-size: 15pt; text-indent: 2.5cm; line-height: 1.8;">
                ด้วย กลุ่มงาน/คลัง {{ selectedPR.warehouse_name || 'วัสดุและเวชภัณฑ์' }} โรงพยาบาลชานุมาน มีความประสงค์จะขออนุมัติจัดซื้อพัสดุ/เวชภัณฑ์ เพื่อใช้ในการปฏิบัติราชการและการให้บริการผู้ป่วย โดยมีเหตุผลความจำเป็น ดังนี้:
                <strong>{{ selectedPR.reason || 'เพื่อสำรองใช้ในคลังและสนับสนุนการให้บริการทางการแพทย์' }}</strong>
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
                  <tr v-for="(it, i) in selectedPR.items" :key="i">
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
                      ({{ thaiBahtText(calculateTotal(selectedPR.items)) }})
                    </td>
                    <td class="text-end">รวมวงเงินทั้งสิ้น</td>
                    <td class="text-end text-dark" style="background-color: #fafafa;">
                      {{ calculateTotal(selectedPR.items).toLocaleString('th-TH', {minimumFractionDigits: 2}) }}
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
                  <div>( {{ selectedPR.requester_name }} )</div>
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
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'PRDashboard',
  data() {
    return {
      prs: [],
      loading: true,

      // Print Modal
      showPrintModal: false,
      loadingPrintPR: false,
      selectedPR: null
    };
  },
  mounted() {
    this.fetchPRs();
  },
  methods: {
    async fetchPRs() {
      this.loading = true;
      try {
        const response = await axios.get('/api-digital/inventory/pr_list.php');
        if (response.data.status === 'success') {
          this.prs = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'ไม่สามารถโหลดข้อมูล PR ได้', 'error');
        }
      } catch (error) {
        console.error('Error fetching PRs:', error);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      } finally {
        this.loading = false;
      }
    },
    async printPR(pr) {
      this.selectedPR = null;
      this.loadingPrintPR = true;
      this.showPrintModal = true;
      try {
        const res = await axios.get(`/api-digital/inventory/pr_get.php?id=${pr.id}`);
        if (res.data.status === 'success') {
          this.selectedPR = res.data.data;
        } else {
          Swal.fire('Error', 'ไม่สามารถโหลดรายละเอียดเอกสารได้', 'error');
          this.showPrintModal = false;
        }
      } catch (err) {
        console.error('Error loading PR for print:', err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
        this.showPrintModal = false;
      } finally {
        this.loadingPrintPR = false;
      }
    },
    executePrint() {
      window.print();
    },
    calculateTotal(items) {
      if (!items || !items.length) return 0;
      return items.reduce((sum, it) => sum + (Number(it.qty_requested || 0) * Number(it.estimated_price || 0)), 0);
    },
    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('th-TH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
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
    getStatusBadgeClass(status) {
      switch (status) {
        case 'COMPLETED': return 'bg-primary text-white';
        case 'REJECTED':
        case 'HEAD_REJECTED': 
        case 'DIR_REJECTED': return 'bg-danger text-white';
        default: return 'bg-success text-white'; // พร้อมพิมพ์ / รอ ผอ. เซ็น
      }
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
    viewPR(pr) {
      this.$router.push(`/inventory-pr-form?id=${pr.id}`);
    },
    async deletePR(pr) {
      const result = await Swal.fire({
        title: 'ยืนยันการลบใบขอซื้อ?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการลบใบขอซื้อเลขที่ <strong class="text-danger">${pr.pr_number}</strong> ?<br><small class="text-muted">*รายการพัสดุในใบขอซื้อนี้จะถูกลบออกทั้งหมด</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> ยืนยันลบ',
        cancelButtonText: 'ยกเลิก'
      });

      if (!result.isConfirmed) return;

      try {
        const res = await axios.post('/api-digital/inventory/pr_delete.php', { id: pr.id });
        if (res.data.status === 'success') {
          await Swal.fire({
            icon: 'success',
            title: 'ลบสำเร็จ!',
            text: res.data.message,
            timer: 1500,
            showConfirmButton: false
          });
          this.fetchPRs();
        } else {
          Swal.fire('ไม่สามารถลบได้', res.data.message, 'error');
        }
      } catch (err) {
        console.error('Delete PR error:', err);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบใบขอซื้อได้ กรุณาลองใหม่อีกครั้ง', 'error');
      }
    }
  }
};
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.group:hover td {
  background-color: #f8f9fa;
}

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
