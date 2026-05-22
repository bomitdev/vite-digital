<template>
  <div class="container-fluid py-4 min-vh-100 bg-light font-sarabun">
    <div class="container-lg">
      <!-- Header Section -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
          <i class="bi bi-printer-fill fs-3 text-secondary me-3"></i>
          <h4 class="fw-bold m-0 text-dark">พิมพ์ลงเวลาเข้า-ออกงาน</h4>
        </div>
        <button
          @click="$router.push('/home-backoffice')"
          class="btn btn-outline-secondary rounded-pill fw-bold"
        >
          <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
        </button>
      </div>

      <!-- Filters Card -->
      <div class="card border-0 shadow-sm rounded-4 mb-4" style="overflow: visible">
        <div class="card-body p-4">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-muted">เจ้าหน้าที่ :</label>
              <div v-if="canSelectUser" class="position-relative">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0"
                    ><i class="bi bi-search text-muted"></i
                  ></span>
                  <input
                    type="text"
                    class="form-control border-start-0 ps-0"
                    v-model="searchQuery"
                    @focus="isDropdownOpen = true"
                    @blur="closeDropdown"
                    placeholder="พิมพ์ชื่อเพื่อค้นหา..."
                  />
                  <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="isDropdownOpen = !isDropdownOpen"
                  >
                    <i class="bi bi-chevron-down"></i>
                  </button>
                </div>

                <ul
                  class="dropdown-menu w-100 shadow-sm mt-1"
                  :class="{ show: isDropdownOpen && filteredStaff.length > 0 }"
                  style="max-height: 250px; overflow-y: auto"
                >
                  <li v-for="staff in filteredStaff" :key="staff.ID">
                    <a class="dropdown-item py-2" href="#" @click.prevent="selectUser(staff)">
                      {{ staff.FULLNAME }}
                    </a>
                  </li>
                </ul>
              </div>
              <div v-else>
                <input
                  type="text"
                  class="form-control bg-light"
                  :value="userProfile.fullname"
                  readonly
                />
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold text-muted">เลือกเดือน/ปี :</label>
              <div
                class="d-flex align-items-center justify-content-between border rounded-3 bg-white p-1"
                style="height: 38px"
              >
                <button
                  class="btn btn-link text-primary text-decoration-none p-0 px-2"
                  @click="changeMonth(-1)"
                >
                  <i class="bi bi-arrow-left-circle-fill fs-4"></i>
                </button>
                <span class="fw-bold text-primary">
                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                </span>
                <button
                  class="btn btn-link text-primary text-decoration-none p-0 px-2"
                  @click="changeMonth(1)"
                >
                  <i class="bi bi-arrow-right-circle-fill fs-4"></i>
                </button>
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold text-muted">วันที่เริ่ม :</label>
              <input type="date" class="form-control" v-model="startDate" />
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold text-muted">วันที่สิ้นสุด :</label>
              <input type="date" class="form-control" v-model="endDate" />
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button
                class="btn btn-success text-white fw-bold px-3 flex-grow-1"
                @click="fetchData"
              >
                <i class="bi bi-search me-1"></i> ค้นหา
              </button>
              <button class="btn btn-danger text-white fw-bold px-3" @click="exportPDF">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light border-bottom">
              <tr>
                <th class="py-3 text-secondary small fw-bold">วันที่</th>
                <th class="py-3 text-secondary small fw-bold">เวลา</th>
                <th class="py-3 text-secondary small fw-bold">ประเภท</th>
                <th class="py-3 text-secondary small fw-bold">เวร</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in scanData" :key="index">
                <td class="py-3">{{ formatThaiDate(item.date) }}</td>
                <td class="py-3 fw-bold text-dark">{{ item.time }}</td>
                <td class="py-3">
                  <span
                    class="badge rounded-pill fw-normal px-3"
                    :class="
                      item.type === 'เข้า'
                        ? 'bg-success-subtle text-success'
                        : 'bg-danger-subtle text-danger'
                    "
                  >
                    {{ item.type }}
                  </span>
                </td>
                <td class="py-3 text-muted small">{{ item.shift }}</td>
              </tr>
              <tr v-if="scanData.length === 0 && !loading">
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                  ไม่มีข้อมูลการลงเวลาในช่วงวันที่เลือก
                </td>
              </tr>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-5 text-primary">
                  <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                  กำลังโหลดข้อมูล...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Swal from 'sweetalert2';

