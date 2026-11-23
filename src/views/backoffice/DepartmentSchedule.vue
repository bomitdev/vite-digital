<template>
  <div class="dept-schedule-page container-fluid py-4 px-3 px-lg-4">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 print-hide">
      <div class="card-body p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="header-icon-box">
              <i class="bi bi-calendar2-week-fill"></i>
            </div>
            <div>
              <h4 class="fw-bold mb-0 text-dark">ตารางเวรแต่ละกลุ่มงาน</h4>
              <small class="text-muted"
                >สร้างและจัดการตารางเวรแยกตามกลุ่มงาน พร้อมส่งออก Excel / พิมพ์ตารางเวร</small
              >
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-2 print-hide">
            <button
              v-if="isSystemAdmin || canManageGroup"
              type="button"
              class="btn btn-sm rounded-pill px-3 fw-semibold"
              :class="viewMode === 'settings' ? 'btn-dark' : 'btn-outline-dark'"
              @click="viewMode = viewMode === 'settings' ? 'schedule' : 'settings'"
            >
              <i
                class="bi me-1"
                :class="viewMode === 'settings' ? 'bi-calendar3' : 'bi-list-ul'"
              ></i>
              {{ viewMode === 'settings' ? 'กลับหน้าตารางเวร' : 'รายชื่อกลุ่มงานทั้งหมด' }}
            </button>
            <button
              v-if="isSystemAdmin"
              type="button"
              class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold shadow-sm"
              @click="openGroupModal()"
            >
              <i class="bi bi-plus-circle me-1"></i> สร้างตารางเวรกลุ่มงาน
            </button>
            <button
              v-if="selectedGroup && canManageGroup"
              type="button"
              class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold"
              @click="openPermissionModal"
            >
              <i class="bi bi-shield-lock me-1"></i> ตั้งค่าสิทธิ์เข้าใช้
            </button>
            <button
              v-if="selectedGroup && canManageGroup"
              type="button"
              class="btn btn-outline-secondary btn-sm rounded-pill px-3"
              @click="openGroupModal(selectedGroup)"
            >
              <i class="bi bi-sliders me-1"></i> ตั้งค่าเวร/กลุ่มงาน
            </button>
            <button
              type="button"
              class="btn btn-light border btn-sm rounded-pill px-3 text-secondary"
              @click="$router.push('/home-backoffice')"
            >
              <i class="bi bi-house-fill me-1"></i> กลับหน้าเมนู
            </button>
          </div>
        </div>

        <!-- Filter & Controls Bar -->
        <div
          v-if="viewMode === 'schedule'"
          class="row g-2 align-items-end mt-3 pt-3 border-top print-hide"
        >
          <div class="col-12 col-md-4">
            <label class="form-label small fw-bold text-secondary mb-1">
              กลุ่มงาน / ตารางเวร
              <span v-if="groups.length > 0" class="text-primary fw-normal"
                >({{ groups.length }} กลุ่มงาน)</span
              >
            </label>
            <select v-model="selectedGroupId" class="form-select" @change="fetchSchedule">
              <option value="" disabled>-- เลือกกลุ่มงาน --</option>
              <option v-for="g in groups" :key="g.id" :value="g.id">
                {{ g.title }} ({{ g.department_name }})
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">เดือน</label>
            <select v-model.number="selectedMonth" class="form-select" @change="fetchSchedule">
              <option v-for="(m, idx) in months" :key="idx" :value="idx + 1">{{ m }}</option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">ปี พ.ศ.</label>
            <select v-model.number="selectedYear" class="form-select" @change="fetchSchedule">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y + 543 }}</option>
            </select>
          </div>

          <div class="col-12 col-md-4 d-flex flex-wrap justify-content-md-end gap-2">
            <button
              v-if="selectedGroup && canEditSchedule && !isLocked"
              type="button"
              class="btn btn-outline-primary btn-sm rounded-pill px-3"
              @click="copyPrevMonth"
              :disabled="loading"
              title="คัดลอกรูปแบบเวรจากเดือนก่อนหน้า"
            >
              <i class="bi bi-copy me-1"></i> คัดลอกเดือนก่อน
            </button>
            <button
              v-if="selectedGroup && members.length > 0"
              type="button"
              class="btn btn-success btn-sm rounded-pill px-3"
              @click="exportExcel"
            >
              <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
            <button
              v-if="selectedGroup && members.length > 0"
              type="button"
              class="btn btn-outline-primary btn-sm rounded-pill px-3"
              @click="openDutyMemoModal"
            >
              <i class="bi bi-file-earmark-text me-1"></i> บันทึกข้อความขอขึ้นเวร
            </button>
            <button
              v-if="selectedGroup && members.length > 0"
              type="button"
              class="btn btn-dark btn-sm rounded-pill px-3"
              @click="openPrintModal"
            >
              <i class="bi bi-printer me-1"></i> พิมพ์ตารางเวร
            </button>
            <button
              v-if="selectedGroup && canEditSchedule"
              type="button"
              class="btn btn-sm rounded-pill px-3 fw-semibold"
              :class="isLocked ? 'btn-warning text-dark' : 'btn-outline-secondary'"
              @click="toggleScheduleLock(!isLocked)"
              :disabled="togglingLock"
              :title="isLocked ? 'ปลดล็อกเพื่อแก้ไขตารางเวร' : 'ล็อกตารางเวรป้องกันการแก้ไขโดยไม่ตั้งใจ'"
            >
              <span v-if="togglingLock" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi me-1" :class="isLocked ? 'bi-unlock-fill' : 'bi-lock-fill'"></i>
              {{ isLocked ? 'ปลดล็อกแก้ไข' : 'ล็อกตาราง' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings View: All Department Schedule Groups Table (like InventorySettings) -->
    <div v-if="viewMode === 'settings'" class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <div>
            <h5 class="fw-bold text-dark mb-1">รายชื่อตารางเวรกลุ่มงานทั้งหมด</h5>
            <small class="text-muted"
              >เฉพาะผู้ดูแลระบบ (Admin) สามารถเพิ่มกลุ่มงานใหม่
              และผู้ดูแลกลุ่มงานสามารถตั้งค่าสิทธิ์เข้าถึงได้</small
            >
          </div>
          <button
            v-if="isSystemAdmin"
            type="button"
            class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm"
            @click="openGroupModal()"
          >
            <i class="bi bi-plus-lg me-1"></i> เพิ่มกลุ่มงานใหม่
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="70">รหัส</th>
                <th>ชื่อตารางเวร / กลุ่มงาน</th>
                <th>แจ้งเตือน MOPH Notify</th>
                <th>รหัสเวร</th>
                <th class="text-center" width="100">จำนวนคน</th>
                <th class="text-center" width="150">ระดับสิทธิ์ของคุณ</th>
                <th class="text-end pe-3" width="230">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="groups.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">ยังไม่มีตารางเวรกลุ่มงาน</td>
              </tr>
              <tr v-for="(g, idx) in groups" :key="g.id">
                <td>
                  <span class="badge bg-secondary rounded-2 px-2 py-1">{{
                    String(idx + 1).padStart(2, '0')
                  }}</span>
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ g.title }}</div>
                  <small class="text-muted">{{ g.department_name }}</small>
                </td>
                <td>
                  <div v-if="g.notify_channel">
                    <span
                      class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"
                    >
                      <i class="bi bi-bell-fill me-1"></i
                      >{{ getMophChannelLabel(g.notify_channel) }}
                    </span>
                    <div v-if="g.notify_enabled" class="small text-muted mt-1">
                      <i class="bi bi-clock me-1"></i>{{ formatNotifyDays(g.notify_days) }} เวลา
                      {{ g.notify_time || '08:00' }} น.
                    </div>
                  </div>
                  <small v-else class="text-muted">- ไม่แจ้งเตือน -</small>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span
                      v-for="st in g.shift_types"
                      :key="st.code"
                      class="badge text-white"
                      :style="{ backgroundColor: st.color || '#4f46e5' }"
                    >
                      {{ st.code }}
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                    {{ g.member_count || 0 }} คน
                  </span>
                </td>
                <td class="text-center">
                  <span
                    v-if="isSystemAdmin || g.access_level === 'ADMIN'"
                    class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1"
                  >
                    <i class="bi bi-shield-check me-1"></i>
                    {{ isSystemAdmin ? 'ผู้ดูแลระบบ' : 'ผู้ดูแลกลุ่มงาน' }}
                  </span>
                  <span
                    v-else-if="g.access_level === 'STAFF'"
                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1"
                  >
                    <i class="bi bi-pencil-square me-1"></i> ผู้จัดเวร
                  </span>
                  <span v-else class="badge bg-light text-secondary border rounded-pill px-3 py-1">
                    <i class="bi bi-eye me-1"></i> ดูอย่างเดียว
                  </span>
                </td>
                <td class="text-end pe-3 text-nowrap">
                  <div class="d-inline-flex gap-1">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary rounded-3"
                      @click="openGroupSchedule(g)"
                      title="เปิดดูตารางเวร"
                    >
                      <i class="bi bi-calendar3"></i>
                    </button>
                    <button
                      v-if="isSystemAdmin || g.access_level === 'ADMIN'"
                      type="button"
                      class="btn btn-sm btn-outline-primary rounded-3"
                      @click="openGroupModal(g)"
                      title="แก้ไขกลุ่มงาน"
                    >
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button
                      v-if="isSystemAdmin || g.access_level === 'ADMIN'"
                      type="button"
                      class="btn btn-sm btn-outline-info rounded-3 fw-semibold"
                      @click="openPermissionForGroup(g)"
                    >
                      <i class="bi bi-person-gear me-1"></i> สิทธิ์เข้าถึง
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State when no group exists yet -->
    <div v-else-if="!loading && groups.length === 0" class="card border-0 shadow-sm rounded-4">
      <div class="card-body text-center py-5">
        <div class="empty-icon mx-auto mb-3">
          <i class="bi bi-calendar-plus fs-1 text-primary"></i>
        </div>
        <h5 class="fw-bold text-dark">
          {{
            isSystemAdmin ? 'ยังไม่มีตารางเวรกลุ่มงาน' : 'ยังไม่ได้รับสิทธิ์เข้าถึงตารางเวรกลุ่มงาน'
          }}
        </h5>
        <p class="text-muted mb-3">
          {{
            isSystemAdmin
              ? 'เริ่มต้นด้วยการสร้างตารางเวรสำหรับกลุ่มงานแรกของคุณ'
              : 'กรุณาติดต่อผู้ดูแลระบบหรือหัวหน้ากลุ่มงานเพื่อกำหนดสิทธิ์เข้าใช้งาน'
          }}
        </p>
        <button
          v-if="isSystemAdmin"
          type="button"
          class="btn btn-primary rounded-pill px-4 fw-semibold"
          @click="openGroupModal()"
        >
          <i class="bi bi-plus-circle me-1"></i> สร้างตารางเวรกลุ่มงาน
        </button>
      </div>
    </div>

    <!-- Active Group Workspace -->
    <template v-else-if="selectedGroup">
      <!-- Today's On-Call Banner -->
      <div
        v-if="isCurrentMonth && todayOnCall.length > 0"
        class="card border-0 shadow-sm rounded-4 mb-4 oncall-banner d-print-none print-hide"
      >
        <div
          class="card-body p-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3"
        >
          <div class="d-flex align-items-center gap-3">
            <span
              class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold"
            >
              <i class="bi bi-broadcast me-1"></i> เวรประจำวันนี้ ({{ currentDay }}
              {{ months[selectedMonth - 1] }})
            </span>
            <div class="d-flex flex-wrap gap-2">
              <div
                v-for="oc in todayOnCall"
                :key="oc.member.id"
                class="oncall-chip d-inline-flex align-items-center gap-2"
              >
                <span class="shift-badge" :style="{ backgroundColor: getShiftColor(oc.shift) }">
                  {{ oc.shift }}
                </span>
                <span class="fw-semibold text-dark">{{ oc.member.person_name }}</span>
                <small v-if="oc.member.phone" class="text-muted font-monospace">
                  <i class="bi bi-telephone me-1"></i>{{ oc.member.phone }}
                </small>
              </div>
            </div>
          </div>
          <button
            v-if="selectedGroup?.notify_channel && canEditSchedule"
            type="button"
            class="btn btn-success btn-sm rounded-pill px-3 fw-semibold shadow-xs print-hide"
            @click="sendTodayNotify"
            :disabled="sendingNotify"
          >
            <span v-if="sendingNotify" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="bi bi-bell-fill me-1"></i>
            ส่งแจ้งเตือนเวรวันนี้ (MOPH)
          </button>
        </div>
      </div>

      <!-- Locked Banner -->
      <div
        v-if="isLocked"
        class="alert alert-warning border border-warning-subtle rounded-4 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2 print-hide"
      >
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-lock-fill fs-5 text-warning-emphasis"></i>
          <div>
            <span class="fw-bold text-dark">ตารางเวรเดือนนี้ถูกล็อกไว้แล้ว</span>
            <span v-if="lockInfo?.locked_by" class="small text-muted ms-2">
              (ล็อกโดย: {{ lockInfo.locked_by }})
            </span>
          </div>
        </div>
        <button
          v-if="canEditSchedule"
          type="button"
          class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark"
          @click="toggleScheduleLock(false)"
          :disabled="togglingLock"
        >
          <i class="bi bi-unlock-fill me-1"></i> ปลดล็อกเพื่อแก้ไข
        </button>
      </div>

      <!-- Shift Brush & Member Toolbar -->
      <div v-if="canEditSchedule && !isLocked" class="card border-0 shadow-sm rounded-4 mb-4 print-hide">
        <div
          class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3"
        >
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="small fw-bold text-secondary me-1">
              <i class="bi bi-brush-fill text-primary me-1"></i>เลือกเวรเพื่อคลิกลงตาราง:
            </span>
            <button
              v-for="st in selectedGroup.shift_types"
              :key="st.code"
              type="button"
              class="btn btn-sm rounded-pill px-3 fw-semibold brush-btn"
              :class="{ 'brush-active': activeBrush === st.code }"
              :style="
                activeBrush === st.code
                  ? { backgroundColor: st.color, color: '#fff', borderColor: st.color }
                  : { borderColor: st.color, color: st.color, backgroundColor: '#fff' }
              "
              @click="activeBrush = st.code"
            >
              <span class="me-1">{{ st.code }}</span>
              <small class="opacity-75">({{ st.label }})</small>
            </button>
            <button
              type="button"
              class="btn btn-sm rounded-pill px-3 fw-semibold"
              :class="activeBrush === '' ? 'btn-danger' : 'btn-outline-danger'"
              @click="activeBrush = ''"
            >
              <i class="bi bi-eraser me-1"></i> ล้างช่อง
            </button>
          </div>

          <button
            type="button"
            class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold"
            @click="openAddMemberModal"
          >
            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มเจ้าหน้าที่ในตารางเวร
          </button>
        </div>
      </div>

      <!-- Schedule Table -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden print-sheet">
        <!-- Official Print Header (Visible only when printing) -->
        <div class="d-none d-print-block text-center mb-2 pt-1">
          <h5 class="fw-bold mb-1 text-dark" style="font-size: 14pt">
            {{ selectedGroup.title }} โรงพยาบาลชานุมาน
          </h5>
          <div class="fw-semibold text-dark" style="font-size: 12pt">
            {{ selectedGroup.department_name }} • ประจำเดือน {{ months[selectedMonth - 1] }} พ.ศ.
            {{ selectedYear + 543 }}
          </div>
        </div>

        <div
          class="card-header bg-white border-bottom px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 print-hide"
        >
          <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <h5 class="fw-bold mb-0 text-dark">
                {{ selectedGroup.title }} — เดือน {{ months[selectedMonth - 1] }}
                {{ selectedYear + 543 }}
              </h5>
              <span
                v-if="!canEditSchedule"
                class="badge bg-light text-secondary border rounded-pill px-2 py-1 small"
              >
                <i class="bi bi-eye me-1"></i>โหมดดูข้อมูล (เฉพาะผู้ได้รับสิทธิ์จึงจะแก้ไขได้)
              </span>
            </div>
            <small class="text-muted"
              >{{ selectedGroup.department_name }} ({{ members.length }} คน)</small
            >
          </div>
          <div class="d-flex flex-wrap gap-2 small text-muted">
            <span
              v-for="st in selectedGroup.shift_types"
              :key="st.code"
              class="d-inline-flex align-items-center gap-1"
            >
              <span class="legend-dot" :style="{ backgroundColor: st.color }"></span>
              <span>{{ st.code }} = {{ st.label }}</span>
            </span>
          </div>
        </div>

        <div class="card-body p-0">
          <div v-if="loading" class="text-center py-5 text-muted">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <div>กำลังโหลดข้อมูลตารางเวร...</div>
          </div>

          <div v-else-if="members.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
            <div class="fw-semibold text-dark mb-1">ยังไม่มีรายชื่อเจ้าหน้าที่ในตารางเวรนี้</div>
            <p class="small mb-3">กดปุ่ม "เพิ่มเจ้าหน้าที่ในตารางเวร" เพื่อดึงรายชื่อจากแผนก</p>
            <button
              type="button"
              class="btn btn-primary btn-sm rounded-pill px-3"
              @click="openAddMemberModal"
            >
              <i class="bi bi-person-plus-fill me-1"></i> เพิ่มเจ้าหน้าที่
            </button>
          </div>

          <div v-else class="table-responsive print-table-wrap">
            <table class="table table-bordered align-middle mb-0 schedule-table">
              <thead>
                <tr>
                  <th class="text-center d-none d-print-table-cell" style="width: 34px">ลำดับ</th>
                  <th class="sticky-col text-start ps-3" style="min-width: 220px">ชื่อ - สกุล</th>
                  <th
                    v-for="day in daysInMonth"
                    :key="day"
                    class="text-center day-header"
                    :class="{
                      'is-today-col': isToday(day),
                      'is-weekend-col': isHolidayOrWeekend(day) && !isToday(day)
                    }"
                    :title="getHolidayName(day)"
                  >
                    <div class="day-num">{{ day }}</div>
                    <div class="day-name">{{ getThaiWeekdayShort(day) }}</div>
                  </th>
                  <th class="text-center total-col">รวม</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(m, idx) in members" :key="m.id">
                  <td class="text-center d-none d-print-table-cell">{{ idx + 1 }}</td>
                  <td class="sticky-col text-start ps-3">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                      <div>
                        <div class="fw-semibold text-dark text-nowrap">{{ m.person_name }}</div>
                        <small v-if="m.phone" class="text-muted font-monospace print-hide">
                          <i class="bi bi-telephone me-1"></i>{{ m.phone }}
                        </small>
                      </div>
                      <div
                        v-if="canEditSchedule && !isLocked"
                        class="d-inline-flex align-items-center gap-1 print-hide"
                      >
                        <button
                          type="button"
                          class="btn btn-link text-secondary p-0 opacity-50 hover-opacity-100"
                          :disabled="idx === 0"
                          @click="moveMember(idx, -1)"
                          title="เลื่อนขึ้น"
                        >
                          <i class="bi bi-arrow-up-short fs-5"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-link text-secondary p-0 opacity-50 hover-opacity-100"
                          :disabled="idx === members.length - 1"
                          @click="moveMember(idx, 1)"
                          title="เลื่อนลง"
                        >
                          <i class="bi bi-arrow-down-short fs-5"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-link text-danger p-0 opacity-50 hover-opacity-100"
                          @click="removeMember(m)"
                          title="นำออกจากตารางเวร"
                        >
                          <i class="bi bi-x-circle"></i>
                        </button>
                      </div>
                    </div>
                  </td>

                  <td
                    v-for="day in daysInMonth"
                    :key="day"
                    class="text-center day-cell"
                    :class="{
                      'cursor-pointer': canEditSchedule && !isLocked,
                      'is-today-col': isToday(day),
                      'is-weekend-col': isHolidayOrWeekend(day) && !isToday(day)
                    }"
                    @click="canEditSchedule && !isLocked && toggleCell(m, day)"
                  >
                    <span
                      v-if="m.duties && m.duties[String(day)]"
                      class="duty-pill"
                      :style="{ backgroundColor: getShiftColor(m.duties[String(day)]) }"
                    >
                      {{ m.duties[String(day)] }}
                    </span>
                  </td>

                  <td class="text-center fw-bold text-primary total-col">
                    {{ countMemberShifts(m) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Official Print Footer: Legend & Signatures (Visible only when printing) -->
          <div class="d-none d-print-block mt-3 px-2 print-footer">
            <div class="mb-3" style="font-size: 10pt">
              <div>
                <strong>หมายเหตุ :</strong>
                <span v-for="st in selectedGroup.shift_types" :key="'leg-' + st.code" class="ms-3">
                  <strong>{{ st.code }}</strong> = {{ st.label }}
                </span>
              </div>
              <div v-if="printNote && printNote.trim()" class="mt-1 ps-4" style="white-space: pre-line;">
                {{ printNote }}
              </div>
            </div>
            <div
              class="row mt-4 text-center g-2 align-items-start"
              style="font-size: 9.5pt; line-height: 1.4"
            >
              <div class="col-4">
                <div class="text-nowrap mb-1">
                  (ลงชื่อ).................................................... ผู้จัดตารางเวร
                </div>
                <div class="text-nowrap">({{ displaySchedulerName }})</div>
                <div v-if="displaySchedulerPosition">{{ displaySchedulerPosition }}</div>
                <div>ผู้จัดตารางเวรปฏิบัติงาน</div>
              </div>
              <div class="col-4">
                <div class="text-nowrap mb-1">
                  (ลงชื่อ).............................................................
                  ผู้ขออนุมัติตารางเวร
                </div>
                <div class="text-nowrap">
                  ({{ selectedGroup.leader_name || '....................................' }})
                </div>
                <div v-if="selectedGroup.leader_position">{{ selectedGroup.leader_position }}</div>
                <div>หัวหน้า{{ selectedGroup.department_name }}</div>
              </div>
              <div class="col-4">
                <div class="text-nowrap mb-1">
                  (ลงชื่อ).......................................................... ผู้อนุมัติ
                </div>
                <div class="text-nowrap">(นายธนากร คนเพียร)</div>
                <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
                <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal: Before Print Note -->
    <div v-if="showPrintModal" class="modal-backdrop fade show no-print"></div>
    <div v-if="showPrintModal" class="modal fade show d-block no-print" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 780px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-printer text-primary me-2"></i>ตั้งค่าก่อนพิมพ์ตารางเวร
            </h5>
            <button type="button" class="btn-close" @click="showPrintModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div v-if="selectedGroup && selectedGroup.shift_types?.length" class="mb-3 p-3 rounded-3 bg-light border small">
              <div class="fw-bold text-secondary mb-1">คำอธิบายเวร (แสดงอัตโนมัติ):</div>
              <div>
                <span v-for="st in selectedGroup.shift_types" :key="'pm-' + st.code" class="me-3">
                  <strong>{{ st.code }}</strong> = {{ st.label }}
                </span>
              </div>
            </div>
            <div>
              <label class="form-label fw-bold small">
                <i class="bi bi-chat-left-text me-1 text-primary"></i>หมายเหตุเพิ่มเติม (ก่อนพิมพ์)
              </label>
              <textarea
                class="form-control"
                rows="7"
                v-model="printNote"
                placeholder="พิมพ์หมายเหตุเพิ่มเติมที่ต้องการให้แสดงท้ายตารางเวร (ถ้ามี)..."
              ></textarea>
              <div class="form-text small text-muted">
                ระบบจะจำหมายเหตุนี้ไว้ให้สำหรับกลุ่มงานนี้ในครั้งถัดไป สามารถแก้ไขหรือลบออกได้ตลอดเวลา
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
            <button
              v-if="printNote"
              type="button"
              class="btn btn-outline-danger btn-sm rounded-pill px-3"
              @click="printNote = ''"
            >
              ล้างหมายเหตุ
            </button>
            <span v-else></span>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-light rounded-pill px-4" @click="showPrintModal = false">
                ยกเลิก
              </button>
              <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold" @click="confirmPrintPage">
                <i class="bi bi-printer me-1"></i> สั่งพิมพ์เลย
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Create / Edit Group -->
    <div v-if="showGroupModal" class="modal-backdrop fade show"></div>
    <div v-if="showGroupModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-calendar2-plus text-primary me-2"></i>
              {{ groupForm.id ? 'แก้ไขตารางเวรกลุ่มงาน' : 'สร้างตารางเวรกลุ่มงานใหม่' }}
            </h5>
            <button type="button" class="btn-close" @click="showGroupModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <!-- Summary of already created groups -->
            <div
              v-if="!groupForm.id && groups.length > 0"
              class="mb-3 p-3 rounded-3 bg-light border"
            >
              <div class="small fw-bold text-secondary mb-2">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                กลุ่มงานที่สร้างตารางเวรแล้ว ({{ groups.length }} กลุ่มงาน):
              </div>
              <div class="d-flex flex-wrap gap-1">
                <span
                  v-for="g in groups"
                  :key="g.id"
                  class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"
                >
                  <i class="bi bi-check2 me-1"></i>{{ g.department_name }}
                </span>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small">เลือกกลุ่มงาน / แผนก *</label>
                <select
                  v-model="groupForm.department_id"
                  class="form-select"
                  @change="onSelectDepartment"
                >
                  <option value="">-- เลือกกลุ่มงานจากระบบ --</option>
                  <option
                    v-for="d in departments"
                    :key="d.HR_DEPARTMENT_SUB_ID"
                    :value="d.HR_DEPARTMENT_SUB_ID"
                  >
                    {{ hasDepartmentSchedule(d.HR_DEPARTMENT_SUB_ID) ? '✓ [สร้างแล้ว] ' : ''
                    }}{{ d.HR_DEPARTMENT_SUB_NAME }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small">ชื่อกลุ่มงานที่แสดง *</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="groupForm.department_name"
                  placeholder="เช่น กลุ่มงานเภสัชกรรม"
                />
              </div>
              <div class="col-12">
                <label class="form-label fw-bold small">ชื่อตารางเวร *</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="groupForm.title"
                  placeholder="เช่น ตารางเวรนอกเวลากลุ่มงานเภสัชกรรม"
                />
              </div>
              <!-- Scheduler Signature Settings -->
              <div class="col-12">
                <div class="border rounded-3 p-3 bg-light-subtle">
                  <div class="fw-bold small mb-2">
                    <i class="bi bi-pen me-1 text-primary"></i>ผู้จัดตารางเวร
                    (สำหรับลงนามในหน้าพิมพ์)
                  </div>
                  <div class="mb-2" v-if="schedulerStaffOptions.length > 0">
                    <select
                      class="form-select form-select-sm"
                      :value="selectedSchedulerStaffId"
                      @change="onSelectSchedulerStaff($event.target.value)"
                    >
                      <option value="">-- เลือกรายชื่อจากกลุ่มงาน (หรือกรอกเองด้านล่าง) --</option>
                      <option
                        v-for="s in schedulerStaffOptions"
                        :key="'sch-' + s.ID"
                        :value="String(s.ID)"
                      >
                        {{ s.FULLNAME }}{{ s.HR_POSITION_NAME ? ` (${s.HR_POSITION_NAME})` : '' }}
                      </option>
                    </select>
                  </div>
                  <div class="row g-2">
                    <div class="col-md-6">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="groupForm.scheduler_name"
                        placeholder="ชื่อ-สกุล ผู้จัดตารางเวร (เว้นว่าง = ใช้ชื่อผู้ล็อกอิน)"
                      />
                    </div>
                    <div class="col-md-6">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="groupForm.scheduler_position"
                        placeholder="ตำแหน่ง (เช่น นักวิชาการสาธารณสุขปฏิบัติการ)"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small">
                  <i class="bi bi-bell-fill text-success me-1"></i>ช่องทางแจ้งเตือน MOPH Notify
                  (Channel)
                </label>
                <div class="d-flex gap-2">
                  <select class="form-select" v-model="groupForm.notify_channel">
                    <option value="">-- ไม่ส่งแจ้งเตือน --</option>
                    <option
                      v-for="ch in mophChannels"
                      :key="ch.channel_code"
                      :value="ch.channel_code"
                    >
                      {{ ch.channel_name }} ({{ ch.channel_code }})
                    </option>
                  </select>
                  <button
                    v-if="groupForm.notify_channel"
                    type="button"
                    class="btn btn-outline-success text-nowrap fw-semibold px-3"
                    @click="testNotifyChannel"
                    :disabled="sendingNotify"
                  >
                    <span v-if="sendingNotify" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-send-check me-1"></i>
                    ทดสอบส่ง
                  </button>
                </div>
              </div>

              <!-- Auto Notify Schedule (Days & Time) -->
              <div v-if="groupForm.notify_channel" class="col-12">
                <div class="border rounded-3 p-3 bg-light-subtle">
                  <div
                    class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"
                  >
                    <div class="form-check form-switch mb-0">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="deptNotifyEnabled"
                        v-model="groupForm.notify_enabled"
                      />
                      <label class="form-check-label fw-bold small" for="deptNotifyEnabled">
                        เปิดแจ้งเตือนเวรอัตโนมัติ (ตามวันและเวลาที่กำหนด)
                      </label>
                    </div>
                    <div v-if="groupForm.notify_enabled" class="d-flex gap-1">
                      <button
                        type="button"
                        class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0"
                        style="font-size: 0.72rem"
                        @click="groupForm.notify_days = [1, 2, 3, 4, 5, 6, 7]"
                      >
                        ทุกวัน
                      </button>
                      <button
                        type="button"
                        class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0"
                        style="font-size: 0.72rem"
                        @click="groupForm.notify_days = [1, 2, 3, 4, 5]"
                      >
                        จ-ศ
                      </button>
                    </div>
                  </div>

                  <div v-if="groupForm.notify_enabled" class="row g-2 align-items-center mt-1">
                    <div class="col-12 col-md-8">
                      <label class="form-label small text-muted mb-1">วันที่ต้องการแจ้งเตือน</label>
                      <div class="d-flex flex-wrap gap-1">
                        <button
                          v-for="wd in weekdayOptions"
                          :key="wd.val"
                          type="button"
                          class="btn btn-sm rounded-pill px-2 fw-semibold"
                          :class="
                            groupForm.notify_days.includes(wd.val)
                              ? 'btn-success'
                              : 'btn-outline-secondary bg-white'
                          "
                          @click="toggleNotifyDay(wd.val)"
                        >
                          {{ wd.label }}
                        </button>
                      </div>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label small text-muted mb-1">เวลาที่แจ้งเตือน</label>
                      <input
                        type="time"
                        class="form-control form-control-sm"
                        v-model="groupForm.notify_time"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="border rounded-3 p-3 bg-light-subtle">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-bold small mb-0">รหัสเวรที่ใช้ในกลุ่มงานนี้</label>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary rounded-pill px-2"
                  @click="addShiftTypeRow"
                >
                  <i class="bi bi-plus me-1"></i> เพิ่มรหัสเวร
                </button>
              </div>
              <div
                v-for="(st, idx) in groupForm.shift_types"
                :key="idx"
                class="row g-2 align-items-center mb-2"
              >
                <div class="col-3">
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    v-model="st.code"
                    placeholder="รหัส (เช่น ช)"
                  />
                </div>
                <div class="col-5">
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    v-model="st.label"
                    placeholder="ความหมาย (เช่น เวรเช้า)"
                  />
                </div>
                <div class="col-2">
                  <input
                    type="color"
                    class="form-control form-control-color form-control-sm w-100"
                    v-model="st.color"
                  />
                </div>
                <div class="col-2 text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="groupForm.shift_types.splice(idx, 1)"
                    :disabled="groupForm.shift_types.length <= 1"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3 justify-content-between">
            <button
              v-if="groupForm.id"
              type="button"
              class="btn btn-outline-danger rounded-pill px-3"
              @click="deleteGroup"
            >
              <i class="bi bi-trash me-1"></i> ลบกลุ่มตารางเวรนี้
            </button>
            <div v-else></div>
            <div class="d-flex gap-2">
              <button
                type="button"
                class="btn btn-light border rounded-pill px-4"
                @click="showGroupModal = false"
              >
                ยกเลิก
              </button>
              <button
                type="button"
                class="btn btn-primary rounded-pill px-4 fw-semibold"
                @click="saveGroup"
                :disabled="saving"
              >
                <i class="bi bi-save me-1"></i> บันทึก
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Add Members -->
    <div v-if="showMemberModal" class="modal-backdrop fade show"></div>
    <div v-if="showMemberModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-person-plus-fill text-primary me-2"></i>เพิ่มเจ้าหน้าที่ในตารางเวร
            </h5>
            <button type="button" class="btn-close" @click="showMemberModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-2 mb-3 align-items-center">
              <div class="col-md-7">
                <input
                  type="text"
                  class="form-control"
                  v-model="staffSearch"
                  placeholder="ค้นหาชื่อ-สกุล หรือแผนก..."
                />
              </div>
              <div class="col-md-5">
                <div class="form-check form-switch">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="showAllHospitalStaff"
                    v-model="showAllStaff"
                  />
                  <label class="form-check-label small fw-semibold" for="showAllHospitalStaff">
                    แสดงเจ้าหน้าที่ทุกแผนก
                  </label>
                </div>
              </div>
            </div>

            <div class="border rounded-3 p-2" style="max-height: 280px; overflow-y: auto">
              <div
                v-if="filteredStaffOptions.length === 0"
                class="text-center py-4 text-muted small"
              >
                ไม่พบรายชื่อเจ้าหน้าที่ (ลองเปิด "แสดงเจ้าหน้าที่ทุกแผนก")
              </div>
              <label
                v-for="s in filteredStaffOptions"
                :key="s.ID"
                class="d-flex align-items-center justify-content-between p-2 rounded-2 staff-option cursor-pointer"
              >
                <div class="d-flex align-items-center gap-2">
                  <input
                    type="checkbox"
                    class="form-check-input mt-0"
                    :value="s"
                    v-model="selectedStaffToAdd"
                  />
                  <div>
                    <div class="fw-semibold text-dark small">{{ s.FULLNAME }}</div>
                    <small class="text-muted">{{ s.HR_DEPARTMENT_SUB_NAME || '-' }}</small>
                  </div>
                </div>
                <small class="text-muted font-monospace">{{ s.HR_PHONE || '' }}</small>
              </label>
            </div>

            <!-- Manual fallback -->
            <div class="mt-3 pt-3 border-top">
              <label class="form-label small fw-bold text-secondary">หรือเพิ่มชื่อด้วยตนเอง</label>
              <div class="row g-2">
                <div class="col-md-6">
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    v-model="manualMemberName"
                    placeholder="ชื่อ - สกุล"
                  />
                </div>
                <div class="col-md-4">
                  <input
                    type="text"
                    class="form-control form-control-sm"
                    v-model="manualMemberPhone"
                    placeholder="เบอร์โทร (ถ้ามี)"
                  />
                </div>
                <div class="col-md-2">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary w-100"
                    @click="addManualMember"
                  >
                    เพิ่ม
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3">
            <button
              type="button"
              class="btn btn-light border rounded-pill px-4"
              @click="showMemberModal = false"
            >
              ปิด
            </button>
            <button
              type="button"
              class="btn btn-primary rounded-pill px-4 fw-semibold"
              @click="confirmAddMembers"
              :disabled="selectedStaffToAdd.length === 0 || saving"
            >
              เพิ่มที่เลือก ({{ selectedStaffToAdd.length }} คน)
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Group User Permissions -->
    <div v-if="showPermissionModal" class="modal-backdrop fade show"></div>
    <div v-if="showPermissionModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-shield-lock-fill text-primary me-2"></i>ตั้งค่าสิทธิ์ผู้เข้าใช้ —
              {{ selectedGroup?.title }}
            </h5>
            <button type="button" class="btn-close" @click="showPermissionModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-muted">
                แสดงรายชื่อใน:
                <strong class="text-dark">{{ selectedGroup?.department_name }}</strong>
              </small>
              <div class="form-check form-switch mb-0">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="showAllPermStaffSwitch"
                  v-model="showAllPermStaff"
                />
                <label class="form-check-label small fw-semibold" for="showAllPermStaffSwitch">
                  แสดงเจ้าหน้าที่ทุกแผนก
                </label>
              </div>
            </div>
            <div class="row g-2 align-items-end mb-3 pb-3 border-bottom">
              <div class="col-md-5">
                <label class="form-label small fw-bold">ค้นหาและเลือกผู้ใช้งาน</label>
                <input
                  type="text"
                  class="form-control form-control-sm mb-1"
                  v-model="permUserSearch"
                  placeholder="พิมพ์ชื่อเพื่อกรอง..."
                />
                <select v-model="permForm.target_user_id" class="form-select form-select-sm">
                  <option value="">-- เลือกเจ้าหน้าที่ --</option>
                  <option v-for="s in filteredPermStaffOptions" :key="s.ID" :value="s.ID">
                    {{ s.FULLNAME }}
                    {{ s.HR_DEPARTMENT_SUB_NAME ? `(${s.HR_DEPARTMENT_SUB_NAME})` : '' }}
                  </option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">ระดับสิทธิ์</label>
                <select v-model="permForm.access_level" class="form-select form-select-sm">
                  <option value="ADMIN">ADMIN (จัดการ/ตั้งค่า/กำหนดสิทธิ์ได้)</option>
                  <option value="STAFF">STAFF (ลงเวร/เพิ่มรายชื่อได้)</option>
                  <option value="VIEWER">VIEWER (ดู/พิมพ์อย่างเดียว)</option>
                </select>
              </div>
              <div class="col-md-3">
                <button
                  type="button"
                  class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold"
                  @click="saveGroupUserPermission"
                  :disabled="!permForm.target_user_id || saving"
                >
                  <i class="bi bi-plus-circle me-1"></i> เพิ่มสิทธิ์
                </button>
              </div>
            </div>

            <div class="table-responsive" style="max-height: 280px; overflow-y: auto">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>ชื่อ - สกุล</th>
                    <th width="210">ระดับสิทธิ์</th>
                    <th width="60" class="text-center">ลบ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="groupUsers.length === 0">
                    <td colspan="3" class="text-center py-4 text-muted small">
                      ยังไม่มีผู้ใช้งานที่ถูกกำหนดสิทธิ์เฉพาะกลุ่มนี้ (ผู้ดูแลระบบหลักเข้าได้ทุกคน)
                    </td>
                  </tr>
                  <tr v-for="u in groupUsers" :key="u.id">
                    <td class="fw-semibold small">{{ u.user_name }}</td>
                    <td>
                      <select
                        class="form-select form-select-sm"
                        :value="u.access_level"
                        @change="updateGroupUserLevel(u, $event.target.value)"
                      >
                        <option value="ADMIN">ADMIN</option>
                        <option value="STAFF">STAFF</option>
                        <option value="VIEWER">VIEWER</option>
                      </select>
                    </td>
                    <td class="text-center">
                      <button
                        type="button"
                        class="btn btn-sm btn-outline-danger py-0 px-2"
                        @click="removeGroupUserPermission(u)"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3">
            <button
              type="button"
              class="btn btn-light border rounded-pill px-4"
              @click="showPermissionModal = false"
            >
              ปิด
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Duty Request Memo (บันทึกข้อความขอขึ้นเวร) -->
    <div v-if="showDutyMemoModal" class="modal-backdrop fade show"></div>
    <div
      v-if="showDutyMemoModal"
      class="modal fade show d-block duty-memo-modal-root"
      tabindex="-1"
      @click.self="showDutyMemoModal = false"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom px-4 py-3">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-file-earmark-text text-primary me-2"></i>บันทึกข้อความขอขึ้นเวร
            </h5>
            <button type="button" class="btn-close" @click="showDutyMemoModal = false"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div class="row g-3">
              <!-- Left: Editable Memo Settings -->
              <div class="col-lg-4 duty-memo-sidebar">
                <div class="card border-0 shadow-sm rounded-4">
                  <div class="card-body p-3">
                    <h6 class="fw-bold text-primary mb-3">
                      <i class="bi bi-sliders me-1"></i>ตั้งค่าข้อความ
                    </h6>
                    <div class="mb-2">
                      <label class="form-label small fw-bold mb-1">ส่วนราชการ</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="dutyMemoForm.agency"
                      />
                    </div>
                    <div class="row g-2 mb-2">
                      <div class="col-6">
                        <label class="form-label small fw-bold mb-1">ที่</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="dutyMemoForm.docNo"
                        />
                      </div>
                      <div class="col-6">
                        <label class="form-label small fw-bold mb-1">วันที่</label>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="dutyMemoForm.docDate"
                        />
                      </div>
                    </div>
                    <div class="mb-2">
                      <label class="form-label small fw-bold mb-1">เรื่อง</label>
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        v-model="dutyMemoForm.subject"
                      />
                    </div>
                    <div class="mb-2">
                      <label class="form-label small fw-bold mb-1"
                        >เหตุผลความจำเป็น (ย่อหน้า 1)</label
                      >
                      <textarea
                        class="form-control form-control-sm"
                        rows="4"
                        v-model="dutyMemoForm.reason"
                      ></textarea>
                    </div>
                    <div class="row g-2 mb-2">
                      <div class="col-6">
                        <label class="form-label small fw-bold mb-1"
                          >ผู้เสนอ (หัวหน้ากลุ่มงาน)</label
                        >
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="dutyMemoForm.leaderName"
                        />
                      </div>
                      <div class="col-6">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง</label>
                        <input
                          type="text"
                          class="form-control form-control-sm"
                          v-model="dutyMemoForm.leaderPos"
                        />
                      </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="onlyScheduledChk"
                        v-model="dutyMemoForm.onlyScheduled"
                      />
                      <label class="form-check-label small" for="onlyScheduledChk"
                        >แสดงเฉพาะผู้ที่มีเวรในเดือนนี้</label
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: A4 Portrait Memo Preview -->
              <div class="col-lg-8 duty-memo-col">
                <div
                  ref="dutyMemoPreview"
                  class="bg-white shadow-sm rounded-3 mx-auto p-4 p-md-5 duty-memo-paper"
                  style="
                    font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
                    font-size: 16pt;
                    color: #000;
                    line-height: 1.15;
                  "
                >
                  <div class="d-flex align-items-center mb-2">
                    <img
                      :src="krutImg"
                      alt="Garuda"
                      style="height: 2cm; width: auto"
                      class="me-3"
                    />
                    <div class="flex-grow-1 text-center" style="margin-right: 1.5cm">
                      <h1
                        class="fw-bold mb-0"
                        style="
                          font-size: 29pt;
                          font-family: 'TH Sarabun New', 'Sarabun', sans-serif;
                        "
                      >
                        บันทึกข้อความ
                      </h1>
                    </div>
                  </div>

                  <div class="d-flex align-items-baseline mb-0">
                    <span
                      class="fw-bold text-nowrap"
                      style="font-size: 20pt; width: 25mm; flex-shrink: 0"
                      >ส่วนราชการ</span
                    >
                    <span style="font-size: 16pt">{{ dutyMemoForm.agency }}</span>
                  </div>
                  <div class="row g-0 align-items-baseline">
                    <div class="col-6 d-flex align-items-baseline">
                      <span
                        class="fw-bold text-nowrap"
                        style="font-size: 18pt; width: 10mm; flex-shrink: 0"
                        >ที่</span
                      >
                      <span style="font-size: 16pt">{{ dutyMemoForm.docNo }}</span>
                    </div>
                    <div class="col-6 d-flex align-items-baseline">
                      <span
                        class="fw-bold text-nowrap"
                        style="font-size: 20pt; width: 15mm; flex-shrink: 0"
                        >วันที่</span
                      >
                      <span style="font-size: 16pt">{{
                        formatThaiMemoDate(dutyMemoForm.docDate)
                      }}</span>
                    </div>
                  </div>
                  <div class="d-flex align-items-baseline mb-0">
                    <span
                      class="fw-bold text-nowrap"
                      style="font-size: 20pt; width: 15mm; flex-shrink: 0"
                      >เรื่อง</span
                    >
                    <span style="font-size: 16pt">{{ dutyMemoForm.subject }}</span>
                  </div>
                  <div class="d-flex align-items-baseline mb-3">
                    <span
                      class="fw-bold text-nowrap"
                      style="font-size: 18pt; width: 15mm; flex-shrink: 0"
                      >เรียน</span
                    >
                    <span style="font-size: 16pt">ผู้อำนวยการโรงพยาบาลชานุมาน</span>
                  </div>

                  <p
                    class="mb-2"
                    style="
                      text-indent: 25mm;
                      text-align: justify;
                      line-height: 1.15;
                      font-size: 16pt;
                    "
                  >
                    {{ dutyMemoForm.reason }}
                  </p>
                  <p
                    class="mb-2"
                    style="
                      text-indent: 25mm;
                      text-align: justify;
                      line-height: 1.15;
                      font-size: 16pt;
                    "
                  >
                    ในการนี้
                    {{ selectedGroup?.department_name }}
                    จึงขออนุมัติให้เจ้าหน้าที่ปฏิบัติงานนอกเวลาราชการ ประจำเดือน{{
                      months[selectedMonth - 1]
                    }}
                    {{ selectedYear + 543 }} ดังรายนามต่อไปนี้
                  </p>

                  <div class="ms-4 mb-2" style="font-size: 16pt; line-height: 1.15">
                    <div
                      v-for="(emp, idx) in memoMembersList"
                      :key="'memo-' + emp.id"
                      class="d-flex"
                    >
                      <span style="width: 24px; flex-shrink: 0">{{ idx + 1 }}.</span>
                      <span style="min-width: 195px; padding-right: 12px; white-space: nowrap">{{
                        emp.person_name
                      }}</span>
                      <span class="text-nowrap">ตำแหน่ง {{ emp.position_name || '-' }}</span>
                    </div>
                    <div v-if="memoMembersList.length === 0" class="text-muted small fst-italic">
                      - ยังไม่มีรายชื่อผู้ขึ้นเวรในเดือนนี้ -
                    </div>
                  </div>

                  <p class="mb-2" style="text-indent: 25mm; font-size: 16pt">
                    จึงเรียนมาเพื่อโปรดทราบและพิจารณาอนุมัติ
                  </p>

                  <div
                    class="ms-auto text-center mt-2 mb-2"
                    style="width: 55%; line-height: 1.15; font-size: 16pt"
                  >
                    <br />
                    <div class="mb-1">
                      ({{
                        dutyMemoForm.leaderName ||
                        '....................................................'
                      }})
                    </div>
                    <div v-if="dutyMemoForm.leaderPos">{{ dutyMemoForm.leaderPos }}</div>
                    <div>หัวหน้า{{ selectedGroup?.department_name }}</div>
                  </div>

                  <div class="mt-2 mb-2" style="line-height: 1.15; font-size: 16pt">
                    <div class="mb-1">เรียน ผู้อำนวยการโรงพยาบาลชานุมาน</div>
                    <div class="ps-5">- ได้ตรวจสอบถูกต้องแล้ว</div>
                    <div class="ps-5 mb-2">- เห็นควรอนุมัติ</div>
                    <div
                      class="text-center mt-2 text-nowrap"
                      style="width: 42%; line-height: 1.15; font-size: 16pt"
                    >
                      <br />
                      <div class="mb-1">(นายธนากร คนเพียร)</div>
                      <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
                      <div>หัวหน้ากลุ่มงานบริหารทั่วไป</div>
                    </div>
                  </div>

                  <div
                    class="ms-auto text-center mt-2"
                    style="width: 55%; line-height: 1.15; font-size: 16pt"
                  >
                    <div class="mb-1">อนุมัติ</div>
                    <br />
                    <div class="mt-1">(นายธนากร คนเพียร)</div>
                    <div>นายแพทย์ชำนาญการ รักษาการในตำแหน่ง</div>
                    <div>ผู้อำนวยการโรงพยาบาลชานุมาน</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top px-4 py-3">
            <button
              type="button"
              class="btn btn-light border rounded-pill px-4"
              @click="showDutyMemoModal = false"
            >
              ปิด
            </button>
            <button
              type="button"
              class="btn btn-dark rounded-pill px-4 fw-semibold"
              @click="printDutyMemo"
            >
              <i class="bi bi-printer me-1"></i> สั่งพิมพ์บันทึกข้อความ (A4 แนวตั้ง)
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import krutImg from '../../assets/krut.png';

export default {
  name: 'DepartmentSchedule',
  data() {
    const now = new Date();
    const curY = now.getFullYear();
    return {
      loading: false,
      saving: false,
      viewMode: 'schedule',
      groups: [],
      selectedGroupId: '',
      members: [],
      departments: [],
      allStaff: [],
      mophChannels: [],
      sendingNotify: false,
      weekdayOptions: [
        { val: 1, label: 'จ' },
        { val: 2, label: 'อ' },
        { val: 3, label: 'พ' },
        { val: 4, label: 'พฤ' },
        { val: 5, label: 'ศ' },
        { val: 6, label: 'ส' },
        { val: 7, label: 'อา' }
      ],
      selectedMonth: now.getMonth() + 1,
      selectedYear: curY,
      yearOptions: [curY - 1, curY, curY + 1],
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
      activeBrush: 'ช',
      monthHolidays: {},
      isLocked: false,
      lockInfo: null,
      togglingLock: false,
      showPrintModal: false,
      printNote: '',
      showGroupModal: false,
      groupForm: {
        id: 0,
        department_id: '',
        department_name: '',
        title: '',
        notify_channel: '',
        notify_enabled: false,
        notify_days: [1, 2, 3, 4, 5, 6, 7],
        notify_time: '08:00',
        scheduler_name: '',
        scheduler_position: '',
        shift_types: []
      },
      showMemberModal: false,
      staffSearch: '',
      showAllStaff: false,
      selectedStaffToAdd: [],
      manualMemberName: '',
      manualMemberPhone: '',
      currentUserId: localStorage.getItem('user_id') || '',
      currentUserDeptId: '',
      isSystemAdmin: false,
      showPermissionModal: false,
      groupUsers: [],
      permUserSearch: '',
      showAllPermStaff: false,
      krutImg,
      showDutyMemoModal: false,
      dutyMemoForm: {
        agency: '',
        docNo: 'อจ.0033/',
        docDate: '',
        subject: '',
        reason: '',
        leaderName: '',
        leaderPos: '',
        onlyScheduled: true
      },
      permForm: {
        target_user_id: '',
        access_level: 'STAFF'
      }
    };
  },
  computed: {
    selectedGroup() {
      return this.groups.find((g) => String(g.id) === String(this.selectedGroupId)) || null;
    },
    selectedGroupAccess() {
      if (this.isSystemAdmin) return 'ADMIN';
      return this.selectedGroup?.access_level || null;
    },
    canManageGroup() {
      return this.isSystemAdmin || this.selectedGroupAccess === 'ADMIN';
    },
    canEditSchedule() {
      return this.isSystemAdmin || ['ADMIN', 'STAFF'].includes(this.selectedGroupAccess);
    },
    memoMembersList() {
      if (!this.dutyMemoForm.onlyScheduled) return this.members;
      return this.members.filter((m) => this.countMemberShifts(m) > 0);
    },
    displaySchedulerName() {
      const saved = String(this.selectedGroup?.scheduler_name || '').trim();
      if (saved) return saved;
      return localStorage.getItem('user_name') || '............................................';
    },
    displaySchedulerPosition() {
      const saved = String(this.selectedGroup?.scheduler_position || '').trim();
      if (saved) return saved;
      const myName = (localStorage.getItem('user_name') || '').replace(/\s+/g, '');
      if (myName) {
        const me = this.members.find(
          (m) => String(m.person_name || '').replace(/\s+/g, '') === myName
        );
        if (me?.position_name) return me.position_name;
      }
      return '';
    },
    schedulerStaffOptions() {
      if (!this.allStaff || !this.allStaff.length) return [];
      if (this.groupForm.department_id) {
        const deptList = this.allStaff.filter(
          (s) => String(s.HR_DEPARTMENT_SUB_ID) === String(this.groupForm.department_id)
        );
        if (deptList.length > 0) return deptList;
      }
      return this.allStaff.slice(0, 100);
    },
    selectedSchedulerStaffId() {
      const name = String(this.groupForm.scheduler_name || '').trim();
      if (!name) return '';
      const matched = this.schedulerStaffOptions.find(
        (s) => String(s.FULLNAME || '').trim() === name
      );
      return matched ? String(matched.ID) : '';
    },
    filteredPermStaffOptions() {
      const assignedIds = new Set(this.groupUsers.map((u) => String(u.user_id)));
      let list = this.allStaff.filter((s) => !assignedIds.has(String(s.ID)));
      if (!this.showAllPermStaff && this.selectedGroup?.department_id) {
        list = list.filter(
          (s) => String(s.HR_DEPARTMENT_SUB_ID) === String(this.selectedGroup.department_id)
        );
      }
      const q = this.permUserSearch.trim().toLowerCase();
      if (q) {
        list = list.filter(
          (s) =>
            String(s.FULLNAME || '')
              .toLowerCase()
              .includes(q) ||
            String(s.HR_DEPARTMENT_SUB_NAME || '')
              .toLowerCase()
              .includes(q)
        );
      }
      return list.slice(0, 80);
    },
    daysInMonth() {
      const days = new Date(this.selectedYear, this.selectedMonth, 0).getDate();
      return Array.from({ length: days }, (_, i) => i + 1);
    },
    isCurrentMonth() {
      const now = new Date();
      return this.selectedYear === now.getFullYear() && this.selectedMonth === now.getMonth() + 1;
    },
    currentDay() {
      return new Date().getDate();
    },
    todayOnCall() {
      if (!this.isCurrentMonth) return [];
      const d = String(this.currentDay);
      const list = [];
      for (const m of this.members) {
        const shift = m.duties?.[d];
        if (shift) list.push({ member: m, shift });
      }
      return list;
    },
    filteredStaffOptions() {
      const existingNames = new Set(this.members.map((m) => m.person_name.trim()));
      let list = this.allStaff.filter((s) => !existingNames.has(String(s.FULLNAME || '').trim()));
      if (!this.showAllStaff && this.selectedGroup?.department_id) {
        list = list.filter(
          (s) => String(s.HR_DEPARTMENT_SUB_ID) === String(this.selectedGroup.department_id)
        );
      }
      const q = this.staffSearch.trim().toLowerCase();
      if (q) {
        list = list.filter(
          (s) =>
            String(s.FULLNAME || '')
              .toLowerCase()
              .includes(q) ||
            String(s.HR_DEPARTMENT_SUB_NAME || '')
              .toLowerCase()
              .includes(q)
        );
      }
      return list.slice(0, 100);
    }
  },
  async mounted() {
    await Promise.all([this.loadUserProfile(), this.fetchDepartmentsAndStaff()]);
    if (!this.currentUserDeptId && this.currentUserId && this.allStaff.length > 0) {
      const me = this.allStaff.find((s) => String(s.ID) === String(this.currentUserId));
      if (me?.HR_DEPARTMENT_SUB_ID) {
        this.currentUserDeptId = String(me.HR_DEPARTMENT_SUB_ID);
      }
    }
    await this.fetchGroups();
    if (this.$route.query.create === '1' && this.isSystemAdmin) {
      this.openGroupModal();
    } else if (this.groups.length > 0) {
      const qId = this.$route.query.group_id;
      const found = qId ? this.groups.find((g) => String(g.id) === String(qId)) : null;
      this.selectedGroupId = found ? found.id : this.groups[0].id;
      this.activeBrush = (found || this.groups[0]).shift_types?.[0]?.code || 'ช';
      await this.fetchSchedule();
    }
  },
  methods: {
    async loadUserProfile() {
      try {
        const res = await axios.get('/api-hosoffice/get_user_profile.php');
        if (res.data?.status === 'success') {
          this.currentUserId = String(res.data.id || res.data.user_id || this.currentUserId || '');
          this.currentUserDeptId = String(
            res.data.department_sub_id || res.data.HR_DEPARTMENT_SUB_ID || ''
          );
          const perms = String(res.data.access_user || '').split(':');
          this.isSystemAdmin = perms.includes('administrator');
        }
      } catch (err) {
        console.error('Failed to load user profile:', err);
      }
    },
    authPayload() {
      return {
        user_id: this.currentUserId || '',
        user_department_id: this.currentUserDeptId || '',
        is_admin: this.isSystemAdmin ? 1 : 0
      };
    },
    async fetchGroups() {
      try {
        const res = await axios.get('/api-digital/dept_schedule/api.php', {
          params: { action: 'groups', ...this.authPayload() }
        });
        if (res.data?.status === 'success') {
          this.groups = res.data.data || [];
        }
      } catch (err) {
        console.error('Failed to load schedule groups:', err);
      }
    },
    async fetchDepartmentsAndStaff() {
      try {
        const [deptRes, staffRes, mophRes] = await Promise.all([
          axios.get('/api-hosoffice/departments.php').catch(() => null),
          axios.get('/api-hosoffice/get_all_staff.php').catch(() => null),
          axios.get('/api-digital/dept_schedule/api.php?action=moph_channels').catch(() => null)
        ]);
        if (deptRes?.data?.status === 'success') {
          this.departments = deptRes.data.data || [];
        } else if (Array.isArray(deptRes?.data)) {
          this.departments = deptRes.data;
        }
        if (staffRes?.data?.status === 'success') {
          this.allStaff = staffRes.data.data || [];
        } else if (Array.isArray(staffRes?.data)) {
          this.allStaff = staffRes.data;
        }
        if (mophRes?.data?.status === 'success') {
          this.mophChannels = mophRes.data.data || [];
        }
      } catch (err) {
        console.error('Failed to load departments/staff:', err);
      }
    },
    async fetchSchedule() {
      if (!this.selectedGroupId) return;
      if (String(this.$route.query.group_id || '') !== String(this.selectedGroupId)) {
        this.$router.replace({
          query: { ...this.$route.query, group_id: this.selectedGroupId, create: undefined }
        });
      }
      this.loading = true;
      this.fetchHolidays();
      try {
        const res = await axios.get('/api-digital/dept_schedule/api.php', {
          params: {
            action: 'get_schedule',
            group_id: this.selectedGroupId,
            year: this.selectedYear,
            month: this.selectedMonth,
            ...this.authPayload()
          }
        });
        if (res.data?.status === 'success') {
          this.members = res.data.data.members || [];
          this.lockInfo = res.data.data.lock_info || null;
          this.isLocked = Boolean(this.lockInfo?.is_locked);
          const shifts = res.data.data.group?.shift_types || [];
          if (
            shifts.length > 0 &&
            !shifts.some((s) => s.code === this.activeBrush) &&
            this.activeBrush !== ''
          ) {
            this.activeBrush = shifts[0].code;
          }
        }
      } catch (err) {
        console.error('Failed to load schedule:', err);
      } finally {
        this.loading = false;
      }
    },
    openGroupModal(group = null) {
      if (group) {
        const parsedDays = String(group.notify_days || '1,2,3,4,5,6,7')
          .split(',')
          .map(Number)
          .filter((n) => n >= 1 && n <= 7);
        this.groupForm = {
          id: group.id,
          department_id: group.department_id || '',
          department_name: group.department_name,
          title: group.title,
          notify_channel: group.notify_channel || '',
          notify_enabled: Number(group.notify_enabled) === 1,
          notify_days: parsedDays.length > 0 ? parsedDays : [1, 2, 3, 4, 5, 6, 7],
          notify_time: group.notify_time || '08:00',
          scheduler_name: group.scheduler_name || '',
          scheduler_position: group.scheduler_position || '',
          shift_types: JSON.parse(JSON.stringify(group.shift_types || []))
        };
      } else {
        this.groupForm = {
          id: 0,
          department_id: '',
          department_name: '',
          title: '',
          notify_channel: '',
          notify_enabled: false,
          notify_days: [1, 2, 3, 4, 5, 6, 7],
          notify_time: '08:00',
          scheduler_name: '',
          scheduler_position: '',
          shift_types: [
            { code: 'ช', label: 'เวรเช้า', color: '#0ea5e9' },
            { code: 'บ', label: 'เวรบ่าย', color: '#f59e0b' },
            { code: 'ด', label: 'เวรดึก', color: '#6366f1' },
            { code: 'Call', label: 'On-Call', color: '#10b981' }
          ]
        };
      }
      this.showGroupModal = true;
    },
    onSelectDepartment() {
      const dept = this.departments.find(
        (d) => String(d.HR_DEPARTMENT_SUB_ID) === String(this.groupForm.department_id)
      );
      if (dept) {
        this.groupForm.department_name = dept.HR_DEPARTMENT_SUB_NAME;
        if (!this.groupForm.title) {
          this.groupForm.title = `ตารางเวร ${dept.HR_DEPARTMENT_SUB_NAME}`;
        }
      }
    },
    addShiftTypeRow() {
      this.groupForm.shift_types.push({ code: '', label: '', color: '#64748b' });
    },
    hasDepartmentSchedule(deptId) {
      if (!deptId) return false;
      return this.groups.some((g) => String(g.department_id) === String(deptId));
    },
    async saveGroup() {
      if (!this.groupForm.department_name.trim() || !this.groupForm.title.trim()) {
        Swal.fire('กรอกข้อมูลไม่ครบ', 'กรุณาระบุชื่อกลุ่มงานและชื่อตารางเวร', 'warning');
        return;
      }
      const validShifts = this.groupForm.shift_types.filter((s) => s.code.trim() !== '');
      if (validShifts.length === 0) {
        Swal.fire('กรอกข้อมูลไม่ครบ', 'กรุณาระบุรหัสเวรอย่างน้อย 1 รายการ', 'warning');
        return;
      }

      this.saving = true;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'save_group',
          id: this.groupForm.id,
          department_id: this.groupForm.department_id || null,
          department_name: this.groupForm.department_name.trim(),
          title: this.groupForm.title.trim(),
          notify_channel: this.groupForm.notify_channel || '',
          notify_enabled: this.groupForm.notify_enabled ? 1 : 0,
          notify_days: this.groupForm.notify_days,
          notify_time: this.groupForm.notify_time || '08:00',
          scheduler_name: this.groupForm.scheduler_name || '',
          scheduler_position: this.groupForm.scheduler_position || '',
          shift_types: validShifts,
          created_by: this.currentUserId || null,
          ...this.authPayload()
        });
        if (res.data?.status === 'success') {
          this.showGroupModal = false;
          await this.fetchGroups();
          this.selectedGroupId = res.data.id;
          await this.fetchSchedule();
        } else {
          Swal.fire('ไม่สำเร็จ', res.data?.message || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกกลุ่มตารางเวรได้', 'error');
      } finally {
        this.saving = false;
      }
    },
    async deleteGroup() {
      const cf = await Swal.fire({
        title: 'ยืนยันการลบกลุ่มตารางเวร?',
        text: 'ตารางเวรนี้จะถูกซ่อนออกจากรายการ',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
      });
      if (!cf.isConfirmed) return;

      await axios.post('/api-digital/dept_schedule/api.php', {
        action: 'delete_group',
        id: this.groupForm.id,
        ...this.authPayload()
      });
      this.showGroupModal = false;
      await this.fetchGroups();
      this.selectedGroupId = this.groups[0]?.id || '';
      if (this.selectedGroupId) {
        await this.fetchSchedule();
      } else {
        this.members = [];
      }
    },
    openAddMemberModal() {
      this.staffSearch = '';
      this.selectedStaffToAdd = [];
      this.manualMemberName = '';
      this.manualMemberPhone = '';
      this.showAllStaff = !this.selectedGroup?.department_id;
      this.showMemberModal = true;
    },
    async confirmAddMembers() {
      if (this.selectedStaffToAdd.length === 0) return;
      this.saving = true;
      try {
        await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'add_members',
          group_id: this.selectedGroupId,
          members: this.selectedStaffToAdd.map((s) => ({
            person_id: s.ID,
            person_name: s.FULLNAME,
            phone: s.HR_PHONE || ''
          })),
          ...this.authPayload()
        });
        this.showMemberModal = false;
        await this.fetchSchedule();
      } finally {
        this.saving = false;
      }
    },
    async addManualMember() {
      const name = this.manualMemberName.trim();
      if (!name) return;
      this.saving = true;
      try {
        await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'add_members',
          group_id: this.selectedGroupId,
          members: [{ person_id: null, person_name: name, phone: this.manualMemberPhone.trim() }],
          ...this.authPayload()
        });
        this.manualMemberName = '';
        this.manualMemberPhone = '';
        await this.fetchSchedule();
      } finally {
        this.saving = false;
      }
    },
    async removeMember(member) {
      const cf = await Swal.fire({
        title: `นำ "${member.person_name}" ออกจากตารางเวร?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'นำออก',
        cancelButtonText: 'ยกเลิก'
      });
      if (!cf.isConfirmed) return;

      await axios.post('/api-digital/dept_schedule/api.php', {
        action: 'remove_member',
        member_id: member.id
      });
      await this.fetchSchedule();
    },
    async moveMember(idx, dir) {
      const targetIdx = idx + dir;
      if (targetIdx < 0 || targetIdx >= this.members.length) return;
      const list = [...this.members];
      const [moved] = list.splice(idx, 1);
      list.splice(targetIdx, 0, moved);
      this.members = list;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'reorder_members',
          group_id: this.selectedGroupId,
          member_ids: list.map((m) => m.id),
          ...this.authPayload()
        });
        if (res.data?.status !== 'success') {
          Swal.fire('ไม่สามารถบันทึกลำดับได้', res.data?.message || 'เกิดข้อผิดพลาด', 'error');
          await this.fetchSchedule();
        }
      } catch (err) {
        console.error('Reorder failed:', err);
        await this.fetchSchedule();
      }
    },
    async toggleCell(member, day) {
      const dayKey = String(day);
      const current = member.duties?.[dayKey] || '';
      const nextVal = current === this.activeBrush ? '' : this.activeBrush;

      if (!member.duties) member.duties = {};
      if (nextVal === '') {
        delete member.duties[dayKey];
      } else {
        member.duties[dayKey] = nextVal;
      }

      try {
        await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'set_cell',
          group_id: this.selectedGroupId,
          member_id: member.id,
          year: this.selectedYear,
          month: this.selectedMonth,
          day,
          shift_code: nextVal,
          ...this.authPayload()
        });
      } catch (err) {
        console.error('Failed to save shift cell:', err);
      }
    },
    async copyPrevMonth() {
      const cf = await Swal.fire({
        title: 'คัดลอกตารางเวรจากเดือนก่อน?',
        text: 'ข้อมูลเวรจากเดือนก่อนหน้าจะถูกคัดลอกมาลงในเดือนที่เลือก',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'คัดลอก',
        cancelButtonText: 'ยกเลิก'
      });
      if (!cf.isConfirmed) return;

      const res = await axios.post('/api-digital/dept_schedule/api.php', {
        action: 'copy_prev_month',
        group_id: this.selectedGroupId,
        year: this.selectedYear,
        month: this.selectedMonth,
        ...this.authPayload()
      });
      if (res.data?.status === 'success') {
        Swal.fire('สำเร็จ', `คัดลอกข้อมูลเวร ${res.data.copied} ช่องเรียบร้อย`, 'success');
        await this.fetchSchedule();
      } else {
        Swal.fire('ไม่พบข้อมูล', res.data?.message || 'ไม่มีข้อมูลในเดือนก่อนหน้า', 'info');
      }
    },
    getShiftColor(code) {
      const st = this.selectedGroup?.shift_types?.find((s) => s.code === code);
      return st?.color || '#4f46e5';
    },
    countMemberShifts(member) {
      return Object.keys(member.duties || {}).length;
    },
    isToday(day) {
      return this.isCurrentMonth && day === this.currentDay;
    },
    async toggleScheduleLock(lock) {
      if (!this.selectedGroupId) return;
      this.togglingLock = true;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php?action=toggle_lock', {
          group_id: this.selectedGroupId,
          year: this.selectedYear,
          month: this.selectedMonth,
          is_locked: lock ? 1 : 0,
          locked_by: localStorage.getItem('user_name') || '',
          ...this.authPayload()
        });
        if (res.data?.status === 'success') {
          this.lockInfo = res.data.data || null;
          this.isLocked = Boolean(this.lockInfo?.is_locked);
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: this.isLocked ? 'success' : 'info',
            title: this.isLocked ? 'ล็อกตารางเวรเรียบร้อยแล้ว' : 'ปลดล็อกตารางเวรแล้ว สามารถแก้ไขได้',
            showConfirmButton: false,
            timer: 1800
          });
        } else {
          Swal.fire('ข้อผิดพลาด', res.data?.message || 'ไม่สามารถเปลี่ยนสถานะล็อกได้', 'error');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
      } finally {
        this.togglingLock = false;
      }
    },
    async fetchHolidays() {
      try {
        const res = await axios.get('/api-digital/duties/get_holidays.php', {
          params: {
            year: this.selectedYear,
            month: this.selectedMonth
          }
        });
        if (res.data?.status === 'success' && res.data.data) {
          this.monthHolidays = res.data.data;
        } else {
          this.monthHolidays = {};
        }
      } catch (err) {
        this.monthHolidays = {};
      }
    },
    getHolidayName(day) {
      return this.monthHolidays?.[String(day)] || '';
    },
    isHoliday(day) {
      return Boolean(this.monthHolidays?.[String(day)]);
    },
    isHolidayOrWeekend(day) {
      return this.isWeekend(day) || this.isHoliday(day);
    },
    isWeekend(day) {
      const wd = new Date(this.selectedYear, this.selectedMonth - 1, day).getDay();
      return wd === 0 || wd === 6;
    },
    getThaiWeekdayShort(day) {
      const names = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
      const wd = new Date(this.selectedYear, this.selectedMonth - 1, day).getDay();
      return names[wd];
    },
    exportExcel() {
      const rows = this.members.map((m) => {
        const obj = { 'ชื่อ - สกุล': m.person_name, เบอร์โทร: m.phone || '' };
        for (const d of this.daysInMonth) {
          obj[String(d)] = m.duties?.[String(d)] || '';
        }
        obj['รวม'] = this.countMemberShifts(m);
        return obj;
      });
      const ws = XLSX.utils.json_to_sheet(rows);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Schedule');
      const fname = `${this.selectedGroup?.title || 'ตารางเวร'}_${this.months[this.selectedMonth - 1]}_${this.selectedYear + 543}.xlsx`;
      XLSX.writeFile(wb, fname);
    },
    setPrintPageRule(ruleCss) {
      let styleEl = document.getElementById('dept-schedule-print-page');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'dept-schedule-print-page';
        document.head.appendChild(styleEl);
      }
      styleEl.textContent = ruleCss;
    },
    onSelectSchedulerStaff(staffId) {
      if (!staffId) return;
      const staff = this.allStaff.find((s) => String(s.ID) === String(staffId));
      if (staff) {
        this.groupForm.scheduler_name = staff.FULLNAME || '';
        this.groupForm.scheduler_position = staff.HR_POSITION_NAME || '';
      }
    },
    openPrintModal() {
      const gid = this.selectedGroupId || 'default';
      this.printNote = localStorage.getItem(`dept_schedule_print_note_${gid}`) || '';
      this.showPrintModal = true;
    },
    confirmPrintPage() {
      const gid = this.selectedGroupId || 'default';
      localStorage.setItem(`dept_schedule_print_note_${gid}`, this.printNote || '');
      this.showPrintModal = false;
      setTimeout(() => {
        this.printPage();
      }, 150);
    },
    printPage() {
      this.setPrintPageRule('@page { size: A4 landscape; margin: 10mm 14mm; }');
      window.print();
    },
    getMophChannelLabel(code) {
      if (!code) return '';
      const ch = this.mophChannels.find((c) => c.channel_code === code);
      return ch ? `${ch.channel_name} (${ch.channel_code})` : code;
    },
    toggleNotifyDay(val) {
      const idx = this.groupForm.notify_days.indexOf(val);
      if (idx >= 0) {
        if (this.groupForm.notify_days.length > 1) {
          this.groupForm.notify_days.splice(idx, 1);
        }
      } else {
        this.groupForm.notify_days.push(val);
        this.groupForm.notify_days.sort((a, b) => a - b);
      }
    },
    formatNotifyDays(daysStr) {
      const arr = String(daysStr || '1,2,3,4,5,6,7')
        .split(',')
        .map(Number)
        .filter((n) => n >= 1 && n <= 7);
      if (arr.length === 7) return 'ทุกวัน';
      if (arr.join(',') === '1,2,3,4,5') return 'จ-ศ';
      const names = { 1: 'จ', 2: 'อ', 3: 'พ', 4: 'พฤ', 5: 'ศ', 6: 'ส', 7: 'อา' };
      return arr.map((n) => names[n]).join(', ');
    },
    async sendTodayNotify() {
      if (!this.selectedGroupId) return;
      this.sendingNotify = true;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'send_today_notify',
          group_id: this.selectedGroupId,
          ...this.authPayload()
        });
        if (res.data?.status === 'success') {
          Swal.fire(
            'ส่งแจ้งเตือนสำเร็จ',
            res.data.message || 'ส่งข้อมูลเข้า MOPH Notify เรียบร้อยแล้ว',
            'success'
          );
        } else {
          Swal.fire('ไม่สำเร็จ', res.data?.message || 'ไม่สามารถส่งแจ้งเตือนได้', 'warning');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการส่งแจ้งเตือน MOPH Notify', 'error');
      } finally {
        this.sendingNotify = false;
      }
    },
    async testNotifyChannel() {
      if (!this.groupForm.notify_channel) return;
      this.sendingNotify = true;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'test_notify',
          channel_code: this.groupForm.notify_channel,
          title: this.groupForm.title || this.groupForm.department_name || 'ตารางเวรกลุ่มงาน',
          group_id: this.groupForm.id || 0,
          ...this.authPayload()
        });
        if (res.data?.status === 'success') {
          Swal.fire(
            'ทดสอบส่งสำเร็จ',
            res.data.message || 'ส่งข้อความทดสอบเข้า MOPH Notify เรียบร้อยแล้ว',
            'success'
          );
        } else {
          Swal.fire('ส่งไม่สำเร็จ', res.data?.message || 'ไม่สามารถส่งแจ้งเตือนได้', 'warning');
        }
      } catch (err) {
        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการทดสอบส่ง MOPH Notify', 'error');
      } finally {
        this.sendingNotify = false;
      }
    },
    async openGroupSchedule(group) {
      this.selectedGroupId = group.id;
      this.viewMode = 'schedule';
      await this.fetchSchedule();
    },
    async openPermissionForGroup(group) {
      this.selectedGroupId = group.id;
      await this.openPermissionModal();
    },
    async openPermissionModal() {
      this.permUserSearch = '';
      this.showAllPermStaff = !this.selectedGroup?.department_id;
      this.permForm = { target_user_id: '', access_level: 'STAFF' };
      this.showPermissionModal = true;
      await this.fetchGroupUsers();
    },
    async fetchGroupUsers() {
      if (!this.selectedGroupId) return;
      try {
        const res = await axios.get('/api-digital/dept_schedule/api.php', {
          params: { action: 'group_users', group_id: this.selectedGroupId }
        });
        if (res.data?.status === 'success') {
          this.groupUsers = res.data.data || [];
        }
      } catch (err) {
        console.error('Failed to fetch group users:', err);
      }
    },
    async saveGroupUserPermission() {
      const staff = this.allStaff.find(
        (s) => String(s.ID) === String(this.permForm.target_user_id)
      );
      if (!staff) return;
      this.saving = true;
      try {
        const res = await axios.post('/api-digital/dept_schedule/api.php', {
          action: 'save_group_user',
          group_id: this.selectedGroupId,
          target_user_id: staff.ID,
          target_user_name: staff.FULLNAME,
          access_level: this.permForm.access_level,
          ...this.authPayload()
        });
        if (res.data?.status === 'success') {
          this.permForm.target_user_id = '';
          await this.fetchGroupUsers();
        } else {
          Swal.fire('ไม่สำเร็จ', res.data?.message || 'ไม่สามารถบันทึกสิทธิ์ได้', 'error');
        }
      } finally {
        this.saving = false;
      }
    },
    async updateGroupUserLevel(user, newLevel) {
      await axios.post('/api-digital/dept_schedule/api.php', {
        action: 'save_group_user',
        group_id: this.selectedGroupId,
        target_user_id: user.user_id,
        target_user_name: user.user_name,
        access_level: newLevel,
        ...this.authPayload()
      });
      await this.fetchGroupUsers();
    },
    async removeGroupUserPermission(user) {
      await axios.post('/api-digital/dept_schedule/api.php', {
        action: 'remove_group_user',
        group_id: this.selectedGroupId,
        id: user.id,
        ...this.authPayload()
      });
      await this.fetchGroupUsers();
    },
    openDutyMemoModal() {
      const g = this.selectedGroup;
      if (!g) return;
      const now = new Date();
      const yyyy = now.getFullYear();
      const mm = String(now.getMonth() + 1).padStart(2, '0');
      const dd = String(now.getDate()).padStart(2, '0');
      const bookPrefix = g.book_num
        ? g.book_num.endsWith('/')
          ? g.book_num
          : `${g.book_num}/`
        : 'อจ.0033/';
      this.dutyMemoForm = {
        agency: `${g.department_name} โรงพยาบาลชานุมาน`,
        docNo: bookPrefix,
        docDate: `${yyyy}-${mm}-${dd}`,
        subject: `ขออนุมัติปฏิบัติงานนอกเวลาราชการ (${g.department_name})`,
        reason: `ด้วย${g.department_name} มีความจำเป็นต้องจัดเจ้าหน้าที่ขึ้นปฏิบัติงานนอกเวลาราชการ เพื่อดูแลให้บริการผู้ป่วยและงานราชการได้อย่างต่อเนื่องและมีประสิทธิภาพ`,
        leaderName: g.leader_name || '',
        leaderPos: g.leader_position || '',
        onlyScheduled: true
      };
      this.showDutyMemoModal = true;
    },
    formatThaiMemoDate(dateStr) {
      if (!dateStr) return '-';
      const parts = String(dateStr).split('-');
      if (parts.length !== 3) return dateStr;
      const y = parseInt(parts[0], 10) + 543;
      const m = parseInt(parts[1], 10);
      const d = parseInt(parts[2], 10);
      return `${d} ${this.months[m - 1] || ''} ${y}`;
    },
    printDutyMemo() {
      this.setPrintPageRule('@page { size: A4 portrait; margin: 0; }');
      document.body.classList.add('printing-duty-memo');
      const cleanup = () => {
        document.body.classList.remove('printing-duty-memo');
        this.setPrintPageRule('@page { size: A4 landscape; margin: 10mm 14mm; }');
        window.removeEventListener('afterprint', cleanup);
      };
      window.addEventListener('afterprint', cleanup);
      this.$nextTick(() => {
        window.print();
        setTimeout(cleanup, 1500);
      });
    }
  }
};
</script>

<style scoped>
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun.ttf') format('truetype');
  font-weight: normal;
  font-style: normal;
}
@font-face {
  font-family: 'TH Sarabun New';
  src: url('/fonts/THSarabun%20Bold.ttf') format('truetype');
  font-weight: bold;
  font-style: normal;
}
.header-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.empty-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.oncall-banner {
  background: linear-gradient(90deg, #f0fdf4, #ffffff);
  border-left: 4px solid #16a34a !important;
}

.oncall-chip {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 0.25rem 0.75rem 0.25rem 0.35rem;
  font-size: 0.85rem;
}

.shift-badge,
.duty-pill {
  display: inline-block;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.75rem;
  padding: 0.2rem 0.45rem;
  border-radius: 6px;
  min-width: 26px;
  text-align: center;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  display: inline-block;
}

.schedule-table thead th {
  background-color: #f8fafc;
  font-size: 0.78rem;
  border-color: #e2e8f0;
}

.sticky-col {
  position: sticky;
  left: 0;
  background-color: #ffffff !important;
  z-index: 2;
  border-right: 2px solid #e2e8f0 !important;
}

.day-header,
.day-cell {
  min-width: 38px;
  padding: 0.35rem 0.2rem !important;
}

.day-cell:hover {
  background-color: #f1f5f9 !important;
}

.is-today-col {
  background-color: #eef2ff !important;
}

.is-weekend-col {
  background-color: #cbd5e1 !important;
}

th.is-weekend-col {
  background-color: #94a3b8 !important;
  color: #0f172a !important;
}

.total-col {
  min-width: 54px;
  background-color: #fcfcfd;
}

.staff-option:hover {
  background-color: #f8fafc;
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.45);
}

@media print {
  .print-hide {
    display: none !important;
  }

  .dept-schedule-page {
    padding: 0 10mm !important;
    margin: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
  }

  .print-sheet {
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible !important;
  }

  .print-table-wrap {
    overflow: visible !important;
  }

  .schedule-table {
    width: 100% !important;
    font-size: 9pt !important;
    border-color: #000 !important;
  }

  .schedule-table th,
  .schedule-table td {
    border-color: #000 !important;
    padding: 2px 3px !important;
    color: #000 !important;
  }

  .sticky-col {
    position: static !important;
    min-width: 145px !important;
    border-right: 1px solid #000 !important;
    padding-left: 6px !important;
  }

  .day-header,
  .day-cell {
    min-width: 19px !important;
    width: 21px !important;
    padding: 2px 1px !important;
  }

  .day-name {
    font-size: 7pt !important;
  }

  .is-weekend-col {
    background-color: #cbd5e1 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  th.is-weekend-col {
    background-color: #94a3b8 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .is-today-col {
    background-color: transparent !important;
  }

  .duty-pill {
    background-color: transparent !important;
    color: #000 !important;
    font-weight: 700 !important;
    font-size: 9pt !important;
    padding: 0 !important;
    min-width: auto !important;
  }

  .total-col {
    min-width: 30px !important;
    color: #000 !important;
    background-color: transparent !important;
  }
}
</style>

<style>
@media print {
  body.printing-duty-memo header,
  body.printing-duty-memo nav,
  body.printing-duty-memo .navbar,
  body.printing-duty-memo footer,
  body.printing-duty-memo .dept-schedule-page > *:not(.duty-memo-modal-root),
  body.printing-duty-memo .modal-backdrop,
  body.printing-duty-memo .duty-memo-sidebar,
  body.printing-duty-memo .modal-header,
  body.printing-duty-memo .modal-footer {
    display: none !important;
  }

  body.printing-duty-memo .dept-schedule-page {
    padding: 0 !important;
    margin: 0 !important;
  }

  body.printing-duty-memo .duty-memo-modal-root {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
  }

  body.printing-duty-memo .duty-memo-modal-root .modal-dialog,
  body.printing-duty-memo .duty-memo-modal-root .modal-content,
  body.printing-duty-memo .duty-memo-modal-root .modal-body,
  body.printing-duty-memo .duty-memo-modal-root .row,
  body.printing-duty-memo .duty-memo-col {
    position: static !important;
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    background: #fff !important;
    overflow: visible !important;
  }

  body.printing-duty-memo .duty-memo-paper {
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 2.3cm 2cm 2cm 3cm !important;
    margin: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
  }
}
</style>
