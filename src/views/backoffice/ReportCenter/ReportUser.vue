<template>
  <div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h3 class="mb-0 text-primary fw-bold"><i class="bi bi-table me-2"></i>Report Center</h3>
        <div class="d-flex gap-2">
          <button v-if="isAdmin" @click="$router.push('/report-center/admin')" class="btn btn-warning shadow-sm rounded-pill text-white fw-bold">
            <i class="bi bi-gear-fill me-1"></i> จัดการรายงาน (Admin)
          </button>
          <button @click="$router.push('/report')" class="btn btn-success shadow-sm rounded-pill text-white fw-bold">
            <i class="bi bi-chat-text-fill me-1"></i> ระบบขอรายงานข้อมูล
          </button>
          <button @click="goHome" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-house me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>

      <div class="card-body">
        <div class="row">
          <!-- Sidebar / List -->
          <div class="col-md-3 border-end">
            <div class="mb-4 position-relative">
              <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              <input
                type="text"
                class="form-control form-control-lg rounded-pill bg-light border-0 ps-5 shadow-sm"
                placeholder="ค้นหารายงาน..."
                v-model="searchQuery"
              />
            </div>
            <div class="list-group list-group-flush custom-scrollbar">
              <template v-for="group in groupedReports" :key="group.id">
                <div
                  class="list-group-item fw-bold py-3 d-flex justify-content-between align-items-center cursor-pointer border-0 bg-light mb-2 rounded-3 transition-all"
                  v-if="group.reports.length > 0"
                  @click="toggleGroup(group.id)"
                  onmouseover="this.classList.add('shadow-sm', 'bg-white'); this.classList.remove('bg-light');"
                  onmouseout="this.classList.remove('shadow-sm', 'bg-white'); this.classList.add('bg-light');"
                >
                  <div class="text-dark"><i class="bi bi-folder2-open me-2 text-primary fs-5 align-middle"></i>{{ group.name }}</div>
                  <i
                    class="bi text-secondary"
                    :class="expandedGroups.includes(group.id) ? 'bi-chevron-down' : 'bi-chevron-right'"
                  ></i>
                </div>

                <div v-show="expandedGroups.includes(group.id)" class="mb-3 px-1">
                  <button
                    v-for="rep in group.reports"
                    :key="rep.id"
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1 rounded-3 border-0 transition-all"
                    :class="{
                      'bg-primary text-white shadow-sm': selectedReport && selectedReport.id === rep.id,
                      'bg-white hover-bg-light': !selectedReport || selectedReport.id !== rep.id
                    }"
                    style="border-left: 4px solid transparent !important;"
                    :style="selectedReport && selectedReport.id === rep.id ? 'border-left: 4px solid #fff !important;' : 'border-left: 4px solid #dee2e6 !important;'"
                    @click="selectReport(rep)"
                  >
                    <div>
                      <div class="fw-bold"><i class="bi bi-file-earmark-text me-2" :class="selectedReport && selectedReport.id === rep.id ? 'text-white' : 'text-primary'"></i>{{ rep.title }}</div>
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
              </template>

              <div v-if="groupedReports.length === 0" class="text-center text-muted p-3">
                ไม่พบรายงาน
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="col-md-9">
            <div v-if="!selectedReport" class="d-flex flex-column align-items-center justify-content-center h-100 py-5 mt-5">
              <div class="bg-primary bg-opacity-10 rounded-circle p-4 mb-4 shadow-sm" style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-bar-chart-line text-primary" style="font-size: 4rem;"></i>
              </div>
              <h3 class="text-secondary fw-bold">ยินดีต้อนรับสู่ Report Center</h3>
              <p class="text-muted fs-5">กรุณาเลือกรายงานจากเมนูด้านซ้าย เพื่อเรียกดูข้อมูล</p>
            </div>

            <div v-else>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 text-primary fw-bold">{{ selectedReport.title }}</h4>
              </div>

              <!-- Filter Section -->
              <div class="card border-0 mb-4 rounded-4 shadow-sm" style="background: linear-gradient(to right, #f8f9fa, #ffffff);">
                <div class="card-body p-4">
                  <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                      <label class="form-label small text-secondary fw-bold mb-2">
                        <i class="bi bi-calendar-check text-primary me-1"></i> วันที่เริ่มต้น
                      </label>
                      <input type="date" class="form-control form-control-lg rounded-pill border-0 shadow-sm px-4" v-model="startDate" />
                    </div>
                    <div class="col-md-5">
                      <label class="form-label small text-secondary fw-bold mb-2">
                        <i class="bi bi-calendar-check-fill text-primary me-1"></i> วันที่สิ้นสุด
                      </label>
                      <input type="date" class="form-control form-control-lg rounded-pill border-0 shadow-sm px-4" v-model="endDate" />
                    </div>
                    <div class="col-md-2">
                      <button
                        class="btn btn-primary btn-lg w-100 shadow-sm fw-bold rounded-pill"
                        @click="runReport"
                        :disabled="loading"
                      >
                        <i class="bi bi-play-circle-fill me-1"></i> ดึงข้อมูล
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Bar -->
              <div
                class="d-flex justify-content-end mb-3"
                v-if="resultData && resultData.length > 0"
              >
                <button
                  class="btn btn-success custom-shadow hover-scale rounded-pill px-4"
                  @click="exportExcel"
                >
                  <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </button>
              </div>

              <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              <div
                v-else-if="resultData"
                class="table-responsive border-0 rounded-4 bg-white shadow-sm"
                style="max-height: 600px; overflow: auto"
              >
                <table class="table table-hover mb-0 align-middle" id="report-table">
                  <thead class="bg-light sticky-top" style="z-index: 1;">
                    <tr>
                      <th v-for="col in columns" :key="col" class="text-nowrap py-3 text-secondary border-bottom-0">{{ col }}</th>
                    </tr>
                  </thead>
                  <tbody class="border-top-0">
                    <tr v-for="(row, idx) in resultData" :key="idx">
                      <td v-for="col in columns" :key="col" class="text-nowrap text-secondary">{{ row[col] }}</td>
                    </tr>
                    <tr v-if="resultData.length === 0">
                      <td :colspan="columns.length" class="text-center py-5 text-muted">ไม่พบข้อมูล</td>
                    </tr>
                  </tbody>
                </table>
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
const selectedDepartment = ref('ALL');
const expandedGroups = ref([]);
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

