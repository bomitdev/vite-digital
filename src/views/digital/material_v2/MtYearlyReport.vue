<template>
  <div class="page-container min-vh-100 bg-light py-4">
    <div class="container-fluid px-4 px-md-5">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
              <li class="breadcrumb-item">
                <router-link to="/material-v2" class="text-decoration-none"
                  >หน้าหลักวัสดุคอม</router-link
                >
              </li>
              <li class="breadcrumb-item active" aria-current="page">รายงานประจำปี</li>
            </ol>
          </nav>
          <h2 class="fw-bold text-dark mb-0">
            <i class="bi bi-file-earmark-pdf me-2 text-primary"></i>รายงานตามช่วงเวลา
            วัสดุคอมพิวเตอร์ (PDF)
          </h2>
        </div>
        <div>
          <router-link to="/home-backoffice" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house-door me-2"></i>กลับหน้าหลัก
          </router-link>
        </div>
      </div>

      <!-- Filters & Preview -->
      <div class="row g-4">
        <!-- Controls -->
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4 text-primary">ตั้งค่าการออกรายงาน</h5>
              <div class="mb-4">
                <label class="form-label fw-semibold">เลือกวันที่เริ่มต้น</label>
                <input type="date" class="form-control form-control-lg mb-3" v-model="startDate" />
                <label class="form-label fw-semibold">เลือกวันที่สิ้นสุด</label>
                <input type="date" class="form-control form-control-lg mb-3" v-model="endDate" />

                <button
                  class="btn btn-primary w-100 btn-lg rounded-pill"
                  @click="fetchData"
                  :disabled="loading"
                >
                  <i class="bi bi-search me-2"></i> ค้นหา
                </button>
              </div>
              <hr />
              <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">กำลังคำนวณยอด...</div>
              </div>
              <div v-else class="summary-box bg-light p-3 rounded-3 mb-4">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">ยกมา:</span>
                  <span class="fw-bold">{{ formatCurrency(summary.forward_baht) }} บาท</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">ซื้อ:</span>
                  <span class="fw-bold text-success"
                    >{{ formatCurrency(summary.in_baht) }} บาท</span
                  >
                </div>
                <div class="d-flex justify-content-between ps-3 border-bottom pb-2 mb-2">
                  <span class="text-muted">รวม:</span>
                  <span class="fw-bold">{{ formatCurrency(summary.total_baht) }} บาท</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">จ่าย:</span>
                  <span class="fw-bold text-danger"
                    >{{ formatCurrency(summary.out_baht) }} บาท</span
                  >
                </div>
                <div class="d-flex justify-content-between pt-2 border-top">
                  <span class="fw-bold fs-5">คงเหลือ:</span>
                  <span class="fw-bold fs-5 text-primary"
                    >{{ formatCurrency(summary.balance_baht) }} บาท</span
                  >
                </div>
              </div>

              <button
                class="btn btn-danger w-100 btn-lg rounded-pill"
                @click="generatePDF"
                :disabled="loading"
              >
                <i class="bi bi-file-pdf me-2"></i> สร้างไฟล์ PDF
              </button>
            </div>
          </div>
        </div>

        <!-- Preview Dummy -->
        <div class="col-md-8">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white" style="min-height: 600px">
            <div
              class="card-body p-5 d-flex flex-column align-items-center justify-content-center text-center"
            >
              <img :src="krutImg" alt="Krut" height="120" class="mb-4 opacity-50" />
              <h4 class="text-muted mb-3">ตัวอย่างรายงานบันทึกข้อความ</h4>
              <p class="text-secondary" style="max-width: 400px">
                ระบบจะสร้างไฟล์ PDF รูปแบบบันทึกข้อความอย่างเป็นทางการ โดยสรุปยอดรับ-จ่าย
                วัสดุคอมพิวเตอร์ ตามช่วงเวลาที่คุณเลือก คลิกปุ่ม
                <strong>สร้างไฟล์ PDF</strong> ด้านซ้ายเพื่อดาวน์โหลด
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import krutImgUrl from '../../../assets/krut.png';

moment.locale('th');

