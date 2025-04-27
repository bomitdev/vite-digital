<template>
  <footer class="footer bg-light py-4 border-top">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
          <a href="https://chanuman.moph.go.th" class="text-decoration-none fw-bold text-primary" target="_blank">
            <i class="fas fa-hospital me-2"></i>Chanuman Hospital
            
          </a>
          <span class="d-block d-md-inline-block text-muted small ms-md-2 mt-2 mt-md-0">
            &copy; 2025 Digital Health. All rights reserved.
          </span>
        </div>
        
        <div class="col-md-4 text-center mb-3 mb-md-0">
          <button @click="goBack" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
          </button>
        </div>
        
        <div class="col-md-4 text-center text-md-end">
          <span class="text-muted small me-2">Powered by</span>
          <a href="#" class="text-decoration-none fw-bold text-primary">ITchanuman</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: "Footer",
  methods: {
    goBack() {
      this.$router.back();
    },
  },
};
</script>

<style scoped>
.footer {
  background-color: #f8f9fa;
  transition: all 0.3s ease;
}

.footer a:hover {
  color: #0d6efd !important;
  text-decoration: underline !important;
}

.btn-outline-primary {
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background-color: #0d6efd;
  color: white !important;
}

@media (max-width: 768px) {
  .footer {
    text-align: center;
  }
}
</style>