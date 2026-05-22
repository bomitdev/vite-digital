<template>
  <div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h3 class="mb-0 text-primary fw-bold"><i class="bi bi-table me-2"></i>Report Center</h3>
        <button @click="goHome" class="btn btn-outline-secondary rounded-pill">
          <i class="bi bi-house me-1"></i> กลับหน้าหลัก
        </button>
      </div>

      <div class="card-body">
        <div class="row">
          <!-- Sidebar / List -->
          <div class="col-md-3 border-end">
            <div class="mb-3">
              <input
                type="text"
                class="form-control"
                placeholder="ค้นหารายงาน..."
                v-model="searchQuery"
              />
            </div>
            <div class="list-group list-group-flush custom-scrollbar">
              <template v-for="group in groupedReports" :key="group.id">
                <div
                  class="list-group-item fw-bold text-uppercase py-2 text-white d-flex justify-content-between align-items-center cursor-pointer"
                  style="background: linear-gradient(45deg, #0d6efd, #0dcaf0); cursor: pointer"
                  v-if="group.reports.length > 0"
                  @click="toggleGroup(group.id)"
                >
                  <div><i class="bi bi-bookmarks-fill me-2"></i>{{ group.name }}</div>
                  <i
                    class="bi"
                    :class="expandedGroups.includes(group.id) ? 'bi-dash-lg' : 'bi-plus-lg'"
                  ></i>
                </div>

                <div v-show="expandedGroups.includes(group.id)">
                  <button
                    v-for="rep in group.reports"
                    :key="rep.id"
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ps-4 border-start border-4"
                    :class="{
                      active: selectedReport && selectedReport.id === rep.id,
                      'border-primary': selectedReport && selectedReport.id === rep.id,
                      'border-light': !selectedReport || selectedReport.id !== rep.id
                    }"
                    @click="selectReport(rep)"
                  >
                    <div>
                      <div class="fw-bold">{{ rep.title }}</div>
                      <small
                        class="text-muted d-block text-truncate"
                        style="max-width: 200px"
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
            <div v-if="!selectedReport" class="text-center py-5 text-muted">
              <i class="bi bi-arrow-left-circle display-4 mb-3 d-block"></i>
              <h4>กรุณาเลือกรายงานจากเมนูด้านซ้าย</h4>
            </div>

            <div v-else>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 text-primary fw-bold">{{ selectedReport.title }}</h4>
              </div>

              <!-- Filter Section -->
              <div class="card border-0 bg-light mb-4 rounded-3 shadow-sm">
                <div class="card-body p-4">
                  <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                      <label class="form-label small text-uppercase text-secondary fw-bold mb-1">
                        <i class="bi bi-calendar-event me-1"></i>Start Date
                      </label>
                      <input type="date" class="form-control shadow-sm" v-model="startDate" />
                    </div>
                    <div class="col-md-5">
                      <label class="form-label small text-uppercase text-secondary fw-bold mb-1">
                        <i class="bi bi-calendar-event me-1"></i>End Date
                      </label>
                      <input type="date" class="form-control shadow-sm" v-model="endDate" />
                    </div>
                    <div class="col-md-2">
                      <button
                        class="btn btn-primary w-100 shadow-sm fw-bold text-uppercase"
                        @click="runReport"
                        :disabled="loading"
                      >
                        <i class="bi bi-play-fill me-1"></i> Run
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
                class="table-responsive border rounded bg-white shadow-sm"
                style="max-height: 600px; overflow: auto"
              >
                <table class="table table-striped table-hover mb-0" id="report-table">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th v-for="col in columns" :key="col" class="text-nowrap">{{ col }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in resultData" :key="idx">
                      <td v-for="col in columns" :key="col" class="text-nowrap">{{ row[col] }}</td>
                    </tr>
                    <tr v-if="resultData.length === 0">
                      <td :colspan="columns.length" class="text-center p-3">ไม่พบข้อมูล</td>
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
import { useRouter } from 'vue-router';
import * as XLSX from 'xlsx'; // Need to insure XLSX is installed or use CDN?
// The user project has `node_modules`, so I should hope `xlsx` is there or I use a simple csv export if not.
// "xlsx" is common. If not, I can create a simple CSV function.
// Let's assume standard dependencies or I will use a simple CSV export function as backup or just verify later.

const router = useRouter();
const reports = ref([]);
const searchQuery = ref('');
const selectedReport = ref(null);
const resultData = ref(null);
const columns = ref([]);
const loading = ref(false);
const startDate = ref('');
const endDate = ref('');
const departments = ref([]);
const selectedDepartment = ref('ALL');
const expandedGroups = ref([]);

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
  fetchReports();
  fetchDepartments();
  // Dynamically load XLSX if not present? Or just use CSV.
  // I'll check package.json in next step to see if xlsx is installed.
});
</script>
