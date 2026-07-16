<template>
  <div class="print-container mt-4">
    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger mx-auto mt-4" style="max-width: 600px">
      {{ error }}
    </div>

    <div
      v-else
      class="print-page bg-white p-5 mx-auto mt-4 portrait-section memo-section"
      style="max-width: 210mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1)"
    >
      <!-- Action Toolbar -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 no-print gap-3">
        <button class="btn btn-outline-secondary" @click="closeWindow">
          <i class="bi bi-x-lg"></i> ปิด
        </button>
        <button class="btn btn-primary text-white fw-bold px-3" @click="printPage">
          <i class="bi bi-printer-fill me-1"></i> พิมพ์เอกสาร (PDF)
        </button>
      </div>

      <!-- Header -->
      <div class="text-center mb-4">
        <h2 class="fw-bold fs-20pt">ใบสั่งซื้อ</h2>
      </div>

      <!-- Metadata -->
      <div class="row fs-16pt mb-4">
        <div class="col-6">
          <div class="d-flex"><div style="width: 50px">ผู้ขาย</div><div>{{ bill.vendor_name }}</div></div>
          <div class="d-flex"><div style="width: 50px">ที่อยู่</div><div>{{ docData.vendor_address }}</div></div>
          <div class="d-flex"><div style="width: 130px">โทรศัพท์</div><div>{{ docData.vendor_tel }}</div></div>
          <div class="d-flex"><div style="width: 130px">เลขประจำตัวผู้เสียภาษี</div><div>{{ docData.vendor_tax_id }}</div></div>
        </div>
        <div class="col-6">
          <div class="d-flex"><div style="width: 80px">ใบสั่งซื้อเลขที่</div><div>๑๘๘๐/{{ getThaiYear(docData.doc_date) }}</div></div>
          <div class="d-flex"><div style="width: 80px">วันที่</div><div>{{ formatThaiDateFull(docData.doc_date) }}</div></div>
          <div class="d-flex"><div style="width: 80px">ส่วนราชการ</div><div>โรงพยาบาลชานุมาน</div></div>
          <div class="d-flex"><div style="width: 80px">ที่อยู่</div><div>๔ หมู่ ๘ ตำบลชานุมาน อำเภอชานุมาน จังหวัดอำนาจเจริญ</div></div>
          <div class="d-flex"><div style="width: 80px">โทรศัพท์</div><div>๐๔๕ ๔๖๖๐๐๙</div></div>
        </div>
      </div>

      <div class="fs-16pt text-center mb-3">
        ตามที่ บริษัท {{ bill.vendor_name }} ได้เสนอราคา ไว้ต่อโรงพยาบาลชานุมาน ซึ่งได้รับราคา และตกลงซื้อ ตามรายการดังต่อไปนี้
      </div>

      <!-- Table -->
      <table class="table table-bordered border-dark text-center align-middle fs-16pt print-table mb-4">
        <thead>
          <tr>
            <th style="width: 8%">ลำดับ</th>
            <th style="width: 40%">รายการ</th>
            <th style="width: 12%">จำนวน</th>
            <th style="width: 10%">หน่วย</th>
            <th style="width: 15%">ราคาต่อหน่วย<br>(บาท)</th>
            <th style="width: 15%">จำนวนเงิน<br>(บาท)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>๑</td>
            <td class="text-start ps-2">{{ bill.notes || 'ค่าพัสดุอุปกรณ์' }}</td>
            <td>๑</td>
            <td>งาน</td>
            <td>{{ formatCurrency(docData.budget) }}</td>
            <td>{{ formatCurrency(docData.budget) }}</td>
          </tr>
          <tr class="fw-bold">
            <td colspan="4" rowspan="3" class="align-middle border-end-0">
               ({{ thaiBahtText(docData.budget) }})
            </td>
            <td class="text-end pe-2 border-start-0">รวมเป็นเงิน</td>
            <td>{{ formatCurrency(docData.budget * 100 / 107) }}</td>
          </tr>
          <tr class="fw-bold">
            <td class="text-end pe-2 border-start-0">ภาษีมูลค่าเพิ่ม ๗%</td>
            <td>{{ formatCurrency((docData.budget * 100 / 107) * 0.07) }}</td>
          </tr>
          <tr class="fw-bold">
            <td class="text-end pe-2 border-start-0">รวมเป็นเงินทั้งสิ้น</td>
            <td>{{ formatCurrency(docData.budget) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Conditions -->
      <div class="fs-16pt mb-4">
        <div>การซื้อ อยู่ภายใต้เงื่อนไขต่อไปนี้</div>
        <div>๑. กำหนดส่งมอบภายใน {{ toThaiDigits(docData.delivery_days) }} วัน นับถัดจากวันที่ผู้ซื้อได้รับใบสั่งซื้อ</div>
        <div>๒. ครบกำหนดส่งมอบวันที่ {{ calculateDeliveryDate(docData.doc_date, docData.delivery_days) }}</div>
        <div>๓. สถานที่ส่งมอบ โรงพยาบาลชานุมาน อ.ชานุมาน จ.อำนาจเจริญ</div>
        <div>๔. ระยะเวลารับประกัน </div>
        <div>๕. สงวนสิทธิ์ค่าปรับกรณีส่งมอบเกินกำหนด โดยคิดค่าปรับเป็นรายวันในอัตราร้อยละ ๐.๒๐ ของราคาสิ่งของที่ยังไม่ได้รับมอบ</div>
        <div>๖. ส่วนราชการสงวนสิทธิ์ที่จะไม่รับมอบถ้าปรากฏว่าสินค้านั้นมีลักษณะไม่ตรงตามรายการที่ระบุไว้ในใบสั่งซื้อ กรณีนี้ผู้ขายจะต้องดำเนินการเปลี่ยนใหม่ให้ถูกต้องตามใบสั่งซื้อทุกประการ</div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'PrintPOView',
  data() {
    return {
      bill: null,
      docData: null,
      loading: true,
      error: null
    };
  },
  async mounted() {
    await this.fetchData();
  },
  methods: {
    async fetchData() {
      const id = this.$route.params.id;
      this.loading = true;
      try {
        const [resBills, resDoc] = await Promise.all([
          axios.get('/api-digital/procurement/get_bills.php'),
          axios.get(`/api-digital/procurement/document_data.php?bill_id=${id}`)
        ]);

        if (resBills.data.status === 'success') {
          this.bill = resBills.data.data.find(b => b.id == id);
        }
        if (resDoc.data.status === 'success' && resDoc.data.data) {
          this.docData = resDoc.data.data;
        }

        if (!this.bill || !this.docData) {
          this.error = 'ไม่พบข้อมูล กรุณาสร้างเอกสารก่อนพิมพ์';
        }
      } catch (err) {
        this.error = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    closeWindow() {
      window.close();
    },
    printPage() {
      window.print();
    },
    calculateDeliveryDate(dateStr, days) {
       if(!dateStr) return '';
       let d = new Date(dateStr);
       d.setDate(d.getDate() + parseInt(days));
       return this.formatThaiDateFull(d.toISOString().split('T')[0]);
    },
    formatThaiDateFull(dateStr) {
      if (!dateStr) return '.....................................';
      const months = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
      const d = new Date(dateStr);
      const day = this.toThaiDigits(d.getDate());
      const month = months[d.getMonth()];
      const year = this.toThaiDigits(d.getFullYear() + 543);
      return `${day} ${month} ${year}`;
    },
    getThaiYear(dateStr) {
       if(!dateStr) return '........';
       return this.toThaiDigits(new Date(dateStr).getFullYear() + 543);
    },
    formatCurrency(val) {
      if (!val) return '๐.๐๐';
      const num = parseFloat(val).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits:2});
      return this.toThaiDigits(num);
    },
    toThaiDigits(str) {
      const thaiDigits = ['๐','๑','๒','๓','๔','๕','๖','๗','๘','๙'];
      return String(str).replace(/[0-9]/g, function(w) {
        return thaiDigits[w];
      });
    },
    thaiBahtText(val) {
      if (!val) return '';
      return BAHTTEXT(parseFloat(val));
    }
  }
};
</script>

<style scoped>
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun.ttf') format('truetype');
  font-weight: normal;
  font-style: normal;
}
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun Bold.ttf') format('truetype');
  font-weight: bold;
  font-style: normal;
}

.print-container {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 2rem 0;
  font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
  color: #000;
}

.fs-16pt { font-size: 16pt !important; }
.fs-18pt { font-size: 18pt !important; }
.fs-20pt { font-size: 20pt !important; }

.print-table th, .print-table td {
   border-color: #000 !important;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 0;
  }
  
  html, body {
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  
  .print-container { 
    background-color: white !important; 
    padding: 0 !important; 
  }
  
  .print-page { 
    box-shadow: none !important; 
    width: 100% !important; 
    padding: 15mm 20mm 15mm 30mm !important;
    color: #000 !important;
  }
  
  .no-print { display: none !important; }

  body * {
    visibility: hidden;
  }

  .print-page,
  .print-page * {
    visibility: visible !important;
  }
}
</style>
