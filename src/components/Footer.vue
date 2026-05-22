<template>
  <footer class="footer py-4 mt-auto">
    <div class="container">
      <div class="row align-items-center gy-3">
        <!-- Brand / Links -->
        <div class="col-md-5 text-center text-md-start">
          <div class="d-flex flex-column align-items-center align-items-md-start">
            <a
              href="https://hosoffice-chanuman.moph.go.th"
              class="footer-link d-flex align-items-center gap-2 mb-2"
              target="_blank"
            >
              <div class="icon-box">
                <i class="fas fa-hospital"></i>
              </div>
              <span class="fw-bold gradient-text">โรงพยาบาลชานุมาน</span>
            </a>
            <p class="text-muted small mb-0 opacity-75">
              &copy; 2025 Digital Health. All rights reserved.
            </p>
          </div>
        </div>

        <!-- Navigation Action -->
        <div class="col-md-3 text-center">
          <button @click="goBack" class="btn btn-back rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
          </button>
        </div>

        <!-- Credits -->
        <div class="col-md-4 text-center text-md-end">
          <div
            class="d-flex align-items-center justify-content-center justify-content-md-end gap-2"
          >
            <span class="text-muted small">Powered by</span>
            <a href="#" class="credit-badge shadow-sm">
              <i class="fas fa-code me-1"></i> ITchanuman
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'Footer',
  methods: {
    goBack() {
      this.$router.back();
    }
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');

.footer {
  font-family: 'Sarabun', sans-serif;
  background-color: white;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.02);
  position: relative;
  z-index: 10;
}

/* Links */
.footer-link {
  text-decoration: none;
  transition: transform 0.2s ease;
}
.footer-link:hover {
  transform: translateX(4px);
}
.footer-link:hover .icon-box {
  background: linear-gradient(135deg, #6f42c1, #8e24aa);
  color: white;
  border-color: transparent;
}

.icon-box {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8f9fa;
  border-radius: 8px;
  color: #6f42c1;
  transition: all 0.3s ease;
}

.gradient-text {
  background: linear-gradient(90deg, #2c3e50, #4ca1af);

  -webkit-text-fill-color: transparent;
  font-size: 1.1rem;
}

/* Buttons */
.btn-back {
  background-color: white;
  border: 1px solid #e9ecef;
  color: #6c757d;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.btn-back:hover {
  background-color: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
  color: #212529;
}

/* Credit Badge */
.credit-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  background: linear-gradient(135deg, #2193b0, #6dd5ed);
  color: white !important;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}
.credit-badge:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 4px 15px rgba(33, 147, 176, 0.3);
}

@media (max-width: 768px) {
  .footer {
    text-align: center;
    padding-bottom: 80px; /* Space for mobile nav if applicable */
  }
}
</style>
