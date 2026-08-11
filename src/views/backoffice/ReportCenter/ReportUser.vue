<template>
  <div class="container-fluid py-4" style="background-color: #ffffff; min-height: 100vh;">
    <div class="card border-0 rounded-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0">
        <h3 class="mb-0 calm-text-navy fw-bold"><i class="bi bi-table me-2"></i>Report Center</h3>
        <div class="d-flex gap-2">
          <button v-if="isAdmin" @click="$router.push('/report-center/admin')" class="btn btn-warning rounded-pill text-white fw-bold">
            <i class="bi bi-gear-fill me-1"></i> จัดการรายงาน (Admin)
          </button>
          <button @click="$router.push('/report')" class="btn calm-btn-primary rounded-pill fw-bold">
            <i class="bi bi-chat-text-fill me-1"></i> ระบบขอรายงานข้อมูล
          </button>
          <button @click="goHome" class="btn calm-btn-secondary rounded-pill fw-bold">
            <i class="bi bi-house me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>

      <div class="card-body">
        <div class="row">
          <!-- Sidebar / List -->
          <div class="col-md-3 border-end">
            <!-- Filter Department Dropdown -->
            <div class="mb-3">
              <label class="form-label small text-dark fw-bold mb-2">
                <i class="bi bi-building calm-text-navy me-1"></i> เลือกกลุ่มงาน
              </label>
              <select class="form-select form-select-lg calm-input px-4 calm-text-navy fw-bold" v-model="filterDepartment" style="cursor: pointer;">
                <option value="" disabled>-- กรุณาเลือกกลุ่มงาน --</option>
                <option value="ALL" class="text-dark">-- ทุกกลุ่มงาน --</option>
                <option v-for="dept in departmentsWithReports" :key="dept.HR_DEPARTMENT_SUB_ID" :value="dept.HR_DEPARTMENT_SUB_ID" class="text-dark">
                  {{ dept.HR_DEPARTMENT_SUB_NAME }}
                </option>
                <option v-if="hasGeneralReports" value="GENERAL" class="text-dark">General / Uncategorized</option>
              </select>
            </div>

            <!-- Search -->
            <div class="mb-4 position-relative">
              <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              <input
                type="text"
                class="form-control form-control-lg calm-input ps-5"
                placeholder="ค้นหารายงาน..."
                v-model="searchQuery"
              />
            </div>
            
            <div class="list-group list-group-flush">
              
              <div v-if="!filterDepartment && !searchQuery" class="text-center text-muted p-4">
                <i class="bi bi-arrow-up-circle fs-1 d-block mb-2 text-primary opacity-50"></i>
                กรุณาเลือกกลุ่มงานด้านบนเพื่อดูรายงาน
              </div>
              <div v-else-if="filteredReports.length === 0" class="text-center text-muted p-4">
                ไม่พบรายงาน
              </div>

              <button
                v-for="rep in filteredReports"
                :key="rep.id"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1 rounded-3 border-0 transition-all"
                :class="{
                  'calm-btn-primary': selectedReport && selectedReport.id === rep.id,
                  'bg-white hover-bg-light': !selectedReport || selectedReport.id !== rep.id
                }"
                style="border-left: 4px solid transparent !important;"
                :style="selectedReport && selectedReport.id === rep.id ? 'border-left: 4px solid #ffffff !important;' : 'border-left: 4px solid #dee2e6 !important;'"
                @click="selectReport(rep)"
              >
                <div>
                  <div class="fw-bold"><i class="bi bi-file-earmark-text me-2" :class="selectedReport && selectedReport.id === rep.id ? 'text-white' : 'calm-text-navy'"></i>{{ rep.title }}</div>
                  <small
                    class="d-block text-truncate mt-1"
                    :class="selectedReport && selectedReport.id === rep.id ? 'text-white-50' : 'text-muted'"
                    style="max-width: 220px"
                    v-if="rep.description"
                  >
                    {{ rep.description }}
                  </small>
                </div>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="col-md-9" id="report-filter-section">
            <div v-if="!selectedReport" class="d-flex flex-column align-items-center justify-content-center py-5 mt-5 calm-bg-lavender calm-card">
              <h3 class="calm-text-navy fw-bold mb-3">ยินดีต้อนรับสู่ Report Center</h3>
              <p class="text-dark fs-5">กรุณาเลือกรายงานจากเมนูด้านซ้าย เพื่อเรียกดูข้อมูล</p>
            </div>

            <div v-else>
              <div class="sticky-top bg-white pb-2" style="top: 0; z-index: 10; padding-top: 10px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0 calm-text-navy fw-bold">{{ selectedReport.title }}</h4>
                </div>

                <!-- Filter Section -->
                <div class="card calm-card calm-bg-lavender mb-2">
                  <div class="card-body p-4">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-5">
                        <label class="form-label small text-dark fw-bold mb-1">
                          <i class="bi bi-calendar-check calm-text-navy me-1"></i> วันที่เริ่มต้น
                        </label>
                        <input type="date" class="form-control form-control-lg calm-input px-4" v-model="startDate" />
                      </div>
                      <div class="col-md-5">
                        <label class="form-label small text-dark fw-bold mb-1">
                          <i class="bi bi-calendar-check-fill calm-text-navy me-1"></i> วันที่สิ้นสุด
                        </label>
                        <input type="date" class="form-control form-control-lg calm-input px-4" v-model="endDate" />
                      </div>
                      <div class="col-md-2">
                        <button
                          class="btn calm-btn-primary btn-lg w-100 fw-bold rounded-pill"
                          @click="runReport"
                          :disabled="loading"
                        >
                          ดึงข้อมูล
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Bar -->
              <div
                class="d-flex justify-content-between align-items-center mb-3"
                v-if="resultData && resultData.length > 0"
              >
                <div class="d-flex align-items-center">
                  <span class="me-2 text-dark small">แสดง</span>
                  <select class="form-select form-select-sm calm-input" v-model="itemsPerPage" @change="currentPage = 1" style="min-width: 80px; width: auto; height: auto;">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                    <option :value="999999">ทั้งหมด</option>
                  </select>
                  <span class="ms-2 text-dark small">รายการ</span>
                </div>
                <button
                  class="btn calm-btn-primary rounded-pill px-4 fw-bold"
                  @click="exportExcel"
                >
                  Export Excel
                </button>
              </div>

              <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              <div
                v-else-if="resultData"
                class="table-responsive border-0 calm-card bg-white"
              >
                <table class="table table-hover mb-0 align-middle" id="report-table">
                  <thead class="bg-light sticky-top" style="z-index: 1;">
                    <tr>
                      <th class="text-nowrap py-3 text-dark border-bottom-0 text-center" style="width: 60px;">ลำดับ</th>
                      <th v-for="col in columns" :key="col" class="text-nowrap py-3 text-dark border-bottom-0">{{ col }}</th>
                    </tr>
                  </thead>
                  <tbody class="border-top-0">
                    <tr v-for="(row, idx) in paginatedData" :key="idx">
                      <td class="text-nowrap text-dark text-center fw-bold">{{ (currentPage - 1) * itemsPerPage + idx + 1 }}</td>
                      <td v-for="col in columns" :key="col" class="text-nowrap text-dark">{{ row[col] }}</td>
                    </tr>
                    <tr v-if="paginatedData.length === 0">
                      <td :colspan="columns.length + 1" class="text-center py-5 text-muted">ไม่พบข้อมูล</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination Controls -->
              <div v-if="resultData && resultData.length > 0" class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-3">
                <div class="text-dark small">
                  แสดง {{ (currentPage - 1) * itemsPerPage + 1 }} ถึง {{ Math.min(currentPage * itemsPerPage, resultData.length) }} จากทั้งหมด {{ resultData.length }} รายการ
                </div>
                <nav>
                  <ul class="pagination pagination-sm mb-0 rounded-pill">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                      <button class="page-link rounded-start-pill border-0 text-dark" @click="prevPage">ก่อนหน้า</button>
                    </li>
                    
                    <li v-for="(p, index) in visiblePages" :key="index" class="page-item" :class="{ disabled: p === '...' }">
                      <button class="page-link border-0 text-dark" :class="{ 'calm-bg-lavender calm-text-navy fw-bold': currentPage === p }" @click="goToPage(p)">
                        {{ p }}
                      </button>
                    </li>

                    <li class="page-item" :class="{ disabled: currentPage === totalPages || totalPages === 0 }">
                      <button class="page-link rounded-end-pill border-0 text-dark" @click="nextPage">ถัดไป</button>
                    </li>
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRouter, useRoute } from 'vue-router';
import * as XLSX from 'xlsx'; // Need to insure XLSX is installed or use CDN?
// The user project has `node_modules`, so I should hope `xlsx` is there or I use a simple csv export if not.
// "xlsx" is common. If not, I can create a simple CSV function.
// Let's assume standard dependencies or I will use a simple CSV export function as backup or just verify later.

