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
      class="print-page bg-white p-5 mx-auto mt-4"
      style="max-width: 800px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1)"
    >
      <!-- Action Toolbar (Hidden when printing) -->
      <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 no-print gap-3"
      >
        <nav aria-label="breadcrumb" class="mb-0">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <router-link to="/material-admin" class="text-decoration-none"
                >หน้าหลักวัสดุ</router-link
              >
            </li>
            <li class="breadcrumb-item active" aria-current="page">พิมพ์ใบเบิก</li>
          </ol>
        </nav>
        <div class="d-flex gap-2 justify-content-end">
          <router-link to="/home-backoffice" class="btn btn-outline-secondary">
            <i class="bi bi-house-door"></i> กลับหน้าหลัก
          </router-link>
          <button
            class="btn btn-outline-secondary"
            @click="$router.push('/material-admin/requests')"
          >
            <i class="bi bi-arrow-left"></i> กลับ
          </button>
          <button class="btn btn-danger text-white fw-bold px-3" @click="exportPDF">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
          </button>
        </div>
      </div>

      <!-- Header -->
      <div class="text-center mb-4">
        <h4 class="fw-bold mb-3">ใบเบิกวัสดุ</h4>
      </div>

      <div class="d-flex justify-content-between mb-4">
        <div><strong>ใบเบิกที่:</strong> {{ formatId(request.id) }}</div>
        <div>
          <strong>วันที่</strong> {{ formatDay(request.request_date) }} <strong>เดือน</strong>
          {{ formatMonth(request.request_date) }} <strong>พ.ศ.</strong>
          {{ formatYear(request.request_date) }}
        </div>
      </div>

      <div class="mb-2"><strong>เรื่อง</strong> ขอเบิกวัสดุสำนักงาน</div>
      <div class="mb-4"><strong>เรียน</strong> เจ้าหน้าที่พัสดุ</div>

      <div class="mb-3">
        ข้าพเจ้า
        <span class="dotted-line">{{
          request.requester_name ||
          '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
        }}</span>
        ตำแหน่ง
        <span class="dotted-line">{{
          request.position_name2 ||
          '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
        }}</span>
      </div>
      <div class="mb-3">
        กลุ่มงาน
        <span class="dotted-line">{{
          request.department ||
          '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
        }}</span>
      </div>
      <div class="mb-4">ขอเบิกวัสดุสำนักงานเพื่อใช้ใน สำนักงาน ดังต่อไปนี้</div>

      <!-- Table -->
      <table class="table table-bordered text-center align-middle mb-5 print-table">
        <thead class="table-light">
          <tr>
            <th style="width: 10%">ลำดับ<br />ที่</th>
            <th style="width: 40%">รายการ</th>
            <th style="width: 10%">หน่วย<br />นับ</th>
            <th style="width: 10%">จำนวน<br />เบิก</th>
            <th style="width: 15%">อนุมัติ</th>
            <th style="width: 15%">คงเหลือในคลัง</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td class="text-start">
              {{ request.material_name }}
              <span v-if="request.material_code">({{ request.material_code }})</span>
            </td>
            <td>{{ request.material_unit || 'ชิ้น' }}</td>
            <td>{{ request.quantity }}</td>
            <td>{{ request.status === 'approved' ? request.quantity : '-' }}</td>
            <td>{{ request.current_balance }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Signatures Row 1 -->
      <div class="row text-center mt-3">
        <div class="col-6 mb-3">
          <div class="mb-3">ผู้ขอใช้พัสดุ...........................................ลงชื่อ</div>

          <div>
            (
            {{
              deptSigner.requester_name ||
              request.requester_name ||
              '...........................................'
            }}
            )
          </div>
          <div>
            ตำแหน่ง
            {{
              deptSigner.requester_position ||
              '.....................................................'
            }}
          </div>
        </div>
        <div class="col-6 mb-3">
          <div class="mb-3">ผู้จ่าย...........................................ลงชื่อ</div>

          <div>
            ( {{ globalSettings.payer_name || '...........................................' }} )
          </div>
          <div>
            ตำแหน่ง
            {{
              globalSettings.payer_position ||
              '.....................................................'
            }}
          </div>
        </div>
      </div>

      <!-- Signatures Row 2 -->
      <div class="row text-center mt-3">
        <div class="col-6 mb-3">
          <div class="mb-3">ผู้เบิก...........................................ลงชื่อ</div>

          <div>
            (
            {{
              deptSigner.requester_name ||
              request.requester_name ||
              '...........................................'
            }}
            )
          </div>
          <div>
            ตำแหน่ง
            {{
              deptSigner.requester_position ||
              '.....................................................'
            }}
          </div>
        </div>
        <div class="col-6 mb-3">
          <div class="mb-3">ผู้สั่งจ่าย...........................................ลงชื่อ</div>

          <div>
            ( {{ globalSettings.approver_name || '...........................................' }} )
          </div>
          <div>
            ตำแหน่ง
            {{ globalSettings.approver_position }}
          </div>
        </div>
      </div>

      <!-- Signature Row 3 -->
      <div class="row text-center mt-3">
        <div class="col-6 mb-3">
          <div class="mb-3">ผู้รับพัสดุ...........................................ลงชื่อ</div>

          <div>
            (
            {{ request.requester_name }}
            )
          </div>
          <div>
            ตำแหน่ง
            {{ request.position_name2 || '.....................................................' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import html2pdf from 'html2pdf.js';
import Swal from 'sweetalert2';

export default {
  name: 'MtRequestPrint',
  data() {
    return {
      request: null,
      globalSettings: {},
      departmentsConfig: [],
      deptSigner: {},
      loading: true,
      error: null
    };
  },
  async mounted() {
    const id = this.$route.params.id;
    if (id) {
      await this.fetchSettings();
      await this.fetchRequest(id);
      if (this.request) {
        this.mapSigner();

        if (this.$route.query.auto === '1') {
          Swal.fire({
            title: 'กำลังสร้างไฟล์ PDF...',
            text: 'กรุณารอสักครู่ ระบบกำลังสร้างแบบฟอร์มให้คุณ',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });
          setTimeout(() => {
            this.exportPDF(true);
          }, 1000);
        }
      }
    } else {
      this.error = 'ไม่พบรหัสคำขอเบิก';
      this.loading = false;
    }
  },
  methods: {
    exportPDF(autoClose = false) {
      const element = document.querySelector('.print-page');
      const opt = {
        margin: 10,
        filename: `material-request-${this.request?.id || 'doc'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      // Hide buttons temporarily
      const actionToolbar = element.querySelector('.no-print');
      if (actionToolbar) actionToolbar.style.display = 'none';

      html2pdf()
        .set(opt)
        .from(element)
        .save()
        .then(() => {
          // Restore buttons
          if (actionToolbar) actionToolbar.style.display = 'flex';
          if (autoClose) {
            Swal.close();
            setTimeout(() => window.close(), 500);
          }
        });
    },
    async fetchSettings() {
      try {
        const res = await axios.get('/api-digital/material_admin/get_print_settings.php');
        if (res.data.success) {
          this.globalSettings = res.data.global || {};
          this.departmentsConfig = res.data.departments || [];
        }
      } catch (err) {
        console.error('Failed to load settings', err);
      }
    },
    mapSigner() {
      if (this.request && this.request.department) {
        const matched = this.departmentsConfig.find(
          (d) => d.department_name === this.request.department
        );
        if (matched) {
          this.deptSigner = matched;
        }
      }
    },
    async fetchRequest(id) {
      this.loading = true;
      try {
        const res = await axios.get(`/api-digital/material_admin/get_request_by_id.php?id=${id}`);
        if (res.data.success) {
          this.request = res.data.data;
        } else {
          this.error = res.data.message || 'ไม่สามารถโหลดข้อมูลได้';
        }
      } catch (err) {
        console.error(err);
        this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
      } finally {
        this.loading = false;
      }
    },
    formatId(id) {
      const year = new Date().getFullYear() + 543;
      return String(year) + String(id).padStart(4, '0');
    },
    formatDay(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.getDate();
    },
    formatMonth(dateStr) {
      if (!dateStr) return '';
      const months = [
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
      const d = new Date(dateStr);
      return months[d.getMonth()];
    },
    formatYear(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.getFullYear() + 543;
    }
  }
};
</script>

<style scoped>
.print-container {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 2rem 0;
}

.dotted-line {
  border-bottom: 1px dotted #000;
  display: inline-block;
  min-width: 150px;
  text-align: center;
}

.signature-line {
  border-bottom: 1px dotted #000;
  width: 250px;
}

.print-table th,
.print-table td {
  border: 1px solid #000 !important;
  padding: 8px;
}

@media print {
  @page {
    size: A4 portrait;
    margin: 0;
  }

  :deep(body) {
    background: white;
  }

  .print-container {
    background-color: white;
    padding: 0;
  }

  .print-page {
    box-shadow: none !important;
    max-width: 100% !important;
    padding: 10mm !important;
  }

  /* Hide navigation, sidebar, etc. assuming they are outside this component or hidden globally */
  nav,
  header,
  footer,
  .sidebar,
  .no-print {
    display: none !important;
  }
}
</style>
