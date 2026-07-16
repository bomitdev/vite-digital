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
        <h2 class="fw-bold fs-20pt">ใบตรวจรับพัสดุ</h2>
      </div>
      <div class="text-end mb-4 fs-16pt">
         เขียนที่ โรงพยาบาลชานุมาน จังหวัดอำนาจเจริญ
      </div>
      <div class="text-end mb-4 fs-16pt">
         วันที่ {{ formatThaiDateFull(docData.doc_date) }}
      </div>

      <!-- Body -->
      <div class="memo-body fs-16pt lh-base text-justify mt-5" style="text-indent: 2.5cm;">
        ตามที่ โรงพยาบาลชานุมาน ได้ตกลงซื้อ {{ bill.notes || 'พัสดุอุปกรณ์' }} จำนวน ๑ รายการ จาก {{ bill.vendor_name }} ตามใบสั่งซื้อ เลขที่ ๑๘๘๐/{{ getThaiYear(docData.doc_date) }} ลงวันที่ {{ formatThaiDateFull(docData.doc_date) }} 
        ครบกำหนดส่งมอบวันที่ {{ calculateDeliveryDate(docData.doc_date, docData.delivery_days) }}
      </div>

      <div class="memo-body fs-16pt lh-base text-justify mt-3" style="text-indent: 2.5cm;">
        บัดนี้ ผู้ขายได้ส่งมอบพัสดุถูกต้องครบถ้วน ตามใบส่งของ/ใบแจ้งหนี้ เลขที่ ......................... ลงวันที่ ......................... 
        คณะกรรมการตรวจรับพัสดุ ได้ตรวจรับพัสดุไว้เป็นการถูกต้องครบถ้วน ตามเงื่อนไขแห่งข้อตกลงและใบสั่งซื้อแล้ว 
        จึงได้ลงลายมือชื่อไว้เป็นหลักฐาน
      </div>

      <div class="memo-body fs-16pt lh-base text-justify mt-3" style="text-indent: 2.5cm;">
        จึงรายงานผลการตรวจรับพัสดุ เพื่อโปรดทราบและพิจารณาอนุมัติเบิกจ่ายเงิน จำนวน {{ formatCurrency(docData.budget) }} บาท ({{ thaiBahtText(docData.budget) }}) ให้แก่ผู้ขายต่อไป
      </div>

      <!-- Signatures (Committee) -->
      <div class="memo-signatures fs-16pt mt-5 pt-5 d-flex flex-column align-items-center gap-5">
        <div class="text-center" style="width: 60%;" v-for="(comm, idx) in docData.committee" :key="idx">
           <div class="mb-4">(ลงชื่อ) .............................................................. {{ comm.role }}</div>
           <div>({{ comm.name }})</div>
           <div>{{ comm.position }}</div>
        </div>
      </div>
      
      <!-- Approver -->
      <div class="memo-signatures fs-16pt mt-5 pt-4">
        <div class="text-center ms-auto" style="width: 50%;">
           <div class="fw-bold mb-4">ทราบ / อนุมัติเบิกจ่าย</div>
           <div class="mb-4">(ลงชื่อ) ..............................................................</div>
           <div>(นายธนากร คนเพียร)</div>
           <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
           <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'PrintInspectionView',
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
