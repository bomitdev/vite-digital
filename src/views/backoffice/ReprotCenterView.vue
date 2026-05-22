<template>
  <div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h3 class="mb-4 text-primary fw-bold">
          <i class="bi bi-database-fill-gear me-2"></i>ระบบรายงานข้อมูล (Data Report)
        </h3>

        <div class="d-flex gap-2">
          <button
            @click="fetchData"
            class="btn btn-outline-primary btn-sm rounded-pill"
            :disabled="loading"
          >
            <i class="bi bi-arrow-clockwise me-1" :class="{ spin: loading }"></i>
            รีเฟรช
          </button>
          <button @click="openAddModal" class="btn btn-success btn-sm rounded-pill shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลรายงาน
          </button>
          <button @click="goHomeBackoffice" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
          </button>
        </div>
      </div>

      <div class="card-body p-0">
        <div v-if="error" class="alert alert-danger d-flex align-items-center m-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
        </div>
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
          <div class="input-group" style="max-width: 300px">
            <span class="input-group-text bg-white border-end-0"
              ><i class="bi bi-search text-muted"></i
            ></span>
            <input
              type="text"
              class="form-control border-start-0 ps-0"
              placeholder="ค้นหาชื่อรายงาน, ผู้ขอ..."
              v-model="searchQuery"
            />
          </div>
          <div>
            <span class="badge bg-primary rounded-pill">{{ filteredList.length }} รายการ</span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle border-0">
            <thead class="table-light">
              <tr>
                <th
                  width="50"
                  class="text-center text-uppercase text-secondary small fw-bold opacity-75"
                >
                  ลำดับ
                </th>
                <th
                  width="160"
                  class="text-center text-uppercase text-secondary small fw-bold opacity-75"
                >
                  Action
                </th>
                <th class="text-uppercase text-secondary small fw-bold opacity-75">เหตุผล</th>
                <th class="text-uppercase text-secondary small fw-bold opacity-75">ชื่อรายงาน</th>
                <th class="text-uppercase text-secondary small fw-bold opacity-75">ผู้ขอข้อมูล</th>
                <th class="text-uppercase text-secondary small fw-bold opacity-75">
                  วันที่ขอข้อมูล
                </th>
                <th class="text-uppercase text-secondary small fw-bold opacity-75">
                  วันที่ต้องการ
                </th>
                <th
                  width="140"
                  class="text-center text-uppercase text-secondary small fw-bold opacity-75"
                >
                  สถานะ
                </th>
                <th
                  width="100"
                  class="text-center text-uppercase text-secondary small fw-bold opacity-75"
                  v-if="isSuper"
                >
                  Admin
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedList" :key="item.data_id">
                <td class="text-center">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                <td class="text-center">
                  <div class="d-flex gap-2 justify-content-center">
                    <button
                      class="btn btn-warning btn-sm shadow-sm px-3 rounded-pill fw-bold text-white"
                      @click="openEditModal(item)"
                      title="แก้ไข"
                    >
                      <i class="bi bi-pencil-square me-1"></i>แก้ไข
                    </button>
                    <button
                      class="btn btn-primary btn-sm shadow-sm px-3 rounded-pill fw-bold"
                      @click="openViewModal(item)"
                      title="ดูรายละเอียด"
                    >
                      <i class="bi bi-eye me-1"></i>รายละเอียด
                    </button>
                  </div>
                </td>
                <td>
                  {{ item.reason_name || '-' }}
                  <span v-if="item.reason_other" class="text-muted small"
                    >({{ item.reason_other }})</span
                  >
                </td>
                <td>
                  <div class="fw-bold">{{ item.data_name }}</div>
                  <small
                    v-if="item.data_column"
                    class="text-muted text-truncate d-inline-block"
                    style="max-width: 200px"
                  >
                    {{ item.data_column }}
                  </small>
                </td>
                <td>{{ item.p_name || 'ไม่ทราบชื่อ' }}</td>
                <td>{{ formatDate(item.crt_date) }}</td>
                <td>{{ formatDate(item.want_date) || '-' }}</td>
                <td>
                  <span :class="getStatusBadge(item.data_status_id)">
                    {{ getStatusName(item.data_status_id) }}
                  </span>
                </td>
                <td class="text-center" v-if="isSuper">
                  <button
                    class="btn btn-info btn-sm shadow-sm px-3 rounded-pill fw-bold text-white"
                    @click="openStatusModal(item)"
                    title="อัปเดตสถานะ"
                  >
                    <i class="bi bi-gear-fill me-1"></i>Update
                  </button>
                </td>
              </tr>
              <tr v-if="dataList.length === 0 && !loading">
                <td colspan="9" class="text-center py-4 text-muted">ไม่พบข้อมูลรายงาน</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="d-flex justify-content-between align-items-center mt-3 p-3"
          v-if="totalPages > 1"
        >
          <span class="text-muted small">
            แสดงหน้า {{ currentPage }} จาก {{ totalPages }} (ทั้งหมด
            {{ filteredList.length }} รายการ)
          </span>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button
                  class="page-link"
                  @click="changePage(currentPage - 1)"
                  aria-label="Previous"
                >
                  <span aria-hidden="true">&laquo;</span>
                </button>
              </li>
              <li
                class="page-item"
                v-for="page in displayedPages"
                :key="page"
                :class="{ active: currentPage === page, disabled: page === '...' }"
              >
                <button class="page-link" @click="changePage(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="changePage(currentPage + 1)" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal Form (Add/Edit) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-light border-bottom py-3">
            <h5 class="modal-title text-warning fw-bold">
              <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i
              >{{ isEdit ? 'แก้ไขข้อมูลรายงาน' : 'บันทึกขอข้อมูล รายงาน' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>

          <div class="modal-body p-4">
            <form @submit.prevent="saveData">
              <!-- Show Requester Info (Read Only) -->
              <div v-if="isEdit" class="alert alert-light border mb-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <small class="text-muted d-block">ผู้ขอข้อมูล:</small>
                    <span class="fw-bold text-primary">{{ form.p_name || '-' }}</span>
                  </div>
                  <div>
                    <small class="text-muted d-block">วันที่ขอ:</small>
                    <span class="fw-bold">{{ formatDate(form.crt_date) }}</span>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label text-success fw-bold">ขอข้อมูล/รายงาน</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="form.data_name"
                  placeholder="ระบุชื่อรายงาน"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label text-success fw-bold"
                  >ข้อมูลที่ต้องการ เช่น จำนวน, HN, ชื่อ-สกุล</label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="form.data_column"
                  placeholder="ระบุคอลัมน์ข้อมูลที่ต้องการ"
                />
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold"
                    >เหตุผล/วัตถุประสงค์ ที่ขอข้อมูล</label
                  >
                  <select class="form-select" v-model="form.reason_id">
                    <option value="1">การวิจัย</option>
                    <option value="2">ทำรายงาน</option>
                    <option value="3">ตัวชี้วัด</option>
                    <option value="4">อื่นๆ</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold">อื่นๆ ระบุ</label>
                  <input type="text" class="form-control" v-model="form.reason_other" />
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold">ประเภทการขอข้อมูล</label>
                  <select class="form-select" v-model="form.data_type_id">
                    <option value="1">ครั้งเดียว</option>
                    <option value="2">รายสัปดาห์</option>
                    <option value="3">รายเดือน</option>
                    <option value="4">รายปี</option>
                    <option value="5">ปีงบประมาณ</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold">ช่วงเวลาที่ต้องการข้อมูล</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="form.data_date"
                    placeholder="เช่น 01/01/2567 - 31/12/2567"
                  />
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold">ต้องการรับข้อมูลภายในวันที่</label>
                  <input type="date" class="form-control" v-model="form.want_date_only" />
                </div>
                <div class="col-md-6">
                  <label class="form-label text-success fw-bold">เวลา :</label>
                  <input type="time" class="form-control" v-model="form.want_time_only" />
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button
                  v-if="isEdit"
                  type="button"
                  class="btn btn-danger px-4 rounded-pill me-auto"
                  @click="deleteItem(form.data_id)"
                >
                  <i class="bi bi-trash me-1"></i>ลบข้อมูล
                </button>
                <button type="button" class="btn btn-light px-4 rounded-pill" @click="closeModal">
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  class="btn btn-primary px-4 rounded-pill fw-bold"
                  :disabled="isSaving"
                >
                  <i class="bi bi-save me-1"></i>
                  {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div v-if="showViewModal" class="modal-backdrop fade show"></div>
    <div class="modal fade" :class="{ 'show d-block': showViewModal }" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-primary text-white py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดข้อมูล
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              @click="closeViewModal"
            ></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="text-muted small">ชื่อรายงาน/ข้อมูล</label>
                <div class="fw-bold fs-5 text-dark">{{ viewItem.data_name }}</div>
              </div>
              <div class="col-md-12">
                <label class="text-muted small">คอลัมน์ที่ต้องการ</label>
                <div class="p-2 bg-light rounded border">{{ viewItem.data_column || '-' }}</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">เหตุผล/วัตถุประสงค์/ดึงข้อมูลจาก</label>
                <div class="fw-bold">
                  {{ viewItem.reason_name }}
                  {{ viewItem.reason_other ? `(${viewItem.reason_other})` : '' }}
                </div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">ประเภทข้อมูล</label>
                <div class="fw-bold">
                  {{ viewItem.file_type || 'Excel' }} ({{
                    ['', 'ครั้งเดียว', 'รายสัปดาห์', 'รายเดือน', 'รายปี', 'ปีงบประมาณ'][
                      viewItem.data_type_id
                    ] || viewItem.data_type_id
                  }})
                  <p class="text-muted small">{{ viewItem.data_receive }}</p>
                </div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">ช่วงเวลาข้อมูล</label>
                <div class="fw-bold">{{ viewItem.data_date || '-' }}</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">วันที่ต้องการรับข้อมูล</label>
                <div class="fw-bold text-danger">{{ formatDate(viewItem.want_date) || '-' }}</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">ผู้ขอข้อมูล</label>
                <div class="fw-bold">{{ viewItem.p_name }}</div>
              </div>
              <div class="col-md-6">
                <label class="text-muted small">วันที่บันทึกรายการ</label>
                <div class="fw-bold">{{ formatDate(viewItem.crt_date) }}</div>
              </div>
              <div class="col-md-12">
                <label class="text-muted small">การรับข้อมูล</label>
                <div class="fw-bold">{{ viewItem.data_receive || '-' }}</div>
              </div>
              <div class="col-md-12">
                <label class="text-muted small">หมายเหตุ</label>
                <div class="p-2 bg-light rounded border">{{ viewItem.remark || '-' }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button
              type="button"
              class="btn btn-secondary rounded-pill px-4"
              @click="closeViewModal"
            >
              ปิดหน้าต่าง
            </button>
          </div>
        </div>
      </div>
    </div>
    <!-- Status Update Modal -->
    <div v-if="showStatusModal" class="modal-backdrop fade show"></div>
    <div class="modal fade" :class="{ 'show d-block': showStatusModal }" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-white border-bottom py-3">
            <h5 class="modal-title fw-bold text-warning">
              <i class="bi bi-file-earmark-code-fill me-2"></i>Admin แก้ไขขอข้อมูล รายงาน
            </h5>
            <button type="button" class="btn-close" @click="closeStatusModal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-2">
              <label class="form-label fw-bold text-dark">{{ statusForm.data_name }}</label>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-success fw-bold">สถานะ</label>
                <select class="form-select" v-model="statusForm.data_status_id">
                  <option value="1">รอดำเนินการ</option>
                  <option value="2">กำลังดำเนินการ</option>
                  <option value="3">ดำเนินการเรียบร้อย</option>
                  <option value="4">ยกเลิก</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label text-success fw-bold">วันที่เสร็จ</label>
                <input type="date" class="form-control" v-model="statusForm.success_date_only" />
              </div>
              <div class="col-md-3">
                <label class="form-label text-success fw-bold">เวลา :</label>
                <div class="input-group">
                  <input type="time" class="form-control" v-model="statusForm.success_time_only" />
                  <span class="input-group-text bg-white"><i class="bi bi-clock"></i></span>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-success fw-bold">SQL</label>
              <textarea
                class="form-control"
                v-model="statusForm.sql"
                rows="10"
                placeholder="ระบุ SQL ที่ใช้ดึงข้อมูล..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <div class="d-flex justify-content-end gap-2 w-100">
              <button
                type="button"
                class="btn btn-warning text-white rounded-pill px-4 fw-bold"
                @click="resetStatusForm"
              >
                เคลียร์
              </button>
              <button
                type="button"
                class="btn btn-primary rounded-pill px-4 fw-bold"
                @click="saveStatus"
              >
                <i class="bi bi-save-fill me-1"></i>บันทึก
              </button>
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
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';

const router = useRouter(); // เรียกใช้งาน router

// State
const dataList = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const showModal = ref(false);
const showViewModal = ref(false);
const showStatusModal = ref(false);
const viewItem = ref({});
const isEdit = ref(false);
const isSaving = ref(false);
const isSuper = ref(false);

const statusForm = ref({
  data_id: null,
  data_name: '',
  data_status_id: 1,
  success_date_only: '',
  success_time_only: '',
  sql: ''
});

// Pagination State
const currentPage = ref(1);
const itemsPerPage = ref(20);

// Computed Properties
const filteredList = computed(() => {
  if (!searchQuery.value) return dataList.value;
  const query = searchQuery.value.toLowerCase();
  return dataList.value.filter(
    (item) =>
      (item.data_name && item.data_name.toLowerCase().includes(query)) ||
      (item.p_name && item.p_name.toLowerCase().includes(query)) ||
      (item.reason_name && item.reason_name.toLowerCase().includes(query))
  );
});

const totalPages = computed(() => {
  return Math.ceil(filteredList.value.length / itemsPerPage.value);
});

const paginatedList = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredList.value.slice(start, end);
});

const displayedPages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const delta = 2;
  const range = [];
  const rangeWithDots = [];
  let l;

  range.push(1);
  for (let i = current - delta; i <= current + delta; i++) {
    if (i < total && i > 1) {
      range.push(i);
    }
  }
  range.push(total);

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// Form State
const form = ref({
  data_id: null,
  data_name: '',
  data_column: '',
  reason_id: '2',
  reason_other: '',
  data_type_id: '1',
  data_date: '',
  file_type: 'Excel',
  data_receive: 'ส่งผ่าน Email/Line',
  want_date_only: '',
  want_time_only: '08:30',
  remark: ''
});

// Fetch Data
const fetchData = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get('/api-hosoffice/get_data_report.php');
    if (response.data.status === 'success') {
      dataList.value = response.data.data;
      isSuper.value = response.data.is_super;
    }
  } catch (err) {
    error.value = 'โหลดข้อมูลไม่สำเร็จ: ' + err.message;
  } finally {
    loading.value = false;
  }
};