const router = useRouter();
const route = useRoute();
const reports = ref([]);
const searchQuery = ref('');
const selectedReport = ref(null);
const resultData = ref(null);
const columns = ref([]);
const loading = ref(false);

const currentPage = ref(1);
const itemsPerPage = ref(10);

const paginatedData = computed(() => {
  if (!resultData.value) return [];
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return resultData.value.slice(start, end);
});

const totalPages = computed(() => {
  if (!resultData.value) return 0;
  return Math.ceil(resultData.value.length / itemsPerPage.value);
});

const visiblePages = computed(() => {
  const pages = [];
  const maxPagesToShow = 5;
  const current = currentPage.value;
  const total = totalPages.value;
  
  if (total <= maxPagesToShow) {
    for (let i = 1; i <= total; i++) pages.push(i);
  } else {
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', total);
    } else if (current >= total - 2) {
      pages.push(1, '...', total - 3, total - 2, total - 1, total);
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total);
    }
  }
  return pages;
});

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};
const goToPage = (p) => {
  if (p !== '...') currentPage.value = p;
};

const getFiscalYearDates = () => {
  const now = new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth(); // 0-indexed (0 = Jan, 9 = Oct)
  
  let fiscalYearEndYear = currentYear;
  if (currentMonth >= 9) {
    fiscalYearEndYear = currentYear + 1;
  }
  
  return {
    start: `${fiscalYearEndYear - 1}-10-01`,
    end: `${fiscalYearEndYear}-09-30`
  };
};

