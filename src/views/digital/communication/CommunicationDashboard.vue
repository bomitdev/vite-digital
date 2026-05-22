<template>
  <div class="comm-dashboard-container">
    <header class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
      <div>
        <h2 class="text-primary fw-bold m-0">
          <i class="bi bi-broadcast me-2"></i>Communication Channels Dashboard
        </h2>
        <p class="text-muted small mb-0 mt-1">ทะเบียนช่องทางการสื่อสารขององค์กร</p>
      </div>
      <button
        class="btn btn-outline-primary shadow-sm"
        @click="$router.push('/communication-list')"
      >
        <i class="bi bi-list-task me-1"></i> Manage Channels
      </button>
    </header>

    <!-- Top Summary Row -->
    <div class="row g-4 mb-5">
      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100 bg-gradient-primary text-white">
          <div
            class="card-body p-4 d-flex flex-column align-items-center justify-content-center text-center"
          >
            <i class="bi bi-bezier2 fs-1 mb-2 opacity-75"></i>
            <h5 class="fw-medium mb-1 opacity-75">Total Channels</h5>
            <h2 class="display-4 fw-bold mb-0">{{ stats.total }}</h2>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100 border-start border-4 border-info">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle bg-info-subtle text-info me-3">
                <i class="bi bi-building fs-4"></i>
              </div>
              <h5 class="text-muted fw-bold mb-0">Internal</h5>
            </div>
            <h2 class="fw-bold text-dark text-end mb-0">
              {{ stats.categories['Internal'] || 0 }}
              <span class="fs-6 text-muted fw-normal">channels</span>
            </h2>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100 border-start border-4 border-success">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle bg-success-subtle text-success me-3">
                <i class="bi bi-globe fs-4"></i>
              </div>
              <h5 class="text-muted fw-bold mb-0">External</h5>
            </div>
            <h2 class="fw-bold text-dark text-end mb-0">
              {{ stats.categories['External'] || 0 }}
              <span class="fs-6 text-muted fw-normal">channels</span>
            </h2>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100 border-start border-4 border-warning">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <div class="icon-circle bg-warning-subtle text-warning me-3">
                <i class="bi bi-headset fs-4"></i>
              </div>
              <h5 class="text-muted fw-bold mb-0">Customer Service</h5>
            </div>
            <h2 class="fw-bold text-dark text-end mb-0">
              {{ stats.categories['Customer Service'] || 0 }}
              <span class="fs-6 text-muted fw-normal">channels</span>
            </h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Statuses -->
    <div class="row g-4">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold text-secondary mb-3">
              <i class="bi bi-activity me-2"></i>Channel Operating Status
            </h5>
          </div>
          <div class="card-body">
            <div class="row text-center container-fluid">
              <div
                class="col-md-4 py-3 border-end"
                v-for="status in stats.statuses"
                :key="status.status"
              >
                <h4 class="fw-bold text-dark">{{ status.count }}</h4>
                <span class="badge" :class="getStatusBadge(status.status)">{{
                  status.status
                }}</span>
              </div>
              <div class="col-12 py-4 text-muted" v-if="stats.statuses.length === 0">
                No status data available.
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
import Swal from 'sweetalert2';

export default {
  name: 'CommunicationDashboard',
  data() {
    return {
      stats: {
        total: 0,
        categories: { Internal: 0, External: 0, 'Customer Service': 0 },
        statuses: []
      }
    };
  },
  methods: {
    async fetchDashboardStats() {
      try {
        const response = await axios.get(
          '/api-digital/communication/get_dashboard.php'
        );
        if (response.data.success) {
          this.stats = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'Failed to fetch stats', 'error');
        }
      } catch (error) {
        console.error('API Error:', error);
      }
    },
    getStatusBadge(status) {
      if (status === 'Active') return 'bg-success text-white px-3 py-2 rounded-pill';
      if (status === 'Backup') return 'bg-warning text-dark px-3 py-2 rounded-pill';
      return 'bg-secondary text-white px-3 py-2 rounded-pill';
    }
  },
  mounted() {
    this.fetchDashboardStats();
  }
};
</script>

<style scoped>
.comm-dashboard-container {
  padding: 2rem;
  background-color: #f8f9fc;
  min-height: calc(100vh - 60px);
}

.stat-card {
  border-radius: 1rem;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

.icon-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
