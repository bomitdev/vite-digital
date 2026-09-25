<template>
  <div class="container-fluid mt-5 px-4 pb-5">
    <div class="card calm-card mb-4 shadow-sm border-0">
      <div class="card-header calm-bg-navy text-white py-3 d-flex justify-content-between align-items-center border-bottom-0 rounded-top">
        <h4 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i> Dashboard ความก้าวหน้าแผนพัฒนาคุณภาพ (HA)</h4>
        <div>
          <button class="btn btn-light rounded-pill px-3 fw-bold me-2 text-primary" @click="$router.push('/qi-committees')">
            <i class="bi bi-people-fill me-1"></i> ข้อมูลคณะกรรมการและแผนฯ
          </button>
          <button class="btn btn-outline-light rounded-pill px-3 fw-bold" @click="$router.push('/home-backoffice')">
            <i class="bi bi-house-fill me-1"></i> กลับหน้าหลัก
          </button>
        </div>
      </div>
      
      <div class="card-body p-4 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="fw-bold text-dark mb-0">ภาพรวมความก้าวหน้าผลการดำเนินงาน</h5>
          <div class="d-flex align-items-center gap-2">
            <label class="fw-bold text-muted mb-0">ปีงบประมาณ:</label>
            <select class="form-select form-select-sm shadow-sm" style="width: 120px;" v-model="selectedYear">
              <option value="2568">ปี 2568</option>
              <option value="2569">ปี 2569</option>
              <option value="2570">ปี 2570</option>
              <option value="2571">ปี 2571</option>
            </select>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-5">
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
              <div class="card-body text-center p-4">
                <div class="display-4 fw-bold text-primary mb-2">{{ dashboardData.length }}</div>
                <div class="text-muted fw-bold">จำนวนทีมทั้งหมด</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
              <div class="card-body text-center p-4">
                <div class="display-4 fw-bold text-success mb-2">{{ totalPlansAcrossAll }}</div>
                <div class="text-muted fw-bold">แผนพัฒนาคุณภาพทั้งหมด (ข้อ)</div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 calm-bg-lavender">
              <div class="card-body p-4 d-flex flex-column justify-content-center">
                <h6 class="fw-bold text-navy mb-3">ความก้าวหน้าภาพรวม (ปี {{ selectedYear }})</h6>
                <div class="d-flex align-items-center mb-2">
                  <div class="flex-grow-1 me-3">
                    <div class="progress" style="height: 25px; border-radius: 12px;">
                      <div class="progress-bar progress-bar-striped progress-bar-animated" :class="overallProgressColor" role="progressbar" :style="{ width: overallProgress + '%' }"></div>
                    </div>
                  </div>
                  <div class="fs-4 fw-bold" :class="overallProgressTextColor">{{ overallProgress }}%</div>
                </div>
                <div class="text-muted small">มีการรายงานผลแล้ว {{ totalReportedCurrentYear }} จาก {{ totalPlansAcrossAll }} แผนฯ</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Progress by Team -->
        <h5 class="fw-bold text-dark mb-4 border-bottom pb-2">
          <i class="bi bi-bar-chart-fill text-primary me-2"></i>ความก้าวหน้าแต่ละทีม
        </h5>
        
        <div class="row g-4">
          <div class="col-md-4" v-for="team in dashboardData" :key="team.id">
            <div class="card border-0 shadow-sm rounded-4 h-100 team-card transition-hover">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="fw-bold text-dark mb-0 text-truncate" :title="team.name">
                    <i class="bi bi-people-fill text-primary me-2"></i>{{ team.name }}
                  </h5>
                  <span class="badge bg-light text-dark border shadow-sm">
                    {{ team.total_plans }} แผนฯ
                  </span>
                </div>
                
                <div v-if="team.total_plans > 0">
                  <div class="d-flex justify-content-between align-items-center mb-1 mt-4">
                    <span class="small fw-bold text-muted">รายงานแล้ว (ปี {{ selectedYear }})</span>
                    <span class="small fw-bold">{{ getReported(team) }} / {{ team.total_plans }}</span>
                  </div>
                  <div class="progress" style="height: 10px; border-radius: 5px; background-color: #e9ecef;">
                    <div class="progress-bar" :class="getProgressColor(getProgress(team))" role="progressbar" :style="{ width: getProgress(team) + '%' }"></div>
                  </div>
                  <div class="text-end mt-1">
                    <span class="fw-bold fs-5" :class="getProgressTextColor(getProgress(team))">{{ getProgress(team) }}%</span>
                  </div>
                </div>
                
                <div v-else class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-3 d-block mb-2 text-light"></i>
                  <span class="small">ยังไม่มีแผนพัฒนาคุณภาพ</span>
                </div>
              </div>
              <div class="card-footer bg-white border-top-0 rounded-bottom-4 text-center pb-3 pt-0">
                <button class="btn btn-sm btn-outline-primary rounded-pill px-4" @click="goToTeamPlans(team.id)">
                  ดูรายละเอียด <i class="bi bi-arrow-right-short"></i>
                </button>
              </div>
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
  name: 'QiDashboard',
  data() {
    return {
      dashboardData: [],
      selectedYear: '2568',
      loading: false
    };
  },
  computed: {
    totalPlansAcrossAll() {
      return this.dashboardData.reduce((sum, team) => sum + team.total_plans, 0);
    },
    totalReportedCurrentYear() {
      return this.dashboardData.reduce((sum, team) => sum + this.getReported(team), 0);
    },
    overallProgress() {
      if (this.totalPlansAcrossAll === 0) return 0;
      return Math.round((this.totalReportedCurrentYear / this.totalPlansAcrossAll) * 100);
    },
    overallProgressColor() {
      return this.getProgressColor(this.overallProgress);
    },
    overallProgressTextColor() {
      return this.getProgressTextColor(this.overallProgress);
    }
  },
  methods: {
    async fetchDashboardData() {
      this.loading = true;
      try {
        const res = await axios.get('/api-digital/qi/get_dashboard_progress.php');
        if (res.data.status === 'success') {
          this.dashboardData = res.data.data;
        }
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      } finally {
        this.loading = false;
      }
    },
    getReported(team) {
      return team[`reported_${this.selectedYear}`] || 0;
    },
    getProgress(team) {
      return team[`progress_${this.selectedYear}`] || 0;
    },
    getProgressColor(progress) {
      if (progress === 100) return 'bg-success';
      if (progress >= 50) return 'bg-primary';
      if (progress > 0) return 'bg-warning text-dark';
      return 'bg-secondary';
    },
    getProgressTextColor(progress) {
      if (progress === 100) return 'text-success';
      if (progress >= 50) return 'text-primary';
      if (progress > 0) return 'text-warning';
      return 'text-secondary';
    },
    goToTeamPlans(teamId) {
      // Navigate to the committee page, possibly passing a query to auto-select the team and tab
      // For now just go to the page
      this.$router.push(`/qi-committees`);
    }
  },
  mounted() {
    this.fetchDashboardData();
  }
};
</script>

<style scoped>
.calm-bg-navy { background-color: #1a3e6f !important; }
.calm-text-navy { color: #1a3e6f !important; }
.calm-bg-lavender { background-color: #f0f0fa !important; }
.transition-hover {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.transition-hover:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>
