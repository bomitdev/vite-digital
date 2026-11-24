<template>
  <div class="data-report-page container-fluid py-3 py-md-4 px-3 px-md-4 min-vh-100">
    <div class="card main-card border-0 rounded-4 overflow-hidden">
      <div class="card-top-accent"></div>
      <!-- Header -->
      <div class="card-header bg-white border-bottom px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="header-icon-box">
            <i class="bi bi-database-fill-gear"></i>
          </div>
          <div>
            <h4 class="mb-0 text-dark fw-bold">ระบบรายงานข้อมูล (Data Report)</h4>
            <small class="text-muted">ติดตามคำขอและจัดการสถานะการจัดทำรายงานข้อมูล</small>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
          <button
            @click="fetchData"
            class="btn btn-light border btn-sm rounded-pill px-3 text-secondary fw-medium"
            :disabled="loading"
          >
            <i class="bi bi-arrow-clockwise me-1" :class="{ spin: loading }"></i>
            รีเฟรช
          </button>
          <button
            @click="$router.push('/report-center')"
            class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-medium"
          >
            <i class="bi bi-file-earmark-bar-graph-fill me-1"></i> Report Center
          </button>
          <button
            @click="openAddModal"
            class="btn btn-success btn-sm rounded-pill px-3 fw-semibold shadow-sm"
          >
            <i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลรายงาน
          </button>
          <button
            @click="goHomeBackoffice"
            class="btn btn-light border btn-sm rounded-pill px-3 text-secondary fw-medium"
          >
            <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
          </button>
        </div>
      </div>

      <div class="card-body p-0">
        <div v-if="error" class="alert alert-danger d-flex align-items-center m-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="px-4 py-3 border-bottom bg-light-subtle d-flex flex-wrap justify-content-between align-items-center gap-3">
          <!-- Status Filter Pills -->
          <div class="d-flex flex-wrap align-items-center gap-2">
            <button
              type="button"
              class="status-filter-pill"
              :class="{ active: statusFilter === 'all' }"
              @click="statusFilter = 'all'"
            >
              ทั้งหมด
              <span class="filter-count">{{ dataList.length }}</span>
            </button>
            <button
              type="button"
              class="status-filter-pill status-pending"
              :class="{ active: statusFilter === 1 }"
              @click="statusFilter = 1"
            >
              รอดำเนินการ
              <span class="filter-count">{{ statusCounts[1] }}</span>
            </button>
            <button
              type="button"
              class="status-filter-pill status-progress"
              :class="{ active: statusFilter === 2 }"
              @click="statusFilter = 2"
            >
              กำลังดำเนินการ
              <span class="filter-count">{{ statusCounts[2] }}</span>
            </button>
            <button
              type="button"
              class="status-filter-pill status-done"
              :class="{ active: statusFilter === 3 }"
              @click="statusFilter = 3"
            >
              ดำเนินการเรียบร้อย
              <span class="filter-count">{{ statusCounts[3] }}</span>
            </button>
          </div>

          <!-- Search Box -->
          <div class="input-group input-group-sm search-box" style="max-width: 320px">
            <span class="input-group-text bg-white border-end-0 text-muted ps-3">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="text"
              class="form-control border-start-0 ps-1"
              :class="{ 'border-end-0': searchQuery }"
              placeholder="ค้นหาชื่อรายงาน, ผู้ขอ, เหตุผล..."
              v-model="searchQuery"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="btn bg-white border border-start-0 text-muted pe-3"
              @click="searchQuery = ''"
              title="ล้างคำค้นหา"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 modern-table">
            <thead>
              <tr>
                <th width="60" class="text-center">ลำดับ</th>
                <th>ชื่อรายงาน</th>
                <th>เหตุผล</th>
                <th class="text-nowrap">ผู้ขอข้อมูล</th>
                <th class="text-nowrap">วันที่ขอข้อมูล</th>
                <th class="text-nowrap">วันที่ต้องการ</th>
                <th width="150" class="text-center">สถานะ</th>
                <th class="text-end pe-4 text-nowrap">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedList" :key="item.data_id">
                <td class="text-center text-muted fw-medium">
                  {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ item.data_name }}</div>
                  <small
                    v-if="item.data_column"
                    class="text-muted text-truncate d-inline-block"
                    style="max-width: 320px"
                    :title="item.data_column"
                  >
                    {{ item.data_column }}
                  </small>
                </td>
                <td style="max-width: 240px">
                  <span class="reason-pill">
                    {{ item.reason_name || '-' }}
                  </span>
                  <small v-if="item.reason_other" class="text-muted d-block text-truncate mt-1" :title="item.reason_other">
                    ({{ item.reason_other }})
                  </small>
                </td>
                <td class="text-nowrap">
                  <i class="bi bi-person text-muted me-1"></i>{{ item.p_name || 'ไม่ทราบชื่อ' }}
                </td>
                <td class="text-nowrap text-muted">
                  <i class="bi bi-calendar3 me-1 opacity-75"></i>{{ formatDate(item.crt_date) }}
                </td>
                <td class="text-nowrap text-muted">
                  <i class="bi bi-calendar-check me-1 opacity-75"></i>{{ formatDate(item.want_date) || '-' }}
                </td>
                <td class="text-center">
                  <span :class="getStatusBadge(item.data_status_id)" class="status-pill-badge">
                    <i :class="['bi me-1', getStatusIcon(item.data_status_id)]"></i>
                    {{ getStatusName(item.data_status_id) }}
                  </span>
                </td>
                <td class="text-end pe-4 text-nowrap">
                  <div class="d-inline-flex align-items-center gap-1 flex-nowrap">
                    <button
                      v-if="item.data_status_id == 3 && item.linked_report_id"
                      class="btn btn-soft-success btn-sm rounded-pill px-2 fw-semibold text-nowrap"
                      @click="$router.push({ path: '/report-center', query: { report_id: item.linked_report_id } })"
                      title="เปิดดูรายงาน"
                    >
                      <i class="bi bi-file-earmark-bar-graph me-1"></i>ดูรายงาน
                    </button>
                    <button
                      class="btn btn-soft-primary btn-action-icon"
                      @click="openViewModal(item)"
                      title="ดูรายละเอียด"
                    >
                      <i class="bi bi-eye"></i>
                    </button>
                    <button
                      class="btn btn-soft-warning btn-action-icon"
                      @click="openEditModal(item)"
                      title="แก้ไข"
                    >
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button
                      v-if="isSuper"
                      class="btn btn-soft-info btn-action-icon"
                      @click="openStatusModal(item)"
                      title="อัปเดตสถานะ (Admin)"
                    >
                      <i class="bi bi-gear-fill"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="paginatedList.length === 0 && !loading">
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                  ไม่พบข้อมูลรายงานที่ตรงกับเงื่อนไข
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top bg-light-subtle px-4 py-3"
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
              <label class="form-label text-success fw-bold">SQL (ถ้ามี)</label>
              <textarea
                class="form-control"
                v-model="statusForm.sql"
                rows="6"
                placeholder="ระบุ SQL ที่ใช้ดึงข้อมูล..."
              ></textarea>
            </div>
            
            <div class="mb-3">
              <label class="form-label text-success fw-bold"><i class="bi bi-link-45deg"></i> เชื่อมโยงกับ Report Center (เพื่อให้ User กดดูรายงานได้)</label>
              <select class="form-select" v-model="statusForm.linked_report_id">
                <option :value="null">-- ไม่เชื่อมโยง (ปล่อยว่าง) --</option>
                <option v-for="rep in availableReports" :key="rep.id" :value="rep.id">
                  [{{ rep.id }}] {{ rep.title }}
                </option>
              </select>
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
const statusFilter = ref('all');
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
  sql: '',
  linked_report_id: null
});

