<template>
  <div class="schedule-page py-4 px-3 px-md-4">
    <div class="schedule-wrapper mx-auto">
      <!-- 1. Hospital Header Card -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <!-- Logo & Hospital Title -->
            <div class="d-flex align-items-center gap-3">
              <div class="logo-wrapper bg-light rounded-4 p-2 border flex-shrink-0">
                <img src="../../components/img/logo_cnmh.png" alt="Logo" class="hospital-logo" />
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h4 class="fw-bold text-dark mb-0">โรงพยาบาลชานุมาน</h4>
                  <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-pc-display me-1"></i> ตารางเวร IT Support
                  </span>
                </div>
                <p class="text-muted small mb-0 mt-1">
                  วิสัยทัศน์ : โรงพยาบาลชุมชน คุณภาพชั้นนำ แห่งลุ่มน้ำโขง ในดวงใจประชาชน
                </p>
              </div>
            </div>

            <!-- Controls: Month, Year, Manage Link -->
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="input-group shadow-xs rounded-pill overflow-hidden border" style="width: auto;">
                <span class="input-group-text bg-white border-0 text-purple ps-3">
                  <i class="bi bi-calendar3"></i>
                </span>
                <select
                  v-model="selectedMonth"
                  @change="fetchSchedule"
                  class="form-select border-0 fw-semibold text-dark"
                  style="width: auto; flex: 0 0 auto;"
                  aria-label="เลือกเดือน"
                >
                  <option v-for="(month, index) in months" :key="index" :value="index + 1">
                    {{ month }}
                  </option>
                </select>
                <select
                  v-model="selectedYear"
                  @change="fetchSchedule"
                  class="form-select border-0 border-start fw-semibold text-dark"
                  style="width: auto; flex: 0 0 auto;"
                  aria-label="เลือกปี"
                >
                  <option v-for="y in yearOptions" :key="y" :value="y">
                    พ.ศ. {{ y + 543 }}
                  </option>
                </select>
              </div>

              <router-link
                to="/manager-schedule"
                class="btn btn-purple rounded-pill px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-2"
              >
                <i class="bi bi-calendar2-week-fill"></i>
                <span>จัดการเวร IT</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Today's On-Call IT Highlight Banner -->
      <div class="oncall-banner rounded-4 shadow-sm p-3 p-md-4 mb-4 text-white position-relative overflow-hidden">
        <div class="banner-decor-circle"></div>
        <div class="position-relative z-1 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="oncall-icon-box rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
              <i class="bi bi-headset fs-2 text-white"></i>
            </div>
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-white text-purple rounded-pill px-2 py-1 fw-bold" style="font-size: 0.7rem;">
                  <i class="bi bi-broadcast me-1"></i> เวร IT ประจำวันนี้
                </span>
                <span class="small text-white-75">{{ todayThaiLabel }}</span>
              </div>
              <div v-if="loading" class="fw-bold fs-5">กำลังตรวจสอบข้อมูลเวรวันนี้...</div>
              <div v-else-if="todayDutyPeople.length > 0" class="d-flex flex-wrap align-items-center gap-3">
                <div v-for="p in todayDutyPeople" :key="p.name" class="d-flex align-items-center gap-2">
                  <span class="fw-bold fs-4">{{ p.name }}</span>
                  <span v-if="p.duty" class="badge bg-white bg-opacity-25 text-white rounded-pill px-2 py-1">
                    {{ p.duty }}
                  </span>
                </div>
              </div>
              <div v-else class="fw-semibold fs-6 text-white-75">
                ยังไม่มีรายชื่อผู้เข้าเวรในวันนี้ — สามารถติดต่อทีม IT ด้านล่างได้
              </div>
            </div>
          </div>

          <div v-if="!loading && todayDutyPeople.length > 0" class="d-flex flex-wrap gap-2">
            <template v-for="p in todayDutyPeople" :key="'call-' + p.name">
              <a
                v-if="p.phone"
                :href="`tel:${cleanPhone(p.phone)}`"
                class="btn btn-light text-purple fw-bold rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-2"
              >
                <i class="bi bi-telephone-fill"></i>
                <span>{{ p.phone }}</span>
              </a>
              <button
                v-if="p.phone"
                type="button"
                class="btn btn-outline-light rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1"
                @click="copyPhone(p.phone, p.name)"
              >
                <i :class="copiedPhone === p.phone ? 'bi bi-check2-all' : 'bi bi-copy'"></i>
                <span>{{ copiedPhone === p.phone ? 'คัดลอกแล้ว' : 'คัดลอกเบอร์' }}</span>
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- 3. Monthly Schedule Table Card -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold text-dark mb-0">
              ตารางเวรประจำเดือน {{ months[selectedMonth - 1] }} {{ selectedYear + 543 }}
            </h5>
          </div>
          <div class="d-flex align-items-center gap-3 small text-muted flex-wrap">
            <span class="d-inline-flex align-items-center gap-1">
              <span class="legend-dot bg-today"></span> วันนี้
            </span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="legend-dot bg-weekend"></span> เสาร์–อาทิตย์
            </span>
            <span class="d-inline-flex align-items-center gap-1">
              <span class="badge bg-purple-subtle text-purple rounded-pill px-2">IT</span> มีเวร
            </span>
          </div>
        </div>

        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-purple" role="status"></div>
            <div class="mt-2 text-muted small fw-semibold">กำลังโหลดตารางเวร...</div>
          </div>

          <div v-else-if="schedule.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
            <div class="fw-bold">ไม่พบข้อมูลตารางเวรในเดือนนี้</div>
          </div>

          <div v-else class="table-responsive">
            <table class="table schedule-table align-middle mb-0">
              <thead>
                <tr>
                  <th class="sticky-col text-start ps-4">ชื่อเจ้าหน้าที่</th>
                  <th
                    v-for="day in daysInMonth"
                    :key="day"
                    class="day-header text-center"
                    :class="{
                      'is-today-col': isToday(day),
                      'is-weekend-col': isWeekend(day) && !isToday(day)
                    }"
                  >
                    <div class="day-num">{{ day }}</div>
                    <div class="day-name">{{ getThaiWeekdayShort(day) }}</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(person, index) in schedule" :key="index" class="schedule-row">
                  <td class="sticky-col text-start ps-4 fw-semibold text-dark">
                    <div class="d-flex align-items-center gap-2">
                      <span class="mini-avatar">{{ getInitials(person.name) }}</span>
                      <div>
                        <div class="text-nowrap">{{ person.name }}</div>
                        <small v-if="person.phone" class="text-muted fw-normal font-monospace">
                          <i class="bi bi-telephone me-1"></i>{{ person.phone }}
                        </small>
                      </div>
                    </div>
                  </td>
                  <td
                    v-for="day in daysInMonth"
                    :key="day"
                    class="text-center day-cell"
                    :class="{
                      'is-today-col': isToday(day),
                      'is-weekend-col': isWeekend(day) && !isToday(day)
                    }"
                  >
                    <span
                      v-if="person.duties[day]"
                      class="duty-pill"
                      :class="{ 'duty-pill-today': isToday(day) }"
                    >
                      {{ person.duties[day] }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ScheduleView',
  data() {
    const now = new Date();
    const currentYear = now.getFullYear();
    return {
      selectedMonth: now.getMonth() + 1,
      selectedYear: currentYear,
      yearOptions: [currentYear - 1, currentYear, currentYear + 1],
      daysInMonth: [],
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
      weekdaysShort: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
      weekdaysFull: ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'],
      schedule: [],
      contacts: [],
      todaySchedule: [],
      loading: false,
      copiedPhone: ''
    };
  },
  computed: {
    todayThaiLabel() {
      const now = new Date();
      const wd = this.weekdaysFull[now.getDay()];
      const d = now.getDate();
      const m = this.months[now.getMonth()];
      const y = now.getFullYear() + 543;
      return `วัน${wd}ที่ ${d} ${m} พ.ศ. ${y}`;
    },
    todayDutyPeople() {
      const now = new Date();
      const d = now.getDate();
      const source = this.todaySchedule.length > 0 ? this.todaySchedule : [];
      return source
        .filter((p) => p.duties && p.duties[d] && String(p.duties[d]).trim() !== '')
        .map((p) => ({
          name: p.name,
          duty: p.duties[d],
          phone: p.phone || ''
        }));
    }
  },
  methods: {
    async fetchSchedule() {
      this.loading = true;
      try {
        const response = await fetch(
          `/api-digital/duties/get-schedule.php?year=${this.selectedYear}&month=${this.selectedMonth}`
        );
        const data = await response.json();

        if (data && data.data) {
          this.schedule = data.data.map((person) => ({
            name: person.name,
            phone: person.phone,
            duties: this.formatDuties(person)
          }));
          this.contacts = this.extractContacts(data.data);
          this.updateDays();

          const now = new Date();
          if (this.selectedYear === now.getFullYear() && this.selectedMonth === now.getMonth() + 1) {
            this.todaySchedule = this.schedule;
          }
        } else {
          this.schedule = [];
          this.contacts = [];
          this.updateDays();
        }
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchTodayScheduleIfNeeded() {
      const now = new Date();
      try {
        const response = await fetch(
          `/api-digital/duties/get-schedule.php?year=${now.getFullYear()}&month=${now.getMonth() + 1}`
        );
        const data = await response.json();
        if (data && data.data) {
          this.todaySchedule = data.data.map((person) => ({
            name: person.name,
            phone: person.phone,
            duties: this.formatDuties(person)
          }));
        }
      } catch (e) {
        // ignore
      }
    },
    formatDuties(person) {
      const duties = {};
      for (let i = 1; i <= 31; i++) {
        duties[i] = person[`d${i}`] || '';
      }
      return duties;
    },
    extractContacts(data) {
      return data.map((person) => ({
        name: person.name,
        phone: person.phone
      }));
    },
    updateDays() {
      const days = new Date(this.selectedYear, this.selectedMonth, 0).getDate();
      this.daysInMonth = Array.from({ length: days }, (_, i) => i + 1);
    },
    isToday(day) {
      const now = new Date();
      return (
        this.selectedYear === now.getFullYear() &&
        this.selectedMonth === now.getMonth() + 1 &&
        day === now.getDate()
      );
    },
    isWeekend(day) {
      const wd = new Date(this.selectedYear, this.selectedMonth - 1, day).getDay();
      return wd === 0 || wd === 6;
    },
    getThaiWeekdayShort(day) {
      const wd = new Date(this.selectedYear, this.selectedMonth - 1, day).getDay();
      return this.weekdaysShort[wd];
    },
    countPersonDuties(person) {
      return this.daysInMonth.filter((d) => person.duties[d] && String(person.duties[d]).trim() !== '').length;
    },
    isPersonOnDutyToday(name) {
      return this.todayDutyPeople.some((p) => p.name === name);
    },
    getInitials(name) {
      const clean = String(name || '')
        .replace(/^(นาย|นางสาว|นาง|น\.ส\.|ดร\.)\s*/u, '')
        .trim();
      return clean ? clean.charAt(0) : 'IT';
    },
    cleanPhone(phone) {
      return String(phone || '').replace(/[^0-9+]/g, '');
    },
    async copyPhone(phone) {
      try {
        await navigator.clipboard.writeText(String(phone || ''));
        this.copiedPhone = phone;
        setTimeout(() => {
          if (this.copiedPhone === phone) this.copiedPhone = '';
        }, 2000);
      } catch (e) {
        // ignore clipboard error
      }
    }
  },
  mounted() {
    this.fetchSchedule();
    this.fetchTodayScheduleIfNeeded();
  }
};
</script>

<style scoped>
.schedule-page {
  background-color: #f3f4f6;
  min-height: calc(100vh - 120px);
}

.schedule-wrapper {
  max-width: 1440px;
}

.logo-wrapper {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hospital-logo {
  width: 48px;
  height: 48px;
  object-fit: contain;
}

.text-purple {
  color: #6b21a8 !important;
}

.bg-purple {
  background-color: #6b21a8 !important;
}

.bg-purple-subtle {
  background-color: rgba(107, 33, 168, 0.1) !important;
}

.border-purple-subtle {
  border-color: rgba(107, 33, 168, 0.25) !important;
}

.btn-purple {
  background: linear-gradient(135deg, #6b21a8 0%, #4c1d95 100%);
  color: #ffffff;
  border: none;
  transition: all 0.2s ease;
}

.btn-purple:hover {
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(107, 33, 168, 0.25) !important;
}

/* On-Call Banner */
.oncall-banner {
  background: linear-gradient(135deg, #581c87 0%, #6d28d9 55%, #3b82f6 100%);
}

.banner-decor-circle {
  position: absolute;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  right: -60px;
  top: -80px;
}

.oncall-icon-box {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(4px);
}

.text-white-75 {
  color: rgba(255, 255, 255, 0.85);
}

/* Schedule Table */
.schedule-table {
  font-size: 0.85rem;
  border-collapse: separate;
  border-spacing: 0;
}

.schedule-table thead th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
  border-right: 1px solid #f1f5f9;
  padding: 0.65rem 0.35rem;
  vertical-align: middle;
}

.schedule-table tbody td {
  border-bottom: 1px solid #f1f5f9;
  border-right: 1px solid #f1f5f9;
  padding: 0.65rem 0.25rem;
}

.schedule-row:hover td {
  background-color: #f8fafc;
}

.sticky-col {
  position: sticky;
  left: 0;
  z-index: 5;
  background-color: #ffffff;
  min-width: 195px;
  border-right: 2px solid #e2e8f0 !important;
}

.schedule-table thead th.sticky-col {
  background-color: #f8fafc;
  z-index: 6;
}

.schedule-row:hover td.sticky-col {
  background-color: #f8fafc;
}

.mini-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(107, 33, 168, 0.1);
  color: #6b21a8;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.day-header {
  min-width: 38px;
}

.day-num {
  font-size: 0.85rem;
  line-height: 1.1;
}

.day-name {
  font-size: 0.65rem;
  font-weight: 500;
  color: #94a3b8;
  margin-top: 2px;
}

.is-weekend-col {
  background-color: #e2e8f0 !important;
}

.schedule-table thead th.is-weekend-col {
  background-color: #fee2e2 !important;
}

.schedule-table thead th.is-weekend-col .day-num,
.schedule-table thead th.is-weekend-col .day-name {
  color: #e11d48;
}

.is-today-col {
  background-color: #eef2ff !important;
}

.schedule-table thead th.is-today-col {
  background-color: #4f46e5 !important;
  color: #ffffff !important;
}

.schedule-table thead th.is-today-col .day-name {
  color: #e0e7ff !important;
}

.duty-pill {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 999px;
  background-color: rgba(107, 33, 168, 0.12);
  color: #6b21a8;
  font-weight: 700;
  font-size: 0.75rem;
  line-height: 1.3;
}

.duty-pill-today {
  background-color: #4f46e5;
  color: #ffffff;
  box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
}

.total-col {
  min-width: 55px;
  background-color: #fcfcfd;
  border-left: 1px solid #e2e8f0;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  display: inline-block;
}

.bg-today {
  background-color: #4f46e5;
}

.bg-weekend {
  background-color: #cbd5e1;
  border: 1px solid #94a3b8;
}

/* Contact Cards */
.contact-card {
  border: 1px solid #e5e7eb !important;
  transition: all 0.2s ease;
}

.contact-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px -8px rgba(15, 23, 42, 0.12) !important;
  border-color: rgba(107, 33, 168, 0.35) !important;
}

.contact-oncall {
  border: 1.5px solid #16a34a !important;
  background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 40%) !important;
}

.contact-avatar {
  width: 44px;
  height: 44px;
  font-size: 1.05rem;
}
</style>
