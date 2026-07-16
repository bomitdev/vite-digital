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
      class="print-content"
    >
      <!-- Page 1 -->
      <div
        class="print-page bg-white p-5 mx-auto mt-4 portrait-section memo-section position-relative"
        style="max-width: 210mm; min-height: 297mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1)"
      >
      <!-- Action Toolbar (Hidden when printing) -->
      <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 no-print gap-3"
      >
        <button class="btn btn-outline-secondary" @click="closeWindow">
          <i class="bi bi-x-lg"></i> ปิด
        </button>
        <button class="btn btn-primary text-white fw-bold px-3" @click="printPage">
          <i class="bi bi-printer-fill me-1"></i> พิมพ์เอกสาร (PDF)
        </button>
      </div>

      <!-- Memo Header -->
      <div class="memo-header d-flex align-items-center mb-4 position-relative">
        <div class="garuda-wrapper position-absolute" style="left: 0; top: 0;">
          <img src="../../assets/krut.png" alt="Garuda" style="height: 1.5cm;" />
        </div>
        <div class="memo-title-wrapper flex-grow-1 text-center pt-2">
          <h1 class="fw-bold" style="font-size: 29pt; margin:0;">บันทึกข้อความ</h1>
        </div>
      </div>

      <!-- Memo Metadata -->
      <div class="memo-metadata fs-16pt mt-4">
        <div class="d-flex align-items-baseline mb-2">
          <span class="fw-bold fs-20pt label-agency">ส่วนราชการ</span>
          <span class="ms-2 flex-grow-1 dotted-bottom">โรงพยาบาลชานุมาน กลุ่มงานบริหารทั่วไป โทร. ๐๔๕ ๔๖๖๐๐๙</span>
        </div>

        <div class="row g-0 align-items-baseline mb-2">
          <div class="col-6 d-flex align-items-baseline">
            <span class="fw-bold fs-20pt label-ref">ที่</span>
            <span class="ms-2 flex-grow-1 dotted-bottom">อจ.๐๐๓๓.๓๐๑/</span>
          </div>
          <div class="col-6 d-flex align-items-baseline ps-3">
            <span class="fw-bold fs-20pt label-date">วันที่</span>
            <span class="ms-2 flex-grow-1 dotted-bottom">{{ formatThaiDateFull(docData.doc_date) }}</span>
          </div>
        </div>

        <div class="d-flex align-items-baseline mb-2">
          <span class="fw-bold fs-20pt label-subject">เรื่อง</span>
          <span class="ms-2 flex-grow-1 dotted-bottom">รายงานขอซื้อวัสดุเวชภัณฑ์ยา จำนวน ๑ รายการ</span>
        </div>

        <div class="d-flex align-items-baseline mb-4">
          <span class="fw-bold fs-20pt label-to">เรียน</span>
          <span class="ms-2">{{ docData.to_person }}</span>
        </div>
      </div>

      <!-- Memo Body -->
      <div class="memo-body fs-16pt lh-base text-justify" style="text-indent: 2.5cm;">
        ด้วย โรงพยาบาลชานุมาน กลุ่มงานเภสัชกรรม มีความประสงค์จะ ขอซื้อเวชภัณฑ์ยา จำนวน ๑ รายการ โดยวิธีเฉพาะเจาะจง ซึ่งมีรายละเอียด ดังต่อไปนี้
      </div>

      <div class="memo-list fs-16pt lh-base mt-2 ms-4">
        <div class="mb-1"><strong>๑. เหตุผลความจำเป็นที่ต้องซื้อ</strong></div>
        <div class="mb-2 ms-4">{{ docData.reason }}</div>
        
        <div class="mb-1"><strong>๒. รายละเอียดของพัสดุจัดซื้อแนบท้าย</strong></div>
        
        <div class="mb-1 mt-2"><strong>๓. ราคากลางของพัสดุที่จะซื้อ</strong> จำนวน {{ formatCurrency(docData.budget) }} บาท ({{ thaiBahtText(docData.budget) }}) สำหรับราคากลางที่ใช้จัดซื้อจัดจ้างในครั้งนี้ เป็นราคาที่ได้จากการสืบราคาจากท้องตลาดตามพื้นที่จังหวัดอำนาจเจริญ</div>
        
        <div class="mb-1 mt-2"><strong>๔. วงเงินที่จะซื้อเงินนอกงบประมาณรายจ่ายประจำปี พ.ศ. {{ getThaiYear(docData.doc_date) }}</strong> จำนวน {{ formatCurrency(docData.budget) }} บาท ({{ thaiBahtText(docData.budget) }})</div>
        
        <div class="mb-1 mt-2"><strong>๕. กำหนดเวลาที่ต้องการใช้พัสดุนั้น หรือให้งานนั้นแล้วเสร็จ</strong></div>
        <div class="mb-2 ms-4">กำหนดเวลาการส่งมอบพัสดุ หรือให้งานแล้วเสร็จภายใน {{ docData.delivery_days }} วัน นับถัดจากวันลงนามในสัญญา</div>
        
        <div class="mb-1 mt-2"><strong>๖. วิธีที่จะซื้อ และเหตุผลที่ต้องซื้อ</strong></div>
        <div class="mb-2 ms-4 text-justify">โดยวิธีเฉพาะเจาะจง ตาม พรบ.จัดซื้อจัดจ้าง พ.ศ. ๒๕๖๐ มาตรา ๕๖ วรรคแรก(๒)(ข) เนื่องจากการจัดซื้อจัดจ้างพัสดุที่มีการผลิต จำหน่าย ก่อสร้าง หรือให้บริการทั่วไป และมีวงเงินในการจัดซื้อจัดจ้างครั้งหนึ่งไม่เกินวงเงินในการ จัดซื้อจัดจ้างครั้งหนึ่งไม่เกิน ๕๐๐,๐๐๐ บาท ตามกฎกระทรวง วันที่ ๒๓ สิงหาคม ๒๕๖๐</div>
        
        <div class="mb-1 mt-2"><strong>๗. หลักเกณฑ์การพิจารณาคัดเลือกข้อเสนอ</strong></div>
        <div class="mb-2 ms-4">การพิจารณาคัดเลือกข้อเสนอโดยใช้เกณฑ์ราคาต่ำสุด</div>
      </div>

      <!-- Bottom right of Page 1 -->
      <div class="position-absolute fs-16pt" style="bottom: 15mm; right: 25mm;">
        /๘. การขออนุมัติ...
      </div>
    </div> <!-- End Page 1 -->

    <!-- Page 2 -->
    <div
      class="print-page page-break bg-white p-5 mx-auto mt-4 portrait-section memo-section position-relative"
      style="max-width: 210mm; min-height: 297mm; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1)"
    >
      <div class="text-center fs-16pt mb-4">-๒-</div>

      <div class="memo-list fs-16pt lh-base ms-4">
        <div class="mb-1"><strong>๘. การขออนุมัติแต่งตั้งคณะกรรมการต่าง ๆ</strong></div>
        <div class="mb-2 ms-4">
          <div class="mb-1">ผู้ตรวจรับพัสดุ</div>
          <div v-for="(comm, idx) in docData.committee" :key="idx" class="d-flex mb-1">
             <div style="width: 280px;">({{ comm.name }})</div>
             <div>{{ comm.role }}</div>
          </div>
          <div v-for="(comm, idx) in docData.committee" :key="'pos-'+idx" class="ms-3 mb-1">
             {{ comm.position }}
          </div>
        </div>

        <div class="mb-1"><strong>อำนาจหน้าที่</strong></div>
        <div class="mb-3 ms-4">ทำการตรวจรับพัสดุให้เป็นไปตามเงื่อนไขของสัญญาหรือข้อตกลงนั้น</div>
      </div>

      <div class="memo-body fs-16pt lh-base text-justify mt-3" style="text-indent: 2.5cm;">
        จึงเรียนมาเพื่อโปรดทราบ หากเห็นชอบขอได้โปรด อนุมัติให้ดำเนินการ ตามรายละเอียดในรายงานขอซื้อดังกล่าวข้างต้น
      </div>

      <!-- Signatures -->
      <div class="memo-signatures fs-16pt mt-5 pt-3 d-flex flex-column gap-5">
        <div class="text-center ms-auto" style="width: 50%;">
           <div class="mb-4"></div>
           <div>({{ docData.buyer_name || '......................................................' }})</div>
           <div>{{ docData.buyer_position || '......................................................' }}</div>
           <div>เจ้าหน้าที่</div>
        </div>
        
        <div class="text-center ms-auto" style="width: 50%;">
           <div class="mb-4"></div>
           <div>(นายปัญญา กระบวนศรี)</div>
           <div>เภสัชกรชำนาญการพิเศษ</div>
           <div>หัวหน้าเจ้าหน้าที่</div>
        </div>

        <div class="text-center mx-auto mt-4" style="width: 60%;">
           <div class="fw-bold mb-5">เห็นชอบ/อนุมัติ</div>
           <div class="d-flex align-items-end justify-content-center mb-2">
             <div class="me-2">ลงชื่อ</div>
             <div class="dotted-bottom" style="width: 200px;"></div>
           </div>
           <div>(นายธนากร คนเพียร)</div>
           <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
           <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
           <div>ปฏิบัติราชการแทนผู้ว่าราชการจังหวัดอำนาจเจริญ</div>
        </div>
      </div>

    </div> <!-- End Page 2 -->
  </div>
  </div>
</template>

<script>
import axios from 'axios';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'PrintMemoView',
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
        // We only have get_bills.php but we can filter by ID.
        // Wait, the easiest is just fetching the document data, which includes bill_id, and fetching bills then finding it.
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

.dotted-bottom {
  border-bottom: 1px dotted #000;
  display: inline-block;
  min-height: 1.2rem;
}

.label-agency { width: 90px; display: inline-block; }
.label-ref { width: 40px; display: inline-block; }
.label-date { width: 50px; display: inline-block; }
.label-subject { width: 50px; display: inline-block; }
.label-to { width: 50px; display: inline-block; }

.memo-body { line-height: 1.15; }
.memo-list { line-height: 1.15; }

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
    padding: 5mm 20mm 15mm 30mm !important;
    color: #000 !important;
  }
  
  .no-print { display: none !important; }
  
  .page-break { 
    page-break-before: always !important; 
  }
  
  body * {
    visibility: hidden;
  }

  .print-page,
  .print-page * {
    visibility: visible !important;
  }
}
</style>
