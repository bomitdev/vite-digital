<template>
  <div class="login-wrapper vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="blob blob-purple"></div>
    <div class="blob blob-blue"></div>

    <div class="card login-card border-0 shadow-xl overflow-hidden">
      <div class="row g-0">
        <div
          class="col-lg-5 d-none d-lg-flex flex-column justify-content-center align-items-center bg-purple-gradient p-5 text-white"
        >
          <img src="/src/assets/digital-logo.png" alt="Logo" width="300" class="mb-1 drop-shadow" />
        </div>

        <div class="col-lg-7 p-4 p-md-5 bg-white">
          <div class="mb-4 d-lg-none text-center">
            <img src="/src/assets/digital-logo.png" alt="Logo" width="60" class="mb-3" />
          </div>

          <h3 class="fw-bold text-dark mb-1">เข้าสู่ระบบ</h3>

          <p class="text-muted small">
            ใช้ Username และ Password ของ *** HOSOffice *** ในการใช้งานระบบ
          </p>

          <form @submit.prevent="handleLogin">
            <div class="mb-3">
              <label class="form-label fw-semibold small text-secondary">ชื่อผู้ใช้งาน</label>
              <div class="input-group-modern">
                <i class="bi bi-person ms-3"></i>
                <input
                  v-model="form.username"
                  type="text"
                  class="form-control-modern"
                  placeholder="Username"
                  required
                />
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold small text-secondary">รหัสผ่าน</label>
              <div class="input-group-modern">
                <i class="bi bi-lock ms-3"></i>
                <input
                  v-model="form.password"
                  type="password"
                  class="form-control-modern"
                  placeholder="Password"
                  required
                />
              </div>
            </div>

            <transition name="fade">
              <div v-if="errorMessage" class="error-box mb-3">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <span>{{ errorMessage }}</span>
              </div>
            </transition>

            <div class="d-flex justify-content-between align-items-center mb-4">
              <a
                href="#"
                class="text-secondary small text-decoration-none hover-purple"
                @click.prevent="handleForgotPassword"
                >ลืมรหัสผ่าน?</a
              >
            </div>

            <button type="submit" class="btn-gradient w-100 py-3 fw-bold mb-4" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              {{ loading ? 'กำลังตรวจสอบ...' : 'ลงชื่อเข้าใช้' }}
            </button>

            <div class="text-center">
              <router-link to="/" class="back-link">
                <i class="bi bi-arrow-left me-2"></i>กลับสู่หน้าหลัก
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  username: '',
  password: ''
});

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.post('/backend/login.php', {
      username: form.username,
      password: form.password
    });

    if (response.data.success) {
      localStorage.setItem('user_token', response.data.token);
      localStorage.setItem('user_name', response.data.user.name);
      localStorage.setItem('last_activity', Date.now());

      Swal.fire({
        icon: 'success',
        title: 'เข้าสู่ระบบสำเร็จ',
        text: `สวัสดีคุณ ${response.data.user.name}`,
        timer: 2000,
        showConfirmButton: false,
        background: '#fff',
        iconColor: '#6f42c1'
      }).then(() => {
        router.push('/home-backoffice');
      });
    } else {
      errorMessage.value = response.data.message;
      Swal.fire({
        icon: 'warning',
        title: 'ไม่สามารถเข้าใช้งานได้',
        text: response.data.message,
        confirmButtonColor: '#6f42c1'
      });
    }
  } catch (error) {
    let msg = error.response?.data?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
    errorMessage.value = msg;
    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg, confirmButtonColor: '#d33' });
  } finally {
    loading.value = false;
  }
};

const handleForgotPassword = async () => {
  const { value: cid } = await Swal.fire({
    title: 'ลืมรหัสผ่าน',
    input: 'text',
    inputLabel: 'กรุณากรอกเลขประจำตัวประชาชน 13 หลัก',
    inputPlaceholder: 'เลขประจำตัวประชาชน',
    showCancelButton: true,
    confirmButtonText: 'ส่งข้อมูล',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#6f42c1',
    inputValidator: (value) => {
      if (!value) {
        return 'กรุณากรอกข้อมูล!';
      }
      if (value.length !== 13 || !/^\d+$/.test(value)) {
        return 'กรุณากรอกเลข 13 หลักให้ถูกต้อง';
      }
    }
  });

  if (cid) {
    Swal.fire({
      title: 'กำลังตรวจสอบข้อมูล...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    try {
      const response = await axios.post('/backend/forgot_password.php', { cid });
      if (response.data.success) {
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ',
          text: response.data.message,
          confirmButtonColor: '#6f42c1'
        });
      }
    } catch (error) {
      let msg = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
      Swal.fire({
        icon: 'error',
        title: 'ผิดพลาด',
        text: msg,
        confirmButtonColor: '#d33'
      });
    }
  }
};
</script>

<style scoped>
/* พื้นหลังแบบ Modern */
.login-wrapper {
  background-color: #f0f2f5;
  position: relative;
  overflow: hidden;
}

.blob {
  position: absolute;
  width: 500px;
  height: 500px;
  background: linear-gradient(135deg, rgba(111, 66, 193, 0.2) 0%, rgba(0, 123, 255, 0.2) 100%);
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
}
.blob-purple {
  top: -100px;
  right: -100px;
}
.blob-blue {
  bottom: -100px;
  left: -100px;
}

/* การ์ดแบบใหม่ */
.login-card {
  z-index: 1;
  width: 1000px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
}

.bg-purple-gradient {
  background: linear-gradient(135deg, #6f42c1 0%, #4a2d81 100%);
}

.drop-shadow {
  filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.2));
}

/* Input แบบใหม่ */
.input-group-modern {
  display: flex;
  align-items: center;
  background-color: #f3f6f9;
  border: 2px solid transparent;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.input-group-modern:focus-within {
  border-color: #6f42c1;
  background-color: #fff;
  box-shadow: 0 0 0 4px rgba(111, 66, 193, 0.1);
}

.form-control-modern {
  background: transparent;
  border: none;
  padding: 12px 15px;
  width: 100%;
  outline: none;
  font-size: 1rem;
}

/* ปุ่มแบบไล่สี */
.btn-gradient {
  background: linear-gradient(to right, #6f42c1, #59359a);
  border: none;
  color: white;
  border-radius: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

.btn-gradient:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
}

/* Error Box */
.error-box {
  background-color: #fff5f5;
  color: #e53e3e;
  padding: 12px;
  border-radius: 10px;
  border-left: 4px solid #e53e3e;
  font-size: 0.85rem;
}

.back-link {
  color: #6c757d;
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.2s;
}
.back-link:hover {
  color: #6f42c1;
}

/* Animation */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.hover-purple:hover {
  color: #6f42c1 !important;
}
</style>