// Modal Actions
const openAddModal = () => {
  isEdit.value = false;
  resetForm();
  showModal.value = true;
};

const openEditModal = (item) => {
  isEdit.value = true;
  form.value = { ...item };
  if (item.want_date && item.want_date !== '0000-00-00 00:00:00') {
    const parts = item.want_date.split(' ');
    form.value.want_date_only = parts[0];
    form.value.want_time_only = parts[1] ? parts[1].substring(0, 5) : '08:30';
  }
  showModal.value = true;
};

const openViewModal = (item) => {
  viewItem.value = { ...item };
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
};

const openStatusModal = (item) => {
  statusForm.value.data_id = item.data_id;
  statusForm.value.data_name = item.data_name;
  statusForm.value.data_status_id = item.data_status_id;
  statusForm.value.sql = item.sql || ''; // Bind SQL from item

  // Set default date/time to now if not set, or current success date
  if (item.success_date && item.success_date !== '0000-00-00 00:00:00') {
    const parts = item.success_date.split(' ');
    statusForm.value.success_date_only = parts[0];
    statusForm.value.success_time_only = parts[1] ? parts[1].substring(0, 5) : '00:00';
  } else {
    const now = new Date();
    const offsetMap = {
      'Asia/Bangkok': 7 * 60
    };
    // Simplified implementation for local time
    const localIso = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString();
    statusForm.value.success_date_only = localIso.split('T')[0];

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    statusForm.value.success_time_only = `${hours}:${minutes}`;
  }

  showStatusModal.value = true;
};