export default {
  name: 'MtV2YearlyReport',
  data() {
    return {
      krutImg: krutImgUrl,
      startDate: moment().startOf('month').format('YYYY-MM-DD'),
      endDate: moment().format('YYYY-MM-DD'),
      loading: false,
      summary: {
        forward_baht: 0,
        in_baht: 0,
        total_baht: 0,
        out_baht: 0,
        balance_baht: 0
      },
      details: [],
      period: null
    };
  },
  methods: {
    formatCurrency(value) {
      return Number(value).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    async fetchData() {
      this.loading = true;
      try {
        const start = this.startDate;
        const end = this.endDate;

        if (!start || !end) return;

        const res = await axios.get(
          `/api-digital/material_v2/get_yearly_report.php?start_date=${start}&end_date=${end}`
        );

        if (res.data.status === 'success') {
          this.summary = res.data.data;
          this.details = res.data.details || [];
          this.period = res.data.period;
        } else {
          Swal.fire('Error', res.data.message, 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลยอดรวมได้', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchFont(url) {
      return fetch(url)
        .then((res) => res.arrayBuffer())
        .then((buffer) => {
          let binary = '';
          const bytes = new Uint8Array(buffer);
          const len = bytes.byteLength;
          for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
          }
          return window.btoa(binary);
        });
    },
    async getBase64ImageFromURL(url) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = () => {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          const dataURL = canvas.toDataURL('image/png');
          resolve(dataURL);
        };
        img.onerror = (error) => reject(error);
        img.src = url;
      });
    },
    async generatePDF() {
      Swal.fire({
        title: 'กำลังสร้าง PDF...',
        text: 'กรุณารอสักครู่ ระบบกำลังจัดเตรียมหน้ากระดาษ',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      try {
        const doc = new jsPDF();

        const baseUrl = import.meta.env.BASE_URL.endsWith('/')
          ? import.meta.env.BASE_URL
          : import.meta.env.BASE_URL + '/';
        const fontNormal = await this.fetchFont(`${baseUrl}fonts/THSarabun.ttf`);
        const fontBold = await this.fetchFont(`${baseUrl}fonts/THSarabun Bold.ttf`);

        doc.addFileToVFS('THSarabun.ttf', fontNormal);
        doc.addFont('THSarabun.ttf', 'Sarabun', 'normal');
        doc.addFileToVFS('THSarabunBold.ttf', fontBold);
        doc.addFont('THSarabunBold.ttf', 'Sarabun', 'bold');

        doc.setFont('Sarabun', 'normal');

        // Add Garuda Logo
        try {
          const logoData = await this.getBase64ImageFromURL(this.krutImg);
          // position the logo on the left
          doc.addImage(logoData, 'PNG', 20, 15, 16, 17);
        } catch (e) {
          console.warn('Could not load logo image for PDF:', e);
        }

        doc.setFontSize(30);
        doc.setFont('Sarabun', 'bold');
        doc.text('บันทึกข้อความ', 105, 25, { align: 'center' });

        doc.setFontSize(16);
        doc.setFont('Sarabun', 'bold');
        doc.text('ส่วนราชการ', 20, 40);
        doc.setFont('Sarabun', 'normal');
        doc.text('ศูนย์คอมพิวเตอร์ โรงพยาบาลชานุมาน อำเภอชานุมาน จังหวัดอำนาจเจริญ', 42, 40);

        doc.setFont('Sarabun', 'bold');
        doc.text('ที่', 20, 48);
        doc.setFont('Sarabun', 'normal');
        doc.text('อจ.0033.314/', 26, 48);

        // Date calculations
        const dStart = moment(this.startDate).format('DD/MM/YYYY');
        const dEnd = moment(this.endDate).format('DD/MM/YYYY');
        const reportDate = moment();
        const thaiMonths = [
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
        ];

        doc.setFont('Sarabun', 'bold');
        doc.text('วันที่', 100, 48);
        doc.setFont('Sarabun', 'normal');
        doc.text(
          `${reportDate.date()} ${thaiMonths[reportDate.month()]} ${reportDate.year() + 543}`,
          110,
          48
        );

        doc.setFont('Sarabun', 'bold');
        doc.text('เรื่อง', 20, 56);
        doc.setFont('Sarabun', 'normal');
        doc.text(`ส่งรายงานการเงินช่วงวันที่ ${dStart} ถึง ${dEnd}`, 32, 56);

        doc.setFont('Sarabun', 'bold');
        doc.text('เรียน', 20, 64);
        doc.setFont('Sarabun', 'normal');
        doc.text('ผู้อำนวยการโรงพยาบาลชานุมาน', 32, 64);

        // Paragraph 1
        const p1Opts = { maxWidth: 160, align: 'justify', lineHeightFactor: 1.5 };
        const indentStr = '          ';
        doc.text(
          `${indentStr}ศูนย์คอมพิวเตอร์ได้สรุปยอดการรับ-จ่าย วัสดุคอมพิวเตอร์ ระหว่างวันที่ ${dStart} ถึง ${dEnd} เพื่อส่งการเงินจัดทำรายงาน ดังรายการต่อไปนี้`,
          20,
          74,
          p1Opts
        );

        // List
        const listY = 95;
        const col1 = 50;
        const col2 = 110;
        const col3 = 155;
        const col4 = 175;

        doc.text('วัสดุคอมพิวเตอร์', col1, listY);
        doc.text('ยกมา', col2, listY);
        doc.text(this.formatCurrency(this.summary.forward_baht), col3, listY, { align: 'right' });
        doc.text('บาท', col4, listY);

        doc.text('ซื้อ', col2, listY + 8);
        doc.text(this.formatCurrency(this.summary.in_baht), col3, listY + 8, { align: 'right' });
        doc.text('บาท', col4, listY + 8);

        doc.text('รวม', col2, listY + 16);
        doc.text(this.formatCurrency(this.summary.total_baht), col3, listY + 16, {
          align: 'right'
        });
        doc.text('บาท', col4, listY + 16);

        doc.text('จ่าย', col2, listY + 24);
        doc.text(this.formatCurrency(this.summary.out_baht), col3, listY + 24, { align: 'right' });
        doc.text('บาท', col4, listY + 24);

        doc.text('คงเหลือ', col2, listY + 32);
        // Underline the balance
        doc.setLineWidth(0.3);
        const wBal = doc.getTextWidth(this.formatCurrency(this.summary.balance_baht));
        doc.line(col3 - wBal, listY + 33, col3, listY + 33);
        doc.text(this.formatCurrency(this.summary.balance_baht), col3, listY + 32, {
          align: 'right'
        });
        doc.text('บาท', col4, listY + 32);

        // Paragraph 2
        doc.text('จึงเรียนมาเพื่อทราบ', 20, listY + 45);

        // Signature 1
        let signY = listY + 65;
        doc.text('(นายสุริยา จันทรา)', 140, signY, { align: 'center' });
        doc.line(125, signY + 1, 155, signY + 1);
        doc.text('นักวิชาการคอมพิวเตอร์', 140, signY + 8, { align: 'center' });

        // Approval 1
        signY += 20;
        doc.text('เห็นควรอนุมัติ', 40, signY, { align: 'center' });
        doc.text('ทราบ', 75, signY + 10, { align: 'center' });

        doc.line(70, signY + 11, 80, signY + 11);

        // Signature 2
        signY += 30;
        doc.text('(นายศราวุฒิ แสนโท)', 75, signY, { align: 'center' });
        doc.line(55, signY + 1, 95, signY + 1);
        doc.text('นักวิชาการคอมพิวเตอร์ชำนาญการ', 75, signY + 8, { align: 'center' });

        // Approval 2 (Director)
        signY += 5;
        doc.text('อนุมัติ / ไม่อนุมัติ', 140, signY + 16, { align: 'center' });

        // Signature 3 (Director)
        signY += 40;
        doc.text('(นายธนากร  คนเพียร)', 140, signY, { align: 'center' });
        doc.line(125, signY + 1, 155, signY + 1);
        doc.text('ผู้อำนวยการโรงพยาบาลชานุมาน', 140, signY + 8, { align: 'center' });

        // ==========================================
        //PAGE 2: Detailed Table (Landscape)
        // ==========================================
        doc.addPage('a4', 'landscape');

        // Extract all unique departments from details to build dynamic headers
        const deptSet = new Set();
        this.details.forEach((item) => {
          if (item.out_departments) {
            Object.keys(item.out_departments).forEach((d) => deptSet.add(d));
          }
        });
        const departments = Array.from(deptSet).sort();

        // Build Table Headers
        const head1 = [
          {
            content: 'ลำดับ',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 10 }
          },
          {
            content: 'บริษัท/ร้าน',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 20 }
          },
          {
            content: 'รายการ',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 35 }
          },
          {
            content: 'หน่วยนับ',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 12 }
          },
          {
            content: 'ราคา/หน่วย',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 15 }
          },
          {
            content: 'ยอดยกมา',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 12 }
          },
          {
            content: 'จำนวนเงิน',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 16 }
          },
          {
            content: 'รับเข้า',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 10 }
          },
          {
            content: 'จำนวนเงิน',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 16 }
          },
          {
            content: 'รวม',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 12 }
          }
        ];

        if (departments.length > 0) {
          head1.push({
            content: 'จ่าย',
            colSpan: departments.length,
            styles: { halign: 'center' }
          });
        } else {
          head1.push({
            content: 'จ่าย',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 12 }
          });
        }

        head1.push(
          {
            content: 'รวมจ่าย',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 14 }
          },
          {
            content: 'จำนวนเงินใช้ไป',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 18 }
          },
          {
            content: 'คงเหลือ',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 14 }
          },
          {
            content: 'จำนวนเงิน',
            rowSpan: 2,
            styles: { halign: 'center', valign: 'middle', cellWidth: 18 }
          }
        );

        const head2 = [];
        departments.forEach((d) => {
          // allow table to calculate dynamic width but force a smaller font and no explicit width constraints
          head2.push({
            content: d,
            styles: { halign: 'center', valign: 'bottom', fontSize: 6.5, minCellHeight: 40 }
          });
        });

        const tableBody = [];
        let rIndex = 1;

        let sumForwardBaht = 0;
        let sumInBaht = 0;
        let sumOutBaht = 0;
        let sumBalBaht = 0;

        this.details.forEach((item) => {
          const row = [
            rIndex++,
            item.vendor || '-',
            item.name,
            item.unit || '-',
            this.formatCurrency(item.price_per_unit),
            item.forward_qty || '-',
            item.forward_qty > 0 ? this.formatCurrency(item.forward_baht) : '-',
            item.in_qty || '-',
            item.in_qty > 0 ? this.formatCurrency(item.in_baht) : '-',
            item.total_qty || '-'
          ];

          if (departments.length > 0) {
            departments.forEach((d) => {
              const q = item.out_departments[d] || 0;
              row.push(q > 0 ? q : '-');
            });
          } else {
            row.push('-');
          }

          row.push(
            item.out_qty || '-',
            item.out_qty > 0 ? this.formatCurrency(item.out_baht) : '-',
            item.balance_qty || '-',
            this.formatCurrency(item.balance_baht)
          );

          sumForwardBaht += item.forward_baht;
          sumInBaht += item.in_baht;
          sumOutBaht += item.out_baht;
          sumBalBaht += item.balance_baht;

          tableBody.push(row);
        });

        // Summary footer row
        const footRow = [
          { content: 'รวม', colSpan: 6, styles: { halign: 'center', fontStyle: 'bold' } },
          {
            content: this.formatCurrency(sumForwardBaht),
            styles: { halign: 'right', fontStyle: 'bold' }
          },
          { content: '-', styles: { halign: 'center' } },
          {
            content: this.formatCurrency(sumInBaht),
            styles: { halign: 'right', fontStyle: 'bold' }
          },
          { content: '-', styles: { halign: 'center' } }
        ];

        let deptSumSpan = departments.length > 0 ? departments.length : 1;
        footRow.push({
          content: 'รวมจ่ายทั้งหมด',
          colSpan: deptSumSpan + 1,
          styles: { halign: 'center', fontStyle: 'bold' }
        });

        footRow.push(
          {
            content: this.formatCurrency(sumOutBaht),
            styles: { halign: 'right', fontStyle: 'bold' }
          },
          { content: '-', styles: { halign: 'center' } },
          {
            content: this.formatCurrency(sumBalBaht),
            styles: { halign: 'right', fontStyle: 'bold' }
          }
        );
        tableBody.push(footRow);

        autoTable(doc, {
          startY: 28,
          head: [head1, head2],
          body: tableBody,
          theme: 'grid',
          styles: {
            font: 'Sarabun',
            fontSize: 7.5,
            lineWidth: 0.1,
            lineColor: [0, 0, 0],
            textColor: [0, 0, 0],
            cellPadding: 1
          },
          headStyles: {
            fillColor: [240, 240, 240],
            textColor: [0, 0, 0],
            fontStyle: 'bold',
            halign: 'center',
            fontSize: 7.5
          },
          columnStyles: {
            0: { halign: 'center', cellWidth: 8 },
            1: { cellWidth: 20 },
            2: { cellWidth: 32 },
            [4]: { halign: 'right' },
            [6]: { halign: 'right' },
            [8]: { halign: 'right' },
            [12 + departments.length]: { halign: 'right', cellWidth: 16 },
            [14 + departments.length]: { halign: 'right', cellWidth: 16 }
          },
          margin: { top: 30, left: 5, right: 5, bottom: 10 },
          willDrawCell: (data) => {
            // Hide the default horizontal text in the department header row
            if (
              data.section === 'head' &&
              data.row.index === 1 &&
              departments.length > 0 &&
              data.column.index >= 10 &&
              data.column.index < 10 + departments.length
            ) {
              doc.setTextColor(240, 240, 240); // Math the fill color
            }
          },
          didDrawCell: (data) => {
            // Restore text color and draw vertical text
            if (
              data.section === 'head' &&
              data.row.index === 1 &&
              departments.length > 0 &&
              data.column.index >= 10 &&
              data.column.index < 10 + departments.length
            ) {
              doc.setTextColor(0, 0, 0);
              doc.setFontSize(7);
              doc.setFont('Sarabun', 'normal');
              const text = data.cell.raw.content || '';

              // Angle the text 90 degrees starting from the bottom of the cell
              doc.text(
                text,
                data.cell.x + data.cell.width / 2 + 1.2,
                data.cell.y + data.cell.height - 2,
                { angle: 90, align: 'left' }
              );
            }
          },
          didDrawPage: (data) => {
            doc.setFontSize(16);
            doc.setFont('Sarabun', 'bold');
            doc.text(`การใช้วัสดุคอมพิวเตอร์ ระหว่างวันที่ ${dStart} ถึง ${dEnd}`, 148, 15, {
              align: 'center'
            });
            doc.text('โรงพยาบาลชานุมาน อ.ชานุมาน จ.อำนาจเจริญ', 148, 22, { align: 'center' });
          }
        });

        // Small bottom summary box
        let finalY = doc.lastAutoTable.finalY + 10;
        if (finalY > 175) {
          doc.addPage('a4', 'landscape');
          finalY = 30;
        }
        doc.setFontSize(11);
        doc.setFont('Sarabun', 'normal');
        doc.text('ยอดยกมา', 20, finalY);
        doc.text(this.formatCurrency(sumForwardBaht), 60, finalY, { align: 'right' });
        doc.text('บาท', 65, finalY);

        doc.text('รวม', 20, finalY + 6);
        doc.text(this.formatCurrency(sumForwardBaht + sumInBaht), 60, finalY + 6, {
          align: 'right'
        });
        doc.text('บาท', 65, finalY + 6);

        doc.text('จ่าย', 20, finalY + 12);
        doc.text(this.formatCurrency(sumOutBaht), 60, finalY + 12, { align: 'right' });
        doc.text('บาท', 65, finalY + 12);
        doc.line(30, finalY + 13, 60, finalY + 13);

        doc.text('คงเหลือ', 20, finalY + 18);
        doc.text(this.formatCurrency(sumBalBaht), 60, finalY + 18, { align: 'right' });
        doc.text('บาท', 65, finalY + 18);
        doc.line(30, finalY + 19, 60, finalY + 19);
        doc.line(30, finalY + 20, 60, finalY + 20);

        // ==========================================
        // PAGE 3: Final Summary & Signatures (Portrait)
        // ==========================================
        doc.addPage('a4', 'portrait');

        doc.setFontSize(16);
        doc.setFont('Sarabun', 'normal');
        doc.text('รายงานสถานะคงคลัง วัสดุคอมพิวเตอร์ โรงพยาบาลชานุมาน', 105, 20, {
          align: 'center'
        });
        doc.text(`ข้อมูลระหว่างวันที่ ${dStart} ถึง ${dEnd}`, 105, 28, {
          align: 'center'
        });

        const summaryHead = [
          [
            { content: '', styles: { halign: 'center', fillColor: [255, 255, 255] } },
            {
              content: 'วัสดุคอมพิวเตอร์',
              styles: { halign: 'center', fontStyle: 'normal', fillColor: [255, 255, 255] }
            }
          ]
        ];

        const summaryBody = [
          [
            'มูลค่าคงคลังยกมาจากปีก่อน',
            { content: this.formatCurrency(sumForwardBaht), styles: { halign: 'right' } }
          ],
          [
            'มูลค่ารับเข้า (จัดซื้อ)',
            { content: this.formatCurrency(sumInBaht), styles: { halign: 'right' } }
          ],
          [
            'มูลค่าการเบิกใช้(รพ.)',
            { content: this.formatCurrency(sumOutBaht), styles: { halign: 'right' } }
          ],
          [
            'มูลค่าคงคลังยกไป',
            { content: this.formatCurrency(sumBalBaht), styles: { halign: 'right' } }
          ]
        ];

        autoTable(doc, {
          startY: 40,
          head: summaryHead,
          body: summaryBody,
          theme: 'grid',
          styles: {
            font: 'Sarabun',
            fontSize: 14,
            lineWidth: 0.1,
            lineColor: [0, 0, 0],
            textColor: [0, 0, 0],
            cellPadding: 3
          },
          headStyles: {
            fillColor: [255, 255, 255],
            textColor: [0, 0, 0]
          },
          columnStyles: {
            0: { cellWidth: 80 },
            1: { cellWidth: 60 }
          },
          margin: { left: 35 } // (210 - 140) / 2 = 35 for centering
        });

        // Signatures Page 3
        let sumSignY = doc.lastAutoTable.finalY + 30;

        doc.setFontSize(14);

        // --- User ---
        doc.text(
          'ลงชื่อ..........................................................ผู้สรุปรายงาน /เจ้าหน้าที่ศูนย์คอมฯ',
          105,
          sumSignY,
          { align: 'center' }
        );
        doc.text('(นายสุริยา จันทรา)', 105, sumSignY + 10, { align: 'center' });
        doc.text('นักวิชาการคอมพิวเตอร์', 105, sumSignY + 18, { align: 'center' });

        // --- Manager ---
        sumSignY += 50;
        doc.text('ทราบ', 105, sumSignY, { align: 'center' });
        doc.text(
          'ลงชื่อ..........................................................ศูนย์คอมพิวเตอร์',
          105,
          sumSignY + 12,
          { align: 'center' }
        );
        doc.text('(นายศราวุฒิ แสนโท)', 105, sumSignY + 22, { align: 'center' });
        doc.text('นักวิชาการคอมพิวเตอร์ชำนาญการ', 105, sumSignY + 30, { align: 'center' });

        // --- Director ---
        sumSignY += 60;
        doc.text('อนุมัติ / ไม่อนุมัติ', 105, sumSignY, { align: 'center' });
        doc.text(
          'ลงชื่อ.........................................................................',
          105,
          sumSignY + 12,
          { align: 'center' }
        );
        doc.text('(นายธนากร  คนเพียร)', 105, sumSignY + 22, { align: 'center' });
        doc.text('ผู้อำนวยการโรงพยาบาลชานุมาน', 105, sumSignY + 30, { align: 'center' });

        doc.save(`material-report-${this.startDate}-to-${this.endDate}.pdf`);
        Swal.close();
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการสร้าง PDF', 'error');
      }
    }
  },
  mounted() {
    this.fetchData();
  }
};
</script>

<style scoped>
.breadcrumb a {
  text-decoration: none;
  color: #0d6efd;
}
.krut-placeholder {
  width: 100px;
  height: 100px;
  background-color: #f1f1f1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}
</style>
