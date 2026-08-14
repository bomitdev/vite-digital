<template>
  <div class="container-fluid py-4 min-vh-100 bg-light font-sarabun">
    <div class="container-lg">
      <!-- Header -->
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
          <i class="bi bi-person-lines-fill fs-3 text-primary me-3"></i>
          <div>
            <h4 class="fw-bold m-0 text-dark">จัดการสิทธิ์ผู้ใช้งาน</h4>
            <small class="text-muted">User Access Management</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          
          <button
            @click="$router.push('/home-backoffice')"
            class="btn btn-outline-secondary rounded-pill fw-bold"
          >
            <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
          </button>
        </div>
      </div>

      <!-- Summary Card -->
      <div class="row mb-4">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
          <div
            class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100"
            style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)"
          >
            <div class="card-body p-3 p-md-4 d-flex align-items-center">
              <div
                class="rounded-circle bg-white bg-opacity-25 p-3 me-3 d-flex align-items-center justify-content-center"
                style="width: 64px; height: 64px"
              >
                <i class="bi bi-people-fill fs-1"></i>
              </div>
              <div>
                <h6 class="mb-1 fw-bold text-white-50">จำนวนเจ้าหน้าที่ทั้งหมด</h6>
                <h2 class="mb-0 fw-bold display-6">
                  {{ users.length }} <span class="fs-5 fw-normal text-white-50">คน</span>
                </h2>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-8">
          <div class="row g-2 h-100 align-content-start">
            <div class="col-12 mb-1">
              <h6 class="fw-bold text-dark text-center">แยกตามประเภทบุคลากร</h6>
            </div>
            <div
              class="col-6 col-md-4 col-lg-3"
              v-for="type in personnelTypeStats"
              :key="type.name"
            >
              <div
                class="card border-0 shadow-sm text-center h-100 overflow-hidden"
                style="border-radius: 4px"
              >
                <div
                  class="text-white py-1 fw-bold"
                  style="
                    background-color: #b091fb;
                    font-size: 0.85rem;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    padding-left: 5px;
                    padding-right: 5px;
                  "
                  :title="type.name"
                >
                  {{ type.name }}
                </div>
                <div
                  class="card-body py-2 bg-white d-flex align-items-center justify-content-center"
                >
                  <h5 class="mb-0 fw-bold text-dark">
                    {{ type.count }} <span class="small fw-normal">คน</span>
                  </h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div v-if="users.length > 0" class="row mb-4 g-3">
        <!-- Position Chart -->
        <div class="col-12 col-xl-6">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
              <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-bar-chart-fill me-2 text-primary"></i
                >กราฟแสดงจำนวนผู้ใช้งานแบ่งตามตำแหน่ง
              </h6>
              <div style="height: 250px; width: 100%">
                <canvas id="positionChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <!-- Department Chart -->
        <div class="col-12 col-xl-6">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
              <h6 class="fw-bold text-dark mb-3">
                <i class="bi bi-bar-chart-line-fill me-2 text-success"></i
                >กราฟแสดงจำนวนผู้ใช้งานแบ่งตามแผนก
              </h6>
              <div style="height: 250px; width: 100%">
                <canvas id="departmentChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Search & Filters -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted"
                >ค้นหา (ชื่อ, สกุล, Username) :</label
              >
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"
                  ><i class="bi bi-search text-muted"></i
                ></span>
                <input
                  type="text"
                  class="form-control border-start-0 ps-0"
                  v-model="searchQuery"
                  placeholder="พิมพ์คำค้นหา..."
                  @keyup.enter="fetchUsers"
                />
                <button class="btn btn-primary px-4" @click="fetchUsers">ค้นหา</button>
              </div>
            </div>
            <div class="col-md-4" v-if="canEdit">
              <label class="form-label small fw-bold text-muted">แผนก :</label>
              <select class="form-select" v-model="selectedDepartment" @change="fetchUsers">
                <option value="">ทั้งหมด</option>
                <option
                  v-for="dept in departments"
                  :key="dept.HR_DEPARTMENT_SUB_ID"
                  :value="dept.HR_DEPARTMENT_SUB_ID"
                >
                  {{ dept.HR_DEPARTMENT_SUB_NAME }}
                </option>
              </select>
            </div>
            <div class="col-md-2 align-self-end">
              <button
                @click="exportExcel"
                class="btn btn-success rounded-pill fw-bold w-100"
                :disabled="users.length === 0"
              >
                <i class="bi bi-file-earmark-excel-fill me-1"></i> ส่งออก Excel
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Users Table -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light border-bottom">
              <tr>
                <th class="py-3 px-4 text-secondary small fw-bold text-center">ลำดับ</th>
                <th class="py-3 px-4 text-secondary small fw-bold">person_id</th>
                <th class="py-3 px-4 text-secondary small fw-bold">finger_id</th>
                <th class="py-3 px-4 text-secondary small fw-bold">ชื่อ-นามสกุล (ชื่อเล่น)</th>
                <th class="py-3 text-secondary small fw-bold" style="min-width: 250px">
                  ตำแหน่ง / แผนก / ข้อมูลเพิ่มเติม
                </th>
                <th class="py-3 text-secondary small fw-bold">สิทธิ์การใช้งาน</th>
                <th class="py-3 text-secondary small fw-bold text-center position-sticky end-0 bg-light" style="width: 100px; z-index: 1;">
                  จัดการ
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(user, index) in users" :key="user.ID">
                <td class="py-3 px-4 text-center">
                  <div class="fw-bold text-muted">{{ index + 1 }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="fw-bold text-dark">{{ user.ID }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="fw-bold text-dark">{{ user.FINGLE_ID }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="fw-bold text-dark">
                    {{ user.FULLNAME }}
                    <span v-if="user.NICKNAME" class="text-primary ms-1"
                      >({{ user.NICKNAME }})</span
                    >
                  </div>
                  <div class="small text-muted">
                    <i class="bi bi-person me-1"></i>{{ user.HR_USERNAME }}
                  </div>
                  <div class="small text-muted mt-1" v-if="user.HR_EMAIL || user.HR_PHONE">
                    <div v-if="user.HR_EMAIL">
                      <i class="bi bi-envelope me-1"></i>{{ user.HR_EMAIL }}
                    </div>
                    <div v-if="user.HR_PHONE">
                      <i class="bi bi-telephone me-1"></i>{{ user.HR_PHONE }}
                    </div>
                  </div>
                </td>
                <td class="py-3">
                  <div class="text-dark fw-bold mb-1" style="font-size: 0.95rem">
                    {{ user.POSITION_NAME }}
                  </div>

                  <div class="mb-2">
                    <span class="badge bg-light text-secondary border">
                      <i class="bi bi-building me-1"></i>{{ user.DEPARTMENT_NAME }}
                    </span>
                  </div>

                  <div class="d-flex flex-column gap-1" style="font-size: 0.85rem">
                    <div class="text-secondary" v-if="user.HR_POSITION_NUM">
                      <i class="bi bi-hash text-muted me-1"></i
                      ><span class="text-muted">เลขตำแหน่ง:</span>
                      <span class="fw-semibold text-dark">{{ user.HR_POSITION_NUM }}</span>
                    </div>
                    <div class="text-secondary" v-if="user.VCODE">
                      <i class="bi bi-card-checklist text-muted me-1"></i
                      ><span class="text-muted">ใบประกอบ:</span>
                      <span class="fw-semibold text-dark">{{ user.VCODE }}</span>
                    </div>
                    <div
                      class="text-success"
                      v-if="user.HR_STARTWORK_DATE && user.HR_STARTWORK_DATE !== '0000-00-00'"
                    >
                      <i class="bi bi-calendar-check text-success me-1"></i
                      ><span class="text-muted">เริ่มงาน:</span>
                      <span class="fw-semibold text-success">{{
                        formatDate(user.HR_STARTWORK_DATE)
                      }}</span>
                    </div>
                  </div>
                </td>
                <td class="py-3">
                  <div class="d-flex flex-wrap gap-1">
                    <!-- Loop through IDs but display mapped Names -->
                    <span
                      v-for="(id, idx) in parseAccess(user.access_user)"
                      :key="idx"
                      class="badge bg-info-subtle text-info-emphasis fw-normal border border-info-subtle text-truncate d-inline-block"
                      style="max-width: 200px; vertical-align: bottom;"
                      :title="getAccessName(id)"
                    >
                      {{ getAccessName(id) }}
                    </span>
                    <span v-if="!user.access_user" class="text-muted small">- ไม่มีสิทธิ์ -</span>
                  </div>
                </td>
                <td class="text-center position-sticky end-0 bg-white shadow-sm">
                  <button
                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                    @click="openEditModal(user)"
                  >
                    <i class="bi bi-pencil-fill me-1"></i> แก้ไข
                  </button>
                </td>
              </tr>
              <tr v-if="users.length === 0 && !loading">
                <td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูล</td>
              </tr>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5 text-primary">
                  <div class="spinner-border spinner-border-sm me-2"></div>
                  กำลังโหลด...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editAccessModal" tabindex="-1" ref="editModal">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-warning-subtle text-warning-emphasis border-0">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-key-fill me-2"></i>แก้ไขสิทธิ์ : {{ editingUser?.FULLNAME }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-3">
            <div class="row g-2">
              <div class="col-12 mb-3">
                <label class="form-label small fw-bold text-muted"
                  >Finger ID (รหัสลายนิ้วมือ) :</label
                >
                <input
                  type="text"
                  class="form-control"
                  v-model="editingFingerId"
                  placeholder="ระบุรหัสลายนิ้วมือ"
                />
              </div>
              <div class="col-md-6" v-for="(access, index) in availableAccess" :key="index">
                <label
                  class="d-flex align-items-center gap-2 p-2 border rounded cursor-pointer hover-bg-light h-100"
                >
                  <input
                    class="form-check-input flex-shrink-0 mt-0"
                    type="checkbox"
                    :value="access.access_id"
                    :id="'acc' + index"
                    v-model="selectedAccess"
                  />
                  <div class="lh-1">
                    <div class="fw-bold text-dark small">{{ access.access_name }}</div>
                    <div class="text-muted" style="font-size: 0.7em">
                      {{ access.access_desc || '-' }}
                    </div>
                  </div>
                </label>
              </div>
            </div>
            <div v-if="availableAccess.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
              ไม่พบข้อมูลสิทธิ์การใช้งาน
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary px-4" @click="closeModal">ยกเลิก</button>
            <button
              type="button"
              class="btn btn-primary px-4 fw-bold"
              @click="saveAccess"
              :disabled="saving"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              บันทึกการเปลี่ยนแปลง
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';
import Chart from 'chart.js/auto';
import * as XLSX from 'xlsx';

export default {
  name: 'UserManagerView',
  data() {
    return {
      searchQuery: '',
      users: [],
      availableAccess: [],
      selectedDepartment: '',
      departments: [],
      loading: false,
      currentUserProfile: null,

      // Modal State
      modalInstance: null,
      editingUser: null,
      selectedAccess: [],
      editingFingerId: '',
      saving: false
    };
  },
  created() {
    this.positionChartInstance = null;
    this.departmentChartInstance = null;
  },
  computed: {
    accessMap() {
      // Map ID -> Access Name
      const map = {};
      this.availableAccess.forEach((a) => (map[a.access_id] = a.access_name));
      return map;
    },
    canEdit() {
      if (!this.currentUserProfile || !this.currentUserProfile.access_user) return false;

      const userRights = this.currentUserProfile.access_user.split(':');
      const allowedRoles = ['Super', 'Admin', 'administrator'];

      return userRights.some((token) => {
        // Check if token is directly a name (Legacy)
        if (allowedRoles.includes(token)) return true;

        // Check if token is an ID that maps to a name (New)
        const name = this.accessMap[token];
        return name && allowedRoles.includes(name);
      });
    },
    personnelTypeStats() {
      if (!this.users || this.users.length === 0) return [];
      const counts = {};
      this.users.forEach((u) => {
        const type = u.PERSON_TYPE_NAME || 'ไม่ระบุ';
        counts[type] = (counts[type] || 0) + 1;
      });
      return Object.keys(counts)
        .map((key) => ({
          name: key,
          count: counts[key]
        }))
        .sort((a, b) => b.count - a.count);
    }
  },
  methods: {
    parseAccess(accessString) {
      if (!accessString) return [];
      // Return array of IDs
      return accessString.split(':').filter((a) => a);
    },
    formatDate(dateString) {
      if (!dateString || dateString === '0000-00-00') return '-';
      const date = new Date(dateString);
      if (isNaN(date)) return dateString;
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    getAccessName(id) {
      return this.accessMap[id] || id; // Fallback to ID if not found
    },
    exportExcel() {
      if (this.users.length === 0) return;

      const exportData = this.users.map((u, index) => {
        return {
          ลำดับ: index + 1,
          person_id: u.ID,
          finger_id: u.FINGLE_ID || '-',
          'ชื่อ-นามสกุล': u.FULLNAME,
          ชื่อเล่น: u.NICKNAME || '-',
          Username: u.HR_USERNAME || '-',
          Email: u.HR_EMAIL || '-',
          โทรศัพท์: u.HR_PHONE || '-',
          ตำแหน่ง: u.POSITION_NAME || '-',
          เลขตำแหน่ง: u.HR_POSITION_NUM || '-',
          เลขใบประกอบ: u.VCODE || '-',
          แผนก: u.DEPARTMENT_NAME || '-',
          เริ่มงาน: this.formatDate(u.HR_STARTWORK_DATE)
        };
      });

      const ws = XLSX.utils.json_to_sheet(exportData);

      const colWidths = [
        { wch: 8 },
        { wch: 10 },
        { wch: 10 },
        { wch: 25 },
        { wch: 10 },
        { wch: 15 },
        { wch: 20 },
        { wch: 15 },
        { wch: 20 },
        { wch: 15 },
        { wch: 15 },
        { wch: 25 },
        { wch: 15 }
      ];
      ws['!cols'] = colWidths;

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Users');

      const dateStr = new Date().toISOString().slice(0, 10);
      XLSX.writeFile(wb, `รายงานสิทธิ์ผู้ใช้งาน_${dateStr}.xlsx`);
    },
    async fetchUserProfile() {
      try {
        const response = await axios.get('/api-hosoffice/get_user_profile.php');
        if (response.data.status === 'success') {
          this.currentUserProfile = response.data;
        }
      } catch (error) {
        console.error('Profile Error', error);
      }
    },
    async fetchUsers() {
      this.loading = true;
      try {
        const response = await axios.get('/api-hosoffice/get_users_manager.php', {
          params: {
            search: this.searchQuery,
            department_id: this.selectedDepartment
          }
        });
        if (response.data.status === 'success') {
          let fetchedUsers = response.data.data;
          // Sort by finger_id descending, but put empty finger_id at the top
          fetchedUsers.sort((a, b) => {
            const emptyA = !a.FINGLE_ID || String(a.FINGLE_ID).trim() === '' || a.FINGLE_ID == '0';
            const emptyB = !b.FINGLE_ID || String(b.FINGLE_ID).trim() === '' || b.FINGLE_ID == '0';

            if (emptyA && !emptyB) return -1; // a is empty, put at top
            if (!emptyA && emptyB) return 1; // b is empty, put at top

            const idA = parseInt(a.FINGLE_ID) || 0;
            const idB = parseInt(b.FINGLE_ID) || 0;
            return idB - idA; // Otherwise sort by ID descending
          });
          this.users = fetchedUsers;
          this.$nextTick(() => {
            this.renderChart();
            this.renderDepartmentChart();
          });
        }
      } catch (error) {
        console.error('Error fetching users', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchDepartments() {
      try {
        const response = await axios.get('/api-hosoffice/get_departments.php');
        if (response.data.status === 'success') {
          this.departments = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching departments', error);
      }
    },
    async fetchAccessList() {
      try {
        const response = await axios.get('/api-hosoffice/get_access_list.php');
        if (response.data.status === 'success') {
          this.availableAccess = response.data.data;
        } else {
          console.error('Access List Error:', response.data.message);
          alert('Failed to load access list: ' + response.data.message);
        }
      } catch (error) {
        console.error('Error fetching access list', error);
      }
    },
    openEditModal(user) {
      this.editingUser = user;
      this.editingFingerId = user.FINGLE_ID || '';
      this.selectedAccess = this.parseAccess(user.access_user);

      if (!this.modalInstance) {
        this.modalInstance = new Modal(this.$refs.editModal);
      }
      this.modalInstance.show();
    },
    closeModal() {
      if (this.modalInstance) {
        this.modalInstance.hide();
      }
      this.editingUser = null;
      this.selectedAccess = [];
    },
    async saveAccess() {
      if (!this.editingUser) return;
      this.saving = true;

      // Join with : as requested
      const newAccessString = this.selectedAccess.join(':');

      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-hosoffice/update_user_access.php',
          {
            target_user_id: this.editingUser.ID,
            new_access: newAccessString,
            finger_id: this.editingFingerId
          },
          {
            headers: {
              Authorization: `Bearer ${token}`
            }
          }
        );

        if (response.data.status === 'success') {
          // Update local data
          this.editingUser.access_user = newAccessString;
          this.editingUser.FINGLE_ID = this.editingFingerId;
          // Optional: Show success alert
          this.closeModal();
          // Refresh list to be sure
          this.fetchUsers();
        } else {
          alert('Error: ' + response.data.message);
        }
      } catch (error) {
        alert('Save failed: ' + error.message);
      } finally {
        this.saving = false;
      }
    },
    renderChart() {
      if (!this.users || this.users.length === 0) return;

      const positionCounts = {};
      this.users.forEach((u) => {
        let pos = u.POSITION_NAME || 'ไม่ระบุ';
        positionCounts[pos] = (positionCounts[pos] || 0) + 1;
      });

      // Sort by counts descending
      const sortedKeys = Object.keys(positionCounts).sort(
        (a, b) => positionCounts[b] - positionCounts[a]
      );

      let labels = [];
      let data = [];

      if (sortedKeys.length > 15) {
        labels = sortedKeys.slice(0, 15);
        data = labels.map((k) => positionCounts[k]);

        let otherCount = 0;
        for (let i = 15; i < sortedKeys.length; i++) {
          otherCount += positionCounts[sortedKeys[i]];
        }
        labels.push('อื่นๆ');
        data.push(otherCount);
      } else {
        labels = sortedKeys;
        data = sortedKeys.map((k) => positionCounts[k]);
      }

      const ctx = document.getElementById('positionChart');
      if (!ctx) return;

      if (this.positionChartInstance) {
        this.positionChartInstance.destroy();
      }

      this.positionChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'จำนวน (คน)',
              data: data,
              backgroundColor: '#0d6efd',
              borderRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: {
            padding: {
              top: 25
            }
          },
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            },
            x: {
              ticks: {
                autoSkip: false,
                maxRotation: 45,
                minRotation: 0,
                font: {
                  size: 11
                }
              }
            }
          }
        },
        plugins: [
          {
            id: 'topLabels',
            afterDatasetsDraw(chart) {
              const { ctx } = chart;
              ctx.save();
              ctx.font = "bold 14px 'Sarabun'";
              ctx.textAlign = 'center';
              ctx.textBaseline = 'bottom';
              ctx.fillStyle = '#0a58ca';

              chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach((bar, index) => {
                  const value = dataset.data[index];
                  if (value > 0) {
                    ctx.fillText(value, bar.x, bar.y - 8);
                  }
                });
              });
              ctx.restore();
            }
          }
        ]
      });
    },
    renderDepartmentChart() {
      if (!this.users || this.users.length === 0) return;

      const deptCounts = {};
      this.users.forEach((u) => {
        let dept = u.DEPARTMENT_NAME || 'ไม่ระบุ';
        deptCounts[dept] = (deptCounts[dept] || 0) + 1;
      });

      // Sort by counts descending
      const sortedKeys = Object.keys(deptCounts).sort((a, b) => deptCounts[b] - deptCounts[a]);

      let labels = [];
      let data = [];

      if (sortedKeys.length > 15) {
        labels = sortedKeys.slice(0, 15);
        data = labels.map((k) => deptCounts[k]);

        let otherCount = 0;
        for (let i = 15; i < sortedKeys.length; i++) {
          otherCount += deptCounts[sortedKeys[i]];
        }
        labels.push('อื่นๆ');
        data.push(otherCount);
      } else {
        labels = sortedKeys;
        data = sortedKeys.map((k) => deptCounts[k]);
      }

      const ctx = document.getElementById('departmentChart');
      if (!ctx) return;

      if (this.departmentChartInstance) {
        this.departmentChartInstance.destroy();
      }

      this.departmentChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'จำนวน (คน)',
              data: data,
              backgroundColor: '#198754', // bs-success
              borderRadius: 4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: {
            padding: {
              top: 25
            }
          },
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            },
            x: {
              ticks: {
                autoSkip: false,
                maxRotation: 45,
                minRotation: 0,
                font: {
                  size: 11
                }
              }
            }
          }
        },
        plugins: [
          {
            id: 'topLabelsDept',
            afterDatasetsDraw(chart) {
              const { ctx } = chart;
              ctx.save();
              ctx.font = "bold 14px 'Sarabun'";
              ctx.textAlign = 'center';
              ctx.textBaseline = 'bottom';
              ctx.fillStyle = '#146c43';

              chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach((bar, index) => {
                  const value = dataset.data[index];
                  if (value > 0) {
                    ctx.fillText(value, bar.x, bar.y - 8);
                  }
                });
              });
              ctx.restore();
            }
          }
        ]
      });
    }
  },
  mounted() {
    this.fetchUserProfile();
    this.fetchDepartments(); // Fetch departments first/parallel
    this.fetchUsers();
    this.fetchAccessList();
  }
};
</script>

<style scoped>
.font-sarabun {
  font-family: 'Sarabun', sans-serif;
}
.hover-shadow {
  transition: all 0.2s;
}
.hover-shadow:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
}
.hover-bg-light:hover {
  background-color: #f8f9fa;
}
</style>