const closeStatusModal = () => {
  showStatusModal.value = false;
};

const resetStatusForm = () => {
  statusForm.value.sql = '';
};

const saveStatus = async () => {
  try {
    let successDate = null;
    if (statusForm.value.data_status_id == 3) {
      // If Completed
      if (statusForm.value.success_date_only && statusForm.value.success_time_only) {
        successDate = `${statusForm.value.success_date_only} ${statusForm.value.success_time_only}:00`;
      } else {
        alert('กรุณาระบุวันที่และเวลาที่เสร็จสิ้น');
        return;
      }
    }

    const response = await axios.post('/api-hosoffice/update_status_report.php', {
      data_id: statusForm.value.data_id,
      data_status_id: statusForm.value.data_status_id,
      success_date: successDate,
      sql: statusForm.value.sql
    });

    if (response.data.status === 'success') {
      await Swal.fire('สำเร็จ', 'อัปเดตสถานะเรียบร้อยแล้ว', 'success');
      closeStatusModal();
      fetchData();
    } else {
      await Swal.fire('ล้มเหลว', response.data.message || 'เกิดข้อผิดพลาด', 'error');
    }
  } catch (err) {
    console.error(err);
    await Swal.fire('Error', 'Failed to update status', 'error');
  }
};

const closeModal = () => {
  showModal.value = false;
};

