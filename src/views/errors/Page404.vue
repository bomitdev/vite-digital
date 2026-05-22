<template>
  <div class="error-container">
    <div class="error-content">
      <div class="icon-box">
        <i class="fas fa-hospital-user"></i>
        <span class="number">404</span>
      </div>

      <h1 class="title">ไม่พบหน้าที่คุณต้องการ</h1>
      <p class="message">
        ขออภัยครับ หน้าที่คุณพยายามเข้าถึงอาจถูกลบ ย้าย หรือไม่มีอยู่จริง <br />
        กรุณาตรวจสอบ URL อีกครั้ง หรือกดปุ่มด้านล่างเพื่อกลับไปหน้าหลัก
      </p>

      <div class="action-buttons">
        <RouterLink to="/" class="btn btn-home">
          <i class="fas fa-home me-2"></i> กลับหน้าแรก
        </RouterLink>
        <button @click="$router.go(-1)" class="btn btn-back">
          <i class="fas fa-arrow-left me-2"></i> ย้อนกลับ
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFoundView'
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap');

.error-container {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Kanit', sans-serif;
  background-color: #f8f9fa;
  padding: 20px;
}

.error-content {
  text-align: center;
  max-width: 600px;
  background: white;
  padding: 3rem;
  border-radius: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.icon-box {
  position: relative;
  margin-bottom: 2rem;
  color: #0e965d;
}

.icon-box i {
  font-size: 5rem;
  opacity: 0.2;
}

.number {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 4rem;
  font-weight: 800;
  color: #0e965d;
  letter-spacing: 5px;
}

.title {
  font-size: 2rem;
  font-weight: 600;
  color: #2d3436;
  margin-bottom: 1rem;
}

.message {
  color: #636e72;
  line-height: 1.8;
  margin-bottom: 2.5rem;
}

.action-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btn {
  padding: 0.8rem 1.5rem;
  border-radius: 50px;
  font-weight: 500;
  transition: all 0.3s ease;
  text-decoration: none;
  border: none;
}

.btn-home {
  background: linear-gradient(135deg, #0e965d 0%, #086d44 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(14, 150, 93, 0.3);
}

.btn-home:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(14, 150, 93, 0.4);
  color: white;
}

.btn-back {
  background-color: #e9ecef;
  color: #495057;
}

.btn-back:hover {
  background-color: #dee2e6;
  transform: translateY(-3px);
}

/* ปรับแต่งสำหรับมือถือ */
@media (max-width: 576px) {
  .error-content {
    padding: 2rem 1.5rem;
  }

  .number {
    font-size: 3rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
