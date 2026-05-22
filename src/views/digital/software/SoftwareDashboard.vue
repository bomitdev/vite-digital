<template>
  <div class="software-dashboard-container">
    <header class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-primary fw-bold m-0">Software Dashboard</h2>
      <button class="btn btn-outline-primary shadow-sm" @click="$router.push('/software-list')">
        <i class="bi bi-list-task"></i> Manage Software
      </button>
    </header>

    <!-- Summary Cards -->
    <div class="row g-4 mb-5">
      <!-- Total Software -->
      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div class="icon-circle bg-primary-subtle text-primary mb-3">
              <i class="bi bi-box-seam fs-2"></i>
            </div>
            <h5 class="text-muted fw-semibold">Total Software</h5>
            <h2 class="fw-bold mb-0 text-dark">{{ stats.total_software }}</h2>
          </div>
        </div>
      </div>
      <!-- Expiring Soon -->
      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div class="icon-circle bg-warning-subtle text-warning mb-3">
              <i class="bi bi-exclamation-triangle fs-2"></i>
            </div>
            <h5 class="text-muted fw-semibold">Expiring (≤30 Days)</h5>
            <h2 class="fw-bold mb-0 text-dark">{{ stats.expiring_soon }}</h2>
          </div>
        </div>
      </div>
      <!-- Expired -->
      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div class="icon-circle bg-danger-subtle text-danger mb-3">
              <i class="bi bi-x-circle fs-2"></i>
            </div>
            <h5 class="text-muted fw-semibold">Expired licenses</h5>
            <h2 class="fw-bold mb-0 text-dark">{{ stats.expired }}</h2>
          </div>
        </div>
      </div>
      <!-- Total Installations -->
      <div class="col-md-6 col-lg-3">
        <div class="card stat-card shadow-sm border-0 h-100">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div class="icon-circle bg-success-subtle text-success mb-3">
              <i class="bi bi-pc-display fs-2"></i>
            </div>
            <h5 class="text-muted fw-semibold">Total Installations</h5>
            <h2 class="fw-bold mb-0 text-dark">{{ stats.total_installations }}</h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="fw-bold text-secondary mb-3">
          <i class="bi bi-clock-history me-2"></i>Recent Installations
        </h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Software</th>
                <th>Machine Name</th>
                <th>User</th>
                <th>Install Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="stats.recent_installations.length === 0">
                <td colspan="4" class="text-center text-muted py-4">
                  No recent installations found.
                </td>
              </tr>
              <tr v-for="install in stats.recent_installations" :key="install.id">
                <td class="fw-medium">{{ install.software_name }}</td>
                <td>{{ install.machine_name }}</td>
                <td>{{ install.user_name || '-' }}</td>
                <td>
                  {{
                    install.install_date
                      ? new Date(install.install_date).toLocaleDateString('th-TH')
                      : '-'
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  name: 'SoftwareDashboard',
  data() {
    return {
      stats: {
        total_software: 0,
        expiring_soon: 0,
        expired: 0,
        total_installations: 0,
        recent_installations: []
      }
    };
  },
  methods: {
    async fetchDashboardStats() {
      try {
        const response = await axios.get(
          '/api-digital/software/get_dashboard.php'
        );
        if (response.data.success) {
          this.stats = response.data.data;
        } else {
          Swal.fire('Error', response.data.message || 'Failed to fetch stats', 'error');
        }
      } catch (error) {
        console.error('API Error:', error);
        Swal.fire('Error', 'Unable to connect to the server.', 'error');
      }
    }
  },
  mounted() {
    this.fetchDashboardStats();
  }
};
</script>

<style scoped>
.software-dashboard-container {
  padding: 2rem;
  background-color: #f8f9fa;
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

.icon-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
