<template>
  <div class="leave-calendar-page">
    <div class="header-section">
      <div class="title-area">
        <h2 class="page-title"><i class="bi bi-calendar2-week"></i> ปฏิทินวันลา / ไปราชการ</h2>
        <p class="text-muted">กลุ่มงานสุขภาพดิจิทัล และกลุ่มงานประกันสุขภาพ ยุทธศาสตร์</p>
      </div>
      <div class="controls-area">
        <button class="btn btn-outline-primary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
        <h4 class="mb-0 mx-3">{{ currentMonthName }} {{ currentYear }}</h4>
        <button class="btn btn-outline-primary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>
        <button class="btn btn-primary ms-3" @click="goToToday">วันนี้</button>
        
        <!-- Small Stats -->
        <div class="d-flex align-items-center gap-2 border-start ps-3 ms-3">
          <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-normal" title="ไปราชการวันนี้">
            <i class="bi bi-briefcase-fill me-1"></i>ไปราชการ {{ countTrips }}
          </div>
          <div class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-2 fw-normal" title="ลาวันนี้">
            <i class="bi bi-person-dash-fill me-1"></i>ลา {{ countLeaves }}
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
        <div class="day-name text-danger">เสาร์</div>
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
            <div class="day-number" :class="{'text-danger': isWeekend(day.date)}">{{ getDayNumber(day.date) }}</div>
            <div class="events-container">
              <div 
                v-for="(event, idx) in day.events.slice(0, 3)" 
                :key="idx" 
                class="event-badge"
                :class="getEventColorClass(event)"
              >
                <i :class="getEventIcon(event)" class="me-1 flex-shrink-0"></i>
                <span class="event-text text-truncate" :title="event.person_name + ' - ' + event.title">
                  {{ event.person_name.split(' ')[0] }} ({{ event.event_type === 'TRIP' ? 'ไปราชการ' : 'ลา' }})
                </span>
              </div>
              <div v-if="day.events.length > 3" class="more-events">
                +{{ day.events.length - 3 }} รายการ
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
              รายการวันที่ {{ selectedDateText }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-3">
            <div v-if="selectedDayEvents.length === 0" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
              ไม่มีรายการลา หรือ ไปราชการ ในวันนี้
            </div>
            <div v-else class="event-list">
              <div v-for="(event, idx) in selectedDayEvents" :key="idx" class="event-card mb-3" :class="getEventCardBorder(event)">
                <div class="event-icon-area" :class="getEventIconBg(event)">
                  <i :class="getEventIcon(event)" class="fs-4"></i>
                </div>
                <div class="event-details">
                  <h6 class="destination mb-1" :class="getEventTextColor(event)">
                    {{ event.person_name }}
                  </h6>
                  <p class="mb-1 text-dark fw-bold">
                    {{ event.event_type === 'TRIP' ? 'ไปราชการ' : 'การลา' }}: {{ event.title }}
                  </p>
                  <p class="mb-1 text-muted small" v-if="event.detail">
                    <i class="bi bi-info-circle me-1"></i> รายละเอียด: {{ event.detail }}
                  </p>
                  <p class="mb-1 text-muted small" v-if="event.event_type === 'TRIP' && event.attendees">
                    <i class="bi bi-people-fill me-1"></i> ผู้ร่วมเดินทาง: {{ event.attendees }}
                  </p>
                  <div class="info-grid mt-2">
                    <div class="info-item">
                      <small class="text-muted d-block">วันที่เริ่มต้น</small>
                      <span>{{ formatDateThai(event.start_date) }}</span>
                    </div>
                    <div class="info-item">
                      <small class="text-muted d-block">วันที่สิ้นสุด</small>
                      <span>{{ formatDateThai(event.end_date) }}</span>
                    </div>
                    <div class="info-item">
                      <small class="text-muted d-block">สถานะ</small>
                      <span :class="getStatusBadge(event)">{{ getStatusText(event) }}</span>
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
  name: 'LeaveCalendar',
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
      for (let i = 0; i < startingDayOfWeek; i++) {
        days.push({ date: null, events: [] });
      }

      for (let i = 1; i <= totalDays; i++) {
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        
        const dayEvents = events.value.filter(e => {
          return dateString >= e.start_date && dateString <= e.end_date;
        });

        days.push({
          date: dateString,
          events: dayEvents
        });
      }

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
      return events.value.filter(e => td >= e.start_date && td <= e.end_date);
    });

    const countTrips = computed(() => todayEvents.value.filter(e => e.event_type === 'TRIP').length);
    const countLeaves = computed(() => todayEvents.value.filter(e => e.event_type === 'LEAVE').length);

    const fetchEvents = async () => {
      loading.value = true;
      try {
        const year = currentDate.value.getFullYear();
        const month = currentDate.value.getMonth() + 1;
        const url = `${import.meta.env.VITE_API_URL || '/backend'}/api-digital/leave/get_leave_schedule.php?year=${year}&month=${month}`;
        const response = await axios.get(url);
        if (response.data.status === 'success') {
          events.value = response.data.data;
        }
      } catch (error) {
        console.error('Error fetching leave events:', error);
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

    const isWeekend = (dateString) => {
      if (!dateString) return false;
      const d = new Date(dateString);
      return d.getDay() === 0 || d.getDay() === 6;
    };

    const getEventColorClass = (event) => {
      if (event.event_type === 'TRIP') return 'event-primary';
      
      // Based on title (ลาป่วย, ลากิจ, ลาพักผ่อน)
      if (event.title && event.title.includes('ป่วย')) return 'event-danger';
      if (event.title && (event.title.includes('กิจ') || event.title.includes('คลอด'))) return 'event-warning';
      if (event.title && event.title.includes('พักผ่อน')) return 'event-success';
      
      return 'event-secondary';
    };

    const getEventCardBorder = (event) => {
      if (event.event_type === 'TRIP') return 'border-primary';
      if (event.title && event.title.includes('ป่วย')) return 'border-danger';
      if (event.title && event.title.includes('กิจ')) return 'border-warning';
      if (event.title && event.title.includes('พักผ่อน')) return 'border-success';
      return 'border-secondary';
    };

    const getEventIconBg = (event) => {
      if (event.event_type === 'TRIP') return 'bg-primary-subtle text-primary';
      if (event.title && event.title.includes('ป่วย')) return 'bg-danger-subtle text-danger';
      if (event.title && event.title.includes('กิจ')) return 'bg-warning-subtle text-warning';
      if (event.title && event.title.includes('พักผ่อน')) return 'bg-success-subtle text-success';
      return 'bg-secondary-subtle text-secondary';
    };

    const getEventTextColor = (event) => {
      if (event.event_type === 'TRIP') return 'text-primary';
      if (event.title && event.title.includes('ป่วย')) return 'text-danger';
      if (event.title && event.title.includes('กิจ')) return 'text-warning';
      if (event.title && event.title.includes('พักผ่อน')) return 'text-success';
      return 'text-secondary';
    };

    const getEventIcon = (event) => {
      if (event.event_type === 'TRIP') return 'bi-briefcase-fill';
      return 'bi-person-dash-fill';
    };

    const getStatusText = (event) => {
      if (event.status_id === 'Z') return 'อนุมัติแล้ว';
      if (event.status_id === 'SUCCESS') return 'อนุมัติแล้ว';
      if (event.status_id === 'REQUEST') return 'รออนุมัติ';
      return event.status_id || 'บันทึกแล้ว';
    };

    const getStatusBadge = (event) => {
      if (event.status_id === 'Z' || event.status_id === 'SUCCESS') return 'badge bg-success-subtle text-success border border-success';
      if (event.status_id === 'REQUEST') return 'badge bg-warning-subtle text-warning border border-warning';
      return 'badge bg-light text-dark border';
    };

    const formatDateThai = (dateString) => {
      if (!dateString) return '-';
      const d = new Date(dateString);
      return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear() + 543}`;
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
      isWeekend,
      openDayDetail,
      dayDetailModalRef,
      selectedDateText,
      selectedDayEvents,
      countTrips,
      countLeaves,
      getEventColorClass,
      getEventIcon,
      getEventCardBorder,
      getEventIconBg,
      getEventTextColor,
      getStatusText,
      getStatusBadge,
      formatDateThai
    };
  }
};
</script>

<style scoped>
.leave-calendar-page {
  background-color: #f8fafc;
  height: 100%;
  display: flex;
  flex-direction: column;
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
  flex-grow: 1;
}

.calendar-day {
  min-height: 100px;
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
  font-size: 0.75rem;
  padding: 3px 6px;
  border-radius: 4px;
  max-width: 100%;
}

.event-primary {
  background-color: #eff6ff;
  color: #1d4ed8;
  border-left: 3px solid #3b82f6;
}

.event-success {
  background-color: #f0fdf4;
  color: #15803d;
  border-left: 3px solid #22c55e;
}

.event-danger {
  background-color: #fef2f2;
  color: #b91c1c;
  border-left: 3px solid #ef4444;
}

.event-warning {
  background-color: #fffbeb;
  color: #b45309;
  border-left: 3px solid #f59e0b;
}

.event-secondary {
  background-color: #f1f5f9;
  color: #475569;
  border-left: 3px solid #94a3b8;
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
  background: #fff;
  border-left: 4px solid;
  border-radius: 0.5rem;
  padding: 1.25rem;
  gap: 1.5rem;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.event-icon-area {
  flex-shrink: 0;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.event-details {
  flex-grow: 1;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  background: #f8fafc;
  padding: 0.75rem;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
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
    padding: 0.25rem;
  }
  
  .event-text {
    font-size: 0.65rem;
  }
  
  .event-card {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
