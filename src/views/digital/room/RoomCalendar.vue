<template>
  <div class="room-calendar-page">
    <div class="header-section">
      <div class="title-area">
        <h2 class="page-title"><i class="bi bi-display"></i> ปฏิทินจองห้องประชุม (Meeting Room Schedule)</h2>
        <p class="text-muted">ดูตารางการใช้ห้องประชุมในแต่ละวัน</p>
      </div>
      <div class="controls-area">
        <button class="btn btn-outline-primary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
        <h4 class="mb-0 mx-3">{{ currentMonthName }} {{ currentYear }}</h4>
        <button class="btn btn-outline-primary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>
        <button class="btn btn-primary ms-3" @click="goToToday">วันนี้</button>
        
        <!-- Small Stats -->
        <div class="d-flex align-items-center gap-2 border-start ps-3 ms-3">
          <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-normal" title="รายการวันนี้ทั้งหมด">
            <i class="bi bi-display me-1"></i>รวม {{ countTotal }}
          </div>
          <div class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-normal" title="อนุมัติแล้ว">
            <i class="bi bi-check-circle-fill me-1"></i>อนุมัติ {{ countAllocated }}
          </div>
          <div class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2 fw-normal" title="รออนุมัติ">
            <i class="bi bi-clock-fill me-1"></i>รอ {{ countPending }}
          </div>
        </div>
      </div>
    </div>



    <!-- Calendar Grid -->
    <div class="calendar-card">
      <div class="calendar-header">
        <div class="day-name text-danger">อาทิตย์</div>
        <div class="day-name">จันทร์</div>
        <div class="day-name">อังคาร</div>
        <div class="day-name">พุธ</div>
        <div class="day-name">พฤหัสบดี</div>
        <div class="day-name">ศุกร์</div>
        <div class="day-name">เสาร์</div>
      </div>
      <div class="calendar-body">
        <div 
          v-for="(day, index) in calendarDays" 
          :key="index" 
          class="calendar-day" 
          :class="{ 
            'empty-day': !day.date, 
            'today': isToday(day.date),
            'has-events': day.events && day.events.length > 0
          }"
          @click="day.date ? openDayDetail(day) : null"
        >
          <template v-if="day.date">
            <div class="day-number">{{ getDayNumber(day.date) }}</div>
            <div class="events-container">
              <div 
                v-for="(event, idx) in day.events.slice(0, 2)" 
                :key="idx" 
                class="event-badge"
                :class="getEventColorClass(event)"
              >
                <i class="bi bi-display flex-shrink-0 me-1"></i>
                <span class="event-text text-truncate">
                  {{ formatTime(event.TIME_BEGIN) }} น. - 
                  {{ truncateText(event.room_name || event.SERVICE_STORY, 30) }}
                </span>
              </div>
              <div v-if="day.events.length > 2" class="more-events">
                +{{ day.events.length - 2 }} รายการ
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Day Detail Modal -->
    <div class="modal fade" id="dayDetailModal" tabindex="-1" aria-hidden="true" ref="dayDetailModalRef">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-calendar-event me-2 text-primary"></i> 
              รายการใช้ห้องประชุมวันที่ {{ selectedDateText }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-3">
            <div v-if="selectedDayEvents.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
              ไม่มีรายการใช้ห้องประชุมในวันนี้
            </div>
            <div v-else class="event-list">
              <div v-for="(event, idx) in selectedDayEvents" :key="idx" class="event-card mb-3">
                <div class="event-time">
                  <span class="time-badge">
                    {{ formatTime(event.TIME_BEGIN) }} - {{ formatTime(event.TIME_END) }}
                  </span>
                </div>
                <div class="event-details">
                  <h6 class="destination mb-1 text-primary">
                    <i class="bi bi-journal-text me-1"></i> {{ event.SERVICE_STORY || 'ไม่มีชื่อเรื่อง' }}
                  </h6>
                  <p class="mb-1 text-muted small">
                    <i class="bi bi-door-open-fill me-1"></i> ห้อง: {{ event.room_name || '-' }}
                  </p>
                  
                  <div class="info-grid mt-3">
                    <div class="info-item">
                      <small class="text-muted d-block">ผู้ขอ/หน่วยงาน</small>
                      <span>{{ event.PERSON_REQUEST_NAME || '-' }} <span v-if="event.PERSON_REQUEST_DEP">({{ event.PERSON_REQUEST_DEP }})</span></span>
                    </div>
                    <div class="info-item">
                      <small class="text-muted d-block">จำนวนผู้เข้าร่วม</small>
                      <span><i class="bi bi-people-fill me-1"></i>{{ event.TOTAL_PEOPLE || 'ไม่ระบุ' }} ท่าน</span>
                    </div>
                    <div class="info-item">
                      <small class="text-muted d-block">รูปแบบ</small>
                      <span class="badge bg-light text-dark border">
                        {{ getAppTypeText(event.APP_TYPE_SAVE) }}
                      </span>
                    </div>
                    <div class="info-item">
                      <small class="text-muted d-block">สถานะ</small>
                      <span :class="getStatusBadge(event.STATUS)">{{ getStatusText(event.STATUS) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

export default {
  name: 'RoomCalendar',
  setup() {
    const currentDate = ref(new Date());
    const events = ref([]);
    const loading = ref(false);
    const dayDetailModalRef = ref(null);
    let modalInstance = null;
    const selectedDay = ref(null);

    const monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    const currentYear = computed(() => currentDate.value.getFullYear() + 543);
    const currentMonthName = computed(() => monthNames[currentDate.value.getMonth()]);

    const calendarDays = computed(() => {
      const year = currentDate.value.getFullYear();
      const month = currentDate.value.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      
      const startingDayOfWeek = firstDay.getDay(); 
      const totalDays = lastDay.getDate();

      const days = [];
      // Empty blocks before the first day of the month
      for (let i = 0; i < startingDayOfWeek; i++) {
        days.push({ date: null, events: [] });
      }

      // Actual days of the month
      for (let i = 1; i <= totalDays; i++) {
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        
        // Find events that fall on this day
        const dayEvents = events.value.filter(e => {
          const beginStr = e.DATE_BEGIN;
          const endStr = e.DATE_END || e.DATE_BEGIN;
          return dateString >= beginStr && dateString <= endStr;
        });

        days.push({
          date: dateString,
          events: dayEvents
        });
      }

      // Empty blocks after the last day to complete the week
      const remainingDays = 7 - (days.length % 7);
      if (remainingDays < 7) {
        for (let i = 0; i < remainingDays; i++) {
          days.push({ date: null, events: [] });
        }
      }

      return days;
    });

    const selectedDateText = computed(() => {
      if (!selectedDay.value || !selectedDay.value.date) return '';
      const d = new Date(selectedDay.value.date);
      return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear() + 543}`;
    });

    const selectedDayEvents = computed(() => {
      return selectedDay.value ? selectedDay.value.events : [];
    });

    const todayEvents = computed(() => {
      const today = new Date();
      const td = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      return events.value.filter(e => td >= e.DATE_BEGIN && td <= (e.DATE_END || e.DATE_BEGIN));
    });

    const countTotal = computed(() => todayEvents.value.length);
    const countAllocated = computed(() => todayEvents.value.filter(e => e.STATUS === 'SUCCESS').length);
    const countPending = computed(() => todayEvents.value.filter(e => e.STATUS === 'REQUEST').length);

    const fetchEvents = async () => {
      loading.value = true;
      try {
        const year = currentDate.value.getFullYear();
        const month = currentDate.value.getMonth() + 1;
        const url = `${import.meta.env.VITE_API_URL || '/backend'}/api-digital/room/get_room_schedule.php?year=${year}&month=${month}`;
        const response = await axios.get(url);
        if (response.data.status === 'success') {
          events.value = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching room events:', error);
      } finally {
        loading.value = false;
      }
    };

    const prevMonth = () => {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
      fetchEvents();
    };

    const nextMonth = () => {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
      fetchEvents();
    };

    const goToToday = () => {
      currentDate.value = new Date();
      fetchEvents();
    };

    const getDayNumber = (dateString) => {
      if (!dateString) return '';
      return parseInt(dateString.split('-')[2], 10);
    };

    const isToday = (dateString) => {
      if (!dateString) return false;
      const today = new Date();
      const td = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      return dateString === td;
    };

    const formatTime = (timeStr) => {
      if (!timeStr) return '-';
      return timeStr.substring(0, 5);
    };

    const truncateText = (text, length = 10) => {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    };

    const getEventColorClass = (event) => {
      if (event.STATUS === 'SUCCESS') return 'event-success';
      if (event.STATUS === 'CANCEL') return 'event-danger';
      return 'event-warning';
    };

    const getStatusText = (status) => {
      if (status === 'SUCCESS') return 'อนุมัติ';
      if (status === 'CANCEL') return 'ยกเลิก';
      if (status === 'REQUEST') return 'ร้องขอ';
      return status || 'ไม่ระบุ';
    };

    const getStatusBadge = (status) => {
      if (status === 'SUCCESS') return 'badge bg-success-subtle text-success border border-success';
      if (status === 'CANCEL') return 'badge bg-danger-subtle text-danger border border-danger';
      if (status === 'REQUEST') return 'badge bg-warning-subtle text-warning border border-warning';
      return 'badge bg-light text-dark border';
    };

    const getAppTypeText = (type) => {
      if (type === 'WIN') return 'On-site';
      if (type === 'ONLINE') return 'Online';
      if (type === 'MIX') return 'Hybrid (On-site + Online)';
      return type || 'ปกติ';
    };

    const openDayDetail = (day) => {
      selectedDay.value = day;
      if (!modalInstance && dayDetailModalRef.value) {
        modalInstance = new Modal(dayDetailModalRef.value);
      }
      if (modalInstance) {
        modalInstance.show();
      }
    };

    onMounted(() => {
      fetchEvents();
      nextTick(() => {
        if (dayDetailModalRef.value) {
          modalInstance = new Modal(dayDetailModalRef.value);
        }
      });
    });

    return {
      currentDate,
      currentYear,
      currentMonthName,
      calendarDays,
      loading,
      prevMonth,
      nextMonth,
      goToToday,
      getDayNumber,
      isToday,
      openDayDetail,
      dayDetailModalRef,
      selectedDateText,
      selectedDayEvents,
      countTotal,
      countAllocated,
      countPending,
      formatTime,
      truncateText,
      getEventColorClass,
      getStatusText,
      getStatusBadge,
      getAppTypeText
    };
  }
};
</script>

<style scoped>
.room-calendar-page {
  padding: 2rem;
  background-color: #f8fafc;
  min-height: calc(100vh - 60px);
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.page-title {
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.controls-area {
  display: flex;
  align-items: center;
}

.calendar-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.calendar-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background-color: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
}

.day-name {
  padding: 1rem;
  text-align: center;
  font-weight: 600;
  color: #64748b;
  font-size: 0.875rem;
}

.calendar-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-auto-rows: 1fr;
}

.calendar-day {
  min-height: 90px;
  min-width: 0;
  border-right: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.5rem;
  transition: all 0.2s ease;
  cursor: pointer;
  background-color: white;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day:hover:not(.empty-day) {
  background-color: #f8fafc;
}

.empty-day {
  background-color: #f8fafc;
  cursor: default;
}

.today {
  background-color: #eff6ff;
}

.today .day-number {
  background-color: #3b82f6;
  color: white;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.day-number {
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  display: inline-block;
}

.events-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.event-badge {
  display: flex;
  align-items: center;
  font-size: 0.7rem;
  padding: 2px 4px;
  border-radius: 4px;
  background-color: #eff6ff;
  color: #1d4ed8;
  border-left: 3px solid #3b82f6;
  max-width: 100%;
}

.event-success {
  background-color: #f0fdf4;
  color: #15803d;
  border-left-color: #22c55e;
}

.event-danger {
  background-color: #fef2f2;
  color: #b91c1c;
  border-left-color: #ef4444;
}

.event-warning {
  background-color: #fefce8;
  color: #b45309;
  border-left-color: #eab308;
}

.event-text {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.more-events {
  font-size: 0.7rem;
  color: #64748b;
  text-align: center;
  font-weight: 600;
  margin-top: 2px;
}

/* Modal Styling */
.modal-content {
  border-radius: 1rem;
  border: none;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.event-card {
  display: flex;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1.25rem;
  gap: 1.5rem;
  align-items: stretch;
}

.event-time {
  flex-shrink: 0;
  width: 120px;
  border-right: 2px dashed #cbd5e1;
  padding-right: 1.5rem;
  display: flex;
  align-items: center;
}

.time-badge {
  font-weight: 700;
  color: #475569;
  background: white;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  display: inline-block;
  text-align: center;
  width: 100%;
}

.event-details {
  flex-grow: 1;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.info-item span {
  font-weight: 500;
  color: #1e293b;
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .calendar-day {
    min-height: 80px;
    padding: 0.5rem;
  }
  
  .event-text {
    display: none;
  }
  
  .event-card {
    flex-direction: column;
    gap: 1rem;
  }
  
  .event-time {
    width: 100%;
    border-right: none;
    border-bottom: 2px dashed #cbd5e1;
    padding-right: 0;
    padding-bottom: 1rem;
  }
  
  .time-badge {
    text-align: left;
    width: auto;
  }
}
</style>
