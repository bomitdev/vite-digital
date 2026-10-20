<template>
  <div class="warfarin-calculator-view bg-light min-vh-100 py-4 px-3 px-md-4">
    <!-- Breadcrumbs & Navigation -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><router-link to="/">หน้าหลัก</router-link></li>
            <li class="breadcrumb-item">IPDPaperless</li>
            <li class="breadcrumb-item active" aria-current="page">เครื่องมือคำนวณเม็ดยา Warfarin (INR)</li>
          </ol>
        </nav>
      </div>

      <div class="d-flex align-items-center gap-2">
        <router-link to="/" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2">
          <i class="bi bi-house-door"></i>
          <span>กลับหน้าหลัก</span>
        </router-link>
      </div>
    </div>

    <!-- Embedded Calculator Component -->
    <WarfarinCalculator />
  </div>
</template>

<script>
import WarfarinCalculator from '../../../components/had/WarfarinCalculator.vue';

export default {
  name: 'WarfarinCalculatorView',
  components: {
    WarfarinCalculator
  }
};
</script>

<style scoped>
.warfarin-calculator-view {
  font-family: 'Sarabun', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
</style>