const availableReports = ref([]);

// Pagination State
const currentPage = ref(1);
const itemsPerPage = ref(20);

// Computed Properties
const statusCounts = computed(() => {
  const counts = { 1: 0, 2: 0, 3: 0 };
  for (const item of dataList.value) {
    const id = Number(item.data_status_id);
    if (counts[id] !== undefined) counts[id]++;
  }
  return counts;
});

const filteredList = computed(() => {
  let list = dataList.value;
  if (statusFilter.value !== 'all') {
    list = list.filter((item) => Number(item.data_status_id) === Number(statusFilter.value));
  }
  if (!searchQuery.value) return list;
  const query = searchQuery.value.toLowerCase();
  return list.filter(
    (item) =>
      (item.data_name && item.data_name.toLowerCase().includes(query)) ||
      (item.p_name && item.p_name.toLowerCase().includes(query)) ||
      (item.reason_name && item.reason_name.toLowerCase().includes(query)) ||
      (item.reason_other && item.reason_other.toLowerCase().includes(query))
  );
});

watch([searchQuery, statusFilter], () => {
  currentPage.value = 1;
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
  statusForm.value.linked_report_id = item.linked_report_id || null;

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
  statusForm.value.linked_report_id = null;
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
      sql: statusForm.value.sql,
      linked_report_id: statusForm.value.linked_report_id
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
    1: 'bg-danger-subtle text-danger border border-danger-subtle',
    2: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    3: 'bg-success-subtle text-success border border-success-subtle'
  };
  return mapping[id] || 'bg-secondary-subtle text-secondary border border-secondary-subtle';
};

