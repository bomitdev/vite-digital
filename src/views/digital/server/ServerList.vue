<template>
  <div class="row g-4 mt-1">
    <!-- Header Section -->
    <div
      class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-2"
    >
      <div class="mb-3 mb-md-0 fade-in-up">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-2 custom-breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/home-backoffice" class="text-decoration-none">
                <i class="bi bi-house-door-fill me-1"></i>หน้าหลัก
              </router-link>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page">ทะเบียน Server</li>
          </ol>
        </nav>
        <h3 class="fw-black text-dark mb-1 d-flex align-items-center gap-3">
          <div class="icon-square bg-gradient-primary text-white shadow-sm">
            <i class="bi bi-server"></i>
          </div>
          ศูนย์ข้อมูลทะเบียนระบบในโรงพยาบาล
        </h3>
        <p class="text-muted mb-0 ms-5 ps-3 fs-6">
          ระบบบริหารจัดการและจัดเก็บข้อมูลสเปคเครื่อง Server, VM และโฮสต์ภายในระบบ
        </p>
      </div>
      <div class="d-flex gap-2 fade-in-up delay-100">
        <button
          class="btn btn-primary rounded-pill px-4 shadow-sm hover-lift fw-bold d-flex align-items-center gap-2"
          @click="openAddModal"
        >
          <i class="bi bi-plus-circle-fill"></i>ลงทะเบียน Server
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-12 mt-0">
      <div class="row g-3">
        <div class="col-md-4 col-sm-12 fade-in-up delay-100">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-primary border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-server fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">เซิร์ฟเวอร์ทั้งหมด</p>
                <h4 class="mb-0 fw-black text-dark">{{ totalServers }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-sm-6 fade-in-up delay-200">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-success border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-power fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">กำลังทำงาน (Active)</p>
                <h4 class="mb-0 fw-black text-dark">{{ activeServers }}</h4>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-sm-6 fade-in-up delay-300">
          <div
            class="card bg-white border-0 shadow-sm rounded-4 h-100 stat-card border-bottom border-warning border-4"
          >
            <div class="card-body p-3 d-flex align-items-center">
              <div
                class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-flex align-items-center justify-content-center"
                style="width: 50px; height: 50px"
              >
                <i class="bi bi-exclamation-triangle fs-4"></i>
              </div>
              <div class="ms-3">
                <p class="text-muted mb-0 small fw-bold text-uppercase">ปิดปรับปรุง / ไม่ใช้งาน</p>
                <h4 class="mb-0 fw-black text-dark">{{ nonActiveServers }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-12 mt-4 fade-in-up delay-400">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden glass-card">
        <div class="card-body p-4">
          <!-- Filters & Search -->
          <div class="row mb-4 g-3">
            <div class="col-md-12 col-lg-5">
              <div class="input-group input-group-custom shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-primary px-3">
                  <i class="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  class="form-control border-start-0 ps-0"
                  v-model="searchQuery"
                  @input="debounceFetch"
                  placeholder="ค้นหาชื่อ, IP, OS, หน้าที่แบบ Real-time..."
                />
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <select
                class="form-select form-select-custom shadow-sm"
                v-model="typeFilter"
                @change="fetchServers"
              >
                <option value="all">ทุกประเภทเซิร์ฟเวอร์</option>
                <option value="Physical">Physical Server (เซิร์ฟเวอร์จริง)</option>
                <option value="Virtual">Virtual Server (เครื่องเสมือน/VM)</option>
              </select>
            </div>
            <div class="col-md-6 col-lg-3">
              <select
                class="form-select form-select-custom shadow-sm"
                v-model="statusFilter"
                @change="fetchServers"
              >
                <option value="all">ทุกสถานะการทำงาน</option>
                <option value="active">🟢 ใช้งานอยู่ (Active)</option>
                <option value="maintenance">🟡 ปิดปรับปรุง (Maintenance)</option>
                <option value="inactive">⚪ ปิดใช้งาน (Inactive)</option>
              </select>
            </div>
          </div>

          <!-- Table -->
          <div class="table-responsive rounded-3 border">
            <table class="table table-hover table-custom mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th scope="col" class="text-secondary fw-bold py-3 px-3">ชื่อเซิร์ฟเวอร์ / IP</th>
                  <th scope="col" class="text-secondary fw-bold py-3">รายละเอียด (OS & Role)</th>
                  <th scope="col" class="text-secondary fw-bold py-3">สเปค (CPU/RAM/Storage)</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center">สถานที่</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center">สถานะ</th>
                  <th scope="col" class="text-secondary fw-bold py-3 text-center" width="120">
                    จัดการ
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading">
                  <td colspan="6" class="text-center py-5 text-muted">
                    <div
                      class="spinner-border spinner-border-sm text-primary me-2"
                      role="status"
                    ></div>
                    กำลังโหลดข้อมูล...
                  </td>
                </tr>
                <tr v-else-if="servers.length === 0">
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> ไม่พบข้อมูล Server
                  </td>
                </tr>
                <tr v-for="srv in paginatedServers" :key="srv.id" class="item-row">
                  <td class="px-3">
                    <div class="fw-bold text-dark fs-6">{{ srv.server_name }}</div>
                    <div class="small fw-bold text-secondary mt-1">
                      <i
                        :class="
                          srv.server_type === 'Virtual' ? 'bi-cloud-fill' : 'bi-hdd-rack-fill'
                        "
                        class="me-1"
                      ></i>
                      {{
                        srv.server_type === 'Virtual' ? 'Virtual Server (VM)' : 'Physical Server'
                      }}
                    </div>
                    <span class="badge bg-light text-primary border font-monospace mt-1"
                      ><i class="bi bi-diagram-2 me-1"></i>{{ srv.ip_address || 'No IP' }}</span
                    >
                  </td>
                  <td>
                    <div class="fw-bold text-dark">
                      <i class="bi bi-window-sidebar me-1 text-secondary"></i>
                      {{ srv.os || '-' }}
                      <span class="badge bg-light text-secondary border ms-1" v-if="srv.version"
                        >v{{ srv.version }}</span
                      >
                    </div>
                    <div class="small text-muted mt-1">
                      <i class="bi bi-gear-fill me-1 text-secondary"></i>{{ srv.role || '-' }}
                      <template v-if="srv.user_name"
                        >| <i class="bi bi-person ms-1"></i> {{ srv.user_name }}</template
                      >
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-1">
                      <span class="badge bg-light text-dark border"
                        ><i class="bi bi-cpu me-1"></i>{{ srv.cpu || '-' }}</span
                      >
                      <span class="badge bg-light text-dark border"
                        ><i class="bi bi-memory me-1"></i>{{ srv.ram || '-' }}</span
                      >
                      <span class="badge bg-light text-dark border"
                        ><i class="bi bi-device-hdd me-1"></i>{{ srv.storage || '-' }}</span
                      >
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="small text-secondary fw-medium">
                      <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ srv.location || '-' }}
                    </div>
                  </td>
                  <td class="text-center">
                    <span
                      class="badge rounded-pill p-2 px-3 fw-bold border"
                      :class="getStatusClass(srv.status)"
                    >
                      {{ getStatusLabel(srv.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <button
                        class="btn btn-sm btn-light text-primary hover-lift rounded-circle"
                        style="width: 35px; height: 35px"
                        @click="editServer(srv)"
                        title="แก้ไข"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-light text-danger hover-lift rounded-circle"
                        style="width: 35px; height: 35px"
                        @click="deleteServer(srv)"
                        title="ลบ"
                      >
                        <i class="bi bi-trash3-fill"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex justify-content-between align-items-center mt-3" v-if="totalPages > 1">
            <div class="text-muted small">
              แสดงผล <strong>{{ paginatedServers.length }}</strong> รายการ จากทั้งหมด
              <strong>{{ servers.length }}</strong> รายการ (หน้า {{ currentPage }} /
              {{ totalPages }})
            </div>
            <nav aria-label="Page navigation">
              <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button class="page-link" @click="prevPage">
                    <i class="bi bi-chevron-left"></i> ก่อนหน้า
                  </button>
                </li>
                <li
                  class="page-item"
                  v-for="page in totalPages"
                  :key="page"
                  :class="{ active: currentPage === page }"
                >
                  <button class="page-link" @click="goToPage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button class="page-link" @click="nextPage">
                    ถัดไป <i class="bi bi-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
          <div class="mt-3 text-muted small text-end" v-else>
            แสดงทั้งหมด <strong>{{ servers.length }}</strong> รายการ
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="serverModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 bg-white rounded-top-4">
            <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
              <div
                class="icon-square bg-gradient-primary text-white shadow-sm"
                style="width: 35px; height: 35px; font-size: 1rem"
              >
                <i :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-lg'"></i>
              </div>
              {{ isEditMode ? 'แก้ไขข้อมูลเครื่อง Server' : 'ลงทะเบียนเครื่อง Server ใหม่' }}
            </h5>
            <button
              type="button"
              class="btn-close shadow-none"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-4 pt-3">
            <form @submit.prevent="saveServer">
              <div class="row g-3">
                <div class="col-md-6 border-end-md pb-3 pb-md-0">
                  <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                    <i class="bi bi-info-circle me-2"></i>ข้อมูลทั่วไปองค์ประกอบ
                  </h6>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ชื่อ Server <span class="text-danger">*</span></label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-server"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0 fw-bold fs-5 text-dark"
                        v-model="form.server_name"
                        required
                        placeholder="เช่น SRV-DB-01"
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ประเภทเซิร์ฟเวอร์ <span class="text-danger">*</span></label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0">
                        <i
                          :class="
                            form.server_type === 'Virtual'
                              ? 'bi-cloud-fill text-primary'
                              : 'bi-hdd-rack-fill text-primary'
                          "
                        ></i>
                      </span>
                      <select
                        v-model="form.server_type"
                        class="form-select border-start-0 ps-0 fw-bold pb-2"
                        required
                      >
                        <option value="Physical">Physical Server (เซิร์ฟเวอร์จริง)</option>
                        <option value="Virtual">Virtual Server (เครื่องเสมือน/VM)</option>
                      </select>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >IP Address</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0 text-primary"
                        ><i class="bi bi-diagram-2"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0 font-monospace"
                        v-model="form.ip_address"
                        placeholder="10.0.x.x"
                      />
                    </div>
                  </div>

                  <div class="row g-2 mb-3">
                    <div class="col-md-7">
                      <label class="form-label fw-bold text-secondary small text-uppercase"
                        >ระบบปฏิบัติการ (OS)</label
                      >
                      <div class="input-group input-group-custom">
                        <span class="input-group-text bg-white border-end-0"
                          ><i class="bi bi-window-sidebar"></i
                        ></span>
                        <input
                          type="text"
                          class="form-control border-start-0 ps-0"
                          v-model="form.os"
                          placeholder="เช่น Ubuntu"
                          list="os_list"
                        />
                        <datalist id="os_list">
                          <option value="Ubuntu"></option>
                          <option value="Windows Server"></option>
                          <option value="CentOS"></option>
                          <option value="AlmaLinux"></option>
                          <option value="VMware ESXi"></option>
                        </datalist>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <label class="form-label fw-bold text-secondary small text-uppercase"
                        >เวอร์ชัน</label
                      >
                      <div class="input-group input-group-custom">
                        <input
                          type="text"
                          class="form-control text-center px-1"
                          v-model="form.version"
                          placeholder="22.04 LTS"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="mb-0">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >หน้าที่หลัก (Role)</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-gear-fill"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.role"
                        placeholder="เช่น Web Server, Database, AD"
                      />
                    </div>
                  </div>

                  <div class="mb-3 mt-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >ผู้รับผิดชอบ / คนใช้งาน</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-person"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.user_name"
                        placeholder="ระบุชื่อพนักงาน"
                        list="staff_list"
                      />
                      <datalist id="staff_list">
                        <option
                          v-for="staff in staffList"
                          :key="staff.id"
                          :value="staff.name"
                        ></option>
                      </datalist>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 ps-md-4">
                  <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">
                    <i class="bi bi-cpu me-2"></i>รายละเอียดสเปก & อื่นๆ
                  </h6>

                  <div class="row g-2 mb-3">
                    <div class="col-6">
                      <label class="form-label fw-bold text-secondary small text-uppercase"
                        >CPU (vCore)</label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm text-center"
                        v-model="form.cpu"
                        placeholder="e.g. 8 Core"
                      />
                    </div>
                    <div class="col-6">
                      <label class="form-label fw-bold text-secondary small text-uppercase"
                        >RAM</label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm text-center"
                        v-model="form.ram"
                        placeholder="e.g. 32 GB"
                      />
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-bold text-secondary small text-uppercase"
                        >Storage (ความจุ)</label
                      >
                      <input
                        type="text"
                        class="form-control form-control-sm text-center"
                        v-model="form.storage"
                        placeholder="e.g. 1 TB SSD"
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >สถานที่ตั้ง (Location)</label
                    >
                    <div class="input-group input-group-custom">
                      <span class="input-group-text bg-white border-end-0"
                        ><i class="bi bi-geo-alt"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control border-start-0 ps-0"
                        v-model="form.location"
                        placeholder="เช่น ห้อง Server ชั้น 1, AWS"
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >สถานะ <span class="text-danger">*</span></label
                    >
                    <select
                      class="form-select form-select-custom fw-bold"
                      v-model="form.status"
                      required
                      :class="{
                        'text-success': form.status === 'active',
                        'text-warning': form.status === 'maintenance',
                        'text-secondary': form.status === 'inactive'
                      }"
                    >
                      <option value="active">🟢 ใช้งานอยู่ (Active)</option>
                      <option value="maintenance">🟡 ปิดปรับปรุง (Maintenance)</option>
                      <option value="inactive">⚪ ปิดใช้งาน (Inactive)</option>
                    </select>
                  </div>

                  <div class="mb-0">
                    <label class="form-label fw-bold text-secondary small text-uppercase"
                      >หมายเหตุ</label
                    >
                    <textarea
                      class="form-control"
                      v-model="form.notes"
                      rows="2"
                      placeholder="ระบุรายละเอียด..."
                    ></textarea>
                  </div>
                </div>
              </div>
              <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <button
                  type="button"
                  class="btn btn-light rounded-pill px-4 fw-bold shadow-sm"
                  data-bs-dismiss="modal"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
                  :disabled="saving"
                >
                  <div class="spinner-border spinner-border-sm" role="status" v-if="saving"></div>
                  <i class="bi bi-save" v-else></i> บันทึกข้อมูล
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import * as bootstrap from 'bootstrap';

export default {
  name: 'ServerList',
  data() {
    return {
      servers: [],
      currentPage: 1,
      itemsPerPage: 5,
      loading: true,
      saving: false,
      searchQuery: '',
      statusFilter: 'all',
      typeFilter: 'all',
      debounceTimer: null,
      modalInstance: null,
      isEditMode: false,
      staffList: [],
      form: {
        id: null,
        server_name: '',
        server_type: 'Physical',
        ip_address: '',
        os: '',
        version: '',
        cpu: '',
        ram: '',
        storage: '',
        role: '',
        user_name: '',
        location: '',
        status: 'active',
        notes: ''
      }
    };
  },
  computed: {
    paginatedServers() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.servers.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.servers.length / this.itemsPerPage);
    },
    totalServers() {
      return this.servers.length;
    },
    activeServers() {
      return this.servers.filter((s) => s.status === 'active').length;
    },
    nonActiveServers() {
      return this.servers.filter((s) => s.status !== 'active').length;
    }
  },
  mounted() {
    this.fetchServers();
    this.fetchStaff();
    // Initialize Modal
    const modalEl = document.getElementById('serverModal');
    if (modalEl) {
      this.modalInstance = new bootstrap.Modal(modalEl);
      modalEl.addEventListener('hidden.bs.modal', () => {
        this.resetForm();
      });
    }
  },
  beforeUnmount() {
    if (this.modalInstance) {
      this.modalInstance.dispose();
    }
  },
  methods: {
    debounceFetch() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.currentPage = 1; // Reset to page 1 on new search
        this.fetchServers();
      }, 400);
    },
    async fetchServers() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/server/get_servers.php', {
          params: {
            search: this.searchQuery,
            status: this.statusFilter,
            server_type: this.typeFilter
          }
        });
        if (res.data.success) {
          this.servers = res.data.data || [];
          if (this.currentPage > this.totalPages && this.totalPages > 0) {
            this.currentPage = this.totalPages;
          } else if (this.servers.length === 0) {
            this.currentPage = 1;
          }
        }
      } catch (error) {
        console.error('Error fetching servers:', error);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'ดึงข้อมูลล้มเหลว',
          showConfirmButton: false,
          timer: 3000
        });
      } finally {
        this.loading = false;
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    },
    async fetchStaff() {
      try {
        const res = await axios.get('/api-digital/duties/get_employee.php');
        if (res.data && res.data.data) {
          this.staffList = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching staff info:', error);
      }
    },
    getStatusLabel(status) {
      if (status === 'active') return '🟢 ใช้งานอยู่';
      if (status === 'inactive') return '⚪ ปิดใช้งาน';
      if (status === 'maintenance') return '🟡 ระหว่างปรับปรุง';
      return parseInt(status) || status;
    },
    getStatusClass(status) {
      if (status === 'active') return 'text-success bg-success bg-opacity-10 border-success';
      if (status === 'inactive')
        return 'text-secondary bg-secondary bg-opacity-10 border-secondary';
      if (status === 'maintenance') return 'text-warning bg-warning bg-opacity-10 border-warning';
      return '';
    },
    openAddModal() {
      this.isEditMode = false;
      this.resetForm();
      this.modalInstance.show();
    },
    editServer(item) {
      this.isEditMode = true;
      this.form = { ...item };
      this.modalInstance.show();
    },
    resetForm() {
      this.form = {
        id: null,
        server_name: '',
        server_type: 'Physical',
        ip_address: '',
        os: '',
        version: '',
        cpu: '',
        ram: '',
        storage: '',
        role: '',
        user_name: '',
        location: '',
        status: 'active',
        notes: ''
      };
    },
    async saveServer() {
      this.saving = true;
      const url = this.isEditMode
        ? '/api-digital/server/update_server.php'
        : '/api-digital/server/add_server.php';

      try {
        const res = await axios.post(url, this.form);
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: res.data.message,
            timer: 2000,
            showConfirmButton: false
          });
          this.modalInstance.hide();
          this.fetchServers();
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        console.error('Save error', error);
        Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
      } finally {
        this.saving = false;
      }
    },
    deleteServer(item) {
      Swal.fire({
        title: 'ยืนยันการลบ',
        text: `คุณต้องการลบข้อมูล Server: ${item.server_name} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#secondary',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const res = await axios.post('/api-digital/server/delete_server.php', { id: item.id });
            if (res.data.success) {
              Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'ลบข้อมูลสำเร็จ',
                showConfirmButton: false,
                timer: 3000
              });
              this.fetchServers();
            } else {
              throw new Error(res.data.message);
            }
          } catch (error) {
            Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถลบข้อมูลได้', 'error');
          }
        }
      });
    }
  }
};
</script>

<style scoped>
/* Animations */
.fade-in {
  animation: fadeIn 0.4s ease-out;
}
.fade-in-up {
  animation: fadeInUp 0.5s ease-out forwards;
  opacity: 0;
}
.delay-100 {
  animation-delay: 0.1s;
}
.delay-200 {
  animation-delay: 0.2s;
}
.delay-300 {
  animation-delay: 0.3s;
}
.delay-400 {
  animation-delay: 0.4s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Custom UI Items */
.icon-square {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.25rem;
}
.bg-gradient-primary {
  background: linear-gradient(135deg, var(--bs-primary) 0%, #0d6efd 100%);
}

.hover-lift {
  transition:
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s ease;
}
.hover-lift:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.glass-card {
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

/* Input Groups */
.input-group-custom .form-control:focus {
  border-color: #dee2e6;
  box-shadow: none;
}
.input-group-custom {
  transition: all 0.2s ease;
  border-radius: 0.5rem;
  overflow: hidden;
}
.input-group-custom:focus-within {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
  border-color: #86b7fe;
}
.input-group-custom .input-group-text,
.input-group-custom .form-control {
  background-color: #f8f9fa;
  border-color: #e9ecef;
}
.input-group-custom:focus-within .input-group-text,
.input-group-custom:focus-within .form-control {
  background-color: #fff;
  border-color: #86b7fe;
}
.form-select-custom {
  background-color: #f8f9fa;
  border-color: #e9ecef;
  border-radius: 0.5rem;
}
.form-select-custom:focus {
  background-color: #fff;
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Data tables */
.table-custom th {
  border-bottom-width: 2px !important;
  letter-spacing: 0.5px;
}
.item-row {
  transition: all 0.2s ease;
}
.item-row:hover {
  background-color: #f8f9fa !important;
}

/* Stats Card */
.stat-card {
  transition:
    transform 0.2s,
    box-shadow 0.2s;
  cursor: default;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}

.custom-breadcrumb .breadcrumb-item a {
  color: #6c757d;
  transition: color 0.2s;
}
.custom-breadcrumb .breadcrumb-item a:hover {
  color: var(--bs-primary);
}

.border-end-md {
  border-right: 1px solid #dee2e6;
}
@media (max-width: 767.98px) {
  .border-end-md {
    border-right: none;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
  }
}
</style>