const toggleGroup = (id) => {
  if (expandedGroups.value.includes(id)) {
    expandedGroups.value = expandedGroups.value.filter((gId) => gId !== id);
  } else {
    expandedGroups.value.push(id);
  }
};

const groupedReports = computed(() => {
  let baseReports = reports.value; // Start with all reports

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    baseReports = baseReports.filter((r) => r.title.toLowerCase().includes(q));
  }

  // Group by Department
  const groups = {};

  // Initialize groups from departments list to ensure order (optional, but good)
  departments.value.forEach((dept) => {
    groups[dept.HR_DEPARTMENT_SUB_ID] = {
      id: dept.HR_DEPARTMENT_SUB_ID,
      name: dept.HR_DEPARTMENT_SUB_NAME,
      reports: []
    };
  });
  // Add General group
  groups['GENERAL'] = { id: 'GENERAL', name: 'General / Uncategorized', reports: [] };

  baseReports.forEach((rep) => {
    if (rep.department_id && groups[rep.department_id]) {
      groups[rep.department_id].reports.push(rep);
    } else {
      groups['GENERAL'].reports.push(rep);
    }
  });

  // Convert to array and filter out empty groups (optional, or keep them)
  // Let's filter out empty to keep it clean, unless "All" is selected?
  // User just wants to see categorization.
  // Convert to array and filter out empty groups
  const result = Object.values(groups).filter((g) => g.reports.length > 0);

  // Auto-expand if searching
  if (searchQuery.value) {
    result.forEach((g) => {
      if (!expandedGroups.value.includes(g.id)) {
        expandedGroups.value.push(g.id);
      }
    });
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
  // Reset dates or keep them? Let's keep them for convenience.
};

const runReport = async () => {
  if (!selectedReport.value) return;
  loading.value = true;
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
    const ws = XLSX.utils.json_to_sheet(resultData.value);
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
    keys.join(','),
    ...items.map((row) => keys.map((k) => `"${String(row[k]).replace(/"/g, '""')}"`).join(','))
  ].join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
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
