<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">นโยบายและระเบียบปฏิบัติ</h1>
      </div>
      <hr />

      <div class="content-section">
        <DocumentManager category="policy" title="เอกสารนโยบาย" />
      </div>
    </div>
  </div>
</template>

<script setup>
import DocumentManager from '../../components/DocumentManager.vue';
</script>

<style scoped>
.container-fluid {
  background-color: #e3f8ff;
  min-height: 100vh;
  padding: 0;
  font-family: Arial, sans-serif;
}
.container {
  padding-top: 20px;
  padding-bottom: 40px;
  max-width: 1200px;
  margin: 0 auto;
}
h1.mfc {
  color: #800080;
  font-weight: 700;
}
.content-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
</style>