const fyDates = getFiscalYearDates();
const startDate = ref(fyDates.start);
const endDate = ref(fyDates.end);

const departments = ref([]);
const filterDepartment = ref('');
const selectedDepartment = ref('ALL');
const expandedGroups = ref([]); // Not used anymore, but safe to keep
const isAdmin = ref(false);

const fetchUserProfile = async () => {
  try {
    const token = localStorage.getItem('user_token');
    const response = await axios.get(`${import.meta.env.VITE_API_URL || ''}/api-hosoffice/get_user_profile.php`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (response.data.status === 'success') {
      const userPerms = response.data.access_user ? response.data.access_user.split(':') : [];
      isAdmin.value = userPerms.includes('administrator');
    }
  } catch (error) {
    console.error('Failed to load profile', error);
  }
};

const departmentsWithReports = computed(() => {
  if (!reports.value || reports.value.length === 0) return [];
  const deptIdsWithReports = new Set(reports.value.map(r => r.department_id).filter(id => id));
  return departments.value.filter(dept => deptIdsWithReports.has(dept.HR_DEPARTMENT_SUB_ID));
});

const hasGeneralReports = computed(() => {
  return reports.value.some(r => !r.department_id || r.department_id === 'GENERAL');
});

const filteredReports = computed(() => {
  if (!filterDepartment.value && !searchQuery.value) {
    return []; // Show nothing initially unless they search
  }

  let result = reports.value;

  if (filterDepartment.value && filterDepartment.value !== 'ALL') {
    if (filterDepartment.value === 'GENERAL') {
      result = result.filter(r => !r.department_id || r.department_id === 'GENERAL');
    } else {
      result = result.filter(r => r.department_id === filterDepartment.value);
    }
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(r => r.title.toLowerCase().includes(q));
  }

  return result;
});

const fetchDepartments = async () => {
  try {
    const res = await axios.get(
      `${import.meta.env.VITE_API_URL || ''}/api-hosoffice/get_departments.php`
    );
    if (res.data.status === 'success') {
      departments.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to fetch departments', e);
  }
};

const fetchReports = async () => {
  try {
    const res = await axios.get(
      `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/get_reports.php`
    );
    reports.value = res.data;

    if (route.query.report_id) {
      const repId = parseInt(route.query.report_id);
      const found = reports.value.find(r => r.id === repId);
      if (found) {
        selectReport(found);
      }
    }
  } catch (e) {
    console.error(e);
  }
};

const selectReport = (rep) => {
  selectedReport.value = rep;
  resultData.value = null;
  columns.value = [];
  currentPage.value = 1;
  
  // เลื่อนหน้าจอขึ้นไปที่ส่วนค้นหาข้อมูลเมื่อเลือกรายงาน
  setTimeout(() => {
    const el = document.getElementById('report-filter-section');
    if (el) {
      // เลื่อนโดยเผื่อระยะ header ด้านบนไว้หน่อย
      const y = el.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top: y, behavior: 'smooth' });
    }
  }, 100);
};