import logoImage from '../../assets/digital-logo.png';

export default {
  name: 'FingerScanView',
  data() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth(); // 0-11

    // First day: 1st of current month
    const startObj = new Date(year, month, 1);
    const startDate = `${startObj.getFullYear()}-${String(startObj.getMonth() + 1).padStart(2, '0')}-${String(startObj.getDate()).padStart(2, '0')}`;

    // Last day: 0th day of next month
    const endObj = new Date(year, month + 1, 0);
    const endDate = `${endObj.getFullYear()}-${String(endObj.getMonth() + 1).padStart(2, '0')}-${String(endObj.getDate()).padStart(2, '0')}`;

    // Generate Year List (Current - 5 to Current + 1)
    const yearList = [];
    for (let y = year - 5; y <= year + 1; y++) {
      yearList.push(y);
    }

    // Thai Month Names
    const monthList = [
      { id: 1, name: 'มกราคม' },
      { id: 2, name: 'กุมภาพันธ์' },
      { id: 3, name: 'มีนาคม' },
      { id: 4, name: 'เมษายน' },
      { id: 5, name: 'พฤษภาคม' },
      { id: 6, name: 'มิถุนายน' },
      { id: 7, name: 'กรกฎาคม' },
      { id: 8, name: 'สิงหาคม' },
      { id: 9, name: 'กันยายน' },
      { id: 10, name: 'ตุลาคม' },
      { id: 11, name: 'พฤศจิกายน' },
      { id: 12, name: 'ธันวาคม' }
    ];

    return {
      selectedMonth: month + 1, // 1-12
      selectedYear: year,
      monthList,
      yearList,
      startDate: startDate,
      endDate: endDate,
      userProfile: {
        fullname: '',
        id: null
      },
      scanData: [],
      staffList: [], // List of selectable staff
      selectedUserId: '',
      searchQuery: '', // For search input
      isDropdownOpen: false,
      canSelectUser: false,
      loading: false
    };
  },
  computed: {
    filteredStaff() {
      if (!this.searchQuery) return this.staffList;
      const lowerQuery = this.searchQuery.toLowerCase();
      return this.staffList.filter((staff) => staff.FULLNAME.toLowerCase().includes(lowerQuery));
    }
  },
  methods: {
    onPeriodChange() {
      if (!this.selectedMonth || !this.selectedYear) return;

      const year = parseInt(this.selectedYear);
      const month = parseInt(this.selectedMonth); // 1-12

      // Convert to Date Index (0-11)
      const startObj = new Date(year, month - 1, 1);
      const endObj = new Date(year, month, 0);

      this.startDate = `${startObj.getFullYear()}-${String(startObj.getMonth() + 1).padStart(2, '0')}-${String(startObj.getDate()).padStart(2, '0')}`;
      this.endDate = `${endObj.getFullYear()}-${String(endObj.getMonth() + 1).padStart(2, '0')}-${String(endObj.getDate()).padStart(2, '0')}`;
    },
    selectUser(staff) {
      this.selectedUserId = staff.ID;
      this.searchQuery = staff.FULLNAME;
      this.isDropdownOpen = false;
      this.fetchData();
    },
    closeDropdown() {
      // Delay to allow click to register
      setTimeout(() => {
        this.isDropdownOpen = false;
      }, 200);
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.userProfile = {
            fullname: response.data.fullname,
            id: response.data.id,
            access_user: response.data.access_user,
            department_id: response.data.department_id,
            position: response.data.position,
            hr_level_name: response.data.hr_level_name,
            department: response.data.department
          };
          this.selectedUserId = this.userProfile.id;
          this.searchQuery = this.userProfile.fullname; // Set initial name

          // Check permission to view others
          const roles = (this.userProfile.access_user || '').split(':');
          const allowedRoles = [
            'administrator',
            'Admin',
            'Super',
            'fingerscan_print_all',
            'fingerscan_user_all'
          ];

          if (roles.some((r) => allowedRoles.includes(r))) {
            this.canSelectUser = true;
            this.fetchAuthorizedStaff();
          }
        }
      } catch (error) {
        console.error('Profile Fetch Error', error);
      }
    },
    async fetchAuthorizedStaff() {
      try {
        const response = await axios.get('/api-hosoffice/get_authorized_staff.php');
        if (response.data.status === 'success') {
          this.staffList = response.data.data;
        }
      } catch (error) {
        console.error('Staff List Error', error);
      }
    },
    getMonthName(monthId) {
      const found = this.monthList.find((m) => m.id === monthId);
      return found ? found.name : monthId;
    },
    changeMonth(step) {
      let newMonth = this.selectedMonth + step;
      let newYear = this.selectedYear;

      if (newMonth > 12) {
        newMonth = 1;
        newYear++;
      } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
      }

      this.selectedMonth = newMonth;
      this.selectedYear = newYear;
      this.onPeriodChange();
    },
    formatThaiDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      // Check if date is valid
      if (isNaN(date.getTime())) return dateString;

      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return date.toLocaleDateString('th-TH', options);
    },
    async fetchData() {
      this.loading = true;
      try {
        const token = localStorage.getItem('user_token');
        let userId = null;
        // ... (existing code for converting token) ...
        if (token) {
          try {
            const parts = token.split('.');
            if (parts.length === 3) {
              const base64Url = parts[1];
              const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
              const jsonPayload = decodeURIComponent(
                window
                  .atob(base64)
                  .split('')
                  .map(function (c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                  })
                  .join('')
              );
              const payload = JSON.parse(jsonPayload);
              userId = payload.data?.id || payload.id;
            }
          } catch (e) {
            console.error('Token parse error', e);
          }
        }

        const response = await axios.get('/api-hosoffice/get_finger_scan.php', {
          params: {
            start_date: this.startDate,
            end_date: this.endDate,
            user_id: this.canSelectUser && this.selectedUserId ? this.selectedUserId : undefined
          },
          headers: {
            Authorization: `Bearer ${token}`
          }
        });

        if (response.data.status === 'success') {
          this.scanData = response.data.data;
        } else {
          this.scanData = [];

          // Check for Unauthorized / Session Expired
          if (
            response.data.message &&
            (response.data.message.includes('Unauthorized') ||
              response.data.message.includes('No Session'))
          ) {
            Swal.fire({
              title: 'หมดเวลาการใช้งาน',
              text: 'กรุณาเข้าสู่ระบบใหม่',
              icon: 'warning',
              timer: 2000,
              showConfirmButton: false,
              confirmButtonColor: '#6f42c1'
            }).then(() => {
              // Clear Session
              localStorage.removeItem('user_token');
              localStorage.removeItem('user_name');
              localStorage.removeItem('last_activity');
              this.$router.push('/login');
            });
          } else if (response.data.message) {
            Swal.fire({
              title: 'Error',
              text: response.data.message,
              icon: 'error',
              confirmButtonText: 'OK'
            });
          }
        }
      } catch (error) {
        console.error(error);
        this.scanData = [];

        if (error.response && error.response.status === 401) {
          Swal.fire({
            title: 'หมดเวลาการใช้งาน',
            text: 'กรุณาเข้าสู่ระบบใหม่',
            icon: 'warning',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            localStorage.removeItem('user_token');
            localStorage.removeItem('user_name');
            localStorage.removeItem('last_activity');
            this.$router.push('/login');
          });
        } else {
          alert('เกิดข้อผิดพลาดในการเชื่อมต่อข้อมูล: ' + error.message);
        }
      } finally {
        this.loading = false;
      }
    },
    async exportPDF() {
      // Determine Target User Permission
      let targetAccess = this.userProfile.access_user || '';
      if (this.canSelectUser && this.selectedUserId && this.staffList.length > 0) {
        const found = this.staffList.find((s) => s.ID == this.selectedUserId);
        if (found) {
          targetAccess = found.access_user || '';
        }
      }

      const roles = targetAccess.split(':');
      const hasUserWage = roles.includes('user_wage');

      // Data to print
      let printingData = [];
      let useRawData = false;

      if (hasUserWage) {
        // 1. With Permission: Attempt to fetch Backend Calculated Data
        const token = localStorage.getItem('user_token');
        try {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'กำลังสร้าง PDF...',
              text: 'กรุณารอสักครู่',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });
          }
          const response = await axios.get('/api-hosoffice/get_finger_scan.php', {
            params: {
              start_date: this.startDate,
              end_date: this.endDate,
              user_id: this.canSelectUser && this.selectedUserId ? this.selectedUserId : undefined,
              mode: 'export'
            },
            headers: { Authorization: `Bearer ${token}` }
          });

          if (response.data.status === 'success') {
            const exportData = response.data.data;
            const hasContent = exportData.some(
              (row) =>
                row.mor || row.aft || row.ot || (row.status_name && row.status_name !== 'วันร.')
            );

            if (hasContent) {
              // Success: Use Backend Summary
              printingData = exportData;
              useRawData = false;
            } else {
              // Fallback for WAGE users: Use Frontend Calculation if Backend is empty
              printingData = calculateDailySummary(this.scanData);
              useRawData = false;
            }
          } else {
            throw new Error(response.data.message || 'Error fetching export data');
          }
        } catch (error) {
          console.error('Export API Error:', error);
          // Fallback on error for WAGE users: Use Frontend Calculation
          printingData = calculateDailySummary(this.scanData);
          useRawData = false;
        }
      } else {
        // 2. No Permission: Use Raw Data (10985_hos_fingerscan) DIRECTLY
        printingData = this.scanData;
        useRawData = true;
      }

      // Helper to aggregate raw data into summary
      function calculateDailySummary(rawData) {
        if (!rawData || !Array.isArray(rawData)) return [];
        const summary = [];
        // Group by Date
        const grouped = {};
        rawData.forEach((item) => {
          if (!grouped[item.date]) grouped[item.date] = [];
          grouped[item.date].push(item);
        });

        // Get all dates in range from grouped keys
        Object.keys(grouped)
          .sort()
          .forEach((date) => {
            const dayScans = grouped[date];
            let mor = '';
            let aft = '';
            let ot = '';
            let status = 'ปกติ';

            // Sort by time
            dayScans.sort((a, b) => (a.time || '').localeCompare(b.time || ''));

            const ins = dayScans.filter((s) => s.type === 'เข้า');
            const outs = dayScans.filter((s) => s.type === 'ออก');

            // Logic: First IN is Morning
            if (ins.length > 0) mor = ins[0].time;

            // Logic: Last OUT
            if (outs.length > 0) {
              const lastOut = outs[outs.length - 1];
              if (lastOut.time > '16:30') {
                ot = lastOut.time;
                if (outs.length > 1) {
                  aft = outs[outs.length - 2].time;
                }
              } else {
                aft = lastOut.time;
              }
            }

            if (!mor && !aft && !ot) status = 'ขาดงาน';
            else if (!mor && (aft || ot)) status = 'ไม่ลงเวลาเข้า';
            else if (mor > '08:45') status = 'สาย'; // Adjust late threshold as needed

            summary.push({
              gov_date: date,
              mor: mor,
              aft: aft,
              ot: ot,
              status_name: status
            });
          });
        return summary;
      }

      if (!printingData || printingData.length === 0) {
        if (typeof Swal !== 'undefined') {
          Swal.close(); // Close loading
          Swal.fire({ title: 'Warning', text: 'รอประมวลผลเวลาเข้า-ออก', icon: 'warning' });
        } else {
          alert('ไม่พบข้อมูลสำหรับ Export PDF');
        }
        return;
      }

      // Calculate Summary Counts
      let lateCount = 0;
      let noInCount = 0;

      printingData.forEach((item) => {
        const status = item.status_name || '';
        if (status.includes('สาย')) lateCount++;
        if (status.includes('ไม่ลงเวลาเข้า')) noInCount++;
      });

      const doc = new jsPDF();

      // --- LOGO SECTION ---
      // A4 Width = 210mm
      // Logo Width = 25mm (Adjusted for side-by-side)
      // Center X = (210 - 30) / 2 = 90
      try {
        const img = new Image();
        img.src = logoImage;
        await new Promise((resolve, reject) => {
          img.onload = resolve;
          img.onerror = reject;
        });
        // Move Logo Left (X=50), Y=8 (Moved Up), Size 25x25
        doc.addImage(img, 'PNG', 10, 2, 25, 25);
      } catch (e) {
        console.error('Logo load error', e);
      }

      // Helper to convert ArrayBuffer to Base64
      function arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
          binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
      }

      // Load font
      try {
        // Use import.meta.env.BASE_URL to respect app base path
        const baseUrl = import.meta.env.BASE_URL.endsWith('/')
          ? import.meta.env.BASE_URL
          : import.meta.env.BASE_URL + '/';

        // Load Regular
        const fontUrl = `${baseUrl}fonts/THSarabun.ttf`;
        const fontBytes = await fetch(fontUrl).then((res) => {
          if (!res.ok) throw new Error(`Failed to load font: ${res.statusText}`);
          return res.arrayBuffer();
        });
        const filename = 'THSarabun.ttf';
        const base64String = arrayBufferToBase64(fontBytes);

        // Load Bold
        const fontUrlBold = `${baseUrl}fonts/THSarabun Bold.ttf`;
        const fontBytesBold = await fetch(fontUrlBold)
          .then((res) => {
            if (!res.ok) throw new Error('Bold font missing');
            return res.arrayBuffer();
          })
          .catch((err) => null);

        // Add Regular
        doc.addFileToVFS(filename, base64String);
        doc.addFont(filename, 'Sarabun', 'normal');

        // Add Bold if found, otherwise map Bold to Regular (fallback)
        if (fontBytesBold) {
          const filenameBold = 'THSarabunBold.ttf';
          const base64StringBold = arrayBufferToBase64(fontBytesBold);
          doc.addFileToVFS(filenameBold, base64StringBold);
          doc.addFont(filenameBold, 'Sarabun', 'bold');
        } else {
          doc.addFont(filename, 'Sarabun', 'bold');
        }

        doc.setFont('Sarabun', 'normal');
      } catch (e) {
        console.error('Font load error', e);
      }

      // Header (Adjust Y to be aligned with Logo)
      doc.setFontSize(22); // Slightly larger header
      doc.setFont('Sarabun', 'bold');
      // Text at X=80 (Right of Logo), Y=18 and Y=27 (Moved Up)
      doc.text('รายงานการลงเวลาเข้า-ออกงาน', 40, 10);
      doc.text('โรงพยาบาลชานุมาน จังหวัดอำนาจเจริญ', 40, 20);
      doc.setFont('Sarabun', 'normal');

      doc.setFontSize(16);
      let name = this.userProfile.fullname;
      let position = this.userProfile.position || '-';
      if (this.userProfile.hr_level_name) {
        position += `${this.userProfile.hr_level_name}`;
      }
      let department = this.userProfile.department || '-';

      if (this.canSelectUser && this.selectedUserId && this.staffList.length > 0) {
        const found = this.staffList.find((s) => s.ID == this.selectedUserId);
        if (found) {
          name = found.FULLNAME;
          position = found.HR_POSITION_NAME || '-';
          if (found.HR_LEVEL_NAME) {
            position += ` ${found.HR_LEVEL_NAME}`;
          }
          department = found.HR_DEPARTMENT_SUB_NAME || '-';
        }
      }

      // Info Section (Shifted down)
      doc.text(`ชื่อ-สกุล : ${name}`, 14, 28);
      doc.text(`ตำแหน่ง : ${position}`, 120, 28);
      doc.text(`กลุ่มงาน : ${department}`, 14, 35);
      doc.text(
        `ช่วงวันที่ : ${this.formatThaiDate(this.startDate)} ถึง ${this.formatThaiDate(this.endDate)}`,
        120,
        35
      );

      // Define Table Columns and Body based on Permission AND Content Availability
      let head = [];
      let body = [];

      if (!useRawData) {
        // ** SUMMARY FORMAT (For User Wage) **
        head = [['วันที่', 'เวลาเข้า', 'เวลาออก', 'OT', 'สถานะ', 'หมายเหตุ']];
        body = printingData.map((item) => [
          this.formatThaiDate(item.gov_date),
          item.mor,
          item.aft,
          item.ot,
          item.status_name || '',
          '' // Empty Remark
        ]);
      } else {
        // ** RAW FORMAT (For Non-Wage User) **
        // Matches user screenshot request
        head = [['วันที่', 'เวลา', 'ประเภท', 'เวร', 'หมายเหตุ']];
        body = printingData.map((item) => [
          this.formatThaiDate(item.date),
          item.time,
          item.type,
          item.shift || '',
          ''
        ]);
      }

      autoTable(doc, {
        head: head,
        body: body,
        startY: 40,
        margin: { bottom: 40 }, // Reduced to allow 31 rows and summary to fit on one page
        styles: {
          font: 'Sarabun',
          fontSize: 14,
          cellPadding: 1,
          minCellHeight: 6,
          valign: 'middle'
        },
        headStyles: {
          fillColor: [41, 128, 185],
          textColor: 255,
          fontStyle: 'bold',
          lineWidth: 0.1,
          lineColor: [0, 0, 0]
        },
        theme: 'grid'
      });

      // Summary Counts (New Section)
      let finalY = doc.lastAutoTable.finalY + 10;

      // Check page break for summary
      if (finalY > doc.internal.pageSize.height - 28) {
        doc.addPage();
        finalY = 20;
      }

      if (!useRawData) {
        doc.setFont('Sarabun', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);
        doc.text(
          `สรุปการลงเวลา: สาย ${lateCount} ครั้ง, ไม่ลงเวลาเข้า ${noInCount} ครั้ง`,
          16,
          finalY
        );
      }

      // --- Footer: Printer Name & Page Number ---
      const totalPages = doc.internal.getNumberOfPages();
      const printerName = this.userProfile.fullname;
      doc.setFontSize(10);
      doc.setTextColor(150); // Gray color for footer

      for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);

        const pageHeight = doc.internal.pageSize.height;

        // --- Persistent Footer (Summary & Signature) ---
        // Start drawing from ~38mm from bottom to align last line with Printer Info (-10mm)
        // Summary (-38) -> Signature (-26) -> Name (-18) -> Position (-10)
        let footerContentY = pageHeight - 38;

        // Set Bold and Black for Signature
        doc.setFont('Sarabun', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(0, 0, 0);

        // if (!useRawData) { ... Summary was here ... }

        const centerX = 40;
        // Signature
        footerContentY += 12;
        doc.text(
          'ลงชื่อ .................................................',
          centerX,
          footerContentY,
          { align: 'center' }
        );

        footerContentY += 8;
        doc.text(`( ${name} )`, centerX, footerContentY, { align: 'center' });

        footerContentY += 8;
        const posText =
          position && position !== '-'
            ? `ตำแหน่ง ${position}`
            : 'ตำแหน่ง .................................................';
        doc.text(posText, centerX, footerContentY, { align: 'center' });

        // Right: Printer Info & Page Number
        // Reset to Normal, Small, Gray
        doc.setFont('Sarabun', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(150);

        const footerText = `ผู้พิมพ์: ${printerName} | หน้า ${i} / ${totalPages}`;
        doc.text(footerText, doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10, {
          align: 'right'
        });
      }

      if (typeof Swal !== 'undefined') Swal.close();
      // Open in new tab instead of save
      const pdfBlob = doc.output('blob');
      const pdfUrl = URL.createObjectURL(pdfBlob);
      window.open(pdfUrl, '_blank');
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchData();
  }
};
</script>

<style scoped>
.font-sarabun {
  font-family: 'Sarabun', sans-serif;
}
</style>

<style>
.swal2-popup {
  font-family: 'Sarabun', sans-serif !important;
}
</style>
