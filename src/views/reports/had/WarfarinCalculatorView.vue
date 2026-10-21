<template>
  <div class="warfarin-calculator-view bg-light min-vh-100 py-3 px-2 px-md-3">
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
