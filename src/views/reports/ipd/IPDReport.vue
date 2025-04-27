<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">
        <i class="fas fa-hospital-alt me-2"></i>
        รายงานผู้ป่วยในปัจจุบัน
      </h3>
      <button
        @click="fetchData"
        class="btn btn-outline-primary"
        :disabled="loading"
      >
        <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
        โหลดข้อมูลใหม่
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-3">กำลังโหลดข้อมูล...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger text-center">
      <i class="fas fa-exclamation-circle me-2"></i>
      {{ error }}
    </div>

    <!-- Cards -->
    <div v-else class="row">
      <div class="col-md-4 mb-4" v-for="(label, key) in labelMap" :key="key">
        <div
          class="card text-white h-100 hover-effect"
          :style="{ backgroundColor: colorMap[key] || '#3b82f6' }"
        >
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="card-title mb-0">{{ label }}</h5>
              <i :class="iconMap[key] + ' fs-4'"></i>
            </div>
            <h2 class="card-value">{{ formatNumber(ipdData[key]) }}</h2>
            <div class="mt-auto pt-2">
              <small class="text-white-50">อัปเดตเมื่อ {{ currentTime }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="!loading && !error" class="mt-4 text-muted text-end small">
      <i class="fas fa-clock me-1"></i>
      ข้อมูลล่าสุด: {{ lastUpdateTime }}
    </div>
  </div>
</template>

<script>
export default {
  name: "IpdAdmitReport",
  data() {
    return {
      ipdData: {},
      loading: true,
      error: null,
      lastUpdateTime: null,
      labelMap: {
        ipd_all_now: "ผู้ป่วยใน Admit ทั้งหมดตอนนี้",
        ipd: "ผู้ป่วยใน (Ward 06)",
        ipd_labor: "ห้องคลอด (Ward 02)",
        ipd_homeward: "Homeward (Ward 07)",
        hr_hos: "เจ้าหน้าที่ รพ.",
      },
      iconMap: {
        ipd_all_now: "fas fa-hospital-user",
        ipd: "fas fa-bed",
        ipd_labor: "fas fa-baby-carriage",
        ipd_homeward: "fas fa-home-heart",
        hr_hos: "fas fa-user-nurse",
      },
      colorMap: {
        ipd_all_now: "#4361ee",
        ipd: "#3a0ca3",
        ipd_labor: "#f72585",
        ipd_homeward: "#4cc9f0",
        hr_hos: "#7209b7",
      },
    };
  },
  computed: {
    currentTime() {
      return new Date().toLocaleTimeString("th-TH", {
        hour: "2-digit",
        minute: "2-digit",
      });
    },
  },
  methods: {
    async fetchData() {
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch(
          "http://192.168.7.12/vue-app/vite-digital/backend/api-hosxe/ipd/get_ipdadmit.php"
        );
        const json = await res.json();
        if (json.error) {
          this.error = json.error;
        } else {
          this.ipdData = json;
          this.lastUpdateTime = new Date().toLocaleString("th-TH", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });
        }
      } catch (err) {
        this.error = err.message || "ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้";
      } finally {
        this.loading = false;
      }
    },
    formatNumber(num) {
      if (num === null || num === undefined) return "-";
      return Number(num).toLocaleString("th-TH");
    },
  },
  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
.card-value {
  font-size: 2.5rem;
  font-weight: bold;
}
.hover-effect {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-effect:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}
</style>
