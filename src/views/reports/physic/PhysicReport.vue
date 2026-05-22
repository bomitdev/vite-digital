<template>
  <div class="container py-4">
    <div class="card report-card mb-4">
      <div class="card-header report-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="report-title mb-1">รายงานผู้ป่วยกายภาพ</h1>
            <p class="report-subtitle">กรุณาเลือกช่วงวันที่เพื่อแสดงข้อมูล</p>
          </div>
          <i class="fas fa-notes-medical report-icon"></i>
        </div>
      </div>

      <div class="card-body">
        <!-- Summary Cards Row -->
        <div class="row g-4 mb-4">
          <div class="col-md-4">
            <div class="card shadow border-0 border-start border-4 border-info h-100 bg-light">
              <div class="card-body text-center py-4">
                <h6 class="text-muted fw-bold mb-3 fs-5" style="letter-spacing: 0.5px">
                  ผู้ป่วยที่มารับบริการ วันนี้
                </h6>
                <h2 class="text-info fw-bold mb-0" style="font-size: 2.5rem">
                  <span v-if="loadingSummary" class="spinner-border text-info"></span>
                  <span v-else
                    >{{ formatNumber(visitToday) }}
                    <small class="text-muted fs-6 text-uppercase">ครั้ง</small></span
                  >
                </h2>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow border-0 border-start border-4 border-primary h-100 bg-light">
              <div class="card-body text-center py-4">
                <h6 class="text-muted fw-bold mb-3 fs-5" style="letter-spacing: 0.5px">
                  ผู้ป่วยที่มารับบริการ เดือนนี้
                </h6>
                <h2 class="text-primary fw-bold mb-0" style="font-size: 2.5rem">
                  <span v-if="loadingSummary" class="spinner-border text-primary"></span>
                  <span v-else
                    >{{ formatNumber(visitMonth) }}
                    <small class="text-muted fs-6 text-uppercase">ครั้ง</small></span
                  >
                </h2>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow border-0 border-start border-4 border-success h-100 bg-light">
              <div class="card-body text-center py-4">
                <h6 class="text-muted fw-bold mb-3 fs-5" style="letter-spacing: 0.5px">
                  ผู้ป่วยที่มารับบริการ ทั้งปี
                </h6>
                <h2 class="text-success fw-bold mb-0" style="font-size: 2.5rem">
                  <span v-if="loadingSummary" class="spinner-border text-success"></span>
                  <span v-else
                    >{{ formatNumber(visitYear) }}
                    <small class="text-muted fs-6 text-uppercase">ครั้ง</small></span
                  >
                </h2>
              </div>
            </div>
          </div>
        </div>

        <hr class="mb-4 text-muted opacity-25" />

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" v-model="startDate" class="form-control" id="startDate" />
              <label for="startDate">วันที่เริ่มต้น:</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-floating">
              <input type="date" v-model="endDate" class="form-control" id="endDate" />
              <label for="endDate">วันที่สิ้นสุด:</label>
            </div>
          </div>
        </div>

        <div class="text-center mb-3">
          <button
            class="btn btn-generate px-4 py-2 me-2"
            :disabled="loading || !datesValid"
            @click="fetchAllData"
          >
            <span v-if="!loading"><i class="fas fa-file-medical me-2"></i>ประมวลผลรายงาน</span>
            <span v-else
              ><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลด...</span
            >
          </button>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="!datesValid" class="alert alert-warning">กรุณาเลือกช่วงวันที่ให้ถูกต้อง</div>
      </div>
    </div>

    <!-- Treatment Value Summary -->
    <div v-if="mergedPriceData.length > 0" class="card mt-4 report-card">
      <div class="card-header bg-gradient-info text-white rounded-top shadow-sm">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="mb-0 fs-4">
            <i class="fas fa-hand-holding-usd me-2"></i>สรุปมูลค่าการรักษา (OPD และ IPD)
          </h2>
          <div>
            <button
              class="btn btn-light btn-sm text-success fw-bold me-3 shadow-sm rounded-pill px-3"
              @click="exportTreatmentToExcel"
            >
              <i class="fas fa-file-excel me-1"></i>ส่งออก Excel
            </button>
            <span class="badge bg-light text-dark rounded-pill fs-6 py-2 px-3 shadow-sm"
              >{{ formatCurrency(totalPriceParams) }} บาท</span
            >
          </div>
        </div>
      </div>

      <div class="card-body bg-white py-4 p-md-5">
        <!-- Chart Section -->
        <div class="mb-5" style="height: 400px; position: relative">
          <Line :data="chartData" :options="chartOptions" />
        </div>

        <!-- Table Section -->
        <div class="table-responsive rounded border shadow-sm">
          <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="bg-light sticky-top text-center">
              <tr>
                <th rowspan="2" class="py-3 ps-3 text-start align-middle">สิทธิการรักษา</th>
                <th colspan="2" class="py-2 border-bottom text-primary">ผู้ป่วยนอก (OPD)</th>
                <th colspan="2" class="py-2 border-bottom text-danger">ผู้ป่วยใน (IPD)</th>
                <th rowspan="2" class="py-3 pe-3 text-end align-middle">รวมทั้งหมด (บาท)</th>
              </tr>
              <tr>
                <th class="py-2 text-muted fw-normal border-end">จำนวนครั้ง</th>
                <th class="py-2 text-muted fw-normal border-end">มูลค่า (บาท)</th>
                <th class="py-2 text-muted fw-normal border-end">จำนวนครั้ง</th>
                <th class="py-2 text-muted fw-normal">มูลค่า (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in mergedPriceData" :key="idx">
                <td class="ps-3 fw-bold text-secondary">{{ item.pttype_name }}</td>
                <td class="text-center border-end">{{ formatNumber(item.opd_visit) }}</td>
                <td class="text-end border-end text-primary fw-bold">
                  {{ formatCurrency(item.opd_price) }}
                </td>
                <td class="text-center border-end">{{ formatNumber(item.ipd_visit) }}</td>
                <td class="text-end text-danger fw-bold">{{ formatCurrency(item.ipd_price) }}</td>
                <td class="text-end pe-3 fw-bold text-dark">
                  {{ formatCurrency(item.total_price) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light fw-bold border-top-2">
              <tr>
                <td class="ps-3 text-primary">รวมทั้งหมด</td>
                <td class="text-center border-end">{{ formatNumber(totalOpdVisitCount) }}</td>
                <td class="text-end border-end text-primary">
                  {{ formatCurrency(totalOpdPrice) }}
                </td>
                <td class="text-center border-end">{{ formatNumber(totalIpdVisitCount) }}</td>
                <td class="text-end text-danger">{{ formatCurrency(totalIpdPrice) }}</td>
                <td class="text-end pe-3 text-dark fs-5">
                  {{ formatCurrency(totalPriceParams) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ICD10 Data -->
    <div v-if="data.length" class="card mt-4 report-card">
      <div class="card-header bg-white shadow-sm border-bottom-0 pb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h2 class="mb-1 fs-4">
              <i class="fas fa-disease me-2 text-warning"></i>สถิติรหัสโรค 15 อันดับแรก และจำนวน
            </h2>
            <p class="text-muted mb-0 small">
              ตั้งแต่ {{ formattedStartDate }} ถึง {{ formattedEndDate }}
            </p>
          </div>
          <div>
            <button
              class="btn btn-outline-success btn-sm fw-bold me-3 rounded-pill px-3"
              @click="exportICD10ToExcel"
            >
              <i class="fas fa-file-excel me-1"></i>ส่งออก Excel
            </button>
            <span class="badge bg-primary rounded-pill py-2 px-3 shadow-sm fs-6"
              >{{ data.length }} โรค</span
            >
          </div>
        </div>
      </div>

      <div class="card-body bg-white py-4 p-md-5">
        <!-- ICD10 Chart Section -->
        <div class="mb-5" style="height: 350px; position: relative" v-if="data.length > 0">
          <Line :data="icd10ChartData" :options="icd10ChartOptions" />
        </div>

        <div class="table-responsive rounded border shadow-sm">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="py-3 ps-3">ICD10</th>
                <th class="py-3 text-center">จำนวนผู้ป่วย (HN)</th>
                <th class="py-3 text-center">จำนวนครั้ง (Visit)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.pdx">
                <td class="ps-3 fw-bold text-secondary">{{ item.pdx }}</td>
                <td class="text-center fw-bold text-primary">{{ formatNumber(item.C_hn) }}</td>
                <td class="text-center fw-bold text-success">{{ formatNumber(item.C_vn) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer bg-light text-end border-top-0 pt-3">
        <small class="text-muted"
          ><i class="fas fa-clock me-1"></i>อัปเดตล่าสุด: {{ currentTime }}</small
        >
      </div>
    </div>

    <div v-else-if="!loading && processed" class="card mt-4 report-card">
      <div class="card-body text-center py-5">
        <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
        <h4 class="text-muted">ไม่พบข้อมูลในช่วงวันที่ที่เลือก</h4>
      </div>
    </div>
  </div>
</template>

<script>
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Line } from 'vue-chartjs';
import * as XLSX from 'xlsx';

ChartJS.register(CategoryScale, LinearScale, LineElement, PointElement, Title, Tooltip, Legend);

export default {
  components: {
    Line
  },
  data() {
    return {
      startDate: '',
      endDate: '',
      data: [],
      loading: false,
      error: null,
      currentTime: null,
      opdData: [],
      ipdData: [],
      mergedPriceData: [],
      processed: false,
      visitToday: 0,
      visitMonth: 0,
      visitYear: 0,
      loadingSummary: false
    };
  },
  computed: {
    formattedStartDate() {
      return this.formatDate(this.startDate);
    },
    formattedEndDate() {
      return this.formatDate(this.endDate);
    },
    datesValid() {
      if (!this.startDate || !this.endDate) return false;
      return new Date(this.endDate) >= new Date(this.startDate);
    },
    totalOpdVisitCount() {
      return this.mergedPriceData.reduce((sum, item) => sum + item.opd_visit, 0);
    },
    totalOpdPrice() {
      return this.mergedPriceData.reduce((sum, item) => sum + item.opd_price, 0);
    },
    totalIpdVisitCount() {
      return this.mergedPriceData.reduce((sum, item) => sum + item.ipd_visit, 0);
    },
    totalIpdPrice() {
      return this.mergedPriceData.reduce((sum, item) => sum + item.ipd_price, 0);
    },
    totalPriceParams() {
      return this.mergedPriceData.reduce((sum, item) => sum + item.total_price, 0);
    },
    chartData() {
      return {
        labels: this.mergedPriceData.map((d) =>
          d.pttype_name.length > 25 ? d.pttype_name.substring(0, 25) + '...' : d.pttype_name
        ),
        datasets: [
          {
            label: 'ผู้ป่วยนอก (OPD) - บาท',
            backgroundColor: '#0d6efd',
            data: this.mergedPriceData.map((d) => d.opd_price)
          },
          {
            label: 'ผู้ป่วยใน (IPD) - บาท',
            backgroundColor: '#dc3545',
            data: this.mergedPriceData.map((d) => d.ipd_price)
          }
        ]
      };
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: function (context) {
                let value = context.raw || 0;
                return value.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' บาท';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return value.toLocaleString('th-TH');
              }
            }
          }
        }
      };
    },
    icd10ChartData() {
      const topData = this.data.slice(0, 15);
      return {
        labels: topData.map((d) => d.pdx),
        datasets: [
          {
            label: 'จำนวนคน (HN)',
            backgroundColor: '#0d6efd',
            data: topData.map((d) => d.C_hn)
          },
          {
            label: 'จำนวนครั้ง (Visit)',
            backgroundColor: '#198754',
            data: topData.map((d) => d.C_vn)
          }
        ]
      };
    },
    icd10ChartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: function (context) {
                let value = context.raw || 0;
                return value.toLocaleString('th-TH');
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return value.toLocaleString('th-TH');
              }
            }
          }
        }
      };
    }
  },
  methods: {
    async fetchAllData() {
      if (!this.datesValid) return;
      this.loading = true;
      this.processed = true;
      this.error = null;
      this.data = [];
      this.opdData = [];
      this.ipdData = [];
      this.mergedPriceData = [];

      try {
        const [icd10Res, opdRes, ipdRes] = await Promise.all([
          fetch(
            `/api-hosxe/physic/get_icd10_physic.php?start_date=${this.startDate}&end_date=${this.endDate}&physic=16`
          ),
          fetch(
            `/api-hosxe/physic/get_physic_price.php?start_date=${this.startDate}&end_date=${this.endDate}&type=opd`
          ),
          fetch(
            `/api-hosxe/physic/get_physic_price.php?start_date=${this.startDate}&end_date=${this.endDate}&type=ipd`
          )
        ]);

        const icd10Result = await icd10Res.json();
        const opdResult = await opdRes.json();
        const ipdResult = await ipdRes.json();

        if (icd10Res.ok && !icd10Result.error) this.data = icd10Result;

        if (opdRes.ok && !opdResult.error) this.opdData = opdResult;
        if (ipdRes.ok && !ipdResult.error) this.ipdData = ipdResult;

        this.updateCurrentTime();

        // Process Merged Data
        const mergedMap = new Map();

        this.opdData.forEach((item) => {
          mergedMap.set(item.pttype_name, {
            pttype_name: item.pttype_name,
            opd_head: parseInt(item.head_count || 0),
            opd_visit: parseInt(item.visit_count || 0),
            opd_price: parseFloat(item.total_price || 0),
            ipd_head: 0,
            ipd_visit: 0,
            ipd_price: 0
          });
        });

        this.ipdData.forEach((item) => {
          if (mergedMap.has(item.pttype_name)) {
            const existing = mergedMap.get(item.pttype_name);
            existing.ipd_head = parseInt(item.head_count || 0);
            existing.ipd_visit = parseInt(item.visit_count || 0);
            existing.ipd_price = parseFloat(item.total_price || 0);
          } else {
            mergedMap.set(item.pttype_name, {
              pttype_name: item.pttype_name,
              opd_head: 0,
              opd_visit: 0,
              opd_price: 0,
              ipd_head: parseInt(item.head_count || 0),
              ipd_visit: parseInt(item.visit_count || 0),
              ipd_price: parseFloat(item.total_price || 0)
            });
          }
        });

        const mergedArray = Array.from(mergedMap.values());
        mergedArray.forEach((row) => {
          row.total_price = row.opd_price + row.ipd_price;
          row.total_visit = row.opd_visit + row.ipd_visit;
        });

        mergedArray.sort((a, b) => b.total_price - a.total_price);
        this.mergedPriceData = mergedArray;
      } catch (err) {
        console.error('Connection Error', err);
        this.error = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
      } finally {
        this.loading = false;
      }
    },

    async fetchSummary() {
      this.loadingSummary = true;
      try {
        const todayStr = new Date().toISOString().split('T')[0];

        const todayObj = new Date();
        const firstDay = new Date(todayObj.getFullYear(), todayObj.getMonth(), 1);
        const lastDay = new Date(todayObj.getFullYear(), todayObj.getMonth() + 1, 0);

        const fMonth = String(firstDay.getMonth() + 1).padStart(2, '0');
        const fDay = String(firstDay.getDate()).padStart(2, '0');
        const lMonth = String(lastDay.getMonth() + 1).padStart(2, '0');
        const lDay = String(lastDay.getDate()).padStart(2, '0');

        const monthStartStr = `${firstDay.getFullYear()}-${fMonth}-${fDay}`;
        const monthEndStr = `${lastDay.getFullYear()}-${lMonth}-${lDay}`;

        const yearRange = this.getCurrentFiscalYearRange();

        const [todayRes, monthRes, yearRes] = await Promise.all([
          fetch(
            `/api-hosxe/physic/get_icd10_physic.php?start_date=${todayStr}&end_date=${todayStr}&physic=16`
          ),
          fetch(
            `/api-hosxe/physic/get_icd10_physic.php?start_date=${monthStartStr}&end_date=${monthEndStr}&physic=16`
          ),
          fetch(
            `/api-hosxe/physic/get_icd10_physic.php?start_date=${yearRange.start}&end_date=${yearRange.end}&physic=16`
          )
        ]);

        const todayData = await todayRes.json();
        const monthData = await monthRes.json();
        const yearData = await yearRes.json();

        this.visitToday = todayData.reduce((sum, item) => sum + parseInt(item.C_vn || 0), 0);
        this.visitMonth = monthData.reduce((sum, item) => sum + parseInt(item.C_vn || 0), 0);
        this.visitYear = yearData.reduce((sum, item) => sum + parseInt(item.C_vn || 0), 0);
      } catch (err) {
        console.error('Error fetching summary', err);
      } finally {
        this.loadingSummary = false;
      }
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    exportTreatmentToExcel() {
      if (this.mergedPriceData.length === 0) return;

      const excelData = this.mergedPriceData.map((item) => ({
        สิทธิการรักษา: item.pttype_name,
        'ผู้ป่วยนอก จำนวนครั้ง': item.opd_visit,
        'ผู้ป่วยนอก มูลค่า (บาท)': item.opd_price,
        'ผู้ป่วยใน จำนวนครั้ง': item.ipd_visit,
        'ผู้ป่วยใน มูลค่า (บาท)': item.ipd_price,
        'รวมทั้งหมด (บาท)': item.total_price
      }));

      // Add summary row
      excelData.push({
        สิทธิการรักษา: 'รวมทั้งหมด',
        'ผู้ป่วยนอก จำนวนครั้ง': this.totalOpdVisitCount,
        'ผู้ป่วยนอก มูลค่า (บาท)': this.totalOpdPrice,
        'ผู้ป่วยใน จำนวนครั้ง': this.totalIpdVisitCount,
        'ผู้ป่วยใน มูลค่า (บาท)': this.totalIpdPrice,
        'รวมทั้งหมด (บาท)': this.totalPriceParams
      });

      const ws = XLSX.utils.json_to_sheet(excelData);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'สรุปมูลค่าการรักษา');
      XLSX.writeFile(
        wb,
        `สรุปมูลค่าการรักษา_กายภาพบำบัด_${this.startDate}_ถึง_${this.endDate}.xlsx`
      );
    },
    exportICD10ToExcel() {
      if (this.data.length === 0) return;

      const excelData = this.data.map((item) => ({
        ICD10: item.pdx,
        'จำนวนผู้ป่วย (HN)': item.C_hn,
        'จำนวนครั้ง (Visit)': item.C_vn
      }));

      const ws = XLSX.utils.json_to_sheet(excelData);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'สถิติรหัสโรค');
      XLSX.writeFile(wb, `สถิติรหัสโรค_กายภาพบำบัด_${this.startDate}_ถึง_${this.endDate}.xlsx`);
    },
    formatNumber(num) {
      return Number(num || 0).toLocaleString();
    },
    formatCurrency(num) {
      return Number(num || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    updateCurrentTime() {
      this.currentTime = new Date().toLocaleString('th-TH');
    },
    getCurrentFiscalYearRange() {
      const today = new Date();
      const year = today.getFullYear();
      const month = today.getMonth(); // 0 = Jan

      let startFiscalYear, endFiscalYear;
      if (month >= 9) {
        startFiscalYear = `${year}-10-01`;
        endFiscalYear = `${year + 1}-09-30`;
      } else {
        startFiscalYear = `${year - 1}-10-01`;
        endFiscalYear = `${year}-09-30`;
      }

      return { start: startFiscalYear, end: endFiscalYear };
    }
  },
  mounted() {
    const { start, end } = this.getCurrentFiscalYearRange();
    this.startDate = start;
    this.endDate = end;
    this.updateCurrentTime();
    this.fetchSummary();
    this.fetchAllData();
  }
};
</script>

<style scoped>
.container {
  max-width: 1400px;
}
.report-card {
  border-radius: 12px;
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
  border: none;
  overflow: hidden;
}
.report-header {
  background: linear-gradient(to right, #43cea2, #185a9d);
  color: #fff;
  border-radius: 12px 12px 0 0 !important;
}
.bg-gradient-info {
  background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}
.report-title {
  font-size: 1.8rem;
  font-weight: 700;
}
.btn-generate {
  background-color: #185a9d;
  border: none;
  color: #fff;
  border-radius: 8px;
  transition: all 0.3s ease;
}
.btn-generate:hover {
  background-color: #0b3c5d;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.btn-generate:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}
.table {
  margin-bottom: 0;
}
</style>
