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

            <div class="position-relative text-center mb-4">
              <hr class="text-secondary">
              <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">หรือ</span>
            </div>

            <a :href="`${backendUrl}/vue-app/vite-digital/login-providerid/index.php`" class="btn-provider-id w-100 py-3 fw-bold mb-4 d-flex align-items-center justify-content-center text-decoration-none">
              <img :src="`${backendUrl}/vue-app/vite-digital/login-providerid/assets/img/provider_logo.png`" alt="Provider ID Logo" style="height: 36px; width: auto;" class="me-3" />
              เข้าสู่ระบบด้วย Provider ID
            </a>

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
import { reactive, ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const errorMessage = ref('');
const backendUrl = import.meta.env.VITE_BACKEND_URL || '';

const form = reactive({
  username: '',
  password: ''
});

onMounted(() => {
  if (route.query.token) {
    localStorage.setItem('user_token', route.query.token);
    localStorage.setItem('user_name', decodeURIComponent(route.query.name || ''));
    localStorage.setItem('last_activity', Date.now());

    Swal.fire({
      icon: 'success',
      title: 'เข้าสู่ระบบสำเร็จ',
      text: `สวัสดีคุณ ${decodeURIComponent(route.query.name || '')}`,
      timer: 2000,
      showConfirmButton: false,
      background: '#fff',
      iconColor: '#6f42c1'
    }).then(() => {
      router.replace('/home-backoffice');
    });
  } else if (route.query.error) {
    let errorMsg = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ Provider ID';
    if (route.query.error === 'not_found') {
      errorMsg = 'ไม่พบข้อมูลบุคลากรในระบบ HOSOffice ของเรา (ไม่มีสิทธิ์เข้าใช้งาน)';
      if (route.query.debug_cid) {
        errorMsg += `\n(ข้อมูลอ้างอิงจากหมอพร้อม: ${route.query.debug_cid})`;
      }
    } else if (route.query.error === 'no_cid') {
      errorMsg = 'ไม่พบเลขบัตรประชาชนจากระบบ MOPH';
    } else if (route.query.error === 'profile_failed') {
      errorMsg = 'ไม่สามารถดึงข้อมูลส่วนตัวจาก MOPH ได้';
    }
    
    Swal.fire({
      icon: 'error',
      title: 'ไม่สามารถเข้าใช้งานได้',
      text: errorMsg,
      confirmButtonColor: '#d33'
    }).then(() => {
      // Clear error parameter from URL without refreshing
      router.replace('/login');
    });
  }
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
      if (response.data.require_otp) {
        // --- Flow OTP ---
        let currentTempToken = response.data.temp_token;
        let isTotp = response.data.is_totp || false;
        
        const showOTPDialog = async (token) => {
          const expiryTime = Date.now() + (1 * 60 * 1000); // 1 minute
          let timerInterval;

          let titleText = isTotp ? 'ยืนยันรหัส Authenticator' : 'ยืนยันรหัส OTP';
          let messageHtml = isTotp 
            ? 'กรุณาเปิดแอป <b>Google Authenticator</b><br/>และกรอกรหัส 6 หลักเพื่อเข้าสู่ระบบ'
            : 'ส่งรหัส OTP ไปยังแอปหมอพร้อมเรียบร้อยแล้ว<br/>กรุณากรอกรหัส 6 หลักเพื่อเข้าสู่ระบบ';
          
          let timerHtml = isTotp 
            ? '' 
            : '<div class="text-danger fw-bold mb-3">เหลือเวลา: <span id="otp-timer">01:00</span> นาที</div>';

          const result = await Swal.fire({
            title: titleText,
            html: `
              <div class="mb-3 text-muted" style="font-size: 0.95rem;">
                ${messageHtml}
              </div>
              ${timerHtml}
              <div class="d-flex justify-content-center gap-2 mb-3" id="otp-input-container">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
                <input type="text" class="form-control text-center otp-box fs-4 fw-bold shadow-sm" maxlength="1" style="width: 45px; height: 55px;" autocomplete="off" inputmode="numeric">
              </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'ยืนยัน',
            denyButtonText: 'ส่งใหม่',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#6f42c1',
            denyButtonColor: '#10b981',
            didOpen: () => {
              const timerDisplay = document.getElementById('otp-timer');
              const denyBtn = Swal.getDenyButton();
              
              // ซ่อนปุ่มส่งใหม่ตอนเริ่มต้น
              if (denyBtn) {
                denyBtn.style.display = 'none';
              }

              // จัดการ Focus และการพิมพ์ใน 6 ช่อง
              const otpBoxes = document.querySelectorAll('.otp-box');
              if (otpBoxes.length > 0) otpBoxes[0].focus();
              
              otpBoxes.forEach((box, index) => {
                box.addEventListener('keydown', (e) => {
                  if (e.key === 'Backspace') {
                    if (box.value === '' && index > 0) {
                      otpBoxes[index - 1].focus();
                      otpBoxes[index - 1].value = '';
                    }
                  } else if (e.key === 'ArrowLeft' && index > 0) {
                    otpBoxes[index - 1].focus();
                  } else if (e.key === 'ArrowRight' && index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                  }
                });
                
                box.addEventListener('input', (e) => {
                  box.value = box.value.replace(/[^0-9]/g, ''); // บังคับตัวเลขเท่านั้น
                  if (box.value !== '' && index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                  }
                  
                  // เช็คว่ากรอกครบ 6 ช่องหรือยัง ถ้าครบให้กดยืนยันอัตโนมัติ
                  let isAllFilled = true;
                  otpBoxes.forEach(b => {
                    if (b.value === '') isAllFilled = false;
                  });
                  if (isAllFilled) {
                    const confirmBtn = Swal.getConfirmButton();
                    if (confirmBtn) confirmBtn.click();
                  }
                });
                
                box.addEventListener('paste', (e) => {
                  e.preventDefault();
                  const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                  for (let i = 0; i < pastedData.length; i++) {
                    if (otpBoxes[i]) {
                      otpBoxes[i].value = pastedData[i];
                      if (i < 5) otpBoxes[i + 1].focus();
                      else otpBoxes[5].focus();
                    }
                  }
                  
                  // ถ้าวางรหัสมาครบ 6 ตัว ให้กดยืนยันอัตโนมัติ
                  if (pastedData.length === 6) {
                    const confirmBtn = Swal.getConfirmButton();
                    if (confirmBtn) confirmBtn.click();
                  }
                });
              });

              timerInterval = setInterval(() => {
                if (isTotp) {
                   clearInterval(timerInterval);
                   return;
                }
                const now = Date.now();
                const distance = expiryTime - now;

                if (distance <= 0) {
                  clearInterval(timerInterval);
                  if (timerDisplay) timerDisplay.innerHTML = "หมดเวลา!";
                  Swal.showValidationMessage('รหัส OTP หมดอายุแล้ว กรุณากดปุ่ม "ส่งใหม่"');
                  const confirmBtn = Swal.getConfirmButton();
                  if (confirmBtn) confirmBtn.disabled = true;
                  
                  // แสดงปุ่มส่งใหม่เมื่อหมดเวลา
                  if (denyBtn) {
                    denyBtn.style.display = 'inline-block';
                  }
                } else {
                  const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                  const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                  if (timerDisplay) {
                    timerDisplay.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                  }
                }
              }, 1000);
            },
            willClose: () => {
              clearInterval(timerInterval);
            },
            preConfirm: async () => {
              const otpBoxes = document.querySelectorAll('.otp-box');
              let otp = '';
              otpBoxes.forEach(box => otp += box.value);
              
              if (!otp || otp.length !== 6) {
                Swal.showValidationMessage('กรุณากรอกรหัส OTP!');
                return false;
              }
              if (otp.length !== 6 || !/^\d+$/.test(otp)) {
                Swal.showValidationMessage('กรุณากรอกตัวเลข 6 หลัก');
                return false;
              }

              try {
                const otpResponse = await axios.post('/backend/verify_otp.php', {
                  temp_token: token,
                  otp: otp,
                  is_totp: isTotp
                });
                
                if (otpResponse.data.success) {
                  return otpResponse.data;
                } else {
                  Swal.showValidationMessage(otpResponse.data.message);
                  return false;
                }
              } catch (error) {
                let msg = error.response?.data?.message || 'เกิดข้อผิดพลาดในการตรวจสอบ OTP';
                Swal.showValidationMessage(msg);
                return false;
              }
            }
          });

          if (result.isConfirmed) {
            const data = result.value;
            localStorage.setItem('user_token', data.token);
            localStorage.setItem('user_name', data.user.name);
            localStorage.setItem('last_activity', Date.now());

            Swal.fire({
              icon: 'success',
              title: 'เข้าสู่ระบบสำเร็จ',
              text: `สวัสดีคุณ ${data.user.name}`,
              timer: 2000,
              showConfirmButton: false,
              background: '#fff',
              iconColor: '#6f42c1'
            }).then(() => {
              router.push('/home-backoffice');
            });
          } else if (result.isDenied) {
            // Resend OTP by calling handleLogin again
            handleLogin();
          }
        };

        showOTPDialog(currentTempToken);
      } else {
        // --- Normal Flow (if OTP is bypassed for some reason) ---
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
      }
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

.btn-provider-id {
  background: white;
  border: 2px solid #10b981;
  color: #10b981;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.btn-provider-id:hover {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}
</style>
