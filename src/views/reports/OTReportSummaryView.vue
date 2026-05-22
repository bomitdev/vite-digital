<template>
  <div class="report-container pt-5 pb-5 bg-light min-vh-100">
    <div class="container d-print-none mb-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0 text-purple fw-bold" style="font-size: 1.75rem">
              <i class="bi bi-file-earmark-spreadsheet me-2"></i> สรุปการเบิกค่าตอบแทนรวม
              (กลุ่มงานสุขภาพดิจิทัล)
            </h2>
            <button @click="$router.push('/manager-schedule')" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-1"></i> กลับตารางเวร
            </button>
          </div>

          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label fw-bold text-muted">เลือกเดือน:</label>
              <select
                class="form-select form-select-lg"
                v-model="selectedMonth"
                @change="fetchData"
              >
                <option v-for="(mName, index) in monthNames" :key="index" :value="index + 1">
                  {{ mName }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold text-muted">เลือกปี (ค.ศ.):</label>
              <select class="form-select form-select-lg" v-model="selectedYear" @change="fetchData">
                <option
                  v-for="y in [currentYear - 1, currentYear, currentYear + 1]"
                  :key="y"
                  :value="y"
                >
                  {{ y }} ({{ y + 543 }})
                </option>
              </select>
            </div>
          </div>

          <div class="row mt-3 border-top pt-3 d-print-none">
            <div class="col-12 d-flex flex-wrap gap-3 justify-content-center align-items-center">
              <button
                class="btn btn-lg btn-info text-white shadow-sm px-4"
                @click="printReport('memo')"
              >
                <i class="bi bi-printer me-2"></i> พิมพ์บันทึกข้อความ
              </button>

              <button class="btn btn-lg btn-success shadow-sm px-4" @click="printReport('table')">
                <i class="bi bi-printer me-2"></i> พิมพ์ตารางรวม
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5 d-print-none">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">กำลังดึงข้อมูลและประมวลผลสรุป...</p>
    </div>

    <div v-if="!isLoading" class="report-content" :class="printMode">
      <!-- 1: บันทึกข้อความ (Page 1 - แนวตั้ง Portrait) -->
      <div
        v-if="summaryData.length > 0"
        class="print-page memo-section portrait-section bg-white shadow-sm mb-4 mx-auto pt-5 pb-4 px-4 memo-print-section"
      >
        <!-- Header: Garuda and Title -->
        <div class="memo-header d-flex align-items-center mb-2">
          <div class="garuda-wrapper">
            <img
              src="../../assets/krut.png"
              alt="Garuda"
              class="garuda-img"
              @error="$event.target.style.display = 'none'"
            />
          </div>

          <div class="memo-title-wrapper flex-grow-1 text-center">
            <h1 class="memo-title">บันทึกข้อความ</h1>
          </div>
        </div>

        <div class="memo-metadata">
          <div class="d-flex align-items-baseline mb-0">
            <span class="fw-bold fs-20pt label-agency">ส่วนราชการ</span>
            <span class="fs-16pt">กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน</span>
          </div>

          <div class="row g-0 align-items-baseline">
            <div class="col-6 d-flex align-items-baseline">
              <span class="fw-bold fs-18pt label-ref">ที่</span>
              <span class="fs-16pt">อจ.0033.314/</span>
            </div>
            <div class="col-6 d-flex align-items-baseline">
              <span class="fw-bold fs-20pt label-date">วันที่</span>
              <span class="fs-16pt">
                {{ new Date().getDate() }}
                {{ monthNames[new Date().getMonth()] }}
                {{ new Date().getFullYear() + 543 }}
              </span>
            </div>
          </div>

          <div class="d-flex align-items-baseline mb-0">
            <span class="fw-bold fs-20pt label-subject">เรื่อง</span>
            <span class="fs-16pt">
              ขออนุมัติ - เบิกจ่ายเงินค่าตอบแทนนอกเวลาราชการ ประจำเดือน
              {{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }}
            </span>
          </div>

          <div class="d-flex align-items-baseline mb-3">
            <span class="fw-bold fs-18pt label-to">เรียน</span>
            <span class="fs-16pt">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
          </div>
        </div>

        <!-- Content Body -->
        <div class="memo-body fs-16pt">
          <div class="content-paragraph text-justify">
            ด้วยโรงพยาบาลชานุมาน อำเภอชานุมาน จังหวัดอำนาจเจริญ
            มีเจ้าหน้าที่ประจำกลุ่มงานสุขภาพดิจิทัล ได้ปฏิบัติงาน นอกเวลาราชการในวันราชการ
            ประจำเดือน{{ monthNames[selectedMonth - 1] }} {{ selectedYear + 543 }} จำนวน
            {{ summaryData.length }} ราย เป็นเงิน {{ grandTotal.toLocaleString() }} บาท ({{
              thaiBahtText(grandTotal)
            }})
          </div>
          <div class="content-paragraph mb-2">จึงเรียนมาเพื่อโปรดทราบและพิจารณาอนุมัติ</div>

          <!-- Signatures Section -->
          <div class="memo-signatures">
            <!-- Signature 1: Requester -->
            <div class="signature-block ms-auto text-center" style="width: 50%">
             <br>
              <div class="mb-1">(นายศราวุฒิ แสนโท)</div>
              <div>นักวิชาการคอมพิวเตอร์ปฏิบัติการ</div>
              <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
            </div>

            <!-- Signature 2: Routine/Check -->
            <div class="signature-block mt-3">
              <div class="mb-1">เรียน ผู้อำนวยการโรงพยาบาลชานุมาน</div>
              <div class="ps-5">- ได้ตรวจสอบถูกต้องแล้ว</div>
              <div class="ps-5 mb-2">- เห็นควรอนุมัติ</div>
              <div class="signature-block ms-auto text-center" style="width: 50%">
                <br>
                <div class="mb-1">(นายธนากร คนเพียร)</div>
                <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
                <div>หัวหน้ากลุ่มงานบริหารทั่วไป</div>
              </div>
            </div>

            <!-- Signature 3: Approval -->
            <div class="signature-block mt-5">
              <div class="signature-block ms-auto text-center" style="width: 50%">
                <div class="mb-3">อนุมัติ</div>
                <br>
                <div class="mb-1">(นายธนากร คนเพียร)</div>
                <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
                <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2: สรุปตารางรวม (Page 2 - แนวนอน Landscape) -->
      <div
        v-if="summaryData.length > 0"
        class="print-page table-section landscape-section bg-white shadow-sm mb-4 mx-auto pt-5 pb-4 px-4"
      >
        <div class="text-center mb-2">
          <h2 class="fw-bold" style="font-size: 16pt">
            สรุปการเบิกค่าตอบแทนนอกเวลาราชการ ประจำเดือน {{ monthNames[selectedMonth - 1] }} ปี
            {{ selectedYear + 543 }}
          </h2>
        </div>

        <table
          class="table table-bordered border-dark table-sm report-table align-middle text-center"
          style="font-size: 16pt"
        >
          <thead>
            <tr class="bg-light">
              <th class="align-middle" style="width: 45px">ลำดับ</th>
              <th class="align-middle" style="width: 220px">ชื่อ - สกุล</th>
              <th class="align-middle" style="width: 220px">ตำแหน่ง</th>
              <th class="align-middle" style="width: 90px; line-height: 1.3">
                ขึ้นเวร<br />ห้องบัตร
              </th>
              <th class="align-middle" style="width: 110px; line-height: 1.3">
                ขึ้นเวร<br />คอมพิวเตอร์
              </th>
              <th class="align-middle" style="width: 95px; line-height: 1.3">
                ขึ้นเวร<br />เบิกเคลม
              </th>
              <th class="align-middle" style="width: 90px">รวม</th>
              <th class="align-middle" style="width: 120px; line-height: 1.3">
                ลายมือชื่อ<br />ผู้รับเงิน
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(emp, index) in summaryData" :key="index">
              <td>{{ index + 1 }}</td>
              <td class="text-start px-2 text-nowrap">{{ emp.name }}</td>
              <td class="text-start px-2">{{ emp.position }}</td>
              <td class="text-end px-2">{{ emp.opd ? emp.opd.toLocaleString() : '' }}</td>
              <td class="text-end px-2">{{ emp.it ? emp.it.toLocaleString() : '' }}</td>
              <td class="text-end px-2">{{ emp.claim ? emp.claim.toLocaleString() : '' }}</td>
              <td class="text-end px-2">{{ emp.total.toLocaleString() }}</td>
              <td></td>
            </tr>
            <tr class="fw-bold">
              <td colspan="3" class="text-center">รวม</td>
              <td class="text-end px-2">
                {{ totalOpdCard ? totalOpdCard.toLocaleString() : '0' }}
              </td>
              <td class="text-end px-2">{{ totalIt ? totalIt.toLocaleString() : '0' }}</td>
              <td class="text-end px-2">{{ totalClaim ? totalClaim.toLocaleString() : '0' }}</td>
              <td class="text-end px-2">{{ grandTotal.toLocaleString() }}</td>
              <td></td>
            </tr>
          </tbody>
        </table>

        <!-- Footer Text Amount -->
        <div class="d-flex mt-0 ps-4 align-items-center" style="font-size: 16pt">
          <div class="fw-bold me-4" style="width: 150px">จำนวนเงิน</div>
          <div class="fw-bold">{{ thaiBahtText(grandTotal) }}</div>
        </div>

        <!-- Signatures (Table Page) -->
        <div class="row mt-1 pt-1" style="font-size: 16pt">
          <div class="col-6 offset-6 text-center">
            <div class="mb-1">
              (ลงชื่อ)......................................................หัวหน้าผู้ควบคุม
            </div>

            <div class="mb-1">(นายศราวุฒิ แสนโท)</div>
            <div>หัวหน้ากลุ่มงานสุขภาพดิจิทัล</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import BAHTTEXT from 'thai-baht-text';

export default {
  name: 'OTReportSummaryView',
  data() {
    const today = new Date();
    return {
      selectedMonth: today.getMonth() + 1,
      selectedYear: today.getFullYear(),
      currentYear: today.getFullYear(),
      monthNames: [
        'มกราคม',
        'กุมภาพันธ์',
        'มีนาคม',
        'เมษายน',
        'พฤษภาคม',
        'มิถุนายน',
        'กรกฎาคม',
        'สิงหาคม',
        'กันยายน',
        'ตุลาคม',
        'พฤศจิกายน',
        'ธันวาคม'
      ],
      isLoading: false,
      summaryData: [],
      printMode: '', // '', memo, table
      memoCategory: 'payment', // payment, duty
      dutyDept: 'opd', // opd, it, claim
      printCategory: '' // 'payment', 'duty', 'all'
    };
  },
  computed: {
    totalOpdCard() {
      return this.summaryData.reduce((sum, emp) => sum + (emp.opd || 0), 0);
    },
    totalIt() {
      return this.summaryData.reduce((sum, emp) => sum + (emp.it || 0), 0);
    },
    totalClaim() {
      return this.summaryData.reduce((sum, emp) => sum + (emp.claim || 0), 0);
    },
    grandTotal() {
      return this.summaryData.reduce((sum, emp) => sum + (emp.total || 0), 0);
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.isLoading = true;
      this.summaryData = [];
      const employeeMap = {};

      try {
        const params = { month: this.selectedMonth, year: this.selectedYear };

        // Fetch from 3 endpoints
        const [resIt, resOpd, resClaim] = await Promise.all([
          axios
            .get('/api-digital/duties/get-ot-report.php', { params })
            .catch(() => ({ data: { data: [] } })),
          axios
            .get('/api-digital/duties_opdcard/get-ot-report-opdcard.php', { params })
            .catch(() => ({ data: { data: [] } })),
          axios
            .get('/api-digital/duties_claim/get-ot-report-claim.php', { params })
            .catch(() => ({ data: { data: [] } }))
        ]);

        // Merge IT
        if (resIt.data && resIt.data.data) {
          resIt.data.data.forEach((emp) => {
            if (emp.total_amount > 0) {
              this.initEmployee(employeeMap, emp);
              employeeMap[emp.name].it = emp.total_amount;
              employeeMap[emp.name].total += emp.total_amount;
            }
          });
        }

        // Merge OPD Card
        if (resOpd.data && resOpd.data.data) {
          resOpd.data.data.forEach((emp) => {
            if (emp.total_amount > 0) {
              this.initEmployee(employeeMap, emp);
              employeeMap[emp.name].opd = emp.total_amount;
              employeeMap[emp.name].total += emp.total_amount;
            }
          });
        }

        // Merge Claim
        if (resClaim.data && resClaim.data.data) {
          resClaim.data.data.forEach((emp) => {
            if (emp.total_amount > 0) {
              this.initEmployee(employeeMap, emp);
              employeeMap[emp.name].claim = emp.total_amount;
              employeeMap[emp.name].total += emp.total_amount;
            }
          });
        }

        const nameOrder = ['ลัดดา', 'ศราวุฒิ', 'สุริยา', 'ธีระพงษ์', 'ยุทธชัย', 'ศิริลักษณ์'];

        this.summaryData = Object.values(employeeMap).sort((a, b) => {
          const indexA = nameOrder.findIndex((name) => a.name.includes(name));
          const indexB = nameOrder.findIndex((name) => b.name.includes(name));

          if (indexA !== -1 && indexB !== -1) return indexA - indexB;
          if (indexA !== -1) return -1;
          if (indexB !== -1) return 1;
          return a.name.localeCompare(b.name, 'th');
        });
      } catch (error) {
        console.error('Error fetching combined OT report data:', error);
      } finally {
        this.isLoading = false;
      }
    },
    initEmployee(map, emp) {
      if (!map[emp.name]) {
        map[emp.name] = {
          name: emp.name,
          position: emp.position || '', // fallback
          it: 0,
          opd: 0,
          claim: 0,
          total: 0
        };
      } else if (!map[emp.name].position && emp.position) {
        // Keep the best position available if it was empty
        map[emp.name].position = emp.position;
      }
    },
    printReport(mode) {
      this.printMode = mode;
      if (mode === 'memo') {
        this.printCategory = this.memoCategory;
      } else if (mode === 'table') {
        this.printCategory = 'payment'; // Table is always for payment summary
      } else {
        this.printCategory = '';
      }

      this.$nextTick(() => {
        window.print();
        setTimeout(() => {
          this.printMode = '';
          this.printCategory = '';
        }, 500);
      });
    },
    thaiBahtText(amount) {
      if (amount === 0) return 'ศูนย์บาทถ้วน';
      try {
        if (typeof BAHTTEXT === 'function') {
          return BAHTTEXT(amount);
        }
        return amount.toLocaleString() + ' บาทถ้วน';
      } catch (e) {
        return amount.toLocaleString() + ' บาทถ้วน';
      }
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

.report-container {
  font-family: 'TH Sarabun New', sans-serif;
  font-size: 16pt;
  color: #000;
  line-height: 1.1;
}

/* Helper Utilities for Government Sizes */
.fs-20pt {
  font-size: 20pt !important;
}
.fs-18pt {
  font-size: 18pt !important;
}
.fs-16pt {
  font-size: 16pt !important;
}

.memo-header {
  width: 100%;
}

.garuda-wrapper {
  margin-right: -1.5cm; /* Push the title visually to center by compensating for image width */
}

.garuda-img {
  height: 2cm; /* กำหนดความสูงตราครุฑ (มาตรฐาน: 1.5cm สำหรับบันทึกข้อความภายใน / 3cm สำหรับภายนอก) */
  width: auto;
}

/* Memo Section Styles */
.memo-title {
  font-size: 29pt !important;
  font-weight: bold;
  margin: 0 !important;
  font-family: 'TH Sarabun New', sans-serif !important;
}

.memo-metadata {
  margin-bottom: 2mm;
}

.label-agency {
  width: 25mm;
}
.label-ref {
  width: 10mm;
}
.label-date {
  width: 15mm;
}
.label-subject {
  width: 15mm;
}
.label-to {
  width: 15mm;
}

.memo-body {
  line-height: 1.15;
}

.content-paragraph {
  text-indent: 25mm;
  margin-bottom: 2mm;
  text-align: justify;
}

.signature-line {
  border-bottom: 1px dotted #000;
  width: 200px;
  display: inline-block;
  margin-bottom: 10px;
}

.signature-block {
  line-height: 1.1;
  font-size: 16pt;
}

.report-table th,
.report-table td {
  padding: 4px 6px !important;
  border: 1px solid #000 !important;
}

/* --- Screen Preview Styles --- */
@media screen {
  .report-content {
    padding-bottom: 50px;
  }
  .print-page {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
    background: white;
  }
  .portrait-section {
    width: 210mm;
    min-height: 297mm;
    padding: 25mm 20mm 20mm 30mm !important; /* Top Right Bottom Left */
    margin-bottom: 2rem;
  }
  .landscape-section {
    width: 297mm;
    min-height: 210mm;
    padding: 20mm !important;
  }
}

/* --- Print Styles --- */
@media print {
  @page {
    margin: 0;
  }
  @page portrait-page {
    size: A4 portrait;
  }
  @page landscape-page {
    size: A4 landscape;
  }

  html, body {
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    height: auto !important;
    min-height: 0 !important;
  }

  /* Force display only the active sections */
  .report-container, .report-container.min-vh-100.pb-5.pt-5 {
    padding: 0 !important;
    margin: 0 !important;
    min-height: 0 !important;
    background: none !important;
  }

  .print-page {
    margin: 0 !important;
    border: none !important;
    box-shadow: none !important;
    width: 100% !important;
    background: white;
    visibility: visible !important;
  }

  /* Specific Layout for Memo (Page 1) */
  .memo-section {
    page: portrait-page !important;
    width: 210mm !important;
    padding: 5mm 20mm 15mm 30mm !important; /* Standard Government Margins */
    display: block !important;
  }

  /* Specific Layout for Table (Page 2) */
  .table-section {
    page: landscape-page !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 10mm 15mm !important;
    display: block !important;
  }

  body * {
    visibility: hidden;
  }

  .report-content,
  .report-content *,
  .print-page,
  .print-page * {
    visibility: visible !important;
  }

  .d-print-none {
    display: none !important;
  }

  .memo .table-section {
    display: none !important;
  }
  .table .memo-section {
    display: none !important;
  }
}
</style>
