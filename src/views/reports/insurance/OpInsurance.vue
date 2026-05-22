<template>
  <div class="container-lg mt-4">
    <h2 class="dashboard-title mb-1 fw-bold">
      <i class="fas fa-chart-line me-2 text-primary"></i>
      OP Insurance Dashboard
      <p class="dashboard-subtitle text-muted">ภาพรวมข้อมูลการให้บริการผู้ป่วยนอก</p>
    </h2>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3" v-for="card in summaryCards" :key="card.title">
        <div class="card shadow-sm border-0 rounded-3 h-100">
          <div class="card-body text-center">
            <h5 class="card-title fw-bold">{{ card.title }}</h5>
            <h3 class="text-success fw-bold">{{ card.value }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3">
          <div class="card-body">
            <h6 class="fw-bold">จำนวนผู้มารับบริการย้อนหลัง 11 วัน</h6>
            <canvas id="visitChart"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3">
          <div class="card-body">
            <h6 class="fw-bold">มูลค่ารวมย้อนหลัง 11 วัน</h6>
            <canvas id="incomeChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card table-card shadow-sm border-0 rounded-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3">ตารางข้อมูลรายวัน</h6>
        <table class="table table-bordered table-hover table-sm">
          <thead class="table-light">
            <tr>
              <th>วันที่</th>
              <th>HN Total</th>
              <th>Visit Total</th>
              <th>รายได้รวม</th>
              <th>UCS</th>
              <th>OFC</th>
              <th>SSS</th>
              <th>PAY</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in data" :key="index">
              <td>{{ item.vstdate }}</td>
              <td>{{ item.hn_total }}</td>
              <td>{{ item.visit_total }}</td>
              <td>{{ formatNumber(item.inc_total) }}</td>
              <td>{{ formatNumber(item.inc_ucs_incup) }}</td>
              <td>{{ formatNumber(item.inc_ofc) }}</td>
              <td>{{ formatNumber(item.inc_sss) }}</td>
              <td>{{ formatNumber(item.inc_pay) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  data() {
    return {
      data: [],
      summaryCards: [],
      apiUrl: 'http://1.179.128.29:3394/api/opd',
      apiToken: 'uiG6iVJBDbh4JjgIlvZgh1ndFYHqOvqEo4bR8Cp4deb4e39b'
    };
  },
  methods: {
    formatNumber(num) {
      return new Intl.NumberFormat('th-TH', { maximumFractionDigits: 2 }).format(num);
    },
    async fetchData() {
      try {
        const res = await fetch(this.apiUrl, {
          headers: {
            Authorization: `Bearer ${this.apiToken}`,
            'Content-Type': 'application/json'
          }
        });
        const json = await res.json();
        this.data = json.data || [];
        this.generateSummary();
        this.$nextTick(() => this.createCharts());
      } catch (error) {
        console.error('Error fetching API:', error);
      }
    },
    generateSummary() {
      const latest = this.data[0];
      if (!latest) return;
      this.summaryCards = [
        { title: 'จำนวนผู้รับบริการวันนี้', value: this.formatNumber(latest.visit_total) },
        { title: 'มูลค่ารวมวันนี้ (บาท)', value: this.formatNumber(latest.inc_total) },
        { title: 'มูลค่าสิทธิ์ UC', value: this.formatNumber(latest.inc_ucs_incup) },
        { title: 'มูลค่าสิทธิ์ข้าราชการ', value: this.formatNumber(latest.inc_ofc) }
      ];
    },
    createCharts() {
      const labels = this.data.map((d) => d.vstdate).reverse();
      const visits = this.data.map((d) => d.visit_total).reverse();
      const income = this.data.map((d) => d.inc_total).reverse();

      new Chart(document.getElementById('visitChart'), {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'Visit Total',
              data: visits,
              borderWidth: 2,
              backgroundColor: 'rgba(54, 162, 235, 0.5)'
            }
          ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });

      new Chart(document.getElementById('incomeChart'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'Income Total',
              data: income,
              borderColor: '#28a745',
              fill: false,
              tension: 0.3
            }
          ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }
  },
  mounted() {
    this.fetchData();
  }
};
</script>

<style scoped>
.dashboard-container {
  padding: 1.5rem;
  background-color: #f8f9fa;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem 0;
}

.header-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.dashboard-title {
  font-weight: 700;
  color: #2e3a59;
  font-size: 1.75rem;
}

.dashboard-subtitle {
  font-size: 0.9rem;
}

.date-indicator .badge {
  font-size: 0.8rem;
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
}

.summary-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition:
    transform 0.2s,
    box-shadow 0.2s;
  overflow: hidden;
}

.summary-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.summary-card.card-0 {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  color: white;
}

.summary-card.card-1 {
  background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
  color: white;
}

.summary-card.card-2 {
  background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
  color: white;
}

.summary-card.card-3 {
  background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
  color: white;
}

.summary-card .card-title {
  font-size: 0.85rem;
  opacity: 0.9;
  margin-bottom: 0.5rem;
}

.summary-card .card-value {
  font-size: 1.75rem;
  font-weight: 700;
}

.summary-card .card-icon {
  font-size: 1.5rem;
  opacity: 0.8;
}

.summary-card .card-trend {
  font-size: 0.8rem;
  opacity: 0.9;
}

.chart-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.chart-card .card-header {
  padding: 1.25rem 1.5rem 0;
}

.chart-actions .btn {
  border-radius: 20px;
  margin-left: 0.25rem;
}

.chart-actions .btn.active {
  background-color: #4e73df;
  border-color: #4e73df;
  color: white;
}

.table-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-card .card-header {
  padding: 1.25rem 1.5rem 0.5rem;
}

.table-card .card-footer {
  padding: 1rem 1.5rem;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #6c757d;
  font-size: 0.85rem;
  padding: 1rem 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
}

.table-row-even {
  background-color: rgba(248, 249, 250, 0.5);
}

.table-row-odd {
  background-color: white;
}

.table-hover tbody tr:hover {
  background-color: rgba(78, 115, 223, 0.05);
}

.badge.rounded-pill {
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
}

.input-group-text {
  border-radius: 20px 0 0 20px;
}

.form-control.bg-light {
  border-radius: 0 20px 20px 0;
}

.pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: none;
  color: #6c757d;
}

.pagination .page-item.active .page-link {
  background-color: #4e73df;
  border-color: #4e73df;
}

@media (max-width: 768px) {
  .dashboard-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .date-indicator {
    margin-top: 1rem;
  }

  .dashboard-title {
    font-size: 1.5rem;
  }

  .header-icon {
    width: 50px;
    height: 50px;
    font-size: 1.25rem;
  }
}
</style>
