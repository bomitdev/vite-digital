<template>
  <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold text-dark">
            <i class="bi bi-clock-history me-2 text-info"></i>ประวัติการทำรายการ
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
          </div>
          <div v-else-if="logs.length === 0" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
            ยังไม่มีประวัติการทำรายการ
          </div>
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="20%">วันที่-เวลา</th>
                  <th width="25%">ผู้ทำรายการ</th>
                  <th width="55%">รายละเอียด</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td class="text-muted small">{{ formatDate(log.created_at) }}</td>
                  <td class="fw-bold text-primary small"><i class="bi bi-person-circle me-1"></i>{{ log.user_profile_name }}</td>
                  <td class="small">{{ log.details }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';

export default {
  name: 'MtLogModal',
  props: {
    modalId: {
      type: String,
      default: 'logModal'
    },
    logType: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      logs: [],
      loading: false
    };
  },
  methods: {
    async fetchLogs() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/admin_material/admin_get_logs.php?type=' + this.logType);
        if (res.data.status === 'success') {
          this.logs = res.data.data;
        }
      } catch (err) {
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    formatDate(dateStr) {
      if (!dateStr) return '-';
      return moment(dateStr).format('DD/MM/YYYY HH:mm');
    }
  }
};
</script>