const runReport = async () => {
  if (!selectedReport.value) return;
  loading.value = true;
  currentPage.value = 1;
  try {
    const res = await axios.post(
      `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/execute_report.php`,
      {
        report_id: selectedReport.value.id,
        start_date: startDate.value || null,
        end_date: endDate.value || null,
        department_id: selectedDepartment.value
      }
    );

    if (res.data.success) {
      columns.value = res.data.columns;
      resultData.value = res.data.data;
    } else {
      Swal.fire('Error', res.data.message, 'error');
    }
  } catch (e) {
    Swal.fire('Error', 'Execution failed', 'error');
  } finally {
    loading.value = false;
  }
};

const exportExcel = () => {
  if (!resultData.value || resultData.value.length === 0) return;

  // Check if XLSX is available
  if (typeof XLSX !== 'undefined') {
    // สร้างแผ่นงานเปล่าก่อน
    const ws = XLSX.utils.json_to_sheet([]);
    
    // เพิ่มชื่อรายงานลงในแถวแรก (A1)
    XLSX.utils.sheet_add_aoa(ws, [[selectedReport.value.title]], { origin: 'A1' });
    
    // เพิ่มข้อมูลตารางโดยให้เริ่มที่แถวที่ 3 (A3)
    XLSX.utils.sheet_add_json(ws, resultData.value, { origin: 'A3', skipHeader: false });

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    XLSX.writeFile(
      wb,
      `${selectedReport.value.title}_${new Date().toISOString().slice(0, 10)}.xlsx`
    );
  } else {
    // Fallback to CSV
    console.warn('XLSX lib not found, falling back to CSV');
    exportCSV();
  }
};

const exportCSV = () => {
  const items = resultData.value;
  const keys = Object.keys(items[0]);
  const csvContent = [
    `"${selectedReport.value.title}"`,
    "", // Empty line
    keys.join(','),
    ...items.map((row) => keys.map((k) => `"${String(row[k]).replace(/"/g, '""')}"`).join(','))
  ].join('\n');

  const blob = new Blob([new Uint8Array([0xef, 0xbb, 0xbf]), csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `${selectedReport.value.title}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const goHome = () => {
  router.push('/home-backoffice');
};

onMounted(() => {
  fetchUserProfile();
  fetchReports();
  fetchDepartments();
  // Dynamically load XLSX if not present? Or just use CSV.
  // I'll check package.json in next step to see if xlsx is installed.
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;700&display=swap');

* {
  font-family: 'Figtree', sans-serif;
}

.calm-text-navy {
  color: #1a3e6f !important;
}

.calm-bg-lavender {
  background-color: #e2eaff !important;
}

.calm-btn-primary {
  background: linear-gradient(90deg, #2477aa, #6461e0) !important;
  color: #ffffff !important;
  border: none !important;
}

.calm-btn-primary:hover {
  background: linear-gradient(90deg, #6461e0, #2477aa) !important;
}

.calm-btn-secondary {
  background-color: #ffffff !important;
  color: #1a3e6f !important;
  border: 1px solid #1a3e6f !important;
}

.calm-btn-secondary:hover {
  background-color: #f8f9fa !important;
}

.calm-input {
  border: 1px solid #1a3e6f !important;
  border-radius: 10px !important;
  box-shadow: none !important;
}

.calm-input:focus {
  border-width: 2px !important;
  outline: none !important;
}

.calm-card {
  border-radius: 20px !important;
  box-shadow: none !important;
  border: none !important;
}

/* Remove Bootstrap shadows globally within this scoped component to strictly enforce flat design */
:deep(.shadow-sm), :deep(.shadow) {
  box-shadow: none !important;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}
.hover-bg-light:hover {
  background-color: #f8f9fa !important;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
