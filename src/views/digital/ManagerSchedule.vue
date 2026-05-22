<template>
  <div class="container-fluid p-0 vh-100 d-flex flex-column flex-md-row overflow-hidden bg-light">
    <aside
      class="d-none d-md-flex flex-column p-3 bg-white border-end shadow-sm"
      style="width: 280px"
    >
      <div class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <img src="/src/assets/digital-logo.png" alt="Logo" width="40" height="40" class="me-2" />
        <div>
          <div class="fs-6 fw-bold text-purple">กลุ่มงานสุขภาพดิจิทัล</div>
          <div class="small text-muted" style="font-size: 0.75rem">โรงพยาบาลชานุมาน</div>
        </div>
      </div>
      <div class="mt-3 d-grid gap-2">
        <button class="btn btn-warning btn-sm ms-1 fw-bold shadow-sm" @click="processTime">
          <i class="bi bi-gear-fill"></i> ประมวลผลเวลาเข้า-ออก
        </button>
        <button
          class="btn btn-light text-purple btn-sm ms-1 shadow-sm fw-bold border"
          @click="openScanModal"
          title="เพิ่มเวลาที่ลืมลง"
        >
          <i class="bi bi-clock-history"></i> !
        </button>
        <button
          class="btn btn-info btn-sm ms-1 shadow-sm fw-bold border text-white"
          @click="openCheckTimeModal"
          title="ตรวจสอบเวลา"
        >
          <i class="bi bi-search"> ตรวจสอบเวลา</i>
        </button>
        <button
          class="btn btn-primary btn-sm ms-1 shadow-sm fw-bold border text-white"
          @click="openWorkingDaysModal"
          title="จัดการวันทำการ"
        >
          <i class="bi bi-calendar-check"> วันทำการ</i>
        </button>
      </div>
      <hr />

      <ul class="nav nav-pills flex-column">
        <li class="nav-item">
          <a
            href="#"
            class="nav-link"
            :class="currentTab === 'card' ? 'active bg-purple text-white' : 'link-dark'"
            @click.prevent="switchTab('card')"
          >
            <i class="bi bi-card-checklist me-2"></i> ตารางเวรห้องบัตร
          </a>
        </li>
        <li class="nav-item">
          <a
            href="#"
            class="nav-link"
            :class="currentTab === 'claim' ? 'active bg-purple text-white' : 'link-dark'"
            @click.prevent="switchTab('claim')"
          >
            <i class="bi bi-shield-plus me-2"></i> ตารางเวรเบิกเครม
          </a>
        </li>
        <li class="nav-item">
          <a
            href="#"
            class="nav-link"
            :class="currentTab === 'it' ? 'active bg-purple text-white' : 'link-dark'"
            @click.prevent="switchTab('it')"
          >
            <i class="bi bi-pc-display me-2"></i> ตารางเวร IT
          </a>
        </li>
      </ul>
      <hr />

      <div class="contacts-section mt-2">
        <h6
          class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-2 mb-1 text-muted text-uppercase"
        >
          <span>เบอร์โทรติดต่อ ({{ currentTabName }})</span>
        </h6>
        <div
          class="list-group list-group-flush small overflow-auto custom-scrollbar"
          style="max-height: 200px"
        >
          <div
            v-for="(contact, idx) in contacts"
            :key="idx"
            class="list-group-item d-flex justify-content-between align-items-center px-2"
          >
            <span class="text-truncate" style="max-width: 140px" :title="contact.name">{{
              contact.name
            }}</span>
            <span class="text-muted">{{ contact.phone }}</span>
          </div>
          <div v-if="contacts.length === 0" class="text-center text-muted py-2">
            - ไม่มีข้อมูล -
          </div>
        </div>
      </div>
    </aside>

    <main class="flex-grow-1 d-flex flex-column h-100 overflow-hidden">
      <header
        class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center shadow-sm z-index-1"
      >
        <div>
          <h1 class="h4 mb-0 text-purple fw-bold">
            <span v-if="currentTab === 'it'">ตารางเวร IT Support</span>
            <span v-else-if="currentTab === 'claim'">ตารางเวรงานประกัน/เบิกเครม</span>
            <span v-else>ตารางเวรห้องบัตร (OPD Card)</span>
          </h1>
          <small class="text-muted d-none d-sm-block"
            >วิสัยทัศน์ : โรงพยาบาลชุมชน คุณภาพชั้นนำ แห่งลุ่มน้ำโขง</small
          >
        </div>
        <div class="btn-group">
          <button
            class="btn btn-outline-danger btn-sm fw-bold border"
            @click="$router.push('/ot-report-summary')"
            title="พิมพ์หน้าสรุปทุกแผนก"
          >
            <i class="bi bi-file-earmark-pdf"></i> สรุป OT รวม
          </button>
          <button
            v-if="currentTab === 'it'"
            class="btn btn-outline-success btn-sm fw-bold border"
            @click="$router.push('/ot-report-it')"
          >
            <i class="bi bi-printer"></i> รายงาน OT
          </button>
          <button
            v-if="currentTab === 'claim'"
            class="btn btn-outline-success btn-sm fw-bold border"
            @click="$router.push('/ot-report-claim')"
          >
            <i class="bi bi-printer"></i> รายงาน OT
          </button>
          <button
            v-if="currentTab === 'card'"
            class="btn btn-outline-success btn-sm fw-bold border"
            @click="$router.push('/ot-report-opdcard')"
          >
            <i class="bi bi-printer"></i> รายงาน OT
          </button>
          <button class="btn btn-warning btn-sm text-dark fw-bold border" @click="goToDeletePage">
            <i class="bi bi-pencil-square"></i> แก้ไข
          </button>
          <button class="btn btn-purple btn-sm text-white" @click="goToAddPage">
            <i class="bi bi-plus-lg"></i> เพิ่มเวร
          </button>
          <button
            @click="$router.push('/home-backoffice')"
            class="btn btn-light btn-sm fw-bold border text-secondary shadow-sm"
          >
            <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
          </button>
        </div>
      </header>

      <div class="p-3 flex-grow-1 overflow-auto bg-light">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body d-flex flex-column p-0">
            <div
              class="p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
            >
              <div class="btn-group shadow-sm">
                <button class="btn btn-outline-secondary" @click="changeMonth(-1)">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <button
                  class="btn btn-white border px-4 fw-bold text-purple"
                  style="min-width: 200px"
                  disabled
                >
                  {{ months[selectedMonth - 1] }} {{ currentYear }}
                </button>
                <button class="btn btn-outline-secondary" @click="changeMonth(1)">
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>

              <div class="d-flex align-items-center bg-light px-2 py-1 rounded border">
                <label class="me-2 small text-muted text-nowrap">ไปยังเดือน:</label>
                <select
                  class="form-select form-select-sm border-0 bg-transparent"
                  v-model="selectedMonth"
                  @change="handleMonthSelectChange"
                  style="width: auto; cursor: pointer"
                >
                  <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                </select>
              </div>
            </div>

            <div class="calendar-grid-header bg-light border-top border-bottom">
              <div
                v-for="day in weekDays"
                :key="day"
                class="text-center py-2 fw-bold text-secondary small"
              >
                {{ day }}
              </div>
            </div>

            <div class="calendar-grid-body flex-grow-1 bg-light">
              <div
                v-for="(date, index) in calendarDays"
                :key="index"
                class="calendar-cell bg-white border-end border-bottom p-2 position-relative d-flex flex-column"
                :class="{ 'text-muted bg-light-subtle': !date.isCurrentMonth }"
              >
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span
                    class="date-number d-flex align-items-center justify-content-center"
                    :class="{ 'today-circle': date.isToday }"
                  >
                    {{ date.day }}
                  </span>
                </div>

                <div class="shift-container custom-scrollbar flex-grow-1">
                  <div
                    v-for="(staff, sIdx) in getStaffOnDuty(date.day, date.isCurrentMonth)"
                    :key="sIdx"
                    class="badge w-100 text-start fw-normal mb-1 d-flex justify-content-between align-items-center shadow-sm border"
                    :class="getShiftClass(staff.shift)"
                  >
                    <span class="text-truncate" style="max-width: 70%">{{ staff.name }}</span>
                    <span class="fw-bold small opacity-75">{{ staff.shift }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal for Adding Forgotten Time -->
    <div
      v-if="showScanModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      style="background-color: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">เพิ่มเวลาที่ลืมลง</h5>
            <button type="button" class="btn-close" @click="closeScanModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitScanForm">
              <div class="mb-3">
                <label class="form-label">ชื่อเจ้าหน้าที่</label>
                <input
                  type="text"
                  class="form-control mb-2 form-control-sm"
                  placeholder="ค้นหาชื่อ..."
                  v-model="searchQuery"
                />
                <select class="form-select" v-model="scanForm.target_user_id" size="5" required>
                  <option value="" disabled>เลือกเจ้าหน้าที่</option>
                  <option v-for="staff in filteredStaffList" :key="staff.ID" :value="staff.ID">
                    {{ staff.FULLNAME }}
                  </option>
                </select>
                <div class="form-text text-muted" v-if="scanForm.target_user_id">
                  เลือก: {{ getSelectedStaffName }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                  <span>รายการเวลาที่เพิ่ม</span>
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-success"
                    @click="addScanEntry"
                  >
                    <i class="bi bi-plus-lg"></i> เพิ่มแถว
                  </button>
                </label>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto">
                  <table class="table table-bordered table-sm">
                    <thead class="table-light">
                      <tr>
                        <th>วันที่</th>
                        <th>เวลา</th>
                        <th>ประเภท</th>
                        <th style="width: 40px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(entry, index) in scanForm.entries" :key="index">
                        <td>
                          <input
                            type="date"
                            class="form-control form-control-sm"
                            v-model="entry.date"
                            required
                          />
                        </td>
                        <td>
                          <input
                            type="time"
                            class="form-control form-control-sm"
                            v-model="entry.time"
                            required
                          />
                        </td>
                        <td>
                          <select class="form-select form-select-sm" v-model="entry.type" required>
                            <option value="C/In">เข้า</option>
                            <option value="C/Out">ออก</option>
                          </select>
                        </td>
                        <td class="text-center">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-danger border-0"
                            @click="removeScanEntry(index)"
                            v-if="scanForm.entries.length > 1"
                          >
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closeScanModal">
                  ยกเลิก
                </button>
                <button type="submit" class="btn btn-primary">บันทึกทั้งหมด</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Processing Time -->
    <!-- ... (Process Modal logic remains same) ... -->

    <!-- Modal for Checking Time (New) -->
    <div
      v-if="showCheckTimeModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      style="background-color: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header bg-info-subtle text-dark border-0">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-search me-2"></i>ตรวจสอบเวลาสแกนนิ้ว
            </h5>
            <button type="button" class="btn-close" @click="closeCheckTimeModal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2 mb-3">
              <div class="col-12">
                <label class="form-label small fw-bold">เจ้าหน้าที่</label>
                <input
                  type="text"
                  class="form-control form-control-sm mb-2"
                  placeholder="ค้นหาชื่อ..."
                  v-model="checkTimeSearch"
                />
                <select class="form-select" v-model="checkTimeUserId">
                  <option value="" disabled>เลือกเจ้าหน้าที่ (พิมพ์ค้นหาได้ด้านบน)</option>
                  <option
                    v-for="staff in filteredCheckTimeStaffList"
                    :key="staff.ID"
                    :value="staff.ID"
                  >
                    {{ staff.FULLNAME }}
                  </option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold">เดือน</label>
                <select class="form-select" v-model="checkTimeMonth">
                  <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold">ปี</label>
                <select class="form-select" v-model="checkTimeYear">
                  <option
                    v-for="y in [currentYear - 1, currentYear, currentYear + 1]"
                    :key="y"
                    :value="y"
                  >
                    {{ y }}
                  </option>
                </select>
              </div>
              <div class="col-12 text-end">
                <button
                  class="btn btn-info text-white btn-sm"
                  @click="fetchCheckTimeLogs"
                  :disabled="!checkTimeUserId || isCheckTimeLoading"
                >
                  <i class="bi bi-search"></i> ค้นหา
                </button>
              </div>
            </div>

            <hr />

            <div v-if="isCheckTimeLoading" class="text-center py-3">
              <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="checkTimeLogs && checkTimeLogs.length > 0">
              <div class="table-responsive">
                <table class="table table-sm table-striped table-hover small">
                  <thead class="table-light">
                    <tr>
                      <th>วันที่</th>
                      <th style="width: 120px">เวลา</th>
                      <th style="width: 140px">ประเภท</th>
                      <th style="width: 90px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(log, idx) in checkTimeLogs" :key="idx">
                      <td>{{ log.date }}</td>
                      <td>
                        <input
                          type="time"
                          class="form-control form-control-sm"
                          v-model="log.time"
                        />
                      </td>
                      <td>
                        <select class="form-select form-select-sm" v-model="log.type">
                          <option value="C/In">เข้า (C/In)</option>
                          <option value="C/Out">ออก (C/Out)</option>
                        </select>
                      </td>
                      <td>
                        <button
                          class="btn btn-sm btn-outline-success me-1"
                          @click="updateFingerScan(log)"
                          title="บันทึกการแก้ไข"
                        >
                          <i class="bi bi-check-lg"></i>
                        </button>
                        <button
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteFingerScan(log)"
                          title="ลบรายการ"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-center text-muted py-3">- ไม่พบข้อมูล -</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Processing Time -->
    <div
      v-if="showProcessModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      style="background-color: rgba(0, 0, 0, 0.5)"
    >
      <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header bg-warning-subtle text-dark border-0">
            <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>ประมวลผลเวลา</h5>
            <button type="button" class="btn-close" @click="closeProcessModal"></button>
          </div>
          <div class="modal-body p-4">
            <p class="small text-muted mb-3">
              เลือกเดือนและปีที่ต้องการประมวลผล (ข้อมูลเดิมจะถูกล้าง)
            </p>
            <div class="mb-3">
              <label class="form-label fw-bold small">เดือน</label>
              <select class="form-select" v-model="processForm.month">
                <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold small">ปี (ค.ศ.)</label>
              <select class="form-select" v-model="processForm.year">
                <option
                  v-for="y in [currentYear - 1, currentYear, currentYear + 1]"
                  :key="y"
                  :value="y"
                >
                  {{ y }}
                </option>
              </select>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary btn-sm" @click="closeProcessModal">
              ยกเลิก
            </button>
            <button
              type="button"
              class="btn btn-warning btn-sm fw-bold"
              @click="confirmProcessTime"
            >
              ยืนยันประมวลผล
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Working Days Management -->
  <div
    v-if="showWorkingDaysModal"
    class="modal fade show d-block"
    tabindex="-1"
    role="dialog"
    style="background-color: rgba(0, 0, 0, 0.5)"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white border-0">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-calendar-check me-2"></i>จัดการวันทำการ
          </h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            @click="closeWorkingDaysModal"
          ></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold small">เดือน</label>
              <select
                class="form-select"
                v-model="workingDaysForm.month"
                @change="fetchWorkingDays"
              >
                <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">ปี (ค.ศ.)</label>
              <select class="form-select" v-model="workingDaysForm.year" @change="fetchWorkingDays">
                <option
                  v-for="y in [currentYear - 1, currentYear, currentYear + 1]"
                  :key="y"
                  :value="y"
                >
                  {{ y }} ({{ y + 543 }})
                </option>
              </select>
            </div>
          </div>

          <div v-if="isLoadingWorkingDays" class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div v-else>
            <div v-if="workingDaysList.length > 0">
              <h6 class="fw-bold text-success mb-2">
                <i class="bi bi-check-circle-fill me-1"></i> มีข้อมูลวันทำการแล้ว ({{
                  workingDaysList.length
                }}
                วัน)
              </h6>
              <div
                class="border rounded p-2 bg-light custom-scrollbar"
                style="max-height: 200px; overflow-y: auto"
              >
                <div class="d-flex flex-wrap gap-2">
                  <span
                    v-for="(date, idx) in workingDaysList"
                    :key="idx"
                    class="badge bg-white text-dark border shadow-sm d-flex align-items-center gap-2"
                  >
                    {{ formatDate(date.gov_date) }}
                    <button
                      class="btn btn-close btn-close-danger p-1 rounded-circle flex-shrink-0"
                      style="width: 16px; height: 16px; background-size: 8px"
                      @click.stop="deleteWorkingDay(date)"
                      title="ลบวันทำการ"
                    ></button>
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 border rounded bg-light-subtle">
              <i class="bi bi-calendar-x text-muted fs-1 d-block mb-2"></i>
              <p class="text-muted mb-3">ยังไม่มีข้อมูลวันทำการสำหรับเดือนนี้</p>
              <button class="btn btn-primary shadow-sm" @click="generateWorkingDays">
                <i class="bi bi-magic me-2"></i> สร้างวันทำการ (อัตโนมัติ)
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ScheduleViewFull',
  data() {
    return {
      currentTab: 'card',
      selectedMonth: new Date().getMonth() + 1,
      currentYear: new Date().getFullYear(),
      weekDays: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
      months: [
        'มกราคม',
        'กุมภาพันธ์',
        'มีนาคม',
        'เมษายน',
        'พฤษภาคม',
        'มิถุนายน',
        'กรกฎาคม',
        'สิงหาคม',
        'กันยายน',
        'ตุลาคม',
        'พฤศจิกายน',
        'ธันวาคม'
      ],
      rawScheduleData: [],
      contacts: [],
      // For Add Time Modal
      showScanModal: false,
      staffList: [],
      searchQuery: '',
      scanForm: {
        target_user_id: '',
        entries: []
      },
      // Process Modal
      showProcessModal: false,
      processForm: {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear()
      },
      // Check Time Feature
      showCheckTimeModal: false,
      checkTimeUserId: '',
      checkTimeMonth: new Date().getMonth() + 1,
      checkTimeYear: new Date().getFullYear(),
      checkTimeLogs: [],
      isCheckTimeLoading: false,
      workingDaysList: [],
      isLoadingWorkingDays: false,
      showWorkingDaysModal: false,
      workingDaysForm: {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear()
      },
      checkTimeSearch: ''
    };
  },
  computed: {
    filteredStaffList() {
      if (!this.searchQuery) return this.staffList;
      const lower = this.searchQuery.toLowerCase();
      return this.staffList.filter((s) => s.FULLNAME.toLowerCase().includes(lower));
    },
    filteredCheckTimeStaffList() {
      if (!this.checkTimeSearch) return this.staffList;
      const lower = this.checkTimeSearch.toLowerCase();
      return this.staffList.filter((s) => s.FULLNAME.toLowerCase().includes(lower));
    },
    getSelectedStaffName() {
      const found = this.staffList.find((s) => s.ID === this.scanForm.target_user_id);
      return found ? found.FULLNAME : '';
    },
    currentTabName() {
      if (this.currentTab === 'it') return 'IT Support';
      if (this.currentTab === 'claim') return 'งานประกัน/เบิกเครม';
      return 'ห้องบัตร';
    },
    calendarDays() {
      const year = this.currentYear;
      const monthIndex = this.selectedMonth - 1;
      const firstDayOfMonth = new Date(year, monthIndex, 1);
      const lastDayOfMonth = new Date(year, monthIndex + 1, 0);
      const daysInMonth = lastDayOfMonth.getDate();
      const startDayOfWeek = firstDayOfMonth.getDay();

      const days = [];
      const prevMonthLastDay = new Date(year, monthIndex, 0).getDate();
      for (let i = startDayOfWeek - 1; i >= 0; i--) {
        days.push({ day: prevMonthLastDay - i, isCurrentMonth: false, isToday: false });
      }

      const today = new Date();
      const isCurrentMonthActual = today.getMonth() === monthIndex && today.getFullYear() === year;
      for (let i = 1; i <= daysInMonth; i++) {
        days.push({
          day: i,
          isCurrentMonth: true,
          isToday: isCurrentMonthActual && i === today.getDate()
        });
      }

      const remainingCells = 42 - days.length;
      for (let i = 1; i <= remainingCells; i++) {
        days.push({ day: i, isCurrentMonth: false, isToday: false });
      }
      return days;
    }
  },

  watch: {
    filteredCheckTimeStaffList(newVal) {
      if (newVal.length === 1) {
        this.checkTimeUserId = newVal[0].ID;
      }
    }
  },
  methods: {
    // --- ฟังก์ชัน Logout ---
    goHome() {
      this.$router.push('/home-backoffice');
    },
    logout() {
      if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
        localStorage.removeItem('user_token'); // ล้าง Token
        this.$router.push('/login'); // กลับหน้า Login
      }
    },

    switchTab(tabName) {
      if (this.currentTab === tabName) return;
      this.currentTab = tabName;
      this.rawScheduleData = [];
      this.contacts = [];
      this.fetchSchedule();
    },

    // --- ใช้ Axios เพื่อดึงข้อมูล ---
    async fetchSchedule() {
      try {
        let apiUrl = '';
        if (this.currentTab === 'card') {
          apiUrl = `/api-digital/duties_opdcard/get-schedule-opdcard.php`;
        } else if (this.currentTab === 'it') {
          apiUrl = `/api-digital/duties/get-schedule.php`;
        } else if (this.currentTab === 'claim') {
          apiUrl = `/api-digital/duties_claim/get-schedule-claim.php`;
        }

        const token = localStorage.getItem('user_token');

        const response = await axios.get(apiUrl, {
          params: {
            year: this.currentYear,
            month: this.selectedMonth
          },
          headers: {
            Authorization: `Bearer ${token}` // ส่ง Token เพื่อ Verify
          }
        });

        if (response.data && response.data.data) {
          this.rawScheduleData = response.data.data;
          this.contacts = this.extractContacts(response.data.data);
        } else {
          this.rawScheduleData = [];
          this.contacts = [];
        }
      } catch (error) {
        console.error('Fetch Error:', error);
        // ถ้า Unauthorized (401) ให้ Logout ทันที
        if (error.response && error.response.status === 401) {
          this.logout();
        }
      }
    },

    getStaffOnDuty(day, isCurrentMonth) {
      if (!isCurrentMonth) return [];
      const dutyList = [];
      const dayKey = `d${day}`;

      if (this.rawScheduleData) {
        this.rawScheduleData.forEach((person) => {
          const shift = person[dayKey];
          if (shift && shift.trim() !== '') {
            dutyList.push({ name: person.name, shift: shift });
          }
        });
      }
      return dutyList;
    },

    extractContacts(data) {
      return data
        .filter((p) => p.phone)
        .map((person) => ({ name: person.name, phone: person.phone }));
    },

    changeMonth(offset) {
      let newMonth = this.selectedMonth + offset;
      if (newMonth > 12) {
        newMonth = 1;
        this.currentYear++;
      } else if (newMonth < 1) {
        newMonth = 12;
        this.currentYear--;
      }
      this.selectedMonth = newMonth;
      this.fetchSchedule();
    },

    handleMonthSelectChange() {
      this.fetchSchedule();
    },

    goToAddPage() {
      if (this.currentTab === 'it') {
        this.$router.push({ path: '/from-duty' });
      } else if (this.currentTab === 'claim') {
        this.$router.push({ path: '/from-dutyclaim' });
      } else {
        this.$router.push({ path: '/from-dutyopdcard' });
      }
    },
    goToDeletePage() {
      if (this.currentTab === 'it') {
        this.$router.push({ path: '/manager_duties_it' });
      } else if (this.currentTab === 'claim') {
        this.$router.push({ path: '/manager_duties_claim' });
      } else {
        this.$router.push({ path: '/manager_duties_opdcard' });
      }
    },

    getShiftClass(shiftCode) {
      if (!shiftCode) return 'text-bg-light';
      const code = shiftCode.toLowerCase();
      if (code.includes('ช') || code.includes('m') || code.includes('8'))
        return 'text-bg-primary bg-gradient';
      if (code.includes('บ') || code.includes('e') || code.includes('16'))
        return 'text-bg-warning text-dark bg-gradient';
      if (code.includes('ด') || code.includes('n') || code.includes('0'))
        return 'text-bg-dark bg-gradient';
      return 'text-bg-light border text-dark';
    },

    // --- Add Forgotten Time Feature ---
    async openScanModal() {
      if (this.staffList.length === 0) {
        await this.fetchStaffList();
      }
      this.showScanModal = true;
      this.searchQuery = '';
      this.scanForm.entries = [];
      this.addScanEntry(); // Add first default row
    },
    closeScanModal() {
      this.showScanModal = false;
      this.searchQuery = '';
      this.scanForm.target_user_id = '';
      this.scanForm.entries = [];
    },
    addScanEntry() {
      const now = new Date();
      const dateStr = now.toISOString().split('T')[0];
      const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
      this.scanForm.entries.push({
        date: dateStr,
        time: timeStr,
        type: 'C/In'
      });
    },
    removeScanEntry(index) {
      this.scanForm.entries.splice(index, 1);
    },
    async fetchStaffList() {
      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.get('/api-hosoffice/get_authorized_staff.php', {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (response.data.status === 'success') {
          this.staffList = response.data.data;
        } else {
          alert('ไม่สามารถดึงรายชื่อเจ้าหน้าที่ได้: ' + response.data.message);
        }
      } catch (error) {
        console.error('Fetch Staff Error:', error);
        alert('เกิดข้อผิดพลาดในการดึงรายชื่อ');
      }
    },
    async submitScanForm() {
      if (!this.scanForm.target_user_id || this.scanForm.entries.length === 0) {
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return;
      }

      // Prepare payload
      const entriesPayload = this.scanForm.entries.map((e) => ({
        datetime: `${e.date} ${e.time}:00`,
        type: e.type
      }));

      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-hosoffice/add_fingerscan_manual.php',
          {
            target_user_id: this.scanForm.target_user_id,
            entries: entriesPayload
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (response.data.status === 'success') {
          alert(`เพิ่มข้อมูลเรียบร้อยแล้ว (${response.data.message})`);
          this.closeScanModal();
        } else {
          alert('Error: ' + response.data.message);
        }
      } catch (error) {
        console.error('Submit Error:', error);
        alert('เกิดข้อผิดพลาดในการบันทึก');
      }
    },
    processTime() {
      // Open Modal instead of direct confirm
      this.processForm.month = this.selectedMonth; // Default to current view
      this.processForm.year = this.currentYear;
      this.showProcessModal = true;
    },
    closeProcessModal() {
      this.showProcessModal = false;
    },
    async confirmProcessTime() {
      if (
        !confirm(
          `ยืนยันการประมวลผลเวลาประจำเดือน ${this.months[this.processForm.month - 1]} ${this.processForm.year}?\nข้อมูลเก่าในระบบสำหรับเดือนนี้จะถูกล้างและคำนวณใหม่`
        )
      ) {
        return;
      }

      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-hosoffice/process_finger_scan.php',
          {
            year: this.processForm.year,
            month: this.processForm.month
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (response.data.status === 'success') {
          alert('ประมวลผลเสร็จสิ้น');
          this.closeProcessModal();
          // if processed month == view month, refresh
          if (
            this.processForm.month === this.selectedMonth &&
            this.processForm.year === this.currentYear
          ) {
            this.fetchSchedule();
          }
        } else {
          alert('เกิดข้อผิดพลาด: ' + response.data.message);
        }
      } catch (error) {
        console.error('Process Error', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      }
    },

    // --- Check Time Feature ---
    async openCheckTimeModal() {
      if (this.staffList.length === 0) {
        await this.fetchStaffList();
      }
      this.checkTimeUserId = '';
      this.checkTimeLogs = [];
      this.showCheckTimeModal = true;
    },
    closeCheckTimeModal() {
      this.showCheckTimeModal = false;
      this.checkTimeLogs = [];
    },
    async fetchCheckTimeLogs() {
      if (!this.checkTimeUserId) return;

      this.isCheckTimeLoading = true;
      this.checkTimeLogs = [];

      try {
        const token = localStorage.getItem('user_token');
        // Calculate start and end date of selected month
        const startDate = `${this.checkTimeYear}-${String(this.checkTimeMonth).padStart(2, '0')}-01`;
        const lastDay = new Date(this.checkTimeYear, this.checkTimeMonth, 0).getDate();
        const endDate = `${this.checkTimeYear}-${String(this.checkTimeMonth).padStart(2, '0')}-${lastDay}`;

        const response = await axios.get('/api-hosoffice/get_finger_scan.php', {
          params: {
            user_id: this.checkTimeUserId,
            start_date: startDate,
            end_date: endDate
          },
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.data.status === 'success') {
          this.checkTimeLogs = response.data.data;
        } else {
          alert('ไม่สามารถดึงข้อมูลได้: ' + response.data.message);
        }
      } catch (error) {
        console.error('Check Time Error', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      } finally {
        this.isCheckTimeLoading = false;
      }
    },
    async updateFingerScan(log) {
      if (!confirm(`ยืนยันการแก้ไขข้อมูล ${log.date} เวลา ${log.time} (${log.type})?`)) return;

      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-hosoffice/update_finger_scan.php',
          {
            id: log.id,
            time: log.time,
            type: log.type,
            date: log.date
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (response.data.status === 'success') {
          alert('บันทึกข้อมูลสำเร็จ');
        } else {
          alert('เกิดข้อผิดพลาด: ' + response.data.message);
        }
      } catch (error) {
        console.error('Update Error', error);
        alert('เกิดข้อผิดพลาดในการบันทึก');
      }
    },
    async deleteFingerScan(log) {
      if (!confirm(`ยืนยันการลบรายการ ${log.date} เวลา ${log.time} หรือไม่?`)) return;

      try {
        const token = localStorage.getItem('user_token');
        const response = await axios.post(
          '/api-hosoffice/delete_finger_scan.php',
          {
            id: log.id
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (response.data.status === 'success') {
          this.checkTimeLogs = this.checkTimeLogs.filter((l) => l.id !== log.id);
          alert('ลบข้อมูลสำเร็จ');
        } else {
          alert('เกิดข้อผิดพลาด: ' + response.data.message);
        }
      } catch (error) {
        console.error('Delete Error', error);
        alert('เกิดข้อผิดพลาดในการลบ');
      }
    },

    // --- Working Days Management ---
    openWorkingDaysModal() {
      this.showWorkingDaysModal = true;
      this.fetchWorkingDays();
    },
    closeWorkingDaysModal() {
      this.showWorkingDaysModal = false;
    },
    formatDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }); // 1 ก.ย. 66
    },
    async fetchWorkingDays() {
      this.isLoadingWorkingDays = true;
      this.workingDaysList = [];
      try {
        const token = localStorage.getItem('user_token');
        const res = await axios.get('/api-digital/schedule/get_government_dates.php', {
          params: {
            month: this.workingDaysForm.month,
            year: this.workingDaysForm.year
          },
          headers: { Authorization: `Bearer ${token}` }
        });
        if (res.data.status === 'success') {
          this.workingDaysList = res.data.data;
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoadingWorkingDays = false;
      }
    },
    async generateWorkingDays() {
      if (
        !confirm(
          `ยืนยันการสร้างข้อมูลวันทำการสำหรับเดือน ${this.months[this.workingDaysForm.month - 1]} ${this.workingDaysForm.year}?`
        )
      )
        return;

      this.isLoadingWorkingDays = true;
      try {
        const token = localStorage.getItem('user_token');
        const res = await axios.post(
          '/api-digital/schedule/generate_government_dates.php',
          {
            month: this.workingDaysForm.month,
            year: this.workingDaysForm.year
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (res.data.status === 'success') {
          alert(res.data.message);
          this.fetchWorkingDays();
        } else {
          alert('Failed: ' + res.data.message);
        }
      } catch (e) {
        console.error(e);
        alert('Error generating dates');
      } finally {
        this.isLoadingWorkingDays = false;
      }
    },
    async deleteWorkingDay(dateObj) {
      if (!confirm(`ยืนยันการลบวันที่ ${this.formatDate(dateObj.gov_date)} ออกจากวันทำการ?`))
        return;

      try {
        const token = localStorage.getItem('user_token');
        const res = await axios.post(
          '/api-digital/schedule/delete_government_date.php',
          {
            id: dateObj.gov_id
          },
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );

        if (res.data.status === 'success') {
          this.workingDaysList = this.workingDaysList.filter((d) => d.gov_id !== dateObj.gov_id);
        } else {
          alert('Failed: ' + res.data.message);
        }
      } catch (e) {
        console.error(e);
        alert('Error deleting date');
      }
    }
  },

  async mounted() {
    try {
      const response = await axios.get('/api-hosoffice/get_user_profile.php');
      if (response.data.status === 'success') {
        const dept = response.data.department || '';
        // Check if department contains allowed keywords
        const allowedDepts = [
          'สุขภาพดิจิทัล',
          'กลุ่มงานประกันสุขภาพ ยุทธศาสตร์' // Explicitly added per error message
        ];
        const hasAccess = allowedDepts.some((d) => dept.includes(d));

        if (!hasAccess) {
          alert(`คุณไม่มีสิทธิ์เข้าถึงหน้านี้ \n(หน่วยงาน: "${dept}")\n(Length: ${dept.length})`);
          this.$router.push('/home-backoffice');
          return;
        }
      } else {
        alert('ไม่สามารถตรวจสอบสิทธิ์ได้');
        this.$router.push('/home-backoffice');
        return;
      }
    } catch (error) {
      console.error(error);
      alert('เกิดข้อผิดพลาดในการตรวจสอบสิทธิ์');
      this.$router.push('/home-backoffice');
      return;
    }

    this.fetchSchedule();
  }
};
</script>

<style scoped>
/* --- Premium Layout & Typography --- */
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');

.container-fluid {
  font-family: 'Sarabun', sans-serif;
  background-color: #f8f9fa;
}

/* --- Sidebar --- */
aside {
  background: white;
  transition: all 0.3s ease;
}

.nav-pills .nav-link {
  border-radius: 50px; /* Pill shape for premium feel */
  padding: 10px 20px;
  font-weight: 500;
  transition: all 0.2s ease-in-out;
  color: #6c757d;
  margin-bottom: 4px;
}

.nav-pills .nav-link:hover:not(.active) {
  background-color: #f3e5f5; /* Light purple tint */
  color: #6f42c1;
  transform: translateX(5px);
}

.nav-pills .nav-link.active {
  background: linear-gradient(135deg, #6f42c1 0%, #8e24aa 100%);
  box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3);
  color: white !important;
}

/* --- Contacts List --- */
.contacts-section h6 {
  letter-spacing: 0.5px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #adb5bd !important;
}
.list-group-item {
  border: none;
  border-radius: 8px !important;
  margin-bottom: 4px;
  transition: background-color 0.2s;
}
.list-group-item:hover {
  background-color: #f8f9fa;
}

/* --- Header --- */
header {
  background-color: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px);
}

/* --- Buttons --- */
.btn {
  border-radius: 50px; /* Fully rounded buttons */
  font-weight: 500;
  transition: all 0.2s;
}
.btn:active {
  transform: scale(0.98);
}
.btn-purple {
  background: linear-gradient(135deg, #6f42c1 0%, #5e35b1 100%);
  border: none;
  box-shadow: 0 4px 6px rgba(111, 66, 193, 0.25);
}
.btn-purple:hover {
  background: linear-gradient(135deg, #5e35b1 0%, #4527a0 100%);
  box-shadow: 0 6px 12px rgba(111, 66, 193, 0.35);
  transform: translateY(-1px);
}
.btn-warning {
  background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
  border: none;
  color: #000;
  box-shadow: 0 4px 6px rgba(255, 193, 7, 0.3);
}
.btn-warning:hover {
  background: linear-gradient(135deg, #ffb300 0%, #ffa000 100%);
  transform: translateY(-1px);
}

/* --- Calendar Grid --- */
.calendar-grid-header,
.calendar-grid-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.calendar-grid-header div {
  text-transform: uppercase;
  letter-spacing: 1px;
  font-size: 0.85rem;
  color: #6c757d;
}

.calendar-cell {
  min-height: 120px;
  background-color: white;
  transition: all 0.2s ease;
  border-color: #f1f3f5 !important;
}

.calendar-cell:hover {
  background-color: #fafafa;
  z-index: 5;
}

.today-circle {
  width: 28px;
  height: 28px;
  background: linear-gradient(135deg, #6f42c1 0%, #8e24aa 100%);
  color: white;
  border-radius: 50%;
  font-weight: bold;
  box-shadow: 0 3px 6px rgba(111, 66, 193, 0.4);
}

/* --- Shifts --- */
.shift-container {
  max-height: 90px;
  overflow-y: auto;
}
.badge {
  padding: 6px 10px;
  border-radius: 6px;
  font-weight: 500;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: none !important;
  margin-bottom: 2px !important;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #d1c4e9;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #b39ddb;
}

@media (max-width: 768px) {
  .calendar-cell {
    min-height: 80px;
    padding: 0.25rem !important;
  }
}
</style>
```
