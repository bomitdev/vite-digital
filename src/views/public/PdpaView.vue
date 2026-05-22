<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">นโยบายความคุ้มครองส่วนบุคคล</h1>
      </div>
      <hr />

      <div class="text-center mb-4">
        <h2>(Personal Data Protection Act : PDPA)</h2>
        <br />
        <img
          src="/src/components/img/pdpa.png"
          class="img-fluid rounded shadow-sm"
          style="max-height: 400px; object-fit: cover"
          alt="PDPA Banner"
        />
      </div>

      <DocumentManager category="pdpa" title="เอกสาร PDPA" />
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
h2 {
  color: orange;
  font-weight: bold;
}
</style>
