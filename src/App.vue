<template>
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <RouterLink class="navbar-brand d-flex align-items-center" to="/">
        <img src="./assets/logo-digital.jpg" alt="Logo" class="logo-img me-2" />
      </RouterLink>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <RouterLink class="nav-link" to="/">
              <i class="fas fa-home me-1"></i> HOME
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/dashboard">
              <i class="fas fa-chart-line me-1"></i> DASHBOARD
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/schedule">
              <i class="fas fa-calendar-alt me-1"></i> ตารางเวร IT
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/policy">
              <i class="fas fa-file-contract me-1"></i> นโยบายและระเบียบปฏิบัติ
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/document">
              <i class="fas fa-file-alt me-1"></i> ข่าวประกาศ
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/pdpa">
              <i class="fas fa-shield-alt me-1"></i> PDPA
            </RouterLink>
          </li>
          <li class="nav-item">
            <RouterLink class="nav-link" to="/sla">
              <i class="fas fa-handshake me-1"></i> SLA
            </RouterLink>
          </li>

          <!-- Dropdown Menu -->
          <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle d-flex align-items-center"
              href="#"
              id="mophDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <i class="fas fa-link me-1"></i> LINK-CENTER
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end"
              aria-labelledby="mophDropdown"
            >
              <li><h6 class="dropdown-header">ระบบ MOPH</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://cvp1.moph.go.th/accountcenter/"
                  target="_blank"
                >
                  <i class="fas fa-user-circle fa-fw me-2"></i> Moph Account
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://cvp1.moph.go.th/dashboard/"
                  target="_blank"
                >
                  <i class="fas fa-tachometer-alt fa-fw me-2"></i>
                  Moph Dashboard
                </a>
              </li>

              <li>
                <a
                  class="dropdown-item"
                  href="https://cvp1.moph.go.th/appointment/"
                  target="_blank"
                >
                  <i class="fas fa-calendar-check fa-fw me-2"></i> Moph
                  Appointment
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://phr1.moph.go.th/dashboard/"
                  target="_blank"
                >
                  <i class="fas fa-notes-medical fa-fw me-2"></i> Moph PHR
                </a>
              </li>

              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบ Claim</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://claim-nhso.moph.go.th/nhso/"
                  target="_blank"
                >
                  <i class="fas fa-file-invoice-dollar fa-fw me-2"></i> Moph
                  Claim
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://fdh.moph.go.th/hospital/"
                  target="_blank"
                >
                  <i class="fas fa-file-invoice-dollar fa-fw me-2"></i>
                  Financial Data Hub
                </a>
              </li>

              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบพิสูจน์ตัวตน</h6></li>

              <li>
                <a
                  class="dropdown-item"
                  href="https://phr1.moph.go.th/idpadmin/"
                  target="_blank"
                >
                  <i class="fas fa-file-invoice-dollar fa-fw me-2"></i> MOPH IDP
                  Center (ekyc)
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://provider.id.th/"
                  target="_blank"
                >
                  <i class="fas fa-id-card fa-fw me-2"></i> Provider ID
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://moph.id.th/login"
                  target="_blank"
                >
                  <i class="fas fa-id-badge fa-fw me-2"></i> Health ID
                </a>
              </li>

              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบบริการ</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://moph-refer.inet.co.th/"
                  target="_blank"
                >
                  <i class="fas fa-exchange-alt fa-fw me-2"></i> Moph Refer
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="http://192.168.2.11:8080"
                  target="_blank"
                >
                  <i class="fas fa-procedures fa-fw me-2"></i> Smart Refer
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://connect.moph.go.th/pher-plus/#/login"
                  target="_blank"
                >
                  <i class="fas fa-network-wired fa-fw me-2"></i> IS Online
                </a>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบประเมิน</h6></li>

              <li>
                <a
                  class="dropdown-item"
                  href="https://bdh-service.moph.go.th/smarthosp-quest/"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i> ระบบประเมิน
                  รพ.อัจฉริยะ
                </a>
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://hs4.hss.moph.go.th/hs4_67/"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i> ระบบประเมินมาตรฐานระบบบริการสุขภาพ HS4
                </a>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li><h6 class="dropdown-header">ระบบอื่นๆ</h6></li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://learning.kaorag.com/login"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>พลเมืองดิจิทัล</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://buddy-care.org"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>สอน.บัดดี้</a
                >
              </li>

              <li>
                <a
                  class="dropdown-item"
                  href="https://gppc-app.pdpc.or.th/signin"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>GPPC</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://tdga.dga.or.th/"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>TDGA</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://mcs.inet.co.th/"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>CSOC</a
                >
              </li>
              <li>
                <a
                  class="dropdown-item"
                  href="https://play.mooc.ncsa.or.th/login?src=timeout"
                  target="_blank"
                  ><i class="fas fa-procedures fa-fw me-2"></i>MOOC</a
                >
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <RouterView />
  <Footer />
</template>

<script>
import Footer from "./components/Footer.vue";
import { library } from "@fortawesome/fontawesome-svg-core";
import { fas } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

library.add(fas);

export default {
  components: {
    Footer,
    FontAwesomeIcon,
  },
};
</script>

<style scoped>
.navbar {
  background: linear-gradient(135deg, #0e965d 0%, #0a7a4a 100%);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 0.8rem 1rem;
}

.navbar-brand {
  font-weight: 600;
  font-size: 1.2rem;
  transition: all 0.3s ease;
}

.brand-text {
  font-family: "Kanit", sans-serif;
  color: white;
}

.logo-img {
  height: 50px;
  width: auto;
  transition: transform 0.3s ease;
}

.navbar-brand:hover .logo-img {
  transform: scale(1.05);
}

.nav-link {
  color: rgba(255, 255, 255, 0.9) !important;
  font-family: "Kanit", sans-serif;
  font-weight: 500;
  font-size: 1rem;
  padding: 0.5rem 1rem;
  margin: 0 0.1rem;
  border-radius: 4px;
  transition: all 0.3s ease;
  position: relative;
}

.nav-link:hover,
.nav-link:focus,
.nav-link.router-link-active {
  color: white !important;
  background-color: rgba(255, 255, 255, 0.15);
}

.nav-link.router-link-active::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 1rem;
  right: 1rem;
  height: 2px;
  background-color: white;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding: 0.5rem 0;
  max-height: 70vh;
  overflow-y: auto;
}

.dropdown-item {
  padding: 0.5rem 1.5rem;
  font-family: "Kanit", sans-serif;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  color: #0e965d !important;
}

.dropdown-header {
  font-family: "Kanit", sans-serif;
  font-weight: 600;
  color: #6c757d;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.navbar-toggler {
  border: none;
  padding: 0.5rem;
}

.navbar-toggler:focus {
  box-shadow: none;
}

@media (max-width: 991.98px) {
  .navbar-collapse {
    padding: 1rem 0;
    background-color: rgba(14, 150, 93, 0.98);
    border-radius: 0 0 10px 10px;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
  }

  .nav-link {
    margin: 0.2rem 0;
    padding: 0.75rem 1rem;
  }

  .dropdown-menu {
    background-color: rgba(255, 255, 255, 0.95);
    margin: 0.5rem 0;
    box-shadow: none;
  }
}
</style>
