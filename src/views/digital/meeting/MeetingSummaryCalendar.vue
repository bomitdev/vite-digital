<template>
  <div class="meeting-list-page">
    <div class="header-section">
      <div class="title-area">
        <h2 class="page-title"><i class="bi bi-journal-check"></i> รายการความรู้ที่ได้จากการประชุม/อบรม/สัมนา</h2>
      </div>
      <div class="controls-area">
        <button class="btn btn-outline-primary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
        <h4 class="mb-0 mx-3">{{ currentMonthName }} {{ currentYear }}</h4>
        <button class="btn btn-outline-primary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>
        <button class="btn btn-primary ms-3" @click="goToToday">เดือนปัจจุบัน</button>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mt-4">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="px-4 py-3" style="width: 15%">วันที่</th>
                <th class="px-4 py-3" style="width: 20%">ผู้ไปราชการ</th>
                <th class="px-4 py-3" style="width: 30%">หัวข้อ/โครงการ</th>
                <th class="px-4 py-3" style="width: 10%">ประเภท</th>
                <th class="px-4 py-3 text-center" style="width: 10%">ผู้ร่วมเดินทาง</th>
                <th class="px-4 py-3 text-center" style="width: 15%">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-5 text-muted">
                  <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <div>กำลังโหลดข้อมูล...</div>
                </td>
              </tr>
              <tr v-else-if="events.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  ไม่มีรายการในเดือนนี้
                </td>
              </tr>
              <tr v-else v-for="(event, index) in events" :key="index">
                <td class="px-4">
                  <div class="fw-bold text-primary">{{ event.DATE_BEGIN }}</div>
                  <div class="small text-muted" v-if="event.DATE_BEGIN !== event.DATE_END">
                    ถึง {{ event.DATE_END }}
                  </div>
                </td>
                <td class="px-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar bg-primary-subtle text-primary rounded-circle p-2 me-2">
                      <i class="bi bi-person"></i>
                    </div>
                    <span class="fw-medium">{{ event.SEND_HR_NAME || '-' }}</span>
                  </div>
                </td>
                <td class="px-4">
                  <div class="fw-semibold text-dark">{{ event.GO_NAME }}</div>
                  <div class="small text-muted mt-1" v-if="event.BOOK_NUM">
                    <i class="bi bi-book me-1"></i>เลขที่หนังสือ: {{ event.BOOK_NUM }}
                  </div>
                </td>
                <td class="px-4">
                  <span class="badge bg-light text-dark border">
                    {{ event.GO_TYPE_NAME || '-' }}
                  </span>
                </td>
                <td class="px-4 text-center">
                  <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                    <i class="bi bi-people-fill me-1"></i>
                    {{ countPeople(event.GOTO_WITH) }} คน
                  </span>
                </td>
                <td class="px-4 text-center">
                  <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="openDetail(event)">
                    <i class="bi bi-search me-1"></i> รายละเอียด
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true" ref="eventDetailModalRef">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-journal-text me-2 text-primary"></i> 
              รายละเอียดการประชุม/ไปราชการ
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body pt-4" v-if="selectedEvent">
            
            <div class="mb-4">
              <h5 class="text-primary mb-2">{{ selectedEvent.GO_NAME }}</h5>
              <div class="d-flex gap-3 text-muted small">
                <span><i class="bi bi-calendar3 me-1"></i> {{ selectedEvent.DATE_BEGIN }} - {{ selectedEvent.DATE_END }}</span>
                <span><i class="bi bi-tag-fill me-1"></i> {{ selectedEvent.GO_TYPE_NAME || '-' }}</span>
                <span><i class="bi bi-book-fill me-1"></i> {{ selectedEvent.BOOK_NUM || '-' }}</span>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100 border">
                  <h6 class="text-dark fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>ผู้ไปราชการ</h6>
                  <p class="mb-1">{{ selectedEvent.SEND_HR_NAME || '-' }}</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 bg-light rounded h-100 border">
                  <h6 class="text-dark fw-bold mb-3"><i class="bi bi-people-fill me-2"></i>ผู้ร่วมเดินทาง</h6>
                  <p class="mb-1" style="white-space: pre-line;">{{ selectedEvent.GOTO_WITH || '-' }}</p>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-primary-subtle rounded border border-primary-subtle">
                  <h6 class="text-primary fw-bold mb-2"><i class="bi bi-bullseye me-2"></i>สิ่งที่คาดหวัง</h6>
                  <p class="mb-0 text-dark">{{ selectedEvent.DETAIL_EXPECT || 'ไม่ได้ระบุ' }}</p>
                </div>
              </div>
              <div class="col-12">
                <div class="p-3 bg-success-subtle rounded border border-success-subtle">
                  <h6 class="text-success fw-bold mb-2"><i class="bi bi-check2-circle me-2"></i>รายงานผล</h6>
                  <p class="mb-0 text-dark" style="white-space: pre-line;">{{ selectedEvent.DETAIL_REPORT || 'ไม่ได้ระบุ' }}</p>
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
  name: 'MeetingSummaryList',
  setup() {
    const currentDate = ref(new Date());
    const events = ref([]);
    const loading = ref(false);
    const eventDetailModalRef = ref(null);
    let modalInstance = null;
    const selectedEvent = ref(null);

    const monthNames = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    const currentYear = computed(() => currentDate.value.getFullYear() + 543);
    const currentMonthName = computed(() => monthNames[currentDate.value.getMonth()]);

    const fetchEvents = async () => {
      loading.value = true;
      try {
        const year = currentDate.value.getFullYear();
        const month = currentDate.value.getMonth() + 1;
        const url = `${import.meta.env.VITE_API_URL || '/backend'}/api-digital/meeting/get_meeting_summary.php?year=${year}&month=${month}`;
        const response = await axios.get(url);
        if (response.data.status === 'success') {
          // Group events by person and date to merge fragmented rows
          const grouped = {};
          
          response.data.data.forEach(item => {
            const key = `${item.SEND_HR_NAME}_${item.DATE_BEGIN}_${item.DATE_END}`;
            if (!grouped[key]) {
              grouped[key] = { ...item };
            } else {
              const existing = grouped[key];
              if (!existing.GO_NAME && item.GO_NAME) existing.GO_NAME = item.GO_NAME;
              if (!existing.BOOK_NUM && item.BOOK_NUM) existing.BOOK_NUM = item.BOOK_NUM;
              if (!existing.GO_TYPE_NAME && item.GO_TYPE_NAME) existing.GO_TYPE_NAME = item.GO_TYPE_NAME;
              if (!existing.GOTO_WITH && item.GOTO_WITH) existing.GOTO_WITH = item.GOTO_WITH;
              if (!existing.DETAIL_EXPECT && item.DETAIL_EXPECT) existing.DETAIL_EXPECT = item.DETAIL_EXPECT;
              if (!existing.DETAIL_REPORT && item.DETAIL_REPORT) existing.DETAIL_REPORT = item.DETAIL_REPORT;
            }
          });

          // Sort by date begin
          events.value = Object.values(grouped).sort((a, b) => new Date(a.DATE_BEGIN) - new Date(b.DATE_BEGIN));
        }
      } catch (error) {
        console.error('Error fetching meeting events:', error);
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
    
    const countPeople = (gotoWithStr) => {
      if (!gotoWithStr || gotoWithStr.trim() === '') return 0;
      // Count commas and add 1 (e.g. "A, B" => 2 people)
      return gotoWithStr.split(',').length;
    };

    const openDetail = (event) => {
      selectedEvent.value = event;
      if (!modalInstance && eventDetailModalRef.value) {
        modalInstance = new Modal(eventDetailModalRef.value);
      }
      if (modalInstance) {
        modalInstance.show();
      }
    };

    onMounted(() => {
      fetchEvents();
      nextTick(() => {
        if (eventDetailModalRef.value) {
          modalInstance = new Modal(eventDetailModalRef.value);
        }
      });
    });

    return {
      currentDate,
      currentYear,
      currentMonthName,
      events,
      loading,
      prevMonth,
      nextMonth,
      goToToday,
      openDetail,
      eventDetailModalRef,
      selectedEvent,
      countPeople
    };
  }
};
</script>

<style scoped>
.meeting-list-page {
  padding: 2rem;
  background-color: #f8fafc;
  min-height: calc(100vh - 60px);
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.table th {
  font-weight: 600;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}

.table td {
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}

.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

/* Modal Styling */
.modal-content {
  border-radius: 1rem;
  border: none;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}
</style>