const resetForm = () => {
  form.value = {
    data_id: null,
    data_name: '',
    data_column: '',
    reason_id: '2', // Default to ทำรายงาน (2) based on likely usage, or keep 1 (Research)
    reason_other: '',
    data_type_id: '1', // Default to ครั้งเดียว (1)
    data_date: '',
    file_type: 'Excel',
    data_receive: 'ส่งผ่าน Email/Line',
    want_date_only: '',
    want_time_only: '08:30',
    remark: ''
  };
};

// Save Action
const saveData = async () => {
  isSaving.value = true;
  try {
    const url = isEdit.value ? '/api-hosoffice/update_report.php' : '/api-hosoffice/add_report.php';

    const payload = {
      ...form.value,
      want_date: form.value.want_date_only
        ? `${form.value.want_date_only} ${form.value.want_time_only}:00`
        : null
    };

    const response = await axios.post(url, payload);
    if (response.data.status === 'success') {
      alert('บันทึกสำเร็จ!');
      closeModal();
      fetchData();
    } else {
      alert('เกิดข้อผิดพลาดจาก Server: ' + response.data.message);
    }
  } catch (err) {
    alert('เกิดข้อผิดพลาด: ' + err.message);
  } finally {
    isSaving.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr || dateStr.startsWith('0000')) return '-';
  return new Date(dateStr).toLocaleDateString('th-TH', {
    day: '2-digit',
    month: 'short',
    year: '2-digit'
  });
};