const getStatusIcon = (id) => {
  const mapping = {
    1: 'bi-clock-fill',
    2: 'bi-arrow-repeat',
    3: 'bi-check-circle-fill'
  };
  return mapping[id] || 'bi-question-circle';
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

const fetchReports = async () => {
  try {
    // API from Report Center module
    const res = await axios.get(`${import.meta.env.VITE_API_URL || ''}/api-digital/report-center/get_reports.php`);
    availableReports.value = res.data;
  } catch (e) {
    console.error('Failed to fetch available reports', e);
  }
};

onMounted(() => {
  fetchData();
  fetchReports();
});
</script>

<style scoped>
.data-report-page {
  background: radial-gradient(circle at top left, #eff6ff 0%, #f8fafc 45%, #f1f5f9 100%);
}

.main-card {
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  border: 1px solid #e2e8f0 !important;
}

.card-top-accent {
  height: 4px;
  background: linear-gradient(90deg, #2563eb 0%, #4f46e5 50%, #10b981 100%);
}

.header-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.reason-pill {
  display: inline-block;
  background-color: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 0.18rem 0.65rem;
  font-size: 0.78rem;
  font-weight: 600;
}

.status-pill-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.7rem;
  font-size: 0.76rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-filter-pill {
  border: 1px solid #e2e8f0;
  background-color: #ffffff;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 600;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
  cursor: pointer;
}

.status-filter-pill:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.status-filter-pill .filter-count {
  background-color: #f1f5f9;
  color: #475569;
  font-size: 0.74rem;
  padding: 0.05rem 0.45rem;
  border-radius: 999px;
}

.status-filter-pill.active {
  background-color: #1e293b;
  border-color: #1e293b;
  color: #ffffff;
}

.status-filter-pill.active .filter-count {
  background-color: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.status-filter-pill.status-pending.active {
  background-color: #dc2626;
  border-color: #dc2626;
}

.status-filter-pill.status-progress.active {
  background-color: #d97706;
  border-color: #d97706;
}

.status-filter-pill.status-done.active {
  background-color: #16a34a;
  border-color: #16a34a;
}

.search-box .input-group-text,
.search-box .form-control,
.search-box .btn {
  border-color: #e2e8f0;
  padding-top: 0.42rem;
  padding-bottom: 0.42rem;
}

.modern-table thead th {
  background-color: #f8fafc;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.8rem 0.75rem;
}

.modern-table tbody td {
  border-bottom: 1px solid #f1f5f9;
  padding: 0.78rem 0.75rem;
  font-size: 0.88rem;
}

.modern-table tbody tr:hover {
  background-color: #f8fafc;
}

.btn-action-icon {
  width: 30px;
  height: 30px;
  padding: 0;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}

.btn-soft-primary {
  background-color: #eff6ff;
  color: #2563eb;
}
.btn-soft-primary:hover {
  background-color: #2563eb;
  color: #ffffff;
}

.btn-soft-warning {
  background-color: #fefce8;
  color: #ca8a04;
}
.btn-soft-warning:hover {
  background-color: #eab308;
  color: #ffffff;
}

.btn-soft-info {
  background-color: #ecfeff;
  color: #0891b2;
}
.btn-soft-info:hover {
  background-color: #0891b2;
  color: #ffffff;
}

.btn-soft-success {
  background-color: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  font-size: 0.78rem;
}
.btn-soft-success:hover {
  background-color: #16a34a;
  color: #ffffff;
}

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
