<template>
  <div class="report-admin-page container-fluid py-3 py-md-4 px-3 px-md-4 min-vh-100">
    <div class="card main-card border-0 rounded-4 overflow-hidden">
      <div class="card-top-accent"></div>

      <!-- Header -->
      <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-box shadow-sm">
            <i class="bi bi-code-square"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h5 class="mb-0 text-dark fw-bold">จัดการรายงาน SQL (Admin)</h5>
              <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">
                {{ reports.length }} รายงาน
              </span>
            </div>
            <small class="text-muted">สร้าง แก้ไข และทดสอบคำสั่ง SQL สำหรับระบบ Report Center</small>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <button @click="openModal()" class="btn btn-primary btn-create-report rounded-pill px-3 shadow-sm fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> สร้างรายงานใหม่
          </button>
          <button @click="$router.push('/report')" class="btn btn-outline-info rounded-pill px-3 fw-medium">
            <i class="bi bi-chat-text-fill me-1"></i> ระบบขอรายงาน
          </button>
          <button @click="$router.push('/report-center')" class="btn btn-outline-primary rounded-pill px-3 fw-medium">
            <i class="bi bi-table me-1"></i> หน้า Report Center
          </button>
          <button @click="goHome" class="btn btn-light border rounded-pill px-3 text-secondary fw-medium">
            <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>

      <div class="card-body p-3 p-md-4">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="text-muted small mt-2">กำลังโหลดรายการรายงาน...</div>
        </div>

        <div v-else>
          <!-- Search & Filter Toolbar -->
          <div class="filter-toolbar p-3 rounded-4 mb-3">
            <div class="row g-2 align-items-center">
              <div class="col-lg-5 col-md-6">
                <div class="input-group search-input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                  </span>
                  <input
                    type="text"
                    class="form-control border-start-0 shadow-none"
                    placeholder="ค้นหาชื่อรายงาน, รายละเอียด, แผนก..."
                    v-model="searchQuery"
                  />
                  <button
                    v-if="searchQuery"
                    type="button"
                    class="btn btn-white border border-start-0 text-muted"
                    @click="searchQuery = ''"
                    title="ล้างคำค้นหา"
                  >
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>

              <div class="col-lg-3 col-md-3">
                <select class="form-select rounded-pill border-slate shadow-none" v-model="selectedDeptFilter">
                  <option value="">-- ทุกแผนก / กลุ่มงาน --</option>
                  <option value="GENERAL">ทั่วไป</option>
                  <option
                    v-for="dept in usedDepartments"
                    :key="dept.id"
                    :value="String(dept.id)"
                  >
                    {{ dept.name }}
                  </option>
                </select>
              </div>

              <div class="col-lg-2 col-md-3">
                <select class="form-select rounded-pill border-slate shadow-none" v-model="selectedDbFilter">
                  <option value="">-- ทุกฐานข้อมูล --</option>
                  <option value="1">DB1 (HOSxP)</option>
                  <option value="2">DB2 (Digital)</option>
                  <option value="3">DB3 (HOSoffice)</option>
                </select>
              </div>

              <div class="col-lg-2 text-lg-end small text-muted fw-medium">
                แสดง {{ filteredReports.length }} จาก {{ reports.length }} รายการ
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="table-responsive rounded-3 border">
            <table class="table table-hover align-middle mb-0 report-table">
              <thead>
                <tr>
                  <th style="width: 52px" class="text-center">#</th>
                  <th>ชื่อรายงาน / รายละเอียด</th>
                  <th style="width: 240px">แผนก / กลุ่มงาน</th>
                  <th style="width: 150px" class="text-center">ฐานข้อมูล</th>
                  <th style="width: 130px" class="text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(report, index) in filteredReports" :key="report.id">
                  <td class="text-center text-muted small fw-semibold">{{ index + 1 }}</td>
                  <td>
                    <div class="fw-semibold text-dark">{{ report.title }}</div>
                    <div v-if="report.description" class="small text-muted mt-1">
                      {{ report.description }}
                    </div>
                  </td>
                  <td>
                    <span class="dept-pill dept-pill-general" v-if="!report.department_id">
                      <i class="bi bi-folder2 me-1"></i>ทั่วไป
                    </span>
                    <span class="dept-pill dept-pill-active" v-else>
                      <i class="bi bi-building me-1"></i>{{ getDepartmentName(report.department_id) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span :class="['db-pill', getDbBadgeClass(report.db_connection)]">
                      <i class="bi bi-database me-1"></i>{{ getDbLabel(report.db_connection) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-inline-flex gap-1">
                      <button
                        @click="openModal(report)"
                        class="btn btn-sm btn-outline-warning rounded-pill px-2 fw-medium"
                        title="แก้ไขรายงาน"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        @click="runPreview(report)"
                        class="btn btn-sm btn-success rounded-pill px-3 fw-medium"
                        title="ทดสอบรัน SQL"
                      >
                        <i class="bi bi-play-fill me-1"></i>รัน
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredReports.length === 0">
                  <td colspan="5" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    <div class="fw-medium">ไม่พบรายงานที่ตรงกับเงื่อนไข</div>
                    <small>ลองเปลี่ยนคำค้นหาหรือตัวกรองแผนก/ฐานข้อมูล</small>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <div class="modal-header modal-header-gradient text-white py-3 px-4">
            <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
              <i class="bi bi-code-slash"></i>
              <span>{{ isEdit ? 'แก้ไขรายงาน SQL' : 'สร้างรายงาน SQL ใหม่' }}</span>
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-bold">Title</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.title"
                  placeholder="ชื่อรายงาน"
                />
              </div>
              <div class="col-md-4">
                <label class="form-label fw-bold">Database Connection</label>
                <select class="form-select" v-model="form.db_connection">
                  <option :value="1">HOSxP</option>
                  <option :value="2">Digital</option>
                  <option :value="3">HOSoffice</option>
                </select>
              </div>
              <div class="col-md-12">
                <label class="form-label fw-bold">Department Category (For Menu Grouping)</label>
                <select class="form-select" v-model="form.department_id">
                  <option :value="null">-- General / No Specific Department --</option>
                  <option
                    v-for="dept in departments"
                    :key="dept.HR_DEPARTMENT_SUB_ID"
                    :value="dept.HR_DEPARTMENT_SUB_ID"
                  >
                    {{ dept.HR_DEPARTMENT_SUB_NAME }}
                  </option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.description"
                  placeholder="รายละเอียดเพิ่มเติม"
                />
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">SQL Query</label>
                <textarea
                  class="form-control font-monospace bg-light"
                  v-model="form.sql_query"
                  rows="10"
                  placeholder="SELECT * FROM ..."
                ></textarea>
                <small class="text-muted d-block mt-1">
                  <i class="bi bi-info-circle me-1"></i>
                  Use <code>:start_date</code> and <code>:end_date</code> placeholders for date
                  filtering.
                </small>
              </div>
              


              <!-- Preview Section inside Modal -->
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label fw-bold mb-0">Result Preview</label>
                  <div class="bg-light p-3 rounded-3 border mb-2">
                    <div class="row g-2 align-items-end">
                      <template v-if="testParameters.length > 0">
                        <div class="col-md-3" v-for="param in testParameters" :key="param.name">
                          <label class="form-label small text-muted fw-bold mb-1">{{ param.label }}</label>
                          <input v-if="param.type === 'date'" type="date" class="form-control form-control-sm" v-model="testFormValues[param.name]" />
                          <input v-else-if="param.type === 'text' || param.type === 'number'" :type="param.type" class="form-control form-control-sm" v-model="testFormValues[param.name]" />
                          
                          <select v-else-if="param.type === 'query_select'" class="form-select form-select-sm" v-model="testFormValues[param.name]" :disabled="testIsFetchingOptions[param.name]">
                            <option value="" disabled>-- Select --</option>
                            <option v-for="opt in testDynamicOptions[param.name]" 
                                    :key="opt.id || opt.value || (typeof opt === 'object' ? Object.values(opt)[0] : opt)" 
                                    :value="opt.id || opt.value || (typeof opt === 'object' ? Object.values(opt)[0] : opt)">
                              {{ opt.name || opt.label || (typeof opt === 'object' ? (Object.values(opt)[1] || Object.values(opt)[0]) : opt) }}
                            </option>
                          </select>
                          <div v-if="testIsFetchingOptions[param.name]" class="small text-muted mt-1" style="font-size: 0.7rem;">กำลังโหลด...</div>
                        </div>
                      </template>
                      <template v-else>
                        <div class="col-md-4">
                          <label class="form-label small text-muted fw-bold mb-1">Start</label>
                          <input type="date" class="form-control form-control-sm" v-model="testStartDate" />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label small text-muted fw-bold mb-1">End</label>
                          <input type="date" class="form-control form-control-sm" v-model="testEndDate" />
                        </div>
                      </template>
                      
                      <div class="col-md-2 ms-auto">
                        <button
                          @click="testRun"
                          class="btn btn-sm btn-primary w-100"
                          title="Run Test"
                        >
                          <i class="bi bi-play-fill me-1"></i> Test Run
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  v-if="previewResult"
                  class="border rounded p-2 bg-white"
                  style="max-height: 200px; overflow: auto; font-size: 0.85rem"
                >
                  <table
                    class="table table-bordered table-sm mb-0"
                    v-if="previewResult.data && previewResult.data.length > 0"
                  >
                    <thead>
                      <tr>
                        <th v-for="col in previewResult.columns" :key="col">{{ col }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, idx) in previewResult.data" :key="idx">
                        <td v-for="col in previewResult.columns" :key="col">{{ row[col] }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-else-if="previewResult.success" class="text-success p-2">
                    Query executed successfully. (No rows returned or non-SELECT query)
                  </div>
                  <div v-else class="text-danger p-2">
                    {{ previewResult.message }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer d-flex justify-content-between">
            <div>
              <button
                v-if="isEdit"
                type="button"
                class="btn btn-outline-danger"
                @click="deleteReport"
              >
                <i class="bi bi-trash me-1"></i> Delete
              </button>
            </div>
            <div>
              <button type="button" class="btn btn-secondary me-2" @click="closeModal">
                Cancel
              </button>
              <button type="button" class="btn btn-primary" @click="saveReport">Save Report</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

const router = useRouter();
const reports = ref([]);
const departments = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEdit = ref(false);
const searchQuery = ref('');
const selectedDeptFilter = ref('');
const selectedDbFilter = ref('');

const getDepartmentName = (deptId) => {
  if (!deptId) return 'ทั่วไป';
  const dept = departments.value.find(d => String(d.HR_DEPARTMENT_SUB_ID) === String(deptId));
  return dept ? dept.HR_DEPARTMENT_SUB_NAME : 'ทั่วไป';
};

const getDbLabel = (conn) => {
  switch (Number(conn)) {
    case 1: return 'DB1 · HOSxP';
    case 2: return 'DB2 · Digital';
    case 3: return 'DB3 · HOSoffice';
    default: return `DB${conn}`;
  }
};

const getDbBadgeClass = (conn) => {
  switch (Number(conn)) {
    case 1: return 'db-pill-hosxp';
    case 2: return 'db-pill-digital';
    case 3: return 'db-pill-hosoffice';
    default: return 'db-pill-default';
  }
};

const usedDepartments = computed(() => {
  const seen = new Map();
  for (const r of reports.value) {
    if (r.department_id) {
      const id = String(r.department_id);
      if (!seen.has(id)) {
        seen.set(id, { id, name: getDepartmentName(r.department_id) });
      }
    }
  }
  return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name, 'th'));
});

const filteredReports = computed(() => {
  return reports.value.filter((r) => {
    if (selectedDeptFilter.value === 'GENERAL' && r.department_id) return false;
    if (selectedDeptFilter.value && selectedDeptFilter.value !== 'GENERAL' && String(r.department_id) !== selectedDeptFilter.value) {
      return false;
    }
    if (selectedDbFilter.value && String(r.db_connection) !== selectedDbFilter.value) {
      return false;
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const matchTitle = r.title && r.title.toLowerCase().includes(q);
      const matchDesc = r.description && r.description.toLowerCase().includes(q);
      const matchDept = getDepartmentName(r.department_id).toLowerCase().includes(q);
      if (!matchTitle && !matchDesc && !matchDept) return false;
    }
    return true;
  });
});

const form = ref({
  id: null,
  title: '',
  description: '',
  db_connection: 1,
  department_id: null,
  sql_query: '',
  parameters: ''
});

const previewResult = ref(null);

const checkAdmin = async () => {
  try {
    const token = localStorage.getItem('user_token');
    if (!token) {
      router.push('/login');
      return;
    }
    const response = await axios.get(`${import.meta.env.VITE_API_URL || ''}/api-hosoffice/get_user_profile.php`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (response.data.status === 'success') {
      const userPerms = response.data.access_user ? response.data.access_user.split(':') : [];
      if (!userPerms.includes('administrator')) {
        Swal.fire('ปฏิเสธการเข้าถึง', 'เมนูนี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น', 'warning');
        router.push('/report-center');
      }
    } else {
      router.push('/report-center');
    }
  } catch (error) {
    console.error('Failed to load profile', error);
    router.push('/report-center');
  }
};

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
  loading.value = true;
  try {
    const res = await axios.get(
      `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/get_reports.php`
    );
    reports.value = res.data;
  } catch (e) {
    Swal.fire('Error', 'Failed to load reports', 'error');
  } finally {
    loading.value = false;
  }
};

const openModal = (report = null) => {
  previewResult.value = null;
  const today = new Date().toISOString().split('T')[0];
  testStartDate.value = today;
  testEndDate.value = today;
  
  if (report) {
    isEdit.value = true;
    form.value = { 
      ...report,
      parameters: report.parameters ? JSON.stringify(report.parameters, null, 2) : ''
    };
  } else {
    isEdit.value = false;
    form.value = {
      id: null,
      title: '',
      description: '',
      db_connection: 1,
      department_id: null,
      sql_query: '',
      parameters: ''
    };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const testStartDate = ref('');
const testEndDate = ref('');
const testDepartment = ref('ALL');
const testFormValues = ref({});
const testDynamicOptions = ref({});
const testIsFetchingOptions = ref({});

const testParameters = computed(() => {
  const params = [];
  if (!form.value.sql_query) return params;
  
  if (form.value.sql_query.includes(':start_date')) {
    params.push({ name: 'start_date', type: 'date', label: 'Start Date' });
  }
  if (form.value.sql_query.includes(':end_date')) {
    params.push({ name: 'end_date', type: 'date', label: 'End Date' });
  }
  
  const lines = form.value.sql_query.split('\n');
  for (const line of lines) {
    const match = line.trim().match(/^:([a-zA-Z0-9_]+)\s*=\s*(SELECT\s+.*)/i);
    if (match) {
      params.push({
        name: match[1],
        type: 'query_select',
        label: match[1],
        query: match[2]
      });
    }
  }
  return params;
});

let debounceTimer = null;
watch(() => form.value.sql_query, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    testParameters.value.forEach(async (param) => {
      if (param.type === 'query_select' && param.query) {
        if (!testDynamicOptions.value[param.name]) {
          testIsFetchingOptions.value[param.name] = true;
          try {
            const res = await axios.post(`${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/execute_raw_sql.php`, {
              sql_query: param.query,
              db_connection: form.value.db_connection
            });
            if (res.data.success && res.data.data) {
              testDynamicOptions.value[param.name] = res.data.data;
            }
          } catch (e) {
            console.error(e);
          } finally {
            testIsFetchingOptions.value[param.name] = false;
          }
        }
      }
    });
  }, 500);
});

const testRun = async () => {
  if (!form.value.sql_query) {
    Swal.fire('Warning', 'Please enter SQL Query', 'warning');
    return;
  }
  try {
    const res = await axios.post(
      `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/execute_raw_sql.php`,
      {
        sql_query: form.value.sql_query,
        db_connection: form.value.db_connection,
        start_date: testFormValues.value['start_date'] || testStartDate.value || null,
        end_date: testFormValues.value['end_date'] || testEndDate.value || null,
        department_id: testDepartment.value,
        parameters: testFormValues.value
      }
    );
    previewResult.value = res.data;
  } catch (e) {
    previewResult.value = { success: false, message: e.message };
  }
};

const saveReport = async () => {
  if (!form.value.title || !form.value.sql_query) {
    Swal.fire('Warning', 'Title and Query are required', 'warning');
    return;
  }
  try {
    const res = await axios.post(
      `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/save_report.php`,
      form.value
    );
    if (res.data.success) {
      Swal.fire('Success', 'Report saved', 'success');
      closeModal();
      fetchReports();
    } else {
      Swal.fire('Error', res.data.message, 'error');
    }
  } catch (e) {
    Swal.fire('Error', 'Save failed', 'error');
  }
};

const deleteReport = async () => {
  if (!form.value.id) return;

  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  });

  if (result.isConfirmed) {
    try {
      const res = await axios.post(
        `${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/delete_report.php`,
        { id: form.value.id }
      );
      if (res.data.success) {
        Swal.fire('Deleted!', 'Report has been deleted.', 'success');
        closeModal();
        fetchReports();
      } else {
        Swal.fire('Error', res.data.message, 'error');
      }
    } catch (e) {
      Swal.fire('Error', 'Failed to delete report', 'error');
    }
  }
};

const goHome = () => {
  router.push('/home-backoffice');
};

const runPreview = (report) => {
  // Maybe open user view or just quick run
  // For now, let's open modal and auto-run?
  openModal(report);
  setTimeout(() => {
    testRun();
  }, 100);
};

onMounted(() => {
  checkAdmin();
  fetchReports();
  fetchDepartments();
});
</script>

<style scoped>
.report-admin-page {
  background: radial-gradient(circle at top left, #eff6ff 0%, #f8fafc 45%, #f1f5f9 100%);
}

.main-card {
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  border: 1px solid #e2e8f0 !important;
}

.card-top-accent {
  height: 4px;
  background: linear-gradient(90deg, #2563eb 0%, #4f46e5 50%, #0ea5e9 100%);
}

.header-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.btn-create-report {
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  border: none;
}

.filter-toolbar {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
}

.search-input-group .input-group-text,
.search-input-group .form-control,
.search-input-group .btn,
.border-slate {
  border-color: #cbd5e1;
}

.search-input-group .input-group-text {
  border-top-left-radius: 999px;
  border-bottom-left-radius: 999px;
}

.search-input-group .form-control:last-child,
.search-input-group .btn:last-child {
  border-top-right-radius: 999px;
  border-bottom-right-radius: 999px;
}

.report-table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.75rem 0.85rem;
  white-space: nowrap;
}

.report-table tbody td {
  padding: 0.7rem 0.85rem;
  border-color: #f1f5f9;
}

.dept-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.75rem;
  font-size: 0.78rem;
  font-weight: 600;
  max-width: 100%;
}

.dept-pill-active {
  background-color: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.dept-pill-general {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.db-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.22rem 0.7rem;
  font-size: 0.76rem;
  font-weight: 700;
  white-space: nowrap;
}

.db-pill-hosxp {
  background-color: #ecfeff;
  color: #0e7490;
  border: 1px solid #a5f3fc;
}

.db-pill-digital {
  background-color: #f5f3ff;
  color: #6d28d9;
  border: 1px solid #ddd6fe;
}

.db-pill-hosoffice {
  background-color: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.db-pill-default {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.modal-header-gradient {
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
}

.font-monospace {
  font-family: 'Consolas', 'Monaco', monospace;
  font-size: 14px;
}
</style>