const getStatusBadge = (id) => {
  const mapping = {
    1: 'badge bg-danger-subtle text-danger border border-danger',
    2: 'badge bg-warning-subtle text-warning-emphasis border border-warning',
    3: 'badge bg-success-subtle text-success border border-success'
  };
  return mapping[id] || 'badge bg-secondary';
};

const getStatusName = (id) => {
  const mapping = {
    1: 'รอดำเนินการ',
    2: 'กำลังดำเนินการ',
    3: 'ดำเนินการเรียบร้อย'
  };
  return mapping[id] || 'ไม่ระบุ';
};

// แก้ไขฟังก์ชัน Logout ให้ถูกต้อง
const logout = () => {
  if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
    localStorage.removeItem('user_token');
    router.push('/login');
  }
};

const deleteItem = async (id) => {
  if (!confirm('ยืนยันการลบข้อมูล?')) return;
  try {
    const response = await axios.post('/api-hosoffice/delete_report.php', {
      data_id: id
    });
    if (response.data.status === 'success') {
      alert('ลบข้อมูลสำเร็จ');
      closeModal(); // Close modal if open
      fetchData();
    } else {
      alert('เกิดข้อผิดพลาด: ' + response.data.message);
    }
  } catch (err) {
    alert('Error: ' + err.message);
  }
};

const goHomeBackoffice = () => {
  router.push('/home-backoffice');
};

onMounted(fetchData);
</script>

<style scoped>
.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.4);
}
.form-control,
.form-select {
  border-radius: 8px;
  border: 1px solid #dee2e6;
  padding: 0.6rem;
}
.form-control:focus {
  border-color: #198754;
  box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.1);
}
.form-label {
  font-size: 0.85rem;
  margin-bottom: 0.3rem;
}
.spin {
  animation: spin 1s linear infinite;
  display: inline-block;
}
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
