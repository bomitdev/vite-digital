<template>
  <div class="sla-container">
    <div class="container mt-4">
      <div class="header-section text-center">
        <h1 class="main-title">
          ข้อตกลงระดับในการให้บริการ ระหว่างผู้ให้บริการ
        </h1>
      </div>
      <hr class="divider" />

      <div class="content-section">
        <div class="image-group">
          <div class="image-wrapper">
            <img
              src="/src/components/img/img-sla.png"
              class="content-image"
              alt="SLA agreement illustration"
            />
          </div>
          <div class="image-wrapper">
            <img
              src="/src/components/img/do-notdo.png"
              class="content-image"
              alt="Do's and Don'ts guidelines"
            />
          </div>
        </div>

        <div class="full-width-image">
          <img
            src="/src/components/img/sla.png"
            class="sla-details-image"
            alt="Detailed SLA terms and conditions"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "SlaView",
};
</script>

<style scoped>
.sla-container {
  background-color: #e3f8ff;
  min-height: 100vh;
  padding: 20px 0;
  font-family: 'Arial', sans-serif;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

.header-section {
  margin-bottom: 30px;
}

.main-title {
  color: #800080; /* purple */
  font-weight: 700;
  font-size: 2rem;
  margin-bottom: 1rem;
}

.divider {
  border-top: 2px solid #ddd;
  margin: 20px 0;
}

.content-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 30px;
}

.image-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 30px;
  width: 100%;
}

.image-wrapper {
  flex: 1;
  min-width: 300px;
  max-width: 500px;
  text-align: center;
}

.content-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.full-width-image {
  width: 100%;
  margin-top: 20px;
}

.sla-details-image {
  width: 100%;
  height: auto;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .main-title {
    font-size: 1.5rem;
  }
  
  .image-group {
    flex-direction: column;
    align-items: center;
  }
  
  .image-wrapper {
    min-width: 100%;
  }
}
</style>