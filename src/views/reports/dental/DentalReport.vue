<template>
    <div class="container py-4">
      <!-- Main Report Card -->
      <div class="card report-card mb-4">
        <div class="card-header report-header">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h1 class="report-title mb-1">Dental Report</h1>
              <p class="report-subtitle">ดูรายงานทันตกรรม กรุณาเลือกวันที่</p>
            </div>
            <i class="fas fa-tooth report-icon"></i>
          </div>
        </div>
  
        <div class="card-body">
          <!-- Date Selection Section -->
          <div class="date-selection-section mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <input
                    type="date"
                    id="startDate"
                    v-model="startDate"
                    class="form-control form-control-lg"
                    @change="validateDates"
                  />
                  <label for="startDate" class="fw-semibold">Start Date</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <input
                    type="date"
                    id="endDate"
                    v-model="endDate"
                    class="form-control form-control-lg"
                    @change="validateDates"
                  />
                  <label for="endDate" class="fw-semibold">End Date</label>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Action Button -->
          <div class="text-center mb-3">
            <button
              @click="fetchData"
              class="btn  btn-generate px-4 py-2"
              :disabled="loading || !datesValid"
            >
              <span v-if="!loading">
                <i class="fas fa-file-medical me-2"></i>Generate Report
              </span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Processing...
              </span>
            </button>
          </div>
  
          <!-- Alerts -->
          <div v-if="error" class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ error }}
            <button type="button" class="btn-close" @click="error = null" aria-label="Close"></button>
          </div>
  
          <div v-if="!datesValid" class="alert alert-warning mb-3">
            <i class="fas fa-calendar-exclamation me-2"></i>
            กรุณาเลือกวันที่ใหม่ อีกครั้ง!
          </div>
        </div>
      </div>
  
      <!-- Results Section -->
      <div v-if="data.length" class="results-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="results-title mb-0">
            <i class="fas fa-clipboard-list me-2"></i>รายการหัตถการทันตกรรม
          </h2>
          <span class="badge bg-primary rounded-pill">
            {{ data.length }} รายการ
          </span>
        </div>
  
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <div v-for="item in data" :key="item.code">
            <!-- Procedure Card -->
            <div class="card h-100 procedure-card">
              <div class="card-header procedure-card-header">
                <h5 class="mb-0 d-flex align-items-center">
                  <span class="badge bg-secondary me-2">#{{ item.code }}</span>
                  <span class="text-truncate">{{ item.eng }}</span>
                </h5>
              </div>
  
              <div class="card-body">
                <div class="mb-3">
                  <h6 class="section-title">
                    <i class="fas fa-language me-2"></i>Descriptions
                  </h6>
                  <div class="description-item">
                    <span class="description-label">English:</span>
                    <span class="description-text">{{ item.eng }}</span>
                  </div>
                  <div class="description-item">
                    <span class="description-label">Thai:</span>
                    <span class="description-text">{{ item.thai }}</span>
                  </div>
                </div>
  
                <div class="stats-section">
                  <h6 class="section-title">
                    <i class="fas fa-chart-bar me-2"></i>สถิติ
                  </h6>
                  <div class="row g-2">
                    <div class="col-6">
                      <div class="stat-card">
                        <div class="stat-value">{{ formatNumber(item.c_hn) }}</div>
                        <div class="stat-label">จำนวนคน</div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="stat-card">
                        <div class="stat-value">{{ formatNumber(item.c_vn) }}</div>
                        <div class="stat-label">จำนวนครั้ง</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Report Footer -->
        <div class="report-footer mt-4 text-end">
          <small class="text-muted">
            <i class="fas fa-clock me-1"></i>
            รายงานวันที่: {{ currentTime }}
          </small>
        </div>
      </div>
  
      <!-- Empty State -->
      <div v-else-if="!loading" class="empty-state text-center py-5">
        <div class="empty-state-icon mb-3">
          <i class="fas fa-file-medical-alt"></i>
        </div>
        <h3 class="empty-state-title">ไม่พบข้อมูล</h3>
        <p class="empty-state-text text-muted">
          กรุณาเลือกช่วงวันที่ แล้วกดปุ๋ม "Generate Report" เพื่อดูรายงานการทำหัตถการทันตกรรม.
        </p>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        startDate: '2024-10-01',
        endDate: '2025-03-25',
        data: [],
        loading: false,
        error: null,
        currentTime: null
      };
    },
    computed: {
      formattedStartDate() {
        return this.formatDate(this.startDate);
      },
      formattedEndDate() {
        return this.formatDate(this.endDate);
      },
      datesValid() {
        if (!this.startDate || !this.endDate) return false;
        return new Date(this.endDate) >= new Date(this.startDate);
      }
    },
    methods: {
      async fetchData() {
        if (!this.datesValid) return;
  
        this.loading = true;
        this.error = null;
        try {
          const response = await fetch(
            `http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/dental/get_dental.php?start_date=${this.startDate}&end_date=${this.endDate}`
          );
          const result = await response.json();
  
          if (response.ok) {
            this.data = result.data || [];
            this.updateCurrentTime();
          } else {
            this.error = result.error || 'An error occurred while fetching data.';
          }
        } catch (err) {
          this.error = 'Failed to fetch data. Please check your connection and try again.';
          console.error(err);
        } finally {
          this.loading = false;
        }
      },
      validateDates() {
        if (new Date(this.endDate) < new Date(this.startDate)) {
          this.error = 'วันที่ End Date น้อยกว่าวัน Start Date';
        } else {
          this.error = null;
        }
      },
      formatDate(dateString) {
        if (!dateString) return '';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined);
      },
      formatNumber(num) {
        return parseInt(num || 0).toLocaleString();
      },
      updateCurrentTime() {
        const now = new Date();
        this.currentTime = now.toLocaleString();
      }
    },
    mounted() {
      this.updateCurrentTime();
      
    }
  };
  </script>
  
  <style scoped>
  /* Color Variables */
  :root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --danger-color: #f72585;
  }
  
  /* Base Styles */
  .container {
    max-width: 1400px;
  }
  
  /* Card Styles */
  .report-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
    overflow: hidden;
  }
  
  .report-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: rgb(94, 83, 83);
    padding: 1.5rem;
    border-bottom: none;
  }
  
  .report-title {
    font-size: 1.8rem;
    font-weight: 700;
    letter-spacing: -0.5px;
  }
  
  .report-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin-bottom: 0;
  }
  
  .report-icon {
    font-size: 2.5rem;
    opacity: 0.2;
  }
  
  /* Form Styles */
  .form-floating > label {
    color: #6c757d;
    font-weight: 500;
  }
  
  .form-control-lg {
    height: calc(3.5rem + 2px);
    font-size: 1rem;
  }
  
  /* Button Styles */
  .btn-generate {
    background-color: var(--primary-color);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    font-size: 1.05rem;
  }
  
  .btn-generate:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
  }
  
  .btn-generate:disabled {
    background-color: #adb5bd;
    transform: none;
    box-shadow: none;
  }
  
  /* Procedure Cards */
  .procedure-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
  }
  
  .procedure-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
  }
  
  .procedure-card-header {
    background-color: rgba(24, 51, 170, 0.1);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.25rem;
  }
  
  .section-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
  }
  
  .description-item {
    margin-bottom: 0.5rem;
  }
  
  .description-label {
    font-weight: 600;
    color: #495057;
    margin-right: 0.5rem;
    font-size: 0.9rem;
  }
  
  .description-text {
    color: #6c757d;
    font-size: 0.9rem;
  }
  
  /* Stat Cards */
  .stat-card {
    background-color: rgba(248, 249, 250, 0.8);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
    height: 100%;
  }
  
  .stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1.2;
  }
  
  .stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-top: 0.25rem;
  }
  
  /* Empty State */
  .empty-state {
    background-color: #f8f9fa;
    border-radius: 12px;
    margin-top: 2rem;
  }
  
  .empty-state-icon {
    font-size: 3.5rem;
    color: #dee2e6;
  }
  
  .empty-state-title {
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  
  .empty-state-text {
    max-width: 500px;
    margin: 0 auto;
  }
  
  /* Responsive Adjustments */
  @media (max-width: 768px) {
    .report-title {
      font-size: 1.5rem;
    }
    
    .report-subtitle {
      font-size: 0.85rem;
    }
    
    .btn-generate {
      width: 100%;
    }
  }
  
  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .results-section {
    animation: fadeIn 0.5s ease-out forwards;
  }
  
  .procedure-card {
    animation: fadeIn 0.3s ease-out forwards;
  }
  </style>