<template>
  <div class="p-3 mb-2 bg-info-subtle text-dark">
    <div class="container-fluid">
      <br />
      <div class="home">
        <div
          id="carouselExample"
          class="carousel slide"
          data-bs-ride="carousel"
        >
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img
                src="/src/components/img/digital3.png"
                class="d-block w-100"
                alt="digital3"
              />
            </div>
            <div class="carousel-item active">
              <img
                src="/src/components/img/digital2.png"
                class="d-block w-100"
                alt="digital2"
              />
            </div>
            <div class="carousel-item active">
              <img
                src="/src/components/img/digital.png"
                class="d-block w-100"
                alt="digital"
              />
            </div>
          </div>
          <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#carouselExample"
            data-bs-slide="prev"
          >
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#carouselExample"
            data-bs-slide="next"
          >
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {};
</script>

<style scoped>
.container-fluid {
  background-color: #e3f8ff; /* ตัวอย่าง: สีฟ้าอ่อน */
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  min-height: 0vh; /* ทำให้ body ครอบคลุมความสูงทั้งหมดของ viewport */
}

.container {
  text-align: center;
  padding-top: 20px; /* เพิ่ม padding ด้านบน */
  padding-bottom: 20px; /* เพิ่ม padding ด้านล่าง */
}

.home {
  text-align: center;
}

.carousel.slide {
  max-width: 70%; /* เพิ่มขนาด Carousel */
  margin: 0 auto; /* จัดให้อยู่ตรงกลาง */
}

.carousel-inner img {
  max-height: 1000px; /* จำกัดความสูงของภาพ */
  object-fit: cover; /* ครอบคลุมพื้นที่ของ Carousel โดยไม่ผิดเพี้ยน */
}
h1 {
  color: blue;
  font-weight: bold;
  font-family: Arial, sans-serif;
}
</style>
