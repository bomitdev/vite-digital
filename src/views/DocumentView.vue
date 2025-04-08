<template>
  <div class="container-fluid">
    <div class="container mt-4">
      <div class="d-flex justify-content-center">
        <h1 class="mfc fw-bold">เอกสาร</h1>
        <br />
      </div>
      <hr />
      <div class="container mt-4">
        <ul class="fs-4">
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b6.pdf" target="_blank"
              >คำสั่งแต่งตั้งผู้ประสานCIRT พ.ศ.2567</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b1.pdf" target="_blank"
              >คำสั่งแต่งตั้งผู้ประสานDPO พ.ศ.2567</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b2.pdf" target="_blank"
              >คำสั่งคณะกรรมการดิจิทัลทางการแพทย์ รพ.ชานุมาน</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b3.pdf" target="_blank">
              คณะกรรมการรับเรื่องร้องเรียน</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b4.pdf" target="_blank">
              ข้อตกลงระดับการให้บริการ (Service Level Agreement -SLA)</a
            >
          </li>
          <li class="fs-4">
            <a class="fs-4" href="/src/doc/books/doc_b5.pdf" target="_blank">
              บันทึกรายการประมวลผลข้อมูลส่วนบุคคล โรงพยาบาลชานุมาน
              เพื่อให้เป็นไปตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล (Record of
              Processing Activities: ROPA)</a
            >
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DocumentView",
};
</script>
<style scoped>
.container-fluid {
  background-color: #e3f8ff; /* ตัวอย่าง: สีฟ้าอ่อน */
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
  min-height: 100vh; /* ทำให้ body ครอบคลุมความสูงทั้งหมดของ viewport */
}
.container {
  padding-top: 20px; /* เพิ่ม padding ด้านบน */
  padding-bottom: 20px; /* เพิ่ม padding ด้านล่าง */
  margin-left: auto;
  margin-right: auto;
}
h1 {
  color: purple;
  font-family: Arial, sans-serif;
  font-weight: bold;
}
h2 {
  color: orange;
  font-family: Arial, sans-serif;
  font-weight: bold;
}
.policy {
  text-align: center;
}
</style>
