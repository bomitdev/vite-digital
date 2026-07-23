<template>
  <div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h3 class="mb-0 text-primary fw-bold">
          <i class="bi bi-code-square me-2"></i>จัดการรายงาน SQL (Admin)
        </h3>
        <div>
          <button @click="openModal()" class="btn btn-primary rounded-pill shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> สร้างรายงานใหม่
          </button>
          <button @click="goHome" class="btn btn-outline-secondary rounded-pill ms-2">
            <i class="bi bi-house me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>

      <div class="card-body">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th scope="col">Title</th>
                <th scope="col">Description</th>
                <th scope="col">DB Connection</th>
                <th scope="col" style="width: 150px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in reports" :key="report.id">
                <td class="fw-bold">{{ report.title }}</td>
                <td>{{ report.description }}</td>
                <td>
                  <span class="badge bg-info text-dark">DB{{ report.db_connection }}</span>
                </td>
                <td>
                  <button @click="openModal(report)" class="btn btn-sm btn-warning me-1 text-white">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button @click="runPreview(report)" class="btn btn-sm btn-success">
                    <i class="bi bi-play-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="reports.length === 0">
                <td colspan="4" class="text-center text-muted py-4">ไม่พบรายงาน</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">
              {{ isEdit ? 'แก้ไขรายงาน' : 'สร้างรายงานใหม่' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
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
                      <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold mb-1">Start</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="testStartDate"
                        />
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold mb-1">End</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="testEndDate"
                        />
                      </div>
                      <div class="col-md-4">
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

const router = useRouter();
const reports = ref([]);
const departments = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEdit = ref(false);

const form = ref({
  id: null,
  title: '',
  description: '',
  db_connection: 1,
  department_id: null,
  sql_query: ''
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
    form.value = { ...report };
  } else {
    isEdit.value = false;
    form.value = {
      id: null,
      title: '',
      description: '',
      db_connection: 1,
      department_id: null,
      sql_query: ''
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
        start_date: testStartDate.value || null,
        end_date: testEndDate.value || null,
        department_id: testDepartment.value
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
.font-monospace {
  font-family: 'Consolas', 'Monaco', monospace;
  font-size: 14px;
}
</style>
