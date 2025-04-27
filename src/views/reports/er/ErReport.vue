<template>
  <div class="emergency-dashboard">
    <div class="container mt-4">
      <!-- Header Section -->
      <div class="dashboard-header text-center mb-4">
        <h2 class="dashboard-title">
          <i class="bi bi-activity me-2"></i>สถิติผู้ป่วยฉุกเฉิน
        </h2>
        <p class="text-muted">รายงานข้อมูลความเร่งด่วนของผู้ป่วย</p>
      </div>

      <!-- Date Range Selector -->
      <div class="date-range-card card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title text-primary">
            <i class="bi bi-calendar-range me-2"></i>เลือกช่วงวันที่
          </h5>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label for="startDate" class="form-label">วันที่เริ่มต้น:</label>
              <input
                type="date"
                v-model="startDate"
                class="form-control"
                id="startDate"
              />
            </div>
            <div class="col-md-4">
              <label for="endDate" class="form-label">วันที่สิ้นสุด:</label>
              <input
                type="date"
                v-model="endDate"
                class="form-control"
                id="endDate"
              />
            </div>
            <div class="col-md-4">
              <button
                class="btn btn-primary w-100 py-2"
                @click="fetchData"
                :disabled="loading"
              >
                <i class="bi bi-search me-2"></i>
                <span v-if="!loading">ค้นหา</span>
                <span v-else>กำลังค้นหา...</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center my-5 py-5">
        <div
          class="spinner-border text-primary"
          style="width: 3rem; height: 3rem"
          role="status"
        >
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Data Display -->
      <div v-else-if="data">
        <div class="stats-summary-card card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
              <i class="bi bi-clipboard2-pulse me-2"></i>สรุปตามความเร่งด่วน
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-12">
                <div class="list-group">
                  <div
                    class="list-group-item p-3 mb-2"
                    v-for="(value, key) in data"
                    :key="key"
                    :class="getCardClass(key)"
                  >
                    <div
                      class="d-flex justify-content-between align-items-center"
                    >
                      <div>
                        <h6 class="mb-1">{{ translateKey(key) }}</h6>
                        <h2 class="mb-0">{{ value }}</h2>
                      </div>
                      <i :class="getIconClass(key) + ' fs-3'"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error/No Data State -->
      <div v-else class="no-data-card card text-center py-5">
        <div class="card-body">
          <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
          <h5 class="mt-3">ไม่มีข้อมูลหรือเกิดข้อผิดพลาด</h5>
          <p class="text-muted">โปรดตรวจสอบช่วงวันที่หรือลองใหม่อีกครั้ง</p>
          <button class="btn btn-outline-primary mt-2" @click="fetchData">
            <i class="bi bi-arrow-repeat me-2"></i>ลองอีกครั้ง
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      startDate: "2024-10-01",
      endDate: "2025-03-25",
      data: null,
      loading: false,
    };
  },
  methods: {
    async fetchData() {
      this.loading = true;
      this.data = null;
      try {
        const response = await fetch(
          `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/er/get-emergency.php?start_date=${this.startDate}&end_date=${this.endDate}`
        );
        const result = await response.json();
        this.data = result;
      } catch (error) {
        console.error("เกิดข้อผิดพลาด:", error);
        this.data = null;
      } finally {
        this.loading = false;
      }
    },
    translateKey(key) {
      const translations = {
        Resuscitate: "ผู้ป่วยวิกฤต (Resuscitate)",
        Emergency: "ผู้ป่วยฉุกเฉิน (Emergency)",
        Urgency: "ผู้ป่วยเร่งด่วน (Urgency)",
        Semi_Urgency: "ผู้ป่วยกึ่งเร่งด่วน (Semi Urgency)",
        Non_Urgency: "ผู้ป่วยไม่เร่งด่วน (Non Urgency)",
      };
      return translations[key] || key;
    },
    getCardClass(key) {
      const classes = {
        Resuscitate: "bg-red text-dark box-shadow",
        Emergency: "bg-pink text-dark box-shadow",
        Urgency: "bg-yellow text-dark box-shadow",
        Semi_Urgency: "bg-green text-dark box-shadow",
        Non_Urgency: "bg-whtie text-dark box-shadow",
      };
      return classes[key] || "bg-light text-dark ";
    },
    getIconClass(key) {
      const icons = {
        Resuscitate: "bi bi-heart-pulse-fill",
        Emergency: "bi bi-exclamation-triangle-fill",
        Urgency: "bi bi-clock-fill",
        Semi_Urgency: "bi bi-hourglass-split",
        Non_Urgency: "bi bi-check-circle-fill",
      };
      return icons[key] || "bi bi-question-circle-fill";
    },
  },
  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
/* เพิ่มสไตล์สำหรับ list group ที่ปรับแต่ง */
.list-group-item {
  border-radius: 8px !important;
  border: none;
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.list-group-item:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* ปรับขนาดฟอนต์สำหรับ mobile */
@media (max-width: 768px) {
  .list-group-item h2 {
    font-size: 1.5rem;
  }

  .list-group-item h6 {
    font-size: 0.9rem;
  }
}
.emergency-dashboard {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 2rem 0;
}

.dashboard-header {
  padding: 1rem 0;
}

.dashboard-title {
  color: #2c3e50;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.date-range-card {
  border-radius: 10px;
}

.stat-card {
  border: none;
  border-radius: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.card-title {
  font-weight: 600;
  font-size: 0.9rem;
}

.card-value {
  font-weight: 700;
  font-size: 1.8rem;
  margin: 0.5rem 0;
}
.box-shadow {
  box-shadow: 4px 4px 4px 6px rgba(0, 0, 0, 0.1);
}

.bg-red {
  background-color: red !important;
}
.bg-pink {
  background-color: pink !important;
}
.bg-yellow {
  background-color: yellow !important;
}
.bg-green {
  background-color: green !important;
}
.bg-white {
  background-color: white !important;
}

.data-table-card {
  border-radius: 10px;
}

.no-data-card {
  border-radius: 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-title {
    font-size: 1.5rem;
  }

  .date-range-card .col-md-4 {
    margin-bottom: 1rem;
  }

  .card-value {
    font-size: 1.5rem;
  }
}
</style>
